<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Visitor Access Management Report</title>
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
    <div class="header">
        <h1>Althesa Village Subdivision</h1>
        <p>Zone 1, Tagbong, Pili, Camarines Sur, 4418 &bull; Management &amp; Administrative Office</p>
        <p class="sub-header">Gate Security &amp; Visitor Access Control Division &bull; Official Registry System</p>
    </div>

    <table class="meta-bar">
        <tr>
            <td style="width: 60%;">
                <div class="report-title">Visitor Access &amp; Gate Clearance Ledger</div>
                <div class="scope-badge">
                    Status: {{ ucfirst($status) }} &bull; Date Range: {{ ucfirst(str_replace('_', ' ', $dateRange)) }} &bull; Sorted: {{ $sortLabel }}
                </div>
            </td>
            <td style="width: 40%; text-align: right; color: #000000; font-size: 8.5px;">
                <div><strong>Ref No:</strong> <span style="font-family: monospace;">{{ $refNo }}</span></div>
                <div><strong>Date Generated:</strong> {{ $generatedAt }}</div>
                <div><strong>Total Requests:</strong> {{ count($visitors) }} visitors</div>
            </td>
        </tr>
    </table>

    <table class="summary-box">
        <tr>
            <td>
                <div class="summary-label">Total Requests</div>
                <div class="summary-value">{{ $stats['total'] }}</div>
            </td>
            <td>
                <div class="summary-label">Pending Approval</div>
                <div class="summary-value">{{ $stats['pending'] }}</div>
            </td>
            <td>
                <div class="summary-label">Approved</div>
                <div class="summary-value">{{ $stats['approved'] }}</div>
            </td>
            <td>
                <div class="summary-label">Entered Gate</div>
                <div class="summary-value">{{ $stats['entered'] }}</div>
            </td>
            <td>
                <div class="summary-label">Exited Gate</div>
                <div class="summary-value">{{ $stats['exited'] ?? 0 }}</div>
            </td>
            <td>
                <div class="summary-label">Rejected / Expired</div>
                <div class="summary-value">{{ $stats['rejected'] }}</div>
            </td>
        </tr>
    </table>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 26px; text-align: center;">#</th>
                <th style="width: 60px; text-align: center;">Req ID</th>
                <th style="width: 135px;">Resident / Host</th>
                <th style="width: 135px;">Visitor Name &amp; Contact</th>
                <th>Purpose</th>
                <th style="width: 95px; text-align: center;">Visit Date &amp; Time</th>
                <th style="width: 90px;">Validated By</th>
                <th style="width: 60px; text-align: center;">PIN</th>
                <th style="width: 80px; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($visitors as $index => $v)
                <tr>
                    <td style="text-align: center; font-weight: bold;">{{ $index + 1 }}</td>
                    <td style="text-align: center; font-family: monospace; font-weight: bold;">{{ $v['id'] }}</td>
                    <td>
                        <div style="font-weight: bold;">{{ $v['host'] ?? 'Unknown' }}</div>
                        <div style="font-size: 7.5px; color: #333333;">Block {{ $v['block'] ?? 'N/A' }}, Lot {{ $v['lot'] ?? 'N/A' }}</div>
                    </td>
                    <td>
                        <div style="font-weight: bold;">{{ $v['visitor'] ?? '—' }}</div>
                        @if(!empty($v['contact_number']))
                            <div style="font-size: 7.5px; color: #333333;">{{ $v['contact_number'] }}</div>
                        @endif
                    </td>
                    <td>{{ \Illuminate\Support\Str::limit($v['purpose'] ?? '—', 70) }}</td>
                    <td style="text-align: center;">
                        <div>{{ $v['validity'] ?? $v['date_formatted'] ?? '—' }}</div>
                        @if(!empty($v['arrival_time']))
                            <div style="font-size: 7.5px; color: #333333;">In: {{ $v['arrival_time'] }}</div>
                        @endif
                        @if(!empty($v['left_house_at']))
                            <div style="font-size: 7.5px; color: #333333;">Left House: {{ $v['left_house_at'] }}</div>
                        @endif
                        @if(!empty($v['exit_time']))
                            <div style="font-size: 7.5px; color: #333333;">Out: {{ $v['exit_time'] }}</div>
                        @endif
                    </td>
                    <td>{{ $v['guard_name'] ?? (in_array(strtolower($v['status'] ?? ''), ['entered', 'exited']) ? 'Gate Guard' : 'Pending') }}</td>
                    <td style="text-align: center; font-family: monospace; font-weight: bold;">{{ $v['pin'] ?? '—' }}</td>
                    <td style="text-align: center;">
                        <span class="badge {{ in_array(strtolower($v['status'] ?? ''), ['approved', 'entered', 'exited']) ? 'badge-active' : 'badge-archived' }}">
                            {{ strtoupper($v['status'] ?? 'PENDING') }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px; font-style: italic;">
                        No visitor records found matching the specified report criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

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
