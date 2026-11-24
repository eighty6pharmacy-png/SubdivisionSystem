<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reservation Application Receipt - {{ $r['or_number'] }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 14mm 16mm 14mm 16mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5px;
            line-height: 1.42;
            color: #111;
            margin: 0;
            padding: 0;
        }
        .page-break {
            page-break-before: always;
        }
        .top-meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .top-meta-table td {
            padding: 0;
            vertical-align: middle;
            font-size: 10.5px;
        }
        .underline-val {
            display: inline-block;
            border-bottom: 1px solid #111;
            padding: 0 6px 1px 6px;
            font-weight: bold;
            min-width: 120px;
        }
        .status-stamp {
            display: inline-block;
            border: 2px solid #047857;
            color: #047857;
            font-weight: bold;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .status-stamp.cancelled {
            border-color: #b91c1c;
            color: #b91c1c;
        }
        .company-header {
            text-align: center;
            margin-bottom: 12px;
        }
        .company-header .corp-name {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .company-header .corp-sub {
            font-size: 10.5px;
            font-weight: bold;
        }
        .sub-address-row {
            margin-top: 8px;
            margin-bottom: 10px;
            font-size: 10.5px;
        }
        .doc-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0 8px 0;
            letter-spacing: 0.4px;
        }
        .name-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .name-grid td.line-cell {
            border-bottom: 1px solid #111;
            font-weight: bold;
            padding: 2px 4px;
            text-align: center;
            font-size: 11px;
        }
        .name-grid td.caption-cell {
            font-size: 9px;
            text-align: center;
            padding-top: 2px;
            color: #333;
        }
        .prose {
            text-align: justify;
            margin: 6px 0 10px 0;
        }
        .prop-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 4px 0;
        }
        .prop-table th, .prop-table td {
            border: 1px solid #111;
            padding: 6px 5px;
            text-align: center;
            font-size: 10px;
        }
        .prop-table th {
            font-weight: bold;
            text-transform: uppercase;
            background-color: #f8fafc;
        }
        .prop-table td {
            font-weight: bold;
            font-size: 10.5px;
        }
        .gis-spec-bar {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .gis-spec-bar td {
            border: 1px solid #94a3b8;
            background-color: #f8fafc;
            padding: 4px 6px;
            font-size: 9px;
            color: #1e293b;
        }
        .terms-heading {
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0 6px 0;
            font-size: 10.5px;
        }
        .term-item {
            text-align: justify;
            margin-bottom: 7px;
        }
        .term-indent {
            margin-left: 24px;
            text-align: justify;
            margin-bottom: 6px;
        }
        .plan-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .plan-table td {
            padding: 2.5px 4px;
            vertical-align: bottom;
            font-size: 10px;
        }
        .plan-table td.label-col {
            width: 64%;
        }
        .plan-table td.val-col {
            width: 36%;
            border-bottom: 1px solid #111;
            text-align: right;
            font-weight: bold;
        }
        .plan-table td.sub-indent {
            padding-left: 28px;
        }
        .plan-table tr.spacer td {
            height: 6px;
        }
        .financing-row {
            margin: 10px 0 12px 0;
            font-size: 10px;
            font-weight: bold;
        }
        .fin-option {
            display: inline-block;
            margin-right: 16px;
        }
        .fin-check {
            display: inline-block;
            border-bottom: 1px solid #111;
            min-width: 24px;
            text-align: center;
            margin-right: 4px;
        }
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
        }
        .sig-table td {
            border: 1px solid #111;
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
            padding: 4px 6px;
        }
        .sig-table tr.sig-space td {
            height: 44px;
            font-weight: bold;
            font-size: 10px;
            vertical-align: bottom;
            padding-bottom: 4px;
        }
        .sig-table tr.sig-label td {
            font-weight: bold;
            font-size: 9.5px;
            background-color: #f8fafc;
        }
        .sig-sub {
            font-weight: normal;
            font-size: 8.5px;
            display: block;
        }
        .model-box {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            padding: 8px 10px;
            margin: 10px 0;
            font-size: 10px;
        }
        .attestation {
            font-weight: bold;
            text-align: justify;
            font-size: 9.5px;
            text-transform: uppercase;
            margin: 12px 0;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    @php
        $fmtMoney = fn ($v) => number_format((float) $v, 2);
        $fmtDate = function ($d) {
            if (empty($d)) return '____________';
            try {
                return \Carbon\Carbon::parse($d)->format('F d, Y');
            } catch (\Throwable $e) {
                return (string) $d;
            }
        };
        $civilLabel = strtolower((string) ($r['civil_status'] ?? '')) === 'married' ? 'married' : 'single';
    @endphp

    <!-- ================= PAGE 1 ================= -->
    <table class="top-meta-table">
        <tr>
            <td style="width: 58%;">
                <strong>Reservation Application Date:</strong>
                <span class="underline-val">{{ $r['reservation_date_formatted'] }}</span>
            </td>
            <td style="width: 42%; text-align: right;">
                <strong>OR #:</strong>
                <span class="underline-val" style="min-width: 95px;">{{ $r['or_number'] }}</span>
                <span class="status-stamp {{ strtolower($r['status']) === 'cancelled' ? 'cancelled' : '' }}" style="margin-left: 6px;">
                    {{ strtoupper($r['status'] ?: 'RESERVED') }}
                </span>
            </td>
        </tr>
    </table>

    <div class="company-header">
        <div class="corp-name">MAYON BUILDERS REALTY AND DEVELOPMENT CORPORATION</div>
        <div class="corp-sub">CRH Building, Salugan, Camalig, Albay</div>
    </div>

    <div class="sub-address-row">
        <div><strong>Address: ALTHESA VILLAGE SUBDIVISION, Tagbong, Pili, Camarines Sur</strong></div>
        <div>Tel. Nos: <span class="underline-val" style="min-width: 150px;">{{ $r['tel_nos'] ?: 'N/A' }}</span></div>
    </div>

    <div class="doc-title">RESERVATION APPLICATION / OFFICIAL RESERVATION RECEIPT</div>

    <table class="name-grid">
        <tr>
            <td style="width: 7%; vertical-align: bottom; font-weight: bold;">I/We,</td>
            <td class="line-cell" style="width: 31%;">{{ strtoupper($r['buyer_last_name'] ?: '—') }}</td>
            <td class="line-cell" style="width: 31%;">{{ strtoupper($r['buyer_first_name'] ?: '—') }}</td>
            <td class="line-cell" style="width: 31%;">{{ strtoupper($r['buyer_middle_name'] ?: '—') }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="caption-cell">Family Name</td>
            <td class="caption-cell">Given Name</td>
            <td class="caption-cell">Middle Name</td>
        </tr>
    </table>

    <div class="prose">
        Whose signature(s) appear(s) below Filipino, of legal age, <strong>{{ $civilLabel }}</strong>, with postal address at
        <span class="underline-val" style="min-width: 240px;">{{ $r['postal_address'] ?: 'N/A' }}</span>,
        have personally visited your project site, familiarized myself with your pricing computations and found the terms and condition quoted therein acceptable.
        In this connection, I/We hereby freely and willingly offer to buy a ( / ) house and Lot in your project and tended voluntarily to
        <strong>MAYON BUILDERS REALTY AND DEVELOPMENT CORP.</strong> the amount of
        <strong>{{ strtoupper($r['amount_in_words']) }} (₱ {{ $fmtMoney($r['amount']) }})</strong> as <strong>FULL RESERVATION FEE</strong>
        for the house and lot, indicated below:
    </div>

    <table class="prop-table">
        <thead>
            <tr>
                <th style="width: 17%;">PROJECT</th>
                <th style="width: 12%;">BLOCK NO.</th>
                <th style="width: 12%;">LOT NO.</th>
                <th style="width: 14%;">LOT AREA</th>
                <th style="width: 23%;">HOUSE MODEL</th>
                <th style="width: 22%;">TOTAL CONTRACT PRICE</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ strtoupper($r['project_name'] ?: 'ALTHESA VILLAGE') }}</td>
                <td>{{ $r['block'] !== '' ? $r['block'] : '—' }}</td>
                <td>{{ $r['lot_number'] !== '' ? $r['lot_number'] : '—' }}</td>
                <td>{{ $r['lot_area'] !== null ? number_format((float) $r['lot_area'], 2) . ' sqm' : '—' }}</td>
                <td>{{ $r['house_model'] ?: '2-STOREY' }}</td>
                <td>₱ {{ $fmtMoney($r['total_contract_price']) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="terms-heading">UNDER THE FOLLOWING TERM'S AND CONDITIONS</div>

    <div class="term-item">
        <strong>1.</strong> &nbsp; <strong>THIS APPLICATION IS SUBJECT TO APPROVAL BY MAYON BUILDERS REALTY AND DEVELOPMENT CORP.</strong>
        When approved in accordance with the terms stated herein below, the above mentioned property shall be deemed reserved.
        The Reservation Fee shall form part of the Net Required Equity/Down payment.
    </div>

    <div class="term-indent">
        <strong>a.</strong> Within NINETY (90) days from Reservation Application date or until
        <span class="underline-val" style="min-width: 110px;">{{ $fmtDate($r['loan_req_deadline']) }}</span>,
        unless this Application is not approved, I/We undertake to submit my housing loan requirements (list attached Annex "A" hereof).
    </div>

    <div class="term-indent">
        <strong>b.</strong> Within THREE MONTHS from Reservation Application date or until
        <span class="underline-val" style="min-width: 110px;">{{ $fmtDate($r['pdc_deadline']) }}</span>,
        I/We undertake to issue complete postdated cheques or CASH to be paid at the office of MBRDC Satellite Office located at Tagbong, Pili, Camarines Sur, equivalent to the term/period to be approved under Par. 2 hereof.
    </div>

    <div class="term-indent">
        Upon completion of requirements (a) and (b), I/We undertake to pay the required amounts stated in Par. 2 hereof. Place of payment and submission of documents shall be at MAYON BUILDERS REALTY AND DEVELOPMENT CORP.
    </div>

    <div class="term-indent">
        In the event that I/We fail to accomplish ANY of the foregoing requirements, <strong>MAYON BUILDERS REALTY AND DEVELOPMENT CORP.</strong> shall cancel my/our application and forfeit all amounts I/We paid to <strong>MAYON BUILDERS REALTY AND DEVELOPMENT CORP.</strong> without further notice or demand.
    </div>

    <div class="term-indent">
        Unless my application is disapproved by <strong>MAYON BUILDERS REALTY AND DEVELOPMENT CORP.</strong>, I/We shall not withdraw or back-out, otherwise, <strong>MAYON BUILDERS REALTY</strong> hereby authorized by me/us to automatically cancel my/our application and forfeit all amounts paid without further notice or demand.
    </div>

    <div class="term-item">
        <strong>2.</strong> &nbsp; Subject to <strong>MAYON BUILDERS REALTY AND DEVELOPMENT CORP.</strong> approval, I/We could like to request for installment plan as indicated below:
    </div>

    <!-- ================= PAGE 2 ================= -->
    <div class="page-break"></div>

    <table class="plan-table">
        <tr>
            <td class="label-col"><strong>TOTAL CONTRACT PRICE (TCP)</strong></td>
            <td class="val-col">₱ {{ $fmtMoney($r['total_contract_price']) }}</td>
        </tr>
        <tr>
            <td class="label-col"><strong>Discount(s) / Promo (if applicable)</strong> &nbsp; {{ number_format((float) $r['discount_percent'], 2) }} %</td>
            <td class="val-col">{{ $fmtMoney($r['discount_amount']) }}</td>
        </tr>
        <tr>
            <td class="label-col"><strong>NET CONTRACT PRICE (NTP)</strong></td>
            <td class="val-col">₱ {{ $fmtMoney($r['net_contract_price']) }}</td>
        </tr>
        <tr class="spacer"><td colspan="2"></td></tr>
        <tr>
            <td class="label-col"><strong>Loanable Amount</strong></td>
            <td></td>
        </tr>
        <tr>
            <td class="label-col sub-indent"><strong>Pag-ibig</strong></td>
            <td class="val-col">{{ $r['loanable_pagibig'] > 0 ? '₱ ' . $fmtMoney($r['loanable_pagibig']) : '—' }}</td>
        </tr>
        <tr>
            <td class="label-col sub-indent"><strong>DF</strong></td>
            <td class="val-col">{{ $r['loanable_df'] > 0 ? '₱ ' . $fmtMoney($r['loanable_df']) : '—' }}</td>
        </tr>
        <tr>
            <td class="label-col"><strong>EQUITY</strong></td>
            <td class="val-col">₱ {{ $fmtMoney($r['required_equity']) }}</td>
        </tr>
        <tr class="spacer"><td colspan="2"></td></tr>
        <tr>
            <td class="label-col"><strong>Required Equity / Downpayment {{ number_format((float) $r['equity_percent'], 0) }}% -</strong></td>
            <td class="val-col">₱ {{ $fmtMoney($r['required_equity']) }}</td>
        </tr>
        <tr>
            <td class="label-col"><strong>Reservation Fee (Paid Under OR # {{ $r['or_number'] }})</strong></td>
            <td class="val-col" style="border-bottom: 1.5px solid #dc2626;">₱ {{ $fmtMoney($r['amount']) }}</td>
        </tr>
        <tr>
            <td class="label-col"><strong>NET EQUITY DOWNPAYMENT (Paid Under OR # {{ $r['net_equity_or_number'] ?: '________' }})</strong></td>
            <td class="val-col">₱ {{ $fmtMoney($r['net_equity_downpayment']) }}</td>
        </tr>
        <tr class="spacer"><td colspan="2"></td></tr>
        <tr>
            <td class="label-col"><strong>Term of Equity</strong></td>
            <td class="val-col">{{ $r['term_of_equity'] ?: ($r['equity_period_count'] . ' Months') }}</td>
        </tr>
        <tr>
            <td class="label-col"><strong>Monthly Amortization of Equity</strong></td>
            <td class="val-col">₱ {{ $fmtMoney($r['monthly_amortization_equity']) }}</td>
        </tr>
        <tr>
            <td class="label-col"><strong>Period of Payment ( {{ $r['equity_period_count'] }} MONTHLY Installments )</strong></td>
            <td></td>
        </tr>
        <tr>
            <td class="label-col"><strong>Start on</strong></td>
            <td class="val-col">{{ $r['equity_start_date'] ?: '____________' }}</td>
        </tr>
    </table>

    <div class="financing-row">
        <div style="margin-bottom: 6px;">Through the following type of financing:</div>
        <div>
            <span class="fin-option">
                <span class="fin-check">{{ $r['financing_type'] === 'PAG-IBIG FUND' ? '✓' : '' }}</span> PAG-IBIG FUND
            </span>
            <span class="fin-option">
                <span class="fin-check">{{ $r['financing_type'] === 'DIRECT FINANCING' ? '✓' : '' }}</span> DIRECT FINANCING
            </span>
            <span class="fin-option">
                <span class="fin-check">{{ $r['financing_type'] === "END BUYER'S BANK FINANCING" ? '✓' : '' }}</span> END BUYER'S BANK FINANCING
            </span>
        </div>
    </div>

    <table class="sig-table">
        <tr class="sig-space">
            <td>{{ strtoupper($r['witness_name'] ?: '') }}</td>
            <td>{{ strtoupper($r['buyer_full_name'] ?: '') }}</td>
            <td>{{ strtoupper($r['spouse_name'] ?: '') }}</td>
        </tr>
        <tr class="sig-label">
            <td>WITNESS (SALES/MARKETING STAFF)</td>
            <td>PROSPECTIVE BUYER</td>
            <td>SPOUSE MARITAL CONSENT</td>
        </tr>
    </table>

    <div class="model-box">
        <strong>Model:</strong>
        <span class="underline-val" style="min-width: 140px;">{{ $r['house_model'] ?: '—' }}</span>
        <strong>( {{ $r['building_model_label'] ?: '2 STOREY RESIDENTIAL BLDG.' }} )</strong>
    </div>

    <div class="prose" style="margin-top: 10px;">
        <strong>MAYON BUILDERS REALTY AND DEVELOPMENT CORP.</strong> consider my/our request for change of installment term above indicated if said request is made in writing and submitted within (5) working days from reservation application date. Provided, however, that a change from one type of financing to another shall be subject to a transfer fee equivalent to TWENTY THOUSAND PESOS (₱20,000.00).
    </div>

    <div class="term-item">
        <strong>3.</strong> &nbsp; I/We undertake to comply with the terms of payment of the reserved property. I/We acknowledge that the period approved within which I/We should pay in full the Required
    </div>

    <!-- ================= PAGE 3 ================= -->
    <div class="page-break"></div>

    <div class="term-item">
        Equity/Down payment shall be non-extendible except if approved in writing by MBRDC. If an extension is approved, I/We further agree that the extension shall be subject to penalty equivalent to 2% of the amount due per month reckoned from the date payment was originally due until the date when payment is actually made.
    </div>

    <div class="term-item">
        <strong>4.</strong> &nbsp; I/We fully understand that my/our failure to pay at least two (2) monthly amortization shall be considered as an event of default which authorizes MBRDC to cancel my/our reservation, without further notice.
    </div>

    <div class="term-item">
        <strong>5.</strong> &nbsp; I/We acknowledge that the remaining balance of the purchase price shall be paid by way of assignment of the net proceed of my/our housing loan with <strong>PAG-IBIG MUTUAL FUND</strong>.
    </div>

    <div class="term-item">
        <strong>6.</strong> &nbsp; I/We fully understand that any material misrepresentation on my/our part made herein and/or made under my/our loan application, which misrepresentation has resulted to the denial of my/our loan application, shall automatically authorized MBRDC to cancel my reservation and to forfeit any and all payments I/We have made as liquidated damages.
    </div>

    <div class="term-item">
        <strong>7.</strong> &nbsp; Only payments made to and accepted and received by MBRDC Cashier or authorized representative shall be valid. Payment given to brokers and/or MBRDC employee(s) for transmittal or safekeeping shall be deemed received only upon issuance of a valid receipt by the MBRDC Cashier or authorized representative.
    </div>

    <div class="term-item">
        <strong>8.</strong> &nbsp; My/Our billing address is
        <span class="underline-val" style="min-width: 310px;">{{ $r['billing_address'] ?: 'N/A' }}</span>,
        In case of any change of my/our billing address, I/We undertake to notify MBRDC immediately.
    </div>

    <div class="term-item">
        <strong>9.</strong> &nbsp; MBRDC shall deliver all relevant notices at my/our billing address, which notices shall be deemed as sufficient notice to me/us of its contents.
    </div>

    <div class="term-item">
        <strong>10.</strong> &nbsp; I/We cannot sell, convey, assign this Application or approved reservation to another party without the prior written consent of MBRDC. Neither can I/We substitute another unit with the reserved unit nor transfer to another location without prior written consent of MBRDC. If consented to by MBRDC, the transfer/substitution shall be subject to the following fees/charges:
        <ul style="margin: 4px 0 6px 24px; padding-left: 12px;">
            <li>Transfer fee of Twenty Thousand Pesos (₱20,000.00)</li>
            <li>Adjustment in Prices and Terms prevailing at the time of transfer/substitution</li>
            <li>Incidental expenses</li>
        </ul>
    </div>

    <div class="term-item">
        <strong>11.</strong> &nbsp; All representations made and any other term or condition inconsistent with the conditions set forth in this application shall not be valid or recognized unless made in writing duly signed by the authorized officers of MBRDC.
    </div>

    <div class="attestation">
        I/WE, HEREBY ATTEST THAT I/WE HAVE READ THE CONTENTS OF THIS RESERVATION AGREEMENT THAT I/WE FULLY UNDERSTAND ALL THE TERMS AND CONDITIONS CONTAINED HEREIN. IN CASE OF MY/OUR FAILURE TO COMPLY WITH ANY OF THE ABOVE TERMS OF CONDITIONS WITHIN THE PERIOD REQUIRED FOR ANY REASON WHATSOEVER, I/WE GIVE MY/OUR FULL ASSENT TO AND HEREBY EMPOWER MBRDC OR ITS AUTHORIZED REPRESENTATIVE TO EFFECT THE CANCELLATION OF MY/OUR RESERVATION APPLICATION AND TO FORFEIT AS LIQUIDATED DAMAGES THE ENTIRE AMOUNT I/WE HAVE PAID WITHOUT FURTHER NOTICE OR DEMAND.
    </div>

    <table class="sig-table">
        <tr class="sig-space">
            <td>{{ strtoupper($r['witness_name'] ?: '') }}</td>
            <td>{{ strtoupper($r['buyer_full_name'] ?: '') }}</td>
            <td>{{ strtoupper($r['spouse_name'] ?: '') }}</td>
        </tr>
        <tr class="sig-label">
            <td>
                WITNESS<br>(SALE/ASSISTANT STAFF)
                <span class="sig-sub">(SIGNATURE OVER PRINTED NAME)</span>
            </td>
            <td>
                PROSPECTIVE BUYER
                <span class="sig-sub">(SIGNATURE OVER PRINTED NAME)</span>
            </td>
            <td>
                SPOUSE MARITAL CONSENT
                <span class="sig-sub">(SIGNATURE OVER PRINTED NAME)</span>
            </td>
        </tr>
    </table>
</body>
</html>
