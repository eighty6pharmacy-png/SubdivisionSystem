@extends('layouts.admin')

@section('title', 'Downpayment Fee')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">
    <style>
        .dp-contract-modal .modal-header { padding: 24px 32px; }
        .dp-contract-modal .modal-body {
            grid-template-columns: 1fr 300px;
            gap: 24px;
            padding: 24px 32px;
            align-items: start;
        }
        .dp-contract-modal .dp-pay-sidebar {
            border-left: 1px solid var(--bill-border);
            padding-left: 24px;
            position: sticky;
            top: 0;
        }
        .dp-contract-modal .dp-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        .dp-contract-modal .dp-summary-card {
            background: var(--bill-bg-soft);
            padding: 16px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .dp-contract-modal .dp-ledger-scroll {
            max-height: 360px;
            overflow-y: auto;
            border: 1px solid var(--bill-border);
            border-radius: 16px;
        }
        .dp-contract-modal .dp-ledger-row-next {
            background: #eff6ff !important;
            box-shadow: inset 3px 0 0 var(--bill-primary);
        }
        .dp-contract-modal .dp-ledger-row-unpaid td {
            background: #fff7ed;
        }
        .dp-contract-modal details.dp-contract-details summary {
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 12px;
            list-style: none;
        }
        .dp-contract-modal details.dp-contract-details summary::-webkit-details-marker { display: none; }
        @media (max-width: 1024px) {
            .dp-contract-modal .modal-body { grid-template-columns: 1fr !important; }
            .dp-contract-modal .dp-pay-sidebar {
                border-left: none;
                padding-left: 0;
                border-top: 1px solid var(--bill-border);
                padding-top: 24px;
            }
            .dp-contract-modal .dp-summary-grid { grid-template-columns: repeat(2, 1fr); }
            .dp-contract-modal .dp-ledger-scroll {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
        @media (max-width: 768px) {
            .bill-modal.dp-contract-modal {
                padding: 0;
                align-items: stretch;
            }
            .dp-contract-modal .bill-modal-content {
                max-width: 100%;
                max-height: 100%;
                height: 100%;
                border-radius: 0;
            }
            .dp-contract-modal .modal-header {
                padding: 16px 20px;
                gap: 12px;
                flex-shrink: 0;
            }
            .dp-contract-modal .modal-header h2 {
                font-size: 22px !important;
            }
            .dp-contract-modal .modal-body {
                padding: 16px 20px 24px !important;
                gap: 20px !important;
                flex: 1;
                min-height: 0;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
            .dp-contract-modal .dp-summary-grid {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            .dp-contract-modal .dp-summary-card {
                padding: 12px;
            }
            .dp-contract-modal .dp-summary-card div[id^="modal"] {
                font-size: 18px !important;
            }
            .dp-contract-modal .dp-ledger-scroll {
                max-height: none;
                overflow: visible;
                border: none;
                border-radius: 0;
            }
            .dp-contract-modal .dp-ledger-table thead {
                display: none;
            }
            .dp-contract-modal .dp-ledger-table,
            .dp-contract-modal .dp-ledger-table tbody,
            .dp-contract-modal .dp-ledger-table tr {
                display: block;
                width: 100%;
            }
            .dp-contract-modal .dp-ledger-table tr {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 12px 14px;
                margin-bottom: 10px;
            }
            .dp-contract-modal .dp-ledger-table tr.dp-ledger-row-next {
                border-color: #bfdbfe;
            }
            .dp-contract-modal .dp-ledger-table tr.dp-ledger-row-unpaid td {
                background: transparent;
            }
            .dp-contract-modal .dp-ledger-table td {
                display: flex !important;
                justify-content: space-between;
                align-items: flex-start;
                gap: 12px;
                padding: 8px 0 !important;
                border: none !important;
                text-align: right !important;
                white-space: normal !important;
            }
            .dp-contract-modal .dp-ledger-table td::before {
                content: attr(data-label);
                font-weight: 700;
                font-size: 10px;
                text-transform: uppercase;
                color: #94a3b8;
                text-align: left;
                flex-shrink: 0;
            }
            .dp-contract-modal .dp-ledger-table td:first-child {
                font-size: 12px;
                padding-bottom: 4px !important;
                border-bottom: 1px solid #e2e8f0 !important;
                margin-bottom: 4px;
            }
            .dp-contract-modal .dp-ledger-table td:first-child::before {
                content: 'Installment';
            }
        }
        @media (max-width: 480px) {
            .dp-contract-modal .dp-summary-grid {
                grid-template-columns: 1fr;
            }
            .dp-contract-modal .modal-header h2 {
                font-size: 20px !important;
            }
        }
    </style>

    <div class="bill-container fade-in">
        <div class="bill-header">
            <div class="bill-title">
                <h1>Downpayment Fee Tracking</h1>
                <p>Manage and track downpayment installments for property acquisitions.</p>
            </div>
            <div class="bill-actions" style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-outline" style="background: #fff; border-color: #0284c7; color: #0284c7; font-weight: 700; display: inline-flex; align-items: center;" onclick="openDownpaymentReportModal()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Generate Report
                </button>
                <button class="btn btn-outline" style="border-color: var(--bill-primary); color: var(--bill-primary);"
                    onclick="openContractModal()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                        style="margin-right: 8px;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Create New DP Contract
                </button>
            </div>
        </div>

        <!-- Collection Summary Dashboard -->
        <div
            style="background: #fff; border: 1px solid var(--bill-border); border-radius: 16px; padding: 16px 20px; margin-bottom: 0; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 12px;">
                <h2 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;">Downpayment Portfolio Health</h2>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px;">
                <div
                    style="background: #f8fafc; padding: 12px 14px; border-radius: 10px; border-left: 4px solid var(--bill-primary);">
                    <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;">Active
                        Contracts</div>
                    <div style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 2px;">{{ count($bills) }}
                    </div>
                </div>
                <div
                    style="background: #f8fafc; padding: 12px 14px; border-radius: 10px; border-left: 4px solid var(--bill-success);">
                    <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;">Total
                        Collected</div>
                    <div style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 2px;">
                        @php 
                            $total = 0;
                            foreach ($bills as $b) {
                                $total += $b['paid_amount'];
                            }
                        @endphp
                        ₱{{ number_format($total, 2) }}
                    </div>
                </div>
                <div style="background: #f8fafc; padding: 12px 14px; border-radius: 10px; border-left: 4px solid #f59e0b;">
                    <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;">Pending
                        Balance</div>
                    <div style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 2px;">
                        @php 
                            $bal = 0;
                            foreach ($bills as $b) {
                                $bal += ($b['total_dp'] - $b['paid_amount']);
                            }
                        @endphp
                        ₱{{ number_format($bal, 2) }}
                    </div>
                </div>
                <div
                    style="background: #f8fafc; padding: 12px 14px; border-radius: 10px; border-left: 4px solid var(--bill-danger);">
                    <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;">Late
                    </div>
                    <div style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 2px;">
                        {{ collect($bills)->where('payment_behavior', 'Late')->count() }}
                    </div>
                </div>
            </div>
        </div>

        @php
            $dpChartData = getMonthlyChartData('downpayment');
            $dpTwelveMonthTotal = array_sum($dpChartData['data'] ?? []);
            $recentDpPayers = getRecentPayers('downpayment', 8);

            $dpTotalExpected = collect($bills)->sum('total_dp');
            $dpTotalCollected = collect($bills)->sum('paid_amount');
            $dpTotalOutstanding = max(0, $dpTotalExpected - $dpTotalCollected);
            $dpCerPct = $dpTotalExpected > 0 ? min(100, max(0, round(($dpTotalCollected / $dpTotalExpected) * 100))) : 0;
            $dpCircumference = 2 * M_PI * 52;
            $dpCerStroke = ($dpCerPct / 100) * $dpCircumference;

            $dpTotalContracts = count($bills);
            $dpFullyPaidCount = collect($bills)->filter(fn($b) => ($b['total_dp'] - $b['paid_amount']) <= 0.01 || strtolower((string)($b['status'] ?? '')) === 'fully paid')->count();
            $dpInProgressCount = collect($bills)->filter(fn($b) => ($b['paid_amount'] > 0) && (($b['total_dp'] - $b['paid_amount']) > 0.01) && strtolower((string)($b['status'] ?? '')) !== 'fully paid')->count();
            $dpUnstartedCount = max(0, $dpTotalContracts - $dpFullyPaidCount - $dpInProgressCount);
        @endphp

        <!-- 3 Inline Collection Analytics Cards -->
        <div class="analytics-grid cols-3">
            <!-- Card 1: Collection Efficiency -->
            <div class="analytic-card" style="display: flex; flex-direction: column;">
                <div class="card-title">
                    <span>Collection Efficiency</span>
                    <span class="card-badge">Revenue Ratio</span>
                </div>
                <div class="donut-chart-container">
                    <div class="donut-ring-wrap">
                        <svg viewBox="0 0 42 42" class="donut-svg">
                            <circle class="donut-segment segment-track" cx="21" cy="21" r="15.915" stroke-dasharray="100 0"></circle>
                            <circle class="donut-segment segment-paid" cx="21" cy="21" r="15.915"
                                stroke-dasharray="{{ $dpCerPct }} {{ 100 - $dpCerPct }}"
                                stroke-dashoffset="0"
                                style="{{ $dpCerPct <= 0 ? 'stroke-opacity: 0;' : '' }}"></circle>
                        </svg>
                        <div class="donut-center-label">
                            <div class="donut-center-pct">{{ $dpCerPct }}%</div>
                        </div>
                    </div>
                    <div class="cer-meta">
                        <div class="cer-meta-row">
                            <span>Total Equity</span>
                            <strong>₱{{ number_format($dpTotalExpected, 2) }}</strong>
                        </div>
                        <div class="cer-meta-row">
                            <span>Total Collected</span>
                            <strong style="color: var(--bill-success);">₱{{ number_format($dpTotalCollected, 2) }}</strong>
                        </div>
                        <div class="cer-meta-row">
                            <span>Outstanding</span>
                            <strong style="color: var(--bill-danger);">₱{{ number_format($dpTotalOutstanding, 2) }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Collection Status Breakdown -->
            <div class="analytic-card" style="display: flex; flex-direction: column;">
                <div class="card-title">
                    <span>Collection Breakdown</span>
                    <span class="card-badge">{{ $dpTotalContracts }} Contracts</span>
                </div>
                <div style="flex: 1; min-height: 220px; position: relative; display: flex; justify-content: center; align-items: center;">
                    <canvas id="downpaymentCollectionChart"></canvas>
                </div>
            </div>

            <!-- Card 3: Recent Payers -->
            <div class="analytic-card" style="display: flex; flex-direction: column;">
                <div class="card-title" style="margin-bottom: 12px;">
                    <span>Recent Payers</span>
                    <span class="card-badge" style="background: #ecfdf5; color: #059669;">Latest Paid</span>
                </div>
                @if(empty($recentDpPayers))
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px 16px; color: #94a3b8; text-align: center;">
                        <div style="font-size: 13px; font-weight: 700; color: #64748b;">No Recent Payments Yet</div>
                        <div style="font-size: 11px; margin-top: 2px;">Paid downpayment installments will appear here.</div>
                    </div>
                @else
                    <div style="display: flex; flex-direction: column; gap: 8px; max-height: 235px; overflow-y: auto; padding-right: 2px;">
                        @foreach($recentDpPayers as $payer)
                            @php
                                $initials = collect(explode(' ', trim($payer['name'])))->filter()->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->implode('');
                            @endphp
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 9px 11px; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                                    <div style="width: 30px; height: 30px; border-radius: 8px; background: #eef2ff; color: #4f46e5; font-size: 11px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        {{ $initials ?: 'B' }}
                                    </div>
                                    <div style="min-width: 0;">
                                        <div style="font-size: 12px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $payer['name'] }}</div>
                                        <div style="font-size: 10.5px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $payer['lot'] }} &bull; {{ $payer['date'] }}</div>
                                    </div>
                                </div>
                                <div style="text-align: right; flex-shrink: 0;">
                                    <div style="font-size: 12px; font-weight: 800; color: #059669;">₱{{ number_format($payer['amount'], 2) }}</div>
                                    <div style="font-size: 9.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">{{ $payer['method'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Monthly Collections (Full Width) -->
        <div class="analytic-card" style="display: flex; flex-direction: column;">
            <div class="card-title" style="margin-bottom: 10px;">
                <span>Monthly Collections</span>
                <span class="card-badge" style="background: #eef2ff; color: #4338ca;">₱{{ number_format($dpTwelveMonthTotal, 0) }}</span>
            </div>
            <p style="font-size: 11px; color: #64748b; margin: 0 0 12px 0;">12-month collection trend</p>
            <div style="height: 190px; position: relative;">
                <canvas id="downpaymentSalesChart"></canvas>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar" style="display: flex; gap: 12px; margin-bottom: 0; padding: 14px 18px; background: #fff; border-radius: 14px; border: 1px solid var(--bill-border); align-items: flex-end;">
            <div class="filter-group" style="flex: 1;">
                <span class="filter-label" style="display: block; font-size: 12px; color: #64748b; margin-bottom: 4px;">Search</span>
                <input type="text" id="billingSearch" class="filter-select" placeholder="Client or Lot..." style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
            <div class="filter-group">
                <span class="filter-label" style="display: block; font-size: 12px; color: #64748b; margin-bottom: 4px;">Payment Behavior</span>
                <select id="behaviorFilter" class="filter-select" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    <option value="all">All Behaviors</option>
                    <option value="Early">Early</option>
                    <option value="On-Time">On-Time</option>
                    <option value="Late">Late</option>
                    <option value="Not available yet">N/A</option>
                </select>
            </div>
            <div class="filter-group">
                <span class="filter-label" style="display: block; font-size: 12px; color: #64748b; margin-bottom: 4px;">Block</span>
                <select id="blockFilter" class="filter-select" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    <option value="all">All Blocks</option>
                    @php
                        $uniqueBlocks = collect($bills)->pluck('block')->filter(function($b) { return $b !== 'N/A'; })->unique()->sort();
                    @endphp
                    @foreach($uniqueBlocks as $b)
                        <option value="{{ $b }}">Block {{ $b }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Downpayment Ledger -->
        <div class="analytic-card">
            <div class="card-title">
                <span>Installment Contracts</span>
            </div>
            <div class="bill-table-container">
                <table class="bill-table">
                    <thead>
                        <tr>
                            <th data-sort="text">Client / Lot</th>
                            <th data-sort="number">Progress</th>
                            <th data-sort="text">Monthly Payments</th>
                            <th data-sort="date">Next Due</th>
                            <th data-sort="text">Payment Behavior</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="dpTableBody">
                        @forelse($bills as $bill)
                            @php
                                $progress = $bill['total_dp'] > 0 ? ($bill['paid_amount'] / $bill['total_dp']) * 100 : 0;
                                $behavior = $bill['payment_behavior'] ?? 'Not available yet';
                            @endphp
                            <tr class="bill-row" id="row-{{ $bill['id'] }}"
                                data-resident="{{ strtolower($bill['buyer']) }}"
                                data-behavior="{{ $behavior }}"
                                data-block="{{ $bill['block'] }}">
                                <td>
                                    <div style="font-weight: 700;">{{ $bill['buyer'] }}</div>
                                    <div style="font-size: 12px; color: #64748b;">{{ $bill['contact'] ?? 'N/A' }}</div>
                                    <div style="font-size: 12px; color: #94a3b8;">Block {{ $bill['block'] }} Lot {{ $bill['lot'] }}</div>
                                </td>
                                <td style="min-width: 200px;">
                                    <div
                                        style="display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 4px; font-weight: 700; color: #64748b;">
                                        <span>₱{{ number_format($bill['paid_amount'], 2) }} Paid</span>
                                        <span>₱{{ number_format($bill['total_dp'], 2) }} Total</span>
                                    </div>
                                    <div
                                        style="width: 100%; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                        <div
                                            style="height: 100%; width: {{ $progress }}%; background: var(--bill-primary);">
                                        </div>
                                    </div>
                                    <div style="font-size: 10px; color: #94a3b8; margin-top: 4px; text-align: right;">
                                        {{ $bill['months_paid'] }} of {{ $bill['total_months'] }} mos
                                        @if(($bill['missed_months'] ?? 0) > 0)
                                            <span style="color: var(--bill-danger); font-weight: 700;"> · {{ $bill['missed_months'] }} missed</span>
                                        @endif
                                        @if($bill['status'] === 'Fully Paid')
                                            <span style="color: #166534; font-weight: 700;"> · Fully Paid</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700;">₱{{ number_format($bill['monthly_amortization'], 2) }}</div>
                                </td>
                                <td>
                                    <div style="font-size: 13px;">
                                        {{ $bill['next_due'] != 'N/A' ? date('M d, Y', strtotime($bill['next_due'])) : 'N/A' }}
                                    </div>
                                    @if(($bill['overdue_days'] ?? 0) > 0)
                                        <div style="font-size: 11px; color: var(--bill-danger); font-weight: 700; margin-top: 4px;">
                                            Overdue {{ $bill['overdue_days'] }} {{ $bill['overdue_days'] == 1 ? 'day' : 'days' }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($behavior === 'Early')
                                        <span class="trend-chip trend-early">Early</span>
                                    @elseif($behavior === 'On-Time')
                                        <span class="trend-chip trend-early" style="background: var(--bill-primary); color: #fff;">On-Time</span>
                                    @elseif($behavior === 'Late')
                                        <span class="trend-chip trend-late">Late</span>
                                    @else
                                        <span class="trend-chip" style="background: #e2e8f0; color: #475569;">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-outline" style="padding: 6px 10px; font-size: 11px;"
                                        onclick="viewDetail('{{ $bill['id'] }}')">View</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 32px; text-align: center; color: #64748b;">No downpayment contracts yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.admin-client-pagination', ['prefix' => 'downpaymentTable'])
        </div>
    </div>

    <!-- Full Screen Immersive Modal -->
    <div id="billModal" class="bill-modal dp-contract-modal">
        <div class="bill-modal-content">
            <div class="modal-header">
                <div>
                    <span id="modalLot"
                        style="font-size: 12px; font-weight: 700; color: var(--bill-primary); text-transform: uppercase; letter-spacing: 0.1em;"></span>
                    <h2 id="modalResident" style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 8px;"></h2>
                    <div id="modalContact" style="font-size: 14px; color: #64748b; margin-top: 4px;"></div>
                </div>
                <button class="ann-btn-icon" onclick="closeModal()" style="padding: 12px; background: #f1f5f9;">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div id="modalMainContent">
                    <div class="dp-summary-grid">
                        <div class="dp-summary-card">
                            <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Remaining Balance</span>
                            <div id="modalAmount" style="font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 6px;"></div>
                        </div>
                        <div class="dp-summary-card">
                            <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Next Due</span>
                            <div id="modalDate" style="font-size: 18px; font-weight: 800; color: var(--bill-primary); margin-top: 6px;"></div>
                            <div id="modalOverdue" style="font-size: 11px; font-weight: 700; color: var(--bill-danger); margin-top: 4px;"></div>
                        </div>
                        <div class="dp-summary-card">
                            <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Months Paid</span>
                            <div id="modalMonthsPaid" style="font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 6px;"></div>
                        </div>
                        <div class="dp-summary-card">
                            <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Penalty Rate</span>
                            <div id="modalPenaltyRate" style="font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 6px;"></div>
                            <div id="modalPenaltyDue" style="font-size: 11px; font-weight: 700; color: var(--bill-danger); margin-top: 4px;"></div>
                        </div>
                    </div>

                    <details class="dp-contract-details" style="margin-bottom: 20px;">
                        <summary>Contract details</summary>
                        <div id="modalFacts" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-top: 12px;"></div>
                    </details>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; margin: 0; color: #64748b;">Installment Schedule</h3>
                        <button type="button" class="btn btn-outline" style="padding: 6px 12px; font-size: 11px;" onclick="scrollToNextPayableRow()">Go to next payment</button>
                    </div>
                    <div class="dp-ledger-scroll">
                        <table class="bill-table dp-ledger-table" style="margin: 0;">
                            <thead style="position: sticky; top: 0; z-index: 2;">
                                <tr>
                                    <th style="width: 48px;">#</th>
                                    <th>Due Date</th>
                                    <th style="text-align: right;">Amount</th>
                                    <th>Status</th>
                                    <th style="text-align: right;">Receipt</th>
                                </tr>
                            </thead>
                            <tbody id="modalInstallmentLedger"></tbody>
                        </table>
                    </div>
                </div>

                <div id="modalPaySidebar" class="dp-pay-sidebar">
                    <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; margin: 0 0 16px; color: #64748b;">Log Payment</h3>
                    <div id="modalNextPayableBox" style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
                        <div id="modalNextPayableHeading" style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Next installment</div>
                        <div id="modalNextPayableLabel" style="font-size: 16px; font-weight: 800; color: #0f172a; margin-top: 8px;">—</div>
                        <div id="modalNextPayableAmount" style="font-size: 14px; font-weight: 700; color: var(--bill-primary); margin-top: 4px;"></div>
                        <div id="modalNextPayablePenalty" style="font-size: 11px; font-weight: 700; color: var(--bill-danger); margin-top: 6px;"></div>
                    </div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Amount Paid (₱)</label>
                    <input type="text" id="directPaymentAmount" class="filter-select"
                        style="width: 100%; font-size: 16px; padding: 12px; margin-bottom: 8px;" placeholder="e.g. 43,750"
                        oninput="formatNumberInput(this)">
                    <div style="font-size: 11px; color: #94a3b8; margin-bottom: 16px;">Method: Office Payment</div>
                    <button class="btn btn-primary" id="submitPaymentBtn" style="width: 100%; justify-content: center; padding: 14px;" onclick="submitPayment()">Mark this month paid</button>
                </div>
            </div>
        </div>
    </div>

    <div id="receiptModal" style="display:none; position:fixed; inset:0; z-index:20000; background:rgba(15,23,42,0.75); backdrop-filter:blur(6px); align-items:center; justify-content:center; padding:24px; box-sizing:border-box;">
        <div style="background:#fff; max-width:680px; width:100%; border-radius:16px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.35); overflow:hidden; display:flex; flex-direction:column; height:90vh; max-height:860px;">
            <div style="display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc;">
                <span style="font-size:14px; font-weight:800; color:#0f172a; text-transform:uppercase; letter-spacing:0.05em;">Downpayment Receipt</span>
                <div style="display:flex; gap:10px; align-items:center;">
                    <button type="button" onclick="printReceiptFrame()" style="background:#047857; color:#fff; border:none; padding:8px 18px; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer;">Print</button>
                    <button type="button" onclick="closeReceiptModal()" style="background:#f1f5f9; border:none; padding:8px 12px; border-radius:8px; cursor:pointer; font-size:18px; line-height:1; color:#64748b;">✕</button>
                </div>
            </div>
            <div style="overflow-y:auto; flex:1; padding:0;">
                <iframe id="receiptFrame" style="width:100%; height:100%; min-height:560px; border:none; display:block;"></iframe>
            </div>
        </div>
    </div>

    <!-- Calculator & Create Contract Modal -->
    <div id="newContractModal" class="bill-modal"
        style="display: none; align-items: center; justify-content: center; z-index: 3000;">
        <div class="bill-modal-content" style="max-width: 550px; padding: 32px; border-radius: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h3 style="font-size: 20px; font-weight: 800; color: #0f172a;">DP Calculator & Contract Setup</h3>
                <button onclick="closeContractModal()"
                    style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            </div>

            @if($masterListBuyers->isEmpty())
                <div role="status"
                    style="margin-bottom: 16px; padding: 14px 16px; border-radius: 12px; font-size: 14px; font-weight: 600; border: 1px solid #fde68a; background: #fffbeb; color: #92400e;">
                    No buyers found in Buyer Master List. Please add buyers in the Master List first.
                </div>
            @endif

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Select
                    buyer from Master List</label>
                <select id="dpBuyerMasterList" class="filter-select" style="width: 100%; padding: 12px;"
                    onchange="autoFillDownpaymentBuyer(this)">
                    <option value="">-- Choose Buyer --</option>
                    @foreach($masterListBuyers as $b)
                        @foreach($b->reservations as $readyReservation)
                            @php
                                $dispBlock = $readyReservation->lot?->block ?? $b->block_no ?? '?';
                                $dispLot = $readyReservation->lot?->lot_number ?? $b->lot_no ?? '?';
                                $lotPrice = $readyReservation->lot?->contract_price ?? $b->lot?->contract_price;
                                $effectiveTcp = ($lotPrice !== null && $lotPrice !== '' && (float) $lotPrice > 0)
                                    ? $lotPrice
                                    : ($b->financingRecord?->contract_price ?? '');
                            @endphp
                            <option value="{{ $b->id }}" data-first="{{ $b->first_name }}" data-last="{{ $b->last_name }}"
                                data-block="{{ $dispBlock }}" data-lot="{{ $dispLot }}" data-tcp="{{ $effectiveTcp }}"
                                data-lot-price="{{ $lotPrice ?? '' }}"
                                data-reservation-id="{{ $readyReservation->id }}"
                                data-reservation-amount="{{ (float) ($readyReservation->amount ?? 0) }}">
                                {{ $b->last_name }}, {{ $b->first_name }}
                                {{ $b->middle_name ? substr($b->middle_name, 0, 1) . '.' : '' }} — (Block {{ $dispBlock }}, Lot {{ $dispLot }})
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; display: none;"
                id="manualNameContainer">
                <div style="display: flex; gap: 8px;">
                    <div style="flex:1;">
                        <label
                            style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">First
                            Name</label>
                        <input type="text" id="calcFirstName" class="filter-select" style="width: 100%; padding: 12px;"
                            placeholder="First Name">
                    </div>
                    <div style="flex:1;">
                        <label
                            style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Last
                            Name</label>
                        <input type="text" id="calcLastName" class="filter-select" style="width: 100%; padding: 12px;"
                            placeholder="Last Name">
                    </div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <div style="flex:1;">
                        <label
                            style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Blk</label>
                        <select id="calcBlk" class="filter-select" style="width: 100%; padding: 12px;">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                            <option value="7">7</option>
                            <option value="8">8</option>
                            <option value="9">9</option>
                            <option value="10">10</option>
                            <option value="11">11</option>
                            <option value="12">12</option>
                            <option value="13">13</option>
                            <option value="14">14</option>
                            <option value="15">15</option>
                            <option value="16">16</option>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label
                            style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Lot</label>
                        <select id="calcLot" class="filter-select" style="width: 100%; padding: 12px;">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                            <option value="7">7</option>
                            <option value="8">8</option>
                            <option value="9">9</option>
                            <option value="10">10</option>
                            <option value="11">11</option>
                            <option value="12">12</option>
                            <option value="13">13</option>
                            <option value="14">14</option>
                            <option value="15">15</option>
                        </select>
                    </div>
                </div>
            </div>

            <div
                style="padding: 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; margin-bottom: 24px;">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label
                            style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Unit
                            Price (TCP) ₱</label>
                        <input type="text" id="calcTcp" class="filter-select"
                            style="width: 100%; padding: 12px; font-weight: 700; transition: border-color 0.2s;" placeholder="2,000,000"
                            inputmode="numeric" autocomplete="off"
                            onkeydown="filterTcpKeydown(event)"
                            oninput="handleTcpInput(this)"
                            onblur="handleTcpBlur()">
                        <div id="calcTcpError" style="display: none; color: #ef4444; font-size: 12px; font-weight: 600; margin-top: 6px;"></div>
                    </div>
                    <div>
                        <label
                            style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">DP
                            Percentage %</label>
                        <input type="number" id="calcDpPercent" class="filter-select"
                            style="width: 100%; padding: 12px; font-weight: 700;" value="30" min="1" max="100"
                            onkeydown="filterPositiveNumberKeydown(event)"
                            oninput="sanitizePositiveNumber(this, 100)">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
                    <div>
                        <label
                            style="display: block; font-size: 12px; font-weight: 700; color: var(--bill-primary); margin-bottom: 8px;">Total
                            DP Amount ₱</label>
                        <input type="text" id="calcDpAmount" class="filter-select"
                            style="width: 100%; padding: 12px; background: #e2e8f0; font-weight: 800;" readonly>
                        <div id="calcReservationNote" style="display: none; font-size: 11px; font-weight: 600; color: #059669; margin-top: 6px;"></div>
                    </div>
                    <div>
                        <label
                            style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Months
                            to Pay</label>
                        <input type="number" id="calcMonths" class="filter-select" style="width: 100%; padding: 12px;"
                            value="24" min="1" max="120"
                            onkeydown="filterPositiveNumberKeydown(event)"
                            oninput="sanitizePositiveNumber(this)">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr; margin-top: 16px;">
                    <div>
                        <label
                            style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Penalty %</label>
                        <input type="number" id="calcPenalty" class="filter-select" style="width: 100%; padding: 12px;"
                            value="0" min="0" max="100"
                            onkeydown="filterPositiveNumberKeydown(event)"
                            oninput="sanitizePositiveNumber(this, 100)">
                    </div>
                </div>
                <div style="margin-top: 16px; border-top: 1px dashed #cbd5e1; padding-top: 16px;">
                    <label
                        style="display: block; font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: 8px; text-transform: uppercase;">Calculated
                        Monthly Amortization</label>
                    <div id="calcAmortization" style="font-size: 32px; font-weight: 800; color: var(--bill-primary);">₱0.00
                    </div>
                </div>
            </div>

            <button class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px;"
                onclick="submitContract()">Save & Initialize Contract</button>
        </div>
    </div>

    <script src="{{ asset('js/gis-dropdowns.js') }}"></script>
    <script>
        const allBillsRaw = @json($bills);

        let currentModalId = null;

        function peso(amount) {
            return '₱' + Number(amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function formatIsoDate(iso, longMonth) {
            if (!iso || iso === 'N/A') return 'N/A';
            const parts = String(iso).split('-');
            if (parts.length < 3) return iso;
            const date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
            return date.toLocaleDateString('en-US', { month: longMonth ? 'long' : 'short', day: 'numeric', year: 'numeric' });
        }

        function installmentStatusBadge(status) {
            if (status === 'Paid') return '<span class="badge badge-success">Paid</span>';
            if (status === 'Unpaid') return '<span class="badge badge-danger">Unpaid</span>';
            if (status === 'Upcoming') return '<span class="badge" style="background:#e0e7ff;color:#4338ca;">Upcoming</span>';
            return '<span class="badge">—</span>';
        }

        function scrollToNextPayableRow() {
            const row = document.querySelector('#modalInstallmentLedger tr[data-next="true"]');
            if (row) {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        function formatNumberInput(input) {
            let clean = input.value.replace(/[^\d.]/g, '');
            const parts = clean.split('.');
            if (parts.length > 2) {
                clean = parts[0] + '.' + parts.slice(1).join('');
            }
            let whole = parts[0];
            let decimal = parts.length > 1 ? '.' + parts[1].slice(0, 2) : '';
            if (whole) {
                input.value = Number(whole).toLocaleString('en-US') + decimal;
            } else {
                input.value = '';
            }
        }

        function filterTcpKeydown(e) {
            const allowedKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab', 'Home', 'End', 'Enter'];
            if (allowedKeys.includes(e.key) || e.ctrlKey || e.metaKey) {
                return;
            }
            if (e.key === '-' || e.key === '+' || /^[a-zA-Z]$/.test(e.key)) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Input',
                        text: 'Letters and signs like negative (-) are not allowed. Please enter positive digits only.',
                        toast: true,
                        position: 'top-end',
                        timer: 2500,
                        showConfirmButton: false
                    });
                }
                return;
            }
            // Disallow any other non-digit character
            if (!/^\d$/.test(e.key)) {
                e.preventDefault();
            }
        }

        function filterPositiveNumberKeydown(e) {
            // Disallow negative '-', '+', exponent 'e' / 'E', letters
            if (['-', '+', 'e', 'E'].includes(e.key) || /^[a-zA-Z]$/.test(e.key)) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Input',
                        text: 'Negative signs and letters are not allowed in this field.',
                        toast: true,
                        position: 'top-end',
                        timer: 2500,
                        showConfirmButton: false
                    });
                }
            }
        }

        function sanitizePositiveNumber(input, max = null) {
            let val = parseFloat(input.value);
            if (isNaN(val) || val < 0) {
                input.value = '';
            } else if (max !== null && val > max) {
                input.value = max;
            }
            calculateAmortization();
        }

        function handleTcpInput(input) {
            const original = input.value;
            if (/[a-zA-Z\-]/.test(original) && typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Input',
                    text: 'Letters and negative signs were removed. Only positive numbers are allowed.',
                    toast: true,
                    position: 'top-end',
                    timer: 2500,
                    showConfirmButton: false
                });
            }
            let rawDigits = original.replace(/\D/g, '');
            if (rawDigits !== '') {
                input.value = Number(rawDigits).toLocaleString('en-US');
            } else {
                input.value = '';
            }

            const errBox = document.getElementById('calcTcpError');
            if (errBox && errBox.style.display !== 'none') {
                validateTcpInput(true);
            }

            calculateAmortization();
        }

        function handleTcpBlur() {
            const input = document.getElementById('calcTcp');
            if (!input) return;
            const rawDigits = input.value.replace(/\D/g, '');
            if (input.value.trim() !== '') {
                if (rawDigits.length < 6) {
                    validateTcpInput(true);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid Unit Price (TCP)',
                            text: `Unit Price (TCP) must be at least 6 digits (minimum ₱100,000). Currently ${rawDigits.length} digit${rawDigits.length === 1 ? '' : 's'}.`
                        });
                    }
                } else {
                    validateTcpInput(true);
                }
            } else {
                validateTcpInput(true);
            }
        }

        function validateTcpInput(showVisual = false) {
            const input = document.getElementById('calcTcp');
            const errBox = document.getElementById('calcTcpError');
            if (!input) return { valid: false, message: 'Unit Price input not found.' };

            const rawDigits = input.value.replace(/\D/g, '');

            if (!rawDigits || rawDigits.length === 0) {
                if (showVisual && errBox) {
                    input.style.borderColor = '#ef4444';
                    errBox.textContent = 'Please enter a valid Unit Price (TCP) number.';
                    errBox.style.display = 'block';
                }
                return { valid: false, message: 'Please enter a valid Unit Price (TCP) number.' };
            }

            if (rawDigits.length < 6) {
                if (showVisual && errBox) {
                    input.style.borderColor = '#ef4444';
                    errBox.textContent = `Unit Price must be at least 6 digits (currently ${rawDigits.length} digit${rawDigits.length === 1 ? '' : 's'}). Minimum ₱100,000.`;
                    errBox.style.display = 'block';
                }
                return { valid: false, message: 'Unit Price (TCP) must be at least 6 digits (minimum ₱100,000).' };
            }

            if (showVisual && errBox) {
                input.style.borderColor = '#cbd5e1';
                errBox.textContent = '';
                errBox.style.display = 'none';
            }
            return { valid: true, value: Number(rawDigits) };
        }

        function preselectDownpaymentBuyer(buyerId, reservationId = null) {
            const select = document.getElementById('dpBuyerMasterList');
            if (!select || !buyerId) return false;
            const options = Array.from(select.options);
            let targetIndex = -1;
            if (reservationId) {
                targetIndex = options.findIndex(o =>
                    o.value === String(buyerId) &&
                    o.getAttribute('data-reservation-id') === String(reservationId)
                );
            }
            if (targetIndex === -1) {
                targetIndex = options.findIndex(o => o.value === String(buyerId));
            }
            if (targetIndex === -1) return false;

            select.selectedIndex = targetIndex;
            if (window.jQuery && $.fn.select2) {
                $('#dpBuyerMasterList').val(String(buyerId)).trigger('change.select2');
            }
            autoFillDownpaymentBuyer(select);
            return true;
        }

        function openContractModal() {
            const errBox = document.getElementById('calcTcpError');
            if (errBox) {
                errBox.textContent = '';
                errBox.style.display = 'none';
            }
            const tcpInput = document.getElementById('calcTcp');
            if (tcpInput) {
                tcpInput.style.borderColor = '#cbd5e1';
                tcpInput.value = '';
            }
            const buyerSelect = document.getElementById('dpBuyerMasterList');
            if (buyerSelect) {
                buyerSelect.selectedIndex = 0;
            }
            selectedReservationId = '';
            currentReservationFee = 0;
            document.getElementById('calcDpPercent').value = 30;
            document.getElementById('calcMonths').value = 24;
            document.getElementById('calcPenalty').value = 0;
            document.getElementById('newContractModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            calculateAmortization();
        }

        function closeContractModal() {
            window.history.replaceState(null, '', window.location.pathname);
            const errBox = document.getElementById('calcTcpError');
            if (errBox) {
                errBox.textContent = '';
                errBox.style.display = 'none';
            }
            const tcpInput = document.getElementById('calcTcp');
            if (tcpInput) {
                tcpInput.style.borderColor = '#cbd5e1';
            }
            document.getElementById('newContractModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function closeModal() {
            window.history.replaceState(null, '', window.location.pathname);
            document.getElementById('billModal').style.display = 'none';
            document.body.style.overflow = 'auto';
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

        function printDpReceipt(historyId) {
            const bill = allBillsRaw.find(b => String(b.id) === String(currentModalId));
            if (!bill) return;
            const ledger = bill.installment_ledger || [];
            const row = ledger.find(r => String(r.history_id) === String(historyId))
                || (bill.history || []).find(h => String(h.id) === String(historyId));
            if (!row) return;

            const money = (n) => '₱' + Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const monthly = Number(bill.monthly_amortization || 0);
            const amount = Number(row.amount || 0);
            let principal = Number(row.principal || 0);
            let penalty = Number(row.penalty || 0);
            if (!principal && amount) {
                principal = amount > monthly + 0.009 ? monthly : amount;
                penalty = Math.max(0, amount - principal);
            }
            const penaltyRate = Number(bill.penalty_percentage || 0);
            const trn = row.trn || 'N/A';
            const mop = row.payment_method || 'Office Payment';
            const paidDate = row.paid_at || row.date || 'N/A';
            const dueDate = row.due_label || row.month || 'N/A';
            let installmentIdx = Number(row.index || 0);
            if (!installmentIdx && bill.history) {
                const hIdx = (bill.history || []).findIndex(h => String(h.id) === String(historyId));
                if (hIdx !== -1) installmentIdx = bill.history.length - hIdx;
            }
            const installmentNo = installmentIdx > 0 ? installmentIdx : (row.index || '—');
            const behavior = row.behavior && row.behavior !== 'Not available yet' ? row.behavior : '—';
            const totalDp = Number(bill.total_dp || 0);
            let principalThroughThis = 0;
            ledger.forEach((r) => {
                if (r.status === 'Paid' && Number(r.index || 0) <= Number(installmentIdx || 0)) {
                    const p = Number(r.principal || 0) || (Number(r.amount || 0) > monthly + 0.009 ? monthly : Number(r.amount || 0));
                    principalThroughThis += p;
                }
            });
            if (principalThroughThis <= 0) principalThroughThis = principal;
            const remainingAfter = Math.max(0, totalDp - principalThroughThis);
            const monthsAfter = installmentNo !== '—' ? `${installmentNo} of ${bill.total_months}` : `${bill.months_paid} of ${bill.total_months}`;

            const invoiceNo = String(historyId || '0001').padStart(4, '0');
            const mopLower = String(mop || '').toLowerCase();
            const cleanMop = mopLower.includes('gcash') ? 'GCash' : (mopLower.includes('maya') ? 'Maya' : (mopLower.includes('office') ? 'Office' : mop));

            const html = `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sales Invoice - Downpayment Receipt</title>
<style>
  @page { size: portrait; margin: 0; }
  body {
    width: 100%;
    max-width: 760px;
    margin: 0 auto;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    color: #000;
    font-size: 11px;
    line-height: 1.35;
    padding: 12mm 14mm;
    background: #fff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    box-sizing: border-box;
  }
  .invoice-wrapper { border: 1.5px solid #000; padding: 14px 16px; background: #fff; position: relative; }
  .corp-header { text-align: center; margin-bottom: 10px; position: relative; }
  .corp-header h1 { margin: 0; font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4px; color: #000; }
  .corp-header p { margin: 2px 0 0; font-size: 10px; color: #000; }
  .title-row { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
  .title-row td { border: none; padding: 0; vertical-align: middle; }
  .sales-invoice-badge {
    display: inline-block; background: #000; color: #fff; font-weight: bold;
    font-size: 13px; letter-spacing: 0.8px; padding: 4px 14px; text-transform: uppercase;
  }
  .invoice-no { text-align: right; font-size: 13px; font-weight: bold; color: #000; }
  .invoice-no .num { font-family: monospace; font-size: 13px; color: #000; margin-left: 4px; }
  .meta-grid { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
  .meta-grid td { border: none; padding: 3px 4px; vertical-align: bottom; font-size: 10.5px; color: #000; }
  .meta-label { white-space: nowrap; width: 1%; }
  .meta-line { border-bottom: 1px solid #000; font-weight: bold; padding-left: 6px; color: #000; }
  .items-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
  .items-table th, .items-table td { border: 1px solid #000; padding: 5px 6px; font-size: 10.5px; vertical-align: top; color: #000; }
  .items-table th { text-align: center; font-weight: bold; background: #fff; text-transform: uppercase; font-size: 10px; color: #000; }
  .text-center { text-align: center; }
  .text-right { text-align: right; }
  .fw-bold { font-weight: bold; }
  .tax-label { font-size: 9.5px; }
  .total-due-row td { border-top: 2px solid #000; border-bottom: 2px solid #000; font-size: 12px; font-weight: bold; background: #fff; padding: 6px; color: #000; }
  .footer-grid { width: 100%; border-collapse: collapse; margin-top: 14px; }
  .footer-grid td { border: none; padding: 0; vertical-align: bottom; color: #000; }
  .sig-line { border-top: 1px solid #000; width: 220px; margin-left: auto; padding-top: 4px; text-align: center; font-size: 10px; font-weight: bold; color: #000; }
  .validity-note { text-align: center; font-size: 9px; font-style: italic; margin-top: 10px; color: #000; }
</style>
</head>
<body>
<div class="invoice-wrapper">
  <div class="corp-header">
    <h1>Mayon Builders Realty &amp; Development Corporation</h1>
    <p>Tagbong, 4418, Pili, (Capital), Camarines Sur, Philippines</p>
    <p>VAT Reg. TIN 009-860-405-00001</p>
  </div>
  <table class="title-row">
    <tr>
      <td style="width:50%;"><span class="sales-invoice-badge">SALES INVOICE</span></td>
      <td class="invoice-no" style="width:50%;">N&#186; <span class="num">${invoiceNo}</span></td>
    </tr>
  </table>
  <table class="meta-grid">
    <tr>
      <td class="meta-label">Sold to:</td>
      <td class="meta-line" style="width:44%;">${bill.buyer}</td>
      <td style="width:3%;"></td>
      <td class="meta-label">Date:</td>
      <td class="meta-line" style="width:32%;">${paidDate}</td>
    </tr>
    <tr>
      <td class="meta-label">Address:</td>
      <td class="meta-line">Block ${bill.block}, Lot ${bill.lot}</td>
      <td></td>
      <td class="meta-label">Terms:</td>
      <td class="meta-line">Installment ${installmentNo} of ${bill.total_months}</td>
    </tr>
    <tr>
      <td class="meta-label">Business Style:</td>
      <td class="meta-line">Althesa Village</td>
      <td></td>
      <td class="meta-label">OSCA/PWD ID No.:</td>
      <td class="meta-line">&nbsp;</td>
    </tr>
    <tr>
      <td class="meta-label">Payment Method:</td>
      <td class="meta-line">${cleanMop}</td>
      <td></td>
      <td class="meta-label">Cardholder's:</td>
      <td class="meta-line">&nbsp;</td>
    </tr>
    <tr>
      <td class="meta-label">Due Date:</td>
      <td class="meta-line">${dueDate}</td>
      <td></td>
      <td class="meta-label">Signature:</td>
      <td class="meta-line">&nbsp;</td>
    </tr>
  </table>
  <table class="items-table">
    <thead>
      <tr>
        <th style="width:7%;">QTY</th>
        <th style="width:8%;">UNIT</th>
        <th style="width:49%;">DESCRIPTION</th>
        <th style="width:17%;">UNIT PRICE</th>
        <th style="width:19%;">AMOUNT</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="text-center">1</td>
        <td class="text-center">mo.</td>
        <td><strong>Downpayment — Installment ${installmentNo} of ${bill.total_months}</strong></td>
        <td class="text-right">${money(principal)}</td>
        <td class="text-right">${money(principal)}</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>Block ${bill.block}, Lot ${bill.lot} (TCP: ${money(bill.tcp)})</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>Total Downpayment: ${money(totalDp)} &bull; Applied: ${money(principal)} &bull; Remaining: ${money(remainingAfter)} (${monthsAfter})</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td colspan="2" rowspan="6" style="border-right:none;">&nbsp;</td>
        <td class="tax-label">VATable Sales</td>
        <td class="tax-label">Total Sales (VAT Inclusive)</td>
        <td class="text-right fw-bold">${money(amount)}</td>
      </tr>
      <tr>
        <td class="tax-label">VAT-Exempt Sales &nbsp;&nbsp; <strong>${money(amount)}</strong></td>
        <td class="tax-label">Less: VAT</td>
        <td class="text-right">&#8212;</td>
      </tr>
      <tr>
        <td class="tax-label">Zero Rated Sales</td>
        <td class="tax-label">Amount: Net of VAT</td>
        <td class="text-right">${money(amount)}</td>
      </tr>
      <tr>
        <td class="tax-label">VAT Amount</td>
        <td class="tax-label">Less: SC/PWD Discount</td>
        <td class="text-right">&#8212;</td>
      </tr>
      <tr>
        <td rowspan="2">&nbsp;</td>
        <td class="tax-label">Amount Due</td>
        <td class="text-right">${money(amount)}</td>
      </tr>
      <tr>
        <td class="tax-label">Add: VAT</td>
        <td class="text-right">&#8212;</td>
      </tr>
      <tr class="total-due-row">
        <td colspan="4" class="text-right" style="text-transform:uppercase;">TOTAL AMOUNT DUE</td>
        <td class="text-right">${money(amount)}</td>
      </tr>
    </tbody>
  </table>
  <table class="footer-grid">
    <tr>
      <td style="width:55%;font-size:9px;color:#000;">
        Official Downpayment Receipt &bull; Payment Method: <strong>${cleanMop}</strong><br>
        Recorded: ${paidDate}
      </td>
      <td style="width:45%;text-align:right;padding-top:22px;">
        <div class="sig-line">Cashier / Authorized Signature</div>
      </td>
    </tr>
  </table>
  <div class="validity-note">"THIS INVOICE SHALL BE VALID FOR FIVE (5) YEARS FROM THE DATE OF ATP."</div>
</div>
</body>
</html>`;

            const frame = document.getElementById('receiptFrame');
            const receiptModal = document.getElementById('receiptModal');
            receiptModal.style.display = 'flex';
            frame.srcdoc = html;
        }



        async function submitPayment() {
            if (!currentModalId) return;
            const amountStr = document.getElementById('directPaymentAmount').value.replace(/,/g, '');
            const amount = parseFloat(amountStr);
            const now = new Date();
            const paymentDate = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);

            if (!amount || amount <= 0) return Swal.fire('Enter valid amount');

            const bill = allBillsRaw.find(b => b.id === currentModalId);
            const np = bill?.next_payable;
            const dueLabel = np?.due_label ? ` for ${np.due_label}` : '';

            const result = await Swal.fire({
                title: 'Confirm Payment',
                text: `Log ₱${amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}${dueLabel}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, log payment!'
            });

            if (!result.isConfirmed) return;

            try {
                Swal.fire({ title: 'Logging payment...', didOpen: () => Swal.showLoading(), allowOutsideClick: false });

                const res = await fetch('/admin/downpayment-fee/pay', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ id: currentModalId, amount: amount, payment_date: paymentDate })
                });

                const data = await res.json();
                Swal.close();

                if (res.ok && data.success) {
                    const bill = allBillsRaw.find(b => b.id === currentModalId);
                    if (window.pushSystemNotification && bill) {
                        window.pushSystemNotification("Payment Logged", `₱${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} posted to ${bill.buyer}'s contract.`, "System");
                    }
                    window.location.reload();
                } else {
                    Swal.fire('Failed to save payment');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error connecting to server');
            }
        }

        function viewDetail(id) {
            const bill = allBillsRaw.find(b => String(b.id) === String(id));
            if (!bill) return;

            currentModalId = bill.id;
            const balance = bill.total_dp - bill.paid_amount;
            const np = bill.next_payable;

            document.getElementById('modalResident').textContent = bill.buyer;
            document.getElementById('modalContact').textContent = bill.contact || 'N/A';
            document.getElementById('modalLot').textContent = `Block ${bill.block} - Lot ${bill.lot}`;
            document.getElementById('modalAmount').textContent = peso(balance);
            document.getElementById('modalMonthsPaid').textContent = `${bill.months_paid} of ${bill.total_months}`;
            document.getElementById('modalPenaltyRate').textContent = `${Number(bill.penalty_percentage || 0)}%`;
            document.getElementById('modalDate').textContent = np ? np.due_label : formatIsoDate(bill.next_due, true);

            const overdueEl = document.getElementById('modalOverdue');
            if ((bill.overdue_days || 0) > 0) {
                overdueEl.textContent = `Overdue ${bill.overdue_days} ${bill.overdue_days === 1 ? 'day' : 'days'}`;
            } else {
                overdueEl.textContent = '';
            }

            const fact = (label, value) => `<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px;"><div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">${label}</div><div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 4px;">${value}</div></div>`;
            document.getElementById('modalFacts').innerHTML = [
                fact('TCP', peso(bill.tcp)),
                fact('Total DP', peso(bill.total_dp)),
                fact('Monthly Payment', peso(bill.monthly_amortization)),
                fact('Term', `${bill.total_months} months`),
                fact('Contract Date', formatIsoDate(bill.contract_date, true)),
                fact('Missed Months', String(bill.missed_months || 0))
            ].join('');

            const ledger = bill.installment_ledger || [];
            const ledgerBody = document.getElementById('modalInstallmentLedger');
            if (ledger.length === 0) {
                ledgerBody.innerHTML = `<tr><td colspan="5" style="padding: 24px; text-align: center; color: #64748b;">No installment schedule available.</td></tr>`;
            } else {
                ledgerBody.innerHTML = ledger.map(row => {
                    const rowClass = [
                        row.is_next ? 'dp-ledger-row-next' : '',
                        row.status === 'Unpaid' ? 'dp-ledger-row-unpaid' : ''
                    ].filter(Boolean).join(' ');
                    const receipt = row.history_id
                        ? `<button type="button" onclick="printDpReceipt('${row.history_id}')" style="background:#047857; color:#fff; border:none; padding:4px 10px; border-radius:6px; font-size:11px; font-weight:700; cursor:pointer;">Receipt</button>`
                        : '';
                    const penaltyNote = row.status !== 'Paid' && Number(row.penalty || 0) > 0
                        ? `<div style="font-size:10px;color:var(--bill-danger);font-weight:700;">incl. ${peso(row.penalty)} penalty</div>`
                        : '';
                    return `
                    <tr class="${rowClass}" data-due="${row.due_iso}" data-next="${row.is_next ? 'true' : 'false'}">
                        <td data-label="#">${row.index}</td>
                        <td data-label="Due Date" style="font-weight: ${row.is_next ? '800' : '600'}; color: ${row.is_next ? 'var(--bill-primary)' : '#0f172a'};">${row.due_label}</td>
                        <td data-label="Amount" style="font-weight: 700;">${peso(row.amount)}${penaltyNote}</td>
                        <td data-label="Status">${installmentStatusBadge(row.status)}</td>
                        <td data-label="Receipt">${receipt || '—'}</td>
                    </tr>`;
                }).join('');
            }

            const fullyPaid = bill.status === 'Fully Paid' || balance <= 0;
            const nextHeading = document.getElementById('modalNextPayableHeading');
            if (nextHeading) nextHeading.textContent = 'Next installment';
            const nextLabel = document.getElementById('modalNextPayableLabel');
            const nextAmount = document.getElementById('modalNextPayableAmount');
            const nextBox = document.getElementById('modalNextPayableBox');
            const nextPenalty = document.getElementById('modalNextPayablePenalty');
            const penaltyDueEl = document.getElementById('modalPenaltyDue');
            if (fullyPaid || !np) {
                nextLabel.textContent = fullyPaid ? 'Contract fully paid' : 'No payment due yet';
                nextAmount.textContent = '';
                nextPenalty.textContent = '';
                penaltyDueEl.textContent = '';
                nextBox.style.opacity = fullyPaid ? '0.7' : '1';
            } else {
                nextLabel.textContent = `Month ${np.index} · ${np.due_label}`;
                nextAmount.textContent = peso(np.amount);
                if (Number(np.penalty || 0) > 0) {
                    nextPenalty.textContent = `Includes ${peso(np.penalty)} late penalty`;
                    penaltyDueEl.textContent = `Due now: ${peso(np.penalty)}`;
                } else {
                    nextPenalty.textContent = '';
                    penaltyDueEl.textContent = '';
                }
                nextBox.style.opacity = '1';
            }

            const payAmount = np ? np.amount : bill.monthly_amortization;
            document.getElementById('directPaymentAmount').value = payAmount ? Number(payAmount).toLocaleString('en-US') : '';
            document.getElementById('directPaymentAmount').disabled = fullyPaid || !np;
            document.getElementById('submitPaymentBtn').disabled = fullyPaid || !np;
            document.getElementById('submitPaymentBtn').textContent = fullyPaid ? 'Fully Paid' : 'Mark this month paid';

            document.getElementById('billModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        // Calculator Logic
        const inpTcp = document.getElementById('calcTcp');
        const inpPercent = document.getElementById('calcDpPercent');
        const inpMonths = document.getElementById('calcMonths');
        const outDp = document.getElementById('calcDpAmount');
        const outAmort = document.getElementById('calcAmortization');
        const outResNote = document.getElementById('calcReservationNote');
        let currentReservationFee = 0;
        let selectedReservationId = '';

        function calculateAmortization() {
            const tcpStr = inpTcp.value.replace(/,/g, '');
            const tcp = parseFloat(tcpStr) || 0;
            const percent = parseFloat(inpPercent.value) || 0;
            const months = parseInt(inpMonths.value) || 1;

            const grossDp = tcp * (percent / 100);
            const totalDp = Math.max(0, grossDp - currentReservationFee);
            const amortization = totalDp / months;

            outDp.value = totalDp.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            outAmort.textContent = `₱${amortization.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            if (outResNote) {
                if (currentReservationFee > 0) {
                    outResNote.textContent = `Reservation fee deducted: -₱${currentReservationFee.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    outResNote.style.display = 'block';
                } else {
                    outResNote.textContent = '';
                    outResNote.style.display = 'none';
                }
            }
        }

        inpTcp.addEventListener('input', calculateAmortization);
        inpPercent.addEventListener('input', calculateAmortization);
        inpMonths.addEventListener('input', calculateAmortization);

        let allLotsCache = [];
        fetch('/api/lots')
            .then(r => r.ok ? r.json() : [])
            .then(data => { allLotsCache = Array.isArray(data) ? data : []; })
            .catch(() => {});

        function checkLotPriceForSelection() {
            const buyerSelect = document.getElementById('dpBuyerMasterList');
            const manualContainer = document.getElementById('manualNameContainer');
            const isManual = manualContainer && manualContainer.style.display !== 'none';
            if (!isManual && buyerSelect && buyerSelect.value) {
                return;
            }

            const blk = document.getElementById('calcBlk')?.value;
            const lot = document.getElementById('calcLot')?.value;
            if (!blk || !lot || !allLotsCache.length) return;

            const match = allLotsCache.find(l => String(l.block) === String(blk) && String(l.lot_number) === String(lot));
            if (match && match.contract_price && Number(match.contract_price) > 0) {
                document.getElementById('calcTcp').value = Number(match.contract_price).toLocaleString('en-US');
                validateTcpInput(true);
                calculateAmortization();
            } else {
                selectedReservationId = '';
                currentReservationFee = 0;
                calculateAmortization();
            }
        }

        document.getElementById('calcBlk')?.addEventListener('change', checkLotPriceForSelection);
        document.getElementById('calcLot')?.addEventListener('change', checkLotPriceForSelection);

        function autoFillDownpaymentBuyer(select) {
            const option = select.options[select.selectedIndex];
            if (option && option.value) {
                document.getElementById('calcFirstName').value = option.getAttribute('data-first') || '';
                document.getElementById('calcLastName').value = option.getAttribute('data-last') || '';

                selectedReservationId = option.getAttribute('data-reservation-id') || '';
                const blk = option.getAttribute('data-block');
                const lot = option.getAttribute('data-lot');
                if (blk) {
                    let bOpt = Array.from(document.getElementById('calcBlk').options).find(o => o.text.includes(blk) || o.value == blk);
                    if (!bOpt) {
                        bOpt = new Option(blk, blk, true, true);
                        document.getElementById('calcBlk').add(bOpt);
                    }
                    bOpt.selected = true;
                }
                if (lot) {
                    let lOpt = Array.from(document.getElementById('calcLot').options).find(o => o.text.includes(lot) || o.value == lot);
                    if (!lOpt) {
                        lOpt = new Option(lot, lot, true, true);
                        document.getElementById('calcLot').add(lOpt);
                    }
                    lOpt.selected = true;
                }

                const tcp = option.getAttribute('data-tcp');
                const resAmt = parseFloat(option.getAttribute('data-reservation-amount'));
                currentReservationFee = !isNaN(resAmt) && resAmt > 0 ? resAmt : 0;
                if (tcp) {
                    document.getElementById('calcTcp').value = Number(tcp).toLocaleString('en-US');
                    validateTcpInput(true);
                } else {
                    document.getElementById('calcTcp').value = '';
                    const errBox = document.getElementById('calcTcpError');
                    if (errBox) {
                        errBox.textContent = '';
                        errBox.style.display = 'none';
                    }
                    const tcpInput = document.getElementById('calcTcp');
                    if (tcpInput) {
                        tcpInput.style.borderColor = '#cbd5e1';
                    }
                }

                calculateAmortization();
            } else {
                selectedReservationId = '';
                currentReservationFee = 0;
                calculateAmortization();
            }
        }

        async function submitContract() {
            const btn = document.querySelector('#newContractModal .btn-primary');
            const restoreBtn = () => {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Save & Initialize Contract';
                    btn.style.opacity = '1';
                }
            };

            const buyerSelect = document.getElementById('dpBuyerMasterList');
            const buyerId = buyerSelect.value;
            if (!buyerId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select Buyer',
                    text: 'Please select a buyer from the Master List.'
                });
                restoreBtn();
                return;
            }

            const tcpValidation = validateTcpInput(true);
            if (!tcpValidation.valid) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Unit Price (TCP)',
                    text: tcpValidation.message
                });
                document.getElementById('calcTcp').focus();
                restoreBtn();
                return;
            }

            const percent = parseFloat(inpPercent.value) || 0;
            if (percent <= 0 || percent > 100) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid DP Percentage',
                    text: 'DP Percentage must be between 1% and 100%.'
                });
                restoreBtn();
                return;
            }

            const months = parseInt(inpMonths.value) || 0;
            if (months < 1) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Months to Pay',
                    text: 'Months to pay must be at least 1 month.'
                });
                restoreBtn();
                return;
            }

            const penalty = parseFloat(document.getElementById('calcPenalty').value);
            if (isNaN(penalty) || penalty < 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Penalty',
                    text: 'Penalty percentage cannot be negative.'
                });
                restoreBtn();
                return;
            }

            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Saving...';
                btn.style.opacity = '0.7';
            }

            const tcp = tcpValidation.value;
            const selectedOption = buyerSelect.options[buyerSelect.selectedIndex];
            const reservationId = selectedReservationId || selectedOption?.getAttribute('data-reservation-id') || '';
            const firstName = document.getElementById('calcFirstName').value || selectedOption?.getAttribute('data-first') || 'New';
            const lastName = document.getElementById('calcLastName').value || selectedOption?.getAttribute('data-last') || 'Buyer';
            const contractDate = new Date().toISOString().split('T')[0];
            const blk = document.getElementById('calcBlk')?.value || selectedOption?.getAttribute('data-block') || '';
            const lot = document.getElementById('calcLot')?.value || selectedOption?.getAttribute('data-lot') || '';
            const grossDp = tcp * (percent / 100);
            const dpAmount = Math.max(0, grossDp - currentReservationFee);
            const amortization = dpAmount / months;

            const nextDate = new Date();
            nextDate.setMonth(nextDate.getMonth() + 1);

            try {
                const res = await fetch('/admin/downpayment-fee', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        buyer_master_list_id: buyerId,
                        reservation_id: reservationId || null,
                        first_name: firstName,
                        last_name: lastName,
                        contract_date: contractDate,
                        block: blk,
                        lot: lot,
                        contract_amount: tcp,
                        dp_percentage: percent,
                        dpAmount: dpAmount,
                        nextDate: nextDate.toISOString(),
                        monthly_amortization: amortization,
                        months_to_pay: months,
                        penalty_percentage: penalty
                    })
                });

                if (res.ok) {
                    window.location.href = '/admin/downpayment-fee';
                } else {
                    const errorData = await res.json().catch(() => null);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorData && errorData.message ? errorData.message : 'Failed to save contract to database'
                    });
                    restoreBtn();
                }
            } catch (e) {
                console.error(e);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error connecting to server'
                });
                restoreBtn();
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('downpaymentSalesChart')?.getContext('2d');
            if (ctx) {
                @php
                    $chartData = getMonthlyChartData('downpayment');
                @endphp
                const dpValues = @json($chartData['data']);
                const dpCurrentGrad = ctx.createLinearGradient(0, 0, 0, 210);
                dpCurrentGrad.addColorStop(0, '#4f46e5');
                dpCurrentGrad.addColorStop(1, '#818cf8');

                const dpPriorGrad = ctx.createLinearGradient(0, 0, 0, 210);
                dpPriorGrad.addColorStop(0, '#0284c7');
                dpPriorGrad.addColorStop(1, '#7dd3fc');

                const dpBgColors = dpValues.map((_, idx) => idx === dpValues.length - 1 ? dpCurrentGrad : dpPriorGrad);

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: @json($chartData['labels']),
                        datasets: [{
                            label: 'Downpayment & Amortization Collection (₱)',
                            data: dpValues,
                            backgroundColor: dpBgColors,
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

            const dpColCtx = document.getElementById('downpaymentCollectionChart')?.getContext('2d');
            if (dpColCtx) {
                new Chart(dpColCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Fully Paid', 'In Progress', 'Unstarted'],
                        datasets: [{
                            data: [{{ $dpFullyPaidCount }}, {{ $dpInProgressCount }}, {{ $dpUnstartedCount }}],
                            backgroundColor: ['#10b981', '#4f46e5', '#f59e0b'],
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { usePointStyle: true, pointStyle: 'circle', padding: 14, boxWidth: 8, font: { size: 11, weight: '600' }, color: '#475569' }
                            },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                padding: 10,
                                cornerRadius: 8
                            }
                        }
                    }
                });
            }
        });
        window.addEventListener('DOMContentLoaded', () => {
            bindGisDropdowns('calcBlk', 'calcLot');

            if (window.jQuery && $.fn.select2) {
                $('#dpBuyerMasterList').select2({
                    placeholder: "-- Choose Buyer --",
                    allowClear: true,
                    width: '100%'
                });
            }

            const params = new URLSearchParams(window.location.search);
            const shouldOpenCreate = params.get('create') === '1' || params.get('action') === 'add';
            if (shouldOpenCreate) {
                openContractModal();
                const buyerId = params.get('buyer_id');
                const reservationId = params.get('reservation_id');
                const preselected = buyerId ? preselectDownpaymentBuyer(buyerId, reservationId) : false;
                if (buyerId && !preselected) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Buyer not available',
                        text: 'That buyer has no active reservation ready for downpayment, or a contract already exists.',
                    });
                }
            }

            window.downpaymentTablePagination = initTablePagination({
                rowSelector: '.bill-row',
                infoEl: '#downpaymentTablePageInfo',
                prevEl: '#downpaymentTablePagePrev',
                nextEl: '#downpaymentTablePageNext',
                perPage: 15,
            });
            filterTable();

            document.getElementById('billingSearch')?.addEventListener('input', filterTable);
            document.getElementById('behaviorFilter')?.addEventListener('change', filterTable);
            document.getElementById('blockFilter')?.addEventListener('change', filterTable);
        });

        function filterTable() {
            const search = document.getElementById('billingSearch').value.toLowerCase();
            const behavior = document.getElementById('behaviorFilter').value;
            const block = document.getElementById('blockFilter').value;

            document.querySelectorAll('.bill-row').forEach(row => {
                const text = row.innerText.toLowerCase();
                const matchesSearch = text.includes(search);
                const matchesBehavior = behavior === 'all' || row.dataset.behavior === behavior;
                const matchesBlock = block === 'all' || row.dataset.block === block;
                row.classList.toggle('pagination-match', matchesSearch && matchesBehavior && matchesBlock);
            });

            window.downpaymentTablePagination?.resetPage();
        }

        function openDownpaymentReportModal() {
            const currentBehavior = document.getElementById('behaviorFilter')?.value || 'all';
            const currentBlock = document.getElementById('blockFilter')?.value || 'all';
            if (document.getElementById('reportDpBehaviorFilter')) {
                document.getElementById('reportDpBehaviorFilter').value = currentBehavior;
            }
            if (document.getElementById('reportDpBlockFilter')) {
                document.getElementById('reportDpBlockFilter').value = currentBlock;
            }
            reloadDownpaymentReportPdf();
            document.getElementById('downpaymentReportModal').style.display = 'flex';
        }

        function closeDownpaymentReportModal() {
            document.getElementById('downpaymentReportModal').style.display = 'none';
        }

        function reloadDownpaymentReportPdf() {
            const behavior = document.getElementById('reportDpBehaviorFilter')?.value || 'all';
            const block = document.getElementById('reportDpBlockFilter')?.value || 'all';
            const sort = document.getElementById('reportDpSortFilter')?.value || 'name_asc';
            const base = "{{ route('admin.downpayment-fee.report.pdf') }}";
            const qs = `?behavior=${encodeURIComponent(behavior)}&block=${encodeURIComponent(block)}&sort=${encodeURIComponent(sort)}`;

            const frame = document.getElementById('downpaymentReportPdfFrame');
            if (frame) {
                frame.src = base + qs;
            }
            const dlBtn = document.getElementById('downpaymentReportDownloadBtn');
            if (dlBtn) {
                dlBtn.href = base + qs + '&download=1';
            }
        }

        function printDownpaymentReportPdfFrame() {
            const frame = document.getElementById('downpaymentReportPdfFrame');
            if (frame && frame.contentWindow) {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            }
        }
    </script>

    <!-- Official Downpayment Fee PDF Report Modal -->
    <div id="downpaymentReportModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 2100; background: rgba(15,23,42,0.7); backdrop-filter: blur(8px); padding: 20px;">
        <div class="bill-modal-content" style="max-width: 1100px; width: 95%; max-height: 94vh; overflow: hidden; padding: 20px 24px; border-radius: 20px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); display: flex; flex-direction: column;">
            
            <!-- Controls Bar -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0;">
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Behavior</span>
                        <select id="reportDpBehaviorFilter" class="filter-select" onchange="reloadDownpaymentReportPdf()" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer;">
                            <option value="all">All Behaviors</option>
                            <option value="Early">Early</option>
                            <option value="On-Time">On-Time</option>
                            <option value="Late">Late</option>
                            <option value="Not available yet">N/A</option>
                        </select>
                    </div>
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Block</span>
                        <select id="reportDpBlockFilter" class="filter-select" onchange="reloadDownpaymentReportPdf()" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer;">
                            <option value="all">All Blocks</option>
                            @php
                                $dpUniqueBlocks = collect($bills)->pluck('block')->filter(function($b) { return $b !== 'N/A'; })->unique()->sort();
                            @endphp
                            @foreach($dpUniqueBlocks as $b)
                                <option value="{{ $b }}">Block {{ $b }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Sort By</span>
                        <select id="reportDpSortFilter" class="filter-select" onchange="reloadDownpaymentReportPdf()" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer;">
                            <option value="name_asc" selected>Alphabetical: Name (A to Z)</option>
                            <option value="name_desc">Alphabetical: Name (Z to A)</option>
                            <option value="paid_desc">Paid Amount: Highest First</option>
                            <option value="paid_asc">Paid Amount: Lowest First</option>
                            <option value="balance_desc">Remaining Balance: Highest First</option>
                            <option value="date_asc">Next Due Date: Earliest to Latest</option>
                            <option value="date_desc">Next Due Date: Latest to Earliest</option>
                            <option value="id_asc">Contract ID: Ascending</option>
                            <option value="id_desc">Contract ID: Descending</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <a id="downpaymentReportDownloadBtn" href="{{ route('admin.downpayment-fee.report.pdf') }}?download=1" class="btn btn-primary" style="background: #0284c7; border: none; padding: 8px 16px; font-size: 12px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; color: #fff;">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download PDF
                    </a>
                    <button type="button" class="btn btn-outline" onclick="printDownpaymentReportPdfFrame()" style="background: #fff; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #334155;">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeDownpaymentReportModal()" style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 600; border-radius: 8px; cursor: pointer;">
                        ✕ Close
                    </button>
                </div>
            </div>

            <!-- Embedded Real PDF Viewer (Viewable in-page, no new tab) -->
            <div style="flex: 1; min-height: 0; width: 100%;">
                <iframe id="downpaymentReportPdfFrame" src="{{ route('admin.downpayment-fee.report.pdf') }}" style="width: 100%; height: 75vh; border: 1px solid #e2e8f0; border-radius: 10px; background: #525659;"></iframe>
            </div>

        </div>
    </div>
    </script>
@endsection
