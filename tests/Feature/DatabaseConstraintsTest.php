<?php

namespace Tests\Feature;

use App\Models\BuyerMasterList;
use App\Models\Downpayment;
use App\Models\DownpaymentHistory;
use App\Models\FinancingPaymentHistory;
use App\Models\FinancingRecord;
use App\Models\Lot;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Models\UtilityBill;
use App\Services\PaymentRecorder;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DatabaseConstraintsTest extends TestCase
{
    use RefreshDatabase;

    private function lot(string $block = '1', string $lotNumber = '1'): Lot
    {
        return Lot::create(['block' => $block, 'lot_number' => $lotNumber]);
    }

    private function buyer(array $attributes = []): BuyerMasterList
    {
        return BuyerMasterList::create(array_merge([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan.'.str()->uuid().'@example.test',
        ], $attributes));
    }

    private function reservation(Lot $lot, string $status = 'Reserved', ?BuyerMasterList $buyer = null): Reservation
    {
        return Reservation::create([
            'lot_id' => $lot->id,
            'buyer_master_list_id' => $buyer?->id,
            'status' => $status,
            'reservation_date' => now(),
            'amount' => 20000,
        ]);
    }

    private function downpayment(Reservation $reservation): Downpayment
    {
        return Downpayment::create([
            'reservation_id' => $reservation->id,
            'amount' => 0,
            'balance' => 30000,
            'due_date' => now()->addMonth(),
            'status' => 'Good Standing',
            'monthly_amortization' => 15000,
            'months_to_pay' => 2,
        ]);
    }

    private function bill(Lot $lot, string $period = '2026-10-01'): UtilityBill
    {
        return UtilityBill::create([
            'type' => 'water',
            'lot_id' => $lot->id,
            'billing_period' => $period,
            'usage_value' => 10,
            'amount' => 1000,
            'status' => 'unpaid',
        ]);
    }

    private function financing(BuyerMasterList $buyer, Lot $lot): FinancingRecord
    {
        return FinancingRecord::create([
            'buyer_master_list_id' => $buyer->id,
            'lot_id' => $lot->id,
            'receivables' => 10000,
            'balance' => 10000,
            'status' => 'Released',
        ]);
    }

    private function admin(): User
    {
        Role::findOrCreate('Admin', 'web');
        $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'secret123']);
        $user->assignRole('Admin');

        return $user;
    }

    public function test_duplicate_lot_is_rejected(): void
    {
        $this->lot('3', '7');

        $this->expectException(UniqueConstraintViolationException::class);
        $this->lot('3', '7');
    }

    public function test_second_active_reservation_on_same_lot_is_rejected(): void
    {
        $lot = $this->lot();
        $this->reservation($lot, 'Reserved');

        $this->expectException(UniqueConstraintViolationException::class);
        $this->reservation($lot, 'Pending');
    }

    public function test_new_reservation_is_allowed_after_previous_one_is_cancelled(): void
    {
        $lot = $this->lot();
        $first = $this->reservation($lot, 'Reserved');
        $first->update(['status' => 'Cancelled']);

        $second = $this->reservation($lot, 'Reserved');

        $this->assertSame(2, Reservation::where('lot_id', $lot->id)->count());
        $this->assertSame('Reserved', $second->fresh()->status);
    }

    public function test_duplicate_bill_for_same_lot_type_and_month_is_rejected(): void
    {
        $lot = $this->lot();
        $this->bill($lot, '2026-10-01');
        $this->bill($lot, '2026-11-01');

        $this->expectException(UniqueConstraintViolationException::class);
        $this->bill($lot, '2026-10-01');
    }

    public function test_same_paymongo_payment_recorded_twice_applies_downpayment_once(): void
    {
        $dp = $this->downpayment($this->reservation($this->lot()));
        $recorder = app(PaymentRecorder::class);

        $first = $recorder->recordDownpaymentPayment($dp, 15000, 'GCash', 'pay_dp_1');
        $second = $recorder->recordDownpaymentPayment($dp, 15000, 'GCash', 'pay_dp_1');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, DownpaymentHistory::where('downpayment_id', $dp->id)->count());
        $dp->refresh();
        $this->assertEquals(15000, $dp->balance);
        $this->assertEquals(15000, $dp->amount);
        $this->assertSame('Good Standing', $dp->status);
    }

    public function test_same_paymongo_payment_recorded_twice_applies_utility_payment_once(): void
    {
        $bill = $this->bill($this->lot());
        $bill->update(['paymongo_checkout_id' => 'cs_1', 'paymongo_checkout_url' => 'https://example.test/cs_1']);
        $recorder = app(PaymentRecorder::class);

        $recorder->recordUtilityPayment($bill, 1000, 'GCash', 'pay_bill_1');
        $recorder->recordUtilityPayment($bill, 1000, 'GCash', 'pay_bill_1');

        $this->assertSame(1, Payment::where('utility_bill_id', $bill->id)->count());
        $this->assertEquals(1000, Payment::where('utility_bill_id', $bill->id)->sum('amount_paid'));
        $bill->refresh();
        $this->assertSame('paid', $bill->status);
        $this->assertNull($bill->paymongo_checkout_id);
    }

    public function test_same_paymongo_payment_recorded_twice_applies_financing_payment_once(): void
    {
        $fin = $this->financing($this->buyer(), $this->lot());
        $recorder = app(PaymentRecorder::class);

        $recorder->recordFinancingPayment($fin, 4000, 'GCash', 'pay_fin_1');
        $recorder->recordFinancingPayment($fin, 4000, 'GCash', 'pay_fin_1');

        $this->assertSame(1, FinancingPaymentHistory::where('financing_record_id', $fin->id)->count());
        $fin->refresh();
        $this->assertEquals(4000, $fin->amount_paid);
        $this->assertEquals(6000, $fin->balance);
    }

    public function test_office_payments_without_paymongo_id_each_write_history(): void
    {
        $fin = $this->financing($this->buyer(), $this->lot());
        $recorder = app(PaymentRecorder::class);

        $recorder->recordFinancingPayment($fin, 4000, 'Office Payment');
        $recorder->recordFinancingPayment($fin, 6000, 'Office Payment');

        $this->assertSame(2, FinancingPaymentHistory::where('financing_record_id', $fin->id)->count());
        $fin->refresh();
        $this->assertEquals(0, $fin->balance);
        $this->assertSame('Cleared', $fin->status);
    }

    public function test_webhook_replayed_twice_records_downpayment_once(): void
    {
        $dp = $this->downpayment($this->reservation($this->lot()));
        $body = json_encode(['data' => ['attributes' => [
            'type' => 'checkout_session.payment.paid',
            'data' => ['attributes' => [
                'reference_number' => 'DP-' . $dp->id,
                'payments' => [[
                    'id' => 'pay_webhook_1',
                    'attributes' => ['amount' => 1500000, 'status' => 'paid', 'source' => ['type' => 'gcash'], 'payment_intent_id' => 'pi_1'],
                ]],
            ]],
        ]]]);
        $timestamp = '1700000000';
        $signature = hash_hmac('sha256', $timestamp . '.' . $body, config('services.paymongo.webhook_secret'));
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PAYMONGO_SIGNATURE' => "t={$timestamp},te={$signature},li=",
        ];

        $this->call('POST', '/api/webhooks/paymongo', [], [], [], $server, $body)->assertOk();
        $this->call('POST', '/api/webhooks/paymongo', [], [], [], $server, $body)->assertOk();

        $this->assertSame(1, DownpaymentHistory::where('downpayment_id', $dp->id)->count());
        $this->assertSame('GCash', DownpaymentHistory::first()->payment_method);
        $this->assertEquals(15000, $dp->fresh()->balance);
    }

    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        $this->call('POST', '/api/webhooks/paymongo', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PAYMONGO_SIGNATURE' => 't=1,te=bad,li=bad',
        ], '{}')->assertStatus(401);
    }

    public function test_archiving_buyer_keeps_downpayment_history(): void
    {
        $buyer = $this->buyer();
        $reservation = $this->reservation($this->lot(), 'Converted', $buyer);
        $dp = $this->downpayment($reservation);
        app(PaymentRecorder::class)->recordDownpaymentPayment($dp, 5000, 'Office Payment');

        $this->actingAs($this->admin())
            ->deleteJson("/admin/buyer-master-list/{$buyer->id}")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('buyers', ['id' => $buyer->id]);
        $this->assertNull($reservation->fresh()->deleted_at);
        $this->assertSame('Converted', $reservation->fresh()->status);
        $this->assertDatabaseHas('downpayments', ['id' => $dp->id]);
        $this->assertSame(1, DownpaymentHistory::where('downpayment_id', $dp->id)->count());
    }

    public function test_restore_brings_back_buyer_user_and_lapsed_reservation(): void
    {
        $lot = $this->lot();
        $buyer = $this->buyer();
        $reservation = Reservation::create([
            'lot_id' => $lot->id,
            'buyer_master_list_id' => $buyer->id,
            'status' => 'Reserved',
            'reservation_date' => now()->subMonth(),
            'deadline_date' => now()->subDays(5)->toDateString(),
            'amount' => 20000,
        ]);
        $user = $buyer->user;
        $user->email = 'juan-restore@example.test';
        $user->password = 'secret123';
        $user->status = 'Active';
        $user->save();
        $admin = $this->admin();

        $this->actingAs($admin)->deleteJson("/admin/buyer-master-list/{$buyer->id}")->assertOk();
        $this->actingAs($admin)
            ->postJson("/admin/buyer-master-list/{$buyer->id}/restore")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNull($buyer->fresh()->deleted_at);
        $this->assertNull($user->fresh()->deleted_at);
        $this->assertNull($reservation->fresh()->deleted_at);
        $this->assertGreaterThanOrEqual(now()->toDateString(), substr((string) $reservation->fresh()->deadline_date, 0, 10));

        $this->actingAs($admin)->get('/admin/buyer-master-list')->assertOk();
        $this->assertNull($buyer->fresh()->deleted_at);
        $this->assertNull($reservation->fresh()->deleted_at);
    }

    public function test_restore_skips_reservation_when_lot_is_taken(): void
    {
        $lot = $this->lot();
        $buyer = $this->buyer();
        $reservation = $this->reservation($lot, 'Reserved', $buyer);
        $admin = $this->admin();
        $this->actingAs($admin)->deleteJson("/admin/buyer-master-list/{$buyer->id}")->assertOk();

        $other = $this->buyer(['first_name' => 'Pedro', 'last_name' => 'Santos']);
        $this->reservation($lot, 'Reserved', $other);

        $this->actingAs($admin)
            ->postJson("/admin/buyer-master-list/{$buyer->id}/restore")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNull($buyer->fresh()->deleted_at);
        $this->assertSame('Cancelled', $reservation->fresh()->status);
        $this->assertNull($reservation->fresh()->deleted_at);
    }

    public function test_restore_skips_user_when_email_is_reused(): void
    {
        $buyer = $this->buyer(['email' => 'reuse@example.test']);
        $user = $buyer->user;
        $user->password = 'secret123';
        $user->save();
        $admin = $this->admin();
        $this->actingAs($admin)->deleteJson("/admin/buyer-master-list/{$buyer->id}")->assertOk();
        User::create(['name' => 'Other', 'email' => 'reuse@example.test', 'password' => 'secret123']);

        $this->actingAs($admin)->postJson("/admin/buyer-master-list/{$buyer->id}/restore")->assertOk();

        $this->assertNull($buyer->fresh()->deleted_at);
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_restore_rejected_when_active_same_name_exists(): void
    {
        $buyer = $this->buyer();
        $admin = $this->admin();
        $this->actingAs($admin)->deleteJson("/admin/buyer-master-list/{$buyer->id}")->assertOk();
        $this->buyer();

        $this->actingAs($admin)
            ->postJson("/admin/buyer-master-list/{$buyer->id}/restore")
            ->assertStatus(409)
            ->assertJson(['success' => false]);

        $this->assertSoftDeleted('buyers', ['id' => $buyer->id]);
    }

    public function test_second_active_buyer_on_same_lot_is_rejected_via_api(): void
    {
        $lot = $this->lot('4', '4');
        $this->buyer(['first_name' => 'First', 'last_name' => 'Owner', 'email' => 'first.owner@gmail.com', 'lot_id' => $lot->id]);

        $this->actingAs($this->admin())
            ->postJson('/admin/buyer-master-list', [
                'first_name' => 'Second',
                'middle_name' => 'X',
                'last_name' => 'Buyer',
                'email' => 'second.buyer@gmail.com',
                'civil_status' => 'Single',
                'present_address' => 'Addr',
                'postal_address' => 'Addr',
                'contact_number' => '09171234567',
                'proof_of_id' => 'ID',
                'id_type' => 'Passport',
                'pagibig_number' => '123',
                'block_no' => '4',
                'lot_no' => '4',
                'lot_area' => 100,
                'floor_area' => 80,
                'description' => 'House',
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_archiving_buyer_cancels_reservation_without_deleting_row(): void
    {
        $lot = $this->lot('2', '2');
        $buyer = $this->buyer(['lot_id' => $lot->id]);
        $reservation = $this->reservation($lot, 'Reserved', $buyer);

        $this->actingAs($this->admin())
            ->deleteJson("/admin/buyer-master-list/{$buyer->id}")
            ->assertOk();

        $this->assertSoftDeleted('buyers', ['id' => $buyer->id]);
        $this->assertSame('Cancelled', $reservation->fresh()->status);
        $this->assertNull($reservation->fresh()->deleted_at);
    }

    public function test_hard_deleting_lot_with_reservation_is_restricted(): void
    {
        $lot = $this->lot();
        $this->reservation($lot);

        $this->expectException(QueryException::class);
        $lot->delete();
    }

    public function test_archived_user_email_can_be_reused_but_active_duplicate_is_rejected(): void
    {
        $old = User::create(['name' => 'Old', 'email' => 'same@example.test', 'password' => 'secret123']);
        $old->delete();

        User::create(['name' => 'New', 'email' => 'same@example.test', 'password' => 'secret123']);
        $this->assertSame(2, User::withTrashed()->where('email', 'same@example.test')->count());

        $this->expectException(UniqueConstraintViolationException::class);
        User::create(['name' => 'Dup', 'email' => 'same@example.test', 'password' => 'secret123']);
    }

    public function test_buyer_master_list_store_links_matching_lot(): void
    {
        $lot = $this->lot('5', '12');

        $this->actingAs($this->admin())
            ->postJson('/admin/buyer-master-list', [
                'first_name' => 'Maria',
                'middle_name' => 'Reyes',
                'last_name' => 'Santos',
                'email' => 'maria.santos@gmail.com',
                'contact_number' => '09171234567',
                'civil_status' => 'Single',
                'present_address' => 'Block 5 Lot 12',
                'postal_address' => 'Block 5 Lot 12',
                'id_type' => 'Passport',
                'proof_of_id' => 'P1234567',
                'pagibig_number' => '1234-5678-9012',
                'block_no' => '5',
                'lot_no' => '12',
                'lot_area' => 100,
                'floor_area' => 50,
                'description' => 'House and lot',
            ])
            ->assertOk();

        $buyer = BuyerMasterList::whereHas('user', fn ($q) => $q->where('first_name', 'Maria'))->first();
        $this->assertSame($lot->id, $buyer->lot_id);
        $this->assertSame('Passport', $buyer->id_type);
        $this->assertSame('P1234567', $buyer->proof_of_id);
        $this->assertSame(1, Lot::count());
    }

    public function test_blank_mri_on_financing_update(): void
    {
        $lot = $this->lot('9', '4');
        $buyer = $this->buyer();
        $record = $this->financing($buyer, $lot);
        $record->update([
            'financing_method' => 'Pag-IBIG',
            'contract_price' => 1000000,
            'sri_mri' => 5000,
            'interim_mri' => 2500,
            'loan_release' => 800000,
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($this->admin())->put(route('financing.update', $record->id), [
            'financing_method' => 'Pag-IBIG',
            'status' => 'Pending',
            'contract_price' => '1000000',
            'paid_by_vendee_equity' => '100000',
            'mbrdc_add_fees' => '',
            'other_exp_annotation' => '',
            'improvements_fee' => '',
            'mbrdc_turn_over_fee' => '',
            'loan_release' => '800000',
            'loan_base' => '',
            'loan_term' => '',
            'monthly_amortization' => '',
            'inspection_fee' => '',
            'retention_fee' => '',
            'retention_on_hold' => '1',
            'sri_mri' => '',
            'pag_ibig_non_life' => '',
            'interim_mri' => '',
            'net_loan_proceeds' => '',
        ]);

        $response->assertRedirect(route('financing.show', $record->id));
        $fresh = $record->fresh();
        $this->assertSame('0.00', number_format((float) $fresh->sri_mri, 2));
        $this->assertSame('0.00', number_format((float) $fresh->interim_mri, 2));
        $this->assertSame('0.00', number_format((float) $fresh->mbrdc_add_fees, 2));

        $cash = $this->put(route('financing.update', $record->id), [
            'financing_method' => 'Cash',
            'status' => 'Pending',
            'contract_price' => '1000000',
            'paid_by_vendee_equity' => '100000',
            'loan_release' => '800000',
            'sri_mri' => '5000',
            'interim_mri' => '2500',
            'retention_on_hold' => '1',
        ]);
        $cash->assertRedirect(route('financing.show', $record->id));
        $cleared = $record->fresh();
        $this->assertSame('Cash', $cleared->financing_method);
        $this->assertSame('0.00', number_format((float) $cleared->sri_mri, 2));
        $this->assertSame('0.00', number_format((float) $cleared->loan_release, 2));

        $this->get(route('financing.show', $record->id))
            ->assertOk()
            ->assertSee('id="loanFields" style="display:none"', false)
            ->assertSee('value="1" selected', false);
    }

    public function test_appointment_status_update_rejects_unknown_status(): void
    {
        $this->putJson('/api/appointments/abc/status', ['status' => 'Bogus'])->assertStatus(422);
    }

    public function test_reservation_uses_the_master_list_lot(): void
    {
        $lot = $this->lot('8', '3');
        $other = $this->lot('9', '4');
        $buyer = $this->buyer(['lot_id' => $lot->id, 'email' => 'reserved.buyer@gmail.com']);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/admin/reservation-fee')
            ->assertOk()
            ->assertDontSee('id="resBlock"', false)
            ->assertDontSee('id="resLot"', false)
            ->assertSee('id="resAssignedLot"', false);

        $this->actingAs($admin)
            ->postJson('/admin/reservation-fee', [
                'buyer_master_list_id' => $buyer->id,
                'block' => $other->block,
                'lot' => $other->lot_number,
                'amount' => 20000,
                'deadline_date' => now()->addDays(30)->toDateString(),
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $reservation = Reservation::where('buyer_id', $buyer->id)->first();
        $this->assertNotNull($reservation);
        $this->assertSame($lot->id, $reservation->lot_id);
        $this->assertSame($lot->id, $buyer->fresh()->lot_id);
        $this->assertSame('Reserved', $lot->fresh()->status);
        $this->assertSame('Available', $other->fresh()->status);

        $unassigned = $this->buyer(['email' => 'nolot.buyer@gmail.com']);
        $this->actingAs($admin)
            ->postJson('/admin/reservation-fee', [
                'buyer_master_list_id' => $unassigned->id,
                'amount' => 20000,
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }
}
