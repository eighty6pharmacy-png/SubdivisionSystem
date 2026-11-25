@extends('layouts.admin')

@section('title', 'Dashboard Overview')

@section('content')
    <!-- Stats Row -->
    <div class="stats-grid fade-up" style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px; margin-bottom: 14px;">
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
        <div class="stat-card blue">
            <div class="stat-value" style="margin-top: 10px;">{{ $visitorsTodayCount ?? 0 }}</div>
            <div class="stat-label">Visitors Today</div>
        </div>
        <div class="stat-card red">
            <div class="stat-value">{{ $openIncidentsCount ?? 0 }}</div>
            <div class="stat-label">Open Incidents</div>
        </div>
    </div>

    <!-- Charts & Recent Payers Row (50% / 50%) -->
    <div class="responsive-grid grid-2 fade-up-2" style="margin-bottom: 14px; gap: 14px; align-items: stretch;">
        <!-- Monthly Sales Performance (Half Width) -->
        <div class="card" style="padding: 16px; border-radius: 16px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.04); display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 14px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="card-title" style="margin: 0;">Monthly Sales Performance</span>
                    @php
                        $mom = $salesSummary['mom_change'] ?? null;
                    @endphp
                    @if($mom !== null)
                        <span style="font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 999px; background: {{ $mom >= 0 ? '#ecfdf5' : '#fef2f2' }}; color: {{ $mom >= 0 ? '#059669' : '#dc2626' }}; border: 1px solid {{ $mom >= 0 ? '#a7f3d0' : '#fecaca' }};">
                            {{ $mom >= 0 ? '▲' : '▼' }} {{ abs($mom) }}% vs last mo
                        </span>
                    @endif
                </div>
                <div style="display: inline-flex; background: #f1f5f9; padding: 2px; border-radius: 8px; gap: 2px;">
                    <button type="button" id="btnSalesBreakdown" onclick="switchSalesChartMode('breakdown')" style="padding: 4px 10px; font-size: 11px; font-weight: 700; border-radius: 6px; border: none; cursor: pointer; background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(0,0,0,0.06);">By Source</button>
                    <button type="button" id="btnSalesTotal" onclick="switchSalesChartMode('total')" style="padding: 4px 10px; font-size: 11px; font-weight: 700; border-radius: 6px; border: none; cursor: pointer; background: transparent; color: #64748b;">Total</button>
                </div>
            </div>

            <!-- Structured KPI Strip -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 14px;">
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <div style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px;">12-Month Total</div>
                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 2px;">₱{{ number_format($salesSummary['annual_total'] ?? 0, 2) }}</div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <div style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px;">Monthly Avg</div>
                    <div style="font-size: 15px; font-weight: 800; color: #059669; margin-top: 2px;">₱{{ number_format($salesSummary['monthly_avg'] ?? 0, 2) }}</div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <div style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px;">Peak ({{ $salesSummary['peak_month'] ?? '—' }})</div>
                    <div style="font-size: 15px; font-weight: 800; color: #2563eb; margin-top: 2px;">₱{{ number_format($salesSummary['peak_amount'] ?? 0, 2) }}</div>
                </div>
            </div>

            <div style="height: 225px; position: relative; margin-top: auto;">
                <canvas id="dashboardSalesChart"></canvas>
            </div>
        </div>

        <!-- Recent Payers & Payment History (Half Width) -->
        <div class="card" style="padding: 16px; border-radius: 16px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.04); display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
                <div>
                    <span class="card-title" style="margin: 0;">Recent Payers</span>
                    <div style="font-size: 11.5px; color: #64748b; font-weight: 600; margin-top: 2px;">Latest payments &amp; exact billing source</div>
                </div>
                <div style="display: inline-flex; background: #f1f5f9; padding: 2px; border-radius: 8px; gap: 2px; flex-wrap: wrap;">
                    <button type="button" onclick="filterRecentPayers('all', this)" class="rp-filter-btn" style="padding: 4px 9px; font-size: 11px; font-weight: 700; border-radius: 6px; border: none; cursor: pointer; background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(0,0,0,0.06);">All</button>
                    <button type="button" onclick="filterRecentPayers('downpayment', this)" class="rp-filter-btn" style="padding: 4px 9px; font-size: 11px; font-weight: 700; border-radius: 6px; border: none; cursor: pointer; background: transparent; color: #64748b;">Downpayment</button>
                    <button type="button" onclick="filterRecentPayers('financing', this)" class="rp-filter-btn" style="padding: 4px 9px; font-size: 11px; font-weight: 700; border-radius: 6px; border: none; cursor: pointer; background: transparent; color: #64748b;">Financing</button>
                    <button type="button" onclick="filterRecentPayers('utility', this)" class="rp-filter-btn" style="padding: 4px 9px; font-size: 11px; font-weight: 700; border-radius: 6px; border: none; cursor: pointer; background: transparent; color: #64748b;">Utilities</button>
                </div>
            </div>

            <div id="recentPayersList" style="display: flex; flex-direction: column; gap: 8px; max-height: 292px; overflow-y: auto; padding-right: 4px;">
                @forelse($recentPayments ?? [] as $rp)
                    @php
                        $catGroup = in_array($rp['category'], ['electricity', 'water']) ? 'utility' : $rp['category'];
                        $badgeColors = match($rp['category']) {
                            'electricity' => ['bg' => '#fef3c7', 'fg' => '#b45309', 'icon' => '⚡'],
                            'water'       => ['bg' => '#e0f2fe', 'fg' => '#0369a1', 'icon' => '💧'],
                            'downpayment' => ['bg' => '#ecfdf5', 'fg' => '#047857', 'icon' => '🏠'],
                            'financing'   => ['bg' => '#eff6ff', 'fg' => '#1d4ed8', 'icon' => '🏦'],
                            default       => ['bg' => '#f1f5f9', 'fg' => '#475569', 'icon' => '💳'],
                        };
                    @endphp
                    <a href="{{ $rp['target_url'] }}" class="recent-payer-row" data-category="{{ $catGroup }}"
                       style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 12px; border: 1px solid #f1f5f9; background: #f8fafc; text-decoration: none; transition: background 0.15s ease, border-color 0.15s ease;"
                       onmouseover="this.style.background='#f1f5f9'; this.style.borderColor='#cbd5e1';"
                       onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#f1f5f9';">
                        <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                            <div style="width: 34px; height: 34px; border-radius: 10px; background: {{ $badgeColors['bg'] }}; color: {{ $badgeColors['fg'] }}; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;">
                                {{ $badgeColors['icon'] }}
                            </div>
                            <div style="min-width: 0;">
                                <div style="font-size: 13px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $rp['payer'] }}
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 2px;">
                                    <span style="font-size: 11px; font-weight: 700; padding: 1px 7px; border-radius: 6px; background: {{ $badgeColors['bg'] }}; color: {{ $badgeColors['fg'] }};">
                                        {{ $rp['specific_payment'] }}
                                    </span>
                                    @if(!empty($rp['lot_label']))
                                        <span style="font-size: 11px; font-weight: 600; color: #64748b;">{{ $rp['lot_label'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div style="text-align: right; flex-shrink: 0;">
                            <div style="font-size: 13.5px; font-weight: 800; color: #059669;">₱{{ number_format($rp['amount'], 2) }}</div>
                            <div style="font-size: 10.5px; font-weight: 600; color: #64748b; margin-top: 2px;">{{ $rp['method'] }} · {{ $rp['paid_ago'] ?: $rp['paid_at'] }}</div>
                        </div>
                    </a>
                @empty
                    <div style="padding: 36px 16px; text-align: center; color: #94a3b8; font-size: 13px; font-weight: 600;">
                        No recent payments recorded yet.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Reservation, Downpayment & Finance Status Summary -->
    <div class="responsive-grid fade-up-2" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 14px; margin-bottom: 14px;">
        <!-- Reservation Card -->
        @php
            $resTotal = $reservationStatusSummary['total_count'] ?? 0;
            $resActive = $reservationStatusSummary['active'] ?? 0;
            $resCancelled = $reservationStatusSummary['cancelled'] ?? 0;
            $resCollected = $reservationStatusSummary['total_collected'] ?? 0;
        @endphp
        <div class="card" style="padding: 20px; border-radius: 16px; background: #fff; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.04); display: flex; flex-direction: column; gap: 14px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #f59e0b;"></span>
                    <span class="card-title" style="margin: 0;">Reservation</span>
                    <span style="font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 999px; background: #fffbeb; color: #b45309;">{{ $resTotal }} total</span>
                </div>
                <a href="/admin/reservation-fee" style="font-size: 12px; font-weight: 700; color: #2563eb; text-decoration: none;">View &rarr;</a>
            </div>

            <div style="display: grid; grid-template-columns: 120px 1fr; gap: 16px; align-items: center;">
                <div style="height: 120px; width: 120px; position: relative; margin: 0 auto;">
                    <canvas id="dashReservationStatusChart"></canvas>
                    <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none;">
                        <span style="font-size: 18px; font-weight: 800; color: #0f172a; line-height: 1;">{{ $resActive }}</span>
                        <span style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Active</span>
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 8px 12px;">
                        <div style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Fees Collected</div>
                        <div style="font-size: 15px; font-weight: 800; color: #059669;">₱{{ number_format($resCollected, 2) }}</div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600; color: #334155;">
                        <div style="display: flex; justify-content: space-between;">
                            <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#f59e0b;margin-right:6px;"></span>Active</span>
                            <span style="font-weight: 800;">{{ $resActive }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#ef4444;margin-right:6px;"></span>Cancelled</span>
                            <span style="font-weight: 800;">{{ $resCancelled }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Downpayment Card -->
        @php
            $dpTotal = $downpaymentStatusSummary['total_count'] ?? 0;
            $dpCollected = $downpaymentStatusSummary['total_collected'] ?? 0;
            $dpBalance = $downpaymentStatusSummary['total_balance'] ?? 0;
            $dpRate = $downpaymentStatusSummary['collection_rate'] ?? 0;
        @endphp
        <div class="card" style="padding: 20px; border-radius: 16px; background: #fff; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.04); display: flex; flex-direction: column; gap: 14px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #10b981;"></span>
                    <span class="card-title" style="margin: 0;">Downpayment</span>
                    <span style="font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 999px; background: #ecfdf5; color: #047857;">{{ $dpTotal }} accounts</span>
                </div>
                <a href="/admin/downpayment-fee" style="font-size: 12px; font-weight: 700; color: #2563eb; text-decoration: none;">View &rarr;</a>
            </div>

            <div style="display: grid; grid-template-columns: 120px 1fr; gap: 16px; align-items: center;">
                <div style="height: 120px; width: 120px; position: relative; margin: 0 auto;">
                    <canvas id="dashDownpaymentStatusChart"></canvas>
                    <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none;">
                        <span style="font-size: 16px; font-weight: 800; color: #0f172a; line-height: 1;">{{ $dpRate }}%</span>
                        <span style="font-size: 9.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Collected</span>
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                        <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 8px; padding: 7px 9px;">
                            <div style="font-size: 9.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Collected</div>
                            <div style="font-size: 13px; font-weight: 800; color: #059669;">₱{{ number_format($dpCollected, 0) }}</div>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 8px; padding: 7px 9px;">
                            <div style="font-size: 9.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Balance</div>
                            <div style="font-size: 13px; font-weight: 800; color: #d97706;">₱{{ number_format($dpBalance, 0) }}</div>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px 10px; font-size: 11.5px; font-weight: 600; color: #334155;">
                        <div><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#10b981;margin-right:5px;"></span>Fully Paid: <strong>{{ $downpaymentStatusSummary['fully_paid'] ?? 0 }}</strong></div>
                        <div><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#3b82f6;margin-right:5px;"></span>On Schedule: <strong>{{ $downpaymentStatusSummary['on_schedule'] ?? 0 }}</strong></div>
                        <div><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#ef4444;margin-right:5px;"></span>Overdue: <strong>{{ $downpaymentStatusSummary['overdue'] ?? 0 }}</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Finance Card -->
        @php
            $finTotal = $financeStatusSummary['total_count'] ?? 0;
            $finPaid = $financeStatusSummary['total_paid'] ?? 0;
            $finBalance = $financeStatusSummary['total_balance'] ?? 0;
            $finRate = $financeStatusSummary['collection_rate'] ?? 0;
        @endphp
        <div class="card" style="padding: 20px; border-radius: 16px; background: #fff; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.04); display: flex; flex-direction: column; gap: 14px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #3b82f6;"></span>
                    <span class="card-title" style="margin: 0;">Finance</span>
                    <span style="font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 999px; background: #eff6ff; color: #1d4ed8;">{{ $finTotal }} records</span>
                </div>
                <a href="/admin/financing" style="font-size: 12px; font-weight: 700; color: #2563eb; text-decoration: none;">View &rarr;</a>
            </div>

            <div style="display: grid; grid-template-columns: 120px 1fr; gap: 16px; align-items: center;">
                <div style="height: 120px; width: 120px; position: relative; margin: 0 auto;">
                    <canvas id="dashFinanceStatusChart"></canvas>
                    <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none;">
                        <span style="font-size: 16px; font-weight: 800; color: #0f172a; line-height: 1;">{{ $finRate }}%</span>
                        <span style="font-size: 9.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Paid</span>
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                        <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 8px; padding: 7px 9px;">
                            <div style="font-size: 9.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Paid</div>
                            <div style="font-size: 13px; font-weight: 800; color: #059669;">₱{{ number_format($finPaid, 0) }}</div>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 8px; padding: 7px 9px;">
                            <div style="font-size: 9.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Receivable</div>
                            <div style="font-size: 13px; font-weight: 800; color: #2563eb;">₱{{ number_format($finBalance, 0) }}</div>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px 10px; font-size: 11.5px; font-weight: 600; color: #334155;">
                        <div><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;margin-right:5px;"></span>Pending: <strong>{{ $financeStatusSummary['pending'] ?? 0 }}</strong></div>
                        <div><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#f59e0b;margin-right:5px;"></span>Processing: <strong>{{ $financeStatusSummary['processing'] ?? 0 }}</strong></div>
                        <div><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#3b82f6;margin-right:5px;"></span>Released: <strong>{{ $financeStatusSummary['released'] ?? 0 }}</strong></div>
                        <div><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#10b981;margin-right:5px;"></span>Cleared: <strong>{{ $financeStatusSummary['cleared'] ?? 0 }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Community Utilities & Consumption Analytics Section (Electricity & Water) -->
    <div class="fade-up-2" style="margin-bottom: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 10px;">
            <div>
                <div style="font-size: 18px; font-weight: 800; color: var(--text-dark, #0f172a);">
                    <span>Utility Consumption &amp; Billing Analytics</span>
                </div>
            </div>
            
            <div style="display: flex; gap: 8px;">
                <a href="/admin/billing" class="btn btn-outline btn-sm" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700; font-size: 12px; border-radius: 8px;">
                    <span>⚡</span> Electricity Billing &rarr;
                </a>
                <a href="/admin/water" class="btn btn-outline btn-sm" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700; font-size: 12px; border-radius: 8px;">
                    <span>💧</span> Water Billing &rarr;
                </a>
            </div>
        </div>

        <div class="responsive-grid grid-2" style="gap: 14px;">
            <!-- Electricity Analytics Card -->
            <div class="card" style="padding: 20px; border-radius: 16px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; flex-direction: column;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 38px; height: 38px; border-radius: 10px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 19px; flex-shrink: 0;">
                            ⚡
                        </div>
                        <div>
                            <div style="font-size: 15px; font-weight: 800; color: #0f172a; line-height: 1.2;">Electricity Consumption</div>
                            <div style="font-size: 12px; color: #64748b; font-weight: 600; margin-top: 2px;">Monthly kWh Usage vs. Billed Revenue</div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 6px; align-items: center;">
                        <span style="background: #fffbeb; color: #b45309; border: 1px solid #fef3c7; padding: 4px 8px; border-radius: 8px; font-size: 11px; font-weight: 700;">
                            Rate: ₱{{ number_format($elecSummary['rate'] ?? 13, 2) }}/kWh
                        </span>
                        <div style="display: inline-flex; background: #f1f5f9; padding: 2px; border-radius: 8px; gap: 2px;">
                            <button type="button" onclick="switchElecChartMetric('both')" id="btnElecBoth" style="padding: 3px 8px; font-size: 11px; font-weight: 700; border-radius: 6px; border: none; cursor: pointer; background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">Both</button>
                            <button type="button" onclick="switchElecChartMetric('usage')" id="btnElecUsage" style="padding: 3px 8px; font-size: 11px; font-weight: 700; border-radius: 6px; border: none; cursor: pointer; background: transparent; color: #64748b;">kWh</button>
                            <button type="button" onclick="switchElecChartMetric('amount')" id="btnElecAmount" style="padding: 3px 8px; font-size: 11px; font-weight: 700; border-radius: 6px; border: none; cursor: pointer; background: transparent; color: #64748b;">₱ Billed</button>
                        </div>
                    </div>
                </div>

                <!-- Electricity Stat Badges -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 14px;">
                    <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                        <div style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Total Paid</div>
                        <div style="font-size: 15px; font-weight: 800; color: #d97706; margin-top: 2px;">₱ {{ number_format($elecSummary['total_paid'] ?? 0, 2) }}</div>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                        <div style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Total Billed</div>
                        <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 2px;">₱ {{ number_format($elecSummary['total_billed'] ?? 0, 2) }}</div>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                        <div style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Account Status</div>
                        <div style="font-size: 12.5px; font-weight: 800; color: #059669; margin-top: 3px;">
                            {{ $elecSummary['paid_count'] ?? 0 }} Paid <span style="color: #94a3b8; font-weight: 500;">/</span> <span style="color: #dc2626;">{{ $elecSummary['pending_count'] ?? 0 }} Unpaid</span>
                        </div>
                    </div>
                </div>

                <!-- Electricity Chart Canvas -->
                <div style="height: 220px; position: relative; width: 100%; margin-top: auto;">
                    <canvas id="dashboardElectricityChart"></canvas>
                </div>
            </div>

            <!-- Water Analytics Card -->
            <div class="card" style="padding: 20px; border-radius: 16px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; flex-direction: column;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 38px; height: 38px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 19px; flex-shrink: 0;">
                            💧
                        </div>
                        <div>
                            <div style="font-size: 15px; font-weight: 800; color: #0f172a; line-height: 1.2;">Water Consumption</div>
                            <div style="font-size: 12px; color: #64748b; font-weight: 600; margin-top: 2px;">Monthly m³ Volume vs. Billed Revenue</div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 6px; align-items: center;">
                        <span style="background: #f0f9ff; color: #0369a1; border: 1px solid #e0f2fe; padding: 4px 8px; border-radius: 8px; font-size: 11px; font-weight: 700;">
                            Rate: ₱{{ number_format($waterSummary['rate'] ?? 15, 2) }}/m³
                        </span>
                        <div style="display: inline-flex; background: #f1f5f9; padding: 2px; border-radius: 8px; gap: 2px;">
                            <button type="button" onclick="switchWaterChartMetric('both')" id="btnWaterBoth" style="padding: 3px 8px; font-size: 11px; font-weight: 700; border-radius: 6px; border: none; cursor: pointer; background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">Both</button>
                            <button type="button" onclick="switchWaterChartMetric('usage')" id="btnWaterUsage" style="padding: 3px 8px; font-size: 11px; font-weight: 700; border-radius: 6px; border: none; cursor: pointer; background: transparent; color: #64748b;">m³</button>
                            <button type="button" onclick="switchWaterChartMetric('amount')" id="btnWaterAmount" style="padding: 3px 8px; font-size: 11px; font-weight: 700; border-radius: 6px; border: none; cursor: pointer; background: transparent; color: #64748b;">₱ Billed</button>
                        </div>
                    </div>
                </div>

                <!-- Water Stat Badges -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 14px;">
                    <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                        <div style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Total Paid</div>
                        <div style="font-size: 15px; font-weight: 800; color: #0284c7; margin-top: 2px;">₱ {{ number_format($waterSummary['total_paid'] ?? 0, 2) }}</div>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                        <div style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Total Billed</div>
                        <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 2px;">₱ {{ number_format($waterSummary['total_billed'] ?? 0, 2) }}</div>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                        <div style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Account Status</div>
                        <div style="font-size: 12.5px; font-weight: 800; color: #059669; margin-top: 3px;">
                            {{ $waterSummary['paid_count'] ?? 0 }} Paid <span style="color: #94a3b8; font-weight: 500;">/</span> <span style="color: #dc2626;">{{ $waterSummary['pending_count'] ?? 0 }} Unpaid</span>
                        </div>
                    </div>
                </div>

                <!-- Water Chart Canvas -->
                <div style="height: 220px; position: relative; width: 100%; margin-top: auto;">
                    <canvas id="dashboardWaterChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Operations Calendars Section: Incident & Appointment Dual Suite -->
    <div class="fade-up-2" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
            <div>
                <div style="font-size: 18px; font-weight: 800; color: var(--text-dark, #0f172a);">
                    <span>Operations &amp; Schedule Calendars</span>
                </div>
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
            const salesLabels = {!! json_encode($monthlySalesLabels) !!};
            const salesTotalData = {!! json_encode($monthlySalesData) !!};
            const salesBySource = {!! json_encode($monthlySalesBySource ?? ['downpayment' => [], 'financing' => [], 'utility' => [], 'counts' => []]) !!};

            const ctx = document.getElementById('dashboardSalesChart')?.getContext('2d');
            if (ctx) {
                window.salesChartMode = 'breakdown';
                window.dashboardSalesChartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: salesLabels,
                        datasets: [
                            {
                                label: 'Downpayment',
                                data: salesBySource.downpayment || [],
                                backgroundColor: '#10b981',
                                borderRadius: 4,
                                stack: 'sales'
                            },
                            {
                                label: 'Financing',
                                data: salesBySource.financing || [],
                                backgroundColor: '#3b82f6',
                                borderRadius: 4,
                                stack: 'sales'
                            },
                            {
                                label: 'Utilities',
                                data: salesBySource.utility || [],
                                backgroundColor: '#f59e0b',
                                borderRadius: 4,
                                stack: 'sales'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: { usePointStyle: true, boxWidth: 8, padding: 14, font: { size: 11, weight: '600' }, color: '#475569' }
                            },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ₱' + Number(context.raw || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                    },
                                    footer: function(items) {
                                        if (!items.length) return '';
                                        const idx = items[0].dataIndex;
                                        const total = Number(salesTotalData[idx] || 0);
                                        const cnt = Number((salesBySource.counts || [])[idx] || 0);
                                        return 'Total: ₱' + total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (' + cnt + (cnt === 1 ? ' txn)' : ' txns)');
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                stacked: true,
                                ticks: {
                                    callback: function(value) {
                                        if (value >= 1000000) return '₱' + (value / 1000000).toFixed(1) + 'M';
                                        if (value >= 1000) return '₱' + Math.round(value / 1000) + 'k';
                                        return '₱' + value;
                                    },
                                    font: { weight: '600', size: 11 },
                                    color: '#64748b'
                                },
                                grid: { color: '#f1f5f9' }
                            },
                            x: {
                                stacked: true,
                                ticks: { font: { weight: '600', size: 11 }, color: '#64748b' },
                                grid: { display: false }
                            }
                        }
                    }
                });

                window.switchSalesChartMode = function(mode) {
                    const chart = window.dashboardSalesChartInstance;
                    if (!chart) return;
                    const btnB = document.getElementById('btnSalesBreakdown');
                    const btnT = document.getElementById('btnSalesTotal');
                    const activeStyle = 'padding: 4px 10px; font-size: 11px; font-weight: 700; border-radius: 6px; border: none; cursor: pointer; background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(0,0,0,0.06);';
                    const inactiveStyle = 'padding: 4px 10px; font-size: 11px; font-weight: 700; border-radius: 6px; border: none; cursor: pointer; background: transparent; color: #64748b;';
                    if (btnB) btnB.style = mode === 'breakdown' ? activeStyle : inactiveStyle;
                    if (btnT) btnT.style = mode === 'total' ? activeStyle : inactiveStyle;

                    if (mode === 'total') {
                        chart.data.datasets = [{
                            label: 'Total Monthly Sales',
                            data: salesTotalData,
                            backgroundColor: '#10b981',
                            borderRadius: 6,
                            borderSkipped: false
                        }];
                        chart.options.plugins.legend.display = false;
                        chart.options.scales.x.stacked = false;
                        chart.options.scales.y.stacked = false;
                    } else {
                        chart.data.datasets = [
                            { label: 'Downpayment', data: salesBySource.downpayment || [], backgroundColor: '#10b981', borderRadius: 4, stack: 'sales' },
                            { label: 'Financing', data: salesBySource.financing || [], backgroundColor: '#3b82f6', borderRadius: 4, stack: 'sales' },
                            { label: 'Utilities', data: salesBySource.utility || [], backgroundColor: '#f59e0b', borderRadius: 4, stack: 'sales' }
                        ];
                        chart.options.plugins.legend.display = true;
                        chart.options.scales.x.stacked = true;
                        chart.options.scales.y.stacked = true;
                    }
                    chart.update();
                };
            }

            // Initialize Dual Dashboard Calendars
            initDashboardCalendars();

            // Initialize Community Electricity Analytics Chart
            const elecCanvas = document.getElementById('dashboardElectricityChart');
            if (elecCanvas) {
                const elecCtx = elecCanvas.getContext('2d');
                window.elecChartInstance = new Chart(elecCtx, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode($utilityMonthsLabels ?? []) !!},
                        datasets: [
                            {
                                type: 'bar',
                                label: 'Electricity Usage (kWh)',
                                data: {!! json_encode($elecUsageSeries ?? []) !!},
                                backgroundColor: 'rgba(245, 158, 11, 0.75)',
                                borderColor: '#f59e0b',
                                borderWidth: 1,
                                borderRadius: 5,
                                yAxisID: 'y',
                                order: 2
                            },
                            {
                                type: 'line',
                                label: 'Billed Revenue (₱)',
                                data: {!! json_encode($elecAmountSeries ?? []) !!},
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                borderWidth: 2.5,
                                pointBackgroundColor: '#10b981',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 1.5,
                                pointRadius: 3.5,
                                pointHoverRadius: 5,
                                fill: false,
                                tension: 0.35,
                                yAxisID: 'y1',
                                order: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    boxWidth: 12,
                                    font: { weight: '600', size: 11 },
                                    color: '#475569'
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        if (ctx.datasetIndex === 0) {
                                            return ' Usage: ' + Number(ctx.raw || 0).toLocaleString() + ' kWh';
                                        }
                                        return ' Billed: ₱' + Number(ctx.raw || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                beginAtZero: true,
                                title: { display: true, text: 'kWh', color: '#b45309', font: { weight: '700', size: 10 } },
                                ticks: {
                                    font: { weight: '600', size: 10 },
                                    color: '#64748b'
                                },
                                grid: { color: '#f1f5f9' }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                beginAtZero: true,
                                title: { display: true, text: '₱ Billed', color: '#047857', font: { weight: '700', size: 10 } },
                                ticks: {
                                    font: { weight: '600', size: 10 },
                                    color: '#64748b',
                                    callback: function(v) { return '₱' + (v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v); }
                                },
                                grid: { drawOnChartArea: false }
                            },
                            x: {
                                ticks: { font: { weight: '600', size: 11 }, color: '#64748b' },
                                grid: { display: false }
                            }
                        }
                    }
                });
            }

            // Initialize Community Water Analytics Chart
            const waterCanvas = document.getElementById('dashboardWaterChart');
            if (waterCanvas) {
                const waterCtx = waterCanvas.getContext('2d');
                window.waterChartInstance = new Chart(waterCtx, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode($utilityMonthsLabels ?? []) !!},
                        datasets: [
                            {
                                type: 'bar',
                                label: 'Water Volume (m³)',
                                data: {!! json_encode($waterUsageSeries ?? []) !!},
                                backgroundColor: 'rgba(2, 132, 199, 0.75)',
                                borderColor: '#0284c7',
                                borderWidth: 1,
                                borderRadius: 5,
                                yAxisID: 'y',
                                order: 2
                            },
                            {
                                type: 'line',
                                label: 'Billed Revenue (₱)',
                                data: {!! json_encode($waterAmountSeries ?? []) !!},
                                borderColor: '#6366f1',
                                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                borderWidth: 2.5,
                                pointBackgroundColor: '#6366f1',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 1.5,
                                pointRadius: 3.5,
                                pointHoverRadius: 5,
                                fill: false,
                                tension: 0.35,
                                yAxisID: 'y1',
                                order: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    boxWidth: 12,
                                    font: { weight: '600', size: 11 },
                                    color: '#475569'
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        if (ctx.datasetIndex === 0) {
                                            return ' Volume: ' + Number(ctx.raw || 0).toLocaleString() + ' m³';
                                        }
                                        return ' Billed: ₱' + Number(ctx.raw || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                beginAtZero: true,
                                title: { display: true, text: 'm³', color: '#0369a1', font: { weight: '700', size: 10 } },
                                ticks: {
                                    font: { weight: '600', size: 10 },
                                    color: '#64748b'
                                },
                                grid: { color: '#f1f5f9' }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                beginAtZero: true,
                                title: { display: true, text: '₱ Billed', color: '#4338ca', font: { weight: '700', size: 10 } },
                                ticks: {
                                    font: { weight: '600', size: 10 },
                                    color: '#64748b',
                                    callback: function(v) { return '₱' + (v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v); }
                                },
                                grid: { drawOnChartArea: false }
                            },
                            x: {
                                ticks: { font: { weight: '600', size: 11 }, color: '#64748b' },
                                grid: { display: false }
                            }
                        }
                    }
                });
            }

            const initSummaryDonut = (id, labels, data, colors) => {
                const c = document.getElementById(id)?.getContext('2d');
                if (!c) return;
                const total = data.reduce((sum, v) => sum + Number(v || 0), 0);
                const chartLabels = total > 0 ? labels : ['No Data'];
                const chartData = total > 0 ? data : [1];
                const chartColors = total > 0 ? colors : ['#e2e8f0'];

                new Chart(c, {
                    type: 'doughnut',
                    data: {
                        labels: chartLabels,
                        datasets: [{ data: chartData, backgroundColor: chartColors, borderWidth: 0, hoverOffset: total > 0 ? 4 : 0 }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: { display: false },
                            tooltip: { enabled: total > 0 }
                        }
                    }
                });
            };

            initSummaryDonut(
                'dashReservationStatusChart',
                ['Active', 'Cancelled'],
                [{{ $reservationStatusSummary['active'] ?? 0 }}, {{ $reservationStatusSummary['cancelled'] ?? 0 }}],
                ['#f59e0b', '#ef4444']
            );
            initSummaryDonut(
                'dashDownpaymentStatusChart',
                ['Fully Paid', 'On Schedule', 'Overdue'],
                [{{ $downpaymentStatusSummary['fully_paid'] ?? 0 }}, {{ $downpaymentStatusSummary['on_schedule'] ?? 0 }}, {{ $downpaymentStatusSummary['overdue'] ?? 0 }}],
                ['#10b981', '#3b82f6', '#ef4444']
            );
            initSummaryDonut(
                'dashFinanceStatusChart',
                ['Pending', 'Processing', 'Released', 'Cleared'],
                [{{ $financeStatusSummary['pending'] ?? 0 }}, {{ $financeStatusSummary['processing'] ?? 0 }}, {{ $financeStatusSummary['released'] ?? 0 }}, {{ $financeStatusSummary['cleared'] ?? 0 }}],
                ['#94a3b8', '#f59e0b', '#3b82f6', '#10b981']
            );
        });

        function filterRecentPayers(category, btn) {
            document.querySelectorAll('.rp-filter-btn').forEach(b => {
                b.style.background = 'transparent';
                b.style.color = '#64748b';
                b.style.boxShadow = 'none';
            });
            if (btn) {
                btn.style.background = '#fff';
                btn.style.color = '#0f172a';
                btn.style.boxShadow = '0 1px 2px rgba(0,0,0,0.06)';
            }
            document.querySelectorAll('.recent-payer-row').forEach(row => {
                const rowCat = row.getAttribute('data-category');
                row.style.display = (category === 'all' || rowCat === category) ? 'flex' : 'none';
            });
        }

        // Utility Chart Metric Toggle Switchers
        function switchElecChartMetric(mode) {
            if (!window.elecChartInstance) return;
            const btnBoth = document.getElementById('btnElecBoth');
            const btnUsage = document.getElementById('btnElecUsage');
            const btnAmount = document.getElementById('btnElecAmount');
            const activeStyle = 'padding: 3px 8px; font-size: 11px; font-weight: 700; border-radius: 6px; border: none; cursor: pointer; background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(0,0,0,0.05);';
            const inactiveStyle = 'padding: 3px 8px; font-size: 11px; font-weight: 700; border-radius: 6px; border: none; cursor: pointer; background: transparent; color: #64748b;';
            if (btnBoth) btnBoth.style = mode === 'both' ? activeStyle : inactiveStyle;
            if (btnUsage) btnUsage.style = mode === 'usage' ? activeStyle : inactiveStyle;
            if (btnAmount) btnAmount.style = mode === 'amount' ? activeStyle : inactiveStyle;

            if (mode === 'both') {
                window.elecChartInstance.setDatasetVisibility(0, true);
                window.elecChartInstance.setDatasetVisibility(1, true);
                window.elecChartInstance.options.scales.y.display = true;
                window.elecChartInstance.options.scales.y1.display = true;
            } else if (mode === 'usage') {
                window.elecChartInstance.setDatasetVisibility(0, true);
                window.elecChartInstance.setDatasetVisibility(1, false);
                window.elecChartInstance.options.scales.y.display = true;
                window.elecChartInstance.options.scales.y1.display = false;
            } else if (mode === 'amount') {
                window.elecChartInstance.setDatasetVisibility(0, false);
                window.elecChartInstance.setDatasetVisibility(1, true);
                window.elecChartInstance.options.scales.y.display = false;
                window.elecChartInstance.options.scales.y1.display = true;
            }
            window.elecChartInstance.update();
        }

        function switchWaterChartMetric(mode) {
            if (!window.waterChartInstance) return;
            const btnBoth = document.getElementById('btnWaterBoth');
            const btnUsage = document.getElementById('btnWaterUsage');
            const btnAmount = document.getElementById('btnWaterAmount');
            const activeStyle = 'padding: 3px 8px; font-size: 11px; font-weight: 700; border-radius: 6px; border: none; cursor: pointer; background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(0,0,0,0.05);';
            const inactiveStyle = 'padding: 3px 8px; font-size: 11px; font-weight: 700; border-radius: 6px; border: none; cursor: pointer; background: transparent; color: #64748b;';
            if (btnBoth) btnBoth.style = mode === 'both' ? activeStyle : inactiveStyle;
            if (btnUsage) btnUsage.style = mode === 'usage' ? activeStyle : inactiveStyle;
            if (btnAmount) btnAmount.style = mode === 'amount' ? activeStyle : inactiveStyle;

            if (mode === 'both') {
                window.waterChartInstance.setDatasetVisibility(0, true);
                window.waterChartInstance.setDatasetVisibility(1, true);
                window.waterChartInstance.options.scales.y.display = true;
                window.waterChartInstance.options.scales.y1.display = true;
            } else if (mode === 'usage') {
                window.waterChartInstance.setDatasetVisibility(0, true);
                window.waterChartInstance.setDatasetVisibility(1, false);
                window.waterChartInstance.options.scales.y.display = true;
                window.waterChartInstance.options.scales.y1.display = false;
            } else if (mode === 'amount') {
                window.waterChartInstance.setDatasetVisibility(0, false);
                window.waterChartInstance.setDatasetVisibility(1, true);
                window.waterChartInstance.options.scales.y.display = false;
                window.waterChartInstance.options.scales.y1.display = true;
            }
            window.waterChartInstance.update();
        }

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
                        <div class="dash-event-chip" style="cursor: pointer; background: ${meta.bg}; color: ${meta.color}; border-color: ${meta.border};"
                             onclick="window.location.href='/admin/appointments?apt=${encodeURIComponent(item.id)}'"
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
