@extends('layouts.admin')

@section('title', 'Incident reports')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-incidents.css') }}">

@php
    $inc_col = collect($incidents);
    $groupedIncidents = $inc_col->groupBy('type');

    $allCategories = ['Security', 'Maintenance', 'Utilities', 'Others'];
    $categoryMeta  = [
        'Security'    => ['icon' => '🛡️', 'color' => '#ef4444', 'bg' => '#fef2f2', 'border' => '#fecaca'],
        'Maintenance' => ['icon' => '🛠️', 'color' => '#f59e0b', 'bg' => '#fffbeb', 'border' => '#fde68a'],
        'Utilities'   => ['icon' => '💡', 'color' => '#3b82f6', 'bg' => '#eff6ff', 'border' => '#bfdbfe'],
        'Others'      => ['icon' => '📁', 'color' => '#8b5cf6', 'bg' => '#f5f3ff', 'border' => '#ddd6fe'],
    ];
@endphp

<div class="incident-show-container fade-in">
    <!-- Page Header -->
    <div class="incident-header">
        <div class="incident-title">
            <h1>Incident Management</h1>
            <p>Monitor and resolve community concerns. Select a category to view its reports.</p>
        </div>
        <div class="incident-header-actions" style="display: flex; gap: 12px; align-items: center;">
            <select id="incidentDateFilter" class="search-input" style="width: 150px; padding-left: 14px; border-radius: 12px; font-weight: 600; cursor: pointer;">
                <option value="all">All Time</option>
                <option value="30days">Last 30 Days</option>
                <option value="this_month">This Month</option>
            </select>
            <div class="incident-search-wrapper">
                <input type="text" id="incidentSearch" placeholder="Search reports..." class="search-input">
                <svg width="18" height="18" fill="none" stroke="var(--incident-text-sub)" stroke-width="2" viewBox="0 0 24 24"
                     style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); pointer-events: none;">
                    <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="responsive-grid grid-4" style="margin-bottom: 32px;">
        <div class="stat-card yellow" style="cursor: default;">
            <div id="stat-total" class="stat-value">0</div>
            <div class="stat-label">Total Reports</div>
        </div>
        <div class="stat-card blue" style="cursor: default;">
            <div id="stat-progress" class="stat-value">0</div>
            <div class="stat-label">In Progress</div>
        </div>
        <div class="stat-card green" style="cursor: default; background: rgba(16, 185, 129, 0.05);">
            <div id="stat-resolved" class="stat-value">0</div>
            <div class="stat-label">Resolved</div>
        </div>
        <div class="stat-card red" style="cursor: default;">
            <div id="stat-pending" class="stat-value">0</div>
            <div class="stat-label">Pending Review</div>
        </div>
    </div>

    <!-- Category Cards -->
    <div style="margin-bottom: 12px;">
        <h2 style="font-size: 14px; font-weight: 700; color: var(--incident-text-sub); text-transform: uppercase; letter-spacing: 0.08em; margin: 0 0 16px;">Browse by Category</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px;">
            @foreach($allCategories as $cat)
            @php
                $meta = $categoryMeta[$cat];
            @endphp
            <button
                onclick="showCategory('{{ $cat }}')"
                id="catBtn-{{ Str::slug($cat) }}"
                style="
                    background: {{ $meta['bg'] }};
                    border: 2px solid {{ $meta['border'] }};
                    border-radius: 16px;
                    padding: 20px 18px;
                    text-align: left;
                    cursor: pointer;
                    transition: all 0.2s;
                    position: relative;
                    outline: none;
                "
                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)';"
                onmouseout="this.style.transform=''; this.style.boxShadow='';"
            >
                <div style="font-size: 28px; margin-bottom: 10px;">{{ $meta['icon'] }}</div>
                <div style="font-size: 15px; font-weight: 800; color: #1e293b; margin-bottom: 6px;">{{ $cat }}</div>
                <div id="cat-content-{{ Str::slug($cat) }}">
                    <!-- JS populated -->
                </div>
            </button>
            @endforeach
        </div>
    </div>

    <!-- Report Panel (shown when a category is selected) -->
    <div id="reportPanel" style="display: none; margin-top: 28px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid var(--border);">
            <span id="panelIcon" style="font-size: 22px;"></span>
            <div>
                <h2 id="panelTitle" style="font-size: 18px; font-weight: 800; color: var(--incident-text-main); margin: 0;"></h2>
                <p id="panelSubtitle" style="font-size: 12px; color: var(--incident-text-sub); margin: 2px 0 0;"></p>
            </div>
            <button onclick="closePanel()" style="margin-left: auto; background: #f1f5f9; border: none; border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 700; color: #64748b; cursor: pointer;">✕ Close</button>
        </div>

        <div id="panelEmpty" style="display: none; padding: 48px; text-align: center; background: #f8fafc; border-radius: 16px; border: 1px dashed #e2e8f0;">
            <div style="font-size: 40px; margin-bottom: 12px;">📭</div>
            <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0;">No reports found</h3>
            <p style="color: #64748b; margin-top: 6px; font-size: 13px;">Adjust your search or date filter to find what you're looking for.</p>
        </div>

        <div id="panelGrid" class="incident-grid"></div>
        
        <div id="paginationControls" style="display:none; margin-top:32px; text-align:center; justify-content:center; align-items:center;">
            <button id="btnPrevPage" class="btn btn-outline" style="padding: 8px 16px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; font-weight: 600;" onclick="changePage(-1)">Previous</button>
            <span id="pageIndicator" style="font-size: 14px; font-weight: 700; color: #475569; margin: 0 16px;">Page 1 of 1</span>
            <button id="btnNextPage" class="btn btn-outline" style="padding: 8px 16px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; font-weight: 600;" onclick="changePage(1)">Next</button>
        </div>
    </div>

    <!-- Incident Detail Modal -->
    <div id="incidentModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 1050; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(12px); position: fixed; top: 0; left: 0; right: 0; bottom: 0;">
        <div class="bill-modal-content" style="max-width: 550px; width: 100%; border-radius: 20px; overflow: hidden; background: #fff; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div class="modal-header" style="border-bottom: 1px solid var(--border); padding: 24px; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <div>
                    <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0;">Incident Details</h2>
                    <p id="modalIncId" style="font-size: 13px; font-weight: 600; color: #64748b; margin: 2px 0 0 0;"></p>
                </div>
                <button class="ann-btn-icon" onclick="closeIncidentModal()" style="padding: 8px; background: none; border: none; cursor: pointer;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <h3 id="modalIncSubject" style="font-size: 18px; font-weight: 800; color: #1e293b; margin: 0 0 4px 0;"></h3>
                    <div style="font-size: 13px; font-weight: 600; color: var(--incident-text-sub);">
                        <span id="modalIncType" style="color: #3b82f6; background: #eff6ff; padding: 2px 8px; border-radius: 12px; margin-right: 8px;"></span>
                        <span id="modalIncDate"></span>
                    </div>
                </div>
                
                <div style="background: #f1f5f9; padding: 16px; border-radius: 12px;">
                    <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Description</span>
                    <p id="modalIncDesc" style="font-size: 14px; color: #334155; margin: 8px 0 0 0; line-height: 1.5;"></p>
                </div>

                <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Attached Media</span>
                    <div id="modalIncMedia" style="margin-top: 12px; width: 100%; height: 160px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; border: 2px dashed #cbd5e1; color: #94a3b8; font-size: 13px; font-weight: 600;">
                        <!-- JS injected -->
                    </div>
                </div>

                <div class="responsive-grid grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                        <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Reported By</span>
                        <div id="modalIncRes" style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 4px;"></div>
                    </div>
                    <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                        <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Update Status</span>
                        <select id="modalIncStatus" class="search-input" style="width: 100%; margin-top: 8px; padding: 10px; border-radius: 8px; font-weight: 600; background: #fff; border: 1px solid #cbd5e1;">
                            <option value="pending">Pending</option>
                            <option value="progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                </div>
            </div>
            <div style="padding: 16px 24px; background: #f8fafc; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 12px;">
                <button class="btn btn-outline" style="padding: 8px 16px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; font-weight: 600;" onclick="closeIncidentModal()">Cancel</button>
                <button class="btn btn-primary" style="padding: 12px 28px; border-radius: 10px; background: linear-gradient(135deg, #059669, #10b981); color: #fff; font-weight: 700; font-size: 14px; border: none; cursor: pointer; box-shadow: 0 4px 14px rgba(16,185,129,0.4); transition: all 0.2s;" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 6px 20px rgba(16,185,129,0.5)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 14px rgba(16,185,129,0.4)'" onclick="saveIncidentStatus()">✅ Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Fullscreen Image Viewer -->
    <div id="fullscreenImageViewer" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 9999; align-items: center; justify-content: center; flex-direction: column;">
        <button onclick="closeFullscreenImage()" style="position: absolute; top: 20px; right: 20px; background: none; border: none; color: white; font-size: 36px; cursor: pointer;">&times;</button>
        <img id="fullscreenImage" src="" style="max-width: 90%; max-height: 90%; object-fit: contain; border-radius: 8px;">
    </div>

    <!-- Hidden data store for JS -->
    <div id="incidentData" style="display:none;">
    @foreach($groupedIncidents as $category => $items)
        @foreach($items as $inc)
        <div
            class="inc-data"
            data-cat="{{ $category }}"
            data-id="{{ $inc['id'] }}"
            data-sub="{{ $inc['sub'] }}"
            data-type="{{ $inc['type'] }}"
            data-status="{{ $inc['status'] }}"
            data-desc="{{ Str::limit($inc['desc'], 90) }}"
            data-res="{{ $inc['res'] }}"
            data-href="/admin/incidents/{{ $inc['id'] }}"
        ></div>
        @endforeach
    @endforeach
    </div>
</div>

<script>
    const rawIncidents = @json($incidents);
    const categoryMeta = {
        'Security':    { icon: '🛡️', color: '#ef4444' },
        'Maintenance': { icon: '🛠️', color: '#f59e0b' },
        'Utilities':   { icon: '💡', color: '#3b82f6' },
        'Others':      { icon: '📁', color: '#8b5cf6' },
    };

    let activeCategory = null;

    document.addEventListener('DOMContentLoaded', function() {
        recalculateIncidentMetrics();
    });

    function recalculateIncidentMetrics() {
        let incidents = rawIncidents;
        const dateFilter = document.getElementById('incidentDateFilter').value;
        const now = new Date();
        
        incidents = incidents.filter(d => {
            if (dateFilter === 'all') return true;
            let incDate = new Date(d.date);
            if (isNaN(incDate.getTime())) incDate = new Date(); 
            
            if (dateFilter === '30days') {
                const diffTime = Math.abs(now - incDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
                return diffDays <= 30;
            }
            if (dateFilter === 'this_month') {
                return incDate.getMonth() === now.getMonth() && incDate.getFullYear() === now.getFullYear();
            }
            return true;
        });
        
        document.getElementById('stat-total').textContent = incidents.length;
        document.getElementById('stat-progress').textContent = incidents.filter(i => i.status === 'progress').length;
        document.getElementById('stat-resolved').textContent = incidents.filter(i => i.status === 'resolved').length;
        document.getElementById('stat-pending').textContent = incidents.filter(i => i.status === 'pending').length;

        const categories = ['Security', 'Maintenance', 'Utilities', 'Others'];
        categories.forEach(cat => {
            const catData = incidents.filter(i => (i.type || '').toLowerCase() === cat.toLowerCase());
            const catCount = catData.length;
            const pendingCount = catData.filter(i => i.status === 'pending').length;
            const slug = cat.toLowerCase().replace(/\s+/g, '-');
            const container = document.getElementById('cat-content-' + slug);
            
            if (container) {
                if (catCount > 0) {
                    const color = categoryMeta[cat]?.color || '#3b82f6';
                    let html = `<div style="font-size: 22px; font-weight: 900; color: ${color}; line-height: 1;">${catCount}</div>
                                <div style="font-size: 11px; font-weight: 600; color: #64748b; margin-top: 2px;">
                                    ${catCount === 1 ? 'report' : 'reports'}`;
                    if (pendingCount > 0) {
                        html += ` &nbsp;&middot;&nbsp;<span style="color: #ef4444;">${pendingCount} pending</span>`;
                    }
                    html += `</div>`;
                    container.innerHTML = html;
                } else {
                    container.innerHTML = `<div style="font-size: 13px; font-weight: 600; color: #94a3b8; font-style: italic;">No reports</div>`;
                }
            }
        });
    }

    let currentPage = 1;
    const ITEMS_PER_PAGE = 6;
    let filteredData = [];

    document.getElementById('incidentDateFilter').addEventListener('change', () => {
        recalculateIncidentMetrics();
        if (activeCategory) {
            currentPage = 1;
            renderGrid();
        }
    });

    document.getElementById('incidentSearch').addEventListener('input', () => {
        currentPage = 1;
        renderGrid();
    });

    function applyFiltersAndSearch(dataArr) {
        const dateFilter = document.getElementById('incidentDateFilter').value;
        const now = new Date();
        
        let filtered = dataArr.filter(d => {
            if (dateFilter === 'all') return true;
            let incDate = new Date(d.date);
            if (isNaN(incDate.getTime())) incDate = new Date(); 
            
            if (dateFilter === '30days') {
                const diffTime = Math.abs(now - incDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
                return diffDays <= 30;
            }
            if (dateFilter === 'this_month') {
                return incDate.getMonth() === now.getMonth() && incDate.getFullYear() === now.getFullYear();
            }
            return true;
        });

        const q = document.getElementById('incidentSearch').value.toLowerCase();
        if (q) {
            filtered = filtered.filter(d => {
                const text = `${d.type} ${d.sub} ${d.res} ${d.desc} ${d.status}`.toLowerCase();
                return text.includes(q);
            });
        }
        return filtered;
    }

    function showCategory(cat) {
        document.querySelectorAll('[id^="catBtn-"]').forEach(btn => {
            btn.style.outline = 'none';
            btn.style.borderWidth = '2px';
        });
        const slug = cat.toLowerCase().replace(/\s+/g, '-');
        const activeBtn = document.getElementById('catBtn-' + slug);
        if (activeBtn) {
            activeBtn.style.outline = '3px solid ' + (categoryMeta[cat]?.color || '#3b82f6');
            activeBtn.style.outlineOffset = '2px';
        }

        activeCategory = cat;
        currentPage = 1;
        
        const storeIncidents = rawIncidents;
        const catData = storeIncidents.filter(d => (d.type || '').toLowerCase() === cat.toLowerCase());

        const panel    = document.getElementById('reportPanel');
        const title    = document.getElementById('panelTitle');
        const subtitle = document.getElementById('panelSubtitle');
        const icon     = document.getElementById('panelIcon');

        icon.textContent  = categoryMeta[cat]?.icon || '📋';
        title.textContent = cat + ' Reports';
        
        const matchCount = applyFiltersAndSearch(catData).length;
        subtitle.textContent = matchCount + ' report' + (matchCount !== 1 ? 's' : '') + ' found';

        renderGrid();
        
        panel.style.display = 'block';
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function renderGrid() {
        if(!activeCategory) return;
        
        const storeIncidents = rawIncidents;
        const catData = storeIncidents.filter(d => (d.type || '').toLowerCase() === activeCategory.toLowerCase());
        
        filteredData = applyFiltersAndSearch(catData);
        
        const grid = document.getElementById('panelGrid');
        const empty = document.getElementById('panelEmpty');
        const pagination = document.getElementById('paginationControls');
        
        grid.innerHTML = '';

        if (filteredData.length === 0) {
            empty.style.display = 'block';
            grid.style.display  = 'none';
            pagination.style.display = 'none';
        } else {
            empty.style.display = 'none';
            grid.style.display  = 'grid';
            
            const totalPages = Math.ceil(filteredData.length / ITEMS_PER_PAGE);
            if (currentPage > totalPages) currentPage = totalPages;
            
            const startIndex = (currentPage - 1) * ITEMS_PER_PAGE;
            const pageData = filteredData.slice(startIndex, startIndex + ITEMS_PER_PAGE);
            
            pageData.forEach(d => {
                const statusLabel = { pending: 'Pending', progress: 'In Progress', resolved: 'Resolved' }[d.status] || d.status;
                let formattedDate = 'Today';
                if (d.date) {
                    const dObj = new Date(d.date);
                    if (!isNaN(dObj)) {
                        formattedDate = dObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
                    }
                }
                grid.innerHTML += `
                    <div class="incident-card" style="display:flex; flex-direction:column; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1)';" onmouseout="this.style.transform=''; this.style.boxShadow='var(--shadow-sm)';" onclick="openIncidentModal('${d.id}')">
                        <div class="status-dot">
                            <span class="badge-premium badge-${d.status}">${statusLabel}</span>
                        </div>
                        <div class="incident-card-header">
                            <span class="incident-type">${d.type}</span>
                            <h3 class="incident-subject">${d.sub}</h3>
                            <div class="incident-meta">
                                <span class="incident-id">${d.id}</span>
                                <span>&bull;</span>
                                <span>${d.res || 'Resident'}</span>
                            </div>
                        </div>
                        <p class="incident-preview">${d.desc}</p>
                        <div class="incident-footer" style="margin-top:12px; border-top:1px solid #f1f5f9; padding-top:8px;">
                            <div style="font-size:11px;font-weight:600;color:var(--incident-text-sub);">${formattedDate}</div>
                        </div>
                    </div>`;
            });
            
            if (totalPages > 1) {
                pagination.style.display = 'flex';
                document.getElementById('pageIndicator').textContent = `Page ${currentPage} of ${totalPages}`;
                document.getElementById('btnPrevPage').disabled = currentPage === 1;
                document.getElementById('btnNextPage').disabled = currentPage === totalPages;
                
                document.getElementById('btnPrevPage').style.opacity = currentPage === 1 ? '0.5' : '1';
                document.getElementById('btnNextPage').style.opacity = currentPage === totalPages ? '0.5' : '1';
                document.getElementById('btnPrevPage').style.cursor = currentPage === 1 ? 'not-allowed' : 'pointer';
                document.getElementById('btnNextPage').style.cursor = currentPage === totalPages ? 'not-allowed' : 'pointer';
            } else {
                pagination.style.display = 'none';
            }
        }
    }

    function changePage(delta) {
        const totalPages = Math.ceil(filteredData.length / ITEMS_PER_PAGE);
        currentPage += delta;
        if (currentPage < 1) currentPage = 1;
        if (currentPage > totalPages) currentPage = totalPages;
        renderGrid();
    }

    function closePanel() {
        document.getElementById('reportPanel').style.display = 'none';
        document.querySelectorAll('[id^="catBtn-"]').forEach(btn => {
            btn.style.outline = 'none';
            btn.style.borderWidth = '2px';
        });
        activeCategory = null;
    }

    let currentIncidentId = null;

    function openIncidentModal(id) {
        if (new URLSearchParams(window.location.search).get('incident') !== id) {
            window.history.pushState(null, '', '?incident=' + id);
        }
        const incidents = rawIncidents;
        const inc = incidents.find(i => i.id === id);
        if (!inc) return;

        currentIncidentId = id;

        document.getElementById('modalIncId').textContent = inc.id;
        document.getElementById('modalIncSubject').textContent = inc.sub;
        document.getElementById('modalIncType').textContent = inc.type;
        let formattedDate = 'Today';
        if (inc.date) {
            const dObj = new Date(inc.date);
            if (!isNaN(dObj)) {
                formattedDate = dObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
            }
        }
        document.getElementById('modalIncDate').textContent = formattedDate;
        document.getElementById('modalIncDesc').textContent = inc.desc;
        document.getElementById('modalIncRes').textContent = inc.res || 'Resident';
        document.getElementById('modalIncStatus').value = inc.status;

        const mediaContainer = document.getElementById('modalIncMedia');
        if (inc.photos && inc.photos.length > 0) {
            let photosHtml = '';
            inc.photos.forEach(p => {
                photosHtml += `<img src="${p}" onclick="openFullscreenImage('${p}')" style="height: 100%; max-width: 250px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1; flex-shrink: 0; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'" alt="Attachment">`;
            });
            mediaContainer.innerHTML = `<div style="display: flex; gap: 12px; width: 100%; height: 100%; overflow-x: auto; padding-bottom: 4px;">${photosHtml}</div>`;
            mediaContainer.style.border = 'none';
            mediaContainer.style.background = 'transparent';
        } else if (inc.img) {
            mediaContainer.innerHTML = `<img src="${inc.img}" onclick="openFullscreenImage('${inc.img}')" style="width:100%; height:100%; object-fit:cover; border-radius:6px; cursor: pointer;" alt="Attachment">`;
            mediaContainer.style.border = 'none';
        } else {
            mediaContainer.innerHTML = `<div style="text-align:center;">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-bottom:8px; opacity:0.6; display:inline-block;"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <br>No media attached
            </div>`;
            mediaContainer.style.border = '2px dashed #cbd5e1';
            mediaContainer.style.background = '#f1f5f9';
        }

        document.getElementById('incidentModal').style.display = 'flex';
    }

    function closeIncidentModal() {
        window.history.replaceState(null, '', window.location.pathname);
        document.getElementById('incidentModal').style.display = 'none';
        currentIncidentId = null;
    }

    async function saveIncidentStatus() {
        if (!currentIncidentId) return;
        const newStatus = document.getElementById('modalIncStatus').value;
        
        try {
            const response = await fetch(`/api/incidents/${currentIncidentId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ status: newStatus })
            });
            
            const result = await response.json();
            if (result.success) {
                window.location.reload(); // Reload to fetch fresh data from DB
            } else {
                alert('Failed to update status.');
            }
        } catch (error) {
            console.error(error);
            alert('An error occurred.');
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
