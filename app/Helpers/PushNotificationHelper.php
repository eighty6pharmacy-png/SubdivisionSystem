<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationHelper
{
    /**
     * Send a Push Notification via OneSignal.
     */
    public static function send($title, $message, $userIds = null)
    {
        $appId = config('services.onesignal.app_id');
        $restApiKey = config('services.onesignal.rest_api_key');

        if (!$appId || !$restApiKey) {
            Log::warning('OneSignal missing credentials. Simulated Push: ' . $title);
            return false;
        }
        
        $payload = [
            'app_id' => $appId,
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
        ];

        if ($userIds) {
            $payload['include_aliases'] = [
                'external_id' => is_array($userIds) ? $userIds : [$userIds]
            ];
            $payload['target_channel'] = 'push';
        } else {
            $payload['included_segments'] = ['All'];
        }

        try {
            $response = Http::withHeaders([
                    'Authorization' => 'Key ' . $restApiKey,
                    'accept' => 'application/json',
                    'content-type' => 'application/json'
                ])
                ->post('https://onesignal.com/api/v1/notifications', $payload);
                
            if (!$response->successful()) {
                Log::error('OneSignal API Error: ' . $response->body());
            }
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('OneSignal exception: ' . $e->getMessage());
            return false;
        }
    }
}
