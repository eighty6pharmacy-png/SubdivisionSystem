@extends('layouts.admin')

@section('title', 'Visitor Access Approvals')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">

<div class="bill-container fade-in">
    <!-- Loading Overlay -->
    <div id="loadingOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.8); z-index:9999; justify-content:center; align-items:center; flex-direction:column;">
        <div class="spinner" style="width:40px; height:40px; border:4px solid #e2e8f0; border-top-color:#3b82f6; border-radius:50%; animation:spin 1s linear infinite;"></div>
        <div style="margin-top:16px; font-weight:700; color:#1e293b;">Processing Visitor, Please Wait...</div>
        <style>@keyframes spin { to { transform: rotate(360deg); } }</style>
    </div>

    <div class="bill-header">
        <div class="bill-title">
            <h1>Visitor Access Approvals</h1>
            <p>Review resident visitor requests, generate verification PINs, and forward access clearance to Gate Security.</p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-outline" style="background: #fff; border-color: #0284c7; color: #0284c7; font-weight: 700; display: inline-flex; align-items: center; padding: 10px 18px;" onclick="openVisitorReportModal()">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 6px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Generate Report
            </button>
            <button id="exportPdfBtn" class="btn btn-outline" style="font-size: 13px; padding: 10px 18px; background: #fff;">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 6px;"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Quick Export
            </button>
        </div>
    </div>

    <!-- Stats Summary Row -->
    <div class="responsive-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px;">
        <div onclick="filterVisitorsByStat('pending')" style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid #f59e0b; cursor: pointer;">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Pending Admin Approval</div>
            <div id="statPendingCount" style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">0</div>
        </div>
        <div onclick="filterVisitorsByStat('approved')" style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid #10b981; cursor: pointer;">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Approved &amp; Forwarded</div>
            <div id="statApprovedCount" style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">0</div>
        </div>
        <div onclick="filterVisitorsByStat('entered')" style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid #6366f1; cursor: pointer;">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Entered Gate</div>
            <div id="statEnteredCount" style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">0</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar" style="background: #f8fafc; padding: 16px 20px; border-radius: 14px; border: 1px solid #e2e8f0; display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 20px;">
        <div style="flex: 1; min-width: 240px;">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; display: block;">Search Visitors</label>
            <input type="text" id="visitorSearch" class="filter-select" placeholder="Search ID, Visitor, Host, Purpose, or PIN..." style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;" oninput="applyVisitorFilters()">
        </div>
        <div style="width: 190px;">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; display: block;">Approval Status</label>
            <select id="visitorStatusFilter" class="filter-select" onchange="applyVisitorFilters()" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #fff;">
                <option value="all">All Statuses</option>
                <option value="pending">Pending Approval</option>
                <option value="approved">Approved</option>
                <option value="entered">Entered Gate</option>
                <option value="exited">Exited Gate</option>
                <option value="rejected">Rejected / Expired</option>
            </select>
        </div>
        <div style="width: 170px;">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; display: block;">Date Range</label>
            <select id="visitorDateFilter" class="filter-select" onchange="applyVisitorFilters()" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #fff;">
                <option value="all">All Time</option>
                <option value="today">Today</option>
                <option value="this_week">This Week</option>
                <option value="this_month">This Month</option>
                <option value="last_month">Last Month</option>
            </select>
        </div>
        <div>
            <button type="button" class="btn btn-outline" onclick="resetVisitorFilters()" style="padding: 8px 14px; font-size: 13px; font-weight: 600; border-radius: 8px; background: #fff;">Clear</button>
        </div>
    </div>

    <!-- Visitor Approvals Table -->
    <div class="analytic-card" style="padding: 0; overflow: hidden; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <div style="padding: 18px 24px; border-bottom: 1px solid var(--bill-border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Resident Visitor Access Queue</h3>
        </div>
        <div class="bill-table-container">
            <table class="bill-table" id="adminVisitorTable">
                <thead>
                    <tr>
                        <th data-sort="number">Request ID</th>
                        <th data-sort="text">Resident / Host</th>
                        <th data-sort="text">Visitor Details</th>
                        <th data-sort="date">Visit Date</th>
                        <th data-sort="text">Validated By (Guard)</th>
                        <th data-sort="text">Approval Status</th>
                        <th data-sort="text">Verification Code</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="adminVisitorTableBody">
                    <!-- Populated by JS -->
                </tbody>
            </table>
        </div>
        @include('partials.admin-client-pagination', ['prefix' => 'visitorTable', 'defaultPerPage' => 15])
    </div>
</div>

<script>
    let visitorPagination = null;

    document.addEventListener('DOMContentLoaded', function() {
        if (window.initTablePagination) {
            visitorPagination = window.initTablePagination({
                rowSelector: '.visitor-req-row',
                matchClass: 'pagination-match',
                infoEl: '#visitorTablePageInfo',
                prevEl: '#visitorTablePagePrev',
                nextEl: '#visitorTablePageNext',
                pageSizeEl: '#visitorTablePageSize',
                numbersEl: '#visitorTablePageNumbers',
                table: '#adminVisitorTable',
                perPage: 15
            });
        }
        renderAdminVisitorTable();
    });

    function filterVisitorsByStat(status) {
        const sel = document.getElementById('visitorStatusFilter');
        if (sel) {
            sel.value = sel.value === status ? 'all' : status;
            applyVisitorFilters();
        }
    }

    function resetVisitorFilters() {
        document.getElementById('visitorSearch').value = '';
        document.getElementById('visitorStatusFilter').value = 'all';
        document.getElementById('visitorDateFilter').value = 'all';
        applyVisitorFilters();
    }

    function matchesVisitorDate(dateStr, filter) {
        if (filter === 'all' || !dateStr) return true;
        const d = new Date(dateStr + 'T00:00:00');
        if (isNaN(d.getTime())) return true;
        const now = new Date();
        if (filter === 'today') return d.toDateString() === now.toDateString();
        if (filter === 'this_week') {
            const start = new Date(now);
            start.setHours(0, 0, 0, 0);
            start.setDate(now.getDate() - now.getDay());
            const end = new Date(start);
            end.setDate(start.getDate() + 6);
            end.setHours(23, 59, 59, 999);
            return d >= start && d <= end;
        }
        if (filter === 'this_month') {
            return d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth();
        }
        if (filter === 'last_month') {
            const last = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            return d.getFullYear() === last.getFullYear() && d.getMonth() === last.getMonth();
        }
        return true;
    }

    function applyVisitorFilters() {
        const search = (document.getElementById('visitorSearch')?.value || '').toLowerCase().trim();
        const status = document.getElementById('visitorStatusFilter')?.value || 'all';
        const dateFilter = document.getElementById('visitorDateFilter')?.value || 'all';

        document.querySelectorAll('.visitor-req-row').forEach(row => {
            const text = (row.dataset.search || '').toLowerCase();
            const rowStatus = row.dataset.statusCat || '';
            const rowDate = row.dataset.date || '';

            const matchSearch = !search || text.includes(search);
            const matchStatus = status === 'all' || rowStatus === status;
            const matchDate = matchesVisitorDate(rowDate, dateFilter);

            if (matchSearch && matchStatus && matchDate) {
                row.classList.add('pagination-match');
            } else {
                row.classList.remove('pagination-match');
            }
        });

        if (visitorPagination) {
            visitorPagination.render();
        }
    }

    function renderAdminVisitorTable() {
        fetch('/api/visitors')
        .then(res => res.json())
        .then(visitors => {
            const tbody = document.getElementById('adminVisitorTableBody');
            if (!tbody) return;

            tbody.innerHTML = '';

            let pendingCount = 0;
            let approvedCount = 0;
            let enteredCount = 0;

            visitors.forEach(v => {
                const tr = document.createElement('tr');
                tr.className = 'visitor-req-row pagination-match';
                tr.dataset.id = v.db_id;
                tr.dataset.date = v.date || '';
                tr.dataset.search = `${v.id} ${v.host || ''} ${v.visitor || ''} ${v.purpose || ''} ${v.pin || ''} ${v.contact_number || ''}`;

                let statusBadge = '';
                let codeDisplay = '';
                let actionHtml = '';

                if (v.status === 'Pending' || v.status === 'Awaiting Admin Approval') {
                    pendingCount++;
                    tr.dataset.statusCat = 'pending';
                    statusBadge = `<span class="badge status-badge" style="background: #fef3c7; color: #b45309; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 11px;">AWAITING ADMIN APPROVAL</span>`;
                    codeDisplay = `<span style="color: #94a3b8; font-size: 13px; font-weight: 500;">Pending Code</span>`;
                    actionHtml = `
                        <div style="display: flex; gap: 8px;" class="action-cell">
                            <button class="btn btn-primary" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; background: #059669;" onclick="approveVisitorRequest('${v.db_id}', '${(v.host || 'Resident').replace(/'/g, "\\'")}', '${v.visitor.replace(/'/g, "\\'")}')">
                                ✓ Approve & Gen Code
                            </button>
                            <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; color: #ef4444; border-color: #fee2e2;" onclick="rejectVisitorRequest('${v.db_id}', '${(v.visitor || 'this visitor').replace(/'/g, "\\'")}')">
                                ✕ Reject
                            </button>
                        </div>
                    `;
                } else if (v.status === 'Approved') {
                    approvedCount++;
                    tr.dataset.statusCat = 'approved';
                    statusBadge = `<span class="badge" style="background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 11px;">APPROVED & FORWARDED TO GUARD</span>`;
                    codeDisplay = `<span style="color:#059669; font-family:monospace; font-size:16px; font-weight:800; letter-spacing:2px;">${v.pin || '123456'}</span>`;
                    actionHtml = `<span style="font-size: 12px; color: #10b981; font-weight: 700;">Forwarded to Gate ✓</span>`;
                } else if (v.status === 'Entered') {
                    enteredCount++;
                    tr.dataset.statusCat = 'entered';
                    statusBadge = `<span class="badge" style="background: #e0e7ff; color: #4f46e5; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 11px;">ENTERED GATE</span>`;
                    codeDisplay = `<span style="color:#4f46e5; font-family:monospace; font-size:16px; font-weight:800; letter-spacing:2px;">${v.pin || 'USED'}</span>`;
                    actionHtml = `<span style="font-size: 12px; color: #4f46e5; font-weight: 700;">Entered Gate ✓</span><br><span style="font-size:10px; color:#64748b; font-weight:700;">${v.arrival_time || ''}</span>`;
                } else if (v.status === 'Exited') {
                    tr.dataset.statusCat = 'exited';
                    statusBadge = `<span class="badge" style="background: #f1f5f9; color: #475569; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 11px;">EXITED GATE</span>`;
                    codeDisplay = `<span style="color:#64748b; font-family:monospace; font-size:14px; font-weight:700;">${v.pin || 'USED'}</span>`;
                    actionHtml = `<span style="font-size: 12px; color: #64748b; font-weight: 700;">Exited Gate</span>${v.arrival_time ? `<br><span style="font-size:10px; color:#10b981; font-weight:700;">In: ${v.arrival_time}</span>` : ''}${v.left_house_at ? `<br><span style="font-size:10px; color:#d97706; font-weight:700;">Left House: ${v.left_house_at}</span>` : ''}${v.exit_time ? `<br><span style="font-size:10px; color:#64748b; font-weight:700;">Out: ${v.exit_time}</span>` : ''}`;
                } else if (v.status === 'Expired') {
                    tr.dataset.statusCat = 'rejected';
                    statusBadge = `<span class="badge" style="background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 11px;">EXPIRED</span>`;
                    codeDisplay = `<span style="color:#94a3b8; font-size:13px;">-</span>`;
                    actionHtml = `<span style="font-size: 12px; color: #64748b; font-weight: 700;">Expired</span>`;
                } else {
                    tr.dataset.statusCat = 'rejected';
                    statusBadge = `<span class="badge" style="background: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 11px;">REJECTED</span>`;
                    codeDisplay = `<span style="color:#94a3b8; font-size:13px;">-</span>`;
                    actionHtml = `<span style="font-size: 12px; color: #ef4444; font-weight: 700;">Rejected</span>`;
                }

                tr.innerHTML = `
                    <td data-sort-val="${v.id}" style="font-family: monospace; font-weight: 700; color: #64748b;">${v.id}</td>
                    <td>
                        <div style="font-weight: 700; color: #0f172a;">${v.host || 'Resident'}</div>
                        <div style="font-size: 12px; color: var(--bill-primary);">Block ${v.block || 1}, Lot ${v.lot || 1}</div>
                    </td>
                    <td>
                        <div style="font-weight: 700; color: #0f172a;">${v.visitor}</div>
                        <div style="font-size: 12px; color: #64748b;">${v.purpose}</div>
                        ${v.contact_number ? `<div style="font-size: 11px; color: #0284c7; font-weight: 600; margin-top: 2px;">📞 ${v.contact_number}</div>` : ''}
                    </td>
                    <td data-sort-val="${v.date || ''}" style="font-size: 13px; color: #64748b; font-weight: 600;">${v.validity || 'Today'}</td>
                    <td style="font-size: 12px; color: #0f172a; font-weight: 600;">${v.guard_name || (v.status === 'Entered' ? 'Gate Guard' : 'System / Pending')}</td>
                    <td>${statusBadge}</td>
                    <td>
                        ${codeDisplay}
                        ${v.access_token && (v.status === 'Approved' || v.status === 'Entered') ? `<div style="margin-top: 4px;"><a href="/v/${v.access_token}" target="_blank" style="font-size: 10px; color: #0284c7; text-decoration: none; font-weight: 700;">Direct Pass ↗</a></div>` : ''}
                    </td>
                    <td>${actionHtml}</td>
                `;

                tbody.appendChild(tr);
            });

            const pendingEl = document.getElementById('statPendingCount');
            const approvedEl = document.getElementById('statApprovedCount');
            const enteredEl = document.getElementById('statEnteredCount');
            if (pendingEl) pendingEl.innerText = pendingCount;
            if (approvedEl) approvedEl.innerText = approvedCount;
            if (enteredEl) enteredEl.innerText = enteredCount;

            applyVisitorFilters();
        });
    }

    function approveVisitorRequest(reqId, hostName, visitorName) {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) overlay.style.display = 'flex';
        
        fetch('/api/visitors/' + reqId + '/approve', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' },
        }).then(res => res.json()).then(data => {
            if(data.success) {
                renderAdminVisitorTable();
                
                if (window.pushSystemNotification) {
                    window.pushSystemNotification("Visitor Approved", `PIN generated for ${visitorName} and dispatched via SMS & gate pass.`, "Just now", true);
                }

                const pinSection = data.pin ? `
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 16px; margin: 12px 0; display: inline-block;">
                        <span style="font-size: 11px; font-weight: 700; color: #166534; text-transform: uppercase; letter-spacing: 0.5px; display: block;">Gate Clearance PIN</span>
                        <span style="font-size: 24px; font-weight: 800; color: #15803d; letter-spacing: 4px; font-family: monospace;">${data.pin}</span>
                    </div>
                ` : '';

                Swal.fire({
                    icon: 'success',
                    title: 'Visitor Approved!',
                    html: `
                        <div style="font-size: 14px; color: #334155; line-height: 1.5;">
                            ${pinSection}
                            <p style="margin: 6px 0 2px; font-weight: 600; color: #0f172a;">${visitorName}</p>
                            <p style="font-size: 13px; color: #64748b; margin-bottom: 12px;">Host: ${hostName || 'Resident'}</p>
                            <div style="background: #f8fafc; border-radius: 8px; padding: 10px 14px; text-align: left; font-size: 13px; color: #475569; border: 1px solid #e2e8f0;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                    <span>📱</span>
                                    <span><strong>SMS Dispatched:</strong> Gate Clearance PIN and location sent to resident and visitor.</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span>🛡️</span>
                                    <span><strong>Gate Access:</strong> Clearance forwarded to Security Guard Portal.</span>
                                </div>
                            </div>
                        </div>
                    `,
                    confirmButtonText: 'Done',
                    confirmButtonColor: '#059669'
                });
            } else {
                Swal.fire('Error approving visitor: ' + data.message);
            }
        }).catch(err => {
            console.error('Error:', err);
            Swal.fire('An error occurred during approval.');
        }).finally(() => {
            if (overlay) overlay.style.display = 'none';
        });
    }

    function rejectVisitorRequest(reqId, visitorName = '') {
        const label = visitorName ? `visitor request for <strong>${visitorName}</strong>` : `visitor request #${reqId}`;
        Swal.fire({
            icon: 'warning',
            title: 'Reject Visitor Request?',
            html: `Are you sure you want to reject the ${label}? The resident will be notified.`,
            showCancelButton: true,
            confirmButtonText: 'Yes, Reject',
            confirmButtonColor: '#ef4444',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
        }).then((result) => {
            if (!result.isConfirmed) return;

            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.style.display = 'flex';

            fetch('/api/visitors/' + reqId + '/reject', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    renderAdminVisitorTable();
                    Swal.fire({
                        icon: 'success',
                        title: 'Visitor Rejected',
                        text: 'The visitor request has been rejected and the resident has been notified.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({ icon: 'error', text: 'Error rejecting visitor: ' + (data.message || 'Unknown error') });
                }
            }).catch(err => {
                console.error('Error:', err);
                Swal.fire({ icon: 'error', text: 'An error occurred during rejection.' });
            }).finally(() => {
                if (overlay) overlay.style.display = 'none';
            });
        });
    }

    document.getElementById('exportPdfBtn').addEventListener('click', function() {
        const doc = new jsPDF('landscape');
        
        const dateStr = new Date().toISOString().split('T')[0];
        
        doc.setFontSize(16);
        doc.text("Admin Visitor Approvals Log - " + dateStr, 14, 15);
        
        const headers = [['Request ID', 'Host', 'Visitor', 'Visit Date', 'Validated By', 'Status', 'Code']];
        const data = [];
        
        document.querySelectorAll('.visitor-req-row').forEach(row => {
            const rowData = [];
            row.querySelectorAll('td').forEach((td, index) => {
                // skip Actions column (index 7)
                if (index < 7) {
                    rowData.push(td.innerText.trim().replace(/\n/g, ' - '));
                }
            });
            data.push(rowData);
        });
        
        doc.autoTable({
            head: headers,
            body: data,
            startY: 25,
            theme: 'grid',
            headStyles: { fillColor: [5, 150, 105] },
            styles: { fontSize: 9, cellPadding: 3 }
        });
        
        doc.save('Admin-Visitor-Log-' + dateStr + '.pdf');
    });

    function openVisitorReportModal() {
        const curStatus = document.getElementById('visitorStatusFilter')?.value || 'all';
        const curDate = document.getElementById('visitorDateFilter')?.value || 'all';
        if (document.getElementById('reportVisStatusFilter')) {
            document.getElementById('reportVisStatusFilter').value = curStatus;
        }
        if (document.getElementById('reportVisDateFilter')) {
            document.getElementById('reportVisDateFilter').value = curDate;
        }
        reloadVisitorReportPdf();
        document.getElementById('visitorReportModal').style.display = 'flex';
    }

    function closeVisitorReportModal() {
        document.getElementById('visitorReportModal').style.display = 'none';
    }

    function reloadVisitorReportPdf() {
        const status = document.getElementById('reportVisStatusFilter')?.value || 'all';
        const dateRange = document.getElementById('reportVisDateFilter')?.value || 'all';
        const sort = document.getElementById('reportVisSortFilter')?.value || 'date_desc';
        const base = "{{ route('admin.visitors.report.pdf') }}";
        const qs = `?status=${encodeURIComponent(status)}&date_range=${encodeURIComponent(dateRange)}&sort=${encodeURIComponent(sort)}`;

        const frame = document.getElementById('visitorReportPdfFrame');
        if (frame) frame.src = base + qs;
        const dlBtn = document.getElementById('visitorReportDownloadBtn');
        if (dlBtn) dlBtn.href = base + qs + '&download=1';
    }

    function printVisitorReportPdfFrame() {
        const frame = document.getElementById('visitorReportPdfFrame');
        if (frame && frame.contentWindow) {
            frame.contentWindow.focus();
            frame.contentWindow.print();
        }
    }
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.5.3/jspdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.6/jspdf.plugin.autotable.min.js"></script>

<!-- Official Visitor Access PDF Report Modal -->
<div id="visitorReportModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 2100; background: rgba(15,23,42,0.7); backdrop-filter: blur(8px); padding: 20px;">
    <div class="bill-modal-content" style="max-width: 1100px; width: 95%; max-height: 94vh; overflow: hidden; padding: 20px 24px; border-radius: 20px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); display: flex; flex-direction: column;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Status</span>
                    <select id="reportVisStatusFilter" class="filter-select" onchange="reloadVisitorReportPdf()" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer;">
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending Approval</option>
                        <option value="approved">Approved</option>
                        <option value="entered">Entered Gate</option>
                        <option value="exited">Exited Gate</option>
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

            <div style="display: flex; align-items: center; gap: 8px;">
                <a id="visitorReportDownloadBtn" href="{{ route('admin.visitors.report.pdf') }}?download=1" class="btn btn-primary" style="background: #0284c7; border: none; padding: 8px 16px; font-size: 12px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; color: #fff;">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download PDF
                </a>
                <button type="button" class="btn btn-outline" onclick="printVisitorReportPdfFrame()" style="background: #fff; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #334155;">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print
                </button>
                <button type="button" class="btn btn-outline" onclick="closeVisitorReportModal()" style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 600; border-radius: 8px; cursor: pointer;">
                    ✕ Close
                </button>
            </div>
        </div>

        <div style="flex: 1; min-height: 0; width: 100%;">
            <iframe id="visitorReportPdfFrame" src="about:blank" style="width: 100%; height: 75vh; border: 1px solid #e2e8f0; border-radius: 10px; background: #525659;"></iframe>
        </div>
    </div>
</div>
@endsection
