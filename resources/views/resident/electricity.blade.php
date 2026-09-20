@extends('layouts.resident')

@section('title', 'My Electricity Bill')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">

<div class="fade-in">
    @if($elecBill['provider_managed'] ?? false)
        <div style="background: #e0f2fe; color: #0369a1; padding: 16px 24px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; font-weight: 600;">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 12px;"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Your lot is directly connected to CASURECO. No new internal electricity bills will be generated.
        </div>
    @endif
    <div style="margin-bottom: 32px;">
        <h1 style="font-size: 28px; font-weight: 800; color: #0f172a; margin: 0;">Electricity Billing</h1>
        <p style="color: #64748b; margin-top: 8px;">Monitor your electricity consumption and settle outstanding balances.</p>
    </div>

    <!-- Current Bill Card -->
    <div class="analytic-card" style="padding: 28px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Current Electricity Amount</div>
            <div style="font-size: 32px; font-weight: 800; color: #0f172a; margin-top: 8px;">₱{{ number_format($elecBill['amount'], 2) }}</div>
            <div style="font-size: 13px; color: {{ $elecBill['status'] === 'paid' ? '#10b981' : ($elecBill['status'] === 'no-bill' ? '#64748b' : '#ef4444') }}; font-weight: 600; margin-top: 4px;">
                {{ $elecBill['status'] === 'paid' ? '✓ Paid on '.$elecBill['paid_date'] : ($elecBill['status'] === 'no-bill' ? 'ℹ No Pending Bill' : '⚠ Due: '.$elecBill['due']) }}
            </div>
            
            <div style="font-size: 12px; color: #64748b; margin-top: 8px;">Consumption: <strong>{{ $elecBill['usage'] }}</strong></div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 12px; align-items: flex-end;">
            @if($elecBill['status'] === 'unpaid' || $elecBill['status'] === 'overdue')
                <button onclick="payBill('{{ $elecBill['db_id'] }}')" id="payBtn" class="btn btn-primary" style="padding: 14px 28px; font-weight: 700; background: #0057B8; cursor: pointer;">📱 Pay via GCash</button>
            @elseif($elecBill['status'] === 'paid')
                <div style="background: #f0fdf4; padding: 12px 24px; border-radius: 12px; border: 1px solid #bbf7d0; text-align: center;">
                    <div style="font-size: 11px; color: #065f46; font-weight: 700;">BILL SETTLED</div>
                    <div style="font-size: 13px; font-weight: 700; color: #10b981; margin-top: 2px;">Thank you!</div>
                </div>
            @else
                <div style="background: #f8fafc; padding: 12px 24px; border-radius: 12px; border: 1px solid #cbd5e1; text-align: center;">
                    <div style="font-size: 11px; color: #475569; font-weight: 700;">NO BILL</div>
                    <div style="font-size: 13px; font-weight: 700; color: #64748b; margin-top: 2px;">Nothing due yet</div>
                </div>
            @endif
        </div>
    </div>
    </div>

    <!-- Detailed Billing Breakdown -->
    <div class="analytic-card" style="padding: 24px; margin-bottom: 24px; border-radius: 24px; border: 1px solid var(--bill-border); background: #ffffff;">
        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 20px; text-transform: uppercase;">Current Statement Details</h3>
        
        @if(empty($elecBill['db_id']))
            <div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1;">
                <div style="font-size: 32px; margin-bottom: 12px;">⚡</div>
                @if($elecBill['provider_managed'] ?? false)
                    <h3 style="font-size: 18px; color: #0f172a; margin-bottom: 8px;">CASURECO Connected</h3>
                    <p style="color: #64748b; font-size: 14px;">Your electricity is now managed by CASURECO. You do not have any past bills with the subdivision grid.</p>
                @else
                    <h3 style="font-size: 18px; color: #0f172a; margin-bottom: 8px;">No Billing History</h3>
                    <p style="color: #64748b; font-size: 14px;">Your account has no electricity bills generated yet.</p>
                @endif
            </div>
        @else
        <div style="display: flex; flex-wrap: nowrap; gap: 10px; overflow-x: auto; padding-bottom: 8px;">
            <div style="flex: 1; min-width: 115px; background: #f8fafc; padding: 12px 10px; border-radius: 10px; border: 1px solid #e2e8f0;">
                <div style="font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase;">Previous Reading</div>
                <div style="font-size: 16px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ $elecBill['prev_reading'] }}</div>
                <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">{{ $elecBill['prev_reading_date'] }}</div>
            </div>
            <div style="flex: 1; min-width: 115px; background: #f8fafc; padding: 12px 10px; border-radius: 10px; border: 1px solid #e2e8f0;">
                <div style="font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase;">Current Reading</div>
                <div style="font-size: 16px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ $elecBill['curr_reading'] }}</div>
                <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">{{ $elecBill['curr_reading_date'] }}</div>
            </div>
            <div style="flex: 1; min-width: 115px; background: #f8fafc; padding: 12px 10px; border-radius: 10px; border: 1px solid #e2e8f0;">
                <div style="font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase;">Base Rate</div>
                <div style="font-size: 16px; font-weight: 800; color: #0f172a; margin-top: 4px;">₱{{ number_format($elecBill['rate'], 2) }}</div>
            </div>
            <div style="flex: 1; min-width: 115px; background: #f8fafc; padding: 12px 10px; border-radius: 10px; border: 1px solid #e2e8f0;">
                <div style="font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase;">Consumption</div>
                <div style="font-size: 16px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ $elecBill['usage'] }}</div>
            </div>
            <div style="flex: 1; min-width: 130px; background: #f8fafc; padding: 12px 10px; border-radius: 10px; border: 1px solid #e2e8f0;">
                <div style="font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase;">Amt Before Due</div>
                @php $baseTotal = $elecBill['base_amount'] + $elecBill['previous_balance']; @endphp
                <div style="font-size: 16px; font-weight: 800; color: #0f172a; margin-top: 4px;">₱{{ number_format($baseTotal, 2) }}</div>
            </div>
            <div style="flex: 1; min-width: 130px; background: #fef2f2; padding: 12px 10px; border-radius: 10px; border: 1px solid #fecaca;">
                <div style="font-size: 10px; color: #991b1b; font-weight: 700; text-transform: uppercase;">Amt After Due</div>
                @php $afterAmt = $baseTotal + ($baseTotal * 0.05); @endphp
                <div style="font-size: 16px; font-weight: 800; color: #ef4444; margin-top: 4px;">₱{{ number_format($afterAmt, 2) }}</div>
            </div>
            <div style="flex: 1; min-width: 115px; background: #f8fafc; padding: 12px 10px; border-radius: 10px; border: 1px solid #e2e8f0;">
                <div style="font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase;">Prev Balance</div>
                <div style="font-size: 16px; font-weight: 800; color: #0f172a; margin-top: 4px;">₱{{ number_format($elecBill['previous_balance'], 2) }}</div>
            </div>
            <div style="flex: 1; min-width: 115px; background: #f8fafc; padding: 12px 10px; border-radius: 10px; border: 1px solid #e2e8f0;">
                <div style="font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase;">Penalty (5%)</div>
                @php $penalty = ($elecBill['base_amount'] + $elecBill['previous_balance']) * 0.05; @endphp
                <div style="font-size: 16px; font-weight: 800; color: #f59e0b; margin-top: 4px;">₱{{ number_format($penalty, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Consumption Chart -->
    <div class="analytic-card" style="padding: 24px; margin-bottom: 24px; border-radius: 24px; border: 1px solid var(--bill-border); background: #ffffff;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
            <div>
                <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">⚡ Electricity Consumption Trend</h3>
                <p style="font-size: 13px; color: #64748b;">Track your monthly usage in kWh.</p>
            </div>
            <select id="elecTimeRange" onchange="updateElecChartRange(this.value)" style="padding: 6px 12px; font-size: 12px; border-radius: 12px; border: 1px solid #cbd5e1; background: #fff; color: #475569; font-weight: 600; cursor: pointer;">
                <option value="6">Last 6 Months</option>
                <option value="12">Last 12 Months</option>
            </select>
        </div>
        <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0;">
            <div style="position: relative; height: 250px; width: 100%;">
                <canvas id="elecChart"></canvas>
            </div>
        </div>
        <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; margin-top: 16px;">
            <h4 style="font-size: 14px; font-weight: 700; color: #475569; margin-bottom: 12px; margin-top: 0;">Billing Amount (₱)</h4>
            <div style="position: relative; height: 250px; width: 100%;">
                <canvas id="amountChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Payment History -->
    <div class="analytic-card" style="padding: 0; overflow: hidden;">
        <div style="padding: 20px 24px; border-bottom: 1px solid var(--bill-border);">
            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Payment Ledger</h3>
        </div>
        <div class="bill-table-container">
            <table class="bill-table">
                <thead><tr><th>Reference</th><th>Method</th><th>Date & Time</th><th>Period</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    @foreach($elecBill['payment_history'] as $h)
                    <tr>
                        <td style="font-family: monospace; font-size: 11px;">{{ $h['trn'] }}</td>
                        <td style="font-size: 13px; color: #475569;">{{ $h['method'] ?? 'N/A' }}</td>
                        <td style="font-size: 13px; color: #475569;">{{ $h['date'] }}</td>
                        <td>{{ $h['month'] }}</td>
                        <td style="font-weight: 700;">₱{{ number_format($h['amount'], 2) }}</td>
                        <td><span class="badge {{ $h['status'] === 'Paid' ? 'badge-success' : 'badge-danger' }}">{{ $h['status'] }}</span></td>
                        <td>
                            @if($h['status'] === 'Paid')
                            <button onclick="downloadHistoryItem('{{ $h['trn'] }}', 'Electricity', '{{ $h['month'] }}', '{{ $h['amount'] }}', '{{ $h['date'] }}')" style="background: none; border: none; cursor: pointer; color: #3b82f6;" title="Download Receipt">
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<script>
    async function payBill(billId) {
        const btn = document.getElementById('payBtn');
        btn.disabled = true;
        btn.innerHTML = 'Securely connecting...';

        try {
            const res = await fetch('/resident/api/billing/pay', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ bill_id: billId })
            });
            const data = await res.json();
            
            if (data.success && data.checkout_url) {
                window.location.href = data.checkout_url;
            } else {
                throw new Error(data.message || 'Failed to connect to PayMongo.');
            }
        } catch(e) {
            console.error(e);
            alert('⚠️ Unable to initiate secure payment. ' + e.message);
            btn.disabled = false;
            btn.innerHTML = '📱 Pay via GCash';
        }
    }

    let elecChart = null;
    let amountChart = null;
    let currentElecRange = 6;
    
    const rawElecHistory = {!! json_encode($elecBill['usage_history']) !!};
    const rawAmountHistory = {!! json_encode($elecBill['amount_history']) !!};

    const fullMonthLabels = rawElecHistory.map(item => item.label);
    const fullElecHistory = rawElecHistory.map(item => item.value);
    const fullAmountHistory = rawAmountHistory.map(item => item.value);

    function updateElecChartRange(monthsCount) {
        currentElecRange = parseInt(monthsCount);
        renderElecChart();
    }

    function renderElecChart() {
        const slicedLabels = fullMonthLabels.slice(-currentElecRange);
        const slicedData = fullElecHistory.slice(-currentElecRange);
        const slicedAmountData = fullAmountHistory.slice(-currentElecRange);
        
        const ctx = document.getElementById('elecChart').getContext('2d');
        const ctxAmount = document.getElementById('amountChart').getContext('2d');
        
        if (elecChart) elecChart.destroy();
        if (amountChart) amountChart.destroy();
        
        elecChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: slicedLabels,
                datasets: [{
                    label: 'Electricity Consumption',
                    data: slicedData,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.15)',
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#10b981',
                    pointBorderWidth: 3,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) { return context.parsed.y + ' kWh'; }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: { color: '#e2e8f0', drawBorder: false },
                        ticks: {
                            callback: function(value) { return value + ' kWh'; },
                            color: '#64748b', font: { size: 11 }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b', font: { size: 11 } }
                    }
                },
                interaction: { intersect: false, mode: 'index' }
            }
        });

        amountChart = new Chart(ctxAmount, {
            type: 'bar',
            data: {
                labels: slicedLabels,
                datasets: [{
                    label: 'Billing Amount',
                    data: slicedAmountData,
                    backgroundColor: '#10b981',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) { return '₱ ' + context.parsed.y; }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#e2e8f0', drawBorder: false },
                        ticks: {
                            callback: function(value) { return '₱ ' + value; },
                            color: '#64748b', font: { size: 11 }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b', font: { size: 11 } }
                    }
                },
                interaction: { intersect: false, mode: 'index' }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderElecChart();
    });
</script>

@endsection
