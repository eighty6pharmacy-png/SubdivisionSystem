<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\BuyerMasterList;
use App\Models\Lot;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReservationExpirySmsTest extends TestCase
{
    use RefreshDatabase;

    private function configureTextBeeFake(): void
    {
        config([
            'services.textbee.api_key' => 'test-api-key',
            'services.textbee.device_id' => 'test-device-id',
        ]);

        Http::fake([
            'https://api.textbee.dev/*' => Http::response(['status' => 'ok'], 200),
        ]);
    }

    private function createBuyerReservation(int $daysOffset, string $status = ReservationStatus::Reserved->value, string $contact = '09171234567'): Reservation
    {
        $lot = Lot::create([
            'block' => '2',
            'lot_number' => (string) random_int(1, 999),
        ]);

        $buyer = BuyerMasterList::create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan' . random_int(100, 99999) . '@example.test',
            'contact_number' => $contact,
            'lot_id' => $lot->id,
            'block_no' => $lot->block,
            'lot_no' => $lot->lot_number,
        ]);

        return Reservation::create([
            'buyer_id' => $buyer->id,
            'lot_id' => $lot->id,
            'amount' => 20000,
            'reservation_date' => now()->subDays(10)->toDateString(),
            'deadline_date' => now()->addDays($daysOffset)->toDateString(),
            'status' => $status,
        ]);
    }

    public function test_command_sends_sms_five_days_before_expiration_and_prevents_duplicates(): void
    {
        $this->configureTextBeeFake();

        $dueIn5Days = $this->createBuyerReservation(5, ReservationStatus::Reserved->value, '09171234567');
        $dueIn3Days = $this->createBuyerReservation(3, ReservationStatus::Reserved->value, '09179998888');
        $converted = $this->createBuyerReservation(5, ReservationStatus::Converted->value, '09175554444');

        $this->artisan('sms:reservation-expiry')->assertExitCode(0);

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $body = $request->data();
            return in_array('09171234567', $body['recipients'] ?? [], true)
                && str_contains($body['message'] ?? '', 'will expire in 5 days');
        });

        $dueIn5Days->refresh();
        $dueIn3Days->refresh();
        $converted->refresh();

        $this->assertNotNull($dueIn5Days->expiry_reminder_5d_sent_at);
        $this->assertNull($dueIn3Days->expiry_reminder_5d_sent_at);
        $this->assertNull($converted->expiry_reminder_5d_sent_at);

        // Second run must not send duplicate SMS
        $this->artisan('sms:reservation-expiry')->assertExitCode(0);
        Http::assertSentCount(1);
    }

    public function test_reservation_page_displays_auto_sms_status_and_triggers_pending_five_day_reminder(): void
    {
        $this->configureTextBeeFake();
        Cache::forget('reservation_expiry_sms_dispatch_lock');

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $reservation = $this->createBuyerReservation(5, ReservationStatus::Reserved->value, '09181234567');

        $response = $this->actingAs($admin)->get('/admin/reservation-fee');

        $response->assertOk();
        $response->assertSee('Auto SMS: Active (5 days before expiry', false);

        $reservation->refresh();
        $this->assertNotNull($reservation->expiry_reminder_5d_sent_at);
        Http::assertSentCount(1);
    }
}
