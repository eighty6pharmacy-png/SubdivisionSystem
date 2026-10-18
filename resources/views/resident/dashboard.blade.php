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
            $elecBase = $elecBill['base_amount'] ?? 0;
            $elecPrevBalance = $elecBill['previous_balance'] ?? 0;
            $elecAmountBefore = $elecBase + $elecPrevBalance;
            $elecPenalty = isset($elecBill['at_risk']) && $elecBill['at_risk'] ? ($elecAmountBefore * 0.05) : 0;
            $elecTotalPaid = $elecBill['total_paid'] ?? 0;
            $elecTotalOwed = max(0, $elecAmountBefore + $elecPenalty - $elecTotalPaid);
            
            $waterBase = $waterBill['base_amount'] ?? 0;
            $waterPrevBalance = $waterBill['previous_balance'] ?? 0;
            $waterAmountBefore = $waterBase + $waterPrevBalance;
            $waterPenalty = isset($waterBill['at_risk']) && $waterBill['at_risk'] ? ($waterAmountBefore * 0.05) : 0;
            $waterTotalPaid = $waterBill['total_paid'] ?? 0;
            $waterTotalOwed = max(0, $waterAmountBefore + $waterPenalty - $waterTotalPaid);

            $totalOwed = $elecTotalOwed + $waterTotalOwed;
            
            $nextDue = 'N/A';
            if($elecTotalOwed > 0) $nextDue = $elecBill['due'] ?? 'N/A';
            if($waterTotalOwed > 0) {
                if($nextDue === 'N/A' || (isset($waterBill['due']) && $waterBill['due'] < $nextDue)) $nextDue = $waterBill['due'];
            }
        @endphp
        <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 24px; border-radius: 24px; color: #fff; box-shadow: 0 10px 15px -3px rgba(5, 150, 105, 0.2);">
            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; opacity: 0.85; letter-spacing: 0.05em;">Utility Balance Due</div>
            <div style="font-size: 36px; font-weight: 800; margin-top: 8px; font-family: var(--font-display);">₱ {{ number_format($totalOwed, 2) }}</div>
            
            <div style="margin-top: 16px; background: rgba(0,0,0,0.15); padding: 12px; border-radius: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 12px;">
                <div style="display: flex; align-items: center; gap: 6px;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Elec: ₱{{ number_format($elecBase, 2) }}
                </div>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                    Water: ₱{{ number_format($waterBase, 2) }}
                </div>
                <div>Prev Bal: ₱{{ number_format($elecPrevBalance + $waterPrevBalance, 2) }}</div>
                <div>Penalty: ₱{{ number_format($elecPenalty + $waterPenalty, 2) }}</div>
                <div style="grid-column: span 2; padding-top: 8px; border-top: 1px solid rgba(255,255,255,0.2); margin-top: 4px;">
                    Total Paid: -₱{{ number_format($elecTotalPaid + $waterTotalPaid, 2) }}
                </div>
            </div>
            
            <div style="margin-top: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.2); width: fit-content; padding: 6px 12px; border-radius: 12px;">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Next Due: {{ $nextDue }}
            </div>
        </div>

        <div style="background: #fff; padding: 24px; border-radius: 24px; border: 1px solid var(--bill-border);">
            <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 0.05em;">Current Meter Readings</div>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div style="background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 13px; font-weight: 700; color: #047857; display: flex; align-items: center; gap: 6px;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Electricity
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 10px; color: #64748b;">Prev: {{ $elecBill['prev_reading'] ?? 0 }} | Curr: {{ $elecBill['curr_reading'] ?? 0 }}</div>
                        <div style="font-size: 16px; font-weight: 800; color: #0f172a;">{{ $elecBill['usage_kwh'] ?? 0 }} kWh</div>
                    </div>
                </div>
                <div style="background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 13px; font-weight: 700; color: #0284c7; display: flex; align-items: center; gap: 6px;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                        Water
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 10px; color: #64748b;">Prev: {{ $waterBill['prev_reading'] ?? 0 }} | Curr: {{ $waterBill['curr_reading'] ?? 0 }}</div>
                        <div style="font-size: 16px; font-weight: 800; color: #0f172a;">{{ $waterBill['usage_cbm'] ?? ($waterBill['usage_m3'] ?? 0) }} m³</div>
                    </div>
                </div>
            </div>
        </div>
        <div style="background: #fff; padding: 24px; border-radius: 24px; border: 1px solid var(--bill-border); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Active Incident Reports</div>
                <div style="font-size: 32px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ $activeIncidentsCount ?? 0 }}</div>
                <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Concerns under review or in progress</div>
            </div>
            <button class="btn btn-outline" onclick="window.location.href='/resident/incidents?action=report'" style="width: 100%; justify-content: center; margin-top: 16px; font-weight: 700; border-radius: 12px; display: inline-flex; align-items: center; gap: 8px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
                Report a Concern
            </button>
        </div>
    </div>

    <!-- Utility Consumption Monitor Graph -->
    <div class="analytic-card" style="padding: 24px; margin-bottom: 32px; border-radius: 24px; border: 1px solid var(--bill-border); background: #ffffff;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <svg width="22" height="22" fill="none" stroke="#059669" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Utility Usage Monitoring
                </h3>
                <p style="font-size: 13px; color: #64748b; margin-top: 4px;">Track your monthly Electricity (kWh) and Water (m³) consumption over time.</p>
            </div>
            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <select id="timeRange" onchange="updateChartRange(this.value)" style="padding: 6px 12px; font-size: 12px; border-radius: 12px; border: 1px solid #cbd5e1; background: #fff; color: #475569; font-weight: 600; cursor: pointer;">
                    <option value="6">Last 6 Months</option>
                    <option value="12">Last 12 Months</option>
                </select>
                <button class="btn btn-outline" id="btnChartElec" onclick="toggleDashboardUtility('elec')" style="padding: 6px 14px; font-size: 12px; border-radius: 20px; background: #ecfdf5; color: #047857; border-color: #a7f3d0; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Electricity (kWh)
                </button>
                <button class="btn btn-outline" id="btnChartWater" onclick="toggleDashboardUtility('water')" style="padding: 6px 14px; font-size: 12px; border-radius: 20px; color: #0284c7; border-color: #bae6fd; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                    Water (m³)
                </button>
            </div>
        </div>

        <!-- Monthly Trend Line Graph -->
        <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0;">
            <div style="position: relative; height: 250px; width: 100%;">
                <canvas id="utilityChart"></canvas>
            </div>
        </div>

        <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; margin-top: 16px;">
            <h4 style="font-size: 14px; font-weight: 700; color: #475569; margin-bottom: 12px; margin-top: 0;">Billing Amount (₱)</h4>
            <div style="position: relative; height: 250px; width: 100%;">
                <canvas id="amountChart"></canvas>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 20px;">
            <div style="background: #f0fdf4; padding: 16px; border-radius: 16px; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: #dcfce7; color: #166534; display: flex; align-items: center; justify-content: center;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #166534; text-transform: uppercase;">Avg Electricity Usage</div>
                    @php 
                        $rawElecVals = array_map(function($item) {
                            return is_array($item) ? ($item['value'] ?? 0) : (is_numeric($item) ? (float)$item : 0);
                        }, $elecBill['usage_history'] ?? []);
                        $elecNonZero = array_filter($rawElecVals, function($v) { return $v > 0; });
                        $elecAvg = count($elecNonZero) > 0 ? round(array_sum($elecNonZero) / count($elecNonZero)) : 0; 
                    @endphp
                    <div style="font-size: 18px; font-weight: 800; color: #064e3b; margin-top: 2px;">{{ $elecAvg }} kWh / mo</div>
                </div>
            </div>
            <div style="background: #f0f9ff; padding: 16px; border-radius: 16px; border: 1px solid #bae6fd; display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: #e0f2fe; color: #0369a1; display: flex; align-items: center; justify-content: center;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #0369a1; text-transform: uppercase;">Avg Water Usage</div>
                    @php 
                        $rawWaterVals = array_map(function($item) {
                            return is_array($item) ? ($item['value'] ?? 0) : (is_numeric($item) ? (float)$item : 0);
                        }, $waterBill['usage_history'] ?? []);
                        $waterNonZero = array_filter($rawWaterVals, function($v) { return $v > 0; });
                        $waterAvg = count($waterNonZero) > 0 ? round(array_sum($waterNonZero) / count($waterNonZero)) : 0; 
                    @endphp
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
        const rawElecHistory = {!! json_encode($elecBill['usage_history'] ?? []) !!};
        const rawWaterHistory = {!! json_encode($waterBill['usage_history'] ?? []) !!};
        const rawElecAmount = {!! json_encode($elecBill['amount_history'] ?? []) !!};
        const rawWaterAmount = {!! json_encode($waterBill['amount_history'] ?? []) !!};

        const fullElecHistory = rawElecHistory.map(i => (typeof i === 'object' && i !== null ? (i.value ?? 0) : Number(i || 0)));
        const fullWaterHistory = rawWaterHistory.map(i => (typeof i === 'object' && i !== null ? (i.value ?? 0) : Number(i || 0)));
        const fullElecAmount = rawElecAmount.map(i => (typeof i === 'object' && i !== null ? (i.value ?? 0) : Number(i || 0)));
        const fullWaterAmount = rawWaterAmount.map(i => (typeof i === 'object' && i !== null ? (i.value ?? 0) : Number(i || 0)));

        let currentUtilityType = 'elec';
        let currentRange = 6;
        let utilityChart = null;
        let amountChart = null;

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
            const ctxAmount = document.getElementById('amountChart').getContext('2d');
            const color = type === 'elec' ? '#10b981' : '#3b82f6';
            const bgColor = type === 'elec' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(59, 130, 246, 0.15)';
            const unit = type === 'elec' ? 'kWh' : 'm³';
            const label = type === 'elec' ? 'Electricity Consumption' : 'Water Consumption';
            const fullData = type === 'elec' ? fullElecHistory : fullWaterHistory;
            const fullAmountData = type === 'elec' ? fullElecAmount : fullWaterAmount;

            const slicedLabels = fullMonthLabels.slice(-currentRange);
            const slicedData = fullData.slice(-currentRange);
            const slicedAmountData = fullAmountData.slice(-currentRange);

            if (utilityChart) {
                utilityChart.destroy();
            }
            if (amountChart) {
                amountChart.destroy();
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

            amountChart = new Chart(ctxAmount, {
                type: 'bar',
                data: {
                    labels: slicedLabels,
                    datasets: [{
                        label: 'Billing Amount',
                        data: slicedAmountData,
                        backgroundColor: color,
                        borderRadius: 6
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
                                    return '₱ ' + context.parsed.y;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#e2e8f0',
                                drawBorder: false
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₱ ' + value;
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

    <div class="responsive-grid grid-2-1" style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <!-- Left Column: Notifications -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <!-- Notification Panel -->
            <div id="notifications" class="analytic-card" style="padding: 0; overflow: hidden; border-radius: 20px; background: #fff; border: 1px solid var(--bill-border);">
                <div style="padding: 20px 24px; border-bottom: 1px solid var(--bill-border); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        Community Announcements
                    </h3>
                    <span style="font-size: 11px; font-weight: 700; color: #3b82f6; background: #eff6ff; padding: 2px 8px; border-radius: 12px;">{{ count($announcements) }} NEW</span>
                </div>
                <div style="max-height: 400px; overflow-y: auto;">
                    @if(count($announcements) === 0)
                        <div style="padding: 48px 24px; text-align: center; color: #94a3b8;">
                            <div style="width: 48px; height: 48px; border-radius: 12px; background: #f1f5f9; color: #94a3b8; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            </div>
                            <div style="font-weight: 600; color: #1e293b;">No announcements yet</div>
                            <div style="font-size: 13px; margin-top: 4px;">New notices from HOA management will appear here.</div>
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
            </div>
        </div>

        <!-- Right Column: Quick Resident Services -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <div class="analytic-card" style="padding: 24px; border-radius: 20px; background: #fff; border: 1px solid var(--bill-border);">
                <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 16px 0;">Resident Quick Actions</h3>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="/resident/electricity" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; text-decoration: none; color: #1e293b; font-size: 13px; font-weight: 600; transition: all 0.2s;">
                        <span style="display: flex; align-items: center; gap: 10px;">
                            <svg width="18" height="18" fill="none" stroke="#10b981" stroke-width="2.5" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Pay Electricity Bill
                        </span>
                        <svg width="16" height="16" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                    </a>
                    <a href="/resident/water" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; text-decoration: none; color: #1e293b; font-size: 13px; font-weight: 600; transition: all 0.2s;">
                        <span style="display: flex; align-items: center; gap: 10px;">
                            <svg width="18" height="18" fill="none" stroke="#0284c7" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                            Pay Water Bill
                        </span>
                        <svg width="16" height="16" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                    </a>
                    <a href="/resident/visitors" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; text-decoration: none; color: #1e293b; font-size: 13px; font-weight: 600; transition: all 0.2s;">
                        <span style="display: flex; align-items: center; gap: 10px;">
                            <svg width="18" height="18" fill="none" stroke="#6366f1" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                            Pre-register Visitor
                        </span>
                        <svg width="16" height="16" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                    </a>
                    <a href="/resident/map" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; text-decoration: none; color: #1e293b; font-size: 13px; font-weight: 600; transition: all 0.2s;">
                        <span style="display: flex; align-items: center; gap: 10px;">
                            <svg width="18" height="18" fill="none" stroke="#f59e0b" stroke-width="2" viewBox="0 0 24 24"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
                            Subdivision GIS Map
                        </span>
                        <svg width="16" height="16" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                    </a>
                    <a href="/resident/incidents" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; text-decoration: none; color: #1e293b; font-size: 13px; font-weight: 600; transition: all 0.2s;">
                        <span style="display: flex; align-items: center; gap: 10px;">
                            <svg width="18" height="18" fill="none" stroke="#ef4444" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            Community Concerns
                        </span>
                        <svg width="16" height="16" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

