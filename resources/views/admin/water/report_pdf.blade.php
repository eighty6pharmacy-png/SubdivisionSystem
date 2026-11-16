<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Water Utility Billing Report</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 10mm 12mm 10mm 12mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #000000;
            background: #ffffff;
            font-size: 9.5px;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000000;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .header h1 {
            margin: 0;
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000000;
        }
        .header p {
            margin: 2px 0 0 0;
            font-size: 9px;
            color: #000000;
        }
        .sub-header {
            margin: 1px 0 0 0;
            font-size: 8.5px;
            color: #333333;
        }
        .meta-bar {
            width: 100%;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #000000;
        }
        .meta-bar td {
            vertical-align: middle;
            font-size: 9px;
            color: #000000;
        }
        .report-title {
            font-size: 13px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .scope-badge {
            color: #000000;
            font-weight: bold;
            font-size: 8.5px;
            margin-top: 2px;
        }
        .summary-box {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
            background: #ffffff;
            border: 1px solid #000000;
        }
        .summary-box td {
            padding: 6px 8px;
            text-align: center;
            border-right: 1px solid #cccccc;
        }
        .summary-box td:last-child {
            border-right: none;
        }
        .summary-label {
            font-size: 7.5px;
            font-weight: bold;
            color: #333333;
            text-transform: uppercase;
        }
        .summary-value {
            font-size: 13px;
            font-weight: bold;
            color: #000000;
            margin-top: 1px;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .report-table th {
            background: #ffffff;
            color: #000000;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 6px 6px;
            text-align: left;
            border: 1px solid #000000;
        }
        .report-table td {
            padding: 5px 6px;
            font-size: 8.5px;
            color: #000000;
            border: 1px solid #cccccc;
            vertical-align: middle;
        }
        .report-table tr:nth-child(even) td {
            background: #f7f7f7;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            border: 1px solid #000000;
        }
        .badge-active {
            background: #ffffff;
            color: #000000;
        }
        .badge-archived {
            background: #e5e5e5;
            color: #000000;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 7.5px;
            color: #333333;
            border-top: 1px solid #000000;
            padding-top: 4px;
            display: table;
            width: 100%;
        }
        .footer-left {
            display: table-cell;
            text-align: left;
        }
        .footer-right {
            display: table-cell;
            text-align: right;
        }
    </style>
</head>
<body>

    <!-- Official Masthead (Pure Monochrome) -->
    <div class="header">
        <h1>Althesa Village Subdivision Users</h1>
        <p>Zone 1, Tagbong, Pili, Camarines Sur, 4418 &bull; Management &amp; Administrative Office</p>
        <p class="sub-header">Water Utility Management &amp; Metered Billing Services &bull; Official Registry System</p>
    </div>

    <!-- Document Information Bar -->
    <table class="meta-bar">
        <tr>
            <td style="width: 60%;">
                <div class="report-title">Water Utility Billing &amp; Consumption Report</div>
                <div class="scope-badge">
                    Cycle: {{ $cycleLabel }} &bull; Status: {{ ucfirst($status) }} &bull; Sorted: {{ $sortLabel ?? 'Alphabetical (Name A-Z)' }}
                </div>
            </td>
            <td style="width: 40%; text-align: right; color: #000000; font-size: 8.5px;">
                <div><strong>Ref No:</strong> <span style="font-family: monospace;">{{ $refNo }}</span></div>
                <div><strong>Date Generated:</strong> {{ $generatedAt }}</div>
                <div><strong>Total Accounts:</strong> {{ count($bills) }} units</div>
            </td>
        </tr>
    </table>

    <!-- Executive Summary Metrics (Black & White) -->
    <table class="summary-box">
        <tr>
            <td>
                <div class="summary-label">Total Accounts</div>
                <div class="summary-value">{{ $stats['total'] }}</div>
            </td>
            <td>
                <div class="summary-label">Total Billed</div>
                <div class="summary-value">₱{{ number_format($stats['total_billed'], 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Total Collected</div>
                <div class="summary-value">₱{{ number_format($stats['total_collected'], 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Total Consumption</div>
                <div class="summary-value">{{ number_format($stats['total_usage'], 1) }} m³</div>
            </td>
            <td>
                <div class="summary-label">Collection Rate</div>
                <div class="summary-value">{{ $stats['collection_rate'] }}%</div>
            </td>
        </tr>
    </table>

    <!-- Full Database Table (Black & White) -->
    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 25px; text-align: center;">#</th>
                <th style="width: 55px; text-align: center;">Account</th>
                <th style="width: 140px;">Resident Name</th>
                <th style="width: 85px;">Lot / Unit</th>
                <th style="width: 65px; text-align: right;">Usage (m³)</th>
                <th style="width: 80px; text-align: right;">Billed Amount</th>
                <th style="width: 80px; text-align: right;">Amount Paid</th>
                <th style="width: 75px; text-align: center;">Due Date</th>
                <th style="width: 65px; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bills as $index => $b)
                <tr>
                    <td style="text-align: center; color: #000000; font-weight: bold;">{{ $index + 1 }}</td>
                    <td style="text-align: center; font-family: monospace; font-weight: bold; color: #000000;">{{ $b['account_no'] ?? ('WAT-' . ($b['id'] ?? $index+1)) }}</td>
                    <td style="font-weight: bold; color: #000000;">{{ $b['resident'] }}</td>
                    <td style="color: #000000;">{{ $b['lot'] }}</td>
                    <td style="text-align: right; font-family: monospace;">{{ number_format((float)($b['usage_cbm'] ?? 0), 1) }}</td>
                    <td style="text-align: right; font-family: monospace;">₱{{ number_format((float)($b['amount'] ?? 0), 2) }}</td>
                    <td style="text-align: right; font-family: monospace; font-weight: bold;">₱{{ number_format((float)($b['total_paid'] ?? 0), 2) }}</td>
                    <td style="text-align: center; color: #000000;">{{ $b['due'] ?? '—' }}</td>
                    <td style="text-align: center;">
                        <span class="badge {{ strtolower($b['status']) === 'paid' ? 'badge-active' : 'badge-archived' }}">
                            {{ strtoupper($b['status']) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px; color: #000000; font-style: italic;">
                        No water billing records found matching the specified report criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Clean Footer -->
    <div class="footer">
        <div class="footer-left">
            Althesa Village Subdivision &bull; Official Database Report &bull; Generated from Subdivision Management System
        </div>
        <div class="footer-right">
            Confidential &bull; Internal Use Only
        </div>
    </div>

</body>
</html>
