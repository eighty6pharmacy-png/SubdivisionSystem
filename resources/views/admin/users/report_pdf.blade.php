<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management &amp; Registry Report</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 10mm 12mm 10mm 12mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #000000;
            background: #ffffff;
            font-size: 10px;
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
            font-size: 9.5px;
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
            font-size: 9px;
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
            font-size: 14px;
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
            padding: 6px 7px;
            text-align: left;
            border: 1px solid #000000;
        }
        .report-table td {
            padding: 5px 7px;
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
        <h1>Tagbong Residences / Alteza Ville Homeowners Association</h1>
        <p>Zone 1, Tagbong, Pili, Camarines Sur, 4418 &bull; Management &amp; Administrative Office</p>
        <p class="sub-header">Identity &amp; System Access Control Division &bull; Official Registry System</p>
    </div>

    <!-- Document Information Bar -->
    <table class="meta-bar">
        <tr>
            <td style="width: 60%;">
                <div class="report-title">User Management &amp; Access Registry Report</div>
                <div class="scope-badge">
                    Scope: {{ $role === 'all' ? 'All Roles' : $role . 's' }} &bull; {{ $status === 'all' ? 'All Status' : $status . ' Only' }} &bull; Sorted: {{ $sortLabel ?? 'Alphabetical (Name A-Z)' }}
                </div>
            </td>
            <td style="width: 40%; text-align: right; color: #000000; font-size: 8.5px;">
                <div><strong>Ref No:</strong> <span style="font-family: monospace;">{{ $refNo }}</span></div>
                <div><strong>Date Generated:</strong> {{ $generatedAt }}</div>
                <div><strong>Total Records:</strong> {{ count($users) }} accounts</div>
            </td>
        </tr>
    </table>

    <!-- Executive Summary Metrics (Black & White) -->
    <table class="summary-box">
        <tr>
            <td>
                <div class="summary-label">Total Listed</div>
                <div class="summary-value">{{ $stats['total'] }}</div>
            </td>
            <td>
                <div class="summary-label">Active</div>
                <div class="summary-value">{{ $stats['active'] }}</div>
            </td>
            <td>
                <div class="summary-label">Archived</div>
                <div class="summary-value">{{ $stats['archived'] }}</div>
            </td>
            <td>
                <div class="summary-label">Residents</div>
                <div class="summary-value">{{ $stats['residents'] }}</div>
            </td>
            <td>
                <div class="summary-label">Security Staff</div>
                <div class="summary-value">{{ $stats['guards'] }}</div>
            </td>
            <td>
                <div class="summary-label">Finance Team</div>
                <div class="summary-value">{{ $stats['finance'] }}</div>
            </td>
            <td>
                <div class="summary-label">Buyers</div>
                <div class="summary-value">{{ $stats['buyers'] }}</div>
            </td>
            <td>
                <div class="summary-label">Admins</div>
                <div class="summary-value">{{ $stats['admins'] }}</div>
            </td>
        </tr>
    </table>

    <!-- Full Database Table (Black & White) -->
    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 25px; text-align: center;">#</th>
                <th style="width: 65px; text-align: center;">User ID</th>
                <th style="width: 140px;">Full Name</th>
                <th style="width: 150px;">Email Address</th>
                <th style="width: 90px;">Contact Number</th>
                <th style="width: 95px;">System Role</th>
                <th>Property Scope / Assignment</th>
                <th style="width: 65px; text-align: center;">Status</th>
                <th style="width: 80px; text-align: right;">Joined</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $index => $u)
                <tr>
                    <td style="text-align: center; color: #000000; font-weight: bold;">{{ $index + 1 }}</td>
                    <td style="text-align: center; font-family: monospace; font-weight: bold; color: #000000;">{{ $u['id'] }}</td>
                    <td style="font-weight: bold; color: #000000;">{{ $u['name'] }}</td>
                    <td style="color: #000000;">{{ $u['email'] }}</td>
                    <td style="color: #000000;">{{ $u['contact_number'] }}</td>
                    <td style="font-weight: bold; color: #000000;">{{ $u['role'] }}</td>
                    <td style="color: #000000;">{{ $u['meta'] }}</td>
                    <td style="text-align: center;">
                        @if($u['status'] === 'Active')
                            <span class="badge badge-active">ACTIVE</span>
                        @else
                            <span class="badge badge-archived">ARCHIVED</span>
                        @endif
                    </td>
                    <td style="text-align: right; color: #000000;">{{ $u['joined'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px; color: #000000; font-style: italic;">
                        No user records found matching the specified report criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Clean Footer -->
    <div class="footer">
        <div class="footer-left">
            Tagbong Residences &bull; Official Database Report &bull; Generated from Subdivision Management System
        </div>
        <div class="footer-right">
            Confidential &bull; Internal Use Only
        </div>
    </div>

</body>
</html>
