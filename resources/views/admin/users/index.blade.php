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
        <div class="bill-actions" style="display: flex; gap: 8px;">
            <button class="btn btn-primary" onclick="openAddUserModal()">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px;"><path d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                Register New User
            </button>
            <button class="btn btn-outline" style="background: #fff;" onclick="openImportModal()">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                Import CSV
            </button>
        </div>
    </div>

    <!-- Stats Summary Row -->
    <div class="responsive-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-bottom: 24px;">
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
        <div style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid #ec4899;">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Buyers</div>
            <div id="statBuyers" style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ collect($users)->where('role', 'Buyer')->count() }}</div>
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
                <option value="Admin">Admin</option>
                <option value="Buyer">Buyer</option>
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
        <div id="blockFilterContainer" style="width: 150px; display: none;">
            <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px; display: block;">Filter by Block</label>
            <select id="blockFilter" class="filter-select" onchange="applyUserFilters()" style="width: 100%;">
                <option value="all">All Blocks</option>
                <option value="1">Block 1</option>
                <option value="2">Block 2</option>
                <option value="3">Block 3</option>
                <option value="4">Block 4</option>
                <option value="5">Block 5</option>
                <option value="6">Block 6</option>
                <option value="7">Block 7</option>
                <option value="8">Block 8</option>
                <option value="9">Block 9</option>
                <option value="10">Block 10</option>
                <option value="11">Block 11</option>
                <option value="12">Block 12</option>
                <option value="13">Block 13</option>
                <option value="14">Block 14</option>
                <option value="15">Block 15</option>
                <option value="16">Block 16</option>
                <option value="17">Block 17</option>
                <option value="18">Block 18</option>
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
                    <tr class="user-row" 
                        data-dbid="{{ $user['db_id'] }}" 
                        data-name="{{ strtolower($user['name']) }}" 
                        data-email="{{ strtolower($user['email']) }}" 
                        data-role="{{ $user['role'] }}" 
                        data-status="{{ $user['status'] }}" 
                        data-block="{{ $user['block'] ?? '' }}"
                        data-lots='@json($user['lots'] ?? [])'
                        data-schedule-days='@json($user['schedule_days'] ?? [])'
                        data-shift-type="{{ $user['shift_type'] ?? 'day' }}"
                        data-shift-start="{{ $user['shift_start'] ?? '06:00' }}"
                        data-shift-end="{{ $user['shift_end'] ?? '18:00' }}"
                        data-is-on-duty="{{ !empty($user['is_on_duty']) ? '1' : '0' }}"
                        data-active-shift-label="{{ $user['active_shift_label'] ?? '' }}">
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
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span style="font-weight: 700; font-size: 12px; color: #0f172a;">{{ $user['role'] }}</span>
                                    @if($user['role'] === 'Security Guard')
                                        @if(!empty($user['is_on_duty']))
                                            <span style="font-size: 10px; font-weight: 700; background: #ecfdf5; color: #047857; padding: 2px 8px; border-radius: 9999px; border: 1px solid #a7f3d0; display: inline-flex; align-items: center; gap: 5px;">
                                                <span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981; display: inline-block;"></span>
                                                On Duty
                                            </span>
                                        @else
                                            <span style="font-size: 10px; font-weight: 600; background: #f8fafc; color: #64748b; padding: 2px 8px; border-radius: 9999px; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; gap: 5px;">
                                                <span style="width: 6px; height: 6px; border-radius: 50%; background: #94a3b8; display: inline-block;"></span>
                                                Off Duty
                                            </span>
                                        @endif
                                    @endif
                                </div>
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
                                <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 6px;" onclick="openViewModal('{{ $user['id'] }}', '{{ addslashes($user['name']) }}', '{{ $user['email'] }}', '{{ $user['role'] }}', '{{ addslashes($user['contact_number'] ?? 'N/A') }}', '{{ $user['block'] ?? '' }}', '{{ $user['lot'] ?? '' }}', '{{ date('M d, Y', strtotime($user['joined'])) }}', '{{ $user['status'] }}', '{{ $user['db_id'] }}')">View</button>
                                @if($user['status'] === 'Active')
                                    <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 6px;" onclick="openEditModal('{{ $user['id'] }}', '{{ $user['db_id'] ?? '' }}', '{{ addslashes($user['first_name'] ?? '') }}', '{{ addslashes($user['middle_name'] ?? '') }}', '{{ addslashes($user['last_name'] ?? '') }}', '{{ $user['email'] }}', '{{ $user['role'] }}', '{{ $user['contact_number'] ?? '' }}', '{{ $user['block'] ?? '' }}', '{{ $user['lot'] ?? '' }}')">Edit</button>
                                    <button class="btn btn-outline archive-btn" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; color: #ef4444; border-color: #fee2e2;" onclick="archiveUser('{{ $user['id'] }}', '{{ $user['db_id'] ?? '' }}', '{{ $user['role'] }}', this)">Archive</button>
                                @else
                                    <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; opacity: 0.5; cursor: not-allowed;" disabled>Edit</button>
                                    <button class="btn btn-outline restore-btn" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; color: #10b981; border-color: #a7f3d0;" onclick="restoreUser('{{ $user['id'] }}', '{{ $user['db_id'] ?? '' }}', this)">Restore</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('partials.admin-client-pagination', ['prefix' => 'userTable'])
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 2000; background: rgba(15,23,42,0.6); backdrop-filter: blur(8px); padding: 20px; overflow-y: auto;">
    <div class="bill-modal-content" style="max-width: 520px; width: 90%; max-height: 88vh; overflow-y: auto; padding: 28px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
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
                    <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Middle Name</label>
                    <input type="text" id="userMiddleName" class="filter-select" style="width: 100%; margin-top: 6px; padding: 12px; border-radius: 12px;" placeholder="Optional">
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
                    <input type="text" id="userContact" class="filter-select" required style="width: 100%; margin-top: 6px; padding: 12px; border-radius: 12px;" placeholder="Required">
                </div>
            </div>

            <div id="roleSpecificFields">
                <div id="residentFields">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Block</label>
                            <select id="resBlock" class="filter-select" style="width: 100%; margin-top: 6px; padding: 12px;"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option></select>
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
                <div id="guardScheduleFields" style="display: none; background: #f8fafc; padding: 18px; border-radius: 14px; border: 1px solid #e2e8f0;">
                    <div style="margin-bottom: 14px;">
                        <label style="font-size: 11px; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px;">Guard Duty Schedule</label>
                        <p style="font-size: 12px; color: #64748b; margin: 2px 0 0 0;">Select working days and customize the shift hours for each day.</p>
                    </div>

                    <!-- Working Days Selection -->
                    <div style="margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <label style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase;">Duty Days</label>
                            <div style="display: flex; gap: 6px;">
                                <button type="button" class="btn btn-outline" style="padding: 2px 8px; font-size: 11px; border-radius: 6px;" onclick="setGuardDays('add', 'weekday')">Mon - Fri</button>
                                <button type="button" class="btn btn-outline" style="padding: 2px 8px; font-size: 11px; border-radius: 6px;" onclick="setGuardDays('add', 'all')">Mon - Sun</button>
                                <button type="button" class="btn btn-outline" style="padding: 2px 8px; font-size: 11px; border-radius: 6px; color: #64748b;" onclick="setGuardDays('add', 'none')">Clear</button>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px;">
                            @foreach(['Monday' => 'Mon', 'Tuesday' => 'Tue', 'Wednesday' => 'Wed', 'Thursday' => 'Thu', 'Friday' => 'Fri', 'Saturday' => 'Sat', 'Sunday' => 'Sun'] as $day => $abbr)
                            <label style="display: flex; flex-direction: column; align-items: center; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 4px; cursor: pointer; user-select: none; transition: all 0.15s ease;" class="guard-day-pill" id="lbl_add_day_{{ $day }}">
                                <input type="checkbox" id="add_day_{{ $day }}" value="{{ $day }}" style="margin-bottom: 4px; cursor: pointer;" onchange="toggleGuardDayStyle(this, 'lbl_add_day_{{ $day }}', 'add', '{{ $day }}')">
                                <span style="font-size: 11px; font-weight: 700; color: #1e293b;">{{ $abbr }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Per-Day Shift Selection -->
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 6px;">
                            <label style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase;">Shift Time per Selected Day</label>
                            <div style="display: flex; gap: 6px;">
                                <button type="button" class="btn btn-outline" style="padding: 2px 8px; font-size: 10px; border-radius: 6px;" onclick="applyPresetToSelectedDays('add', 'day')">Set All Day</button>
                                <button type="button" class="btn btn-outline" style="padding: 2px 8px; font-size: 10px; border-radius: 6px;" onclick="applyPresetToSelectedDays('add', 'night')">Set All Night</button>
                            </div>
                        </div>
                        <div id="add_no_days_msg" style="display: none; font-size: 12px; color: #64748b; background: #fff; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 12px; text-align: center;">
                            Select one or more duty days above to configure shift hours.
                        </div>
                        <div id="addGuardDayShiftsList" style="display: flex; flex-direction: column; gap: 8px;">
                            @foreach(['Monday' => 'Mon', 'Tuesday' => 'Tue', 'Wednesday' => 'Wed', 'Thursday' => 'Thu', 'Friday' => 'Fri', 'Saturday' => 'Sat', 'Sunday' => 'Sun'] as $day => $abbr)
                            <div id="add_day_shift_row_{{ $day }}" style="display: none; align-items: center; justify-content: space-between; gap: 8px; background: #fff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 8px 10px; flex-wrap: wrap;">
                                <div style="min-width: 78px; font-size: 12px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 6px;">
                                    <span style="width: 7px; height: 7px; border-radius: 50%; background: #3b82f6; display: inline-block;"></span>
                                    {{ $day }}
                                </div>
                                <select id="add_shift_type_{{ $day }}" class="filter-select" style="flex: 1; min-width: 135px; padding: 6px 8px; font-size: 11px; border-radius: 8px;" onchange="handleDayShiftTypeChange('add', '{{ $day }}')">
                                    <option value="day">Day Shift (6AM - 6PM)</option>
                                    <option value="night">Night Shift (6PM - 6AM)</option>
                                    <option value="custom">Custom Hours</option>
                                </select>
                                <div style="display: flex; align-items: center; gap: 4px;">
                                    <input type="time" id="add_shift_start_{{ $day }}" class="filter-select" style="width: 98px; padding: 6px; font-size: 11px; border-radius: 8px;" value="06:00" onchange="handleDayTimeInputChange('add', '{{ $day }}')">
                                    <span style="font-size: 11px; color: #64748b; font-weight: 600;">to</span>
                                    <input type="time" id="add_shift_end_{{ $day }}" class="filter-select" style="width: 98px; padding: 6px; font-size: 11px; border-radius: 8px;" value="18:00" onchange="handleDayTimeInputChange('add', '{{ $day }}')">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
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
<div id="editUserModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 2000; background: rgba(15,23,42,0.6); backdrop-filter: blur(8px); padding: 20px; overflow-y: auto;">
    <div class="bill-modal-content" style="max-width: 560px; width: 92%; max-height: 88vh; overflow-y: auto; padding: 28px; border-radius: 24px;">
        <div class="modal-header" style="padding: 0; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="font-size: 24px; font-weight: 800; color: #0f172a;">Edit Account</h2>
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
                    <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Middle Name</label>
                    <input type="text" id="editUserMiddleName" class="filter-select" style="width: 100%; margin-top: 6px; padding: 12px; border-radius: 12px;" placeholder="Optional">
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
                    <input type="text" id="editUserContact" class="filter-select" required style="width: 100%; margin-top: 6px; padding: 12px; border-radius: 12px;" placeholder="Required">
                </div>
            </div>

            <div id="editResidentFields" style="display: flex; flex-direction: column; gap: 14px; background: #f8fafc; padding: 16px; border-radius: 14px; border: 1px solid #e2e8f0;">
                <div>
                    <label style="font-size: 11px; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px;">Assigned Lots / Houses</label>
                    <p style="font-size: 12px; color: #64748b; margin: 2px 0 8px 0;">This resident can own or occupy multiple lots. Manage their assigned properties below.</p>
                    <div id="editResidentLotsBadges" style="display: flex; flex-wrap: wrap; gap: 8px; min-height: 32px; align-items: center;">
                        <!-- Badges rendered via JS -->
                    </div>
                </div>

                <div style="border-top: 1px dashed #cbd5e1; padding-top: 12px;">
                    <label style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase;">Attach Another Lot</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 8px; margin-top: 6px; align-items: flex-end;">
                        <div>
                            <span style="font-size: 10px; color: #64748b; font-weight: 700;">BLOCK</span>
                            <select id="editResBlock" class="filter-select" style="width: 100%; padding: 8px; font-size: 12px;">
                                @for($i=1; $i<=18; $i++) <option value="{{$i}}">{{$i}}</option> @endfor
                            </select>
                        </div>
                        <div>
                            <span style="font-size: 10px; color: #64748b; font-weight: 700;">LOT</span>
                            <select id="editResLot" class="filter-select" style="width: 100%; padding: 8px; font-size: 12px;">
                                @for($i=1; $i<=15; $i++) <option value="{{$i}}">{{$i}}</option> @endfor
                            </select>
                        </div>
                        <div>
                            <button type="button" class="btn btn-outline" style="padding: 8px 14px; font-size: 12px; font-weight: 700; border-radius: 8px; color: #047857; border-color: #a7f3d0; background: #ecfdf5; white-space: nowrap;" onclick="addLotToEditResident()">
                                + Attach Lot
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="editGuardScheduleFields" style="display: none; background: #f8fafc; padding: 18px; border-radius: 14px; border: 1px solid #e2e8f0;">
                <div style="margin-bottom: 14px;">
                    <label style="font-size: 11px; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px;">Guard Duty Schedule</label>
                    <p style="font-size: 12px; color: #64748b; margin: 2px 0 0 0;">Select working days and customize the shift hours for each day.</p>
                </div>

                <!-- Working Days Selection -->
                <div style="margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase;">Duty Days</label>
                        <div style="display: flex; gap: 6px;">
                            <button type="button" class="btn btn-outline" style="padding: 2px 8px; font-size: 11px; border-radius: 6px;" onclick="setGuardDays('edit', 'weekday')">Mon - Fri</button>
                            <button type="button" class="btn btn-outline" style="padding: 2px 8px; font-size: 11px; border-radius: 6px;" onclick="setGuardDays('edit', 'all')">Mon - Sun</button>
                            <button type="button" class="btn btn-outline" style="padding: 2px 8px; font-size: 11px; border-radius: 6px; color: #64748b;" onclick="setGuardDays('edit', 'none')">Clear</button>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px;">
                        @foreach(['Monday' => 'Mon', 'Tuesday' => 'Tue', 'Wednesday' => 'Wed', 'Thursday' => 'Thu', 'Friday' => 'Fri', 'Saturday' => 'Sat', 'Sunday' => 'Sun'] as $day => $abbr)
                        <label style="display: flex; flex-direction: column; align-items: center; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 4px; cursor: pointer; user-select: none; transition: all 0.15s ease;" class="guard-day-pill" id="lbl_edit_day_{{ $day }}">
                            <input type="checkbox" id="edit_day_{{ $day }}" value="{{ $day }}" style="margin-bottom: 4px; cursor: pointer;" onchange="toggleGuardDayStyle(this, 'lbl_edit_day_{{ $day }}', 'edit', '{{ $day }}')">
                            <span style="font-size: 11px; font-weight: 700; color: #1e293b;">{{ $abbr }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <!-- Per-Day Shift Selection -->
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 6px;">
                        <label style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase;">Shift Time per Selected Day</label>
                        <div style="display: flex; gap: 6px;">
                            <button type="button" class="btn btn-outline" style="padding: 2px 8px; font-size: 10px; border-radius: 6px;" onclick="applyPresetToSelectedDays('edit', 'day')">Set All Day</button>
                            <button type="button" class="btn btn-outline" style="padding: 2px 8px; font-size: 10px; border-radius: 6px;" onclick="applyPresetToSelectedDays('edit', 'night')">Set All Night</button>
                        </div>
                    </div>
                    <div id="edit_no_days_msg" style="display: none; font-size: 12px; color: #64748b; background: #fff; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 12px; text-align: center;">
                        Select one or more duty days above to configure shift hours.
                    </div>
                    <div id="editGuardDayShiftsList" style="display: flex; flex-direction: column; gap: 8px;">
                        @foreach(['Monday' => 'Mon', 'Tuesday' => 'Tue', 'Wednesday' => 'Wed', 'Thursday' => 'Thu', 'Friday' => 'Fri', 'Saturday' => 'Sat', 'Sunday' => 'Sun'] as $day => $abbr)
                        <div id="edit_day_shift_row_{{ $day }}" style="display: none; align-items: center; justify-content: space-between; gap: 8px; background: #fff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 8px 10px; flex-wrap: wrap;">
                            <div style="min-width: 78px; font-size: 12px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 6px;">
                                <span style="width: 7px; height: 7px; border-radius: 50%; background: #3b82f6; display: inline-block;"></span>
                                {{ $day }}
                            </div>
                            <select id="edit_shift_type_{{ $day }}" class="filter-select" style="flex: 1; min-width: 135px; padding: 6px 8px; font-size: 11px; border-radius: 8px;" onchange="handleDayShiftTypeChange('edit', '{{ $day }}')">
                                <option value="day">Day Shift (6AM - 6PM)</option>
                                <option value="night">Night Shift (6PM - 6AM)</option>
                                <option value="custom">Custom Hours</option>
                            </select>
                            <div style="display: flex; align-items: center; gap: 4px;">
                                <input type="time" id="edit_shift_start_{{ $day }}" class="filter-select" style="width: 98px; padding: 6px; font-size: 11px; border-radius: 8px;" value="06:00" onchange="handleDayTimeInputChange('edit', '{{ $day }}')">
                                <span style="font-size: 11px; color: #64748b; font-weight: 600;">to</span>
                                <input type="time" id="edit_shift_end_{{ $day }}" class="filter-select" style="width: 98px; padding: 6px; font-size: 11px; border-radius: 8px;" value="18:00" onchange="handleDayTimeInputChange('edit', '{{ $day }}')">
                            </div>
                        </div>
                        @endforeach
                    </div>
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

<!-- View User Modal -->
<div id="viewUserModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 2000; background: rgba(15,23,42,0.6); backdrop-filter: blur(8px); padding: 20px; overflow-y: auto;">
    <div class="bill-modal-content" style="max-width: 480px; width: 90%; max-height: 88vh; overflow-y: auto; padding: 28px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <div class="modal-header" style="padding: 0; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="display: flex; gap: 16px; align-items: center;">
                <div id="viewUserInitials" style="width: 56px; height: 56px; border-radius: 16px; background: #e0e7ff; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #4338ca; font-size: 20px;">
                    J
                </div>
                <div>
                    <h2 id="viewUserName" style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0;">Name</h2>
                    <p id="viewUserRole" style="font-size: 13px; font-weight: 700; color: var(--bill-primary); margin-top: 2px;">Role</p>
                </div>
            </div>
            <button class="ann-btn-icon" onclick="closeViewModal()" style="background: none; border: none; cursor: pointer; color: #64748b;">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div style="display: flex; flex-direction: column; gap: 16px;">
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <div style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Account ID</div>
                        <div id="viewUserId" style="font-size: 13px; font-weight: 700; color: #334155; margin-top: 4px; font-family: monospace;">USR-0000</div>
                    </div>
                    <div>
                        <div style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Status</div>
                        <div id="viewUserStatus" style="font-size: 13px; font-weight: 700; color: #10b981; margin-top: 4px;">Active</div>
                    </div>
                </div>
            </div>

            <div style="background: #fff; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <div style="margin-bottom: 12px;">
                    <div style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Email Address</div>
                    <div id="viewUserEmail" style="font-size: 13px; font-weight: 600; color: #334155; margin-top: 4px;">email</div>
                </div>
                <div style="margin-bottom: 12px;">
                    <div style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Contact Number</div>
                    <div id="viewUserContact" style="font-size: 13px; font-weight: 600; color: #334155; margin-top: 4px;">N/A</div>
                </div>
                <div id="viewUserPropertyContainer" style="margin-bottom: 12px; display: none;">
                    <div style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Property Details</div>
                    <div id="viewUserProperty" style="font-size: 13px; font-weight: 600; color: #334155; margin-top: 4px;">Block X, Lot Y</div>
                </div>
                <div id="viewUserGuardContainer" style="margin-bottom: 12px; display: none;">
                    <div style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Guard Duty Schedule</div>
                    <div id="viewUserGuardSchedule" style="font-size: 13px; font-weight: 700; color: #1e40af; margin-top: 4px;">Schedule</div>
                    <div id="viewUserDutyStatus" style="font-size: 11px; margin-top: 4px;">Status</div>
                </div>
                <div>
                    <div style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Member Since</div>
                    <div id="viewUserJoined" style="font-size: 13px; font-weight: 600; color: #334155; margin-top: 4px;">Date</div>
                </div>
            </div>
        </div>
        <button class="btn btn-outline" style="width: 100%; justify-content: center; padding: 12px; margin-top: 24px; border-radius: 12px; font-weight: 700;" onclick="closeViewModal()">Close Profile</button>
    </div>
</div>

<!-- Import Users Modal -->
<div id="importUserModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 2000; background: rgba(15,23,42,0.6); backdrop-filter: blur(8px); padding: 20px; overflow-y: auto;">
    <div class="bill-modal-content" style="max-width: 520px; width: 90%; max-height: 88vh; overflow-y: auto; padding: 28px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <div class="modal-header" style="padding: 0; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h2 style="font-size: 24px; font-weight: 800; color: #0f172a; margin: 0;">Import Users from CSV</h2>
                <p style="font-size: 13px; font-weight: 400; color: #64748b; margin-top: 4px;">Upload a CSV to create or update residents, security guards, and finance officers. The same <strong>email</strong> updates that account.</p>
            </div>
            <button class="ann-btn-icon" onclick="closeImportModal()" style="background: none; border: none; cursor: pointer; color: #64748b;">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="importUserForm" onsubmit="submitImportUser(event)" style="display: flex; flex-direction: column; gap: 16px;" enctype="multipart/form-data">

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; font-size: 12px; color: #475569; line-height: 1.5;">
                <div style="font-weight: 700; color: #334155; margin-bottom: 6px;">Required columns</div>
                <code style="font-size: 11px;">first_name, middle_name, last_name, email, contact_number, role, block, lot</code>
                <div style="margin-top: 8px;"><strong>Residents:</strong> include <code>block</code> and <code>lot</code>. <code>middle_name</code> is optional.</div>
                <div><strong>Staff:</strong> <code>Security Guard</code> or <code>Finance Officer</code>. Leave block and lot blank.</div>
                <div><strong>Buyers</strong> are added from the buyer master list, not this file.</div>
                <a href="/admin/users/import/template" class="btn btn-outline" style="margin-top: 12px; width: 100%; justify-content: center; padding: 10px; border-radius: 10px; font-size: 12px; font-weight: 700; text-decoration: none;">Download CSV template</a>
            </div>

            <div>
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Select CSV File</label>
                <input type="file" id="importCsvFile" name="csv_file" accept=".csv" class="filter-select" required style="width: 100%; margin-top: 6px; padding: 12px; border-radius: 12px; background: #fff;">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px; margin-top: 12px; border-radius: 12px; font-weight: 700;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                Upload and Process
            </button>
        </form>
    </div>
</div>

<script src="{{ asset('js/gis-dropdowns.js') }}"></script>
<script>
    const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[A-Za-z]{2,}$/;
    const PH_MOBILE_PATTERN = /^(09\d{9}|\+639\d{9}|639\d{9})$/;

    function normalizePhone(raw) {
        return String(raw || '').replace(/[\s\-().]/g, '');
    }

    function validateUserEmailAndContact(emailRaw, contactRaw) {
        const email = emailRaw.trim();
        const contact = normalizePhone(contactRaw);
        if (!EMAIL_PATTERN.test(email)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid email',
                text: 'Please enter a real email address (any domain is fine, e.g. you@gmail.com or you@company.com).'
            });
            return null;
        }
        if (!PH_MOBILE_PATTERN.test(contact)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid mobile number',
                text: 'Use a Philippine mobile number: 09XXXXXXXXX or +639XXXXXXXXX.'
            });
            return null;
        }
        return { email, contact_number: contact };
    }

    function apiErrorMessage(errorData, fallback) {
        if (errorData?.message) return errorData.message;
        if (errorData?.errors) {
            const first = Object.values(errorData.errors).flat()[0];
            if (first) return first;
        }
        return fallback;
    }

    function generateRandomEditPass() {
        const pass = 'AL' + Math.random().toString(36).slice(-5).toUpperCase() + '!';
        document.getElementById('editUserPassword').value = pass;
    }

    const GUARD_DAYS_OF_WEEK = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    function formatTime12Hour(time24) {
        if (!time24) return '';
        const parts = String(time24).split(':');
        let h = parseInt(parts[0], 10);
        const m = parts[1] || '00';
        if (isNaN(h)) return time24;
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12;
        if (h === 0) h = 12;
        return `${String(h).padStart(2, '0')}:${m} ${ampm}`;
    }

    function handleDayShiftTypeChange(prefix, day) {
        const selectEl = document.getElementById(`${prefix}_shift_type_${day}`);
        const startEl = document.getElementById(`${prefix}_shift_start_${day}`);
        const endEl = document.getElementById(`${prefix}_shift_end_${day}`);
        if (!selectEl || !startEl || !endEl) return;

        if (selectEl.value === 'day') {
            startEl.value = '06:00';
            endEl.value = '18:00';
        } else if (selectEl.value === 'night') {
            startEl.value = '18:00';
            endEl.value = '06:00';
        }
    }

    function handleDayTimeInputChange(prefix, day) {
        const selectEl = document.getElementById(`${prefix}_shift_type_${day}`);
        const startEl = document.getElementById(`${prefix}_shift_start_${day}`);
        const endEl = document.getElementById(`${prefix}_shift_end_${day}`);
        if (!selectEl || !startEl || !endEl) return;

        const s = startEl.value.slice(0, 5);
        const e = endEl.value.slice(0, 5);
        if (s === '06:00' && e === '18:00') {
            selectEl.value = 'day';
        } else if (s === '18:00' && e === '06:00') {
            selectEl.value = 'night';
        } else {
            selectEl.value = 'custom';
        }
    }

    function applyPresetToSelectedDays(prefix, presetShift) {
        GUARD_DAYS_OF_WEEK.forEach(d => {
            const chk = document.getElementById(`${prefix}_day_${d}`);
            if (chk && chk.checked) {
                const selectEl = document.getElementById(`${prefix}_shift_type_${d}`);
                if (selectEl) {
                    selectEl.value = presetShift;
                    handleDayShiftTypeChange(prefix, d);
                }
            }
        });
    }

    function updateNoDaysMessage(prefix) {
        const anyChecked = GUARD_DAYS_OF_WEEK.some(d => {
            const chk = document.getElementById(`${prefix}_day_${d}`);
            return chk && chk.checked;
        });
        const msgEl = document.getElementById(`${prefix}_no_days_msg`);
        if (msgEl) {
            msgEl.style.display = anyChecked ? 'none' : 'block';
        }
    }

    function setGuardDays(prefix, mode) {
        GUARD_DAYS_OF_WEEK.forEach(d => {
            const chk = document.getElementById(`${prefix}_day_${d}`);
            if (!chk) return;
            if (mode === 'all') {
                chk.checked = true;
            } else if (mode === 'weekday') {
                chk.checked = (d !== 'Sunday' && d !== 'Saturday');
            } else if (mode === 'none') {
                chk.checked = false;
            }
            toggleGuardDayStyle(chk, `lbl_${prefix}_day_${d}`, prefix, d);
        });
    }

    function toggleGuardDayStyle(checkbox, labelId, prefix, day) {
        const lbl = document.getElementById(labelId);
        if (lbl) {
            if (checkbox.checked) {
                lbl.style.background = '#eff6ff';
                lbl.style.borderColor = '#3b82f6';
                lbl.style.color = '#1d4ed8';
            } else {
                lbl.style.background = '#fff';
                lbl.style.borderColor = '#cbd5e1';
                lbl.style.color = '#1e293b';
            }
        }

        const resolvedPrefix = prefix || (checkbox.id.startsWith('edit_') ? 'edit' : 'add');
        const resolvedDay = day || checkbox.value;
        const shiftRow = document.getElementById(`${resolvedPrefix}_day_shift_row_${resolvedDay}`);
        if (shiftRow) {
            shiftRow.style.display = checkbox.checked ? 'flex' : 'none';
        }
        updateNoDaysMessage(resolvedPrefix);
    }

    function normalizeGuardScheduleData(raw, fallbackType = 'day', fallbackStart = '06:00', fallbackEnd = '18:00') {
        const fbType = (fallbackType === 'night' || fallbackType === 'day') ? fallbackType : 'custom';
        const fbStart = String(fallbackStart || (fbType === 'night' ? '18:00' : '06:00')).slice(0, 5);
        const fbEnd = String(fallbackEnd || (fbType === 'night' ? '06:00' : '18:00')).slice(0, 5);

        const result = {};
        GUARD_DAYS_OF_WEEK.forEach(d => {
            result[d] = { enabled: false, shift: fbType, start: fbStart, end: fbEnd };
        });

        if (Array.isArray(raw)) {
            raw.forEach(d => {
                if (result[d]) {
                    result[d] = { enabled: true, shift: fbType, start: fbStart, end: fbEnd };
                }
            });
        } else if (typeof raw === 'object' && raw !== null) {
            Object.keys(raw).forEach(d => {
                if (!result[d]) return;
                const cfg = raw[d];
                if (!cfg || cfg === 'off') {
                    result[d].enabled = false;
                    return;
                }
                if (typeof cfg === 'string') {
                    if (cfg === 'day') {
                        result[d] = { enabled: true, shift: 'day', start: '06:00', end: '18:00' };
                    } else if (cfg === 'night') {
                        result[d] = { enabled: true, shift: 'night', start: '18:00', end: '06:00' };
                    } else if (cfg === '24_hours') {
                        result[d] = { enabled: true, shift: 'custom', start: '00:00', end: '23:59' };
                    }
                } else if (typeof cfg === 'object') {
                    if (cfg.off || cfg.shift === 'off') {
                        result[d].enabled = false;
                        return;
                    }
                    const sType = cfg.shift || 'day';
                    const defStart = sType === 'night' ? '18:00' : (sType === '24_hours' ? '00:00' : '06:00');
                    const defEnd = sType === 'night' ? '06:00' : (sType === '24_hours' ? '23:59' : '18:00');
                    const sStart = cfg.start ? String(cfg.start).slice(0, 5) : defStart;
                    const sEnd = cfg.end ? String(cfg.end).slice(0, 5) : defEnd;
                    let uiShift = 'custom';
                    if (sType === 'day' && sStart === '06:00' && sEnd === '18:00') {
                        uiShift = 'day';
                    } else if (sType === 'night' && sStart === '18:00' && sEnd === '06:00') {
                        uiShift = 'night';
                    }
                    result[d] = { enabled: true, shift: uiShift, start: sStart, end: sEnd };
                }
            });
        }

        return result;
    }

    function buildGuardSchedulePayload(prefix) {
        const scheduleDays = {};
        let firstShiftType = 'day';
        let firstShiftStart = '06:00:00';
        let firstShiftEnd = '18:00:00';
        let hasFirst = false;

        GUARD_DAYS_OF_WEEK.forEach(d => {
            const chk = document.getElementById(`${prefix}_day_${d}`);
            if (chk && chk.checked) {
                const sType = document.getElementById(`${prefix}_shift_type_${d}`)?.value || 'day';
                const sStartRaw = document.getElementById(`${prefix}_shift_start_${d}`)?.value || (sType === 'night' ? '18:00' : '06:00');
                const sEndRaw = document.getElementById(`${prefix}_shift_end_${d}`)?.value || (sType === 'night' ? '06:00' : '18:00');
                const sStart = sStartRaw.length === 5 ? `${sStartRaw}:00` : sStartRaw;
                const sEnd = sEndRaw.length === 5 ? `${sEndRaw}:00` : sEndRaw;

                scheduleDays[d] = {
                    shift: sType,
                    start: sStart,
                    end: sEnd
                };

                if (!hasFirst) {
                    firstShiftType = sType;
                    firstShiftStart = sStart;
                    firstShiftEnd = sEnd;
                    hasFirst = true;
                }
            }
        });

        return {
            schedule_days: scheduleDays,
            shift_type: firstShiftType,
            shift_start: firstShiftStart,
            shift_end: firstShiftEnd
        };
    }

    function openViewModal(id, name, email, role, contact, block, lot, joined, status, dbid) {
        if (new URLSearchParams(window.location.search).get('view') !== id) {
            window.history.pushState(null, '', '?view=' + id);
        }
        document.getElementById('viewUserInitials').innerText = name.charAt(0).toUpperCase();
        document.getElementById('viewUserName').innerText = name;
        document.getElementById('viewUserRole').innerText = role;
        document.getElementById('viewUserId').innerText = id;
        document.getElementById('viewUserStatus').innerText = status;
        document.getElementById('viewUserStatus').style.color = status === 'Active' ? '#10b981' : '#ef4444';
        
        document.getElementById('viewUserEmail').innerText = email;
        document.getElementById('viewUserContact').innerText = contact || 'N/A';
        document.getElementById('viewUserJoined').innerText = joined;

        const propContainer = document.getElementById('viewUserPropertyContainer');
        if (role === 'Resident') {
            const row = document.querySelector(`.user-row[data-dbid="${dbid}"]`);
            let userLots = [];
            if (row && row.dataset.lots) {
                try { userLots = JSON.parse(row.dataset.lots); } catch(e) {}
            }
            if (userLots.length > 0) {
                document.getElementById('viewUserProperty').innerText = userLots.map(l => `Block ${l.block}, Lot ${l.lot_number}`).join(' | ');
                propContainer.style.display = 'block';
            } else if (block && lot) {
                document.getElementById('viewUserProperty').innerText = `Block ${block}, Lot ${lot}`;
                propContainer.style.display = 'block';
            } else {
                propContainer.style.display = 'none';
            }
        } else {
            propContainer.style.display = 'none';
        }

        const guardContainer = document.getElementById('viewUserGuardContainer');
        if (guardContainer) {
            if (role === 'Security Guard') {
                const row = document.querySelector(`.user-row[data-dbid="${dbid}"]`);
                let scheduleHtml = '';
                let onDuty = false;
                if (row) {
                    try {
                        const raw = JSON.parse(row.dataset.scheduleDays || '[]');
                        onDuty = row.dataset.isOnDuty === '1';
                        const shiftType = row.dataset.shiftType || 'day';
                        const shiftStart = row.dataset.shiftStart || '06:00';
                        const shiftEnd = row.dataset.shiftEnd || '18:00';

                        const normalized = normalizeGuardScheduleData(raw, shiftType, shiftStart, shiftEnd);
                        const todayName = new Intl.DateTimeFormat('en-US', { weekday: 'long', timeZone: 'Asia/Manila' }).format(new Date());

                        scheduleHtml = '<div style="display: flex; flex-direction: column; gap: 6px; margin-top: 6px;">';
                        GUARD_DAYS_OF_WEEK.forEach(d => {
                            const cfg = normalized[d];
                            const isWorkDay = cfg && cfg.enabled;
                            const isToday = d === todayName;
                            const bg = isWorkDay ? '#eff6ff' : '#f8fafc';
                            const border = isWorkDay ? '#bfdbfe' : '#e2e8f0';
                            const todayStyle = isToday ? 'border: 2px solid #2563eb;' : `border: 1px solid ${border};`;
                            const shiftBadgeLabel = cfg.shift === 'night' ? 'Night Shift' : (cfg.shift === 'day' ? 'Day Shift' : 'Custom Shift');
                            const timeText = isWorkDay ? `${shiftBadgeLabel} (${formatTime12Hour(cfg.start)} - ${formatTime12Hour(cfg.end)})` : 'Off Duty';

                            scheduleHtml += `
                                <div style="background: ${bg}; ${todayStyle} border-radius: 8px; padding: 6px 10px; display: flex; justify-content: space-between; align-items: center;">
                                    <div style="font-size: 12px; font-weight: 700; color: #1e293b;">
                                        ${d} ${isToday ? '<span style="font-size: 10px; color: #2563eb; font-weight: 800; margin-left: 4px;">(Today)</span>' : ''}
                                    </div>
                                    <div style="font-size: 11px; font-weight: 700; color: ${isWorkDay ? '#1d4ed8' : '#94a3b8'};">
                                        ${timeText}
                                    </div>
                                </div>
                            `;
                        });
                        scheduleHtml += '</div>';
                    } catch(e) {
                        scheduleHtml = '<div style="font-size: 12px; color: #64748b;">No schedule assigned</div>';
                    }
                }
                document.getElementById('viewUserGuardSchedule').innerHTML = scheduleHtml;
                document.getElementById('viewUserDutyStatus').innerHTML = onDuty 
                    ? '<span style="display: inline-flex; align-items: center; gap: 5px; color: #047857; font-weight: 700; background: #ecfdf5; padding: 3px 10px; border-radius: 9999px; border: 1px solid #a7f3d0;"><span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981; display: inline-block;"></span> Active on Duty</span>' 
                    : '<span style="display: inline-flex; align-items: center; gap: 5px; color: #64748b; font-weight: 600; background: #f8fafc; padding: 3px 10px; border-radius: 9999px; border: 1px solid #e2e8f0;"><span style="width: 6px; height: 6px; border-radius: 50%; background: #94a3b8; display: inline-block;"></span> Currently Off Duty</span>';
                guardContainer.style.display = 'block';
            } else {
                guardContainer.style.display = 'none';
            }
        }

        document.getElementById('viewUserModal').style.display = 'flex';
    }

    function closeViewModal() {
        window.history.replaceState(null, '', window.location.pathname);
        document.getElementById('viewUserModal').style.display = 'none';
    }

    function openEditModal(id, dbid, firstName, middleName, lastName, email, role, contact, block, lot) {
        if (new URLSearchParams(window.location.search).get('edit') !== id) {
            window.history.pushState(null, '', '?edit=' + id);
        }
        document.getElementById('editUserId').value = id;
        document.getElementById('editUserDbId').value = dbid;
        document.getElementById('editUserIdLabel').innerText = `Updating ${id} (${role})`;
        document.getElementById('editUserModal').dataset.role = role;

        document.getElementById('editUserFirstName').value = firstName;
        document.getElementById('editUserMiddleName').value = middleName || '';
        document.getElementById('editUserLastName').value = lastName;
        
        document.getElementById('editUserEmail').value = email;
        document.getElementById('editUserContact').value = contact;
        
        const resFields = document.getElementById('editResidentFields');
        if (role === 'Resident') {
            resFields.style.display = 'flex';
            const row = document.querySelector(`.user-row[data-dbid="${dbid}"]`);
            let userLots = [];
            if (row && row.dataset.lots) {
                try { userLots = JSON.parse(row.dataset.lots); } catch(e) {}
            }
            if (userLots.length > 0) {
                currentEditResidentLots = userLots.map(l => ({ id: l.id, block: l.block, lot: l.lot_number, lot_number: l.lot_number }));
            } else if (block && lot) {
                currentEditResidentLots = [{ block: block, lot: lot, lot_number: lot }];
            } else {
                currentEditResidentLots = [];
            }
            renderEditResidentLotsBadges();
            bindGisDropdowns('editResBlock', 'editResLot', { currentBlock: block, currentLot: lot });
        } else {
            resFields.style.display = 'none';
        }

        const guardFields = document.getElementById('editGuardScheduleFields');
        if (guardFields) {
            if (role === 'Security Guard') {
                guardFields.style.display = 'block';
                const row = document.querySelector(`.user-row[data-dbid="${dbid}"]`);
                let raw = [];
                let shiftType = 'day';
                let shiftStart = '06:00';
                let shiftEnd = '18:00';
                if (row) {
                    try {
                        raw = JSON.parse(row.dataset.scheduleDays || '[]');
                        shiftType = row.dataset.shiftType || 'day';
                        shiftStart = row.dataset.shiftStart || '06:00';
                        shiftEnd = row.dataset.shiftEnd || '18:00';
                    } catch(e) {
                        raw = [];
                    }
                }
                const normalized = normalizeGuardScheduleData(raw, shiftType, shiftStart, shiftEnd);
                GUARD_DAYS_OF_WEEK.forEach(d => {
                    const cfg = normalized[d];
                    const chk = document.getElementById(`edit_day_${d}`);
                    const sSelect = document.getElementById(`edit_shift_type_${d}`);
                    const sStart = document.getElementById(`edit_shift_start_${d}`);
                    const sEnd = document.getElementById(`edit_shift_end_${d}`);

                    if (sSelect) sSelect.value = cfg.shift;
                    if (sStart) sStart.value = cfg.start;
                    if (sEnd) sEnd.value = cfg.end;
                    if (chk) {
                        chk.checked = cfg.enabled;
                        toggleGuardDayStyle(chk, `lbl_edit_day_${d}`, 'edit', d);
                    }
                });
            } else {
                guardFields.style.display = 'none';
            }
        }
        
        document.getElementById('editUserPassword').value = ''; // Clear password field
        document.getElementById('editUserModal').style.display = 'flex';
    }

    let currentEditResidentLots = [];

    function renderEditResidentLotsBadges() {
        const container = document.getElementById('editResidentLotsBadges');
        if (!container) return;
        if (currentEditResidentLots.length === 0) {
            container.innerHTML = '<span style="font-size: 12px; color: #dc2626; font-style: italic;">No lots currently assigned. Please attach at least one lot below.</span>';
            return;
        }
        container.innerHTML = currentEditResidentLots.map((l, idx) => `
            <span style="display: inline-flex; align-items: center; gap: 6px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 700;">
                Block ${l.block}, Lot ${l.lot_number || l.lot}
                <button type="button" onclick="removeLotFromEditResident(${idx})" style="background: none; border: none; color: #047857; cursor: pointer; padding: 0; font-size: 14px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; border-radius: 50%;" title="Remove this lot">&times;</button>
            </span>
        `).join('');
    }

    function addLotToEditResident() {
        const block = document.getElementById('editResBlock').value;
        const lot = document.getElementById('editResLot').value;
        if (!block || !lot) {
            Swal.fire({ icon: 'warning', text: 'Please select both Block and Lot.' });
            return;
        }
        const exists = currentEditResidentLots.some(l => String(l.block) === String(block) && String(l.lot_number || l.lot) === String(lot));
        if (exists) {
            Swal.fire({ icon: 'info', text: `Block ${block}, Lot ${lot} is already in the list.` });
            return;
        }
        currentEditResidentLots.push({ block: block, lot: lot, lot_number: lot });
        renderEditResidentLotsBadges();
    }

    function removeLotFromEditResident(idx) {
        if (currentEditResidentLots.length <= 1) {
            Swal.fire({ icon: 'warning', text: 'A Resident must have at least one assigned lot.' });
            return;
        }
        currentEditResidentLots.splice(idx, 1);
        renderEditResidentLotsBadges();
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
        const middleName = document.getElementById('editUserMiddleName').value.trim();
        const lastName = document.getElementById('editUserLastName').value.trim();
        const emailRaw = document.getElementById('editUserEmail').value;
        const contactRaw = document.getElementById('editUserContact').value;
        const validatedContact = validateUserEmailAndContact(emailRaw, contactRaw);
        if (!validatedContact) return;

        const newPass = document.getElementById('editUserPassword').value;
        const name = [firstName, middleName, lastName].filter(Boolean).join(' ');
        const payload = { first_name: firstName, middle_name: middleName, last_name: lastName, email: validatedContact.email, contact_number: validatedContact.contact_number, password: newPass };
        
        const resFields = document.getElementById('editResidentFields');
        if (resFields && resFields.style.display !== 'none') {
            if (currentEditResidentLots.length === 0) {
                Swal.fire('At least one Block and Lot is mandatory for Resident accounts.');
                return;
            }
            payload.lots_payload = currentEditResidentLots;
            payload.block = currentEditResidentLots[0].block;
            payload.lot = currentEditResidentLots[0].lot_number || currentEditResidentLots[0].lot;
        }

        const editRole = document.getElementById('editUserModal').dataset.role;
        if (editRole === 'Security Guard') {
            Object.assign(payload, buildGuardSchedulePayload('edit'));
        }
        
        try {
            Swal.fire({ title: 'Processing...', text: 'Saving changes...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
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
                const errorMsg = apiErrorMessage(errorData, 'Failed to update user.');
                Swal.fire(`Error: ${errorMsg}`);
                return;
            }
            
            const data = await res.json();
            if(data.success) {
                // Find row and update UI
                const rows = document.querySelectorAll('.user-row');
                rows.forEach(row => {
                    if (row.querySelector('td').innerText === id) {
                        row.querySelector('div[style*="font-weight: 700; color: #0f172a;"]').innerText = name;
                        row.querySelector('div[style*="font-size: 12px; color: #64748b;"]').innerText = validatedContact.email;
                        row.dataset.name = name.toLowerCase();
                        row.dataset.email = validatedContact.email.toLowerCase();
                    }
                });

                let successMsg = `Account ${id} has been successfully updated.`;
                if (newPass) {
                    successMsg += `\n\nPassword has been reset to: ${newPass}\nThe old password was invalidated.`;
                }

                Swal.fire(successMsg).then(() => window.location.reload());
            } else {
                Swal.fire('Failed to update user.');
            }
        } catch(e) {
            console.error(e);
            Swal.fire('An error occurred');
        }
    }
    function openAddUserModal() {
        if (new URLSearchParams(window.location.search).get('action') !== 'add') {
            window.history.pushState(null, '', '?action=add');
        }
        setGuardDays('add', 'weekday');
        applyPresetToSelectedDays('add', 'day');
        document.getElementById('addUserModal').style.display = 'flex';
    }

    function closeAddUserModal() {
        window.history.replaceState(null, '', window.location.pathname);
        document.getElementById('addUserModal').style.display = 'none';
        document.getElementById('addUserForm').reset();
    }

    window.addEventListener('DOMContentLoaded', () => {
        bindGisDropdowns('resBlock', 'resLot');

        window.userTablePagination = initTablePagination({
            rowSelector: '.user-row',
            infoEl: '#userTablePageInfo',
            prevEl: '#userTablePagePrev',
            nextEl: '#userTablePageNext',
            perPage: 15,
        });
        applyUserFilters();

        const params = new URLSearchParams(window.location.search);
        if (params.get('action') === 'add') {
            openAddUserModal();
        } else if (params.get('edit')) {
            const editBtn = document.querySelector(`button[onclick*="openEditModal('${params.get('edit')}'"]`);
            if (editBtn) editBtn.click();
        } else if (params.get('view')) {
            const viewBtn = document.querySelector(`button[onclick*="openViewModal('${params.get('view')}'"]`);
            if (viewBtn) viewBtn.click();
        }
    });

    function updateRoleFields() {
        const role = document.getElementById('newRole').value;
        const resFields = document.getElementById('residentFields');
        const staffFields = document.getElementById('staffFields');
        const guardFields = document.getElementById('guardScheduleFields');

        if (role === 'Resident') {
            resFields.style.display = 'block';
            staffFields.style.display = 'none';
            if (guardFields) guardFields.style.display = 'none';
        } else if (role === 'Security Guard') {
            resFields.style.display = 'none';
            staffFields.style.display = 'none';
            if (guardFields) {
                guardFields.style.display = 'block';
                setGuardDays('add', 'weekday');
                applyPresetToSelectedDays('add', 'day');
            }
        } else {
            resFields.style.display = 'none';
            staffFields.style.display = 'block';
            if (guardFields) guardFields.style.display = 'none';
        }
    }

    function applyUserFilters() {
        const search = document.getElementById('userSearch').value.toLowerCase();
        const role = document.getElementById('roleFilter').value;
        const status = document.getElementById('statusFilter').value;
        const block = document.getElementById('blockFilter').value;
        
        // Show/Hide block filter
        document.getElementById('blockFilterContainer').style.display = (role === 'Resident') ? 'block' : 'none';

        const rows = document.querySelectorAll('.user-row');

        rows.forEach(row => {
            const name = row.dataset.name;
            const email = row.dataset.email;
            const r = row.dataset.role;
            const s = row.dataset.status;
            const b = row.dataset.block;

            const matchesSearch = name.includes(search) || email.includes(search);
            const matchesRole = role === 'all' || r === role;
            const matchesStatus = status === 'all' || s === status;
            
            // Only apply block filter if role is Resident and a specific block is selected
            let matchesBlock = true;
            if (role === 'Resident' && block !== 'all') {
                matchesBlock = (b === block);
            }

            const matches = matchesSearch && matchesRole && matchesStatus && matchesBlock;
            row.classList.toggle('pagination-match', matches);
        });

        if (window.userTablePagination) {
            window.userTablePagination.resetPage();
        }
    }

    async function submitNewUser(e) {
        e.preventDefault();
        
        const firstName = document.getElementById('userFirstName').value.trim();
        const middleName = document.getElementById('userMiddleName').value.trim();
        const lastName = document.getElementById('userLastName').value.trim();
        const role = document.getElementById('newRole').value;
        const emailRaw = document.getElementById('userEmail').value;
        const contactRaw = document.getElementById('userContact').value;
        const validatedContact = validateUserEmailAndContact(emailRaw, contactRaw);
        if (!validatedContact) return;

        const payload = { first_name: firstName, middle_name: middleName, last_name: lastName, email: validatedContact.email, contact_number: validatedContact.contact_number, role, status: 'Active' };
        
        if (role === 'Resident') {
            payload.block = document.getElementById('resBlock').value;
            payload.lot = document.getElementById('resLot').value;
            if (!payload.block || !payload.lot) {
                Swal.fire('Block and Lot are mandatory for Resident accounts.');
                return;
            }
        } else if (role === 'Security Guard') {
            Object.assign(payload, buildGuardSchedulePayload('add'));
        }
        
        // Generate a secure temporary password
        const tempPassword = 'Alth' + Math.random().toString(36).slice(-6).toUpperCase() + '!';
        payload.password = tempPassword;
        
        try {
            Swal.fire({ title: 'Processing...', text: 'Creating account...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
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
                const errorMsg = apiErrorMessage(errorData, 'Failed to create account.');
                Swal.fire(`Error: ${errorMsg}`);
                return;
            }
            
            const data = await res.json();
            if(data.success) {
                Swal.fire(`Account created successfully!\n\nEmail: ${validatedContact.email}\nTemporary Password: ${tempPassword}\n\nPlease provide this password to the user.`).then(() => window.location.reload());
            } else {
                Swal.fire('Failed to create account. Email may already be in use.');
            }
        } catch(err) {
            console.error(err);
            Swal.fire('An error occurred.');
        }
    }

    function resetPassword(id) {
        Swal.fire(`A secure password reset link has been dispatched to the registered email for user ${id}.`);
    }

    function openImportModal() {
        document.getElementById('importUserModal').style.display = 'flex';
    }

    function closeImportModal() {
        document.getElementById('importUserModal').style.display = 'none';
        document.getElementById('importUserForm').reset();
    }

    async function submitImportUser(e) {
        e.preventDefault();
        
        const fileInput = document.getElementById('importCsvFile');
        if (!fileInput.files.length) {
            Swal.fire('Please select a CSV file.');
            return;
        }

        const formData = new FormData();
        formData.append('csv_file', fileInput.files[0]);

        try {
            Swal.fire({ title: 'Processing...', text: 'Reading CSV and importing users. This may take a moment.', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            
            const res = await fetch('/admin/users/import', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await res.json().catch(() => null);
            
            if (!res.ok) {
                const errorMsg = data && data.message ? data.message : 'Failed to import users.';
                Swal.fire(`Error: ${errorMsg}`);
                return;
            }
            
            if(data && data.success) {
                let msg = `Successfully processed ${data.processed} rows.\n\nCreated ${data.created} new users.`;
                if (typeof data.updated === 'number' && data.updated > 0) {
                    msg += `\nUpdated ${data.updated} existing users (matched by email).`;
                }
                if (data.errors && data.errors.length > 0) {
                    msg += `\n\nThere were ${data.errors.length} errors/skipped rows (e.g., duplicates or missing data).`;
                    console.warn('Import errors:', data.errors);
                }
                Swal.fire(msg).then(() => window.location.reload());
            } else {
                Swal.fire('Failed to import users.');
            }
        } catch(err) {
            console.error(err);
            Swal.fire('An error occurred during import.');
        }
    }

    function archiveUser(id, dbid, role, btnElement) {
        if (role === 'Admin') {
            Swal.fire({
                icon: 'error',
                title: 'Action Denied',
                text: 'Admin accounts cannot be archived to prevent system lockouts.'
            });
            return;
        }

        Swal.fire({
            title: 'Archive User?',
            text: `Are you sure you want to archive user ${id}? They will no longer be able to log in.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, Archive'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetch(`/admin/users/${dbid}/archive`, {
                        method: 'PUT',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    const data = await res.json();
                    if(data.success) {
                        Swal.fire('User archived successfully.').then(() => window.location.reload());
                    } else {
                        Swal.fire('Failed to archive user.');
                    }
                } catch(e) {
                    console.error(e);
                    Swal.fire('An error occurred.');
                }
            }
        });
    }

    function restoreUser(id, dbid, btnElement) {
        Swal.fire({
            title: 'Restore User?',
            text: `Are you sure you want to restore user ${id}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            confirmButtonText: 'Yes, Restore'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetch(`/admin/users/${dbid}/restore`, {
                        method: 'PUT',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    const data = await res.json();
                    if(data.success) {
                        Swal.fire('User restored successfully.').then(() => window.location.reload());
                    } else {
                        Swal.fire('Failed to restore user.');
                    }
                } catch(e) {
                    console.error(e);
                    Swal.fire('An error occurred.');
                }
            }
        });
    }
</script>

<style>
    /* Fix SweetAlert z-index being behind modals */
    .swal2-container {
        z-index: 99999 !important;
    }

    /* Modern clean scrollbar for scrollable modals */
    .bill-modal-content {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }
    .bill-modal-content::-webkit-scrollbar {
        width: 6px;
    }
    .bill-modal-content::-webkit-scrollbar-track {
        background: transparent;
    }
    .bill-modal-content::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 9999px;
    }
    .bill-modal-content::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    .guard-day-pill:hover {
        border-color: #93c5fd;
        background: #f8fafc;
    }
</style>

<link rel="stylesheet" href="{{ asset('css/views/admin-users.css') }}">
@endsection
