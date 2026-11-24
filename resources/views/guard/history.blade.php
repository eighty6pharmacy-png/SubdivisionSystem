@extends('layouts.guard')

@section('title', 'Visitor History Logs')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}?v={{ filemtime(public_path('css/admin-finance.css')) }}">
<style>
    @media print {
        body { background: #fff !important; }
        .sidebar, .navbar, .topbar, .sidebar-overlay { display: none !important; }
        .admin-main { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .filter-bar, #generateReportBtn, .admin-table-pagination-bar { display: none !important; }
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
    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 800; color: #0f172a; margin: 0;">Visitors</h1>
            <p style="color: #64748b; margin-top: 8px;">Full audit log of all entries and exits through the Main Gate.</p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button id="generateReportBtn" type="button" class="btn btn-outline" style="background: #fff; border-color: #0284c7; color: #0284c7; font-weight: 700; display: inline-flex; align-items: center; padding: 10px 18px;" onclick="openVisitorReportModal()">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 6px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Generate Report
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar" style="background: #f8fafc; padding: 16px 20px; border-radius: 14px; border: 1px solid #e2e8f0; display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 20px;">
        <div style="flex: 1; min-width: 240px;">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; display: block;">Search Visitors</label>
            <input type="text" id="historySearch" class="filter-select" placeholder="Search ID, Visitor, Purpose, Address, Plate, Destination..." oninput="filterRows()" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #fff;">
        </div>
        <div style="width: 180px;">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; display: block;">Status</label>
            <select id="historyStatusFilter" class="filter-select" onchange="filterRows()" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #fff;">
                <option value="all">All Statuses</option>
                <option value="entered">Entered Gate</option>
                <option value="exited">Exited Gate</option>
            </select>
        </div>
        <div>
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; display: block;">Filter by Date</label>
            <div style="display: flex; gap: 8px;">
                <input type="date" id="dateFilter" class="filter-select" onchange="filterRows()" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; font-weight: 600; background: #fff;" value="{{ date('Y-m-d') }}">
                <button id="clearFilterBtn" type="button" class="btn btn-outline" style="font-size: 12px; padding: 8px 14px; background: #fff; border-radius: 8px;" onclick="clearDateFilter()">Show All</button>
            </div>
        </div>
    </div>

    <div class="analytic-card" style="padding: 0; overflow: hidden; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <div class="bill-table-container">
            <table class="bill-table" id="guardHistoryTable">
                <thead>
                    <tr>
                        <th data-sort="date">Timestamp</th>
                        <th data-sort="text">Type</th>
                        <th data-sort="text">Visitor</th>
                        <th data-sort="text">Purpose</th>
                        <th data-sort="text">Home Address</th>
                        <th data-sort="text">Vehicle / Plate</th>
                        <th data-sort="text">Destination</th>
                        <th data-sort="text">Status</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">
                    @php
                        $allVisitors = collect($pins)
                            ->filter(fn($v) => in_array($v['status'] ?? '', ['Entered', 'Exited'], true))
                            ->values();
                    @endphp

                    @forelse($allVisitors as $v)
                    @php
                        $searchBlob = strtolower(implode(' ', [
                            $v['id'] ?? '',
                            $v['type'] ?? '',
                            $v['visitor'] ?? '',
                            $v['purpose'] ?? '',
                            $v['visitor_address'] ?? '',
                            $v['plate_number'] ?? '',
                            $v['block'] ?? '',
                            $v['lot'] ?? '',
                            $v['host'] ?? '',
                            $v['status'] ?? '',
                        ]));
                    @endphp
                    <tr class="visitor-row pagination-match"
                        data-date="{{ $v['date'] ?? date('Y-m-d') }}"
                        data-status="{{ strtolower($v['status'] ?? 'entered') }}"
                        data-search="{{ $searchBlob }}">
                        <td data-sort-val="{{ $v['date'] ?? date('Y-m-d') }}" style="font-size: 13px; font-weight: 700; color: #1e3a8a;">
                            <div>In: {{ $v['arrival_time'] ?? 'N/A' }}</div>
                            @if(!empty($v['house_arrived_at']))
                                <div style="font-size: 11px; color: #0284c7; font-weight: 600;">House: {{ $v['house_arrived_at'] }}</div>
                            @endif
                            @if(!empty($v['left_house_at']))
                                <div style="font-size: 11px; color: #d97706; font-weight: 600;">Left House: {{ $v['left_house_at'] }}</div>
                            @endif
                            @if(!empty($v['exit_time']))
                                <div style="font-size: 11px; color: #475569; font-weight: 700;">Out: {{ $v['exit_time'] }}</div>
                            @endif
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
                            @if(($v['status'] ?? '') === 'Exited')
                                <div style="display: flex; align-items: center; gap: 6px; color: #475569; font-weight: 700; font-size: 13px;">
                                    <span style="width: 8px; height: 8px; background: #64748b; border-radius: 50%;"></span>
                                    Exited
                                </div>
                                @if(!empty($v['exit_time']))
                                    <div style="font-size: 11px; color: #64748b; font-weight: 600; margin-top: 2px;">{{ $v['exit_time'] }}</div>
                                @endif
                            @else
                                <div style="display: flex; align-items: center; gap: 6px; color: #10b981; font-weight: 700; font-size: 13px;">
                                    <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%;"></span>
                                    Entered
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    @endforelse

                    <tr id="emptyHistoryRow" style="display: none;">
                        <td colspan="8" style="text-align: center; padding: 40px 20px; color: #94a3b8; font-weight: 600;">
                            No visitor entries found matching the selected filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        @include('partials.admin-client-pagination', ['prefix' => 'guardHistoryTable', 'defaultPerPage' => 15])
    </div>
</div>

<!-- Official Visitor Access PDF Report Modal -->
<div id="visitorReportModal" class="bill-modal" style="display: none; position: fixed; inset: 0; align-items: center; justify-content: center; z-index: 2100; background: rgba(15,23,42,0.7); backdrop-filter: blur(8px); padding: 20px;">
    <div class="bill-modal-content" style="max-width: 1100px; width: 95%; max-height: 94vh; overflow: hidden; padding: 20px 24px; border-radius: 20px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); display: flex; flex-direction: column;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Status</span>
                    <select id="reportVisStatusFilter" class="filter-select" onchange="reloadVisitorReportPdf()" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer;">
                        <option value="all">All Statuses</option>
                        <option value="entered">Entered Gate</option>
                        <option value="exited">Exited Gate</option>
                        <option value="approved">Approved</option>
                        <option value="pending">Pending Approval</option>
                        <option value="rejected">Rejected / Expired</option>
                    </select>
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Date Range</span>
                    <select id="reportVisDateFilter" class="filter-select" onchange="reloadVisitorReportPdf()" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer;">
                        <option value="all">All Time</option>
                        <option value="today">Today</option>
                        <option value="this_week">This Week</option>
                        <option value="this_month">This Month</option>
                        <option value="last_month">Last Month</option>
                    </select>
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Sort By</span>
                    <select id="reportVisSortFilter" class="filter-select" onchange="reloadVisitorReportPdf()" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer;">
                        <option value="date_desc" selected>Date: Newest First</option>
                        <option value="date_asc">Date: Oldest First</option>
                        <option value="id_asc">Request ID: Ascending</option>
                        <option value="id_desc">Request ID: Descending</option>
                        <option value="visitor_asc">Visitor Name: A to Z</option>
                        <option value="host_asc">Host Name: A to Z</option>
                    </select>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <a id="visitorReportDownloadBtn" href="{{ route('guard.visitors.report.pdf') }}?download=1" class="btn btn-primary" style="background: #0284c7; border: none; padding: 8px 16px; font-size: 12px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; color: #fff;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download PDF
                </a>
                <button type="button" class="btn btn-outline" onclick="printVisitorReportPdfFrame()" style="background: #fff; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #334155;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
                    Print
                </button>
                <button type="button" class="btn btn-outline" onclick="closeVisitorReportModal()" style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 600; border-radius: 8px; cursor: pointer;">
                    Close
                </button>
            </div>
        </div>
        <div style="flex: 1; min-height: 0;">
            <iframe id="visitorReportPdfFrame" src="about:blank" style="width: 100%; height: 75vh; border: 1px solid #e2e8f0; border-radius: 10px; background: #525659;"></iframe>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let guardHistoryPagination = null;

    document.addEventListener('DOMContentLoaded', function() {
        if (window.initTablePagination) {
            guardHistoryPagination = window.initTablePagination({
                rowSelector: '.visitor-row',
                matchClass: 'pagination-match',
                infoEl: '#guardHistoryTablePageInfo',
                prevEl: '#guardHistoryTablePagePrev',
                nextEl: '#guardHistoryTablePageNext',
                pageSizeEl: '#guardHistoryTablePageSize',
                numbersEl: '#guardHistoryTablePageNumbers',
                table: '#guardHistoryTable',
                perPage: 15
            });
        }
        filterRows();
    });

    function filterRows() {
        const dateFilter = document.getElementById('dateFilter');
        const searchEl = document.getElementById('historySearch');
        const statusEl = document.getElementById('historyStatusFilter');
        const emptyRow = document.getElementById('emptyHistoryRow');

        const selectedDate = dateFilter ? dateFilter.value : '';
        const search = (searchEl ? searchEl.value : '').toLowerCase().trim();
        const status = (statusEl ? statusEl.value : 'all').toLowerCase();

        let visibleCount = 0;
        document.querySelectorAll('.visitor-row').forEach(row => {
            const rowDate = row.getAttribute('data-date') || '';
            const rowStatus = (row.getAttribute('data-status') || '').toLowerCase();
            const rowSearch = row.getAttribute('data-search') || '';

            const matchDate = selectedDate === '' || rowDate === selectedDate;
            const matchStatus = status === 'all' || rowStatus === status;
            const matchSearch = !search || rowSearch.includes(search);

            if (matchDate && matchStatus && matchSearch) {
                row.classList.add('pagination-match');
                visibleCount++;
            } else {
                row.classList.remove('pagination-match');
                row.style.display = 'none';
            }
        });

        if (emptyRow) {
            emptyRow.style.display = (visibleCount === 0) ? '' : 'none';
        }
        document.body.setAttribute('data-print-date', selectedDate || 'All Dates');

        if (guardHistoryPagination) {
            guardHistoryPagination.render();
        }
    }

    function clearDateFilter() {
        const dateFilter = document.getElementById('dateFilter');
        const searchEl = document.getElementById('historySearch');
        const statusEl = document.getElementById('historyStatusFilter');
        if (dateFilter) dateFilter.value = '';
        if (searchEl) searchEl.value = '';
        if (statusEl) statusEl.value = 'all';
        filterRows();
    }

    function openVisitorReportModal() {
        const curStatus = document.getElementById('historyStatusFilter')?.value || 'all';
        const statusSelect = document.getElementById('reportVisStatusFilter');
        if (statusSelect) {
            statusSelect.value = curStatus;
        }
        const dateFilterVal = document.getElementById('dateFilter')?.value || '';
        const dateSelect = document.getElementById('reportVisDateFilter');
        if (dateSelect && dateFilterVal === new Date().toISOString().split('T')[0]) {
            dateSelect.value = 'today';
        }
        reloadVisitorReportPdf();
        const modal = document.getElementById('visitorReportModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeVisitorReportModal() {
        const modal = document.getElementById('visitorReportModal');
        if (modal) modal.style.display = 'none';
    }

    function reloadVisitorReportPdf() {
        const status = document.getElementById('reportVisStatusFilter')?.value || 'all';
        const dateRange = document.getElementById('reportVisDateFilter')?.value || 'all';
        const sort = document.getElementById('reportVisSortFilter')?.value || 'date_desc';
        const base = "{{ route('guard.visitors.report.pdf') }}";
        const query = `?status=${encodeURIComponent(status)}&date_range=${encodeURIComponent(dateRange)}&sort=${encodeURIComponent(sort)}`;

        const frame = document.getElementById('visitorReportPdfFrame');
        if (frame) frame.src = base + query;

        const dlBtn = document.getElementById('visitorReportDownloadBtn');
        if (dlBtn) dlBtn.href = base + query + '&download=1';
    }

    function printVisitorReportPdfFrame() {
        const frame = document.getElementById('visitorReportPdfFrame');
        if (frame && frame.contentWindow) {
            frame.contentWindow.focus();
            frame.contentWindow.print();
        }
    }
</script>
@endsection
