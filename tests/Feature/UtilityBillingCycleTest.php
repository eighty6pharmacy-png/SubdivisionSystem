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

    public function test_past_due_bill_applies_penalty_and_supports_partial_then_full_office_payment(): void
    {
        Role::firstOrCreate(['name' => 'Resident']);
        Role::firstOrCreate(['name' => 'Admin']);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $lot = Lot::create(['block' => '3', 'lot_number' => '1', 'provider_managed' => false]);
        $user = User::factory()->create();
        $user->assignRole('Resident');
        $lot->users()->attach($user);

        // Create a past-due bill: base amount 1000, previous balance 200 -> amount_before_due = 1200
        // With 5% penalty -> penalty = 60, amount_after_due = 1260
        $overdueBill = UtilityBill::create([
            'type' => 'electricity',
            'lot_id' => $lot->id,
            'resident_id' => $user->id,
            'billing_period' => '2026-10-01',
            'previous_reading' => 100,
            'current_reading' => 200,
            'usage_value' => 100,
            'amount' => 1000,
            'previous_balance' => 200,
            'status' => 'unpaid',
            'due_date' => now()->subDays(3)->toDateString(),
            'created_at' => '2026-10-05 10:00:00',
        ]);

        $bills = getElectricalBills('2026-10', true, $user->id);
        $billData = collect($bills)->firstWhere('id', $overdueBill->id);

        $this->assertNotNull($billData);
        $this->assertTrue($billData['at_risk']);
        $this->assertEquals(1000.0, $billData['base_amount']);
        $this->assertEquals(200.0, $billData['previous_balance']);
        $this->assertEquals(1200.0, $billData['amount_before_due']);
        $this->assertEquals(60.0, $billData['applied_penalty']);
        $this->assertEquals(1260.0, $billData['amount_after_due']);
        $this->assertEquals(1260.0, $billData['amount']);
        $this->assertEquals(1260.0, $billData['remaining_balance']);

        // Record a partial payment of 500
        $partialRes = $this->actingAs($admin)->postJson('/admin/api/billing/pay', [
            'id' => $overdueBill->id,
            'amount' => 500,
            'method' => 'Cash (Office)',
        ]);
        $partialRes->assertStatus(200);
        $partialRes->assertJson([
            'success' => true,
            'status' => 'partially_paid',
            'total_paid' => 500.0,
            'remaining_balance' => 760.0,
        ]);

        $this->assertDatabaseHas('utility_bills', [
            'id' => $overdueBill->id,
            'status' => 'partially_paid',
        ]);

        // Verify getElectricalBills reflects partially_paid status and 760 remaining balance
        $billsAfterPartial = getElectricalBills('2026-10', true, $user->id);
        $billDataPartial = collect($billsAfterPartial)->firstWhere('id', $overdueBill->id);
        $this->assertEquals('partially_paid', $billDataPartial['status']);
        $this->assertEquals(500.0, $billDataPartial['total_paid']);
        $this->assertEquals(760.0, $billDataPartial['remaining_balance']);

        // Record remaining payment of 760 to settle the overdue bill in full
        $fullRes = $this->actingAs($admin)->postJson('/admin/api/billing/pay', [
            'id' => $overdueBill->id,
            'amount' => 760,
            'method' => 'Cash (Office)',
        ]);
        $fullRes->assertStatus(200);
        $fullRes->assertJson([
            'success' => true,
            'status' => 'paid',
            'total_paid' => 1260.0,
            'remaining_balance' => 0.0,
        ]);

        $this->assertDatabaseHas('utility_bills', [
            'id' => $overdueBill->id,
            'status' => 'paid',
        ]);

        $pdfResponse = $this->actingAs($user)->get("/resident/bills/history/electricity/pdf/{$overdueBill->id}");
        $pdfResponse->assertStatus(200);
        $pdfResponse->assertHeader('Content-Type', 'application/pdf');

        $viewHtml = view('resident.bill_pdf', ['bill' => $overdueBill->fresh(['payments', 'user', 'lot'])])->render();
        $this->assertStringContainsString('Payment Date &amp; Time:', $viewHtml);
        $this->assertStringContainsString('Payment Transactions', $viewHtml);
        $this->assertStringContainsString('OFFICIAL RECEIPT &amp; STATEMENT OF ACCOUNT', $viewHtml);

        $historyHtml = $this->actingAs($user)->get('/resident/bills/history/electricity');
        $historyHtml->assertStatus(200);
        $historyHtml->assertSee('Paid');
        $latestPayment = $overdueBill->payments()->latest('payment_date')->first();
        $expectedDate = \Carbon\Carbon::parse($latestPayment->payment_date)->timezone(config('app.timezone', 'Asia/Manila'))->format('M d, Y');
        $historyHtml->assertSee($expectedDate);
    }

    public function test_admin_cycle_page_embeds_cycle_bills_json_for_past_cycle_details(): void
    {
        Role::firstOrCreate(['name' => 'Resident']);
        Role::firstOrCreate(['name' => 'Admin']);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $lot = Lot::create(['block' => '4', 'lot_number' => '2', 'provider_managed' => false]);
        $user = User::factory()->create(['name' => 'October Resident']);
        $user->assignRole('Resident');
        $lot->users()->attach($user);

        $octBill = UtilityBill::create([
            'type' => 'electricity',
            'lot_id' => $lot->id,
            'resident_id' => $user->id,
            'billing_period' => '2026-10-01',
            'previous_reading' => 50,
            'current_reading' => 120,
            'usage_value' => 70,
            'amount' => 910,
            'previous_balance' => 100,
            'status' => 'unpaid',
            'due_date' => '2026-10-15',
            'created_at' => '2026-10-02 09:00:00',
        ]);

        $res = $this->actingAs($admin)->get('/admin/billing?cycle=2026-10');
        $res->assertStatus(200);
        $res->assertSee('id="cycle-bills-json"', false);
        $res->assertSee('October Resident');
        $res->assertSee((string) $octBill->id);
    }
}
