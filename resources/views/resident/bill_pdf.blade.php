<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ ucfirst($bill->type) }} Statement of Account - {{ \Carbon\Carbon::parse($bill->created_at)->format('F Y') }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 14px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #047857;
            padding-bottom: 10px;
            margin-bottom: 20px;
            position: relative;
        }
        .header h1 {
            color: #047857;
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 12px;
        }
        .watermark {
            position: absolute;
            top: 5px;
            right: 10px;
            border: 4px solid;
            padding: 8px 16px;
            font-size: 28px;
            font-weight: bold;
            border-radius: 8px;
            transform: rotate(15deg);
            opacity: 0.7;
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
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .box h3 {
            margin-top: 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            font-size: 16px;
            color: #047857;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        th {
            text-align: left;
            color: #555;
            background-color: #f0f0f0;
        }
        .text-right {
            text-align: right;
        }
        .fw-bold {
            font-weight: bold;
        }
        .total-row td {
            border-top: 2px solid #047857;
            font-size: 16px;
            font-weight: bold;
            color: #047857;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Althesa Subdivision</h1>
        <p>Z1, Tagbong, Pili, Camarines Sur</p>
        <p><strong>STATEMENT OF ACCOUNT ({{ strtoupper($bill->type) }})</strong></p>
        
        @if($bill->status === 'paid')
            <div class="watermark paid">PAID</div>
        @else
            <div class="watermark unpaid">UNPAID</div>
        @endif
    </div>

    @php
        $prevDate = $bill->previous_reading_date ? \Carbon\Carbon::parse($bill->previous_reading_date)->format('M d, Y') : \Carbon\Carbon::parse($bill->created_at)->subMonth()->format('M 01, Y');
        $currDate = $bill->current_reading_date ? \Carbon\Carbon::parse($bill->current_reading_date)->format('M d, Y') : \Carbon\Carbon::parse($bill->created_at)->format('M d, Y');
        $unit = $bill->type === 'electricity' ? 'kWh' : 'm³';
        
        $rate = $bill->usage_value > 0 ? ($bill->amount / $bill->usage_value) : 0;
        if ($bill->type === 'water' && $bill->usage_value > 0 && $bill->amount == 250) {
            $rate = 25; // Default assumption for minimum
        }

        $penalty = 0;
        $totalBeforeDue = $bill->amount;
        $totalPaid = $bill->payments ? $bill->payments->sum('amount_paid') : 0;

        if ($bill->status === 'paid') {
            // If it's paid, calculate if penalty was applied
            $expected = $totalBeforeDue + $bill->previous_balance;
            if ($totalPaid > $expected + 0.01) {
                $penalty = $totalPaid - $expected;
            }
        } else {
            // If unpaid and past due, show potential penalty
            if ($bill->due_date && \Carbon\Carbon::parse($bill->due_date)->isPast()) {
                $penalty = ($totalBeforeDue + $bill->previous_balance) * 0.05;
            }
        }
        $totalAfterDue = $totalBeforeDue + $bill->previous_balance + $penalty;

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
                    <td style="padding:5px; border:none; width:40%;"><strong>Name:</strong></td>
                    <td style="padding:5px; border:none;">{{ $bill->user->name ?? 'Resident' }}</td>
                </tr>
                <tr>
                    <td style="padding:5px; border:none;"><strong>Address:</strong></td>
                    <td style="padding:5px; border:none;">Block {{ $bill->lot->block ?? 'N/A' }}, Lot {{ $bill->lot->lot_number ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="padding:5px; border:none;"><strong>Billing Period:</strong></td>
                    <td style="padding:5px; border:none;">{{ \Carbon\Carbon::parse($bill->created_at)->format('F Y') }}</td>
                </tr>
                <tr>
                    <td style="padding:5px; border:none;"><strong>Due Date:</strong></td>
                    <td style="padding:5px; border:none; color:red;">{{ $bill->due_date ? \Carbon\Carbon::parse($bill->due_date)->format('M d, Y') : 'N/A' }}</td>
                </tr>
                @if($bill->status === 'paid')
                <tr>
                    <td style="padding:5px; border:none;"><strong>MOP:</strong></td>
                    <td style="padding:5px; border:none;">{{ $mop }}</td>
                </tr>
                <tr>
                    <td style="padding:5px; border:none;"><strong>Reference (TRN):</strong></td>
                    <td style="padding:5px; border:none; font-family: monospace; font-size: 11px;">{{ $trn }}</td>
                </tr>
                <tr>
                    <td style="padding:5px; border:none;"><strong>Date Paid:</strong></td>
                    <td style="padding:5px; border:none; font-size: 11px;">{{ $paidDate }}</td>
                </tr>
                @endif
            </table>
        </div>
        <div class="col-half box">
            <h3>Meter Information</h3>
            <table style="border:none; margin-bottom:0;">
                <tr>
                    <td style="padding:5px; border:none; width:50%;"><strong>Previous Reading:</strong><br><span style="font-size:10px;color:#888;">{{ $prevDate }}</span></td>
                    <td style="padding:5px; border:none;" class="text-right">{{ $bill->previous_reading }}</td>
                </tr>
                <tr>
                    <td style="padding:5px; border:none;"><strong>Current Reading:</strong><br><span style="font-size:10px;color:#888;">{{ $currDate }}</span></td>
                    <td style="padding:5px; border:none;" class="text-right">{{ $bill->current_reading ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="padding:5px; border:none;"><strong>Consumption:</strong></td>
                    <td style="padding:5px; border:none;" class="text-right fw-bold">{{ $bill->usage_value }} {{ $unit }}</td>
                </tr>
            </table>
        </div>
    </div>

    <h3 style="color:#047857; border-bottom:1px solid #ddd; padding-bottom:5px;">Billing Summary</h3>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Amount (PHP)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Current Charges ({{ $bill->usage_value }} {{ $unit }} @ ₱{{ number_format($rate, 2) }}/{{ $unit }})</td>
                <td class="text-right">₱{{ number_format($totalBeforeDue, 2) }}</td>
            </tr>
            <tr>
                <td>Previous Balance</td>
                <td class="text-right">₱{{ number_format($bill->previous_balance, 2) }}</td>
            </tr>
            @if($penalty > 0)
            <tr>
                <td>Late Payment Penalty (5%)</td>
                <td class="text-right">₱{{ number_format($penalty, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>TOTAL AMOUNT DUE</td>
                <td class="text-right">₱{{ number_format($totalAfterDue, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>This is a system generated statement of account. No signature is required.</p>
        @if($bill->status === 'paid')
            <p style="color: #10b981; font-weight: bold;">This bill has been fully settled. Thank you for your payment!</p>
        @else
            <p>Please pay on or before the due date to avoid service disconnection.</p>
        @endif
    </div>
</body>
</html>
