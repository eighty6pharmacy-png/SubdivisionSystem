@extends('layouts.admin')

@section('title', 'User Management')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">

<div class="bill-container fade-in">
    <div class="bill-header">
        <div class="bill-title">
            <h1>Identity & Access Management</h1>
            <p>Control system access for Residents, Security Personnel, and Finance Officers.</p>
        </div>
        <div class="bill-actions">
            <button class="btn btn-primary" onclick="openAddUserModal()">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px;"><path d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                Register New User
            </button>
        </div>
    </div>

    <!-- Stats Summary Row -->
    <div class="responsive-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 24px;">
        <div style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid var(--bill-primary);">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Total Active Users</div>
            <div id="statTotal" style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ collect($users)->where('status', 'Active')->count() }}</div>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid #10b981;">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Residents</div>
            <div id="statResidents" style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ collect($users)->where('role', 'Resident')->count() }}</div>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid #6366f1;">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Security Staff</div>
            <div id="statSecurity" style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ collect($users)->where('role', 'Security Guard')->count() }}</div>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid #f59e0b;">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Finance Team</div>
            <div id="statFinance" style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ collect($users)->where('role', 'Finance Officer')->count() }}</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar" style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 24px;">
        <div style="flex: 1; min-width: 250px;">
            <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px; display: block;">Search Directory</label>
            <input type="text" id="userSearch" class="filter-select" placeholder="Name, Email, or Employee ID..." style="width: 100%;" oninput="applyUserFilters()">
        </div>
        <div style="width: 200px;">
            <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px; display: block;">Filter by Role</label>
            <select id="roleFilter" class="filter-select" onchange="applyUserFilters()" style="width: 100%;">
                <option value="all">All Roles</option>
                <option value="Resident">Residents</option>
                <option value="Security Guard">Security Guard</option>
                <option value="Finance Officer">Finance Officer</option>
            </select>
        </div>
        <div style="width: 150px;">
            <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px; display: block;">Status</label>
            <select id="statusFilter" class="filter-select" onchange="applyUserFilters()" style="width: 100%;">
                <option value="all">All Status</option>
                <option value="Active">Active Only</option>
                <option value="Archived">Archived</option>
            </select>
        </div>
    </div>

    <!-- User Table -->
    <div class="analytic-card" style="padding: 0; overflow: hidden; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <div class="bill-table-container">
            <table class="bill-table" id="userTable">
                <thead>
                    <tr>
                        <th>Access ID</th>
                        <th>User Identity</th>
                        <th>Role & Scope</th>
                        <th>Account Details</th>
                        <th>Portal Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    @foreach($users as $user)
                    <tr class="user-row" data-name="{{ strtolower($user['name']) }}" data-email="{{ strtolower($user['email']) }}" data-role="{{ $user['role'] }}" data-status="{{ $user['status'] }}">
                        <td style="font-family: monospace; font-weight: 700; color: #64748b;">{{ $user['id'] }}</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-weight: 800; color: var(--bill-primary); font-size: 12px;">
                                    {{ substr($user['name'], 0, 1) }}
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: #0f172a;">{{ $user['name'] }}</div>
                                    <div style="font-size: 12px; color: #64748b;">{{ $user['email'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <span style="font-weight: 700; font-size: 12px; color: #0f172a;">{{ $user['role'] }}</span>
                                <span style="font-size: 11px; color: var(--bill-primary);">{{ $user['meta'] }}</span>
                            </div>
                        </td>
                        <td style="font-size: 12px; color: #64748b;">
                            Created: {{ date('M d, Y', strtotime($user['joined'])) }}
                        </td>
                        <td>
                            <span class="badge {{ $user['status'] === 'Active' ? 'badge-success' : 'badge-danger' }}" style="padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700;">
                                {{ strtoupper($user['status']) }}
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 6px;" onclick="openEditModal('{{ $user['id'] }}', '{{ $user['name'] }}', '{{ $user['email'] }}', '{{ $user['role'] }}', '{{ $user['meta'] }}')">Edit</button>
                                @if($user['status'] === 'Active')
                                    <button class="btn btn-outline archive-btn" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; color: #ef4444; border-color: #fee2e2;" onclick="archiveUser('{{ $user['id'] }}', this)">Archive</button>
                                @else
                                    <button class="btn btn-outline restore-btn" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; color: #10b981; border-color: #a7f3d0;" onclick="restoreUser('{{ $user['id'] }}', this)">Restore</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 2000; background: rgba(15,23,42,0.6); backdrop-filter: blur(8px);">
    <div class="bill-modal-content" style="max-width: 500px; width: 90%; padding: 32px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <div class="modal-header" style="padding: 0; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="font-size: 24px; font-weight: 800; color: #0f172a;">Register System User</h2>
                <p style="font-size: 13px; color: #64748b; margin-top: 4px;">Provision new access credentials for staff or residents.</p>
            </div>
            <button class="ann-btn-icon" onclick="closeAddUserModal()" style="background: none; border: none; cursor: pointer;">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="addUserForm" onsubmit="submitNewUser(event)" style="display: flex; flex-direction: column; gap: 16px;">
            <div>
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Select System Role</label>
                <select id="newRole" class="filter-select" required style="width: 100%; margin-top: 6px; padding: 12px; border-radius: 12px;" onchange="updateRoleFields()">
                    <option value="Resident">Resident (Homeowner Portal)</option>
                    <option value="Security Guard">Security Guard (Gate Operations)</option>
                    <option value="Finance Officer">Finance Officer (Billing/Payments)</option>
                </select>
            </div>

            <div>
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Full Legal Name</label>
                <input type="text" id="userName" class="filter-select" required style="width: 100%; margin-top: 6px; padding: 12px; border-radius: 12px;" placeholder="Enter full name">
            </div>

            <div>
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Email Address (Primary Login)</label>
                <input type="email" id="userEmail" class="filter-select" required style="width: 100%; margin-top: 6px; padding: 12px; border-radius: 12px;" placeholder="user@gmail.com">
            </div>

            <div id="roleSpecificFields">
                <div id="residentFields">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Block</label>
                            <input type="number" id="resBlock" class="filter-select" style="width: 100%; margin-top: 6px; padding: 12px;" placeholder="1">
                        </div>
                        <div>
                            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Lot</label>
                            <input type="number" id="resLot" class="filter-select" style="width: 100%; margin-top: 6px; padding: 12px;" placeholder="5">
                        </div>
                    </div>
                </div>
                <div id="staffFields" style="display: none;">
                    <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Employee/Badge ID</label>
                    <input type="text" id="staffId" class="filter-select" style="width: 100%; margin-top: 6px; padding: 12px;" placeholder="e.g. S-101">
                </div>
            </div>

            <div style="background: #f0fdf4; padding: 16px; border-radius: 12px; border: 1px solid #bbf7d0; margin-top: 8px;">
                <p style="font-size: 12px; color: #166534; line-height: 1.5; margin: 0;">
                    <strong>Admin Control:</strong> Once you finalize registration, the system will generate a secure initial password. You can then provide this credential to the user, or have it dispatched automatically to their Gmail.
                </p>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px; margin-top: 12px; font-weight: 700; border-radius: 12px;">
                Finalize Registration
            </button>
        </form>
    </div>
</div>

<!-- Manage/Edit User Modal -->
<div id="editUserModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 2000; background: rgba(15,23,42,0.6); backdrop-filter: blur(8px);">
    <div class="bill-modal-content" style="max-width: 500px; width: 90%; padding: 32px; border-radius: 24px;">
        <div class="modal-header" style="padding: 0; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="font-size: 24px; font-weight: 800; color: #0f172a;">Manage Account</h2>
                <p style="font-size: 13px; color: #64748b; margin-top: 4px;" id="editUserIdLabel">Updating USR-XXXX</p>
            </div>
            <button class="ann-btn-icon" onclick="closeEditModal()" style="background: none; border: none; cursor: pointer;">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="editUserForm" onsubmit="saveUserChanges(event)" style="display: flex; flex-direction: column; gap: 16px;">
            <input type="hidden" id="editUserId">
            <div>
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Full Name</label>
                <input type="text" id="editUserName" class="filter-select" required style="width: 100%; margin-top: 6px; padding: 12px; border-radius: 12px;">
            </div>

            <div>
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Email Address</label>
                <input type="email" id="editUserEmail" class="filter-select" required style="width: 100%; margin-top: 6px; padding: 12px; border-radius: 12px;">
            </div>

            <div>
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Role Info / Scope (Block/Lot or Badge ID)</label>
                <input type="text" id="editUserMeta" class="filter-select" required style="width: 100%; margin-top: 6px; padding: 12px; border-radius: 12px;">
            </div>

            <div>
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Update Password (Leave blank to keep current)</label>
                <div style="display: flex; gap: 8px; margin-top: 6px;">
                    <input type="text" id="editUserPassword" class="filter-select" style="flex: 1; padding: 12px; border-radius: 12px;" placeholder="Type new password...">
                    <button type="button" class="btn btn-outline" style="padding: 0 16px; border-radius: 12px; font-size: 11px; font-weight: 700;" onclick="generateRandomEditPass()">Auto-Gen</button>
                </div>
                <p style="font-size: 10px; color: #94a3b8; margin-top: 4px;">* Changing this will immediately invalidate the user's old password.</p>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 12px; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                <button type="button" class="btn btn-outline" style="flex: 1; justify-content: center; padding: 12px;" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center; padding: 12px;">Save Account Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function generateRandomEditPass() {
        const pass = 'AL' + Math.random().toString(36).slice(-5).toUpperCase() + '!';
        document.getElementById('editUserPassword').value = pass;
    }

    function openEditModal(id, name, email, role, meta) {
        document.getElementById('editUserId').value = id;
        document.getElementById('editUserIdLabel').innerText = `Updating ${id} (${role})`;
        document.getElementById('editUserName').value = name;
        document.getElementById('editUserEmail').value = email;
        document.getElementById('editUserMeta').value = meta;
        document.getElementById('editUserPassword').value = ''; // Clear password field
        document.getElementById('editUserModal').style.display = 'flex';
    }

    function closeEditModal() {
        document.getElementById('editUserModal').style.display = 'none';
    }

    function saveUserChanges(e) {
        e.preventDefault();
        const id = document.getElementById('editUserId').value;
        const name = document.getElementById('editUserName').value;
        const email = document.getElementById('editUserEmail').value;
        const newPass = document.getElementById('editUserPassword').value;
        
        // Find row and update UI
        const rows = document.querySelectorAll('.user-row');
        rows.forEach(row => {
            if (row.querySelector('td').innerText === id) {
                row.querySelector('div[style*="font-weight: 700; color: #0f172a;"]').innerText = name;
                row.querySelector('div[style*="font-size: 12px; color: #64748b;"]').innerText = email;
                row.dataset.name = name.toLowerCase();
                row.dataset.email = email.toLowerCase();
            }
        });

        let successMsg = `Account ${id} has been successfully updated.`;
        if (newPass) {
            successMsg += `\n\nPassword has been reset to: ${newPass}\nThe old password was invalidated and "flashed" out of the system.`;
        }

        alert(successMsg);
        closeEditModal();
    }
    function openAddUserModal() {
        document.getElementById('addUserModal').style.display = 'flex';
    }

    function closeAddUserModal() {
        document.getElementById('addUserModal').style.display = 'none';
        document.getElementById('addUserForm').reset();
    }

    function updateRoleFields() {
        const role = document.getElementById('newRole').value;
        const resFields = document.getElementById('residentFields');
        const staffFields = document.getElementById('staffFields');

        if (role === 'Resident') {
            resFields.style.display = 'block';
            staffFields.style.display = 'none';
        } else {
            resFields.style.display = 'none';
            staffFields.style.display = 'block';
        }
    }

    function applyUserFilters() {
        const search = document.getElementById('userSearch').value.toLowerCase();
        const role = document.getElementById('roleFilter').value;
        const status = document.getElementById('statusFilter').value;
        const rows = document.querySelectorAll('.user-row');

        rows.forEach(row => {
            const name = row.dataset.name;
            const email = row.dataset.email;
            const r = row.dataset.role;
            const s = row.dataset.status;

            const matchesSearch = name.includes(search) || email.includes(search);
            const matchesRole = role === 'all' || r === role;
            const matchesStatus = status === 'all' || s === status;

            row.style.display = (matchesSearch && matchesRole && matchesStatus) ? '' : 'none';
        });
    }

    function submitNewUser(e) {
        e.preventDefault();
        
        const name = document.getElementById('userName').value;
        const role = document.getElementById('newRole').value;
        const email = document.getElementById('userEmail').value;
        const id = 'USR-' + (1000 + Math.floor(Math.random() * 9000));
        
        // Software Engineer Logic: Generate a secure temporary password
        const tempPassword = 'Alth' + Math.random().toString(36).slice(-6).toUpperCase() + '!';
        
        let meta = "";
        if (role === 'Resident') {
            meta = `Block ${document.getElementById('resBlock').value}, Lot ${document.getElementById('resLot').value}`;
        } else {
            meta = `ID: ${document.getElementById('staffId').value}`;
        }

        const tbody = document.getElementById('userTableBody');
        const newRow = document.createElement('tr');
        newRow.className = 'user-row';
        newRow.dataset.name = name.toLowerCase();
        newRow.dataset.email = email.toLowerCase();
        newRow.dataset.role = role;
        newRow.dataset.status = 'Active';

        newRow.innerHTML = `
            <td style="font-family: monospace; font-weight: 700; color: #64748b;">${id}</td>
            <td>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-weight: 800; color: var(--bill-primary); font-size: 12px;">
                        ${name.charAt(0)}
                    </div>
                    <div>
                        <div style="font-weight: 700; color: #0f172a;">${name}</div>
                        <div style="font-size: 12px; color: #64748b;">${email}</div>
                    </div>
                </div>
            </td>
            <td>
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <span style="font-weight: 700; font-size: 12px; color: #0f172a;">${role}</span>
                    <span style="font-size: 11px; color: var(--bill-primary);">${meta}</span>
                </div>
            </td>
            <td style="font-size: 12px; color: #64748b;">
                Created: ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}
            </td>
            <td>
                <span class="badge badge-success" style="padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700;">ACTIVE</span>
            </td>
            <td>
                <div style="display: flex; gap: 8px;">
                    <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 6px;" onclick="openEditModal('${id}', '${name}', '${email}', '${role}', '${meta}')">Edit</button>
                    <button class="btn btn-outline archive-btn" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; color: #ef4444; border-color: #fee2e2;" onclick="archiveUser('${id}', this)">Archive</button>
                </div>
            </td>
        `;

        tbody.prepend(newRow);
        
        // Update Stats
        document.getElementById('statTotal').innerText = parseInt(document.getElementById('statTotal').innerText) + 1;
        if (role === 'Resident') document.getElementById('statResidents').innerText = parseInt(document.getElementById('statResidents').innerText) + 1;
        if (role === 'Security Guard') document.getElementById('statSecurity').innerText = parseInt(document.getElementById('statSecurity').innerText) + 1;
        if (role === 'Finance Officer') document.getElementById('statFinance').innerText = parseInt(document.getElementById('statFinance').innerText) + 1;

        if (window.pushSystemNotification) {
            window.pushSystemNotification("Account Provisioned", `Temporary password generated for ${name}`, new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}), true);
        }

        // Display Password to Admin
        const successMsg = `
            <div style="text-align: left;">
                <p style="font-weight: 700; color: #166534; margin-bottom: 12px;">Account Created Successfully!</p>
                <div style="background: #f8fafc; padding: 16px; border: 1px solid #e2e8f0; border-radius: 12px;">
                    <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Initial Password:</div>
                    <div style="font-family: monospace; font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 4px; letter-spacing: 2px;">${tempPassword}</div>
                </div>
                <p style="font-size: 12px; color: #64748b; margin-top: 12px;">Please copy this password and provide it to the resident or staff member. They will use this for their first login.</p>
            </div>
        `;
        
        // Custom Alert/Dialog for Password Visibility
        const overlay = document.createElement('div');
        overlay.style = "position:fixed; inset:0; background:rgba(15,23,42,0.8); backdrop-filter:blur(4px); display:flex; align-items:center; justify-content:center; z-index:3000;";
        overlay.innerHTML = `
            <div style="background:white; padding:40px; border-radius:24px; max-width:400px; width:90%; text-align:center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
                ${successMsg}
                <button onclick="this.parentElement.parentElement.remove()" class="btn btn-primary" style="width:100%; margin-top:24px; justify-content:center; padding:14px;">I have noted the password</button>
            </div>
        `;
        document.body.appendChild(overlay);

        closeAddUserModal();
    }

    function resetPassword(id) {
        alert(`A secure password reset link has been dispatched to the registered email for user ${id}.`);
    }

    function archiveUser(id, btnElement) {
        if (confirm(`Are you sure you want to archive user ${id}? Account status will be set to Archived.`)) {
            const rows = document.querySelectorAll('.user-row');
            rows.forEach(row => {
                if (row.querySelector('td').innerText === id) {
                    row.dataset.status = 'Archived';
                    const badge = row.querySelector('.badge');
                    badge.innerText = 'ARCHIVED';
                    badge.className = 'badge badge-danger';

                    // Switch button to Restore
                    const btn = btnElement || row.querySelector('.archive-btn');
                    if (btn) {
                        btn.innerText = 'Restore';
                        btn.style.color = '#10b981';
                        btn.style.borderColor = '#a7f3d0';
                        btn.onclick = function() { restoreUser(id, btn); };
                        btn.className = 'btn btn-outline restore-btn';
                    }
                }
            });
            const statTotal = document.getElementById('statTotal');
            if (statTotal) statTotal.innerText = Math.max(0, parseInt(statTotal.innerText) - 1);
        }
    }

    function restoreUser(id, btnElement) {
        if (confirm(`Are you sure you want to restore user ${id}? This will reactivate their access.`)) {
            const rows = document.querySelectorAll('.user-row');
            rows.forEach(row => {
                if (row.querySelector('td').innerText === id) {
                    row.dataset.status = 'Active';
                    const badge = row.querySelector('.badge');
                    badge.innerText = 'ACTIVE';
                    badge.className = 'badge badge-success';

                    // Switch button to Archive
                    const btn = btnElement || row.querySelector('.restore-btn');
                    if (btn) {
                        btn.innerText = 'Archive';
                        btn.style.color = '#ef4444';
                        btn.style.borderColor = '#fee2e2';
                        btn.onclick = function() { archiveUser(id, btn); };
                        btn.className = 'btn btn-outline archive-btn';
                    }
                }
            });
            const statTotal = document.getElementById('statTotal');
            if (statTotal) statTotal.innerText = parseInt(statTotal.innerText) + 1;
        }
    }
</script>

<style>
    .user-row { transition: background 0.2s ease; }
    .user-row:hover { background: #f8fafc; }
    .badge-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .badge-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
</style>
@endsection
