<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Downpayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class SendBuyerDueSMS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:buyer-due';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send SMS to buyers when their downpayment due date is near or overdue.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Find downpayments due in exactly 5 days (not yet reminded)
        $dueIn5Days = Downpayment::where('balance', '>', 0)
            ->whereNull('reminder_5d_sent_at')
            ->whereDate('due_date', Carbon::now()->addDays(5)->toDateString())
            ->with('reservation.buyerMasterList.user')
            ->get();
            
        foreach ($dueIn5Days as $dp) {
            $buyer = $dp->reservation->buyerMasterList ?? null;
            if ($buyer && $buyer->contact_number) {
                $msg = "Hi {$buyer->first_name}, a friendly reminder that your downpayment of PHP " . number_format($dp->monthly_amortization, 2) . " is due in 5 days on " . Carbon::parse($dp->due_date)->format('M d, Y') . ". Please log in to your Buyer Portal to pay.";
                if ($this->sendSms($buyer->contact_number, $msg)) {
                    $dp->update(['reminder_5d_sent_at' => now()]);
                }
            }
        }
        
        // Find downpayments that are exactly 1 day overdue (not yet reminded)
        $overdue1Day = Downpayment::where('balance', '>', 0)
            ->whereNull('overdue_reminder_sent_at')
            ->whereDate('due_date', Carbon::now()->subDays(1)->toDateString())
            ->with('reservation.buyerMasterList.user')
            ->get();
            
        foreach ($overdue1Day as $dp) {
            $buyer = $dp->reservation->buyerMasterList ?? null;
            if ($buyer && $buyer->contact_number) {
                $msg = "URGENT: Hi {$buyer->first_name}, your downpayment of PHP " . number_format($dp->monthly_amortization, 2) . " was due yesterday. Please settle your account immediately via the Buyer Portal.";
                if ($this->sendSms($buyer->contact_number, $msg)) {
                    $dp->update(['overdue_reminder_sent_at' => now()]);
                }
            }
        }
        
        $this->info('SMS reminders sent successfully.');
    }
    
    private function sendSms($phone, $message): bool
    {
        Log::info("Buyer SMS queued for {$phone}");
        return (bool) \App\Helpers\SmsHelper::sendSms($phone, $message);
    }
}
