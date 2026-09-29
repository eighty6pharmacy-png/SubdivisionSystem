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
        // Find downpayments due in exactly 3 days
        $dueIn3Days = Downpayment::where('balance', '>', 0)
            ->whereDate('due_date', Carbon::now()->addDays(3)->toDateString())
            ->with('reservation.buyerMasterList.user')
            ->get();
            
        foreach ($dueIn3Days as $dp) {
            $buyer = $dp->reservation->buyerMasterList ?? null;
            if ($buyer && $buyer->contact_number) {
                $msg = "Hi {$buyer->first_name}, a friendly reminder that your downpayment of PHP " . number_format($dp->monthly_amortization, 2) . " is due on " . Carbon::parse($dp->due_date)->format('M d, Y') . ". Please log in to your Buyer Portal to pay.";
                $this->sendSms($buyer->contact_number, $msg);
            }
        }
        
        // Find downpayments that are exactly 1 day overdue
        $overdue1Day = Downpayment::where('balance', '>', 0)
            ->whereDate('due_date', Carbon::now()->subDays(1)->toDateString())
            ->with('reservation.buyerMasterList.user')
            ->get();
            
        foreach ($overdue1Day as $dp) {
            $buyer = $dp->reservation->buyerMasterList ?? null;
            if ($buyer && $buyer->contact_number) {
                $msg = "URGENT: Hi {$buyer->first_name}, your downpayment of PHP " . number_format($dp->monthly_amortization, 2) . " was due yesterday. Please settle your account immediately via the Buyer Portal.";
                $this->sendSms($buyer->contact_number, $msg);
            }
        }
        
        $this->info('SMS reminders sent successfully.');
    }
    
    private function sendSms($phone, $message)
    {
        Log::info("Buyer SMS queued for {$phone}");
        \App\Helpers\SmsHelper::sendSms($phone, $message);
    }
}
