@extends('layouts.admin')

@section('title', 'Electricity Billing')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">

<div class="bill-container fade-in">
    <div class="bill-header">
        <div class="bill-title">
            <h1>Electricity Management & Analytics</h1>
            <p>12-month consumption analysis, payment channel management, and predictive delinquency tracking.</p>
        </div>
        <div class="bill-actions">
            <!-- New: Billing Settings Button -->
            <button class="btn btn-outline" style="border-color: var(--bill-primary); color: var(--bill-primary);" onclick="openSettingsModal()">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px;"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Billing Settings
            </button>
            <button class="btn btn-outline" style="border-color: var(--bill-danger); color: var(--bill-danger);" onclick="resetBillingCycle()">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px;"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Reset Billing Cycle
            </button>
        </div>
    </div>

    <!-- Collection Summary Dashboard -->
    <div style="background: #fff; border: 1px solid var(--bill-border); border-radius: 20px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
            <h2 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">Ledger Performance Summary</h2>
            <select id="summaryMonth" class="filter-select" style="font-weight: 600; color: var(--bill-primary); border-color: #cbd5e1;" onchange="updateSummaryDashboard()">
                <option value="May 2026" selected>Current Month (May 2026)</option>
                <option value="Apr 2026">April 2026</option>
                <option value="Mar 2026">March 2026</option>
                <option value="Feb 2026">February 2026</option>
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
                <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Past Due (At Risk)</div>
                <div id="sumPastDue" style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">0</div>
            </div>
        </div>
    </div>

    <!-- Monthly Electricity Sales & Collection Trend Bar Graph -->
    <div style="background: #fff; border: 1px solid var(--bill-border); border-radius: 20px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">Monthly Electricity Revenue Trends</h3>
                <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">12-month electrical billing revenue & collection performance (in ₱ Thousands)</p>
            </div>
            <span class="badge" style="background: #ecfdf5; color: #059669; font-weight: 700; padding: 6px 14px; border-radius: 12px;">+12.4% YoY Growth</span>
        </div>
        <div style="height: 200px; position: relative;">
            <canvas id="electricitySalesChart"></canvas>
        </div>
    </div>

    <!-- Analytics Dashboard -->
    @php
        $totalBills = count($bills);
        $paidBills  = collect($bills)->where('status', 'paid')->count();
        $totalBilled   = collect($bills)->sum('amount');
        $totalCollected = collect($bills)->where('status', 'paid')->sum('amount');
        $cerPct = $totalBilled > 0 ? round(($totalCollected / $totalBilled) * 100) : 0;
        $circumference = 2 * M_PI * 52; // radius 52 on 120px svg
        $cerStroke = ($cerPct / 100) * $circumference;

        // Simulated master meter: ~8% more than billed
        $masterUsageKwh  = collect($bills)->sum('usage_kwh') * 1.08;
        $billedUsageKwh  = collect($bills)->sum('usage_kwh');
        $systemLossPct   = $masterUsageKwh > 0 ? round((($masterUsageKwh - $billedUsageKwh) / $masterUsageKwh) * 100, 1) : 8;
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
                <svg width="200" height="200" viewBox="0 0 42 42" class="donut-svg">
                    <circle class="donut-segment segment-late"   cx="21" cy="21" r="15.915" stroke-dasharray="20 80"></circle>
                    <circle class="donut-segment segment-ontime" cx="21" cy="21" r="15.915" stroke-dasharray="30 70" stroke-dashoffset="-20"></circle>
                    <circle class="donut-segment segment-early"  cx="21" cy="21" r="15.915" stroke-dasharray="50 50" stroke-dashoffset="-50"></circle>
                </svg>
                <div class="donut-legend">
                    <div class="legend-item">
                        <span class="legend-label"><span class="dot" style="background: var(--bill-success);"></span> Early Payers</span>
                        <strong>50%</strong>
                    </div>
                    <div class="legend-item">
                        <span class="legend-label"><span class="dot" style="background: var(--bill-primary);"></span> On-Time</span>
                        <strong>30%</strong>
                    </div>
                    <div class="legend-item">
                        <span class="legend-label"><span class="dot" style="background: var(--bill-warning);"></span> Late / At Risk</span>
                        <strong>20%</strong>
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
            <span class="filter-label">Reliability Trend</span>
            <select id="trendFilter" class="filter-select">
                <option value="all">All Trends</option>
                <option value="reliable">🌟 Reliable</option>
                <option value="at-risk">⚠️ At Risk</option>
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
        <div class="bill-table-container">
            <table class="bill-table" id="billingTable">
                <thead>
                    <tr>
                        <th>Resident / Lot</th>
                        <th>Amount</th>
                        <th>Predictive Trend</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bills as $bill)
                    <tr class="bill-row" data-resident="{{ strtolower($bill['resident']) }}" data-status="{{ $bill['status'] }}" data-trend="{{ $bill['at_risk'] ? 'at-risk' : 'reliable' }}" data-block="{{ $bill['block'] }}">
                        <td>
                            <div style="font-weight: 700;">{{ $bill['resident'] }}</div>
                            <div style="font-size: 12px; color: #64748b;">{{ $bill['lot'] }}</div>
                        </td>
                        <!-- Dynamic Amounts for JS recalculation -->
                        <td class="dynamic-amount-cell" 
                            data-usage-kwh="{{ $bill['usage_kwh'] }}" 
                            data-at-risk="{{ $bill['at_risk'] ? 'true' : 'false' }}" 
                            data-status="{{ $bill['status'] }}"
                            style="font-weight: 700;">
                            ₱{{ number_format($bill['base_amount'], 2) }}
                        </td>
                        <td>
                            @if(!$bill['at_risk'])
                                <span class="trend-chip trend-early">🌟 Reliable</span>
                            @else
                                <span class="trend-chip trend-late">⚠️ At Risk</span>
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
                            @else
                                <span class="badge badge-danger">Unpaid</span>
                                <div style="font-size: 10px; color: var(--bill-danger); margin-top: 4px;">Due: {{ $bill['due'] }}</div>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button class="btn btn-outline" style="padding: 6px 10px; font-size: 11px;" onclick="viewDetail('{{ $bill['id'] }}')">View</button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Full Screen Immersive Modal -->
<div id="billModal" class="bill-modal">
    <div class="bill-modal-content">
        <div class="modal-header">
            <div>
                <span id="modalLot" style="font-size: 12px; font-weight: 700; color: var(--bill-primary); text-transform: uppercase; letter-spacing: 0.1em;">BLOCK 1 LOT 5</span>
                <h2 id="modalResident" style="font-size: 32px; font-weight: 800; color: #0f172a; margin-top: 8px;">Juan Dela Cruz</h2>
                <div style="margin-top: 12px; display: flex; gap: 12px; align-items: center;">
                    <span id="modalTrend" class="trend-chip trend-early">🌟 Reliable Payer</span>
                    <span id="modalPaymentBadge" class="badge">Paid</span>
                </div>
            </div>
            <button class="ann-btn-icon" onclick="closeModal()" style="padding: 12px; background: #f1f5f9;">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div id="modalMainContent">
                <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; margin-bottom: 20px; color: #64748b;">Current Statement Details</h3>
                <div class="modal-summary-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
                    <div style="background: var(--bill-bg-soft); padding: 20px; border-radius: 16px;">
                        <span style="font-size: 12px; color: #64748b;">Current Usage</span>
                        <div id="modalUsage" style="font-size: 24px; font-weight: 700; color: #0f172a; margin-top: 4px;">150 kWh</div>
                    </div>
                    <div style="background: var(--bill-bg-soft); padding: 20px; border-radius: 16px;">
                        <span style="font-size: 12px; color: #64748b;">Total Amount Due</span>
                        <div id="modalAmount" style="font-size: 24px; font-weight: 700; color: var(--bill-primary); margin-top: 4px;">₱1,250.50</div>
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
                    <button class="btn btn-outline" id="disconnectBtn" style="color: var(--bill-danger); border-color: #fee2e2;" onclick="issueDisconnectWarning()">Disconnect Warning</button>
                </div>
                
                <div style="margin-top: 40px; padding: 20px; border: 1px solid var(--bill-border); border-radius: 20px;">
                    <h4 style="font-size: 12px; font-weight: 700; margin-bottom: 12px;">System Audit & Comm Trail</h4>
                    <div id="modalAuditLog" style="display:flex; flex-direction: column; gap: 12px;">
                        <!-- Audit Log Injected via JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic Billing Settings Modal -->
<div id="settingsModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 2000;">
    <div class="bill-modal-content" style="max-width: 400px; padding: 24px;">
        <div class="modal-header" style="margin-bottom: 24px; display: flex; justify-content: space-between;">
            <h2 style="font-size: 20px; font-weight: 800;">Billing Rates Configuration</h2>
            <button class="ann-btn-icon" onclick="closeSettingsModal()" style="padding: 8px;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Base Rate per kWh (₱)</label>
            <input type="number" id="inputKwhRate" class="filter-select" style="width: 100%; font-size: 16px; padding: 12px;" value="10">
        </div>

        <button class="btn btn-primary" style="width: 100%; justify-content: center;" onclick="applySettings()">Update & Recalculate Ledger</button>
    </div>
</div>

<!-- Reset Cycle Modal -->
<div id="resetCycleModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 3000;">
    <div class="bill-modal-content" style="max-width: 400px; padding: 32px; text-align: center; border-radius: 24px;">
        <div style="width: 64px; height: 64px; background: #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
            <svg width="32" height="32" fill="none" stroke="#ef4444" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <h3 style="font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 12px;">Reset Billing Cycle?</h3>
        <p style="font-size: 14px; color: #64748b; margin-bottom: 24px; line-height: 1.6;">This will clear all current readings and shift all residents to <strong>Unpaid</strong> for the new month. This action cannot be undone.</p>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <button class="btn btn-outline" style="justify-content: center; padding: 12px;" onclick="closeResetCycleModal()">Cancel</button>
            <button class="btn" style="justify-content: center; padding: 12px; background: #ef4444; color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer;" onclick="executeResetCycle()">Yes, Reset</button>
        </div>
    </div>
</div>

<script>
    const statsData = @json($stats);
    let isPaidTodayFilter = false;

    // Global Billing Settings
    let currentKwhRate = {{ \App\Models\Setting::where('key', 'elec_rate')->value('value') ?? 10 }};

    // Load Data safely
    const allBillsRaw = @json($bills);

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
                alert(`Rates updated to ₱${currentKwhRate}/kWh.`);
            }
        });
    }

    function recalculateLedger() {
        const amountCells = document.querySelectorAll('.dynamic-amount-cell');
        amountCells.forEach(cell => {
            const usage = parseInt(cell.dataset.usageKwh);
            let total = usage * currentKwhRate;

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

        if (monthSel !== 'May 2026') {
            // Historic State View: Modify the table actively
            const mFactor = monthSel === 'Apr 2026' ? 0.9 : 0.85;
            let totalHistoric = 0;
            let countHistoric = 0;

            rows.forEach(row => {
                const cell = row.querySelector('.dynamic-amount-cell') || row.cells[1];
                let amt = parseFloat(cell.dataset.rawTotal || cell.textContent.replace(/[^\d.-]/g, '')) || 0;
                
                // Simulate slightly different past amounts
                amt = amt * mFactor;
                totalHistoric += amt;
                countHistoric++;

                if (!row.dataset.originalStatus) {
                    row.dataset.originalStatus = row.dataset.status;
                    row.dataset.originalStatusHtml = row.cells[4].innerHTML;
                    row.dataset.originalActionHtml = row.cells[5].innerHTML;
                }

                // Lock table to 'paid' historic view
                cell.innerHTML = `₱${amt.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                row.dataset.status = 'paid';
                row.cells[4].innerHTML = `<span class="badge badge-success">Paid</span><div style="font-size: 10px; color: #10b981; margin-top: 4px;">Historic Record</div>`;
                row.cells[5].innerHTML = `<div style="display: flex; gap: 8px;"><button class="btn btn-outline" style="padding: 6px 10px; font-size: 11px;">Receipt</button></div>`;
            });

            sumColEl.textContent = `₱${totalHistoric.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            sumPaidEl.textContent = countHistoric;
            sumUnpaidEl.textContent = '0'; 
            sumPastDueEl.textContent = '0';
        } else {
            // Return to live current state
            let totalCollected = 0;
            let countPaid = 0;
            let countUnpaid = 0;
            let countPastDue = 0;

            rows.forEach(row => {
                if (row.dataset.originalStatus) {
                    row.dataset.status = row.dataset.originalStatus;
                    row.cells[4].innerHTML = row.dataset.originalStatusHtml;
                    row.cells[5].innerHTML = row.dataset.originalActionHtml;
                    delete row.dataset.originalStatus;
                }

                const status = row.dataset.status;
                const isAtRisk = row.dataset.trend === 'at-risk';
                const cell = row.querySelector('.dynamic-amount-cell') || row.cells[1];
                const amt = parseFloat(cell.dataset.rawTotal || cell.textContent.replace(/[^\d.-]/g, '')) || 0;

                if (status === 'paid') {
                    countPaid++;
                    totalCollected += amt;
                } else {
                    countUnpaid++;
                    if (isAtRisk) countPastDue++;
                }
            });

            // Initial load mock collected check
            if (countPaid > 0 && totalCollected < 5000) totalCollected = 4550; // Mock base

            sumColEl.textContent = `₱${totalCollected.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            sumPaidEl.textContent = countPaid;
            sumUnpaidEl.textContent = countUnpaid;
            sumPastDueEl.textContent = countPastDue;
        }

        updatePredictiveDonut();
    }

    function updatePredictiveDonut() {
        const rows = document.querySelectorAll('.bill-row');
        let early = 0, ontime = 0, late = 0;
        let total = rows.length;

        rows.forEach(row => {
            const status = row.dataset.status;
            const trend = row.dataset.trend;
            
            if (trend === 'at-risk') {
                late++;
            } else if (status === 'paid') {
                early++;
            } else {
                ontime++;
            }
        });

        const pEarly = total > 0 ? Math.round((early / total) * 100) : 0;
        const pOntime = total > 0 ? Math.round((ontime / total) * 100) : 0;
        const pLate = total > 0 ? Math.round((late / total) * 100) : 0;

        const donutSvg = document.querySelector('.donut-svg');
        if (donutSvg) {
            donutSvg.innerHTML = `
                <circle class="donut-segment segment-late" cx="21" cy="21" r="15.915" stroke-dasharray="${pLate} ${100-pLate}"></circle>
                <circle class="donut-segment segment-ontime" cx="21" cy="21" r="15.915" stroke-dasharray="${pOntime} ${100-pOntime}" stroke-dashoffset="-${pLate}"></circle>
                <circle class="donut-segment segment-early" cx="21" cy="21" r="15.915" stroke-dasharray="${pEarly} ${100-pEarly}" stroke-dashoffset="-${pLate + pOntime}"></circle>
            `;
            
            const legend = document.querySelector('.donut-legend');
            legend.innerHTML = `
                <div class="legend-item"><span class="legend-label"><span class="dot" style="background: var(--bill-success);"></span> Early Payers</span><strong>${pEarly}%</strong></div>
                <div class="legend-item"><span class="legend-label"><span class="dot" style="background: var(--bill-primary);"></span> On-Time</span><strong>${pOntime}%</strong></div>
                <div class="legend-item"><span class="legend-label"><span class="dot" style="background: var(--bill-warning);"></span> Late (At Risk)</span><strong>${pLate}%</strong></div>
            `;
        }
    }

    function resetBillingCycle() {
        if (new URLSearchParams(window.location.search).get('action') !== 'reset') {
            window.history.pushState(null, '', '?action=reset');
        }
        document.getElementById('resetCycleModal').style.display = 'flex';
    }

    function closeResetCycleModal() {
        window.history.replaceState(null, '', window.location.pathname);
        document.getElementById('resetCycleModal').style.display = 'none';
    }

    async function executeResetCycle() {
        closeResetCycleModal();
        try {
            const res = await fetch('/admin/api/billing/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ type: 'electricity', rate: currentKwhRate })
            });
            const data = await res.json();
            if(data.success) {
                if (window.pushSystemNotification) {
                    window.pushSystemNotification("Cycle Reset", "A new monthly billing cycle has been initiated.", "System");
                }
                setTimeout(() => window.location.reload(), 1000);
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
        const bill = allBillsRaw.find(b => b.id === id);
        if (!bill) return;

        const resident = bill.resident;
        const lot = bill.lot;
        const usageKwh = bill.usage_kwh;
        const isAtRisk = bill.at_risk;
        const status = bill.status;
        const history = bill.payment_history || [];
        const auditLog = bill.audit_log || [];

        const atRiskBool = isAtRisk;

        // Calculate custom amounts dynamically based on settings
        let totalAmount = usageKwh * currentKwhRate;

        document.getElementById('modalResident').textContent = resident;
        document.getElementById('modalLot').textContent = lot;
        document.getElementById('modalUsage').textContent = `${usageKwh} kWh (₱${currentKwhRate}/kWh)`;
        
        let amountHtml = `₱${totalAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

        document.getElementById('modalAmount').innerHTML = amountHtml;
        
        const trendEl = document.getElementById('modalTrend');
        trendEl.textContent = !atRiskBool ? '🌟 Reliable Payer' : '⚠️ At Risk';
        trendEl.className = 'trend-chip ' + (!atRiskBool ? 'trend-early' : 'trend-late');

        // Payment Action Button
        const actionContainer = document.getElementById('modalPaymentAction');
        if (status === 'unpaid') {
            actionContainer.innerHTML = `
                <button class="btn btn-success" style="width: 100%; justify-content: center; padding: 14px; font-weight: 700;" onclick="recordOfficePaymentFromModal('${id}', '${resident}', ${totalAmount})">🏢 Record Office Payment</button>`;
        } else {
            actionContainer.innerHTML = '';
        }

        // Populate Audit Log
        const auditContainer = document.getElementById('modalAuditLog');
        auditContainer.innerHTML = '';
        auditLog.forEach(log => {
            const isWarning = log.action.includes('Warning');
            auditContainer.innerHTML += `
                <div style="font-size: 12px;">
                    <div style="font-weight: 600; color: ${isWarning ? 'var(--bill-danger)' : 'var(--text-dark)'}">${log.action}</div>
                    <div style="color: #64748b; font-size: 11px;">${log.user} • ${log.date}</div>
                </div>
            `;
        });

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
        try {
            const res = await fetch('/admin/api/billing/pay', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ id: id, amount: totalAmount })
            });
            const data = await res.json();
            if(data.success) {
                // Update Modal UI Instantly
                document.getElementById('modalPaymentBadge').className = 'badge badge-success';
                document.getElementById('modalPaymentBadge').textContent = 'Paid';
                document.getElementById('modalPaymentAction').innerHTML = '';
                
                // Add a simulated entry to the Audit Log UI
                const auditContainer = document.getElementById('modalAuditLog');
                auditContainer.innerHTML = `
                    <div style="font-size: 12px; background: #f0fdf4; padding: 8px; border-radius: 6px;">
                        <span style="font-weight: 700; color: #16a34a;">Office Payment</span> 
                        <span style="color: #64748b; margin-left: 8px;">Just now &bull; Admin</span>
                    </div>
                ` + auditContainer.innerHTML;
                
                alert(`Payment of ₱${totalAmount.toLocaleString()} recorded for ${resident}.`);
                setTimeout(() => window.location.reload(), 1000);
            } else {
                alert('Payment recording failed.');
            }
        } catch(e) {
            console.error(e);
            alert('Failed to record payment.');
        }
    }

    function issueDisconnectWarning() {
        const btn = document.getElementById('disconnectBtn');
        const residentName = document.getElementById('modalResidentName')?.textContent || 'Resident';
        const billId = document.getElementById('modalBillId')?.textContent || 'EB-002';
        const amount = document.getElementById('modalBillAmount')?.textContent || '2,100.00';

        if (confirm("Issue a formal 48-hour disconnection warning for this resident?")) {
            btn.textContent = '⚠️ WARNING ISSUED';
            btn.style.background = '#fee2e2';
            btn.style.color = '#991b1b';

            fetch('/api/send-billing-warning-email', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    email: 'eighty6pharmacy@gmail.com',
                    resident_name: residentName,
                    bill_id: billId,
                    amount: amount
                })
            }).catch(err => console.error("Billing warning email error:", err));

            alert("Disconnection warning has been logged and sent via live email to eighty6pharmacy@gmail.com!");
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
        const ctx = document.getElementById('electricitySalesChart')?.getContext('2d');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May (Now)', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Electricity Collection (₱)',
                        data: [142500, 155000, 168000, 192400, 215800, 175000, 185000, 198000, 170000, 160000, 152000, 165000],
                        backgroundColor: [
                            '#10b981', '#10b981', '#10b981', '#10b981', '#3b82f6',
                            '#e2e8f0', '#e2e8f0', '#e2e8f0', '#e2e8f0', '#e2e8f0', '#e2e8f0', '#e2e8f0'
                        ],
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
                            max: 250000,
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
