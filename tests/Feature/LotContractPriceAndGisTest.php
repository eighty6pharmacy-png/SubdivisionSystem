<?php

namespace Tests\Feature;

use App\Enums\LotStatus;
use App\Enums\ReservationStatus;
use App\Models\BuyerMasterList;
use App\Models\Lot;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LotContractPriceAndGisTest extends TestCase
{
    use RefreshDatabase;

    public function test_lot_can_store_and_retrieve_contract_price(): void
    {
        $lot = Lot::create([
            'block' => '1',
            'lot_number' => '1',
            'status' => LotStatus::Available->value,
            'contract_price' => 2500000.50,
        ]);

        $this->assertEquals(2500000.50, (float) $lot->fresh()->contract_price);
    }

    public function test_gis_api_lots_returns_contract_price_and_reserved_status(): void
    {
        $lot = Lot::create([
            'block' => '2',
            'lot_number' => '5',
            'status' => LotStatus::Reserved->value,
            'contract_price' => 3200000.00,
        ]);

        $buyer = BuyerMasterList::create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria@example.com',
            'lot_id' => $lot->id,
        ]);

        Reservation::create([
            'lot_id' => $lot->id,
            'buyer_master_list_id' => $buyer->id,
            'status' => ReservationStatus::Reserved->value,
            'amount' => 25000,
            'reservation_date' => now(),
        ]);

        $response = $this->getJson('/api/lots');
        $response->assertOk();

        $data = collect($response->json())->firstWhere('block', '2');
        $this->assertNotNull($data);
        $this->assertEquals(3200000.00, $data['contract_price']);
        $this->assertEquals(25000.00, $data['reservation_amount']);
        $this->assertEquals('Reserved', $data['status']);
        $this->assertStringContainsString('Santos', $data['owner']);
    }

    public function test_admin_can_update_lot_details_with_contract_price(): void
    {
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $lot = Lot::create([
            'block' => '3',
            'lot_number' => '8',
            'status' => LotStatus::VacantLot->value,
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/gis/update-lot-details', [
            'block' => '3',
            'lot' => '8',
            'contract_price' => 2750000,
            'lot_area' => 150.5,
            'floor_area' => 85.0,
            'description' => 'Corner Lot Unit',
            'tct_no' => 'TCT-9999',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertEquals(2750000, (float) $lot->fresh()->contract_price);
        $this->assertEquals(150.5, (float) $lot->fresh()->lot_area);
        $this->assertEquals('Corner Lot Unit', $lot->fresh()->description);
    }

    public function test_negative_contract_price_is_rejected(): void
    {
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $lot = Lot::create([
            'block' => '4',
            'lot_number' => '2',
            'status' => LotStatus::VacantLot->value,
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/gis/update-lot-details', [
            'block' => '4',
            'lot' => '2',
            'contract_price' => -500000,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['contract_price']);
    }
}
