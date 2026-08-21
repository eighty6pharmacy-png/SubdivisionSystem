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
                <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Converted to DP</div>
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
            <span class="badge" style="background: #f3e8ff; color: #7e22ce; font-weight: 700; padding: 6px 14px; border-radius: 12px;">6 Active Reservations</span>
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
                                <span class="badge badge-success">Converted to DP</span>
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

                <div style="padding: 24px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px; margin-bottom: 32px;">
                    <h4 style="font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Sales Agent Notes</h4>
                    <p id="modalPredictiveText" style="font-size: 13px; color: #334155; line-height: 1.5;"></p>
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
            <h3 style="font-size: 20px; font-weight: 800; color: #0f172a;">Record New Reservation</h3>
            <button onclick="document.getElementById('newReservationModal').style.display='none'" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
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
                <select id="resBlock" class="filter-select" style="width: 100%; padding: 12px;"><option value="1">Block 1</option><option value="2">Block 2</option><option value="3">Block 3</option><option value="4">Block 4</option><option value="5">Block 5</option><option value="6">Block 6</option><option value="7">Block 7</option><option value="8">Block 8</option></select>
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Lot</label>
                <select id="resLot" class="filter-select" style="width: 100%; padding: 12px;"><option value="1">Lot 1</option><option value="2">Lot 2</option><option value="3">Lot 3</option></select>
            </div>
        </div>
        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Amount Paid (₱)</label>
            <input type="number" id="resAmount" class="filter-select" style="width: 100%; font-size: 16px; padding: 12px;" value="20000">
        </div>

        <button class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px;" onclick="submitReservation()">Save Reservation Record</button>
    </div>
</div>

<script>
    const allBillsRaw = @json($bills);

    let currentModalId = null;

    function openReservationModal() {
        document.getElementById('newReservationModal').style.display = 'flex';
    }
    
    function submitReservation() {
        const name = document.getElementById('resName').value || 'New Buyer';
        const contact = document.getElementById('resContact').value || 'N/A';
        const block = document.getElementById('resBlock').value;
        const lot = document.getElementById('resLot').value;
        const amount = parseFloat(document.getElementById('resAmount').value) || 20000;
        const date = new Date().toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});
        const newId = 'RESV-' + Math.floor(Math.random() * 9000 + 1000);

        allBillsRaw.push({
            id: newId, buyer: name, contact: contact, block: block, lot: lot, amount: amount, date: new Date().toISOString(), status: 'Reserved', notes: 'Manually logged.'
        });

        const tbody = document.getElementById('reservationTableBody');
        const tr = document.createElement('tr');
        tr.className = 'bill-row fade-in';
        tr.id = 'row-' + newId;
        tr.innerHTML = `
            <td>
                <div style="font-weight: 700;">${name}</div>
                <div style="font-size: 11px; color: #64748b;">ID: ${newId}</div>
            </td>
            <td>
                <div style="font-weight: 600; color: var(--bill-primary);">Block ${block}</div>
                <div style="font-size: 12px; color: #64748b;">Lot ${lot}</div>
            </td>
            <td><div style="font-size: 13px;">${contact}</div></td>
            <td><div style="font-weight: 700;">₱${amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div></td>
            <td><div style="font-size: 13px;">${date}</div></td>
            <td class="status-cell">
                <span class="badge badge-warning" style="background: #fef3c7; color: #d97706;">Active Hold</span>
            </td>
            <td class="action-cell">
                <div style="display: flex; gap: 8px;">
                    <button class="btn btn-outline" style="padding: 6px 10px; font-size: 11px;" onclick="viewDetail('${newId}')">Details</button>
                </div>
            </td>
        `;
        tbody.insertBefore(tr, tbody.firstChild);

        document.getElementById('newReservationModal').style.display = 'none';
        if (window.pushSystemNotification) {
            window.pushSystemNotification("Reservation Recorded", "A new lot reservation has been added to the pipeline.", "System");
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
        
        document.getElementById('modalPredictiveText').textContent = bill.notes || 'No notes provided by agent.';
        
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
        
        updateRowStatus(currentModalId, '<span class="badge badge-success">Converted to DP</span>');
        closeModal();
        if (window.pushSystemNotification) pushSystemNotification("Converted", "Reservation successfully converted to Downpayment.", "System");
    }

    function cancelReservation() {
        if(!currentModalId) return;
        const bill = allBillsRaw.find(b => b.id === currentModalId);
        if(bill) bill.status = 'Cancelled';
        
        updateRowStatus(currentModalId, '<span class="badge badge-danger">Cancelled</span>');
        closeModal();
        if (window.pushSystemNotification) pushSystemNotification("Cancelled", "Reservation has been cancelled.", "System");
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
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May (Now)', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Reservation Fee Collection (₱)',
                        data: [40000, 60000, 80000, 120000, 100000, 70000, 80000, 90000, 60000, 50000, 45000, 80000],
                        backgroundColor: [
                            '#a855f7', '#a855f7', '#a855f7', '#a855f7', '#6366f1',
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
</script>
@endsection
