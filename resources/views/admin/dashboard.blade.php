@extends('layouts.admin')

@section('title', 'Dashboard Overview')

@section('content')
    <!-- Stats Row -->
    <div class="stats-grid fade-up">
        <div class="stat-card green">
            <div class="stat-value">₱ {{ number_format($monthlyRevenue, 2) }}</div>
            <div class="stat-label">Revenue This Month</div>
        </div>
        <div class="stat-card blue">
            <div class="stat-value">{{ $totalResidentsCount }}</div>
            <div class="stat-label">Total Residents</div>
        </div>
        <div class="stat-card yellow">
            <div class="stat-value" style="margin-top: 10px;">{{ $appointmentsTodayCount ?? 0 }}</div>
            <div class="stat-label">Appointments Today</div>
        </div>
        <div class="stat-card red">
            <div class="stat-value">{{ $openIncidentsCount ?? 0 }}</div>
            <div class="stat-label">Open Incidents</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="responsive-grid grid-2-1 fade-up-2" style="margin-bottom:24px;">
        <div class="card">
            <div class="card-header">
                <div>
                    <span class="card-title">Monthly Sales Performance</span>
                    <div style="font-size: 12px; color: #64748b; font-weight: 600; margin-top: 2px;">Total Annual Revenue & Monthly Trends</div>
                </div>
            </div>
            <div style="height: 220px; position: relative; margin-top: 12px;">
                <canvas id="dashboardSalesChart"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span class="card-title">Utility Payment Status</span></div>
            <div class="donut-wrap" style="margin-top:6px;">
                <svg width="90" height="90" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e2e8f0" stroke-width="3.8"/>
                    @php
                        $paidStroke = ($collectionPercent / 100) * 100;
                        $pendingStroke = $totalBills > 0 ? ($billsPending / $totalBills) * 100 : 0;
                        $overdueStroke = $totalBills > 0 ? ($billsOverdue / $totalBills) * 100 : 0;
                        
                        $pendingOffset = 100 - $paidStroke + 25;
                        $overdueOffset = $pendingOffset - $pendingStroke;
                    @endphp
                    
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#4caf7d" stroke-width="3.8" stroke-dasharray="{{ $paidStroke }} {{ 100 - $paidStroke }}" stroke-dashoffset="25"/>
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f5a623" stroke-width="3.8" stroke-dasharray="{{ $pendingStroke }} {{ 100 - $pendingStroke }}" stroke-dashoffset="{{ $pendingOffset }}"/>
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e05c5c" stroke-width="3.8" stroke-dasharray="{{ $overdueStroke }} {{ 100 - $overdueStroke }}" stroke-dashoffset="{{ $overdueOffset }}"/>
                    <text x="18" y="20.5" text-anchor="middle" font-size="6" fill="#1a1a1a" font-weight="700">{{ $collectionPercent }}%</text>
                </svg>
                <div class="donut-legend">
                    <div class="donut-legend-item"><span class="donut-dot" style="background:#4caf7d"></span>Paid — {{ $billsPaid }}</div>
                    <div class="donut-legend-item"><span class="donut-dot" style="background:#f5a623"></span>Pending — {{ $billsPending }}</div>
                    <div class="donut-legend-item"><span class="donut-dot" style="background:#e05c5c"></span>Overdue — {{ $billsOverdue }}</div>
                </div>
            </div>
            <div class="progress-wrap">
                <div class="progress-label"><span>Collection Progress</span><span>{{ $collectionPercent }}%</span></div>
                <div class="progress-bar"><div class="progress-fill" style="width:{{ $collectionPercent }}%" id="dash-progress"></div></div>
            </div>
        </div>
    </div>

    <!-- Operations Calendars Section: Incident & Appointment Dual Suite -->
    <div class="fade-up-2" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
            <div>
                <div style="font-size: 18px; font-weight: 800; color: var(--text-dark, #0f172a); display: flex; align-items: center; gap: 8px;">
                    <span>Operations &amp; Schedule Calendars</span>
                    <span class="badge" style="background: #eff6ff; color: #1d4ed8; font-weight: 700; font-size: 11px; padding: 2px 8px; border-radius: 999px;">Live Overview</span>
                </div>
                <p style="font-size: 12.5px; color: var(--text-mid, #64748b); margin: 3px 0 0 0;">Interactive calendar tracking for reported community incidents and incoming buyer lot inquiries.</p>
            </div>
            
            <div style="display: inline-flex; background: #e2e8f0; padding: 3px; border-radius: 10px; gap: 3px;">
                <button type="button" id="btnDashDualView" onclick="setDashboardCalendarView('dual')" class="dash-view-tab active" style="padding: 6px 14px; font-size: 12px; font-weight: 700; border-radius: 7px; border: none; cursor: pointer; transition: all 0.15s; background: #fff; color: #0f172a; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: inline-flex; align-items: center; gap: 6px;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="8" height="18" rx="1"/><rect x="13" y="3" width="8" height="18" rx="1"/></svg>
                    Dual Calendars
                </button>
                <button type="button" id="btnDashIncidentView" onclick="setDashboardCalendarView('incidents')" class="dash-view-tab" style="padding: 6px 14px; font-size: 12px; font-weight: 700; border-radius: 7px; border: none; cursor: pointer; transition: all 0.15s; background: transparent; color: #64748b; display: inline-flex; align-items: center; gap: 6px;">
                    <span>🛡️</span>
                    Incidents
                </button>
                <button type="button" id="btnDashAppointmentView" onclick="setDashboardCalendarView('appointments')" class="dash-view-tab" style="padding: 6px 14px; font-size: 12px; font-weight: 700; border-radius: 7px; border: none; cursor: pointer; transition: all 0.15s; background: transparent; color: #64748b; display: inline-flex; align-items: center; gap: 6px;">
                    <span>📅</span>
                    Appointments
                </button>
            </div>
        </div>

        <div class="responsive-grid grid-2" id="dashCalendarsGrid" style="gap: 20px;">
            <!-- Calendar 1: Incident Reports Calendar -->
            <div class="dash-cal-card" id="dashIncCard">
                <div class="dash-cal-toolbar">
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <span class="dash-cal-title">
                            <span>🛡️</span> Incident Tracking
                        </span>
                        <div style="display: flex; gap: 6px; font-size: 11px;">
                            <span id="dashIncActiveBadge" style="background: #fef3c7; color: #b45309; padding: 2px 7px; border-radius: 999px; font-weight: 700;">0 Active</span>
                            <span id="dashIncResolvedBadge" style="background: #d1fae5; color: #065f46; padding: 2px 7px; border-radius: 999px; font-weight: 700;">0 Resolved</span>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="dash-cal-nav">
                            <button type="button" class="dash-cal-btn" onclick="dashIncPrev()" title="Previous Month">‹</button>
                            <button type="button" class="dash-cal-btn" onclick="dashIncToday()">Today</button>
                            <button type="button" class="dash-cal-btn" onclick="dashIncNext()" title="Next Month">›</button>
                        </div>
                        <span id="dashIncMonthTitle" style="font-size: 13.5px; font-weight: 800; color: #0f172a; min-width: 120px; text-align: right;"></span>
                        <a href="/admin/incidents" style="font-size: 11.5px; font-weight: 700; color: #0284c7; text-decoration: none; padding: 4px 8px; border-radius: 6px; background: #f0f9ff; margin-left: 4px;">Manage &rarr;</a>
                    </div>
                </div>

                <div class="dash-cal-legend-bar">
                    <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                        <span onclick="toggleDashIncCat('all')" class="dash-cal-legend-pill active" id="dashIncPill-all" style="background: #f1f5f9; color: #334155; font-weight: 800;">All</span>
                        <span onclick="toggleDashIncCat('security')" class="dash-cal-legend-pill" id="dashIncPill-security" style="background: #fef2f2; color: #b91c1c; border-color: #fecaca;">🛡️ Security</span>
                        <span onclick="toggleDashIncCat('maintenance')" class="dash-cal-legend-pill" id="dashIncPill-maintenance" style="background: #fffbeb; color: #b45309; border-color: #fde68a;">🛠️ Maint</span>
                        <span onclick="toggleDashIncCat('utilities')" class="dash-cal-legend-pill" id="dashIncPill-utilities" style="background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe;">💡 Util</span>
                        <span onclick="toggleDashIncCat('others')" class="dash-cal-legend-pill" id="dashIncPill-others" style="background: #f5f3ff; color: #6d28d9; border-color: #ddd6fe;">📁 Other</span>
                    </div>
                    <span style="color: #94a3b8; font-size: 10.5px;">Click date to view details</span>
                </div>

                <div class="dash-cal-grid" id="dashIncGrid">
                    <!-- JS populated -->
                </div>
            </div>

            <!-- Calendar 2: Appointments & Lot Inquiries Calendar -->
            <div class="dash-cal-card" id="dashAptCard">
                <div class="dash-cal-toolbar">
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <span class="dash-cal-title">
                            <span>📅</span> Lot &amp; Visit Inquiries
                        </span>
                        <div style="display: flex; gap: 6px; font-size: 11px;">
                            <span id="dashAptScheduledBadge" style="background: #d1fae5; color: #065f46; padding: 2px 7px; border-radius: 999px; font-weight: 700;">0 Scheduled</span>
                            <span id="dashAptPendingBadge" style="background: #fef3c7; color: #b45309; padding: 2px 7px; border-radius: 999px; font-weight: 700;">0 Pending</span>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="dash-cal-nav">
                            <button type="button" class="dash-cal-btn" onclick="dashAptPrev()" title="Previous Month">‹</button>
                            <button type="button" class="dash-cal-btn" onclick="dashAptToday()">Today</button>
                            <button type="button" class="dash-cal-btn" onclick="dashAptNext()" title="Next Month">›</button>
                        </div>
                        <span id="dashAptMonthTitle" style="font-size: 13.5px; font-weight: 800; color: #0f172a; min-width: 120px; text-align: right;"></span>
                        <a href="/admin/appointments" style="font-size: 11.5px; font-weight: 700; color: #059669; text-decoration: none; padding: 4px 8px; border-radius: 6px; background: #ecfdf5; margin-left: 4px;">Pipeline &rarr;</a>
                    </div>
                </div>

                <div class="dash-cal-legend-bar">
                    <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                        <span onclick="toggleDashAptStatus('all')" class="dash-cal-legend-pill active" id="dashAptPill-all" style="background: #f1f5f9; color: #334155; font-weight: 800;">All</span>
                        <span onclick="toggleDashAptStatus('pending')" class="dash-cal-legend-pill" id="dashAptPill-pending" style="background: #fef3c7; color: #b45309; border-color: #fde68a;">Pending</span>
                        <span onclick="toggleDashAptStatus('scheduled')" class="dash-cal-legend-pill" id="dashAptPill-scheduled" style="background: #d1fae5; color: #065f46; border-color: #a7f3d0;">Scheduled</span>
                        <span onclick="toggleDashAptStatus('completed')" class="dash-cal-legend-pill" id="dashAptPill-completed" style="background: #f1f5f9; color: #475569; border-color: #cbd5e1;">Completed</span>
                    </div>
                    <span style="color: #94a3b8; font-size: 10.5px;">Click date to view details</span>
                </div>

                <div class="dash-cal-grid" id="dashAptGrid">
                    <!-- JS populated -->
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Day Details Modal -->
    <div id="dashDayModal" class="dash-day-modal" onclick="if(event.target===this) closeDashDayModal()">
        <div class="dash-day-modal-content">
            <div style="padding: 18px 22px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <div>
                    <h3 id="dashDayModalTitle" style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;"></h3>
                    <p id="dashDayModalSubtitle" style="font-size: 12px; color: #64748b; margin: 2px 0 0 0; font-weight: 600;"></p>
                </div>
                <button type="button" onclick="closeDashDayModal()" style="background: none; border: none; font-size: 20px; color: #64748b; cursor: pointer; padding: 4px 8px;">✕</button>
            </div>
            <div id="dashDayModalBody" style="flex: 1; overflow-y: auto; padding: 18px 22px; display: flex; flex-direction: column; gap: 12px;">
                <!-- JS populated -->
            </div>
            <div style="padding: 12px 22px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <a id="dashDayModalActionBtn" href="#" class="btn btn-outline btn-sm" style="font-weight: 700;">Open Management Page &rarr;</a>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeDashDayModal()" style="background: #fff;">Close</button>
            </div>
        </div>
    </div>

    <!-- Floating Tooltip -->
    <div id="dashCalTooltip" class="dash-cal-tooltip"></div>

    <!-- Bottom Row -->
    <div class="responsive-grid grid-2 fade-up-3">
        <!-- Recent Activity Feed -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Notifications</span>
                <span class="badge badge-info">Live</span>
            </div>
            <div class="activity-feed">
                @forelse($notifications as $noti)
                <div class="activity-item">
                    <span class="activity-dot" style="background:{{ $noti['color'] }}"></span>
                    <div class="activity-content">
                        <strong>{{ $noti['title'] }}</strong>
                        <span>{{ $noti['desc'] }} · {{ $noti['time'] }}</span>
                    </div>
                </div>
                @empty
                <div style="padding: 24px; text-align: center; color: var(--text-mid); font-size: 13px;">
                    No new notifications.
                </div>
                @endforelse
            </div>
        </div>

        <!-- Quick Access / Summary -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Today's Active Appointments</span>
                <a href="/admin/appointments" class="btn btn-outline btn-sm">View Calendar</a>
            </div>
            
            <div style="display:flex; flex-direction:column; gap:12px;">
                @forelse($todaysAppointmentsList as $apt)
                <div style="display:flex; justify-content:space-between; align-items:center; padding:14px; background:var(--bg); border-radius:10px; border:1px solid var(--border);">
                    <div>
                        <div style="font-size:14px; font-weight:600; color:var(--text-dark);">{{ $apt->client_name }}</div>
                        <div style="font-size:12.5px; color:var(--text-mid); margin-top:2px;">{{ \Carbon\Carbon::parse($apt->time)->format('h:i A') }} · {{ $apt->type ?? 'General' }}</div>
                    </div>
                    @if(strtolower($apt->status) == 'pending')
                        <span class="badge badge-warning">Pending</span>
                    @elseif(strtolower($apt->status) == 'approved' || strtolower($apt->status) == 'checked_in')
                        <span class="badge badge-success">Approved</span>
                    @else
                        <span class="badge badge-danger">{{ ucfirst($apt->status) }}</span>
                    @endif
                </div>
                @empty
                <div style="padding: 24px; text-align: center; color: var(--text-mid); font-size: 13px;">
                    No appointments scheduled for today.
                </div>
                @endforelse
            </div>

            <div style="margin-top:24px; padding-top:20px; border-top:1px solid var(--border); display:flex; gap:10px;">
                <a href="/admin/billing" class="btn btn-primary" style="flex:1;">🧾 Post Billing</a>
                <a href="/admin/announcements" class="btn btn-outline" style="flex:1;">📢 New Announcement</a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('dashboardSalesChart')?.getContext('2d');
            if (ctx) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode($monthlySalesLabels) !!},
                        datasets: [{
                            label: 'Monthly Sales',
                            data: {!! json_encode($monthlySalesData) !!},
                            backgroundColor: '#10b981',
                            borderRadius: 6,
                            borderSkipped: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Sales: ₱' + context.raw.toLocaleString();
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 500000,
                                ticks: {
                                    callback: function(value) {
                                        return '₱' + (value / 1000) + 'k';
                                    },
                                    font: { weight: '600', size: 11 },
                                    color: '#64748b'
                                },
                                grid: { color: '#f1f5f9' }
                            },
                            x: {
                                ticks: { font: { weight: '600', size: 11 }, color: '#64748b' },
                                grid: { display: false }
                            }
                        }
                    }
                });
            }

            // Initialize Dual Dashboard Calendars
            initDashboardCalendars();
        });

        // ==========================================
        // DUAL DASHBOARD CALENDARS JAVASCRIPT LOGIC
        // ==========================================
        const dashIncidentsData = @json($dashboardIncidents ?? []);
        const dashAppointmentsData = @json($dashboardAppointments ?? []);

        let dashIncDate = new Date();
        let dashAptDate = new Date();
        let dashIncFilterCat = 'all';
        let dashAptFilterStatus = 'all';
        let dashActiveView = 'dual';

        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        const dayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

        function padZero(num) {
            return String(num).padStart(2, '0');
        }

        function formatDateKey(dateObj) {
            return `${dateObj.getFullYear()}-${padZero(dateObj.getMonth() + 1)}-${padZero(dateObj.getDate())}`;
        }

        function formatDisplayDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr + 'T00:00:00');
            return d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }

        function formatTimeDisplay(timeStr) {
            if (!timeStr) return '';
            if (timeStr.includes(':')) {
                const parts = timeStr.split(':');
                let h = parseInt(parts[0], 10);
                const m = parts[1].substring(0, 2);
                const ampm = h >= 12 ? 'PM' : 'AM';
                h = h % 12;
                h = h ? h : 12;
                return `${h}:${m} ${ampm}`;
            }
            return timeStr;
        }

        // --- View Mode Switcher ---
        function setDashboardCalendarView(view) {
            dashActiveView = view;
            const btnDual = document.getElementById('btnDashDualView');
            const btnInc = document.getElementById('btnDashIncidentView');
            const btnApt = document.getElementById('btnDashAppointmentView');
            const cardInc = document.getElementById('dashIncCard');
            const cardApt = document.getElementById('dashAptCard');
            const grid = document.getElementById('dashCalendarsGrid');

            const activeBtnStyle = 'padding: 6px 14px; font-size: 12px; font-weight: 700; border-radius: 7px; border: none; cursor: pointer; transition: all 0.15s; background: #fff; color: #0f172a; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: inline-flex; align-items: center; gap: 6px;';
            const inactiveBtnStyle = 'padding: 6px 14px; font-size: 12px; font-weight: 700; border-radius: 7px; border: none; cursor: pointer; transition: all 0.15s; background: transparent; color: #64748b; display: inline-flex; align-items: center; gap: 6px;';

            if (btnDual) btnDual.style = view === 'dual' ? activeBtnStyle : inactiveBtnStyle;
            if (btnInc) btnInc.style = view === 'incidents' ? activeBtnStyle : inactiveBtnStyle;
            if (btnApt) btnApt.style = view === 'appointments' ? activeBtnStyle : inactiveBtnStyle;

            if (view === 'dual') {
                if (cardInc) cardInc.style.display = 'block';
                if (cardApt) cardApt.style.display = 'block';
                if (grid) grid.style.gridTemplateColumns = '';
            } else if (view === 'incidents') {
                if (cardInc) cardInc.style.display = 'block';
                if (cardApt) cardApt.style.display = 'none';
                if (grid) grid.style.gridTemplateColumns = '1fr';
            } else if (view === 'appointments') {
                if (cardInc) cardInc.style.display = 'none';
                if (cardApt) cardApt.style.display = 'block';
                if (grid) grid.style.gridTemplateColumns = '1fr';
            }
        }

        // --- Tooltip Logic ---
        function showDashTooltip(e, title, subtitle, statusText) {
            const tip = document.getElementById('dashCalTooltip');
            if (!tip) return;
            tip.innerHTML = `
                <div style="font-weight: 800; font-size: 12px; color: #38bdf8; margin-bottom: 2px;">${title}</div>
                <div style="font-size: 11px; color: #cbd5e1; margin-bottom: 4px;">${subtitle}</div>
                <div style="font-size: 10px; font-weight: 700; color: #f8fafc; background: rgba(255,255,255,0.12); display: inline-block; padding: 1px 6px; border-radius: 4px;">${statusText}</div>
            `;
            tip.style.display = 'block';
            tip.style.left = (e.clientX + 14) + 'px';
            tip.style.top = (e.clientY + 14) + 'px';
        }

        function hideDashTooltip() {
            const tip = document.getElementById('dashCalTooltip');
            if (tip) tip.style.display = 'none';
        }

        // --- Incident Calendar Helpers & Render ---
        function getIncidentCategoryMeta(cat) {
            const c = (cat || '').toLowerCase();
            if (c.includes('sec')) return { label: 'Security', icon: '🛡️', bg: '#fef2f2', color: '#b91c1c', border: '#fecaca' };
            if (c.includes('maint')) return { label: 'Maintenance', icon: '🛠️', bg: '#fffbeb', color: '#b45309', border: '#fde68a' };
            if (c.includes('util')) return { label: 'Utilities', icon: '💡', bg: '#eff6ff', color: '#1d4ed8', border: '#bfdbfe' };
            if (c.includes('nois')) return { label: 'Noise', icon: '🔊', bg: '#fdf2f8', color: '#be185d', border: '#fbcfe8' };
            return { label: 'Others', icon: '📁', bg: '#f5f3ff', color: '#6d28d9', border: '#ddd6fe' };
        }

        function dashIncPrev() {
            dashIncDate.setMonth(dashIncDate.getMonth() - 1);
            renderDashIncidentCalendar();
        }
        function dashIncNext() {
            dashIncDate.setMonth(dashIncDate.getMonth() + 1);
            renderDashIncidentCalendar();
        }
        function dashIncToday() {
            dashIncDate = new Date();
            renderDashIncidentCalendar();
        }

        function toggleDashIncCat(cat) {
            dashIncFilterCat = cat;
            document.querySelectorAll('[id^="dashIncPill-"]').forEach(pill => {
                pill.style.boxShadow = 'none';
                pill.style.opacity = '0.7';
            });
            const activePill = document.getElementById(`dashIncPill-${cat}`);
            if (activePill) {
                activePill.style.opacity = '1';
                activePill.style.boxShadow = '0 0 0 2px #0284c7';
            }
            renderDashIncidentCalendar();
        }

        function renderDashIncidentCalendar() {
            const titleEl = document.getElementById('dashIncMonthTitle');
            const gridEl = document.getElementById('dashIncGrid');
            if (!titleEl || !gridEl) return;

            const year = dashIncDate.getFullYear();
            const month = dashIncDate.getMonth();
            titleEl.textContent = `${monthNames[month]} ${year}`;

            // Map incidents to dates
            const incidentMap = {};
            let monthActiveCount = 0;
            let monthResolvedCount = 0;

            dashIncidentsData.forEach(inc => {
                if (!inc.date) return;
                const dKey = inc.date.substring(0, 10);
                const incDateObj = new Date(dKey + 'T00:00:00');
                
                if (incDateObj.getFullYear() === year && incDateObj.getMonth() === month) {
                    const st = (inc.status || '').toLowerCase();
                    if (st === 'resolved' || st === 'closed') {
                        monthResolvedCount++;
                    } else {
                        monthActiveCount++;
                    }
                }

                // Filter check
                if (dashIncFilterCat !== 'all') {
                    const c = (inc.type || '').toLowerCase();
                    if (!c.includes(dashIncFilterCat)) return;
                }

                if (!incidentMap[dKey]) incidentMap[dKey] = [];
                incidentMap[dKey].push(inc);
            });

            // Update badge counters
            const activeBadge = document.getElementById('dashIncActiveBadge');
            const resolvedBadge = document.getElementById('dashIncResolvedBadge');
            if (activeBadge) activeBadge.textContent = `${monthActiveCount} Active`;
            if (resolvedBadge) resolvedBadge.textContent = `${monthResolvedCount} Resolved`;

            // Build calendar grid
            const todayStr = formatDateKey(new Date());
            const firstDayIndex = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();

            let html = '';
            dayNames.forEach(d => {
                html += `<div class="dash-cal-dow">${d}</div>`;
            });

            // Prev month padding
            for (let i = firstDayIndex - 1; i >= 0; i--) {
                const dayNum = daysInPrevMonth - i;
                const dObj = new Date(year, month - 1, dayNum);
                const dKey = formatDateKey(dObj);
                const items = incidentMap[dKey] || [];
                html += renderDashCalCell(dayNum, dKey, items, true, false, 'incident');
            }

            // Current month days
            for (let day = 1; day <= daysInMonth; day++) {
                const dObj = new Date(year, month, day);
                const dKey = formatDateKey(dObj);
                const isToday = dKey === todayStr;
                const items = incidentMap[dKey] || [];
                html += renderDashCalCell(day, dKey, items, false, isToday, 'incident');
            }

            // Next month padding
            const totalRendered = firstDayIndex + daysInMonth;
            const remaining = (7 - (totalRendered % 7)) % 7;
            for (let day = 1; day <= remaining; day++) {
                const dObj = new Date(year, month + 1, day);
                const dKey = formatDateKey(dObj);
                const items = incidentMap[dKey] || [];
                html += renderDashCalCell(day, dKey, items, true, false, 'incident');
            }

            gridEl.innerHTML = html;
        }

        // --- Appointment Calendar Helpers & Render ---
        function getAppointmentStatusMeta(status) {
            const s = (status || '').toLowerCase();
            if (s === 'scheduled' || s === 'approved') return { label: 'Scheduled', icon: '📅', bg: '#d1fae5', color: '#065f46', border: '#a7f3d0' };
            if (s === 'checked_in') return { label: 'Checked In', icon: '✅', bg: '#e0e7ff', color: '#3730a3', border: '#c7d2fe' };
            if (s === 'pending') return { label: 'Pending', icon: '⏳', bg: '#fef3c7', color: '#b45309', border: '#fde68a' };
            if (s === 'completed') return { label: 'Completed', icon: '🏁', bg: '#f1f5f9', color: '#475569', border: '#cbd5e1' };
            if (s === 'cancelled' || s === 'rejected') return { label: 'Cancelled', icon: '❌', bg: '#fee2e2', color: '#991b1b', border: '#fca5a5' };
            return { label: ucfirst(status || 'Other'), icon: '📌', bg: '#f8fafc', color: '#334155', border: '#e2e8f0' };
        }

        function ucfirst(str) {
            if (!str) return '';
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        function dashAptPrev() {
            dashAptDate.setMonth(dashAptDate.getMonth() - 1);
            renderDashAppointmentCalendar();
        }
        function dashAptNext() {
            dashAptDate.setMonth(dashAptDate.getMonth() + 1);
            renderDashAppointmentCalendar();
        }
        function dashAptToday() {
            dashAptDate = new Date();
            renderDashAppointmentCalendar();
        }

        function toggleDashAptStatus(status) {
            dashAptFilterStatus = status;
            document.querySelectorAll('[id^="dashAptPill-"]').forEach(pill => {
                pill.style.boxShadow = 'none';
                pill.style.opacity = '0.7';
            });
            const activePill = document.getElementById(`dashAptPill-${status}`);
            if (activePill) {
                activePill.style.opacity = '1';
                activePill.style.boxShadow = '0 0 0 2px #059669';
            }
            renderDashAppointmentCalendar();
        }

        function renderDashAppointmentCalendar() {
            const titleEl = document.getElementById('dashAptMonthTitle');
            const gridEl = document.getElementById('dashAptGrid');
            if (!titleEl || !gridEl) return;

            const year = dashAptDate.getFullYear();
            const month = dashAptDate.getMonth();
            titleEl.textContent = `${monthNames[month]} ${year}`;

            // Map appointments to dates
            const aptMap = {};
            let monthScheduledCount = 0;
            let monthPendingCount = 0;

            dashAppointmentsData.forEach(apt => {
                if (!apt.date) return;
                const dKey = apt.date.substring(0, 10);
                const aptDateObj = new Date(dKey + 'T00:00:00');

                if (aptDateObj.getFullYear() === year && aptDateObj.getMonth() === month) {
                    const st = (apt.status || '').toLowerCase();
                    if (st === 'scheduled' || st === 'approved' || st === 'checked_in') {
                        monthScheduledCount++;
                    } else if (st === 'pending') {
                        monthPendingCount++;
                    }
                }

                // Filter check
                if (dashAptFilterStatus !== 'all') {
                    const st = (apt.status || '').toLowerCase();
                    if (dashAptFilterStatus === 'scheduled') {
                        if (st !== 'scheduled' && st !== 'approved' && st !== 'checked_in') return;
                    } else if (!st.includes(dashAptFilterStatus)) {
                        return;
                    }
                }

                if (!aptMap[dKey]) aptMap[dKey] = [];
                aptMap[dKey].push(apt);
            });

            // Update badge counters
            const scheduledBadge = document.getElementById('dashAptScheduledBadge');
            const pendingBadge = document.getElementById('dashAptPendingBadge');
            if (scheduledBadge) scheduledBadge.textContent = `${monthScheduledCount} Scheduled`;
            if (pendingBadge) pendingBadge.textContent = `${monthPendingCount} Pending`;

            // Build calendar grid
            const todayStr = formatDateKey(new Date());
            const firstDayIndex = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();

            let html = '';
            dayNames.forEach(d => {
                html += `<div class="dash-cal-dow">${d}</div>`;
            });

            // Prev month padding
            for (let i = firstDayIndex - 1; i >= 0; i--) {
                const dayNum = daysInPrevMonth - i;
                const dObj = new Date(year, month - 1, dayNum);
                const dKey = formatDateKey(dObj);
                const items = aptMap[dKey] || [];
                html += renderDashCalCell(dayNum, dKey, items, true, false, 'appointment');
            }

            // Current month days
            for (let day = 1; day <= daysInMonth; day++) {
                const dObj = new Date(year, month, day);
                const dKey = formatDateKey(dObj);
                const isToday = dKey === todayStr;
                const items = aptMap[dKey] || [];
                html += renderDashCalCell(day, dKey, items, false, isToday, 'appointment');
            }

            // Next month padding
            const totalRendered = firstDayIndex + daysInMonth;
            const remaining = (7 - (totalRendered % 7)) % 7;
            for (let day = 1; day <= remaining; day++) {
                const dObj = new Date(year, month + 1, day);
                const dKey = formatDateKey(dObj);
                const items = aptMap[dKey] || [];
                html += renderDashCalCell(day, dKey, items, true, false, 'appointment');
            }

            gridEl.innerHTML = html;
        }

        // --- Common Calendar Cell Renderer ---
        function renderDashCalCell(dayNum, dateKey, items, isOtherMonth, isToday, type) {
            let cellClass = 'dash-cal-cell';
            if (isOtherMonth) cellClass += ' other-month';
            if (isToday) cellClass += ' today';

            let countBadge = '';
            if (items.length > 0) {
                const badgeColor = type === 'incident' ? '#fee2e2; color: #b91c1c;' : '#d1fae5; color: #065f46;';
                countBadge = `<span class="dash-cal-badge" style="background:${badgeColor}">${items.length}</span>`;
            }

            let chipsHtml = '';
            const maxChips = 2;
            const visibleItems = items.slice(0, maxChips);

            visibleItems.forEach(item => {
                if (type === 'incident') {
                    const meta = getIncidentCategoryMeta(item.type);
                    const safeTitle = (item.sub || 'Incident #' + item.id).replace(/'/g, "\\'").replace(/"/g, '&quot;');
                    const safeSub = (item.res || 'Resident').replace(/'/g, "\\'").replace(/"/g, '&quot;');
                    const safeStatus = (item.status || 'Open').toUpperCase();

                    chipsHtml += `
                        <div class="dash-event-chip" style="background: ${meta.bg}; color: ${meta.color}; border-color: ${meta.border};"
                             onmouseenter="showDashTooltip(event, '${safeTitle}', 'By: ${safeSub}', 'Status: ${safeStatus}')"
                             onmouseleave="hideDashTooltip()">
                            <span>${meta.icon}</span>
                            <span style="overflow:hidden; text-overflow:ellipsis;">${item.sub || item.type || 'Incident'}</span>
                        </div>
                    `;
                } else {
                    const meta = getAppointmentStatusMeta(item.status);
                    const clientName = item.client || (item.first_name ? `${item.first_name} ${item.last_name || ''}` : 'Buyer Inquiry');
                    const safeTitle = clientName.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                    const safeSub = (item.type || 'Site Viewing') + (item.time ? ' · ' + formatTimeDisplay(item.time) : '');
                    const safeStatus = (item.status || 'Pending').toUpperCase();

                    chipsHtml += `
                        <div class="dash-event-chip" style="background: ${meta.bg}; color: ${meta.color}; border-color: ${meta.border};"
                             onmouseenter="showDashTooltip(event, '${safeTitle}', '${safeSub}', 'Status: ${safeStatus}')"
                             onmouseleave="hideDashTooltip()">
                            <span>${meta.icon}</span>
                            <span style="overflow:hidden; text-overflow:ellipsis;">${clientName}</span>
                        </div>
                    `;
                }
            });

            if (items.length > maxChips) {
                const remaining = items.length - maxChips;
                chipsHtml += `<div class="dash-cal-more">+${remaining} more</div>`;
            }

            return `
                <div class="${cellClass}" onclick="openDashDayDetails('${type}', '${dateKey}')">
                    <div class="dash-cal-cell-header">
                        <span class="dash-cal-num">${dayNum}</span>
                        ${countBadge}
                    </div>
                    ${chipsHtml}
                </div>
            `;
        }

        // --- Day Details Modal ---
        function openDashDayDetails(type, dateKey) {
            const modal = document.getElementById('dashDayModal');
            const titleEl = document.getElementById('dashDayModalTitle');
            const subtitleEl = document.getElementById('dashDayModalSubtitle');
            const bodyEl = document.getElementById('dashDayModalBody');
            const actionBtn = document.getElementById('dashDayModalActionBtn');
            if (!modal || !titleEl || !bodyEl) return;

            const formattedDate = formatDisplayDate(dateKey);

            if (type === 'incident') {
                titleEl.innerHTML = `🛡️ Incident Reports`;
                subtitleEl.textContent = formattedDate;
                if (actionBtn) {
                    actionBtn.href = `/admin/incidents`;
                    actionBtn.textContent = 'Go to Incident Management →';
                }

                // Filter incidents for this date
                const items = dashIncidentsData.filter(i => i.date && i.date.substring(0, 10) === dateKey);

                if (items.length === 0) {
                    bodyEl.innerHTML = `
                        <div style="text-align: center; padding: 32px 16px; color: #94a3b8;">
                            <div style="font-size: 32px; margin-bottom: 8px;">📋</div>
                            <div style="font-weight: 700; color: #475569;">No incidents recorded on this day.</div>
                            <div style="font-size: 12px; margin-top: 4px;">Peaceful day in the subdivision community.</div>
                        </div>
                    `;
                } else {
                    let itemsHtml = '';
                    items.forEach(inc => {
                        const meta = getIncidentCategoryMeta(inc.type);
                        const isResolved = (inc.status || '').toLowerCase() === 'resolved';
                        const statusBadge = isResolved
                            ? '<span style="background: #d1fae5; color: #065f46; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 999px;">Resolved</span>'
                            : '<span style="background: #fef3c7; color: #b45309; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 999px;">' + ucfirst(inc.status || 'Open') + '</span>';

                        itemsHtml += `
                            <div style="padding: 14px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff; display: flex; flex-direction: column; gap: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 3px;">
                                            <span style="background: ${meta.bg}; color: ${meta.color}; border: 1px solid ${meta.border}; padding: 1px 6px; border-radius: 4px; font-size: 10.5px; font-weight: 800;">
                                                ${meta.icon} ${meta.label}
                                            </span>
                                            <span style="font-size: 11px; font-weight: 700; color: #64748b;">#${inc.id}</span>
                                        </div>
                                        <div style="font-weight: 800; font-size: 14px; color: #0f172a;">${inc.sub || 'Incident Report'}</div>
                                    </div>
                                    <div>${statusBadge}</div>
                                </div>
                                <div style="font-size: 12px; color: #475569; line-height: 1.45; background: #f8fafc; padding: 8px 10px; border-radius: 8px;">
                                    ${inc.desc || 'No additional details provided.'}
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #64748b; padding-top: 4px; border-top: 1px solid #f1f5f9;">
                                    <span>👤 Reporter: <strong>${inc.res || 'Anonymous'}</strong></span>
                                    <a href="/admin/incidents" style="color: #0284c7; font-weight: 700; text-decoration: none;">View details &rarr;</a>
                                </div>
                            </div>
                        `;
                    });
                    bodyEl.innerHTML = itemsHtml;
                }
            } else {
                titleEl.innerHTML = `📅 Lot &amp; Visit Inquiries`;
                subtitleEl.textContent = formattedDate;
                if (actionBtn) {
                    actionBtn.href = `/admin/appointments`;
                    actionBtn.textContent = 'Go to Appointments Pipeline →';
                }

                // Filter appointments for this date
                const items = dashAppointmentsData.filter(a => a.date && a.date.substring(0, 10) === dateKey);

                if (items.length === 0) {
                    bodyEl.innerHTML = `
                        <div style="text-align: center; padding: 32px 16px; color: #94a3b8;">
                            <div style="font-size: 32px; margin-bottom: 8px;">🗓️</div>
                            <div style="font-weight: 700; color: #475569;">No appointments scheduled on this day.</div>
                            <div style="font-size: 12px; margin-top: 4px;">Slots are open for buyer bookings.</div>
                        </div>
                    `;
                } else {
                    let itemsHtml = '';
                    items.forEach(apt => {
                        const meta = getAppointmentStatusMeta(apt.status);
                        const clientName = apt.client || (apt.first_name ? `${apt.first_name} ${apt.last_name || ''}` : 'Buyer Inquiry');
                        const timeBadge = apt.time ? `<span style="font-weight: 700; color: #0f172a; font-size: 12px;">🕒 ${formatTimeDisplay(apt.time)}</span>` : '';

                        itemsHtml += `
                            <div style="padding: 14px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff; display: flex; flex-direction: column; gap: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                                    <div>
                                        <div style="font-weight: 800; font-size: 14px; color: #0f172a;">${clientName}</div>
                                        <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">
                                            <span>🏷️ ${apt.type || 'Lot Inquiry'}</span>
                                            ${apt.contact ? ` · 📞 ${apt.contact}` : ''}
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="background: ${meta.bg}; color: ${meta.color}; border: 1px solid ${meta.border}; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 800;">
                                            ${meta.icon} ${meta.label}
                                        </span>
                                    </div>
                                </div>
                                ${timeBadge ? `<div style="background: #f8fafc; padding: 6px 10px; border-radius: 6px;">${timeBadge}</div>` : ''}
                                ${apt.notes ? `
                                    <div style="font-size: 12px; color: #475569; background: #f8fafc; padding: 8px 10px; border-radius: 8px;">
                                        📝 ${apt.notes}
                                    </div>
                                ` : ''}
                                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #64748b; padding-top: 4px; border-top: 1px solid #f1f5f9;">
                                    <span>✉️ ${apt.email || 'No email provided'}</span>
                                    <a href="/admin/appointments" style="color: #059669; font-weight: 700; text-decoration: none;">Manage booking &rarr;</a>
                                </div>
                            </div>
                        `;
                    });
                    bodyEl.innerHTML = itemsHtml;
                }
            }

            modal.style.display = 'flex';
        }

        function closeDashDayModal() {
            const modal = document.getElementById('dashDayModal');
            if (modal) modal.style.display = 'none';
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDashDayModal();
                hideDashTooltip();
            }
        });

        // Initialize both calendars
        function initDashboardCalendars() {
            renderDashIncidentCalendar();
            renderDashAppointmentCalendar();
        }
    </script>
@endpush
