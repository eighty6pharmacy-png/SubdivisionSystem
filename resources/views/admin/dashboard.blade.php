@extends('layouts.admin')

@section('title', 'Dashboard Overview')

@section('content')
    <!-- Stats Row -->
    <div class="stats-grid fade-up">
        <div class="stat-card green">
            <div class="stat-value">₱ 392K</div>
            <div class="stat-label">Revenue This Month</div>
            <div class="stat-change up">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                8.2% vs last month
            </div>
        </div>
        <div class="stat-card blue">
            <div class="stat-value">120</div>
            <div class="stat-label">Total Residents</div>
            <div class="stat-change up">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                2 new this month
            </div>
        </div>
        <div class="stat-card yellow">
            <div class="stat-label" style="float: right; margin-top: 0; margin-bottom: 24px;">Appointments</div>
            <br>
            <div class="stat-value">6</div>
            <div class="stat-label">Visits Today</div>
            <div class="stat-change up">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                Lot inquiry & Admin ops
            </div>
        </div>
        <div class="stat-card red">
            <div class="stat-value">{{ $openIncidentsCount ?? 0 }}</div>
            <div class="stat-label">Open Incidents</div>
            <div class="stat-change down">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>
                Needs your attention
            </div>
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
                <select class="form-input" style="width:140px; padding:6px 12px; font-size:13px; font-weight:600; border-radius: 8px;">
                    <option>2026 Sales</option>
                    <option>2025 Sales</option>
                </select>
            </div>
            <div style="height: 220px; position: relative; margin-top: 12px;">
                <canvas id="dashboardSalesChart"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span class="card-title">Payment Status</span></div>
            <div class="donut-wrap" style="margin-top:6px;">
                <svg width="90" height="90" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e2e8f0" stroke-width="3.8"/>
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#4caf7d" stroke-width="3.8" stroke-dasharray="88.9 11.1" stroke-dashoffset="25"/>
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f5a623" stroke-width="3.8" stroke-dasharray="5.8 94.2" stroke-dashoffset="-63.9"/>
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e05c5c" stroke-width="3.8" stroke-dasharray="5.8 94.2" stroke-dashoffset="-69.7"/>
                    <text x="18" y="20.5" text-anchor="middle" font-size="6" fill="#1a1a1a" font-weight="700">94%</text>
                </svg>
                <div class="donut-legend">
                    <div class="donut-legend-item"><span class="donut-dot" style="background:#4caf7d"></span>Paid — 233</div>
                    <div class="donut-legend-item"><span class="donut-dot" style="background:#f5a623"></span>Pending — 10</div>
                    <div class="donut-legend-item"><span class="donut-dot" style="background:#e05c5c"></span>Overdue — 5</div>
                </div>
            </div>
            <div class="progress-wrap">
                <div class="progress-label"><span>Collection Progress</span><span>94%</span></div>
                <div class="progress-bar"><div class="progress-fill" style="width:0%" id="dash-progress"></div></div>
            </div>
        </div>
    </div>

    <!-- Bottom Row -->
    <div class="responsive-grid grid-2 fade-up-3">
        <!-- Recent Activity Feed -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">System Activity</span>
                <span class="badge badge-info">Live</span>
            </div>
            <div class="activity-feed">
                <div class="activity-item">
                    <span class="activity-dot" style="background:var(--success)"></span>
                    <div class="activity-content">
                        <strong>Monthly Dues Paid: ₱2,000</strong>
                        <span>Maria Ivy, Block B Lot 12 · 9:41 AM</span>
                    </div>
                </div>
                <div class="activity-item">
                    <span class="activity-dot" style="background:var(--accent)"></span>
                    <div class="activity-content">
                        <strong>New Appointment Booked</strong>
                        <span>Juan Dela Cruz · Lot inquiry for Block C · 10:00 AM</span>
                    </div>
                </div>
                <div class="activity-item">
                    <span class="activity-dot" style="background:var(--warning)"></span>
                    <div class="activity-content">
                        <strong>Incident Reported: Streetlight Issue</strong>
                        <span>Near Gate 2, waiting for admin review · 8:25 AM</span>
                    </div>
                </div>
                <div class="activity-item">
                    <span class="activity-dot" style="background:var(--danger)"></span>
                    <div class="activity-content">
                        <strong>Automated Notice Sent</strong>
                        <span>Overdue bill reminders sent to 5 residents · 7:00 AM</span>
                    </div>
                </div>
                <div class="activity-item">
                    <span class="activity-dot" style="background:var(--success)"></span>
                    <div class="activity-content">
                        <strong>Water Bill Paid: ₱450</strong>
                        <span>Rosa Lim, Block A Lot 5 · 6:30 AM</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Access / Summary -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Today's Active Appointments</span>
                <a href="/admin/appointments" class="btn btn-outline btn-sm">View Calendar</a>
            </div>
            
            <div style="display:flex; flex-direction:column; gap:12px;">
                <div style="display:flex; justify-content:space-between; align-items:center; padding:14px; background:var(--bg); border-radius:10px; border:1px solid var(--border);">
                    <div>
                        <div style="font-size:14px; font-weight:600; color:var(--text-dark);">Maria Agwas</div>
                        <div style="font-size:12.5px; color:var(--text-mid); margin-top:2px;">9:00 AM · Lot Inquiry</div>
                    </div>
                    <span class="badge badge-warning">Pending</span>
                </div>
                
                <div style="display:flex; justify-content:space-between; align-items:center; padding:14px; background:var(--bg); border-radius:10px; border:1px solid var(--border);">
                    <div>
                        <div style="font-size:14px; font-weight:600; color:var(--text-dark);">Princess Dimagiba Jr</div>
                        <div style="font-size:12.5px; color:var(--text-mid); margin-top:2px;">10:00 AM · Developer Meeting</div>
                    </div>
                    <span class="badge badge-success">Checked In</span>
                </div>
                
                <div style="display:flex; justify-content:space-between; align-items:center; padding:14px; background:var(--bg); border-radius:10px; border:1px solid var(--border);">
                    <div>
                        <div style="font-size:14px; font-weight:600; color:var(--text-dark);">Rosa Lim</div>
                        <div style="font-size:12.5px; color:var(--text-mid); margin-top:2px;">11:00 AM · Dues Dispute</div>
                    </div>
                    <span class="badge badge-success">Done</span>
                </div>
                
                <div style="display:flex; justify-content:space-between; align-items:center; padding:14px; background:var(--bg); border-radius:10px; border:1px solid var(--border);">
                    <div>
                        <div style="font-size:14px; font-weight:600; color:var(--text-dark);">Mark Denber</div>
                        <div style="font-size:12.5px; color:var(--text-mid); margin-top:2px;">1:00 PM · Site Visit</div>
                    </div>
                    <span class="badge badge-warning">Pending</span>
                </div>
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
            setTimeout(() => { 
                const progressFill = document.getElementById('dash-progress'); 
                if (progressFill) progressFill.style.width = '94%'; 
            }, 300);

            const ctx = document.getElementById('dashboardSalesChart')?.getContext('2d');
            if (ctx) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        datasets: [{
                            label: 'Monthly Sales',
                            data: [310000, 335000, 350000, 375000, 340000, 395000, 365000, 410000, 380000, 430000, 445000, 392000],
                            backgroundColor: [
                                '#10b981', '#10b981', '#10b981', '#10b981', '#10b981', '#10b981',
                                '#10b981', '#10b981', '#10b981', '#10b981', '#10b981', '#3b82f6'
                            ],
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
        });
    </script>
@endpush
