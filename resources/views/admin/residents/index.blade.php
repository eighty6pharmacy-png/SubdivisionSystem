@extends('layouts.admin')

@section('title', 'Resident Management')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">

<div class="bill-container fade-in">
    <div class="bill-header">
        <div class="bill-title">
            <h1>Resident Database</h1>
            <p>Manage subdivision homeowners, property assignments, and digital access credentials.</p>
        </div>
        <div class="bill-actions">
            <button class="btn btn-primary" onclick="openAddResModal()">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px;"><path d="M12 4v16m8-8H4"/></svg>
                Add New Resident
            </button>
        </div>
    </div>

    <!-- Stats Summary Row -->
    <div class="responsive-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px;">
        <div style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid var(--bill-primary);">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Total Residents</div>
            <div id="statTotal" style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ count($residents) }}</div>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid #10b981;">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Active Portals</div>
            <div id="statActive" style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ collect($residents)->where('status', 'Active')->count() }}</div>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid #f59e0b;">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Archived/Inactive</div>
            <div id="statArchived" style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ collect($residents)->where('status', 'Archived')->count() }}</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-group" style="flex: 1;">
            <span class="filter-label">Search Resident</span>
            <input type="text" id="resSearch" class="filter-select" placeholder="Name, Email, or ID..." style="width: 100%;" oninput="applyResFilters()">
        </div>
        <div class="filter-group">
            <span class="filter-label">Block Filter</span>
            <select id="blockFilter" class="filter-select" onchange="applyResFilters()">
                <option value="all">All Blocks</option>
                <option value="1">Block 1</option>
                <option value="2">Block 2</option>
                <option value="3">Block 3</option>
                <option value="4">Block 4</option>
                <option value="5">Block 5</option>
                <option value="6">Block 6</option>
                <option value="7">Block 7</option>
                <option value="8">Block 8</option>
            </select>
        </div>
        <div class="filter-group">
            <span class="filter-label">Status</span>
            <select id="statusFilter" class="filter-select" onchange="applyResFilters()">
                <option value="all">All Status</option>
                <option value="Active">Active</option>
                <option value="Archived">Archived</option>
            </select>
        </div>
    </div>

    <!-- Resident Table -->
    <div class="analytic-card" style="padding: 0; overflow: hidden;">
        <div class="bill-table-container">
            <table class="bill-table" id="resTable">
                <thead>
                    <tr>
                        <th>Resident ID</th>
                        <th>Name & Contact</th>
                        <th>Property Location</th>
                        <th>Joined Date</th>
                        <th>Portal Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="resTableBody">
                    @foreach($residents as $res)
                    <tr class="res-row" data-name="{{ strtolower($res['name']) }}" data-email="{{ strtolower($res['email']) }}" data-id="{{ strtolower($res['id']) }}" data-block="{{ $res['block'] }}" data-lot="{{ $res['lot'] }}" data-status="{{ $res['status'] }}">
                        <td style="font-family: monospace; font-weight: 700; color: #64748b;">{{ $res['id'] }}</td>
                        <td>
                            <div style="font-weight: 700; color: #0f172a;">{{ $res['name'] }}</div>
                            <div style="font-size: 12px; color: var(--bill-primary);">{{ $res['email'] }}</div>
                            <div style="font-size: 11px; color: #94a3b8;">{{ $res['contact'] }}</div>
                        </td>
                        <td>
                            <div style="background: #eff6ff; color: #1e40af; padding: 4px 10px; border-radius: 6px; display: inline-block; font-weight: 700; font-size: 12px;">
                                Block {{ $res['block'] }} Lot {{ $res['lot'] }}
                            </div>
                        </td>
                        <td style="font-size: 13px; color: #64748b;">{{ date('M d, Y', strtotime($res['joined'])) }}</td>
                        <td>
                            <span class="badge {{ $res['status'] === 'Active' ? 'badge-success' : 'badge-danger' }}" style="padding: 4px 12px; border-radius: 20px;">
                                {{ $res['status'] }}
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px;" onclick="editResident('{{ $res['id'] }}')">Edit</button>
                                <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; color: var(--bill-danger); border-color: #fee2e2;" onclick="archiveResident('{{ $res['id'] }}')">Archive</button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Resident Modal -->
<div id="addResModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 2000; background: rgba(15,23,42,0.6); backdrop-filter: blur(8px);">
    <div class="bill-modal-content" style="max-width: 500px; width: 90%; padding: 32px; border-radius: 24px;">
        <div class="modal-header" style="padding: 0; margin-bottom: 24px;">
            <h2 style="font-size: 24px; font-weight: 800; color: #0f172a;">Add New Resident</h2>
            <button class="ann-btn-icon" onclick="closeAddResModal()">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="addResForm" onsubmit="submitNewResident(event)" style="display: flex; flex-direction: column; gap: 16px;">
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Full Name</label>
                <input type="text" id="resName" class="filter-select" required style="width: 100%; margin-top: 4px;" placeholder="e.g. Juan Dela Cruz">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Block</label>
                    <input type="number" id="resBlock" class="filter-select" required style="width: 100%; margin-top: 4px;" min="1">
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Lot</label>
                    <input type="number" id="resLot" class="filter-select" required style="width: 100%; margin-top: 4px;" min="1">
                </div>
            </div>

            <div>
                <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Contact Number</label>
                <input type="text" id="resContact" class="filter-select" required style="width: 100%; margin-top: 4px;" placeholder="09XX-XXX-XXXX">
            </div>

            <div>
                <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Email Address (For Credentials)</label>
                <input type="email" id="resEmail" class="filter-select" required style="width: 100%; margin-top: 4px;" placeholder="resident@email.com">
            </div>

            <div style="background: #f0fdf4; padding: 16px; border-radius: 12px; border: 1px solid #bbf7d0; margin-top: 8px;">
                <p style="font-size: 12px; color: #166534; line-height: 1.5; margin: 0;">
                    <strong>Auto-Credentialing:</strong> Once saved, the system will automatically generate a secure portal password and dispatch it to the email provided above.
                </p>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px; margin-top: 12px; font-weight: 700;">
                Create Account & Dispatch Credentials
            </button>
        </form>
    </div>
</div>

<script>
    function openAddResModal() {
        if (new URLSearchParams(window.location.search).get('action') !== 'add') {
            window.history.pushState(null, '', '?action=add');
        }
        document.getElementById('addResModal').style.display = 'flex';
    }

    function closeAddResModal() {
        window.history.replaceState(null, '', window.location.pathname);
        document.getElementById('addResModal').style.display = 'none';
        document.getElementById('addResForm').reset();
    }

    function applyResFilters() {
        const search = document.getElementById('resSearch').value.toLowerCase();
        const block = document.getElementById('blockFilter').value;
        const status = document.getElementById('statusFilter').value;
        const rows = document.querySelectorAll('.res-row');

        rows.forEach(row => {
            const name = row.dataset.name;
            const email = row.dataset.email;
            const id = row.dataset.id;
            const b = row.dataset.block;
            const s = row.dataset.status;

            const matchesSearch = name.includes(search) || email.includes(search) || id.includes(search);
            const matchesBlock = block === 'all' || b === block;
            const matchesStatus = status === 'all' || s === status;

            row.style.display = (matchesSearch && matchesBlock && matchesStatus) ? '' : 'none';
        });
    }

    function submitNewResident(e) {
        e.preventDefault();
        
        const name = document.getElementById('resName').value;
        const block = document.getElementById('resBlock').value;
        const lot = document.getElementById('resLot').value;
        const contact = document.getElementById('resContact').value;
        const email = document.getElementById('resEmail').value;
        const id = 'RES-' + (2000 + Math.floor(Math.random() * 9000));
        const joined = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });

        // Add to table
        const tbody = document.getElementById('resTableBody');
        const newRow = document.createElement('tr');
        newRow.className = 'res-row';
        newRow.dataset.name = name.toLowerCase();
        newRow.dataset.email = email.toLowerCase();
        newRow.dataset.id = id.toLowerCase();
        newRow.dataset.block = block;
        newRow.dataset.lot = lot;
        newRow.dataset.status = 'Active';

        newRow.innerHTML = `
            <td style="font-family: monospace; font-weight: 700; color: #64748b;">${id}</td>
            <td>
                <div style="font-weight: 700; color: #0f172a;">${name}</div>
                <div style="font-size: 12px; color: var(--bill-primary);">${email}</div>
                <div style="font-size: 11px; color: #94a3b8;">${contact}</div>
            </td>
            <td>
                <div style="background: #eff6ff; color: #1e40af; padding: 4px 10px; border-radius: 6px; display: inline-block; font-weight: 700; font-size: 12px;">
                    Block ${block} Lot ${lot}
                </div>
            </td>
            <td style="font-size: 13px; color: #64748b;">${joined}</td>
            <td>
                <span class="badge badge-success" style="padding: 4px 12px; border-radius: 20px;">Active</span>
            </td>
            <td>
                <div style="display: flex; gap: 8px;">
                    <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px;" onclick="editResident('${id}')">Edit</button>
                    <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; color: var(--bill-danger); border-color: #fee2e2;" onclick="archiveResident('${id}')">Archive</button>
                </div>
            </td>
        `;

        tbody.prepend(newRow);
        
        // Notification
        if (window.pushSystemNotification) {
            window.pushSystemNotification("Account Created", `Credentials dispatched to ${email}`, new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}), true);
        }

        alert(`Digital Account Created for ${name}.\nInitial credentials have been dispatched to ${email}.\n\nNote: In a production environment, an automated SMTP/Mailgun service would handle the secure password delivery.`);
        
        // Update stats
        document.getElementById('statTotal').textContent = parseInt(document.getElementById('statTotal').textContent) + 1;
        document.getElementById('statActive').textContent = parseInt(document.getElementById('statActive').textContent) + 1;

        closeAddResModal();
    }

    function archiveResident(id) {
        if (confirm(`Are you sure you want to archive resident ${id}? This will prevent them from logging into the portal.`)) {
            const rows = document.querySelectorAll('.res-row');
            rows.forEach(row => {
                if (row.dataset.id === id.toLowerCase()) {
                    row.dataset.status = 'Archived';
                    const badge = row.querySelector('.badge');
                    badge.textContent = 'Archived';
                    badge.className = 'badge badge-danger';
                }
            });
            document.getElementById('statActive').textContent = parseInt(document.getElementById('statActive').textContent) - 1;
            document.getElementById('statArchived').textContent = parseInt(document.getElementById('statArchived').textContent) + 1;
        }
    }

    function editResident(id) {
        alert(`Edit feature for ${id} will be wired to the profile patcher module.`);
    }

    // Close on escape
    window.onclick = function(event) {
        if (event.target == document.getElementById('addResModal')) closeAddResModal();
    }
    window.addEventListener('DOMContentLoaded', () => {
        if (new URLSearchParams(window.location.search).get('action') === 'add') {
            openAddResModal();
        }
    });
</script>

<link rel="stylesheet" href="{{ asset('css/views/admin-residents.css') }}">
@endsection
