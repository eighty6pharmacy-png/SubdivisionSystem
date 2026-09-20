@extends('layouts.admin')

@section('title', 'Water Billing')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">

<div class="bill-container fade-in">
    <div class="bill-header">
        <div class="bill-title">
            <h1>Water Management & Analytics</h1>
            <p>12-month consumption analysis, payment channel management, and predictive delinquency tracking.</p>
        </div>
        <div class="bill-actions">
            <button class="btn btn-outline" style="border-color: var(--bill-primary); color: var(--bill-primary);" onclick="resetBillingCycle()">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                New Cycle
            </button>
        </div>
    </div>

    <!-- Collection Summary Dashboard -->
    <div style="background: #fff; border: 1px solid var(--bill-border); border-radius: 20px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
            <h2 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">Ledger Performance Summary</h2>
            <select id="summaryMonth" class="filter-select" style="font-weight: 600; color: var(--bill-primary); border-color: #cbd5e1;" onchange="window.location.href='?cycle='+this.value">
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
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid var(--bill-primary);">
                <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Total Collection</div>
                <div id="sumCollection" style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">₱0.00</div>
            </div>
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid #10b981;">
                <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Residents Paid</div>
                <div id="sumPaid" style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">0</div>
            </div>
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid #f59e0b;">
                <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Unpaid / Pending</div>
                <div id="sumUnpaid" style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">0</div>
            </div>
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid #ef4444;">
                <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Past Due</div>
                <div id="sumPastDue" style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">0</div>
            </div>
        </div>
    </div>

    <!-- Monthly Water Sales & Collection Trend Bar Graph -->
    <div style="background: #fff; border: 1px solid var(--bill-border); border-radius: 20px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">Monthly Water Revenue Trends</h3>
                <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">12-month collection performance (in ₱ Thousands)</p>
            </div>
        </div>
        <div style="height: 200px; position: relative;">
            <canvas id="waterSalesChart"></canvas>
        </div>
    </div>

    <!-- Analytics Dashboard -->
    @php
        $totalBills = count($bills);
        $paidBills  = collect($bills)->where('status', 'paid')->count();
        $totalBilled    = collect($bills)->sum('amount');
        $totalCollected = collect($bills)->where('status', 'paid')->sum('amount');
        $cerPct = $totalBilled > 0 ? round(($totalCollected / $totalBilled) * 100) : 0;
        $circumference = 2 * M_PI * 52;
        $cerStroke = ($cerPct / 100) * $circumference;

        // Simulated water master meter: ~6% more than billed (water loss)
        $billedUsageCbm  = collect($bills)->sum('usage_cbm');
        $masterUsageCbm  = $billedUsageCbm * 1.06;
        $systemLossPct   = $masterUsageCbm > 0 ? round((($masterUsageCbm - $billedUsageCbm) / $masterUsageCbm) * 100, 1) : 0;
        $billedFillPct   = 100 - $systemLossPct;
    @endphp
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
                    <button class="btn btn-outline" onclick="window.print()">Print Statement</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Cycle Modal -->
<div id="resetCycleModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 3000;">
    <div class="bill-modal-content" style="max-width: 450px; padding: 32px; border-radius: 24px;">
        <div style="text-align: center;">
            <div style="width: 64px; height: 64px; background: #e0f2fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <svg width="32" height="32" fill="none" stroke="#0284c7" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            </div>
            <h3 style="font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 12px;">Create New Billing Cycle</h3>
            <p style="font-size: 14px; color: #64748b; margin-bottom: 24px; line-height: 1.6;">This will generate a new billing cycle. Previous cycles will remain accessible from the historic ledger dropdown.</p>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 6px;">Start Date</label>
                <input type="date" id="newCycleStart" class="filter-select" style="width: 100%; box-sizing: border-box;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 6px;">End Date</label>
                <input type="date" id="newCycleEnd" class="filter-select" style="width: 100%; box-sizing: border-box;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 6px;">Min Usage (m³)</label>
                <input type="number" id="newCycleMinM3" class="filter-select" value="10" style="width: 100%; box-sizing: border-box;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 6px;">Min Rate (₱)</label>
                <input type="number" id="newCycleMinRate" class="filter-select" value="250" style="width: 100%; box-sizing: border-box;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 6px;">Excess Rate (₱)</label>
                <input type="number" id="newCycleRate" class="filter-select" value="25" style="width: 100%; box-sizing: border-box;">
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr; gap: 16px; margin-bottom: 32px;">
            <div>
                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 6px;">Penalty Past Due (%)</label>
                <input type="number" id="newCyclePenalty" class="filter-select" value="5" style="width: 100%; box-sizing: border-box;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <button class="btn btn-outline" style="justify-content: center; padding: 12px;" onclick="closeResetCycleModal()">Cancel</button>
            <button class="btn btn-primary" style="justify-content: center; padding: 12px;" onclick="executeResetCycle()">Create Cycle</button>
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
    let currentRate = {{ \App\Models\Setting::where('key', 'water_rate')->value('value') ?? 15 }};
    let currentPenaltyRate = {{ \App\Models\Setting::where('key', 'water_penalty')->value('value') ?? 5 }};

    // Load Data safely
    const allBillsRaw = @json($bills);


    function recalculateLedger() {
        const amountCells = document.querySelectorAll('.dynamic-amount-cell');
        amountCells.forEach(cell => {
            const usage = parseInt(cell.dataset.usageCubic) || 0;
            const previousBalance = parseFloat(cell.dataset.previousBalance) || 0;
            const isAtRisk = cell.dataset.atRisk === 'true';
            
            let amountBefore = (usage * currentRate) + previousBalance;
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
        const monthSel = document.getElementById('summaryMonth').value;
        const sumColEl = document.getElementById('sumCollection');
        const sumPaidEl = document.getElementById('sumPaid');
        const sumUnpaidEl = document.getElementById('sumUnpaid');
        const sumPastDueEl = document.getElementById('sumPastDue');
        const rows = document.querySelectorAll('.bill-row');

        let totalCollected = 0;
        let countPaid = 0;
        let countUnpaid = 0;
        let countPastDue = 0;

        rows.forEach(row => {
            const status = row.dataset.status;
            const isAtRisk = row.dataset.trend === 'at-risk' || row.dataset.trend === 'late';
            const cell = row.querySelector('.dynamic-amount-cell') || row.cells[1];
            const amt = parseFloat(cell.dataset.rawTotal || cell.textContent.replace(/[^\d.-]/g, '')) || 0;

            if (status === 'paid') {
                countPaid++;
                totalCollected += amt;
            } else {
                countUnpaid++;
                if (row.querySelector('.trend-late') || row.dataset.trend === 'at-risk') {
                    countPastDue++;
                }
            }
        });

        sumColEl.textContent = `₱${totalCollected.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        sumPaidEl.textContent = countPaid;
        sumUnpaidEl.textContent = countUnpaid;
        sumPastDueEl.textContent = countPastDue;
        
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

    function resetBillingCycle() {
        if (new URLSearchParams(window.location.search).get('action') !== 'reset') {
            window.history.pushState(null, '', '?action=reset');
        }
        document.getElementById('newCycleRate').value = currentRate;
        document.getElementById('resetCycleModal').style.display = 'flex';
    }

    function closeResetCycleModal() {
        window.history.replaceState(null, '', window.location.pathname);
        document.getElementById('resetCycleModal').style.display = 'none';
    }

    async function executeResetCycle() {
        const rate = document.getElementById('newCycleRate').value || currentRate;
        const penalty = document.getElementById('newCyclePenalty').value || 5;
        const startDate = document.getElementById('newCycleStart').value;
        const endDate = document.getElementById('newCycleEnd').value;
        const minM3 = document.getElementById('newCycleMinM3').value || 10;
        const minRate = document.getElementById('newCycleMinRate').value || 250;
        
        if (!startDate || !endDate) {
            alert('Please select start and end dates.');
            return;
        }

        closeResetCycleModal();
        try {
            const res = await fetch('/admin/api/billing/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ type: 'water', rate: rate, penalty: penalty, start_date: startDate, end_date: endDate, water_min_m3: minM3, water_min_rate: minRate })
            });
            const data = await res.json();
            if(data.success) {
                alert(`New billing cycle generated successfully with base rate ₱${rate}.`);
                setTimeout(() => window.location.reload(), 500);
            }
        } catch(e) {
            console.error(e);
            alert('Failed to reset billing cycle.');
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

    function viewDetail(id) {
        let bill = allBillsRaw.find(b => String(b.id).trim() === String(id).trim());
        if (!bill) {
            bill = allBillsRaw.find(b => b.db_id && String(b.db_id).trim() === String(id).trim());
        }
        if (!bill) {
            bill = allBillsRaw.find(b => String(b.id).includes(String(id)) || String(id).includes(String(b.id)));
        }
        if (!bill && allBillsRaw.length > 0) {
            bill = allBillsRaw[0];
        }
        if (!bill) return;

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
        let excessRate = bill.excess_rate || 25;
        
        let minCharge = minRate; // base minimum
        let excessCharge = usageM3 > minM3 ? (usageM3 - minM3) * excessRate : 0;
        let currentCharges = minCharge + excessCharge;
        
        let arrearsPenalty = previousBalance > 0 ? previousBalance * 0.05 : 0;
        
        let amountBefore = currentCharges + previousBalance + arrearsPenalty;
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
            row.innerHTML = `
                <td data-label="TRN">
                    <span style="font-family: monospace; background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 11px;">
                        ${item.trn}
                    </span>
                </td>
                <td data-label="Month">${item.month}</td>
                <td data-label="Amount" style="font-weight: 700;">₱${item.amount.toLocaleString()}</td>
                <td data-label="Status"><span class="badge ${item.status === 'Paid' ? 'badge-success' : 'badge-danger'}">${item.status}</span></td>
                <td data-label="Date" style="font-size: 11px; color: #64748b;">${item.date}</td>
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

    async function recordOfficePaymentFromModal(id, resident, totalAmount) {
        const inputAmount = await promptAsync(
            "Record Office Payment", 
            `Enter payment amount received from ${resident}:`, 
            totalAmount
        );
        if (inputAmount === null || inputAmount === "") return;
        const amountNum = parseFloat(inputAmount);
        if (isNaN(amountNum) || amountNum <= 0) {
            alert("Invalid amount.");
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
                alert(`Payment of ₱${amountNum.toLocaleString()} recorded successfully.`);
                location.reload();
            } else {
                alert(data.message || 'Failed to record payment.');
                if(actionContainer) {
                    const buttons = actionContainer.querySelectorAll('button');
                    buttons.forEach(btn => btn.disabled = false);
                }
            }
        } catch (e) {
            console.error(e);
            alert('An error occurred.');
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
            alert("Invalid balance amount.");
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
                alert(`Balance of ₱${balanceNum} added successfully.`);
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
                alert('Failed to add balance.');
            }
        } catch (e) {
            console.error(e);
            alert('Error adding balance.');
        }
    }

    function applyFilters() {
        const search = document.getElementById('billingSearch').value.toLowerCase();
        const status = document.getElementById('statusFilter').value;
        const trend = document.getElementById('trendFilter').value;
        const block = document.getElementById('blockFilter').value;
        const rows = document.querySelectorAll('.bill-row');

        rows.forEach(row => {
            const res = row.dataset.resident;
            const s = row.dataset.status;
            const t = row.dataset.trend;
            const b = row.dataset.block;

            const matchesSearch = res.includes(search);
            const matchesStatus = status === 'all' || s === status;
            const matchesTrend = trend === 'all' || t === trend;
            const matchesBlock = block === 'all' || b === block;

            row.style.display = (matchesSearch && matchesStatus && matchesTrend && matchesBlock) ? '' : 'none';
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
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

</script>
@endsection
