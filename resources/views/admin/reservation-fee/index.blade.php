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
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Record New Reservation
            </button>
        </div>
    </div>

    <!-- Collection Summary Dashboard -->
    <div style="background: #fff; border: 1px solid var(--bill-border); border-radius: 20px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
            <h2 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">Reservation Pipeline Summary</h2>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid var(--bill-primary);">
                <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Total Reservations</div>
                <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ count($bills) }}</div>
            </div>
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid var(--bill-success);">
                <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Total Collected</div>
                <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                    @php 
                        $total = 0;
                        foreach($bills as $b) { if($b['status'] != 'Cancelled') $total += $b['amount']; }
                    @endphp
                    ₱{{ number_format($total, 2) }}
                </div>
            </div>
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid #f59e0b;">
                <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Active Holds</div>
                <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                    {{ collect($bills)->where('status', 'Reserved')->count() }}
                </div>
            </div>
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid #94a3b8;">
                <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Paid</div>
                <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                    {{ collect($bills)->where('status', 'Converted')->count() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Reservation Sales & Collection Trend Bar Graph -->
    <div style="background: #fff; border: 1px solid var(--bill-border); border-radius: 20px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">Monthly Reservation Fee Revenue Trends</h3>
                <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">12-month initial lot reservation deposits & total client bookings (in ₱ Thousands)</p>
            </div>
        </div>
        <div style="height: 200px; position: relative;">
            <canvas id="reservationSalesChart"></canvas>
        </div>
    </div>

    <!-- Reservation Ledger -->
    <div class="analytic-card">
        <div class="card-title">
            <span>Recent Reservations</span>
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
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="reservationTableBody">
                    @foreach($bills as $bill)
                    <tr class="bill-row">
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
                            @if($bill['status'] == 'Reserved')
                                <span class="badge badge-warning" style="background: #fef3c7; color: #d97706;">Active Hold</span>
                            @elseif($bill['status'] == 'Converted')
                                <span class="badge badge-success">Paid</span>
                            @else
                                <span class="badge badge-danger">Cancelled</span>
                            @endif
                        </td>
                        <td class="action-cell">
                            <div style="display: flex; gap: 8px;">
                                <button class="btn btn-outline" style="padding: 6px 10px; font-size: 11px;" onclick="viewDetail('{{ $bill['id'] }}')">Details</button>
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
                <span id="modalLot" style="font-size: 12px; font-weight: 700; color: var(--bill-primary); text-transform: uppercase; letter-spacing: 0.1em;"></span>
                <h2 id="modalResident" style="font-size: 32px; font-weight: 800; color: #0f172a; margin-top: 8px;"></h2>
                <div style="margin-top: 12px; display: flex; gap: 12px; align-items: center;">
                    <span id="modalTrend" class="trend-chip trend-early"></span>
                </div>
            </div>
            <button class="ann-btn-icon" onclick="closeModal()" style="padding: 12px; background: #f1f5f9;">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div id="modalMainContent">
                <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; margin-bottom: 20px; color: #64748b;">Reservation Details</h3>
                <div class="modal-summary-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
                    <div style="background: var(--bill-bg-soft); padding: 20px; border-radius: 16px;">
                        <span style="font-size: 12px; color: #64748b;">Amount Paid</span>
                        <div id="modalAmount" style="font-size: 24px; font-weight: 700; color: #0f172a; margin-top: 4px;"></div>
                    </div>
                    <div style="background: var(--bill-bg-soft); padding: 20px; border-radius: 16px;">
                        <span style="font-size: 12px; color: #64748b;">Date Recorded</span>
                        <div id="modalDate" style="font-size: 24px; font-weight: 700; color: var(--bill-primary); margin-top: 4px;"></div>
                    </div>
                </div>

                <div style="display: flex; gap: 16px; margin-top: 40px; border-top: 1px solid var(--bill-border); padding-top: 32px;">
                    <button class="btn btn-primary" onclick="convertReservation()">Convert to Downpayment</button>
                    <button class="btn btn-outline" style="color: var(--bill-danger); border-color: #fee2e2;" onclick="cancelReservation()">Cancel Reservation</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Record New Reservation Modal -->
<div id="newReservationModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 3000;">
    <div class="bill-modal-content" style="max-width: 500px; padding: 32px; border-radius: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="font-size: 24px; font-weight: 800; color: #0f172a;">Manual Reservation Logging</h2>
            <button onclick="closeReservationModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Buyer Name</label>
                <input type="text" id="resName" class="filter-select" style="width: 100%; padding: 12px;" placeholder="Full Name">
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Contact Number</label>
                <input type="text" id="resContact" class="filter-select" style="width: 100%; padding: 12px;" placeholder="09XX-XXX-XXXX">
            </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Block</label>
                <select id="resBlock" class="filter-select" style="width: 100%; padding: 12px;"><option value="1">Block 1</option><option value="2">Block 2</option><option value="3">Block 3</option><option value="4">Block 4</option><option value="5">Block 5</option><option value="6">Block 6</option><option value="7">Block 7</option><option value="8">Block 8</option><option value="9">Block 9</option><option value="10">Block 10</option><option value="11">Block 11</option><option value="12">Block 12</option><option value="13">Block 13</option><option value="14">Block 14</option><option value="15">Block 15</option><option value="16">Block 16</option></select>
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Lot</label>
                <select id="resLot" class="filter-select" style="width: 100%; padding: 12px;"><option value="1">Lot 1</option><option value="2">Lot 2</option><option value="3">Lot 3</option><option value="4">Lot 4</option><option value="5">Lot 5</option><option value="6">Lot 6</option><option value="7">Lot 7</option><option value="8">Lot 8</option><option value="9">Lot 9</option><option value="10">Lot 10</option><option value="11">Lot 11</option><option value="12">Lot 12</option><option value="13">Lot 13</option><option value="14">Lot 14</option><option value="15">Lot 15</option></select>
            </div>
        </div>
        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Amount Paid (₱)</label>
            <input type="text" id="resAmount" class="filter-select" style="width: 100%; font-size: 16px; padding: 12px;" value="20,000" oninput="formatNumberInput(this)">
        </div>

        <button class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px;" onclick="submitReservation()">Save Reservation Record</button>
    </div>
</div>

<script src="{{ asset('js/gis-dropdowns.js') }}"></script>
<script>
    const allBillsRaw = @json($bills);

    let currentModalId = null;

    function formatNumberInput(input) {
        let value = input.value.replace(/,/g, '');
        if (!isNaN(value) && value !== '') {
            input.value = Number(value).toLocaleString('en-US');
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

        const name = document.getElementById('resName').value.trim();
        const contact = document.getElementById('resContact').value.trim();
        const block = document.getElementById('resBlock').value;
        const lot = document.getElementById('resLot').value;
        const amountStr = document.getElementById('resAmount').value.replace(/,/g, '');
        const amount = parseFloat(amountStr);

        if (!name || !contact || isNaN(amount) || amount <= 0) {
            alert('Please fill in all required fields (Name, Contact, Amount) with valid data.');
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
                    name: name,
                    contact: contact,
                    block: block,
                    lot: lot,
                    amount: amount
                })
            });

            if (res.ok) {
                window.location.reload();
            } else {
                alert('Failed to save reservation to database');
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Save Reservation Record';
                    btn.style.opacity = '1';
                }
            }
        } catch (e) {
            console.error(e);
            alert('Error connecting to server');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Save Reservation Record';
                btn.style.opacity = '1';
            }
        }
    }

    function viewDetail(id) {
        const bill = allBillsRaw.find(b => b.id === id);
        if (!bill) return;

        currentModalId = id;

        document.getElementById('modalResident').textContent = bill.buyer;
        document.getElementById('modalLot').textContent = `Block ${bill.block} - Lot ${bill.lot}`;
        document.getElementById('modalAmount').textContent = `₱${bill.amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        
        const dateObj = new Date(bill.date);
        document.getElementById('modalDate').textContent = dateObj.toLocaleDateString('en-US', {month: 'long', day: 'numeric', year: 'numeric'});
        
        const trendEl = document.getElementById('modalTrend');
        trendEl.textContent = bill.status;
        trendEl.className = 'trend-chip ' + (bill.status === 'Converted' ? 'trend-early' : (bill.status === 'Cancelled' ? 'trend-late' : 'trend-early'));
        if (bill.status === 'Reserved') {
            trendEl.style.background = '#fef3c7';
            trendEl.style.color = '#d97706';
        }

        document.getElementById('billModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function convertReservation() {
        if(!currentModalId) return;
        const bill = allBillsRaw.find(b => b.id === currentModalId);
        if(bill) bill.status = 'Converted';
        
        updateRowStatus(currentModalId, '<span class="badge badge-success">Paid</span>');
        closeModal();
        if (window.pushSystemNotification) pushSystemNotification("Paid", "Reservation successfully marked as Paid.", "System");
    }
    async function cancelReservation() {
        if(!currentModalId) return;
        if(!confirm('Are you sure you want to cancel this reservation?')) return;
        
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
                alert('Failed to cancel reservation.');
            }
        } catch (e) {
            console.error('Error cancelling reservation:', e);
            alert('An error occurred.');
        }
    }

    function updateRowStatus(id, badgeHtml) {
        const row = Array.from(document.querySelectorAll('.bill-row')).find(r => r.innerHTML.includes(id));
        if(row) {
            const statusCell = row.querySelector('.status-cell') || row.cells[5];
            if(statusCell) statusCell.innerHTML = badgeHtml;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
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
        bindGisDropdowns('resBlock', 'resLot', { blockPrefix: 'Block ', lotPrefix: 'Lot ' });
        
        if (new URLSearchParams(window.location.search).get('action') === 'add') {
            openReservationModal();
        }
    });

</script>
@endsection
