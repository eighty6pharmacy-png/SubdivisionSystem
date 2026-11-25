<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sms:utility-due')->dailyAt('06:00');
Schedule::command('sms:buyer-due')->dailyAt('06:00');
Schedule::command('sms:reservation-expiry')->dailyAt('06:00');

Schedule::call(function () {
    expirePastAppointments();
})->everyFifteenMinutes();

Schedule::call(function () {
    archiveOldAnnouncements();
})->daily();
