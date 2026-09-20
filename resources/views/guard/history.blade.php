@extends('layouts.guard')

@section('title', 'Visitor History Logs')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">
<style>
    @media print {
        body { background: #fff !important; }
        .sidebar, .navbar, .topbar { display: none !important; }
        .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        #dateFilter, #exportPdfBtn, label { display: none !important; }
        .analytic-card { border: none !important; box-shadow: none !important; padding: 0 !important; }
        .bill-table th { background: #f8fafc !important; color: #000 !important; -webkit-print-color-adjust: exact; }
        /* Add a title specifically for print */
        body::before {
            content: "Visitor History Log - " attr(data-print-date);
            display: block;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
        }
    }
</style>

<div class="fade-in">
    <div style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <h1 style="font-size: 28px; font-weight: 800; color: #0f172a; margin: 0;">Visitors</h1>
            <p style="color: #64748b; margin-top: 8px;">Full audit log of all entries through the Main Gate.</p>
        </div>
        <div style="display: flex; gap: 12px;">
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Filter by Date</label>
                <input type="date" id="dateFilter" class="filter-select" style="padding: 10px 16px; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 13px; font-weight: 600;" value="{{ date('Y-m-d') }}">
            </div>
            <button id="exportPdfBtn" class="btn btn-outline" style="font-size: 13px; padding: 10px 20px; align-self: flex-end;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px;"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export PDF
            </button>
        </div>
    </div>

    <div class="analytic-card" style="padding: 0; overflow: hidden;">
        <div class="bill-table-container">
            <table class="bill-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Type</th>
                        <th>Visitor</th>
                        <th>Purpose</th>
                        <th>Home Address</th>
                        <th>Vehicle / Plate</th>
                        <th>Destination</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $overrides = session('visitor_overrides', []);
                        // Combine baseline data with overrides and new walk-ins
                        $allVisitors = collect($pins)
                            ->map(function($v) use ($overrides) {
                                return isset($overrides[$v['id']]) ? array_merge($v, $overrides[$v['id']]) : $v;
                            })
                            ->concat(collect($overrides)->filter(fn($o) => str_starts_with($o['id'] ?? '', 'WALK-')))
                            ->sortByDesc('arrival_time')
                            ->values();
                    @endphp

                    @foreach($allVisitors as $v)
                    @if(isset($v['status']) && $v['status'] === 'Entered')
                    <tr class="visitor-row" data-date="{{ $v['date'] ?? date('Y-m-d') }}">
                        <td style="font-size: 13px; font-weight: 700; color: #1e3a8a;">
                            {{ $v['arrival_time'] ?? 'N/A' }}
                            <div style="font-size: 11px; color: #94a3b8; font-weight: 500;">{{ $v['date_formatted'] ?? date('M d, Y') }}</div>
                        </td>
                        <td>
                            <span style="font-size: 11px; font-weight: 800; padding: 4px 8px; border-radius: 6px; background: {{ isset($v['type']) && $v['type'] === 'Walk-in' ? '#eff6ff; color: #2563eb;' : '#f0fdf4; color: #16a34a;' }}">
                                {{ $v['type'] ?? 'PIN Auth' }}
                            </span>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #0f172a;">{{ $v['visitor'] }}</div>
                            <div style="font-size: 11px; color: #64748b;">ID: {{ $v['id'] }}</div>
                        </td>
                        <td style="font-size: 13px; color: #475569;">
                            {{ $v['purpose'] ?? 'N/A' }}
                        </td>
                        <td>
                            <div style="font-size: 12px; color: #64748b; font-weight: 500;">
                                {{ isset($v['type']) && $v['type'] === 'Walk-in' ? ($v['visitor_address'] ?? 'N/A') : 'N/A' }}
                            </div>
                        </td>
                        <td>
                            <div style="font-family: monospace; font-weight: 700; background: #f8fafc; padding: 4px 8px; border: 1px solid #e2e8f0; border-radius: 4px; display: inline-block;">
                                {{ $v['plate_number'] ?? 'N/A' }}
                            </div>
                        </td>
                        <td>
                            @if(isset($v['type']) && $v['type'] === 'Walk-in')
                                <div style="font-size: 12px; color: #94a3b8; font-style: italic;">No specific destination</div>
                            @else
                                <div style="font-weight: 700; color: #475569; font-size: 13px;">B{{ $v['block'] }} L{{ $v['lot'] }}</div>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 6px; color: #10b981; font-weight: 700; font-size: 13px;">
                                <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%;"></span>
                                Entered
                            </div>
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dateFilter = document.getElementById('dateFilter');
        const rows = document.querySelectorAll('.visitor-row');

        function filterRows() {
            const selectedDate = dateFilter.value;
            rows.forEach(row => {
                if (row.getAttribute('data-date') === selectedDate || selectedDate === '') {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        dateFilter.addEventListener('change', filterRows);
        filterRows(); // initial filter

        document.getElementById('exportPdfBtn').addEventListener('click', function() {
            document.body.setAttribute('data-print-date', dateFilter.value || new Date().toISOString().split('T')[0]);
            window.print();
        });
    });
</script>
@endsection
