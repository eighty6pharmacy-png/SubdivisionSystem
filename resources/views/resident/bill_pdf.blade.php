<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ ucfirst($bill->type) }} Statement of Account - {{ \Carbon\Carbon::parse($bill->created_at)->format('F Y') }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 12mm 15mm 15mm 15mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #000;
            font-size: 13px;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
            position: relative;
        }
        .header h1 {
            color: #000;
            margin: 0;
            font-size: 22px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #000;
            font-size: 12px;
        }
        .watermark {
            position: absolute;
            top: 5px;
            right: 10px;
            border: 3px solid;
            padding: 6px 14px;
            font-size: 24px;
            font-weight: bold;
            border-radius: 6px;
            transform: rotate(12deg);
            opacity: 0.85;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .watermark.paid {
            color: #10b981;
            border-color: #10b981;
        }
        .watermark.unpaid {
            color: #ef4444;
            border-color: #ef4444;
        }
        .row {
            width: 100%;
            margin-bottom: 20px;
        }
        .col-half {
            width: 48%;
            display: inline-block;
            vertical-align: top;
        }
        .box {
            border: 1px solid #000;
            padding: 12px;
            border-radius: 4px;
            background-color: #fff;
        }
        .box h3 {
            margin-top: 0;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
            font-size: 14px;
            color: #000;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px;
            border: 1px solid #000;
            color: #000;
        }
        th {
            text-align: left;
            color: #000;
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .fw-bold {
            font-weight: bold;
        }
        .total-row td {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            font-size: 14px;
            font-weight: bold;
            color: #000;
            background-color: #f5f5f5;
        }
        .highlight-row td {
            font-size: 13px;
            font-weight: bold;
            color: #000;
            background-color: #fafafa;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #000;
            border-top: 1px solid #000;
            padding-top: 14px;
        }
    </style>
</head>
<body>
    @php
        $isPaid = strtolower($bill->status) === 'paid';
    @endphp

    <div class="header">
        <h1>Mayon Builders Realty & Development Corporation</h1>
        <p>Z1, Tagbong, Pili, Camarines Sur</p>
        <p><strong>STATEMENT OF ACCOUNT ({{ strtoupper($bill->type) }})</strong></p>
        
        @if($isPaid)
            <div class="watermark paid">PAID</div>
        @else
            <div class="watermark unpaid">UNPAID</div>
        @endif
    </div>

    @php
        $prevDate = $bill->previous_reading_date ? \Carbon\Carbon::parse($bill->previous_reading_date)->format('M d, Y') : \Carbon\Carbon::parse($bill->created_at)->subMonth()->format('M 01, Y');
        $currDate = $bill->current_reading_date ? \Carbon\Carbon::parse($bill->current_reading_date)->format('M d, Y') : \Carbon\Carbon::parse($bill->created_at)->format('M d, Y');
        $unit = $bill->type === 'electricity' ? 'kWh' : 'm³';
        $dueDateFormatted = $bill->due_date ? \Carbon\Carbon::parse($bill->due_date)->format('M d, Y') : 'Due Date';
        
        $rate = $bill->usage_value > 0 ? ($bill->amount / $bill->usage_value) : 0;
        if ($bill->type === 'water' && $bill->usage_value > 0 && $bill->amount == 250) {
            $rate = 25; // Default assumption for minimum
        }

        $currentCharges = (float) $bill->amount;
        $prevBalance = (float) ($bill->previous_balance ?? 0);
        $amountBeforeDue = $currentCharges + $prevBalance;
        $penaltyRate = 5;
        $penaltyAmount = $amountBeforeDue * ($penaltyRate / 100);
        $amountAfterDue = $amountBeforeDue + $penaltyAmount;
        $totalPaid = $bill->payments ? (float) $bill->payments->sum('amount_paid') : 0;

        $mop = 'N/A';
        $trn = 'N/A';
        $paidDate = 'N/A';
        if ($bill->payments && $bill->payments->count() > 0) {
            $latestPayment = $bill->payments->sortByDesc('payment_date')->first();
            $mop = $latestPayment->method;
            $trn = $latestPayment->trn;
            $paidDate = \Carbon\Carbon::parse($latestPayment->payment_date)->format('M d, Y h:i A');
        }
    @endphp

    <div class="row">
        <div class="col-half box" style="margin-right: 2%;">
            <h3>Account Details</h3>
            <table style="border:none; margin-bottom:0;">
                <tr>
                    <td style="padding:4px; border:none; width:40%;"><strong>Name:</strong></td>
                    <td style="padding:4px; border:none;">{{ $bill->user->name ?? $bill->billed_to_name ?? 'Resident' }}</td>
                </tr>
                <tr>
                    <td style="padding:4px; border:none;"><strong>Address:</strong></td>
                    <td style="padding:4px; border:none;">Block {{ $bill->lot->block ?? 'N/A' }}, Lot {{ $bill->lot->lot_number ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="padding:4px; border:none;"><strong>Billing Period:</strong></td>
                    <td style="padding:4px; border:none;">{{ \Carbon\Carbon::parse($bill->created_at)->format('F Y') }}</td>
                </tr>
                <tr>
                    <td style="padding:4px; border:none;"><strong>Due Date:</strong></td>
                    <td style="padding:4px; border:none; font-weight:bold;">{{ $dueDateFormatted }}</td>
                </tr>
                @if($isPaid)
                <tr>
                    <td style="padding:4px; border:none;"><strong>MOP:</strong></td>
                    <td style="padding:4px; border:none;">{{ $mop }}</td>
                </tr>
                <tr>
                    <td style="padding:4px; border:none;"><strong>Reference (TRN):</strong></td>
                    <td style="padding:4px; border:none; font-family: monospace; font-size: 11px;">{{ $trn }}</td>
                </tr>
                <tr>
                    <td style="padding:4px; border:none;"><strong>Date Paid:</strong></td>
                    <td style="padding:4px; border:none; font-size: 11px;">{{ $paidDate }}</td>
                </tr>
                @endif
            </table>
        </div>
        <div class="col-half box">
            <h3>Meter Information</h3>
            <table style="border:none; margin-bottom:0;">
                <tr>
                    <td style="padding:4px; border:none; width:50%;"><strong>Previous Reading:</strong><br><span style="font-size:10px;color:#000;">{{ $prevDate }}</span></td>
                    <td style="padding:4px; border:none;" class="text-right">{{ $bill->previous_reading }}</td>
                </tr>
                <tr>
                    <td style="padding:4px; border:none;"><strong>Current Reading:</strong><br><span style="font-size:10px;color:#000;">{{ $currDate }}</span></td>
                    <td style="padding:4px; border:none;" class="text-right">{{ $bill->current_reading ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="padding:4px; border:none;"><strong>Consumption:</strong></td>
                    <td style="padding:4px; border:none;" class="text-right fw-bold">{{ $bill->usage_value }} {{ $unit }}</td>
                </tr>
            </table>
        </div>
    </div>

    <h3 style="color:#000; border-bottom:1px solid #000; padding-bottom:5px; text-transform:uppercase;">Billing Summary</h3>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Amount (PHP)</th>
            </tr>
        </thead>
        <tbody>
            @if($bill->type === 'water')
                @php
                    $waterMinM3 = 10;
                    $waterMinRate = 250.00;
                    $waterExcessRate = 25.00;
                    $excessM3 = max(0, (float)$bill->usage_value - $waterMinM3);
                    $excessCharge = $excessM3 * $waterExcessRate;
                @endphp
                <tr>
                    <td>Minimum Charge (First {{ $waterMinM3 }} {{ $unit }})</td>
                    <td class="text-right">₱{{ number_format($waterMinRate, 2) }}</td>
                </tr>
                @if($excessM3 > 0)
                <tr>
                    <td>Excess Consumption ({{ $excessM3 }} {{ $unit }} @ ₱{{ number_format($waterExcessRate, 2) }}/{{ $unit }})</td>
                    <td class="text-right">₱{{ number_format($excessCharge, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td><strong>Current Month Charges</strong></td>
                    <td class="text-right"><strong>₱{{ number_format($currentCharges, 2) }}</strong></td>
                </tr>
            @else
                <tr>
                    <td><strong>Current Month Charges</strong> ({{ $bill->usage_value }} {{ $unit }} @ ₱{{ number_format($rate, 2) }}/{{ $unit }})</td>
                    <td class="text-right"><strong>₱{{ number_format($currentCharges, 2) }}</strong></td>
                </tr>
            @endif
            <tr>
                <td>Previous Balance (Arrears)</td>
                <td class="text-right">₱{{ number_format($prevBalance, 2) }}</td>
            </tr>
            <tr>
                <td>Late Payment Penalty ({{ $penaltyRate }}% if paid after due date)</td>
                <td class="text-right">₱{{ number_format($penaltyAmount, 2) }}</td>
            </tr>
            <tr class="highlight-row">
                <td><strong>Amount before {{ $dueDateFormatted }}</strong></td>
                <td class="text-right"><strong>₱{{ number_format($amountBeforeDue, 2) }}</strong></td>
            </tr>
            <tr class="total-row">
                <td><strong>Amount after {{ $dueDateFormatted }}</strong></td>
                <td class="text-right"><strong>₱{{ number_format($amountAfterDue, 2) }}</strong></td>
            </tr>
            @if($isPaid)
            <tr>
                <td class="fw-bold">Total Paid</td>
                <td class="text-right fw-bold">₱{{ number_format($totalPaid, 2) }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        @if($isPaid)
            <p style="font-weight: bold; margin: 0;">This bill has been fully settled. Thank you for your payment!</p>
        @else
            <p style="margin: 0;">Please pay on or before the due date to avoid penalty charges.</p>
        @endif
    </div>
</body>
</html>
