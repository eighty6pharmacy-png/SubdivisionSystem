<?php

namespace Tests\Feature;

use App\Models\BuyerMasterList;
use App\Models\Lot;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DownpaymentCreationValidationTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        return $admin;
    }

    private function createBuyerWithReservation(): array
    {
        $lot = Lot::create(['block' => '1', 'lot_number' => '10']);
        $buyer = BuyerMasterList::create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria@example.test',
            'lot_id' => $lot->id,
            'block_no' => '1',
            'lot_no' => '10',
        ]);
        $reservation = Reservation::create([
            'buyer_id' => $buyer->id,
            'lot_id' => $lot->id,
            'amount' => 20000,
            'reservation_date' => now(),
            'status' => 'Reserved',
        ]);

        return [$buyer, $reservation, $lot];
    }

    public function test_rejects_missing_buyer(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->postJson('/admin/downpayment-fee', [
            'buyer_master_list_id' => null,
            'contract_amount' => 2000000,
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'Please select a buyer from the Master List.']);
    }

    public function test_rejects_non_numeric_or_empty_contract_amount(): void
    {
        $admin = $this->createAdmin();
        [$buyer, $reservation] = $this->createBuyerWithReservation();

        $response = $this->actingAs($admin)->postJson('/admin/downpayment-fee', [
            'buyer_master_list_id' => $buyer->id,
            'reservation_id' => $reservation->id,
            'contract_amount' => 'invalid-abc',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_rejects_negative_contract_amount(): void
    {
        $admin = $this->createAdmin();
        [$buyer, $reservation] = $this->createBuyerWithReservation();

        $response = $this->actingAs($admin)->postJson('/admin/downpayment-fee', [
            'buyer_master_list_id' => $buyer->id,
            'reservation_id' => $reservation->id,
            'contract_amount' => -2000000,
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_rejects_contract_amount_with_less_than_6_digits(): void
    {
        $admin = $this->createAdmin();
        [$buyer, $reservation] = $this->createBuyerWithReservation();

        $response = $this->actingAs($admin)->postJson('/admin/downpayment-fee', [
            'buyer_master_list_id' => $buyer->id,
            'reservation_id' => $reservation->id,
            'contract_amount' => 50000, // 5 digits
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Unit Price (TCP) must be a valid number of at least 6 digits (minimum ₱100,000).'
            ]);
    }

    public function test_accepts_valid_contract_amount_with_at_least_6_digits(): void
    {
        $admin = $this->createAdmin();
        [$buyer, $reservation] = $this->createBuyerWithReservation();

        $response = $this->actingAs($admin)->postJson('/admin/downpayment-fee', [
            'buyer_master_list_id' => $buyer->id,
            'reservation_id' => $reservation->id,
            'contract_amount' => 2000000, // 7 digits (>= 6 digits)
            'dp_percentage' => 20,
            'dpAmount' => 400000,
            'months_to_pay' => 24,
            'penalty_percentage' => 0,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('downpayments', [
            'reservation_id' => $reservation->id,
            'balance' => 400000,
            'months_to_pay' => 24,
        ]);
    }

    public function test_can_create_downpayment_for_buyer_without_prior_reservation(): void
    {
        $admin = $this->createAdmin();
        $buyer = BuyerMasterList::create([
            'first_name' => 'Carlos',
            'last_name' => 'Reyes',
            'email' => 'carlos@example.test',
            'block_no' => '5',
            'lot_no' => '12',
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/downpayment-fee', [
            'buyer_master_list_id' => $buyer->id,
            'block' => '5',
            'lot' => '12',
            'contract_amount' => 1500000,
            'dp_percentage' => 20,
            'dpAmount' => 300000,
            'months_to_pay' => 12,
            'penalty_percentage' => 0,
        ]);

        $response->assertStatus(200);

        // Verify reservation was automatically established and linked to downpayment
        $res = Reservation::where('buyer_id', $buyer->id)->first();
        $this->assertNotNull($res);
        $this->assertEquals('Converted', $res->status);
        $this->assertDatabaseHas('downpayments', [
            'reservation_id' => $res->id,
            'balance' => 300000,
            'months_to_pay' => 12,
        ]);
    }
}
