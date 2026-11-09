@extends('layouts.admin')

@section('title', 'Incident Management')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-incidents.css') }}">

@php
    $incidentsList = collect($incidents ?? []);
    $categoryMeta = [
        'Security'    => ['icon' => '🛡️', 'color' => '#ef4444', 'bg' => '#fef2f2', 'border' => '#fecaca'],
        'Maintenance' => ['icon' => '🛠️', 'color' => '#f59e0b', 'bg' => '#fffbeb', 'border' => '#fde68a'],
        'Utilities'   => ['icon' => '💡', 'color' => '#0284c7', 'bg' => '#eff6ff', 'border' => '#bfdbfe'],
        'Others'      => ['icon' => '📁', 'color' => '#8b5cf6', 'bg' => '#f5f3ff', 'border' => '#ddd6fe'],
    ];
@endphp

<div class="bill-container fade-in">
    <!-- Page Header -->
    <div class="bill-header">
        <div class="bill-title">
            <h1 style="font-size: 24px; font-weight: 800; color: #0f172a; margin: 0;">Incident Management</h1>
            <p style="font-size: 13px; color: #64748b; margin-top: 4px;">Monitor, triage, and resolve community security issues, utility faults, and resident reports.</p>
        </div>
        <div class="bill-actions" style="display: flex; gap: 8px; align-items: center;">
            <select id="incidentDateFilter" class="filter-select" style="padding: 8px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; font-weight: 600; background: #fff; cursor: pointer;">
                <option value="all">All Time</option>
                <option value="today">Today</option>
                <option value="this_week">This Week</option>
                <option value="this_month">This Month</option>
                <option value="last_month">Last Month</option>
            </select>
        </div>
    </div>

    <!-- Summary Metric Cards -->
    <div class="responsive-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div onclick="filterByQuickStatus('all')" style="background: #fff; padding: 18px 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid var(--bill-primary); cursor: pointer; transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Total Incidents</div>
            <div id="statTotal" style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ $incidentsList->count() }}</div>
        </div>
        <div onclick="filterByQuickStatus('progress')" style="background: #fff; padding: 18px 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid #3b82f6; cursor: pointer; transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">In Progress</div>
            <div id="statProgress" style="font-size: 28px; font-weight: 800; color: #3b82f6; margin-top: 4px;">{{ $incidentsList->where('status', '!=', 'resolved')->count() }}</div>
        </div>
        <div onclick="filterByQuickStatus('resolved')" style="background: #fff; padding: 18px 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid #10b981; cursor: pointer; transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Resolved</div>
            <div id="statResolved" style="font-size: 28px; font-weight: 800; color: #10b981; margin-top: 4px;">{{ $incidentsList->where('status', 'resolved')->count() }}</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar" style="background: #f8fafc; padding: 18px 20px; border-radius: 16px; border: 1px solid #e2e8f0; display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 24px;">
        <div style="flex: 1; min-width: 260px;">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; display: block;">Search Incidents</label>
            <input type="text" id="incidentSearch" class="filter-select" placeholder="Search by Ticket #, Resident, Subject, or details..." style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;" oninput="applyIncidentFilters()">
        </div>
        <div style="width: 180px;">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; display: block;">Category</label>
            <select id="categoryFilter" class="filter-select" onchange="applyIncidentFilters()" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #fff;">
                <option value="all">All Categories</option>
                <option value="security">Security</option>
                <option value="maintenance">Maintenance</option>
                <option value="utilities">Utilities</option>
                <option value="others">Others</option>
            </select>
        </div>
        <div style="width: 170px;">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; display: block;">Status</label>
            <select id="statusFilter" class="filter-select" onchange="applyIncidentFilters()" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #fff;">
                <option value="all">All Statuses</option>
                <option value="progress">In Progress</option>
                <option value="resolved">Resolved</option>
            </select>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="button" class="btn btn-outline" onclick="resetIncidentFilters()" style="padding: 8px 14px; font-size: 13px; font-weight: 600; border-radius: 8px; background: #fff;">Clear</button>
        </div>
    </div>

    <!-- Incidents Database Table -->
    <div class="analytic-card" style="padding: 0; overflow: hidden; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <div class="bill-table-container">
            <table class="bill-table" id="incidentsTable">
                <thead>
                    <tr>
                        <th data-sort="text" style="width: 110px;">Ticket ID</th>
                        <th data-sort="date" style="width: 150px;">Date &amp; Time</th>
                        <th data-sort="text" style="width: 140px;">Category</th>
                        <th data-sort="text" style="width: 170px;">Reported By</th>
                        <th data-sort="text">Subject &amp; Summary</th>
                        <th data-sort="text" style="width: 130px;">Status</th>
                        <th style="width: 110px; text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody id="incidentsTableBody">
                    @forelse($incidentsList as $inc)
                        @php
                            $cat = $inc['type'] ?? 'Others';
                            $meta = $categoryMeta[$cat] ?? $categoryMeta['Others'];
                            $status = strtolower($inc['status'] ?? 'progress');
                            if ($status === 'pending') {
                                $status = 'progress';
                            }
                            $statusLabel = match($status) {
                                'resolved' => 'Resolved',
                                default => 'In Progress'
                            };
                            $statusClass = match($status) {
                                'resolved' => 'badge-success',
                                default => 'badge-primary'
                            };
                            $photoCount = is_array($inc['photos'] ?? null) ? count($inc['photos']) : (!empty($inc['img']) ? 1 : 0);
                            $hasVideo = !empty($inc['video']) || !empty($inc['video_url']);
                            $dateObj = !empty($inc['date']) ? \Carbon\Carbon::parse($inc['date']) : null;
                        @endphp
                        <tr class="incident-row pagination-match"
                            data-id="{{ $inc['id'] }}"
                            data-cat="{{ strtolower($cat) }}"
                            data-status="{{ $status }}"
                            data-date="{{ $inc['date'] ?? '' }}"
                            data-search="{{ strtolower($inc['id'].' '.$inc['sub'].' '.($inc['res'] ?? '').' '.$inc['desc'].' '.$cat.' '.$status) }}">
                            
                            <!-- Ticket ID -->
                            <td data-sort-val="{{ $inc['id'] }}" style="font-family: monospace; font-weight: 700; color: #0284c7; white-space: nowrap;">
                                INC-{{ str_pad($inc['id'], 5, '0', STR_PAD_LEFT) }}
                            </td>

                            <!-- Date & Time -->
                            <td data-sort-val="{{ $inc['date'] ?? '' }}" style="font-size: 12px; color: #475569; white-space: nowrap;">
                                @if($dateObj)
                                    <div style="font-weight: 600; color: #0f172a;">{{ $dateObj->format('M d, Y') }}</div>
                                    <div style="font-size: 11px; color: #94a3b8;">{{ $dateObj->format('h:i A') }}</div>
                                @else
                                    <span>—</span>
                                @endif
                            </td>

                            <!-- Category -->
                            <td data-sort-val="{{ $cat }}">
                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; background: {{ $meta['bg'] }}; color: {{ $meta['color'] }}; border: 1px solid {{ $meta['border'] }};">
                                    <span>{{ $meta['icon'] }}</span>
                                    <span>{{ $cat }}</span>
                                </span>
                            </td>

                            <!-- Reported By -->
                            <td data-sort-val="{{ $inc['res'] ?? 'Anonymous' }}" style="font-size: 13px; font-weight: 600; color: #1e293b;">
                                {{ $inc['res'] ?: 'Anonymous' }}
                            </td>

                            <!-- Subject & Summary -->
                            <td>
                                <div style="font-weight: 700; color: #0f172a; margin-bottom: 2px;">
                                    {{ $inc['sub'] }}
                                </div>
                                <div style="font-size: 12px; color: #64748b; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;">
                                    {{ $inc['desc'] }}
                                </div>
                                @if($photoCount > 0 || $hasVideo)
                                    <div style="display: flex; gap: 6px; margin-top: 4px; align-items: center;">
                                        @if($photoCount > 0)
                                            <span style="font-size: 10px; font-weight: 700; background: #eff6ff; color: #1d4ed8; padding: 1px 6px; border-radius: 4px; border: 1px solid #bfdbfe;">
                                                📷 {{ $photoCount }} photo{{ $photoCount > 1 ? 's' : '' }}
                                            </span>
                                        @endif
                                        @if($hasVideo)
                                            <span style="font-size: 10px; font-weight: 700; background: #fef3c7; color: #92400e; padding: 1px 6px; border-radius: 4px; border: 1px solid #fde68a;">
                                                🎥 video
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <!-- Status -->
                            <td data-sort-val="{{ $status }}">
                                <span class="badge {{ $statusClass }}" style="padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            <!-- Action -->
                            <td style="text-align: center;">
                                <button type="button" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; border-color: #cbd5e1;" onclick="openIncidentModal('{{ $inc['id'] }}')">
                                    Details
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr id="emptyIncidentsRow">
                            <td colspan="7" style="padding: 40px; text-align: center; color: #64748b;">
                                No incident reports recorded in the system.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials.admin-client-pagination', ['prefix' => 'incidentsTable', 'defaultPerPage' => 15])
    </div>
</div>

<!-- Incident Detail Modal -->
<div id="incidentModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 1050; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); position: fixed; top: 0; left: 0; right: 0; bottom: 0;">
    <div class="bill-modal-content" style="max-width: 580px; width: 95%; max-height: 90vh; overflow-y: auto; border-radius: 20px; background: #fff; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <div class="modal-header" style="border-bottom: 1px solid var(--bill-border); padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div>
                <h2 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0;">Incident Details</h2>
                <p id="modalIncId" style="font-size: 12px; font-weight: 700; color: #0284c7; margin: 2px 0 0 0; font-family: monospace;"></p>
            </div>
            <button class="ann-btn-icon" onclick="closeIncidentModal()" style="padding: 8px; background: none; border: none; cursor: pointer; color: #64748b;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
            <div>
                <h3 id="modalIncSubject" style="font-size: 18px; font-weight: 800; color: #1e293b; margin: 0 0 6px 0;"></h3>
                <div style="font-size: 12px; font-weight: 600; color: #64748b; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span id="modalIncType" style="color: #0369a1; background: #e0f2fe; padding: 2px 8px; border-radius: 9999px; font-weight: 700;"></span>
                    <span>&bull;</span>
                    <span id="modalIncRes"></span>
                    <span>&bull;</span>
                    <span id="modalIncDate"></span>
                </div>
            </div>
            
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Description</span>
                <p id="modalIncDesc" style="font-size: 13px; color: #334155; margin: 8px 0 0 0; line-height: 1.6; white-space: pre-wrap;"></p>
            </div>

            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Attached Media</span>
                <div id="modalIncMedia" style="margin-top: 10px; width: 100%; height: 160px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; border: 2px dashed #cbd5e1; color: #94a3b8; font-size: 13px; font-weight: 600;">
                    <!-- JS populated -->
                </div>
            </div>

            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Update Triage Status</span>
                <select id="modalIncStatus" class="filter-select" style="width: 100%; margin-top: 8px; padding: 10px; border-radius: 8px; font-weight: 700; background: #fff; border: 1px solid #cbd5e1; font-size: 13px;">
                    <option value="progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                </select>
            </div>
        </div>
        <div style="padding: 16px 24px; background: #f8fafc; border-top: 1px solid var(--bill-border); display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
            <a id="modalIncFullView" href="#" class="btn btn-outline" style="padding: 8px 14px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; font-weight: 700; color: #1e293b; text-decoration: none; font-size: 12px;">
                Full Report Page &rarr;
            </a>
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn btn-outline" style="padding: 8px 16px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; font-weight: 600; font-size: 13px;" onclick="closeIncidentModal()">Cancel</button>
                <button type="button" class="btn btn-primary" style="padding: 8px 20px; border-radius: 8px; background: #0284c7; color: #fff; font-weight: 700; font-size: 13px; border: none; cursor: pointer;" onclick="saveIncidentStatus()">Save Status</button>
            </div>
        </div>
    </div>
</div>

<!-- Fullscreen Image Viewer -->
<div id="fullscreenImageViewer" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.92); z-index: 9999; align-items: center; justify-content: center; flex-direction: column;">
    <button onclick="closeFullscreenImage()" style="position: absolute; top: 20px; right: 20px; background: none; border: none; color: white; font-size: 36px; cursor: pointer;">&times;</button>
    <img id="fullscreenImage" src="" style="max-width: 90%; max-height: 90%; object-fit: contain; border-radius: 8px;">
</div>

<script>
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    const rawIncidents = @json($incidents);
    let currentIncidentId = null;

    window.addEventListener('DOMContentLoaded', () => {
        window.incidentsTablePagination = initTablePagination({
            rowSelector: '.incident-row',
            infoEl: '#incidentsTablePageInfo',
            prevEl: '#incidentsTablePagePrev',
            nextEl: '#incidentsTablePageNext',
            perPage: 15,
        });

        applyIncidentFilters();

        const params = new URLSearchParams(window.location.search);
        if (params.get('incident')) {
            openIncidentModal(params.get('incident'));
        }
    });

    document.getElementById('incidentDateFilter').addEventListener('change', () => {
        applyIncidentFilters();
    });

    function filterByQuickStatus(status) {
        document.getElementById('statusFilter').value = status;
        applyIncidentFilters();
    }

    function resetIncidentFilters() {
        document.getElementById('incidentSearch').value = '';
        document.getElementById('categoryFilter').value = 'all';
        document.getElementById('statusFilter').value = 'all';
        document.getElementById('incidentDateFilter').value = 'all';
        applyIncidentFilters();
    }

    function applyIncidentFilters() {
        const query = document.getElementById('incidentSearch').value.toLowerCase().trim();
        const category = document.getElementById('categoryFilter').value.toLowerCase();
        const status = document.getElementById('statusFilter').value.toLowerCase();
        const dateFilter = document.getElementById('incidentDateFilter').value;
        const now = new Date();

        const rows = document.querySelectorAll('.incident-row');
        let visibleCount = 0;
        let pendingCount = 0;
        let progressCount = 0;
        let resolvedCount = 0;

        rows.forEach(row => {
            const rowSearch = row.dataset.search || '';
            const rowCat = row.dataset.cat || '';
            const rowStatus = row.dataset.status || '';
            const rowDateStr = row.dataset.date || '';

            const matchesQuery = !query || rowSearch.includes(query);
            const matchesCat = (category === 'all') || (rowCat === category);
            const matchesStatus = (status === 'all') || (rowStatus === status);

            let matchesDate = true;
            if (dateFilter !== 'all' && rowDateStr) {
                const incDate = new Date(rowDateStr);
                if (!isNaN(incDate.getTime())) {
                    if (dateFilter === 'today') {
                        matchesDate = incDate.toDateString() === now.toDateString();
                    } else if (dateFilter === 'this_week') {
                        const startOfWeek = new Date(now);
                        startOfWeek.setHours(0, 0, 0, 0);
                        startOfWeek.setDate(now.getDate() - now.getDay());
                        const endOfWeek = new Date(startOfWeek);
                        endOfWeek.setDate(startOfWeek.getDate() + 6);
                        endOfWeek.setHours(23, 59, 59, 999);
                        matchesDate = incDate >= startOfWeek && incDate <= endOfWeek;
                    } else if (dateFilter === 'this_month') {
                        matchesDate = incDate.getFullYear() === now.getFullYear() && incDate.getMonth() === now.getMonth();
                    } else if (dateFilter === 'last_month') {
                        const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                        const endOfLastMonth = new Date(now.getFullYear(), now.getMonth(), 0, 23, 59, 59, 999);
                        matchesDate = incDate >= lastMonth && incDate <= endOfLastMonth;
                    }
                }
            }

            const matches = matchesQuery && matchesCat && matchesStatus && matchesDate;
            row.classList.toggle('pagination-match', matches);

            if (matches) {
                visibleCount++;
                if (rowStatus === 'resolved') {
                    resolvedCount++;
                } else {
                    progressCount++;
                }
            }
        });

        // Update stats
        document.getElementById('statTotal').textContent = visibleCount;
        if (document.getElementById('statProgress')) document.getElementById('statProgress').textContent = progressCount;
        if (document.getElementById('statResolved')) document.getElementById('statResolved').textContent = resolvedCount;

        if (window.incidentsTablePagination) {
            window.incidentsTablePagination.resetPage();
        }
    }

    function openIncidentModal(id) {
        const inc = rawIncidents.find(i => String(i.id) === String(id));
        if (!inc) return;

        currentIncidentId = id;
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('incident', id);
        window.history.replaceState(null, '', currentUrl.toString());

        document.getElementById('modalIncId').textContent = 'INC-' + String(inc.id).padStart(5, '0');
        document.getElementById('modalIncSubject').textContent = inc.sub;
        document.getElementById('modalIncType').textContent = inc.type;
        document.getElementById('modalIncRes').textContent = inc.res || 'Resident';

        let formattedDate = 'Recently';
        if (inc.date) {
            const dObj = new Date(inc.date);
            if (!isNaN(dObj)) {
                formattedDate = dObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
            }
        }
        document.getElementById('modalIncDate').textContent = formattedDate;
        document.getElementById('modalIncDesc').textContent = inc.desc;
        document.getElementById('modalIncStatus').value = (inc.status || 'progress').toLowerCase() === 'resolved' ? 'resolved' : 'progress';

        const fullViewLink = document.getElementById('modalIncFullView');
        if (fullViewLink) {
            fullViewLink.href = `/admin/incidents/${inc.id}`;
        }

        const mediaContainer = document.getElementById('modalIncMedia');
        let mediaHtml = '';
        
        if (inc.video || inc.video_url) {
            const vSrc = escapeHtml(inc.video || inc.video_url);
            mediaHtml += `
                <div style="height: 100%; max-width: 250px; flex-shrink: 0; border-radius: 6px; overflow: hidden; background: #000;">
                    <video src="${vSrc}" controls preload="metadata" style="height: 100%; max-width: 250px; object-fit: contain;">
                        Your browser does not support HTML5 video.
                    </video>
                </div>
            `;
        }

        if (inc.photos && inc.photos.length > 0) {
            inc.photos.forEach(p => {
                mediaHtml += `<img src="${p}" onclick="openFullscreenImage('${p}')" style="height: 100%; max-width: 240px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1; flex-shrink: 0; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'" alt="Attachment">`;
            });
        } else if (inc.img && !inc.video && !inc.video_url) {
            mediaHtml += `<img src="${inc.img}" onclick="openFullscreenImage('${inc.img}')" style="width:100%; height:100%; object-fit:cover; border-radius:6px; cursor: pointer;" alt="Attachment">`;
        }

        if (mediaHtml) {
            mediaContainer.innerHTML = `<div style="display: flex; gap: 10px; width: 100%; height: 100%; overflow-x: auto; padding-bottom: 4px;">${mediaHtml}</div>`;
            mediaContainer.style.border = 'none';
            mediaContainer.style.background = 'transparent';
        } else {
            mediaContainer.innerHTML = `<div style="text-align:center;">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-bottom:6px; opacity:0.6; display:inline-block;"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <br>No media attached
            </div>`;
            mediaContainer.style.border = '2px dashed #cbd5e1';
            mediaContainer.style.background = '#f1f5f9';
        }

        document.getElementById('incidentModal').style.display = 'flex';
    }

    function closeIncidentModal() {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.delete('incident');
        window.history.replaceState(null, '', currentUrl.toString());

        const v = document.querySelector('#modalIncMedia video');
        if (v) v.pause();
        document.getElementById('incidentModal').style.display = 'none';
        currentIncidentId = null;
    }

    async function saveIncidentStatus() {
        if (!currentIncidentId) return;
        const newStatus = document.getElementById('modalIncStatus').value;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        try {
            const response = await fetch(`/admin/incidents/${currentIncidentId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            });
            
            const result = await response.json();
            if (result.success) {
                // Update in rawIncidents
                const target = rawIncidents.find(i => String(i.id) === String(currentIncidentId));
                if (target) target.status = newStatus;

                // Update in DOM table row
                const row = document.querySelector(`.incident-row[data-id="${currentIncidentId}"]`);
                if (row) {
                    row.dataset.status = newStatus.toLowerCase();
                    const statusTd = row.children[5];
                    if (statusTd) {
                        const statusClass = newStatus === 'resolved' ? 'badge-success' : 'badge-primary';
                        const statusLabel = newStatus === 'resolved' ? 'RESOLVED' : 'IN PROGRESS';
                        statusTd.innerHTML = `<span class="badge ${statusClass}" style="padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase;">${statusLabel}</span>`;
                        statusTd.setAttribute('data-sort-val', newStatus.toLowerCase());
                    }
                }

                closeIncidentModal();
                applyIncidentFilters();
                if (window.Swal) {
                    Swal.fire({ icon: 'success', title: 'Updated!', text: 'Incident status updated successfully.', timer: 1500, showConfirmButton: false });
                }
            } else {
                if (window.Swal) {
                    Swal.fire({ icon: 'error', text: result.message || 'Failed to update status.' });
                } else {
                    alert(result.message || 'Failed to update status.');
                }
            }
        } catch (error) {
            console.error(error);
            if (window.Swal) {
                Swal.fire({ icon: 'error', text: 'An error occurred while updating status.' });
            } else {
                alert('An error occurred while updating status.');
            }
        }
    }

    function openFullscreenImage(src) {
        document.getElementById('fullscreenImage').src = src;
        document.getElementById('fullscreenImageViewer').style.display = 'flex';
    }

    function closeFullscreenImage() {
        document.getElementById('fullscreenImageViewer').style.display = 'none';
    }
</script>
@endsection
