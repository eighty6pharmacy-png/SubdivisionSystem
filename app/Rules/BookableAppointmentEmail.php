<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookableAppointmentEmail implements ValidationRule
{
    /**
     * Run even when the field is blank. Laravel otherwise skips custom rules
     * for empty values, which let a missing email reach the rest of booking.
     */
    public bool $implicit = true;

    /**
     * Reject an appointment email that is blank, malformed, too short, or undeliverable.
     *
     * A one-character name such as f@hjgk.com is a valid mailbox shape, so the
     * length check is separate from the RFC check. DNS runs last so short
     * addresses fail before a lookup.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('Please enter an email address.');

            return;
        }

        if (Validator::make(['email' => $value], ['email' => 'email:rfc'])->fails()) {
            $fail('Please enter a valid email address.');

            return;
        }

        if (! preg_match('/^[^@]{2,}@.+$/', $value)) {
            $fail('Please enter a valid email address. The name before @ must be at least 2 characters.');

            return;
        }

        if (Validator::make(['email' => $value], ['email' => 'email:dns'])->fails()) {
            $fail('Please enter a valid email address that can receive mail.');
        }
    }

    public static function responseIfInvalid(Request $request): ?JsonResponse
    {
        $request->merge([
            'email' => trim((string) $request->input('email', '')),
        ]);

        $validator = Validator::make($request->all(), [
            'email' => [new self],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('email'),
                'errors' => [
                    'email' => $validator->errors()->get('email'),
                ],
            ], 422);
        }

        return null;
    }
}
