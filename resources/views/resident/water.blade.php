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
            <div style="font-size: 13px; color: {{ $waterBill['status'] === 'paid' ? '#10b981' : '#ef4444' }}; font-weight: 600; margin-top: 4px;">
                {{ $waterBill['status'] === 'paid' ? '✓ Paid on '.$waterBill['paid_date'] : '⚠ Due: '.$waterBill['due'] }}
            </div>
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Consumption: <strong>{{ $waterBill['usage'] }}</strong></div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 12px; align-items: flex-end;">
            @if($waterBill['status'] !== 'paid')
                <button class="btn btn-primary" style="padding: 14px 28px; font-weight: 700; background: #0057B8; cursor: default;">📱 Pay via GCash</button>
            @else
                <div style="background: #f0fdf4; padding: 12px 24px; border-radius: 12px; border: 1px solid #bbf7d0; text-align: center;">
                    <div style="font-size: 11px; color: #065f46; font-weight: 700;">BILL SETTLED</div>
                    <div style="font-size: 13px; font-weight: 700; color: #10b981; margin-top: 2px;">Thank you!</div>
                </div>
            @endif
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
</div>

@include('partials.gcash-modal')

<script>
    function onGcashPaymentComplete(ctx) {
        location.reload();
    }
</script>

@endsection
