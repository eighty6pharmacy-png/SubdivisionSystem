@extends('layouts.buyer')
@section('title', 'Financial Dashboard')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}?v={{ filemtime(public_path('css/admin-finance.css')) }}">
<style>
    .status-badge {
        padding: 4px 10px;
        border-radius: 99px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-block;
    }
    .status-good { background: #dcfce7; color: #059669; }
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-fully-paid { background: #dbeafe; color: #2563eb; }
    
    .buyer-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .buyer-charts-grid {
        display: grid;
        grid-template-columns: 1fr 1.6fr;
        gap: 20px;
        margin-bottom: 24px;
    }
    @media (max-width: 960px) {
        .buyer-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .buyer-charts-grid {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 560px) {
        .buyer-kpi-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="bill-container fade-in">
    <!-- Property Info Header -->
    <div class="bill-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div class="bill-title">
            <h1>Welcome, {{ $buyer->first_name }}</h1>
            <p>Block {{ $selectedLot?->block ?? $buyer->block_no ?? '—' }}, Lot {{ $selectedLot?->lot_number ?? $buyer->lot_no ?? '—' }} • {{ $financing?->financing_method ?? 'Downpayment & Financing' }}</p>
        </div>
        @if(isset($lots) && $lots->isNotEmpty())
        <div style="display: flex; align-items: center; gap: 10px; background: #fff; padding: 8px 14px; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <label for="buyerLotSelect" style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; margin: 0;">Property:</label>
            <select id="buyerLotSelect" onchange="window.location.href = '{{ url('/buyer/dashboard') }}?lot_id=' + encodeURIComponent(this.value)" style="border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; font-size: 13px; font-weight: 700; color: #0f172a; background: #f8fafc; cursor: pointer; outline: none;">
                @foreach($lots as $lotOption)
                    <option value="{{ $lotOption->id }}" {{ ($selectedLot && $selectedLot->id === $lotOption->id) ? 'selected' : '' }}>
                        Block {{ $lotOption->block }}, Lot {{ $lotOption->lot_number }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif
    </div>

    @php
        $dpPct = $dpTotal > 0 ? min(100, round(($dpPaid / $dpTotal) * 100, 1)) : 0;
        $finPaid = $financing ? (float) ($financing->amount_paid ?? 0) : 0;
        $finBalance = $financing ? (float) ($financing->balance ?? 0) : 0;
    @endphp

    <!-- Summary KPI Cards -->
    <div class="buyer-kpi-grid">
        <div class="analytic-card" style="background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; border-left: 4px solid #3b82f6; padding: 18px 20px;">
            <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em;">Total Downpayment</div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 6px;">₱{{ number_format($dpTotal, 2) }}</div>
        </div>
        <div class="analytic-card" style="background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; border-left: 4px solid #10b981; padding: 18px 20px;">
            <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em;">Total Paid (DP)</div>
            <div style="font-size: 22px; font-weight: 800; color: #059669; margin-top: 6px;">₱{{ number_format($dpPaid, 2) }}</div>
            <div style="font-size: 11px; color: #64748b; margin-top: 4px;">{{ $dpPct }}% completed</div>
        </div>
        <div class="analytic-card" style="background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; border-left: 4px solid #f59e0b; padding: 18px 20px;">
            <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em;">Remaining Balance</div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 6px;">₱{{ number_format($dpBalance, 2) }}</div>
            @if($financing)
                <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Loan Bal: ₱{{ number_format($finBalance, 2) }}</div>
            @endif
        </div>
        <div class="analytic-card" style="background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; border-left: 4px solid #6366f1; padding: 18px 20px;">
            <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em;">Next Due Date</div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 6px;">{{ $nextDueDate }}</div>
        </div>
    </div>

    <!-- Financial Graphs -->
    <div class="buyer-charts-grid">
        <div class="analytic-card" style="background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 22px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Downpayment Progress</h3>
                <span class="status-badge {{ $dpPct >= 100 ? 'status-fully-paid' : 'status-good' }}">{{ $dpPct }}%</span>
            </div>
            <p style="font-size: 12px; color: #64748b; margin: 0 0 14px 0;">Paid vs. remaining downpayment balance</p>
            <div style="height: 220px; position: relative;">
                <canvas id="buyerDpDoughnutChart"></canvas>
            </div>
        </div>

        <div class="analytic-card" style="background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 22px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Property Cost &amp; Equity Breakdown</h3>
                <span style="font-size: 12px; font-weight: 700; color: #2563eb; background: #eff6ff; padding: 4px 10px; border-radius: 99px;">
                    TCP: ₱{{ number_format($tcp, 2) }}
                </span>
            </div>
            <p style="font-size: 12px; color: #64748b; margin: 0 0 14px 0;">Reservation, downpayment, and financing balances</p>
            <div style="height: 220px; position: relative;">
                <canvas id="buyerCostBreakdownChart"></canvas>
            </div>
        </div>
    </div>

    @include('buyer.partials.payment-calendar')

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const dpCtx = document.getElementById('buyerDpDoughnutChart')?.getContext('2d');
    if (dpCtx) {
        const dpPaid = @json($dpPaid);
        const dpBalance = @json($dpBalance);
        const hasValues = (dpPaid + dpBalance) > 0;
        new Chart(dpCtx, {
            type: 'doughnut',
            data: {
                labels: hasValues ? ['Paid', 'Remaining Balance'] : ['No Downpayment Yet'],
                datasets: [{
                    data: hasValues ? [dpPaid, dpBalance] : [1],
                    backgroundColor: hasValues ? ['#10b981', '#e2e8f0'] : ['#e2e8f0'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true, pointStyle: 'circle', padding: 14, font: { size: 12, weight: '600' }, color: '#475569' }
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => hasValues ? `${ctx.label}: ₱${Number(ctx.raw).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : 'No downpayment recorded'
                        }
                    }
                }
            }
        });
    }

    const costCtx = document.getElementById('buyerCostBreakdownChart')?.getContext('2d');
    if (costCtx) {
        new Chart(costCtx, {
            type: 'bar',
            data: {
                labels: ['Reservation Paid', 'Downpayment Paid', 'DP Remaining', 'Loan Paid', 'Loan Balance'],
                datasets: [{
                    label: 'Amount (₱)',
                    data: [
                        @json($reservationPaid),
                        @json($dpPaid),
                        @json($dpBalance),
                        @json($finPaid),
                        @json($finBalance)
                    ],
                    backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#0ea5e9', '#94a3b8'],
                    borderRadius: 8,
                    maxBarThickness: 28
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `₱${Number(ctx.raw).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: (val) => '₱' + Number(val).toLocaleString(),
                            font: { size: 11 },
                            color: '#64748b'
                        },
                        grid: { color: '#f1f5f9' },
                        border: { display: false }
                    },
                    y: {
                        ticks: { font: { size: 11, weight: '600' }, color: '#334155' },
                        grid: { display: false },
                        border: { display: false }
                    }
                }
            }
        });
    }
});
</script>
@endsection
