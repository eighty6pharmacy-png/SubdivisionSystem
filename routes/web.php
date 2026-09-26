<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/home', function () {
    $user = \Illuminate\Support\Facades\Auth::user();
    if (!$user) return redirect('/login');
    if ($user->hasRole('Admin')) return redirect('/admin/dashboard');
    if ($user->hasRole('Security Guard')) return redirect('/guard/dashboard');
    if ($user->hasRole('Finance Officer')) return redirect('/finance/dashboard');
    return redirect('/resident/dashboard');
});

Route::get('/appointment', function () {
    return view('appointment');
});

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

    if (\Illuminate\Support\Facades\Auth::attempt($credentials)) {
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
        }

        return response()->json(['success' => true, 'redirect' => $target]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Username or password is invalid!!',
    ], 401);
})->middleware('throttle:5,1');

Route::post('/logout', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
});

Route::post('/forgot-password', function (\Illuminate\Http\Request $request) {
    $request->validate(['email' => 'required|email']);
    $user = \App\Models\User::where('email', $request->email)->first();
    
    if (!$user) {
        return response()->json(['success' => false, 'message' => 'Email address not found in the system.']);
    }
    
    $otp = sprintf('%06d', rand(100000, 999999));
    \Illuminate\Support\Facades\Cache::put('otp_' . $user->email, $otp, now()->addMinutes(15));
    
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
    
    return response()->json(['success' => true]);
});

Route::post('/verify-otp', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'email' => 'required|email',
        'otp' => 'required|string'
    ]);
    
    $cachedOtp = \Illuminate\Support\Facades\Cache::get('otp_' . $request->email);
    
    if ($cachedOtp && $cachedOtp === $request->otp) {
        return response()->json(['success' => true]);
    }
    
    return response()->json(['success' => false, 'message' => 'Invalid or expired OTP.']);
});

Route::post('/reset-password', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'email' => 'required|email',
        'otp' => 'required|string',
        'password' => 'required|string|min:8'
    ]);
    
    $cachedOtp = \Illuminate\Support\Facades\Cache::get('otp_' . $request->email);
    
    if ($cachedOtp && $cachedOtp === $request->otp) {
        $user = \App\Models\User::where('email', $request->email)->first();
        if ($user) {
            $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
            $user->save();
            \Illuminate\Support\Facades\Cache::forget('otp_' . $request->email);
            return response()->json(['success' => true]);
        }
    }
    
    return response()->json(['success' => false, 'message' => 'Failed to reset password.']);
});

// getVisitorPins() removed, now using API endpoints and controller

// Resident Routes
Route::prefix('resident')->middleware(['auth', 'web', 'role:Resident', 'sync_paymongo'])->group(function () {

    Route::post('/api/billing/pay', function (\Illuminate\Http\Request $request) {
        $billId = $request->input('bill_id');
        $bill = \App\Models\UtilityBill::where('id', 'like', $billId . '%')->where('status', '!=', 'paid')->first();
        
        if (!$bill) {
            return response()->json(['success' => false, 'message' => 'Bill not found or already paid.'], 404);
        }

        if ($bill->paymongo_checkout_url) {
            return response()->json(['success' => true, 'checkout_url' => $bill->paymongo_checkout_url]);
        }

        $lineItems = [];
        $usageAmount = $bill->amount;
        if ($usageAmount > 0) {
            $lineItems[] = [
                'currency' => 'PHP',
                'amount' => (int)round($usageAmount * 100),
                'name' => ucfirst($bill->type) . ' Usage',
                'quantity' => 1
            ];
        }

        if ($bill->previous_balance > 0) {
            $lineItems[] = [
                'currency' => 'PHP',
                'amount' => (int)round($bill->previous_balance * 100),
                'name' => 'Previous Balance',
                'quantity' => 1
            ];
        }
        
        $totalDueBeforePenalty = $bill->amount + $bill->previous_balance;
        $penalty = ($bill->due_date && \Carbon\Carbon::parse($bill->due_date)->isPast()) ? ($totalDueBeforePenalty * 0.05) : 0;
        
        if ($penalty > 0) {
            $lineItems[] = [
                'currency' => 'PHP',
                'amount' => (int)round($penalty * 100),
                'name' => 'Late Penalty Fee',
                'quantity' => 1
            ];
        }

        if (empty($lineItems)) {
            return response()->json(['success' => false, 'message' => 'Bill amount is 0.'], 400);
        }

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'authorization' => 'Basic ' . base64_encode(env('PAYMONGO_SECRET_KEY') . ':')
        ])->post('https://api.paymongo.com/v1/checkout_sessions', [
            'data' => [
                'attributes' => [
                    'send_email_receipt' => true,
                    'show_description' => true,
                    'show_line_items' => true,
                    'line_items' => $lineItems,
                    'payment_method_types' => ['gcash', 'paymaya', 'card', 'grab_pay', 'qrph'],
                    'success_url' => request()->getSchemeAndHttpHost() . '/resident/' . $bill->type . '?payment=success',
                    'description' => 'Subdivision ' . ucfirst($bill->type) . ' Bill',
                    'reference_number' => $bill->id
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
    $secret = env('PAYMONGO_WEBHOOK_SECRET');
    
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
        
        $bill = \App\Models\UtilityBill::find($billId);
        if ($bill && $bill->status !== 'paid') {
            $payments = $paymentData['payments'] ?? [];
            $paidPayment = collect($payments)->first(function ($payment) {
                return ($payment['attributes']['status'] ?? '') === 'paid';
            });
            
            if ($paidPayment || ($paymentData['status'] ?? '') === 'paid') {
                $sourceType = $paidPayment['attributes']['source']['type'] ?? ($paymentData['source']['type'] ?? '');
                $method = $sourceType === 'gcash' ? 'GCash' : ($sourceType ? ucfirst($sourceType) : 'PayMongo');
                $trn = $paidPayment['attributes']['payment_intent_id'] ?? ($paymentData['payment_intent_id'] ?? ('PM-' . strtoupper(\Illuminate\Support\Str::random(8))));
                $trn .= '-' . substr($bill->id, 0, 8);
                
                $amountPaid = isset($paidPayment['attributes']['amount']) ? ($paidPayment['attributes']['amount'] / 100) : (isset($paymentData['amount']) ? ($paymentData['amount'] / 100) : ($bill->amount + $bill->previous_balance));
                $paidAt = isset($paidPayment['attributes']['paid_at']) ? \Carbon\Carbon::createFromTimestamp($paidPayment['attributes']['paid_at'], config('app.timezone')) : (isset($paymentData['paid_at']) ? \Carbon\Carbon::createFromTimestamp($paymentData['paid_at'], config('app.timezone')) : now());
                
                \App\Models\Payment::create([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'utility_bill_id' => $bill->id,
                    'amount_paid' => $amountPaid,
                    'method' => $method,
                    'trn' => $trn,
                    'payment_date' => $paidAt,
                ]);
                $bill->update(['status' => 'paid']);
                \Illuminate\Support\Facades\Log::info('Webhook marked bill ' . $bill->id . ' as paid.');
            }
        }
    }
    
    return response()->json(['success' => true]);
});

// Guard Security Portal Routes
Route::prefix('guard')->middleware(['auth', 'web', 'role:Security Guard'])->group(function () {
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
Route::get('/api/visitors', [\App\Http\Controllers\VisitorController::class, 'index']);
Route::middleware(['auth'])->group(function() {
    Route::post('/api/visitors', [\App\Http\Controllers\VisitorController::class, 'store']);
    Route::put('/api/visitors/{id}/approve', [\App\Http\Controllers\VisitorController::class, 'approve']);
    Route::put('/api/visitors/{id}/reject', [\App\Http\Controllers\VisitorController::class, 'reject']);
    Route::put('/api/visitors/{id}/enter', [\App\Http\Controllers\VisitorController::class, 'markEntered']);
});

Route::post('/api/appointments', function (\Illuminate\Http\Request $request) {
    \App\Models\Appointment::create([
        'client_name' => $request->input('client_name'),
        'contact_number' => $request->input('contact_number'),
        'email' => $request->input('email'),
        'type' => $request->input('type'),
        'date' => \Carbon\Carbon::parse($request->input('date')),
        'time' => $request->input('time'),
        'status' => 'Pending',
        'notes' => $request->input('notes'),
    ]);
    return response()->json(['success' => true]);
});
Route::put('/api/incidents/{id}/status', function (\Illuminate\Http\Request $request, $id) {
    $inc = \App\Models\Incident::where('id', 'like', $id . '%')->first();
    if ($inc) {
        $inc->update(['status' => $request->input('status')]);
        return response()->json(['success' => true]);
    }
    return response()->json(['success' => false], 404);
});

Route::put('/api/appointments/{id}/email', function (\Illuminate\Http\Request $request, $id) {
    $apt = \App\Models\Appointment::where('id', 'like', $id . '%')->first();
    if ($apt) {
        $apt->update(['email' => $request->input('email')]);
        return response()->json(['success' => true]);
    }
    return response()->json(['success' => false], 404);
});
Route::put('/api/appointments/{id}/status', function (\Illuminate\Http\Request $request, $id) {
    $apt = \App\Models\Appointment::where('id', 'like', $id . '%')->first();
    if ($apt) {
        $oldStatus = $apt->status;
        $apt->update([
            'status' => $request->input('status'),
            'report' => $request->input('report', $apt->report)
        ]);

        if ($request->input('status') === 'Scheduled' && $oldStatus !== 'Scheduled' && !empty($apt->email)) {
            try {
                $name = $apt->client_name;
                $date = \Carbon\Carbon::parse($apt->date)->format('F j, Y');
                $pin = $request->input('pin', rand(100000, 999999));
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
        } elseif ($request->input('status') === 'Cancelled' && $oldStatus !== 'Cancelled' && !empty($apt->email)) {
            try {
                $name = $apt->client_name;
                $reason = $request->input('reason', 'Schedule conflict / fully booked.');
                \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($apt, $name, $reason) {
                    $message->to($apt->email)
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
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send cancellation email: ' . $e->getMessage());
            }
        }
    }
    return response()->json(['success' => true]);
});

// GIS API Route
Route::get('/api/lots', [\App\Http\Controllers\GisController::class, 'getLotsData']);

// Admin Routes
Route::prefix('admin')->middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/dashboard', function () {
        $openIncidentsCount = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('incidents')) {
            $openIncidentsCount = \App\Models\Incident::whereIn('status', ['pending', 'progress'])->count();
        }
        return view('admin.dashboard', compact('openIncidentsCount'));
    });

    // Master List System
    Route::resource('buyer-master-list', \App\Http\Controllers\Admin\BuyerMasterListController::class);
    Route::get('/buyer-master-list/{id}/export-msvs', [\App\Http\Controllers\Admin\BuyerMasterListController::class, 'exportMsvs'])->name('admin.buyer.export.msvs');
    Route::get('/buyer-master-list/{id}/export-bvs', [\App\Http\Controllers\Admin\BuyerMasterListController::class, 'exportBvs'])->name('admin.buyer.export.bvs');
    Route::get('/buyer-master-list/{id}/export-housing-loan', [\App\Http\Controllers\Admin\BuyerMasterListController::class, 'exportHousingLoan'])->name('admin.buyer.export.housing_loan');
    Route::get('/buyer-master-list/{id}/export-buyer-conformity', [\App\Http\Controllers\Admin\BuyerMasterListController::class, 'exportBuyerConformity'])->name('admin.buyer.export.buyer_conformity');

    // User Management System
    Route::get('/users', function () { 
        $users = \App\Models\User::with(['lots', 'roles'])->get()->map(function(\App\Models\User $u) {
            $role = $u->roles->first()->name ?? 'Resident';
            $lot = $u->lots->first();
            return [
                'id' => 'USR-' . str_pad($u->id, 4, '0', STR_PAD_LEFT),
                'db_id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $role,
                'status' => $u->status ?? 'Active',
                'joined' => $u->created_at->format('Y-m-d'),
                'contact_number' => $u->contact_number,
                'block' => $lot ? $lot->block : '',
                'lot' => $lot ? $lot->lot_number : '',
                'meta' => $lot ? 'Block ' . $lot->block . ', Lot ' . $lot->lot_number : ''
            ];
        })->toArray();
        return view('admin.users.index', ['users' => $users]); 
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
        $lot = \App\Models\Lot::firstOrCreate(
            ['block' => $request->input('block'), 'lot_number' => $request->input('lot')],
            ['status' => 'Reserved', 'provider_managed' => false]
        );
        $res = \App\Models\Reservation::create([
            'lot_id' => $lot->id,
            'buyer_master_list_id' => $request->input('buyer_master_list_id'),
            'status' => 'Reserved',
            'reservation_date' => now(),
            'deadline_date' => $request->input('deadline_date') ? \Carbon\Carbon::parse($request->input('deadline_date')) : null,
            'amount' => $request->input('amount') ?? 20000,
            'notes' => ($request->input('name') ?? 'New Buyer') . ' | ' . ($request->input('contact') ?? 'N/A')
        ]);
        return response()->json(['success' => true, 'id' => substr($res->id, 0, 8)]);
    });

    Route::post('/reservation-fee/cancel', function (\Illuminate\Http\Request $request) {
        $res = \App\Models\Reservation::where('id', 'LIKE', $request->input('id') . '%')->first();
        if ($res) {
            $res->update(['status' => 'Cancelled']);
            if ($res->lot) {
                $res->lot->update(['status' => 'Available']);
            }
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    });

    Route::post('/downpayment-fee', function (\Illuminate\Http\Request $request) {
        $lot = \App\Models\Lot::firstOrCreate(
            ['block' => $request->input('block'), 'lot_number' => $request->input('lot')],
            ['status' => 'Reserved', 'provider_managed' => false]
        );
        $res = \App\Models\Reservation::create([
            'lot_id' => $lot->id,
            'buyer_master_list_id' => $request->input('buyer_master_list_id'),
            'status' => 'Pending',
            'reservation_date' => now(),
            'amount' => 0,
            'notes' => $request->input('name') ?? 'New Buyer',
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name')
        ]);
        $dp = \App\Models\Downpayment::create([
            'reservation_id' => $res->id,
            'amount' => 0,
            'balance' => $request->input('dpAmount') ?? 0,
            'due_date' => \Carbon\Carbon::parse($request->input('nextDate') ?? now()->addMonth()),
            'status' => 'Good Standing',
            'monthly_amortization' => $request->input('monthly_amortization') ?? 15000,
            'months_to_pay' => $request->input('months_to_pay') ?? 24,
            'contract_date' => $request->input('contract_date') ? \Carbon\Carbon::parse($request->input('contract_date')) : now()
        ]);

        // "Client First" Workflow: Sync back the newly established contract details to the Masterlist
        $buyer = \App\Models\BuyerMasterList::find($request->input('buyer_master_list_id'));
        if ($buyer) {
            $buyer->block_no = $request->input('block');
            $buyer->lot_no = $request->input('lot');
            $buyer->contract_amount = $request->input('contract_amount');
            $buyer->equity = $request->input('dpAmount');
            $buyer->save();
        }

        return response()->json(['success' => true, 'id' => substr($dp->id, 0, 8)]);
    });

    Route::post('/downpayment-fee/pay', function (\Illuminate\Http\Request $request) {
        $dp = \App\Models\Downpayment::where('id', 'LIKE', $request->input('id') . '%')->first();
        if ($dp) {
            $payment_amount = (float)$request->input('amount');
            $dp->amount += $payment_amount;
            $dp->balance -= $payment_amount;
            if ($dp->balance <= 0) {
                $dp->status = 'Fully Paid';
                $dp->balance = 0;
            } else {
                $dp->status = 'Good Standing';
            }
            // Update next due date to next month
            $dp->due_date = \Carbon\Carbon::parse($dp->due_date)->addMonth();
            $dp->save();
            
            // Record in downpayment_histories
            $trn = strtoupper(\Illuminate\Support\Str::random(10));
            \Illuminate\Support\Facades\DB::table('downpayment_histories')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'downpayment_id' => $dp->id,
                'amount' => $payment_amount,
                'payment_date' => $request->input('payment_date') ? \Carbon\Carbon::parse($request->input('payment_date')) : now(),
                'trn' => $trn,
                'status' => 'Paid',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true, 
                'trn' => $trn,
                'next_due' => $dp->due_date ? \Carbon\Carbon::parse($dp->due_date)->format('Y-m-d') : null
            ]);
        }
        return response()->json(['success' => false], 404);
    });

    Route::get('/reservation-fee', function () {
        $yearly_stats = [
            '2026' => [
                'May' => 400000, 'Jun' => 400000, 'Jul' => 400000, 'Aug' => 400000, 
                'Sep' => 400000, 'Oct' => 400000, 'Nov' => 400000, 'Dec' => 400000,
                'Jan' => 400000, 'Feb' => 400000, 'Mar' => 400000, 'Apr' => 400000
            ],
            'today_paid' => 40000.00
        ];
        return view('admin.reservation-fee.index', [
            'bills' => getReservationFees(),
            'stats' => $yearly_stats,
            'masterListBuyers' => \App\Models\BuyerMasterList::orderBy('last_name')->get()
        ]);
    });

    Route::get('/downpayment-fee', function () {
        $yearly_stats = [
            '2026' => [
                'May' => 300000, 'Jun' => 300000, 'Jul' => 300000, 'Aug' => 300000, 
                'Sep' => 300000, 'Oct' => 300000, 'Nov' => 300000, 'Dec' => 300000,
                'Jan' => 300000, 'Feb' => 300000, 'Mar' => 300000, 'Apr' => 300000
            ],
            'today_paid' => 15000.00
        ];
        return view('admin.downpayment-fee.index', [
            'bills' => getDownpaymentFees(),
            'stats' => $yearly_stats,
            'masterListBuyers' => \App\Models\BuyerMasterList::orderBy('last_name')->get()
        ]);
    });
    Route::get('/appointments', function () { 
        return view('admin.appointments.index', ['appointments' => getAppointments()]); 
    });
    Route::get('/incidents', function () {
        return view('admin.incidents.index', ['incidents' => getIncidents()]);
    });
    Route::get('/incidents/{id}', function ($id) {
        $incident = collect(getIncidents())->firstWhere('id', $id);
        if (!$incident) abort(404);
        return view('admin.incidents.show', ['inc' => $incident, 'id' => $id]);
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

        $ann = \App\Models\Announcement::create([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'content' => $validated['content'],
            'author' => \Illuminate\Support\Facades\Auth::user()->name ?? 'Admin',
            'status' => 'Published',
            'publish_date' => now(),
        ]);

        // Send email to all residents
        $residents = \App\Models\User::role('Resident')->get();
        foreach ($residents as $resident) {
            \Illuminate\Support\Facades\Mail::to($resident->email)->send(new \App\Mail\AnnouncementPosted($ann));
        }

        return response()->json(['success' => true, 'message' => 'Announcement posted and emails sent.']);
    });
    Route::get('/visitors', function () {
        return view('admin.visitors.index');
    });
    Route::get('/gis', function () { return view('admin.gis.index'); });
    Route::post('/gis/update-occupancy', function (\Illuminate\Http\Request $request) {
        $lot = \App\Models\Lot::firstOrCreate(
            ['block' => $request->input('block'), 'lot_number' => $request->input('lot')]
        );

        if ($lot->users()->exists()) {
            return response()->json(['success' => false, 'message' => 'Lot is occupied by a resident and cannot be changed.'], 403);
        }

        $lot->update(['status' => $request->input('status')]);
        return response()->json(['success' => true]);
    });
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

// Resident Portal Routes
Route::prefix('resident')->middleware(['auth', 'role:Resident', 'sync_paymongo'])->group(function () {
    Route::get('/dashboard', function () {
        $user = \Illuminate\Support\Facades\Auth::user();
        $elecBills = getElectricalBills(request('cycle'));
        $waterBills = getWaterBills(request('cycle'));
        $elecBill = collect($elecBills)->firstWhere('resident', $user->name ?? 'Jepuso') ?? [
            'id' => 'N/A', 'db_id' => null, 'lot' => 'N/A', 'block' => 'N/A',
            'resident' => $user->name ?? 'Resident',
            'amount' => 0, 'usage' => '0 kWh', 'usage_kwh' => 0, 'base_amount' => 0,
            'status' => 'no-bill', 'due' => 'N/A', 'paid_date' => null, 'method' => null,
            'usage_history' => array_fill(0, 12, 0),
            'amount_history' => array_fill(0, 12, 0),
            'at_risk' => false,
            'payment_history' => [],
            'payment_behavior' => 'On-Time',
            'audit_log' => [],
            'kwh' => 0, 'rate' => 0, 'prev' => 0, 'curr' => 0,
        ];
        $waterBill = collect($waterBills)->firstWhere('resident', $user->name ?? 'Jepuso') ?? [
            'id' => 'N/A', 'db_id' => null, 'lot' => 'N/A', 'block' => 'N/A',
            'resident' => $user->name ?? 'Resident',
            'amount' => 0, 'usage' => '0 m³', 'usage_cubic' => 0, 'base_amount' => 0,
            'status' => 'no-bill', 'due' => 'N/A', 'paid_date' => null, 'method' => null,
            'usage_history' => array_fill(0, 12, 0),
            'amount_history' => array_fill(0, 12, 0),
            'at_risk' => false,
            'payment_history' => [],
            'payment_behavior' => 'On-Time',
            'audit_log' => [],
            'cubic' => 0, 'rate' => 0, 'prev' => 0, 'curr' => 0,
        ];
        
        $announcements = \App\Models\Announcement::orderBy('created_at', 'desc')->take(10)->get();
        
        return view('resident.dashboard', [
            'elecBill' => $elecBill, 
            'waterBill' => $waterBill,
            'announcements' => $announcements
        ]);
    });
    Route::get('/profile', function () {
        return view('resident.profile');
    });
    Route::get('/map', function () {
        return view('resident.map');
    });
    Route::get('/visitors', function () {
        return view('resident.visitors.index');
    });
    Route::get('/electricity', function () {
        $user = \Illuminate\Support\Facades\Auth::user();
        $elecBills = getElectricalBills(request('cycle'));
        $lot = $user->lots()->first();
        $providerManaged = $lot ? $lot->provider_managed : false;
        
        $elecBill = collect($elecBills)->firstWhere('resident', $user->name) ?? [
            'id' => 'N/A', 'db_id' => null, 'lot' => $lot ? 'B'.$lot->block.' L'.$lot->lot_number : 'N/A', 'block' => $lot ? $lot->block : 'N/A',
            'resident' => $user->name, 'period' => 'N/A', 'provider_managed' => $providerManaged,
            'amount' => 0, 'usage' => '0 kWh', 'usage_kwh' => 0, 'base_amount' => 0,
            'status' => 'no-bill', 'due' => 'N/A', 'paid_date' => null, 'method' => null,
            'usage_history' => array_fill(0, 12, 0),
            'amount_history' => array_fill(0, 12, 0),
            'at_risk' => false,
            'payment_history' => [],
            'payment_behavior' => 'On-Time',
            'audit_log' => [],
            'kwh' => 0, 'rate' => 0, 'prev' => 0, 'curr' => 0,
        ];
        return view('resident.electricity', ['elecBill' => $elecBill]);
    });
    Route::get('/water', function () {
        $user = \Illuminate\Support\Facades\Auth::user();
        $waterBills = getWaterBills(request('cycle'));
        $waterBill = collect($waterBills)->firstWhere('resident', $user->name) ?? [
            'id' => 'N/A', 'db_id' => null, 'lot' => 'N/A', 'block' => 'N/A',
            'resident' => $user->name, 'period' => 'N/A',
            'amount' => 0, 'usage' => '0 m³', 'usage_cubic' => 0, 'base_amount' => 0,
            'status' => 'no-bill', 'due' => 'N/A', 'paid_date' => null, 'method' => null,
            'usage_history' => array_fill(0, 12, 0),
            'amount_history' => array_fill(0, 12, 0),
            'at_risk' => false,
            'payment_history' => [],
            'payment_behavior' => 'On-Time',
            'audit_log' => [],
            'cubic' => 0, 'rate' => 0, 'prev' => 0, 'curr' => 0,
        ];
        return view('resident.water', ['waterBill' => $waterBill]);
    });
    Route::get('/incidents', function () {
        $user = \Illuminate\Support\Facades\Auth::user();
        $reports = \App\Models\Incident::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        return view('resident.incidents', ['incidents' => $reports]);
    });
    Route::post('/incidents', function (\Illuminate\Http\Request $request) {
        $photos = $request->input('photos');
        $imageUrls = [];
        
        if (is_array($photos) && count($photos) > 0) {
            foreach ($photos as $photo) {
                if (preg_match('/^data:image\/(\w+);base64,/', $photo, $type)) {
                    $photoData = substr($photo, strpos($photo, ',') + 1);
                    $type = strtolower($type[1]);
                    if (in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                        $photoDecoded = base64_decode($photoData);
                        if ($photoDecoded !== false) {
                            $filename = 'incidents/' . \Illuminate\Support\Str::random(10) . '.' . $type;
                            \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $photoDecoded);
                            $imageUrls[] = '/storage/' . $filename;
                        }
                    }
                }
            }
        }

        \App\Models\Incident::create([
            'subject' => $request->input('subject'),
            'type' => $request->input('type'),
            'description' => $request->input('description'),
            'status' => 'pending',
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'image_url' => count($imageUrls) > 0 ? json_encode($imageUrls) : null,
        ]);
        return response()->json(['success' => true]);
    });
    
    Route::put('/incidents/{id}/status', function (\Illuminate\Http\Request $request, $id) {
        $inc = \App\Models\Incident::where('id', 'like', $id . '%')->first();
        if ($inc) {
            $inc->update(['status' => $request->input('status')]);
        }
        return response()->json(['success' => true]);
    });
    Route::get('/notifications', function () {
        $announcements = \App\Models\Announcement::orderBy('created_at', 'desc')->get();
        return view('resident.notifications', ['announcements' => $announcements]);
    });
});

Route::get('/visitor-pin', function () {
    return view('visitor-pin');
});

Route::get('/routing-guide', function () {
    return view('visitor.routing');
});

// Finance Officer Portal Routes
Route::prefix('finance')->middleware(['auth', 'role:Finance Officer'])->group(function () {
    Route::get('/dashboard', function () {
        $cycle = request('cycle');
        $residents = \App\Models\User::role('Resident')->with(['lots', 'utilityBills' => function($q) {
            $q->orderBy('created_at', 'desc');
        }])->get();
        
        $houses = [];
        foreach ($residents as $res) {
            $lot = $res->lots->first();
            if (!$lot) continue;
            
            $elecBills = $res->utilityBills->where('type', 'electricity');
            $waterBills = $res->utilityBills->where('type', 'water');
            
            if ($cycle) {
                $dt = \Carbon\Carbon::createFromFormat('Y-m', $cycle);
                $elec_bill = $elecBills->filter(function($b) use ($dt) {
                    $c = \Carbon\Carbon::parse($b->created_at);
                    return $c->year == $dt->year && $c->month == $dt->month;
                })->first();
                $water_bill = $waterBills->filter(function($b) use ($dt) {
                    $c = \Carbon\Carbon::parse($b->created_at);
                    return $c->year == $dt->year && $c->month == $dt->month;
                })->first();
            } else {
                $elec_bill = $elecBills->first();
                $water_bill = $waterBills->first();
            }

            $prev_elec = 0;
            $curr_elec = null;
            if ($elec_bill) {
                $prev_elec = $elec_bill->previous_reading;
                $curr_elec = $elec_bill->current_reading;
            } else {
                $latest_elec = $elecBills->first();
                if ($latest_elec) {
                    $prev_elec = $latest_elec->current_reading;
                }
            }

            $prev_water = 0;
            $curr_water = null;
            if ($water_bill) {
                $prev_water = $water_bill->previous_reading;
                $curr_water = $water_bill->current_reading;
            } else {
                $latest_water = $waterBills->first();
                if ($latest_water) {
                    $prev_water = $latest_water->current_reading;
                }
            }

            $houses[] = [
                'block' => $lot->block,
                'lot' => $lot->lot_number,
                'elec_status' => $elec_bill ? ($elec_bill->status == 'paid' ? 'Paid' : ($elec_bill->amount > 0 || $elec_bill->usage_value > 0 ? 'Billed' : 'Pending')) : 'Pending',
                'water_status' => $water_bill ? ($water_bill->status == 'paid' ? 'Paid' : ($water_bill->amount > 0 || $water_bill->usage_value > 0 ? 'Billed' : 'Pending')) : 'Pending',
                'prev_elec' => $prev_elec,
                'curr_elec' => $curr_elec,
                'prev_water' => $prev_water,
                'curr_water' => $curr_water,
                'resident' => $res->name,
                'provider_managed' => $lot->provider_managed
            ];
        }

        $elecCycles = getValidBillingCycles('electricity');
        $waterCycles = getValidBillingCycles('water');
        $validCycles = collect(array_merge($elecCycles, $waterCycles))
            ->unique('cycle')
            ->sortByDesc('cycle')
            ->values()
            ->toArray();
        return view('finance.dashboard', ['houses' => $houses, 'validCycles' => $validCycles]);
    });

    Route::post('/api/billing/reading', function (\Illuminate\Http\Request $request) {
        $type = $request->input('type');
        $lotStr = $request->input('lot');
        $blockStr = $request->input('block');
        $prevReading = (int)$request->input('previous_reading', 0);
        $currReading = (int)$request->input('current_reading', 0);
        $usage = $request->input('usage');
        if ($usage === null) {
            $usage = max(0, $currReading - $prevReading);
        } else {
            $usage = (int)$usage;
        }
        $rate = $request->input('rate');
        
        $cycle = $request->input('cycle');
        if (empty($cycle)) {
            $cycle = date('Y-m');
        }
        $dt = \Carbon\Carbon::createFromFormat('Y-m', $cycle);
        
        if (!$rate) {
            $settingKey = $type === 'electricity' ? 'elec_rate' : 'water_rate';
            $setting = \App\Models\Setting::find($settingKey);
            $rate = $setting ? (float)$setting->value : ($type === 'electricity' ? 10 : 15);
        }
        
        if ($type === 'water') {
            $minWaterM3Setting = \App\Models\Setting::find('water_min_m3');
            $minWaterM3 = $minWaterM3Setting ? (float)$minWaterM3Setting->value : 10;
            
            $minWaterRateSetting = \App\Models\Setting::find('water_min_rate');
            $minWaterRate = $minWaterRateSetting ? (float)$minWaterRateSetting->value : 250;
            
            if ($usage <= $minWaterM3 && $usage > 0) {
                $amount = $minWaterRate;
            } elseif ($usage > $minWaterM3) {
                $amount = $minWaterRate + (($usage - $minWaterM3) * $rate);
            } else {
                $amount = 0; // If usage is 0, they can either pay 0 or min rate. Given lack of feedback on 0 usage, let's keep 0 as 0. Wait, standard utility usually charges minimum if 0. I will charge minimum if usage is 0. 
                // Ah, the user didn't respond to the question. Let's just charge the minimum for 0.
                $amount = $minWaterRate;
            }
        } else {
            $amount = $usage * $rate;
        }
        
        $lot = \App\Models\Lot::where('block', $blockStr)->where('lot_number', $lotStr)->first();
        if ($lot) {
            $user = $lot->users->first();
            $bill = \App\Models\UtilityBill::where('lot_id', $lot->id)->where('type', $type)
                        ->whereYear('created_at', $dt->year)
                        ->whereMonth('created_at', $dt->month)->first();
            
            if ($bill) {
                $bill->update([
                    'previous_reading' => $prevReading,
                    'current_reading' => $currReading,
                    'usage_value' => $usage,
                    'amount' => $amount,
                    'due_date' => $dt->copy()->addDays(30)
                ]);
            } else {
                $bill = \App\Models\UtilityBill::create([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'type' => $type,
                    'user_id' => $user ? $user->id : null,
                    'lot_id' => $lot->id,
                    'previous_reading' => $prevReading,
                    'current_reading' => $currReading,
                    'usage_value' => $usage,
                    'amount' => $amount,
                    'due_date' => $dt->copy()->addDays(30),
                    'status' => 'unpaid',
                    'is_at_risk' => false,
                ]);
                $bill->created_at = $dt->copy()->startOfMonth();
                $bill->save();
            }
            if ($user) {
                updateResidentBehavior($user);
                
                if (!empty($user->contact_number) && $amount > 0) {
                    $dueDateStr = \Carbon\Carbon::parse($bill->due_date)->format('M d, Y');
                    $amtStr = number_format($amount, 2);
                    $msg = "Althesa Subd: Your {$type} bill for {$dt->format('M Y')} is P{$amtStr}. Due on {$dueDateStr}. Please settle on time to avoid penalties.";
                    \App\Helpers\SmsHelper::sendSms($user->contact_number, $msg);
                }
            }
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false, 'message' => 'Lot not found'], 404);
    });
});

    /* =========================================================================
       PHASE 4 ROUTES
       ========================================================================= */

    // Resident Incident Reporting
    Route::post('/resident/incidents', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'subject' => 'required|string',
            'type' => 'required|string',
            'description' => 'required|string',
            'photos' => 'nullable|array',
        ]);
        
        $imagePaths = [];
        if (!empty($validated['photos']) && is_array($validated['photos'])) {
            foreach ($validated['photos'] as $base64Image) {
                if (count($imagePaths) >= 3) break; // Limit to 3 on backend too
                if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                    $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
                    $type = strtolower($type[1]);
                    if (in_array($type, ['jpg', 'jpeg', 'gif', 'png', 'webp'])) {
                        $base64Image = str_replace(' ', '+', $base64Image);
                        $imageName = 'incident_' . time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $type;
                        \Illuminate\Support\Facades\Storage::disk('public')->put('incidents/' . $imageName, base64_decode($base64Image));
                        $imagePaths[] = '/storage/incidents/' . $imageName;
                    }
                }
            }
        }
        
        $finalImageUrl = count($imagePaths) > 0 ? json_encode($imagePaths) : null;
        
        \App\Models\Incident::create([
            'subject' => $validated['subject'],
            'type' => $validated['type'],
            'description' => $validated['description'],
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'status' => 'pending',
            'image_url' => $finalImageUrl
        ]);
        
        return response()->json(['success' => true]);
    });

    // Admin User Management
    Route::post('/admin/users', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'contact_number' => 'required|string',
            'role' => 'required|string',
            'status' => 'required|string',
            'password' => 'required|string|min:6',
            'block' => 'required_if:role,Resident|integer',
            'lot' => 'required_if:role,Resident|integer',
        ]);

        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'contact_number' => $validated['contact_number'] ?? null,
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
        ]);
        $user->assignRole($validated['role']);
        
        if ($request->has('block') && $request->has('lot')) {
            if (!empty($validated['block']) && !empty($validated['lot'])) {
                $lot = \App\Models\Lot::firstOrCreate([
                    'block' => $validated['block'],
                    'lot_number' => $validated['lot']
                ]);
                $user->lots()->attach($lot->id);
                $lot->update(['status' => 'Occupied']);
            }
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

    Route::put('/admin/users/{id}', function (\Illuminate\Http\Request $request, $id) {
        $user = \App\Models\User::find($id);
        if ($user) {
            if ($user->status === 'Archived') {
                return response()->json(['success' => false, 'message' => 'Archived users cannot be edited.'], 403);
            }
            $user->name = $request->input('name', $user->name);
            $user->email = $request->input('email', $user->email);
            if ($request->has('contact_number')) {
                $user->contact_number = $request->input('contact_number');
            }
            if ($request->has('password') && !empty($request->input('password'))) {
                $user->password = \Illuminate\Support\Facades\Hash::make($request->input('password'));
            }
            $user->save();
            
            if ($request->has('role')) {
                $user->syncRoles([$request->input('role')]);
            }
            if ($request->has('block') && $request->has('lot')) {
                if (!empty($request->input('block')) && !empty($request->input('lot'))) {
                    $lot = \App\Models\Lot::firstOrCreate([
                        'block' => $request->input('block'),
                        'lot_number' => $request->input('lot')
                    ]);
                    $user->lots()->sync([$lot->id]);
                }
            }
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    });

    Route::get('/admin/api/billing/export-soa', [App\Http\Controllers\ExportController::class, 'exportSOA'])->name('admin.billing.export-soa');

    Route::post('/admin/api/billing/generate', function (\Illuminate\Http\Request $request) {
        $type = $request->input('type'); // 'electricity' or 'water'
        $rate = $request->input('rate');
        $penalty = $request->input('penalty', 5);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $waterMinM3 = $request->input('water_min_m3');
        $waterMinRate = $request->input('water_min_rate');
        
        if ($rate !== null) {
            $settingKey = $type === 'electricity' ? 'elec_rate' : 'water_rate';
            \App\Models\Setting::updateOrCreate(['key' => $settingKey], ['value' => $rate]);
        }
        if ($penalty !== null) {
            \App\Models\Setting::updateOrCreate(['key' => $type . '_penalty'], ['value' => $penalty]);
        }
        if ($type === 'water') {
            if ($waterMinM3 !== null) {
                \App\Models\Setting::updateOrCreate(['key' => 'water_min_m3'], ['value' => $waterMinM3]);
            }
            if ($waterMinRate !== null) {
                \App\Models\Setting::updateOrCreate(['key' => 'water_min_rate'], ['value' => $waterMinRate]);
            }
        }
        
        $lots = \App\Models\Lot::has('users')->with('users')->get();
        $generatedCount = 0;
        $skippedCasureco = 0;
        foreach ($lots as $lot) {
            if ($type === 'electricity' && $lot->provider_managed) {
                $skippedCasureco++;
                continue; // Skip CASURECO disconnected lots for electricity
            }
            
            $user = $lot->users->first();
            
            $lastBill = \App\Models\UtilityBill::where('lot_id', $lot->id)
                ->where('type', $type)
                ->orderBy('created_at', 'desc')
                ->first();
                
            $prevReading = $lastBill && $lastBill->current_reading !== null ? $lastBill->current_reading : 0;
            
            $carriedBalance = 0;
            if ($lastBill && $lastBill->status !== 'paid') {
                $totalDueBeforePenalty = $lastBill->amount + $lastBill->previous_balance;
                $penalty = $totalDueBeforePenalty * 0.05;
                $totalDueAfterPenalty = $totalDueBeforePenalty + $penalty;
                $totalPaid = \App\Models\Payment::where('utility_bill_id', $lastBill->id)->sum('amount_paid');
                $carriedBalance = max(0, $totalDueAfterPenalty - $totalPaid);
            }
            
            if ($startDate) {
                $startDt = \Carbon\Carbon::parse($startDate)->startOfDay();
                $existingBill = \App\Models\UtilityBill::where('lot_id', $lot->id)
                    ->where('type', $type)
                    ->whereYear('created_at', $startDt->year)
                    ->whereMonth('created_at', $startDt->month)
                    ->exists();
                    
                if ($existingBill) {
                    continue; // Skip to prevent duplicate cycle for this exact month
                }
            }

            $bill = \App\Models\UtilityBill::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => $type,
                'user_id' => $user ? $user->id : null,
                'lot_id' => $lot->id,
                'previous_reading' => $prevReading,
                'current_reading' => 0,
                'usage_value' => 0,
                'amount' => 0,
                'previous_balance' => $carriedBalance,
                'due_date' => $endDate,
                'status' => 'unpaid',
                'is_at_risk' => false,
            ]);
            $generatedCount++;
            
            if ($startDate) {
                $bill->created_at = \Carbon\Carbon::parse($startDate)->startOfDay();
                $bill->save();
            }
        }
        
        if ($generatedCount === 0 && $skippedCasureco > 0) {
            return response()->json(['success' => false, 'message' => 'No bills generated. All assigned lots are managed by CASURECO.']);
        }

        return response()->json(['success' => true]);
    });

    Route::post('/admin/api/billing/disconnect', function (\Illuminate\Http\Request $request) {
        $lotNumber = $request->input('lot');
        $lot = \App\Models\Lot::where('lot_number', $lotNumber)->orWhere('block', 'like', "%$lotNumber%")->first();
        if ($lot) {
            $lot->provider_managed = true;
            $lot->save();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false, 'message' => 'Lot not found.'], 404);
    });

    Route::post('/admin/api/billing/reconnect', function (\Illuminate\Http\Request $request) {
        $lotNumber = $request->input('lot');
        $lot = \App\Models\Lot::where('lot_number', $lotNumber)->orWhere('block', 'like', "%$lotNumber%")->first();
        if ($lot) {
            $lot->provider_managed = false;
            $lot->save();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false, 'message' => 'Lot not found.'], 404);
    });

    Route::post('/admin/api/billing/add-balance', function (\Illuminate\Http\Request $request) {
        $id = $request->input('id');
        $balance = (float)$request->input('balance');
        $bill = \App\Models\UtilityBill::where('id', 'like', $id . '%')->first();
        if ($bill) {
            $bill->update(['previous_balance' => $bill->previous_balance + $balance]);
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    });

    Route::post('/admin/api/billing/pay', function (\Illuminate\Http\Request $request) {
        $id = $request->input('id');
        $amount = (float)$request->input('amount');
        
        $bill = \App\Models\UtilityBill::where('id', 'like', $id . '%')->first();
        if ($bill) {
            $totalDueBeforePenalty = $bill->amount + $bill->previous_balance;
            $penalty = ($bill->due_date && \Carbon\Carbon::parse($bill->due_date)->isPast()) ? ($totalDueBeforePenalty * 0.05) : 0;
            $totalDue = $totalDueBeforePenalty + $penalty;
            $totalPaid = \App\Models\Payment::where('utility_bill_id', $bill->id)->sum('amount_paid');
            
            $remaining = round($totalDue - $totalPaid, 2);
            if ($remaining <= 0) {
                return response()->json(['success' => false, 'message' => 'Bill is already fully paid.'], 400);
            }
            if ($amount > $remaining) {
                return response()->json(['success' => false, 'message' => 'Payment amount exceeds the remaining balance.'], 400);
            }

            \App\Models\Payment::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'utility_bill_id' => $bill->id,
                'amount_paid' => $amount,
                'method' => 'Office',
                'trn' => 'TRN-' . strtoupper(\Illuminate\Support\Str::random(8)),
                'payment_date' => now(),
            ]);
            
            $totalPaidAfter = \App\Models\Payment::where('utility_bill_id', $bill->id)->sum('amount_paid');
            if ($totalPaidAfter >= $totalDue - 0.01) {
                $bill->update(['status' => 'paid']);
            }
            
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    });

    Route::put('/admin/users/{id}/archive', function ($id) {
        $user = \App\Models\User::find($id);
        if ($user) {
            foreach($user->lots as $lot) {
                $lot->update(['status' => 'Vacant House']);
            }
            $user->update(['status' => 'Archived']);
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    });

    Route::put('/admin/users/{id}/restore', function ($id) {
        $user = \App\Models\User::find($id);
        if ($user) {
            foreach($user->lots as $lot) {
                $lot->update(['status' => 'Occupied']);
            }
            $user->update(['status' => 'Active']);
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
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
                                <p style='margin: 4px 0;'><strong>📋 Inquiry Type:</strong> {$type}</p>
                                <p style='margin: 4px 0;'><strong>📝 Notes:</strong> {$notes}</p>
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

    $user = \App\Models\User::where('email', $email)->orWhere('name', $residentName)->first();

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
    $unavailableDates = json_decode(\App\Models\Setting::where('key', 'unavailable_dates')->value('value') ?? '[]', true);
    
    $appointments = \App\Models\Appointment::where('status', '!=', 'Cancelled')
        ->where('date', '>=', now()->format('Y-m-d'))
        ->get(['date', 'time']);
        
    return response()->json([
        'unavailable_dates' => $unavailableDates,
        'taken_timeslots' => $appointments
    ]);
});
