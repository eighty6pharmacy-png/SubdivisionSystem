@extends('layouts.admin')

@section('title', 'Water Billing')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">

@php
    $latestBill = !empty($bills) ? collect($bills)->first() : null;
    $activePrevDate = $latestBill['prev_reading_date'] ?? 'N/A';
    $activePrevDateRaw = $latestBill['prev_reading_date_raw'] ?? '';
    $activeCurrDate = $latestBill['curr_reading_date'] ?? 'N/A';
    $activeCurrDateRaw = $latestBill['curr_reading_date_raw'] ?? '';
    $activeDueRaw = $latestBill['due'] ?? '';
    $activeDueDate = ($activeDueRaw && $activeDueRaw !== 'N/A') ? \Carbon\Carbon::parse($activeDueRaw)->format('M d, Y') : 'N/A';

    $currentCycleKey = request('cycle');
    $currentCycleObj = collect($validCycles)->firstWhere('cycle', $currentCycleKey) ?? (collect($validCycles)->first() ?? null);
    $activeCycleLabel = $currentCycleObj['label'] ?? ($currentCycleKey ?: 'Current Active Cycle');

    $waterMinM3 = (float)(\App\Models\Setting::find('water_min_m3')?->value ?? 10);
    $waterMinRate = (float)(\App\Models\Setting::find('water_min_rate')?->value ?? 250);
    $waterRate = (float)(\App\Models\Setting::find('water_rate')?->value ?? 30);
    $waterPenalty = (float)(\App\Models\Setting::find('water_penalty')?->value ?? 5);

    $totalBills = count($bills);
    $paidBills  = collect($bills)->where('status', 'paid')->count();
    $unpaidBills = $totalBills - $paidBills;
    $totalBilled    = collect($bills)->sum('amount');
    $totalCollected = collect($bills)->where('status', 'paid')->sum('amount');
    $totalBalanceRemaining = collect($bills)->where('status', '!=', 'paid')->sum('amount');
    $cerPct = $totalBilled > 0 ? round(($totalCollected / $totalBilled) * 100) : 0;
    $circumference = 2 * M_PI * 52;
    $cerStroke = ($cerPct / 100) * $circumference;

    // Simulated water master meter: ~6% more than billed (water loss)
    $billedUsageCbm  = collect($bills)->sum('usage_cbm');
    $masterUsageCbm  = $billedUsageCbm * 1.06;
    $systemLossPct   = $masterUsageCbm > 0 ? round((($masterUsageCbm - $billedUsageCbm) / $masterUsageCbm) * 100, 1) : 0;
    $billedFillPct   = 100 - $systemLossPct;
@endphp

<div class="bill-container fade-in">
    <div class="bill-header">
        <div class="bill-title">
            <h1 style="font-size: 24px; font-weight: 800; color: #0f172a; margin: 0;">Water Management & Analytics</h1>
            <p style="font-size: 13px; color: #64748b; margin-top: 4px;">12-month consumption analysis, payment channel management, and predictive delinquency tracking.</p>
        </div>
        <div class="bill-actions">
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
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                    💧
                </div>
                <div>
                    <span style="font-size: 14px; font-weight: 800; color: #0f172a;">Active Cycle Parameters</span>
                    <span style="font-size: 11px; color: #64748b; margin-left: 6px;">Current reading window & applied utility rates</span>
                </div>
                <span style="background: #e0f2fe; color: #0369a1; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 6px; margin-left: 4px;">{{ $activeCycleLabel }}</span>
            </div>
            <button class="btn btn-outline" style="border-color: #0284c7; color: #0284c7; font-size: 11px; padding: 5px 12px; font-weight: 700; border-radius: 6px;" onclick="openAdjustCycleModal()">
                ⚙️ Adjust Current Cycle
            </button>
        </div>

        <!-- Structured 5-Column Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
                <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; display: flex; align-items: center; gap: 5px;">
                    <i class="fas fa-calendar-alt" style="color: #0284c7;"></i> Reading Window
                </div>
                <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $activePrevDate }} – {{ $activeCurrDate }}">
                    {{ $activePrevDate }} &rarr; {{ $activeCurrDate }}
                </div>
                <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">Consumption period</div>
            </div>

            <div style="background: #fef2f2; border: 1px solid #fee2e2; border-radius: 10px; padding: 10px 12px;">
                <div style="font-size: 10px; font-weight: 800; color: #991b1b; text-transform: uppercase; letter-spacing: 0.04em; display: flex; align-items: center; gap: 5px;">
                    <i class="fas fa-clock" style="color: #ef4444;"></i> Due Date
                </div>
                <div style="font-size: 14px; font-weight: 800; color: #dc2626; margin-top: 4px;">
                    {{ $activeDueDate }}
                </div>
                <div style="font-size: 10px; color: #b91c1c; margin-top: 2px;">Before late penalty</div>
            </div>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
                <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; display: flex; align-items: center; gap: 5px;">
                    <i class="fas fa-tint" style="color: #0284c7;"></i> Excess Rate
                </div>
                <div style="font-size: 14px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                    ₱{{ number_format($waterRate, 2) }} <span style="font-size: 11px; font-weight: 600; color: #64748b;">/ m³</span>
                </div>
                <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">Beyond minimum tier</div>
            </div>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
                <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; display: flex; align-items: center; gap: 5px;">
                    <i class="fas fa-layer-group" style="color: #0284c7;"></i> Minimum Tier
                </div>
                <div style="font-size: 14px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                    ₱{{ number_format($waterMinRate, 2) }}
                </div>
                <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">Covers first {{ $waterMinM3 }} m³</div>
            </div>

            <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 10px; padding: 10px 12px;">
                <div style="font-size: 10px; font-weight: 800; color: #92400e; text-transform: uppercase; letter-spacing: 0.04em; display: flex; align-items: center; gap: 5px;">
                    <i class="fas fa-exclamation-triangle" style="color: #d97706;"></i> Late Penalty
                </div>
                <div style="font-size: 14px; font-weight: 800; color: #b45309; margin-top: 4px;">
                    {{ $waterPenalty }}%
                </div>
                <div style="font-size: 10px; color: #d97706; margin-top: 2px;">Past due surcharge</div>
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
            <div style="background: #fffbeb; padding: 12px 14px; border-radius: 10px; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b;">
                <div style="font-size: 11px; color: #92400e; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;">Total Balance to Collect</div>
                <div id="sumBalanceToCollect" style="font-size: 24px; font-weight: 800; color: #b45309; margin-top: 2px;">₱{{ number_format($totalBilled, 2) }}</div>
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

    <!-- Monthly Water Sales & Collection Trend Bar Graph -->
    <div style="background: #fff; border: 1px solid var(--bill-border); border-radius: 16px; padding: 16px 20px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <div>
                <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;">Monthly Water Revenue Trends</h3>
                <p style="font-size: 11px; color: #64748b; margin: 2px 0 0 0;">12-month collection performance (in ₱ Thousands)</p>
            </div>
        </div>
        <div style="height: 180px; position: relative;">
            <canvas id="waterSalesChart"></canvas>
        </div>
    </div>

    <!-- Analytics Dashboard -->
    <div class="analytics-grid">

        <!-- Card 1: Payment Behaviour + Methods -->
        <div class="analytic-card">
            <div class="card-title">
                <span>Payment Behaviour</span>
                <span style="font-size:11px; color:#94a3b8; font-weight:600;">Current Cycle</span>
            </div>
            <div class="donut-chart-container">
                @php
                    $latePct = $stats['percentages']['Late / At Risk'] ?? 0;
                    $onTimePct = $stats['percentages']['On-Time'] ?? 0;
                    $earlyPct = $stats['percentages']['Early Payers'] ?? 0;
                    $onTimeOffset = $latePct;
                    $earlyOffset = $latePct + $onTimePct;
                @endphp
                <svg width="200" height="200" viewBox="0 0 42 42" class="donut-svg">
                    <circle class="donut-segment segment-late"   cx="21" cy="21" r="15.915" stroke-dasharray="{{ $latePct }} {{ 100 - $latePct }}"></circle>
                    <circle class="donut-segment segment-ontime" cx="21" cy="21" r="15.915" stroke-dasharray="{{ $onTimePct }} {{ 100 - $onTimePct }}" stroke-dashoffset="-{{ $onTimeOffset }}"></circle>
                    <circle class="donut-segment segment-early"  cx="21" cy="21" r="15.915" stroke-dasharray="{{ $earlyPct }} {{ 100 - $earlyPct }}" stroke-dashoffset="-{{ $earlyOffset }}"></circle>
                </svg>
                <div class="donut-legend">
                    <div class="legend-item">
                        <span class="legend-label"><span class="dot" style="background: var(--bill-success);"></span> Early Payers</span>
                        <strong>{{ $earlyPct }}%</strong>
                    </div>
                    <div class="legend-item">
                        <span class="legend-label"><span class="dot" style="background: var(--bill-primary);"></span> On-Time</span>
                        <strong>{{ $onTimePct }}%</strong>
                    </div>
                    <div class="legend-item">
                        <span class="legend-label"><span class="dot" style="background: var(--bill-warning);"></span> Late</span>
                        <strong>{{ $latePct }}%</strong>
                    </div>
                </div>
            </div>

        </div>
        <!-- Card 2: Collection Efficiency Ratio -->
        <div class="analytic-card">
            <div class="card-title">
                <span>Collection Efficiency</span>
            </div>
            <div class="cer-gauge-wrap">
                <div class="cer-gauge-ring">
                    <svg width="200" height="200" viewBox="0 0 120 120">
                        <circle class="gauge-bg" cx="60" cy="60" r="52"></circle>
                        <circle class="gauge-fill" cx="60" cy="60" r="52"
                            stroke-dasharray="{{ round($cerStroke, 2) }} {{ round($circumference, 2) }}"
                            stroke-dashoffset="0"></circle>
                    </svg>
                    <div class="cer-gauge-label">
                        {{ $cerPct }}%
                        <span>CER</span>
                    </div>
                </div>
                <div class="cer-meta">
                    <div class="cer-meta-row">
                        <span>Total Billed</span>
                        <strong>₱{{ number_format($totalBilled, 2) }}</strong>
                    </div>
                    <div class="cer-meta-row">
                        <span>Total Collected</span>
                        <strong style="color: var(--bill-success);">₱{{ number_format($totalCollected, 2) }}</strong>
                    </div>
                    <div class="cer-meta-row">
                        <span>Outstanding</span>
                        <strong style="color: var(--bill-danger);">₱{{ number_format($totalBilled - $totalCollected, 2) }}</strong>
                    </div>
                    <div style="height: 1px; background: var(--bill-border); margin: 4px 0;"></div>
                    <div class="cer-meta-row">
                        <span>Accounts Paid</span>
                        <strong>{{ $paidBills }} / {{ $totalBills }}</strong>
                    </div>
                </div>
            </div>
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
                <option value="unpaid">Unpaid</option>
            </select>
        </div>
        <div class="filter-group">
            <span class="filter-label">Block</span>
            <select id="blockFilter" class="filter-select">
                <option value="all">All Blocks</option>
                @php
                    $uniqueBlocks = collect($bills)->pluck('block')->filter(function($b) { return $b !== 'N/A'; })->unique()->sort();
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
        <div style="margin-left: auto;">
            <button class="btn btn-primary" onclick="applyFilters()">Apply Filters</button>
        </div>
    </div>

    <!-- Predictive Billing Ledger -->
    <div class="analytic-card">
        <div class="card-title">
            <span>Water Billing Ledger</span>
        </div>
        @if(count($bills) == 0)
            <div style="text-align: center; padding: 60px 20px; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1; margin-top: 20px;">
                <div style="font-size: 48px; margin-bottom: 16px;">📂</div>
                <h3 style="font-size: 20px; color: #0f172a; margin-bottom: 8px;">The Ledger is Blank</h3>
                <p style="color: #64748b; font-size: 14px;">The system is new or no cycle has been generated yet.<br>Please click <strong>"New Cycle"</strong> at the top to initialize the ledger and record readings.</p>
            </div>
        @else
        <div class="bill-table-container">
            <table class="bill-table" id="billingTable">
                <thead>
                    <tr>
                        <th>Resident / Lot</th>
                        <th>Amount</th>
                        <th>Payment Behavior</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bills as $bill)
                    <tr class="bill-row" data-resident="{{ strtolower($bill['resident']) }}" data-status="{{ $bill['status'] }}" data-behavior="{{ $bill['payment_behavior'] ?? 'Not available yet' }}" data-block="{{ $bill['block'] }}">
                        <td>
                            <div style="font-weight: 700;">{{ $bill['resident'] }}</div>
                            <div style="font-size: 12px; color: #64748b;">{{ $bill['lot'] }}</div>
                        </td>
                        <!-- Dynamic Amounts for JS recalculation -->
                        <td class="dynamic-amount-cell" 
                            data-usage-cubic="{{ $bill['usage_m3'] }}" 
                            data-previous-balance="{{ $bill['previous_balance'] }}"
                            data-at-risk="{{ $bill['at_risk'] ? 'true' : 'false' }}" 
                            data-status="{{ $bill['status'] }}"
                            style="font-weight: 700;">
                            ₱{{ number_format($bill['amount'], 2) }}
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
                            @else
                                <span class="badge badge-danger">Unpaid</span>
                                <div style="font-size: 10px; color: var(--bill-danger); margin-top: 4px;">Due: {{ $bill['due'] }}</div>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <!-- Ensure we pass usage, date logic, and audit log -->
                                <button class="btn btn-outline" style="padding: 6px 10px; font-size: 11px;" onclick="viewDetail('{{ $bill['db_id'] ?? $bill['id'] }}')">View</button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('partials.admin-client-pagination', ['prefix' => 'waterTable'])
        @endif
    </div>
</div>

<!-- Full Screen Immersive Modal -->
<div id="billModal" class="bill-modal">
    <div class="bill-modal-content">
        <div class="modal-header">
            <div>
                <span id="modalLot" style="font-size: 12px; font-weight: 700; color: var(--bill-primary); text-transform: uppercase; letter-spacing: 0.1em;">BLOCK 1 LOT 5</span>
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
                            <div id="modalUsage" style="font-size: 28px; font-weight: 800; color: var(--bill-primary); margin-top: 4px;">150 m³</div>
                        </div>
                    </div>
                
                    <!-- Financial Breakdown -->
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                        <h4 style="font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 16px; margin-top: 0; letter-spacing: 0.05em;">Financial Breakdown</h4>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span style="font-size: 14px; color: #475569;">Current Bill (Minimum) <span id="modalMinBillSub" style="font-size: 12px; color: #94a3b8; margin-left: 4px;">First 10m³</span></span>
                            <span id="modalMinBill" style="font-size: 15px; font-weight: 700; color: #0f172a;">₱0.00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span style="font-size: 14px; color: #475569;">Excess Consumption <span id="modalExcessSub" style="font-size: 12px; color: #94a3b8; margin-left: 4px;"></span></span>
                            <span id="modalExcessAmount" style="font-size: 15px; font-weight: 700; color: #0f172a;">₱0.00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span style="font-size: 14px; color: #475569;">Previous Unpaid Bill (Arrears)</span>
                            <span id="modalPrevBalance" style="font-size: 15px; font-weight: 700; color: #0f172a;">₱0.00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span style="font-size: 14px; color: #475569;">Late Payment Penalty (5%)</span>
                            <span id="modalPenalty" style="font-size: 15px; font-weight: 700; color: #f59e0b;">₱0.00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span id="modalAmountBeforeLabel" style="font-size: 14px; color: #475569;">Amount Before</span>
                            <span id="modalAmountBefore" style="font-size: 15px; font-weight: 700; color: #0f172a;">₱1,250.50</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span id="modalAmountAfterLabel" style="font-size: 14px; color: #475569;">Amount After</span>
                            <span id="modalAmountAfter" style="font-size: 15px; font-weight: 700; color: #0f172a;">₱1,313.02</span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 2px dashed #e2e8f0;">
                            <span style="font-size: 14px; color: #475569;">Total Paid</span>
                            <span id="modalTotalPaid" style="font-size: 15px; font-weight: 800; color: #10b981;">₱0.00</span>
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
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Preview Modal -->
<div id="receiptModal" style="display:none; position:fixed; inset:0; z-index:20000; background:rgba(15,23,42,0.75); backdrop-filter:blur(6px); align-items:center; justify-content:center; padding:24px; box-sizing:border-box;">
    <div style="background:#fff; max-width:680px; width:100%; border-radius:16px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.35); overflow:hidden; display:flex; flex-direction:column; height:90vh; max-height:860px;">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc;">
            <span id="receiptModalTitle" style="font-size:14px; font-weight:800; color:#0f172a; text-transform:uppercase; letter-spacing:0.05em;">Statement of Account — Water</span>
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
                <h3 id="newCycleModalTitle" style="margin: 0; font-size: 17px; font-weight: 800; color: #0f172a;">Create New Water Billing Cycle</h3>
                <p id="newCycleModalSubtitle" style="margin: 2px 0 0 0; font-size: 12px; color: #64748b;">Smart date projection with automated arrears calculation</p>
            </div>
        </div>
        <div class="reset-cycle-modal-body">
            <!-- Dynamic Automation & Readiness Card -->
            <div id="cycleAutomationStatusCard" style="display: none; border-radius: 12px; padding: 12px 14px; margin-bottom: 16px; font-size: 12px; line-height: 1.45;"></div>

            <!-- Current Cycle Reference Card -->
            <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #0369a1; letter-spacing: 0.05em;">Current Settings Reference</span>
                    <span style="font-size: 11px; background: #e0f2fe; color: #0369a1; font-weight: 700; padding: 1px 8px; border-radius: 9999px;">{{ $activeCycleLabel }}</span>
                </div>
                <div style="font-size: 12px; color: #334155; display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                    <div><strong>Period:</strong> {{ $activePrevDate }} &rarr; {{ $activeCurrDate }}</div>
                    <div><strong>Due Date:</strong> <span style="color: #dc2626; font-weight: 600;">{{ $activeDueDate }}</span></div>
                    <div><strong>Excess Rate:</strong> ₱{{ number_format($waterRate, 2) }}/m³</div>
                    <div><strong>Min Tier:</strong> ₱{{ number_format($waterMinRate, 2) }} ({{ $waterMinM3 }} m³)</div>
                    <div><strong>Penalty:</strong> {{ $waterPenalty }}%</div>
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

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 6px;">Min Usage (m³)</label>
                    <input type="number" id="newCycleMinM3" class="filter-select" value="{{ $waterMinM3 }}" style="width: 100%; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 6px;">Min Rate (₱)</label>
                    <input type="number" id="newCycleMinRate" class="filter-select" value="{{ $waterMinRate }}" style="width: 100%; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 6px;">Excess Rate (₱)</label>
                    <input type="number" id="newCycleRate" class="filter-select" value="{{ $waterRate }}" style="width: 100%; box-sizing: border-box;">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr; gap: 16px; margin-bottom: 8px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 6px;">Penalty Past Due (%)</label>
                    <input type="number" id="newCyclePenalty" class="filter-select" value="{{ $waterPenalty }}" style="width: 100%; box-sizing: border-box;">
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
    <div class="bill-modal-content reset-cycle-modal" style="max-width: 520px;">
        <div class="reset-cycle-modal-header" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 14px;">
            <div class="reset-cycle-modal-icon" style="background: #e0f2fe; color: #0284c7;">
                ⚙️
            </div>
            <div>
                <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: #0f172a;">Adjust Current Cycle Parameters</h3>
                <p style="margin: 2px 0 0 0; font-size: 12px; color: #64748b;">Update reading window, due date, and rates for {{ $activeCycleLabel }}</p>
            </div>
        </div>
        <div class="reset-cycle-modal-body" style="padding-top: 16px;">
            <div style="background: #e0f2fe; border: 1px solid #bae6fd; border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; font-size: 12px; color: #0369a1; display: flex; align-items: center; gap: 8px;">
                <span>ℹ️</span>
                <span>Modifying these values will update the <strong>{{ $activeCycleLabel }}</strong> cycle and recalculate pending water bills.</span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
                <div style="background: #f8fafc; padding: 10px 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px; text-transform: uppercase;"><i class="fas fa-calendar-alt" style="margin-right: 6px; color: #0284c7;"></i>Previous Reading Date</label>
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
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 4px; text-transform: uppercase;">Excess Rate (₱/m³)</label>
                    <input type="number" step="0.01" id="adjCycleRate" class="filter-select" value="{{ $waterRate }}" style="width: 100%; box-sizing: border-box; background: white;">
                </div>
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 4px; text-transform: uppercase;">Minimum Tier (m³)</label>
                    <input type="number" step="1" id="adjCycleMinM3" class="filter-select" value="{{ $waterMinM3 }}" style="width: 100%; box-sizing: border-box; background: white;">
                </div>
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 4px; text-transform: uppercase;">Minimum Tier Rate (₱)</label>
                    <input type="number" step="0.01" id="adjCycleMinRate" class="filter-select" value="{{ $waterMinRate }}" style="width: 100%; box-sizing: border-box; background: white;">
                </div>
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 4px; text-transform: uppercase;">Late Penalty (%)</label>
                    <input type="number" step="0.1" id="adjCyclePenalty" class="filter-select" value="{{ $waterPenalty }}" style="width: 100%; box-sizing: border-box; background: white;">
                </div>
            </div>
        </div>

        <div class="reset-cycle-modal-footer" style="border-top: 1px solid #f1f5f9; padding-top: 14px;">
            <button class="btn btn-outline" style="justify-content: center; padding: 10px 16px;" onclick="closeAdjustCycleModal()">Cancel</button>
            <button class="btn btn-primary" style="justify-content: center; padding: 10px 20px; background: #0284c7; border-color: #0284c7;" onclick="executeAdjustCycle()">Save Adjustments</button>
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
        <div style="padding: 24px;">
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
    function promptAsync(title, desc, defaultValue) {
        return new Promise((resolve) => {
            const modal = document.getElementById('genericInputModal');
            modal.style.display = 'flex';
            setTimeout(() => { modal.firstElementChild.style.transform = 'scale(1)'; }, 10);
            document.getElementById('genericInputTitle').textContent = title;
            document.getElementById('genericInputDesc').textContent = desc;
            const input = document.getElementById('genericInputValue');
            input.value = defaultValue || '';
            
            const confirmBtn = document.getElementById('genericInputConfirm');
            
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
                const val = input.value;
                cleanup();
                resolve(val);
            });

            modal.style.display = 'flex';
            setTimeout(() => { modal.firstElementChild.style.transform = 'scale(1)'; }, 10);
            input.focus();
        });
    }
    const statsData = @json($stats);

    // Global Billing Settings
    let currentRate = {{ floatval(\App\Models\Setting::where('key', 'water_rate')->value('value') ?: 30) }};
    let currentPenaltyRate = {{ floatval(\App\Models\Setting::where('key', 'water_penalty')->value('value') ?: 5) }};
    let currentMinM3 = {{ floatval(\App\Models\Setting::where('key', 'water_min_m3')->value('value') ?: 10) }};
    let currentMinRate = {{ floatval(\App\Models\Setting::where('key', 'water_min_rate')->value('value') ?: 250) }};

    // Load Data safely
    const allBillsRaw = @json($bills);


    function recalculateLedger() {
        const amountCells = document.querySelectorAll('.dynamic-amount-cell');
        amountCells.forEach(cell => {
            const usage = parseInt(cell.dataset.usageCubic) || 0;
            const previousBalance = parseFloat(cell.dataset.previousBalance) || 0;
            const isAtRisk = cell.dataset.atRisk === 'true';
            
            let minCharge = currentMinRate;
            let excessCharge = usage > currentMinM3 ? (usage - currentMinM3) * currentRate : 0;
            let amountBefore = minCharge + excessCharge + previousBalance;
            let total = amountBefore;
            if (isAtRisk) {
                total += amountBefore * (currentPenaltyRate / 100);
            }

            // Save the raw active total back into the cell dataset for the summary dashboard to read
            cell.dataset.rawTotal = total;
            cell.innerHTML = `₱${total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
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
            const status = row.dataset.status;
            const cell = row.querySelector('.dynamic-amount-cell') || row.cells[1];
            const amt = parseFloat(cell.dataset.rawTotal || cell.textContent.replace(/[^\d.-]/g, '')) || 0;

            if (status === 'paid') {
                countPaid++;
                totalCollected += amt;
            } else {
                countUnpaid++;
                totalRemaining += amt;
                if (row.querySelector('.trend-late') || row.dataset.trend === 'at-risk') {
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
        
        // Real-time graph update
        if (window.salesChart && window.salesChart.data && window.salesChart.data.datasets.length > 0) {
            const ds = window.salesChart.data.datasets[0];
            if (ds && ds.data) {
                const currentMonthLabel = new Date(monthSel + '-01').toLocaleString('default', { month: 'short' });
                const labelIdx = window.salesChart.data.labels.findIndex(l => l.includes(currentMonthLabel));
                
                if (labelIdx !== -1) {
                    ds.data[labelIdx] = totalCollected;
                } else {
                    ds.data[ds.data.length - 1] = totalCollected;
                }
                window.salesChart.update();
            }
        }

        updatePredictiveDonut();
    }

    function updatePredictiveDonut() {
        const rows = document.querySelectorAll('.bill-row');
        let early = 0, ontime = 0, late = 0;
        let valid = 0;

        rows.forEach(row => {
            const behavior = row.dataset.behavior;
            if (behavior === 'Early') early++;
            if (behavior === 'On-Time') ontime++;
            if (behavior === 'Late') late++;
            if (['Early','On-Time','Late'].includes(behavior)) valid++;
        });
        
        let total = valid;

        const pEarly = total > 0 ? Math.round((early / total) * 100) : 0;
        const pOntime = total > 0 ? Math.round((ontime / total) * 100) : 0;
        const pLate = total > 0 ? Math.round((late / total) * 100) : 0;

        const donutSvg = document.querySelector('.donut-svg');
        if (donutSvg) {
            if (total === 0) {
                donutSvg.innerHTML = `
                    <circle class="donut-segment" cx="21" cy="21" r="15.915" stroke-dasharray="100 0" stroke="#e2e8f0"></circle>
                `;
            } else {
                donutSvg.innerHTML = `
                    <circle class="donut-segment segment-late" cx="21" cy="21" r="15.915" stroke-dasharray="${pLate} ${100-pLate}"></circle>
                    <circle class="donut-segment segment-ontime" cx="21" cy="21" r="15.915" stroke-dasharray="${pOntime} ${100-pOntime}" stroke-dashoffset="-${pLate}"></circle>
                    <circle class="donut-segment segment-early" cx="21" cy="21" r="15.915" stroke-dasharray="${pEarly} ${100-pEarly}" stroke-dashoffset="-${pLate + pOntime}"></circle>
                `;
            }
            
            const legend = document.querySelector('.donut-legend');
            if (total === 0) {
                legend.innerHTML = `
                    <div class="legend-item" style="justify-content: center; color: #64748b;">No behavioral data yet.</div>
                `;
            } else {
                legend.innerHTML = `
                    <div class="legend-item"><span class="legend-label"><span class="dot" style="background: var(--bill-success);"></span> Early Payers</span><strong>${pEarly}%</strong></div>
                    <div class="legend-item"><span class="legend-label"><span class="dot" style="background: var(--bill-primary);"></span> On-Time</span><strong>${pOntime}%</strong></div>
                    <div class="legend-item"><span class="legend-label"><span class="dot" style="background: var(--bill-warning);"></span> Late</span><strong>${pLate}%</strong></div>
                `;
            }
        }
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
        document.getElementById('newCycleRate').value = currentRate;
        document.getElementById('newCyclePenalty').value = currentPenaltyRate;
        document.getElementById('newCycleMinM3').value = currentMinM3;
        document.getElementById('newCycleMinRate').value = currentMinRate;

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
            const res = await fetch('/admin/api/billing/cycle-status?type=water');
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
            console.error('Failed to fetch water cycle status:', e);
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
                statusCard.innerHTML = `<strong>ℹ️ ${next.label} is Already Active:</strong> All active lots have water ledger entries. Use "Adjust Current Cycle" if you need to update rates or due dates.`;
            } else if (active && active.missing_lots_count > 0) {
                statusCard.style.background = '#fef3c7';
                statusCard.style.border = '1px solid #fde68a';
                statusCard.style.color = '#92400e';
                statusCard.innerHTML = `<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                    <div><strong>⚡ ${active.missing_lots_count} Unsynced Resident Lot(s) in ${active.label}:</strong> Newly assigned resident lots have no bill yet.</div>
                    <button type="button" class="btn btn-primary" style="padding:4px 10px; font-size:11px;" onclick="syncMissingLotsForActiveCycle()">Sync ${active.label} Now</button>
                </div>`;
            } else if (active && active.unrecorded_readings_count > 0) {
                statusCard.style.background = '#fffbeb';
                statusCard.style.border = '1px solid #fde68a';
                statusCard.style.color = '#b45309';
                statusCard.innerHTML = `<strong>⚠️ Meter Readings Notice:</strong> ${active.unrecorded_readings_count} lot(s) in ${active.label} have unrecorded readings (0 m³). Meter baselines will be carried forward automatically.`;
            } else {
                statusCard.style.background = '#f0fdf4';
                statusCard.style.border = '1px solid #bbf7d0';
                statusCard.style.color = '#166534';
                statusCard.innerHTML = `<strong>🟢 Automation Ready:</strong> Target <strong>${next ? next.label : 'Next'}</strong> cycle dates and arrears calculated automatically. 1-click generation.`;
            }
        }
    }

    async function syncMissingLotsForActiveCycle() {
        if (!latestCycleStatusData || !latestCycleStatusData.active_cycle) return;
        const active = latestCycleStatusData.active_cycle;
        const prev = active.prev_reading_date || '';
        const curr = active.curr_reading_date || active.period;
        const due = active.due_date || '';
        const rate = document.getElementById('newCycleRate').value || currentRate;
        const penalty = document.getElementById('newCyclePenalty').value || currentPenaltyRate;
        const minM3 = document.getElementById('newCycleMinM3').value || currentMinM3;
        const minRate = document.getElementById('newCycleMinRate').value || currentMinRate;

        closeResetCycleModal();

        Swal.fire({
            title: `Syncing ${active.label}...`,
            html: '<div style="margin-top:8px;color:#64748b;font-size:14px;">Generating water ledger entries for newly assigned lot(s)...</div>',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const res = await fetch('/admin/api/billing/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    type: 'water',
                    mode: 'sync_missing',
                    rate: rate,
                    penalty: penalty,
                    water_min_m3: minM3,
                    water_min_rate: minRate,
                    prev_date: prev,
                    curr_date: curr,
                    due_date: due
                })
            });
            const data = await res.json().catch(() => ({}));
            if (data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Sync Complete',
                    text: data.message,
                    confirmButtonColor: '#0284c7'
                });
                window.location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Sync Failed',
                    text: data.message || 'Unable to sync lots.',
                    confirmButtonColor: '#ef4444'
                });
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Network Error', text: 'Failed to contact server.' });
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
        const rate = document.getElementById('newCycleRate').value || currentRate;
        const penalty = document.getElementById('newCyclePenalty').value || currentPenaltyRate || 5;
        const minM3 = document.getElementById('newCycleMinM3').value || currentMinM3 || 10;
        const minRate = document.getElementById('newCycleMinRate').value || currentMinRate || 250;
        
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
                body: JSON.stringify({ type: 'water', rate: rate, penalty: penalty, prev_date: prevDate, curr_date: currDate, due_date: dueDate, water_min_m3: minM3, water_min_rate: minRate })
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
                await Swal.fire({
                    icon: 'success',
                    title: 'Billing Cycle Created!',
                    text: data.message || `New water billing cycle generated successfully with excess rate ₱${rate}/m³.`,
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
        const minM3 = parseFloat(document.getElementById('adjCycleMinM3').value);
        const minRate = parseFloat(document.getElementById('adjCycleMinRate').value);
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
                    type: 'water',
                    cycle: '{{ request("cycle") }}',
                    prev_date: prevDate,
                    curr_date: currDate,
                    due_date: dueDate,
                    rate: rate,
                    water_min_m3: minM3,
                    water_min_rate: minRate,
                    penalty: penalty
                })
            });

            const data = await res.json().catch(() => ({}));
            if (data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Active Cycle Updated!',
                    text: data.message || 'Water active cycle parameters and bills successfully updated.',
                    confirmButtonColor: '#0284c7',
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
        recalculateLedger();
        setTimeout(systemNotificationScan, 1000); // Wait 1 sec before populating notifications
    });

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
        const usageM3 = bill.usage_m3;
        const isAtRisk = bill.at_risk;
        const status = bill.status;
        const history = bill.payment_history || [];

        const atRiskBool = isAtRisk;

        let previousBalance = bill.previous_balance || 0;
        
        let minM3 = bill.min_m3 || 10;
        let minRate = bill.min_rate || 250;
        let excessRate = bill.excess_rate || currentRate;
        
        let minCharge = minRate; // base minimum
        let excessCharge = usageM3 > minM3 ? (usageM3 - minM3) * excessRate : 0;
        let currentCharges = minCharge + excessCharge;
        
        let arrearsPenalty = previousBalance > 0 ? previousBalance * 0.05 : 0;
        
        let amountBefore = currentCharges + previousBalance;
        let amountAfter = amountBefore + (amountBefore * (currentPenaltyRate / 100));
        
        let totalPaid = bill.total_paid || 0;
        let totalDueBefore = Math.max(0, amountBefore - totalPaid);
        let totalDueAfter = Math.max(0, amountAfter - totalPaid);

        document.getElementById('modalResident').textContent = resident;
        document.getElementById('modalLot').textContent = lot;
        
        document.getElementById('modalPrevReading').textContent = bill.prev_reading || 0;
        document.getElementById('modalCurrReading').textContent = bill.curr_reading || 0;
        document.getElementById('modalBaseRate').textContent = `₱${excessRate}/m³`;
        document.getElementById('modalUsage').textContent = `${usageM3} m³`;
        
        document.getElementById('modalMinBillSub').textContent = `First ${minM3}m³`;
        document.getElementById('modalMinBill').textContent = `₱${minCharge.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        
        if (usageM3 > minM3) {
            document.getElementById('modalExcessSub').textContent = `${(usageM3 - minM3)}m³ x ₱${excessRate}`;
        } else {
            document.getElementById('modalExcessSub').textContent = ``;
        }
        document.getElementById('modalExcessAmount').textContent = `₱${excessCharge.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        
        document.getElementById('modalAmountBeforeLabel').textContent = `Amount Before ${bill.due}`;
        document.getElementById('modalAmountAfterLabel').textContent = `Amount After ${bill.due}`;
        document.getElementById('modalAmountBefore').textContent = `₱${amountBefore.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('modalAmountAfter').textContent = `₱${amountAfter.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('modalPenalty').textContent = `₱${arrearsPenalty.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('modalPrevBalance').textContent = `₱${previousBalance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('modalTotalPaid').textContent = `₱${totalPaid.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('modalDateIssued').textContent = bill.issued_date || 'N/A';
        
        
        const trendEl = document.getElementById('modalTrend');
        if (trendEl) {
            trendEl.textContent = !atRiskBool ? 'Early' : 'Late';
            trendEl.className = 'trend-chip ' + (!atRiskBool ? 'trend-early' : 'trend-late');
        }

        // Payment Action Button
        const actionContainer = document.getElementById('modalPaymentAction');
        if (status === 'unpaid') {
            const amountToPay = atRiskBool ? totalDueAfter : totalDueBefore;
            actionContainer.innerHTML = `
                <div style="display: flex; gap: 12px; width: 100%; flex-wrap: wrap;">
                    <button class="btn btn-outline" style="flex: 1 1 200px; justify-content: center; padding: 14px; font-weight: 700; border-color: var(--bill-primary); color: var(--bill-primary);" onclick="addPreviousBalanceFromModal('${id}', '${resident}')">➕ Add Unpaid Balance</button>
                    <button class="btn btn-success" style="flex: 1 1 200px; justify-content: center; padding: 14px; font-weight: 700;" onclick="recordOfficePaymentFromModal('${id}', '${resident}', ${amountToPay})">🏢 Record Office Payment</button>
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
  <p><strong>OFFICIAL PAYMENT RECEIPT (WATER)</strong></p>
  <div class="watermark">PAID</div>
</div>
<div class="row">
  <div class="col-half box" style="margin-right:2%;">
    <h3>Resident Details</h3>
    <table style="border:none;margin-bottom:0;">
      <tr><td style="padding:4px;border:none;width:42%;"><strong>Name:</strong></td><td style="padding:4px;border:none;">${resident}</td></tr>
      <tr><td style="padding:4px;border:none;"><strong>Address:</strong></td><td style="padding:4px;border:none;">${lot}</td></tr>
      <tr><td style="padding:4px;border:none;"><strong>Utility:</strong></td><td style="padding:4px;border:none;">Water Utility</td></tr>
      <tr><td style="padding:4px;border:none;"><strong>Billing Month:</strong></td><td style="padding:4px;border:none;">${month}</td></tr>
    </table>
  </div>
  <div class="col-half box">
    <h3>Payment Details</h3>
    <table style="border:none;margin-bottom:0;">
      <tr><td style="padding:4px;border:none;width:48%;"><strong>Reference (TRN):</strong></td><td style="padding:4px;border:none;font-family:monospace;font-size:11px;font-weight:bold;">${trnVal}</td></tr>
      <tr><td style="padding:4px;border:none;"><strong>Date Paid:</strong></td><td style="padding:4px;border:none;font-size:11px;">${paidDate}</td></tr>
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
      <td>Water Utility Payment (${month}) — Ref: ${trnVal}</td>
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
  <p style="margin:4px 0 0;font-size:11px;color:#64748b;">Transaction Reference: ${trnVal} · Printed on: ${new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</p>
</div>
</body>
</html>`;

        const frame = document.getElementById('receiptFrame');
        const receiptModal = document.getElementById('receiptModal');
        const titleEl = document.getElementById('receiptModalTitle') || receiptModal.querySelector('span');
        if (titleEl) titleEl.textContent = 'Official Payment Receipt — Water';
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
        if (titleEl) titleEl.textContent = 'Statement of Account — Water';

        const resident = bill.resident || 'N/A';
        const lot = bill.lot || 'N/A';
        const status = (bill.status || 'unpaid').toLowerCase();
        const usageM3 = bill.usage_m3 || 0;
        const prevReading = bill.prev_reading || 0;
        const currReading = bill.curr_reading || 0;
        const prevBalance = parseFloat(bill.previous_balance) || 0;
        const totalPaid = parseFloat(bill.total_paid) || 0;
        const issuedDate = bill.issued_date || 'N/A';
        const dueDate = bill.due || 'N/A';
        const minM3 = bill.min_m3 || 10;
        const minRate = bill.min_rate || 250;
        const excessRate = bill.excess_rate || currentRate;
        const excessCharge = usageM3 > minM3 ? (usageM3 - minM3) * excessRate : 0;
        const currentCharges = minRate + excessCharge;
        const amountBeforeDue = currentCharges + prevBalance;
        const arrearsPenalty = amountBeforeDue * 0.05;
        const amountAfterDue = amountBeforeDue + arrearsPenalty;

        const history = bill.payment_history || [];
        const latestPay = history.length > 0 ? history[history.length - 1] : null;
        const trn = latestPay ? (latestPay.trn || 'N/A') : 'N/A';
        const mop = latestPay ? (latestPay.method || 'Office Payment') : 'N/A';
        const paidDate = latestPay ? (latestPay.date || 'N/A') : 'N/A';

        const statusColor = status === 'paid' ? '#10b981' : '#ef4444';
        const statusLabel = status === 'paid' ? 'PAID' : 'UNPAID';

        const paidRowsHtml = status === 'paid' ? `
            <tr><td style="padding:4px;border:none;"><strong>MOP:</strong></td><td style="padding:4px;border:none;">${mop}</td></tr>
            <tr><td style="padding:4px;border:none;"><strong>Reference (TRN):</strong></td><td style="padding:4px;border:none;font-family:monospace;font-size:11px;">${trn}</td></tr>
            <tr><td style="padding:4px;border:none;"><strong>Date Paid:</strong></td><td style="padding:4px;border:none;font-size:11px;">${paidDate}</td></tr>
        ` : '';

        const excessRowHtml = excessCharge > 0 ? `
            <tr><td>Excess Consumption (${usageM3 - minM3}m³ x ₱${excessRate}/m³)</td><td class="text-right">₱${excessCharge.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</td></tr>
        ` : '';

        const html = `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title></title>
<style>
  @page { size: portrait; margin: 0; }
  body {
    width: 100%;
    max-width: 760px;
    margin: 0 auto;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    color: #000;
    font-size: 11px;
    line-height: 1.3;
    padding: 8mm 12mm;
    background: #fff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    box-sizing: border-box;
  }
  .header {
    text-align: center;
    border-bottom: 2px solid #000;
    padding-bottom: 6px;
    margin-bottom: 10px;
    position: relative;
  }
  .header h1 {
    color: #000;
    margin: 0;
    font-size: 17px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .header p {
    margin: 2px 0 0;
    color: #000;
    font-size: 10.5px;
  }
  .watermark {
    position: absolute;
    top: 2px;
    right: 8px;
    border: 2px solid ${statusColor};
    color: ${statusColor};
    padding: 4px 10px;
    font-size: 16px;
    font-weight: bold;
    border-radius: 4px;
    transform: rotate(8deg);
    opacity: 0.85;
    letter-spacing: 1.5px;
    text-transform: uppercase;
  }
  .row { width: 100%; margin-bottom: 10px; }
  .col-half { width: 48%; display: inline-block; vertical-align: top; }
  .box {
    border: 1px solid #000;
    padding: 6px 10px;
    border-radius: 4px;
    background: #fff;
    box-sizing: border-box;
  }
  .box h3 {
    margin-top: 0;
    border-bottom: 1px solid #000;
    padding-bottom: 3px;
    margin-bottom: 4px;
    font-size: 11.5px;
    color: #000;
    text-transform: uppercase;
  }
  table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
  th, td { padding: 4px 6px; border: 1px solid #000; color: #000; font-size: 10.5px; }
  th { text-align: left; color: #000; background: #f0f0f0; font-weight: bold; }
  .text-right { text-align: right; }
  .fw-bold { font-weight: bold; }
  .total-row td {
    border-top: 2px solid #000;
    border-bottom: 2px solid #000;
    font-size: 11.5px;
    font-weight: bold;
    color: #000;
    background: #f5f5f5;
  }
  .highlight-row td {
    font-size: 10.5px;
    font-weight: bold;
    color: #000;
    background: #fafafa;
  }
  .footer {
    margin-top: 14px;
    text-align: center;
    font-size: 10px;
    color: #000;
    border-top: 1px solid #000;
    padding-top: 8px;
  }
</style>
</head>
<body>
<div class="header">
  <h1>Mayon Builders Realty & Development Corporation</h1>
  <p>Z1, Tagbong, Pili, Camarines Sur</p>
  <p><strong>STATEMENT OF ACCOUNT (WATER)</strong></p>
  <div class="watermark">${statusLabel}</div>
</div>
<div class="row">
  <div class="col-half box" style="margin-right:2%;">
    <h3>Account Details</h3>
    <table style="border:none;margin-bottom:0;">
      <tr><td style="padding:2px 4px;border:none;width:42%;"><strong>Name:</strong></td><td style="padding:2px 4px;border:none;">${resident}</td></tr>
      <tr><td style="padding:2px 4px;border:none;"><strong>Address:</strong></td><td style="padding:2px 4px;border:none;">${lot}</td></tr>
      <tr><td style="padding:2px 4px;border:none;"><strong>Date Issued:</strong></td><td style="padding:2px 4px;border:none;">${issuedDate}</td></tr>
      <tr><td style="padding:2px 4px;border:none;"><strong>Due Date:</strong></td><td style="padding:2px 4px;border:none;font-weight:bold;">${dueDate}</td></tr>
      ${paidRowsHtml}
    </table>
  </div>
  <div class="col-half box">
    <h3>Meter Information</h3>
    <table style="border:none;margin-bottom:0;">
      <tr><td style="padding:2px 4px;border:none;width:55%;"><strong>Previous Reading:</strong></td><td style="padding:2px 4px;border:none;text-align:right;">${prevReading}</td></tr>
      <tr><td style="padding:2px 4px;border:none;"><strong>Current Reading:</strong></td><td style="padding:2px 4px;border:none;text-align:right;">${currReading}</td></tr>
      <tr><td style="padding:2px 4px;border:none;"><strong>Consumption:</strong></td><td style="padding:2px 4px;border:none;text-align:right;font-weight:bold;">${usageM3} m³</td></tr>
      <tr><td style="padding:2px 4px;border:none;"><strong>Min. Charge (First ${minM3}m³):</strong></td><td style="padding:2px 4px;border:none;text-align:right;">₱${minRate.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</td></tr>
    </table>
  </div>
</div>
<h3 style="color:#000;border-bottom:1px solid #000;padding-bottom:3px;margin:10px 0 6px 0;font-size:11.5px;text-transform:uppercase;">Billing Summary</h3>
<table>
  <thead><tr><th>Description</th><th class="text-right">Amount (PHP)</th></tr></thead>
  <tbody>
    <tr><td>Minimum Charge (First ${minM3}m³)</td><td class="text-right">₱${minRate.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</td></tr>
    ${excessRowHtml}
    <tr><td><strong>Current Month Charges</strong></td><td class="text-right"><strong>₱${currentCharges.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</strong></td></tr>
    <tr><td>Previous Balance (Arrears)</td><td class="text-right">₱${prevBalance.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</td></tr>
    <tr><td>Late Payment Penalty (5% if paid after due date)</td><td class="text-right">₱${arrearsPenalty.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</td></tr>
    <tr class="highlight-row"><td><strong>Amount before ${dueDate}</strong></td><td class="text-right"><strong>₱${amountBeforeDue.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</strong></td></tr>
    <tr class="total-row"><td><strong>Amount after ${dueDate}</strong></td><td class="text-right"><strong>₱${amountAfterDue.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</strong></td></tr>
    ${status === 'paid' ? `<tr><td class="fw-bold">Total Paid</td><td class="text-right fw-bold">₱${totalPaid.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</td></tr>` : ''}
  </tbody>
</table>
<div class="footer">
  ${status === 'paid' ? '<p style="font-weight:bold;margin:0;">This bill has been fully settled. Thank you for your payment!</p>' : '<p style="margin:0;">Please pay on or before the due date to avoid penalty charges.</p>'}
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
        if(actionContainer) {
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
                await Swal.fire({
                    icon: 'success',
                    title: 'Payment Recorded',
                    text: `Payment of ₱${amountNum.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} recorded successfully.`,
                    confirmButtonColor: '#059669'
                });
                location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Payment Failed',
                    text: data.message || 'Failed to record payment.'
                });
                if(actionContainer) {
                    const buttons = actionContainer.querySelectorAll('button');
                    buttons.forEach(btn => btn.disabled = false);
                }
            }
        } catch (e) {
            console.error(e);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while recording payment.'
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
        const search = (document.getElementById('billingSearch')?.value || '').toLowerCase();
        const status = document.getElementById('statusFilter')?.value || 'all';
        const trend = (document.getElementById('behaviorFilter')?.value || document.getElementById('trendFilter')?.value || 'all');
        const block = document.getElementById('blockFilter')?.value || 'all';
        const rows = document.querySelectorAll('.bill-row');

        rows.forEach(row => {
            const res = (row.dataset.resident || '').toLowerCase();
            const s = row.dataset.status || '';
            const t = row.dataset.behavior || row.dataset.trend || '';
            const b = row.dataset.block || '';

            const matchesSearch = res.includes(search);
            const matchesStatus = status === 'all' || s === status;
            const matchesTrend = trend === 'all' || t === trend;
            const matchesBlock = block === 'all' || b === block;

            row.classList.toggle('pagination-match', matchesSearch && matchesStatus && matchesTrend && matchesBlock);
        });

        window.waterTablePagination?.resetPage();
    }

    document.addEventListener('DOMContentLoaded', function() {
        window.waterTablePagination = initTablePagination({
            rowSelector: '.bill-row',
            infoEl: '#waterTablePageInfo',
            prevEl: '#waterTablePagePrev',
            nextEl: '#waterTablePageNext',
            perPage: 15,
        });

        ['billingSearch', 'statusFilter', 'blockFilter', 'behaviorFilter'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', applyFilters);
            }
        });

        applyFilters();
        recalculateLedger();

        const ctx = document.getElementById('waterSalesChart')?.getContext('2d');
        if (ctx) {
            @php
                $chartData = getMonthlyChartData('water');
            @endphp

            window.salesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: @json($chartData['labels']),
                    datasets: [{
                        label: 'Water Collection (₱)',
                        data: @json($chartData['data']),
                        backgroundColor: @json($chartData['colors']),
                        borderRadius: 6,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Revenue: ₱' + context.raw.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100000,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + (value / 1000) + 'k';
                                },
                                font: { weight: '600', size: 11 },
                                color: '#64748b'
                            },
                            grid: { color: '#f1f5f9' }
                        },
                        x: {
                            ticks: { font: { weight: '600', size: 11 }, color: '#64748b' },
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    });
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
                
                // reattach listeners
                ['billingSearch', 'statusFilter', 'blockFilter', 'behaviorFilter'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', applyFilters);
                    }
                });
                applyFilters();
                recalculateLedger();
            })
            .catch(err => {
                console.error(err);
                document.querySelector('.bill-container').style.opacity = '1';
            });
    }
</script>
@endsection
