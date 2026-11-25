<?php

namespace App\Console\Commands;

use App\Helpers\SmsHelper;
use App\Models\UtilityBill;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendUtilityDueSMS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:utility-due';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send automated SMS reminders to residents 5 days before utility bill due date or 1 day past due.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Log::info('Running daily billing SMS reminder check...');
        $sentCount = 0;

        // 1. Utility bills due in exactly 5 days (not yet reminded)
        $nearDueBills = UtilityBill::with('user')
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->whereNotNull('due_date')
            ->whereNull('reminder_5d_sent_at')
            ->whereDate('due_date', Carbon::now()->addDays(5)->toDateString())
            ->get();

        foreach ($nearDueBills as $bill) {
            $user = $bill->user;
            $totalDue = (float) $bill->amount + (float) $bill->previous_balance;
            if (! $user || empty($user->contact_number) || $totalDue <= 0) {
                continue;
            }

            $amtStr = number_format($totalDue, 2);
            $msg = "Althesa Subd Reminder: Your {$bill->type} bill is due in 5 days on " . Carbon::parse($bill->due_date)->format('M d') . ". Total Due: P{$amtStr}. Pls settle to avoid penalties.";

            if (SmsHelper::sendSms($user->contact_number, $msg)) {
                $bill->update(['reminder_5d_sent_at' => now()]);
                $sentCount++;
            }
        }

        // 2. Utility bills 1 day past due (not yet reminded)
        $overdueBills = UtilityBill::with('user')
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->whereNotNull('due_date')
            ->whereNull('overdue_reminder_sent_at')
            ->whereDate('due_date', Carbon::now()->subDays(1)->toDateString())
            ->get();

        foreach ($overdueBills as $bill) {
            $user = $bill->user;
            $totalDue = (float) $bill->amount + (float) $bill->previous_balance;
            if (! $user || empty($user->contact_number) || $totalDue <= 0) {
                continue;
            }

            $amtStr = number_format($totalDue, 2);
            $msg = "Althesa Subd Warning: Your {$bill->type} bill of P{$amtStr} is PAST DUE. Pls settle immediately at the Admin office or GCash to avoid disconnection.";

            if (SmsHelper::sendSms($user->contact_number, $msg)) {
                $bill->update(['overdue_reminder_sent_at' => now()]);
                $sentCount++;
            }
        }

        Log::info("Daily billing SMS reminder check completed. Sent: {$sentCount}");
        $this->info("Utility SMS reminders processed. Sent: {$sentCount}");

        return self::SUCCESS;
    }
}
