<?php

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
                    $photosArray = [];
                    if ($inc->image_url) {
                        $decoded = json_decode($inc->image_url, true);
                        $photosArray = is_array($decoded) ? $decoded : [$inc->image_url];
                    }

                    return [
                        'id' => substr($inc->id, 0, 8),
                        'sub' => $inc->subject,
                        'type' => $inc->type,
                        'res' => $resName,
                        'desc' => $inc->description,
                        'img' => count($photosArray) > 0 ? $photosArray[0] : null,
                        'photos' => $photosArray,
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

if (!function_exists('getMonthlyChartData')) {
    function getMonthlyChartData($type) {
        $labels = [];
        $data = [];
        $colors = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = \Carbon\Carbon::now()->subMonths($i);
            $labels[] = $date->format('M') . ($i === 0 ? ' (Now)' : '');
            
            $month = $date->format('m');
            $year = $date->format('Y');
            
            $sum = 0;
            if ($type === 'electricity' || $type === 'water') {
                if (\Illuminate\Support\Facades\Schema::hasTable('payments')) {
                    $sum = \App\Models\Payment::whereHas('utilityBill', function($q) use($type) {
                        $q->where('type', $type);
                    })->whereYear('payment_date', $year)->whereMonth('payment_date', $month)->sum('amount_paid');
                }
            } elseif ($type === 'reservation') {
                if (\Illuminate\Support\Facades\Schema::hasTable('reservations')) {
                    $sum = \App\Models\Reservation::whereYear('reservation_date', $year)->whereMonth('reservation_date', $month)->sum('amount');
                }
            } elseif ($type === 'downpayment') {
                if (\Illuminate\Support\Facades\Schema::hasTable('downpayments')) {
                    $sum = \App\Models\Downpayment::whereYear('created_at', $year)->whereMonth('created_at', $month)->sum('amount');
                }
            }
            $data[] = (float)$sum;
            
            if ($type === 'reservation' || $type === 'downpayment') {
                $colors[] = $i === 0 ? '#6366f1' : '#e2e8f0';
            } else {
                $colors[] = $i === 0 ? '#0284c7' : '#38bdf8';
            }
        }
        
        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors,
        ];
    }
}

if (!function_exists('getLotMonthlyChartData')) {
    function getLotMonthlyChartData($lotId, $type) {
        $bills = \App\Models\UtilityBill::where('lot_id', $lotId)
            ->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->limit(12)
            ->get();
            
        $history = [];
        $latestDate = $bills->first() ? $bills->first()->created_at : now();
        
        for ($i = 11; $i >= 0; $i--) {
            $date = $latestDate->copy()->subMonths($i);
            $bill = $bills->first(function($b) use ($date) {
                return $b->created_at->year == $date->year && $b->created_at->month == $date->month;
            });
            $history[] = [
                'label' => $date->format('M Y'),
                'value' => $bill ? (float)$bill->usage_value : 0
            ];
        }
        return $history;
    }
}

if (!function_exists('getLotMonthlyAmountChartData')) {
    function getLotMonthlyAmountChartData($lotId, $type) {
        $bills = \App\Models\UtilityBill::where('lot_id', $lotId)
            ->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->limit(12)
            ->get();
            
        $history = [];
        $latestDate = $bills->first() ? $bills->first()->created_at : now();
        
        for ($i = 11; $i >= 0; $i--) {
            $date = $latestDate->copy()->subMonths($i);
            $bill = $bills->first(function($b) use ($date) {
                return $b->created_at->year == $date->year && $b->created_at->month == $date->month;
            });
            $history[] = [
                'label' => $date->format('M Y'),
                'value' => $bill ? (float)$bill->amount : 0
            ];
        }
        return $history;
    }
}


if (!function_exists('updateResidentBehavior')) {
    function updateResidentBehavior(\App\Models\User $user) {
        $bills = \App\Models\UtilityBill::where('user_id', $user->id)->get();
        if ($bills->count() === 0) {
            $user->payment_behavior = 'Not available yet';
            $user->save();
            return;
        }

        $totalScore = 0;
        foreach ($bills as $bill) {
            if ($bill->status === 'paid') {
                $payments = $bill->payments;
                $paidDate = $payments->count() > 0 ? \Carbon\Carbon::parse($payments->first()->payment_date) : null;
                $dueDate = \Carbon\Carbon::parse($bill->due_date);
                
                if ($paidDate && $paidDate->lt($dueDate)) {
                    $totalScore += 3; // Early
                } else if ($paidDate && $paidDate->isSameDay($dueDate)) {
                    $totalScore += 2; // On-Time
                } else {
                    $totalScore += 1; // Late
                }
            } else {
                if (\Carbon\Carbon::parse($bill->due_date)->isPast()) {
                    $totalScore += 1; // Late
                } else {
                    $totalScore += 2; // On-Time / Pending
                }
            }
        }
        
        if ($bills->count() === 0) {
            return;
        }
        
        $avg = $totalScore / $bills->count();
        if ($avg > 2.5) {
            $behavior = 'Early';
        } else if ($avg >= 1.5) {
            $behavior = 'On-Time';
        } else {
            $behavior = 'Late';
        }
        
        $user->payment_behavior = $behavior;
        $user->save();
    }
}

if (!function_exists('getValidBillingCycles')) {
    function getValidBillingCycles($type) {
        if (!\Illuminate\Support\Facades\Schema::hasTable('utility_bills')) return [];
        $dates = \App\Models\UtilityBill::where('type', $type)
            ->select('created_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($bill) {
                $dt = \Carbon\Carbon::parse($bill->created_at);
                return [
                    'cycle' => $dt->format('Y-m'),
                    'label' => $dt->format('F Y'),
                ];
            })
            ->unique('cycle')
            ->values();
        if ($dates->isEmpty()) {
            return [];
        }
        return $dates->toArray();
    }
}
if (!function_exists('getElectricalBills')) {
    function getElectricalBills($cycle = null, $includeHistory = true) {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                // Check if any electricity bills exist at all
                if (\App\Models\UtilityBill::where('type', 'electricity')->count() === 0) {
                    return [];
                }
                
                $residents = \App\Models\User::role('Resident')->with(['lots', 'utilityBills' => function($q) {
                    $q->where('type', 'electricity')->orderBy('created_at', 'desc')->with('payments');
                }])->get();
                
                return $residents->map(function ($resident) use ($cycle, $includeHistory) {
                    $lot = $resident->lots->first();
                    
                    if ($cycle) {
                        $dt = \Carbon\Carbon::createFromFormat('Y-m', $cycle);
                        $bill = $resident->utilityBills->first(function($b) use ($dt) {
                            return $b->created_at->year == $dt->year && $b->created_at->month == $dt->month;
                        });
                    } else {
                        $bill = $resident->utilityBills->first();
                    }
                    
                    $allPayments = collect();
                    foreach ($resident->utilityBills as $ub) {
                        if ($ub->type === 'electricity') {
                            foreach ($ub->payments as $p) {
                                $allPayments->push([
                                    'month' => \Carbon\Carbon::parse($p->payment_date)->format('M Y'),
                                    'amount' => $p->amount_paid,
                                    'status' => 'Paid',
                                    'date' => \Carbon\Carbon::parse($p->payment_date)->format('M d, Y h:i A'),
                                    'trn' => $p->trn,
                                    'method' => $p->method,
                                    'timestamp' => \Carbon\Carbon::parse($p->payment_date)->timestamp,
                                ]);
                            }
                        }
                    }
                    
                    $payments = $allPayments->sortByDesc('timestamp')->values()->toArray();
                    
                    $billIndex = $bill ? $resident->utilityBills->search(fn($b) => $b->id === $bill->id) : false;
                    $previousBill = $billIndex !== false ? $resident->utilityBills->get($billIndex + 1) : null;

                    return [
                        'id' => $bill ? substr($bill->id, 0, 8) : 'NEW-' . $resident->id,
                        'db_id' => $bill ? $bill->id : null,
                        'lot' => $lot ? 'B' . $lot->block . ' L' . $lot->lot_number : 'N/A',
                        'block' => $lot ? $lot->block : 'N/A',
                        'resident' => $resident->name,
                        'provider_managed' => $lot ? $lot->provider_managed : false,
                        'amount' => $bill ? ($bill->amount + $bill->previous_balance + (($bill->is_at_risk && $bill->status !== 'Paid') ? (($bill->amount + $bill->previous_balance) * 0.05) : 0)) : 0,
                        'usage' => ($bill ? $bill->usage_value : 0) . ' kWh',
                        'prev_reading' => $bill ? $bill->previous_reading : 0,
                        'prev_reading_date' => $previousBill ? \Carbon\Carbon::parse($previousBill->created_at)->format('M d, Y') : 'N/A',
                        'curr_reading' => $bill ? $bill->current_reading : 0,
                        'curr_reading_date' => ($bill && $bill->created_at) ? \Carbon\Carbon::parse($bill->created_at)->format('M d, Y') : 'N/A',
                        'previous_balance' => $bill ? (float)$bill->previous_balance : 0,
                        'total_paid' => $bill ? (float)$bill->payments->sum('amount_paid') : 0,
                        'usage_kwh' => $bill ? $bill->usage_value : 0,
                        'rate' => \App\Models\Setting::find('elec_rate')?->value ?? 10,
                        'base_amount' => $bill ? $bill->amount : 0,
                        'status' => $bill ? $bill->status : 'unpaid',
                        'due' => ($bill && $bill->due_date) ? \Carbon\Carbon::parse($bill->due_date)->format('Y-m-d') : 'N/A',
                        'issued_date' => ($bill && $bill->created_at) ? \Carbon\Carbon::parse($bill->created_at)->format('Y-m-d') : 'N/A',
                        'paid_date' => count($payments) > 0 ? $payments[0]['date'] : null,
                        'method' => count($payments) > 0 ? ($payments[0]['method'] ?? 'Office') : null,
                        'usage_history' => ($lot && $includeHistory) ? getLotMonthlyChartData($lot->id, 'electricity') : [],
                        'amount_history' => ($lot && $includeHistory) ? getLotMonthlyAmountChartData($lot->id, 'electricity') : [],
                        'at_risk' => $bill ? ($bill->is_at_risk && $bill->status !== 'Paid') : false,
                        'payment_history' => $payments,
                        'payment_behavior' => $resident->payment_behavior,
                        'audit_log' => [['action' => 'Statement Generated', 'date' => \Carbon\Carbon::now()->format('Y-m-d h:i A'), 'user' => 'System']],
                    ];
                })->toArray();
            }
        } catch (\Exception $e) {} 
        return [];
    }
}

if (!function_exists('getWaterBills')) {
    function getWaterBills($cycle = null, $includeHistory = true) {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                // Check if any water bills exist at all
                if (\App\Models\UtilityBill::where('type', 'water')->count() === 0) {
                    return [];
                }

                $residents = \App\Models\User::role('Resident')->with(['lots', 'utilityBills' => function($q) {
                    $q->where('type', 'water')->orderBy('created_at', 'desc')->with('payments');
                }])->get();
                
                return $residents->map(function ($resident) use ($cycle, $includeHistory) {
                    $lot = $resident->lots->first();
                    
                    if ($cycle) {
                        $dt = \Carbon\Carbon::createFromFormat('Y-m', $cycle);
                        $bill = $resident->utilityBills->first(function($b) use ($dt) {
                            return $b->created_at->year == $dt->year && $b->created_at->month == $dt->month;
                        });
                    } else {
                        $bill = $resident->utilityBills->first();
                    }
                    
                    $allPayments = collect();
                    foreach ($resident->utilityBills as $ub) {
                        if ($ub->type === 'water') {
                            foreach ($ub->payments as $p) {
                                $allPayments->push([
                                    'month' => \Carbon\Carbon::parse($p->payment_date)->format('M Y'),
                                    'amount' => $p->amount_paid,
                                    'status' => 'Paid',
                                    'date' => \Carbon\Carbon::parse($p->payment_date)->format('M d, Y h:i A'),
                                    'trn' => $p->trn,
                                    'method' => $p->method,
                                    'timestamp' => \Carbon\Carbon::parse($p->payment_date)->timestamp,
                                ]);
                            }
                        }
                    }
                    
                    $payments = $allPayments->sortByDesc('timestamp')->values()->toArray();
                    
                    $minWaterM3Setting = \App\Models\Setting::find('water_min_m3');
                    $minWaterM3 = $minWaterM3Setting ? (float)$minWaterM3Setting->value : 10;
                    
                    $minWaterRateSetting = \App\Models\Setting::find('water_min_rate');
                    $minWaterRate = $minWaterRateSetting ? (float)$minWaterRateSetting->value : 250;
                    
                    $currentRateSetting = \App\Models\Setting::find('water_rate');
                    $currentRate = $currentRateSetting ? (float)$currentRateSetting->value : 30;
                    
                    $billIndex = $bill ? $resident->utilityBills->search(fn($b) => $b->id === $bill->id) : false;
                    $previousBill = $billIndex !== false ? $resident->utilityBills->get($billIndex + 1) : null;

                    return [
                        'id' => $bill ? substr($bill->id, 0, 8) : 'NEW-' . $resident->id,
                        'db_id' => $bill ? $bill->id : null,
                        'lot' => $lot ? 'B' . $lot->block . ' L' . $lot->lot_number : 'N/A',
                        'block' => $lot ? $lot->block : 'N/A',
                        'resident' => $resident->name,
                        'amount' => $bill ? ($bill->amount + $bill->previous_balance + ($bill->is_at_risk ? (($bill->amount + $bill->previous_balance) * 0.05) : 0)) : 0,
                        'usage' => ($bill ? $bill->usage_value : 0) . ' m³',
                        'usage_m3' => $bill ? $bill->usage_value : 0,
                        'prev_reading' => $bill ? $bill->previous_reading : 0,
                        'prev_reading_date' => $previousBill ? \Carbon\Carbon::parse($previousBill->created_at)->format('M d, Y') : 'N/A',
                        'curr_reading' => $bill ? $bill->current_reading : 0,
                        'curr_reading_date' => ($bill && $bill->created_at) ? \Carbon\Carbon::parse($bill->created_at)->format('M d, Y') : 'N/A',
                        'previous_balance' => $bill ? (float)$bill->previous_balance : 0,
                        'total_paid' => $bill ? (float)$bill->payments->sum('amount_paid') : 0,
                        'usage_cbm' => $bill ? $bill->usage_value : 0,
                        'min_m3' => $minWaterM3,
                        'min_rate' => $minWaterRate,
                        'excess_rate' => $currentRate,
                        'base_amount' => $bill ? $bill->amount : 0,
                        'status' => $bill ? $bill->status : 'unpaid',
                        'due' => ($bill && $bill->due_date) ? \Carbon\Carbon::parse($bill->due_date)->format('Y-m-d') : 'N/A',
                        'issued_date' => ($bill && $bill->created_at) ? \Carbon\Carbon::parse($bill->created_at)->format('Y-m-d') : 'N/A',
                        'paid_date' => count($payments) > 0 ? $payments[0]['date'] : null,
                        'method' => count($payments) > 0 ? ($payments[0]['method'] ?? 'Office') : null,
                        'usage_history' => ($lot && $includeHistory) ? getLotMonthlyChartData($lot->id, 'water') : [],
                        'amount_history' => ($lot && $includeHistory) ? getLotMonthlyAmountChartData($lot->id, 'water') : [],
                        'at_risk' => $bill ? ($bill->is_at_risk && $bill->status !== 'Paid') : false,
                        'payment_history' => $payments,
                        'payment_behavior' => $resident->payment_behavior,
                        'audit_log' => [['action' => 'Statement Generated', 'date' => \Carbon\Carbon::now()->format('Y-m-d h:i A'), 'user' => 'System']],
                    ];
                })->toArray();
            }
        } catch (\Exception $e) {} 
        return [];
    }
}

if (!function_exists('calculateBillingStats')) {
    function calculateBillingStats($type) {
        $stats = [
            '2025' => array_fill_keys(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'], 0),
            '2026' => array_fill_keys(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'], 0),
            'today_paid' => 0,
            'percentages' => [
                'Early Payers' => 0,
                'On-Time' => 0,
                'Late / At Risk' => 0
            ]
        ];

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('utility_bills')) {
                $bills = \App\Models\UtilityBill::where('type', $type)->with('payments')->get();
                foreach ($bills as $b) {
                    foreach ($b->payments as $p) {
                        $dt = \Carbon\Carbon::parse($p->payment_date);
                        $year = $dt->format('Y');
                        $month = $dt->format('M');
                        if (isset($stats[$year][$month])) {
                            $stats[$year][$month] += $p->amount_paid;
                        }
                        if ($dt->isToday()) {
                            $stats['today_paid'] += $p->amount_paid;
                        }
                    }
                }

                $users = \App\Models\User::role('Resident')->get();
                $total = $users->count();
                if ($total > 0) {
                    $early = $users->where('payment_behavior', 'Early Payer')->count();
                    $onTime = $users->where('payment_behavior', 'On-Time')->count() + $users->where('payment_behavior', 'On-Time Payer')->count();
                    $late = $users->whereIn('payment_behavior', ['Late Payer', 'At Risk', 'Late'])->count();
                    
                    $totalBehavior = $early + $onTime + $late;
                    if ($totalBehavior > 0) {
                        $stats['percentages'] = [
                            'Early Payers' => round(($early / $totalBehavior) * 100),
                            'On-Time' => round(($onTime / $totalBehavior) * 100),
                            'Late / At Risk' => round(($late / $totalBehavior) * 100)
                        ];
                    }
                }
            }
        } catch (\Exception $e) {}
        
        return $stats;
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
                    $histories = \Illuminate\Support\Facades\DB::table('downpayment_histories')->where('downpayment_id', $dp->id)->orderBy('created_at', 'desc')->get();
                    
                    $historyData = $histories->map(function($h) {
                        return [
                            'trn' => $h->trn,
                            'month' => \Carbon\Carbon::parse($h->payment_date)->format('M Y'),
                            'amount' => (float)$h->amount,
                            'status' => $h->status,
                            'date' => \Carbon\Carbon::parse($h->payment_date)->format('y-m-d')
                        ];
                    })->toArray();
                    
                    $amortization = $dp->monthly_amortization ?? 15000;
                    $months_to_pay = $dp->months_to_pay ?? 24;
                    
                    $buyerName = $res->first_name && $res->last_name ? trim($res->first_name . ' ' . $res->last_name) : ($res->notes ?? 'Guest');

                    return [
                        'id' => substr($dp->id, 0, 8),
                        'buyer' => $buyerName,
                        'block' => $res->lot->block ?? 'N/A',
                        'lot' => $res->lot->lot_number ?? 'N/A',
                        'total_dp' => (float)($dp->amount + $dp->balance),
                        'paid_amount' => (float)$dp->amount,
                        'monthly_amortization' => (float)$amortization,
                        'months_paid' => $histories->count(),
                        'total_months' => $months_to_pay,
                        'status' => $dp->status,
                        'next_due' => $dp->due_date ? \Carbon\Carbon::parse($dp->due_date)->format('Y-m-d') : 'N/A',
                        'last_payment' => $dp->updated_at ? \Carbon\Carbon::parse($dp->updated_at)->format('Y-m-d') : 'N/A',
                        'contract_date' => $dp->contract_date ? \Carbon\Carbon::parse($dp->contract_date)->format('Y-m-d') : 'N/A',
                        'history' => $historyData
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
