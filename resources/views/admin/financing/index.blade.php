@extends('layouts.admin')
@section('title', 'Financing Records')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}?v={{ filemtime(public_path('css/admin-finance.css')) }}">
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
            <button type="button" class="btn btn-primary" onclick="openNewFinancingModal()">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                    style="margin-right: 8px;">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 4v16m8-8H4" />
                </svg>
                New Record
            </button>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #d1fae5; color: #065f46; border-left: 4px solid #059669; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; font-size: 14px;">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background: #fee2e2; color: #991b1b; border-left: 4px solid #dc2626; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; font-size: 14px;">
            {{ session('error') }}
        </div>
    @endif

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

                <select name="status" onchange="this.form.submit()" style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: white;">
                    <option value="">All Statuses</option>
                    <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="Processing" {{ request('status') == 'Processing' ? 'selected' : '' }}>Processing</option>
                    <option value="Released" {{ request('status') == 'Released' ? 'selected' : '' }}>Released</option>
                    <option value="Cleared" {{ request('status') == 'Cleared' ? 'selected' : '' }}>Cleared</option>
                </select>

                <select name="block" onchange="this.form.submit()" style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: white;">
                    <option value="">All Blocks</option>
                    @foreach($blocks as $block)
                        <option value="{{ $block }}" {{ (string) request('block') === (string) $block ? 'selected' : '' }}>Block {{ $block }}</option>
                    @endforeach
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
        {{ $records->links('pagination.admin') }}
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

    const finBuyersList = @json($eligibleBuyers ?? []);

    function filterFinBuyers(query) {
        const resultsBox = document.getElementById('finBuyerSearchResults');
        if (!resultsBox) return;

        const q = (query || '').toLowerCase().trim();
        const filtered = finBuyersList.filter(b => {
            const fullName = `${b.first_name || ''} ${b.middle_name || ''} ${b.last_name || ''}`.toLowerCase();
            const reverseName = `${b.last_name || ''} ${b.first_name || ''}`.toLowerCase();
            const email = (b.email || '').toLowerCase();
            const contact = (b.contact_number || '').toLowerCase();
            const lotMatch = `block ${b.block_no || ''} lot ${b.lot_no || ''} blk ${b.block_no || ''}`.toLowerCase();
            return !q || fullName.includes(q) || reverseName.includes(q) || email.includes(q) || contact.includes(q) || lotMatch.includes(q);
        });

        if (filtered.length === 0) {
            resultsBox.innerHTML = '<div style="padding: 16px; text-align: center; color: #94a3b8; font-size: 13px;">No matching buyers found.</div>';
            resultsBox.style.display = 'block';
            return;
        }

        resultsBox.innerHTML = filtered.map((b, idx) => {
            const fullName = `${b.last_name || ''}, ${b.first_name || ''}${b.middle_name ? ' ' + b.middle_name.charAt(0) + '.' : ''}`;
            const contact = b.contact_number || 'No contact';
            const email = b.email || '';
            const recentTag = !q && idx < 3
                ? `<span style="font-size: 10px; padding: 2px 6px; background: #fef3c7; color: #92400e; border-radius: 4px; font-weight: 700; margin-right: 4px;">Recent</span>`
                : '';

            const lots = (b.reservations || [])
                .filter(r => r.status !== 'Cancelled' && r.lot)
                .map(r => `Blk ${r.lot.block} Lot ${r.lot.lot_number}`);
            const lotText = lots.length > 0 ? lots.join(', ') : `Blk ${b.block_no || 'N/A'} Lot ${b.lot_no || 'N/A'}`;
            const lotBadge = `<span style="font-size: 11px; padding: 2px 6px; background: #e0f2fe; color: #0369a1; border-radius: 4px; font-weight: 700;">${lotText}</span>`;

            return `
                <div onclick="selectFinBuyer('${b.id}')"
                    style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.15s ease;"
                    onmouseover="this.style.background='#f8fafc'"
                    onmouseout="this.style.background='#ffffff'">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                        <span style="font-weight: 700; font-size: 14px; color: #0f172a;">${fullName}</span>
                        <span>${recentTag}${lotBadge}</span>
                    </div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                        ${email ? email + ' • ' : ''}${contact}
                    </div>
                </div>
            `;
        }).join('');

        resultsBox.style.display = 'block';
    }

    function selectFinBuyer(id) {
        const buyer = finBuyersList.find(b => String(b.id) === String(id));
        if (!buyer) return;

        document.getElementById('finBuyerId').value = buyer.id;
        const fullName = `${buyer.last_name || ''}, ${buyer.first_name || ''}${buyer.middle_name ? ' ' + buyer.middle_name : ''}`;
        document.getElementById('finSelectedBuyerName').textContent = fullName;
        document.getElementById('finSelectedBuyerMeta').textContent = `${buyer.email ? buyer.email + ' • ' : ''}${buyer.contact_number || 'No contact'}`;

        const lots = (buyer.reservations || [])
            .filter(r => r.status !== 'Cancelled' && r.lot)
            .map(r => `Blk ${r.lot.block} Lot ${r.lot.lot_number}`);
        const lotList = lots.length > 0 ? lots : [`Blk ${buyer.block_no || 'N/A'} Lot ${buyer.lot_no || 'N/A'}`];
        document.getElementById('finSelectedBuyerLots').innerHTML =
            `<span style="font-size: 12px; font-weight: 600; color: #15803d;">Lot: </span>` +
            lotList.map(l => `<span style="display: inline-block; padding: 2px 8px; font-size: 11px; font-weight: 700; background: #dcfce7; color: #166534; border-radius: 6px; margin-right: 4px;">${l}</span>`).join('');

        document.getElementById('finSelectedBuyerCard').style.display = 'flex';
        document.getElementById('finBuyerSearchContainer').style.display = 'none';
        document.getElementById('finBuyerSearchResults').style.display = 'none';
    }

    function clearFinSelectedBuyer(focusInput = true) {
        document.getElementById('finBuyerId').value = '';
        const input = document.getElementById('finBuyerSearchInput');
        if (input) input.value = '';
        const results = document.getElementById('finBuyerSearchResults');
        if (results) results.style.display = 'none';
        const card = document.getElementById('finSelectedBuyerCard');
        if (card) card.style.display = 'none';
        const container = document.getElementById('finBuyerSearchContainer');
        if (container) container.style.display = 'block';
        if (focusInput && input) input.focus();
    }

    document.addEventListener('click', function(e) {
        const container = document.getElementById('finBuyerSearchContainer');
        const results = document.getElementById('finBuyerSearchResults');
        if (container && results && !container.contains(e.target)) {
            results.style.display = 'none';
        }
    });

    function validateFinCreateForm(e) {
        if (!document.getElementById('finBuyerId').value) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Select Buyer',
                text: 'Please select a buyer from the Master List.'
            });
            return false;
        }
        return true;
    }

    function openNewFinancingModal() {
        clearFinSelectedBuyer(false);
        const modal = document.getElementById('newFinancingModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeNewFinancingModal() {
        const modal = document.getElementById('newFinancingModal');
        if (modal) modal.style.display = 'none';
    }
</script>

<!-- Create Financing Record Modal (matching Reservation & Downpayment popup UI) -->
<div id="newFinancingModal" class="bill-modal"
    style="display: none; align-items: center; justify-content: center; z-index: 3000;">
    <div class="bill-modal-content" style="max-width: 540px; width: 95%; padding: 32px; border-radius: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0;">Create Loan &amp; Additional Fees Record</h2>
                <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Select a buyer to initiate their loan tracking process.</p>
            </div>
            <button type="button" onclick="closeNewFinancingModal()"
                style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
        </div>

        <form action="{{ route('financing.store') }}" method="POST" onsubmit="return validateFinCreateForm(event)">
            @csrf
            <input type="hidden" name="buyer_id" id="finBuyerId" value="">

            <div id="finSelectedBuyerCard" style="display: none; background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 14px; padding: 14px 18px; margin-bottom: 16px; justify-content: space-between; align-items: center;">
                <div>
                    <div id="finSelectedBuyerName" style="font-size: 15px; font-weight: 800; color: #166534;"></div>
                    <div id="finSelectedBuyerMeta" style="font-size: 12px; color: #15803d; margin-top: 3px;"></div>
                    <div id="finSelectedBuyerLots" style="margin-top: 6px;"></div>
                </div>
                <button type="button" onclick="clearFinSelectedBuyer(true)"
                    style="background: #ffffff; border: 1px solid #86efac; color: #15803d; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s ease;">
                    Change Buyer
                </button>
            </div>

            <div id="finBuyerSearchContainer" style="margin-bottom: 20px; position: relative;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">
                    Select Buyer from Master List *
                </label>
                <div style="position: relative;">
                    <input type="text" id="finBuyerSearchInput" class="filter-select"
                        style="width: 100%; padding: 12px 14px 12px 38px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; box-sizing: border-box;"
                        placeholder="Search recent buyers by name, email, contact, or lot..."
                        oninput="filterFinBuyers(this.value)"
                        onfocus="filterFinBuyers(this.value)"
                        autocomplete="off">
                    <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; display: flex; align-items: center;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                </div>
                <div id="finBuyerSearchResults" style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: #fff; border: 1.5px solid #cbd5e1; border-radius: 12px; max-height: 220px; overflow-y: auto; z-index: 3100; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);">
                </div>
                <p style="font-size: 12px; color: #64748b; margin-top: 8px; margin-bottom: 0;">
                    Only buyers who already have a downpayment contract are listed.
                </p>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
                <button type="button" class="btn btn-outline" onclick="closeNewFinancingModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Generate Record</button>
            </div>
        </form>
    </div>
</div>
@endsection

