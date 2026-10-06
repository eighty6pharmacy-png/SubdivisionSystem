<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$appId = config('services.onesignal.app_id');
$restApiKey = config('services.onesignal.rest_api_key');

$admin = \App\Models\User::role('Admin')->first();
if (!$admin) {
    die("No admin found\n");
}

$payload = [
    'app_id' => $appId,
    'headings' => ['en' => 'Test Notification for Admin'],
    'contents' => ['en' => 'Testing OneSignal for Admin'],
    'include_aliases' => [
        'external_id' => [$admin->id]
    ],
    'target_channel' => 'push'
];

echo "Sending to Admin ID: {$admin->id}\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n";

$response = \Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => 'Basic ' . $restApiKey,
    'accept' => 'application/json',
    'content-type' => 'application/json'
])->post('https://onesignal.com/api/v1/notifications', $payload);

echo "Status: " . $response->status() . "\n";
echo "Response: " . $response->body() . "\n";
