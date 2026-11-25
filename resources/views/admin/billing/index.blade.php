@extends('layouts.admin')

@section('title', 'Electricity Billing')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}?v={{ filemtime(public_path('css/admin-finance.css')) }}">

@php
    $latestBill = !empty($bills) ? (collect($bills)->first(fn($b) => !empty($b['db_id'])) ?? collect($bills)->first()) : null;
    $activePrevDate = $latestBill['prev_reading_date'] ?? 'N/A';
    $activePrevDateRaw = $latestBill['prev_reading_date_raw'] ?? '';
    $activeCurrDate = $latestBill['curr_reading_date'] ?? 'N/A';
    $activeCurrDateRaw = $latestBill['curr_reading_date_raw'] ?? '';
    $activeDueRaw = $latestBill['due'] ?? '';
    $activeDueDate = ($activeDueRaw && $activeDueRaw !== 'N/A') ? \Carbon\Carbon::parse($activeDueRaw)->format('M d, Y') : 'N/A';

    $currentCycleKey = request('cycle');
    $currentCycleObj = collect($validCycles)->firstWhere('cycle', $currentCycleKey) ?? (collect($validCycles)->first() ?? null);
    $activeCycleLabel = $currentCycleObj['label'] ?? ($currentCycleKey ?: 'Current Active Cycle');

    $elecRate = (float)(\App\Models\Setting::find('elec_rate')?->value ?? 10);
    $elecPenalty = (float)(\App\Models\Setting::find('electricity_penalty')?->value ?? 5);

    $totalBills = count($bills);
    $paidBills  = collect($bills)->where('status', 'paid')->count();
    $unpaidBills = $totalBills - $paidBills;
    $totalBilled   = collect($bills)->sum('amount');
    $totalCollected = collect($bills)->sum(fn($b) => $b['status'] === 'paid' ? max((float)$b['total_paid'], (float)$b['amount']) : (float)$b['total_paid']);
    $totalBalanceRemaining = max(0, $totalBilled - $totalCollected);
    $cerPct = $totalBilled > 0 ? min(100, max(0, round(($totalCollected / $totalBilled) * 100))) : 0;
    $circumference = 2 * M_PI * 52; // radius 52 on 120px svg
    $cerStroke = ($cerPct / 100) * $circumference;

    // Simulated master meter: ~8% more than billed
    $masterUsageKwh  = collect($bills)->sum('usage_kwh') * 1.08;
    $billedUsageKwh  = collect($bills)->sum('usage_kwh');
    $systemLossPct   = $masterUsageKwh > 0 ? round((($masterUsageKwh - $billedUsageKwh) / $masterUsageKwh) * 100, 1) : 8;
    $billedFillPct   = 100 - $systemLossPct;
@endphp

<div class="bill-container fade-in">
    <div class="bill-header">
        <div class="bill-title">
            <h1 style="font-size: 24px; font-weight: 800; color: #0f172a; margin: 0;">Electricity Management & Analytics</h1>
            <p style="font-size: 13px; color: #64748b; margin-top: 4px;">12-month consumption analysis, payment channel management, and predictive delinquency tracking.</p>
        </div>
        <div class="bill-actions" style="display: flex; gap: 8px;">
            <button type="button" class="btn btn-outline" style="background: #fff; border-color: #0284c7; color: #0284c7; font-weight: 700; display: inline-flex; align-items: center; padding: 8px 16px;" onclick="openBillingReportModal()">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 6px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Generate Report
            </button>
            <button class="btn btn-outline" style="border-color: var(--bill-primary); color: var(--bill-primary); padding: 8px 16px; font-weight: 700;" onclick="resetBillingCycle()">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 6px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                New Cycle
            </button>
        </div>
    </div>

    <!-- Structured Active Cycle & Settings Card -->
    <div style="background: #ffffff; border: 1px solid var(--bill-border); border-radius: 14px; padding: 14px 18px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                    ⚡
                </div>
                <div>
                    <span style="font-size: 14px; font-weight: 800; color: #0f172a;">Active Cycle Parameters</span>
                    <span style="font-size: 11px; color: #64748b; margin-left: 6px;">Current reading window & applied utility rates</span>
                </div>
                <span style="background: #fef3c7; color: #b45309; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 6px; margin-left: 4px;">{{ $activeCycleLabel }}</span>
            </div>
            <button class="btn btn-outline" style="border-color: #d97706; color: #d97706; font-size: 11px; padding: 5px 12px; font-weight: 700; border-radius: 6px;" onclick="openAdjustCycleModal()">
                ⚙️ Adjust Current Cycle
            </button>
        </div>

        <!-- Structured 4-Column Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
                <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; display: flex; align-items: center; gap: 5px;">
                    <i class="fas fa-calendar-alt" style="color: #d97706;"></i> Reading Window
                </div>
                <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $activePrevDate }} – {{ $activeCurrDate }}">
                    {{ $activePrevDate }} &rarr; {{ $activeCurrDate }}
                </div>
                <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">Consumption period</div>
            </div>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
                <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; display: flex; align-items: center; gap: 5px;">
                    <i class="fas fa-clock" style="color: #64748b;"></i> Due Date
                </div>
                <div style="font-size: 14px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                    {{ $activeDueDate }}
                </div>
                <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">Before late penalty</div>
            </div>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
                <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; display: flex; align-items: center; gap: 5px;">
                    <i class="fas fa-bolt" style="color: #d97706;"></i> Base Rate
                </div>
                <div style="font-size: 14px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                    ₱{{ number_format($elecRate, 2) }} <span style="font-size: 11px; font-weight: 600; color: #64748b;">/ kWh</span>
                </div>
                <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">Standard consumption rate</div>
            </div>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
                <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; display: flex; align-items: center; gap: 5px;">
                    <i class="fas fa-exclamation-triangle" style="color: #64748b;"></i> Late Penalty
                </div>
                <div style="font-size: 14px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                    {{ $elecPenalty }}%
                </div>
                <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">Past due surcharge</div>
            </div>
        </div>
    </div>

    <!-- Collection Summary Dashboard -->
    <div style="background: #fff; border: 1px solid var(--bill-border); border-radius: 16px; padding: 16px 20px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;">Ledger Performance Summary</h2>
                <p style="font-size: 11px; color: #64748b; margin: 2px 0 0 0;">Real-time collection status for the active cycle</p>
            </div>
            <select id="summaryMonth" class="filter-select" style="font-weight: 600; font-size: 12px; padding: 6px 12px; color: var(--bill-primary); border-color: #cbd5e1;" onchange="fetchCycle(this.value)">
                @if(empty($validCycles))
                    <option value="" disabled selected>No Records Available</option>
                @else
                    @foreach($validCycles as $c)
                        <option value="{{ $c['cycle'] }}" {{ request('cycle') == $c['cycle'] ? 'selected' : '' }}>
                            {{ $c['label'] }}
                        </option>
                    @endforeach
                @endif
            </select>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px;">
            <div style="background: #f8fafc; padding: 12px 14px; border-radius: 10px; border-left: 4px solid var(--bill-primary);">
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;">Total Collection</div>
                <div id="sumCollection" style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 2px;">₱{{ number_format($totalCollected, 2) }}</div>
            </div>
            <div style="background: #f8fafc; padding: 12px 14px; border-radius: 10px; border-left: 4px solid #f59e0b;">
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;">Total Balance to Collect</div>
                <div id="sumBalanceToCollect" style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 2px;">₱{{ number_format($totalBilled, 2) }}</div>
            </div>
            <div style="background: #f8fafc; padding: 12px 14px; border-radius: 10px; border-left: 4px solid #10b981;">
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;">Residents Paid</div>
                <div id="sumPaid" style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 2px;">{{ $paidBills }}</div>
            </div>
            <div style="background: #f8fafc; padding: 12px 14px; border-radius: 10px; border-left: 4px solid #f59e0b;">
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;">Unpaid / Pending</div>
                <div id="sumUnpaid" style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 2px;">{{ $unpaidBills }}</div>
            </div>
            <div style="background: #f8fafc; padding: 12px 14px; border-radius: 10px; border-left: 4px solid #ef4444;">
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;">Past Due</div>
                <div id="sumPastDue" style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 2px;">0</div>
            </div>
        </div>
    </div>

    <!-- Monthly Electricity Sales & Collection Trend Bar Graph -->
    @php
        $chartData = getMonthlyChartData('electricity');
        $twelveMonthTotal = array_sum($chartData['data'] ?? []);
    @endphp
    <div class="revenue-chart-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
            <div>
                <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;">Monthly Electricity Revenue Trends</h3>
                <p style="font-size: 11px; color: #64748b; margin: 2px 0 0 0;">12-month collection performance</p>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; color: #64748b;">
                    <span style="width: 10px; height: 10px; border-radius: 3px; background: #4f46e5;"></span> Current Month
                </span>
                <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; color: #64748b;">
                    <span style="width: 10px; height: 10px; border-radius: 3px; background: #38bdf8;"></span> Prior Months
                </span>
                <span style="background: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 9999px;">
                    12-Mo Total: ₱{{ number_format($twelveMonthTotal, 2) }}
                </span>
            </div>
        </div>
        <div style="height: 220px; position: relative;">
            <canvas id="electricitySalesChart"></canvas>
        </div>
    </div>

    <!-- Analytics Dashboard -->
    <div class="analytics-grid cols-3">

        <!-- Card 1: Paid Status Breakdown -->
        @php
            $paidAccounts = collect($bills)->where('status', 'paid')->count();
            $partialAccounts = collect($bills)->where('status', 'partially_paid')->count();
            $unpaidAccounts = max(0, $totalBills - $paidAccounts - $partialAccounts);
            $paidPct = $totalBills > 0 ? round(($paidAccounts / $totalBills) * 100) : 0;
            $partialPct = $totalBills > 0 ? round(($partialAccounts / $totalBills) * 100) : 0;
            $unpaidPct = $totalBills > 0 ? max(0, 100 - $paidPct - $partialPct) : 0;
            $partialOffset = $paidPct;
            $unpaidOffset = $paidPct + $partialPct;
        @endphp
        <div class="analytic-card">
            <div class="card-title">
                <span>Payment Status (Paid)</span>
                <span class="card-badge">Current Cycle</span>
            </div>
            <div class="donut-chart-container">
                <div class="donut-ring-wrap">
                    <svg viewBox="0 0 42 42" class="donut-svg">
                        <circle class="donut-segment segment-track" cx="21" cy="21" r="15.915" stroke-dasharray="100 0"></circle>
                        @if($totalBills > 0)
                            <circle class="donut-segment segment-paid" cx="21" cy="21" r="15.915" stroke-dasharray="{{ $paidPct }} {{ 100 - $paidPct }}" stroke-dashoffset="0"></circle>
                            <circle class="donut-segment segment-partial" cx="21" cy="21" r="15.915" stroke-dasharray="{{ $partialPct }} {{ 100 - $partialPct }}" stroke-dashoffset="-{{ $partialOffset }}"></circle>
                            <circle class="donut-segment segment-unpaid" cx="21" cy="21" r="15.915" stroke-dasharray="{{ $unpaidPct }} {{ 100 - $unpaidPct }}" stroke-dashoffset="-{{ $unpaidOffset }}"></circle>
                        @endif
                    </svg>
                    <div class="donut-center-label">
                        <div class="donut-center-pct" id="paidDonutPct">{{ $paidPct }}%</div>
                        <span class="donut-center-sub">Paid</span>
                        <span class="donut-center-count" id="paidDonutSub">{{ $paidAccounts }} / {{ $totalBills }}</span>
                    </div>
                </div>
                <div class="donut-legend" id="paidDonutLegend">
                    <div class="legend-item">
                        <span class="legend-label"><span class="dot" style="background: #10b981;"></span> Paid</span>
                        <span><span class="legend-count">{{ $paidAccounts }} accts</span><strong>{{ $paidPct }}%</strong></span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-label"><span class="dot" style="background: #3b82f6;"></span> Partially Paid</span>
                        <span><span class="legend-count">{{ $partialAccounts }} accts</span><strong>{{ $partialPct }}%</strong></span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-label"><span class="dot" style="background: #f59e0b;"></span> Unpaid</span>
                        <span><span class="legend-count">{{ $unpaidAccounts }} accts</span><strong>{{ $unpaidPct }}%</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Collection Efficiency -->
        <div class="analytic-card" style="display: flex; flex-direction: column;">
            <div class="card-title">
                <span>Collection Efficiency</span>
                <span class="card-badge">Revenue Ratio</span>
            </div>
            <div class="donut-chart-container">
                <div class="donut-ring-wrap">
                    <svg viewBox="0 0 42 42" class="donut-svg">
                        <circle class="donut-segment segment-track" cx="21" cy="21" r="15.915" stroke-dasharray="100 0"></circle>
                        <circle id="cerGaugeFill" class="donut-segment segment-paid" cx="21" cy="21" r="15.915"
                            stroke-dasharray="{{ $cerPct }} {{ 100 - $cerPct }}"
                            stroke-dashoffset="0"
                            style="{{ $cerPct <= 0 ? 'stroke-opacity: 0;' : '' }}"></circle>
                    </svg>
                    <div class="donut-center-label">
                        <div class="donut-center-pct" id="cerGaugePct">{{ $cerPct }}%</div>
                    </div>
                </div>
                <div class="cer-meta">
                    <div class="cer-meta-row">
                        <span>Total Billed</span>
                        <strong id="cerTotalBilled">₱{{ number_format($totalBilled, 2) }}</strong>
                    </div>
                    <div class="cer-meta-row">
                        <span>Total Collected</span>
                        <strong id="cerTotalCollected" style="color: var(--bill-success);">₱{{ number_format($totalCollected, 2) }}</strong>
                    </div>
                    <div class="cer-meta-row">
                        <span>Outstanding</span>
                        <strong id="cerOutstanding" style="color: var(--bill-danger);">₱{{ number_format($totalBalanceRemaining, 2) }}</strong>
                    </div>
                    <div class="cer-meta-row">
                        <span>Accounts Paid</span>
                        <strong id="cerAccountsPaid">{{ $paidBills }} / {{ $totalBills }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Recent Payers (Paid) -->
        @php
            $recentElecPayers = getRecentPayers('electricity', 8);
        @endphp
        <div class="analytic-card" style="display: flex; flex-direction: column;">
            <div class="card-title">
                <span>Recent Payers</span>
                <span class="card-badge" style="background: #ecfdf5; color: #059669;">Latest Paid</span>
            </div>
            @if(empty($recentElecPayers))
                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 32px 16px; color: #94a3b8; text-align: center;">
                    <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom: 8px; opacity: 0.7;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <div style="font-size: 13px; font-weight: 700; color: #64748b;">No Recent Payments Yet</div>
                    <div style="font-size: 11px; margin-top: 2px;">Paid accounts will appear here automatically.</div>
                </div>
            @else
                <div style="display: flex; flex-direction: column; gap: 8px; max-height: 290px; overflow-y: auto; padding-right: 4px;">
                    @foreach($recentElecPayers as $payer)
                        @php
                            $initials = collect(explode(' ', trim($payer['name'])))->filter()->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->implode('');
                        @endphp
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 9px 11px; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px;">
                            <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                                <div style="width: 32px; height: 32px; border-radius: 9px; background: #dcfce7; color: #059669; font-size: 11px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    {{ $initials ?: 'R' }}
                                </div>
                                <div style="min-width: 0;">
                                    <div style="font-size: 12.5px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $payer['name'] }}</div>
                                    <div style="font-size: 11px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $payer['lot'] }} &bull; {{ $payer['date'] }}</div>
                                </div>
                            </div>
                            <div style="text-align: right; flex-shrink: 0;">
                                <div style="font-size: 12.5px; font-weight: 800; color: #059669;">₱{{ number_format($payer['amount'], 2) }}</div>
                                <div style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">{{ $payer['method'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-group">
            <span class="filter-label">Search</span>
            <input type="text" id="billingSearch" class="filter-select" placeholder="Resident or Lot..." style="min-width: 200px;">
        </div>
        <div class="filter-group">
            <span class="filter-label">Payment Status</span>
            <select id="statusFilter" class="filter-select">
                <option value="all">All Statuses</option>
                <option value="paid">Paid</option>
                <option value="partially_paid">Partially Paid</option>
                <option value="unpaid">Unpaid</option>
            </select>
        </div>
        <div class="filter-group">
            <span class="filter-label">Block</span>
            <select id="blockFilter" class="filter-select">
                <option value="all">All Blocks</option>
                @php
                    $uniqueBlocks = collect($bills)->pluck('block')->filter(function($b) { return $b !== 'N/A'; })->unique()->sort(fn($a, $b) => strnatcasecmp((string)$a, (string)$b));
                @endphp
                @foreach($uniqueBlocks as $b)
                    <option value="{{ $b }}">Block {{ $b }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <span class="filter-label">Payment Behavior</span>
            <select id="behaviorFilter" class="filter-select">
                <option value="all">All Behaviors</option>
                <option value="Early">Early</option>
                <option value="On-Time">On-Time</option>
                <option value="Late">Late</option>
                <option value="Not available yet">N/A</option>
            </select>
        </div>
        <div class="filter-group">
            <span class="filter-label">Connection</span>
            <select id="connectionFilter" class="filter-select">
                <option value="internal">Subdivision Grid</option>
                <option value="casureco">CASURECO (Archived)</option>
                <option value="all">All</option>
            </select>
        </div>
        <div style="margin-left: auto;">
            <button class="btn btn-primary" onclick="applyFilters()">Apply Filters</button>
        </div>
    </div>

    <!-- Predictive Billing Ledger -->
    <div class="analytic-card">
        <div class="card-title">
            <span>Electricity Billing Ledger</span>
        </div>
        @if(count($bills) == 0)
            <div style="text-align: center; padding: 60px 20px; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1; margin-top: 20px;">
                <div style="font-size: 48px; margin-bottom: 16px;">⚡</div>
                <h3 style="font-size: 20px; color: #0f172a; margin-bottom: 8px;">The Ledger is Blank</h3>
                <p style="color: #64748b; font-size: 14px;">The system is new or no cycle has been generated yet.<br>Please click <strong>"New Cycle"</strong> at the top to initialize the ledger and record readings.</p>
            </div>
        @else
        <div class="bill-table-container">
            <table class="bill-table" id="billingTable">
                <thead>
                    <tr>
                        <th data-sort="text">Resident / Lot</th>
                        <th data-sort="number">Amount</th>
                        <th data-sort="text">Payment Behavior</th>
                        <th data-sort="text">Method</th>
                        <th data-sort="text">Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bills as $bill)
                    <tr class="bill-row" data-resident="{{ strtolower($bill['resident']) }}" data-status="{{ $bill['status'] }}" data-behavior="{{ $bill['payment_behavior'] ?? 'Not available yet' }}" data-block="{{ $bill['block'] }}" data-connection="{{ $bill['provider_managed'] ? 'casureco' : 'internal' }}">
                        <td>
                            <div style="font-weight: 700;">{{ $bill['resident'] }}</div>
                            <div style="font-size: 12px; color: #64748b;">
                                {{ $bill['lot'] }}
                                @if($bill['provider_managed'])
                                    <span style="background: #e2e8f0; color: #475569; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 800; margin-left: 4px;">CASURECO</span>
                                @endif
                            </div>
                        </td>
                        <!-- Dynamic Amounts for JS recalculation -->
                        <td class="dynamic-amount-cell" 
                            data-usage-kwh="{{ $bill['usage_kwh'] }}" 
                            data-previous-balance="{{ $bill['previous_balance'] }}"
                            data-total-paid="{{ $bill['total_paid'] ?? 0 }}"
                            data-at-risk="{{ $bill['at_risk'] ? 'true' : 'false' }}" 
                            data-status="{{ $bill['status'] }}"
                            style="font-weight: 700;">
                            <div>₱{{ number_format($bill['amount'], 2) }}</div>
                            @if(($bill['total_paid'] ?? 0) > 0 && $bill['status'] !== 'paid')
                                <div style="font-size: 11px; color: #10b981; font-weight: 600; margin-top: 2px;">Paid: ₱{{ number_format($bill['total_paid'], 2) }}</div>
                                <div style="font-size: 11px; color: #d97706; font-weight: 700;">Bal: ₱{{ number_format($bill['remaining_balance'] ?? max(0, $bill['amount'] - $bill['total_paid']), 2) }}</div>
                            @endif
                        </td>
                        <td>
                            @if(($bill['payment_behavior'] ?? 'Not available yet') === 'Early')
                                <span class="trend-chip trend-early">Early</span>
                            @elseif(($bill['payment_behavior'] ?? 'Not available yet') === 'On-Time')
                                <span class="trend-chip trend-early" style="background: var(--bill-primary);">On-Time</span>
                            @elseif(($bill['payment_behavior'] ?? 'Not available yet') === 'Late')
                                <span class="trend-chip trend-late">Late</span>
                            @else
                                <span class="trend-chip" style="background: #e2e8f0; color: #475569;">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($bill['method'])
                                <span class="method-badge method-{{ strtolower($bill['method']) }}">
                                    @if($bill['method'] === 'GCash') 📱 @else 🏢 @endif
                                    {{ $bill['method'] }}
                                </span>
                            @else
                                <span style="font-style: italic; color: #94a3b8; font-size: 12px;">Waiting...</span>
                            @endif
                        </td>
                        <td>
                            @if($bill['status'] === 'paid')
                                <span class="badge badge-success">Paid</span>
                                <div style="font-size: 10px; color: #10b981; margin-top: 4px;">{{ $bill['paid_date'] }}</div>
                            @elseif($bill['status'] === 'partially_paid')
                                <span class="badge" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a;">Partially Paid</span>
                                <div style="font-size: 10px; color: #059669; font-weight: 700; margin-top: 4px;">Paid: ₱{{ number_format($bill['total_paid'] ?? 0, 2) }}</div>
                                <div style="font-size: 10px; color: {{ $bill['at_risk'] ? 'var(--bill-danger)' : '#b45309' }}; margin-top: 2px;">Due: {{ $bill['due'] }}{{ $bill['at_risk'] ? ' (Past Due)' : '' }}</div>
                            @else
                                <span class="badge badge-danger">{{ $bill['at_risk'] ? 'Past Due' : 'Unpaid' }}</span>
                                <div style="font-size: 10px; color: var(--bill-danger); margin-top: 4px;">Due: {{ $bill['due'] }}</div>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button class="btn btn-outline" style="padding: 6px 10px; font-size: 11px;" onclick="viewDetail('{{ $bill['db_id'] ?? $bill['id'] }}')">View</button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('partials.admin-client-pagination', ['prefix' => 'billingTable'])
        @endif
    </div>
    <script id="cycle-bills-json" type="application/json">@json($bills)</script>
</div>

<!-- Full Screen Immersive Modal -->
<div id="billModal" class="bill-modal">
    <div class="bill-modal-content">
        <div class="modal-header">
            <div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span id="modalLot" style="font-size: 12px; font-weight: 700; color: var(--bill-primary); text-transform: uppercase; letter-spacing: 0.1em;">BLOCK 1 LOT 5</span>
                    <span id="modalPaymentBadge" class="badge badge-danger">Unpaid</span>
                </div>
                <h2 id="modalResident" style="font-size: 32px; font-weight: 800; color: #0f172a; margin-top: 8px;">Juan Dela Cruz</h2>
            </div>
            <button class="ann-btn-icon" onclick="closeModal()" style="padding: 12px; background: #f1f5f9;">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div id="modalMainContent">
                <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; margin-bottom: 20px; color: #64748b;">Current Statement Details</h3>
                <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 24px; margin-bottom: 32px; align-items: start;">
                    <!-- Meter Data -->
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                        <h4 style="font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 16px; margin-top: 0; letter-spacing: 0.05em;">Meter Data</h4>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                            <span style="font-size: 13px; color: #475569; font-weight: 600;">Previous Reading</span>
                            <span id="modalPrevReading" style="font-size: 14px; font-weight: 800; color: #0f172a;">0</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 16px;">
                            <span style="font-size: 13px; color: #475569; font-weight: 600;">Current Reading</span>
                            <span id="modalCurrReading" style="font-size: 14px; font-weight: 800; color: #0f172a;">0</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 16px;">
                            <span style="font-size: 13px; color: #475569; font-weight: 600;">Base Rate</span>
                            <span id="modalBaseRate" style="font-size: 14px; font-weight: 800; color: #0f172a;">₱0.00</span>
                        </div>
                        
                        <div style="background: #f8fafc; border-radius: 12px; padding: 16px; text-align: center; border: 1px dashed #cbd5e1;">
                            <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Consumption</span>
                            <div id="modalUsage" style="font-size: 28px; font-weight: 800; color: var(--bill-primary); margin-top: 4px;">150 kWh</div>
                        </div>
                    </div>
                
                    <!-- Financial Breakdown -->
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                        <h4 style="font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 16px; margin-top: 0; letter-spacing: 0.05em;">Financial Breakdown</h4>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
                            <span style="font-size: 14px; font-weight: 700; color: #0f172a;">Current Month Charges <span style="font-size: 11px; font-weight: 600; color: #64748b;">(This Month Only)</span></span>
                            <span id="modalCurrentCharges" style="font-size: 15px; font-weight: 800; color: var(--bill-primary);">₱0.00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span style="font-size: 14px; color: #475569;">Previous Balance (Arrears)</span>
                            <span id="modalPrevBalance" style="font-size: 15px; font-weight: 700; color: #0f172a;">₱0.00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span id="modalAmountBeforeLabel" style="font-size: 14px; color: #475569;">Amount Before Due</span>
                            <span id="modalAmountBefore" style="font-size: 15px; font-weight: 700; color: #0f172a;">₱1,250.50</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span id="modalPenaltyLabel" style="font-size: 14px; color: #475569;">Penalty (5% Late Fee)</span>
                            <span id="modalPenalty" style="font-size: 15px; font-weight: 700; color: #f59e0b;">₱62.52</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                            <span id="modalAmountAfterLabel" style="font-size: 14px; font-weight: 700; color: #0f172a;">Amount After Due</span>
                            <span id="modalAmountAfter" style="font-size: 15px; font-weight: 800; color: #0f172a;">₱1,313.02</span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span style="font-size: 14px; font-weight: 600; color: #059669;">Total Paid</span>
                            <span id="modalTotalPaid" style="font-size: 15px; font-weight: 800; color: #10b981;">₱0.00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 2px dashed #e2e8f0;">
                            <span style="font-size: 14px; font-weight: 800; color: #0f172a;">Remaining Balance Due</span>
                            <span id="modalRemainingBalance" style="font-size: 16px; font-weight: 800; color: #ef4444;">₱0.00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 14px; font-weight: 700; color: #64748b;">Date Issued</span>
                            <span id="modalDateIssued" style="font-size: 14px; font-weight: 700; color: #0f172a;">N/A</span>
                        </div>
                    </div>
                </div>

            </div>

            <div id="modalHistoryContent" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; color: #64748b;">Payment History & TRN Record</h3>
                    <button class="btn btn-outline" style="padding: 4px 12px; font-size: 11px;" onclick="toggleModalView('main')">Back to Summary</button>
                </div>
                <div class="bill-table-container" style="max-height: 300px; overflow-y: auto;">
                    <table class="bill-table">
                        <thead style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th>Transaction (TRN)</th>
                                <th>Month</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Receipt</th>
                            </tr>
                        </thead>
                        <tbody id="modalHistoryTableBody">
                            <!-- History rows injected here -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div style="border-left: 1px solid var(--bill-border); padding-left: 40px;">
                <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; margin-bottom: 24px; color: #64748b;">Quick Actions</h3>
                <div class="modal-actions-list">
                    <div id="modalPaymentAction" style="margin-bottom: 8px;">
                        <!-- Payment button injected here if unpaid -->
                    </div>
                    <button class="btn btn-primary" onclick="toggleModalView('history')">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px;"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        View Payment History
                    </button>
                    <button class="btn btn-outline" onclick="printStatementReceipt()">Print Statement</button>
                    <button id="modalDisconnectBtn" class="btn btn-outline" style="color: #ef4444; border-color: #ef4444; margin-top: 8px;" onclick="disconnectCasureco()">Disconnect (CASURECO)</button>
                    <button id="modalReconnectBtn" class="btn btn-outline" style="color: #10b981; border-color: #10b981; margin-top: 8px; display: none;" onclick="reconnectGrid()">Reconnect to Grid</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Preview Modal -->
<div id="receiptModal" style="display:none; position:fixed; inset:0; z-index:20000; background:rgba(15,23,42,0.75); backdrop-filter:blur(6px); align-items:center; justify-content:center; padding:24px; box-sizing:border-box;">
    <div style="background:#fff; max-width:680px; width:100%; border-radius:16px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.35); overflow:hidden; display:flex; flex-direction:column; height:90vh; max-height:860px;">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc;">
            <span id="receiptModalTitle" style="font-size:14px; font-weight:800; color:#0f172a; text-transform:uppercase; letter-spacing:0.05em;">Statement of Account — Electricity</span>
            <div style="display:flex; gap:10px; align-items:center;">
                <button onclick="printReceiptFrame()" style="background:#047857; color:#fff; border:none; padding:8px 18px; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer;">Print</button>
                <button onclick="closeReceiptModal()" style="background:#f1f5f9; border:none; padding:8px 12px; border-radius:8px; cursor:pointer; font-size:18px; line-height:1; color:#64748b;">&times;</button>
            </div>
        </div>
        <div style="overflow-y:auto; flex:1; padding:0;">
            <iframe id="receiptFrame" style="width:100%; height:100%; min-height:560px; border:none; display:block;"></iframe>
        </div>
    </div>
</div>

<!-- New Cycle Modal -->
<div id="resetCycleModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 3000;">
    <div class="bill-modal-content reset-cycle-modal">
        <div class="reset-cycle-modal-header">
            <div class="reset-cycle-modal-icon">
                <svg width="20" height="20" fill="none" stroke="#0284c7" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            </div>
            <div>
                <h3 id="newCycleModalTitle" style="margin: 0; font-size: 17px; font-weight: 800; color: #0f172a;">Create New Billing Cycle</h3>
                <p id="newCycleModalSubtitle" style="margin: 2px 0 0 0; font-size: 12px; color: #64748b;">Smart date projection with automated arrears calculation</p>
            </div>
        </div>
        <div class="reset-cycle-modal-body">
            <!-- Dynamic Automation & Readiness Card -->
            <div id="cycleAutomationStatusCard" style="display: none; border-radius: 12px; padding: 12px 14px; margin-bottom: 16px; font-size: 12px; line-height: 1.45;"></div>

            <!-- Current Cycle Reference Card -->
            <div style="background: #fef3c7; border: 1px solid #fde68a; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #92400e; letter-spacing: 0.05em;">Current Settings Reference</span>
                    <span style="font-size: 11px; background: #fffbeb; color: #b45309; font-weight: 700; padding: 1px 8px; border-radius: 9999px;">{{ $activeCycleLabel }}</span>
                </div>
                <div style="font-size: 12px; color: #334155; display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                    <div><strong>Period:</strong> {{ $activePrevDate }} &rarr; {{ $activeCurrDate }}</div>
                    <div><strong>Due Date:</strong> <span style="color: #dc2626; font-weight: 600;">{{ $activeDueDate }}</span></div>
                    <div><strong>Base Rate:</strong> ₱{{ number_format($elecRate, 2) }}/kWh</div>
                    <div><strong>Late Penalty:</strong> {{ $elecPenalty }}%</div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 16px;">
                <div style="background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;"><i class="fas fa-calendar-alt" style="margin-right: 6px; color: #0284c7;"></i>Previous Reading Date</label>
                    <input type="date" id="newCyclePrev" onchange="syncCycleDateBounds()" class="filter-select" style="width: 100%; box-sizing: border-box; background: white; border-color: #cbd5e1; cursor: pointer;">
                </div>
                <div style="background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;"><i class="fas fa-calendar-check" style="margin-right: 6px; color: #10b981;"></i>Current Reading Date</label>
                    <input type="date" id="newCycleCurr" onchange="syncCycleDateBounds()" class="filter-select" style="width: 100%; box-sizing: border-box; background: white; border-color: #cbd5e1; cursor: pointer;">
                </div>
                <div style="background: #fef2f2; padding: 12px; border-radius: 12px; border: 1px solid #fecaca;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #991b1b; margin-bottom: 6px;"><i class="fas fa-exclamation-circle" style="margin-right: 6px; color: #ef4444;"></i>Due Date</label>
                    <input type="date" id="newCycleDue" class="filter-select" style="width: 100%; box-sizing: border-box; background: white; border-color: #fca5a5; cursor: pointer;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 8px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 6px;">Base Rate (₱)</label>
                    <input type="number" id="newCycleRate" class="filter-select" value="{{ $elecRate }}" style="width: 100%; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 6px;">Penalty Past Due (%)</label>
                    <input type="number" id="newCyclePenalty" class="filter-select" value="{{ $elecPenalty }}" style="width: 100%; box-sizing: border-box;">
                </div>
            </div>
            <p id="cycleDateHint" style="font-size: 11px; color: #64748b; line-height: 1.45; margin: 0 0 4px;"></p>
        </div>

        <div class="reset-cycle-modal-footer">
            <button class="btn btn-outline" style="justify-content: center; padding: 12px;" onclick="closeResetCycleModal()">Cancel</button>
            <button id="btnCreateNewCycle" class="btn btn-primary" style="justify-content: center; padding: 12px;" onclick="executeResetCycle()">Create Cycle</button>
        </div>
    </div>
</div>

<!-- Adjust Current Cycle Modal -->
<div id="adjustCycleModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 3000;">
    <div class="bill-modal-content reset-cycle-modal" style="max-width: 500px;">
        <div class="reset-cycle-modal-header" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 14px;">
            <div class="reset-cycle-modal-icon" style="background: #fef3c7; color: #d97706;">
                ⚙️
            </div>
            <div>
                <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: #0f172a;">Adjust Current Cycle Parameters</h3>
                <p style="margin: 2px 0 0 0; font-size: 12px; color: #64748b;">Update reading window, due date, and rates for {{ $activeCycleLabel }}</p>
            </div>
        </div>
        <div class="reset-cycle-modal-body" style="padding-top: 16px;">
            <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; font-size: 12px; color: #92400e; display: flex; align-items: center; gap: 8px;">
                <span>ℹ️</span>
                <span>Modifying these values will update the <strong>{{ $activeCycleLabel }}</strong> cycle and recalculate pending bills.</span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
                <div style="background: #f8fafc; padding: 10px 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px; text-transform: uppercase;"><i class="fas fa-calendar-alt" style="margin-right: 6px; color: #d97706;"></i>Previous Reading Date</label>
                    <input type="date" id="adjCyclePrev" class="filter-select" value="{{ $activePrevDateRaw }}" style="width: 100%; box-sizing: border-box; background: white; border-color: #cbd5e1; cursor: pointer;">
                </div>
                <div style="background: #f8fafc; padding: 10px 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px; text-transform: uppercase;"><i class="fas fa-calendar-check" style="margin-right: 6px; color: #10b981;"></i>Current Reading Date</label>
                    <input type="date" id="adjCycleCurr" class="filter-select" value="{{ $activeCurrDateRaw }}" style="width: 100%; box-sizing: border-box; background: white; border-color: #cbd5e1; cursor: pointer;">
                </div>
                <div style="background: #fef2f2; padding: 10px 12px; border-radius: 10px; border: 1px solid #fecaca;">
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #991b1b; margin-bottom: 4px; text-transform: uppercase;"><i class="fas fa-exclamation-circle" style="margin-right: 6px; color: #ef4444;"></i>Due Date</label>
                    <input type="date" id="adjCycleDue" class="filter-select" value="{{ $activeDueRaw }}" style="width: 100%; box-sizing: border-box; background: white; border-color: #fca5a5; cursor: pointer;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 4px; text-transform: uppercase;">Base Rate (₱/kWh)</label>
                    <input type="number" step="0.01" id="adjCycleRate" class="filter-select" value="{{ $elecRate }}" style="width: 100%; box-sizing: border-box; background: white;">
                </div>
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 4px; text-transform: uppercase;">Late Penalty (%)</label>
                    <input type="number" step="0.1" id="adjCyclePenalty" class="filter-select" value="{{ $elecPenalty }}" style="width: 100%; box-sizing: border-box; background: white;">
                </div>
            </div>
        </div>

        <div class="reset-cycle-modal-footer" style="border-top: 1px solid #f1f5f9; padding-top: 14px;">
            <button class="btn btn-outline" style="justify-content: center; padding: 10px 16px;" onclick="closeAdjustCycleModal()">Cancel</button>
            <button class="btn btn-primary" style="justify-content: center; padding: 10px 20px; background: #d97706; border-color: #d97706;" onclick="executeAdjustCycle()">Save Adjustments</button>
        </div>
    </div>
</div>

<!-- Generic Input Modal -->
<div id="genericInputModal" class="modal-overlay" style="display: none; align-items: center; justify-content: center; z-index: 10000; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px);">
    <div class="modal-content" style="background: #fff; max-width: 400px; width: 90%; border-radius: 20px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); overflow: hidden; transform: scale(0.95); transition: transform 0.2s ease-out;">
        <div style="padding: 24px; border-bottom: 1px solid #e2e8f0;">
            <h3 id="genericInputTitle" style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0;">Title</h3>
            <p id="genericInputDesc" style="font-size: 13px; color: #64748b; margin-top: 8px; margin-bottom: 0;">Description</p>
        </div>
        <div style="padding: 24px;" id="genericInputContainer">
            <div style="position: relative;">
                <span style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 20px; font-weight: 700;">₱</span>
                <input type="number" id="genericInputValue" class="form-input" style="width: 100%; font-size: 24px; padding: 12px 12px 12px 40px; font-weight: 700; border-radius: 12px; border: 2px solid #e2e8f0;" step="0.01" />
            </div>
        </div>
        <div style="padding: 16px 24px; background: #f8fafc; display: flex; gap: 12px; justify-content: flex-end; border-top: 1px solid #e2e8f0;">
            <button class="btn btn-outline" onclick="closeGenericInputModal()">Cancel</button>
            <button id="genericInputConfirm" class="btn btn-primary">Confirm</button>
        </div>
    </div>
</div>

<script>
    function promptAsync(title, desc, defaultValue, hideInput = false, confirmText = "Confirm", confirmClass = "btn-primary") {
        return new Promise((resolve) => {
            const modal = document.getElementById('genericInputModal');
            modal.style.display = 'flex';
            setTimeout(() => { modal.firstElementChild.style.transform = 'scale(1)'; }, 10);
            document.getElementById('genericInputTitle').textContent = title;
            document.getElementById('genericInputDesc').textContent = desc;
            const inputContainer = document.getElementById('genericInputContainer');
            const input = document.getElementById('genericInputValue');
            
            if (hideInput) {
                inputContainer.style.display = 'none';
                input.value = '';
            } else {
                inputContainer.style.display = 'block';
                input.value = defaultValue || '';
            }
            
            const confirmBtn = document.getElementById('genericInputConfirm');
            confirmBtn.textContent = confirmText;
            confirmBtn.className = `btn ${confirmClass}`;
            
            const cleanup = () => {
                modal.firstElementChild.style.transform = 'scale(0.95)';
                setTimeout(() => { modal.style.display = 'none'; }, 200);
                confirmBtn.replaceWith(confirmBtn.cloneNode(true));
            };

            window.closeGenericInputModal = () => {
                cleanup();
                resolve(null);
            };

            confirmBtn.addEventListener('click', () => {
                const val = hideInput ? true : input.value;
                cleanup();
                resolve(val);
            });

            modal.style.display = 'flex';
            setTimeout(() => { modal.firstElementChild.style.transform = 'scale(1)'; }, 10);
            if (!hideInput) input.focus();
        });
    }
    const statsData = @json($stats);
    let isPaidTodayFilter = false;

    // Global Billing Settings
    let currentKwhRate = {{ floatval(\App\Models\Setting::where('key', 'elec_rate')->value('value') ?: 10) }};
    let currentPenaltyRate = {{ floatval(\App\Models\Setting::where('key', 'electricity_penalty')->value('value') ?: 5) }};

    // Load Data safely
    let allBillsRaw = @json($bills);

    function openSettingsModal() {
        if (new URLSearchParams(window.location.search).get('action') !== 'settings') {
            window.history.pushState(null, '', '?action=settings');
        }
        document.getElementById('inputKwhRate').value = currentKwhRate;
        document.getElementById('settingsModal').style.display = 'flex';
    }

    function closeSettingsModal() {
        window.history.replaceState(null, '', window.location.pathname);
        document.getElementById('settingsModal').style.display = 'none';
    }

    function applySettings() {
        currentKwhRate = parseFloat(document.getElementById('inputKwhRate').value);
        
        fetch('/api/settings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ elec_rate: currentKwhRate })
        }).then(() => {
            recalculateLedger();
            closeSettingsModal();
            
            // Notify
            if (window.pushSystemNotification) {
                window.pushSystemNotification("Global Settings Updated", `New Base Rate: ₱${currentKwhRate}/kWh.`, new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }), false);
                Swal.fire(`Rates updated to ₱${currentKwhRate}/kWh.`);
            }
        });
    }

    function recalculateLedger() {
        const amountCells = document.querySelectorAll('.dynamic-amount-cell');
        amountCells.forEach(cell => {
            const usage = parseFloat(cell.dataset.usageKwh) || 0;
            const previousBalance = parseFloat(cell.dataset.previousBalance) || 0;
            const totalPaid = parseFloat(cell.dataset.totalPaid) || 0;
            const status = (cell.dataset.status || 'unpaid').toLowerCase();
            const isAtRisk = cell.dataset.atRisk === 'true';
            
            let amountBefore = (usage * currentKwhRate) + previousBalance;
            let total = amountBefore;
            if (isAtRisk && status !== 'paid') {
                total += amountBefore * (currentPenaltyRate / 100);
            }
            const remaining = Math.max(0, total - totalPaid);

            // Save the raw active total back into the cell dataset for the summary dashboard to read
            cell.dataset.rawTotal = total;
            let html = `<div>₱${total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>`;
            if (isAtRisk && status !== 'paid' && amountBefore > 0) {
                html += `<div style="font-size: 10px; color: #ef4444; font-weight: 600;">Incl. ${currentPenaltyRate}% Penalty</div>`;
            }
            if (totalPaid > 0 && status !== 'paid') {
                html += `<div style="font-size: 11px; color: #10b981; font-weight: 600; margin-top: 2px;">Paid: ₱${totalPaid.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>`;
                html += `<div style="font-size: 11px; color: #d97706; font-weight: 700;">Bal: ₱${remaining.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>`;
            }
            cell.innerHTML = html;
        });
        
        updateSummaryDashboard();
    }

    function updateSummaryDashboard() {
        const monthSel = document.getElementById('summaryMonth')?.value || '';
        const sumColEl = document.getElementById('sumCollection');
        const sumBalanceEl = document.getElementById('sumBalanceToCollect');
        const sumPaidEl = document.getElementById('sumPaid');
        const sumUnpaidEl = document.getElementById('sumUnpaid');
        const sumPastDueEl = document.getElementById('sumPastDue');
        const rows = document.querySelectorAll('.bill-row');

        let totalCollected = 0;
        let totalRemaining = 0;
        let countPaid = 0;
        let countUnpaid = 0;
        let countPastDue = 0;

        rows.forEach(row => {
            const status = (row.dataset.status || 'unpaid').toLowerCase();
            const cell = row.querySelector('.dynamic-amount-cell') || row.cells[1];
            const amt = parseFloat(cell.dataset.rawTotal || cell.textContent.replace(/[^\d.-]/g, '')) || 0;
            const totalPaid = parseFloat(cell.dataset.totalPaid) || 0;
            const isAtRisk = cell.dataset.atRisk === 'true';

            if (status === 'paid') {
                countPaid++;
                totalCollected += (totalPaid > 0 ? totalPaid : amt);
            } else {
                countUnpaid++;
                totalCollected += totalPaid;
                totalRemaining += Math.max(0, amt - totalPaid);
                if (isAtRisk || row.querySelector('.trend-late') || row.dataset.trend === 'at-risk') {
                    countPastDue++;
                }
            }
        });

        const totalBilled = totalCollected + totalRemaining;

        if (sumColEl) sumColEl.textContent = `₱${totalCollected.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        if (sumBalanceEl) sumBalanceEl.textContent = `₱${totalBilled.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        if (sumPaidEl) sumPaidEl.textContent = countPaid;
        if (sumUnpaidEl) sumUnpaidEl.textContent = countUnpaid;
        if (sumPastDueEl) sumPastDueEl.textContent = countPastDue;

        const subEl = document.getElementById('sumCollectSubtext');
        if (subEl) {
            if (totalRemaining > 0) {
                subEl.innerHTML = `<span style="color:#64748b;">Remaining:</span> <strong style="color:#ef4444;">₱${totalRemaining.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong> <span style="color:#94a3b8;">(${countUnpaid} unpaid)</span>`;
            } else {
                subEl.innerHTML = `<span style="color:#10b981; font-weight:700;">✓ 100% Collected (All Settled)</span>`;
            }
        }
        
        // Real-time graph update (only when a valid YYYY-MM cycle is selected)
        if (monthSel && /^\d{4}-\d{2}$/.test(monthSel) && window.salesChart && window.salesChart.data && window.salesChart.data.datasets.length > 0) {
            const ds = window.salesChart.data.datasets[0];
            if (ds && ds.data) {
                const [yrStr, moStr] = monthSel.split('-');
                const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const currentMonthLabel = monthNames[parseInt(moStr, 10) - 1] || '';
                const labelIdx = currentMonthLabel ? window.salesChart.data.labels.findIndex(l => l.startsWith(currentMonthLabel)) : -1;

                if (labelIdx !== -1) {
                    ds.data[labelIdx] = totalCollected;
                    window.salesChart.update();
                }
            }
        }

        // Update Collection Efficiency card
        const cerPct = totalBilled > 0 ? Math.min(100, Math.max(0, Math.round((totalCollected / totalBilled) * 100))) : 0;
        const cerFill = document.getElementById('cerGaugeFill');
        if (cerFill) {
            cerFill.setAttribute('stroke-dasharray', `${cerPct} ${100 - cerPct}`);
            cerFill.style.strokeOpacity = cerPct <= 0 ? '0' : '1';
        }
        const cerPctEl = document.getElementById('cerGaugePct');
        if (cerPctEl) cerPctEl.textContent = `${cerPct}%`;
        const cerBilledEl = document.getElementById('cerTotalBilled');
        if (cerBilledEl) cerBilledEl.textContent = `₱${totalBilled.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        const cerColEl = document.getElementById('cerTotalCollected');
        if (cerColEl) cerColEl.textContent = `₱${totalCollected.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        const cerOutEl = document.getElementById('cerOutstanding');
        if (cerOutEl) cerOutEl.textContent = `₱${Math.max(0, totalBilled - totalCollected).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        const cerAcctsEl = document.getElementById('cerAccountsPaid');
        if (cerAcctsEl) cerAcctsEl.textContent = `${countPaid} / ${countPaid + countUnpaid}`;

        updatePaidDonut();
    }

    function updatePaidDonut() {
        const rows = document.querySelectorAll('.bill-row');
        let paid = 0, partial = 0, unpaid = 0;

        rows.forEach(row => {
            const st = (row.dataset.status || '').toLowerCase();
            if (st === 'paid') paid++;
            else if (st === 'partially_paid') partial++;
            else unpaid++;
        });

        const total = paid + partial + unpaid;
        const pPaid = total > 0 ? Math.round((paid / total) * 100) : 0;
        const pPartial = total > 0 ? Math.round((partial / total) * 100) : 0;
        const pUnpaid = total > 0 ? Math.max(0, 100 - pPaid - pPartial) : 0;

        const pctEl = document.getElementById('paidDonutPct');
        if (pctEl) pctEl.textContent = `${pPaid}%`;
        const subEl = document.getElementById('paidDonutSub');
        if (subEl) subEl.textContent = `${paid} / ${total}`;

        const donutSvg = document.querySelector('.donut-svg');
        if (donutSvg) {
            if (total === 0) {
                donutSvg.innerHTML = `<circle class="donut-segment segment-track" cx="21" cy="21" r="15.915" stroke-dasharray="100 0"></circle>`;
            } else {
                donutSvg.innerHTML = `
                    <circle class="donut-segment segment-track" cx="21" cy="21" r="15.915" stroke-dasharray="100 0"></circle>
                    <circle class="donut-segment segment-paid" cx="21" cy="21" r="15.915" stroke-dasharray="${pPaid} ${100 - pPaid}" stroke-dashoffset="0"></circle>
                    <circle class="donut-segment segment-partial" cx="21" cy="21" r="15.915" stroke-dasharray="${pPartial} ${100 - pPartial}" stroke-dashoffset="-${pPaid}"></circle>
                    <circle class="donut-segment segment-unpaid" cx="21" cy="21" r="15.915" stroke-dasharray="${pUnpaid} ${100 - pUnpaid}" stroke-dashoffset="-${pPaid + pPartial}"></circle>
                `;
            }

            const legend = document.getElementById('paidDonutLegend') || document.querySelector('.donut-legend');
            if (legend) {
                if (total === 0) {
                    legend.innerHTML = `<div class="legend-item" style="justify-content: center; color: #64748b;">No billing records yet.</div>`;
                } else {
                    legend.innerHTML = `
                        <div class="legend-item"><span class="legend-label"><span class="dot" style="background: #10b981;"></span> Paid</span><span><span class="legend-count">${paid} accts</span><strong>${pPaid}%</strong></span></div>
                        <div class="legend-item"><span class="legend-label"><span class="dot" style="background: #3b82f6;"></span> Partially Paid</span><span><span class="legend-count">${partial} accts</span><strong>${pPartial}%</strong></span></div>
                        <div class="legend-item"><span class="legend-label"><span class="dot" style="background: #f59e0b;"></span> Unpaid</span><span><span class="legend-count">${unpaid} accts</span><strong>${pUnpaid}%</strong></span></div>
                    `;
                }
            }
        }
    }

    function updatePredictiveDonut() {
        updatePaidDonut();
    }

    let smartPrevDate = '';
    let smartCurrDate = '';
    let smartDueDate = '';
    let minCycleCurrYmd = '';

    function formatLocalYmd(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function computeBillingCycleDates() {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        let minCurr = new Date(today.getFullYear(), today.getMonth(), 1);

        smartPrevDate = '';
        smartCurrDate = '';
        smartDueDate = '';

        if (allBillsRaw && allBillsRaw.length > 0 && allBillsRaw[0].current_reading_date) {
            const lastCurr = new Date(allBillsRaw[0].current_reading_date + 'T00:00:00');
            const nextMonthStart = new Date(lastCurr.getFullYear(), lastCurr.getMonth() + 1, 1);
            if (nextMonthStart > minCurr) {
                minCurr = nextMonthStart;
            }

            let nextCurr = new Date(lastCurr.getFullYear(), lastCurr.getMonth() + 1, lastCurr.getDate());
            if (nextCurr < minCurr) {
                nextCurr = new Date(minCurr);
            }
            const dueDay = new Date(nextCurr);
            dueDay.setDate(dueDay.getDate() + 15);

            smartPrevDate = formatLocalYmd(lastCurr);
            smartCurrDate = formatLocalYmd(nextCurr);
            smartDueDate = formatLocalYmd(dueDay);
        }

        minCycleCurrYmd = formatLocalYmd(minCurr);
        return minCycleCurrYmd;
    }

    function applyCycleDateConstraints() {
        const minCurr = minCycleCurrYmd || computeBillingCycleDates();
        document.getElementById('newCycleCurr').min = minCurr;
        document.getElementById('newCycleDue').min = document.getElementById('newCycleCurr').value || minCurr;
        const hint = document.getElementById('cycleDateHint');
        if (hint) {
            hint.textContent = `Current reading must be on or after ${minCurr} (no backdated or previous-month cycles).`;
        }
        syncCycleDateBounds();
    }

    function syncCycleDateBounds() {
        const curr = document.getElementById('newCycleCurr').value;
        const minCurr = minCycleCurrYmd || computeBillingCycleDates();
        document.getElementById('newCycleDue').min = curr || minCurr;
        if (curr) {
            document.getElementById('newCyclePrev').max = curr;
        } else {
            document.getElementById('newCyclePrev').removeAttribute('max');
        }
    }

    function validateCycleDates(prevDate, currDate, dueDate) {
        if (!minCycleCurrYmd) {
            computeBillingCycleDates();
        }
        if (currDate < minCycleCurrYmd) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid current reading date',
                text: `You cannot create a cycle for a past month. Use ${minCycleCurrYmd} or later.`
            });
            return false;
        }
        if (prevDate >= currDate) {
            Swal.fire('Previous reading date must be before the current reading date.');
            return false;
        }
        if (dueDate < currDate) {
            Swal.fire('Due date must be on or after the current reading date.');
            return false;
        }
        return true;
    }

    let latestCycleStatusData = null;

    async function resetBillingCycle() {
        if (new URLSearchParams(window.location.search).get('action') !== 'reset') {
            window.history.pushState(null, '', '?action=reset');
        }
        document.getElementById('newCycleRate').value = currentKwhRate;
        document.getElementById('newCyclePenalty').value = currentPenaltyRate;

        const statusCard = document.getElementById('cycleAutomationStatusCard');
        if (statusCard) {
            statusCard.style.display = 'block';
            statusCard.style.background = '#f8fafc';
            statusCard.style.border = '1px solid #e2e8f0';
            statusCard.style.color = '#64748b';
            statusCard.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 6px;"></i> Checking active cycle status and projecting next reading dates...';
        }

        const createBtn = document.getElementById('btnCreateNewCycle');
        if (createBtn) {
            createBtn.disabled = false;
            createBtn.style.opacity = '1';
            createBtn.style.cursor = 'pointer';
            createBtn.textContent = 'Create Cycle';
        }

        document.getElementById('resetCycleModal').style.display = 'flex';

        try {
            const res = await fetch('/admin/api/billing/cycle-status?type=electricity');
            const data = await res.json();
            if (data.success) {
                latestCycleStatusData = data;
                applySmartCycleData(data);
            } else {
                computeBillingCycleDates();
                applyCycleDateConstraints();
                autoFillDates();
            }
        } catch (e) {
            console.error('Failed to fetch cycle status:', e);
            computeBillingCycleDates();
            applyCycleDateConstraints();
            autoFillDates();
        }
    }

    function applySmartCycleData(data) {
        const next = data.next_cycle;
        const active = data.active_cycle;
        const statusCard = document.getElementById('cycleAutomationStatusCard');
        const createBtn = document.getElementById('btnCreateNewCycle');

        if (next) {
            document.getElementById('newCyclePrev').value = next.suggested_prev_date || '';
            document.getElementById('newCycleCurr').value = next.suggested_curr_date || '';
            document.getElementById('newCycleDue').value = next.suggested_due_date || '';

            minCycleCurrYmd = active ? active.period : (next.suggested_prev_date || '');
            document.getElementById('newCycleCurr').min = minCycleCurrYmd;
            document.getElementById('newCycleDue').min = next.suggested_curr_date || minCycleCurrYmd;

            if (createBtn) {
                if (next.already_generated) {
                    createBtn.textContent = `${next.label} Cycle Already Active`;
                    createBtn.disabled = true;
                    createBtn.style.opacity = '0.6';
                    createBtn.style.cursor = 'not-allowed';
                } else {
                    createBtn.textContent = `Create ${next.label} Cycle`;
                    createBtn.disabled = false;
                    createBtn.style.opacity = '1';
                    createBtn.style.cursor = 'pointer';
                }
            }
        }

        if (statusCard) {
            statusCard.style.display = 'block';
            if (next && next.already_generated) {
                statusCard.style.background = '#eff6ff';
                statusCard.style.border = '1px solid #bfdbfe';
                statusCard.style.color = '#1e40af';
                statusCard.innerHTML = `<strong>ℹ️ ${next.label} is Already Active:</strong> All active lots have ledger entries. Use "Adjust Current Cycle" if you need to update rates or due dates.`;
            } else if (active && active.unrecorded_readings_count > 0) {
                statusCard.style.background = '#fffbeb';
                statusCard.style.border = '1px solid #fde68a';
                statusCard.style.color = '#b45309';
                statusCard.innerHTML = `<strong>⚠️ Meter Readings Notice:</strong> ${active.unrecorded_readings_count} lot(s) in ${active.label} have unrecorded readings (0 kWh). Meter baselines will be carried forward automatically.`;
            } else {
                statusCard.style.background = '#f0fdf4';
                statusCard.style.border = '1px solid #bbf7d0';
                statusCard.style.color = '#166534';
                statusCard.innerHTML = `<strong>🟢 Automation Ready:</strong> Target <strong>${next ? next.label : 'Next'}</strong> cycle dates and arrears calculated automatically. 1-click generation.`;
            }
        }
    }

    function autoFillDates() {
        if (!document.getElementById('newCyclePrev').value && smartPrevDate) {
            document.getElementById('newCyclePrev').value = smartPrevDate;
        }
        if (!document.getElementById('newCycleCurr').value && smartCurrDate) {
            document.getElementById('newCycleCurr').value = smartCurrDate;
        }
        if (!document.getElementById('newCycleDue').value && smartDueDate) {
            document.getElementById('newCycleDue').value = smartDueDate;
        }
        syncCycleDateBounds();
    }

    function closeResetCycleModal() {
        window.history.replaceState(null, '', window.location.pathname);
        document.getElementById('resetCycleModal').style.display = 'none';
    }

    async function executeResetCycle() {
        const rate = document.getElementById('newCycleRate').value || currentKwhRate;
        const penalty = document.getElementById('newCyclePenalty').value || currentPenaltyRate || 5;
        const prevDate = document.getElementById('newCyclePrev').value;
        const currDate = document.getElementById('newCycleCurr').value;
        const dueDate = document.getElementById('newCycleDue').value;
        
        if (!prevDate || !currDate || !dueDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Dates',
                text: 'Please select previous reading, current reading, and due dates.'
            });
            return;
        }
        if (!validateCycleDates(prevDate, currDate, dueDate)) {
            return;
        }

        const createBtn = document.getElementById('btnCreateNewCycle');
        if (createBtn) {
            createBtn.disabled = true;
            createBtn.textContent = 'Generating Cycle...';
        }

        closeResetCycleModal();

        Swal.fire({
            title: 'Generating Billing Cycle...',
            html: '<div style="margin-top:8px;color:#64748b;font-size:14px;">Creating ledger entries and carrying over balances. Please wait...</div>',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const res = await fetch('/admin/api/billing/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ type: 'electricity', rate: rate, penalty: penalty, prev_date: prevDate, curr_date: currDate, due_date: dueDate })
            });
            const data = await res.json().catch(() => ({}));
            if (data.already_exists) {
                await Swal.fire({
                    icon: 'info',
                    title: 'Cycle Already Active',
                    text: data.message,
                    confirmButtonColor: '#0284c7'
                });
            } else if (data.success) {
                if (window.pushSystemNotification) {
                    window.pushSystemNotification("Cycle Reset", "A new monthly billing cycle has been initiated.", "System");
                }
                await Swal.fire({
                    icon: 'success',
                    title: 'Billing Cycle Created!',
                    text: data.message || `New electricity billing cycle generated successfully with rate ₱${rate}/kWh.`,
                    confirmButtonColor: '#0284c7',
                    confirmButtonText: 'Refresh View'
                });
                window.location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Billing Cycle Not Created',
                    text: data.message || 'Failed to reset billing cycle.',
                    confirmButtonColor: '#ef4444'
                });
                if (createBtn) {
                    createBtn.disabled = false;
                    createBtn.textContent = 'Create Cycle';
                }
            }
        } catch(e) {
            console.error(e);
            Swal.fire({
                icon: 'error',
                title: 'Request Failed',
                text: 'A network error occurred while generating the billing cycle. Please try again.',
                confirmButtonColor: '#ef4444'
            });
            if (createBtn) {
                createBtn.disabled = false;
                createBtn.textContent = 'Create Cycle';
            }
        }
    }

    function openAdjustCycleModal() {
        document.getElementById('adjustCycleModal').style.display = 'flex';
    }

    function closeAdjustCycleModal() {
        document.getElementById('adjustCycleModal').style.display = 'none';
    }

    async function executeAdjustCycle() {
        const prevDate = document.getElementById('adjCyclePrev').value;
        const currDate = document.getElementById('adjCycleCurr').value;
        const dueDate = document.getElementById('adjCycleDue').value;
        const rate = parseFloat(document.getElementById('adjCycleRate').value);
        const penalty = parseFloat(document.getElementById('adjCyclePenalty').value);

        if (!prevDate || !currDate || !dueDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Dates',
                text: 'Please select previous reading, current reading, and due dates.'
            });
            return;
        }

        if (new Date(prevDate) >= new Date(currDate)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Date Range',
                text: 'Previous reading date must be before current reading date.'
            });
            return;
        }

        if (new Date(dueDate) < new Date(currDate)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Due Date',
                text: 'Due date must be on or after current reading date.'
            });
            return;
        }

        closeAdjustCycleModal();

        Swal.fire({
            title: 'Updating Active Cycle...',
            html: '<div style="margin-top:8px;color:#64748b;font-size:14px;">Applying new dates, updating rates, and recalculating bills. Please wait...</div>',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const res = await fetch('/admin/api/billing/adjust-cycle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    type: 'electricity',
                    cycle: document.getElementById('summaryMonth')?.value || '{{ request("cycle") }}',
                    prev_date: prevDate,
                    curr_date: currDate,
                    due_date: dueDate,
                    rate: rate,
                    penalty: penalty
                })
            });

            const data = await res.json().catch(() => ({}));
            if (data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Active Cycle Updated!',
                    text: data.message || 'Electricity active cycle parameters and bills successfully updated.',
                    confirmButtonColor: '#d97706',
                    confirmButtonText: 'OK'
                });
                window.location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    text: data.message || 'Failed to update cycle parameters.',
                    confirmButtonColor: '#ef4444'
                });
            }
        } catch(e) {
            console.error(e);
            Swal.fire({
                icon: 'error',
                title: 'Request Failed',
                text: 'A network error occurred while updating the cycle parameters.',
                confirmButtonColor: '#ef4444'
            });
        }
    }

    ['newCycleCurr', 'newCyclePrev', 'newCycleDue'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', syncCycleDateBounds);
        }
    });

    async function addPreviousBalanceFromModal(id, resident) {
        const inputAmount = await promptAsync(
            "Add Unpaid Balance", 
            `Enter previous unpaid balance for ${resident} to add to this cycle:`, 
            ""
        );
        if (inputAmount === null || inputAmount === "") return;
        const amountNum = parseFloat(inputAmount);
        if (isNaN(amountNum) || amountNum <= 0) {
            Swal.fire("Invalid amount.");
            return;
        }

        try {
            const res = await fetch('/admin/api/billing/add-balance', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ id: id, amount: amountNum })
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire(`Unpaid balance of ₱${amountNum.toLocaleString()} added for ${resident}.`);
                setTimeout(() => window.location.reload(), 1000);
            } else {
                Swal.fire(data.message || 'Failed to add balance.');
            }
        } catch(e) {
            console.error(e);
            Swal.fire('Failed to add balance.');
        }
    }

    function systemNotificationScan() {
        if (!window.pushSystemNotification) return;
        
        let warningsSent = 0;
        allBillsRaw.forEach(bill => {
            if (bill.status === 'unpaid' && bill.at_risk) {
                // Throttle notifications so it doesn't spam too much initially, just take first 3
                if (warningsSent < 3) {
                    window.pushSystemNotification("⚠️ Action Required", `Resident ${bill.resident} is past due and at risk of disconnection.`, "System Alert");
                    warningsSent++;
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        applyFilters(); // Initialize connection filter
        recalculateLedger();
        setTimeout(systemNotificationScan, 1000); // Wait 1 sec before populating notifications
    });

    async function disconnectCasureco() {
        const lotNumber = document.getElementById('modalLot').textContent;
        const resident = document.getElementById('modalResident').textContent;
        const lotKey = _currentBill && _currentBill.lot_number != null ? String(_currentBill.lot_number) : '';
        const blockKey = _currentBill && _currentBill.block != null && _currentBill.block !== 'N/A' ? String(_currentBill.block) : '';
        
        const confirmed = await promptAsync(
            "Confirm Disconnection",
            `Are you sure you want to transition ${resident}'s lot (${lotNumber}) to CASURECO? This will prevent future internal electricity bills from being generated for them.`,
            null, true, "Disconnect Lot", "btn-danger"
        );
        
        if (!confirmed) return;
        if (!lotKey || !blockKey) {
            Swal.fire('Could not identify this lot. Nothing was changed.');
            return;
        }

        try {
            const res = await fetch('/admin/api/billing/disconnect', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ resident: resident, lot: lotKey, block: blockKey })
            });
            const data = await res.json();
            if(data.success) {
                Swal.fire(`Successfully disconnected ${resident}. They are now managed by CASURECO.`);
                window.location.reload();
            } else {
                Swal.fire(data.message || 'Failed to disconnect lot.');
            }
        } catch(e) {
            console.error(e);
            Swal.fire('Failed to disconnect lot.');
        }
    }

    async function reconnectGrid() {
        const lotNumber = document.getElementById('modalLot').textContent;
        const resident = document.getElementById('modalResident').textContent;
        const lotKey = _currentBill && _currentBill.lot_number != null ? String(_currentBill.lot_number) : '';
        const blockKey = _currentBill && _currentBill.block != null && _currentBill.block !== 'N/A' ? String(_currentBill.block) : '';
        
        const confirmed = await promptAsync(
            "Confirm Reconnection",
            `Are you sure you want to reconnect ${resident}'s lot (${lotNumber}) back to the Internal Subdivision Grid? They will start receiving internal electricity bills again.`,
            null, true, "Reconnect Grid", "btn-success"
        );
        
        if (!confirmed) return;
        if (!lotKey || !blockKey) {
            Swal.fire('Could not identify this lot. Nothing was changed.');
            return;
        }

        try {
            const res = await fetch('/admin/api/billing/reconnect', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ resident: resident, lot: lotKey, block: blockKey })
            });
            const data = await res.json();
            if(data.success) {
                Swal.fire(`Successfully reconnected ${resident}. They are now managed by the Internal Grid.`);
                window.location.reload();
            } else {
                Swal.fire(data.message || 'Failed to reconnect lot.');
            }
        } catch(e) {
            console.error(e);
            Swal.fire('Failed to reconnect lot.');
        }
    }

    let _currentBill = null;

    function viewDetail(id) {
        let bill = allBillsRaw.find(b => String(b.id).trim() === String(id).trim());
        if (!bill) {
            bill = allBillsRaw.find(b => b.db_id && String(b.db_id).trim() === String(id).trim());
        }
        if (!bill) return;
        _currentBill = bill;

        const resident = bill.resident;
        const lot = bill.lot;
        const usageKwh = parseFloat(bill.usage_kwh) || 0;
        const isAtRisk = Boolean(bill.at_risk);
        const status = (bill.status || 'unpaid').toLowerCase();
        const history = bill.payment_history || [];
        const auditLog = bill.audit_log || [];

        const atRiskBool = isAtRisk && status !== 'paid';

        let currentMonthCharges = usageKwh * currentKwhRate;
        let previousBalance = parseFloat(bill.previous_balance) || 0;
        let amountBefore = currentMonthCharges + previousBalance;
        let penaltyAmount = amountBefore * (currentPenaltyRate / 100);
        let amountAfter = amountBefore + penaltyAmount;
        let totalPaid = parseFloat(bill.total_paid) || 0;
        let totalDueBefore = Math.max(0, Number((amountBefore - totalPaid).toFixed(2)));
        let totalDueAfter = Math.max(0, Number((amountAfter - totalPaid).toFixed(2)));
        let remainingBalance = status === 'paid' ? 0 : (atRiskBool ? totalDueAfter : totalDueBefore);

        document.getElementById('modalResident').textContent = resident;
        document.getElementById('modalLot').textContent = lot;

        const modalBadge = document.getElementById('modalPaymentBadge');
        if (modalBadge) {
            if (status === 'paid') {
                modalBadge.className = 'badge badge-success';
                modalBadge.style.cssText = '';
                modalBadge.textContent = 'Paid';
            } else if (status === 'partially_paid') {
                modalBadge.className = 'badge';
                modalBadge.style.cssText = 'background: #fef3c7; color: #b45309; border: 1px solid #fde68a;';
                modalBadge.textContent = atRiskBool ? 'Partially Paid (Past Due)' : 'Partially Paid';
            } else {
                modalBadge.className = 'badge badge-danger';
                modalBadge.style.cssText = '';
                modalBadge.textContent = atRiskBool ? 'Unpaid (Past Due)' : 'Unpaid';
            }
        }
        
        document.getElementById('modalPrevReading').textContent = bill.prev_reading || 0;
        document.getElementById('modalCurrReading').textContent = bill.curr_reading || 0;
        document.getElementById('modalBaseRate').textContent = `₱${currentKwhRate}/kWh`;
        document.getElementById('modalUsage').textContent = `${usageKwh} kWh`;
        
        const currChargesEl = document.getElementById('modalCurrentCharges');
        if (currChargesEl) {
            currChargesEl.textContent = `₱${currentMonthCharges.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        }
        const penaltyLabelEl = document.getElementById('modalPenaltyLabel');
        if (penaltyLabelEl) {
            penaltyLabelEl.textContent = atRiskBool
                ? `Penalty (${currentPenaltyRate}% Late Fee — Applied)`
                : `Penalty (${currentPenaltyRate}% if paid after ${bill.due})`;
            penaltyLabelEl.style.color = atRiskBool ? '#dc2626' : '#475569';
            penaltyLabelEl.style.fontWeight = atRiskBool ? '700' : '400';
        }
        document.getElementById('modalAmountBeforeLabel').textContent = `Amount Before ${bill.due}`;
        document.getElementById('modalAmountAfterLabel').textContent = `Amount After ${bill.due}${atRiskBool ? ' (Active Due)' : ''}`;
        document.getElementById('modalAmountBefore').textContent = `₱${amountBefore.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('modalAmountAfter').textContent = `₱${amountAfter.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('modalPenalty').textContent = `₱${penaltyAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('modalPrevBalance').textContent = `₱${previousBalance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('modalTotalPaid').textContent = `₱${totalPaid.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        const remBalEl = document.getElementById('modalRemainingBalance');
        if (remBalEl) {
            remBalEl.textContent = `₱${remainingBalance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            remBalEl.style.color = remainingBalance > 0 ? '#ef4444' : '#10b981';
        }
        document.getElementById('modalDateIssued').textContent = bill.issued_date || 'N/A';
        
        
        const trendEl = document.getElementById('modalTrend');
        if (trendEl) {
            trendEl.textContent = !atRiskBool ? 'Early' : 'Late';
            trendEl.className = 'trend-chip ' + (!atRiskBool ? 'trend-early' : 'trend-late');
        }

        // Toggle Disconnect/Reconnect buttons based on provider_managed
        const disconnectBtn = document.getElementById('modalDisconnectBtn');
        const reconnectBtn = document.getElementById('modalReconnectBtn');
        if (disconnectBtn && reconnectBtn) {
            if (bill.provider_managed) {
                disconnectBtn.style.display = 'none';
                reconnectBtn.style.display = 'inline-flex';
            } else {
                disconnectBtn.style.display = 'inline-flex';
                reconnectBtn.style.display = 'none';
            }
        }

        // Payment Action Button
        const actionContainer = document.getElementById('modalPaymentAction');
        if (status !== 'paid') {
            const amountToPay = remainingBalance;
            actionContainer.innerHTML = `
                <div style="display: flex; gap: 12px; width: 100%; flex-wrap: wrap;">
                    <button class="btn btn-outline" style="flex: 1 1 200px; justify-content: center; padding: 14px; font-weight: 700; border-color: var(--bill-primary); color: var(--bill-primary);" onclick="addPreviousBalanceFromModal('${id}', '${resident}')">➕ Add Unpaid Balance</button>
                    <button class="btn btn-success" style="flex: 1 1 200px; justify-content: center; padding: 14px; font-weight: 700;" onclick="recordOfficePaymentFromModal('${id}', '${resident}', ${amountToPay})">🏢 Record Office Payment (₱${amountToPay.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})})</button>
                </div>
            `;
        } else {
            actionContainer.innerHTML = '';
        }

        // Populate History Table
        const historyBody = document.getElementById('modalHistoryTableBody');
        historyBody.innerHTML = '';
        history.forEach(item => {
            const row = document.createElement('tr');
            const amtStr = Number(item.amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            row.innerHTML = `
                <td data-label="TRN">
                    <span style="font-family: monospace; background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 11px;">
                        ${item.trn}
                    </span>
                </td>
                <td data-label="Month">${item.month}</td>
                <td data-label="Amount" style="font-weight: 700;">₱${amtStr}</td>
                <td data-label="Status"><span class="badge ${item.status === 'Paid' ? 'badge-success' : 'badge-danger'}">${item.status}</span></td>
                <td data-label="Date" style="font-size: 11px; color: #64748b;">${item.date}</td>
                <td data-label="Receipt"><button onclick="printPaymentReceipt('${item.trn}')" style="background:#047857; color:#fff; border:none; padding:4px 10px; border-radius:6px; font-size:11px; font-weight:700; cursor:pointer;">Receipt</button></td>
            `;
            historyBody.appendChild(row);
        });

        toggleModalView('main');
        document.getElementById('billModal').style.display = 'flex';
    }

    function toggleModalView(view) {
        const main = document.getElementById('modalMainContent');
        const history = document.getElementById('modalHistoryContent');
        if (view === 'history') {
            main.style.display = 'none';
            history.style.display = 'block';
        } else {
            main.style.display = 'block';
            history.style.display = 'none';
        }
    }

    function closeModal() {
        document.getElementById('billModal').style.display = 'none';
    }

    function closeReceiptModal() {
        document.getElementById('receiptModal').style.display = 'none';
        const frame = document.getElementById('receiptFrame');
        if (frame) frame.srcdoc = '';
    }

    function printReceiptFrame() {
        const frame = document.getElementById('receiptFrame');
        frame.contentWindow.focus();
        frame.contentWindow.print();
    }

    function printPaymentReceipt(trn) {
        const bill = _currentBill;
        if (!bill) return;

        const history = bill.payment_history || [];
        const payment = history.find(p => String(p.trn) === String(trn)) || (history.length > 0 ? history[0] : null);
        if (!payment) return;

        const resident = bill.resident || 'N/A';
        const lot = bill.lot || 'N/A';
        const trnVal = payment.trn || 'N/A';
        const mop = payment.method || 'Office Payment';
        const paidDate = payment.date || 'N/A';
        const month = payment.month || 'Current';
        const amount = parseFloat(payment.amount) || 0;
        const amountFormatted = amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const html = `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Official Payment Receipt - ${trnVal}</title>
<style>
  @page { size: portrait; margin: 0; }
  body {
    width: 100%;
    max-width: 760px;
    margin: 0 auto;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    color: #000;
    font-size: 13px;
    padding: 12mm 15mm;
    background: #fff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    box-sizing: border-box;
  }
  .header {
    text-align: center;
    border-bottom: 2px solid #000;
    padding-bottom: 12px;
    margin-bottom: 18px;
    position: relative;
  }
  .header h1 {
    color: #000;
    margin: 0;
    font-size: 22px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .header p {
    margin: 4px 0 0;
    color: #000;
    font-size: 12px;
  }
  .watermark {
    position: absolute;
    top: 8px;
    right: 12px;
    border: 3px solid #10b981;
    color: #10b981;
    padding: 6px 14px;
    font-size: 22px;
    font-weight: bold;
    border-radius: 6px;
    transform: rotate(12deg);
    opacity: 0.85;
    letter-spacing: 2px;
    text-transform: uppercase;
  }
  .row { width: 100%; margin-bottom: 16px; }
  .col-half { width: 48%; display: inline-block; vertical-align: top; }
  .box {
    border: 1px solid #000;
    padding: 12px;
    border-radius: 4px;
    background: #fff;
    box-sizing: border-box;
  }
  .box h3 {
    margin-top: 0;
    border-bottom: 1px solid #000;
    padding-bottom: 5px;
    font-size: 14px;
    color: #000;
    text-transform: uppercase;
  }
  table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
  th, td { padding: 7px 9px; border: 1px solid #000; color: #000; }
  th { text-align: left; color: #000; background: #f0f0f0; font-weight: bold; }
  .text-right { text-align: right; }
  .fw-bold { font-weight: bold; }
  .total-row td {
    border-top: 2px solid #000;
    border-bottom: 2px solid #000;
    font-size: 14px;
    font-weight: bold;
    color: #000;
    background: #f5f5f5;
  }
  .footer {
    margin-top: 24px;
    text-align: center;
    font-size: 12px;
    color: #000;
    border-top: 1px solid #000;
    padding-top: 12px;
  }
</style>
</head>
<body>
<div class="header">
  <h1>Mayon Builders Realty & Development Corporation</h1>
  <p>Z1, Tagbong, Pili, Camarines Sur</p>
  <p><strong>OFFICIAL PAYMENT RECEIPT (ELECTRICITY)</strong></p>
  <div class="watermark">PAID</div>
</div>
<div class="row">
  <div class="col-half box" style="margin-right:2%;">
    <h3>Resident Details</h3>
    <table style="border:none;margin-bottom:0;">
      <tr><td style="padding:4px;border:none;width:42%;"><strong>Name:</strong></td><td style="padding:4px;border:none;">${resident}</td></tr>
      <tr><td style="padding:4px;border:none;"><strong>Address:</strong></td><td style="padding:4px;border:none;">${lot}</td></tr>
      <tr><td style="padding:4px;border:none;"><strong>Utility:</strong></td><td style="padding:4px;border:none;">Electricity Utility</td></tr>
      <tr><td style="padding:4px;border:none;"><strong>Billing Month:</strong></td><td style="padding:4px;border:none;">${month}</td></tr>
    </table>
  </div>
  <div class="col-half box">
    <h3>Payment Details</h3>
    <table style="border:none;margin-bottom:0;">
      <tr><td style="padding:4px;border:none;width:48%;"><strong>Reference (TRN):</strong></td><td style="padding:4px;border:none;font-family:monospace;font-size:11px;font-weight:bold;">${trnVal}</td></tr>
      <tr><td style="padding:4px;border:none;"><strong>Payment Date &amp; Time:</strong></td><td style="padding:4px;border:none;font-size:11px;font-weight:bold;">${paidDate}</td></tr>
      <tr><td style="padding:4px;border:none;"><strong>Method:</strong></td><td style="padding:4px;border:none;">${mop}</td></tr>
      <tr><td style="padding:4px;border:none;"><strong>Status:</strong></td><td style="padding:4px;border:none;color:#10b981;font-weight:bold;">${payment.status || 'Paid'}</td></tr>
    </table>
  </div>
</div>
<h3 style="color:#000;border-bottom:1px solid #000;padding-bottom:5px;margin:18px 0 10px 0;font-size:14px;text-transform:uppercase;">Payment Breakdown</h3>
<table>
  <thead>
    <tr>
      <th>Description</th>
      <th class="text-right">Amount (PHP)</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Electricity Utility Payment (${month}) — Ref: ${trnVal}</td>
      <td class="text-right">₱${amountFormatted}</td>
    </tr>
    <tr class="total-row">
      <td><strong>Total Amount Paid</strong></td>
      <td class="text-right"><strong>₱${amountFormatted}</strong></td>
    </tr>
  </tbody>
</table>
<div class="footer">
  <p style="font-weight:bold;margin:0;">This payment has been successfully recorded and settled. Thank you for your payment!</p>
  <p style="margin:4px 0 0;font-size:11px;color:#64748b;">Transaction Reference: ${trnVal} · Payment Date &amp; Time: ${paidDate}</p>
</div>
</body>
</html>`;

        const frame = document.getElementById('receiptFrame');
        const receiptModal = document.getElementById('receiptModal');
        const titleEl = document.getElementById('receiptModalTitle') || receiptModal.querySelector('span');
        if (titleEl) titleEl.textContent = 'Official Payment Receipt — Electricity';
        receiptModal.style.display = 'flex';
        frame.srcdoc = html;
    }

    function printStatementReceipt(paymentTrn) {
        if (paymentTrn) {
            return printPaymentReceipt(paymentTrn);
        }
        const bill = _currentBill;
        if (!bill) return;

        const titleEl = document.getElementById('receiptModalTitle') || document.querySelector('#receiptModal span');
        if (titleEl) titleEl.textContent = 'Statement of Account — Electricity';

        const resident = bill.resident || 'N/A';
        const lot = bill.lot || 'N/A';
        const addressLine = `${lot}, Zone 1, Tagbong, Pili, Camarines Sur`;
        const status = (bill.status || 'unpaid').toLowerCase();
        const usageKwh = Number(bill.usage_kwh || 0);
        const prevReading = Number(bill.prev_reading || 0);
        const currReading = Number(bill.curr_reading || 0);
        const prevBalance = parseFloat(bill.previous_balance) || 0;
        const totalPaid = parseFloat(bill.total_paid) || 0;
        const issuedDate = bill.issued_date || 'N/A';
        const dueDate = bill.due || 'N/A';
        const prevDate = bill.prev_reading_date || issuedDate;
        const currDate = bill.curr_reading_date || issuedDate;
        const billingMonthYear = (bill.billing_period || issuedDate || '').toString().toUpperCase();

        const rate = Number(bill.rate || currentKwhRate || 10);
        const currentCharges = bill.base_amount !== undefined ? Number(bill.base_amount) : (usageKwh * rate);
        const amountBeforeDue = currentCharges + prevBalance;
        const penaltyRate = Number(bill.penalty_rate || currentPenaltyRate || 5);
        const penaltyAmount = Math.round(amountBeforeDue * (penaltyRate / 100) * 100) / 100;
        const amountAfterDue = amountBeforeDue + penaltyAmount;
        const history = bill.payment_history || [];
        const latestPay = history.length > 0 ? history[0] : null;
        const trn = latestPay ? (latestPay.trn || 'N/A') : 'N/A';
        const rawMop = latestPay ? (latestPay.method || 'Office') : 'N/A';
        const mopLower = String(rawMop).toLowerCase();
        const mop = mopLower.includes('gcash') ? 'GCash' : (mopLower.includes('maya') ? 'Maya' : (mopLower.includes('office') ? 'Office' : rawMop));
        const paidDate = latestPay ? (latestPay.date || 'N/A') : 'N/A';

        const isPaid = status === 'paid';
        const isPartial = !isPaid && (status === 'partially_paid' || totalPaid > 0);
        const statusColor = isPaid ? '#047857' : (isPartial ? '#b45309' : '#dc2626');
        const statusBg = isPaid ? '#ecfdf5' : (isPartial ? '#fffbeb' : '#fef2f2');
        const statusLabel = isPaid ? 'PAID' : (isPartial ? 'PARTIALLY PAID' : 'UNPAID');
        const isAtRisk = Boolean(bill.at_risk) && !isPaid;
        const effectiveTotal = isAtRisk ? amountAfterDue : amountBeforeDue;
        const remainingBal = isPaid ? 0 : Math.max(0, effectiveTotal - totalPaid);
        const fmt = (n) => Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const paymentBoxHtml = (isPaid || isPartial) ? `
            <div class="payment-box ${isPartial ? 'partial' : ''}">
                <table class="clean-table" style="margin:0;">
                    <tr><td style="width:45%;"><strong>Payment Method:</strong></td><td class="text-right fw-bold">${mop}</td></tr>
                    <tr><td><strong>Amount Paid:</strong></td><td class="text-right fw-bold">₱${fmt(totalPaid)}</td></tr>
                    ${isPartial ? `<tr><td><strong>Remaining Balance:</strong></td><td class="text-right fw-bold">₱${fmt(remainingBal)}</td></tr>` : ''}
                    <tr><td><strong>Reference (TRN):</strong></td><td class="text-right" style="font-family:monospace;">${trn}</td></tr>
                    <tr><td><strong>Payment Date:</strong></td><td class="text-right">${paidDate}</td></tr>
                </table>
            </div>
        ` : '';

        const html = `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Statement of Account - Electricity Bill</title>
<style>
  @page { size: portrait; margin: 0; }
  body {
    width: 100%;
    max-width: 760px;
    margin: 0 auto;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    color: #111;
    font-size: 11px;
    line-height: 1.38;
    padding: 12mm 16mm;
    background: #fff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    box-sizing: border-box;
  }
  .statement-box { border: 1.5px solid #111; padding: 16px 20px; max-width: 520px; margin: 0 auto; background: #fff; position: relative; }
  .status-mark {
    position: absolute; top: 12px; right: 14px; border: 2px solid ${statusColor};
    color: ${statusColor}; background: ${statusBg}; padding: 3px 10px;
    font-size: ${isPartial ? '10.5px' : '12px'}; font-weight: bold; border-radius: 4px;
    text-transform: uppercase; letter-spacing: 1.2px;
  }
  .corp-header { text-align: center; margin-bottom: 12px; padding: 0 70px; }
  .corp-header h1 { margin: 0; font-size: 13px; font-weight: bold; color: #111; }
  .corp-header p { margin: 2px 0 0; font-size: 10.5px; color: #222; }
  .corp-header .bill-type { margin-top: 4px; font-size: 11.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4px; }
  .customer-info { margin-bottom: 10px; font-size: 10.5px; }
  .customer-info p { margin: 2px 0; }
  .divider { border-top: 1px dashed #333; margin: 8px 0; }
  .divider-solid { border-top: 1px solid #111; margin: 8px 0; }
  .notice-title { text-align: center; margin: 6px 0; }
  .notice-title h2 { margin: 0; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
  .notice-title p { margin: 2px 0 0; font-size: 11px; font-weight: bold; text-transform: uppercase; }
  .clean-table { width: 100%; border-collapse: collapse; margin: 4px 0; }
  .clean-table th, .clean-table td { border: none; padding: 2.5px 4px; font-size: 10.5px; color: #111; }
  .clean-table th { font-weight: bold; text-align: left; }
  .text-right { text-align: right; }
  .text-center { text-align: center; }
  .fw-bold { font-weight: bold; }
  .section-heading { text-align: center; font-weight: bold; font-size: 11px; text-transform: uppercase; margin: 6px 0 4px; letter-spacing: 0.4px; }
  .payment-box { border: 1px solid #047857; background: #f0fdf4; padding: 7px 10px; border-radius: 4px; margin: 8px 0; font-size: 10px; }
  .payment-box.partial { border-color: #b45309; background: #fffbeb; }
  .dates-table { width: 100%; border-collapse: collapse; margin: 6px 0; }
  .dates-table td { border: none; padding: 2px 4px; font-size: 10.5px; }
  .note-block { margin-top: 8px; font-size: 10px; }
  .conforme-block { margin-top: 16px; font-size: 10.5px; }
  .conforme-name { text-align: center; margin-top: 18px; font-weight: bold; text-transform: uppercase; }
  .conforme-role { text-align: center; font-size: 10px; color: #333; }
</style>
</head>
<body>
<div class="statement-box">
  <div class="status-mark">${statusLabel}</div>
  <div class="corp-header">
    <h1>Mayon Builders Realty &amp; Development Corporation</h1>
    <p>Zone 1, Tagbong, Pili, Camarines Sur</p>
    <p>STATEMENT OF ACCOUNT</p>
    <div class="bill-type">ELECTRICITY BILL</div>
  </div>
  <div class="customer-info">
    <p><strong>Name:</strong> ${resident}</p>
    <p><strong>Address:</strong> ${addressLine}</p>
  </div>
  <div class="divider-solid"></div>
  <div class="notice-title">
    <h2>BILLING NOTICE</h2>
    <p>${billingMonthYear}</p>
  </div>
  <div class="divider"></div>
  <table class="clean-table">
    <thead>
      <tr>
        <th style="width:40%;"></th>
        <th style="width:32%;text-align:center;">Date</th>
        <th style="width:28%;text-align:right;">Reading</th>
      </tr>
    </thead>
    <tbody>
      <tr><td class="fw-bold">Previous</td><td class="text-center">${prevDate}</td><td class="text-right">${fmt(prevReading)}</td></tr>
      <tr><td class="fw-bold">Present</td><td class="text-center">${currDate}</td><td class="text-right">${fmt(currReading)}</td></tr>
      <tr><td>Consumption</td><td class="text-center">:</td><td class="text-right fw-bold">${fmt(usageKwh)} kWh</td></tr>
    </tbody>
  </table>
  <div class="divider"></div>
  <div class="section-heading">SUMMARY OF CHARGES</div>
  <table class="clean-table">
    <thead>
      <tr>
        <th style="width:46%;">Description</th>
        <th style="width:29%;text-align:center;">Computation</th>
        <th style="width:25%;text-align:right;">Amount</th>
      </tr>
    </thead>
    <tbody>
      <tr><td>Consumption</td><td class="text-center">${fmt(usageKwh)} x ${fmt(rate)}</td><td class="text-right">₱${fmt(currentCharges)}</td></tr>
      <tr><td>Previous Unpaid Bill</td><td class="text-center">Arrears</td><td class="text-right">₱${fmt(prevBalance)}</td></tr>
      <tr><td>Late Payment Bill</td><td class="text-center">${penaltyRate}%</td><td class="text-right">₱${fmt(penaltyAmount)}</td></tr>
    </tbody>
  </table>
  <div class="divider"></div>
  <table class="clean-table">
    <tbody>
      <tr><td class="fw-bold" style="width:68%;">Amount Before ${dueDate}</td><td class="text-right fw-bold" style="width:32%;">₱${fmt(amountBeforeDue)}</td></tr>
      <tr><td class="fw-bold">Amount After ${dueDate}</td><td class="text-right fw-bold">₱${fmt(amountAfterDue)}</td></tr>
    </tbody>
  </table>
  ${paymentBoxHtml}
  <table class="dates-table">
    <tr><td style="width:35%;">Date Issued:</td><td class="text-right" style="width:65%;">${issuedDate}</td></tr>
    <tr><td>Due Date:</td><td class="text-right fw-bold">${dueDate}</td></tr>
  </table>
  <div class="note-block">
    <strong>Note:</strong><br>
    ${isPaid ? `This bill has been <strong>PAID</strong> via <strong>${mop}</strong>. Thank you for your payment!` : 'Please settle your payable on/before the due date. Thank you!'}
  </div>
  <div class="conforme-block">
    <strong>Conforme:</strong>
    <div class="conforme-name">${String(resident).toUpperCase()}</div>
    <div class="conforme-role">Owner</div>
  </div>
</div>
</body>
</html>`;

        const frame = document.getElementById('receiptFrame');
        const receiptModal = document.getElementById('receiptModal');
        receiptModal.style.display = 'flex';
        frame.srcdoc = html;
    }

    async function recordOfficePaymentFromModal(id, resident, totalAmount) {
        const inputAmount = await promptAsync(
            "Record Office Payment", 
            `Enter payment amount received from ${resident}:`, 
            totalAmount
        );
        if (inputAmount === null || inputAmount === "") return;
        const amountNum = parseFloat(inputAmount);
        if (isNaN(amountNum) || amountNum <= 0) {
            Swal.fire("Invalid amount.");
            return;
        }

        const actionContainer = document.getElementById('modalPaymentAction');
        if (actionContainer) {
            const buttons = actionContainer.querySelectorAll('button');
            buttons.forEach(btn => btn.disabled = true);
        }

        try {
            const res = await fetch('/admin/api/billing/pay', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ id: id, amount: amountNum })
            });
            const data = await res.json();
            if (data.success) {
                if (actionContainer) {
                    actionContainer.innerHTML = '';
                }
                const badge = document.getElementById('modalPaymentBadge');
                if (badge) {
                    badge.className = 'badge badge-success';
                    badge.textContent = 'Paid';
                }
                
                await Swal.fire({
                    icon: 'success',
                    title: 'Payment Recorded',
                    text: `Payment of ₱${amountNum.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} recorded for ${resident}.`,
                    confirmButtonColor: '#059669'
                });
                window.location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Payment Failed',
                    text: data.message || 'Payment recording failed.'
                });
                if(actionContainer) {
                    const buttons = actionContainer.querySelectorAll('button');
                    buttons.forEach(btn => btn.disabled = false);
                }
            }
        } catch(e) {
            console.error(e);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while recording the payment.'
            });
            if(actionContainer) {
                const buttons = actionContainer.querySelectorAll('button');
                buttons.forEach(btn => btn.disabled = false);
            }
        }
    }

    async function addPreviousBalanceFromModal(id, resident) {
        const inputBalance = await promptAsync(
            "Add Unpaid Balance", 
            `Enter Previous Unpaid Balance to add for ${resident}:`, 
            0
        );
        if (inputBalance === null || inputBalance === "") return;
        const balanceNum = parseFloat(inputBalance);
        if (isNaN(balanceNum) || balanceNum <= 0) {
            Swal.fire("Invalid balance amount.");
            return;
        }
        
        try {
            const res = await fetch('/admin/api/billing/add-balance', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ id: id, balance: balanceNum })
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire(`Balance of ₱${balanceNum} added successfully.`);
                let bill = allBillsRaw.find(b => String(b.id).trim() === String(id).trim() || String(b.db_id).trim() === String(id).trim());
                if (bill) {
                    bill.previous_balance = (parseFloat(bill.previous_balance) || 0) + balanceNum;
                    const row = document.querySelector(`.bill-row .dynamic-amount-cell[data-id="${id}"]`) || document.querySelector(`.bill-row[data-resident="${bill.resident.toLowerCase()}"] .dynamic-amount-cell`);
                    if (row) {
                        row.dataset.previousBalance = bill.previous_balance;
                        recalculateLedger();
                    }
                    viewDetail(id);
                } else {
                    location.reload();
                }
            } else {
                Swal.fire('Failed to add balance.');
            }
        } catch (e) {
            console.error(e);
            Swal.fire('Error adding balance.');
        }
    }



    function applyFilters() {
        const search = document.getElementById('billingSearch').value.toLowerCase();
        const status = document.getElementById('statusFilter').value;
        const trend = document.getElementById('behaviorFilter').value;
        const block = document.getElementById('blockFilter').value;
        const connFilter = document.getElementById('connectionFilter') ? document.getElementById('connectionFilter').value : 'all';
        const rows = document.querySelectorAll('.bill-row');

        rows.forEach(row => {
            const res = row.dataset.resident;
            const s = row.dataset.status;
            const t = row.dataset.behavior || row.dataset.trend;
            const b = row.dataset.block;
            const c = row.dataset.connection || 'internal';

            const matchesSearch = res.includes(search);
            const matchesStatus = status === 'all' || s === status;
            const matchesTrend = trend === 'all' || t === trend;
            const matchesBlock = block === 'all' || b === block;
            const matchesConn = connFilter === 'all' || c === connFilter;

            row.classList.toggle('pagination-match', matchesSearch && matchesStatus && matchesTrend && matchesBlock && matchesConn);
        });

        window.billingTablePagination?.resetPage();
    }

    document.addEventListener('DOMContentLoaded', function() {
        window.billingTablePagination = initTablePagination({
            rowSelector: '.bill-row',
            infoEl: '#billingTablePageInfo',
            prevEl: '#billingTablePagePrev',
            nextEl: '#billingTablePageNext',
            perPage: 15,
        });

        ['billingSearch', 'statusFilter', 'blockFilter', 'behaviorFilter', 'connectionFilter'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', applyFilters);
            }
        });

        applyFilters();
        recalculateLedger();

        initSalesChart();
    });

    function initSalesChart() {
        const canvas = document.getElementById('electricitySalesChart');
        const ctx = canvas?.getContext('2d');
        if (!ctx) return;

        if (window.salesChart && typeof window.salesChart.destroy === 'function') {
            window.salesChart.destroy();
        }

        @php
            $chartData = getMonthlyChartData('electricity');
        @endphp

        const labels = @json($chartData['labels']);
        const values = @json($chartData['data']);

        const currentGrad = ctx.createLinearGradient(0, 0, 0, 220);
        currentGrad.addColorStop(0, '#4f46e5');
        currentGrad.addColorStop(1, '#818cf8');

        const priorGrad = ctx.createLinearGradient(0, 0, 0, 220);
        priorGrad.addColorStop(0, '#0284c7');
        priorGrad.addColorStop(1, '#7dd3fc');

        const bgColors = values.map((_, idx) => idx === values.length - 1 ? currentGrad : priorGrad);

        window.salesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Electricity Collection (₱)',
                    data: values,
                    backgroundColor: bgColors,
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 38
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { size: 12, weight: '700' },
                        bodyFont: { size: 12, weight: '600' },
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: ₱' + Number(context.raw || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grace: '12%',
                        border: { display: false },
                        ticks: {
                            callback: function(value) {
                                return value >= 1000 ? '₱' + (value / 1000).toLocaleString() + 'k' : '₱' + value;
                            },
                            font: { weight: '600', size: 11 },
                            color: '#64748b'
                        },
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        border: { display: false },
                        ticks: { font: { weight: '600', size: 11 }, color: '#64748b' },
                        grid: { display: false }
                    }
                }
            }
        });
    }
    window.addEventListener('DOMContentLoaded', () => {
        const params = new URLSearchParams(window.location.search);
        if (params.get('action') === 'settings') {
            openSettingsModal();
        } else if (params.get('action') === 'reset') {
            resetBillingCycle();
        }
    });

    function fetchCycle(cycle) {
        document.querySelector('.bill-container').style.opacity = '0.5';
        fetch('?cycle=' + cycle)
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                window.history.pushState({}, '', '?cycle=' + cycle);
                
                // Replace everything except the script tags at the bottom.
                document.querySelector('.bill-container').innerHTML = doc.querySelector('.bill-container').innerHTML;
                document.querySelector('.bill-container').style.opacity = '1';

                const jsonEl = document.getElementById('cycle-bills-json');
                if (jsonEl) {
                    try {
                        allBillsRaw = JSON.parse(jsonEl.textContent);
                    } catch (e) {
                        console.error('Failed to parse cycle bills JSON:', e);
                    }
                }

                window.billingTablePagination = initTablePagination({
                    rowSelector: '.bill-row',
                    infoEl: '#billingTablePageInfo',
                    prevEl: '#billingTablePagePrev',
                    nextEl: '#billingTablePageNext',
                    perPage: 15,
                });
                
                // reattach listeners
                ['billingSearch', 'statusFilter', 'blockFilter', 'behaviorFilter', 'connectionFilter'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', applyFilters);
                    }
                });
                initSalesChart();
                applyFilters();
                recalculateLedger();
            })
            .catch(err => {
                console.error(err);
                document.querySelector('.bill-container').style.opacity = '1';
            });
    }

    function openBillingReportModal() {
        const currentStatus = document.getElementById('statusFilter')?.value || 'all';
        if (document.getElementById('reportBillingStatusFilter')) {
            document.getElementById('reportBillingStatusFilter').value = currentStatus;
        }
        reloadBillingReportPdf();
        document.getElementById('billingReportModal').style.display = 'flex';
    }

    function closeBillingReportModal() {
        document.getElementById('billingReportModal').style.display = 'none';
    }

    function reloadBillingReportPdf() {
        const cycle = "{{ request('cycle', '') }}";
        const status = document.getElementById('reportBillingStatusFilter')?.value || 'all';
        const sort = document.getElementById('reportBillingSortFilter')?.value || 'name_asc';
        const base = "{{ route('admin.billing.report.pdf') }}";
        const qs = `?cycle=${encodeURIComponent(cycle)}&status=${encodeURIComponent(status)}&sort=${encodeURIComponent(sort)}`;

        const frame = document.getElementById('billingReportPdfFrame');
        if (frame) {
            frame.src = base + qs;
        }
        const dlBtn = document.getElementById('billingReportDownloadBtn');
        if (dlBtn) {
            dlBtn.href = base + qs + '&download=1';
        }
    }

    function printBillingReportPdfFrame() {
        const frame = document.getElementById('billingReportPdfFrame');
        if (frame && frame.contentWindow) {
            frame.contentWindow.focus();
            frame.contentWindow.print();
        }
    }
</script>

<!-- Official Electricity Billing PDF Report Modal -->
<div id="billingReportModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 2100; background: rgba(15,23,42,0.7); backdrop-filter: blur(8px); padding: 20px;">
    <div class="bill-modal-content" style="max-width: 1100px; width: 95%; max-height: 94vh; overflow: hidden; padding: 20px 24px; border-radius: 20px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); display: flex; flex-direction: column;">
        
        <!-- Controls Bar -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Status</span>
                    <select id="reportBillingStatusFilter" class="filter-select" onchange="reloadBillingReportPdf()" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer;">
                        <option value="all">All Statuses</option>
                        <option value="paid">Paid Only</option>
                        <option value="partially_paid">Partially Paid</option>
                        <option value="pending">Pending</option>
                        <option value="overdue">Overdue / Past Due</option>
                    </select>
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Sort By</span>
                    <select id="reportBillingSortFilter" class="filter-select" onchange="reloadBillingReportPdf()" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer;">
                        <option value="name_asc" selected>Alphabetical: Resident (A to Z)</option>
                        <option value="name_desc">Alphabetical: Resident (Z to A)</option>
                        <option value="amount_desc">Billed Amount: Highest First</option>
                        <option value="amount_asc">Billed Amount: Lowest First</option>
                        <option value="usage_desc">Consumption (kWh): Highest First</option>
                        <option value="usage_asc">Consumption (kWh): Lowest First</option>
                        <option value="due_asc">Due Date: Earliest to Latest</option>
                        <option value="due_desc">Due Date: Latest to Earliest</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
                <a id="billingReportDownloadBtn" href="{{ route('admin.billing.report.pdf') }}?download=1" class="btn btn-primary" style="background: #0284c7; border: none; padding: 8px 16px; font-size: 12px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; color: #fff;">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download PDF
                </a>
                <button type="button" class="btn btn-outline" onclick="printBillingReportPdfFrame()" style="background: #fff; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #334155;">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print
                </button>
                <button type="button" class="btn btn-outline" onclick="closeBillingReportModal()" style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 600; border-radius: 8px; cursor: pointer;">
                    ✕ Close
                </button>
            </div>
        </div>

        <!-- Embedded Real PDF Viewer (Viewable in-page, no new tab) -->
        <div style="flex: 1; min-height: 0; width: 100%;">
            <iframe id="billingReportPdfFrame" src="{{ route('admin.billing.report.pdf') }}" style="width: 100%; height: 75vh; border: 1px solid #e2e8f0; border-radius: 10px; background: #525659;"></iframe>
        </div>

    </div>
</div>
@endsection
