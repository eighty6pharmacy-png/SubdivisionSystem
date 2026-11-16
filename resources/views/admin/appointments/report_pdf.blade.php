<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointment Management Report</title>
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
        <p class="sub-header">Lot Inquiries &amp; Client Appointment Management Division &bull; Official Registry System</p>
    </div>

    <table class="meta-bar">
        <tr>
            <td style="width: 60%;">
                <div class="report-title">Lot &amp; Visit Inquiries Ledger</div>
                <div class="scope-badge">
                    Status: {{ ucfirst($status) }} &bull; Date Range: {{ ucfirst(str_replace('_', ' ', $dateRange)) }} &bull; Sorted: {{ $sortLabel }}
                </div>
            </td>
            <td style="width: 40%; text-align: right; color: #000000; font-size: 8.5px;">
                <div><strong>Ref No:</strong> <span style="font-family: monospace;">{{ $refNo }}</span></div>
                <div><strong>Date Generated:</strong> {{ $generatedAt }}</div>
                <div><strong>Total Inquiries:</strong> {{ count($appointments) }} appointments</div>
            </td>
        </tr>
    </table>

    <table class="summary-box">
        <tr>
            <td>
                <div class="summary-label">Total Inquiries</div>
                <div class="summary-value">{{ $stats['total'] }}</div>
            </td>
            <td>
                <div class="summary-label">Pending</div>
                <div class="summary-value">{{ $stats['pending'] }}</div>
            </td>
            <td>
                <div class="summary-label">Scheduled</div>
                <div class="summary-value">{{ $stats['scheduled'] }}</div>
            </td>
            <td>
                <div class="summary-label">Completed</div>
                <div class="summary-value">{{ $stats['completed'] }}</div>
            </td>
            <td>
                <div class="summary-label">Cancelled</div>
                <div class="summary-value">{{ $stats['cancelled'] }}</div>
            </td>
        </tr>
    </table>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 26px; text-align: center;">#</th>
                <th style="width: 60px; text-align: center;">Inquiry ID</th>
                <th style="width: 140px;">Client Name</th>
                <th style="width: 155px;">Contact &amp; Email</th>
                <th style="width: 90px; text-align: center;">Schedule Date</th>
                <th style="width: 75px; text-align: center;">Time Slot</th>
                <th style="width: 110px;">Inquiry Type</th>
                <th>Notes / Report</th>
                <th style="width: 75px; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($appointments as $index => $apt)
                <tr>
                    <td style="text-align: center; font-weight: bold;">{{ $index + 1 }}</td>
                    <td style="text-align: center; font-family: monospace; font-weight: bold;">{{ $apt['id'] }}</td>
                    <td style="font-weight: bold;">{{ $apt['client'] ?? '—' }}</td>
                    <td>
                        <div>{{ $apt['contact'] ?? 'No Contact' }}</div>
                        <div style="font-size: 7.5px; color: #333333;">{{ $apt['email'] ?? 'No Email' }}</div>
                    </td>
                    <td style="text-align: center;">{{ !empty($apt['date']) ? \Carbon\Carbon::parse($apt['date'])->format('M d, Y') : '—' }}</td>
                    <td style="text-align: center;">{{ $apt['time'] ?? '—' }}</td>
                    <td>{{ $apt['type'] ?? 'Site Visit / Lot Viewing' }}</td>
                    <td style="font-size: 8px;">{{ \Illuminate\Support\Str::limit($apt['notes'] ?? $apt['report'] ?? '—', 80) }}</td>
                    <td style="text-align: center;">
                        <span class="badge {{ in_array(strtolower($apt['status'] ?? ''), ['completed', 'scheduled']) ? 'badge-active' : 'badge-archived' }}">
                            {{ strtoupper($apt['status'] ?? 'PENDING') }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px; font-style: italic;">
                        No appointment records found matching the specified report criteria.
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
