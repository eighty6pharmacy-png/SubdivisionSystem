@extends('layouts.resident')

@section('title', ucfirst($type) . ' Billing History')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">

<div class="fade-in">
    <div style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 800; color: #0f172a; margin: 0;"><i class="fas fa-history" style="margin-right: 12px; color: var(--res-primary);"></i>{{ ucfirst($type) }} Billing History</h1>
            <p style="color: #64748b; margin-top: 8px;">View and download your past statements and payment history.</p>
        </div>
        <div>
            <a href="/resident/{{ $type }}" class="btn btn-outline" style="border-radius: 12px; font-weight: 600; padding: 10px 20px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Current Bill
            </a>
        </div>
    </div>

    @if($bills->isEmpty())
        <div style="text-align: center; padding: 60px 20px; background: #ffffff; border-radius: 24px; border: 1px dashed #cbd5e1; margin-top: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <div style="font-size: 48px; margin-bottom: 16px;">📜</div>
            <h3 style="font-size: 20px; color: #0f172a; margin-bottom: 8px;">No Billing History</h3>
            <p style="color: #64748b; font-size: 14px;">You don't have any past {{ $type }} bills recorded yet.</p>
        </div>
    @else
        <div class="analytic-card" style="padding: 0; overflow: hidden; border-radius: 24px; border: 1px solid var(--bill-border); background: #ffffff;">
            <div class="table-responsive" style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 16px 24px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase;">Billing Period</th>
                            <th style="padding: 16px 24px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase;">Previous Reading</th>
                            <th style="padding: 16px 24px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase;">Current Reading</th>
                            <th style="padding: 16px 24px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase;">Consumption</th>
                            <th style="padding: 16px 24px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase;">Total Amount</th>
                            <th style="padding: 16px 24px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase;">Status</th>
                            <th style="padding: 16px 24px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bills as $bill)
                            @php
                                $prevDate = $bill->previous_reading_date ? \Carbon\Carbon::parse($bill->previous_reading_date)->format('M d, Y') : \Carbon\Carbon::parse($bill->created_at)->subMonth()->format('M 01, Y');
                                $currDate = $bill->current_reading_date ? \Carbon\Carbon::parse($bill->current_reading_date)->format('M d, Y') : \Carbon\Carbon::parse($bill->created_at)->format('M d, Y');
                                $unit = $type === 'electricity' ? 'kWh' : 'm³';
                                
                                $totalBeforeDue = $bill->amount;
                                $penalty = 0;
                                // Simple penalty calc for UI display
                                if ($bill->status !== 'paid' && $bill->due_date && \Carbon\Carbon::parse($bill->due_date)->isPast()) {
                                    $penalty = ($totalBeforeDue + $bill->previous_balance) * 0.05;
                                }
                                $totalAfterDue = $totalBeforeDue + $bill->previous_balance + $penalty;
                            @endphp
                            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                <td style="padding: 16px 24px;">
                                    <div style="font-weight: 800; color: #0f172a;">{{ \Carbon\Carbon::parse($bill->created_at)->format('F Y') }}</div>
                                    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Due: {{ $bill->due_date ? \Carbon\Carbon::parse($bill->due_date)->format('M d, Y') : 'N/A' }}</div>
                                </td>
                                <td style="padding: 16px 24px;">
                                    <div style="font-weight: 700; color: #0f172a;">{{ $bill->previous_reading }}</div>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 4px;">{{ $prevDate }}</div>
                                </td>
                                <td style="padding: 16px 24px;">
                                    <div style="font-weight: 700; color: #0f172a;">{{ $bill->current_reading ?? 'N/A' }}</div>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 4px;">{{ $currDate }}</div>
                                </td>
                                <td style="padding: 16px 24px;">
                                    <span style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-weight: 700; font-size: 13px; color: #475569;">{{ $bill->usage_value }} {{ $unit }}</span>
                                </td>
                                <td style="padding: 16px 24px;">
                                    <div style="font-weight: 800; color: var(--res-primary);">₱{{ number_format($totalAfterDue, 2) }}</div>
                                    @if($bill->previous_balance > 0 || $penalty > 0)
                                        <div style="font-size: 11px; color: #ef4444; margin-top: 4px;">+ arrears/penalty</div>
                                    @endif
                                </td>
                                <td style="padding: 16px 24px;">
                                    @if($bill->status == 'paid')
                                        <span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 99px; font-size: 12px; font-weight: 700; border: 1px solid #bbf7d0;">Paid</span>
                                    @else
                                        <span style="background: #fef3c7; color: #b45309; padding: 4px 10px; border-radius: 99px; font-size: 12px; font-weight: 700; border: 1px solid #fde68a;">Unpaid</span>
                                    @endif
                                </td>
                                <td style="padding: 16px 24px; text-align: right;">
                                    <a href="/resident/bills/history/{{ $type }}/pdf/{{ $bill->id }}" target="_blank" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-color: var(--res-primary); color: var(--res-primary); display: inline-flex; align-items: center; justify-content: center;">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 6px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> Receipt
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
