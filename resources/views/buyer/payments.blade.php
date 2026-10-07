@extends('layouts.buyer')
@section('title', 'Monthly Payments')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">
<style>
    .status-badge {
        padding: 4px 10px;
        border-radius: 99px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-block;
    }
    .status-good-standing { background: #dcfce7; color: #059669; }
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-fully-paid { background: #dbeafe; color: #2563eb; }
    .status-cleared { background: #dbeafe; color: #2563eb; }
    .status-released { background: #e0e7ff; color: #4338ca; }
</style>

<div class="fade-in">
    <div style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 800; color: #0f172a; margin: 0;">Monthly Payments</h1>
            <p style="color: #64748b; margin-top: 8px;">Monitor and settle your downpayment and financing obligations.</p>
        </div>
        <div>
            <a href="{{ route('buyer.payments.dp.history') }}" class="btn btn-outline" style="border-radius: 12px; font-weight: 600; padding: 10px 20px; background: #fff;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                View History
            </a>
        </div>
    </div>

    @if($downpayments->isEmpty() && !$financing)
        <div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1;">
            <div style="font-size: 32px; margin-bottom: 12px;">📄</div>
            <h3 style="font-size: 18px; color: #0f172a; margin-bottom: 8px;">No Payment Records</h3>
            <p style="color: #64748b; font-size: 14px;">You currently do not have any active downpayment or financing schedules.</p>
        </div>
    @endif

    <div>
        @if($downpayments->isNotEmpty())
            <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 16px; color: #0f172a;">Downpayment Schedule</h3>
            
            @foreach($downpayments as $dp)
            @php
                $totalDp = $dp->balance + $dp->amount;
                $progressPercent = $totalDp > 0 ? ($dp->amount / $totalDp) * 100 : 0;
                $circumference = 2 * 3.14159 * 52;
                $cerStroke = ($progressPercent / 100) * $circumference;
            @endphp
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 24px;">
                <!-- Info Box -->
                <div class="analytic-card" style="padding: 28px; border-top: 4px solid #3b82f6; display: flex; flex-direction: column;">
                    <h4 style="font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0 0 16px 0; letter-spacing: 0.5px;">Equity Details</h4>
                    
                    <div style="margin-bottom: 24px;">
                        <div style="font-size: 13px; color: #64748b; margin-bottom: 4px;">Total Equity Required</div>
                        <div style="font-size: 36px; font-weight: 800; color: #0f172a; line-height: 1;">₱{{ number_format($totalDp, 2) }}</div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 16px; border-top: 1px solid #e2e8f0; padding-top: 20px; flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #64748b; font-size: 14px; font-weight: 500;">Monthly Amortization:</span>
                            <strong style="color: #0f172a; font-size: 15px;">₱{{ number_format($dp->monthly_amortization, 2) }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #64748b; font-size: 14px; font-weight: 500;">Next Due Date:</span>
                            <strong style="color: #dc2626; font-size: 15px;">{{ \Carbon\Carbon::parse($dp->due_date)->format('F d, Y h:i A') }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #64748b; font-size: 14px; font-weight: 500;">Penalty Rate:</span>
                            <strong style="color: #dc2626; font-size: 15px;">{{ number_format($dp->penalty_percentage ?? 0, 2) }}%</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #64748b; font-size: 14px; font-weight: 500;">Status:</span>
                            @if($dp->status !== 'Good Standing')
                                <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $dp->status)) }}">{{ $dp->status }}</span>
                            @else
                                <span style="color: #10b981; font-weight: 700; font-size: 14px;">{{ $dp->status }}</span>
                            @endif
                        </div>
                    </div>

                    @if($dp->balance > 0)
                    <div style="margin-top: 24px;">
                        <button id="payDpBtn_{{ $dp->id }}" class="btn btn-primary" style="width: 100%; padding: 14px; font-weight: 700; background: #0057B8; cursor: pointer; border: none; border-radius: 8px; color: #fff; text-transform: uppercase; font-size: 14px; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'" onclick="payDownpayment('{{ $dp->id }}')">
                            📱 Pay ₱{{ number_format($dp->monthly_amortization, 2) }} via GCash
                        </button>
                    </div>
                    @endif
                </div>

                <!-- Graph Box -->
                <div class="analytic-card" style="padding: 28px; display: flex; flex-direction: column;">
                    <h4 style="font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0 0 24px 0; letter-spacing: 0.5px;">Payment Progress</h4>
                    
                    <div class="cer-gauge-wrap" style="justify-content: flex-start; margin-bottom: 24px; padding: 0; border: none; background: transparent; flex: 1; align-items: center;">
                        <div class="cer-gauge-ring" style="width: 130px; height: 130px; flex-shrink: 0;">
                            <svg width="130" height="130" viewBox="0 0 120 120">
                                <circle class="gauge-bg" cx="60" cy="60" r="52"></circle>
                                <circle class="gauge-fill" cx="60" cy="60" r="52"
                                    stroke-dasharray="{{ round($cerStroke, 2) }} {{ round($circumference, 2) }}"
                                    stroke-dashoffset="0"></circle>
                            </svg>
                            <div class="cer-gauge-label" style="font-size: 22px;">
                                {{ number_format($progressPercent, 1) }}%
                                <span style="font-size: 11px;">Paid</span>
                            </div>
                        </div>
                        <div class="cer-meta" style="margin-left: 32px; display: flex; flex-direction: column; gap: 16px;">
                            <div>
                                <div style="font-size: 13px; color: #64748b; margin-bottom: 4px;">Amount Paid</div>
                                <div style="font-size: 20px; font-weight: 800; color: var(--bill-success); line-height: 1;">₱{{ number_format($dp->amount, 2) }}</div>
                            </div>
                            <div>
                                <div style="font-size: 13px; color: #64748b; margin-bottom: 4px;">Outstanding Balance</div>
                                <div style="font-size: 20px; font-weight: 800; color: var(--bill-danger); line-height: 1;">₱{{ number_format($dp->balance, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach


        @endif

        @if($financing)
            <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 16px; margin-top: 32px; color: #0f172a;">Financing Shortfall</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 24px;">
                <!-- Info Box -->
                <div class="analytic-card" style="padding: 28px; border-top: 4px solid #10b981; display: flex; flex-direction: column;">
                    <h4 style="font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0 0 16px 0; letter-spacing: 0.5px;">Contract Details</h4>
                    
                    <div style="margin-bottom: 24px;">
                        <div style="font-size: 13px; color: #64748b; margin-bottom: 4px;">Total Contract Price</div>
                        <div style="font-size: 36px; font-weight: 800; color: #0f172a; line-height: 1;">₱{{ number_format($financing->contract_price, 2) }}</div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 16px; border-top: 1px solid #e2e8f0; padding-top: 20px; flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #64748b; font-size: 14px; font-weight: 500;">Total Consideration:</span>
                            <strong style="color: #0f172a; font-size: 15px;">₱{{ number_format($financing->total_consideration, 2) }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #64748b; font-size: 14px; font-weight: 500;">Due for Financing:</span>
                            <strong style="color: #0f172a; font-size: 15px;">₱{{ number_format($financing->mbrdc_amt_due_for_financing, 2) }}</strong>
                        </div>
                        
                        @if($financing->status == 'Released' || $financing->status == 'Cleared')
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #64748b; font-size: 14px; font-weight: 500;">Loan Released:</span>
                            <strong style="color: #3b82f6; font-size: 15px;">₱{{ number_format($financing->loan_release, 2) }}</strong>
                        </div>
                        @endif
                        
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #64748b; font-size: 14px; font-weight: 500;">Status:</span>
                            <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $financing->status)) }}">{{ $financing->status }}</span>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #e2e8f0; padding-top: 16px;">
                            <span style="color: #64748b; font-size: 14px; font-weight: 700;">Remaining Balance:</span>
                            <strong style="color: {{ $financing->balance > 0 ? 'var(--bill-danger)' : '#10b981' }}; font-size: 18px;">₱{{ number_format($financing->balance, 2) }}</strong>
                        </div>
                    </div>

                    @if($financing->balance > 0)
                        <div style="margin-top: 24px;">
                            <button id="payFinBtn_{{ $financing->id }}" class="btn btn-primary" style="width: 100%; padding: 14px; font-weight: 700; background: #10b981; cursor: pointer; border: none; border-radius: 8px; color: #fff; text-transform: uppercase; font-size: 14px; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'" onclick="payFinancing('{{ $financing->id }}')">
                                📱 Pay Shortfall via GCash
                            </button>
                        </div>
                    @endif
                </div>
                
                <!-- Graph Box -->
                <div class="analytic-card" style="padding: 28px; display: flex; flex-direction: column;">
                    <h4 style="font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0 0 24px 0; letter-spacing: 0.5px;">Shortfall Progress</h4>

                    @if($financing->status == 'Released' || $financing->status == 'Cleared')
                        @php
                            $totalShortfall = $financing->receivables > 0 ? $financing->receivables : ($financing->amount_paid + $financing->balance);
                            $totalShortfall = $totalShortfall > 0 ? $totalShortfall : 1;
                            $finProgressPercent = ($financing->amount_paid / $totalShortfall) * 100;
                            $finCircumference = 2 * 3.14159 * 52;
                            $finStroke = ($finProgressPercent / 100) * $finCircumference;
                        @endphp
                        
                        <div class="cer-gauge-wrap" style="justify-content: flex-start; margin-bottom: 24px; padding: 0; border: none; background: transparent; flex: 1; align-items: center;">
                            <div class="cer-gauge-ring" style="width: 130px; height: 130px; flex-shrink: 0;">
                                <svg width="130" height="130" viewBox="0 0 120 120">
                                    <circle class="gauge-bg" cx="60" cy="60" r="52"></circle>
                                    <circle class="gauge-fill" cx="60" cy="60" r="52"
                                        stroke-dasharray="{{ round($finStroke, 2) }} {{ round($finCircumference, 2) }}"
                                        stroke-dashoffset="0"></circle>
                                </svg>
                                <div class="cer-gauge-label" style="font-size: 22px;">
                                    {{ number_format($finProgressPercent, 1) }}%
                                    <span style="font-size: 11px;">Paid</span>
                                </div>
                            </div>
                            <div class="cer-meta" style="margin-left: 32px; display: flex; flex-direction: column; gap: 16px;">
                                <div>
                                    <div style="font-size: 13px; color: #64748b; margin-bottom: 4px;">Amount Paid</div>
                                    <div style="font-size: 20px; font-weight: 800; color: var(--bill-success); line-height: 1;">₱{{ number_format($financing->amount_paid, 2) }}</div>
                                </div>
                                <div>
                                    <div style="font-size: 13px; color: #64748b; margin-bottom: 4px;">Remaining Shortfall</div>
                                    <div style="font-size: 20px; font-weight: 800; color: var(--bill-danger); line-height: 1;">₱{{ number_format($financing->balance, 2) }}</div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div style="width: 100%; padding: 32px 24px; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1; text-align: center; flex: 1; display: flex; flex-direction: column; justify-content: center;">
                            <div style="font-size: 32px; margin-bottom: 12px;">⏳</div>
                            <p style="font-size: 14px; color: #475569; margin: 0; font-weight: 500; line-height: 1.5;">Your loan is currently being processed.<br>You will see shortfall deduction details here once it is officially released.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Financing History -->
            @if($finHistories->isNotEmpty())
            <div class="analytic-card" style="margin-bottom: 24px; padding: 0;">
                <div style="padding: 16px 24px; border-bottom: 1px solid #e2e8f0;">
                    <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: #0f172a;">Shortfall Payment History</h4>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; text-align: left; border-collapse: collapse; font-size: 14px;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <th style="padding: 12px 24px; font-weight: 600; color: #64748b;">Date</th>
                                <th style="padding: 12px 24px; font-weight: 600; color: #64748b;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($finHistories as $hist)
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px 24px; color: #0f172a;">{{ \Carbon\Carbon::parse($hist->created_at)->format('M d, Y h:i A') }}</td>
                                <td style="padding: 12px 24px; font-weight: 600; color: #10b981;">₱{{ number_format($hist->amount_paid, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        @endif
    </div>
</div>

<script>
    async function payDownpayment(dpId) {
        const btn = document.getElementById('payDpBtn_' + dpId);
        btn.disabled = true;
        btn.innerHTML = 'Securely connecting...';

        try {
            const res = await fetch('/buyer/api/downpayment/pay', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ dp_id: dpId })
            });
            const data = await res.json();
            
            if (data.success && data.checkout_url) {
                window.location.href = data.checkout_url;
            } else {
                throw new Error(data.message || 'Failed to connect to PayMongo.');
            }
        } catch(e) {
            console.error(e);
            Swal.fire('⚠️ Unable to initiate secure payment. ' + e.message);
            btn.disabled = false;
            btn.innerHTML = 'Pay with GCash';
        }
    }

    async function payFinancing(finId) {
        const btn = document.getElementById('payFinBtn_' + finId);
        btn.disabled = true;
        btn.innerHTML = 'Securely connecting...';

        try {
            const res = await fetch('/buyer/api/financing/pay', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ fin_id: finId })
            });
            const data = await res.json();
            
            if (data.success && data.checkout_url) {
                window.location.href = data.checkout_url;
            } else {
                throw new Error(data.message || 'Failed to connect to PayMongo.');
            }
        } catch(e) {
            console.error(e);
            Swal.fire('⚠️ Unable to initiate secure payment. ' + e.message);
            btn.disabled = false;
            btn.innerHTML = 'Pay Shortfall with GCash';
        }
    }
</script>
@endsection
