<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Announcement Management Report</title>
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
        <p class="sub-header">Community Communications &amp; Announcements Division &bull; Official Registry System</p>
    </div>

    <table class="meta-bar">
        <tr>
            <td style="width: 60%;">
                <div class="report-title">Community Announcements Ledger</div>
                <div class="scope-badge">
                    Status: {{ ucfirst($status) }} &bull; Date Range: {{ ucfirst(str_replace('_', ' ', $dateRange)) }} &bull; Sorted: {{ $sortLabel }}
                </div>
            </td>
            <td style="width: 40%; text-align: right; color: #000000; font-size: 8.5px;">
                <div><strong>Ref No:</strong> <span style="font-family: monospace;">{{ $refNo }}</span></div>
                <div><strong>Date Generated:</strong> {{ $generatedAt }}</div>
                <div><strong>Total Records:</strong> {{ count($announcements) }} announcements</div>
            </td>
        </tr>
    </table>

    <table class="summary-box">
        <tr>
            <td>
                <div class="summary-label">Total Announcements</div>
                <div class="summary-value">{{ $stats['total'] }}</div>
            </td>
            <td>
                <div class="summary-label">Published</div>
                <div class="summary-value">{{ $stats['published'] }}</div>
            </td>
            <td>
                <div class="summary-label">Archived</div>
                <div class="summary-value">{{ $stats['archived'] }}</div>
            </td>
        </tr>
    </table>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 28px; text-align: center;">#</th>
                <th style="width: 65px; text-align: center;">Ann. ID</th>
                <th style="width: 90px; text-align: center;">Publish Date</th>
                <th style="width: 80px;">Category</th>
                <th style="width: 160px;">Title</th>
                <th>Message Content</th>
                <th style="width: 110px;">Author</th>
                <th style="width: 75px; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($announcements as $index => $ann)
                <tr>
                    <td style="text-align: center; font-weight: bold;">{{ $index + 1 }}</td>
                    <td style="text-align: center; font-family: monospace; font-weight: bold;">ANN-{{ str_pad($ann['id'], 4, '0', STR_PAD_LEFT) }}</td>
                    <td style="text-align: center;">{{ !empty($ann['date']) ? \Carbon\Carbon::parse($ann['date'])->format('M d, Y') : '—' }}</td>
                    <td style="font-weight: bold;">{{ $ann['cat'] ?? 'Event' }}</td>
                    <td style="font-weight: bold;">{{ $ann['title'] }}</td>
                    <td style="font-size: 8px;">{{ \Illuminate\Support\Str::limit($ann['content'] ?? '', 140) }}</td>
                    <td>{{ $ann['author'] ?? 'Admin' }}</td>
                    <td style="text-align: center;">
                        <span class="badge {{ strtolower($ann['status'] ?? '') === 'published' ? 'badge-active' : 'badge-archived' }}">
                            {{ strtoupper($ann['status'] ?? 'PUBLISHED') }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px; font-style: italic;">
                        No announcement records found matching the specified report criteria.
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
