<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Log::info('Running daily billing SMS reminder check...');

    // Find near due (3 days from now)
    $nearDueBills = \App\Models\UtilityBill::with('user')->where('status', 'unpaid')
        ->whereDate('due_date', \Carbon\Carbon::now()->addDays(3)->toDateString())
        ->get();

    foreach ($nearDueBills as $bill) {
        $user = $bill->user;
        if ($user && !empty($user->contact_number)) {
            $amtStr = number_format($bill->amount + $bill->previous_balance, 2);
            $msg = "Althesa Subd Reminder: Your {$bill->type} bill is due in 3 days on " . \Carbon\Carbon::parse($bill->due_date)->format('M d') . ". Total Due: P{$amtStr}. Pls settle to avoid penalties.";
            \App\Helpers\SmsHelper::sendSms($user->contact_number, $msg);
        }
    }

    // Find overdue (1 day past due)
    $overdueBills = \App\Models\UtilityBill::with('user')->where('status', 'unpaid')
        ->whereDate('due_date', \Carbon\Carbon::now()->subDays(1)->toDateString())
        ->get();

    foreach ($overdueBills as $bill) {
        $user = $bill->user;
        if ($user && !empty($user->contact_number)) {
            $amtStr = number_format($bill->amount + $bill->previous_balance, 2);
            $msg = "⚠️ Althesa Subd Warning: Your {$bill->type} bill of P{$amtStr} is PAST DUE. Pls settle immediately at the Admin office or GCash to avoid disconnection.";
            \App\Helpers\SmsHelper::sendSms($user->contact_number, $msg);
        }
    }

    Log::info('Daily billing SMS reminder check completed.');
})->dailyAt('08:00');
