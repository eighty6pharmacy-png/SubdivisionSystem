<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsHelper
{
    /**
     * Format a Philippine phone number to international format (+63)
     */
    public static function formatNumber($number)
    {
        $number = preg_replace('/[^0-9]/', '', $number);
        if (str_starts_with($number, '09')) {
            return '+63' . substr($number, 1);
        }
        if (str_starts_with($number, '639')) {
            return '+' . $number;
        }
        if (str_starts_with($number, '9') && strlen($number) == 10) {
            return '+63' . $number;
        }
        // If it already has +63, or doesn't match above, just return as is (with + prefix if it was 63)
        // Wait, preg_replace removed the '+'. Let's handle that.
        return '+' . $number; 
    }

    /**
     * Send an SMS using the TextBee API
     */
    public static function sendSms($contactNumber, $message)
    {
        $apiKey = env('TEXTBEE_API_KEY');
        $deviceId = env('TEXTBEE_DEVICE_ID');

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
