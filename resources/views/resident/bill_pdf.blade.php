<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ (isset($bill->status) && strtolower($bill->status) === 'paid') ? 'Official Receipt' : 'Statement of Account' }} - {{ ucfirst($bill->type) }} - {{ \Carbon\Carbon::parse($bill->billing_period ?? $bill->created_at)->format('F Y') }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 14mm 18mm 14mm 18mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #111;
            font-size: 11px;
            line-height: 1.38;
            margin: 0;
            padding: 0;
        }
        .statement-box {
            border: 1.5px solid #111;
            padding: 16px 20px;
            max-width: 520px;
            margin: 0 auto;
            background-color: #fff;
            position: relative;
        }
        .status-mark {
            position: absolute;
            top: 12px;
            right: 14px;
            border: 2px solid;
            padding: 3px 10px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
        }
        .status-mark.paid {
            color: #047857;
            border-color: #047857;
            background-color: #ecfdf5;
        }
        .status-mark.partial {
            color: #b45309;
            border-color: #b45309;
            background-color: #fffbeb;
            font-size: 10.5px;
        }
        .status-mark.unpaid {
            color: #dc2626;
            border-color: #dc2626;
            background-color: #fef2f2;
        }
        .corp-header {
            text-align: center;
            margin-bottom: 12px;
            padding-right: 70px;
            padding-left: 70px;
        }
        .corp-header h1 {
            margin: 0;
            font-size: 13px;
            font-weight: bold;
            color: #111;
        }
        .corp-header p {
            margin: 2px 0 0 0;
            font-size: 10.5px;
            color: #222;
        }
        .corp-header .bill-type {
            margin-top: 4px;
            font-size: 11.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .customer-info {
            margin-bottom: 10px;
            font-size: 10.5px;
        }
        .customer-info p {
            margin: 2px 0;
        }
        .divider {
            border-top: 1px dashed #333;
            margin: 8px 0;
        }
        .divider-solid {
            border-top: 1px solid #111;
            margin: 8px 0;
        }
        .notice-title {
            text-align: center;
            margin: 6px 0;
        }
        .notice-title h2 {
            margin: 0;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .notice-title p {
            margin: 2px 0 0 0;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .clean-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
        }
        .clean-table th,
        .clean-table td {
            border: none;
            padding: 2.5px 4px;
            font-size: 10.5px;
            color: #111;
        }
        .clean-table th {
            font-weight: bold;
            text-align: left;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .section-heading {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            margin: 6px 0 4px 0;
            letter-spacing: 0.4px;
        }
        .payment-box {
            border: 1px solid #047857;
            background-color: #f0fdf4;
            padding: 7px 10px;
            border-radius: 4px;
            margin: 8px 0;
            font-size: 10px;
        }
        .payment-box.partial {
            border-color: #b45309;
            background-color: #fffbeb;
        }
        .dates-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
        }
        .dates-table td {
            border: none;
            padding: 2px 4px;
            font-size: 10.5px;
        }
        .note-block {
            margin-top: 8px;
            font-size: 10px;
        }
        .conforme-block {
            margin-top: 16px;
            font-size: 10.5px;
        }
        .conforme-name {
            text-align: center;
            margin-top: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .conforme-role {
            text-align: center;
            font-size: 10px;
            color: #333;
        }
    </style>
</head>
<body>
    @php
        $rawStatus = strtolower((string) ($bill->status ?? 'unpaid'));
        $totalPaid = $bill->payments ? (float) $bill->payments->sum('amount_paid') : 0;
        $isPaid = $rawStatus === 'paid';
        $isPartiallyPaid = !$isPaid && ($rawStatus === 'partially_paid' || $totalPaid > 0);

        $tz = config('app.timezone', 'Asia/Manila');
        $billingPeriodDate = $bill->billing_period ?? $bill->created_at;
        $billingMonthYear = strtoupper(\Carbon\Carbon::parse($billingPeriodDate)->format('F Y'));

        $prevDate = $bill->previous_reading_date
            ? \Carbon\Carbon::parse($bill->previous_reading_date)->format('d/m/Y')
            : \Carbon\Carbon::parse($bill->created_at)->subMonth()->format('d/m/Y');
        $currDate = $bill->current_reading_date
            ? \Carbon\Carbon::parse($bill->current_reading_date)->format('d/m/Y')
            : \Carbon\Carbon::parse($bill->created_at)->format('d/m/Y');

        $unit = $bill->type === 'electricity' ? 'kWh' : 'm³';
        $issuedDateFormatted = $bill->created_at ? \Carbon\Carbon::parse($bill->created_at)->format('d/m/Y') : 'N/A';
        $dueDateFormatted = $bill->due_date ? \Carbon\Carbon::parse($bill->due_date)->format('d/m/Y') : 'N/A';

        $rate = (float)$bill->usage_value > 0
            ? ((float)$bill->amount / (float)$bill->usage_value)
            : (float)(\App\Models\Setting::find('elec_rate')?->value ?? 10.00);
        if ($bill->type === 'water') {
            $rate = (float)(\App\Models\Setting::find('water_rate')?->value ?? 15.00);
        }

        $currentCharges = (float) $bill->amount;
        $prevBalance = (float) ($bill->previous_balance ?? 0);
        $amountBeforeDue = $currentCharges + $prevBalance;
        $penaltyKey = $bill->type === 'electricity' ? 'electricity_penalty' : 'water_penalty';
        $penaltyRate = (float) (\App\Models\Setting::find($penaltyKey)?->value ?? 5);
        $penaltyAmount = round($amountBeforeDue * ($penaltyRate / 100), 2);
        $amountAfterDue = $amountBeforeDue + $penaltyAmount;
        $isPastDue = $bill->due_date && \Carbon\Carbon::parse($bill->due_date, $tz)->endOfDay()->isPast();
        $effectiveTotalDue = (!$isPaid && ($bill->is_at_risk || $isPastDue)) ? $amountAfterDue : $amountBeforeDue;
        $remainingBalance = max(0, round($effectiveTotalDue - $totalPaid, 2));

        $residentName = $bill->user->name ?? $bill->billed_to_name ?? 'Resident';
        $addressLine = 'Block ' . ($bill->lot->block ?? 'N/A') . ', Lot ' . ($bill->lot->lot_number ?? 'N/A') . ', Zone 1, Tagbong, Pili, Camarines Sur';

        $mop = 'N/A';
        $trn = 'N/A';
        $paidDate = 'N/A';
        if ($bill->payments && $bill->payments->count() > 0) {
            $latestPayment = $bill->payments->sortByDesc('payment_date')->first();
            $rawMop = trim((string) ($latestPayment->method ?? 'Office'));
            $mopLower = strtolower($rawMop);
            if (str_contains($mopLower, 'gcash')) {
                $mop = 'GCash';
            } elseif (str_contains($mopLower, 'maya')) {
                $mop = 'Maya';
            } elseif (str_contains($mopLower, 'office')) {
                $mop = 'Office';
            } else {
                $mop = $rawMop ?: 'Office';
            }
            $trn = $latestPayment->trn ?? 'N/A';
            $pDateRaw = $latestPayment->payment_date ?? $latestPayment->created_at;
            $paidDate = \Carbon\Carbon::parse($pDateRaw)->timezone($tz)->format('M d, Y h:i A');
        }
    @endphp

    <div class="statement-box">
        @if($isPaid)
            <div class="status-mark paid">PAID</div>
        @elseif($isPartiallyPaid)
            <div class="status-mark partial">PARTIALLY PAID</div>
        @else
            <div class="status-mark unpaid">UNPAID</div>
        @endif

        <div class="corp-header">
            <h1>Mayon Builders Realty &amp; Development Corporation</h1>
            <p>Zone 1, Tagbong, Pili, Camarines Sur</p>
            <p>STATEMENT OF ACCOUNT</p>
            <div class="bill-type">{{ $bill->type === 'water' ? 'WATER BILL' : 'ELECTRICITY BILL' }}</div>
        </div>

        <div class="customer-info">
            <p><strong>Name:</strong> {{ $residentName }}</p>
            <p><strong>Address:</strong> {{ $addressLine }}</p>
        </div>

        <div class="divider-solid"></div>

        <div class="notice-title">
            <h2>BILLING NOTICE</h2>
            <p>{{ $billingMonthYear }}</p>
        </div>

        <div class="divider"></div>

        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 40%;"></th>
                    <th style="width: 32%; text-align: center;">Date</th>
                    <th style="width: 28%; text-align: right;">Reading</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="fw-bold">Previous</td>
                    <td class="text-center">{{ $prevDate }}</td>
                    <td class="text-right">{{ number_format((float)$bill->previous_reading, 2) }}</td>
                </tr>
                <tr>
                    <td class="fw-bold">Present</td>
                    <td class="text-center">{{ $currDate }}</td>
                    <td class="text-right">{{ $bill->current_reading !== null ? number_format((float)$bill->current_reading, 2) : 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Consumption</td>
                    <td class="text-center">:</td>
                    <td class="text-right fw-bold">{{ number_format((float)$bill->usage_value, 2) }} {{ $unit }}</td>
                </tr>
            </tbody>
        </table>

        <div class="divider"></div>

        <div class="section-heading">SUMMARY OF CHARGES</div>

        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 46%;">Description</th>
                    <th style="width: 29%; text-align: center;">Computation</th>
                    <th style="width: 25%; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @if($bill->type === 'water')
                    @php
                        $waterMinM3 = (float)(\App\Models\Setting::find('water_min_m3')?->value ?? 10);
                        $waterMinRate = (float)(\App\Models\Setting::find('water_min_rate')?->value ?? 250.00);
                        $excessM3 = max(0, (float)$bill->usage_value - $waterMinM3);
                        if ($excessM3 > 0 && (float)$bill->amount > $waterMinRate) {
                            $waterExcessRate = round(((float)$bill->amount - $waterMinRate) / $excessM3, 2);
                            $excessCharge = (float)$bill->amount - $waterMinRate;
                        } else {
                            $waterExcessRate = (float)(\App\Models\Setting::find('water_rate')?->value ?? 15.00);
                            $excessCharge = $excessM3 * $waterExcessRate;
                        }
                        $displayMinRate = $currentCharges > 0 ? $waterMinRate : 0;
                    @endphp
                    <tr>
                        <td>Current Bill (Minimum)</td>
                        <td class="text-center">First {{ number_format($waterMinM3, 0) }}m&sup3;</td>
                        <td class="text-right">&#8369;{{ number_format($displayMinRate, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Excess Consumption</td>
                        <td class="text-center">{{ number_format($excessM3, 2) }} x {{ number_format($waterExcessRate, 2) }}</td>
                        <td class="text-right">&#8369;{{ number_format($excessCharge, 2) }}</td>
                    </tr>
                @else
                    <tr>
                        <td>Consumption</td>
                        <td class="text-center">{{ number_format((float)$bill->usage_value, 2) }} x {{ number_format($rate, 2) }}</td>
                        <td class="text-right">&#8369;{{ number_format($currentCharges, 2) }}</td>
                    </tr>
                @endif
                <tr>
                    <td>Previous Unpaid Bill</td>
                    <td class="text-center">Arrears</td>
                    <td class="text-right">&#8369;{{ number_format($prevBalance, 2) }}</td>
                </tr>
                <tr>
                    <td>Late Payment Bill</td>
                    <td class="text-center">{{ number_format($penaltyRate, 0) }}%</td>
                    <td class="text-right">&#8369;{{ number_format($penaltyAmount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="divider"></div>

        <table class="clean-table">
            <tbody>
                <tr>
                    <td class="fw-bold" style="width: 68%;">Amount Before {{ $dueDateFormatted }}</td>
                    <td class="text-right fw-bold" style="width: 32%;">&#8369;{{ number_format($amountBeforeDue, 2) }}</td>
                </tr>
                <tr>
                    <td class="fw-bold">Amount After {{ $dueDateFormatted }}</td>
                    <td class="text-right fw-bold">&#8369;{{ number_format($amountAfterDue, 2) }}</td>
                </tr>
            </tbody>
        </table>

        @if($isPaid || $isPartiallyPaid)
            <div class="payment-box {{ $isPartiallyPaid ? 'partial' : '' }}">
                <table class="clean-table" style="margin: 0;">
                    <tr>
                        <td style="width: 45%;"><strong>Payment Method:</strong></td>
                        <td class="text-right fw-bold">{{ $mop }}</td>
                    </tr>
                    <tr>
                        <td><strong>Amount Paid:</strong></td>
                        <td class="text-right fw-bold">&#8369;{{ number_format($totalPaid, 2) }}</td>
                    </tr>
                    @if($isPartiallyPaid)
                    <tr>
                        <td><strong>Remaining Balance:</strong></td>
                        <td class="text-right fw-bold">&#8369;{{ number_format($remainingBalance, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Reference (TRN):</strong></td>
                        <td class="text-right" style="font-family: monospace;">{{ $trn }}</td>
                    </tr>
                    <tr>
                        <td><strong>Payment Date:</strong></td>
                        <td class="text-right">{{ $paidDate }}</td>
                    </tr>
                </table>
            </div>
        @endif

        <table class="dates-table">
            <tr>
                <td style="width: 35%;">Date Issued:</td>
                <td class="text-right" style="width: 65%;">{{ $issuedDateFormatted }}</td>
            </tr>
            <tr>
                <td>Due Date:</td>
                <td class="text-right fw-bold">{{ $dueDateFormatted }}</td>
            </tr>
        </table>

        <div class="note-block">
            <strong>Note:</strong><br>
            @if($isPaid)
                This bill has been <strong>PAID</strong> via <strong>{{ $mop }}</strong>. Thank you for your payment!
            @else
                Please settle your payable on/before the due date. Thank you!
            @endif
        </div>

        <div class="conforme-block">
            <strong>Conforme:</strong>
            <div class="conforme-name">{{ strtoupper($residentName) }}</div>
            <div class="conforme-role">Owner</div>
        </div>
    </div>
</body>
</html>
