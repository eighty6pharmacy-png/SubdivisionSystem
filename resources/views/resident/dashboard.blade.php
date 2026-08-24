@extends('layouts.resident')

@section('title', 'My Dashboard')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">

<div class="fade-in">
    <div style="margin-bottom: 32px;">
        <h1 style="font-size: 32px; font-weight: 800; color: #0f172a; margin: 0;">Welcome Home, {{ explode(' ', \Illuminate\Support\Facades\Auth::user()->name)[0] }}!</h1>
        <p style="color: #64748b; margin-top: 8px;">Here’s a summary of your property and community activities at Althesa.</p>
    </div>

    <!-- Quick Stats for Resident -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-bottom: 32px;">
        @php
            $totalOwed = 0;
            $nextDue = 'N/A';
            if($elecBill['status'] !== 'paid') {
                $totalOwed += $elecBill['amount'];
                $nextDue = $elecBill['due'];
            }
            if($waterBill['status'] !== 'paid') {
                $totalOwed += $waterBill['amount'];
                if($nextDue === 'N/A' || $waterBill['due'] < $nextDue) $nextDue = $waterBill['due'];
            }
        @endphp
        <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 24px; border-radius: 24px; color: #fff; box-shadow: 0 10px 15px -3px rgba(5, 150, 105, 0.2);">
            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; opacity: 0.8;">Utility Balance Due</div>
            <div style="font-size: 36px; font-weight: 800; margin-top: 8px; font-family: var(--font-display);">₱ {{ number_format($totalOwed, 2) }}</div>
            <div style="margin-top: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.2); width: fit-content; padding: 6px 12px; border-radius: 12px;">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                Next Due: {{ $nextDue }}
            </div>
        </div>


        <div style="background: #fff; padding: 24px; border-radius: 24px; border: 1px solid var(--bill-border); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Active Incident Reports</div>
                <div style="font-size: 32px; font-weight: 800; color: #0f172a; margin-top: 4px;">0</div>
            </div>
            <button class="btn btn-outline" style="width: 100%; justify-content: center; margin-top: 16px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px;"><path d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Report a Concern
            </button>
        </div>
    </div>

    <!-- Utility Consumption Monitor Graph -->
    <div class="analytic-card" style="padding: 24px; margin-bottom: 32px; border-radius: 24px; border: 1px solid var(--bill-border); background: #ffffff;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                    📈 Utility Usage Monitoring
                </h3>
                <p style="font-size: 13px; color: #64748b; margin-top: 4px;">Track your monthly Electricity (kWh) and Water (m³) consumption over time.</p>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
                <select id="timeRange" onchange="updateChartRange(this.value)" style="padding: 6px 12px; font-size: 12px; border-radius: 12px; border: 1px solid #cbd5e1; background: #fff; color: #475569; font-weight: 600; cursor: pointer;">
                    <option value="6">Last 6 Months</option>
                    <option value="12">Last 12 Months</option>
                </select>
                <button class="btn btn-outline" id="btnChartElec" onclick="toggleDashboardUtility('elec')" style="padding: 6px 14px; font-size: 12px; border-radius: 20px; background: #ecfdf5; color: #047857; border-color: #a7f3d0; font-weight: 700;">⚡ Electricity (kWh)</button>
                <button class="btn btn-outline" id="btnChartWater" onclick="toggleDashboardUtility('water')" style="padding: 6px 14px; font-size: 12px; border-radius: 20px; color: #0284c7; border-color: #bae6fd; font-weight: 700;">💧 Water (m³)</button>
            </div>
        </div>

        <!-- Monthly Trend Line Graph -->
        <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0;">
            <div style="position: relative; height: 250px; width: 100%;">
                <canvas id="utilityChart"></canvas>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 20px;">
            <div style="background: #f0fdf4; padding: 16px; border-radius: 16px; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: #dcfce7; color: #166534; display: flex; align-items: center; justify-content: center; font-size: 20px;">⚡</div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #166534; text-transform: uppercase;">Avg Electricity Usage</div>
                    @php $elecAvg = count($elecBill['usage_history']) > 0 ? round(array_sum($elecBill['usage_history']) / count($elecBill['usage_history'])) : 0; @endphp
                    <div style="font-size: 18px; font-weight: 800; color: #064e3b; margin-top: 2px;">{{ $elecAvg }} kWh / mo</div>
                </div>
            </div>
            <div style="background: #f0f9ff; padding: 16px; border-radius: 16px; border: 1px solid #bae6fd; display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: #e0f2fe; color: #0369a1; display: flex; align-items: center; justify-content: center; font-size: 20px;">💧</div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #0369a1; text-transform: uppercase;">Avg Water Usage</div>
                    @php $waterAvg = count($waterBill['usage_history']) > 0 ? round(array_sum($waterBill['usage_history']) / count($waterBill['usage_history'])) : 0; @endphp
                    <div style="font-size: 18px; font-weight: 800; color: #075985; margin-top: 2px;">{{ $waterAvg }} m³ / mo</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        @php
            $months = [];
            for ($i = 11; $i >= 0; $i--) {
                $months[] = date('M Y', strtotime("-$i months"));
            }
        @endphp

        const fullMonthLabels = {!! json_encode($months) !!};
        const fullElecHistory = {!! json_encode($elecBill['usage_history']) !!};
        const fullWaterHistory = {!! json_encode($waterBill['usage_history']) !!};

        let currentUtilityType = 'elec';
        let currentRange = 6;
        let utilityChart = null;

        function updateChartRange(monthsCount) {
            currentRange = parseInt(monthsCount);
            toggleDashboardUtility(currentUtilityType);
        }

        function toggleDashboardUtility(type) {
            currentUtilityType = type;
            const btnElec = document.getElementById('btnChartElec');
            const btnWater = document.getElementById('btnChartWater');
            
            if (type === 'elec') {
                btnElec.style.background = '#ecfdf5'; btnElec.style.color = '#047857'; btnElec.style.borderColor = '#a7f3d0';
                btnWater.style.background = 'transparent'; btnWater.style.color = '#64748b'; btnWater.style.borderColor = '#e2e8f0';
            } else {
                btnWater.style.background = '#f0f9ff'; btnWater.style.color = '#0284c7'; btnWater.style.borderColor = '#bae6fd';
                btnElec.style.background = 'transparent'; btnElec.style.color = '#64748b'; btnElec.style.borderColor = '#e2e8f0';
            }

            const ctx = document.getElementById('utilityChart').getContext('2d');
            const color = type === 'elec' ? '#10b981' : '#3b82f6';
            const bgColor = type === 'elec' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(59, 130, 246, 0.15)';
            const unit = type === 'elec' ? 'kWh' : 'm³';
            const label = type === 'elec' ? 'Electricity Consumption' : 'Water Consumption';
            const fullData = type === 'elec' ? fullElecHistory : fullWaterHistory;

            const slicedLabels = fullMonthLabels.slice(-currentRange);
            const slicedData = fullData.slice(-currentRange);

            if (utilityChart) {
                utilityChart.destroy();
            }

            utilityChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: slicedLabels,
                    datasets: [{
                        label: label,
                        data: slicedData,
                        borderColor: color,
                        backgroundColor: bgColor,
                        borderWidth: 3,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: color,
                        pointBorderWidth: 3,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        fill: true,
                        tension: 0.3
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
                                    return context.parsed.y + ' ' + unit;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            grid: {
                                color: '#e2e8f0',
                                drawBorder: false
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + ' ' + unit;
                                },
                                color: '#64748b',
                                font: { size: 11 }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#64748b', font: { size: 11 } }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            toggleDashboardUtility('elec');
        });
    </script>

    <div class="responsive-grid grid-2-1">
        <!-- Left Column: Notifications -->
        <div style="display: flex; flex-direction: column; gap: 24px;">

            <!-- Notification Panel -->
            <div id="notifications" class="analytic-card" style="padding: 0; overflow: hidden;">
                <div style="padding: 20px 24px; border-bottom: 1px solid var(--bill-border); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Notifications</h3>
                    <span style="font-size: 11px; font-weight: 700; color: #3b82f6; background: #eff6ff; padding: 2px 8px; border-radius: 12px;">{{ count($announcements) }} NEW</span>
                </div>
                <div style="max-height: 400px; overflow-y: auto;">
                    @if(count($announcements) === 0)
                        <div style="padding: 48px 24px; text-align: center; color: #94a3b8;">
                            <div style="font-size: 32px; margin-bottom: 12px;">🔔</div>
                            <div style="font-weight: 600;">No notifications yet</div>
                            <div style="font-size: 13px; margin-top: 4px;">New announcements will appear here.</div>
                        </div>
                    @endif
                    @foreach($announcements as $ann)
                        <div style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; background: #f8fafc;">
                            <div style="display: flex; gap: 12px;">
                                <div style="width: 32px; height: 32px; background: #eff6ff; color: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                </div>
                                <div style="flex: 1;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <div style="font-size: 13px; font-weight: 700; color: #0f172a;">{{ $ann->title }}</div>
                                        <span style="font-size: 10px; color: #94a3b8; white-space: nowrap; margin-left: 8px;">{{ $ann->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div style="font-size: 12px; color: #475569; margin-top: 2px;">{{ Str::limit($ann->content, 100) }}</div>
                                    <div style="font-size: 10px; color: #3b82f6; font-weight: 600; margin-top: 4px; text-transform: uppercase;">{{ $ann->category }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div style="padding: 12px; text-align: center; background: #f8fafc;">
                    <a href="/resident/notifications" style="font-size: 12px; font-weight: 600; color: #3b82f6; text-decoration: none;">View all notifications</a>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

