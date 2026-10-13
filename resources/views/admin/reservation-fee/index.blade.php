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
            <div class="bill-actions">
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
            style="background: #fff; border: 1px solid var(--bill-border); border-radius: 20px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
                <h2 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">Reservation Pipeline Summary</h2>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <div
                    style="background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid var(--bill-primary);">
                    <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Total
                        Reservations</div>
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
                                if ($b['status'] != 'Cancelled')
                                    $total += $b['amount'];
                            }
                        @endphp
                        ₱{{ number_format($total, 2) }}
                    </div>
                </div>
                <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid #f59e0b;">
                    <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Active
                    </div>
                    <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                        {{ collect($bills)->whereIn('status', ['Reserved', 'Pending'])->count() }}
                    </div>
                </div>
                <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid #94a3b8;">
                    <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Converted</div>
                    <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                        {{ collect($bills)->where('status', 'Converted')->count() }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Reservation Sales & Collection Trend Bar Graph -->
        <div
            style="background: #fff; border: 1px solid var(--bill-border); border-radius: 20px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div>
                    <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">Monthly Reservation Fee
                        Revenue Trends</h3>
                    <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">12-month initial lot reservation deposits
                        & total client bookings (in ₱ Thousands)</p>
                </div>
            </div>
            <div style="height: 200px; position: relative;">
                <canvas id="reservationSalesChart"></canvas>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar" style="display: flex; gap: 16px; margin-bottom: 24px; padding: 16px; background: #fff; border-radius: 12px; border: 1px solid var(--bill-border);">
            <div class="filter-group" style="flex: 1;">
                <span class="filter-label" style="display: block; font-size: 12px; color: #64748b; margin-bottom: 4px;">Search</span>
                <input type="text" id="billingSearch" class="filter-select" placeholder="Buyer or Lot..." style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
            <div class="filter-group">
                <span class="filter-label" style="display: block; font-size: 12px; color: #64748b; margin-bottom: 4px;">Status</span>
                <select id="statusFilter" class="filter-select" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    <option value="all">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="converted">Converted</option>
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
                            <th>Buyer Name</th>
                            <th>Block & Lot</th>
                            <th>Contact Info</th>
                            <th>Amount Paid</th>
                            <th>Date Reserved</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="reservationTableBody">
                        @foreach($bills as $bill)
                            @php
                                $statusKey = match ($bill['status']) {
                                    'Reserved', 'Pending' => 'active',
                                    'Converted' => 'converted',
                                    'Cancelled' => 'cancelled',
                                    default => strtolower((string) $bill['status']),
                                };
                                $statusLabel = match ($statusKey) {
                                    'active' => 'Active',
                                    'converted' => 'Converted',
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
                                        $deadlineNote = 'No deadline';
                                        $deadlineOverdue = false;
                                        if (!empty($bill['deadline'])) {
                                            $deadlineDate = \Carbon\Carbon::parse($bill['deadline'])->startOfDay();
                                            $today = now()->startOfDay();
                                            if ($deadlineDate->lt($today)) {
                                                $deadlineOverdue = true;
                                                $overdueDays = (int) $deadlineDate->diffInDays($today);
                                                $deadlineNote = 'Overdue '.$overdueDays.' '.($overdueDays === 1 ? 'day' : 'days');
                                            } elseif ($deadlineDate->equalTo($today)) {
                                                $deadlineNote = 'Due today';
                                            } else {
                                                $daysLeft = (int) $today->diffInDays($deadlineDate);
                                                $deadlineNote = $daysLeft.' '.($daysLeft === 1 ? 'day' : 'days').' left';
                                            }
                                        }
                                    @endphp
                                    <div style="font-size: 13px; font-weight: 700; color: {{ $deadlineOverdue ? '#dc2626' : '#0f172a' }};">
                                        {{ !empty($bill['deadline']) ? date('M d, Y', strtotime($bill['deadline'])) : '—' }}
                                    </div>
                                    <div style="font-size: 11px; margin-top: 4px; font-weight: 700; color: {{ $deadlineOverdue ? '#dc2626' : '#64748b' }};">{{ $deadlineNote }}</div>
                                </td>
                                <td class="status-cell">
                                    @if($statusKey === 'active')
                                        <span class="badge badge-warning" style="background: #fef3c7; color: #d97706;">{{ $statusLabel }}</span>
                                    @elseif($statusKey === 'converted')
                                        <span class="badge badge-success">{{ $statusLabel }}</span>
                                    @else
                                        <span class="badge badge-danger">{{ $statusLabel }}</span>
                                    @endif
                                </td>
                                <td class="action-cell">
                                    <div style="display: flex; gap: 8px;">
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
                            <span style="font-size: 12px; color: #64748b;">Deadline</span>
                            <div id="modalDeadline" style="font-size: 24px; font-weight: 700; color: #0f172a; margin-top: 4px;"></div>
                            <div id="modalDeadlineNote" style="font-size: 13px; font-weight: 700; margin-top: 8px;"></div>
                        </div>
                    </div>

                    <div id="modalStatusAlert" role="status"
                        style="display: none; margin-top: 32px; padding: 14px 16px; border-radius: 12px; font-size: 14px; font-weight: 600; line-height: 1.5; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569;">
                    </div>

                    <div id="modalReservationActions"
                        style="display: flex; gap: 16px; margin-top: 40px; border-top: 1px solid var(--bill-border); padding-top: 32px;">
                        <button type="button" class="btn btn-primary" onclick="convertReservation()">Convert to Downpayment</button>
                        <button type="button" class="btn btn-outline" style="color: var(--bill-danger); border-color: #fee2e2;"
                            onclick="cancelReservation()">Cancel Reservation</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Record New Reservation Modal -->
    <div id="newReservationModal" class="bill-modal"
        style="display: none; align-items: center; justify-content: center; z-index: 3000;">
        <div class="bill-modal-content" style="max-width: 500px; padding: 32px; border-radius: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h2 style="font-size: 24px; font-weight: 800; color: #0f172a;">Manual Reservation Logging</h2>
                <button onclick="closeReservationModal()"
                    style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Select
                    Buyer from Master List</label>
                <select id="resBuyerMasterList" class="filter-select" style="width: 100%; padding: 12px;"
                    onchange="autoFillReservationBuyer(this)">
                    <option value="">-- Choose Buyer --</option>
                    @foreach($masterListBuyers as $b)
                        <option value="{{ $b->id }}" data-name="{{ $b->first_name }} {{ $b->last_name }}"
                            data-contact="{{ $b->contact_number }}" data-block="{{ $b->block_no }}" data-lot="{{ $b->lot_no }}">
                            {{ $b->last_name }}, {{ $b->first_name }}
                            {{ $b->middle_name ? substr($b->middle_name, 0, 1) . '.' : '' }} — (Block {{ $b->block_no ?? '?' }}, Lot {{ $b->lot_no ?? '?' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; display: none;"
                id="manualNameContact">
                <div>
                    <label
                        style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Buyer
                        Name</label>
                    <input type="text" id="resName" class="filter-select" style="width: 100%; padding: 12px;"
                        placeholder="Full Name">
                </div>
                <div>
                    <label
                        style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Contact
                        Number</label>
                    <input type="text" id="resContact" class="filter-select" style="width: 100%; padding: 12px;"
                        placeholder="09XX-XXX-XXXX">
                </div>
            </div>

            <div id="resAssignedLot"
                style="margin-bottom: 16px; padding: 12px 14px; border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 14px; font-weight: 700; color: #64748b;">
                Select a buyer to use their block and lot from the Master List.
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                <div>
                    <label
                        style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Amount
                        Paid (₱)</label>
                    <input type="text" id="resAmount" class="filter-select"
                        style="width: 100%; font-size: 16px; padding: 12px;" value="20,000"
                        oninput="formatNumberInput(this)">
                </div>
                <div>
                    <label
                        style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Deadline
                        of Reservation</label>
                    <input type="date" id="resDeadline" class="filter-select"
                        style="width: 100%; font-size: 16px; padding: 12px;"
                        value="{{ \Carbon\Carbon::now()->addDays(30)->format('Y-m-d') }}">
                </div>
            </div>

            <button class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px;"
                onclick="submitReservation()">Save Reservation Record</button>
        </div>
    </div>

    <script>
        const allBillsRaw = @json($bills);

        let currentModalId = null;

        function formatNumberInput(input) {
            let value = input.value.replace(/,/g, '');
            if (!isNaN(value) && value !== '') {
                input.value = Number(value).toLocaleString('en-US');
            }
        }

        function autoFillReservationBuyer(select) {
            const option = select.options[select.selectedIndex];
            const label = document.getElementById('resAssignedLot');
            if (!option || !option.value) {
                label.textContent = 'Select a buyer to use their block and lot from the Master List.';
                label.style.color = '#64748b';
                return;
            }

            document.getElementById('resName').value = option.getAttribute('data-name');
            document.getElementById('resContact').value = option.getAttribute('data-contact');

            const blk = option.getAttribute('data-block');
            const lot = option.getAttribute('data-lot');
            if (blk && lot) {
                label.textContent = `Block ${blk}, Lot ${lot}`;
                label.style.color = '#0f172a';
            } else {
                label.textContent = 'This buyer has no block and lot on the Master List. Set them there before reserving.';
                label.style.color = '#b45309';
            }
        }

        function openReservationModal() {
            if (new URLSearchParams(window.location.search).get('action') !== 'add') {
                window.history.pushState(null, '', '?action=add');
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
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Saving...';
                btn.style.opacity = '0.7';
            }

            const buyerSelect = document.getElementById('resBuyerMasterList');
            const buyerId = buyerSelect.value;
            const buyerOption = buyerSelect.options[buyerSelect.selectedIndex];
            const name = document.getElementById('resName').value.trim();
            const contact = document.getElementById('resContact').value.trim();
            const deadline = document.getElementById('resDeadline').value;
            const amountStr = document.getElementById('resAmount').value.replace(/,/g, '');
            const amount = parseFloat(amountStr);

            if (!buyerId) {
                Swal.fire('Please select a buyer from the Master List.');
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Save Reservation Record';
                    btn.style.opacity = '1';
                }
                return;
            }

            if (!buyerOption.getAttribute('data-block') || !buyerOption.getAttribute('data-lot')) {
                Swal.fire('This buyer has no block and lot on the Master List. Set them there before reserving.');
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Save Reservation Record';
                    btn.style.opacity = '1';
                }
                return;
            }

            if (isNaN(amount) || amount <= 0) {
                Swal.fire('Please enter a valid amount.');
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Save Reservation Record';
                    btn.style.opacity = '1';
                }
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
                        name: name,
                        contact: contact,
                        amount: amount,
                        deadline_date: deadline
                    })
                });

                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    window.location.reload();
                } else {
                    Swal.fire(data.message || 'Failed to save reservation to database');
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = 'Save Reservation Record';
                        btn.style.opacity = '1';
                    }
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error connecting to server');
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Save Reservation Record';
                    btn.style.opacity = '1';
                }
            }
        }

        function reservationStatusLabel(status) {
            if (status === 'Reserved' || status === 'Pending') return 'Active';
            if (status === 'Converted') return 'Converted';
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
            const deadlineNoteEl = document.getElementById('modalDeadlineNote');
            const deadlineCard = document.getElementById('modalDeadlineCard');
            if (bill.deadline) {
                const deadlineObj = new Date(bill.deadline + 'T00:00:00');
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const days = Math.round((deadlineObj - today) / 86400000);
                deadlineEl.textContent = deadlineObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                if (days < 0) {
                    const overdueDays = Math.abs(days);
                    deadlineNoteEl.textContent = `Overdue ${overdueDays} ${overdueDays === 1 ? 'day' : 'days'}.`;
                    deadlineNoteEl.style.color = '#dc2626';
                    deadlineCard.style.borderColor = '#fecaca';
                    deadlineCard.style.background = '#fef2f2';
                } else if (days === 0) {
                    deadlineNoteEl.textContent = 'Due today.';
                    deadlineNoteEl.style.color = '#d97706';
                    deadlineCard.style.borderColor = '#fde68a';
                    deadlineCard.style.background = '#fffbeb';
                } else {
                    deadlineNoteEl.textContent = `${days} ${days === 1 ? 'day' : 'days'} left from today.`;
                    deadlineNoteEl.style.color = '#047857';
                    deadlineCard.style.borderColor = '#e2e8f0';
                    deadlineCard.style.background = '#f8fafc';
                }
            } else {
                deadlineEl.textContent = '—';
                deadlineNoteEl.textContent = 'No deadline was set for this reservation.';
                deadlineNoteEl.style.color = '#64748b';
                deadlineCard.style.borderColor = '#e2e8f0';
                deadlineCard.style.background = '#f8fafc';
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
            const terminal = bill.status === 'Cancelled' || bill.status === 'Converted';
            if (terminal) {
                actionsEl.style.display = 'none';
                alertEl.style.display = 'block';
                if (bill.status === 'Cancelled') {
                    alertEl.style.background = '#fef2f2';
                    alertEl.style.borderColor = '#fecaca';
                    alertEl.style.color = '#991b1b';
                    alertEl.textContent = 'This reservation is cancelled. It stays on record for reference; no further actions apply here.';
                } else {
                    alertEl.style.background = '#ecfdf5';
                    alertEl.style.borderColor = '#a7f3d0';
                    alertEl.style.color = '#065f46';
                    alertEl.innerHTML = 'This reservation was converted to downpayment. Manage the contract on the <a href="/admin/downpayment-fee" style="color:#047857;font-weight:800;">Downpayment Fee</a> page.';
                }
            } else {
                actionsEl.style.display = 'flex';
                alertEl.style.display = 'none';
                alertEl.textContent = '';
            }

            document.getElementById('billModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
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
        });
        window.addEventListener('DOMContentLoaded', () => {
            if (new URLSearchParams(window.location.search).get('action') === 'add') {
                openReservationModal();
            }

            if (window.jQuery && $.fn.select2) {
                $('#resBuyerMasterList').select2({
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
    </script>
@endsection
