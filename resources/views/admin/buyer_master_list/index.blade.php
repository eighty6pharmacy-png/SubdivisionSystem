@extends('layouts.admin')

@section('title', 'Buyer Master List')

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
        }

        .btn-view {
            background-color: #eff6ff;
            color: #2563eb;
        }

        .btn-view:hover {
            background-color: #dbeafe;
            transform: translateY(-1px);
        }

        .btn-edit {
            background-color: #ecfdf5;
            color: #059669;
        }

        .btn-edit:hover {
            background-color: #d1fae5;
            transform: translateY(-1px);
        }

        .btn-delete {
            background-color: #fef2f2;
            color: #dc2626;
        }

        .btn-delete:hover {
            background-color: #fee2e2;
            transform: translateY(-1px);
        }
    </style>

    <div class="bill-container fade-in">
        <div class="bill-header">
            <div class="bill-title">
                <h1>Buyer Master List</h1>
                <p>Manage subdivision buyers, property lots, and their respective financing details.</p>
            </div>
            <div class="bill-actions">
                <button class="btn btn-outline" style="border-color: var(--bill-primary); color: var(--bill-primary);" onclick="openModal('add')">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                        style="margin-right: 8px;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Add New Buyer
                </button>
            </div>
        </div>

        <!-- Stats Summary Row -->
        <div class="responsive-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px;">
            <div
                style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid #3b82f6;">
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Total Buyers
                </div>
                <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ $totalBuyers ?? 0 }}</div>
            </div>
            <div
                style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid #ef4444;">
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Cancelled Buyers
                </div>
                <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ $cancelledBuyersCount ?? 0 }}</div>
            </div>
        </div>

        <!-- Master List Table -->
        <div class="analytic-card" style="padding: 0; overflow: hidden; border-radius: 16px; border: 1px solid #e2e8f0;">
            <div
                style="padding: 12px 24px; border-bottom: 1px solid var(--bill-border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc; flex-wrap: wrap; gap: 12px;">
                <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Sales Master List</h3>

                <form method="GET" action="{{ route('buyer-master-list.index') }}"
                    style="display: flex; gap: 8px; align-items: center; margin: 0;">
                    <input type="text" name="search" placeholder="Search by name..." value="{{ request('search') }}"
                        style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 200px;">

                    <select name="status" onchange="this.form.submit()"
                        style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: white;">
                        <option value="active" {{ request('status') !== 'cancelled' ? 'selected' : '' }}>Active Buyers</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled Buyers</option>
                    </select>

                    <select name="block" onchange="this.form.submit()"
                        style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: white;">
                        <option value="">All Blocks</option>
                        @for($i = 1; $i <= 18; $i++)
                            <option value="{{ $i }}" {{ request('block') == $i ? 'selected' : '' }}>Block {{ $i }}</option>
                        @endfor
                    </select>

                    @if(request('search') || request('block') || request('status'))
                        <a href="{{ route('buyer-master-list.index') }}"
                            style="padding: 6px 12px; background: #e2e8f0; color: #475569; border-radius: 6px; font-weight: 600; font-size: 13px; text-decoration: none;">Clear</a>
                    @endif
                </form>
            </div>
            <div class="bill-table-container" style="overflow-x: auto;">
                <table class="bill-table" style="min-width: 1500px;">
                    <thead>
                        <tr>
                            <th>First Name</th>
                            <th>Middle Name</th>
                            <th>Last Name</th>
                            <th>Block</th>
                            <th>Lot</th>
                            <th>Contract Amount</th>
                            <th>Equity</th>
                            <th>Reservation Date</th>
                            <th>Reservation Deadline</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($buyers as $buyer)
                            <tr>
                                <td style="font-weight: 600;">{{ $buyer->first_name }}</td>
                                <td style="font-weight: 600;">{{ $buyer->middle_name ?? '-' }}</td>
                                <td style="font-weight: 600;">{{ $buyer->last_name }}</td>
                                <td>{{ $buyer->block_no ?? '-' }}</td>
                                <td>{{ $buyer->lot_no ?? '-' }}</td>
                                <td style="font-family: monospace;">₱{{ number_format($buyer->contract_amount, 2) }}</td>
                                <td style="font-family: monospace;">₱{{ number_format($buyer->equity, 2) }}</td>
                                @php
                                    $reservation = $buyer->reservations->first();
                                @endphp
                                <td>{{ $reservation && $reservation->reservation_date ? \Carbon\Carbon::parse($reservation->reservation_date)->format('M d, Y') : '-' }}
                                </td>
                                <td
                                    style="color: {{ $reservation && $reservation->deadline_date && \Carbon\Carbon::parse($reservation->deadline_date)->isPast() ? '#dc2626' : '#059669' }}; font-weight: 600;">
                                    {{ $reservation && $reservation->deadline_date ? \Carbon\Carbon::parse($reservation->deadline_date)->format('M d, Y') : '-' }}
                                </td>
                                <td style="display: flex; gap: 8px;">
                                    <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 6px;" onclick="viewBuyer('{{ $buyer->id }}')">
                                        View
                                    </button>
                                    <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 6px;" onclick="editBuyer('{{ $buyer->id }}')">
                                        Edit
                                    </button>
                                    @php
                                        $hasUserAccount = \App\Models\User::where('buyer_master_list_id', $buyer->id)->exists();
                                    @endphp
                                    <form method="POST" action="{{ route('buyer.generate-account', $buyer->id) }}" id="generate-account-form-{{ $buyer->id }}" style="display:inline-block; margin: 0;">
                                        @csrf
                                        <button type="button" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; color: #854d0e; border-color: #eab308; background: transparent;" title="Generate Login Account" onclick="confirmGenerateAccount('{{ $buyer->id }}', '{{ $buyer->email }}', {{ $hasUserAccount ? 'true' : 'false' }})">
                                            Account
                                        </button>
                                    </form>
                                    <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 6px;" onclick="openExportModal('{{ $buyer->id }}')" title="Export Contract">
                                        Export
                                    </button>
                                    <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; color: #ef4444; border-color: #fee2e2;" onclick="deleteBuyer('{{ $buyer->id }}')">
                                        Archive
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" style="text-align: center; padding: 40px; color: #64748b;">
                                    No buyers in the Master List yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Buyer Modal -->
    <div id="buyerModal" class="modal-overlay"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div
            style="background: white; padding: 30px; border-radius: 16px; width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto;">
            <h2 id="modalTitle" style="margin-top: 0; margin-bottom: 24px; font-size: 20px;">Add New Buyer</h2>
            <form id="buyerForm" onsubmit="submitBuyerForm(event)">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <input type="hidden" id="buyerId" value="">

                <style>
                    .tab-btn {
                        padding: 8px 16px;
                        border: none;
                        background: transparent;
                        color: #64748b;
                        font-weight: 600;
                        cursor: pointer;
                        border-bottom: 2px solid transparent;
                        margin-bottom: -2px;
                    }

                    .tab-btn.active {
                        color: #3b82f6;
                        border-bottom: 2px solid #3b82f6;
                    }

                    .tab-content {
                        display: none;
                        min-height: 450px;
                    }

                    .tab-content.active {
                        display: block;
                    }
                </style>

                <!-- Tab Navigation -->
                <div style="display: flex; gap: 8px; border-bottom: 2px solid #e2e8f0; margin-bottom: 24px;">
                    <button type="button" class="tab-btn active" onclick="switchTab('tab-profile', this)">Profile &
                        Property</button>
                    <button type="button" class="tab-btn" onclick="switchTab('tab-finance', this)">Financing & Loan</button>
                    <button type="button" class="tab-btn" onclick="switchTab('tab-title', this)">Titling Documents</button>
                </div>

                <div id="tab-profile" class="tab-content active">
                    <h4 style="color: #3b82f6; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-top: 0;">
                        Personal Details</h4>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">First Name
                                *</label><input type="text" name="first_name" id="inp_first_name" required
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Middle
                                Name</label><input type="text" name="middle_name" id="inp_middle_name"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Last Name
                                *</label><input type="text" name="last_name" id="inp_last_name" required
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Civil
                                Status</label><input type="text" name="civil_status" id="inp_civil_status"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Spouse
                                Name</label><input type="text" name="spouse_name" id="inp_spouse_name"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Contact
                                Number</label><input type="text" name="contact_number" id="inp_contact_number"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Email Address</label><input type="email" name="email" id="inp_email"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                        <div style="grid-column: span 3;"><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Present
                                Address</label><input type="text" name="present_address" id="inp_present_address"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                        <div style="grid-column: span 3;"><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Postal
                                Address</label><input type="text" name="postal_address" id="inp_postal_address"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                        <div style="grid-column: span 2;"><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Proof of
                                Identification (ID Type/No)</label><input type="text" name="proof_of_id"
                                id="inp_proof_of_id"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Pag-IBIG
                                No.</label><input type="text" name="pagibig_number" id="inp_pagibig_number"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                    </div>

                    <h4 style="color: #3b82f6; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">Property Details</h4>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
                        <div>
                            <label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Block No.</label>
                            <select name="block_no" id="inp_block_no" class="view-readonly"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;">
                                @for($i = 1; $i <= 16; $i++)
                                <option value="{{$i}}">{{$i}}</option> @endfor
                            </select>
                        </div>
                        <div>
                            <label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Lot No.</label>
                            <select name="lot_no" id="inp_lot_no" class="view-readonly"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;">
                                @for($i = 1; $i <= 15; $i++)
                                <option value="{{$i}}">{{$i}}</option> @endfor
                            </select>
                        </div>
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Lot Area
                                (sqm)</label><input type="number" step="0.01" name="lot_area" id="inp_lot_area"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Floor Area
                                (sqm)</label><input type="number" step="0.01" name="floor_area" id="inp_floor_area"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                        <div style="grid-column: span 2;"><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Description</label><textarea
                                name="description" id="inp_description" rows="4"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px; resize:vertical; font-family:inherit;"
                                class="view-readonly"></textarea></div>
                    </div>
                </div>

                <div id="tab-finance" class="tab-content">
                    <h4 style="color: #3b82f6; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-top: 0;">
                        Contract Computations</h4>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Contract Amount
                                (₱)</label><input type="number" step="0.01" name="contract_amount" id="inp_contract_amount"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Equity
                                (₱)</label><input type="number" step="0.01" name="equity" id="inp_equity"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Improvement Amt
                                (₱)</label><input type="number" step="0.01" name="improvement_amount"
                                id="inp_improvement_amount"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label class="form-label"
                            style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Financing
                            Method</label>
                        <select name="financing_method" id="inp_financing_method" class="view-readonly"
                            style="padding:8px; border:1px solid #cbd5e1; border-radius:8px; width: 100%; max-width: 300px;"
                            onchange="toggleFinancingFields()">
                            <option value="">-- Select Method --</option>
                            <option value="Pag-IBIG">Pag-IBIG</option>
                            <option value="Bank Loan">Bank Loan</option>
                            <option value="Cash">Cash</option>
                        </select>
                    </div>

                    <div id="loanSection">
                        <h4 style="color: #3b82f6; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">Loan & Deductions
                            Details</h4>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
                            <div><label class="form-label"
                                    style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Loan Base
                                    (₱)</label><input type="number" step="0.01" name="loan_base" id="inp_loan_base"
                                    style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                    class="view-readonly"></div>
                            <div><label class="form-label"
                                    style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Loan Term
                                    (Years)</label><input type="number" name="loan_term" id="inp_loan_term" max="100"
                                    style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                    class="view-readonly"></div>
                            <div><label class="form-label"
                                    style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Monthly
                                    Amort. (₱)</label><input type="number" step="0.01" name="monthly_amortization"
                                    id="inp_monthly_amortization"
                                    style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                    class="view-readonly"></div>
                            <div><label class="form-label"
                                    style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">MRI/SRI
                                    (₱)</label><input type="number" step="0.01" name="mri_sri" id="inp_mri_sri"
                                    style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                    class="view-readonly"></div>
                            <div><label class="form-label"
                                    style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Insurance
                                    (₱)</label><input type="number" step="0.01" name="insurance" id="inp_insurance"
                                    style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                    class="view-readonly"></div>
                            <div><label class="form-label"
                                    style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">MRI DS 1-Time
                                    (₱)</label><input type="number" step="0.01" name="mri_ds_1time" id="inp_mri_ds_1time"
                                    style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                    class="view-readonly"></div>
                            <div><label class="form-label"
                                    style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Non-Life
                                    1-Time (₱)</label><input type="number" step="0.01" name="nonlife_1time"
                                    id="inp_nonlife_1time"
                                    style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                    class="view-readonly"></div>
                            <div><label class="form-label"
                                    style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Retention
                                    (₱)</label><input type="number" step="0.01" name="retention" id="inp_retention"
                                    style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                    class="view-readonly"></div>
                            <div><label class="form-label"
                                    style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Total
                                    Deductions (₱)</label><input type="number" step="0.01" name="total_deductions"
                                    id="inp_total_deductions"
                                    style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                    class="view-readonly"></div>
                        </div>
                    </div>
                </div>

                <div id="tab-title" class="tab-content">
                    <h4 style="color: #3b82f6; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-top: 0;">
                        Titling Documents</h4>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 30px;">
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">TCT
                                No.</label><input type="text" name="tct_no" id="inp_tct_no"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Tax Dec
                                No.</label><input type="text" name="tax_dec_no" id="inp_tax_dec_no"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">PID (Property
                                ID)</label><input type="text" name="pid" id="inp_pid"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                    </div>
                </div>

                <div
                    style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
                    <button type="button" onclick="closeModal()"
                        style="padding: 10px 16px; border: none; background: #f1f5f9; color: #475569; border-radius: 8px; cursor: pointer; font-weight: 600;">Close</button>
                    <button type="submit" id="saveButton"
                        style="padding: 10px 16px; border: none; background: #3b82f6; color: white; border-radius: 8px; cursor: pointer; font-weight: 600;">Save
                        Record</button>
                </div>
            </form>
        </div>
    </div>
    </div>

    <!-- Export Contract Modal -->
    <div id="exportModal" class="modal-overlay"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 16px; width: 90%; max-width: 400px;">
            <h2 style="margin-top: 0; margin-bottom: 20px; font-size: 20px;">Export Contract</h2>
            <p style="color: #64748b; font-size: 14px; margin-bottom: 24px;">Select the contract template you want to
                generate and download.</p>

            <form id="exportForm" method="GET" action="" target="_blank" onsubmit="setTimeout(closeExportModal, 500)">
                <div class="form-group" style="margin-bottom: 24px;">
                    <label
                        style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 13px; color: #334155;">Contract
                        Template</label>
                    <select id="exportType" class="form-input"
                        style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 16px;"
                        onchange="updateExportAction()">
                        <option value="msvs">Membership Status Verification Slip (MSVS)</option>
                        <option value="bvs">Borrower's Validation Sheet (BVS)</option>
                        <option value="housing_loan">Housing Loan Application</option>
                        <option value="buyer_conformity">Buyer Conformity</option>
                        <!-- More templates will be added here later -->
                    </select>


                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="closeExportModal()"
                        style="padding: 10px 16px; background: white; border: 1px solid #cbd5e1; border-radius: 8px; cursor: pointer; font-weight: 600; color: #475569;">Cancel</button>
                    <button type="submit"
                        style="padding: 10px 16px; background: #2563eb; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="7 10 12 15 17 10" />
                            <line x1="12" y1="15" x2="12" y2="3" />
                        </svg>
                        Download Contract
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentExportBuyerId = null;

        function openExportModal(buyerId) {
            currentExportBuyerId = buyerId;
            updateExportAction();
            document.getElementById('exportModal').style.display = 'flex';
        }

        function closeExportModal() {
            document.getElementById('exportModal').style.display = 'none';
        }

        function updateExportAction() {
            if (!currentExportBuyerId) return;
            const type = document.getElementById('exportType').value;
            const form = document.getElementById('exportForm');

            if (type === 'msvs') {
                form.action = `/admin/buyer-master-list/${currentExportBuyerId}/export-msvs`;
            } else if (type === 'bvs') {
                form.action = `/admin/buyer-master-list/${currentExportBuyerId}/export-bvs`;
            } else if (type === 'housing_loan') {
                form.action = `/admin/buyer-master-list/${currentExportBuyerId}/export-housing-loan`;
            } else if (type === 'buyer_conformity') {
                form.action = `/admin/buyer-master-list/${currentExportBuyerId}/export-buyer-conformity`;
            }
        }
    </script>

    <script src="{{ asset('js/gis-dropdowns.js') }}"></script>
    <script>
        const buyersData = @json($buyers);

        function switchTab(tabId, btnElement) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            btnElement.classList.add('active');
        }

        function toggleFinancingFields() {
            const method = document.getElementById('inp_financing_method').value;
            const loanSection = document.getElementById('loanSection');

            if (method === 'Cash' || method === '') {
                loanSection.style.display = 'none';
                // Clear out optional fields so they dont get submitted
                document.getElementById('inp_loan_base').value = '';
                document.getElementById('inp_loan_term').value = '';
                document.getElementById('inp_monthly_amortization').value = '';
                document.getElementById('inp_mri_sri').value = '';
                document.getElementById('inp_insurance').value = '';
                document.getElementById('inp_mri_ds_1time').value = '';
                document.getElementById('inp_nonlife_1time').value = '';
                document.getElementById('inp_retention').value = '';
                document.getElementById('inp_total_deductions').value = '';
            } else {
                loanSection.style.display = 'block';
            }
        }

        function openModal(mode, id = null) {
            const form = document.getElementById('buyerForm');
            form.reset();
            const readOnlyElements = document.querySelectorAll('.view-readonly');

            // Reset tabs
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById('tab-profile').classList.add('active');
            document.querySelector('.tab-btn').classList.add('active');

            toggleFinancingFields();

            if (mode === 'add') {
                document.getElementById('modalTitle').textContent = 'Add New Buyer';
                document.getElementById('formMethod').value = 'POST';
                document.getElementById('buyerId').value = '';
                document.getElementById('saveButton').style.display = 'block';
                readOnlyElements.forEach(el => el.removeAttribute('disabled'));
                readOnlyElements.forEach(el => el.removeAttribute('readonly'));

                bindGisDropdowns('inp_block_no', 'inp_lot_no');
            } else {
                const buyer = buyersData.find(b => b.id == id);
                if (!buyer) return;

                document.getElementById('buyerId').value = buyer.id;
                document.getElementById('inp_first_name').value = buyer.first_name || '';
                document.getElementById('inp_middle_name').value = buyer.middle_name || '';
                document.getElementById('inp_last_name').value = buyer.last_name || '';
                document.getElementById('inp_civil_status').value = buyer.civil_status || '';
                document.getElementById('inp_spouse_name').value = buyer.spouse_name || '';
                document.getElementById('inp_contact_number').value = buyer.contact_number || '';
                document.getElementById('inp_present_address').value = buyer.present_address || '';
                document.getElementById('inp_postal_address').value = buyer.postal_address || '';
                document.getElementById('inp_proof_of_id').value = buyer.proof_of_id || '';
                document.getElementById('inp_pagibig_number').value = buyer.pagibig_number || '';

                bindGisDropdowns('inp_block_no', 'inp_lot_no', { currentBlock: buyer.block_no, currentLot: buyer.lot_no });

                document.getElementById('inp_lot_area').value = buyer.lot_area || '';
                document.getElementById('inp_floor_area').value = buyer.floor_area || '';
                document.getElementById('inp_description').value = buyer.description || '';

                document.getElementById('inp_contract_amount').value = buyer.contract_amount || '';
                document.getElementById('inp_improvement_amount').value = buyer.improvement_amount || '';
                document.getElementById('inp_equity').value = buyer.equity || '';

                document.getElementById('inp_financing_method').value = buyer.financing_method || '';
                toggleFinancingFields(); // Show/hide based on saved DB value

                document.getElementById('inp_loan_base').value = buyer.loan_base || '';
                document.getElementById('inp_loan_term').value = buyer.loan_term || '';
                document.getElementById('inp_monthly_amortization').value = buyer.monthly_amortization || '';
                document.getElementById('inp_mri_sri').value = buyer.mri_sri || '';
                document.getElementById('inp_insurance').value = buyer.insurance || '';
                document.getElementById('inp_mri_ds_1time').value = buyer.mri_ds_1time || '';
                document.getElementById('inp_nonlife_1time').value = buyer.nonlife_1time || '';
                document.getElementById('inp_retention').value = buyer.retention || '';
                document.getElementById('inp_total_deductions').value = buyer.total_deductions || '';

                document.getElementById('inp_tct_no').value = buyer.tct_no || '';
                document.getElementById('inp_tax_dec_no').value = buyer.tax_dec_no || '';
                document.getElementById('inp_pid').value = buyer.pid || '';

                if (mode === 'view') {
                    document.getElementById('modalTitle').textContent = 'View Buyer Details';
                    document.getElementById('saveButton').style.display = 'none';
                    readOnlyElements.forEach(el => el.setAttribute('readonly', true));
                    document.getElementById('inp_block_no').setAttribute('disabled', true);
                    document.getElementById('inp_lot_no').setAttribute('disabled', true);
                    document.getElementById('inp_financing_method').setAttribute('disabled', true);
                } else if (mode === 'edit') {
                    document.getElementById('modalTitle').textContent = 'Edit Buyer Details';
                    document.getElementById('formMethod').value = 'PUT';
                    document.getElementById('saveButton').style.display = 'block';
                    document.getElementById('saveButton').textContent = 'Update Record';
                    readOnlyElements.forEach(el => el.removeAttribute('readonly'));
                    document.getElementById('inp_block_no').removeAttribute('disabled');
                    document.getElementById('inp_lot_no').removeAttribute('disabled');
                    document.getElementById('inp_financing_method').removeAttribute('disabled');
                }
            }

            document.getElementById('buyerModal').style.display = 'flex';
        }

        function viewBuyer(id) {
            openModal('view', id);
        }

        function editBuyer(id) {
            openModal('edit', id);
        }

        function closeModal() {
            document.getElementById('buyerModal').style.display = 'none';
        }

        async function submitBuyerForm(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            Object.keys(data).forEach(key => {
                if (data[key] === '') delete data[key];
            });

            const method = document.getElementById('formMethod').value;
            const id = document.getElementById('buyerId').value;
            const url = method === 'PUT' ? `/admin/buyer-master-list/${id}` : '/admin/buyer-master-list';

            const submitBtn = document.getElementById('saveButton');
            const originalBtnText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire(result.message);
                    window.location.reload();
                } else {
                    Swal.fire(result.message || 'Validation error. Please check your inputs.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                }
            } catch (err) {
                Swal.fire('An error occurred. Check input constraints.');
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            }
        }

        function deleteBuyer(id) {
            Swal.fire({
                title: 'Archive Buyer?',
                text: "Are you sure you want to archive this buyer? The buyer account and related reservations will also be archived.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, archive it!'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await fetch('/admin/buyer-master-list/' + id, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                            }
                        });
                        const resJson = await response.json();

                        if (resJson.success) {
                            Swal.fire('Archived successfully.', '', 'success').then(() => window.location.reload());
                        } else {
                            Swal.fire('Failed', resJson.message, 'error');
                        }
                    } catch (err) {
                        Swal.fire('Error', 'Error communicating with server.', 'error');
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            if (params.get('action') === 'add') {
                openModal('add');
                if (params.get('fname')) document.getElementById('inp_first_name').value = decodeURIComponent(params.get('fname'));
                if (params.get('mname')) document.getElementById('inp_middle_name').value = decodeURIComponent(params.get('mname'));
                if (params.get('lname')) document.getElementById('inp_last_name').value = decodeURIComponent(params.get('lname'));
                if (params.get('email')) document.getElementById('inp_email').value = decodeURIComponent(params.get('email'));
                if (params.get('contact')) document.getElementById('inp_contact_number').value = decodeURIComponent(params.get('contact'));
                
                // Clear URL to prevent re-triggering on refresh
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });

        function confirmGenerateAccount(id, defaultEmail, hasAccount) {
            if (hasAccount) {
                Swal.fire({
                    icon: 'info',
                    title: 'Account Exists',
                    text: 'This buyer already has an active system account. You can manage it in User Management.',
                });
                return;
            }

            Swal.fire({
                title: 'Generate Buyer Account',
                text: 'Enter the email address for this account. If they already have an email, it is pre-filled below.',
                input: 'email',
                inputValue: defaultEmail,
                showCancelButton: true,
                confirmButtonText: 'Generate Account',
                showLoaderOnConfirm: true,
                preConfirm: (inputEmail) => {
                    Swal.showLoading();
                    const form = document.getElementById('generate-account-form-' + id);
                    let emailInput = document.createElement('input');
                    emailInput.type = 'hidden';
                    emailInput.name = 'account_email';
                    emailInput.value = inputEmail;
                    form.appendChild(emailInput);
                    
                    form.submit();
                    // Return a promise that never resolves so the loading spinner stays until page refresh
                    return new Promise(() => {});
                }
            });
        }
    </script>
    @if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Account Generated Successfully!',
                text: '{!! addslashes(session('success')) !!}',
                allowOutsideClick: false,
                confirmButtonText: 'I have copied the details'
            });
        });
    </script>
    @endif
    @if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Cannot Generate Account',
                text: '{!! addslashes(session('error')) !!}',
            });
        });
    </script>
    @endif
@endsection