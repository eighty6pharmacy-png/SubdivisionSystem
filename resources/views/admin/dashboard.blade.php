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
        });
    </script>
@endpush
