<?php

namespace App\Console\Commands;

use App\Enums\ReservationStatus;
use App\Helpers\SmsHelper;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendReservationExpirySMS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:reservation-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send SMS reminders to buyers when their reservation deadline is 3 days away or 1 day past due.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $activeStatuses = [
            ReservationStatus::Pending->value,
            ReservationStatus::Reserved->value,
        ];

        $sentCount = 0;

        // 1. Reservations expiring in exactly 3 days
        $expiringIn3Days = Reservation::whereIn('status', $activeStatuses)
            ->whereNotNull('deadline_date')
            ->whereDate('deadline_date', Carbon::now()->addDays(3)->toDateString())
            ->with(['lot', 'buyerMasterList', 'user'])
            ->get();

        foreach ($expiringIn3Days as $reservation) {
            $phone = $this->resolveContactNumber($reservation);
            if (! $phone) {
                continue;
            }

            $buyerName = $this->resolveBuyerFirstName($reservation);
            $lotLabel = $this->resolveLotLabel($reservation);
            $deadlineStr = Carbon::parse($reservation->deadline_date)->format('M d, Y');

            $msg = "Althesa Subd Reminder: Hi {$buyerName}, your lot reservation for {$lotLabel} will expire in 3 days on {$deadlineStr}. Please complete your downpayment or contact the Admin office to avoid cancellation.";

            if ($this->dispatchSms($phone, $msg)) {
                $sentCount++;
            }
        }

        // 2. Reservations that expired yesterday (1 day past deadline)
        $expiredYesterday = Reservation::whereIn('status', $activeStatuses)
            ->whereNotNull('deadline_date')
            ->whereDate('deadline_date', Carbon::now()->subDays(1)->toDateString())
            ->with(['lot', 'buyerMasterList', 'user'])
            ->get();

        foreach ($expiredYesterday as $reservation) {
            $phone = $this->resolveContactNumber($reservation);
            if (! $phone) {
                continue;
            }

            $buyerName = $this->resolveBuyerFirstName($reservation);
            $lotLabel = $this->resolveLotLabel($reservation);
            $deadlineStr = Carbon::parse($reservation->deadline_date)->format('M d, Y');

            $msg = "URGENT - Althesa Subd: Hi {$buyerName}, your reservation for {$lotLabel} expired yesterday ({$deadlineStr}). Please contact the Admin office immediately to renew or proceed with your downpayment.";

            if ($this->dispatchSms($phone, $msg)) {
                $sentCount++;
            }
        }

        $this->info("Reservation expiry SMS reminders processed. Sent: {$sentCount}");
        return self::SUCCESS;
    }

    private function resolveContactNumber(Reservation $reservation): ?string
    {
        $phone = $reservation->buyerMasterList->contact_number
            ?? $reservation->user->contact_number
            ?? null;

        $phone = is_string($phone) ? trim($phone) : null;
        return $phone !== '' ? $phone : null;
    }

    private function resolveBuyerFirstName(Reservation $reservation): string
    {
        return trim((string) (
            $reservation->buyerMasterList->first_name
            ?? $reservation->first_name
            ?? $reservation->user->first_name
            ?? 'Valued Buyer'
        ));
    }

    private function resolveLotLabel(Reservation $reservation): string
    {
        if ($reservation->lot) {
            return "Block {$reservation->lot->block}, Lot {$reservation->lot->lot_number}";
        }
        return 'your reserved lot';
    }

    private function dispatchSms(string $phone, string $message): bool
    {
        Log::info("Reservation expiry SMS queued for {$phone}");
        return SmsHelper::sendSms($phone, $message);
    }
}
