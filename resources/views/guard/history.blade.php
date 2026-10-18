@extends('layouts.guard')

@section('title', 'Visitor History Logs')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">
<style>
    @media print {
        body { background: #fff !important; }
        .sidebar, .navbar, .topbar, .sidebar-overlay { display: none !important; }
        .admin-main { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        #dateFilter, #clearFilterBtn, #exportPdfBtn, label { display: none !important; }
        .analytic-card { border: none !important; box-shadow: none !important; padding: 0 !important; }
        .bill-table th { background: #f8fafc !important; color: #000 !important; -webkit-print-color-adjust: exact; }
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
    <div style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 800; color: #0f172a; margin: 0;">Visitors</h1>
            <p style="color: #64748b; margin-top: 8px;">Full audit log of all entries through the Main Gate.</p>
        </div>
        <div style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Filter by Date</label>
                <div style="display: flex; gap: 8px;">
                    <input type="date" id="dateFilter" class="filter-select" style="padding: 10px 16px; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 13px; font-weight: 600;" value="{{ date('Y-m-d') }}">
                    <button id="clearFilterBtn" type="button" class="btn btn-outline" style="font-size: 12px; padding: 8px 14px;" onclick="clearDateFilter()">Show All</button>
                </div>
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
                <tbody id="historyTableBody">
                    @php
                        $allVisitors = collect($pins)
                            ->filter(fn($v) => ($v['status'] ?? '') === 'Entered')
                            ->values();
                    @endphp

                    @forelse($allVisitors as $v)
                    <tr class="visitor-row" data-date="{{ $v['date'] ?? date('Y-m-d') }}">
                        <td style="font-size: 13px; font-weight: 700; color: #1e3a8a;">
                            {{ $v['arrival_time'] ?? 'N/A' }}
                            <div style="font-size: 11px; color: #94a3b8; font-weight: 500;">{{ $v['date_formatted'] ?? date('M d, Y') }}</div>
                        </td>
                        <td>
                            <span style="font-size: 11px; font-weight: 800; padding: 4px 8px; border-radius: 6px; background: {{ (isset($v['type']) && $v['type'] === 'Walk-in') ? '#eff6ff; color: #2563eb;' : '#f0fdf4; color: #16a34a;' }}">
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
                                {{ $v['visitor_address'] ?? 'N/A' }}
                            </div>
                        </td>
                        <td>
                            <div style="font-family: monospace; font-weight: 700; background: #f8fafc; padding: 4px 8px; border: 1px solid #e2e8f0; border-radius: 4px; display: inline-block;">
                                {{ $v['plate_number'] ?? 'N/A' }}
                            </div>
                        </td>
                        <td>
                            @if(!empty($v['block']) && $v['block'] !== 'N/A' && !empty($v['lot']) && $v['lot'] !== 'N/A')
                                <div style="font-weight: 700; color: #475569; font-size: 13px;">B{{ $v['block'] }} L{{ $v['lot'] }}</div>
                                <div style="font-size: 11px; color: #64748b;">{{ $v['host'] ?? '' }}</div>
                            @elseif(!empty($v['host']) && $v['host'] !== 'Unknown')
                                <div style="font-size: 13px; font-weight: 600; color: #475569;">{{ $v['host'] }}</div>
                            @else
                                <div style="font-size: 12px; color: #94a3b8; font-style: italic;">Main Gate Entry</div>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 6px; color: #10b981; font-weight: 700; font-size: 13px;">
                                <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%;"></span>
                                Entered
                            </div>
                        </td>
                    </tr>
                    @empty
                    @endforelse

                    <tr id="emptyHistoryRow" style="display: none;">
                        <td colspan="8" style="text-align: center; padding: 40px 20px; color: #94a3b8; font-weight: 600;">
                            No visitor entries found for the selected date.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.5.3/jspdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.6/jspdf.plugin.autotable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dateFilter = document.getElementById('dateFilter');
        const rows = document.querySelectorAll('.visitor-row');
        const emptyRow = document.getElementById('emptyHistoryRow');

        window.filterRows = function() {
            const selectedDate = dateFilter.value;
            let visibleCount = 0;
            rows.forEach(row => {
                if (selectedDate === '' || row.getAttribute('data-date') === selectedDate) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            if (emptyRow) {
                emptyRow.style.display = (visibleCount === 0) ? '' : 'none';
            }
            document.body.setAttribute('data-print-date', selectedDate || 'All Dates');
        };

        window.clearDateFilter = function() {
            dateFilter.value = '';
            filterRows();
        };

        dateFilter.addEventListener('change', filterRows);
        filterRows(); // initial filter

        document.getElementById('exportPdfBtn').addEventListener('click', function() {
            const dateStr = dateFilter.value || 'All-Dates';
            const headers = [['Timestamp', 'Type', 'Visitor', 'Purpose', 'Home Address', 'Vehicle / Plate', 'Destination', 'Status']];
            const data = [];
            
            document.querySelectorAll('.visitor-row').forEach(row => {
                if (row.style.display !== 'none') {
                    const rowData = [];
                    row.querySelectorAll('td').forEach(td => {
                        rowData.push(td.innerText.trim().replace(/\n/g, ' - '));
                    });
                    data.push(rowData);
                }
            });

            if (data.length === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'No Data to Export',
                    text: 'There are no visitor entries matching the selected date.'
                });
                return;
            }

            try {
                const doc = new jsPDF('landscape');
                doc.setFontSize(16);
                doc.text("Visitor History Log - " + (dateFilter.value || new Date().toISOString().split('T')[0]), 14, 15);
                
                doc.autoTable({
                    head: headers,
                    body: data,
                    startY: 25,
                    theme: 'grid',
                    headStyles: { fillColor: [5, 150, 105] },
                    styles: { fontSize: 9, cellPadding: 3 }
                });
                
                doc.save('Visitor-Log-' + dateStr + '.pdf');
            } catch(e) {
                console.error(e);
                Swal.fire({ icon: 'error', title: 'Export Error', text: 'Could not generate PDF.' });
            }
        });
    });
</script>
@endsection
