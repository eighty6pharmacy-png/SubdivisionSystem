@extends('layouts.resident')

@section('title', 'My Water Bill')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">

<div class="fade-in">
    <div style="margin-bottom: 32px;">
        <h1 style="font-size: 28px; font-weight: 800; color: #0f172a; margin: 0;">Water Billing</h1>
        <p style="color: #64748b; margin-top: 8px;">Monitor your water consumption and settle outstanding balances.</p>
    </div>

    <!-- Current Bill Card -->
    <div class="analytic-card" style="padding: 28px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Current Water Amount</div>
            <div style="font-size: 32px; font-weight: 800; color: #0f172a; margin-top: 8px;">₱{{ number_format($waterBill['amount'], 2) }}</div>
            <div style="font-size: 13px; color: {{ $waterBill['status'] === 'paid' ? '#10b981' : ($waterBill['status'] === 'no-bill' ? '#64748b' : '#ef4444') }}; font-weight: 600; margin-top: 4px;">
                {{ $waterBill['status'] === 'paid' ? '✓ Paid on '.$waterBill['paid_date'] : ($waterBill['status'] === 'no-bill' ? 'ℹ No Pending Bill' : '⚠ Due: '.$waterBill['due']) }}
            </div>
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Consumption: <strong>{{ $waterBill['usage'] }}</strong></div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 12px; align-items: flex-end;">
            @if($waterBill['status'] === 'unpaid' || $waterBill['status'] === 'overdue')
                <button class="btn btn-primary" style="padding: 14px 28px; font-weight: 700; background: #0057B8; cursor: default;">📱 Pay via GCash</button>
            @elseif($waterBill['status'] === 'paid')
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

    <!-- Detailed Billing Breakdown -->
    <div class="analytic-card" style="padding: 24px; margin-bottom: 24px; border-radius: 24px; border: 1px solid var(--bill-border); background: #ffffff;">
        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 20px; text-transform: uppercase;">Current Statement Details</h3>
        
        @if(empty($waterBill['db_id']))
            <div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1;">
                <div style="font-size: 32px; margin-bottom: 12px;">🚰</div>
                <h3 style="font-size: 18px; color: #0f172a; margin-bottom: 8px;">No Billing History</h3>
                <p style="color: #64748b; font-size: 14px;">Your account has no water bills generated yet.</p>
            </div>
        @else
        
        @php
            $minM3 = $waterBill['min_m3'] ?? 10;
            $minRate = $waterBill['min_rate'] ?? 250;
            $excessRate = $waterBill['excess_rate'] ?? 25;
            $usageM3 = (float)($waterBill['usage_cbm'] ?? 0);
            
            $minCharge = $minRate;
            $excessCharge = $usageM3 > $minM3 ? ($usageM3 - $minM3) * $excessRate : 0;
            
            $prevBal = (float)($waterBill['previous_balance'] ?? 0);
            $arrearsPenalty = $prevBal > 0 ? $prevBal * 0.05 : 0;
        @endphp
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <span style="font-size: 12px; color: #64748b; font-weight: 600;">Previous Reading</span>
                <div style="font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ $waterBill['prev_reading'] }}</div>
            </div>
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <span style="font-size: 12px; color: #64748b; font-weight: 600;">Current Reading</span>
                <div style="font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ $waterBill['curr_reading'] }}</div>
            </div>
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <span style="font-size: 12px; color: #64748b; font-weight: 600;">Total Consumption</span>
                <div style="font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ $waterBill['usage'] }}</div>
            </div>
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <span style="font-size: 12px; color: #64748b; font-weight: 600;">Min Bill (First {{ $minM3 }}m³)</span>
                <div style="font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 4px;">₱{{ number_format($minCharge, 2) }}</div>
            </div>
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <span style="font-size: 12px; color: #64748b; font-weight: 600;">Excess Consumption</span>
                <div style="font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 4px;">₱{{ number_format($excessCharge, 2) }}</div>
            </div>
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <span style="font-size: 12px; color: #64748b; font-weight: 600;">Previous Unpaid Bill</span>
                <div style="font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 4px;">₱{{ number_format($prevBal, 2) }}</div>
            </div>
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <span style="font-size: 12px; color: #64748b; font-weight: 600;">Penalty (5%)</span>
                <div style="font-size: 20px; font-weight: 800; color: #f59e0b; margin-top: 4px;">₱{{ number_format($arrearsPenalty, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Consumption Chart -->
    <div class="analytic-card" style="padding: 24px; margin-bottom: 24px; border-radius: 24px; border: 1px solid var(--bill-border); background: #ffffff;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
            <div>
                <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">💧 Water Consumption Trend</h3>
                <p style="font-size: 13px; color: #64748b;">Track your monthly usage in m³.</p>
            </div>
            <select id="waterTimeRange" onchange="updateWaterChartRange(this.value)" style="padding: 6px 12px; font-size: 12px; border-radius: 12px; border: 1px solid #cbd5e1; background: #fff; color: #475569; font-weight: 600; cursor: pointer;">
                <option value="6">Last 6 Months</option>
                <option value="12">Last 12 Months</option>
            </select>
        </div>
        <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0;">
            <div style="position: relative; height: 250px; width: 100%;">
                <canvas id="waterChart"></canvas>
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
                <thead><tr><th>Reference</th><th>Period</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    @foreach($waterBill['payment_history'] as $h)
                    <tr>
                        <td style="font-family: monospace; font-size: 11px;">{{ $h['trn'] }}</td>
                        <td>{{ $h['month'] }}</td>
                        <td style="font-weight: 700;">₱{{ number_format($h['amount'], 2) }}</td>
                        <td><span class="badge {{ $h['status'] === 'Paid' ? 'badge-success' : 'badge-danger' }}">{{ $h['status'] }}</span></td>
                        <td>
                            @if($h['status'] === 'Paid')
                            <button onclick="downloadHistoryItem('{{ $h['trn'] }}', 'Water', '{{ $h['month'] }}', '{{ $h['amount'] }}', '{{ $h['date'] }}')" style="background: none; border: none; cursor: pointer; color: #3b82f6;" title="Download Receipt">
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

@include('partials.gcash-modal')

<script>
    function onGcashPaymentComplete(ctx) {
        location.reload();
    }

    let waterChart = null;
    let amountChart = null;
    let currentWaterRange = 6;
    
    const rawWaterHistory = {!! json_encode($waterBill['usage_history']) !!};
    const rawAmountHistory = {!! json_encode($waterBill['amount_history']) !!};

    const fullMonthLabels = rawWaterHistory.map(item => item.label);
    const fullWaterHistory = rawWaterHistory.map(item => item.value);
    const fullAmountHistory = rawAmountHistory.map(item => item.value);

    function updateWaterChartRange(monthsCount) {
        currentWaterRange = parseInt(monthsCount);
        renderWaterChart();
    }

    function renderWaterChart() {
        const slicedLabels = fullMonthLabels.slice(-currentWaterRange);
        const slicedData = fullWaterHistory.slice(-currentWaterRange);
        const slicedAmountData = fullAmountHistory.slice(-currentWaterRange);
        
        const ctx = document.getElementById('waterChart').getContext('2d');
        const ctxAmount = document.getElementById('amountChart').getContext('2d');
        
        if (waterChart) waterChart.destroy();
        if (amountChart) amountChart.destroy();
        
        waterChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: slicedLabels,
                datasets: [{
                    label: 'Water Consumption',
                    data: slicedData,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.15)',
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#3b82f6',
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
                            label: function(context) { return context.parsed.y + ' m³'; }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: { color: '#e2e8f0', drawBorder: false },
                        ticks: {
                            callback: function(value) { return value + ' m³'; },
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
                    backgroundColor: '#3b82f6',
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
        renderWaterChart();
    });
</script>

@endsection
