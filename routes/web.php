<?php

use Illuminate\Support\Facades\Route;

// Shared Incident Data Function for Simulation
if (!function_exists('getIncidents')) {
    function getIncidents() {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('incidents') && \App\Models\Incident::count() > 0) {
                return \App\Models\Incident::with('user')->get()->map(function ($inc) {
                    $resName = 'Anonymous';
                    if ($inc->user) {
                        $role = $inc->user->roles->first()->name ?? 'Resident';
                        if ($role === 'Resident' && $inc->user->lots->count() > 0) {
                            $lot = $inc->user->lots->first();
                            $resName = 'Block ' . $lot->block . ', Lot ' . $lot->lot_number;
                        } else {
                            $resName = $inc->user->name;
                        }
                    }
                    return [
                        'id' => substr($inc->id, 0, 8),
                        'sub' => $inc->subject,
                        'type' => $inc->type,
                        'res' => $resName,
                        'desc' => $inc->description,
                        'img' => $inc->image_url,
                        'photos' => $inc->image_url ? [$inc->image_url] : [],
                        'status' => $inc->status,
                        'date' => $inc->created_at ? $inc->created_at->toIso8601String() : now()->toIso8601String(),
                    ];
                })->toArray();
            }
        } catch (\Exception $e) {} 
        return [];
    }
}

if (!function_exists('getAnnouncements')) {
    function getAnnouncements() {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('announcements') && \App\Models\Announcement::count() > 0) {
                return \App\Models\Announcement::all()->map(function ($ann) {
                    return [
                        'id' => substr($ann->id, 0, 8),
                        'title' => $ann->title,
                        'cat' => $ann->category,
                        'status' => $ann->status,
                        'date' => \Carbon\Carbon::parse($ann->publish_date)->format('Y-m-d'),
                        'content' => $ann->content,
                        'author' => $ann->author,
                    ];
                })->toArray();
            }
        } catch (\Exception $e) {} 
        return [];
    }
}

if (!function_exists('getElectricalBills')) {
    function getElectricalBills() {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                $residents = \App\Models\User::role('Resident')->with(['lots', 'utilityBills' => function($q) {
                    $q->where('type', 'electricity')->latest();
                }])->get();
                
                return $residents->map(function ($resident) {
                    $lot = $resident->lots->first();
                    $bill = $resident->utilityBills->first();
                    
                    $payments = [];
                    if ($resident->utilityBills->count() > 0) {
                        $allPayments = $resident->utilityBills->flatMap->payments;
                        $payments = $allPayments->map(function ($p) {
                            return [
                                'month' => \Carbon\Carbon::parse($p->payment_date)->format('M Y'),
                                'amount' => $p->amount_paid,
                                'status' => 'Paid',
                                'date' => \Carbon\Carbon::parse($p->payment_date)->format('y-m-d'),
                                'trn' => $p->trn,
                            ];
                        })->toArray();
                    }
                    
                    return [
                        'id' => $bill ? substr($bill->id, 0, 8) : 'NEW-' . $resident->id,
                        'db_id' => $bill ? $bill->id : null,
                        'lot' => $lot ? 'B' . $lot->block . ' L' . $lot->lot_number : 'N/A',
                        'block' => $lot ? $lot->block : 'N/A',
                        'resident' => $resident->name,
                        'amount' => $bill ? $bill->amount : 0,
                        'usage' => ($bill ? $bill->usage_value : 0) . ' kWh',
                        'usage_kwh' => $bill ? $bill->usage_value : 0,
                        'base_amount' => $bill ? $bill->amount : 0,
                        'status' => $bill ? $bill->status : 'unpaid',
                        'due' => ($bill && $bill->due_date) ? \Carbon\Carbon::parse($bill->due_date)->format('Y-m-d') : 'N/A',
                        'paid_date' => count($payments) > 0 ? $payments[0]['date'] : null,
                        'method' => count($payments) > 0 ? 'Office' : null,
                        'usage_history' => array_fill(0, 12, $bill ? $bill->usage_value : 0),
                        'at_risk' => $bill ? $bill->is_at_risk : false,
                        'payment_history' => $payments,
                        'audit_log' => [['action' => 'Statement Generated', 'date' => \Carbon\Carbon::now()->format('Y-m-d h:i A'), 'user' => 'System']],
                    ];
                })->toArray();
            }
        } catch (\Exception $e) {} 
        return [];
    }
}

if (!function_exists('getWaterBills')) {
    function getWaterBills() {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                $residents = \App\Models\User::role('Resident')->with(['lots', 'utilityBills' => function($q) {
                    $q->where('type', 'water')->latest();
                }])->get();
                
                return $residents->map(function ($resident) {
                    $lot = $resident->lots->first();
                    $bill = $resident->utilityBills->first();
                    
                    $payments = [];
                    if ($resident->utilityBills->count() > 0) {
                        $allPayments = $resident->utilityBills->flatMap->payments;
                        $payments = $allPayments->map(function ($p) {
                            return [
                                'month' => \Carbon\Carbon::parse($p->payment_date)->format('M Y'),
                                'amount' => $p->amount_paid,
                                'status' => 'Paid',
                                'date' => \Carbon\Carbon::parse($p->payment_date)->format('y-m-d'),
                                'trn' => $p->trn,
                            ];
                        })->toArray();
                    }
                    
                    return [
                        'id' => $bill ? substr($bill->id, 0, 8) : 'NEW-' . $resident->id,
                        'db_id' => $bill ? $bill->id : null,
                        'lot' => $lot ? 'B' . $lot->block . ' L' . $lot->lot_number : 'N/A',
                        'block' => $lot ? $lot->block : 'N/A',
                        'resident' => $resident->name,
                        'amount' => $bill ? $bill->amount : 0,
                        'usage' => ($bill ? $bill->usage_value : 0) . ' m³',
                        'usage_m3' => $bill ? $bill->usage_value : 0,
                        'usage_cbm' => $bill ? $bill->usage_value : 0,
                        'base_amount' => $bill ? $bill->amount : 0,
                        'status' => $bill ? $bill->status : 'unpaid',
                        'due' => ($bill && $bill->due_date) ? \Carbon\Carbon::parse($bill->due_date)->format('Y-m-d') : 'N/A',
                        'paid_date' => count($payments) > 0 ? $payments[0]['date'] : null,
                        'method' => count($payments) > 0 ? 'Office' : null,
                        'usage_history' => array_fill(0, 12, $bill ? $bill->usage_value : 0),
                        'at_risk' => $bill ? $bill->is_at_risk : false,
                        'payment_history' => $payments,
                        'audit_log' => [['action' => 'Statement Generated', 'date' => \Carbon\Carbon::now()->format('Y-m-d h:i A'), 'user' => 'System']],
                    ];
                })->toArray();
            }
        } catch (\Exception $e) {} 
        return [];
    }
}

if (!function_exists('getReservationFees')) {
    function getReservationFees() {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('reservations') && \App\Models\Reservation::count() > 0) {
                return \App\Models\Reservation::with(['lot'])->get()->map(function ($res) {
                    return [
                        'id' => substr($res->id, 0, 8),
                        'buyer' => $res->notes ?? 'Guest', // Stored in notes
                        'contact' => '0917-000-0000', // Mock
                        'block' => $res->lot->block ?? 'N/A',
                        'lot' => $res->lot->lot_number ?? 'N/A',
                        'amount' => $res->amount,
                        'date' => \Carbon\Carbon::parse($res->reservation_date)->format('Y-m-d'),
                        'status' => $res->status,
                        'agent' => 'Admin',
                        'notes' => 'Database record',
                    ];
                })->toArray();
            }
        } catch (\Exception $e) {} 
        return [];
    }
}

if (!function_exists('getDownpaymentFees')) {
    function getDownpaymentFees() {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('downpayments') && \App\Models\Downpayment::count() > 0) {
                return \App\Models\Downpayment::with(['reservation.lot'])->get()->map(function ($dp) {
                    $res = $dp->reservation;
                    return [
                        'id' => substr($dp->id, 0, 8),
                        'buyer' => $res->notes ?? 'Guest',
                        'block' => $res->lot->block ?? 'N/A',
                        'lot' => $res->lot->lot_number ?? 'N/A',
                        'total_dp' => $dp->amount + $dp->balance,
                        'paid_amount' => $dp->amount,
                        'monthly_amortization' => 15000,
                        'months_paid' => floor($dp->amount / 15000),
                        'total_months' => floor(($dp->amount + $dp->balance) / 15000),
                        'status' => $dp->status,
                        'next_due' => \Carbon\Carbon::parse($dp->due_date)->format('Y-m-d'),
                        'last_payment' => \Carbon\Carbon::parse($dp->updated_at)->format('Y-m-d'),
                    ];
                })->toArray();
            }
        } catch (\Exception $e) {} 
        return [];
    }
}



if (!function_exists('getLotStatus')) {
    function getLotStatus() {
        return [
            ['lot' => 'B1 L5', 'managed' => true, 'provider' => 'Subdivision'],
            ['lot' => 'B2 L12', 'managed' => true, 'provider' => 'Subdivision'],
            ['lot' => 'B3 L8', 'managed' => true, 'provider' => 'Subdivision'],
            ['lot' => 'A1 L4', 'managed' => false, 'provider' => 'CASURECO'],
            ['lot' => 'A2 L1', 'managed' => false, 'provider' => 'CASURECO'],
            ['lot' => 'C1 L10', 'managed' => true, 'provider' => 'Subdivision'],
        ];
    }
}

Route::get('/', function () {
    return view('welcome');
});

Route::get('/appointment', function () {
    return view('appointment');
});

Route::get('/login', function () {
    return view('login');
})->name('login')->middleware('guest');

Route::post('/login', function (\Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (\Illuminate\Support\Facades\Auth::attempt($credentials)) {
        $request->session()->regenerate();
        $user = \Illuminate\Support\Facades\Auth::user();
        
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

if (!function_exists('getVisitorPins')) {
    function getVisitorPins() {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('visitors') && \App\Models\Visitor::count() > 0) {
                $base = \App\Models\Visitor::with('host')->get()->map(function ($vis) {
                    $block = 'N/A';
                    $lotNum = 'N/A';
                    $hostName = 'Unknown';
                    
                    if ($vis->host) {
                        $hostName = $vis->host->name;
                        if ($vis->host->lots->count() > 0) {
                            $lot = $vis->host->lots->first();
                            $block = $lot->block;
                            $lotNum = $lot->lot_number;
                        }
                    }

                    return [
                        'id' => substr($vis->id, 0, 8),
                        'pin' => $vis->pin,
                        'visitor' => $vis->visitor_name,
                        'host' => $hostName,
                        'block' => $block,
                        'lot' => $lotNum,
                        'purpose' => $vis->purpose,
                        'validity' => $vis->validity,
                        'status' => $vis->status,
                        'arrival_time' => $vis->arrival_time,
                        'type' => $vis->type,
                        'plate_number' => $vis->plate_number,
                    ];
                })->toArray();
            } else {
                $base = [
                    ['id' => 'VIS-3001', 'pin' => '482910', 'visitor' => 'Alex Mendez', 'host' => 'Juan Dela Cruz', 'block' => '1', 'lot' => '5', 'purpose' => 'Plumbing Repair', 'validity' => 'Today', 'status' => 'Pending'],
                    ['id' => 'VIS-3002', 'pin' => '910234', 'visitor' => 'Grab Delivery', 'host' => 'Maria Santos', 'block' => '2', 'lot' => '12', 'purpose' => 'Food Delivery', 'validity' => 'Today', 'status' => 'Entered', 'arrival_time' => '10:15 AM'],
                    ['id' => 'VIS-3003', 'pin' => '551029', 'visitor' => 'Sarah Connor', 'host' => 'Ricardo Reyes', 'block' => '3', 'lot' => '8', 'purpose' => 'Family Visit', 'validity' => 'May 25, 2026', 'status' => 'Pending'],
                ];
            }
        } catch (\Exception $e) {
            $base = [
                ['id' => 'VIS-3001', 'pin' => '482910', 'visitor' => 'Alex Mendez', 'host' => 'Juan Dela Cruz', 'block' => '1', 'lot' => '5', 'purpose' => 'Plumbing Repair', 'validity' => 'Today', 'status' => 'Pending'],
                ['id' => 'VIS-3002', 'pin' => '910234', 'visitor' => 'Grab Delivery', 'host' => 'Maria Santos', 'block' => '2', 'lot' => '12', 'purpose' => 'Food Delivery', 'validity' => 'Today', 'status' => 'Entered', 'arrival_time' => '10:15 AM'],
                ['id' => 'VIS-3003', 'pin' => '551029', 'visitor' => 'Sarah Connor', 'host' => 'Ricardo Reyes', 'block' => '3', 'lot' => '8', 'purpose' => 'Family Visit', 'validity' => 'May 25, 2026', 'status' => 'Pending'],
            ];
        }

        // Simulate persistence via session
        $overrides = session('visitor_overrides', []);
        foreach ($base as &$vis) {
            if (isset($overrides[$vis['id']])) {
                $vis = array_merge($vis, $overrides[$vis['id']]);
            }
        }
        return $base;
    }
}

// Resident Routes
Route::prefix('resident')->middleware(['auth', 'web', 'role:Resident'])->group(function () {
    Route::get('/dashboard', function () {
        return view('resident.dashboard');
    });
});

// Guard Security Portal Routes
Route::prefix('guard')->middleware(['auth', 'web', 'role:Security Guard'])->group(function () {
    Route::get('/dashboard', function () {
        return view('guard.dashboard', ['pins' => getVisitorPins(), 'users' => getUsers()]);
    });
    Route::get('/history', function () {
        return view('guard.history', ['pins' => getVisitorPins()]);
    });
});

// API Routes for Data Persistence
Route::post('/api/visitors', function (\Illuminate\Http\Request $request) {
    \App\Models\Visitor::create([
        'visitor_name' => $request->input('visitor_name'),
        'purpose' => $request->input('purpose'),
        'type' => $request->input('type'),
        'validity' => $request->input('validity', 'Today'),
        'status' => $request->input('status', 'Pending'),
        'arrival_time' => $request->input('arrival_time'),
        'plate_number' => $request->input('plate_number'),
        'pin' => $request->input('pin', (string)rand(100000, 999999)),
        'host_id' => null, // Optional mapping
    ]);
    return response()->json(['success' => true]);
});

Route::put('/api/visitors/{id}/status', function (\Illuminate\Http\Request $request, $id) {
    $visitor = \App\Models\Visitor::where('id', 'like', $id . '%')->first();
    if ($visitor) {
        $visitor->update([
            'status' => $request->input('status'),
            'arrival_time' => $request->input('arrival_time', $visitor->arrival_time)
        ]);
    }
    return response()->json(['success' => true]);
});

if (!function_exists('getUsers')) {
    function getUsers() {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('users') && \App\Models\User::count() > 0) {
                return \App\Models\User::with(['lots', 'roles'])->get()->map(function ($u) {
                    $role = $u->roles->first()->name ?? 'Resident';
                    $meta = 'N/A';
                    if ($role === 'Resident' && $u->lots->count() > 0) {
                        $lot = $u->lots->first();
                        $meta = 'Block ' . $lot->block . ', Lot ' . $lot->lot_number;
                    } elseif ($role === 'Security Guard') {
                        $meta = 'Badge #' . rand(10, 99);
                    } elseif ($role === 'Finance Officer') {
                        $meta = 'Chief Accountant';
                    }
                    return [
                        'db_id' => $u->id,
                        'id' => substr($u->id, 0, 8), // shorten uuid for UI
                        'name' => $u->name,
                        'contact_number' => $u->contact_number,
                        'role' => $role,
                        'email' => $u->email,
                        'status' => $u->status,
                        'joined' => $u->joined_at ? \Carbon\Carbon::parse($u->joined_at)->format('Y-m-d') : null,
                        'meta' => $meta,
                        'block' => isset($lot) ? $lot->block : '',
                        'lot' => isset($lot) ? $lot->lot_number : '',
                        'pin' => rand(100000, 999999),
                    ];
                })->toArray();
            }
        } catch (\Exception $e) {
            // Ignore DB errors if not setup yet
        }

        return [
            // Residents
            ['id' => 'USR-1001', 'name' => 'Juan Dela Cruz', 'role' => 'Resident', 'email' => 'juan@gmail.com', 'status' => 'Active', 'joined' => '2024-01-15', 'meta' => 'Block 1, Lot 5', 'pin' => '884219'],
            ['id' => 'USR-1002', 'name' => 'Maria Santos', 'role' => 'Resident', 'email' => 'maria@gmail.com', 'status' => 'Active', 'joined' => '2024-02-20', 'meta' => 'Block 2, Lot 12', 'pin' => '729104'],
            ['id' => 'USR-1003', 'name' => 'Ricardo Reyes', 'role' => 'Resident', 'email' => 'ricardo@gmail.com', 'status' => 'Active', 'joined' => '2024-03-05', 'meta' => 'Block 3, Lot 8'],
            
            // Security Guards
            ['id' => 'USR-2001', 'name' => 'Sgt. Robert Miller', 'role' => 'Security Guard', 'email' => 'robert.guard@althesa.com', 'status' => 'Active', 'joined' => '2023-10-10', 'meta' => 'Badge #042'],
            ['id' => 'USR-2002', 'name' => 'Officer Jane Doe', 'role' => 'Security Guard', 'email' => 'jane.guard@althesa.com', 'status' => 'Active', 'joined' => '2023-12-05', 'meta' => 'Badge #088'],
            
            // Finance Officer (Single Lead)
            ['id' => 'USR-3001', 'name' => 'CPA Michael Tan', 'role' => 'Finance Officer', 'email' => 'michael.finance@althesa.com', 'status' => 'Active', 'joined' => '2023-11-20', 'meta' => 'Chief Accountant'],
            
            ['id' => 'USR-1004', 'name' => 'Elena Gomez', 'role' => 'Resident', 'email' => 'elena@gmail.com', 'status' => 'Archived', 'joined' => '2023-11-12', 'meta' => 'Block 1, Lot 22'],
        ];
    }
}

if (!function_exists('getAppointments')) {
    function getAppointments() {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('appointments') && \App\Models\Appointment::count() > 0) {
                return \App\Models\Appointment::orderBy('created_at', 'desc')->get()->map(function ($apt) {
                    return [
                        'id' => substr($apt->id, 0, 8),
                        'client' => $apt->client_name,
                        'contact' => $apt->contact_number,
                        'email' => $apt->email,
                        'date' => \Carbon\Carbon::parse($apt->date)->format('Y-m-d'),
                        'time' => $apt->time,
                        'status' => $apt->status,
                        'type' => $apt->type,
                        'notes' => $apt->notes,
                        'report' => $apt->report,
                    ];
                })->toArray();
            }
        } catch (\Exception $e) {} 
        return [];
    }
}

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

// Admin Routes
Route::prefix('admin')->middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/dashboard', function () {
        $openIncidentsCount = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('incidents')) {
            $openIncidentsCount = \App\Models\Incident::whereIn('status', ['pending', 'progress'])->count();
        }
        return view('admin.dashboard', compact('openIncidentsCount'));
    });

    // User Management System
    Route::get('/users', function () { 
        $users = \App\Models\User::with(['lots', 'roles'])->get()->map(function(/** @var \App\Models\User */ $u) {
            $role = $u->roles->first()->name ?? 'Resident';
            $lot = $u->lots->first();
            return [
                'id' => 'USR-' . str_pad($u->id, 4, '0', STR_PAD_LEFT),
                'db_id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $role,
                'status' => 'Active', // Mocking status as DB doesn't have it
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
        $bills = getElectricalBills();
        $lots = getLotStatus();
        
        // Consistent Simulated Statistical Data for Graphing (Reliable)
        $yearly_stats = [
            '2025' => [
                'May' => 10200, 'Jun' => 11500, 'Jul' => 12800, 'Aug' => 14000, 
                'Sep' => 13500, 'Oct' => 12000, 'Nov' => 11000, 'Dec' => 15500,
                'Jan' => 16500, 'Feb' => 14000, 'Mar' => 13200, 'Apr' => 15000
            ],
            '2026' => [
                'May' => 11000, 'Jun' => 12500, 'Jul' => 13500, 'Aug' => 14500, 
                'Sep' => 14000, 'Oct' => 13000, 'Nov' => 12500, 'Dec' => 16000,
                'Jan' => 17000, 'Feb' => 15500, 'Mar' => 14200, 'Apr' => 16500
            ],
            'today_paid' => 4550.00
        ];

        return view('admin.billing.index', [
            'bills' => $bills,
            'lots' => $lots,
            'stats' => $yearly_stats
        ]);
    });
    
    Route::get('/water', function () {
        // Reuse identical statistical graphs for simulation 
        $yearly_stats = [
            '2026' => [
                'May' => 3100, 'Jun' => 4500, 'Jul' => 5500, 'Aug' => 6500, 
                'Sep' => 6000, 'Oct' => 5000, 'Nov' => 4500, 'Dec' => 7000,
                'Jan' => 8000, 'Feb' => 7500, 'Mar' => 6200, 'Apr' => 8500
            ],
            'today_paid' => 1250.00
        ];
        return view('admin.water.index', [
            'bills' => getWaterBills(),
            'stats' => $yearly_stats
        ]);
    });

    Route::post('/reservation-fee', function (\Illuminate\Http\Request $request) {
        $lot = \App\Models\Lot::firstOrCreate(
            ['block' => $request->input('block'), 'lot_number' => $request->input('lot')],
            ['status' => 'Reserved', 'provider_managed' => false]
        );
        $res = \App\Models\Reservation::create([
            'lot_id' => $lot->id,
            'status' => 'Reserved',
            'reservation_date' => now(),
            'amount' => $request->input('amount') ?? 20000,
            'notes' => ($request->input('name') ?? 'New Buyer') . ' | ' . ($request->input('contact') ?? 'N/A')
        ]);
        return response()->json(['success' => true, 'id' => substr($res->id, 0, 8)]);
    });

    Route::post('/downpayment-fee', function (\Illuminate\Http\Request $request) {
        $lot = \App\Models\Lot::firstOrCreate(
            ['block' => $request->input('block'), 'lot_number' => $request->input('lot')],
            ['status' => 'Reserved', 'provider_managed' => false]
        );
        $res = \App\Models\Reservation::create([
            'lot_id' => $lot->id,
            'status' => 'Pending',
            'reservation_date' => now(),
            'amount' => 0,
            'notes' => $request->input('name') ?? 'New Buyer'
        ]);
        $dp = \App\Models\Downpayment::create([
            'reservation_id' => $res->id,
            'amount' => 0,
            'balance' => $request->input('dpAmount') ?? 0,
            'due_date' => \Carbon\Carbon::parse($request->input('nextDate') ?? now()->addMonth()),
            'status' => 'Good Standing'
        ]);
        return response()->json(['success' => true, 'id' => substr($dp->id, 0, 8)]);
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
            'stats' => $yearly_stats
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
            'stats' => $yearly_stats
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
        return view('admin.visitors.index', ['pins' => getVisitorPins()]);
    });
    Route::get('/gis', function () { return view('admin.gis.index'); });
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
Route::prefix('resident')->middleware(['auth', 'role:Resident'])->group(function () {
    Route::get('/dashboard', function () {
        $user = \Illuminate\Support\Facades\Auth::user();
        $elecBills = getElectricalBills();
        $waterBills = getWaterBills();
        $elecBill = collect($elecBills)->firstWhere('resident', $user->name ?? 'Jepuso') ?? [
            'status' => 'paid', 'amount' => 0, 'due' => 'N/A', 'usage_history' => [0],
            'id' => 'N/A', 'resident' => $user->name ?? 'Resident', 'period' => 'N/A',
            'kwh' => 0, 'rate' => 0, 'prev' => 0, 'curr' => 0,
        ];
        $waterBill = collect($waterBills)->firstWhere('resident', $user->name ?? 'Jepuso') ?? [
            'status' => 'paid', 'amount' => 0, 'due' => 'N/A', 'usage_history' => [0],
            'id' => 'N/A', 'resident' => $user->name ?? 'Resident', 'period' => 'N/A',
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
    Route::get('/visitors', function () {
        $pins = collect(getVisitorPins())->where('host', 'Juan Dela Cruz')->values()->all();
        return view('resident.visitors.index', ['pins' => $pins]);
    });
    Route::get('/electricity', function () {
        $user = \Illuminate\Support\Facades\Auth::user();
        $elecBills = getElectricalBills();
        $elecBill = collect($elecBills)->firstWhere('resident', $user->name) ?? [
            'id' => 'N/A', 'resident' => $user->name, 'period' => 'N/A', 'status' => 'paid',
            'amount' => 0, 'due' => 'N/A', 'paid_date' => 'N/A', 'usage' => '0 kWh',
            'kwh' => 0, 'rate' => 0, 'prev' => 0, 'curr' => 0, 'usage_history' => array_fill(0, 12, 0),
            'payment_history' => [],
        ];
        return view('resident.electricity', ['elecBill' => $elecBill]);
    });
    Route::get('/water', function () {
        $user = \Illuminate\Support\Facades\Auth::user();
        $waterBills = getWaterBills();
        $waterBill = collect($waterBills)->firstWhere('resident', $user->name) ?? [
            'id' => 'N/A', 'resident' => $user->name, 'period' => 'N/A', 'status' => 'paid',
            'amount' => 0, 'due' => 'N/A', 'paid_date' => 'N/A', 'usage' => '0 m³',
            'cubic' => 0, 'rate' => 0, 'prev' => 0, 'curr' => 0, 'usage_history' => array_fill(0, 12, 0),
            'payment_history' => [],
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
        \App\Models\Incident::create([
            'subject' => $request->input('subject'),
            'type' => $request->input('type'),
            'description' => $request->input('description'),
            'status' => 'pending',
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
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
        $residents = \App\Models\User::role('Resident')->with(['lots', 'utilityBills' => function($q) {
            $q->latest();
        }])->get();
        
        $houses = [];
        foreach ($residents as $res) {
            $lot = $res->lots->first();
            if (!$lot) continue;
            
            $elec_bill = $res->utilityBills->where('type', 'electricity')->first();
            $water_bill = $res->utilityBills->where('type', 'water')->first();
            
            $houses[] = [
                'block' => $lot->block,
                'lot' => $lot->lot_number,
                'elec_status' => $elec_bill ? ($elec_bill->status == 'unpaid' ? 'Pending' : 'Billed') : 'Pending',
                'water_status' => $water_bill ? ($water_bill->status == 'unpaid' ? 'Pending' : 'Billed') : 'Pending',
                'prev_elec' => $elec_bill ? $elec_bill->usage_value : 0,
                'curr_elec' => null,
                'prev_water' => $water_bill ? $water_bill->usage_value : 0,
                'curr_water' => null,
                'resident' => $res->name
            ];
        }

        return view('finance.dashboard', ['houses' => $houses]);
    });

    Route::post('/api/billing/reading', function (\Illuminate\Http\Request $request) {
        $type = $request->input('type');
        $lotStr = $request->input('lot');
        $blockStr = $request->input('block');
        $usage = $request->input('usage');
        
        $settingKey = $type === 'electricity' ? 'elec_rate' : 'water_rate';
        $setting = \App\Models\Setting::find($settingKey);
        $rate = $setting ? (float)$setting->value : ($type === 'electricity' ? 10 : 15);
        $amount = $usage * $rate;
        
        $lot = \App\Models\Lot::where('block', $blockStr)->where('lot_number', $lotStr)->first();
        if ($lot) {
            $user = $lot->users->first();
            $bill = \App\Models\UtilityBill::where('lot_id', $lot->id)->where('type', $type)->where('status', 'unpaid')->first();
            
            if ($bill) {
                $bill->update([
                    'usage_value' => $usage,
                    'amount' => $amount,
                    'due_date' => now()->addDays(30)
                ]);
            } else {
                \App\Models\UtilityBill::create([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'type' => $type,
                    'user_id' => $user ? $user->id : null,
                    'lot_id' => $lot->id,
                    'usage_value' => $usage,
                    'amount' => $amount,
                    'due_date' => now()->addDays(30),
                    'status' => 'unpaid',
                    'is_at_risk' => false,
                ]);
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
        
        $imagePath = null;
        if (!empty($validated['photos']) && count($validated['photos']) > 0) {
            $base64Image = $validated['photos'][0];
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
                $type = strtolower($type[1]);
                if (in_array($type, ['jpg', 'jpeg', 'gif', 'png', 'webp'])) {
                    $base64Image = str_replace(' ', '+', $base64Image);
                    $imageName = 'incident_' . time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $type;
                    \Illuminate\Support\Facades\Storage::disk('public')->put('incidents/' . $imageName, base64_decode($base64Image));
                    $imagePath = '/storage/incidents/' . $imageName;
                }
            }
        }
        
        \App\Models\Incident::create([
            'subject' => $validated['subject'],
            'type' => $validated['type'],
            'description' => $validated['description'],
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'status' => 'pending',
            'image_url' => $imagePath
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
            }
        }
        
        return response()->json(['success' => true]);
    });

    Route::put('/admin/users/{id}', function (\Illuminate\Http\Request $request, $id) {
        $user = \App\Models\User::find($id);
        if ($user) {
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

    Route::post('/admin/api/billing/generate', function (\Illuminate\Http\Request $request) {
        $type = $request->input('type'); // 'electricity' or 'water'
        
        $lots = \App\Models\Lot::has('users')->with('users')->get();
        foreach ($lots as $lot) {
            $user = $lot->users->first();
            
            // Always create a new bill for the new cycle
            \App\Models\UtilityBill::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => $type,
                'user_id' => $user ? $user->id : null,
                'lot_id' => $lot->id,
                'usage_value' => 0,
                'amount' => 0,
                'due_date' => null,
                'status' => 'unpaid',
                'is_at_risk' => false,
            ]);
        }
        return response()->json(['success' => true]);
    });

    Route::post('/admin/api/billing/pay', function (\Illuminate\Http\Request $request) {
        $id = $request->input('id');
        $amount = $request->input('amount');
        
        $bill = \App\Models\UtilityBill::where('id', 'like', $id . '%')->first();
        if ($bill) {
            $bill->update(['status' => 'paid']);
            \App\Models\Payment::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'utility_bill_id' => $bill->id,
                'amount_paid' => $amount,
                'method' => 'Office',
                'trn' => 'TRN-' . strtoupper(\Illuminate\Support\Str::random(8)),
                'payment_date' => now(),
            ]);
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    });

    Route::delete('/admin/users/{id}', function ($id) {
        $user = \App\Models\User::find($id);
        if ($user) {
            $user->delete();
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

Route::post('/api/send-billing-warning-email', function (Request $request) {
    $email = $request->input('email', 'eighty6pharmacy@gmail.com');
    $residentName = $request->input('resident_name', 'Resident');
    $billId = $request->input('bill_id', 'EB-002');
    $amount = $request->input('amount', '2,100.00');

    try {
        Mail::send([], [], function ($message) use ($email, $residentName, $billId, $amount) {
            $message->to($email)
                ->subject('⚡ Urgent Notice: Past Due Utility Bill Warning — Althesa Subdivision')
                ->html("
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 16px; background: #ffffff;'>
                        <div style='background: #b91c1c; padding: 20px; border-radius: 12px; text-align: center;'>
                            <h2 style='color: #ffffff; margin: 0;'>Althesa Billing Office</h2>
                            <p style='color: #fee2e2; margin: 4px 0 0 0; font-size: 13px;'>Past Due Disconnection Warning</p>
                        </div>
                        <div style='padding: 20px 0;'>
                            <p style='font-size: 16px; color: #0f172a;'>Dear <strong>{$residentName}</strong>,</p>
                            <p style='color: #475569;'>This is an official notice regarding your past due account statement <strong>{$billId}</strong> in the amount of <strong>₱{$amount}</strong>.</p>
                            <div style='background: #fee2e2; padding: 16px; border-radius: 12px; border-left: 4px solid #b91c1c; margin: 20px 0;'>
                                <p style='margin: 0; color: #991b1b; font-weight: 700;'>⚠️ Action Required within 48 Hours</p>
                                <p style='margin: 6px 0 0 0; color: #7f1d1d; font-size: 13px;'>Please settle your balance at the Subdivision Administration Office or via GCash/Online Banking to prevent service disconnection.</p>
                            </div>
                        </div>
                        <div style='border-top: 1px solid #e2e8f0; padding-top: 16px; font-size: 12px; color: #94a3b8; text-align: center;'>
                            Althesa Subdivision Treasury & Financial Office
                        </div>
                    </div>
                ");
        });
        return response()->json(['success' => true, 'message' => 'Billing warning email sent successfully!']);
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
