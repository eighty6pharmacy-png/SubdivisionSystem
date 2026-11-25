@extends('layouts.buyer')
@section('title', 'Monthly Payments')

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
            <p style="color: #64748b; margin-top: 8px;">
                @if(isset($selectedLot))
                    Block {{ $selectedLot->block }}, Lot {{ $selectedLot->lot_number }} •
                @endif
                Monitor and settle your downpayment and financing obligations.
            </p>
        </div>
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            @if(isset($lots) && $lots->isNotEmpty())
            <div style="display: flex; align-items: center; gap: 10px; background: #fff; padding: 8px 14px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <label for="buyerPaymentsLotSelect" style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; margin: 0;">Property:</label>
                <select id="buyerPaymentsLotSelect" onchange="window.location.href = '{{ url('/buyer/payments') }}?lot_id=' + encodeURIComponent(this.value)" style="border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; font-size: 13px; font-weight: 700; color: #0f172a; background: #f8fafc; cursor: pointer; outline: none;">
                    @foreach($lots as $lotOption)
                        <option value="{{ $lotOption->id }}" {{ ($selectedLot && $selectedLot->id === $lotOption->id) ? 'selected' : '' }}>
                            Block {{ $lotOption->block }}, Lot {{ $lotOption->lot_number }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            <a href="{{ route('buyer.payments.dp.history', $selectedLot ? ['lot_id' => $selectedLot->id] : []) }}" class="btn btn-outline" style="border-radius: 12px; font-weight: 600; padding: 10px 20px; background: #fff;">
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
            @php
                $aggTotalDp = $downpayments->sum(fn($d) => (float) $d->balance + (float) $d->amount);
                $aggPaidDp = $downpayments->sum(fn($d) => (float) $d->amount);
                $aggBalDp = $downpayments->sum(fn($d) => (float) $d->balance);
                $nextActiveDp = $downpayments->first(fn($d) => (float) $d->balance > 0);
                $nextDueLabel = $nextActiveDp && $nextActiveDp->due_date
                    ? \Carbon\Carbon::parse($nextActiveDp->due_date)->format('M d, Y')
                    : 'All Settled';
                $nextAmtLabel = $nextActiveDp
                    ? min((float) $nextActiveDp->monthly_amortization, (float) $nextActiveDp->balance) + $nextActiveDp->latePenaltyAmount(min((float) $nextActiveDp->monthly_amortization, (float) $nextActiveDp->balance))
                    : 0;
            @endphp

            <!-- Top Monthly Installment Summary Strip -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 24px;">
                <div style="background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid #4f46e5; border-radius: 14px; padding: 16px 18px; box-shadow: 0 2px 6px rgba(15,23,42,0.03);">
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em;">Total Equity</div>
                    <div style="font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 4px;">₱{{ number_format($aggTotalDp, 2) }}</div>
                </div>
                <div style="background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid #10b981; border-radius: 14px; padding: 16px 18px; box-shadow: 0 2px 6px rgba(15,23,42,0.03);">
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em;">Total Paid</div>
                    <div style="font-size: 22px; font-weight: 800; color: #059669; margin-top: 4px;">₱{{ number_format($aggPaidDp, 2) }}</div>
                </div>
                <div style="background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid #ef4444; border-radius: 14px; padding: 16px 18px; box-shadow: 0 2px 6px rgba(15,23,42,0.03);">
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em;">Remaining Balance</div>
                    <div style="font-size: 22px; font-weight: 800; color: {{ $aggBalDp > 0 ? '#dc2626' : '#059669' }}; margin-top: 4px;">₱{{ number_format($aggBalDp, 2) }}</div>
                </div>
                <div style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); border-radius: 14px; padding: 16px 18px; color: #fff; box-shadow: 0 6px 14px -4px rgba(79,70,229,0.35);">
                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; opacity: 0.85;">Next Installment Due</div>
                    <div style="font-size: 22px; font-weight: 800; margin-top: 4px;">{{ $nextAmtLabel > 0 ? '₱' . number_format($nextAmtLabel, 2) : 'Paid Off' }}</div>
                    <div style="font-size: 11px; font-weight: 600; opacity: 0.9; margin-top: 2px;">{{ $nextDueLabel }}</div>
                </div>
            </div>

            <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 16px; color: #0f172a;">Monthly Installment Schedule</h3>

            @foreach($downpayments as $dp)
            @php
                $totalDp = $dp->balance + $dp->amount;
                $progressPercent = $totalDp > 0 ? min(100, max(0, ($dp->amount / $totalDp) * 100)) : 0;
                $circumference = 2 * 3.14159 * 52;
                $cerStroke = ($progressPercent / 100) * $circumference;
                $principalDue = min((float) $dp->monthly_amortization, (float) $dp->balance);
                $penaltyDue = $dp->latePenaltyAmount($principalDue);
                $payNow = $principalDue + $penaltyDue;
                $monthsLeft = ($dp->monthly_amortization > 0 && $dp->balance > 0)
                    ? (int) ceil($dp->balance / $dp->monthly_amortization)
                    : 0;
            @endphp

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 20px;">
                <!-- Info Box -->
                <div class="analytic-card" style="padding: 24px; border-top: 4px solid #4f46e5; display: flex; flex-direction: column;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <h4 style="font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase; margin: 0; letter-spacing: 0.05em;">Equity & Installment Details</h4>
                        @if($dp->status !== 'Good Standing')
                            <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $dp->status)) }}">{{ $dp->status }}</span>
                        @else
                            <span class="status-badge status-good-standing">{{ $dp->status }}</span>
                        @endif
                    </div>

                    <div style="margin-bottom: 20px;">
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Total Equity Required</div>
                        <div style="font-size: 32px; font-weight: 800; color: #0f172a; line-height: 1.1;">₱{{ number_format($totalDp, 2) }}</div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 12px; border-top: 1px solid #e2e8f0; padding-top: 16px; flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #64748b; font-size: 13px; font-weight: 500;">Monthly Amortization</span>
                            <strong style="color: #0f172a; font-size: 14px;">₱{{ number_format($dp->monthly_amortization, 2) }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #64748b; font-size: 13px; font-weight: 500;">Next Due Date</span>
                            <strong style="color: #dc2626; font-size: 14px;">{{ $dp->due_date ? \Carbon\Carbon::parse($dp->due_date)->format('F d, Y') : 'N/A' }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #64748b; font-size: 13px; font-weight: 500;">Late Penalty ({{ number_format($dp->penalty_percentage ?? 0, 2) }}%)</span>
                            <strong style="color: {{ $penaltyDue > 0 ? '#dc2626' : '#64748b' }}; font-size: 14px;">₱{{ number_format($penaltyDue, 2) }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #64748b; font-size: 13px; font-weight: 500;">Est. Months Remaining</span>
                            <strong style="color: #0f172a; font-size: 14px;">{{ $monthsLeft }} {{ $monthsLeft === 1 ? 'month' : 'months' }}</strong>
                        </div>
                    </div>

                    @if($dp->balance > 0)
                    <div style="margin-top: 20px;">
                        <button id="payDpBtn_{{ $dp->id }}" class="btn btn-primary" style="width: 100%; padding: 14px; font-weight: 700; background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%); cursor: pointer; border: none; border-radius: 12px; color: #fff; font-size: 14px; box-shadow: 0 4px 12px rgba(37,99,235,0.25); transition: transform 0.15s, opacity 0.15s;" onmouseover="this.style.opacity='0.94'" onmouseout="this.style.opacity='1'" onclick="payDownpayment('{{ $dp->id }}')">
                            Pay ₱{{ number_format($payNow, 2) }} Online
                        </button>
                    </div>
                    @endif
                </div>

                <!-- Graph Box -->
                <div class="analytic-card" style="padding: 24px; display: flex; flex-direction: column; justify-content: space-between;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <h4 style="font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase; margin: 0; letter-spacing: 0.05em;">Payment Progress</h4>
                        <span class="card-badge" style="background: #eef2ff; color: #4338ca;">{{ number_format($progressPercent, 1) }}% Complete</span>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: center; gap: 28px; flex-wrap: wrap; padding: 10px 0;">
                        <div class="cer-gauge-ring" style="width: 150px; height: 150px; flex-shrink: 0;">
                            <svg viewBox="0 0 120 120">
                                <circle class="gauge-bg" cx="60" cy="60" r="52"></circle>
                                <circle class="gauge-fill" cx="60" cy="60" r="52"
                                    stroke-dasharray="{{ round($cerStroke, 2) }} {{ round($circumference, 2) }}"
                                    stroke-dashoffset="0"
                                    style="stroke: #10b981; {{ $progressPercent <= 0 ? 'stroke-opacity: 0;' : '' }}"></circle>
                            </svg>
                            <div class="cer-gauge-label" style="font-size: 24px;">
                                {{ number_format($progressPercent, 1) }}%
                                <span style="font-size: 10px;">Paid</span>
                            </div>
                        </div>

                        <div style="flex: 1; min-width: 170px; display: flex; flex-direction: column; gap: 10px;">
                            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 12px 14px;">
                                <div style="font-size: 11px; font-weight: 700; color: #166534; text-transform: uppercase;">Amount Paid</div>
                                <div style="font-size: 19px; font-weight: 800; color: #059669; margin-top: 2px;">₱{{ number_format($dp->amount, 2) }}</div>
                            </div>
                            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 12px 14px;">
                                <div style="font-size: 11px; font-weight: 700; color: #991b1b; text-transform: uppercase;">Outstanding Balance</div>
                                <div style="font-size: 19px; font-weight: 800; color: #dc2626; margin-top: 2px;">₱{{ number_format($dp->balance, 2) }}</div>
                            </div>
                        </div>
                    </div>

                    @if($dp->balance > 0 && $dp->due_date)
                    <div style="margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 14px;">
                        <div style="font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 8px;">Upcoming Monthly Installments</div>
                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            @php
                                $remSim = (float) $dp->balance;
                                $mAmt = (float) $dp->monthly_amortization > 0 ? (float) $dp->monthly_amortization : $remSim;
                                $baseDue = \Carbon\Carbon::parse($dp->due_date);
                            @endphp
                            @for($idx = 0; $idx < 4 && $remSim > 0.01; $idx++)
                                @php
                                    $rowAmt = min($mAmt, $remSim);
                                    $rowPen = $idx === 0 ? (float) $dp->latePenaltyAmount($rowAmt) : 0;
                                    $rowTotal = $rowAmt + $rowPen;
                                    $rowDate = $baseDue->copy()->addMonthsNoOverflow($idx);
                                    $rowOverdue = $idx === 0 && $rowDate->isPast() && !$rowDate->isToday();
                                    $remSim -= $rowAmt;
                                @endphp
                                <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 8px 12px; border-radius: 10px; font-size: 12px;">
                                    <span style="font-weight: 600; color: #334155;">{{ $rowDate->format('M d, Y') }}</span>
                                    <span style="font-weight: 700; color: #0f172a;">₱{{ number_format($rowTotal, 2) }}</span>
                                    @if($rowOverdue)
                                        <span style="background: #fee2e2; color: #991b1b; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 9999px;">Overdue</span>
                                    @elseif($idx === 0)
                                        <span style="background: #fef3c7; color: #92400e; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 9999px;">Due Next</span>
                                    @else
                                        <span style="background: #e0e7ff; color: #3730a3; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 9999px;">Scheduled</span>
                                    @endif
                                </div>
                            @endfor
                        </div>
                    </div>
                    @endif
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
                                💳 Pay Shortfall Online
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
                            $finProgressPercent = min(100, max(0, ($financing->amount_paid / $totalShortfall) * 100));
                            $finCircumference = 2 * 3.14159 * 52;
                            $finStroke = ($finProgressPercent / 100) * $finCircumference;
                        @endphp
                        
                        <div style="display: flex; align-items: center; justify-content: center; gap: 28px; flex-wrap: wrap; padding: 10px 0; flex: 1;">
                            <div class="cer-gauge-ring" style="width: 150px; height: 150px; flex-shrink: 0;">
                                <svg viewBox="0 0 120 120">
                                    <circle class="gauge-bg" cx="60" cy="60" r="52"></circle>
                                    <circle class="gauge-fill" cx="60" cy="60" r="52"
                                        stroke-dasharray="{{ round($finStroke, 2) }} {{ round($finCircumference, 2) }}"
                                        stroke-dashoffset="0"
                                        style="stroke: #10b981; {{ $finProgressPercent <= 0 ? 'stroke-opacity: 0;' : '' }}"></circle>
                                </svg>
                                <div class="cer-gauge-label" style="font-size: 24px;">
                                    {{ number_format($finProgressPercent, 1) }}%
                                    <span style="font-size: 10px;">Paid</span>
                                </div>
                            </div>
                            <div style="flex: 1; min-width: 170px; display: flex; flex-direction: column; gap: 10px;">
                                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 12px 14px;">
                                    <div style="font-size: 11px; font-weight: 700; color: #166534; text-transform: uppercase;">Amount Paid</div>
                                    <div style="font-size: 19px; font-weight: 800; color: #059669; margin-top: 2px;">₱{{ number_format($financing->amount_paid, 2) }}</div>
                                </div>
                                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 12px 14px;">
                                    <div style="font-size: 11px; font-weight: 700; color: #991b1b; text-transform: uppercase;">Remaining Shortfall</div>
                                    <div style="font-size: 19px; font-weight: 800; color: #dc2626; margin-top: 2px;">₱{{ number_format($financing->balance, 2) }}</div>
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
            <div class="analytic-card" style="margin-bottom: 24px; padding: 0;">
                <div style="padding: 16px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                    <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: #0f172a;">Financing Payment History</h4>
                    <span style="font-size: 12px; font-weight: 700; color: #64748b;">{{ $finHistories->count() }} {{ $finHistories->count() === 1 ? 'record' : 'records' }}</span>
                </div>
                @if($finHistories->isEmpty())
                    <div style="padding: 28px 24px; text-align: center; color: #64748b; font-size: 14px;">
                        No financing payments have been recorded yet.
                    </div>
                @else
                <div style="overflow-x: auto;">
                    <table style="width: 100%; text-align: left; border-collapse: collapse; font-size: 14px;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <th style="padding: 12px 24px; font-weight: 600; color: #64748b;">Payment Date &amp; Time</th>
                                <th style="padding: 12px 24px; font-weight: 600; color: #64748b;">Transaction Reference</th>
                                <th style="padding: 12px 24px; font-weight: 600; color: #64748b;">Payment Method</th>
                                <th style="padding: 12px 24px; font-weight: 600; color: #64748b;">Amount Paid</th>
                                <th style="padding: 12px 24px; font-weight: 600; color: #64748b; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($finHistories as $hist)
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px 24px; color: #0f172a;">{{ \Carbon\Carbon::parse($hist->payment_date ?? $hist->created_at)->timezone(config('app.timezone', 'Asia/Manila'))->format('M d, Y h:i A') }}</td>
                                <td style="padding: 12px 24px; font-family: monospace; font-weight: 700; color: #0f172a;">{{ $hist->trn ?? 'Manual' }}</td>
                                <td style="padding: 12px 24px; font-weight: 600; color: #334155;">{{ $hist->method ?? 'Office Payment' }}</td>
                                <td style="padding: 12px 24px; font-weight: 700; color: #10b981;">₱{{ number_format($hist->amount_paid, 2) }}</td>
                                <td style="padding: 12px 24px; text-align: right;">
                                    <button type="button" onclick="openBuyerReceiptModal('{{ route('buyer.payments.financing.receipt', $hist->id) }}')" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-color: #3b82f6; color: #3b82f6; display: inline-flex; align-items: center; justify-content: center; cursor: pointer;">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 6px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> Receipt
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        @endif

        @include('buyer.partials.payment-calendar')
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
            btn.innerHTML = 'Pay Online';
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
            btn.innerHTML = 'Pay Shortfall Online';
        }
    }

    function openBuyerReceiptModal(url) {
        const frame = document.getElementById('buyerReceiptFrame');
        if (frame) frame.src = url;
        const modal = document.getElementById('buyerReceiptModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeBuyerReceiptModal() {
        const modal = document.getElementById('buyerReceiptModal');
        if (modal) modal.style.display = 'none';
        const frame = document.getElementById('buyerReceiptFrame');
        if (frame) frame.src = 'about:blank';
    }

    function printBuyerReceiptFrame() {
        const frame = document.getElementById('buyerReceiptFrame');
        if (frame && frame.contentWindow) {
            frame.contentWindow.focus();
            frame.contentWindow.print();
        }
    }
</script>

<!-- In-Page Receipt Preview Modal -->
<div id="buyerReceiptModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 3200; background: rgba(15,23,42,0.7); backdrop-filter: blur(8px); padding: 20px;">
    <div class="bill-modal-content" style="max-width: 960px; width: 95%; max-height: 94vh; overflow: hidden; padding: 20px 24px; border-radius: 20px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); display: flex; flex-direction: column;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0;">
            <div>
                <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0;">Payment Receipt</h2>
                <p style="font-size: 12px; color: #64748b; margin: 2px 0 0 0;">View-only official receipt preview</p>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="btn btn-outline" onclick="printBuyerReceiptFrame()" style="background: #fff; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #334155;">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print
                </button>
                <button type="button" class="btn btn-outline" onclick="closeBuyerReceiptModal()" style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 600; border-radius: 8px; cursor: pointer;">
                    ✕ Close
                </button>
            </div>
        </div>
        <div style="flex: 1; min-height: 0; width: 100%;">
            <iframe id="buyerReceiptFrame" src="about:blank" style="width: 100%; height: 75vh; border: 1px solid #e2e8f0; border-radius: 10px; background: #525659;"></iframe>
        </div>
    </div>
</div>
@endsection
