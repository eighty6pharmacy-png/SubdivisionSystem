@extends('layouts.resident')

@section('title', 'My Water Bill')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">

<div class="fade-in">
    <div style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <h1 style="font-size: 28px; font-weight: 800; color: #0f172a; margin: 0;">Water Billing</h1>
                @if(isset($lots) && $lots->count() > 1 && isset($selectedLot))
                    <span style="background: #e0f2fe; color: #0369a1; font-size: 13px; font-weight: 800; padding: 4px 12px; border-radius: 9999px;">
                        Block {{ $selectedLot->block }} Lot {{ $selectedLot->lot_number }}
                    </span>
                @endif
            </div>
            <p style="color: #64748b; margin-top: 8px;">Monitor your water consumption and settle outstanding balances.</p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            @if(!empty($waterBill['db_id']))
                <a href="/resident/bills/history/water/pdf/{{ $waterBill['db_id'] }}" target="_blank" class="btn btn-outline" style="border-radius: 12px; font-weight: 600; padding: 10px 18px; display: inline-flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    {{ ($waterBill['status'] ?? '') === 'paid' ? 'Print Receipt' : 'Print Statement' }}
                </a>
            @endif
            <a href="/resident/bills/history/water" class="btn btn-outline" style="border-radius: 12px; font-weight: 600; padding: 10px 20px; display: inline-flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                View History
            </a>
        </div>
    </div>

    @php
        $minM3 = $waterBill['min_m3'] ?? 10;
        $minRate = $waterBill['min_rate'] ?? 250;
        $excessRate = $waterBill['excess_rate'] ?? (float)(\App\Models\Setting::find('water_rate')?->value ?? 15);
        $usageM3 = (float)($waterBill['usage_cbm'] ?? 0);
        $waterBaseAmount = (float)($waterBill['base_amount'] ?? 0);
        $hasBilledWater = $usageM3 > 0 || $waterBaseAmount > 0;
        $minCharge = $hasBilledWater ? $minRate : 0;
        $excessCharge = $usageM3 > $minM3 ? ($usageM3 - $minM3) * $excessRate : 0;
        $waterCurrentMonth = $waterBaseAmount > 0 ? $waterBaseAmount : ($minCharge + $excessCharge);
        $prevBal = (float)($waterBill['previous_balance'] ?? 0);
        $baseTotal = $waterCurrentMonth + $prevBal;
        $waterPenaltyRate = (float)($waterBill['penalty_rate'] ?? (\App\Models\Setting::find('water_penalty')?->value ?? 5));
        $arrearsPenalty = round($baseTotal * ($waterPenaltyRate / 100), 2);
        $afterAmt = round($baseTotal + $arrearsPenalty, 2);
        $waterIsOverdue = !empty($waterBill['at_risk']) && ($waterBill['status'] ?? '') !== 'paid';
        $waterEffectiveTotal = $waterIsOverdue ? $afterAmt : $baseTotal;
        $waterTotalPaid = (float)($waterBill['total_paid'] ?? 0);
        $waterRemaining = ($waterBill['status'] ?? '') === 'paid' ? 0.0 : max(0.0, round($waterEffectiveTotal - $waterTotalPaid, 2));
        $waterIsPartial = ($waterBill['status'] ?? '') === 'partially_paid' || ($waterTotalPaid > 0 && ($waterBill['status'] ?? '') !== 'paid' && $waterRemaining > 0);
        $waterHeaderAmount = $waterIsPartial ? $waterRemaining : $waterEffectiveTotal;
    @endphp

    <!-- Current Bill Card -->
    <div class="analytic-card" style="padding: 28px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">
                    {{ $waterIsPartial ? 'Remaining Water Balance Due' : 'Current Water Amount' }}
                </span>
                @if(($waterBill['status'] ?? '') === 'paid')
                    <span class="badge badge-success">Paid</span>
                @elseif($waterIsPartial)
                    <span class="badge" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a;">Partially Paid</span>
                @elseif(($waterBill['status'] ?? '') !== 'no-bill')
                    <span class="badge badge-danger">{{ $waterIsOverdue ? 'Past Due' : 'Unpaid' }}</span>
                @endif
            </div>
            <div style="font-size: 32px; font-weight: 800; color: #0f172a; margin-top: 8px;">₱{{ number_format($waterHeaderAmount, 2) }}</div>
            @if($waterIsOverdue && $baseTotal > 0)
                <div style="font-size: 12px; color: #dc2626; font-weight: 700; margin-top: 4px;">
                    Includes {{ $waterPenaltyRate }}% Late Penalty (+₱{{ number_format($arrearsPenalty, 2) }}) — Amount Before Due was ₱{{ number_format($baseTotal, 2) }}
                </div>
            @endif
            @if($waterTotalPaid > 0)
                <div style="font-size: 13px; color: #059669; font-weight: 700; margin-top: 4px;">
                    Amount Paid: ₱{{ number_format($waterTotalPaid, 2) }}
                    @if($waterIsPartial)
                        <span style="color: #64748b; font-weight: 600;">(of ₱{{ number_format($waterEffectiveTotal, 2) }} Total Bill)</span>
                    @endif
                </div>
            @endif
            <div style="font-size: 13px; color: {{ $waterBill['status'] === 'paid' ? '#10b981' : ($waterBill['status'] === 'no-bill' ? '#64748b' : ($waterIsPartial ? '#d97706' : '#ef4444')) }}; font-weight: 600; margin-top: 4px;">
                {{ $waterBill['status'] === 'paid' ? '✓ Paid on '.$waterBill['paid_date'] : ($waterBill['status'] === 'no-bill' ? 'ℹ No Pending Bill' : '⚠ Due: '.$waterBill['due'].($waterIsOverdue ? ' (Past Due)' : '')) }}
            </div>
            
            <div style="font-size: 12px; color: #64748b; margin-top: 8px;">
                Consumption: <strong>{{ $waterBill['usage'] }}</strong>
                &bull; Current Month Charges: <strong>₱{{ number_format($waterCurrentMonth, 2) }}</strong>
            </div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 12px; align-items: flex-end;">
            @if(in_array($waterBill['status'], ['unpaid', 'partially_paid', 'overdue']))
                <button onclick="payBill('{{ $waterBill['db_id'] }}')" id="payBtn" class="btn btn-primary" style="padding: 14px 24px; font-weight: 700; background: #0057B8; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    {{ $waterIsPartial ? 'Pay Remaining Balance (₱'.number_format($waterRemaining, 2).')' : 'Pay Online (GCash, Maya, Card)' }}
                </button>
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
                <div style="width: 48px; height: 48px; border-radius: 12px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"/></svg>
                </div>
                <h3 style="font-size: 18px; color: #0f172a; margin-bottom: 8px;">No Current Statement</h3>
                <p style="color: #64748b; font-size: 14px;">No outstanding water bill is due for this billing cycle.</p>
            </div>
        @else
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(125px, 1fr)); gap: 8px; padding-bottom: 8px;">
                <div style="background: #f8fafc; padding: 10px 8px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 9px; color: #64748b; font-weight: 700; text-transform: uppercase;">Prev Reading</div>
                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ $waterBill['prev_reading'] }}</div>
                    <div style="font-size: 9px; color: #94a3b8; margin-top: 2px;">{{ $waterBill['prev_reading_date'] }}</div>
                </div>
                <div style="background: #f8fafc; padding: 10px 8px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 9px; color: #64748b; font-weight: 700; text-transform: uppercase;">Curr Reading</div>
                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ $waterBill['curr_reading'] }}</div>
                    <div style="font-size: 9px; color: #94a3b8; margin-top: 2px;">{{ $waterBill['curr_reading_date'] }}</div>
                </div>
                <div style="background: #f8fafc; padding: 10px 8px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 9px; color: #64748b; font-weight: 700; text-transform: uppercase;">Total Consum.</div>
                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ $waterBill['usage'] }}</div>
                </div>
                <div style="background: #f8fafc; padding: 10px 8px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 9px; color: #64748b; font-weight: 700; text-transform: uppercase;">Min (First {{ $minM3 }}m³)</div>
                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 4px;">₱{{ number_format($minCharge, 2) }}</div>
                </div>
                <div style="background: #f8fafc; padding: 10px 8px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 9px; color: #64748b; font-weight: 700; text-transform: uppercase;">Excess Cons.</div>
                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 4px;">₱{{ number_format($excessCharge, 2) }}</div>
                </div>
                <div style="background: #eff6ff; padding: 10px 8px; border-radius: 8px; border: 1px solid #bfdbfe;">
                    <div style="font-size: 9px; color: #1d4ed8; font-weight: 700; text-transform: uppercase;">Current Month Charges</div>
                    <div style="font-size: 15px; font-weight: 800; color: #1e40af; margin-top: 4px;">₱{{ number_format($waterCurrentMonth, 2) }}</div>
                    <div style="font-size: 9px; color: #3b82f6; margin-top: 2px;">This Month Only</div>
                </div>
                <div style="background: #f8fafc; padding: 10px 8px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 9px; color: #64748b; font-weight: 700; text-transform: uppercase;">Prev Balance</div>
                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 4px;">₱{{ number_format($prevBal, 2) }}</div>
                </div>
                <div style="background: #f8fafc; padding: 10px 8px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 9px; color: #64748b; font-weight: 700; text-transform: uppercase;">Amt Before Due</div>
                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 4px;">₱{{ number_format($baseTotal, 2) }}</div>
                </div>
                <div style="background: #f8fafc; padding: 10px 8px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 9px; color: #64748b; font-weight: 700; text-transform: uppercase;">Penalty ({{ $waterPenaltyRate }}%)</div>
                    <div style="font-size: 15px; font-weight: 800; color: #f59e0b; margin-top: 4px;">₱{{ number_format($arrearsPenalty, 2) }}</div>
                    <div style="font-size: 9px; color: {{ $waterIsOverdue ? '#dc2626' : '#94a3b8' }}; font-weight: 600; margin-top: 2px;">{{ $waterIsOverdue ? 'Applied (Past Due)' : 'If Paid After Due' }}</div>
                </div>
                <div style="background: #fef2f2; padding: 10px 8px; border-radius: 8px; border: 1px solid #fecaca;">
                    <div style="font-size: 9px; color: #991b1b; font-weight: 700; text-transform: uppercase;">Amt After Due</div>
                    <div style="font-size: 15px; font-weight: 800; color: #ef4444; margin-top: 4px;">₱{{ number_format($afterAmt, 2) }}</div>
                    @if($waterIsOverdue)
                        <div style="font-size: 9px; color: #dc2626; font-weight: 700; margin-top: 2px;">Active Total Due</div>
                    @endif
                </div>
                <div style="background: #f0fdf4; padding: 10px 8px; border-radius: 8px; border: 1px solid #bbf7d0;">
                    <div style="font-size: 9px; color: #065f46; font-weight: 700; text-transform: uppercase;">Amount Paid</div>
                    <div style="font-size: 15px; font-weight: 800; color: #10b981; margin-top: 4px;">₱{{ number_format($waterTotalPaid, 2) }}</div>
                    @if($waterIsPartial)
                        <div style="font-size: 9px; color: #d97706; font-weight: 700; margin-top: 2px;">Bal: ₱{{ number_format($waterRemaining, 2) }}</div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- Consumption Chart -->
    <div class="analytic-card" style="padding: 24px; margin-bottom: 24px; border-radius: 24px; border: 1px solid var(--bill-border); background: #ffffff;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
            <div>
                <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                    <svg width="20" height="20" fill="none" stroke="#0284c7" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"/></svg>
                    Water Consumption Trend
                </h3>
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
    <div class="analytic-card" style="padding: 0; overflow: hidden; margin-bottom: 24px;">
        <div style="padding: 20px 24px; border-bottom: 1px solid var(--bill-border);">
            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Payment Ledger</h3>
        </div>
        <div class="bill-table-container">
            <table class="bill-table">
                <thead><tr><th>Reference</th><th>Method</th><th>Date & Time</th><th>Period</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($waterBill['payment_history'] ?? [] as $h)
                    <tr>
                        <td style="font-family: monospace; font-size: 11px;">{{ $h['trn'] }}</td>
                        <td style="font-size: 13px; color: #475569;">{{ $h['method'] ?? 'N/A' }}</td>
                        <td style="font-size: 13px; color: #475569;">{{ $h['date'] }}</td>
                        <td>{{ $h['month'] }}</td>
                        <td style="font-weight: 700;">₱{{ number_format($h['amount'], 2) }}</td>
                        <td><span class="badge {{ $h['status'] === 'Paid' ? 'badge-success' : 'badge-danger' }}">{{ $h['status'] }}</span></td>
                        <td>
                            @if($h['status'] === 'Paid')
                            <a href="/resident/bills/history/water" style="background: none; border: none; cursor: pointer; color: #3b82f6; display: inline-flex; align-items: center;" title="View Statement">
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 32px; color: #94a3b8;">No payment records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('payment') === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Payment Successful',
                text: 'Your water bill payment has been confirmed and recorded.',
                confirmButtonColor: '#0057B8'
            }).then(() => {
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        } else if (urlParams.get('payment') === 'cancelled') {
            Swal.fire({
                icon: 'info',
                title: 'Payment Cancelled',
                text: 'Your payment session was cancelled. Your outstanding bill remains unchanged.',
                confirmButtonColor: '#64748b'
            }).then(() => {
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        }
    });

    async function payBill(billId) {
        const btn = document.getElementById('payBtn');
        if (!billId || billId === 'null' || billId === 'N/A') {
            Swal.fire({
                icon: 'info',
                title: 'No Pending Bill',
                text: 'There is no pending water bill to pay at this moment.'
            });
            return;
        }

        btn.disabled = true;
        btn.innerHTML = 'Connecting securely...';

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
            Swal.fire({
                icon: 'error',
                title: 'Unable to Proceed',
                text: e.message || 'Unable to initiate secure payment.'
            });
            btn.disabled = false;
            btn.innerHTML = '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg> Pay Online (GCash, Maya, Card)';
        }
    }

    let waterChart = null;
    let amountChart = null;
    let currentWaterRange = 6;
    
    const rawWaterHistory = {!! json_encode($waterBill['usage_history'] ?? []) !!};
    const rawAmountHistory = {!! json_encode($waterBill['amount_history'] ?? []) !!};

    const fullMonthLabels = (Array.isArray(rawWaterHistory) && rawWaterHistory.length > 0 && typeof rawWaterHistory[0] === 'object')
        ? rawWaterHistory.map(item => item?.label || '')
        : {!! json_encode(array_map(fn($i) => date('M Y', strtotime("-$i months")), range(11, 0))) !!};

    const fullWaterHistory = Array.isArray(rawWaterHistory)
        ? rawWaterHistory.map(item => typeof item === 'object' ? (item?.value || 0) : (Number(item) || 0))
        : [];

    const fullAmountHistory = Array.isArray(rawAmountHistory)
        ? rawAmountHistory.map(item => typeof item === 'object' ? (item?.value || 0) : (Number(item) || 0))
        : [];

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
