<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Incident Management Report</title>
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
            background: #000000;
            color: #ffffff;
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
        <p class="sub-header">Security, Safety &amp; Facility Incident Management Division &bull; Official Registry System</p>
    </div>

    <!-- Document Information Bar -->
    <table class="meta-bar">
        <tr>
            <td style="width: 60%;">
                <div class="report-title">Incident Reports &amp; Resolution Ledger</div>
                <div class="scope-badge">
                    Category: {{ ucfirst($category) }} &bull; Status: {{ ucfirst($status) }} &bull; Sorted: {{ $sortLabel ?? 'Alphabetical (A-Z)' }}
                </div>
            </td>
            <td style="width: 40%; text-align: right; color: #000000; font-size: 8.5px;">
                <div><strong>Ref No:</strong> <span style="font-family: monospace;">{{ $refNo }}</span></div>
                <div><strong>Date Generated:</strong> {{ $generatedAt }}</div>
                <div><strong>Total Incidents:</strong> {{ count($incidents) }} tickets</div>
            </td>
        </tr>
    </table>

    <!-- Executive Summary Metrics (Black & White) -->
    <table class="summary-box">
        <tr>
            <td>
                <div class="summary-label">Total Tickets</div>
                <div class="summary-value">{{ $stats['total'] }}</div>
            </td>
            <td>
                <div class="summary-label">In Progress</div>
                <div class="summary-value">{{ $stats['progress'] }}</div>
            </td>
            <td>
                <div class="summary-label">Resolved</div>
                <div class="summary-value">{{ $stats['resolved'] }}</div>
            </td>
            <td>
                <div class="summary-label">Security Category</div>
                <div class="summary-value">{{ $stats['security'] }}</div>
            </td>
            <td>
                <div class="summary-label">Maintenance / Utilities</div>
                <div class="summary-value">{{ $stats['maintenance_utilities'] }}</div>
            </td>
        </tr>
    </table>

    <!-- Full Database Table (Black & White) -->
    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 25px; text-align: center;">#</th>
                <th style="width: 70px; text-align: center;">Ticket ID</th>
                <th style="width: 90px; text-align: center;">Reported Date</th>
                <th style="width: 85px;">Category</th>
                <th style="width: 130px;">Reported By</th>
                <th style="width: 140px;">Subject</th>
                <th>Incident Details / Notes</th>
                <th style="width: 75px; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($incidents as $index => $inc)
                <tr>
                    <td style="text-align: center; color: #000000; font-weight: bold;">{{ $index + 1 }}</td>
                    <td style="text-align: center; font-family: monospace; font-weight: bold; color: #000000;">INC-{{ str_pad($inc['id'], 5, '0', STR_PAD_LEFT) }}</td>
                    <td style="text-align: center; color: #000000;">{{ !empty($inc['date']) ? \Carbon\Carbon::parse($inc['date'])->format('M d, Y') : '—' }}</td>
                    <td style="font-weight: bold; color: #000000;">{{ $inc['type'] ?? 'Others' }}</td>
                    <td style="font-weight: bold; color: #000000;">{{ $inc['res'] ?? 'Anonymous' }}</td>
                    <td style="font-weight: bold; color: #000000;">{{ $inc['sub'] }}</td>
                    <td style="color: #000000; font-size: 8px;">
                        <div>{{ \Illuminate\Support\Str::limit($inc['desc'] ?? '', 120) }}</div>
                        @if(!empty($inc['admin_notes']))
                            <div style="font-style: italic; color: #333333; margin-top: 2px;">Admin: {{ \Illuminate\Support\Str::limit($inc['admin_notes'], 60) }}</div>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        <span class="badge {{ strtolower($inc['status'] ?? '') === 'resolved' ? 'badge-active' : 'badge-archived' }}">
                            {{ strtoupper($inc['status'] ?? 'PROGRESS') }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px; color: #000000; font-style: italic;">
                        No incident records found matching the specified report criteria.
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
