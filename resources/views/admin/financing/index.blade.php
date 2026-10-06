@extends('layouts.admin')
@section('title', 'Financing Records')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">
<style>
    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 12px;
        font-size: 13px;
        font-weight: 600;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }
    .btn-view {
        background-color: #eff6ff;
        color: #2563eb;
    }
    .btn-view:hover {
        background-color: #dbeafe;
        transform: translateY(-1px);
    }
    .status-badge {
        padding: 4px 10px;
        border-radius: 99px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-block;
    }
    .status-pending { background: #f1f5f9; color: #64748b; }
    .status-processing { background: #fef3c7; color: #d97706; }
    .status-released { background: #dbeafe; color: #2563eb; }
    .status-cleared { background: #ecfdf5; color: #059669; }
</style>

<div class="bill-container fade-in">
    <div class="bill-header">
        <div class="bill-title">
            <h1>Loan & Additional Fees Tracking</h1>
            <p>Monitor loan releases, deductions, and buyer receivables.</p>
        </div>
        <div class="bill-actions">
            <a href="{{ route('financing.create') }}" class="btn btn-outline" style="border-color: var(--bill-primary); color: var(--bill-primary); display: inline-flex; align-items: center; text-decoration: none;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                    style="margin-right: 8px;">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                New Record
            </a>
        </div>
    </div>

    <!-- Stats Summary Row -->
    <div class="responsive-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px;">
        <div style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid #64748b;">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Pending</div>
            <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ $statusCounts['Pending'] }}</div>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid #f59e0b;">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Processing</div>
            <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ $statusCounts['Processing'] }}</div>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid #3b82f6;">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Released</div>
            <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ $statusCounts['Released'] }}</div>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid #10b981;">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Cleared</div>
            <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ $statusCounts['Cleared'] }}</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="responsive-grid grid-2-1 fade-up-2" style="margin-bottom:24px;">
        <div class="card" style="background: #fff; border-radius: 16px; padding: 20px; border: 1px solid var(--bill-border);">
            <div style="margin-bottom: 16px;">
                <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Receivables Overview</h3>
                <p style="font-size: 13px; color: #64748b; margin: 2px 0 0 0;">Shortfall balances vs collected</p>
            </div>
            <div style="height: 220px; position: relative;">
                <canvas id="receivablesChart"></canvas>
            </div>
        </div>
        <div class="card" style="background: #fff; border-radius: 16px; padding: 20px; border: 1px solid var(--bill-border);">
            <div style="margin-bottom: 16px;">
                <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Status Distribution</h3>
            </div>
            <div style="height: 200px; position: relative; display: flex; justify-content: center;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Master List Table -->
    <div class="analytic-card" style="padding: 0; overflow: hidden; border-radius: 16px; border: 1px solid #e2e8f0; background: #fff;">
        <div style="padding: 12px 24px; border-bottom: 1px solid var(--bill-border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc; flex-wrap: wrap; gap: 12px;">
            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Financing Records</h3>

            <form method="GET" action="{{ route('financing.index') }}" style="display: flex; gap: 8px; align-items: center; margin: 0;">
                <input type="text" name="search" placeholder="Search buyer name..." value="{{ request('search') }}"
                    style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 200px;">

                <select name="status" style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: white;">
                    <option value="">All Statuses</option>
                    <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="Processing" {{ request('status') == 'Processing' ? 'selected' : '' }}>Processing</option>
                    <option value="Released" {{ request('status') == 'Released' ? 'selected' : '' }}>Released</option>
                    <option value="Cleared" {{ request('status') == 'Cleared' ? 'selected' : '' }}>Cleared</option>
                </select>

                <button type="submit" class="pay-button" style="padding: 6px 12px; font-size: 13px;">Filter</button>
                <a href="{{ route('financing.index') }}" style="font-size: 13px; color: #64748b; text-decoration: none;">Reset</a>
            </form>
        </div>

        <div style="overflow-x: auto;">
            <table class="bill-table" style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 12px 24px; text-align: left; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase;">Buyer Name</th>
                        <th style="padding: 12px 24px; text-align: left; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase;">Block & Lot</th>
                        <th style="padding: 12px 24px; text-align: right; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase;">Contract Price</th>
                        <th style="padding: 12px 24px; text-align: right; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase;">Equity</th>
                        <th style="padding: 12px 24px; text-align: right; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase;">Shortfall Bal.</th>
                        <th style="padding: 12px 24px; text-align: center; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase;">Status</th>
                        <th style="padding: 12px 24px; text-align: right; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $rec)
                        <tr style="border-bottom: 1px solid #e2e8f0; transition: background 0.2s;">
                            <td style="padding: 12px 24px; font-size: 14px; font-weight: 600; color: #0f172a;">
                                {{ $rec->buyerMasterList->first_name ?? '' }} {{ $rec->buyerMasterList->last_name ?? 'N/A' }}
                            </td>
                            <td style="padding: 12px 24px; font-size: 14px; color: #475569;">
                                Block {{ $rec->lot->block ?? 'N/A' }}, Lot {{ $rec->lot->lot_number ?? 'N/A' }}
                            </td>
                            <td style="padding: 12px 24px; font-size: 14px; color: #475569; text-align: right; font-variant-numeric: tabular-nums;">
                                ₱{{ number_format($rec->contract_price, 2) }}
                            </td>
                            <td style="padding: 12px 24px; font-size: 14px; color: #475569; text-align: right; font-variant-numeric: tabular-nums;">
                                ₱{{ number_format($rec->paid_by_vendee_equity, 2) }}
                            </td>
                            <td style="padding: 12px 24px; font-size: 14px; text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; color: {{ $rec->balance > 0 ? '#dc2626' : '#059669' }}">
                                ₱{{ number_format($rec->balance, 2) }}
                            </td>
                            <td style="padding: 12px 24px; text-align: center;">
                                <span class="status-badge status-{{ strtolower($rec->status) }}">{{ $rec->status }}</span>
                            </td>
                            <td style="padding: 12px 24px; text-align: right;">
                                <a href="{{ route('financing.show', $rec->id) }}" class="action-btn btn-view">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 32px; text-align: center; color: #64748b; font-size: 14px;">No financing records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Receivables Chart
        var ctx1 = document.getElementById('receivablesChart').getContext('2d');
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: ['Total Receivables (Shortfall)', 'Total Paid by Buyers', 'Total Remaining Balances'],
                datasets: [{
                    label: 'Amount (₱)',
                    data: [{{ $totalReceivables }}, {{ $totalPaid }}, {{ $totalBalances }}],
                    backgroundColor: ['#3b82f6', '#10b981', '#ef4444'],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, grid: { borderDash: [2, 4], color: '#e2e8f0' } }, x: { grid: { display: false } } }
            }
        });

        // Status Chart
        var ctx2 = document.getElementById('statusChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Processing', 'Released', 'Cleared'],
                datasets: [{
                    data: [{{ $statusCounts['Pending'] }}, {{ $statusCounts['Processing'] }}, {{ $statusCounts['Released'] }}, {{ $statusCounts['Cleared'] }}],
                    backgroundColor: ['#94a3b8', '#f59e0b', '#3b82f6', '#10b981'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, font: { size: 11, family: 'Inter' } } }
                }
            }
        });
    });
</script>
@endsection

