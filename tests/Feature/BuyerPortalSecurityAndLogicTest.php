<?php

namespace Tests\Feature;

use App\Enums\DownpaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\BuyerMasterList;
use App\Models\Downpayment;
use App\Models\Lot;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BuyerPortalSecurityAndLogicTest extends TestCase
{
    use RefreshDatabase;

    private function createBuyerUser(string $email = 'buyer@example.com', ?Lot $lot = null): array
    {
        $buyer = BuyerMasterList::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => $email,
            'contact_number' => '09171234567',
            'lot_id' => $lot?->id,
        ]);

        return [$buyer->user, $buyer];
    }

    public function test_buyer_cannot_pay_another_buyers_downpayment_idor_protection(): void
    {
        $lotA = Lot::create(['block' => 1, 'lot_number' => 1, 'area' => 100, 'price' => 1000000, 'status' => 'Reserved']);
        $lotB = Lot::create(['block' => 1, 'lot_number' => 2, 'area' => 100, 'price' => 1000000, 'status' => 'Reserved']);

        [$userA, $buyerA] = $this->createBuyerUser('buyera@example.com', $lotA);
        [$userB, $buyerB] = $this->createBuyerUser('buyerb@example.com', $lotB);

        $resB = Reservation::create([
            'buyer_id' => $buyerB->id,
            'buyer_master_list_id' => $buyerB->id,
            'lot_id' => $lotB->id,
            'amount' => 20000,
            'status' => ReservationStatus::Converted->value,
            'reservation_date' => now(),
        ]);

        $dpB = Downpayment::create([
            'reservation_id' => $resB->id,
            'total_dp' => 100000,
            'amount' => 20000,
            'balance' => 80000,
            'monthly_amortization' => 10000,
            'months_to_pay' => 8,
            'due_date' => now()->addMonth()->toDateString(),
            'status' => DownpaymentStatus::GoodStanding->value,
        ]);

        // Buyer A attempts to initiate checkout for Buyer B's downpayment
        $response = $this->actingAs($userA)->postJson('/buyer/api/downpayment/pay', [
            'dp_id' => $dpB->id,
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthorized.',
        ]);
    }

    public function test_buyer_cannot_pay_fully_paid_downpayment(): void
    {
        $lot = Lot::create(['block' => 2, 'lot_number' => 5, 'area' => 120, 'price' => 1200000, 'status' => 'Reserved']);
        [$user, $buyer] = $this->createBuyerUser('buyer@example.com', $lot);

        $res = Reservation::create([
            'buyer_id' => $buyer->id,
            'buyer_master_list_id' => $buyer->id,
            'lot_id' => $lot->id,
            'amount' => 20000,
            'status' => ReservationStatus::Converted->value,
            'reservation_date' => now(),
        ]);

        $dp = Downpayment::create([
            'reservation_id' => $res->id,
            'total_dp' => 100000,
            'amount' => 100000,
            'balance' => 0,
            'monthly_amortization' => 10000,
            'months_to_pay' => 8,
            'due_date' => now()->toDateString(),
            'status' => DownpaymentStatus::FullyPaid->value,
        ]);

        $response = $this->actingAs($user)->postJson('/buyer/api/downpayment/pay', [
            'dp_id' => $dp->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Downpayment is already fully paid.',
        ]);
    }

    public function test_buyer_topbar_displays_assigned_lot_badge_correctly(): void
    {
        $lot = Lot::create(['block' => 3, 'lot_number' => 14, 'area' => 150, 'price' => 1500000, 'status' => 'Reserved']);
        [$user, $buyer] = $this->createBuyerUser('buyerbadge@example.com', $lot);

        $response = $this->actingAs($user)->get('/buyer/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Block 3 Lot 14');
        $response->assertDontSee('Resident Portal — Althesa');
        $response->assertSee('Buyer Portal — Althesa');
    }

    public function test_sync_middleware_clears_expired_paymongo_checkout_session(): void
    {
        $lot = Lot::create(['block' => 4, 'lot_number' => 9, 'area' => 100, 'price' => 1000000, 'status' => 'Reserved']);
        [$user, $buyer] = $this->createBuyerUser('syncbuyer@example.com', $lot);

        $res = Reservation::create([
            'buyer_id' => $buyer->id,
            'buyer_master_list_id' => $buyer->id,
            'lot_id' => $lot->id,
            'amount' => 20000,
            'status' => ReservationStatus::Converted->value,
            'reservation_date' => now(),
        ]);

        $dp = Downpayment::create([
            'reservation_id' => $res->id,
            'total_dp' => 100000,
            'amount' => 20000,
            'balance' => 80000,
            'monthly_amortization' => 10000,
            'months_to_pay' => 8,
            'due_date' => now()->addMonth()->toDateString(),
            'status' => DownpaymentStatus::GoodStanding->value,
            'paymongo_checkout_id' => 'cs_test_expired_session_123',
        ]);

        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions/cs_test_expired_session_123' => Http::response([
                'data' => [
                    'attributes' => [
                        'status' => 'expired',
                        'payments' => [],
                    ]
                ]
            ], 200),
        ]);

        $this->actingAs($user)->get('/buyer/dashboard')->assertStatus(200);

        $this->assertNull($dp->fresh()->paymongo_checkout_id);
    }

    public function test_buyer_receipt_displays_payment_date_and_time(): void
    {
        $lot = Lot::create(['block' => 5, 'lot_number' => 2, 'area' => 120, 'price' => 1200000, 'status' => 'Reserved']);
        [$user, $buyer] = $this->createBuyerUser('buyerreceipt@example.com', $lot);

        $res = Reservation::create([
            'buyer_id' => $buyer->id,
            'buyer_master_list_id' => $buyer->id,
            'lot_id' => $lot->id,
            'amount' => 20000,
            'status' => ReservationStatus::Converted->value,
            'reservation_date' => now(),
        ]);

        $dp = Downpayment::create([
            'reservation_id' => $res->id,
            'total_dp' => 100000,
            'amount' => 0,
            'balance' => 100000,
            'monthly_amortization' => 10000,
            'months_to_pay' => 10,
            'due_date' => now()->addMonth()->toDateString(),
            'status' => DownpaymentStatus::GoodStanding->value,
        ]);

        $paymentTimestamp = \Carbon\Carbon::create(2026, 10, 20, 14, 30, 0, 'Asia/Manila');
        $history = app(\App\Services\PaymentRecorder::class)->recordDownpaymentPayment(
            $dp,
            10000,
            'GCash',
            'pay_test_receipt_123',
            'TRN-TEST-1234',
            $paymentTimestamp
        );

        // Verify history view includes "Payment Date & Time"
        $histResponse = $this->actingAs($user)->get('/buyer/payments/downpayment/history');
        $histResponse->assertStatus(200);
        $histResponse->assertSee('Payment Date &amp; Time', false);
        $histResponse->assertSee('October 20, 2026');
        $histResponse->assertSee('02:30 PM');

        // Verify PDF receipt view includes Payment Date & Time
        $pdfResponse = $this->actingAs($user)->get("/buyer/payments/downpayment/receipt/{$history->id}");
        $pdfResponse->assertStatus(200);
        $pdfResponse->assertHeader('Content-Type', 'application/pdf');

        $viewHtml = view('buyer.receipt_pdf', [
            'hist' => $history,
            'buyer' => $buyer,
            'dp' => $dp->fresh(),
        ])->render();

        $this->assertStringContainsString('Payment Date &amp; Time:', $viewHtml);
        $this->assertStringContainsString('October 20, 2026 02:30 PM', $viewHtml);
        $this->assertStringContainsString('OFFICIAL DOWNPAYMENT RECEIPT', $viewHtml);
        $this->assertStringContainsString('PAID', $viewHtml);
    }
}
