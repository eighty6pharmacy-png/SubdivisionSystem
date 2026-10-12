<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Downpayment Receipt - {{ \Carbon\Carbon::parse($hist->created_at)->format('M d, Y') }}</title>
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
            border-bottom: 2px solid #0057B8;
            padding-bottom: 10px;
            margin-bottom: 20px;
            position: relative;
        }

        .header h1 {
            color: #0057B8;
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
            color: #10b981;
            border-color: #10b981;
        }

        .row {
            width: 100%;
            margin-bottom: 20px;
        }

        .col-full {
            width: 100%;
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
            color: #0057B8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
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
            border-top: 2px solid #0057B8;
            font-size: 16px;
            font-weight: bold;
            color: #0057B8;
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
        <p><strong>OFFICIAL DOWNPAYMENT RECEIPT</strong></p>

        <div class="watermark paid">PAID</div>
    </div>

    @php
        $paymentType = $hist->payment_method ?? 'Office Payment';
        $trn = empty($hist->trn) ? 'Manual' : $hist->trn;
        $paymentDateRaw = isset($hist->payment_date) ? $hist->payment_date : $hist->created_at;
        $paymentDate = \Carbon\Carbon::parse($paymentDateRaw)->format('F d, Y h:i A');
        $monthly = (float) ($dp->monthly_amortization ?? 0);
        $paid = (float) $hist->amount;
        $principal = $paid > ($monthly + 0.009) ? round($monthly, 2) : round($paid, 2);
        $penalty = round($paid - $principal, 2);
        $penaltyRate = (float) ($dp->penalty_percentage ?? 0);
        $totalDp = (float) $dp->amount + (float) $dp->balance;
        $all = \App\Models\DownpaymentHistory::where('downpayment_id', $dp->id)
            ->orderBy('payment_date')
            ->orderBy('created_at')
            ->get();
        $installmentNo = 0;
        $applied = 0;
        foreach ($all as $idx => $h) {
            $p = ((float) $h->amount > $monthly + 0.009) ? $monthly : (float) $h->amount;
            $applied += $p;
            if ((string) $h->id === (string) $hist->id) {
                $installmentNo = $idx + 1;
                break;
            }
        }
        $remaining = max(0, $totalDp - $applied);
        $term = (int) ($dp->months_to_pay ?? 0);
        $dueThis = 'N/A';
        if ($dp->due_date && $installmentNo > 0) {
            $dueThis = \Carbon\Carbon::parse($dp->due_date)->subMonths($all->count() - $installmentNo + 1)->format('M d, Y');
        }
        $tcp = (float) ($buyer->financingRecord?->contract_price ?? 0);
    @endphp

    <div class="row">
        <div class="col-full box">
            <h3>Buyer Details</h3>
            <table style="border:none; margin-bottom:0;">
                <tr>
                    <td style="padding:5px; border:none; width:30%;"><strong>Buyer Name:</strong></td>
                    <td style="padding:5px; border:none; width:70%;">{{ $buyer->first_name }} {{ $buyer->last_name }}</td>
                </tr>
                <tr>
                    <td style="padding:5px; border:none;"><strong>Block & Lot:</strong></td>
                    <td style="padding:5px; border:none;">Block {{ $buyer->block_no ?? 'N/A' }}, Lot {{ $buyer->lot_no ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="padding:5px; border:none;"><strong>Installment:</strong></td>
                    <td style="padding:5px; border:none;">{{ $installmentNo }} of {{ $term }}</td>
                </tr>
                <tr>
                    <td style="padding:5px; border:none;"><strong>Due Date:</strong></td>
                    <td style="padding:5px; border:none; color: #dc2626;">{{ $dueThis }}</td>
                </tr>
                <tr>
                    <td style="padding:5px; border:none;"><strong>Payment Date:</strong></td>
                    <td style="padding:5px; border:none;">{{ $paymentDate }}</td>
                </tr>
                <tr>
                    <td style="padding:5px; border:none;"><strong>Payment Method:</strong></td>
                    <td style="padding:5px; border:none;"><strong>{{ $paymentType }}</strong></td>
                </tr>
                <tr>
                    <td style="padding:5px; border:none;"><strong>Reference Number:</strong></td>
                    <td style="padding:5px; border:none; font-family: monospace;">{{ $trn }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="row">
        <h3>This Payment Breakdown</h3>
        <p style="font-size:12px;color:#666;">Penalty is charged only when the installment is paid after its due date. It is collected in addition to the installment and is not applied to the remaining downpayment balance.</p>
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total contract price (TCP)</td>
                    <td class="text-right">PHP {{ number_format($tcp, 2) }}</td>
                </tr>
                <tr>
                    <td>Total downpayment</td>
                    <td class="text-right">PHP {{ number_format($totalDp, 2) }}</td>
                </tr>
                <tr>
                    <td>Monthly installment</td>
                    <td class="text-right">PHP {{ number_format($monthly, 2) }}</td>
                </tr>
                <tr>
                    <td>Installment principal (month {{ $installmentNo }})</td>
                    <td class="text-right">PHP {{ number_format($principal, 2) }}</td>
                </tr>
                <tr>
                    <td>Late payment penalty ({{ number_format($penaltyRate, 2) }}%)</td>
                    <td class="text-right">PHP {{ number_format($penalty, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>Total amount received</td>
                    <td class="text-right">PHP {{ number_format($paid, 2) }}</td>
                </tr>
                <tr>
                    <td>Applied to downpayment</td>
                    <td class="text-right">PHP {{ number_format($principal, 2) }}</td>
                </tr>
                <tr>
                    <td>Remaining downpayment after this payment</td>
                    <td class="text-right">PHP {{ number_format($remaining, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>This is a system-generated receipt. No signature required.</p>
    </div>
</body>

</html>