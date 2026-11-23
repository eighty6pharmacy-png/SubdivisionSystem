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
        <div class="bill-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <!-- View Mode Switcher -->
            <div style="display: inline-flex; background: #e2e8f0; padding: 3px; border-radius: 10px; gap: 3px;">
                <button type="button" id="tabTableViewBtn" onclick="switchIncidentView('table')" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; font-size: 12px; font-weight: 700; border-radius: 7px; border: none; cursor: pointer; transition: all 0.15s; background: #fff; color: #0f172a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    Table View
                </button>
                <button type="button" id="tabCalendarViewBtn" onclick="switchIncidentView('calendar')" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; font-size: 12px; font-weight: 700; border-radius: 7px; border: none; cursor: pointer; transition: all 0.15s; background: transparent; color: #64748b;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Calendar View
                </button>
            </div>

            <button type="button" class="btn btn-outline" style="background: #fff; border-color: #0284c7; color: #0284c7; font-weight: 700; display: inline-flex; align-items: center; padding: 8px 16px;" onclick="openIncidentReportModal()">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 6px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Generate Report
            </button>
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
    <div class="responsive-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 0;">
        <div onclick="filterByQuickStatus('all')" style="background: #fff; padding: 14px 18px; border-radius: 12px; border: 1px solid var(--bill-border); border-left: 4px solid var(--bill-primary); cursor: pointer; transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Total Incidents</div>
            <div id="statTotal" style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 2px;">{{ $incidentsList->count() }}</div>
        </div>
        <div onclick="filterByQuickStatus('no_action')" style="background: #fff; padding: 14px 18px; border-radius: 12px; border: 1px solid var(--bill-border); border-left: 4px solid #64748b; cursor: pointer; transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">No Action</div>
            <div id="statNoAction" style="font-size: 24px; font-weight: 800; color: #475569; margin-top: 2px;">{{ $incidentsList->filter(fn($i) => !in_array(strtolower($i['status'] ?? ''), ['progress', 'resolved'], true))->count() }}</div>
        </div>
        <div onclick="filterByQuickStatus('progress')" style="background: #fff; padding: 14px 18px; border-radius: 12px; border: 1px solid var(--bill-border); border-left: 4px solid #3b82f6; cursor: pointer; transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">In Progress</div>
            <div id="statProgress" style="font-size: 24px; font-weight: 800; color: #3b82f6; margin-top: 2px;">{{ $incidentsList->where('status', 'progress')->count() }}</div>
        </div>
        <div onclick="filterByQuickStatus('resolved')" style="background: #fff; padding: 14px 18px; border-radius: 12px; border: 1px solid var(--bill-border); border-left: 4px solid #10b981; cursor: pointer; transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Resolved</div>
            <div id="statResolved" style="font-size: 24px; font-weight: 800; color: #10b981; margin-top: 2px;">{{ $incidentsList->where('status', 'resolved')->count() }}</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar" style="background: #f8fafc; padding: 14px 18px; border-radius: 14px; border: 1px solid #e2e8f0; display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 0;">
        <div style="flex: 1; min-width: 240px;">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; display: block;">Search Incidents</label>
            <input type="text" id="incidentSearch" class="filter-select" placeholder="Search by Ticket #, Resident, Subject, or details..." style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;" oninput="applyIncidentFilters()">
        </div>
        <div style="width: 170px;">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; display: block;">Category</label>
            <select id="categoryFilter" class="filter-select" onchange="applyIncidentFilters()" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #fff;">
                <option value="all">All Categories</option>
                <option value="security">Security</option>
                <option value="maintenance">Maintenance</option>
                <option value="utilities">Utilities</option>
                <option value="others">Others</option>
            </select>
        </div>
        <div style="width: 160px;">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; display: block;">Status</label>
            <select id="statusFilter" class="filter-select" onchange="applyIncidentFilters()" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #fff;">
                <option value="all">All Statuses</option>
                <option value="no_action">No Action</option>
                <option value="progress">In Progress</option>
                <option value="resolved">Resolved</option>
            </select>
        </div>
        <div style="width: 160px;">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; display: block;">Specific Date</label>
            <input type="date" id="specificDateFilter" class="filter-select" onchange="applyIncidentFilters()" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #fff; cursor: pointer;" title="Filter incidents on this exact date">
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="button" class="btn btn-outline" onclick="resetIncidentFilters()" style="padding: 8px 14px; font-size: 13px; font-weight: 600; border-radius: 8px; background: #fff;">Clear</button>
        </div>
    </div>

    <!-- Table View Container -->
    <div id="incidentsTableViewContainer">
        <!-- Incidents Database Table -->
        <div class="analytic-card" style="padding: 0; overflow: hidden; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <div class="bill-table-container">
                <table class="bill-table" id="incidentsTable" style="width: 100%; table-layout: auto;">
                    <thead>
                        <tr>
                            <th data-sort="text" style="width: 95px; white-space: nowrap;">Ticket ID</th>
                            <th data-sort="date" style="width: 120px; white-space: nowrap;">Date &amp; Time</th>
                            <th data-sort="text" style="width: 125px; white-space: nowrap;">Category</th>
                            <th data-sort="text" style="width: 145px;">Reported By</th>
                            <th data-sort="text">Subject &amp; Summary</th>
                            <th data-sort="text" style="width: 115px; white-space: nowrap;">Status</th>
                            <th style="width: 85px; text-align: center; white-space: nowrap;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="incidentsTableBody">
                        @forelse($incidentsList as $inc)
                            @php
                                $cat = $inc['type'] ?? 'Others';
                                $meta = $categoryMeta[$cat] ?? $categoryMeta['Others'];
                                $status = strtolower($inc['status'] ?? 'no_action');
                                if ($status === 'pending' || $status === '') {
                                    $status = 'no_action';
                                }
                                $statusLabel = match($status) {
                                    'resolved' => 'Resolved',
                                    'progress' => 'In Progress',
                                    default => 'No Action'
                                };
                                $statusClass = match($status) {
                                    'resolved' => 'badge-success',
                                    'progress' => 'badge-primary',
                                    default => 'badge-secondary'
                                };
                                $statusInlineStyle = $status === 'no_action'
                                    ? 'background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;'
                                    : '';
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
                                    <div style="font-size: 12px; color: #64748b; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        {{ $inc['desc'] }}
                                    </div>
                                    @if($photoCount > 0 || $hasVideo || !empty($inc['admin_reply']))
                                        <div style="display: flex; gap: 6px; margin-top: 4px; align-items: center; flex-wrap: wrap;">
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
                                            @if(!empty($inc['admin_reply']))
                                                <span style="font-size: 10px; font-weight: 700; background: #ecfdf5; color: #047857; padding: 1px 6px; border-radius: 4px; border: 1px solid #a7f3d0;">
                                                    💬 Replied
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td data-sort-val="{{ $status }}">
                                    <span class="badge {{ $statusClass }}" style="padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; {{ $statusInlineStyle }}">
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

    <!-- Incidents Interactive Calendar View -->
    <div id="incidentsCalendarViewContainer" style="display: none;">
        <div class="incident-cal-card">
            <!-- Calendar Top Controls Toolbar -->
            <div class="incident-cal-toolbar">
                <!-- Navigation & Current Month/Year -->
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <div class="incident-cal-nav-group">
                        <button type="button" class="incident-cal-btn" onclick="calPrev()" title="Previous">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button type="button" class="incident-cal-btn" onclick="calToday()">Today</button>
                        <button type="button" class="incident-cal-btn" onclick="calNext()" title="Next">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px;">
                        <h2 id="calCurrentMonthTitle" style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0; min-width: 170px;"></h2>
                        <select id="calMonthSelect" onchange="calJumpTo()" style="padding: 5px 8px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 12px; font-weight: 700; background: #fff; cursor: pointer;">
                            <option value="0">Jan</option><option value="1">Feb</option><option value="2">Mar</option><option value="3">Apr</option>
                            <option value="4">May</option><option value="5">Jun</option><option value="6">Jul</option><option value="7">Aug</option>
                            <option value="8">Sep</option><option value="9">Oct</option><option value="10">Nov</option><option value="11">Dec</option>
                        </select>
                        <select id="calYearSelect" onchange="calJumpTo()" style="padding: 5px 8px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 12px; font-weight: 700; background: #fff; cursor: pointer;">
                            <!-- JS populated -->
                        </select>
                    </div>
                </div>

                <!-- Monthly Status Metrics Pills -->
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <div style="background: #f1f5f9; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 6px;">
                        <span>🗓️ In Period:</span>
                        <span id="calPeriodTotal" style="color: #0f172a; font-weight: 800;">0</span>
                    </div>
                    <div style="background: #eff6ff; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 700; color: #1d4ed8; display: flex; align-items: center; gap: 6px;">
                        <span style="width: 7px; height: 7px; border-radius: 50%; background: #3b82f6;"></span>
                        <span>Active:</span>
                        <span id="calPeriodProgress" style="font-weight: 800;">0</span>
                    </div>
                    <div style="background: #ecfdf5; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 700; color: #047857; display: flex; align-items: center; gap: 6px;">
                        <span style="width: 7px; height: 7px; border-radius: 50%; background: #10b981;"></span>
                        <span>Resolved:</span>
                        <span id="calPeriodResolved" style="font-weight: 800;">0</span>
                    </div>
                </div>

                <!-- View Mode (Month / Week) & Category Filter Legend -->
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <div style="display: inline-flex; background: #e2e8f0; padding: 3px; border-radius: 8px; gap: 3px;">
                        <button type="button" id="calModeMonthBtn" onclick="setCalViewMode('month')" style="padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; border: none; cursor: pointer; background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">Month</button>
                        <button type="button" id="calModeWeekBtn" onclick="setCalViewMode('week')" style="padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; border: none; cursor: pointer; background: transparent; color: #64748b;">Week</button>
                    </div>

                    <div style="display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700;">
                        <span onclick="toggleCalCategory('security')" class="cal-legend-pill" data-cat="security" style="padding: 3px 8px; border-radius: 999px; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; cursor: pointer; transition: all 0.15s;" title="Toggle Security Filter">🛡️ Security</span>
                        <span onclick="toggleCalCategory('maintenance')" class="cal-legend-pill" data-cat="maintenance" style="padding: 3px 8px; border-radius: 999px; background: #fffbeb; color: #b45309; border: 1px solid #fde68a; cursor: pointer; transition: all 0.15s;" title="Toggle Maintenance Filter">🛠️ Maint</span>
                        <span onclick="toggleCalCategory('utilities')" class="cal-legend-pill" data-cat="utilities" style="padding: 3px 8px; border-radius: 999px; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; cursor: pointer; transition: all 0.15s;" title="Toggle Utilities Filter">💡 Util</span>
                        <span onclick="toggleCalCategory('others')" class="cal-legend-pill" data-cat="others" style="padding: 3px 8px; border-radius: 999px; background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; cursor: pointer; transition: all 0.15s;" title="Toggle Others Filter">📁 Other</span>
                    </div>
                </div>
            </div>

            <!-- Calendar Month Grid Container -->
            <div id="calMonthGridContainer">
                <div class="incident-cal-grid" id="incidentCalGrid">
                    <!-- Dynamic Month Cells -->
                </div>
            </div>

            <!-- Calendar Week Grid Container -->
            <div id="calWeekGridContainer" style="display: none; padding: 16px; background: #f8fafc;">
                <div id="incidentCalWeekGrid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 12px;">
                    <!-- Dynamic Week Columns -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Day Agenda Drawer & Overlay -->
<div id="incidentDayDrawerOverlay" class="incident-agenda-drawer-overlay" onclick="closeDayDrawer()"></div>
<div id="incidentDayDrawer" class="incident-agenda-drawer">
    <div style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
        <div>
            <h3 id="drawerDateTitle" style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;"></h3>
            <p id="drawerDateSubtitle" style="font-size: 12px; color: #64748b; margin: 3px 0 0 0; font-weight: 600;"></p>
        </div>
        <button type="button" onclick="closeDayDrawer()" style="background: none; border: none; font-size: 20px; color: #64748b; cursor: pointer; padding: 4px 8px; border-radius: 6px;">✕</button>
    </div>
    <div id="drawerIncidentList" style="flex: 1; overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 14px;">
        <!-- JS populated -->
    </div>
    <div style="padding: 14px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end;">
        <button type="button" class="btn btn-outline" onclick="closeDayDrawer()" style="padding: 8px 16px; font-size: 13px; font-weight: 700; background: #fff;">Close Drawer</button>
    </div>
</div>

<!-- Floating Hover Tooltip -->
<div id="incidentCalTooltip" class="incident-cal-tooltip"></div>

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
                    <option value="" disabled hidden>-- Select Status (Currently: No Action) --</option>
                    <option value="progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                </select>
            </div>

            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Reply to Resident</span>
                    <span id="modalIncReplyMeta" style="font-size: 11px; color: #047857; font-weight: 600;"></span>
                </div>
                <textarea id="modalIncReply" rows="3" style="width: 100%; margin-top: 8px; padding: 10px 12px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; font-size: 13px; font-family: inherit; resize: vertical; box-sizing: border-box;" placeholder="Type a reply to the resident about this issue..."></textarea>
                <div style="display: flex; justify-content: flex-end; margin-top: 8px;">
                    <button type="button" id="btnSendIncReply" class="btn btn-outline" style="padding: 7px 14px; border-radius: 8px; border: 1px solid #0284c7; color: #0284c7; background: #fff; font-weight: 700; font-size: 12px; cursor: pointer;" onclick="sendIncidentReply()">Send Reply</button>
                </div>
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

    const categoryIcons = {
        'Security': '🛡️',
        'Maintenance': '🛠️',
        'Utilities': '💡',
        'Others': '📁'
    };

    const rawIncidents = @json($incidents);
    let currentIncidentId = null;
    let currentAdminView = localStorage.getItem('incident_admin_view') || 'table';
    let calCurrentDate = new Date();
    let calViewMode = 'month'; // 'month' or 'week'
    let calSelectedDayStr = null;
    let calLegendCategory = 'all';

    function switchIncidentView(mode) {
        currentAdminView = mode;
        localStorage.setItem('incident_admin_view', mode);
        const tableContainer = document.getElementById('incidentsTableViewContainer');
        const calContainer = document.getElementById('incidentsCalendarViewContainer');
        const btnTable = document.getElementById('tabTableViewBtn');
        const btnCal = document.getElementById('tabCalendarViewBtn');

        if (mode === 'calendar') {
            tableContainer.style.display = 'none';
            calContainer.style.display = 'block';
            btnTable.style.background = 'transparent';
            btnTable.style.color = '#64748b';
            btnTable.style.boxShadow = 'none';
            btnCal.style.background = '#fff';
            btnCal.style.color = '#0f172a';
            btnCal.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
            renderAdminCalendar();
        } else {
            tableContainer.style.display = 'block';
            calContainer.style.display = 'none';
            btnCal.style.background = 'transparent';
            btnCal.style.color = '#64748b';
            btnCal.style.boxShadow = 'none';
            btnTable.style.background = '#fff';
            btnTable.style.color = '#0f172a';
            btnTable.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
        }
    }

    function initCalendarDropdowns() {
        const yearSelect = document.getElementById('calYearSelect');
        if (!yearSelect || yearSelect.children.length > 0) return;
        const currentYear = new Date().getFullYear();
        for (let y = currentYear - 3; y <= currentYear + 3; y++) {
            const opt = document.createElement('option');
            opt.value = y;
            opt.textContent = y;
            yearSelect.appendChild(opt);
        }
    }

    function calJumpTo() {
        const m = parseInt(document.getElementById('calMonthSelect').value, 10);
        const y = parseInt(document.getElementById('calYearSelect').value, 10);
        calCurrentDate = new Date(y, m, 1);
        renderAdminCalendar();
    }

    function calPrev() {
        if (calViewMode === 'week') {
            calCurrentDate.setDate(calCurrentDate.getDate() - 7);
        } else {
            calCurrentDate.setMonth(calCurrentDate.getMonth() - 1);
        }
        renderAdminCalendar();
    }

    function calNext() {
        if (calViewMode === 'week') {
            calCurrentDate.setDate(calCurrentDate.getDate() + 7);
        } else {
            calCurrentDate.setMonth(calCurrentDate.getMonth() + 1);
        }
        renderAdminCalendar();
    }

    function calToday() {
        calCurrentDate = new Date();
        renderAdminCalendar();
    }

    function setCalViewMode(mode) {
        calViewMode = mode;
        const btnMonth = document.getElementById('calModeMonthBtn');
        const btnWeek = document.getElementById('calModeWeekBtn');
        const monthContainer = document.getElementById('calMonthGridContainer');
        const weekContainer = document.getElementById('calWeekGridContainer');

        if (mode === 'week') {
            btnWeek.style.background = '#fff';
            btnWeek.style.color = '#0f172a';
            btnWeek.style.boxShadow = '0 1px 2px rgba(0,0,0,0.1)';
            btnMonth.style.background = 'transparent';
            btnMonth.style.color = '#64748b';
            btnMonth.style.boxShadow = 'none';
            monthContainer.style.display = 'none';
            weekContainer.style.display = 'block';
        } else {
            btnMonth.style.background = '#fff';
            btnMonth.style.color = '#0f172a';
            btnMonth.style.boxShadow = '0 1px 2px rgba(0,0,0,0.1)';
            btnWeek.style.background = 'transparent';
            btnWeek.style.color = '#64748b';
            btnWeek.style.boxShadow = 'none';
            monthContainer.style.display = 'block';
            weekContainer.style.display = 'none';
        }
        renderAdminCalendar();
    }

    function toggleCalCategory(cat) {
        if (calLegendCategory === cat) {
            calLegendCategory = 'all';
        } else {
            calLegendCategory = cat;
        }
        document.querySelectorAll('.cal-legend-pill').forEach(pill => {
            const pCat = pill.getAttribute('data-cat');
            if (calLegendCategory === 'all' || calLegendCategory === pCat) {
                pill.style.opacity = '1';
                pill.style.transform = 'scale(1)';
            } else {
                pill.style.opacity = '0.35';
                pill.style.transform = 'scale(0.95)';
            }
        });
        renderAdminCalendar();
    }

    function getFilteredIncidents() {
        const query = (document.getElementById('incidentSearch')?.value || '').toLowerCase().trim();
        let category = (document.getElementById('categoryFilter')?.value || 'all').toLowerCase();
        if (calLegendCategory !== 'all') {
            category = calLegendCategory.toLowerCase();
        }
        const status = (document.getElementById('statusFilter')?.value || 'all').toLowerCase();
        const dateFilter = document.getElementById('incidentDateFilter')?.value || 'all';
        const specDate = document.getElementById('specificDateFilter')?.value || '';
        const now = new Date();

        return rawIncidents.filter(inc => {
            const sText = ((inc.id || '') + ' ' + (inc.sub || '') + ' ' + (inc.res || '') + ' ' + (inc.desc || '') + ' ' + (inc.type || '') + ' ' + (inc.status || '')).toLowerCase();
            const matchesQuery = !query || sText.includes(query);
            const matchesCat = (category === 'all') || ((inc.type || '').toLowerCase() === category);
            const curStatus = (inc.status || 'progress').toLowerCase() === 'resolved' ? 'resolved' : 'progress';
            const matchesStatus = (status === 'all') || (curStatus === status);

            let matchesDate = true;
            if (specDate && inc.date) {
                const incD = new Date(inc.date);
                const pad = n => String(n).padStart(2, '0');
                const incDateStr = `${incD.getFullYear()}-${pad(incD.getMonth()+1)}-${pad(incD.getDate())}`;
                matchesDate = (incDateStr === specDate);
            } else if (dateFilter !== 'all' && inc.date) {
                const incDate = new Date(inc.date);
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
            return matchesQuery && matchesCat && matchesStatus && matchesDate;
        });
    }

    function renderAdminCalendar() {
        initCalendarDropdowns();
        const monthSelect = document.getElementById('calMonthSelect');
        const yearSelect = document.getElementById('calYearSelect');
        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        
        const year = calCurrentDate.getFullYear();
        const month = calCurrentDate.getMonth();

        if (monthSelect) monthSelect.value = month;
        if (yearSelect) yearSelect.value = year;

        const filtered = getFilteredIncidents();

        const incidentsByDate = {};
        const pad = n => String(n).padStart(2, '0');

        filtered.forEach(inc => {
            if (!inc.date) return;
            const d = new Date(inc.date);
            if (isNaN(d.getTime())) return;
            const key = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
            if (!incidentsByDate[key]) incidentsByDate[key] = [];
            incidentsByDate[key].push(inc);
        });

        if (calViewMode === 'week') {
            renderAdminWeekView(year, month, incidentsByDate, filtered);
        } else {
            renderAdminMonthView(year, month, incidentsByDate, filtered, monthNames);
        }
    }

    function renderAdminMonthView(year, month, incidentsByDate, filtered, monthNames) {
        const titleEl = document.getElementById('calCurrentMonthTitle');
        if (titleEl) titleEl.textContent = `${monthNames[month]} ${year}`;

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const startDayIndex = firstDay.getDay();
        const totalDays = lastDay.getDate();
        const prevMonthLastDay = new Date(year, month, 0).getDate();

        const gridEl = document.getElementById('incidentCalGrid');
        if (!gridEl) return;

        const dow = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        let html = dow.map(d => `<div class="incident-cal-dow">${d}</div>`).join('');

        const today = new Date();
        const isCurrentMonth = today.getFullYear() === year && today.getMonth() === month;
        const pad = n => String(n).padStart(2, '0');

        let monthTotal = 0;
        let monthProgress = 0;
        let monthResolved = 0;

        for (let i = startDayIndex - 1; i >= 0; i--) {
            const dNum = prevMonthLastDay - i;
            const prevMonthDate = new Date(year, month - 1, dNum);
            const prevKey = `${prevMonthDate.getFullYear()}-${pad(prevMonthDate.getMonth() + 1)}-${pad(dNum)}`;
            const prevDayIncidents = incidentsByDate[prevKey] || [];
            html += `<div class="incident-cal-day other-month" onclick="selectCalendarDay('${prevKey}')">
                <div class="incident-cal-day-header">
                    <span class="incident-cal-day-num">${dNum}</span>
                    ${prevDayIncidents.length > 0 ? `<span class="incident-cal-day-count">${prevDayIncidents.length}</span>` : ''}
                </div>
                <div class="incident-cal-events-wrap">
                    ${renderDayChips(prevDayIncidents, prevKey)}
                </div>
            </div>`;
        }

        for (let d = 1; d <= totalDays; d++) {
            const dateKey = `${year}-${pad(month + 1)}-${pad(d)}`;
            const isToday = isCurrentMonth && today.getDate() === d;
            const isSelected = calSelectedDayStr === dateKey;
            const dayIncidents = incidentsByDate[dateKey] || [];

            monthTotal += dayIncidents.length;
            dayIncidents.forEach(inc => {
                if ((inc.status || '').toLowerCase() === 'resolved') {
                    monthResolved++;
                } else {
                    monthProgress++;
                }
            });

            html += `<div class="incident-cal-day ${isToday ? 'today' : ''} ${isSelected ? 'selected' : ''}" onclick="selectCalendarDay('${dateKey}')">
                <div class="incident-cal-day-header">
                    <span class="incident-cal-day-num">${d}</span>
                    ${dayIncidents.length > 0 ? `<span class="incident-cal-day-count" title="${dayIncidents.length} reports">${dayIncidents.length}</span>` : ''}
                </div>
                <div class="incident-cal-events-wrap">
                    ${renderDayChips(dayIncidents, dateKey)}
                </div>
            </div>`;
        }

        const currentCells = startDayIndex + totalDays;
        const totalCellsNeeded = currentCells > 35 ? 42 : 35;
        const remaining = totalCellsNeeded - currentCells;
        for (let r = 1; r <= remaining; r++) {
            const nextMonthDate = new Date(year, month + 1, r);
            const nextKey = `${nextMonthDate.getFullYear()}-${pad(nextMonthDate.getMonth() + 1)}-${pad(r)}`;
            const nextDayIncidents = incidentsByDate[nextKey] || [];
            html += `<div class="incident-cal-day other-month" onclick="selectCalendarDay('${nextKey}')">
                <div class="incident-cal-day-header">
                    <span class="incident-cal-day-num">${r}</span>
                    ${nextDayIncidents.length > 0 ? `<span class="incident-cal-day-count">${nextDayIncidents.length}</span>` : ''}
                </div>
                <div class="incident-cal-events-wrap">
                    ${renderDayChips(nextDayIncidents, nextKey)}
                </div>
            </div>`;
        }

        gridEl.innerHTML = html;

        const pTotal = document.getElementById('calPeriodTotal');
        const pProg = document.getElementById('calPeriodProgress');
        const pRes = document.getElementById('calPeriodResolved');
        if (pTotal) pTotal.textContent = monthTotal;
        if (pProg) pProg.textContent = monthProgress;
        if (pRes) pRes.textContent = monthResolved;
    }

    function renderDayChips(dayIncidents, dateKey) {
        if (!dayIncidents || dayIncidents.length === 0) return '';
        let chipsHtml = '';
        const visibleIncidents = dayIncidents.slice(0, 3);

        visibleIncidents.forEach(inc => {
            const cat = (inc.type || 'Others');
            const catLower = cat.toLowerCase();
            let catClass = 'cat-others';
            if (catLower.includes('sec')) catClass = 'cat-security';
            else if (catLower.includes('maint')) catClass = 'cat-maintenance';
            else if (catLower.includes('util')) catClass = 'cat-utilities';

            const isResolved = (inc.status || '').toLowerCase() === 'resolved';
            const icon = categoryIcons[cat] || '📁';
            const ref = 'INC-' + String(inc.id).padStart(5, '0');

            chipsHtml += `
                <div class="incident-event-chip ${catClass}"
                     onclick="event.stopPropagation(); openIncidentModal('${inc.id}')"
                     onmouseenter="showCalTooltip(event, '${inc.id}')"
                     onmouseleave="hideCalTooltip()"
                     data-id="${inc.id}">
                    <span class="incident-chip-status-dot ${isResolved ? 'resolved' : 'progress'}"></span>
                    <span>${icon}</span>
                    <span style="font-family: monospace; font-weight: 800;">${ref}</span>
                    <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 90px;">${escapeHtml(inc.sub)}</span>
                </div>
            `;
        });

        if (dayIncidents.length > 3) {
            chipsHtml += `
                <div class="incident-cal-more-btn" onclick="event.stopPropagation(); openDayDrawer('${dateKey}')">
                    +${dayIncidents.length - 3} more reports
                </div>
            `;
        }
        return chipsHtml;
    }

    function renderAdminWeekView(year, month, incidentsByDate, filtered) {
        const pad = n => String(n).padStart(2, '0');
        const current = new Date(calCurrentDate);
        const dayOfWeek = current.getDay();
        const startOfWeek = new Date(current);
        startOfWeek.setDate(current.getDate() - dayOfWeek);

        const endOfWeek = new Date(startOfWeek);
        endOfWeek.setDate(startOfWeek.getDate() + 6);

        const monthNamesShort = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        const titleEl = document.getElementById('calCurrentMonthTitle');
        if (titleEl) {
            titleEl.textContent = `${monthNamesShort[startOfWeek.getMonth()]} ${startOfWeek.getDate()} – ${monthNamesShort[endOfWeek.getMonth()]} ${endOfWeek.getDate()}, ${endOfWeek.getFullYear()}`;
        }

        const weekGridEl = document.getElementById('incidentCalWeekGrid');
        if (!weekGridEl) return;

        const dowNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const today = new Date();
        const todayKey = `${today.getFullYear()}-${pad(today.getMonth() + 1)}-${pad(today.getDate())}`;

        let weekHtml = '';
        let weekTotal = 0;
        let weekProgress = 0;
        let weekResolved = 0;

        for (let i = 0; i < 7; i++) {
            const dayDate = new Date(startOfWeek);
            dayDate.setDate(startOfWeek.getDate() + i);
            const dayKey = `${dayDate.getFullYear()}-${pad(dayDate.getMonth() + 1)}-${pad(dayDate.getDate())}`;
            const dayIncidents = incidentsByDate[dayKey] || [];
            const isToday = (dayKey === todayKey);

            weekTotal += dayIncidents.length;
            dayIncidents.forEach(inc => {
                if ((inc.status || '').toLowerCase() === 'resolved') weekResolved++;
                else weekProgress++;
            });

            let cardsHtml = '';
            if (dayIncidents.length === 0) {
                cardsHtml = `<div style="text-align: center; color: #94a3b8; font-size: 11px; padding: 24px 0;">No incidents</div>`;
            } else {
                dayIncidents.forEach(inc => {
                    const isResolved = (inc.status || '').toLowerCase() === 'resolved';
                    const cat = inc.type || 'Others';
                    const icon = categoryIcons[cat] || '📁';
                    let timeStr = 'Logged';
                    if (inc.date) {
                        const dObj = new Date(inc.date);
                        if (!isNaN(dObj)) timeStr = dObj.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                    }

                    cardsHtml += `
                        <div onclick="openIncidentModal('${inc.id}')" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px; cursor: pointer; transition: transform 0.15s, box-shadow 0.15s; border-left: 3px solid ${isResolved ? '#10b981' : '#3b82f6'};" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 10px rgba(0,0,0,0.06)';" onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <span style="font-size: 10px; font-weight: 800; color: #64748b;">${icon} ${cat}</span>
                                <span style="font-size: 10px; color: #94a3b8; font-weight: 600;">${timeStr}</span>
                            </div>
                            <div style="font-size: 12px; font-weight: 800; color: #0f172a; line-height: 1.3; margin-bottom: 3px;">
                                ${escapeHtml(inc.sub)}
                            </div>
                            <div style="font-size: 11px; color: #64748b; line-height: 1.3; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                ${escapeHtml(inc.desc)}
                            </div>
                            <div style="margin-top: 6px; display: flex; justify-content: space-between; align-items: center; font-size: 10.5px;">
                                <span style="font-family: monospace; font-weight: 700; color: #0284c7;">INC-${String(inc.id).padStart(5, '0')}</span>
                                <span style="font-weight: 800; color: ${isResolved ? '#059669' : '#d97706'}; font-size: 10px;">${isResolved ? 'Resolved' : 'Active'}</span>
                            </div>
                        </div>
                    `;
                });
            }

            weekHtml += `
                <div style="background: ${isToday ? '#f0f9ff' : '#ffffff'}; border: 1px solid ${isToday ? '#bae6fd' : '#e2e8f0'}; border-radius: 12px; padding: 12px; display: flex; flex-direction: column; min-height: 480px;">
                    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 11px; font-weight: 800; color: ${isToday ? '#0284c7' : '#64748b'}; text-transform: uppercase;">${dowNames[i]}</div>
                            <div style="font-size: 18px; font-weight: 800; color: #0f172a;">${dayDate.getDate()}</div>
                        </div>
                        ${dayIncidents.length > 0 ? `<span style="font-size: 10px; font-weight: 800; background: #eff6ff; color: #1d4ed8; padding: 2px 7px; border-radius: 999px;">${dayIncidents.length}</span>` : ''}
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px; flex: 1; overflow-y: auto;">
                        ${cardsHtml}
                    </div>
                </div>
            `;
        }

        weekGridEl.innerHTML = weekHtml;

        const pTotal = document.getElementById('calPeriodTotal');
        const pProg = document.getElementById('calPeriodProgress');
        const pRes = document.getElementById('calPeriodResolved');
        if (pTotal) pTotal.textContent = weekTotal;
        if (pProg) pProg.textContent = weekProgress;
        if (pRes) pRes.textContent = weekResolved;
    }

    function selectCalendarDay(dateStr) {
        calSelectedDayStr = dateStr;
        renderAdminCalendar();
        openDayDrawer(dateStr);
    }

    function openDayDrawer(dateStr) {
        const parts = dateStr.split('-');
        const dObj = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        const dateFormatted = dObj.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
        document.getElementById('drawerDateTitle').textContent = dateFormatted;

        const pad = n => String(n).padStart(2, '0');
        const filtered = rawIncidents.filter(inc => {
            if (!inc.date) return false;
            const d = new Date(inc.date);
            const key = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
            return key === dateStr;
        });

        document.getElementById('drawerDateSubtitle').textContent = `${filtered.length} Incident Report${filtered.length === 1 ? '' : 's'} Logged`;

        const listEl = document.getElementById('drawerIncidentList');
        if (filtered.length === 0) {
            listEl.innerHTML = `
                <div style="text-align: center; padding: 48px 12px; color: #64748b;">
                    <div style="width: 48px; height: 48px; border-radius: 12px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; color: #94a3b8;">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <h4 style="font-size: 15px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0;">No Incidents on this Date</h4>
                    <p style="font-size: 12px; margin: 0;">No community incidents were reported or recorded on this day.</p>
                </div>
            `;
        } else {
            listEl.innerHTML = filtered.map(inc => {
                const isResolved = (inc.status || '').toLowerCase() === 'resolved';
                const cat = inc.type || 'Others';
                const icon = categoryIcons[cat] || '📁';
                let timeStr = 'Recently';
                if (inc.date) {
                    const d = new Date(inc.date);
                    if (!isNaN(d)) timeStr = d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                }

                return `
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-family: monospace; font-weight: 800; color: #0284c7; font-size: 13px;">INC-${String(inc.id).padStart(5, '0')}</span>
                            <span class="badge ${isResolved ? 'badge-success' : 'badge-primary'}" style="padding: 3px 8px; font-size: 10px; font-weight: 800; border-radius: 6px; text-transform: uppercase;">
                                ${isResolved ? 'Resolved' : 'In Progress'}
                            </span>
                        </div>
                        <div>
                            <h4 style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">${escapeHtml(inc.sub)}</h4>
                            <p style="font-size: 12px; color: #475569; margin: 0; line-height: 1.4;">${escapeHtml(inc.desc)}</p>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 8px; margin-top: 4px; font-size: 11px; color: #64748b;">
                            <span>${icon} ${cat} &bull; 👤 ${escapeHtml(inc.res || 'Resident')}</span>
                            <span>🕒 ${timeStr}</span>
                        </div>
                        <button type="button" class="btn btn-outline" onclick="openIncidentModal('${inc.id}')" style="margin-top: 4px; width: 100%; padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; justify-content: center;">
                            View Full Details
                        </button>
                    </div>
                `;
            }).join('');
        }

        document.getElementById('incidentDayDrawer').classList.add('open');
        document.getElementById('incidentDayDrawerOverlay').classList.add('open');
    }

    function closeDayDrawer() {
        document.getElementById('incidentDayDrawer').classList.remove('open');
        document.getElementById('incidentDayDrawerOverlay').classList.remove('open');
    }

    function showCalTooltip(e, id) {
        const inc = rawIncidents.find(i => String(i.id) === String(id));
        if (!inc) return;
        const tooltip = document.getElementById('incidentCalTooltip');
        if (!tooltip) return;

        const cat = inc.type || 'Others';
        const isResolved = (inc.status || '').toLowerCase() === 'resolved';
        let timeStr = '';
        if (inc.date) {
            const d = new Date(inc.date);
            if (!isNaN(d)) timeStr = d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
        }

        tooltip.innerHTML = `
            <div style="font-weight: 800; color: #fff; font-size: 13px; margin-bottom: 2px;">${escapeHtml(inc.sub)}</div>
            <div style="font-size: 11px; color: #94a3b8; display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                <span>INC-${String(inc.id).padStart(5, '0')}</span>
                <span>&bull;</span>
                <span style="color: ${isResolved ? '#34d399' : '#fbbf24'}; font-weight: 700;">${isResolved ? 'Resolved' : 'In Progress'}</span>
            </div>
            <div style="font-size: 11px; color: #cbd5e1; line-height: 1.3;">
                ${escapeHtml(inc.desc.substring(0, 90))}${inc.desc.length > 90 ? '...' : ''}
            </div>
            <div style="font-size: 10.5px; color: #64748b; margin-top: 6px; border-top: 1px solid #334155; padding-top: 4px; display: flex; justify-content: space-between;">
                <span>By: ${escapeHtml(inc.res || 'Resident')}</span>
                <span>${timeStr}</span>
            </div>
        `;

        tooltip.style.display = 'block';
        const x = e.clientX + 12;
        const y = e.clientY + 12;
        tooltip.style.left = Math.min(x, window.innerWidth - 300) + 'px';
        tooltip.style.top = Math.min(y, window.innerHeight - 150) + 'px';
    }

    function hideCalTooltip() {
        const tooltip = document.getElementById('incidentCalTooltip');
        if (tooltip) tooltip.style.display = 'none';
    }

    window.addEventListener('DOMContentLoaded', () => {
        window.incidentsTablePagination = initTablePagination({
            rowSelector: '.incident-row',
            infoEl: '#incidentsTablePageInfo',
            prevEl: '#incidentsTablePagePrev',
            nextEl: '#incidentsTablePageNext',
            perPage: 15,
        });

        if (currentAdminView === 'calendar') {
            switchIncidentView('calendar');
        } else {
            applyIncidentFilters();
        }

        const params = new URLSearchParams(window.location.search);
        if (params.get('incident')) {
            openIncidentModal(params.get('incident'));
        }
    });

    document.getElementById('incidentDateFilter').addEventListener('change', () => {
        applyIncidentFilters();
    });

    document.getElementById('specificDateFilter').addEventListener('change', () => {
        const specVal = document.getElementById('specificDateFilter').value;
        if (specVal) {
            const parts = specVal.split('-');
            calCurrentDate = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
            calSelectedDayStr = specVal;
        }
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
        document.getElementById('specificDateFilter').value = '';
        calLegendCategory = 'all';
        document.querySelectorAll('.cal-legend-pill').forEach(pill => {
            pill.style.opacity = '1';
            pill.style.transform = 'scale(1)';
        });
        applyIncidentFilters();
    }

    function applyIncidentFilters() {
        const query = document.getElementById('incidentSearch').value.toLowerCase().trim();
        const category = document.getElementById('categoryFilter').value.toLowerCase();
        const status = document.getElementById('statusFilter').value.toLowerCase();
        const dateFilter = document.getElementById('incidentDateFilter').value;
        const specDate = document.getElementById('specificDateFilter').value;
        const now = new Date();

        const rows = document.querySelectorAll('.incident-row');
        let visibleCount = 0;
        let noActionCount = 0;
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
            if (specDate && rowDateStr) {
                const incDate = new Date(rowDateStr);
                const pad = n => String(n).padStart(2, '0');
                const rowDateKey = `${incDate.getFullYear()}-${pad(incDate.getMonth()+1)}-${pad(incDate.getDate())}`;
                matchesDate = (rowDateKey === specDate);
            } else if (dateFilter !== 'all' && rowDateStr) {
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
                } else if (rowStatus === 'progress') {
                    progressCount++;
                } else {
                    noActionCount++;
                }
            }
        });

        document.getElementById('statTotal').textContent = visibleCount;
        if (document.getElementById('statNoAction')) document.getElementById('statNoAction').textContent = noActionCount;
        if (document.getElementById('statProgress')) document.getElementById('statProgress').textContent = progressCount;
        if (document.getElementById('statResolved')) document.getElementById('statResolved').textContent = resolvedCount;

        if (window.incidentsTablePagination) {
            window.incidentsTablePagination.resetPage();
        }

        renderAdminCalendar();
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
        const normalizedStatus = (inc.status || 'no_action').toLowerCase();
        document.getElementById('modalIncStatus').value = normalizedStatus === 'resolved' ? 'resolved' : (normalizedStatus === 'progress' ? 'progress' : '');

        const replyInput = document.getElementById('modalIncReply');
        const replyMeta = document.getElementById('modalIncReplyMeta');
        if (replyInput) replyInput.value = inc.admin_reply || '';
        if (replyMeta) {
            if (inc.admin_reply && inc.replied_at) {
                const rDate = new Date(inc.replied_at);
                replyMeta.textContent = !isNaN(rDate)
                    ? 'Last replied: ' + rDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })
                    : 'Replied';
            } else {
                replyMeta.textContent = '';
            }
        }

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
        if (!newStatus) {
            if (window.Swal) {
                Swal.fire({ icon: 'warning', text: 'Please select In Progress or Resolved.' });
            } else {
                alert('Please select In Progress or Resolved.');
            }
            return;
        }
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
                        const statusClass = newStatus === 'resolved' ? 'badge-success' : (newStatus === 'progress' ? 'badge-primary' : 'badge-secondary');
                        const statusLabel = newStatus === 'resolved' ? 'RESOLVED' : (newStatus === 'progress' ? 'IN PROGRESS' : 'NO ACTION');
                        const inlineStyle = newStatus === 'no_action' ? 'background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;' : '';
                        statusTd.innerHTML = `<span class="badge ${statusClass}" style="padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; ${inlineStyle}">${statusLabel}</span>`;
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

    async function sendIncidentReply() {
        if (!currentIncidentId) return;
        const replyEl = document.getElementById('modalIncReply');
        const btn = document.getElementById('btnSendIncReply');
        const replyText = (replyEl?.value || '').trim();
        if (!replyText) {
            if (window.Swal) Swal.fire({ icon: 'warning', text: 'Please type a reply message first.' });
            else alert('Please type a reply message first.');
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Sending...';
        }

        try {
            const response = await fetch(`/admin/incidents/${currentIncidentId}/reply`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ admin_reply: replyText })
            });

            const result = await response.json();
            if (response.ok && result.success) {
                const target = rawIncidents.find(i => String(i.id) === String(currentIncidentId));
                if (target) {
                    target.admin_reply = result.admin_reply;
                    target.replied_at = result.replied_at;
                }
                const metaEl = document.getElementById('modalIncReplyMeta');
                if (metaEl) metaEl.textContent = 'Sent just now';

                if (window.Swal) {
                    Swal.fire({ icon: 'success', title: 'Reply Sent!', text: 'Your reply has been sent to the resident.', timer: 1500, showConfirmButton: false });
                }
            } else {
                if (window.Swal) Swal.fire({ icon: 'error', text: result.message || 'Failed to send reply.' });
                else alert(result.message || 'Failed to send reply.');
            }
        } catch (error) {
            console.error(error);
            if (window.Swal) Swal.fire({ icon: 'error', text: 'An error occurred while sending reply.' });
            else alert('An error occurred while sending reply.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Send Reply';
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

    function openIncidentReportModal() {
        const curCat = document.getElementById('incidentCatFilter')?.value || 'all';
        const curStatus = document.getElementById('incidentStatusFilter')?.value || 'all';
        if (document.getElementById('reportIncCatFilter')) {
            document.getElementById('reportIncCatFilter').value = curCat;
        }
        if (document.getElementById('reportIncStatusFilter')) {
            document.getElementById('reportIncStatusFilter').value = curStatus;
        }
        reloadIncidentReportPdf();
        document.getElementById('incidentReportModal').style.display = 'flex';
    }

    function closeIncidentReportModal() {
        document.getElementById('incidentReportModal').style.display = 'none';
    }

    function reloadIncidentReportPdf() {
        const cat = document.getElementById('reportIncCatFilter')?.value || 'all';
        const status = document.getElementById('reportIncStatusFilter')?.value || 'all';
        const sort = document.getElementById('reportIncSortFilter')?.value || 'date_desc';
        const base = "{{ route('admin.incidents.report.pdf') }}";
        const qs = `?category=${encodeURIComponent(cat)}&status=${encodeURIComponent(status)}&sort=${encodeURIComponent(sort)}`;

        const frame = document.getElementById('incidentReportPdfFrame');
        if (frame) {
            frame.src = base + qs;
        }
        const dlBtn = document.getElementById('incidentReportDownloadBtn');
        if (dlBtn) {
            dlBtn.href = base + qs + '&download=1';
        }
    }

    function printIncidentReportPdfFrame() {
        const frame = document.getElementById('incidentReportPdfFrame');
        if (frame && frame.contentWindow) {
            frame.contentWindow.focus();
            frame.contentWindow.print();
        }
    }
</script>

<!-- Official Incident Management PDF Report Modal -->
<div id="incidentReportModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 2100; background: rgba(15,23,42,0.7); backdrop-filter: blur(8px); padding: 20px;">
    <div class="bill-modal-content" style="max-width: 1100px; width: 95%; max-height: 94vh; overflow: hidden; padding: 20px 24px; border-radius: 20px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); display: flex; flex-direction: column;">
        
        <!-- Controls Bar -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Category</span>
                    <select id="reportIncCatFilter" class="filter-select" onchange="reloadIncidentReportPdf()" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer;">
                        <option value="all">All Categories</option>
                        <option value="Security">Security</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Utilities">Utilities</option>
                        <option value="Others">Others</option>
                    </select>
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Status</span>
                    <select id="reportIncStatusFilter" class="filter-select" onchange="reloadIncidentReportPdf()" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer;">
                        <option value="all">All Statuses</option>
                        <option value="progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Sort By</span>
                    <select id="reportIncSortFilter" class="filter-select" onchange="reloadIncidentReportPdf()" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer;">
                        <option value="date_desc" selected>Date &amp; Time: Newest First</option>
                        <option value="date_asc">Date &amp; Time: Oldest First</option>
                        <option value="id_asc">Ticket ID: Ascending</option>
                        <option value="id_desc">Ticket ID: Descending</option>
                        <option value="sub_asc">Subject: Alphabetical (A to Z)</option>
                        <option value="sub_desc">Subject: Alphabetical (Z to A)</option>
                        <option value="res_asc">Reported By: Alphabetical (A to Z)</option>
                        <option value="res_desc">Reported By: Alphabetical (Z to A)</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
                <a id="incidentReportDownloadBtn" href="{{ route('admin.incidents.report.pdf') }}?download=1" class="btn btn-primary" style="background: #0284c7; border: none; padding: 8px 16px; font-size: 12px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; color: #fff;">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download PDF
                </a>
                <button type="button" class="btn btn-outline" onclick="printIncidentReportPdfFrame()" style="background: #fff; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #334155;">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print
                </button>
                <button type="button" class="btn btn-outline" onclick="closeIncidentReportModal()" style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 600; border-radius: 8px; cursor: pointer;">
                    ✕ Close
                </button>
            </div>
        </div>

        <!-- Embedded Real PDF Viewer (Viewable in-page, no new tab) -->
        <div style="flex: 1; min-height: 0; width: 100%;">
            <iframe id="incidentReportPdfFrame" src="{{ route('admin.incidents.report.pdf') }}" style="width: 100%; height: 75vh; border: 1px solid #e2e8f0; border-radius: 10px; background: #525659;"></iframe>
        </div>

    </div>
</div>
@endsection
