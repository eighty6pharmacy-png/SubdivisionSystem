<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Sales Invoice - Downpayment Receipt - {{ \Carbon\Carbon::parse($hist->payment_date ?? $hist->created_at)->format('M d, Y h:i A') }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 12mm 14mm 12mm 14mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #000;
            font-size: 11px;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        .invoice-wrapper {
            border: 1.5px solid #000;
            padding: 14px 16px;
            background-color: #fff;
            position: relative;
        }
        .corp-header {
            text-align: center;
            margin-bottom: 10px;
            position: relative;
        }
        .corp-header h1 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #000;
        }
        .corp-header p {
            margin: 2px 0 0 0;
            font-size: 10px;
            color: #000;
        }
        .title-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .title-row td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .sales-invoice-badge {
            display: inline-block;
            background-color: #000;
            color: #fff;
            font-weight: bold;
            font-size: 13px;
            letter-spacing: 0.8px;
            padding: 4px 14px;
            text-transform: uppercase;
        }
        .invoice-no {
            text-align: right;
            font-size: 13px;
            font-weight: bold;
            color: #000;
        }
        .invoice-no span.num {
            font-family: monospace;
            font-size: 13px;
            color: #000;
            margin-left: 4px;
        }
        .meta-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .meta-grid td {
            border: none;
            padding: 3px 4px;
            vertical-align: bottom;
            font-size: 10.5px;
            color: #000;
        }
        .meta-label {
            white-space: nowrap;
            width: 1%;
            font-weight: normal;
        }
        .meta-line {
            border-bottom: 1px solid #000 !important;
            font-weight: bold;
            padding-left: 6px !important;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 5px 6px;
            font-size: 10.5px;
            vertical-align: top;
            color: #000;
        }
        .items-table th {
            text-align: center;
            font-weight: bold;
            background-color: #fff;
            text-transform: uppercase;
            font-size: 10px;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .tax-label {
            font-size: 9.5px;
        }
        .total-due-row td {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            font-size: 12px;
            font-weight: bold;
            background-color: #fff;
            padding: 6px;
            color: #000;
        }
        .footer-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }
        .footer-grid td {
            border: none;
            padding: 0;
            vertical-align: bottom;
            color: #000;
        }
        .sig-line {
            border-top: 1px solid #000;
            width: 220px;
            margin-left: auto;
            padding-top: 4px;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            color: #000;
        }
        .validity-note {
            text-align: center;
            font-size: 9px;
            font-style: italic;
            margin-top: 10px;
            color: #000;
        }
    </style>
</head>

<body>
    @php
        $rawMethod = trim((string) ($hist->method ?? 'Office'));
        $methodLower = strtolower($rawMethod);
        if (str_contains($methodLower, 'gcash')) {
            $paymentType = 'GCash';
        } elseif (str_contains($methodLower, 'maya')) {
            $paymentType = 'Maya';
        } elseif (str_contains($methodLower, 'office')) {
            $paymentType = 'Office';
        } else {
            $paymentType = $rawMethod ?: 'Office';
        }

        $trn = empty($hist->trn) ? 'Manual' : $hist->trn;
        $paymentDateRaw = isset($hist->payment_date) && $hist->payment_date ? $hist->payment_date : $hist->created_at;
        $tz = config('app.timezone', 'Asia/Manila');
        $paymentDateShort = \Carbon\Carbon::parse($paymentDateRaw)->timezone($tz)->format('M d, Y');
        $paymentDateFull = \Carbon\Carbon::parse($paymentDateRaw)->timezone($tz)->format('F d, Y h:i A');
        $isFinancing = isset($fin) && $fin !== null;
        $paid = (float) $hist->amount_paid;

        if ($isFinancing) {
            $principal = round($paid, 2);
            $penalty = 0;
            $penaltyRate = 0;
            $totalDp = (float) ($fin->receivables ?? ($fin->amount_paid + $fin->balance));
            $all = \App\Models\Payment::where('financing_record_id', $fin->id)
                ->orderBy('payment_date')
                ->orderBy('created_at')
                ->get();
            $installmentNo = 0;
            $applied = 0;
            foreach ($all as $idx => $h) {
                $applied += (float) $h->amount_paid;
                if ((string) $h->id === (string) $hist->id) {
                    $installmentNo = $idx + 1;
                    break;
                }
            }
            $remaining = max(0, $totalDp - $applied);
            $term = $all->count();
            $dueThis = !empty($fin->due_date) ? \Carbon\Carbon::parse($fin->due_date)->format('M d, Y') : 'N/A';
            $tcp = (float) ($fin->contract_price ?? 0);
            $blockNo = $fin->lot?->block ?? $buyer->block_no ?? 'N/A';
            $lotNo = $fin->lot?->lot_number ?? $buyer->lot_no ?? 'N/A';
            $itemUnit = 'pmt.';
            $itemDescription = 'Financing Shortfall Payment #' . max(1, $installmentNo) . ($fin->financing_method ? ' (' . $fin->financing_method . ')' : '');
            $summaryLabel = 'Total Receivables';
        } else {
            $monthly = (float) ($dp->monthly_amortization ?? 0);
            $principal = $paid > ($monthly + 0.009) ? round($monthly, 2) : round($paid, 2);
            $penalty = round(max(0, $paid - $principal), 2);
            $penaltyRate = (float) ($dp->penalty_percentage ?? 0);
            $totalDp = (float) $dp->amount + (float) $dp->balance;
            $all = \App\Models\Payment::where('downpayment_id', $dp->id)
                ->orderBy('payment_date')
                ->orderBy('created_at')
                ->get();
            $installmentNo = 0;
            $applied = 0;
            foreach ($all as $idx => $h) {
                $p = ((float) $h->amount_paid > $monthly + 0.009) ? $monthly : (float) $h->amount_paid;
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
                $monthsBack = max(0, $all->count() - $installmentNo + 1);
                $dueThis = \Carbon\Carbon::parse($dp->due_date)->subMonths($monthsBack)->format('M d, Y');
            }
            $tcp = (float) ($buyer->financingRecord?->contract_price ?? 0);
            $blockNo = $buyer->block_no ?? 'N/A';
            $lotNo = $buyer->lot_no ?? 'N/A';
            $itemUnit = 'mo.';
            $itemDescription = 'Downpayment — Installment ' . $installmentNo . ' of ' . $term;
            $summaryLabel = 'Total Downpayment';
        }

        $buyerFullName = trim(($buyer->first_name ?? '') . ' ' . ($buyer->middle_name ? $buyer->middle_name . ' ' : '') . ($buyer->last_name ?? ''));
        $addressParts = array_filter([
            ($blockNo !== 'N/A' || $lotNo !== 'N/A') ? ('Block ' . $blockNo . ', Lot ' . $lotNo) : null,
            $buyer->postal_address ?? null,
        ]);
        $buyerAddress = $addressParts ? implode(' — ', $addressParts) : 'N/A';
        $invoiceNo = str_pad((string) $hist->id, 4, '0', STR_PAD_LEFT);
    @endphp

    <div class="invoice-wrapper">
        <div class="corp-header">
            <h1>Mayon Builders Realty &amp; Development Corporation</h1>
            <p>Tagbong, 4418, Pili, (Capital), Camarines Sur, Philippines</p>
            <p>VAT Reg. TIN 009-860-405-00001</p>
        </div>

        <table class="title-row">
            <tr>
                <td style="width: 50%;">
                    <span class="sales-invoice-badge">SALES INVOICE</span>
                </td>
                <td class="invoice-no" style="width: 50%;">
                    N&#186; <span class="num">{{ $invoiceNo }}</span>
                </td>
            </tr>
        </table>

        <table class="meta-grid">
            <tr>
                <td class="meta-label">Sold to:</td>
                <td class="meta-line" style="width: 44%;">{{ $buyerFullName ?: 'Buyer' }}</td>
                <td style="width: 3%;"></td>
                <td class="meta-label">Date:</td>
                <td class="meta-line" style="width: 32%;">{{ $paymentDateShort }}</td>
            </tr>
            <tr>
                <td class="meta-label">Address:</td>
                <td class="meta-line">{{ $buyerAddress }}</td>
                <td></td>
                <td class="meta-label">Terms:</td>
                <td class="meta-line">Installment {{ $installmentNo }} of {{ $term }}</td>
            </tr>
            <tr>
                <td class="meta-label">Business Style:</td>
                <td class="meta-line">Althesa Village</td>
                <td></td>
                <td class="meta-label">OSCA/PWD ID No.:</td>
                <td class="meta-line">&nbsp;</td>
            </tr>
            <tr>
                <td class="meta-label">Payment Method:</td>
                <td class="meta-line">{{ $paymentType }}</td>
                <td></td>
                <td class="meta-label">Cardholder's:</td>
                <td class="meta-line">&nbsp;</td>
            </tr>
            <tr>
                <td class="meta-label">Due Date:</td>
                <td class="meta-line">{{ $dueThis }}</td>
                <td></td>
                <td class="meta-label">Signature:</td>
                <td class="meta-line">&nbsp;</td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 7%;">QTY</th>
                    <th style="width: 8%;">UNIT</th>
                    <th style="width: 49%;">DESCRIPTION</th>
                    <th style="width: 17%;">UNIT PRICE</th>
                    <th style="width: 19%;">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">1</td>
                    <td class="text-center">{{ $itemUnit }}</td>
                    <td>
                        <strong>{{ $itemDescription }}</strong>
                    </td>
                    <td class="text-right">&#8369;{{ number_format($principal, 2) }}</td>
                    <td class="text-right">&#8369;{{ number_format($principal, 2) }}</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>Block {{ $blockNo }}, Lot {{ $lotNo }} (TCP: &#8369;{{ number_format($tcp, 2) }})</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>
                        {{ $summaryLabel }}: &#8369;{{ number_format($totalDp, 2) }} &bull;
                        Applied: &#8369;{{ number_format($principal, 2) }} &bull;
                        Remaining Balance: &#8369;{{ number_format($remaining, 2) }}
                    </td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>

                <!-- Bottom Tax / Summary section matching Sales Invoice format -->
                <tr>
                    <td colspan="2" rowspan="6" style="border-right: none;">&nbsp;</td>
                    <td class="tax-label">VATable Sales</td>
                    <td class="tax-label">Total Sales (VAT Inclusive)</td>
                    <td class="text-right fw-bold">&#8369;{{ number_format($paid, 2) }}</td>
                </tr>
                <tr>
                    <td class="tax-label">VAT-Exempt Sales &nbsp;&nbsp; <strong>&#8369;{{ number_format($paid, 2) }}</strong></td>
                    <td class="tax-label">Less: VAT</td>
                    <td class="text-right">&#8212;</td>
                </tr>
                <tr>
                    <td class="tax-label">Zero Rated Sales</td>
                    <td class="tax-label">Amount: Net of VAT</td>
                    <td class="text-right">&#8369;{{ number_format($paid, 2) }}</td>
                </tr>
                <tr>
                    <td class="tax-label">VAT Amount</td>
                    <td class="tax-label">Less: SC/PWD Discount</td>
                    <td class="text-right">&#8212;</td>
                </tr>
                <tr>
                    <td rowspan="2">&nbsp;</td>
                    <td class="tax-label">Amount Due</td>
                    <td class="text-right">&#8369;{{ number_format($paid, 2) }}</td>
                </tr>
                <tr>
                    <td class="tax-label">Add: VAT</td>
                    <td class="text-right">&#8212;</td>
                </tr>
                <tr class="total-due-row">
                    <td colspan="4" class="text-right" style="text-transform: uppercase;">TOTAL AMOUNT DUE</td>
                    <td class="text-right">&#8369;{{ number_format($paid, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <table class="footer-grid">
            <tr>
                <td style="width: 55%; font-size: 9px; color: #000;">
                    Official Downpayment Receipt &bull; Payment Method: <strong>{{ $paymentType }}</strong><br>
                    Recorded: {{ $paymentDateFull }}
                </td>
                <td style="width: 45%; text-align: right; padding-top: 22px;">
                    <div class="sig-line">Cashier / Authorized Signature</div>
                </td>
            </tr>
        </table>

        <div class="validity-note">
            "THIS INVOICE SHALL BE VALID FOR FIVE (5) YEARS FROM THE DATE OF ATP."
        </div>
    </div>
</body>

</html>
