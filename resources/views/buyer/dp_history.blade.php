@extends('layouts.buyer')
@section('title', 'Downpayment History')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">

<div class="fade-in">
    <div style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 800; color: #0f172a; margin: 0;"><i class="fas fa-history" style="margin-right: 12px; color: #3b82f6;"></i>Downpayment History</h1>
            <p style="color: #64748b; margin-top: 8px;">View and download your past downpayment records and receipts.</p>
        </div>
        <div>
            <a href="{{ route('buyer.payments') }}" class="btn btn-outline" style="border-radius: 12px; font-weight: 600; padding: 10px 20px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Current Payments
            </a>
        </div>
    </div>

    @if($dpHistories->isEmpty())
        <div style="text-align: center; padding: 60px 20px; background: #ffffff; border-radius: 24px; border: 1px dashed #cbd5e1; margin-top: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <div style="font-size: 48px; margin-bottom: 16px;">📜</div>
            <h3 style="font-size: 20px; color: #0f172a; margin-bottom: 8px;">No Payment History</h3>
            <p style="color: #64748b; font-size: 14px;">You don't have any past downpayments recorded yet.</p>
        </div>
    @else
        <div class="analytic-card" style="padding: 0; overflow: hidden; border-radius: 24px; border: 1px solid var(--bill-border); background: #ffffff;">
            <div class="table-responsive" style="overflow-x: auto;">
                <table style="width: 100%; text-align: left; border-collapse: collapse; font-size: 14px;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 16px 24px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase;">Payment Date</th>
                            <th style="padding: 16px 24px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase;">Due Date</th>
                            <th style="padding: 16px 24px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase;">Transaction Reference</th>
                            <th style="padding: 16px 24px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase;">Payment Method</th>
                            <th style="padding: 16px 24px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase;">Amount Paid</th>
                            <th style="padding: 16px 24px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dpHistories as $hist)
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 16px 24px;">
                                @php
                                    $pDate = isset($hist->payment_date) && $hist->payment_date ? \Carbon\Carbon::parse($hist->payment_date) : \Carbon\Carbon::parse($hist->created_at);
                                @endphp
                                <div style="font-weight: 800; color: #0f172a;">{{ $pDate->format('F d, Y') }}</div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 4px;">{{ $pDate->format('h:i A') }}</div>
                            </td>
                            <td style="padding: 16px 24px;">
                                <div style="font-weight: 800; color: #3b82f6;">{{ $hist->due_date_str ?? 'N/A' }}</div>
                            </td>
                            <td style="padding: 16px 24px;">
                                <div style="font-weight: 700; color: #0f172a; font-family: monospace;">{{ $hist->trn ?? 'Manual' }}</div>
                            </td>
                            <td style="padding: 16px 24px;">
                                <div style="font-weight: 600; color: #0f172a;">{{ $hist->payment_method ?? 'Office Payment' }}</div>
                            </td>
                            <td style="padding: 16px 24px;">
                                <div style="font-weight: 800; color: #10b981;">₱{{ number_format($hist->amount, 2) }}</div>
                            </td>
                            <td style="padding: 16px 24px; text-align: right;">
                                <a href="{{ route('buyer.payments.dp.receipt', $hist->id) }}" target="_blank" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-color: #3b82f6; color: #3b82f6; display: inline-flex; align-items: center; justify-content: center;">
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
