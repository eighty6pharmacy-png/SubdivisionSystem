<?php

namespace Tests\Feature;

use App\Models\Lot;
use App\Models\User;
use App\Models\UtilityBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResidentMultiLotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('Admin', 'web');
        Role::findOrCreate('Resident', 'web');
    }

    public function test_admin_can_assign_multiple_lots_to_resident(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $resident = User::factory()->create([
            'email' => 'multi.resident@gmail.com',
            'contact_number' => '09171234567',
        ]);
        $resident->assignRole('Resident');

        $lot1 = Lot::create(['block' => '1', 'lot_number' => '2', 'status' => 'Available']);
        $lot2 = Lot::create(['block' => '3', 'lot_number' => '5', 'status' => 'Available']);

        $response = $this->actingAs($admin)->putJson("/admin/users/{$resident->id}", [
            'first_name' => $resident->first_name,
            'middle_name' => 'M.',
            'last_name' => $resident->last_name,
            'email' => $resident->email,
            'contact_number' => $resident->contact_number,
            'role' => 'Resident',
            'lots_payload' => [
                ['block' => '1', 'lot' => '2'],
                ['block' => '3', 'lot' => '5'],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertCount(2, $resident->fresh()->lots);
        $this->assertTrue($resident->fresh()->lots->contains('id', $lot1->id));
        $this->assertTrue($resident->fresh()->lots->contains('id', $lot2->id));
    }

    public function test_resident_portal_switches_between_lots(): void
    {
        $resident = User::factory()->create([
            'email' => 'resident.lots@example.com',
            'contact_number' => '09179876543',
        ]);
        $resident->assignRole('Resident');

        $lotA = Lot::create(['block' => '2', 'lot_number' => '10', 'status' => 'Occupied']);
        $lotB = Lot::create(['block' => '4', 'lot_number' => '15', 'status' => 'Occupied']);

        $resident->lots()->attach([$lotA->id, $lotB->id]);

        // Create utility bill for lotA
        UtilityBill::create([
            'type' => 'electricity',
            'resident_id' => $resident->id,
            'lot_id' => $lotA->id,
            'amount' => 1500,
            'current_reading' => 100,
            'previous_reading' => 50,
            'usage_value' => 50,
            'status' => 'unpaid',
            'billing_period' => now()->format('Y-m-d'),
        ]);

        // Create utility bill for lotB
        UtilityBill::create([
            'type' => 'electricity',
            'resident_id' => $resident->id,
            'lot_id' => $lotB->id,
            'amount' => 3200,
            'current_reading' => 220,
            'previous_reading' => 120,
            'usage_value' => 100,
            'status' => 'unpaid',
            'billing_period' => now()->format('Y-m-d'),
        ]);

        // Test dashboard view with lotA selected
        $responseLotA = $this->actingAs($resident)->get("/resident/dashboard?lot_id={$lotA->id}");
        $responseLotA->assertOk();
        $responseLotA->assertViewHas('selectedLotId', $lotA->id);
        $elecBillA = $responseLotA->viewData('elecBill');
        $this->assertEquals(1500, (float)$elecBillA['base_amount']);

        // Test dashboard view with lotB selected
        $responseLotB = $this->actingAs($resident)->get("/resident/dashboard?lot_id={$lotB->id}");
        $responseLotB->assertOk();
        $responseLotB->assertViewHas('selectedLotId', $lotB->id);
        $elecBillB = $responseLotB->viewData('elecBill');
        $this->assertEquals(3200, (float)$elecBillB['base_amount']);
    }
}
