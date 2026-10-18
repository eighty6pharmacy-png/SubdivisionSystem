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
            <div class="bill-actions" style="display: flex; gap: 12px;">
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
            style="background: #fff; border: 1px solid var(--bill-border); border-radius: 20px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
                <h2 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">Downpayment Portfolio Health</h2>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <div
                    style="background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid var(--bill-primary);">
                    <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Active
                        Contracts</div>
                    <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ count($bills) }}
                    </div>
                </div>
                <div
                    style="background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid var(--bill-success);">
                    <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Total
                        Collected</div>
                    <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                        @php 
                                                    $total = 0;
                            foreach ($bills as $b) {
                                $total += $b['paid_amount'];
                            }
                        @endphp
                        ₱{{ number_format($total, 2) }}
                    </div>
                </div>
                <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid #f59e0b;">
                    <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Pending
                        Balance</div>
                    <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">
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
                    style="background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid var(--bill-danger);">
                    <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Late
                    </div>
                    <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                        {{ collect($bills)->where('payment_behavior', 'Late')->count() }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Downpayment & Amortization Sales Trend Bar Graph -->
        <div
            style="background: #fff; border: 1px solid var(--bill-border); border-radius: 20px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div>
                    <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">Monthly Downpayment &
                        Amortization Revenue Trends</h3>
                    <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">12-month buyer installment collections &
                        monthly amortization sales (in ₱ Thousands)</p>
                </div>
            </div>
            <div style="height: 200px; position: relative;">
                <canvas id="downpaymentSalesChart"></canvas>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar" style="display: flex; gap: 16px; margin-bottom: 24px; padding: 16px; background: #fff; border-radius: 12px; border: 1px solid var(--bill-border);">
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
                            <th>Client / Lot</th>
                            <th>Progress</th>
                            <th>Monthly Payments</th>
                            <th>Next Due</th>
                            <th>Payment Behavior</th>
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
                    No buyers are ready for downpayment yet. Each buyer needs an active reservation on the Reservation Fee page first.
                </div>
            @endif

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Select
                    buyer (reservation required)</label>
                <select id="dpBuyerMasterList" class="filter-select" style="width: 100%; padding: 12px;"
                    onchange="autoFillDownpaymentBuyer(this)">
                    <option value="">-- Choose Buyer --</option>
                    @foreach($masterListBuyers as $b)
                        @php
                            $readyReservation = $b->reservations->first();
                        @endphp
                        <option value="{{ $b->id }}" data-first="{{ $b->first_name }}" data-last="{{ $b->last_name }}"
                            data-block="{{ $b->block_no }}" data-lot="{{ $b->lot_no }}" data-tcp="{{ $b->financingRecord?->contract_price ?? '' }}"
                            data-equity="{{ $b->financingRecord?->paid_by_vendee_equity ?? '' }}"
                            data-reservation-id="{{ $readyReservation?->id ?? '' }}"
                            data-reservation-amount="{{ $readyReservation?->amount ?? '' }}">
                            {{ $b->last_name }}, {{ $b->first_name }}
                            {{ $b->middle_name ? substr($b->middle_name, 0, 1) . '.' : '' }} — (Block {{ $b->block_no ?? '?' }}, Lot {{ $b->lot_no ?? '?' }})
                        </option>
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
                            style="width: 100%; padding: 12px; font-weight: 700;" placeholder="2,000,000"
                            oninput="formatNumberInput(this)">
                    </div>
                    <div>
                        <label
                            style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">DP
                            Percentage %</label>
                        <input type="number" id="calcDpPercent" class="filter-select"
                            style="width: 100%; padding: 12px; font-weight: 700;" value="30">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
                    <div>
                        <label
                            style="display: block; font-size: 12px; font-weight: 700; color: var(--bill-primary); margin-bottom: 8px;">Total
                            DP Amount ₱</label>
                        <input type="text" id="calcDpAmount" class="filter-select"
                            style="width: 100%; padding: 12px; background: #e2e8f0; font-weight: 800;" readonly>
                    </div>
                    <div>
                        <label
                            style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Months
                            to Pay</label>
                        <input type="number" id="calcMonths" class="filter-select" style="width: 100%; padding: 12px;"
                            value="24">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr; margin-top: 16px;">
                    <div>
                        <label
                            style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Penalty %</label>
                        <input type="number" id="calcPenalty" class="filter-select" style="width: 100%; padding: 12px;"
                            value="0">
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
            let value = input.value.replace(/,/g, '');
            if (!isNaN(value) && value !== '') {
                input.value = Number(value).toLocaleString('en-US');
            }
        }

        function preselectDownpaymentBuyer(buyerId) {
            const select = document.getElementById('dpBuyerMasterList');
            if (!select || !buyerId) return false;
            const hasOption = Array.from(select.options).some(o => o.value === String(buyerId));
            if (!hasOption) return false;
            select.value = String(buyerId);
            if (window.jQuery && $.fn.select2) {
                $('#dpBuyerMasterList').val(String(buyerId)).trigger('change');
            }
            autoFillDownpaymentBuyer(select);
            return true;
        }

        function openContractModal() {
            document.getElementById('newContractModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            calculateAmortization();
        }

        function closeContractModal() {
            window.history.replaceState(null, '', window.location.pathname);
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

            const penaltyRow = penalty > 0
                ? `<tr><td>Late payment penalty (${penaltyRate}%)</td><td class="text-right">${money(penalty)}</td></tr>`
                : `<tr><td>Late payment penalty (${penaltyRate}%)</td><td class="text-right">${money(0)}</td></tr>`;

            const html = `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Downpayment Receipt</title>
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
  .header{text-align:center;border-bottom:2px solid #000;padding-bottom:12px;margin-bottom:18px;position:relative;}
  .header h1{color:#000;margin:0;font-size:22px;text-transform:uppercase;letter-spacing:0.5px;}
  .header p{margin:4px 0 0;color:#000;font-size:12px;}
  .watermark{position:absolute;top:8px;right:12px;border:3px solid #10b981;color:#10b981;padding:6px 14px;font-size:22px;font-weight:bold;border-radius:6px;transform:rotate(12deg);opacity:0.85;letter-spacing:2px;text-transform:uppercase;}
  .row{width:100%;margin-bottom:16px;}
  .col-half{width:48%;display:inline-block;vertical-align:top;}
  .box{border:1px solid #000;padding:12px;border-radius:4px;background:#fff;box-sizing:border-box;}
  .box h3{margin-top:0;border-bottom:1px solid #000;padding-bottom:5px;font-size:14px;color:#000;text-transform:uppercase;}
  table{width:100%;border-collapse:collapse;margin-bottom:16px;}
  th,td{padding:7px 9px;border:1px solid #000;color:#000;}
  th{text-align:left;color:#000;background:#f0f0f0;font-weight:bold;}
  .text-right{text-align:right;}
  .total-row td{border-top:2px solid #000;border-bottom:2px solid #000;font-size:14px;font-weight:bold;color:#000;background:#f5f5f5;}
  .note{font-size:12px;color:#000;margin:0 0 14px;}
  .footer{margin-top:24px;text-align:center;font-size:12px;color:#000;border-top:1px solid #000;padding-top:12px;}
</style>
</head>
<body>
<div class="header">
  <h1>Mayon Builders Realty & Development Corporation</h1>
  <p>Z1, Tagbong, Pili, Camarines Sur</p>
  <p><strong>OFFICIAL DOWNPAYMENT RECEIPT</strong></p>
  <div class="watermark">PAID</div>
</div>
<div class="row">
  <div class="col-half box" style="margin-right:2%;">
    <h3>Buyer Details</h3>
    <table style="border:none;margin-bottom:0;">
      <tr><td style="padding:4px;border:none;width:42%;"><strong>Name:</strong></td><td style="padding:4px;border:none;">${bill.buyer}</td></tr>
      <tr><td style="padding:4px;border:none;"><strong>Contact:</strong></td><td style="padding:4px;border:none;">${bill.contact || 'N/A'}</td></tr>
      <tr><td style="padding:4px;border:none;"><strong>Address:</strong></td><td style="padding:4px;border:none;">Block ${bill.block} Lot ${bill.lot}</td></tr>
      <tr><td style="padding:4px;border:none;"><strong>Contract date:</strong></td><td style="padding:4px;border:none;">${formatIsoDate(bill.contract_date, true)}</td></tr>
    </table>
  </div>
  <div class="col-half box">
    <h3>Payment Details</h3>
    <table style="border:none;margin-bottom:0;">
      <tr><td style="padding:4px;border:none;width:48%;"><strong>Installment:</strong></td><td style="padding:4px;border:none;">${installmentNo} of ${bill.total_months}</td></tr>
      <tr><td style="padding:4px;border:none;"><strong>Due date:</strong></td><td style="padding:4px;border:none;font-weight:bold;">${dueDate}</td></tr>
      <tr><td style="padding:4px;border:none;"><strong>Date paid:</strong></td><td style="padding:4px;border:none;">${paidDate}</td></tr>
      <tr><td style="padding:4px;border:none;"><strong>Method:</strong></td><td style="padding:4px;border:none;">${mop}</td></tr>
      <tr><td style="padding:4px;border:none;"><strong>Reference (TRN):</strong></td><td style="padding:4px;border:none;font-family:monospace;font-size:11px;">${trn}</td></tr>
      <tr><td style="padding:4px;border:none;"><strong>Timing:</strong></td><td style="padding:4px;border:none;">${behavior}</td></tr>
    </table>
  </div>
</div>
<h3 style="color:#000;border-bottom:1px solid #000;padding-bottom:5px;margin:16px 0 10px 0;font-size:14px;text-transform:uppercase;">Contract Snapshot</h3>
<table>
  <tbody>
    <tr><td>Total contract price (TCP)</td><td class="text-right">${money(bill.tcp)}</td></tr>
    <tr><td>Total downpayment</td><td class="text-right">${money(totalDp)}</td></tr>
    <tr><td>Monthly installment</td><td class="text-right">${money(monthly)}</td></tr>
    <tr><td>Penalty rate</td><td class="text-right">${penaltyRate}%</td></tr>
    <tr><td>Term</td><td class="text-right">${bill.total_months} months</td></tr>
  </tbody>
</table>
<h3 style="color:#000;border-bottom:1px solid #000;padding-bottom:5px;margin:16px 0 10px 0;font-size:14px;text-transform:uppercase;">This Payment Breakdown</h3>
<p class="note">Penalty is charged only when the installment is paid after its due date. It is collected in addition to the installment and is not applied to the remaining downpayment balance.</p>
<table>
  <thead><tr><th>Description</th><th class="text-right">Amount (PHP)</th></tr></thead>
  <tbody>
    <tr><td>Installment principal (month ${installmentNo})</td><td class="text-right">${money(principal)}</td></tr>
    ${penaltyRow}
    <tr class="total-row"><td>Total amount received</td><td class="text-right">${money(amount)}</td></tr>
    <tr><td>Applied to downpayment</td><td class="text-right">${money(principal)}</td></tr>
    <tr><td>Remaining downpayment after this payment</td><td class="text-right">${money(remainingAfter)}</td></tr>
    <tr><td>Months paid after this payment</td><td class="text-right">${monthsAfter}</td></tr>
  </tbody>
</table>
<div class="footer">
  <p style="font-weight:bold;margin:0;">This installment has been settled. Thank you for your payment.</p>
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

        function calculateAmortization() {
            const tcpStr = inpTcp.value.replace(/,/g, '');
            const tcp = parseFloat(tcpStr) || 0;
            const percent = parseFloat(inpPercent.value) || 0;
            const months = parseInt(inpMonths.value) || 1;

            const dpAmount = tcp * (percent / 100);
            const amortization = dpAmount / months;

            outDp.value = dpAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            outAmort.textContent = `₱${amortization.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }

        inpTcp.addEventListener('input', calculateAmortization);
        inpPercent.addEventListener('input', calculateAmortization);
        inpMonths.addEventListener('input', calculateAmortization);

        function autoFillDownpaymentBuyer(select) {
            const option = select.options[select.selectedIndex];
            if (option.value) {
                document.getElementById('calcFirstName').value = option.getAttribute('data-first');
                document.getElementById('calcLastName').value = option.getAttribute('data-last');

                const blk = option.getAttribute('data-block');
                const lot = option.getAttribute('data-lot');
                if (blk) {
                    const bOpt = Array.from(document.getElementById('calcBlk').options).find(o => o.text.includes(blk) || o.value == blk);
                    if (bOpt) bOpt.selected = true;
                }
                if (lot) {
                    const lOpt = Array.from(document.getElementById('calcLot').options).find(o => o.text.includes(lot) || o.value == lot);
                    if (lOpt) lOpt.selected = true;
                }

                const tcp = option.getAttribute('data-tcp');
                const equity = option.getAttribute('data-equity'); // DP
                if (tcp) {
                    document.getElementById('calcTcp').value = Number(tcp).toLocaleString('en-US');
                }
                if (equity && tcp) {
                    const perc = (Number(equity) / Number(tcp)) * 100;
                    document.getElementById('calcDpPercent').value = perc.toFixed(2);
                }

                calculateAmortization();
            }
        }

        async function submitContract() {
            const btn = document.querySelector('#newContractModal .btn-primary');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Saving...';
                btn.style.opacity = '0.7';
            }

            const buyerSelect = document.getElementById('dpBuyerMasterList');
            const buyerId = buyerSelect.value;
            const selectedOption = buyerSelect.options[buyerSelect.selectedIndex];
            const reservationId = selectedOption?.getAttribute('data-reservation-id') || '';
            const firstName = document.getElementById('calcFirstName').value || 'New';
            const lastName = document.getElementById('calcLastName').value || 'Buyer';
            const contractDate = new Date().toISOString().split('T')[0];
            const blk = document.getElementById('calcBlk').value;
            const lot = document.getElementById('calcLot').value;

            const tcpStr = inpTcp.value.replace(/,/g, '');
            const tcp = parseFloat(tcpStr) || 0;
            const percent = parseFloat(inpPercent.value) || 0;
            const months = parseInt(inpMonths.value) || 1;
            const penalty = parseFloat(document.getElementById('calcPenalty').value) || 0;
            const dpAmount = tcp * (percent / 100);
            const amortization = dpAmount / months;

            const nextDate = new Date();
            nextDate.setMonth(nextDate.getMonth() + 1);

            if (!buyerId) {
                Swal.fire('Please select a buyer from the Master List.');
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Save & Initialize Contract';
                    btn.style.opacity = '1';
                }
                return;
            }

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
                    Swal.fire(errorData && errorData.message ? errorData.message : 'Failed to save contract to database');
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = 'Save & Initialize Contract';
                        btn.style.opacity = '1';
                    }
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error connecting to server');
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Save & Initialize Contract';
                    btn.style.opacity = '1';
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('downpaymentSalesChart')?.getContext('2d');
            if (ctx) {
                @php
                    $chartData = getMonthlyChartData('downpayment');
                @endphp
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: @json($chartData['labels']),
                        datasets: [{
                            label: 'Downpayment & Amortization Collection (₱)',
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
                const preselected = buyerId ? preselectDownpaymentBuyer(buyerId) : false;
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
    </script>
@endsection
