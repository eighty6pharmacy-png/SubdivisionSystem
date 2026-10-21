<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/home', function () {
    $user = \Illuminate\Support\Facades\Auth::user();
    if (!$user)
        return redirect('/login');
    if ($user->hasRole('Admin'))
        return redirect('/admin/dashboard');
    if ($user->hasRole('Security Guard'))
        return redirect('/guard/dashboard');
    if ($user->hasRole('Finance Officer'))
        return redirect('/finance/dashboard');
    if ($user->hasRole('Buyer'))
        return redirect('/buyer/dashboard');
    return redirect('/resident/dashboard');
});

Route::get('/appointment', function () {
    expirePastAppointments();

    return view('appointment');
});

if (!function_exists('subdivisionLockoutResponse')) {
    function subdivisionLockoutResponse(string $key, int $maxAttempts, string $reason): ?\Illuminate\Http\JsonResponse
    {
        if (!\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return null;
        }

        $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
        $minutes = max(1, (int) ceil($seconds / 60));
        $wait = $minutes === 1 ? '1 minute' : $minutes.' minutes';

        return response()->json([
            'success' => false,
            'message' => $reason.' Please wait '.$wait.' and try again.',
            'retry_after_seconds' => $seconds,
        ], 429);
    }
}

if (!function_exists('subdivisionOtpCacheKey')) {
    function subdivisionOtpCacheKey(string $email): string
    {
        return 'otp_'.strtolower(trim($email));
    }
}

if (!function_exists('subdivisionOtpMatches')) {
    function subdivisionOtpMatches(string $email, string $otp): bool
    {
        $cached = \Illuminate\Support\Facades\Cache::get(subdivisionOtpCacheKey($email));
        $given = hash('sha256', trim($otp));

        return is_string($cached) && strlen($cached) === strlen($given) && hash_equals($cached, $given);
    }
}

Route::get('/login', function (\Illuminate\Http\Request $request) {
    if (\Illuminate\Support\Facades\Auth::check()) {
        \Illuminate\Support\Facades\Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
    return view('login');
})->name('login');

Route::post('/login', function (\Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    $loginKey = 'login|'.strtolower($credentials['email']).'|'.$request->ip();
    if ($locked = subdivisionLockoutResponse($loginKey, 5, 'Too many failed sign-in attempts.')) {
        return $locked;
    }

    if (\Illuminate\Support\Facades\Auth::attempt($credentials)) {
        \Illuminate\Support\Facades\RateLimiter::clear($loginKey);
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user->status === 'Archived') {
            \Illuminate\Support\Facades\Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return response()->json([
                'success' => false,
                'message' => 'Your account has been archived. Please contact administration.',
            ], 403);
        }

        $request->session()->regenerate();

        $target = '/resident/dashboard';
        if ($user->hasRole('Admin')) {
            $target = '/admin/dashboard';
        } else if ($user->hasRole('Security Guard')) {
            $target = '/guard/dashboard';
        } else if ($user->hasRole('Finance Officer')) {
            $target = '/finance/dashboard';
        } else if ($user->hasRole('Buyer')) {
            $target = '/buyer/dashboard';
        }

        return response()->json(['success' => true, 'redirect' => $target]);
    }

    \Illuminate\Support\Facades\RateLimiter::hit($loginKey, 15 * 60);
    if ($locked = subdivisionLockoutResponse($loginKey, 5, 'Too many failed sign-in attempts.')) {
        return $locked;
    }

    $left = max(0, 5 - \Illuminate\Support\Facades\RateLimiter::attempts($loginKey));

    return response()->json([
        'success' => false,
        'message' => 'Email or password is incorrect. '.$left.' attempt'.($left === 1 ? '' : 's').' left before a 15-minute pause.',
        'attempts_left' => $left,
    ], 401);
});

Route::post('/logout', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
});

Route::post('/forgot-password', function (\Illuminate\Http\Request $request) {
    $request->validate(['email' => 'required|email']);
    $email = strtolower(trim($request->email));
    $emailKey = 'pw-reset-email|'.$email;
    $ipKey = 'pw-reset-ip|'.$request->ip();

    if ($locked = subdivisionLockoutResponse($emailKey, 3, 'Too many password reset requests for this email.')) {
        return $locked;
    }
    if ($locked = subdivisionLockoutResponse($ipKey, 10, 'Too many password reset requests.')) {
        return $locked;
    }

    \Illuminate\Support\Facades\RateLimiter::hit($emailKey, 60 * 60);
    \Illuminate\Support\Facades\RateLimiter::hit($ipKey, 60 * 60);

    $user = \App\Models\User::whereRaw('LOWER(email) = ?', [$email])->first();
    $generic = 'If that email is registered, we sent a 6-digit code. It expires in 15 minutes.';

    if (!$user) {
        return response()->json(['success' => true, 'message' => $generic]);
    }

    $otp = sprintf('%06d', random_int(0, 999999));
    \Illuminate\Support\Facades\Cache::put(subdivisionOtpCacheKey($email), hash('sha256', $otp), now()->addMinutes(15));

    try {
        \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($user, $otp) {
            $message->to($user->email)
                ->subject('Password Reset OTP — Althesa Subdivision')
                ->html("
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 16px; background: #ffffff;'>
                        <div style='background: #3b82f6; padding: 20px; border-radius: 12px; text-align: center;'>
                            <h2 style='color: #ffffff; margin: 0;'>Althesa Subdivision</h2>
                            <p style='color: #eff6ff; margin: 4px 0 0 0; font-size: 13px;'>Password Reset Request</p>
                        </div>
                        <div style='padding: 20px 0;'>
                            <p style='font-size: 16px; color: #0f172a;'>Hello <strong>{$user->name}</strong>,</p>
                            <p style='color: #475569;'>We received a request to reset your password. Use the following One-Time Password (OTP) to proceed.</p>
                            <div style='background: #eff6ff; border: 2px dashed #3b82f6; padding: 20px; border-radius: 12px; text-align: center; margin: 20px 0;'>
                                <span style='font-size: 13px; color: #1d4ed8; text-transform: uppercase; font-weight: 700;'>Your Reset OTP</span>
                                <div style='font-size: 36px; font-weight: 800; color: #1d4ed8; letter-spacing: 6px; margin-top: 8px;'>{$otp}</div>
                            </div>
                            <p style='color: #475569; font-size: 13px;'>This OTP will expire in 15 minutes. If you did not request a password reset, please ignore this email.</p>
                        </div>
                        <div style='border-top: 1px solid #e2e8f0; padding-top: 16px; font-size: 12px; color: #94a3b8; text-align: center;'>
                            Althesa Subdivision Management Office
                        </div>
                    </div>
                ");
        });
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Failed to send OTP email: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Failed to send email.']);
    }

    return response()->json(['success' => true, 'message' => $generic]);
});

Route::post('/verify-otp', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'email' => 'required|email',
        'otp' => 'required|string'
    ]);

    $email = strtolower(trim($request->email));
    $verifyKey = 'otp-verify|'.$email.'|'.$request->ip();
    if ($locked = subdivisionLockoutResponse($verifyKey, 5, 'Too many incorrect codes.')) {
        \Illuminate\Support\Facades\Cache::forget(subdivisionOtpCacheKey($email));

        return $locked;
    }

    if (subdivisionOtpMatches($email, $request->otp)) {
        \Illuminate\Support\Facades\RateLimiter::clear($verifyKey);

        return response()->json(['success' => true]);
    }

    \Illuminate\Support\Facades\RateLimiter::hit($verifyKey, 15 * 60);
    if ($locked = subdivisionLockoutResponse($verifyKey, 5, 'Too many incorrect codes.')) {
        \Illuminate\Support\Facades\Cache::forget(subdivisionOtpCacheKey($email));

        return $locked;
    }

    return response()->json(['success' => false, 'message' => 'Invalid or expired code.']);
});

Route::post('/reset-password', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'email' => 'required|email',
        'otp' => 'required|string',
        'password' => 'required|string|min:8'
    ]);

    $email = strtolower(trim($request->email));
    $verifyKey = 'otp-verify|'.$email.'|'.$request->ip();
    if ($locked = subdivisionLockoutResponse($verifyKey, 5, 'Too many incorrect codes.')) {
        \Illuminate\Support\Facades\Cache::forget(subdivisionOtpCacheKey($email));

        return $locked;
    }

    if (subdivisionOtpMatches($email, $request->otp)) {
        $user = \App\Models\User::whereRaw('LOWER(email) = ?', [$email])->first();
        if ($user) {
            $user->password = $request->password;
            $user->save();
            \Illuminate\Support\Facades\Cache::forget(subdivisionOtpCacheKey($email));
            \Illuminate\Support\Facades\RateLimiter::clear($verifyKey);

            return response()->json(['success' => true]);
        }
    }

    \Illuminate\Support\Facades\RateLimiter::hit($verifyKey, 15 * 60);

    return response()->json(['success' => false, 'message' => 'Failed to reset password. The code may be invalid or expired.']);
});

// getVisitorPins() removed, now using API endpoints and controller

// Resident Routes
Route::prefix('resident')->middleware(['auth', 'web', 'role:Resident', 'sync_paymongo'])->group(function () {

    Route::post('/api/billing/pay', function (\Illuminate\Http\Request $request) {
        $billId = $request->input('bill_id');
        $bill = \App\Models\UtilityBill::whereKey($billId)
            ->where('resident_id', \Illuminate\Support\Facades\Auth::id())
            ->where('status', '!=', 'paid')
            ->first();

        if (!$bill) {
            return response()->json(['success' => false, 'message' => 'Bill not found, unauthorized, or already paid.'], 404);
        }

        if ($bill->paymongo_checkout_id) {
            try {
                $sessionCheck = \Illuminate\Support\Facades\Http::withHeaders([
                    'accept' => 'application/json',
                    'authorization' => 'Basic ' . base64_encode(config('services.paymongo.secret_key') . ':')
                ])->get('https://api.paymongo.com/v1/checkout_sessions/' . $bill->paymongo_checkout_id);

                if ($sessionCheck->successful()) {
                    $sessionData = $sessionCheck->json();
                    $sessionStatus = $sessionData['data']['attributes']['status'] ?? '';

                    if ($sessionStatus === 'paid') {
                        $sessionAttributes = $sessionData['data']['attributes'] ?? [];
                        $paidPayment = \App\Services\PaymentRecorder::paidPayment($sessionAttributes['payments'] ?? []);
                        if ($paidPayment) {
                            $method = \App\Services\PaymentRecorder::methodFromSource($paidPayment['attributes']['source']['type'] ?? '', 'GCash');
                            $trn = \App\Services\PaymentRecorder::onlineTrn($paidPayment['attributes']['payment_intent_id'] ?? null, (string) $bill->id);
                            $exactAmount = isset($paidPayment['attributes']['amount']) ? ($paidPayment['attributes']['amount'] / 100) : ($bill->amount + $bill->previous_balance);
                            $paidAt = isset($paidPayment['attributes']['paid_at']) ? \Carbon\Carbon::createFromTimestamp($paidPayment['attributes']['paid_at'], config('app.timezone')) : now();

                            app(\App\Services\PaymentRecorder::class)->recordUtilityPayment(
                                $bill,
                                (float) $exactAmount,
                                $method,
                                \App\Services\PaymentRecorder::paymongoPaymentId($paidPayment, $sessionAttributes),
                                $trn,
                                $paidAt
                            );
                        }
                        return response()->json(['success' => false, 'message' => 'Bill has already been paid.'], 400);
                    }

                    if ($sessionStatus === 'active' && !empty($bill->paymongo_checkout_url)) {
                        return response()->json(['success' => true, 'checkout_url' => $bill->paymongo_checkout_url]);
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('PayMongo session check error: ' . $e->getMessage());
            }

            $bill->update([
                'paymongo_checkout_id' => null,
                'paymongo_checkout_url' => null,
            ]);
        }

        $tz = config('app.timezone', 'Asia/Manila');
        $totalDueBeforePenalty = (float) $bill->amount + (float) $bill->previous_balance;
        $isOverdue = ($bill->is_at_risk || ($bill->due_date && \Carbon\Carbon::parse($bill->due_date, $tz)->endOfDay()->isPast())) && strtolower((string) $bill->status) !== 'paid';
        $penaltyRatePercent = (float) (\App\Models\Setting::where('key', $bill->type . '_penalty')->value('value') ?: 5);
        $penalty = $isOverdue ? round($totalDueBeforePenalty * ($penaltyRatePercent / 100), 2) : 0.0;
        $totalDue = round($totalDueBeforePenalty + $penalty, 2);
        $totalPaid = (float) \App\Models\Payment::where('utility_bill_id', $bill->id)->sum('amount_paid');
        $remainingBalance = max(0.0, round($totalDue - $totalPaid, 2));

        if ($remainingBalance <= 0) {
            return response()->json(['success' => false, 'message' => 'Bill has already been paid.'], 400);
        }

        $lineItems = [];
        if ($totalPaid > 0) {
            $lineItems[] = [
                'currency' => 'PHP',
                'amount' => (int) round($remainingBalance * 100),
                'name' => ucfirst($bill->type) . ' Bill (Remaining Balance)',
                'quantity' => 1
            ];
        } else {
            $usageAmount = (float) $bill->amount;
            if ($usageAmount > 0) {
                $lineItems[] = [
                    'currency' => 'PHP',
                    'amount' => (int) round($usageAmount * 100),
                    'name' => ucfirst($bill->type) . ' Usage',
                    'quantity' => 1
                ];
            }

            if ($bill->previous_balance > 0) {
                $lineItems[] = [
                    'currency' => 'PHP',
                    'amount' => (int) round($bill->previous_balance * 100),
                    'name' => 'Previous Balance',
                    'quantity' => 1
                ];
            }

            if ($penalty > 0) {
                $lineItems[] = [
                    'currency' => 'PHP',
                    'amount' => (int) round($penalty * 100),
                    'name' => 'Late Penalty Fee',
                    'quantity' => 1
                ];
            }
        }

        if (empty($lineItems)) {
            return response()->json(['success' => false, 'message' => 'Bill amount is 0.'], 400);
        }

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'authorization' => 'Basic ' . base64_encode(config('services.paymongo.secret_key') . ':')
        ])->post('https://api.paymongo.com/v1/checkout_sessions', [
                    'data' => [
                        'attributes' => [
                            'send_email_receipt' => true,
                            'show_description' => true,
                            'show_line_items' => true,
                            'line_items' => $lineItems,
                            'payment_method_types' => ['gcash', 'paymaya', 'card', 'grab_pay', 'qrph'],
                            'success_url' => request()->getSchemeAndHttpHost() . '/resident/' . $bill->type . '?payment=success',
                            'cancel_url' => request()->getSchemeAndHttpHost() . '/resident/' . $bill->type . '?payment=cancelled',
                            'description' => 'Subdivision ' . ucfirst($bill->type) . ' Bill',
                            'reference_number' => (string) $bill->id
                        ]
                    ]
                ]);

        if ($response->successful()) {
            $data = $response->json();
            $checkoutUrl = $data['data']['attributes']['checkout_url'];
            $checkoutId = $data['data']['id'];

            $bill->update([
                'paymongo_checkout_id' => $checkoutId,
                'paymongo_checkout_url' => $checkoutUrl
            ]);

            return response()->json(['success' => true, 'checkout_url' => $checkoutUrl]);
        }

        \Illuminate\Support\Facades\Log::error('PayMongo Checkout Error: ' . json_encode($response->json()));
        return response()->json(['success' => false, 'message' => 'Failed to connect to payment gateway.', 'error' => $response->json()], 500);
    });
});

Route::post('/api/webhooks/paymongo', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Log::info('Webhook received', $request->all());

    $signatureHeader = $request->header('Paymongo-Signature');
    $secret = config('services.paymongo.webhook_secret');

    if (!$signatureHeader || !$secret) {
        \Illuminate\Support\Facades\Log::error('Webhook missing auth', ['header' => $signatureHeader, 'secret_exists' => !!$secret]);
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Parse signature header
    $signatureParts = explode(',', $signatureHeader);
    $t = explode('=', $signatureParts[0])[1] ?? '';
    $te = explode('=', $signatureParts[1])[1] ?? '';
    $li = explode('=', $signatureParts[2])[1] ?? '';

    $payload = $t . '.' . $request->getContent();
    $signature = hash_hmac('sha256', $payload, $secret);

    if ($signature !== $te && $signature !== $li) {
        // Log the failure for debugging, but in a real app block it.
        // For testing, we might want to bypass strict checking if keys are mismatched during dev, 
        // but since security is paramount here, we enforce it strictly.
        \Illuminate\Support\Facades\Log::warning('PayMongo Signature Mismatch', ['header' => $signatureHeader, 'calculated' => $signature]);
        return response()->json(['error' => 'Invalid signature'], 401);
    }

    $event = $request->input('data.attributes.type');

    if ($event === 'checkout_session.payment.paid') {
        $paymentData = $request->input('data.attributes.data.attributes');
        $billId = $paymentData['reference_number'] ?? null;
        $recorder = app(\App\Services\PaymentRecorder::class);

        $payments = $paymentData['payments'] ?? [];
        $paidPayment = \App\Services\PaymentRecorder::paidPayment($payments);
        $paymongoPaymentId = \App\Services\PaymentRecorder::paymongoPaymentId($paidPayment ?? ($payments[0] ?? null), $paymentData ?? []);
        $sourceType = $paidPayment['attributes']['source']['type'] ?? ($paymentData['source']['type'] ?? '');
        $paymentIntentId = $paidPayment['attributes']['payment_intent_id'] ?? ($paymentData['payment_intent_id'] ?? null);

        if (str_starts_with($billId, 'DP-')) {
            $dpId = substr($billId, 3);
            $dp = \App\Models\Downpayment::find($dpId);
            if ($dp) {
                $amountPaid = isset($paymentData['payments'][0]['attributes']['amount']) ? ($paymentData['payments'][0]['attributes']['amount'] / 100) : (isset($paymentData['amount']) ? ($paymentData['amount'] / 100) : $dp->monthly_amortization);
                $paidAt = isset($paidPayment['attributes']['paid_at']) ? \Carbon\Carbon::createFromTimestamp($paidPayment['attributes']['paid_at'], config('app.timezone')) : (isset($paymentData['paid_at']) ? \Carbon\Carbon::createFromTimestamp($paymentData['paid_at'], config('app.timezone')) : now());

                $recorder->recordDownpaymentPayment(
                    $dp,
                    (float) $amountPaid,
                    \App\Services\PaymentRecorder::methodFromSource($sourceType, 'Online Payment'),
                    $paymongoPaymentId,
                    \App\Services\PaymentRecorder::onlineTrn($paymentIntentId, $dp->id),
                    $paidAt
                );
                \Illuminate\Support\Facades\Log::info('Webhook marked DP ' . $dp->id . ' as paid.');
            }
        } elseif (str_starts_with($billId, 'FIN-')) {
            $finId = substr($billId, 4);
            $fin = \App\Models\FinancingRecord::find($finId);
            if ($fin) {
                $amountPaid = isset($paymentData['payments'][0]['attributes']['amount']) ? ($paymentData['payments'][0]['attributes']['amount'] / 100) : (isset($paymentData['amount']) ? ($paymentData['amount'] / 100) : $fin->balance);
                $paidAt = isset($paidPayment['attributes']['paid_at']) ? \Carbon\Carbon::createFromTimestamp($paidPayment['attributes']['paid_at'], config('app.timezone')) : (isset($paymentData['paid_at']) ? \Carbon\Carbon::createFromTimestamp($paymentData['paid_at'], config('app.timezone')) : now());

                $recorder->recordFinancingPayment(
                    $fin,
                    (float) $amountPaid,
                    \App\Services\PaymentRecorder::methodFromSource($sourceType, 'Online Payment'),
                    $paymongoPaymentId,
                    \App\Services\PaymentRecorder::onlineTrn($paymentIntentId, $fin->id),
                    $paidAt
                );
                \Illuminate\Support\Facades\Log::info('Webhook marked FIN ' . $fin->id . ' as paid.');
            }
        } else {
            $billKey = str_starts_with((string) $billId, 'BILL-') ? substr($billId, 5) : $billId;
            $bill = \App\Models\UtilityBill::find($billKey);
            if ($bill && $bill->status !== \App\Enums\BillStatus::Paid->value) {
                if ($paidPayment || ($paymentData['status'] ?? '') === 'paid') {
                    $amountPaid = isset($paidPayment['attributes']['amount']) ? ($paidPayment['attributes']['amount'] / 100) : (isset($paymentData['amount']) ? ($paymentData['amount'] / 100) : ($bill->amount + $bill->previous_balance));
                    $paidAt = isset($paidPayment['attributes']['paid_at']) ? \Carbon\Carbon::createFromTimestamp($paidPayment['attributes']['paid_at'], config('app.timezone')) : (isset($paymentData['paid_at']) ? \Carbon\Carbon::createFromTimestamp($paymentData['paid_at'], config('app.timezone')) : now());

                    $recorder->recordUtilityPayment(
                        $bill,
                        (float) $amountPaid,
                        \App\Services\PaymentRecorder::methodFromSource($sourceType, 'GCash'),
                        $paymongoPaymentId,
                        \App\Services\PaymentRecorder::onlineTrn($paymentIntentId, (string) $bill->id),
                        $paidAt
                    );
                    \Illuminate\Support\Facades\Log::info('Webhook marked bill ' . $bill->id . ' as paid.');
                }
            }
        }
    }

    return response()->json(['success' => true]);
});

// Guard Security Portal Routes
Route::prefix('guard')->middleware(['auth', 'role:Security Guard|Admin'])->group(function () {
    Route::get('/dashboard', function () {
        return view('guard.dashboard');
    });
    Route::get('/history', function () {
        $pins = json_decode(app(\App\Http\Controllers\VisitorController::class)->index()->getContent(), true);
        return view('guard.history', compact('pins'));
    });
});

// API Routes for Visitors
Route::post('/api/validate-pin', [\App\Http\Controllers\VisitorController::class, 'validatePin']);
Route::middleware(['auth'])->group(function () {
    Route::get('/api/visitors', [\App\Http\Controllers\VisitorController::class, 'index']);
    Route::post('/api/visitors', [\App\Http\Controllers\VisitorController::class, 'store']);
    Route::put('/api/visitors/{id}/approve', [\App\Http\Controllers\VisitorController::class, 'approve']);
    Route::put('/api/visitors/{id}/reject', [\App\Http\Controllers\VisitorController::class, 'reject']);
    Route::put('/api/visitors/{id}/enter', [\App\Http\Controllers\VisitorController::class, 'markEntered']);
});

Route::post('/api/appointments', function (\Illuminate\Http\Request $request) {
    // ── Real reCAPTCHA v2 Server-Side Verification ──────────────────────────
    $token = $request->input('recaptcha_token');
    if (!$token) {
        return response()->json(['success' => false, 'message' => 'Please complete the reCAPTCHA verification.'], 422);
    }

    $verify = \Illuminate\Support\Facades\Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
        'secret'   => env('RECAPTCHA_SECRET_KEY'),
        'response' => $token,
        'remoteip' => $request->ip(),
    ]);

    if (!$verify->json('success')) {
        return response()->json(['success' => false, 'message' => 'reCAPTCHA verification failed. Please try again.'], 422);
    }
    // ────────────────────────────────────────────────────────────────────────

    $normalizedContact = preg_replace('/[\s\-().]/', '', (string) $request->input('contact_number'));
    $request->merge([
        'contact_number' => $normalizedContact,
        'email' => trim((string) $request->input('email')),
    ]);

    $allowedTimes = [
        '09:00 AM', '10:00 AM', '11:00 AM', '01:00 PM',
        '02:00 PM', '03:00 PM', '04:00 PM', '05:00 PM',
    ];

    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'first_name' => 'required|string|max:100',
        'middle_name' => 'required|string|max:100',
        'last_name' => 'required|string|max:100',
        'email' => ['required', 'email:rfc,dns', function (string $attribute, mixed $value, \Closure $fail) {
            $domain = substr(strrchr(strtolower((string) $value), '@') ?: '', 1);
            if ($domain === '' || (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A'))) {
                $fail('That email domain does not appear to accept mail. Please use a real, active email address.');
            }
        }],
        'contact_number' => ['required', 'regex:/^(09\d{9}|\+639\d{9}|639\d{9})$/'],
        'date' => 'required|date|after_or_equal:today',
        'time' => ['required', \Illuminate\Validation\Rule::in($allowedTimes)],
        'status' => ['nullable', \Illuminate\Validation\Rule::in(\App\Enums\AppointmentStatus::values())],
    ], [
        'first_name.required' => 'First Name is required.',
        'middle_name.required' => 'Middle Name is required.',
        'last_name.required' => 'Last Name is required.',
        'email.required' => 'Email Address is required.',
        'email.email' => 'Please enter a real email address on a domain that can receive mail (Gmail, Outlook, company mail, etc.).',
        'contact_number.required' => 'Contact / Mobile Number is required.',
        'contact_number.regex' => 'Use a Philippine mobile number: 09XXXXXXXXX or +639XXXXXXXXX.',
        'date.required' => 'Preferred Date is required.',
        'time.required' => 'Preferred Time is required.',
        'date.after_or_equal' => 'Preferred date cannot be in the past.',
        'time.in' => 'Please select a valid appointment time slot.',
    ]);
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422);
    }

    $tz = config('app.timezone', 'Asia/Manila');
    $appointmentDate = \Carbon\Carbon::parse($request->input('date'), $tz)->startOfDay();
    if ($bookingError = appointmentBookingError($request->input('date'), $request->input('time'))) {
        return response()->json([
            'success' => false,
            'message' => $bookingError,
        ], 422);
    }
    expirePastAppointments();

    $unavailableDates = json_decode(\App\Models\Setting::where('key', 'unavailable_dates')->value('value') ?? '[]', true) ?: [];
    $dateYmd = $appointmentDate->format('Y-m-d');
    $datePretty = $appointmentDate->format('M j, Y');
    foreach ($unavailableDates as $blocked) {
        if ($blocked === $dateYmd || $blocked === $datePretty) {
            return response()->json([
                'success' => false,
                'message' => 'This date is unavailable. Please select another date.',
            ], 422);
        }
    }

    $alreadyTaken = \App\Models\Appointment::query()
        ->whereDate('date', $dateYmd)
        ->where('time', $request->input('time'))
        ->where('status', '!=', \App\Enums\AppointmentStatus::Cancelled->value)
        ->exists();
    if ($alreadyTaken) {
        return response()->json([
            'success' => false,
            'message' => 'An appointment is already scheduled on this time and day. Please select a different date or time slot.',
        ], 422);
    }

    $apt = \App\Models\Appointment::create([
        'first_name'     => $request->input('first_name'),
        'middle_name'    => $request->input('middle_name'),
        'last_name'      => $request->input('last_name'),
        'contact_number' => $normalizedContact,
        'email'          => $request->input('email'),
        'type'           => $request->input('type'),
        'date'           => $appointmentDate,
        'time'           => $request->input('time'),
        'status'         => $request->input('status') ?: \App\Enums\AppointmentStatus::Pending->value,
        'notes'          => $request->input('notes'),
    ]);
    
    $admins = \App\Models\User::role('Admin')->get();
    foreach ($admins as $admin) {
        $admin->notify(new \App\Notifications\WebAndPushNotification(
            'New Appointment Request',
            "{$apt->client_name} requested a {$apt->type} appointment for " . \Carbon\Carbon::parse($apt->date)->format('M d, Y') . " at {$apt->time}.",
            'appointment'
        ));
    }
    
    return response()->json(['success' => true]);
})->middleware('throttle:3,60');
Route::put('/api/incidents/{id}/status', function (\Illuminate\Http\Request $request, $id) {
    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'status' => ['required', \Illuminate\Validation\Rule::in(\App\Enums\IncidentStatus::values())],
    ]);
    if ($validator->fails()) {
        return response()->json(['success' => false, 'message' => 'Invalid incident status. Allowed: ' . implode(', ', \App\Enums\IncidentStatus::values()) . '.'], 422);
    }

    $inc = \App\Models\Incident::whereKey($id)->first();
    if ($inc) {
        $inc->update(['status' => $request->input('status')]);
        if ($inc->user) {
            $inc->user->notify(new \App\Notifications\WebAndPushNotification(
                'Incident Report Update',
                "Your incident report '{$inc->subject}' status has been updated to: {$request->input('status')}.",
                'incident'
            ));
        }
        return response()->json(['success' => true]);
    }
    return response()->json(['success' => false], 404);
})->middleware(['auth', 'role:Admin|Security Guard']);

Route::put('/api/appointments/{id}/email', function (\Illuminate\Http\Request $request, $id) {
    $apt = \App\Models\Appointment::whereKey($id)->first();
    if ($apt) {
        $apt->update(['email' => $request->input('email')]);
        return response()->json(['success' => true]);
    }
    return response()->json(['success' => false], 404);
});
Route::put('/api/appointments/{id}/status', function (\Illuminate\Http\Request $request, $id) {
    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'status' => ['required', \Illuminate\Validation\Rule::in(\App\Enums\AppointmentStatus::values())],
    ]);
    if ($validator->fails()) {
        return response()->json(['success' => false, 'message' => 'Invalid appointment status. Allowed: ' . implode(', ', \App\Enums\AppointmentStatus::values()) . '.'], 422);
    }

    $apt = \App\Models\Appointment::whereKey($id)->first();
    if ($apt) {
        $oldStatus = $apt->status;
        $apt->update([
            'status' => $request->input('status'),
            'report' => $request->input('report', $apt->report)
        ]);
        
        $createdVisitorId = null;
        if ($request->input('status') === 'Completed' && $oldStatus !== 'Completed') {
            $vis = \App\Models\Visitor::create([
                'resident_id' => null,
                'visitor_name' => $apt->client_name,
                'pin' => $apt->pin,
                'purpose' => 'Appointment: ' . $apt->type,
                'validity' => \Carbon\Carbon::parse($apt->date)->format('Y-m-d') . ' ' . $apt->time,
                'arrival_time' => \Carbon\Carbon::now()->setTimezone(config('app.timezone', 'Asia/Manila'))->format('h:i A'),
                'status' => 'Entered',
                'plate_number' => $request->input('plate_number', 'N/A'),
                'type' => 'Appointment',
                'visitor_address' => 'N/A',
                'guard_id' => auth()->user()?->guardAccount ? auth()->id() : null,
                'guard_name' => auth()->check() ? auth()->user()->name : 'Guard',
            ]);
            $createdVisitorId = $vis->id;
        } elseif ($request->input('status') === 'Completed') {
            $existingVis = \App\Models\Visitor::where('pin', $apt->pin)->first();
            $createdVisitorId = $existingVis?->id;
        }

        if ($request->input('status') === 'Scheduled' && $oldStatus !== 'Scheduled' && !empty($apt->email)) {
            try {
                $name = $apt->client_name;
                $date = \Carbon\Carbon::parse($apt->date)->format('F j, Y');
                $pin = $request->input('pin', rand(100000, 999999));
                $apt->update(['pin' => $pin]);
                \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($apt, $name, $date, $pin) {
                    $message->to($apt->email)
                        ->subject('🎉 Appointment Approved & Gate Access PIN — Althesa Subdivision')
                        ->html("
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 16px; background: #ffffff;'>
                                <div style='background: #059669; padding: 20px; border-radius: 12px; text-align: center;'>
                                    <h2 style='color: #ffffff; margin: 0;'>Althesa Subdivision</h2>
                                    <p style='color: #ecfdf5; margin: 4px 0 0 0; font-size: 13px;'>Appointment Approved</p>
                                </div>
                                <div style='padding: 20px 0;'>
                                    <p style='font-size: 16px; color: #0f172a;'>Hello <strong>{$name}</strong>,</p>
                                    <p style='color: #475569;'>Great news! Your appointment request for <strong>{$date}</strong> has been approved by the subdivision management office.</p>
                                    <div style='background: #ecfdf5; border: 2px dashed #059669; padding: 20px; border-radius: 12px; text-align: center; margin: 20px 0;'>
                                        <span style='font-size: 13px; color: #047857; text-transform: uppercase; font-weight: 700;'>Your Gate Entry Viewing PIN</span>
                                        <div style='font-size: 36px; font-weight: 800; color: #047857; letter-spacing: 6px; margin-top: 8px;'>{$pin}</div>
                                    </div>
                                    <p style='color: #475569;'>Please present this 6-digit PIN to the security guard at Gate 1 upon your arrival.</p>
                                </div>
                                <div style='border-top: 1px solid #e2e8f0; padding-top: 16px; font-size: 12px; color: #94a3b8; text-align: center;'>
                                    Althesa Subdivision Security & Management
                                </div>
                            </div>
                        ");
                });
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send approval email: ' . $e->getMessage());
            }
        } elseif ($request->input('status') === 'Cancelled' && $oldStatus !== 'Cancelled') {
            sendAppointmentCancellationEmail($apt, $request->input('reason', 'Schedule conflict / fully booked.'));
        }
    }
    return response()->json(['success' => true, 'visitor_id' => $createdVisitorId]);
});

// GIS API Route
Route::get('/api/lots', [\App\Http\Controllers\GisController::class, 'getLotsData']);

// Admin Routes
Route::prefix('admin')->middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/dashboard', function () {
        // 1. Open Incidents
        $openIncidentsCount = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('incidents')) {
            $openIncidentsCount = \App\Models\Incident::whereIn('status', ['pending', 'progress'])->count();
        }

        // 2. Appointments Today
        $appointmentsTodayCount = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('appointments')) {
            $appointmentsTodayCount = \Illuminate\Support\Facades\DB::table('appointments')
                ->whereDate('date', \Carbon\Carbon::today())
                ->count();
        }

        // 3. Total Residents
        $totalResidentsCount = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('users')
            && \Spatie\Permission\Models\Role::where('name', 'Resident')->where('guard_name', 'web')->exists()) {
            $totalResidentsCount = \App\Models\User::role('Resident')->count();
        }

        // 4. Revenue This Month
        $currentMonth = \Carbon\Carbon::now()->month;
        $currentYear = \Carbon\Carbon::now()->year;
        
        $utilityRevenue = \Illuminate\Support\Facades\DB::table('payments')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('amount_paid');
            
        $dpRevenue = \Illuminate\Support\Facades\DB::table('downpayment_histories')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('amount');
            
        $finRevenue = \Illuminate\Support\Facades\DB::table('financing_payment_histories')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('amount_paid');

        $monthlyRevenue = $utilityRevenue + $dpRevenue + $finRevenue;

        // 5. Monthly Sales Chart Data (Last 12 Months)
        $monthlySalesData = [];
        $monthlySalesLabels = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthDate = \Carbon\Carbon::now()->subMonths($i);
            $m = $monthDate->month;
            $y = $monthDate->year;
            $monthlySalesLabels[] = $monthDate->format('M');
            
            $uRev = \Illuminate\Support\Facades\DB::table('payments')->whereMonth('created_at', $m)->whereYear('created_at', $y)->sum('amount_paid');
            $dRev = \Illuminate\Support\Facades\DB::table('downpayment_histories')->whereMonth('created_at', $m)->whereYear('created_at', $y)->sum('amount');
            $fRev = \Illuminate\Support\Facades\DB::table('financing_payment_histories')->whereMonth('created_at', $m)->whereYear('created_at', $y)->sum('amount_paid');
            
            $monthlySalesData[] = $uRev + $dRev + $fRev;
        }

        // 6. Payment Status (Utility Bills)
        $billsPaid = \Illuminate\Support\Facades\DB::table('utility_bills')->where('status', 'paid')->count();
        $billsPending = \Illuminate\Support\Facades\DB::table('utility_bills')->where('status', 'pending')->count();
        $billsOverdue = \Illuminate\Support\Facades\DB::table('utility_bills')->where('status', 'overdue')->count();
        $totalBills = $billsPaid + $billsPending + $billsOverdue;
        $collectionPercent = $totalBills > 0 ? round(($billsPaid / $totalBills) * 100) : 0;

        // 7. Notifications
        $notifications = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('incidents')) {
             $incidents = \App\Models\Incident::orderByDesc('created_at')->take(3)->get()->map(function($i) {
                  return [
                      'type' => 'incident',
                      'title' => 'New Incident Submitted',
                      'desc' => $i->title . ' - ' . \Illuminate\Support\Str::limit($i->description, 30),
                      'timestamp' => $i->created_at,
                      'time' => $i->created_at->diffForHumans(),
                      'color' => 'var(--danger)',
                  ];
             });
             $notifications = $notifications->merge($incidents);
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('appointments')) {
             $appointments = \Illuminate\Support\Facades\DB::table('appointments')->orderByDesc('created_at')->take(3)->get()->map(function($a) {
                  return [
                      'type' => 'appointment',
                      'title' => 'New Appointment Booked',
                      'desc' => \Illuminate\Support\Str::limit($a->type ?? 'No purpose specified', 30),
                      'timestamp' => \Carbon\Carbon::parse($a->created_at),
                      'time' => \Carbon\Carbon::parse($a->created_at)->diffForHumans(),
                      'color' => 'var(--accent)',
                  ];
             });
             $notifications = $notifications->merge($appointments);
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('visitors')) {
             $visitors = \Illuminate\Support\Facades\DB::table('visitors')->orderByDesc('created_at')->take(3)->get()->map(function($v) {
                  return [
                      'type' => 'visitor',
                      'title' => 'New Visitor Request',
                      'desc' => \Illuminate\Support\Str::limit($v->purpose ?? 'No purpose specified', 30),
                      'timestamp' => \Carbon\Carbon::parse($v->created_at),
                      'time' => \Carbon\Carbon::parse($v->created_at)->diffForHumans(),
                      'color' => 'var(--success)',
                  ];
             });
             $notifications = $notifications->merge($visitors);
        }
        $notifications = $notifications->sortByDesc('timestamp')->take(5);

        // 8. Today's Appointments List
        $todaysAppointmentsList = \App\Models\Appointment::query()->whereDate('date', \Carbon\Carbon::today())
            ->orderBy('time')
            ->get();

        return view('admin.dashboard', compact(
            'openIncidentsCount', 
            'appointmentsTodayCount', 
            'totalResidentsCount', 
            'monthlyRevenue',
            'monthlySalesLabels',
            'monthlySalesData',
            'billsPaid',
            'billsPending',
            'billsOverdue',
            'totalBills',
            'collectionPercent',
            'todaysAppointmentsList',
            'notifications'
        ));
    });

    // Master List System
    Route::resource('buyer-master-list', \App\Http\Controllers\Admin\BuyerMasterListController::class);
    Route::post('buyer-master-list/{id}/generate-account', [\App\Http\Controllers\Admin\BuyerMasterListController::class, 'generateAccount'])->name('buyer.generate-account');
    Route::post('buyer-master-list/{id}/restore', [\App\Http\Controllers\Admin\BuyerMasterListController::class, 'restore'])->name('buyer.restore');
    Route::get('/buyer-master-list/{id}/export-msvs', [\App\Http\Controllers\Admin\BuyerMasterListController::class, 'exportMsvs'])->name('admin.buyer.export.msvs');
    Route::get('/buyer-master-list/{id}/export-bvs', [\App\Http\Controllers\Admin\BuyerMasterListController::class, 'exportBvs'])->name('admin.buyer.export.bvs');
    Route::get('/buyer-master-list/{id}/export-housing-loan', [\App\Http\Controllers\Admin\BuyerMasterListController::class, 'exportHousingLoan'])->name('admin.buyer.export.housing_loan');
    Route::get('/buyer-master-list/{id}/export-buyer-conformity', [\App\Http\Controllers\Admin\BuyerMasterListController::class, 'exportBuyerConformity'])->name('admin.buyer.export.buyer_conformity');

    // User Management System
    Route::get('/users', function () {
        $users = \App\Models\User::with(['lots', 'roles', 'buyerMasterList.lot', 'guardAccount'])->get()->map(function (\App\Models\User $u) {
            $role = $u->roles->first()->name ?? 'Resident';
            $lot = $u->lots->first() ?? $u->buyerMasterList?->lot;
            $guard = $u->guardAccount;
            $scheduleDays = $guard ? ($guard->schedule_days ?? []) : [];
            $shiftType = $guard->shift_type ?? 'day';
            $shiftStart = $guard->shift_start ?? '06:00:00';
            $shiftEnd = $guard->shift_end ?? '18:00:00';
            $activeDutyShift = $guard ? $guard->getActiveDutyShift() : null;
            $isOnDuty = $activeDutyShift !== null;
            $activeShiftLabel = $activeDutyShift ? ucfirst($activeDutyShift['shift']) . ' Shift' : null;

            $meta = '';
            if ($u->lots->isNotEmpty()) {
                $meta = $u->lots->map(fn($l) => 'Block ' . $l->block . ', Lot ' . $l->lot_number)->implode(' | ');
            } elseif ($lot) {
                $meta = 'Block ' . $lot->block . ', Lot ' . $lot->lot_number;
            } elseif ($role === 'Security Guard') {
                $meta = $guard ? $guard->formatted_schedule : 'No Schedule Set';
            }

            return [
                'id' => 'USR-' . $u->id,
                'db_id' => $u->id,
                'name' => $u->name,
                'first_name' => $u->first_name,
                'middle_name' => $u->middle_name,
                'last_name' => $u->last_name,
                'email' => $u->email,
                'role' => $role,
                'status' => $u->status ?? 'Active',
                'joined' => $u->created_at->format('Y-m-d'),
                'contact_number' => $u->contact_number,
                'block' => $lot ? $lot->block : '',
                'lot' => $lot ? $lot->lot_number : '',
                'lots' => $u->lots->map(fn($l) => [
                    'id' => $l->id,
                    'block' => $l->block,
                    'lot_number' => $l->lot_number,
                ])->toArray(),
                'meta' => $meta,
                'schedule_days' => $scheduleDays,
                'shift_type' => $shiftType,
                'shift_start' => $shiftStart ? substr($shiftStart, 0, 5) : '06:00',
                'shift_end' => $shiftEnd ? substr($shiftEnd, 0, 5) : '18:00',
                'is_on_duty' => $isOnDuty,
                'active_shift_label' => $activeShiftLabel,
            ];
        })->toArray();
        return view('admin.users.index', ['users' => $users]);
    });
    Route::post('/users/import', [\App\Http\Controllers\Admin\UserImportController::class, 'import']);
    Route::get('/users/import/template', [\App\Http\Controllers\Admin\UserImportController::class, 'downloadTemplate']);
    Route::post('/users', function (\Illuminate\Http\Request $request) {
        $normalizedContact = preg_replace('/[\s\-().]/', '', (string) $request->input('contact_number'));
        $request->merge([
            'contact_number' => $normalizedContact,
            'email' => trim((string) $request->input('email')),
        ]);

        $validated = $request->validate([
            'first_name' => 'required_without:name|nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required_without:name|nullable|string|max:255',
            'name' => 'required_without:first_name|nullable|string|max:255',
            'email' => ['required', 'email:rfc,dns', \Illuminate\Validation\Rule::unique('users', 'email')->whereNull('deleted_at'), function (string $attribute, mixed $value, \Closure $fail) {
                $domain = substr(strrchr(strtolower((string) $value), '@') ?: '', 1);
                if ($domain === '' || (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A'))) {
                    $fail('That email domain does not appear to accept mail. Please use a real, active email address.');
                }
            }],
            'contact_number' => ['required', 'regex:/^(09\d{9}|\+639\d{9}|639\d{9})$/'],
            'role' => 'required|string',
            'status' => 'required|string',
            'password' => 'required|string|min:6',
            'block' => 'required_if:role,Resident|integer',
            'lot' => 'required_if:role,Resident|integer',
        ], [
            'email.required' => 'Email Address is required.',
            'email.email' => 'Please enter a real email address on a domain that can receive mail (Gmail, Outlook, company mail, etc.).',
            'contact_number.required' => 'Contact / Mobile Number is required.',
            'contact_number.regex' => 'Use a Philippine mobile number: 09XXXXXXXXX or +639XXXXXXXXX.',
        ]);

        $userAttributes = [
            'email' => $validated['email'],
            'contact_number' => $validated['contact_number'] ?? null,
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
        ];
        if (! empty($validated['first_name'])) {
            $userAttributes['first_name'] = $validated['first_name'];
            $userAttributes['middle_name'] = trim((string) ($validated['middle_name'] ?? '')) ?: null;
            $userAttributes['last_name'] = $validated['last_name'] ?? null;
        } else {
            $userAttributes['name'] = $validated['name'];
        }
        $user = \App\Models\User::create($userAttributes);
        $user->assignRole($validated['role']);

        if (($validated['role'] ?? '') === 'Resident' && ! empty($validated['block']) && ! empty($validated['lot'])) {
            $lot = \App\Models\Lot::firstOrCreate([
                'block' => (string) $validated['block'],
                'lot_number' => (string) $validated['lot'],
            ]);
            $user->lots()->attach($lot->id);
            $lot->update(['status' => 'Occupied']);
        }

        if (($validated['role'] ?? '') === 'Security Guard') {
            $days = $request->input('schedule_days');
            if (is_string($days)) {
                $days = json_decode($days, true);
            }
            $user->guardAccount()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'schedule_days' => is_array($days) ? $days : [],
                    'shift_type' => $request->input('shift_type', 'day'),
                    'shift_start' => $request->input('shift_start', '06:00:00'),
                    'shift_end' => $request->input('shift_end', '18:00:00'),
                ]
            );
        }

        try {
            \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($validated) {
                $message->to($validated['email'])
                    ->subject('Your Subdivision System Account')
                    ->html("<h2>Welcome to Subdivision System</h2>
                                <p>Your account has been created. Here are your login credentials:</p>
                                <p><strong>Email:</strong> {$validated['email']}<br>
                                <strong>Password:</strong> {$validated['password']}</p>
                                <p>Please login and change your password immediately.</p>");
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send email to new user: ' . $e->getMessage());
        }

        return response()->json(['success' => true]);
    });
    Route::put('/users/{id}', function (\Illuminate\Http\Request $request, $id) {
        $user = \App\Models\User::find($id);
        if ($user) {
            if ($user->status === 'Archived') {
                return response()->json(['success' => false, 'message' => 'Archived users cannot be edited.'], 403);
            }

            $normalizedContact = preg_replace('/[\s\-().]/', '', (string) $request->input('contact_number'));
            $request->merge([
                'contact_number' => $normalizedContact,
                'email' => trim((string) $request->input('email', $user->email)),
            ]);

            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'first_name' => 'required_without:name|nullable|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required_without:name|nullable|string|max:255',
                'name' => 'required_without:first_name|nullable|string|max:255',
                'email' => ['required', 'email:rfc,dns', \Illuminate\Validation\Rule::unique('users', 'email')->whereNull('deleted_at')->ignore($user->id), function (string $attribute, mixed $value, \Closure $fail) {
                    $domain = substr(strrchr(strtolower((string) $value), '@') ?: '', 1);
                    if ($domain === '' || (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A'))) {
                        $fail('That email domain does not appear to accept mail. Please use a real, active email address.');
                    }
                }],
                'contact_number' => ['required', 'regex:/^(09\d{9}|\+639\d{9}|639\d{9})$/'],
                'password' => 'nullable|string|min:6',
                'block' => 'nullable|integer',
                'lot' => 'nullable|integer',
            ], [
                'email.required' => 'Email Address is required.',
                'email.email' => 'Please enter a real email address on a domain that can receive mail (Gmail, Outlook, company mail, etc.).',
                'contact_number.required' => 'Contact / Mobile Number is required.',
                'contact_number.regex' => 'Use a Philippine mobile number: 09XXXXXXXXX or +639XXXXXXXXX.',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();
            if (! empty($validated['first_name'])) {
                $user->first_name = $validated['first_name'];
                $user->middle_name = trim((string) ($validated['middle_name'] ?? '')) ?: null;
                $user->last_name = $validated['last_name'] ?? null;
            } else {
                $user->name = $validated['name'];
            }
            $user->email = $validated['email'];
            $user->contact_number = $validated['contact_number'];
            if (!empty($validated['password'] ?? null)) {
                $user->password = \Illuminate\Support\Facades\Hash::make($validated['password']);
            }
            $user->save();

            if ($request->has('role')) {
                $reason = $user->refusalForRoleChange((string) $request->input('role'));
                if ($reason) {
                    return response()->json(['success' => false, 'message' => $reason], 422);
                }
                $user->syncRoles([$request->input('role')]);
            }
            $roleName = $request->input('role') ?: $user->roles()->first()?->name;
            if ($roleName === 'Resident') {
                if ($request->has('lots_payload')) {
                    $lotData = $request->input('lots_payload');
                    if (is_string($lotData)) {
                        $lotData = json_decode($lotData, true);
                    }
                    if (is_array($lotData)) {
                        $lotIds = [];
                        foreach ($lotData as $item) {
                            if (!empty($item['block']) && !empty($item['lot'])) {
                                $l = \App\Models\Lot::firstOrCreate([
                                    'block' => (string) $item['block'],
                                    'lot_number' => (string) $item['lot'],
                                ]);
                                $l->update(['status' => 'Occupied']);
                                $lotIds[] = $l->id;
                            } elseif (!empty($item['id'])) {
                                $lotIds[] = $item['id'];
                            }
                        }
                        if (!empty($lotIds)) {
                            $user->lots()->sync(array_unique($lotIds));
                        }
                    }
                } elseif ($request->has('block') && $request->has('lot')) {
                    if (!empty($request->input('block')) && !empty($request->input('lot'))) {
                        $lot = \App\Models\Lot::firstOrCreate([
                            'block' => (string) $request->input('block'),
                            'lot_number' => (string) $request->input('lot'),
                        ]);
                        $lot->update(['status' => 'Occupied']);
                        $user->lots()->sync([$lot->id]);
                    }
                }
            }
            if ($roleName === 'Security Guard') {
                $days = $request->input('schedule_days');
                if (is_string($days)) {
                    $days = json_decode($days, true);
                }
                if ($days !== null || $request->has('shift_type')) {
                    $user->guardAccount()->updateOrCreate(
                        ['user_id' => $user->id],
                        [
                            'schedule_days' => is_array($days) ? $days : ($user->guardAccount?->schedule_days ?? []),
                            'shift_type' => $request->input('shift_type', $user->guardAccount?->shift_type ?? 'day'),
                            'shift_start' => $request->input('shift_start', $user->guardAccount?->shift_start ?? '06:00:00'),
                            'shift_end' => $request->input('shift_end', $user->guardAccount?->shift_end ?? '18:00:00'),
                        ]
                    );
                }
            }
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    });
    Route::put('/users/{id}/archive', function ($id) {
        $user = \App\Models\User::find($id);
        if ($user) {
            foreach ($user->lots as $lot) {
                $lot->update(['status' => 'Vacant House']);
            }
            $user->update(['status' => 'Archived']);
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    });
    Route::put('/users/{id}/restore', function ($id) {
        $user = \App\Models\User::find($id);
        if ($user) {
            foreach ($user->lots as $lot) {
                $lot->update(['status' => 'Occupied']);
            }
            $user->update(['status' => 'Active']);
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    });
    Route::get('/residents', function () {
        return redirect('/admin/users');
    });
    Route::get('/billing', function () {
        $bills = getElectricalBills(request('cycle'));
        $lots = getLotStatus();
        $validCycles = getValidBillingCycles('electricity');

        // Consistent Simulated Statistical Data for Graphing (Reliable)
        $yearly_stats = calculateBillingStats('electricity');

        return view('admin.billing.index', [
            'bills' => $bills,
            'lots' => $lots,
            'stats' => $yearly_stats,
            'validCycles' => $validCycles
        ]);
    });

    Route::get('/water', function () {
        $yearly_stats = calculateBillingStats('water');
        $validCycles = getValidBillingCycles('water');
        return view('admin.water.index', [
            'bills' => getWaterBills(request('cycle')),
            'stats' => $yearly_stats,
            'validCycles' => $validCycles
        ]);
    });

    Route::post('/reservation-fee', function (\Illuminate\Http\Request $request) {
        $buyerId = $request->input('buyer_master_list_id') ?: $request->input('buyer_id');
        $buyer = \App\Models\BuyerMasterList::find($buyerId);
        if (! $buyer) {
            return response()->json(['success' => false, 'message' => 'Select a buyer from the Master List.'], 422);
        }

        $lot = null;
        if ($request->filled('lot_id')) {
            $lot = \App\Models\Lot::find($request->input('lot_id'));
        } elseif ($request->filled('block') && ($request->filled('lot') || $request->filled('lot_number') || $request->filled('lot_no'))) {
            $block = (string) $request->input('block');
            $lotNum = (string) ($request->input('lot') ?? $request->input('lot_number') ?? $request->input('lot_no'));
            $lot = \App\Models\Lot::firstOrCreate(
                ['block' => $block, 'lot_number' => $lotNum],
                ['status' => \App\Enums\LotStatus::Available->value, 'provider_managed' => false]
            );
        } elseif ($buyer->lot) {
            $lot = $buyer->lot;
        }

        if (! $lot) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a block and lot to reserve.',
            ], 422);
        }

        $hasActiveRes = \App\Models\Reservation::where('lot_id', $lot->id)
            ->whereIn('status', \App\Enums\ReservationStatus::activeValues())
            ->exists();
        if ($hasActiveRes || in_array($lot->status, [\App\Enums\LotStatus::Reserved->value, \App\Enums\LotStatus::Occupied->value], true)) {
            return response()->json([
                'success' => false,
                'message' => "Block {$lot->block} Lot {$lot->lot_number} is already reserved or occupied. Please choose an available lot.",
            ], 422);
        }

        $name = trim($buyer->first_name . ' ' . $buyer->last_name);
        $contact = $buyer->contact_number;

        try {
            $res = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $buyer, $lot, $name, $contact) {
                $rawAmount = str_replace(',', '', (string) ($request->input('amount') ?? '20000'));
                $amount = is_numeric($rawAmount) ? (float) $rawAmount : 20000;

                $res = \App\Models\Reservation::create([
                    'lot_id' => $lot->id,
                    'buyer_master_list_id' => $buyer->id,
                    'status' => \App\Enums\ReservationStatus::Reserved->value,
                    'reservation_date' => now(),
                    'deadline_date' => $request->input('deadline_date') ? \Carbon\Carbon::parse($request->input('deadline_date')) : null,
                    'amount' => $amount,
                    'notes' => ($name ?: 'New Buyer') . ' | ' . ($contact ?: 'N/A')
                ]);

                $lot->update(['status' => \App\Enums\LotStatus::Reserved->value]);

                return $res;
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return response()->json(['success' => false, 'message' => 'Lot already has an active reservation.'], 422);
        }

        return response()->json(['success' => true, 'id' => $res->id]);
    });

    Route::post('/reservation-fee/cancel', function (\Illuminate\Http\Request $request) {
        $res = \App\Models\Reservation::whereKey($request->input('id'))->first();
        if ($res) {
            $res->update(['status' => \App\Enums\ReservationStatus::Cancelled->value]);
            if ($res->lot) {
                $res->lot->update(['status' => \App\Enums\LotStatus::Available->value]);
            }
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    });

    Route::post('/downpayment-fee', function (\Illuminate\Http\Request $request) {
        $buyerId = $request->input('buyer_master_list_id');
        if (! $buyerId) {
            return response()->json(['success' => false, 'message' => 'Please select a buyer from the Master List.'], 422);
        }

        $contractAmount = $request->input('contract_amount');
        $rawDigits = preg_replace('/\D/', '', (string) $contractAmount);
        if (empty($rawDigits) || strlen($rawDigits) < 6 || (float) $contractAmount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unit Price (TCP) must be a valid number of at least 6 digits (minimum ₱100,000).'
            ], 422);
        }

        $monthsToPay = (int) $request->input('months_to_pay', 24);
        if ($monthsToPay < 1) {
            return response()->json(['success' => false, 'message' => 'Months to pay must be at least 1 month.'], 422);
        }

        $penalty = (float) $request->input('penalty_percentage', 0);
        if ($penalty < 0) {
            return response()->json(['success' => false, 'message' => 'Penalty percentage cannot be negative.'], 422);
        }
        
        // Check duplication on this specific reservation or lot
        if ($request->filled('reservation_id')) {
            $existingRes = \App\Models\Reservation::where('id', $request->input('reservation_id'))
                ->whereHas('downpayments')
                ->first();
            if ($existingRes) {
                return response()->json(['success' => false, 'message' => 'This reservation already has an active downpayment schedule.'], 403);
            }
        }

        $block = $request->input('block');
        $lotNumber = $request->input('lot');
        $lotId = null;
        if ($block && $lotNumber) {
            $lotId = \App\Models\Lot::where('block', (string) $block)
                ->where('lot_number', (string) $lotNumber)
                ->value('id');
            if ($lotId) {
                $alreadyHasDp = \App\Models\Downpayment::whereHas('reservation', function ($q) use ($lotId, $buyerId) {
                    $q->where('lot_id', $lotId)->where('buyer_id', $buyerId);
                })->exists();
                if ($alreadyHasDp) {
                    return response()->json(['success' => false, 'message' => 'This lot already has an active downpayment schedule.'], 403);
                }
            }
        }

        $resQuery = \App\Models\Reservation::where('buyer_id', $buyerId)
            ->whereIn('status', [
                \App\Enums\ReservationStatus::Reserved->value,
                \App\Enums\ReservationStatus::Pending->value,
                \App\Enums\ReservationStatus::Converted->value,
            ])
            ->whereDoesntHave('downpayments')
            ->orderByDesc('created_at');

        if ($request->filled('reservation_id')) {
            $resQuery->whereKey($request->input('reservation_id'));
        } elseif ($lotId) {
            $resQuery->where('lot_id', $lotId);
        }

        $res = $resQuery->first();
        if (! $res) {
            $buyer = \App\Models\BuyerMasterList::find($buyerId);
            $block = $block ?: ($buyer?->block_no ?: '1');
            $lotNumber = $lotNumber ?: ($buyer?->lot_no ?: '1');

            $lot = \App\Models\Lot::firstOrCreate(
                ['block' => (string) $block, 'lot_number' => (string) $lotNumber],
                ['status' => \App\Enums\LotStatus::Reserved->value, 'provider_managed' => false]
            );

            $res = \App\Models\Reservation::create([
                'buyer_id' => $buyerId,
                'lot_id' => $lot->id,
                'amount' => 0,
                'status' => \App\Enums\ReservationStatus::Converted->value,
                'reservation_date' => now(),
                'notes' => $buyer ? "{$buyer->first_name} {$buyer->last_name}" : 'New Buyer'
            ]);
        }

        try {
            $dp = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $buyerId, $res) {
                $res->status = \App\Enums\ReservationStatus::Converted->value;
                $res->save();

                $dp = \App\Models\Downpayment::create([
                    'reservation_id' => $res->id,
                    'amount' => 0,
                    'balance' => $request->input('dpAmount') ?? 0,
                    'due_date' => \Carbon\Carbon::parse($request->input('nextDate') ?? now()->addMonth()),
                    'status' => \App\Enums\DownpaymentStatus::GoodStanding->value,
                    'monthly_amortization' => $request->input('monthly_amortization') ?? 15000,
                    'months_to_pay' => $request->input('months_to_pay') ?? 24,
                    'contract_date' => $request->input('contract_date') ? \Carbon\Carbon::parse($request->input('contract_date')) : now(),
                    'penalty_percentage' => $request->input('penalty_percentage') ?? 0
                ]);

                // "Client First" Workflow: Sync back the newly established contract details to the Masterlist
                $buyer = \App\Models\BuyerMasterList::with('financingRecord')->find($request->input('buyer_master_list_id'));
                if ($buyer) {
                    $lotId = \App\Models\Lot::where('block', $request->input('block'))
                        ->where('lot_number', $request->input('lot'))
                        ->value('id') ?? $res->lot_id;
                    $buyer->lot_id = $lotId;
                    $buyer->save();

                    $contract = (float) ($request->input('contract_amount') ?? 0);
                    $equity = (float) ($request->input('dpAmount') ?? 0);
                    $record = $buyer->financingRecord ?? new \App\Models\FinancingRecord([
                        'buyer_id' => $buyer->id,
                        'lot_id' => $lotId,
                        'status' => \App\Enums\FinancingStatus::Pending->value,
                    ]);
                    $record->lot_id = $record->lot_id ?: $lotId;
                    $record->contract_price = $contract;
                    $record->total_consideration = $contract
                        + (float) $record->mbrdc_add_fees
                        + (float) $record->other_exp_annotation
                        + (float) $record->improvements_fee;
                    $record->paid_by_vendee_equity = $equity;
                    $record->mbrdc_amt_due_for_financing = $record->total_consideration - $equity;
                    $retentionOnHold = $record->retention_on_hold === null ? true : (bool) $record->retention_on_hold;
                    $totalDeductions = (float) $record->inspection_fee
                        + ($retentionOnHold ? (float) $record->retention_fee : 0)
                        + (float) $record->sri_mri
                        + (float) $record->pag_ibig_non_life
                        + (float) $record->interim_mri;
                    $record->total_amt_due = $totalDeductions;
                    $record->net_loan_proceeds = (float) $record->loan_release - $totalDeductions;
                    $record->receivables = max(0, (float) $record->mbrdc_amt_due_for_financing - (float) $record->loan_release + (float) $record->additional_bill_of_materials + (float) $record->mbrdc_turn_over_fee);
                    $record->balance = max(0, (float) $record->receivables - (float) $record->amount_paid);
                    $record->save();
                }

                return $dp;
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return response()->json(['success' => false, 'message' => 'This lot already has an active reservation, or this reservation already has a downpayment schedule.'], 422);
        }

        return response()->json(['success' => true, 'id' => $dp->id]);
    });

    Route::post('/downpayment-fee/pay', function (\Illuminate\Http\Request $request) {
        $dp = \App\Models\Downpayment::whereKey($request->input('id'))->first();
        if ($dp) {
            $rawDate = $request->input('payment_date');
            $paidAt = now();
            if ($rawDate) {
                $parsed = \Carbon\Carbon::parse($rawDate);
                if (strlen(trim($rawDate)) <= 10) {
                    $parsed->setTime(now()->hour, now()->minute, now()->second);
                }
                $paidAt = $parsed;
            }

            $history = app(\App\Services\PaymentRecorder::class)->recordDownpaymentPayment(
                $dp,
                (float) $request->input('amount'),
                'Office Payment',
                null,
                null,
                $paidAt
            );

            return response()->json([
                'success' => true,
                'trn' => $history->trn,
                'id' => $history->id,
                'next_due' => $dp->due_date ? \Carbon\Carbon::parse($dp->due_date)->format('Y-m-d') : null
            ]);
        }
        return response()->json(['success' => false], 404);
    });

    Route::get('/reservation-fee', function () {
        \App\Models\Reservation::whereHas('downpayments')
            ->where('status', '!=', \App\Enums\ReservationStatus::Converted->value)
            ->update(['status' => \App\Enums\ReservationStatus::Converted->value]);

        $yearly_stats = [
            '2026' => [
                'May' => 400000,
                'Jun' => 400000,
                'Jul' => 400000,
                'Aug' => 400000,
                'Sep' => 400000,
                'Oct' => 400000,
                'Nov' => 400000,
                'Dec' => 400000,
                'Jan' => 400000,
                'Feb' => 400000,
                'Mar' => 400000,
                'Apr' => 400000
            ],
            'today_paid' => 40000.00
        ];
        
        // Fetch all master list buyers for selection (supports multiple lot purchases)
        $masterListBuyers = \App\Models\BuyerMasterList::with(['user', 'reservations.lot'])
            ->get()
            ->sortBy(fn ($buyer) => $buyer->last_name)
            ->values();

        $availableLots = \App\Models\Lot::where('status', \App\Enums\LotStatus::Available->value)
            ->whereDoesntHave('reservations', function ($q) {
                $q->whereIn('status', \App\Enums\ReservationStatus::activeValues());
            })
            ->orderBy('block')
            ->orderBy('lot_number')
            ->get();

        return view('admin.reservation-fee.index', [
            'bills' => getReservationFees(),
            'stats' => $yearly_stats,
            'masterListBuyers' => $masterListBuyers,
            'availableLots' => $availableLots
        ]);
    });

    Route::get('/downpayment-fee', function () {
        $yearly_stats = [
            '2026' => [
                'May' => 300000,
                'Jun' => 300000,
                'Jul' => 300000,
                'Aug' => 300000,
                'Sep' => 300000,
                'Oct' => 300000,
                'Nov' => 300000,
                'Dec' => 300000,
                'Jan' => 300000,
                'Feb' => 300000,
                'Mar' => 300000,
                'Apr' => 300000
            ],
            'today_paid' => 15000.00
        ];
        
        \App\Models\Reservation::whereHas('downpayments')
            ->where('status', '!=', \App\Enums\ReservationStatus::Converted->value)
            ->update(['status' => \App\Enums\ReservationStatus::Converted->value]);

        // Fetch all buyers from the Master List
        // Eager-load their financing record and any reservations that don't have downpayments yet
        $masterListBuyers = \App\Models\BuyerMasterList::with([
            'financingRecord',
            'reservations' => function ($q) {
                $q->whereDoesntHave('downpayments')->with('lot');
            }
        ])
        ->get()
        ->sortBy(fn ($buyer) => $buyer->last_name)
        ->values();

        return view('admin.downpayment-fee.index', [
            'bills' => getDownpaymentFees(),
            'stats' => $yearly_stats,
            'masterListBuyers' => $masterListBuyers
        ]);
    });
    Route::get('/appointments', function () {
        return view('admin.appointments.index', ['appointments' => getAppointments()]);
    });
    Route::get('/incidents', function () {
        return view('admin.incidents.index', ['incidents' => getIncidents()]);
    });
    Route::get('/incidents/{id}', function ($id) {
        $inc = \App\Models\Incident::with(['user.roles', 'user.lots'])->find($id);
        if (!$inc) {
            abort(404);
        }

        $resName = $inc->reporter_name ?: 'Anonymous';
        if ($inc->user) {
            $role = $inc->user->roles->first()->name ?? 'Resident';
            if ($role === 'Resident' && $inc->user->lots->count() > 0) {
                $lot = $inc->user->lots->first();
                $resName = 'Block ' . $lot->block . ', Lot ' . $lot->lot_number;
            } else {
                $resName = $inc->user->name ?: ($inc->reporter_name ?: 'Anonymous');
            }
        }
        $photosArray = [];
        if ($inc->image_url) {
            $decoded = json_decode($inc->image_url, true);
            $photosArray = is_array($decoded) ? $decoded : [$inc->image_url];
        }

        $incident = [
            'id' => $inc->id,
            'sub' => $inc->subject,
            'type' => $inc->type,
            'res' => $resName,
            'desc' => $inc->description,
            'img' => count($photosArray) > 0 ? $photosArray[0] : null,
            'photos' => $photosArray,
            'video' => $inc->video_url,
            'video_url' => $inc->video_url,
            'admin_notes' => $inc->admin_notes,
            'status' => $inc->status,
            'date' => $inc->created_at ? $inc->created_at->toIso8601String() : now()->toIso8601String(),
            'created_at' => $inc->created_at,
        ];

        return view('admin.incidents.show', ['inc' => $incident, 'id' => $id]);
    });

    Route::put('/incidents/{id}/status', function (\Illuminate\Http\Request $request, $id) {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'status' => ['required', \Illuminate\Validation\Rule::in(\App\Enums\IncidentStatus::values())],
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid incident status.'], 422);
        }

        $inc = \App\Models\Incident::findOrFail($id);
        $inc->update(['status' => $request->input('status')]);

        if ($inc->user) {
            $inc->user->notify(new \App\Notifications\WebAndPushNotification(
                'Incident Report Update',
                "Your incident report '{$inc->subject}' status has been updated to: {$request->input('status')}.",
                'incident'
            ));
        }

        return response()->json(['success' => true]);
    });

    Route::post('/incidents/{id}/notes', function (\Illuminate\Http\Request $request, $id) {
        $request->validate([
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        $inc = \App\Models\Incident::findOrFail($id);
        $inc->update(['admin_notes' => $request->input('admin_notes')]);

        return response()->json(['success' => true]);
    });
    Route::get('/announcements', function () {
        return view('admin.announcements.index', ['announcements' => getAnnouncements()]);
    });

    Route::post('/announcements', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'content' => 'required|string',
        ]);

        $admin = \Illuminate\Support\Facades\Auth::user();
        \App\Models\AdminAccount::firstOrCreate(['user_id' => $admin->id]);

        $ann = \App\Models\Announcement::create([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'content' => $validated['content'],
            'author' => $admin->name ?? 'Admin',
            'admin_id' => $admin->id,
            'status' => 'Published',
            'publish_date' => now(),
        ]);

        $now = now();
        $recipientRows = \App\Models\Resident::query()->pluck('user_id')->map(fn ($residentId) => [
            'announcement_id' => $ann->id,
            'resident_id' => $residentId,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();
        if ($recipientRows !== []) {
            \Illuminate\Support\Facades\DB::table('announcement_recipients')->insert($recipientRows);
        }

        \App\Jobs\SendAnnouncementEmails::dispatch($ann);

        return response()->json(['success' => true, 'message' => 'Announcement posted and emails sent.']);
    });
    Route::get('/visitors', function () {
        return view('admin.visitors.index');
    });
    Route::get('/gis', function () {
        return view('admin.gis.index'); });
    Route::post('/gis/update-occupancy', function (\Illuminate\Http\Request $request) {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'status' => ['required', \Illuminate\Validation\Rule::in(\App\Enums\LotStatus::values())],
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid lot status. Allowed: ' . implode(', ', \App\Enums\LotStatus::values()) . '.'], 422);
        }

        $lot = \App\Models\Lot::firstOrCreate(
            ['block' => $request->input('block'), 'lot_number' => $request->input('lot')]
        );

        $occupiedByResident = $lot->users()->where(function ($q) {
            $q->where('status', '!=', 'Archived')->orWhereNull('status');
        })->exists();
        if ($occupiedByResident) {
            return response()->json(['success' => false, 'message' => 'Lot is occupied and cannot be changed.'], 403);
        }

        $lot->update(['status' => $request->input('status')]);
        return response()->json(['success' => true]);
    });

    Route::post('/gis/update-lot-details', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'block' => 'required|string|max:50',
            'lot' => 'required|string|max:50',
            'lot_area' => 'nullable|numeric|min:0',
            'floor_area' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:2000',
            'tct_no' => 'nullable|string|max:255',
            'pid' => 'nullable|string|max:255',
            'tax_dec_no' => 'nullable|string|max:255',
        ]);

        $lot = \App\Models\Lot::firstOrCreate(
            ['block' => (string) $validated['block'], 'lot_number' => (string) $validated['lot']],
            ['status' => \App\Enums\LotStatus::VacantLot->value]
        );

        $lot->update([
            'lot_area' => $validated['lot_area'] ?? null,
            'floor_area' => $validated['floor_area'] ?? null,
            'description' => $validated['description'] ?? null,
            'tct_no' => $validated['tct_no'] ?? null,
            'pid' => $validated['pid'] ?? null,
            'tax_dec_no' => $validated['tax_dec_no'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'lot' => $lot->fresh(),
        ]);
    });

    // Financing System
    Route::resource('financing', \App\Http\Controllers\FinancingRecordController::class);
    Route::post('financing/{id}/pay', [\App\Http\Controllers\FinancingRecordController::class, 'recordPayment'])->name('financing.pay');
});

Route::post('/api/settings', function (\Illuminate\Http\Request $request) {
    if ($request->has('water_rate')) {
        \App\Models\Setting::updateOrCreate(['key' => 'water_rate'], ['value' => $request->input('water_rate')]);
    }
    if ($request->has('elec_rate')) {
        \App\Models\Setting::updateOrCreate(['key' => 'elec_rate'], ['value' => $request->input('elec_rate')]);
    }
    if ($request->has('unavailable_dates')) {
        \App\Models\Setting::updateOrCreate(
            ['key' => 'unavailable_dates'],
            ['value' => json_encode($request->input('unavailable_dates'))]
        );
    }
    return response()->json(['success' => true]);
});

// Buyer Routes
Route::prefix('buyer')->middleware(['auth', 'role:Buyer', 'sync_paymongo'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\BuyerPortalController::class, 'index'])->name('buyer.dashboard');
    Route::get('/payments', [\App\Http\Controllers\BuyerPortalController::class, 'payments'])->name('buyer.payments');
    Route::get('/payments/downpayment/history', [\App\Http\Controllers\BuyerPortalController::class, 'dpHistory'])->name('buyer.payments.dp.history');
    Route::get('/payments/downpayment/receipt/{id}', [\App\Http\Controllers\BuyerPortalController::class, 'downloadDpReceipt'])->name('buyer.payments.dp.receipt');
    Route::post('/api/downpayment/pay', [\App\Http\Controllers\BuyerPortalController::class, 'payDownpayment']);
    Route::post('/api/financing/pay', [\App\Http\Controllers\BuyerPortalController::class, 'payFinancing']);
});

// Resident Portal Routes
Route::prefix('resident')->middleware(['auth', 'role:Resident', 'sync_paymongo'])->group(function () {
    $buildEmptyBill = function(string $type, $user, $lot = null) {
        $lot = $lot ?: ($user ? $user->lots()->first() : null);
        $unit = $type === 'electricity' ? 'kWh' : 'm³';
        $emptyHistory = array_map(function($i) {
            return [
                'label' => date('M Y', strtotime("-$i months")),
                'value' => 0
            ];
        }, range(11, 0));

        return [
            'id' => 'N/A',
            'db_id' => null,
            'resident_id' => $user?->id,
            'lot' => $lot ? 'B' . $lot->block . ' L' . $lot->lot_number : 'N/A',
            'block' => $lot ? $lot->block : 'N/A',
            'resident' => $user?->name ?? 'Resident',
            'period' => 'N/A',
            'provider_managed' => $lot ? $lot->provider_managed : false,
            'amount' => 0,
            'amount_before_due' => 0,
            'amount_after_due' => 0,
            'penalty_rate' => 5,
            'penalty_amount' => 0,
            'applied_penalty' => 0,
            'remaining_balance' => 0,
            'usage' => '0 ' . $unit,
            'usage_' . ($type === 'electricity' ? 'kwh' : 'cubic') => 0,
            'usage_cbm' => 0,
            'base_amount' => 0,
            'previous_balance' => 0,
            'total_paid' => 0,
            'status' => 'no-bill',
            'due' => 'N/A',
            'paid_date' => null,
            'method' => null,
            'usage_history' => $emptyHistory,
            'amount_history' => $emptyHistory,
            'at_risk' => false,
            'payment_history' => [],
            'payment_behavior' => 'On-Time',
            'audit_log' => [],
            'rate' => 0,
            'prev' => 0,
            'curr' => 0,
            'prev_reading' => 0,
            'curr_reading' => 0,
            'prev_reading_date' => 'N/A',
            'curr_reading_date' => 'N/A',
        ];
    };

    Route::get('/dashboard', function () use ($buildEmptyBill) {
        $user = \Illuminate\Support\Facades\Auth::user();
        $lots = $user->lots;
        $selectedLotId = request('lot_id') ?: session('resident_selected_lot_id');
        if ($selectedLotId && !$lots->contains('id', $selectedLotId)) {
            $selectedLotId = null;
        }
        if (!$selectedLotId) {
            $selectedLotId = $lots->first()?->id;
        }
        if ($selectedLotId) {
            session(['resident_selected_lot_id' => $selectedLotId]);
        }
        $selectedLot = $lots->firstWhere('id', $selectedLotId) ?? $lots->first();

        $elecBills = getElectricalBills(request('cycle'), true, $user->id, $selectedLotId);
        $waterBills = getWaterBills(request('cycle'), true, $user->id, $selectedLotId);

        $elecBill = collect($elecBills)->first(function($b) use ($user) {
            return (isset($b['resident_id']) && $b['resident_id'] === $user->id) || ($b['resident'] ?? '') === $user->name;
        }) ?? $buildEmptyBill('electricity', $user, $selectedLot);

        $waterBill = collect($waterBills)->first(function($b) use ($user) {
            return (isset($b['resident_id']) && $b['resident_id'] === $user->id) || ($b['resident'] ?? '') === $user->name;
        }) ?? $buildEmptyBill('water', $user, $selectedLot);

        $activeIncidentsCount = \App\Models\Incident::where('resident_id', $user->id)
            ->whereIn('status', ['pending', 'progress', 'Open / Pending', 'In Progress'])
            ->count();

        $announcements = \App\Models\Announcement::orderBy('created_at', 'desc')->take(10)->get();

        return view('resident.dashboard', [
            'elecBill' => $elecBill,
            'waterBill' => $waterBill,
            'activeIncidentsCount' => $activeIncidentsCount,
            'announcements' => $announcements,
            'lots' => $lots,
            'selectedLot' => $selectedLot,
            'selectedLotId' => $selectedLotId,
        ]);
    });
    Route::get('/profile', function () {
        return view('resident.profile');
    });
    Route::put('/profile/password', function (\Illuminate\Http\Request $request) {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        \Illuminate\Support\Facades\Auth::user()->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.'
        ]);
    });
    Route::get('/map', function () {
        return view('resident.map');
    });
    Route::get('/visitors', function () {
        return view('resident.visitors.index');
    });

    Route::get('/bills/history/{type}', function ($type) {
        if (!in_array($type, ['electricity', 'water'])) abort(404);
        
        $user = \Illuminate\Support\Facades\Auth::user();
        
        $latestBill = \App\Models\UtilityBill::where('resident_id', $user->id)
            ->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->first();
            
        $query = \App\Models\UtilityBill::with('payments')
            ->where('resident_id', $user->id)
            ->where('type', $type)
            ->orderBy('created_at', 'desc');
        if (request('lot_id')) {
            $query->where('lot_id', request('lot_id'));
        }
        // Allow residents access to all their statements (both paid and unpaid)
        $bills = $query->get();
        return view('resident.history', compact('bills', 'type'));
    });

    Route::get('/bills/history/{type}/pdf/{id}', function ($type, $id) {
        $bill = \App\Models\UtilityBill::with(['user', 'lot', 'payments'])->findOrFail($id);
        $user = \Illuminate\Support\Facades\Auth::user();
        $userLotIds = $user->lots()->pluck('lots.id');
        $isOwner = ($bill->resident_id === $user->id) || ($userLotIds->contains($bill->lot_id));
        if (!$isOwner) abort(403);
        
        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadView('resident.bill_pdf', compact('bill'));
        $isPaid = strtolower((string) $bill->status) === 'paid';
        $prefix = $isPaid ? 'Receipt_' : 'Statement_of_Account_';
        return $pdf->stream($prefix . $type . '_' . \Carbon\Carbon::parse($bill->created_at)->format('Y_m') . '.pdf');
    });

    Route::get('/electricity', function () use ($buildEmptyBill) {
        $user = \Illuminate\Support\Facades\Auth::user();
        $lots = $user->lots;
        $selectedLotId = request('lot_id') ?: session('resident_selected_lot_id');
        if ($selectedLotId && !$lots->contains('id', $selectedLotId)) {
            $selectedLotId = null;
        }
        if (!$selectedLotId) {
            $selectedLotId = $lots->first()?->id;
        }
        if ($selectedLotId) {
            session(['resident_selected_lot_id' => $selectedLotId]);
        }
        $selectedLot = $lots->firstWhere('id', $selectedLotId) ?? $lots->first();

        $elecBills = getElectricalBills(request('cycle'), true, $user->id, $selectedLotId);
        $providerManaged = $selectedLot ? $selectedLot->provider_managed : false;

        $elecBill = collect($elecBills)->first(function($b) use ($user) {
            return (isset($b['resident_id']) && $b['resident_id'] === $user->id) || ($b['resident'] ?? '') === $user->name;
        }) ?? $buildEmptyBill('electricity', $user, $selectedLot);

        return view('resident.electricity', [
            'elecBill' => $elecBill,
            'lots' => $lots,
            'selectedLot' => $selectedLot,
            'selectedLotId' => $selectedLotId,
        ]);
    });
    Route::get('/water', function () use ($buildEmptyBill) {
        $user = \Illuminate\Support\Facades\Auth::user();
        $lots = $user->lots;
        $selectedLotId = request('lot_id') ?: session('resident_selected_lot_id');
        if ($selectedLotId && !$lots->contains('id', $selectedLotId)) {
            $selectedLotId = null;
        }
        if (!$selectedLotId) {
            $selectedLotId = $lots->first()?->id;
        }
        if ($selectedLotId) {
            session(['resident_selected_lot_id' => $selectedLotId]);
        }
        $selectedLot = $lots->firstWhere('id', $selectedLotId) ?? $lots->first();

        $waterBills = getWaterBills(request('cycle'), true, $user->id, $selectedLotId);

        $waterBill = collect($waterBills)->first(function($b) use ($user) {
            return (isset($b['resident_id']) && $b['resident_id'] === $user->id) || ($b['resident'] ?? '') === $user->name;
        }) ?? $buildEmptyBill('water', $user, $selectedLot);

        return view('resident.water', [
            'waterBill' => $waterBill,
            'lots' => $lots,
            'selectedLot' => $selectedLot,
            'selectedLotId' => $selectedLotId,
        ]);
    });
    Route::get('/incidents', function () {
        $user = \Illuminate\Support\Facades\Auth::user();
        $reports = \App\Models\Incident::where('resident_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        $dutyContact = getOnDutyGuardEmergencyContact();
        $admin = \App\Models\User::role('Admin')->whereNotNull('contact_number')->first();
        $adminPhone = $dutyContact['phone'] ?: ($admin?->contact_number ?? null);

        return view('resident.incidents', [
            'incidents' => $reports,
            'adminPhone' => $adminPhone,
            'dutyContact' => $dutyContact,
        ]);
    });
    Route::post('/incidents', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'description' => 'required|string',
            'photos' => 'nullable',
            'photos.*' => 'nullable|file|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'video' => 'nullable|file|mimes:mp4,webm,mov,quicktime|max:102400',
        ]);

        $imageUrls = [];

        // 1. Handle multipart photo files
        if ($request->hasFile('photos')) {
            $uploadedPhotos = $request->file('photos');
            if (is_array($uploadedPhotos)) {
                foreach ($uploadedPhotos as $photoFile) {
                    if (count($imageUrls) >= 3) break;
                    if ($photoFile && $photoFile->isValid()) {
                        $storedPath = $photoFile->store('incidents', 'public');
                        $imageUrls[] = '/storage/' . $storedPath;
                    }
                }
            }
        }

        // 2. Fallback: Handle base64 photos if sent as JSON payload
        $base64Photos = $request->input('photos');
        if (empty($imageUrls) && is_array($base64Photos) && count($base64Photos) > 0) {
            foreach ($base64Photos as $photo) {
                if (count($imageUrls) >= 3) break;
                if (is_string($photo) && preg_match('/^data:image\/(\w+);base64,/', $photo, $type)) {
                    $photoData = substr($photo, strpos($photo, ',') + 1);
                    $type = strtolower($type[1]);
                    if (in_array($type, ['jpg', 'jpeg', 'gif', 'png', 'webp'])) {
                        $photoData = str_replace(' ', '+', $photoData);
                        $photoDecoded = base64_decode($photoData);
                        if ($photoDecoded !== false) {
                            $filename = 'incidents/' . \Illuminate\Support\Str::random(20) . '.' . $type;
                            \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $photoDecoded);
                            $imageUrls[] = '/storage/' . $filename;
                        }
                    }
                }
            }
        }

        // 3. Handle video upload
        $videoUrl = null;
        if ($request->hasFile('video') && $request->file('video')->isValid()) {
            $videoPath = $request->file('video')->store('incidents/videos', 'public');
            $videoUrl = '/storage/' . $videoPath;
        }

        $inc = \App\Models\Incident::create([
            'subject' => $validated['subject'],
            'type' => $validated['type'],
            'description' => $validated['description'],
            'status' => 'pending',
            'resident_id' => \Illuminate\Support\Facades\Auth::id(),
            'reporter_name' => \Illuminate\Support\Facades\Auth::user()?->name,
            'image_url' => count($imageUrls) > 0 ? json_encode($imageUrls) : null,
            'video_url' => $videoUrl,
        ]);
        
        $admins = \App\Models\User::role('Admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\WebAndPushNotification(
                'New Incident Report',
                'A new incident report "' . $inc->subject . '" has been submitted by a resident.',
                'incident'
            ));
        }

        return response()->json([
            'success' => true,
            'id' => $inc->id,
            'reference_id' => 'INC-' . str_pad($inc->id, 5, '0', STR_PAD_LEFT),
        ]);
    });

    Route::put('/incidents/{id}/status', function (\Illuminate\Http\Request $request, $id) {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user->hasAnyRole(['Admin', 'Security Guard'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Only administration can update incident statuses.'], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'status' => ['required', \Illuminate\Validation\Rule::in(\App\Enums\IncidentStatus::values())],
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid incident status. Allowed: ' . implode(', ', \App\Enums\IncidentStatus::values()) . '.'], 422);
        }

        $inc = \App\Models\Incident::whereKey($id)->first();
        if ($inc) {
            $inc->update(['status' => $request->input('status')]);
            if ($inc->user) {
                $inc->user->notify(new \App\Notifications\WebAndPushNotification(
                    'Incident Report Update',
                    "Your incident report '{$inc->subject}' status has been updated to: {$request->input('status')}.",
                    'incident'
                ));
            }
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false, 'message' => 'Incident not found.'], 404);
    });
    Route::get('/notifications', function () {
        $user = \Illuminate\Support\Facades\Auth::user();
        $user->unreadNotifications->markAsRead();
        
        $announcements = \App\Models\Announcement::orderBy('created_at', 'desc')->get();
        $systemNotifications = $user->notifications()->get();
        
        return view('resident.notifications', [
            'announcements' => $announcements,
            'systemNotifications' => $systemNotifications
        ]);
    });
});

Route::get('/visitor-pin', function () {
    return view('visitor-pin');
});

Route::get('/routing-guide', function () {
    return view('visitor.routing');
});

// Direct Visitor Pass Route (from SMS Link - no manual PIN input required)
Route::get('/v/{token}', [\App\Http\Controllers\VisitorController::class, 'showDirectPass'])->name('visitor.direct-pass');
Route::get('/api/v/{token}/status', [\App\Http\Controllers\VisitorController::class, 'checkDirectPassStatus'])->name('visitor.direct-pass.status');

// Finance Officer Portal Routes
Route::prefix('finance')->middleware(['auth', 'role:Finance Officer'])->group(function () {
    Route::get('/dashboard', function () {
        $elecCycles = getValidBillingCycles('electricity');
        $waterCycles = getValidBillingCycles('water');
        $validCycles = collect(array_merge($elecCycles, $waterCycles))
            ->unique('cycle')
            ->sortByDesc('cycle')
            ->values()
            ->toArray();

        $cycle = request('cycle');
        if (!$cycle && !empty($validCycles)) {
            $cycle = $validCycles[0]['cycle'];
        }

        $dt = $cycle ? \Carbon\Carbon::createFromFormat('Y-m', $cycle)->startOfMonth() : now()->startOfMonth();

        $lots = \App\Models\Lot::has('users')->with([
            'users',
            'utilityBills' => function ($q) {
                $q->orderByRaw('COALESCE(billing_period, created_at) DESC');
            }
        ])->orderBy('block')->orderBy('lot_number')->get();

        $houses = [];
        foreach ($lots as $lot) {
            $user = $lot->users->first();
            $elecBills = $lot->utilityBills->where('type', 'electricity');
            $waterBills = $lot->utilityBills->where('type', 'water');

            $elec_bill = $elecBills->filter(function ($b) use ($dt) {
                $c = \Carbon\Carbon::parse($b->billing_period ?: $b->created_at);
                return $c->year == $dt->year && $c->month == $dt->month;
            })->first();

            $water_bill = $waterBills->filter(function ($b) use ($dt) {
                $c = \Carbon\Carbon::parse($b->billing_period ?: $b->created_at);
                return $c->year == $dt->year && $c->month == $dt->month;
            })->first();

            // Electricity Baseline & Readings
            $prev_elec = 0;
            $curr_elec = null;
            $is_baseline_elec = false;
            if ($elec_bill) {
                $prev_elec = $elec_bill->previous_reading ?? 0;
                $curr_elec = ($elec_bill->current_reading_date !== null || $elec_bill->usage_value > 0 || $elec_bill->amount > 0) ? $elec_bill->current_reading : null;
                $priorElecBill = $elecBills->filter(function ($b) use ($dt) {
                    $c = \Carbon\Carbon::parse($b->billing_period ?: $b->created_at);
                    return $c->lt($dt);
                })->first();
                if (!$priorElecBill || $prev_elec == 0) {
                    $is_baseline_elec = true;
                }
            } else {
                $latest_elec = $elecBills->first();
                if ($latest_elec) {
                    $prev_elec = $latest_elec->current_reading ?? $latest_elec->previous_reading ?? 0;
                } else {
                    $is_baseline_elec = true;
                }
            }

            // Water Baseline & Readings
            $prev_water = 0;
            $curr_water = null;
            $is_baseline_water = false;
            if ($water_bill) {
                $prev_water = $water_bill->previous_reading ?? 0;
                $curr_water = ($water_bill->current_reading_date !== null || $water_bill->usage_value > 0 || $water_bill->amount > 0) ? $water_bill->current_reading : null;
                $priorWaterBill = $waterBills->filter(function ($b) use ($dt) {
                    $c = \Carbon\Carbon::parse($b->billing_period ?: $b->created_at);
                    return $c->lt($dt);
                })->first();
                if (!$priorWaterBill || $prev_water == 0) {
                    $is_baseline_water = true;
                }
            } else {
                $latest_water = $waterBills->first();
                if ($latest_water) {
                    $prev_water = $latest_water->current_reading ?? $latest_water->previous_reading ?? 0;
                } else {
                    $is_baseline_water = true;
                }
            }

            // Status resolution
            $elec_status = 'Pending';
            if ($elec_bill) {
                if ($elec_bill->status === 'paid') {
                    $elec_status = 'Paid';
                } elseif ($elec_bill->current_reading_date !== null || $elec_bill->usage_value > 0 || $elec_bill->amount > 0) {
                    $elec_status = 'Billed';
                }
            }

            $water_status = 'Pending';
            if ($water_bill) {
                if ($water_bill->status === 'paid') {
                    $water_status = 'Paid';
                } elseif ($water_bill->current_reading_date !== null || $water_bill->usage_value > 0 || $water_bill->amount > 0) {
                    $water_status = 'Billed';
                }
            }

            $houses[] = [
                'block' => $lot->block,
                'lot' => $lot->lot_number,
                'elec_status' => $elec_status,
                'water_status' => $water_status,
                'prev_elec' => $prev_elec,
                'curr_elec' => $curr_elec,
                'is_baseline_elec' => $is_baseline_elec,
                'prev_water' => $prev_water,
                'curr_water' => $curr_water,
                'is_baseline_water' => $is_baseline_water,
                'prev_balance_elec' => (float)($elec_bill?->previous_balance ?? 0),
                'prev_balance_water' => (float)($water_bill?->previous_balance ?? 0),
                'resident' => $user?->name ?? 'Occupied',
                'provider_managed' => (bool)$lot->provider_managed
            ];
        }

        return view('finance.dashboard', [
            'houses' => $houses,
            'validCycles' => $validCycles,
            'currentCycle' => $cycle
        ]);
    });

    Route::post('/api/billing/reading', function (\Illuminate\Http\Request $request) {
        $type = $request->input('type');
        if (!in_array($type, ['electricity', 'water'])) {
            return response()->json(['success' => false, 'message' => 'Invalid utility type.'], 422);
        }

        $lotStr = $request->input('lot');
        $blockStr = $request->input('block');

        $lot = \App\Models\Lot::where('block', $blockStr)->where('lot_number', $lotStr)->first();
        if (!$lot) {
            return response()->json(['success' => false, 'message' => 'Lot not found.'], 404);
        }

        if ($type === 'electricity' && $lot->provider_managed) {
            return response()->json(['success' => false, 'message' => 'Electricity for this lot is directly managed by CASURECO and cannot be billed.'], 422);
        }

        $cycle = $request->input('cycle');
        if (empty($cycle)) {
            $cycle = date('Y-m');
        }
        $dt = \Carbon\Carbon::createFromFormat('Y-m', $cycle)->startOfMonth();

        $bill = \App\Models\UtilityBill::where('lot_id', $lot->id)
            ->where('type', $type)
            ->where(function ($q) use ($dt) {
                $q->whereDate('billing_period', $dt->toDateString())
                  ->orWhere(function ($sub) use ($dt) {
                      $sub->whereNull('billing_period')
                          ->whereYear('created_at', $dt->year)
                          ->whereMonth('created_at', $dt->month);
                  });
            })->first();

        if (!$bill) {
            return response()->json(['success' => false, 'message' => "Cannot input reading: Admin has not generated a billing cycle for {$dt->format('F Y')}."], 403);
        }

        if ($bill->status === \App\Enums\BillStatus::Paid->value) {
            return response()->json(['success' => false, 'message' => 'This bill has already been marked as Paid. Readings cannot be modified.'], 422);
        }

        $prevReading = (int) $request->input('previous_reading', $bill->previous_reading ?? 0);
        $currReading = (int) $request->input('current_reading', 0);

        if ($currReading < $prevReading) {
            return response()->json([
                'success' => false,
                'message' => "Current reading ({$currReading}) cannot be lower than previous reading ({$prevReading})."
            ], 422);
        }

        $usage = $currReading - $prevReading;

        // Rates & Min Tier Calculation
        if ($type === 'water') {
            $minWaterM3 = (float) (\App\Models\Setting::find('water_min_m3')?->value ?? 10);
            $minWaterRate = (float) (\App\Models\Setting::find('water_min_rate')?->value ?? 250);
            $rate = (float) (\App\Models\Setting::find('water_rate')?->value ?? 15);

            if ($usage <= $minWaterM3) {
                $amount = $minWaterRate;
            } else {
                $amount = $minWaterRate + (($usage - $minWaterM3) * $rate);
            }
        } else {
            $rate = (float) (\App\Models\Setting::find('elec_rate')?->value ?? 10);
            $amount = $usage * $rate;
        }

        // Update Bill preserving Admin Due Date
        $updateData = [
            'previous_reading' => $prevReading,
            'current_reading' => $currReading,
            'current_reading_date' => now()->toDateString(),
            'usage_value' => $usage,
            'amount' => $amount,
        ];

        if (!$bill->due_date) {
            $updateData['due_date'] = $dt->copy()->addDays(30)->toDateString();
        }

        $bill->update($updateData);

        $user = $lot->users->first();
        if ($user) {
            updateResidentBehavior($user);

            if (!empty($user->contact_number)) {
                $dueDateStr = $bill->due_date ? \Carbon\Carbon::parse($bill->due_date)->format('M d, Y') : $dt->copy()->addDays(30)->format('M d, Y');
                $amtStr = number_format($amount, 2);
                $totalDue = $amount + (float)$bill->previous_balance;
                $totalStr = number_format($totalDue, 2);

                $msg = "Althesa Subd: Your {$type} bill for {$dt->format('M Y')} is P{$amtStr}";
                if ((float)$bill->previous_balance > 0) {
                    $msg .= " (Total due with arrears: P{$totalStr})";
                }
                $msg .= ". Due on {$dueDateStr}. Please settle on time to avoid penalties.";

                \App\Helpers\SmsHelper::sendSms($user->contact_number, $msg);
            }
        }

        return response()->json([
            'success' => true,
            'bill' => [
                'previous_reading' => $prevReading,
                'current_reading' => $currReading,
                'usage' => $usage,
                'amount' => $amount,
                'previous_balance' => (float)$bill->previous_balance,
                'total_due' => $amount + (float)$bill->previous_balance,
                'due_date' => $bill->due_date ? \Carbon\Carbon::parse($bill->due_date)->format('M d, Y') : null
            ]
        ]);
    });
});

/* =========================================================================
   PHASE 4 ROUTES
   ========================================================================= */

Route::get('/admin/api/billing/export-soa', [App\Http\Controllers\ExportController::class, 'exportSOA'])->name('admin.billing.export-soa');

Route::get('/admin/api/billing/cycle-status', function (\Illuminate\Http\Request $request) {
    $type = $request->input('type', 'electricity');
    if (!in_array($type, ['electricity', 'water'])) {
        return response()->json(['success' => false, 'message' => 'Invalid utility type.'], 422);
    }

    $tz = config('app.timezone', 'Asia/Manila');

    $latestBill = \App\Models\UtilityBill::where('type', $type)
        ->orderByRaw('COALESCE(billing_period, created_at) DESC')
        ->first();

    $lotsQuery = \App\Models\Lot::has('users')->with('users');
    if ($type === 'electricity') {
        $lotsQuery->where('provider_managed', false);
    }
    $eligibleLots = $lotsQuery->get();
    $totalEligibleLots = $eligibleLots->count();
    $eligibleLotIds = $eligibleLots->pluck('id')->all();

    $settings = [
        'rate' => (float)(\App\Models\Setting::find($type === 'electricity' ? 'elec_rate' : 'water_rate')?->value ?? ($type === 'electricity' ? 10 : 30)),
        'penalty' => (float)(\App\Models\Setting::find($type . '_penalty')?->value ?? 5),
        'water_min_m3' => (float)(\App\Models\Setting::find('water_min_m3')?->value ?? 10),
        'water_min_rate' => (float)(\App\Models\Setting::find('water_min_rate')?->value ?? 250),
    ];

    if (!$latestBill) {
        $now = now($tz);
        $suggestedPrev = $now->copy()->subMonth()->format('Y-m-d');
        $suggestedCurr = $now->format('Y-m-d');
        $suggestedDue = $now->copy()->addDays(15)->format('Y-m-d');
        return response()->json([
            'success' => true,
            'has_active_cycle' => false,
            'active_cycle' => null,
            'next_cycle' => [
                'label' => $now->format('F Y'),
                'period' => $now->startOfMonth()->toDateString(),
                'suggested_prev_date' => $suggestedPrev,
                'suggested_curr_date' => $suggestedCurr,
                'suggested_due_date' => $suggestedDue,
                'already_generated' => false,
            ],
            'settings' => $settings,
        ]);
    }

    $activePeriodRaw = $latestBill->billing_period ?: $latestBill->created_at;
    $activePeriod = \Carbon\Carbon::parse($activePeriodRaw, $tz)->startOfMonth();

    $activeBills = \App\Models\UtilityBill::where('type', $type)
        ->where(function ($q) use ($activePeriod) {
            $q->whereDate('billing_period', $activePeriod->toDateString())
              ->orWhere(function ($sub) use ($activePeriod) {
                  $sub->whereNull('billing_period')
                      ->whereYear('created_at', $activePeriod->year)
                      ->whereMonth('created_at', $activePeriod->month);
              });
        })->get();

    $billedLotIds = $activeBills->pluck('lot_id')->all();
    $missingLotIds = array_diff($eligibleLotIds, $billedLotIds);
    $missingLotsCount = count($missingLotIds);

    $unrecordedReadingsCount = $activeBills->where('status', '!=', 'paid')->filter(function ($b) {
        return $b->current_reading === null || $b->current_reading == 0;
    })->count();

    $lastCurrDate = $latestBill->current_reading_date 
        ? \Carbon\Carbon::parse($latestBill->current_reading_date, $tz) 
        : $activePeriod->copy()->endOfMonth();

    $nextPrevDate = $lastCurrDate->format('Y-m-d');
    $nextCurrDate = $lastCurrDate->copy()->addMonth()->format('Y-m-d');
    $nextDueDate = \Carbon\Carbon::parse($nextCurrDate, $tz)->addDays(15)->format('Y-m-d');
    $nextBillingPeriod = \Carbon\Carbon::parse($nextCurrDate, $tz)->startOfMonth()->toDateString();
    $nextCycleLabel = \Carbon\Carbon::parse($nextBillingPeriod, $tz)->format('F Y');

    $nextCycleExists = \App\Models\UtilityBill::where('type', $type)
        ->whereDate('billing_period', $nextBillingPeriod)
        ->exists();

    return response()->json([
        'success' => true,
        'has_active_cycle' => true,
        'active_cycle' => [
            'label' => $activePeriod->format('F Y'),
            'period' => $activePeriod->toDateString(),
            'total_lots' => $totalEligibleLots,
            'billed_lots' => count($billedLotIds),
            'missing_lots_count' => $missingLotsCount,
            'unrecorded_readings_count' => $unrecordedReadingsCount,
            'prev_reading_date' => $latestBill->previous_reading_date ? \Carbon\Carbon::parse($latestBill->previous_reading_date)->format('Y-m-d') : null,
            'curr_reading_date' => $latestBill->current_reading_date ? \Carbon\Carbon::parse($latestBill->current_reading_date)->format('Y-m-d') : null,
            'due_date' => $latestBill->due_date ? \Carbon\Carbon::parse($latestBill->due_date)->format('Y-m-d') : null,
        ],
        'next_cycle' => [
            'label' => $nextCycleLabel,
            'period' => $nextBillingPeriod,
            'suggested_prev_date' => $nextPrevDate,
            'suggested_curr_date' => $nextCurrDate,
            'suggested_due_date' => $nextDueDate,
            'already_generated' => $nextCycleExists,
        ],
        'settings' => $settings,
    ]);
});

Route::post('/admin/api/billing/generate', function (\Illuminate\Http\Request $request) {
    $type = $request->input('type'); // 'electricity' or 'water'
    if (!in_array($type, ['electricity', 'water'])) {
        return response()->json(['success' => false, 'message' => 'Invalid utility type.'], 422);
    }

    $rate = $request->input('rate');
    $penalty = $request->input('penalty', 5);
    $prevDate = $request->input('prev_date');
    $currDate = $request->input('curr_date');
    $dueDate = $request->input('due_date');
    $mode = $request->input('mode', 'new_cycle'); // 'new_cycle' or 'sync_missing'

    $waterMinM3 = $request->input('water_min_m3');
    $waterMinRate = $request->input('water_min_rate');

    if (!$prevDate || !$currDate || !$dueDate) {
        return response()->json(['success' => false, 'message' => 'Please select previous reading, current reading, and due dates.'], 422);
    }

    $tz = config('app.timezone', 'Asia/Manila');
    try {
        $prev = \Carbon\Carbon::parse($prevDate, $tz)->startOfDay();
        $curr = \Carbon\Carbon::parse($currDate, $tz)->startOfDay();
        $due = \Carbon\Carbon::parse($dueDate, $tz)->startOfDay();
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'One or more dates are invalid.'], 422);
    }

    if ($prev->gte($curr)) {
        return response()->json(['success' => false, 'message' => 'Previous reading date must be before the current reading date.'], 422);
    }
    if ($due->lt($curr)) {
        return response()->json(['success' => false, 'message' => 'Due date must be on or after the current reading date.'], 422);
    }

    $latestBillForType = \App\Models\UtilityBill::where('type', $type)
        ->orderByRaw('COALESCE(billing_period, created_at) DESC')
        ->first();

    $billingPeriod = $curr->copy()->startOfMonth()->toDateString();
    $cycleLabel = \Carbon\Carbon::parse($billingPeriod, $tz)->format('F Y');

    if ($latestBillForType) {
        $latestPeriodRaw = $latestBillForType->billing_period ?: $latestBillForType->created_at;
        $activePeriod = \Carbon\Carbon::parse($latestPeriodRaw, $tz)->startOfMonth();

        if ($mode !== 'sync_missing' && $curr->lt($activePeriod)) {
            return response()->json([
                'success' => false,
                'message' => 'Current reading date cannot be in a past billing month. Earliest allowed: ' . $activePeriod->format('M j, Y') . '.',
            ], 422);
        }
    }

    $lock = \Illuminate\Support\Facades\Cache::lock("billing_gen_{$type}", 15);
    if (!$lock->get()) {
        return response()->json(['success' => false, 'message' => 'A cycle generation is already in progress. Please wait a moment.'], 429);
    }

    try {
        if ($rate !== null && is_numeric($rate)) {
            $settingKey = $type === 'electricity' ? 'elec_rate' : 'water_rate';
            \App\Models\Setting::updateOrCreate(['key' => $settingKey], ['value' => (float)$rate]);
        }
        if ($penalty !== null && is_numeric($penalty)) {
            \App\Models\Setting::updateOrCreate(['key' => $type . '_penalty'], ['value' => (float)$penalty]);
        }
        if ($type === 'water') {
            if ($waterMinM3 !== null && is_numeric($waterMinM3)) {
                \App\Models\Setting::updateOrCreate(['key' => 'water_min_m3'], ['value' => (float)$waterMinM3]);
            }
            if ($waterMinRate !== null && is_numeric($waterMinRate)) {
                \App\Models\Setting::updateOrCreate(['key' => 'water_min_rate'], ['value' => (float)$waterMinRate]);
            }
        }

        $lots = \App\Models\Lot::has('users')->with('users')->get();
        $generatedCount = 0;
        $alreadyExistingCount = 0;
        $skippedCasureco = 0;

        \Illuminate\Support\Facades\DB::transaction(function () use (
            $lots, $type, $billingPeriod, $prevDate, $currDate, $dueDate, $penalty, $tz,
            &$generatedCount, &$alreadyExistingCount, &$skippedCasureco
        ) {
            foreach ($lots as $lot) {
                if ($type === 'electricity' && $lot->provider_managed) {
                    $skippedCasureco++;
                    continue;
                }

                $existingBill = \App\Models\UtilityBill::where('lot_id', $lot->id)
                    ->where('type', $type)
                    ->whereDate('billing_period', $billingPeriod)
                    ->exists();

                if ($existingBill) {
                    $alreadyExistingCount++;
                    continue;
                }

                $user = $lot->users->first();

                $lastBill = \App\Models\UtilityBill::where('lot_id', $lot->id)
                    ->where('type', $type)
                    ->whereDate('billing_period', '<', $billingPeriod)
                    ->orderByRaw('COALESCE(billing_period, created_at) DESC')
                    ->first();

                // Safe previous reading carryover (protect meter baseline)
                $prevReading = 0;
                if ($lastBill) {
                    if ($lastBill->current_reading !== null && $lastBill->current_reading > 0) {
                        $prevReading = $lastBill->current_reading;
                    } elseif ($lastBill->previous_reading > 0) {
                        $prevReading = $lastBill->previous_reading;
                    }
                }

                // Overdue-aware carried balance
                $carriedBalance = 0;
                if ($lastBill && $lastBill->status !== 'paid') {
                    $totalDueBeforePenalty = (float)$lastBill->amount + (float)$lastBill->previous_balance;
                    $penaltyAmt = 0;
                    $isOverdue = $lastBill->due_date && \Carbon\Carbon::parse($lastBill->due_date, $tz)->endOfDay()->isPast();
                    if ($isOverdue || $lastBill->is_at_risk) {
                        $penaltyRatePercent = (float)($penalty ?? (\App\Models\Setting::where('key', $type . '_penalty')->value('value') ?: 5));
                        $penaltyAmt = $totalDueBeforePenalty * ($penaltyRatePercent / 100);
                    }
                    $totalPaid = (float)\App\Models\Payment::where('utility_bill_id', $lastBill->id)->sum('amount_paid');
                    $carriedBalance = max(0, ($totalDueBeforePenalty + $penaltyAmt) - $totalPaid);
                }

                try {
                    $bill = \App\Models\UtilityBill::create([
                        'type' => $type,
                        'resident_id' => $user?->resident ? $user->id : null,
                        'billed_to_name' => $user?->name,
                        'lot_id' => $lot->id,
                        'previous_reading' => $prevReading,
                        'previous_reading_date' => $prevDate,
                        'current_reading_date' => $currDate,
                        'current_reading' => 0,
                        'usage_value' => 0,
                        'amount' => 0,
                        'previous_balance' => $carriedBalance,
                        'due_date' => $dueDate,
                        'status' => \App\Enums\BillStatus::Unpaid->value,
                        'is_at_risk' => false,
                        'billing_period' => $billingPeriod,
                    ]);
                    if ($currDate) {
                        $bill->created_at = \Carbon\Carbon::parse($currDate, $tz)->startOfDay();
                        $bill->save();
                    }
                    $generatedCount++;
                } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                    $alreadyExistingCount++;
                }
            }
        });

        if ($generatedCount === 0) {
            if ($skippedCasureco > 0 && $alreadyExistingCount === 0) {
                return response()->json(['success' => false, 'message' => 'No bills generated. All assigned lots are managed by CASURECO.']);
            }
            if ($alreadyExistingCount > 0) {
                return response()->json([
                    'success' => true,
                    'already_exists' => true,
                    'message' => "The billing cycle for {$cycleLabel} is already active with all eligible lots generated. No duplicates created.",
                    'generated_count' => 0
                ]);
            }
            return response()->json(['success' => false, 'message' => 'No eligible residents are currently assigned to lots for this utility.']);
        }

        $isSync = $alreadyExistingCount > 0;
        $successMsg = $isSync
            ? "Successfully synced {$generatedCount} newly assigned lot(s) for {$cycleLabel}."
            : "Successfully generated {$generatedCount} bills for the new {$cycleLabel} cycle.";

        return response()->json([
            'success' => true,
            'message' => $successMsg,
            'generated_count' => $generatedCount,
            'cycle' => \Carbon\Carbon::parse($billingPeriod)->format('Y-m')
        ]);
    } finally {
        optional($lock)->release();
    }
});

Route::post('/admin/api/billing/adjust-cycle', function (\Illuminate\Http\Request $request) {
    $type = $request->input('type'); // 'electricity' or 'water'
    $rate = $request->input('rate');
    $penalty = $request->input('penalty');
    $prevDate = $request->input('prev_date');
    $currDate = $request->input('curr_date');
    $dueDate = $request->input('due_date');
    $cycle = $request->input('cycle');

    $waterMinM3 = $request->input('water_min_m3');
    $waterMinRate = $request->input('water_min_rate');

    if (!$type || !in_array($type, ['electricity', 'water'])) {
        return response()->json(['success' => false, 'message' => 'Invalid utility type.'], 422);
    }

    if (!$prevDate || !$currDate || !$dueDate) {
        return response()->json(['success' => false, 'message' => 'Please provide previous reading, current reading, and due dates.'], 422);
    }

    $tz = config('app.timezone', 'Asia/Manila');
    try {
        $prev = \Carbon\Carbon::parse($prevDate, $tz)->startOfDay();
        $curr = \Carbon\Carbon::parse($currDate, $tz)->startOfDay();
        $due = \Carbon\Carbon::parse($dueDate, $tz)->startOfDay();
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'One or more dates are invalid.'], 422);
    }

    if ($prev->gte($curr)) {
        return response()->json(['success' => false, 'message' => 'Previous reading date must be before the current reading date.'], 422);
    }
    if ($due->lt($curr)) {
        return response()->json(['success' => false, 'message' => 'Due date must be on or after the current reading date.'], 422);
    }

    // 1. Update Global Settings
    if ($rate !== null && is_numeric($rate)) {
        $settingKey = $type === 'electricity' ? 'elec_rate' : 'water_rate';
        \App\Models\Setting::updateOrCreate(['key' => $settingKey], ['value' => (float)$rate]);
    }
    if ($penalty !== null && is_numeric($penalty)) {
        \App\Models\Setting::updateOrCreate(['key' => $type . '_penalty'], ['value' => (float)$penalty]);
    }
    if ($type === 'water') {
        if ($waterMinM3 !== null && is_numeric($waterMinM3)) {
            \App\Models\Setting::updateOrCreate(['key' => 'water_min_m3'], ['value' => (float)$waterMinM3]);
        }
        if ($waterMinRate !== null && is_numeric($waterMinRate)) {
            \App\Models\Setting::updateOrCreate(['key' => 'water_min_rate'], ['value' => (float)$waterMinRate]);
        }
    }

    // 2. Locate bills for the target active cycle
    $billsQuery = \App\Models\UtilityBill::where('type', $type);
    if ($cycle) {
        try {
            $dt = \Carbon\Carbon::createFromFormat('Y-m', $cycle);
            $billsQuery->where(function ($q) use ($dt) {
                $q->whereYear('created_at', $dt->year)->whereMonth('created_at', $dt->month)
                  ->orWhere(function ($sub) use ($dt) {
                      $sub->whereNotNull('billing_period')
                          ->whereYear('billing_period', $dt->year)
                          ->whereMonth('billing_period', $dt->month);
                  });
            });
        } catch (\Exception $e) {
            // fallback
        }
    } else {
        $latest = \App\Models\UtilityBill::where('type', $type)->orderByDesc('created_at')->first();
        if ($latest) {
            $billsQuery->whereYear('created_at', $latest->created_at->year)
                       ->whereMonth('created_at', $latest->created_at->month);
        }
    }

    $bills = $billsQuery->get();
    $updatedCount = 0;

    $effRate = (float)($rate ?? ($type === 'electricity' ? \App\Models\Setting::find('elec_rate')?->value ?? 10 : \App\Models\Setting::find('water_rate')?->value ?? 30));
    $effMinM3 = (float)($waterMinM3 ?? \App\Models\Setting::find('water_min_m3')?->value ?? 10);
    $effMinRate = (float)($waterMinRate ?? \App\Models\Setting::find('water_min_rate')?->value ?? 250);

    foreach ($bills as $bill) {
        $bill->previous_reading_date = $prevDate;
        $bill->current_reading_date = $currDate;
        $bill->due_date = $dueDate;

        // Recalculate amount if unpaid
        if ($bill->status !== 'paid') {
            if ($type === 'electricity') {
                $bill->amount = $bill->usage_value * $effRate;
            } else {
                $usage = $bill->usage_value;
                $bill->amount = $usage <= $effMinM3 ? $effMinRate : $effMinRate + (($usage - $effMinM3) * $effRate);
            }
        }

        $bill->save();
        $updatedCount++;
    }

    return response()->json([
        'success' => true,
        'message' => ucfirst($type) . " cycle parameters and {$updatedCount} bills updated successfully."
    ]);
});

Route::post('/admin/api/billing/disconnect', function (\Illuminate\Http\Request $request) {
    $lotNumber = $request->input('lot');
    $lot = $request->filled('block')
        ? \App\Models\Lot::where('block', $request->input('block'))->where('lot_number', $lotNumber)->first()
        : null;
    if ($lot) {
        $lot->provider_managed = true;
        $lot->save();
        return response()->json(['success' => true]);
    }
    return response()->json(['success' => false, 'message' => 'Lot not found.'], 404);
});

Route::post('/admin/api/billing/reconnect', function (\Illuminate\Http\Request $request) {
    $lotNumber = $request->input('lot');
    $lot = $request->filled('block')
        ? \App\Models\Lot::where('block', $request->input('block'))->where('lot_number', $lotNumber)->first()
        : null;
    if ($lot) {
        $lot->provider_managed = false;
        $lot->save();
        return response()->json(['success' => true]);
    }
    return response()->json(['success' => false, 'message' => 'Lot not found.'], 404);
});

Route::post('/admin/api/billing/add-balance', function (\Illuminate\Http\Request $request) {
    $id = $request->input('id');
    $balance = (float) $request->input('balance');
    $bill = \App\Models\UtilityBill::whereKey($id)->first();
    if ($bill) {
        $bill->update(['previous_balance' => $bill->previous_balance + $balance]);
        return response()->json(['success' => true]);
    }
    return response()->json(['success' => false], 404);
});

Route::post('/admin/api/billing/pay', function (\Illuminate\Http\Request $request) {
    $id = $request->input('id');
    $amount = (float) $request->input('amount');

    $bill = \App\Models\UtilityBill::whereKey($id)->first();
    if ($bill) {
        $tz = config('app.timezone', 'Asia/Manila');
        $totalDueBeforePenalty = (float) $bill->amount + (float) $bill->previous_balance;
        $isOverdue = ($bill->is_at_risk || ($bill->due_date && \Carbon\Carbon::parse($bill->due_date, $tz)->endOfDay()->isPast())) && strtolower((string) $bill->status) !== 'paid';
        $penaltyRatePercent = (float) (\App\Models\Setting::where('key', $bill->type . '_penalty')->value('value') ?: 5);
        $penalty = $isOverdue ? round($totalDueBeforePenalty * ($penaltyRatePercent / 100), 2) : 0.0;
        $totalDue = round($totalDueBeforePenalty + $penalty, 2);

        try {
            app(\App\Services\PaymentRecorder::class)->recordUtilityPayment(
                $bill,
                $amount,
                'Office',
                null,
                'TRN-' . strtoupper(\Illuminate\Support\Str::random(8)),
                now(),
                (float) $totalDue
            );
        } catch (\App\Exceptions\PaymentRejectedException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        if ($bill->user) {
            updateResidentBehavior($bill->user);
        }

        $newTotalPaid = (float) \App\Models\Payment::where('utility_bill_id', $bill->id)->sum('amount_paid');
        $remaining = max(0.0, round($totalDue - $newTotalPaid, 2));

        return response()->json([
            'success' => true,
            'status' => $bill->status,
            'total_paid' => $newTotalPaid,
            'remaining_balance' => $remaining,
        ]);
    }
    return response()->json(['success' => false, 'message' => 'Bill not found.'], 404);
});

/* =========================================================================
   API Endpoints for Mail / Notifications (External Integrations)
   ========================================================================= */

Route::post('/api/send-appointment-email', function (\Illuminate\Http\Request $request) {
    $email = $request->input('email', 'eighty6pharmacy@gmail.com');
    $name = $request->input('name', 'Valued Client');
    $date = $request->input('date', date('Y-m-d'));
    $time = $request->input('time', '10:00 AM');
    $type = $request->input('type', 'Site Viewing & Consultation');
    $notes = $request->input('notes', 'No notes provided.');

    try {
        \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($email, $name, $date, $time, $type, $notes) {
            $message->to($email)
                ->subject('📅 Appointment Received — Althesa Subdivision')
                ->html("
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 16px; background: #ffffff;'>
                        <div style='background: #0f172a; padding: 20px; border-radius: 12px; text-align: center;'>
                            <h2 style='color: #ffffff; margin: 0;'>Althesa Subdivision</h2>
                            <p style='color: #94a3b8; margin: 4px 0 0 0; font-size: 13px;'>Appointment Confirmation Request</p>
                        </div>
                        <div style='padding: 20px 0;'>
                            <p style='font-size: 16px; color: #0f172a;'>Dear <strong>{$name}</strong>,</p>
                            <p style='color: #475569;'>Thank you for scheduling an appointment with Althesa Subdivision! We have received your request and your booking details are below:</p>
                            <div style='background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid #10b981; margin: 20px 0;'>
                                <p style='margin: 4px 0;'><strong>📅 Date:</strong> {$date}</p>
                                <p style='margin: 4px 0;'><strong>⏰ Time:</strong> {$time}</p>
                            </div>
                            <p style='color: #475569;'>Our administration team will review your booking shortly. If approved, you will receive a follow-up email with your Gate Viewing Access PIN.</p>
                        </div>
                        <div style='border-top: 1px solid #e2e8f0; padding-top: 16px; font-size: 12px; color: #94a3b8; text-align: center;'>
                            Althesa Subdivision Management Office &bull; Contact: +63 (02) 8912-3456
                        </div>
                    </div>
                ");
        });
        return response()->json(['success' => true, 'message' => 'Confirmation email sent successfully!']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

Route::post('/api/send-approval-email', function (Request $request) {
    $email = $request->input('email', 'eighty6pharmacy@gmail.com');
    $name = $request->input('name', 'Valued Client');
    $pin = $request->input('pin', rand(100000, 999999));
    $date = $request->input('date', date('Y-m-d'));

    try {
        Mail::send([], [], function ($message) use ($email, $name, $pin, $date) {
            $message->to($email)
                ->subject('🎉 Appointment Approved & Gate Access PIN — Althesa Subdivision')
                ->html("
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 16px; background: #ffffff;'>
                        <div style='background: #059669; padding: 20px; border-radius: 12px; text-align: center;'>
                            <h2 style='color: #ffffff; margin: 0;'>Althesa Subdivision</h2>
                            <p style='color: #ecfdf5; margin: 4px 0 0 0; font-size: 13px;'>Appointment Approved</p>
                        </div>
                        <div style='padding: 20px 0;'>
                            <p style='font-size: 16px; color: #0f172a;'>Hello <strong>{$name}</strong>,</p>
                            <p style='color: #475569;'>Great news! Your appointment request for <strong>{$date}</strong> has been approved by the subdivision management office.</p>
                            <div style='background: #ecfdf5; border: 2px dashed #059669; padding: 20px; border-radius: 12px; text-align: center; margin: 20px 0;'>
                                <span style='font-size: 13px; color: #047857; text-transform: uppercase; font-weight: 700;'>Your Gate Entry Viewing PIN</span>
                                <div style='font-size: 36px; font-weight: 800; color: #047857; letter-spacing: 6px; margin-top: 8px;'>{$pin}</div>
                            </div>
                            <p style='color: #475569;'>Please present this 6-digit PIN to the security guard at Gate 1 upon your arrival.</p>
                        </div>
                        <div style='border-top: 1px solid #e2e8f0; padding-top: 16px; font-size: 12px; color: #94a3b8; text-align: center;'>
                            Althesa Subdivision Security & Management
                        </div>
                    </div>
                ");
        });
        return response()->json(['success' => true, 'message' => 'Approval email with PIN sent successfully!']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

Route::post('/api/send-cancellation-email', function (Request $request) {
    $email = $request->input('email', 'eighty6pharmacy@gmail.com');
    $name = $request->input('name', 'Valued Client');
    $reason = $request->input('reason', 'Schedule conflict / fully booked.');

    try {
        Mail::send([], [], function ($message) use ($email, $name, $reason) {
            $message->to($email)
                ->subject('⚠️ Appointment Cancellation Notice — Althesa Subdivision')
                ->html("
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 16px; background: #ffffff;'>
                        <div style='background: #dc2626; padding: 20px; border-radius: 12px; text-align: center;'>
                            <h2 style='color: #ffffff; margin: 0;'>Althesa Subdivision</h2>
                            <p style='color: #fef2f2; margin: 4px 0 0 0; font-size: 13px;'>Appointment Status Update</p>
                        </div>
                        <div style='padding: 20px 0;'>
                            <p style='font-size: 16px; color: #0f172a;'>Dear <strong>{$name}</strong>,</p>
                            <p style='color: #475569;'>We regret to inform you that your appointment request has been cancelled by the administration.</p>
                            <div style='background: #fef2f2; padding: 16px; border-radius: 12px; border-left: 4px solid #dc2626; margin: 20px 0;'>
                                <p style='margin: 0; color: #991b1b;'><strong>Reason:</strong> {$reason}</p>
                            </div>
                            <p style='color: #475569;'>If you would like to reschedule, please visit our website and submit a new booking request.</p>
                        </div>
                        <div style='border-top: 1px solid #e2e8f0; padding-top: 16px; font-size: 12px; color: #94a3b8; text-align: center;'>
                            Althesa Subdivision Management Office
                        </div>
                    </div>
                ");
        });
        return response()->json(['success' => true, 'message' => 'Cancellation notice sent successfully!']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

Route::post('/api/send-visitor-pin-email', function (Request $request) {
    $email = $request->input('email', 'eighty6pharmacy@gmail.com');
    $visitorName = $request->input('visitor_name', 'Guest Visitor');
    $residentName = $request->input('resident_name', 'Resident Host');
    $pin = $request->input('pin', rand(100000, 999999));
    $date = $request->input('date', date('Y-m-d'));

    try {
        Mail::send([], [], function ($message) use ($email, $visitorName, $residentName, $pin, $date) {
            $message->to($email)
                ->subject('🎟️ Visitor Gate Entry Pass PIN — Althesa Subdivision')
                ->html("
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 16px; background: #ffffff;'>
                        <div style='background: #0284c7; padding: 20px; border-radius: 12px; text-align: center;'>
                            <h2 style='color: #ffffff; margin: 0;'>Althesa Gate Security</h2>
                            <p style='color: #e0f2fe; margin: 4px 0 0 0; font-size: 13px;'>Visitor Authorization Code</p>
                        </div>
                        <div style='padding: 20px 0;'>
                            <p style='font-size: 16px; color: #0f172a;'>Hello <strong>{$visitorName}</strong>,</p>
                            <p style='color: #475569;'>Your visitor pass requested by <strong>{$residentName}</strong> for visit date <strong>{$date}</strong> has been approved!</p>
                            <div style='background: #e0f2fe; border: 2px dashed #0284c7; padding: 20px; border-radius: 12px; text-align: center; margin: 20px 0;'>
                                <span style='font-size: 13px; color: #0369a1; text-transform: uppercase; font-weight: 700;'>6-Digit Gate Entry PIN</span>
                                <div style='font-size: 36px; font-weight: 800; color: #0369a1; letter-spacing: 6px; margin-top: 8px;'>{$pin}</div>
                            </div>
                            <p style='color: #475569;'>Show this PIN to the security guard at Gate Control upon arrival for instant validation.</p>
                        </div>
                        <div style='border-top: 1px solid #e2e8f0; padding-top: 16px; font-size: 12px; color: #94a3b8; text-align: center;'>
                            Althesa Subdivision Gate Security System
                        </div>
                    </div>
                ");
        });
        return response()->json(['success' => true, 'message' => 'Visitor pass PIN email sent successfully!']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

Route::post('/api/send-billing-warning-sms', function (\Illuminate\Http\Request $request) {
    $email = $request->input('email');
    $residentName = $request->input('resident_name', 'Resident');
    $billId = $request->input('bill_id', 'Unknown');
    $amount = $request->input('amount', '0.00');

    $nameParts = preg_split('/\s+/', trim((string) $residentName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $user = \App\Models\User::where('email', $email)->first();
    if (! $user && $nameParts !== []) {
        $user = \App\Models\User::query()
            ->where('first_name', $nameParts[0])
            ->when(count($nameParts) > 1, fn ($q) => $q->where('last_name', $nameParts[count($nameParts) - 1]))
            ->first();
    }

    if (!$user || empty($user->contact_number)) {
        return response()->json(['success' => false, 'error' => 'Resident not found or has no contact number.'], 404);
    }

    try {
        $msg = "⚠️ Urgent: Past Due Warning! Althesa Subd bill {$billId} for P{$amount} is overdue. Pls settle immediately to avoid disconnection.";
        $sent = \App\Helpers\SmsHelper::sendSms($user->contact_number, $msg);

        if ($sent) {
            return response()->json(['success' => true, 'message' => 'Billing warning SMS sent successfully!']);
        } else {
            return response()->json(['success' => false, 'error' => 'Failed to send SMS.'], 500);
        }
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

Route::get('/api/settings', function () {
    return response()->json(\App\Models\Setting::pluck('value', 'key'));
});

Route::post('/api/settings', function (\Illuminate\Http\Request $request) {
    foreach ($request->all() as $key => $value) {
        \App\Models\Setting::updateOrCreate(['key' => $key], ['value' => (is_array($value) ? json_encode($value) : $value)]);
    }
    return response()->json(['success' => true]);
});

Route::get('/api/appointments/availability', function () {
    expirePastAppointments();
    $unavailableDates = json_decode(\App\Models\Setting::where('key', 'unavailable_dates')->value('value') ?? '[]', true);

    $appointments = \App\Models\Appointment::where('status', '!=', 'Cancelled')
        ->where('date', '>=', now()->format('Y-m-d'))
        ->get(['date', 'time']);

    return response()->json([
        'unavailable_dates' => $unavailableDates,
        'taken_timeslots' => $appointments
    ]);
});
