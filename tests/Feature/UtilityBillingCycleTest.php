<?php

namespace Tests\Feature;

use App\Models\Lot;
use App\Models\User;
use App\Models\UtilityBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UtilityBillingCycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_cycle_status_endpoint_returns_cycle_projections(): void
    {
        $response = $this->getJson('/admin/api/billing/cycle-status?type=electricity');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'has_active_cycle',
            'next_cycle' => [
                'label',
                'period',
                'suggested_prev_date',
                'suggested_curr_date',
                'suggested_due_date',
                'already_generated',
            ],
            'settings'
        ]);
    }

    public function test_duplicate_cycle_generation_is_prevented_gracefully(): void
    {
        Role::create(['name' => 'Resident']);
        $lot = Lot::create(['block' => '1', 'lot_number' => '1', 'provider_managed' => false]);
        $user = User::factory()->create();
        $user->assignRole('Resident');
        $lot->users()->attach($user);

        // First generation
        $res1 = $this->postJson('/admin/api/billing/generate', [
            'type' => 'electricity',
            'rate' => 13,
            'penalty' => 5,
            'prev_date' => '2026-10-27',
            'curr_date' => '2026-11-01',
            'due_date' => '2026-11-03',
        ]);
        $res1->assertStatus(200);
        $res1->assertJson([
            'success' => true,
            'generated_count' => 1,
        ]);

        // Duplicate attempt for the exact same period
        $res2 = $this->postJson('/admin/api/billing/generate', [
            'type' => 'electricity',
            'rate' => 13,
            'penalty' => 5,
            'prev_date' => '2026-10-27',
            'curr_date' => '2026-11-01',
            'due_date' => '2026-11-03',
        ]);
        $res2->assertStatus(200);
        $res2->assertJson([
            'success' => true,
            'already_exists' => true,
            'generated_count' => 0,
        ]);
        $this->assertStringContainsString('already active', $res2->json('message'));
    }

    public function test_safe_meter_reading_baseline_carryover(): void
    {
        Role::create(['name' => 'Resident']);
        $lot = Lot::create(['block' => '2', 'lot_number' => '1', 'provider_managed' => false]);
        $user = User::factory()->create();
        $user->assignRole('Resident');
        $lot->users()->attach($user);

        // Existing bill for October with 450 kWh reading
        UtilityBill::create([
            'type' => 'electricity',
            'lot_id' => $lot->id,
            'resident_id' => $user->id,
            'billing_period' => '2026-10-01',
            'previous_reading' => 300,
            'current_reading' => 450,
            'usage_value' => 150,
            'amount' => 1950,
            'status' => 'paid',
        ]);

        // Generate November cycle
        $res = $this->postJson('/admin/api/billing/generate', [
            'type' => 'electricity',
            'rate' => 13,
            'penalty' => 5,
            'prev_date' => '2026-10-28',
            'curr_date' => '2026-11-28',
            'due_date' => '2026-12-10',
        ]);
        $res->assertStatus(200);

        $newBill = UtilityBill::where('lot_id', $lot->id)->whereDate('billing_period', '2026-11-01')->first();
        $this->assertNotNull($newBill);
        $this->assertEquals(450, $newBill->previous_reading);
    }

    public function test_resident_can_access_unpaid_bill_pdf_and_history_shows_unpaid_bills(): void
    {
        Role::firstOrCreate(['name' => 'Resident']);
        $lot = Lot::create(['block' => '2', 'lot_number' => '4', 'provider_managed' => false]);
        $user = User::factory()->create();
        $user->assignRole('Resident');
        $lot->users()->attach($user);

        $unpaidBill = UtilityBill::create([
            'type' => 'electricity',
            'lot_id' => $lot->id,
            'resident_id' => $user->id,
            'billing_period' => '2026-11-01',
            'previous_reading' => 100,
            'current_reading' => 200,
            'usage_value' => 100,
            'amount' => 1300,
            'previous_balance' => 200,
            'status' => 'unpaid',
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        // Resident accesses history view and sees the unpaid bill
        $historyRes = $this->actingAs($user)->get('/resident/bills/history/electricity');
        $historyRes->assertStatus(200);
        $historyRes->assertSee('Unpaid');

        // Resident accesses PDF for unpaid bill
        $pdfRes = $this->actingAs($user)->get("/resident/bills/history/electricity/pdf/{$unpaidBill->id}");
        $pdfRes->assertStatus(200);
        $pdfRes->assertHeader('Content-Type', 'application/pdf');
    }
}
