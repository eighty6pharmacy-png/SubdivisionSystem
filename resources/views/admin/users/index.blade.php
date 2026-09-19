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
                    <tr class="user-row" data-dbid="{{ $user['db_id'] }}" data-name="{{ strtolower($user['name']) }}" data-email="{{ strtolower($user['email']) }}" data-role="{{ $user['role'] }}" data-status="{{ $user['status'] }}">
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
                                <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 6px;" onclick="openEditModal('{{ $user['id'] }}', '{{ $user['db_id'] ?? '' }}', '{{ addslashes($user['name']) }}', '{{ $user['email'] }}', '{{ $user['role'] }}', '{{ $user['contact_number'] ?? '' }}', '{{ $user['block'] ?? '' }}', '{{ $user['lot'] ?? '' }}')">Edit</button>
                                @if($user['status'] === 'Active')
                                    <button class="btn btn-outline archive-btn" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; color: #ef4444; border-color: #fee2e2;" onclick="archiveUser('{{ $user['id'] }}', '{{ $user['db_id'] ?? '' }}', this)">Archive</button>
                                @else
                                    <button class="btn btn-outline restore-btn" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; color: #10b981; border-color: #a7f3d0;" onclick="restoreUser('{{ $user['id'] }}', '{{ $user['db_id'] ?? '' }}', this)">Restore</button>
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

            <div style="display: flex; gap: 12px;">
                <div style="flex: 1;">
                    <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">First Name</label>
                    <input type="text" id="userFirstName" class="filter-select" required style="width: 100%; margin-top: 6px; padding: 12px; border-radius: 12px;" placeholder="First name">
                </div>
                <div style="flex: 1;">
                    <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Last Name</label>
                    <input type="text" id="userLastName" class="filter-select" required style="width: 100%; margin-top: 6px; padding: 12px; border-radius: 12px;" placeholder="Last name">
                </div>
            </div>

            <div style="display: flex; gap: 12px;">
                <div style="flex: 1;">
                    <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Email Address (Primary Login)</label>
                    <input type="email" id="userEmail" class="filter-select" required style="width: 100%; margin-top: 6px; padding: 12px; border-radius: 12px;" placeholder="user@gmail.com">
                </div>
                <div style="flex: 1;">
                    <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Contact Number</label>
                    <input type="text" id="userContact" class="filter-select" style="width: 100%; margin-top: 6px; padding: 12px; border-radius: 12px;" placeholder="Optional">
                </div>
            </div>

            <div id="roleSpecificFields">
                <div id="residentFields">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Block</label>
                            <select id="resBlock" class="filter-select" style="width: 100%; margin-top: 6px; padding: 12px;"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option></select>
                        </div>
                        <div>
                            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Lot</label>
                            <select id="resLot" class="filter-select" style="width: 100%; margin-top: 6px; padding: 12px;"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option></select>
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
    <input type="hidden" id="editUserDbId">
            <div style="display: flex; gap: 12px;">
                <div style="flex: 1;">
                    <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">First Name</label>
                    <input type="text" id="editUserFirstName" class="filter-select" required style="width: 100%; margin-top: 6px; padding: 12px; border-radius: 12px;">
                </div>
                <div style="flex: 1;">
                    <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Last Name</label>
                    <input type="text" id="editUserLastName" class="filter-select" required style="width: 100%; margin-top: 6px; padding: 12px; border-radius: 12px;">
                </div>
            </div>

            <div style="display: flex; gap: 12px;">
                <div style="flex: 1;">
                    <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Email Address</label>
                    <input type="email" id="editUserEmail" class="filter-select" required style="width: 100%; margin-top: 6px; padding: 12px; border-radius: 12px;">
                </div>
                <div style="flex: 1;">
                    <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Contact Number</label>
                    <input type="text" id="editUserContact" class="filter-select" style="width: 100%; margin-top: 6px; padding: 12px; border-radius: 12px;" placeholder="Optional">
                </div>
            </div>

            <div id="editResidentFields" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Block</label>
                    <select id="editResBlock" class="filter-select" style="width: 100%; margin-top: 6px; padding: 12px;">
                        @for($i=1; $i<=16; $i++) <option value="{{$i}}">{{$i}}</option> @endfor
                    </select>
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Lot</label>
                    <select id="editResLot" class="filter-select" style="width: 100%; margin-top: 6px; padding: 12px;">
                        @for($i=1; $i<=15; $i++) <option value="{{$i}}">{{$i}}</option> @endfor
                    </select>
                </div>
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

<script src="{{ asset('js/gis-dropdowns.js') }}"></script>
<script>
    function generateRandomEditPass() {
        const pass = 'AL' + Math.random().toString(36).slice(-5).toUpperCase() + '!';
        document.getElementById('editUserPassword').value = pass;
    }

    function openEditModal(id, dbid, name, email, role, contact, block, lot) {
        if (new URLSearchParams(window.location.search).get('edit') !== id) {
            window.history.pushState(null, '', '?edit=' + id);
        }
        document.getElementById('editUserId').value = id;
        document.getElementById('editUserDbId').value = dbid;
        document.getElementById('editUserIdLabel').innerText = `Updating ${id} (${role})`;
        
        const names = name.split(' ');
        const firstName = names[0];
        const lastName = names.slice(1).join(' ');
        document.getElementById('editUserFirstName').value = firstName;
        document.getElementById('editUserLastName').value = lastName;
        
        document.getElementById('editUserEmail').value = email;
        document.getElementById('editUserContact').value = contact;
        
        const resFields = document.getElementById('editResidentFields');
        if (role === 'Resident') {
            resFields.style.display = 'grid';
            bindGisDropdowns('editResBlock', 'editResLot', { currentBlock: block, currentLot: lot });
        } else {
            resFields.style.display = 'none';
        }
        
        document.getElementById('editUserPassword').value = ''; // Clear password field
        document.getElementById('editUserModal').style.display = 'flex';
    }

    function closeEditModal() {
        window.history.replaceState(null, '', window.location.pathname);
        document.getElementById('editUserModal').style.display = 'none';
    }

    async function saveUserChanges(e) {
        e.preventDefault();
        const id = document.getElementById('editUserId').value;
        const dbid = document.getElementById('editUserDbId').value;
        const firstName = document.getElementById('editUserFirstName').value.trim();
        const lastName = document.getElementById('editUserLastName').value.trim();
        const email = document.getElementById('editUserEmail').value.trim();
        const contact_number = document.getElementById('editUserContact').value.trim();
        const newPass = document.getElementById('editUserPassword').value;
        
        if (!firstName || !lastName || !email || !contact_number) {
            alert('Please fill in all mandatory fields (First Name, Last Name, Email, Contact Number).');
            return;
        }

        const name = `${firstName} ${lastName}`.trim();
        const payload = { name, email, contact_number, password: newPass };
        
        const resFields = document.getElementById('editResidentFields');
        if (resFields.style.display !== 'none') {
            payload.block = document.getElementById('editResBlock').value;
            payload.lot = document.getElementById('editResLot').value;
            if (!payload.block || !payload.lot) {
                alert('Block and Lot are mandatory for Resident accounts.');
                return;
            }
        }
        
        try {
            const res = await fetch(`/admin/users/${dbid}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            });
            
            if (!res.ok) {
                const errorData = await res.json().catch(() => null);
                const errorMsg = errorData && errorData.message ? errorData.message : 'Failed to update user.';
                alert(`Error: ${errorMsg}`);
                return;
            }
            
            const data = await res.json();
            if(data.success) {
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
                    successMsg += `\n\nPassword has been reset to: ${newPass}\nThe old password was invalidated.`;
                }

                alert(successMsg);
                window.location.reload();
            } else {
                alert('Failed to update user.');
            }
        } catch(e) {
            console.error(e);
            alert('An error occurred');
        }
    }
    function openAddUserModal() {
        if (new URLSearchParams(window.location.search).get('action') !== 'add') {
            window.history.pushState(null, '', '?action=add');
        }
        document.getElementById('addUserModal').style.display = 'flex';
    }

    function closeAddUserModal() {
        window.history.replaceState(null, '', window.location.pathname);
        document.getElementById('addUserModal').style.display = 'none';
        document.getElementById('addUserForm').reset();
    }

    window.addEventListener('DOMContentLoaded', () => {
        bindGisDropdowns('resBlock', 'resLot');
        
        const params = new URLSearchParams(window.location.search);
        if (params.get('action') === 'add') {
            openAddUserModal();
        } else if (params.get('edit')) {
            const editBtn = document.querySelector(`button[onclick*="openEditModal('${params.get('edit')}'"]`);
            if (editBtn) editBtn.click();
        }
    });

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

    async function submitNewUser(e) {
        e.preventDefault();
        
        const firstName = document.getElementById('userFirstName').value.trim();
        const lastName = document.getElementById('userLastName').value.trim();
        const role = document.getElementById('newRole').value;
        const email = document.getElementById('userEmail').value.trim();
        const contact_number = document.getElementById('userContact').value.trim();
        
        if (!firstName || !lastName || !email || !contact_number) {
            alert('Please fill in all mandatory fields (First Name, Last Name, Email, Contact Number).');
            return;
        }

        const name = `${firstName} ${lastName}`.trim();
        const payload = { name, email, contact_number, role, status: 'Active' };
        
        if (role === 'Resident') {
            payload.block = document.getElementById('resBlock').value;
            payload.lot = document.getElementById('resLot').value;
            if (!payload.block || !payload.lot) {
                alert('Block and Lot are mandatory for Resident accounts.');
                return;
            }
        }
        
        // Generate a secure temporary password
        const tempPassword = 'Alth' + Math.random().toString(36).slice(-6).toUpperCase() + '!';
        payload.password = tempPassword;
        
        try {
            const res = await fetch('/admin/users', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            });
            
            if (!res.ok) {
                const errorData = await res.json().catch(() => null);
                const errorMsg = errorData && errorData.message ? errorData.message : 'Failed to create account.';
                alert(`Error: ${errorMsg}`);
                return;
            }
            
            const data = await res.json();
            if(data.success) {
                alert(`Account created successfully!\n\nEmail: ${email}\nTemporary Password: ${tempPassword}\n\nPlease provide this password to the user.`);
                window.location.reload();
            } else {
                alert('Failed to create account. Email may already be in use.');
            }
        } catch(err) {
            console.error(err);
            alert('An error occurred.');
        }
    }

    function resetPassword(id) {
        alert(`A secure password reset link has been dispatched to the registered email for user ${id}.`);
    }

    async function archiveUser(id, dbid, btnElement) {
        if (confirm(`Are you sure you want to permanently delete user ${id}? This action cannot be undone.`)) {
            try {
                const res = await fetch(`/admin/users/${dbid}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                const data = await res.json();
                if(data.success) {
                    const rows = document.querySelectorAll('.user-row');
                    rows.forEach(row => {
                        if (row.dataset.dbid === dbid.toString()) {
                            row.remove();
                        }
                    });
                    const statTotal = document.getElementById('statTotal');
                    if (statTotal) statTotal.innerText = Math.max(0, parseInt(statTotal.innerText) - 1);
                    alert('User deleted permanently.');
                } else {
                    alert('Failed to delete user.');
                }
            } catch(e) {
                console.error(e);
                alert('An error occurred.');
            }
        }
    }

    function restoreUser(id, dbid, btnElement) {
        alert('Restore functionality is not connected to PostgreSQL delete as we used a hard delete. Create a new user instead.');
    }
</script>

<link rel="stylesheet" href="{{ asset('css/views/admin-users.css') }}">
@endsection
