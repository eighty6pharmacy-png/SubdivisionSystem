@extends('layouts.resident')

@section('title', 'My Dashboard')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">

<div class="fade-in">
    <div style="margin-bottom: 32px;">
        <h1 style="font-size: 32px; font-weight: 800; color: #0f172a; margin: 0;">Welcome Home, Juan!</h1>
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
            <div style="display: flex; gap: 8px;">
                <button class="btn btn-outline" id="btnChartElec" onclick="toggleDashboardUtility('elec')" style="padding: 6px 14px; font-size: 12px; border-radius: 20px; background: #ecfdf5; color: #047857; border-color: #a7f3d0; font-weight: 700;">⚡ Electricity (kWh)</button>
                <button class="btn btn-outline" id="btnChartWater" onclick="toggleDashboardUtility('water')" style="padding: 6px 14px; font-size: 12px; border-radius: 20px; color: #0284c7; border-color: #bae6fd; font-weight: 700;">💧 Water (m³)</button>
            </div>
        </div>

        <!-- Monthly Trend Line Graph -->
        <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0;">
            <div style="position: relative; height: 180px; width: 100%; padding-top: 20px; margin-bottom: 8px;" id="chartBarsContainer">
                <!-- Javascript will inject dynamic SVG line graph -->
            </div>
            <div style="display: flex; justify-content: space-between; border-top: 1px solid #cbd5e1; margin-top: 12px; padding-top: 8px; font-size: 12px; font-weight: 600; color: #64748b;" id="chartLabelsContainer">
                <span>Nov</span><span>Dec</span><span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 20px;">
            <div style="background: #f0fdf4; padding: 16px; border-radius: 16px; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: #dcfce7; color: #166534; display: flex; align-items: center; justify-content: center; font-size: 20px;">⚡</div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #166534; text-transform: uppercase;">Avg Electricity Usage</div>
                    <div style="font-size: 18px; font-weight: 800; color: #064e3b; margin-top: 2px;">152 kWh / mo</div>
                </div>
            </div>
            <div style="background: #f0f9ff; padding: 16px; border-radius: 16px; border: 1px solid #bae6fd; display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: #e0f2fe; color: #0369a1; display: flex; align-items: center; justify-content: center; font-size: 20px;">💧</div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #0369a1; text-transform: uppercase;">Avg Water Usage</div>
                    <div style="font-size: 18px; font-weight: 800; color: #075985; margin-top: 2px;">30 m³ / mo</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const utilityHistoryData = {
            elec: [
                { month: 'Nov', val: 145, unit: 'kWh' },
                { month: 'Dec', val: 160, unit: 'kWh' },
                { month: 'Jan', val: 155, unit: 'kWh' },
                { month: 'Feb', val: 140, unit: 'kWh' },
                { month: 'Mar', val: 150, unit: 'kWh' },
                { month: 'Apr', val: 155, unit: 'kWh' }
            ],
            water: [
                { month: 'Nov', val: 28, unit: 'm³' },
                { month: 'Dec', val: 34, unit: 'm³' },
                { month: 'Jan', val: 31, unit: 'm³' },
                { month: 'Feb', val: 27, unit: 'm³' },
                { month: 'Mar', val: 30, unit: 'm³' },
                { month: 'Apr', val: 31, unit: 'm³' }
            ]
        };

        function toggleDashboardUtility(type) {
            const btnElec = document.getElementById('btnChartElec');
            const btnWater = document.getElementById('btnChartWater');
            const container = document.getElementById('chartBarsContainer');
            const data = utilityHistoryData[type];
            
            if (type === 'elec') {
                btnElec.style.background = '#ecfdf5'; btnElec.style.color = '#047857'; btnElec.style.borderColor = '#a7f3d0';
                btnWater.style.background = 'transparent'; btnWater.style.color = '#64748b'; btnWater.style.borderColor = '#e2e8f0';
            } else {
                btnWater.style.background = '#f0f9ff'; btnWater.style.color = '#0284c7'; btnWater.style.borderColor = '#bae6fd';
                btnElec.style.background = 'transparent'; btnElec.style.color = '#64748b'; btnElec.style.borderColor = '#e2e8f0';
            }

            const maxVal = Math.max(...data.map(d => d.val));
            const minVal = Math.min(...data.map(d => d.val)) * 0.9;
            const range = maxVal - minVal;
            const strokeColor = type === 'elec' ? '#10b981' : '#3b82f6';
            const fillColor = type === 'elec' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(59, 130, 246, 0.15)';

            const points = data.map((item, index) => {
                const x = (index / (data.length - 1)) * 1000;
                const y = 100 - (((item.val - minVal) / range) * 80);
                return `${x},${y}`;
            }).join(' ');

            const polygonPoints = `0,100 ${points} 1000,100`;

            const dataPointsHtml = data.map((item, index) => {
                const x = (index / (data.length - 1)) * 100;
                const y = 100 - (((item.val - minVal) / range) * 80);
                return `
                    <div style="position: absolute; left: ${x}%; top: ${y}%; transform: translate(-50%, -50%); display: flex; flex-direction: column; align-items: center; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translate(-50%, -50%) scale(1.1)'" onmouseout="this.style.transform='translate(-50%, -50%) scale(1)'">
                        <div style="font-size: 11px; font-weight: 700; color: #0f172a; margin-bottom: 6px; background: white; padding: 2px 6px; border-radius: 6px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); white-space: nowrap;">${item.val} ${item.unit}</div>
                        <div style="width: 10px; height: 10px; border-radius: 50%; background: white; border: 3px solid ${strokeColor};"></div>
                    </div>
                `;
            }).join('');

            container.innerHTML = `
                <svg width="100%" height="100%" viewBox="0 0 1000 100" preserveAspectRatio="none" style="position: absolute; bottom: 0; left: 0;">
                    <polygon points="${polygonPoints}" fill="${fillColor}"/>
                    <polyline points="${points}" fill="none" stroke="${strokeColor}" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                ${dataPointsHtml}
            `;
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
                    <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">System Notifications</h3>
                    <span style="font-size: 11px; font-weight: 700; color: #3b82f6; background: #eff6ff; padding: 2px 8px; border-radius: 12px;">3 NEW</span>
                </div>
                <div style="max-height: 300px; overflow-y: auto;">
                    @php
                        $overrides = session('visitor_overrides', []);
                        $hasDynamic = false;
                    @endphp
                    
                    @foreach($overrides as $id => $data)
                        @php $hasDynamic = true; @endphp
                        <div style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; background: #f0fdf4;">
                            <div style="display: flex; gap: 12px;">
                                <div style="width: 32px; height: 32px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: #0f172a;">Visitor Entered</div>
                                    <div style="font-size: 12px; color: #475569; margin-top: 2px;">Your guest (${id}) has passed through the Main Gate at {{ $data['arrival_time'] }}.</div>
                                    <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Just now</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <div style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; background: #f8fafc;">
                        <div style="display: flex; gap: 12px;">
                            <div style="width: 32px; height: 32px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <div style="font-size: 13px; font-weight: 700; color: #0f172a;">Visitor Entered</div>
                                <div style="font-size: 12px; color: #475569; margin-top: 2px;">Your guest (Alex Mendez - VIS-3001) has passed through the Main Gate at 10:15 AM.</div>
                                <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Just now</div>
                            </div>
                        </div>
                    </div>
                    <div style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; background: #f8fafc;">
                        <div style="display: flex; gap: 12px;">
                            <div style="width: 32px; height: 32px; background: #dbeafe; color: #1e40af; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.407 2.67 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.407-2.67-1M12 16v1m-7-4a7 7 0 1114 0 7 7 0 01-14 0z"/></svg>
                            </div>
                            <div>
                                <div style="font-size: 13px; font-weight: 700; color: #0f172a;">Payment Confirmed</div>
                                <div style="font-size: 12px; color: #475569; margin-top: 2px;">Your GCash payment for Electricity Bill (EB-001) has been settled.</div>
                                <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">2 hours ago</div>
                            </div>
                        </div>
                    </div>
                    <div style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9;">
                        <div style="display: flex; gap: 12px;">
                            <div style="width: 32px; height: 32px; background: #fef3c7; color: #92400e; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            </div>
                            <div>
                                <div style="font-size: 13px; font-weight: 700; color: #0f172a;">Scheduled Maintenance</div>
                                <div style="font-size: 12px; color: #475569; margin-top: 2px;">Road repaving near Block B will occur this Friday. Please plan accordingly.</div>
                                <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">1 day ago</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="padding: 12px; text-align: center; background: #f8fafc;">
                    <a href="/resident/notifications" style="font-size: 12px; font-weight: 600; color: #3b82f6; text-decoration: none;">View all notifications</a>
                </div>
            </div>
        </div>

        <!-- Community Feed -->
        <div class="analytic-card" style="padding: 24px;">
            <div class="card-title">
                <span>Neighborhood Alerts</span>
            </div>
            <div style="display: flex; flex-direction: column; gap: 16px; margin-top: 16px;">
                <div style="border-left: 3px solid #f59e0b; padding-left: 12px;">
                    <div style="font-size: 13px; font-weight: 700; color: #0f172a;">Water Interruption</div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Tomorrow at 10 PM</div>
                </div>
                <div style="border-left: 3px solid #10b981; padding-left: 12px;">
                    <div style="font-size: 13px; font-weight: 700; color: #0f172a;">Annual Summer Fest</div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Draft Announcement</div>
                </div>
                <div style="border-left: 3px solid #ef4444; padding-left: 12px;">
                    <div style="font-size: 13px; font-weight: 700; color: #0f172a;">New Security Protocols</div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Effective immediately</div>
                </div>
            </div>
            <button class="btn btn-outline" style="width: 100%; justify-content: center; margin-top: 24px; font-size: 12px;">View All Announcements</button>
        </div>
    </div>

</div>

@endsection
