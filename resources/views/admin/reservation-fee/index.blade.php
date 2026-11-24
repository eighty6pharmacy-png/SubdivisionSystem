@extends('layouts.admin')

@section('title', 'Reservation Fee')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">

    <div class="bill-container fade-in">
        <div class="bill-header">
            <div class="bill-title">
                <h1>Reservation Fee Tracking</h1>
                <p>Manage and track initial lot reservation deposits and client payments.</p>
            </div>
            <div class="bill-actions" style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-outline" style="background: #fff; border-color: #0284c7; color: #0284c7; font-weight: 700; display: inline-flex; align-items: center;" onclick="openReservationReportModal()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Generate Report
                </button>
                <!-- Record New Reservation Button -->
                <button class="btn btn-primary" onclick="openReservationModal()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                        style="margin-right: 8px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Record New Reservation
                </button>
            </div>
        </div>

        <!-- Collection Summary Dashboard -->
        <div
            style="background: #fff; border: 1px solid var(--bill-border); border-radius: 16px; padding: 16px 20px; margin-bottom: 0; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 12px;">
                <h2 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;">Reservation Pipeline Summary</h2>
                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border-radius: 999px; background: #ecfeff; border: 1px solid #a5f3fc; color: #0e7490; font-size: 12px; font-weight: 700;">
                    <span style="width: 7px; height: 7px; border-radius: 50%; background: #06b6d4; display: inline-block;"></span>
                    Auto SMS: Active (5d pre-expiry &amp; 1d overdue)
                </span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px;">
                <div
                    style="background: #f8fafc; padding: 12px 14px; border-radius: 10px; border-left: 4px solid var(--bill-primary);">
                    <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;">Total
                        Reservations</div>
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
                                if ($b['status'] != 'Cancelled')
                                    $total += $b['amount'];
                            }
                        @endphp
                        ₱{{ number_format($total, 2) }}
                    </div>
                </div>
                <div style="background: #f8fafc; padding: 12px 14px; border-radius: 10px; border-left: 4px solid #f59e0b;">
                    <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;">Active
                    </div>
                    <div style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 2px;">
                        {{ collect($bills)->whereIn('status', ['Reserved', 'Pending'])->count() }}
                    </div>
                </div>
                <div style="background: #f8fafc; padding: 12px 14px; border-radius: 10px; border-left: 4px solid #ef4444;">
                    <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;">Cancelled</div>
                    <div style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 2px;">
                        {{ collect($bills)->where('status', 'Cancelled')->count() }}
                    </div>
                </div>
            </div>
        </div>

        @php
            $resActiveCount = collect($bills)->whereIn('status', ['Reserved', 'Pending'])->count();
            $resCancelledCount = collect($bills)->where('status', 'Cancelled')->count();
        @endphp

        <!-- Summary Charts Row -->
        <div class="responsive-grid grid-2-1" style="margin-bottom: 0;">
            <div style="background: #fff; border: 1px solid var(--bill-border); border-radius: 16px; padding: 16px 20px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);">
                <div style="margin-bottom: 12px;">
                    <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;">Monthly Revenue</h3>
                </div>
                <div style="height: 190px; position: relative;">
                    <canvas id="reservationSalesChart"></canvas>
                </div>
            </div>
            <div style="background: #fff; border: 1px solid var(--bill-border); border-radius: 16px; padding: 16px 20px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);">
                <div style="margin-bottom: 12px;">
                    <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;">Status Summary</h3>
                </div>
                <div style="height: 190px; position: relative; display: flex; justify-content: center;">
                    <canvas id="reservationStatusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar" style="display: flex; gap: 12px; margin-bottom: 0; padding: 14px 18px; background: #fff; border-radius: 14px; border: 1px solid var(--bill-border); align-items: flex-end;">
            <div class="filter-group" style="flex: 1;">
                <span class="filter-label" style="display: block; font-size: 12px; color: #64748b; margin-bottom: 4px;">Search</span>
                <input type="text" id="billingSearch" class="filter-select" placeholder="Buyer or Lot..." style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
            <div class="filter-group">
                <span class="filter-label" style="display: block; font-size: 12px; color: #64748b; margin-bottom: 4px;">Status</span>
                <select id="statusFilter" class="filter-select" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    <option value="all">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="cancelled">Cancelled</option>
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

        <!-- Reservation Ledger -->
        <div class="analytic-card">
            <div class="card-title">
                <span>Reservation</span>
            </div>
            <div class="bill-table-container">
                <table class="bill-table">
                    <thead>
                        <tr>
                            <th data-sort="text">Buyer Name</th>
                            <th data-sort="text">Block & Lot</th>
                            <th data-sort="text">Contact Info</th>
                            <th data-sort="number">Amount Paid</th>
                            <th data-sort="date">Date Reserved</th>
                            <th data-sort="date">Expiration</th>
                            <th data-sort="text">Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="reservationTableBody">
                        @foreach($bills as $bill)
                            @php
                                $statusKey = match ($bill['status']) {
                                    'Reserved', 'Pending' => 'active',
                                    'Cancelled' => 'cancelled',
                                    default => strtolower((string) $bill['status']),
                                };
                                $statusLabel = match ($statusKey) {
                                    'active' => 'Active',
                                    'cancelled' => 'Cancelled',
                                    default => $bill['status'],
                                };
                            @endphp
                            <tr class="bill-row" data-status="{{ $statusKey }}" data-block="{{ $bill['block'] }}">
                                <td>
                                    <div style="font-weight: 700;">{{ $bill['buyer'] }}</div>
                                    <div style="font-size: 11px; color: #64748b;">ID: {{ $bill['id'] }}</div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--bill-primary);">Block {{ $bill['block'] }}</div>
                                    <div style="font-size: 12px; color: #64748b;">Lot {{ $bill['lot'] }}</div>
                                </td>
                                <td>
                                    <div style="font-size: 13px;">{{ $bill['contact'] }}</div>
                                </td>
                                <td>
                                    <div style="font-weight: 700;">₱{{ number_format($bill['amount'], 2) }}</div>
                                </td>
                                <td>
                                    <div style="font-size: 13px;">{{ date('M d, Y', strtotime($bill['date'])) }}</div>
                                </td>
                                <td>
                                    @php
                                        $expirationOverdue = false;
                                        if (!empty($bill['deadline'])) {
                                            $expirationDate = \Carbon\Carbon::parse($bill['deadline'])->startOfDay();
                                            if ($expirationDate->lt(now()->startOfDay()) && empty($bill['has_downpayment'])) {
                                                $expirationOverdue = true;
                                            }
                                        }
                                    @endphp
                                    <div style="font-size: 13px; font-weight: 700; color: {{ $expirationOverdue ? '#dc2626' : '#0f172a' }};">
                                        {{ !empty($bill['deadline']) ? date('M d, Y', strtotime($bill['deadline'])) : '—' }}
                                    </div>
                                </td>
                                <td class="status-cell">
                                    @if($statusKey === 'active')
                                        <span class="badge badge-warning" style="background: #fef3c7; color: #d97706;">{{ $statusLabel }}</span>
                                    @else
                                        <span class="badge badge-danger">{{ $statusLabel }}</span>
                                    @endif
                                </td>
                                <td class="action-cell">
                                    <div style="display: flex; gap: 6px;">
                                        <button class="btn btn-outline" style="padding: 6px 10px; font-size: 11px; border-color: #0284c7; color: #0284c7; font-weight: 700;"
                                            onclick="openReservationReceiptModal('{{ $bill['id'] }}')">Receipt</button>
                                        <button class="btn btn-outline" style="padding: 6px 10px; font-size: 11px;"
                                            onclick="viewDetail('{{ $bill['id'] }}')">Details</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @include('partials.admin-client-pagination', ['prefix' => 'reservationTable'])
        </div>
    </div>

    <!-- Full Screen Immersive Modal -->
    <div id="billModal" class="bill-modal">
        <div class="bill-modal-content">
            <div class="modal-header">
                <div>
                    <span id="modalLot"
                        style="font-size: 12px; font-weight: 700; color: var(--bill-primary); text-transform: uppercase; letter-spacing: 0.1em;"></span>
                    <h2 id="modalResident" style="font-size: 32px; font-weight: 800; color: #0f172a; margin-top: 8px;"></h2>
                    <div style="margin-top: 12px; display: flex; gap: 12px; align-items: center;">
                        <span id="modalTrend" class="trend-chip trend-early"></span>
                    </div>
                </div>
                <button class="ann-btn-icon" onclick="closeModal()" style="padding: 12px; background: #f1f5f9;">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div id="modalMainContent">
                    <div id="modalViewSection">
                    <h3
                        style="font-size: 14px; font-weight: 700; text-transform: uppercase; margin-bottom: 20px; color: #64748b;">
                        Reservation Details</h3>
                    <div class="modal-summary-grid"
                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
                        <div style="background: var(--bill-bg-soft); padding: 20px; border-radius: 16px;">
                            <span style="font-size: 12px; color: #64748b;">Amount Paid</span>
                            <div id="modalAmount"
                                style="font-size: 24px; font-weight: 700; color: #0f172a; margin-top: 4px;"></div>
                        </div>
                        <div style="background: var(--bill-bg-soft); padding: 20px; border-radius: 16px;">
                            <span style="font-size: 12px; color: #64748b;">Date Recorded</span>
                            <div id="modalDate"
                                style="font-size: 24px; font-weight: 700; color: var(--bill-primary); margin-top: 4px;">
                            </div>
                        </div>
                        <div id="modalDeadlineCard" style="grid-column: 1 / -1; background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0;">
                            <span style="font-size: 12px; color: #64748b;">Expiration</span>
                            <div id="modalDeadline" style="font-size: 24px; font-weight: 700; color: #0f172a; margin-top: 4px;"></div>
                        </div>
                    </div>

                    <div id="modalStatusAlert" role="status"
                        style="display: none; margin-top: 32px; padding: 14px 16px; border-radius: 12px; font-size: 14px; font-weight: 600; line-height: 1.5; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569;">
                    </div>

                    <div style="margin-top: 24px; display: flex; gap: 12px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-outline" style="border-color: #0284c7; color: #0284c7; font-weight: 700; background: #f0f9ff;"
                            onclick="openReservationReceiptModal(currentModalId)">View Reservation Receipt</button>
                    </div>

                    <div id="modalReservationActions"
                        style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 20px; border-top: 1px solid var(--bill-border); padding-top: 24px;">
                        <button type="button" class="btn btn-primary" onclick="convertReservation()">Convert to Downpayment</button>
                        <button type="button" class="btn btn-outline" style="border-color: #3b82f6; color: #2563eb; font-weight: 700;"
                            onclick="openEditReservation()">Edit Reservation</button>
                        <button type="button" class="btn btn-outline" style="color: var(--bill-danger); border-color: #fee2e2;"
                            onclick="cancelReservation()">Cancel Reservation</button>
                    </div>
                    </div>

                    <div id="modalEditSection" style="display: none;">
                        <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; margin-bottom: 20px; color: #64748b;">
                            Edit Reservation</h3>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Buyer *</label>
                            <select id="editResBuyer" class="filter-select" style="width: 100%; padding: 12px; border-radius: 10px;"></select>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Block *</label>
                                <select id="editResBlock" class="filter-select" style="width: 100%; padding: 12px; border-radius: 10px;">
                                    <option value="">-- Select Block --</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Lot *</label>
                                <select id="editResLot" class="filter-select" style="width: 100%; padding: 12px; border-radius: 10px;">
                                    <option value="">-- Select Lot --</option>
                                </select>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Amount Paid (₱) *</label>
                                <input type="text" id="editResAmount" class="filter-select"
                                    style="width: 100%; font-size: 16px; padding: 12px; border-radius: 10px; box-sizing: border-box;"
                                    oninput="formatNumberInput(this)">
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Number of Months *</label>
                                <input type="number" id="editResMonths" class="filter-select" min="1" max="120" step="1"
                                    style="width: 100%; font-size: 16px; padding: 12px; border-radius: 10px; box-sizing: border-box;"
                                    oninput="updateEditReservationDeadlinePreview()">
                                <div id="editResDeadlinePreview" style="font-size: 11px; color: #64748b; margin-top: 6px; font-weight: 600;"></div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 12px; border-top: 1px solid var(--bill-border); padding-top: 24px;">
                            <button type="button" class="btn btn-outline" style="flex: 1; justify-content: center; padding: 12px;"
                                onclick="cancelEditReservation()">Cancel</button>
                            <button type="button" id="btnSaveEditReservation" class="btn btn-primary" style="flex: 1; justify-content: center; padding: 12px;"
                                onclick="submitEditReservation()">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Record New Reservation Modal -->
    <div id="newReservationModal" class="bill-modal"
        style="display: none; align-items: center; justify-content: center; z-index: 3000;">
        <div class="bill-modal-content" style="max-width: 540px; padding: 32px; border-radius: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h2 style="font-size: 24px; font-weight: 800; color: #0f172a; margin: 0;">Manual Reservation Logging</h2>
                <button onclick="closeReservationModal()"
                    style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
            </div>

            <!-- Buyer Selection with Real-Time Search -->
            <input type="hidden" id="resBuyerMasterList" value="">
            <input type="hidden" id="resName" value="">
            <input type="hidden" id="resContact" value="">

            <div id="selectedBuyerCard" style="display: none; background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 14px; padding: 14px 18px; margin-bottom: 16px; justify-content: space-between; align-items: center;">
                <div>
                    <div id="selectedBuyerName" style="font-size: 15px; font-weight: 800; color: #166534;"></div>
                    <div id="selectedBuyerMeta" style="font-size: 12px; color: #15803d; margin-top: 3px;"></div>
                    <div id="selectedBuyerLots" style="margin-top: 6px;"></div>
                </div>
                <button type="button" onclick="clearSelectedBuyer()" 
                    style="background: #ffffff; border: 1px solid #86efac; color: #15803d; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s ease;">
                    Change Buyer
                </button>
            </div>

            <div id="buyerSearchContainer" style="margin-bottom: 16px; position: relative;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">
                    Select Buyer from Master List *
                </label>
                <div style="position: relative;">
                    <input type="text" id="buyerSearchInput" class="filter-select"
                        style="width: 100%; padding: 12px 14px 12px 38px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; box-sizing: border-box;"
                        placeholder="Search buyer by name, email, or contact number..."
                        oninput="filterBuyers(this.value)"
                        onfocus="filterBuyers(this.value)"
                        autocomplete="off">
                    <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; display: flex; align-items: center;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                </div>
                <div id="buyerSearchResults" style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: #fff; border: 1.5px solid #cbd5e1; border-radius: 12px; max-height: 220px; overflow-y: auto; z-index: 3100; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);">
                </div>
            </div>

            <!-- Block and Lot Selection -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">
                        Select Block *
                    </label>
                    <select id="resBlock" class="filter-select" style="width: 100%; padding: 12px; border-radius: 10px;">
                        <option value="">-- Select Block --</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">
                        Select Lot *
                    </label>
                    <select id="resLot" class="filter-select" style="width: 100%; padding: 12px; border-radius: 10px;">
                        <option value="">-- Select Lot --</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                <div>
                    <label
                        style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Amount
                        Paid (₱)</label>
                    <input type="text" id="resAmount" class="filter-select"
                        style="width: 100%; font-size: 16px; padding: 12px; border-radius: 10px; box-sizing: border-box;" value="20,000"
                        oninput="formatNumberInput(this)">
                </div>
                <div>
                    <label
                        style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Number
                        of Months *</label>
                    <input type="number" id="resMonths" class="filter-select" min="1" max="120" step="1"
                        style="width: 100%; font-size: 16px; padding: 12px; border-radius: 10px; box-sizing: border-box;"
                        value="1" oninput="updateReservationDeadlinePreview()">
                    <div id="resDeadlinePreview" style="font-size: 11px; color: #64748b; margin-top: 6px; font-weight: 600;"></div>
                </div>
            </div>

            <button class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px; border-radius: 10px; font-size: 15px; font-weight: 700;"
                onclick="submitReservation()">Save Reservation Record</button>
        </div>
    </div>

    <script src="{{ asset('js/gis-dropdowns.js') }}"></script>
    <script>
        const allBillsRaw = @json($bills);
        const masterBuyersList = @json($masterListBuyers);

        let currentModalId = null;

        function formatNumberInput(input) {
            let value = input.value.replace(/,/g, '');
            if (!isNaN(value) && value !== '') {
                input.value = Number(value).toLocaleString('en-US');
            }
        }

        function filterBuyers(query) {
            const resultsBox = document.getElementById('buyerSearchResults');
            if (!resultsBox) return;

            const q = (query || '').toLowerCase().trim();
            const filtered = masterBuyersList.filter(b => {
                const fullName = `${b.first_name || ''} ${b.middle_name || ''} ${b.last_name || ''}`.toLowerCase();
                const reverseName = `${b.last_name || ''} ${b.first_name || ''}`.toLowerCase();
                const email = (b.email || '').toLowerCase();
                const contact = (b.contact_number || '').toLowerCase();
                return !q || fullName.includes(q) || reverseName.includes(q) || email.includes(q) || contact.includes(q);
            });

            if (filtered.length === 0) {
                resultsBox.innerHTML = '<div style="padding: 16px; text-align: center; color: #94a3b8; font-size: 13px;">No matching buyers found.</div>';
                resultsBox.style.display = 'block';
                return;
            }

            resultsBox.innerHTML = filtered.map(b => {
                const fullName = `${b.last_name}, ${b.first_name}${b.middle_name ? ' ' + b.middle_name.charAt(0) + '.' : ''}`;
                const contact = b.contact_number || 'No contact';
                const email = b.email || '';
                
                // Active lots owned
                const lots = (b.reservations || [])
                    .filter(r => r.status !== 'Cancelled' && r.lot)
                    .map(r => `Blk ${r.lot.block} Lot ${r.lot.lot_number}`);
                const lotsBadge = lots.length > 0 
                    ? `<span style="font-size: 11px; padding: 2px 6px; background: #e0f2fe; color: #0369a1; border-radius: 4px; font-weight: 700;">${lots.length} active (${lots.join(', ')})</span>`
                    : `<span style="font-size: 11px; padding: 2px 6px; background: #f1f5f9; color: #64748b; border-radius: 4px;">0 lots</span>`;

                return `
                    <div onclick="selectBuyer('${b.id}')" 
                        style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.15s ease;"
                        onmouseover="this.style.background='#f8fafc'" 
                        onmouseout="this.style.background='#ffffff'">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 700; font-size: 14px; color: #0f172a;">${fullName}</span>
                            ${lotsBadge}
                        </div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                            ${email ? email + ' • ' : ''}${contact}
                        </div>
                    </div>
                `;
            }).join('');

            resultsBox.style.display = 'block';
        }

        function selectBuyer(id) {
            const buyer = masterBuyersList.find(b => String(b.id) === String(id));
            if (!buyer) return;

            document.getElementById('resBuyerMasterList').value = buyer.id;
            document.getElementById('resName').value = `${buyer.first_name || ''} ${buyer.last_name || ''}`.trim();
            document.getElementById('resContact').value = buyer.contact_number || '';

            const fullName = `${buyer.last_name}, ${buyer.first_name}${buyer.middle_name ? ' ' + buyer.middle_name : ''}`;
            document.getElementById('selectedBuyerName').textContent = fullName;
            document.getElementById('selectedBuyerMeta').textContent = `${buyer.email ? buyer.email + ' • ' : ''}${buyer.contact_number || 'No contact'}`;

            const lots = (buyer.reservations || [])
                .filter(r => r.status !== 'Cancelled' && r.lot)
                .map(r => `Blk ${r.lot.block} Lot ${r.lot.lot_number}`);
            
            const lotsContainer = document.getElementById('selectedBuyerLots');
            if (lots.length > 0) {
                lotsContainer.innerHTML = `<span style="font-size: 12px; font-weight: 600; color: #15803d;">Existing lots: </span>` +
                    lots.map(l => `<span style="display: inline-block; padding: 2px 8px; font-size: 11px; font-weight: 700; background: #dcfce7; color: #166534; border-radius: 6px; margin-right: 4px;">${l}</span>`).join('');
            } else {
                lotsContainer.innerHTML = `<span style="font-size: 11px; color: #64748b; font-style: italic;">No previous lots reserved</span>`;
            }

            document.getElementById('selectedBuyerCard').style.display = 'flex';
            document.getElementById('buyerSearchContainer').style.display = 'none';
            document.getElementById('buyerSearchResults').style.display = 'none';
        }

        function clearSelectedBuyer() {
            document.getElementById('resBuyerMasterList').value = '';
            document.getElementById('resName').value = '';
            document.getElementById('resContact').value = '';
            document.getElementById('buyerSearchInput').value = '';
            document.getElementById('selectedBuyerCard').style.display = 'none';
            document.getElementById('buyerSearchContainer').style.display = 'block';
            document.getElementById('buyerSearchInput').focus();
        }

        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            const container = document.getElementById('buyerSearchContainer');
            const results = document.getElementById('buyerSearchResults');
            if (container && results && !container.contains(e.target)) {
                results.style.display = 'none';
            }
        });

        function updateReservationDeadlinePreview() {
            const monthsInput = document.getElementById('resMonths');
            const previewEl = document.getElementById('resDeadlinePreview');
            if (!monthsInput || !previewEl) return;

            const months = parseInt(monthsInput.value, 10);
            if (isNaN(months) || months < 1) {
                previewEl.textContent = 'Enter at least 1 month.';
                previewEl.style.color = '#dc2626';
                return;
            }

            const target = new Date();
            const day = target.getDate();
            target.setMonth(target.getMonth() + months);
            if (target.getDate() !== day) {
                target.setDate(0);
            }

            const formatted = target.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            previewEl.textContent = `Expiration: ${formatted}`;
            previewEl.style.color = '#64748b';
        }

        function openReservationModal() {
            if (new URLSearchParams(window.location.search).get('action') !== 'add') {
                window.history.pushState(null, '', '?action=add');
            }
            clearSelectedBuyer();
            const monthsInput = document.getElementById('resMonths');
            if (monthsInput) {
                monthsInput.value = '1';
                updateReservationDeadlinePreview();
            }
            if (typeof bindGisDropdowns === 'function') {
                bindGisDropdowns('resBlock', 'resLot', { includePlaceholder: true });
            }
            document.getElementById('newReservationModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeReservationModal() {
            window.history.replaceState(null, '', window.location.pathname);
            document.getElementById('newReservationModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function closeModal() {
            document.getElementById('billModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        async function submitReservation() {
            const btn = document.querySelector('#newReservationModal .btn-primary');
            const resetBtn = () => {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Save Reservation Record';
                    btn.style.opacity = '1';
                }
            };

            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Saving...';
                btn.style.opacity = '0.7';
            }

            const buyerId = document.getElementById('resBuyerMasterList').value;
            const name = document.getElementById('resName').value.trim();
            const contact = document.getElementById('resContact').value.trim();
            const block = document.getElementById('resBlock').value;
            const lot = document.getElementById('resLot').value;
            const months = parseInt(document.getElementById('resMonths').value, 10);
            const amountStr = document.getElementById('resAmount').value.replace(/,/g, '');
            const amount = parseFloat(amountStr);

            if (!buyerId) {
                Swal.fire({ icon: 'warning', title: 'Buyer Required', text: 'Please search and select a buyer from the Master List.' });
                resetBtn();
                return;
            }

            if (!block || !lot) {
                Swal.fire({ icon: 'warning', title: 'Block and Lot Required', text: 'Please select both Block and Lot to reserve.' });
                resetBtn();
                return;
            }

            if (isNaN(amount) || amount <= 0) {
                Swal.fire({ icon: 'warning', title: 'Invalid Amount', text: 'Please enter a valid reservation fee amount.' });
                resetBtn();
                return;
            }

            if (isNaN(months) || months < 1 || months > 120) {
                Swal.fire({ icon: 'warning', title: 'Invalid Duration', text: 'Please enter a valid number of months (1 to 120).' });
                resetBtn();
                return;
            }

            try {
                const res = await fetch('/admin/reservation-fee', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        buyer_master_list_id: buyerId,
                        block: block,
                        lot: lot,
                        name: name,
                        contact: contact,
                        amount: amount,
                        months: months
                    })
                });

                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    window.location.reload();
                } else {
                    Swal.fire(data.message || 'Failed to save reservation to database');
                    resetBtn();
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error connecting to server');
                resetBtn();
            }
        }

        function reservationStatusLabel(status) {
            if (status === 'Reserved' || status === 'Pending') return 'Active';
            if (status === 'Cancelled') return 'Cancelled';
            return status || '—';
        }

        function viewDetail(id) {
            const bill = allBillsRaw.find(b => String(b.id) === String(id));
            if (!bill) return;

            currentModalId = bill.id;

            document.getElementById('modalResident').textContent = bill.buyer;
            document.getElementById('modalLot').textContent = `Block ${bill.block} - Lot ${bill.lot}`;
            document.getElementById('modalAmount').textContent = `₱${Number(bill.amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            const dateObj = new Date(bill.date + 'T00:00:00');
            document.getElementById('modalDate').textContent = dateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            const deadlineEl = document.getElementById('modalDeadline');
            const deadlineCard = document.getElementById('modalDeadlineCard');
            deadlineCard.style.borderColor = '#e2e8f0';
            deadlineCard.style.background = '#f8fafc';
            if (bill.deadline) {
                const deadlineObj = new Date(bill.deadline + 'T00:00:00');
                deadlineEl.textContent = deadlineObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            } else {
                deadlineEl.textContent = '—';
            }

            const trendEl = document.getElementById('modalTrend');
            const label = reservationStatusLabel(bill.status);
            trendEl.textContent = label;
            trendEl.className = 'trend-chip ' + (bill.status === 'Cancelled' ? 'trend-late' : 'trend-early');
            trendEl.style.background = '';
            trendEl.style.color = '';
            if (bill.status === 'Reserved' || bill.status === 'Pending') {
                trendEl.style.background = '#fef3c7';
                trendEl.style.color = '#d97706';
            }

            const actionsEl = document.getElementById('modalReservationActions');
            const alertEl = document.getElementById('modalStatusAlert');
            const convertBtn = actionsEl ? actionsEl.querySelector('button[onclick="convertReservation()"]') : null;
            const terminal = bill.status === 'Cancelled';
            if (terminal) {
                actionsEl.style.display = 'none';
                alertEl.style.display = 'block';
                alertEl.style.background = '#fef2f2';
                alertEl.style.borderColor = '#fecaca';
                alertEl.style.color = '#991b1b';
                alertEl.textContent = 'This reservation is cancelled. It stays on record for reference; no further actions apply here.';
            } else {
                actionsEl.style.display = 'flex';
                if (convertBtn) {
                    convertBtn.style.display = bill.has_downpayment ? 'none' : '';
                }
                alertEl.style.display = 'none';
                alertEl.textContent = '';
            }

            document.getElementById('modalViewSection').style.display = 'block';
            document.getElementById('modalEditSection').style.display = 'none';
            document.getElementById('billModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function updateEditReservationDeadlinePreview() {
            const bill = allBillsRaw.find(b => String(b.id) === String(currentModalId));
            const monthsInput = document.getElementById('editResMonths');
            const previewEl = document.getElementById('editResDeadlinePreview');
            if (!monthsInput || !previewEl) return;

            const months = parseInt(monthsInput.value, 10);
            if (isNaN(months) || months < 1) {
                previewEl.textContent = 'Enter at least 1 month.';
                previewEl.style.color = '#dc2626';
                return;
            }

            const base = bill && bill.date ? new Date(bill.date + 'T00:00:00') : new Date();
            const day = base.getDate();
            base.setMonth(base.getMonth() + months);
            if (base.getDate() !== day) {
                base.setDate(0);
            }
            const formatted = base.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            previewEl.textContent = `Expiration: ${formatted}`;
            previewEl.style.color = '#64748b';
        }

        function openEditReservation() {
            if (!currentModalId) return;
            const bill = allBillsRaw.find(b => String(b.id) === String(currentModalId));
            if (!bill || bill.status === 'Cancelled') return;

            const buyerSelect = document.getElementById('editResBuyer');
            if (buyerSelect) {
                buyerSelect.innerHTML = '<option value="">-- Select Buyer --</option>' + masterBuyersList.map(b => {
                    const fullName = [b.first_name, b.middle_name, b.last_name].filter(Boolean).join(' ');
                    const selected = String(b.id) === String(bill.buyer_id) ? 'selected' : '';
                    return `<option value="${b.id}" ${selected}>${fullName} (${b.contact_number || 'No Contact'})</option>`;
                }).join('');
            }

            if (typeof bindGisDropdowns === 'function') {
                bindGisDropdowns('editResBlock', 'editResLot', { currentBlock: bill.block, currentLot: bill.lot });
            }

            const amountInput = document.getElementById('editResAmount');
            if (amountInput) {
                amountInput.value = Number(bill.amount || 0).toLocaleString();
            }

            let initialMonths = 1;
            if (bill.date && bill.deadline) {
                const start = new Date(bill.date + 'T00:00:00');
                const end = new Date(bill.deadline + 'T00:00:00');
                const diffMonths = (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth());
                if (diffMonths >= 1) initialMonths = diffMonths;
            }
            document.getElementById('editResMonths').value = String(initialMonths);
            updateEditReservationDeadlinePreview();

            document.getElementById('modalViewSection').style.display = 'none';
            document.getElementById('modalEditSection').style.display = 'block';
        }

        function cancelEditReservation() {
            document.getElementById('modalEditSection').style.display = 'none';
            document.getElementById('modalViewSection').style.display = 'block';
        }

        async function submitEditReservation() {
            if (!currentModalId) return;
            const btn = document.getElementById('btnSaveEditReservation');
            const resetBtn = () => {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Save Changes';
                }
            };

            const buyerId = document.getElementById('editResBuyer').value;
            const block = document.getElementById('editResBlock').value;
            const lot = document.getElementById('editResLot').value;
            const amountStr = document.getElementById('editResAmount').value.replace(/,/g, '');
            const amount = parseFloat(amountStr);
            const months = parseInt(document.getElementById('editResMonths').value, 10);

            if (!buyerId) {
                Swal.fire({ icon: 'warning', title: 'Buyer Required', text: 'Please select a buyer from the Master List.' });
                return;
            }
            if (!block || !lot) {
                Swal.fire({ icon: 'warning', title: 'Block and Lot Required', text: 'Please select both Block and Lot.' });
                return;
            }
            if (isNaN(amount) || amount <= 0) {
                Swal.fire({ icon: 'warning', title: 'Invalid Amount', text: 'Please enter a valid reservation fee amount.' });
                return;
            }
            if (isNaN(months) || months < 1 || months > 120) {
                Swal.fire({ icon: 'warning', title: 'Invalid Duration', text: 'Please enter a valid number of months (1 to 120).' });
                return;
            }

            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Saving...';
            }

            try {
                const res = await fetch(`/admin/reservation-fee/${currentModalId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        buyer_master_list_id: buyerId,
                        block: block,
                        lot: lot,
                        amount: amount,
                        months: months
                    })
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.success) {
                    window.location.reload();
                } else {
                    Swal.fire(data.message || 'Failed to update reservation.');
                    resetBtn();
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error connecting to server');
                resetBtn();
            }
        }

        function convertReservation() {
            if (!currentModalId) return;
            const bill = allBillsRaw.find(b => String(b.id) === String(currentModalId));
            if (!bill) return;
            if (!bill.buyer_id) {
                Swal.fire('This reservation is not linked to a Master List buyer. Assign a buyer before converting.');
                return;
            }
            window.location.href = `/admin/downpayment-fee?create=1&buyer_id=${encodeURIComponent(bill.buyer_id)}&reservation_id=${encodeURIComponent(bill.id)}`;
        }
        async function cancelReservation() {
            if (!currentModalId) return;
            if (!confirm('Are you sure you want to cancel this reservation?')) return;

            try {
                const res = await fetch('/admin/reservation-fee/cancel', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ id: currentModalId })
                });
                if (res.ok) {
                    window.location.reload();
                } else {
                    Swal.fire('Failed to cancel reservation.');
                }
            } catch (e) {
                console.error('Error cancelling reservation:', e);
                Swal.fire('An error occurred.');
            }
        }

        function updateRowStatus(id, badgeHtml, statusKey) {
            const row = Array.from(document.querySelectorAll('.bill-row')).find(r => r.innerHTML.includes(id));
            if (row) {
                if (statusKey) row.setAttribute('data-status', statusKey);
                const statusCell = row.querySelector('.status-cell');
                if (statusCell) statusCell.innerHTML = badgeHtml;
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('reservationSalesChart')?.getContext('2d');
            if (ctx) {
                @php
                    $chartData = getMonthlyChartData('reservation');
                @endphp
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: @json($chartData['labels']),
                        datasets: [{
                            label: 'Reservation Fee Collection (₱)',
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
                                max: 150000,
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

            const statusCtx = document.getElementById('reservationStatusChart')?.getContext('2d');
            if (statusCtx) {
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Active', 'Cancelled'],
                        datasets: [{
                            data: [{{ $resActiveCount }}, {{ $resCancelledCount }}],
                            backgroundColor: ['#f59e0b', '#ef4444'],
                            borderWidth: 0,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { usePointStyle: true, boxWidth: 8, font: { size: 11, weight: '600' } }
                            }
                        }
                    }
                });
            }
        });
        window.addEventListener('DOMContentLoaded', () => {
            if (new URLSearchParams(window.location.search).get('action') === 'add') {
                openReservationModal();
            }

            if (window.jQuery && $.fn.select2 && $('select#resBuyerMasterList').length) {
                $('select#resBuyerMasterList').select2({
                    placeholder: "-- Choose Buyer --",
                    allowClear: true,
                    width: '100%'
                });
            }

            // Bind filter events
            window.reservationTablePagination = initTablePagination({
                rowSelector: '.bill-row',
                infoEl: '#reservationTablePageInfo',
                prevEl: '#reservationTablePagePrev',
                nextEl: '#reservationTablePageNext',
                perPage: 15,
            });
            filterTable();

            document.getElementById('billingSearch')?.addEventListener('input', filterTable);
            document.getElementById('statusFilter')?.addEventListener('change', filterTable);
            document.getElementById('blockFilter')?.addEventListener('change', filterTable);
        });

        function filterTable() {
            const search = document.getElementById('billingSearch').value.toLowerCase();
            const status = document.getElementById('statusFilter').value.toLowerCase();
            const block = document.getElementById('blockFilter').value;
            
            document.querySelectorAll('.bill-row').forEach(row => {
                const text = row.innerText.toLowerCase();
                const rowStatus = (row.getAttribute('data-status') || '').toLowerCase();
                const rowBlock = (row.getAttribute('data-block') || '').toLowerCase();

                const matchesSearch = text.includes(search);
                const matchesStatus = status === 'all' || rowStatus === status;
                const matchesBlock = block === 'all' || rowBlock === block.toLowerCase();

                row.classList.toggle('pagination-match', matchesSearch && matchesStatus && matchesBlock);
            });

            window.reservationTablePagination?.resetPage();
        }

        function openReservationReportModal() {
            const currentStatus = document.getElementById('statusFilter')?.value || 'all';
            const currentBlock = document.getElementById('blockFilter')?.value || 'all';
            if (document.getElementById('reportResStatusFilter')) {
                document.getElementById('reportResStatusFilter').value = currentStatus;
            }
            if (document.getElementById('reportResBlockFilter')) {
                document.getElementById('reportResBlockFilter').value = currentBlock;
            }
            reloadReservationReportPdf();
            document.getElementById('reservationReportModal').style.display = 'flex';
        }

        function closeReservationReportModal() {
            document.getElementById('reservationReportModal').style.display = 'none';
        }

        function reloadReservationReportPdf() {
            const status = document.getElementById('reportResStatusFilter')?.value || 'all';
            const block = document.getElementById('reportResBlockFilter')?.value || 'all';
            const sort = document.getElementById('reportResSortFilter')?.value || 'name_asc';
            const base = "{{ route('admin.reservation-fee.report.pdf') }}";
            const qs = `?status=${encodeURIComponent(status)}&block=${encodeURIComponent(block)}&sort=${encodeURIComponent(sort)}`;

            const frame = document.getElementById('reservationReportPdfFrame');
            if (frame) {
                frame.src = base + qs;
            }
            const dlBtn = document.getElementById('reservationReportDownloadBtn');
            if (dlBtn) {
                dlBtn.href = base + qs + '&download=1';
            }
        }

        function printReservationReportPdfFrame() {
            const frame = document.getElementById('reservationReportPdfFrame');
            if (frame && frame.contentWindow) {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            }
        }
    </script>

    <!-- Official Reservation Fee PDF Report Modal -->
    <div id="reservationReportModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 2100; background: rgba(15,23,42,0.7); backdrop-filter: blur(8px); padding: 20px;">
        <div class="bill-modal-content" style="max-width: 1100px; width: 95%; max-height: 94vh; overflow: hidden; padding: 20px 24px; border-radius: 20px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); display: flex; flex-direction: column;">
            
            <!-- Controls Bar -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0;">
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Status</span>
                        <select id="reportResStatusFilter" class="filter-select" onchange="reloadReservationReportPdf()" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer;">
                            <option value="all">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Block</span>
                        <select id="reportResBlockFilter" class="filter-select" onchange="reloadReservationReportPdf()" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer;">
                            <option value="all">All Blocks</option>
                            @php
                                $pdfUniqueBlocks = collect($bills)->pluck('block')->filter(function($b) { return $b !== 'N/A'; })->unique()->sort();
                            @endphp
                            @foreach($pdfUniqueBlocks as $b)
                                <option value="{{ $b }}">Block {{ $b }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Sort By</span>
                        <select id="reportResSortFilter" class="filter-select" onchange="reloadReservationReportPdf()" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer;">
                            <option value="name_asc" selected>Alphabetical: Name (A to Z)</option>
                            <option value="name_desc">Alphabetical: Name (Z to A)</option>
                            <option value="date_asc">Date Reserved: Earliest to Latest</option>
                            <option value="date_desc">Date Reserved: Latest to Earliest</option>
                            <option value="amount_desc">Amount Paid: Highest First</option>
                            <option value="amount_asc">Amount Paid: Lowest First</option>
                            <option value="id_asc">Reservation ID: Ascending</option>
                            <option value="id_desc">Reservation ID: Descending</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <a id="reservationReportDownloadBtn" href="{{ route('admin.reservation-fee.report.pdf') }}?download=1" class="btn btn-primary" style="background: #0284c7; border: none; padding: 8px 16px; font-size: 12px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; color: #fff;">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download PDF
                    </a>
                    <button type="button" class="btn btn-outline" onclick="printReservationReportPdfFrame()" style="background: #fff; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #334155;">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeReservationReportModal()" style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 600; border-radius: 8px; cursor: pointer;">
                        ✕ Close
                    </button>
                </div>
            </div>

            <!-- Embedded Real PDF Viewer (Viewable in-page, no new tab) -->
            <div style="flex: 1; min-height: 0; width: 100%;">
                <iframe id="reservationReportPdfFrame" src="{{ route('admin.reservation-fee.report.pdf') }}" style="width: 100%; height: 75vh; border: 1px solid #e2e8f0; border-radius: 10px; background: #525659;"></iframe>
            </div>

        </div>
    </div>

    <!-- Official Reservation Application Receipt Modal (Viewable First + Editable GIS/Buyer/Reservation Data) -->
    <div id="reservationReceiptModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 2200; background: rgba(15,23,42,0.75); backdrop-filter: blur(8px); padding: 16px;">
        <div class="bill-modal-content" style="max-width: 1280px; width: 97%; max-height: 95vh; overflow: hidden; padding: 18px 22px; border-radius: 20px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4); display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h2 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0;">Reservation Application &amp; Official Receipt</h2>
                    <p id="receiptModalSubtitle" style="font-size: 12px; color: #64748b; margin: 2px 0 0 0;">Linked with GIS Property Specification, Buyer Information &amp; Reservation Details</p>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <button type="button" id="toggleReceiptEditorBtn" class="btn btn-outline" onclick="toggleReceiptEditorPanel()" style="background: #fffbeb; border: 1px solid #f59e0b; color: #b45309; padding: 8px 14px; font-size: 12px; font-weight: 700; border-radius: 8px; cursor: pointer;">
                        ✏️ Edit Receipt Details
                    </button>
                    <a id="reservationReceiptDownloadBtn" href="#" class="btn btn-primary" style="background: #0284c7; border: none; padding: 8px 16px; font-size: 12px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; color: #fff;">
                        Download PDF
                    </a>
                    <button type="button" class="btn btn-outline" onclick="printReservationReceiptPdfFrame()" style="background: #fff; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 700; border-radius: 8px; cursor: pointer; color: #334155;">
                        Print
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeReservationReceiptModal()" style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 600; border-radius: 8px; cursor: pointer;">
                        ✕ Close
                    </button>
                </div>
            </div>

            <div style="display: flex; gap: 16px; flex: 1; min-height: 0; overflow: hidden;">
                <!-- Collapsible Editable Data Panel -->
                <div id="receiptEditorPanel" style="display: none; width: 430px; min-width: 360px; max-width: 100%; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; background: #f8fafc;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-size: 13px; font-weight: 800; color: #0f172a;">Editable Receipt Data</span>
                        <button type="button" id="saveReceiptDataBtn" onclick="saveReservationReceiptData()" class="btn btn-primary" style="padding: 7px 14px; font-size: 12px; font-weight: 700; border-radius: 8px;">
                            Save &amp; Refresh Receipt
                        </button>
                    </div>

                    <!-- Section 1: Reservation Details -->
                    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 12px;">
                        <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #0284c7; margin-bottom: 8px;">1. Reservation Details</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Application Date</label>
                                <input type="date" id="rcpt_reservation_date" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">OR #</label>
                                <input type="text" id="rcpt_or_number" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Reservation Fee (₱)</label>
                                <input type="number" step="0.01" id="rcpt_amount" oninput="recalcReceiptFinancials()" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Net Equity OR #</label>
                                <input type="text" id="rcpt_net_equity_or_number" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Amount in Words</label>
                                <input type="text" id="rcpt_amount_in_words" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">90-Day Loan Req. Deadline</label>
                                <input type="date" id="rcpt_loan_req_deadline" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">3-Month PDC Deadline</label>
                                <input type="date" id="rcpt_pdc_deadline" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Tel. Nos.</label>
                                <input type="text" id="rcpt_tel_nos" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Witness (Staff)</label>
                                <input type="text" id="rcpt_witness_name" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Buyer Information -->
                    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 12px;">
                        <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #0284c7; margin-bottom: 8px;">2. Buyer Information</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Family Name</label>
                                <input type="text" id="rcpt_buyer_last_name" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Given Name</label>
                                <input type="text" id="rcpt_buyer_first_name" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Middle Name</label>
                                <input type="text" id="rcpt_buyer_middle_name" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Civil Status</label>
                                <select id="rcpt_civil_status" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                </select>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Spouse Marital Consent Name</label>
                                <input type="text" id="rcpt_spouse_name" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Postal Address</label>
                                <input type="text" id="rcpt_postal_address" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Billing Address (Par. 8)</label>
                                <input type="text" id="rcpt_billing_address" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: GIS Lot Specification & Installment Plan -->
                    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 8px;">
                        <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #0284c7; margin-bottom: 8px;">3. GIS Lot Specification &amp; Installment Plan</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Project</label>
                                <input type="text" id="rcpt_project_name" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">House Model (GIS Desc)</label>
                                <input type="text" id="rcpt_house_model" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Lot Area (sqm)</label>
                                <input type="number" step="0.01" id="rcpt_lot_area" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Floor Area (sqm)</label>
                                <input type="number" step="0.01" id="rcpt_floor_area" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">TCT No.</label>
                                <input type="text" id="rcpt_tct_no" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">GIS PID</label>
                                <input type="text" id="rcpt_pid" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Tax Dec No.</label>
                                <input type="text" id="rcpt_tax_dec_no" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Total Contract Price (₱)</label>
                                <input type="number" step="0.01" id="rcpt_total_contract_price" oninput="recalcReceiptFinancials()" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Discount (%)</label>
                                <input type="number" step="0.01" id="rcpt_discount_percent" oninput="recalcReceiptFinancials()" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Discount Amount (₱)</label>
                                <input type="number" step="0.01" id="rcpt_discount_amount" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Equity (%)</label>
                                <input type="number" step="0.01" id="rcpt_equity_percent" oninput="recalcReceiptFinancials()" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Required Equity (₱)</label>
                                <input type="number" step="0.01" id="rcpt_required_equity" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Net Equity Downpayment (₱)</label>
                                <input type="number" step="0.01" id="rcpt_net_equity_downpayment" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Equity Months</label>
                                <input type="number" id="rcpt_equity_period_count" oninput="recalcReceiptFinancials()" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Monthly Equity Amort. (₱)</label>
                                <input type="number" step="0.01" id="rcpt_monthly_amortization_equity" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Equity Start Date</label>
                                <input type="text" id="rcpt_equity_start_date" placeholder="e.g. Dec 01, 2026" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Financing Type</label>
                                <select id="rcpt_financing_type" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                                    <option value="PAG-IBIG FUND">PAG-IBIG FUND</option>
                                    <option value="DIRECT FINANCING">DIRECT FINANCING</option>
                                    <option value="END BUYER'S BANK FINANCING">END BUYER'S BANK FINANCING</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: #475569;">Building Label</label>
                                <input type="text" id="rcpt_building_model_label" class="filter-select" style="width: 100%; padding: 6px 8px; font-size: 12px; box-sizing: border-box;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Embedded Real PDF Viewer (Viewable first, not download) -->
                <div style="flex: 1; min-width: 0;">
                    <iframe id="reservationReceiptPdfFrame" src="about:blank" style="width: 100%; height: 76vh; border: 1px solid #e2e8f0; border-radius: 10px; background: #525659;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentReceiptReservationId = null;

        async function openReservationReceiptModal(id) {
            if (!id) return;
            currentReceiptReservationId = id;

            const modal = document.getElementById('reservationReceiptModal');
            const frame = document.getElementById('reservationReceiptPdfFrame');
            const dlBtn = document.getElementById('reservationReceiptDownloadBtn');
            const editor = document.getElementById('receiptEditorPanel');

            if (editor) editor.style.display = 'none';
            if (frame) frame.src = `/admin/reservation-fee/${id}/receipt?t=${Date.now()}`;
            if (dlBtn) dlBtn.href = `/admin/reservation-fee/${id}/receipt?download=1`;

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            try {
                const res = await fetch(`/admin/reservation-fee/${id}/receipt-data`, {
                    headers: { 'Accept': 'application/json' }
                });
                const json = await res.json();
                if (res.ok && json.data) {
                    populateReceiptEditor(json.data);
                }
            } catch (err) {
                console.error('Failed to load receipt data:', err);
            }
        }

        function closeReservationReceiptModal() {
            const modal = document.getElementById('reservationReceiptModal');
            if (modal) modal.style.display = 'none';
            if (document.getElementById('billModal')?.style.display !== 'flex') {
                document.body.style.overflow = 'auto';
            }
        }

        function toggleReceiptEditorPanel() {
            const panel = document.getElementById('receiptEditorPanel');
            if (!panel) return;
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        }

        function printReservationReceiptPdfFrame() {
            const frame = document.getElementById('reservationReceiptPdfFrame');
            if (frame && frame.contentWindow) {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            }
        }

        function populateReceiptEditor(d) {
            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = val ?? '';
            };

            setVal('rcpt_reservation_date', d.reservation_date);
            setVal('rcpt_or_number', d.or_number);
            setVal('rcpt_amount', d.amount);
            setVal('rcpt_net_equity_or_number', d.net_equity_or_number);
            setVal('rcpt_amount_in_words', d.amount_in_words);
            setVal('rcpt_loan_req_deadline', d.loan_req_deadline);
            setVal('rcpt_pdc_deadline', d.pdc_deadline);
            setVal('rcpt_tel_nos', d.tel_nos);
            setVal('rcpt_witness_name', d.witness_name);

            setVal('rcpt_buyer_last_name', d.buyer_last_name);
            setVal('rcpt_buyer_first_name', d.buyer_first_name);
            setVal('rcpt_buyer_middle_name', d.buyer_middle_name);
            setVal('rcpt_civil_status', (d.civil_status || 'Single').toLowerCase() === 'married' ? 'Married' : 'Single');
            setVal('rcpt_spouse_name', d.spouse_name);
            setVal('rcpt_postal_address', d.postal_address);
            setVal('rcpt_billing_address', d.billing_address);

            setVal('rcpt_project_name', d.project_name);
            setVal('rcpt_house_model', d.house_model);
            setVal('rcpt_lot_area', d.lot_area);
            setVal('rcpt_floor_area', d.floor_area);
            setVal('rcpt_tct_no', d.tct_no);
            setVal('rcpt_pid', d.pid);
            setVal('rcpt_tax_dec_no', d.tax_dec_no);
            setVal('rcpt_total_contract_price', d.total_contract_price);
            setVal('rcpt_discount_percent', d.discount_percent);
            setVal('rcpt_discount_amount', d.discount_amount);
            setVal('rcpt_equity_percent', d.equity_percent);
            setVal('rcpt_required_equity', d.required_equity);
            setVal('rcpt_net_equity_downpayment', d.net_equity_downpayment);
            setVal('rcpt_equity_period_count', d.equity_period_count);
            setVal('rcpt_monthly_amortization_equity', d.monthly_amortization_equity);
            setVal('rcpt_equity_start_date', d.equity_start_date);
            setVal('rcpt_financing_type', d.financing_type || 'PAG-IBIG FUND');
            setVal('rcpt_loan_balance_amount', d.loan_balance_amount);
            setVal('rcpt_loan_term', d.loan_term);
            setVal('rcpt_loan_interest_rate', d.loan_interest_rate);
            setVal('rcpt_loan_monthly_amortization', d.loan_monthly_amortization);
            setVal('rcpt_building_model_label', d.building_model_label);

            const sub = document.getElementById('receiptModalSubtitle');
            if (sub) {
                sub.textContent = `Block ${d.block || '—'}, Lot ${d.lot_number || '—'} • ${d.buyer_full_name || 'Buyer'} • OR # ${d.or_number}`;
            }
        }

        function recalcReceiptFinancials() {
            const tcp = parseFloat(document.getElementById('rcpt_total_contract_price')?.value || 0) || 0;
            const discPct = parseFloat(document.getElementById('rcpt_discount_percent')?.value || 0) || 0;
            const resFee = parseFloat(document.getElementById('rcpt_amount')?.value || 0) || 0;
            const eqPct = parseFloat(document.getElementById('rcpt_equity_percent')?.value || 20) || 0;
            const eqMonths = parseInt(document.getElementById('rcpt_equity_period_count')?.value || 24, 10) || 24;

            const discAmt = Math.round(tcp * (discPct / 100) * 100) / 100;
            const ntp = Math.max(0, Math.round((tcp - discAmt) * 100) / 100);
            const reqEq = Math.round(ntp * (eqPct / 100) * 100) / 100;
            const netEq = Math.max(0, Math.round((reqEq - resFee) * 100) / 100);
            const monthlyEq = eqMonths > 0 ? Math.round((netEq / eqMonths) * 100) / 100 : 0;
            const loanBal = Math.max(0, Math.round((ntp - reqEq) * 100) / 100);

            document.getElementById('rcpt_discount_amount').value = discAmt.toFixed(2);
            document.getElementById('rcpt_required_equity').value = reqEq.toFixed(2);
            document.getElementById('rcpt_net_equity_downpayment').value = netEq.toFixed(2);
            document.getElementById('rcpt_monthly_amortization_equity').value = monthlyEq.toFixed(2);
        }

        async function saveReservationReceiptData() {
            if (!currentReceiptReservationId) return;
            const btn = document.getElementById('saveReceiptDataBtn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Saving...';
            }

            const getVal = (id) => document.getElementById(id)?.value ?? null;
            const eqPct = parseFloat(getVal('rcpt_equity_percent') || 20) || 0;

            const payload = {
                reservation_date: getVal('rcpt_reservation_date'),
                or_number: getVal('rcpt_or_number'),
                amount: getVal('rcpt_amount'),
                net_equity_or_number: getVal('rcpt_net_equity_or_number'),
                amount_in_words: getVal('rcpt_amount_in_words'),
                loan_req_deadline: getVal('rcpt_loan_req_deadline'),
                pdc_deadline: getVal('rcpt_pdc_deadline'),
                tel_nos: getVal('rcpt_tel_nos'),
                witness_name: getVal('rcpt_witness_name'),

                buyer_last_name: getVal('rcpt_buyer_last_name'),
                buyer_first_name: getVal('rcpt_buyer_first_name'),
                buyer_middle_name: getVal('rcpt_buyer_middle_name'),
                civil_status: getVal('rcpt_civil_status'),
                spouse_name: getVal('rcpt_spouse_name'),
                postal_address: getVal('rcpt_postal_address'),
                billing_address: getVal('rcpt_billing_address'),

                project_name: getVal('rcpt_project_name'),
                house_model: getVal('rcpt_house_model'),
                lot_area: getVal('rcpt_lot_area') || null,
                floor_area: getVal('rcpt_floor_area') || null,
                tct_no: getVal('rcpt_tct_no'),
                pid: getVal('rcpt_pid'),
                tax_dec_no: getVal('rcpt_tax_dec_no'),
                total_contract_price: getVal('rcpt_total_contract_price') || 0,
                discount_percent: getVal('rcpt_discount_percent') || 0,
                discount_amount: getVal('rcpt_discount_amount') || 0,
                equity_percent: eqPct,
                loan_balance_percent: Math.max(0, 100 - eqPct),
                required_equity: getVal('rcpt_required_equity') || 0,
                net_equity_downpayment: getVal('rcpt_net_equity_downpayment') || 0,
                equity_period_count: getVal('rcpt_equity_period_count') || 24,
                term_of_equity: (getVal('rcpt_equity_period_count') || 24) + ' Months',
                monthly_amortization_equity: getVal('rcpt_monthly_amortization_equity') || 0,
                equity_start_date: getVal('rcpt_equity_start_date'),
                financing_type: getVal('rcpt_financing_type'),
                loan_balance_amount: getVal('rcpt_loan_balance_amount') || 0,
                loan_term: getVal('rcpt_loan_term'),
                loan_interest_rate: getVal('rcpt_loan_interest_rate') || null,
                loan_monthly_amortization: getVal('rcpt_loan_monthly_amortization') || 0,
                building_model_label: getVal('rcpt_building_model_label'),
            };

            try {
                const res = await fetch(`/admin/reservation-fee/${currentReceiptReservationId}/receipt-data`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (res.ok && json.success) {
                    if (json.data) populateReceiptEditor(json.data);
                    const frame = document.getElementById('reservationReceiptPdfFrame');
                    if (frame) frame.src = `/admin/reservation-fee/${currentReceiptReservationId}/receipt?t=${Date.now()}`;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'success', title: 'Receipt Updated', text: json.message, timer: 1800, showConfirmButton: false });
                    }
                } else {
                    Swal.fire({ icon: 'error', title: 'Update Failed', text: json.message || 'Could not save receipt details.' });
                }
            } catch (err) {
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Server Error', text: 'Unable to save receipt details.' });
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Save & Refresh Receipt';
                }
            }
        }
    </script>
@endsection
