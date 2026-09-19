@extends('layouts.admin')

@section('title', 'Downpayment Fee')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">

<div class="bill-container fade-in">
    <div class="bill-header">
        <div class="bill-title">
            <h1>Downpayment Fee Tracking</h1>
            <p>Manage and track downpayment installments for property acquisitions.</p>
        </div>
        <div class="bill-actions" style="display: flex; gap: 12px;">
            <button class="btn btn-outline" style="border-color: var(--bill-primary); color: var(--bill-primary);" onclick="openContractModal()">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Create New DP Contract
            </button>
        </div>
    </div>

    <!-- Collection Summary Dashboard -->
    <div style="background: #fff; border: 1px solid var(--bill-border); border-radius: 20px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
            <h2 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">Downpayment Portfolio Health</h2>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid var(--bill-primary);">
                <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Active Contracts</div>
                <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ count($bills) }}</div>
            </div>
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid var(--bill-success);">
                <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Total Collected</div>
                <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                    @php 
                        $total = 0;
                        foreach($bills as $b) { $total += $b['paid_amount']; }
                    @endphp
                    ₱{{ number_format($total, 2) }}
                </div>
            </div>
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid #f59e0b;">
                <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Pending Balance</div>
                <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                    @php 
                        $bal = 0;
                        foreach($bills as $b) { $bal += ($b['total_dp'] - $b['paid_amount']); }
                    @endphp
                    ₱{{ number_format($bal, 2) }}
                </div>
            </div>
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid var(--bill-danger);">
                <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Delinquent</div>
                <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                    {{ collect($bills)->where('status', 'Delinquent')->count() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Downpayment & Amortization Sales Trend Bar Graph -->
    <div style="background: #fff; border: 1px solid var(--bill-border); border-radius: 20px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">Monthly Downpayment & Amortization Revenue Trends</h3>
                <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">12-month buyer installment collections & monthly amortization sales (in ₱ Thousands)</p>
            </div>
        </div>
        <div style="height: 200px; position: relative;">
            <canvas id="downpaymentSalesChart"></canvas>
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
                        <th>Client Details</th>
                        <th>Progress</th>
                        <th>Monthly Amortization</th>
                        <th>Next Due</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="dpTableBody">
                    @foreach($bills as $bill)
                    @php
                        $progress = ($bill['paid_amount'] / $bill['total_dp']) * 100;
                    @endphp
                    <tr class="bill-row" id="row-{{ $bill['id'] }}">
                        <td>
                            <div style="font-weight: 700;">{{ $bill['buyer'] }}</div>
                            <div style="font-size: 12px; color: #64748b;">ID: {{ strtoupper($bill['id']) }} &bull; Block {{ $bill['block'] }} Lot {{ $bill['lot'] }}</div>
                        </td>
                        <td style="min-width: 200px;" id="progress-cell-{{ $bill['id'] }}">
                            <div style="display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 4px; font-weight: 700; color: #64748b;">
                                <span class="paid-label">₱{{ number_format($bill['paid_amount']) }} Paid</span>
                                <span>₱{{ number_format($bill['total_dp']) }} Total</span>
                            </div>
                            <div style="width: 100%; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                <div class="progress-fill" style="height: 100%; width: {{ $progress }}%; background: var(--bill-primary); transition: width 0.5s ease;"></div>
                            </div>
                            <div class="months-label" style="font-size: 10px; color: #94a3b8; margin-top: 4px; text-align: right;">{{ $bill['months_paid'] }} of {{ $bill['total_months'] }} mos</div>
                        </td>
                        <td>
                            <div style="font-weight: 700;">₱{{ number_format($bill['monthly_amortization'], 2) }}</div>
                        </td>
                        <td>
                            <div style="font-size: 13px;">{{ $bill['next_due'] != 'N/A' ? date('M d, Y', strtotime($bill['next_due'])) : 'N/A' }}</div>
                        </td>
                        <td id="status-cell-{{ $bill['id'] }}">
                            @if($bill['status'] == 'Good Standing')
                                <span class="badge badge-success">Good Standing</span>
                            @elseif($bill['status'] == 'Fully Paid')
                                <span class="badge badge-success" style="background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;">Fully Paid 🎉</span>
                            @else
                                <span class="badge badge-danger">Delinquent</span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button class="btn btn-outline" style="padding: 6px 10px; font-size: 11px;" onclick="viewDetail('{{ $bill['id'] }}')">Manage</button>
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
                <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; margin-bottom: 20px; color: #64748b;">Contract Details</h3>
                <div class="modal-summary-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
                    <div style="background: var(--bill-bg-soft); padding: 20px; border-radius: 16px;">
                        <span style="font-size: 12px; color: #64748b;">Remaining Balance</span>
                        <div id="modalAmount" style="font-size: 24px; font-weight: 700; color: #0f172a; margin-top: 4px;"></div>
                    </div>
                    <div style="background: var(--bill-bg-soft); padding: 20px; border-radius: 16px;">
                        <span style="font-size: 12px; color: #64748b;">Next Installment Due</span>
                        <div id="modalDate" style="font-size: 24px; font-weight: 700; color: var(--bill-primary); margin-top: 4px;"></div>
                    </div>
                </div>

                <div style="padding: 24px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px; margin-bottom: 32px;">
                    <h4 style="font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 16px; text-transform: uppercase;">Log Installment Payment</h4>
                    <div style="display: flex; gap: 12px; align-items: flex-end;">
                        <div style="flex: 1;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Amount Paid (₱)</label>
                            <input type="number" id="directPaymentAmount" class="filter-select" style="width: 100%; font-size: 16px; padding: 12px;" placeholder="e.g. 15000">
                        </div>
                        <button class="btn btn-primary" style="padding: 14px 24px;" onclick="submitPayment()">Submit Payment</button>
                    </div>
                </div>

                <div style="display: flex; gap: 16px; margin-top: 40px; border-top: 1px solid var(--bill-border); padding-top: 32px;">
                    <button class="btn btn-outline" style="color: var(--bill-primary); border-color: var(--bill-primary);" onclick="alert('Creating receipt...')">Create Receipt</button>
                    <button class="btn btn-outline" onclick="viewHistory()">View History</button>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- Calculator & Create Contract Modal -->
<div id="newContractModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 3000;">
    <div class="bill-modal-content" style="max-width: 550px; padding: 32px; border-radius: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="font-size: 20px; font-weight: 800; color: #0f172a;">DP Calculator & Contract Setup</h3>
            <button onclick="closeContractModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div style="display: flex; gap: 8px;">
                <div style="flex:1;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">First Name</label>
                    <input type="text" id="calcFirstName" class="filter-select" style="width: 100%; padding: 12px;" placeholder="First Name" required>
                </div>
                <div style="flex:1;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Last Name</label>
                    <input type="text" id="calcLastName" class="filter-select" style="width: 100%; padding: 12px;" placeholder="Last Name" required>
                </div>
            </div>
            <div style="display: flex; gap: 8px;">
                <div style="flex:1;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Blk</label>
                    <select id="calcBlk" class="filter-select" style="width: 100%; padding: 12px;"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option></select>
                </div>
                <div style="flex:1;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Lot</label>
                    <select id="calcLot" class="filter-select" style="width: 100%; padding: 12px;"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option></select>
                </div>
            </div>
        </div>

        <div style="padding: 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; margin-bottom: 24px;">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Unit Price (TCP) ₱</label>
                    <input type="number" id="calcTcp" class="filter-select" style="width: 100%; padding: 12px; font-weight: 700;" placeholder="2000000">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">DP Percentage %</label>
                    <input type="number" id="calcDpPercent" class="filter-select" style="width: 100%; padding: 12px; font-weight: 700;" value="30">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: var(--bill-primary); margin-bottom: 8px;">Total DP Amount ₱</label>
                    <input type="text" id="calcDpAmount" class="filter-select" style="width: 100%; padding: 12px; background: #e2e8f0; font-weight: 800;" readonly>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Months to Pay</label>
                    <input type="number" id="calcMonths" class="filter-select" style="width: 100%; padding: 12px;" value="24">
                </div>
            </div>
            <div style="margin-top: 16px; border-top: 1px dashed #cbd5e1; padding-top: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: 8px; text-transform: uppercase;">Calculated Monthly Amortization</label>
                <div id="calcAmortization" style="font-size: 32px; font-weight: 800; color: var(--bill-primary);">₱0.00</div>
            </div>
        </div>

        <button class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px;" onclick="submitContract()">Save & Initialize Contract</button>
    </div>
</div>

<script src="{{ asset('js/gis-dropdowns.js') }}"></script>
<script>
    const allBillsRaw = @json($bills);

    let currentModalId = null;

    function openContractModal() {
        if (new URLSearchParams(window.location.search).get('action') !== 'add') {
            window.history.pushState(null, '', '?action=add');
        }
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


    
    async function submitPayment() {
        if(!currentModalId) return;
        const amount = parseFloat(document.getElementById('directPaymentAmount').value);
        if(!amount || amount <= 0) return alert('Enter valid amount');

        try {
            const res = await fetch('/admin/downpayment-fee/pay', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ id: currentModalId, amount: amount })
            });

            if (res.ok) {
                const bill = allBillsRaw.find(b => b.id === currentModalId);
                if(bill) {
                    bill.paid_amount += amount;
                    bill.months_paid += 1;
                    
                    let progress = (bill.paid_amount / bill.total_dp) * 100;
                    if(progress > 100) progress = 100;
                    if(bill.paid_amount >= bill.total_dp) {
                        bill.status = 'Fully Paid';
                        bill.paid_amount = bill.total_dp;
                    } else {
                        bill.status = 'Good Standing';
                    }

                    const cell = document.getElementById('progress-cell-' + currentModalId);
                    if(cell) {
                        cell.querySelector('.paid-label').textContent = `₱${bill.paid_amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} Paid`;
                        cell.querySelector('.progress-fill').style.width = `${progress}%`;
                        cell.querySelector('.months-label').textContent = `${bill.months_paid} of ${bill.total_months} mos`;
                    }

                    const statusCell = document.getElementById('status-cell-' + currentModalId);
                    if(statusCell) {
                        if(bill.status === 'Fully Paid') {
                            statusCell.innerHTML = `<span class="badge badge-success" style="background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;">Fully Paid 🎉</span>`;
                        } else {
                            statusCell.innerHTML = `<span class="badge badge-success">Good Standing</span>`;
                        }
                    }
                    
                    viewDetail(currentModalId);
                }

                document.getElementById('directPaymentAmount').value = '';
                
                if (window.pushSystemNotification) {
                    window.pushSystemNotification("Payment Logged", `₱${amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} posted to ${bill.buyer}'s contract.`, "System");
                }
            } else {
                alert('Failed to save payment');
            }
        } catch (e) {
            console.error(e);
            alert('Error connecting to server');
        }
    }

    function viewDetail(id) {
        const bill = allBillsRaw.find(b => b.id === id);
        if (!bill) return;

        currentModalId = id;

        const balance = bill.total_dp - bill.paid_amount;

        document.getElementById('modalResident').textContent = bill.buyer;
        document.getElementById('modalLot').textContent = `Block ${bill.block} - Lot ${bill.lot}`;
        document.getElementById('modalAmount').textContent = `₱${balance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        
        if(bill.next_due !== 'N/A'){
            const dateObj = new Date(bill.next_due);
            document.getElementById('modalDate').textContent = dateObj.toLocaleDateString('en-US', {month: 'long', day: 'numeric', year: 'numeric'});
        } else {
            document.getElementById('modalDate').textContent = 'N/A';
        }
        
        const trendEl = document.getElementById('modalTrend');
        trendEl.textContent = bill.status;
        trendEl.className = 'trend-chip ' + (bill.status === 'Good Standing' || bill.status === 'Fully Paid' ? 'trend-early' : 'trend-late');
        if (bill.status === 'Fully Paid') {
            trendEl.style.background = '#dcfce7';
            trendEl.style.color = '#166534';
            trendEl.style.border = '1px solid #bbf7d0';
        }

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
        const tcp = parseFloat(inpTcp.value) || 0;
        const percent = parseFloat(inpPercent.value) || 0;
        const months = parseInt(inpMonths.value) || 1;

        const dpAmount = tcp * (percent / 100);
        const amortization = dpAmount / months;

        outDp.value = dpAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        outAmort.textContent = `₱${amortization.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    }

    inpTcp.addEventListener('input', calculateAmortization);
    inpPercent.addEventListener('input', calculateAmortization);
    inpMonths.addEventListener('input', calculateAmortization);

    async function submitContract() {
        const btn = document.querySelector('#newContractModal .btn-primary');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Saving...';
            btn.style.opacity = '0.7';
        }

        const firstName = document.getElementById('calcFirstName').value || 'New';
        const lastName = document.getElementById('calcLastName').value || 'Buyer';
        const contractDate = new Date().toISOString().split('T')[0];
        const blk = document.getElementById('calcBlk').value;
        const lot = document.getElementById('calcLot').value;
        
        const tcp = parseFloat(inpTcp.value) || 0;
        const percent = parseFloat(inpPercent.value) || 0;
        const months = parseInt(inpMonths.value) || 1;
        const dpAmount = tcp * (percent / 100);
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
                    first_name: firstName,
                    last_name: lastName,
                    contract_date: contractDate,
                    block: blk,
                    lot: lot,
                    dpAmount: dpAmount,
                    nextDate: nextDate.toISOString(),
                    monthly_amortization: amortization,
                    months_to_pay: months
                })
            });

            if (res.ok) {
                window.location.reload();
            } else {
                alert('Failed to save contract to database');
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Save & Initialize Contract';
                    btn.style.opacity = '1';
                }
            }
        } catch (e) {
            console.error(e);
            alert('Error connecting to server');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Save & Initialize Contract';
                btn.style.opacity = '1';
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
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
    function viewHistory() {
        const bill = allBillsRaw.find(b => b.id === currentModalId);
        if (!bill) return;

        let tbody = document.querySelector('#historyModal tbody');
        if (!tbody) {
            const historyHtml = `
            <div id="historyModal" class="bill-modal" style="display: flex; align-items: center; justify-content: center; z-index: 4000;">
                <div class="bill-modal-content" style="max-width: 600px; padding: 24px; border-radius: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h3 style="font-size: 18px; font-weight: 800;">Payment History</h3>
                        <button onclick="document.getElementById('historyModal').remove()" style="background:none;border:none;font-size:24px;cursor:pointer;">&times;</button>
                    </div>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                            <thead>
                                <tr style="border-bottom: 2px solid #e2e8f0; color: #64748b;">
                                    <th style="padding: 12px; text-align: left;">Date</th>
                                    <th style="padding: 12px; text-align: left;">TRN</th>
                                    <th style="padding: 12px; text-align: right;">Amount</th>
                                    <th style="padding: 12px; text-align: center;">Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>`;
            document.body.insertAdjacentHTML('beforeend', historyHtml);
            tbody = document.querySelector('#historyModal tbody');
        } else {
            document.getElementById('historyModal').style.display = 'flex';
        }

        if (bill.history && bill.history.length > 0) {
            tbody.innerHTML = bill.history.map(h => `
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 12px; font-weight: 600;">${h.date}</td>
                    <td style="padding: 12px; color: #64748b; font-family: monospace;">${h.trn}</td>
                    <td style="padding: 12px; text-align: right; font-weight: 700; color: #0f172a;">₱${h.amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td style="padding: 12px; text-align: center;"><span class="trend-chip trend-early" style="font-size:11px;">Paid</span></td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = `<tr><td colspan="4" style="padding: 24px; text-align: center; color: #64748b;">No payments recorded yet.</td></tr>`;
        }
    }

    window.addEventListener('DOMContentLoaded', () => {
        bindGisDropdowns('calcBlk', 'calcLot');
        
        if (new URLSearchParams(window.location.search).get('action') === 'add') {
            openContractModal();
        }
    });
</script>
@endsection
