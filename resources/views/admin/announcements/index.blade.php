@extends('layouts.admin')

@section('title', 'Community Announcements')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}?v={{ filemtime(public_path('css/admin-finance.css')) }}">
<link rel="stylesheet" href="{{ asset('css/admin-announcements.css') }}">

<div class="ann-container">
    <!-- Header -->
    <div class="ann-header">
        <div class="ann-title">
            <h1>Community Announcements</h1>
            <p>Broadcast important news, updates, and emergency alerts to residents.</p>
        </div>
        <div class="ann-header-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-outline" style="background: #fff; border-color: #0284c7; color: #0284c7; font-weight: 700; display: inline-flex; align-items: center; padding: 10px 16px;" onclick="openAnnouncementReportModal()">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 6px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Generate Report
            </button>

            <select id="annDateFilter" style="width: 150px; padding: 11px 14px; border-radius: 12px; border: 1px solid var(--ann-border); font-size: 13px; font-weight: 600; cursor: pointer; background: #fff;">
                <option value="all">All Time</option>
                <option value="today">Today</option>
                <option value="this_week">This Week</option>
                <option value="this_month">This Month</option>
                <option value="last_month">Last Month</option>
            </select>
            <div class="ann-search-wrapper" style="position: relative;">
                <input type="text" id="annSearch" placeholder="Search announcements..." 
                       style="width: 100%; padding: 11px 16px 11px 40px; border-radius: 12px; border: 1px solid var(--ann-border); font-size: 13px; outline: none; transition: all 0.2s; box-shadow: var(--ann-shadow);">
                <svg width="18" height="18" fill="none" stroke="var(--ann-text-sub)" stroke-width="2" viewBox="0 0 24 24" 
                     style="position: absolute; left: 13px; top: 50%; transform: translateY(-50%);">
                    <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <button class="btn btn-primary" onclick="openModal()">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
                Post New
            </button>
        </div>
    </div>

    <!-- Stats & Filters -->
    <div style="display: flex; flex-direction: column; gap: 24px; margin-bottom: 32px;">
        <div class="ann-stats responsive-grid grid-3">
            <div class="ann-stat-card active-stat" data-status="all" style="cursor: pointer; border-color: var(--ann-primary);">
                <span class="ann-stat-label">Total Active</span>
                <div class="ann-stat-value">
                    <span id="stat-total">{{ collect($announcements)->filter(function($a) { return strtolower($a['status'] ?? '') !== 'archived'; })->count() }}</span>
                </div>
            </div>
            <div class="ann-stat-card" data-status="published" style="cursor: pointer;">
                <span class="ann-stat-label">Published</span>
                <div class="ann-stat-value" style="color: var(--ann-success);">
                    <span id="stat-published">{{ collect($announcements)->filter(function($a) { return strtolower($a['status']) === 'published'; })->count() }}</span>
                </div>
            </div>
            <div class="ann-stat-card" data-status="archived" style="cursor: pointer;">
                <span class="ann-stat-label">Archived</span>
                <div class="ann-stat-value" style="color: var(--ann-danger);">
                    <span id="stat-archived">{{ collect($announcements)->filter(function($a) { return strtolower($a['status']) === 'archived'; })->count() }}</span>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 12px; overflow-x: auto; padding-bottom: 8px; scrollbar-width: none;">
            <button class="btn btn-outline active-filter" data-cat="all">All Events</button>
        </div>
    </div>

    <!-- Data Table View (Default) -->
    <div id="annTableViewContainer">
        <div class="analytic-card" style="padding: 0; overflow: hidden; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <div class="bill-table-container">
                <table class="bill-table" id="announcementsTable">
                    <thead>
                        <tr>
                            <th data-sort="number" style="width: 90px;">ID</th>
                            <th data-sort="text">Announcement</th>
                            <th data-sort="text" style="width: 110px;">Category</th>
                            <th data-sort="text" style="width: 140px;">Author</th>
                            <th data-sort="date" style="width: 135px;">Publish Date</th>
                            <th data-sort="text" style="width: 115px;">Status</th>
                            <th style="width: 120px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="annTableBody">
                        @forelse($announcements as $ann)
                        @php
                            $isArchived = strtolower($ann['status'] ?? '') === 'archived';
                        @endphp
                        <tr class="ann-row pagination-match"
                            data-id="{{ $ann['id'] }}"
                            data-title="{{ strtolower(($ann['title'] ?? '') . ' ' . ($ann['content'] ?? '') . ' ' . ($ann['author'] ?? '')) }}"
                            data-cat="{{ $ann['cat'] ?? 'Event' }}"
                            data-status-val="{{ strtolower($ann['status'] ?? 'published') }}"
                            data-date="{{ date('Y-m-d', strtotime($ann['date'])) }}"
                            data-archived="{{ $isArchived ? 'true' : 'false' }}">
                            <td data-sort-val="{{ $ann['id'] }}" style="font-family: monospace; font-weight: 700; color: #64748b;">
                                ANN-{{ str_pad($ann['id'], 4, '0', STR_PAD_LEFT) }}
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #0f172a; margin-bottom: 2px;">{{ $ann['title'] }}</div>
                                <div style="font-size: 12px; color: #64748b; max-width: 420px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $ann['content'] }}</div>
                            </td>
                            <td>
                                <span class="ann-category cat-{{ strtolower($ann['cat'] ?? 'event') }}">{{ $ann['cat'] ?? 'Event' }}</span>
                            </td>
                            <td style="font-weight: 600; color: #334155;">{{ $ann['author'] ?? 'Admin' }}</td>
                            <td data-sort-val="{{ date('Y-m-d', strtotime($ann['date'])) }}" style="font-size: 13px; color: #475569; font-weight: 600;">
                                {{ date('M d, Y', strtotime($ann['date'])) }}
                            </td>
                            <td class="ann-status-cell">
                                @if($isArchived)
                                    <span class="badge" style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;">Archived</span>
                                @else
                                    <span class="badge" style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;">{{ ucfirst($ann['status'] ?? 'Published') }}</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <div style="display: inline-flex; gap: 6px; align-items: center;">
                                    <button type="button" class="ann-btn-icon btn-row-restore" title="Restore" style="display: {{ $isArchived ? 'flex' : 'none' }}; color: var(--ann-success);" onclick="restoreAnnouncementById('{{ $ann['id'] }}')">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </button>
                                    <button type="button" class="ann-btn-icon btn-row-archive" title="Archive" style="display: {{ $isArchived ? 'none' : 'flex' }}; color: var(--ann-text-sub);" onclick="archiveAnnouncementById('{{ $ann['id'] }}')">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">No announcements found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.admin-client-pagination', ['prefix' => 'annTable', 'defaultPerPage' => 15])
        </div>
    </div>
</div>

<!-- Simple Create Modal Mockup -->
<div id="annModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; animation: fadeIn 0.3s ease;">
    <div style="background: white; width: 100%; max-width: 600px; padding: 40px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); position: relative;">
        <button onclick="closeModal()" style="position: absolute; right: 24px; top: 24px; background: none; border: none; color: var(--ann-text-sub); cursor: pointer;">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <h2 style="font-size: 24px; font-weight: 800; color: var(--ann-text-main); margin-bottom: 8px;">Create Announcement</h2>
        <p style="color: var(--ann-text-sub); margin-bottom: 32px;">Draft or publish a new broadcast for the community.</p>
        
        <form id="annForm" style="display: flex; flex-direction: column; gap: 20px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ann-text-main); margin-bottom: 8px;">Announcement Title</label>
                <input type="text" id="annTitle" placeholder="e.g. Schedule Maintenance" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--ann-border); font-size: 14px;" required>
            </div>
            <div style="margin-bottom: 16px; display: none;">
                <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ann-text-main); margin-bottom: 8px;">Category</label>
                <select id="annCat" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--ann-border); font-size: 14px; outline: none;">
                    <option value="Event" selected>Event</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ann-text-main); margin-bottom: 8px;">Content Message</label>
                <textarea id="annContent" rows="4" placeholder="Describe the details of the announcement..." style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--ann-border); font-size: 14px; font-family: inherit; resize: none;" required></textarea>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 12px;">
                <button type="button" id="btnPublish" class="btn btn-primary" style="flex: 1; padding: 14px;" onclick="createPost('published')">Publish Now</button>
            </div>
        </form>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/views/admin-announcements.css') }}">

<script>
    function openModal() {
        document.getElementById('annModal').style.display = 'flex';
    }
    function closeModal() {
        document.getElementById('annModal').style.display = 'none';
    }

    function openAnnouncementReportModal() {
        reloadAnnouncementReportPdf();
        document.getElementById('announcementReportModal').style.display = 'flex';
    }

    function closeAnnouncementReportModal() {
        document.getElementById('announcementReportModal').style.display = 'none';
    }

    function reloadAnnouncementReportPdf() {
        const status = document.getElementById('reportAnnStatusFilter')?.value || 'all';
        const dateRange = document.getElementById('reportAnnDateFilter')?.value || 'all';
        const sort = document.getElementById('reportAnnSortFilter')?.value || 'date_desc';
        const base = "{{ route('admin.announcements.report.pdf') }}";
        const qs = `?status=${encodeURIComponent(status)}&date_range=${encodeURIComponent(dateRange)}&sort=${encodeURIComponent(sort)}`;

        const frame = document.getElementById('announcementReportPdfFrame');
        if (frame) frame.src = base + qs;
        const dlBtn = document.getElementById('announcementReportDownloadBtn');
        if (dlBtn) dlBtn.href = base + qs + '&download=1';
    }

    function printAnnouncementReportPdfFrame() {
        const frame = document.getElementById('announcementReportPdfFrame');
        if (frame && frame.contentWindow) {
            frame.contentWindow.focus();
            frame.contentWindow.print();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('annSearch');
        const filterBtns = document.querySelectorAll('[data-cat]');
        const statCards = document.querySelectorAll('.ann-stat-card');

        let activeStatus = 'all';
        let activeCat = 'all';

        const annTablePagination = window.initTablePagination ? window.initTablePagination({
            rowSelector: '.ann-row',
            matchClass: 'pagination-match',
            infoEl: '#annTablePageInfo',
            prevEl: '#annTablePagePrev',
            nextEl: '#annTablePageNext',
            pageSizeEl: '#annTablePageSize',
            numbersEl: '#annTablePageNumbers',
            table: '#announcementsTable',
            perPage: 15
        }) : null;

        // Search
        searchInput.addEventListener('input', function() {
            filterCards();
        });

        // Category Filter
        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                filterBtns.forEach(b => b.classList.remove('active-filter'));
                this.classList.add('active-filter');
                activeCat = this.getAttribute('data-cat');
                filterCards();
            });
        });

        // Status Filter (Stat Cards)
        statCards.forEach(card => {
            card.addEventListener('click', function() {
                statCards.forEach(c => {
                    c.style.borderColor = 'var(--ann-border)';
                    c.classList.remove('active-stat');
                });
                this.style.borderColor = 'var(--ann-primary)';
                this.classList.add('active-stat');

                activeStatus = this.getAttribute('data-status');
                filterCards();
            });
        });

        window.archiveAnnouncementById = function(id) {
            Swal.fire({
                title: 'Archive Announcement?',
                text: 'Are you sure you want to archive this announcement?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, archive it',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (!result.isConfirmed) return;

                const row = document.querySelector(`.ann-row[data-id="${id}"]`);
                if (row) {
                    row.setAttribute('data-archived', 'true');
                    row.setAttribute('data-status-val', 'archived');
                    const cell = row.querySelector('.ann-status-cell');
                    if (cell) cell.innerHTML = '<span class="badge" style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;">Archived</span>';
                    const archBtn = row.querySelector('.btn-row-archive');
                    const restBtn = row.querySelector('.btn-row-restore');
                    if (archBtn) archBtn.style.display = 'none';
                    if (restBtn) restBtn.style.display = 'flex';
                }
                updateStats();
                filterCards();
                Swal.fire({
                    icon: 'success',
                    title: 'Archived!',
                    text: 'The announcement has been archived.',
                    timer: 1500,
                    showConfirmButton: false
                });
            });
        };

        window.restoreAnnouncementById = function(id) {
            const row = document.querySelector(`.ann-row[data-id="${id}"]`);
            if (row) {
                row.setAttribute('data-archived', 'false');
                row.setAttribute('data-status-val', 'published');
                const cell = row.querySelector('.ann-status-cell');
                if (cell) cell.innerHTML = '<span class="badge" style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;">Published</span>';
                const archBtn = row.querySelector('.btn-row-archive');
                const restBtn = row.querySelector('.btn-row-restore');
                if (archBtn) archBtn.style.display = 'flex';
                if (restBtn) restBtn.style.display = 'none';
            }
            updateStats();
            filterCards();
        };

        const dateSelect = document.getElementById('annDateFilter');

        function matchesDate(dateStr, filter) {
            if (filter === 'all') return true;
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
                const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                return d.getFullYear() === lastMonth.getFullYear() && d.getMonth() === lastMonth.getMonth();
            }
            return true;
        }

        function itemMatchesFilters(el, query, dateFilter) {
            const title = (el.getAttribute('data-title') || '').toLowerCase();
            const cat = el.getAttribute('data-cat');
            const status = el.getAttribute('data-status-val');
            const isArchived = el.getAttribute('data-archived') === 'true';

            const matchesSearch = title.includes(query);
            const matchesCat = activeCat === 'all' || cat === activeCat;
            const matchesDt = matchesDate(el.getAttribute('data-date'), dateFilter);

            let matchesStatus = false;
            if (activeStatus === 'archived') {
                matchesStatus = isArchived;
            } else if (isArchived) {
                matchesStatus = false;
            } else {
                matchesStatus = (activeStatus === 'all' || status === activeStatus);
            }

            return matchesSearch && matchesCat && matchesStatus && matchesDt;
        }

        window.filterCards = function() {
            const query = searchInput.value.toLowerCase();
            const dateFilter = dateSelect.value;

            // Filter Data Table rows
            document.querySelectorAll('.ann-row').forEach(row => {
                if (itemMatchesFilters(row, query, dateFilter)) {
                    row.classList.add('pagination-match');
                } else {
                    row.classList.remove('pagination-match');
                }
            });
            if (annTablePagination) {
                annTablePagination.render();
            }
        }

        dateSelect.addEventListener('change', () => filterCards());
        filterCards();

        window.updateStats = function() {
            const rows = document.querySelectorAll('.ann-row');
            const counts = {
                totalActive: 0,
                published: 0,
                archived: 0
            };

            rows.forEach(row => {
                const status = row.getAttribute('data-status-val');
                const isArchived = row.getAttribute('data-archived') === 'true';

                if (isArchived) {
                    counts.archived++;
                } else {
                    counts.totalActive++;
                    if (counts[status] !== undefined) counts[status]++;
                }
            });

            const totalEl = document.getElementById('stat-total');
            const pubEl = document.getElementById('stat-published');
            const archEl = document.getElementById('stat-archived');
            if (totalEl) totalEl.textContent = counts.totalActive;
            if (pubEl) pubEl.textContent = counts.published;
            if (archEl) archEl.textContent = counts.archived;
        };

        updateStats();

        let isPublishing = false;
        window.createPost = async function(status) {
            if (isPublishing) return;
            isPublishing = true;

            const btnPublish = document.getElementById('btnPublish');
            btnPublish.disabled = true; btnPublish.style.opacity = '0.5'; btnPublish.style.pointerEvents = 'none';
            btnPublish.textContent = 'Publishing...';

            const title = document.getElementById('annTitle').value;
            const cat = document.getElementById('annCat').value;
            const content = document.getElementById('annContent').value;

            if (!title || !content) {
                Swal.fire('Please fill in all required fields.');
                isPublishing = false;
                btnPublish.disabled = false; btnPublish.style.opacity = '1'; btnPublish.style.pointerEvents = 'auto';
                btnPublish.textContent = 'Publish Now';
                return;
            }

            try {
                const response = await fetch('/admin/announcements', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        title: title,
                        category: cat,
                        content: content
                    })
                });

                const result = await response.json().catch(() => ({}));
                if (response.ok && result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Published!',
                        text: 'Announcement posted! An email has been sent to the residents.',
                        confirmButtonText: 'OK',
                    });
                    window.location.reload();
                } else {
                    Swal.fire(result.message || 'Failed to post announcement.');
                    isPublishing = false;
                    btnPublish.disabled = false; btnPublish.style.opacity = '1'; btnPublish.style.pointerEvents = 'auto';
                    btnPublish.textContent = 'Publish Now';
                }
            } catch (error) {
                console.error(error);
                Swal.fire('An error occurred.');
                isPublishing = false;
                btnPublish.disabled = false; btnPublish.style.opacity = '1'; btnPublish.style.pointerEvents = 'auto';
                btnPublish.textContent = 'Publish Now';
            }
        }
    });
</script>

<!-- Official Announcement Management PDF Report Modal -->
<div id="announcementReportModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 2100; background: rgba(15,23,42,0.7); backdrop-filter: blur(8px); padding: 20px;">
    <div class="bill-modal-content" style="max-width: 1100px; width: 95%; max-height: 94vh; overflow: hidden; padding: 20px 24px; border-radius: 20px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); display: flex; flex-direction: column;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Status</span>
                    <select id="reportAnnStatusFilter" class="filter-select" onchange="reloadAnnouncementReportPdf()" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer;">
                        <option value="all">All Statuses</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Date Range</span>
                    <select id="reportAnnDateFilter" class="filter-select" onchange="reloadAnnouncementReportPdf()" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer;">
                        <option value="all">All Time</option>
                        <option value="today">Today</option>
                        <option value="this_week">This Week</option>
                        <option value="this_month">This Month</option>
                        <option value="last_month">Last Month</option>
                    </select>
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Sort By</span>
                    <select id="reportAnnSortFilter" class="filter-select" onchange="reloadAnnouncementReportPdf()" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer;">
                        <option value="date_desc" selected>Publish Date: Newest First</option>
                        <option value="date_asc">Publish Date: Oldest First</option>
                        <option value="title_asc">Title: A to Z</option>
                        <option value="title_desc">Title: Z to A</option>
                        <option value="author_asc">Author: A to Z</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
                <a id="announcementReportDownloadBtn" href="{{ route('admin.announcements.report.pdf') }}?download=1" class="btn btn-primary" style="background: #0284c7; border: none; padding: 8px 16px; font-size: 12px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; color: #fff;">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download PDF
                </a>
                <button type="button" class="btn btn-outline" onclick="printAnnouncementReportPdfFrame()" style="background: #fff; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #334155;">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print
                </button>
                <button type="button" class="btn btn-outline" onclick="closeAnnouncementReportModal()" style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 600; border-radius: 8px; cursor: pointer;">
                    ✕ Close
                </button>
            </div>
        </div>

        <div style="flex: 1; min-height: 0; width: 100%;">
            <iframe id="announcementReportPdfFrame" src="about:blank" style="width: 100%; height: 75vh; border: 1px solid #e2e8f0; border-radius: 10px; background: #525659;"></iframe>
        </div>
    </div>
</div>
@endsection
