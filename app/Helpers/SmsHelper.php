<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsHelper
{
    /**
     * Format a Philippine phone number to national format (09XXXXXXXXX)
     * which TextBee Android gateway requires to dispatch via local SIM.
     */
    public static function formatNumber($number)
    {
        $digits = preg_replace('/[^0-9]/', '', $number);

        // 639XXXXXXXXX (12 digits) -> 09XXXXXXXXX
        if (str_starts_with($digits, '639') && strlen($digits) === 12) {
            return '0' . substr($digits, 2);
        }

        // 9XXXXXXXXX (10 digits) -> 09XXXXXXXXX
        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '0' . $digits;
        }

        // 09XXXXXXXXX (11 digits) -> 09XXXXXXXXX
        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            return $digits;
        }

        return $digits;
    }

    /**
     * Send an SMS using the TextBee API
     */
    public static function sendSms($contactNumber, $message)
    {
        $apiKey = config('services.textbee.api_key');
        $deviceId = config('services.textbee.device_id');

        if (empty($apiKey) || empty($deviceId) || $apiKey === 'your_textbee_api_key_here') {
            Log::warning('TextBee API Key or Device ID not configured. SMS not sent.');
            return false;
        }

        if (empty($contactNumber)) {
            Log::warning('Attempted to send SMS but contact number is empty.');
            return false;
        }

        $formattedNumber = self::formatNumber($contactNumber);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey
            ])->post("https://api.textbee.dev/api/v1/gateway/devices/{$deviceId}/sendSMS", [
                'recipients' => [$formattedNumber],
                'message' => $message
            ]);

            if ($response->successful()) {
                Log::info("SMS sent to {$formattedNumber} via TextBee");
                return true;
            }

            Log::error("TextBee SMS failed: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("TextBee SMS exception: " . $e->getMessage());
            return false;
        }
    }
}
