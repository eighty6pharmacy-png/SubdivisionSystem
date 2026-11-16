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

        .btn-address-helper {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
            color: #0369a1;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            line-height: 1.4;
            box-shadow: 0 1px 2px rgba(2, 132, 199, 0.06);
        }

        .btn-address-helper:hover {
            background: #e0f2fe;
            border-color: #7dd3fc;
            color: #0284c7;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(2, 132, 199, 0.15);
        }

        .btn-address-copy {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            line-height: 1.4;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }

        .btn-address-copy:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
            color: #1e293b;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
        }
    </style>

    <div class="bill-container fade-in">
        <div class="bill-header">
            <div class="bill-title">
                <h1>Buyer Master List</h1>
                <p>Manage subdivision buyers, property lots, and their respective financing details.</p>
            </div>
            <div class="bill-actions" style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-outline" style="background: #fff; border-color: #0284c7; color: #0284c7; font-weight: 700; display: inline-flex; align-items: center;" onclick="openBuyerReportModal()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Generate Report
                </button>
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
            @php
                $curSort = request('sort_by', 'created_at');
                $curDir = strtolower(request('sort_dir', 'desc'));
                $getSortUrl = function($col) use ($curSort, $curDir) {
                    $nextDir = ($curSort === $col && $curDir === 'asc') ? 'desc' : 'asc';
                    $params = request()->query();
                    $params['sort_by'] = $col;
                    $params['sort_dir'] = $nextDir;
                    $params['page'] = 1;
                    return route('buyer-master-list.index', $params);
                };
                $getSortIcon = function($col) use ($curSort, $curDir) {
                    if ($curSort !== $col) return '<span style="opacity:0.35;margin-left:5px;font-size:11px;">⇅</span>';
                    return $curDir === 'asc' 
                        ? '<span style="margin-left:5px;font-size:11px;color:#0284c7;font-weight:bold;">▲</span>' 
                        : '<span style="margin-left:5px;font-size:11px;color:#0284c7;font-weight:bold;">▼</span>';
                };
            @endphp
            <div class="bill-table-container" style="overflow-x: auto;">
                <table class="bill-table" style="min-width: 1240px;">
                    <thead>
                        <tr>
                            <th><a href="{{ $getSortUrl('first_name') }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center;" title="Sort by First Name">First Name {!! $getSortIcon('first_name') !!}</a></th>
                            <th>Middle Name</th>
                            <th><a href="{{ $getSortUrl('last_name') }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center;" title="Sort by Last Name">Last Name {!! $getSortIcon('last_name') !!}</a></th>
                            <th>Reserved Lot(s)</th>
                            <th>Contract Amount</th>
                            <th>Equity</th>
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
                                <td>
                                    @php
                                        $activeRes = $buyer->reservations->filter(fn ($r) => $r->status !== \App\Enums\ReservationStatus::Cancelled->value);
                                    @endphp
                                    @if($activeRes->isNotEmpty())
                                        <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                            @foreach($activeRes as $r)
                                                @if($r->lot)
                                                    <span style="display: inline-block; padding: 2px 8px; font-size: 11px; font-weight: 700; background: #e0f2fe; color: #0369a1; border-radius: 6px; border: 1px solid #bae6fd;">
                                                        Blk {{ $r->lot->block }} Lot {{ $r->lot->lot_number }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @elseif($buyer->block_no && $buyer->lot_no)
                                        <span style="display: inline-block; padding: 2px 8px; font-size: 11px; font-weight: 700; background: #f1f5f9; color: #475569; border-radius: 6px;">
                                            Blk {{ $buyer->block_no }} Lot {{ $buyer->lot_no }}
                                        </span>
                                    @else
                                        <span style="font-size: 12px; color: #94a3b8; font-style: italic;">No lots reserved</span>
                                    @endif
                                    @if($buyer->lot_link_warning)
                                        <div style="margin-top: 4px; max-width: 220px; font-size: 11px; color: #92400e;">{{ $buyer->lot_link_warning }}</div>
                                    @endif
                                </td>
                                @php
                                    $tcpDisplay = (float) ($buyer->financingRecord?->contract_price ?? 0);
                                    if ($tcpDisplay <= 0 && $buyer->lot?->contract_price) {
                                        $tcpDisplay = (float) $buyer->lot->contract_price;
                                    }
                                @endphp
                                <td style="font-family: monospace;">₱{{ number_format($tcpDisplay, 2) }}</td>
                                <td style="font-family: monospace;">₱{{ number_format($buyer->financingRecord?->paid_by_vendee_equity ?? 0, 2) }}</td>
                                @php
                                    $reservation = $buyer->reservations
                                        ->filter(fn ($item) => filled($item->deadline_date))
                                        ->sortByDesc(fn ($item) => [
                                            in_array($item->status, ['Pending', 'Reserved'], true),
                                            $item->id,
                                        ])
                                        ->first();
                                    $deadlineLabel = null;
                                    $deadlineNote = null;
                                    $deadlineColor = '#64748b';
                                    if ($reservation) {
                                        $deadlineDate = \Carbon\Carbon::parse($reservation->deadline_date)->startOfDay();
                                        $today = now()->startOfDay();
                                        $deadlineLabel = $deadlineDate->format('M d, Y');
                                        if ($deadlineDate->lt($today)) {
                                            $overdueDays = (int) $deadlineDate->diffInDays($today);
                                            $deadlineNote = 'Overdue '.$overdueDays.' '.($overdueDays === 1 ? 'day' : 'days');
                                            $deadlineColor = '#dc2626';
                                        } elseif ($deadlineDate->equalTo($today)) {
                                            $deadlineNote = 'Due today';
                                            $deadlineColor = '#d97706';
                                        } else {
                                            $daysLeft = (int) $today->diffInDays($deadlineDate);
                                            $deadlineNote = $daysLeft.' '.($daysLeft === 1 ? 'day' : 'days').' left';
                                            $deadlineColor = '#059669';
                                        }
                                    }
                                @endphp
                                <td style="color: {{ $deadlineColor }}; font-weight: 600; white-space: nowrap;">
                                    {{ $deadlineLabel ?? '-' }}
                                    @if($deadlineNote)
                                        <div style="margin-top: 4px; font-size: 11px; font-weight: 700;">{{ $deadlineNote }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                    <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 6px;" onclick="viewBuyer('{{ $buyer->id }}')">
                                        View
                                    </button>
                                    @if($buyer->trashed())
                                    <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; color: #059669; border-color: #bbf7d0;" onclick="restoreBuyer('{{ $buyer->id }}')" title="Restore this buyer">
                                        Restore
                                    </button>
                                    @endif
                                    @unless($buyer->trashed())
                                    <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 6px;" onclick="editBuyer('{{ $buyer->id }}')">
                                        Edit
                                    </button>
                                    @php
                                        $hasUserAccount = $buyer->user && $buyer->user->password !== null;
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
                                    @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: #64748b;">
                                    {{ request('status') === 'cancelled' ? 'No cancelled buyers.' : 'No buyers in the Master List yet.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $buyers->links('pagination.admin') }}
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
                    <button type="button" class="tab-btn active" onclick="switchTab('tab-profile', this)">Personal Information</button>
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
                                Name *</label><input type="text" name="middle_name" id="inp_middle_name" required
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Last Name
                                *</label><input type="text" name="last_name" id="inp_last_name" required
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Civil
                                Status *</label>
                            <select name="civil_status" id="inp_civil_status" required
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly" onchange="syncSpouseField()">
                                <option value="">-- Select Civil Status --</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Separated">Separated</option>
                                <option value="Annulled">Annulled</option>
                            </select>
                        </div>
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Spouse
                                Name</label><input type="text" name="spouse_name" id="inp_spouse_name"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px; background:#f1f5f9;"
                                class="view-readonly" disabled></div>
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Contact
                                Number *</label><input type="text" name="contact_number" id="inp_contact_number" required
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Email Address *</label><input type="email" name="email" id="inp_email" required
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                        <div style="grid-column: span 3;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <label class="form-label" style="margin: 0; font-size: 12px; font-weight: 600; color: #334155;">Present Address *</label>
                                <button type="button" class="btn-address-helper btn-address-api" onclick="openAddressPicker('present')"
                                    title="Select official Region, Province, City, and Barangay">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    <span>Select Standard Address</span>
                                </button>
                            </div>
                            <input type="text" name="present_address" id="inp_present_address" required
                                style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly" placeholder="e.g. Unit 4B, Emerald St., Brgy. San Antonio, Pasig City">
                        </div>
                        <div style="grid-column: span 3;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <label class="form-label" style="margin: 0; font-size: 12px; font-weight: 600; color: #334155;">Postal Address *</label>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <button type="button" class="btn-address-helper btn-address-api" onclick="openAddressPicker('postal')"
                                        title="Select official Region, Province, City, and Barangay">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                        <span>Select Standard Address</span>
                                    </button>
                                    <button type="button" id="btn_copy_address" class="btn-address-copy" onclick="copyPresentAddress()"
                                        title="Copy Present Address to Postal Address">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                        </svg>
                                        <span>Same as Present Address</span>
                                    </button>
                                </div>
                            </div>
                            <input type="text" name="postal_address" id="inp_postal_address" required
                                style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly" placeholder="e.g. Unit 4B, Emerald St., Brgy. San Antonio, Pasig City">
                        </div>
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Type of ID *</label>
                            <select name="id_type" id="inp_id_type" required
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly">
                                <option value="">-- Select ID Type --</option>
                                @foreach(\App\Models\BuyerMasterList::ID_TYPES as $idType)
                                    <option value="{{ $idType }}">{{ $idType }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">ID Number *</label><input type="text" name="proof_of_id"
                                id="inp_proof_of_id" required
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly"></div>
                        <div><label class="form-label"
                                style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Pag-IBIG
                                No. <span style="font-weight:400; color:#64748b;">(Optional)</span></label><input type="text" name="pagibig_number" id="inp_pagibig_number"
                                style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;"
                                class="view-readonly" placeholder="Optional"></div>
                    </div>

                    <div id="view_reserved_lots_section" style="display: none; margin-top: 16px; padding: 14px 18px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px;">
                        <span style="font-size: 12px; font-weight: 700; color: #166534; text-transform: uppercase; letter-spacing: 0.5px;">Active Reserved Lot(s)</span>
                        <div id="view_reserved_lots_content" style="margin-top: 8px; display: flex; flex-wrap: wrap; gap: 6px;"></div>
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

    <!-- Address Selector Modal -->
    <div id="addressApiModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1200; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: white; border-radius: 16px; width: 100%; max-width: 580px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.25); overflow: hidden;">
            <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </div>
                    <div>
                        <h3 id="addressModalTitle" style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">
                            Select <span id="addressModalTargetLabel">Present Address</span>
                        </h3>
                        <p style="margin: 2px 0 0 0; font-size: 12px; color: #64748b;">Choose the official region, province, city, and barangay.</p>
                    </div>
                </div>
                <button type="button" onclick="closeAddressPicker()" style="background: none; border: none; font-size: 20px; color: #94a3b8; cursor: pointer; padding: 4px; border-radius: 6px; line-height: 1;" title="Close">✕</button>
            </div>

            <div style="padding: 20px 24px; display: flex; flex-direction: column; gap: 14px; max-height: calc(85vh - 130px); overflow-y: auto;">
                <!-- Province / Metro Manila with Live Search -->
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <label style="margin: 0; font-size: 12px; font-weight: 700; color: #334155;">Province / Metro Manila *</label>
                        <span style="font-size: 11px; color: #64748b;">Type to filter or select</span>
                    </div>
                    <div style="position: relative; margin-bottom: 6px;">
                        <input type="text" id="addrProvinceSearch" placeholder="Search province (e.g. Cavite, Laguna, Metro Manila)..." 
                            style="width: 100%; padding: 8px 12px 8px 34px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #f8fafc;"
                            oninput="filterProvinces(this.value)" autocomplete="off">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); pointer-events: none;">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>
                    <select id="addrProvince" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #fff;" onchange="onProvinceChange()">
                        <option value="">Loading provinces...</option>
                    </select>
                </div>

                <!-- City / Municipality -->
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">City / Municipality *</label>
                    <select id="addrCity" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #fff;" onchange="onCityChange()" disabled>
                        <option value="">-- Select Province First --</option>
                    </select>
                </div>

                <!-- Barangay -->
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">Barangay *</label>
                    <select id="addrBarangay" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #fff;" onchange="updateAddressPreview()" disabled>
                        <option value="">-- Select City / Municipality First --</option>
                    </select>
                </div>

                <!-- Street / Unit / House / Subdivision details -->
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">
                        Street / Unit / House No. / Subdivision
                        <span style="font-weight: 400; color: #64748b;">(Optional detail)</span>
                    </label>
                    <input type="text" id="addrStreet" placeholder="e.g. Lot 1 Blk 2, Phase 1, Sunflower St." style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;" oninput="updateAddressPreview()">
                </div>

                <!-- Address Preview -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px;">
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Address Preview</span>
                    <p id="addrPreviewText" style="margin: 6px 0 0 0; font-size: 13px; font-weight: 600; color: #1e293b; min-height: 20px; word-break: break-word;">
                        <span style="color: #94a3b8; font-style: italic;">Make selections above to construct address...</span>
                    </p>
                </div>
            </div>

            <div style="padding: 14px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeAddressPicker()" style="padding: 9px 16px; border: 1px solid #cbd5e1; background: #fff; color: #475569; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">Cancel</button>
                <button type="button" id="btnApplyAddress" onclick="applySelectedAddress()" style="padding: 9px 20px; border: none; background: #0284c7; color: #fff; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; opacity: 0.5;" disabled>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span>Apply Address</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentExportBuyerId = null;

        function openExportModal(buyerId) {
            if (blockIfArchived(buyerId)) return;
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
        const buyersData = @json($buyers->items());
        function isArchivedBuyer(id) {
            const buyer = buyersData.find(b => b.id == id);
            return !!(buyer && buyer.deleted_at);
        }
        function blockIfArchived(id) {
            if (!isArchivedBuyer(id)) return false;
            Swal.fire({
                icon: 'info',
                title: 'Archived buyer',
                text: 'This record is cancelled. Only viewing is allowed.'
            });
            return true;
        }
        const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[A-Za-z]{2,}$/;
        const PH_MOBILE_PATTERN = /^(09\d{9}|\+639\d{9}|639\d{9})$/;

        function normalizePhone(raw) {
            return String(raw || '').replace(/[\s\-().]/g, '');
        }

        function validateBuyerEmailAndContact(emailRaw, contactRaw) {
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

        function apiErrorMessage(payload, fallback) {
            if (payload?.message) return payload.message;
            if (payload?.errors) {
                const first = Object.values(payload.errors).flat()[0];
                if (first) return first;
            }
            return fallback;
        }

        function switchTab(tabId, btnElement) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            btnElement.classList.add('active');
        }

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        let currentAddressTarget = 'present';
        let psgcAllProvincesCache = null;
        const psgcCitiesCache = {};
        const psgcBarangaysCache = {};

        function openAddressPicker(target) {
            currentAddressTarget = target;
            const targetLabel = target === 'present' ? 'Present Address' : 'Postal Address';
            const labelEl = document.getElementById('addressModalTargetLabel');
            if (labelEl) labelEl.textContent = targetLabel;
            document.getElementById('addressApiModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';

            const searchInp = document.getElementById('addrProvinceSearch');
            if (searchInp) searchInp.value = '';

            const streetInp = document.getElementById('addrStreet');
            if (streetInp) streetInp.value = '';

            loadAllProvinces();
        }

        function closeAddressPicker() {
            document.getElementById('addressApiModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('addrProvinceSearch');
            if (searchInput) {
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const select = document.getElementById('addrProvince');
                        if (select && select.options.length > 1) {
                            select.selectedIndex = 1;
                            onProvinceChange();
                        }
                    }
                });
            }
        });

        async function fetchWithFallback(url, fallbackUrl) {
            try {
                const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (resp.ok) {
                    const data = await resp.json();
                    if (Array.isArray(data) && data.length > 0) return data;
                }
            } catch (err) {
                console.warn('Backend address proxy failed, falling back to public endpoint...', err);
            }
            try {
                const respFallback = await fetch(fallbackUrl);
                if (respFallback.ok) {
                    const fbData = await respFallback.json();
                    if (Array.isArray(fbData) && fbData.length > 0) return fbData;
                }
            } catch (e) {
                console.error('Address service error:', e);
            }
            return [];
        }

        async function loadAllProvinces() {
            const select = document.getElementById('addrProvince');
            if (psgcAllProvincesCache && psgcAllProvincesCache.length > 0) {
                populateProvinceSelect(psgcAllProvincesCache);
                return;
            }

            select.innerHTML = '<option value="">Loading provinces...</option>';
            select.disabled = true;

            let provinces = await fetchWithFallback('/api/address/provinces', 'https://psgc.gitlab.io/api/provinces.json');
            if (Array.isArray(provinces) && provinces.length > 0) {
                if (!provinces.some(p => p.name === 'Metro Manila')) {
                    provinces.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
                    provinces.unshift({
                        code: '130000000',
                        name: 'Metro Manila',
                        isNcr: true
                    });
                }
            }

            psgcAllProvincesCache = provinces;
            select.disabled = false;
            populateProvinceSelect(provinces);
        }

        function populateProvinceSelect(provinces, filterTerm = '') {
            const select = document.getElementById('addrProvince');
            if (!provinces || provinces.length === 0) {
                select.innerHTML = '<option value="">Unable to load provinces. Please check connection.</option>';
                return;
            }

            const currentVal = select.value;
            let list = provinces;
            if (filterTerm) {
                const term = filterTerm.toLowerCase();
                list = provinces.filter(p => p.name.toLowerCase().includes(term));
            }

            if (list.length === 0) {
                select.innerHTML = '<option value="">No matching provinces found</option>';
                resetCitySelect();
                resetBarangaySelect();
                updateAddressPreview();
                return;
            }

            let html = '<option value="">-- Select Province / Metro Manila --</option>';
            list.forEach(p => {
                const isNcr = p.isNcr || String(p.code).startsWith('13');
                const isSelected = p.code === currentVal ? 'selected' : '';
                html += `<option value="${p.code}" data-name="${escapeHtml(p.name)}" data-is-ncr="${isNcr ? '1' : '0'}" ${isSelected}>${escapeHtml(p.name)}</option>`;
            });
            select.innerHTML = html;

            resetCitySelect();
            resetBarangaySelect();
            updateAddressPreview();
        }

        function filterProvinces(val) {
            if (!psgcAllProvincesCache) return;
            populateProvinceSelect(psgcAllProvincesCache, (val || '').trim());
            const select = document.getElementById('addrProvince');
            // If user filtered down to exactly 1 province result, automatically select it
            if (select.options.length === 2) {
                select.selectedIndex = 1;
                onProvinceChange();
            }
        }

        function resetCitySelect() {
            const citySelect = document.getElementById('addrCity');
            citySelect.innerHTML = '<option value="">-- Select Province First --</option>';
            citySelect.disabled = true;
        }

        function resetBarangaySelect() {
            const bSelect = document.getElementById('addrBarangay');
            bSelect.innerHTML = '<option value="">-- Select City / Municipality First --</option>';
            bSelect.disabled = true;
        }

        async function onProvinceChange() {
            const provSelect = document.getElementById('addrProvince');
            const code = provSelect.value;
            const opt = provSelect.selectedOptions[0];
            const isNcr = opt && opt.getAttribute('data-is-ncr') === '1';

            resetCitySelect();
            resetBarangaySelect();

            if (!code) {
                updateAddressPreview();
                return;
            }

            loadCities(code, isNcr);
            updateAddressPreview();
        }

        async function loadCities(code, isRegionDirect) {
            const citySelect = document.getElementById('addrCity');
            citySelect.disabled = true;
            citySelect.innerHTML = '<option value="">Loading cities and municipalities...</option>';

            let cities = psgcCitiesCache[code];
            if (!cities) {
                const fallbackUrl = isRegionDirect
                    ? `https://psgc.gitlab.io/api/regions/${code}/cities-municipalities.json`
                    : `https://psgc.gitlab.io/api/provinces/${code}/cities-municipalities.json`;
                const localUrl = isRegionDirect ? `/api/address/cities/${code}?is_region=1` : `/api/address/cities/${code}`;
                cities = await fetchWithFallback(localUrl, fallbackUrl);
                psgcCitiesCache[code] = cities;
            }

            citySelect.disabled = false;
            if (!cities || cities.length === 0) {
                citySelect.innerHTML = '<option value="">No cities or municipalities found</option>';
                return;
            }

            cities.sort((a, b) => a.name.localeCompare(b.name));
            let html = '<option value="">-- Select City / Municipality --</option>';
            cities.forEach(c => {
                html += `<option value="${c.code}" data-name="${escapeHtml(c.name)}">${escapeHtml(c.name)}</option>`;
            });
            citySelect.innerHTML = html;
        }

        async function onCityChange() {
            const citySelect = document.getElementById('addrCity');
            const code = citySelect.value;
            resetBarangaySelect();

            if (!code) {
                updateAddressPreview();
                return;
            }

            const bSelect = document.getElementById('addrBarangay');
            bSelect.disabled = true;
            bSelect.innerHTML = '<option value="">Loading barangays...</option>';

            let barangays = psgcBarangaysCache[code];
            if (!barangays) {
                barangays = await fetchWithFallback(
                    `/api/address/barangays/${code}`,
                    `https://psgc.gitlab.io/api/cities-municipalities/${code}/barangays.json`
                );
                psgcBarangaysCache[code] = barangays;
            }

            bSelect.disabled = false;
            if (!barangays || barangays.length === 0) {
                bSelect.innerHTML = '<option value="">No barangays found</option>';
                updateAddressPreview();
                return;
            }

            barangays.sort((a, b) => a.name.localeCompare(b.name));
            let html = '<option value="">-- Select Barangay --</option>';
            barangays.forEach(b => {
                html += `<option value="${b.code}" data-name="${escapeHtml(b.name)}">${escapeHtml(b.name)}</option>`;
            });
            bSelect.innerHTML = html;

            updateAddressPreview();
        }

        function buildAddressParts() {
            const street = (document.getElementById('addrStreet').value || '').trim();
            const bOpt = document.getElementById('addrBarangay').selectedOptions[0];
            const cOpt = document.getElementById('addrCity').selectedOptions[0];
            const pOpt = document.getElementById('addrProvince').selectedOptions[0];

            const bName = bOpt && bOpt.value ? bOpt.getAttribute('data-name') || bOpt.text : '';
            const cName = cOpt && cOpt.value ? cOpt.getAttribute('data-name') || cOpt.text : '';
            const pName = pOpt && pOpt.value ? pOpt.getAttribute('data-name') || pOpt.text : '';

            const parts = [];
            if (street) parts.push(street);
            if (bName) parts.push(bName.toLowerCase().startsWith('brgy') || bName.toLowerCase().startsWith('barangay') ? bName : 'Brgy. ' + bName);
            if (cName) parts.push(cName);
            if (pName) parts.push(pName);

            return parts;
        }

        function updateAddressPreview() {
            const parts = buildAddressParts();
            const previewEl = document.getElementById('addrPreviewText');
            const applyBtn = document.getElementById('btnApplyAddress');

            if (parts.length === 0) {
                previewEl.innerHTML = '<span style="color: #94a3b8; font-style: italic;">Make selections above to construct address...</span>';
                applyBtn.disabled = true;
                applyBtn.style.opacity = '0.5';
                applyBtn.style.cursor = 'not-allowed';
            } else {
                const fullText = parts.join(', ');
                previewEl.textContent = fullText;
                const cityChosen = !!document.getElementById('addrCity').value;
                applyBtn.disabled = !cityChosen;
                applyBtn.style.opacity = cityChosen ? '1' : '0.5';
                applyBtn.style.cursor = cityChosen ? 'pointer' : 'not-allowed';
            }
        }

        function applySelectedAddress() {
            const parts = buildAddressParts();
            if (parts.length === 0) return;
            const fullAddress = parts.join(', ');

            const inputId = currentAddressTarget === 'postal' ? 'inp_postal_address' : 'inp_present_address';
            const input = document.getElementById(inputId);
            if (input) {
                input.value = fullAddress;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }

            closeAddressPicker();
        }

        function copyPresentAddress() {
            const present = document.getElementById('inp_present_address').value;
            const postalInput = document.getElementById('inp_postal_address');
            postalInput.value = present;
            postalInput.dispatchEvent(new Event('input', { bubbles: true }));
        }

        function syncSpouseField() {
            const status = document.getElementById('inp_civil_status').value;
            const spouse = document.getElementById('inp_spouse_name');
            const isMarried = status === 'Married';
            const isView = document.getElementById('saveButton').style.display === 'none';

            spouse.disabled = !isMarried || isView;
            spouse.required = isMarried && !isView;
            if (!isMarried) {
                spouse.value = '';
            }
            spouse.style.backgroundColor = isMarried ? '#fff' : '#f1f5f9';
            spouse.style.cursor = isMarried ? '' : 'not-allowed';
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

            const lotsSection = document.getElementById('view_reserved_lots_section');
            const lotsContent = document.getElementById('view_reserved_lots_content');
            if (lotsSection) lotsSection.style.display = 'none';
            if (lotsContent) lotsContent.innerHTML = '';

            const copyBtn = document.getElementById('btn_copy_address');
            if (copyBtn) {
                copyBtn.style.display = (mode === 'view') ? 'none' : 'inline-flex';
            }
            document.querySelectorAll('.btn-address-api').forEach(btn => {
                btn.style.display = (mode === 'view') ? 'none' : 'inline-flex';
            });

            if (mode === 'add') {
                document.getElementById('modalTitle').textContent = 'Add New Buyer';
                document.getElementById('formMethod').value = 'POST';
                document.getElementById('buyerId').value = '';
                document.getElementById('saveButton').style.display = 'block';
                document.getElementById('saveButton').textContent = 'Save Record';
                readOnlyElements.forEach(el => el.removeAttribute('disabled'));
                readOnlyElements.forEach(el => el.removeAttribute('readonly'));

                syncSpouseField();
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
                document.getElementById('inp_email').value = buyer.email || '';
                document.getElementById('inp_present_address').value = buyer.present_address || '';
                document.getElementById('inp_postal_address').value = buyer.postal_address || '';
                document.getElementById('inp_id_type').value = buyer.id_type || '';
                document.getElementById('inp_proof_of_id').value = buyer.proof_of_id || '';
                document.getElementById('inp_pagibig_number').value = buyer.pagibig_number || '';

                syncSpouseField();

                // Show reserved lots if any
                if (lotsSection && lotsContent) {
                    const activeLots = (buyer.reservations || [])
                        .filter(r => r.status !== 'Cancelled' && r.lot)
                        .map(r => `Blk ${r.lot.block} Lot ${r.lot.lot_number}`);
                    
                    if (activeLots.length > 0) {
                        lotsContent.innerHTML = activeLots.map(l => 
                            `<span style="display:inline-block; padding: 4px 10px; font-size: 12px; font-weight: 700; background: #dcfce7; color: #15803d; border-radius: 6px; border: 1px solid #86efac;">${l}</span>`
                        ).join('');
                        lotsSection.style.display = 'block';
                    } else if (buyer.block_no && buyer.lot_no) {
                        lotsContent.innerHTML = `<span style="display:inline-block; padding: 4px 10px; font-size: 12px; font-weight: 700; background: #f1f5f9; color: #475569; border-radius: 6px;">Blk ${buyer.block_no} Lot ${buyer.lot_no}</span>`;
                        lotsSection.style.display = 'block';
                    } else {
                        lotsContent.innerHTML = `<span style="font-size: 12px; color: #94a3b8; font-style: italic;">No lots reserved yet.</span>`;
                        lotsSection.style.display = 'block';
                    }
                }

                if (mode === 'view') {
                    document.getElementById('modalTitle').textContent = 'View Buyer Details';
                    document.getElementById('saveButton').style.display = 'none';
                    readOnlyElements.forEach(el => el.setAttribute('readonly', true));
                    document.getElementById('inp_civil_status').setAttribute('disabled', true);
                    document.getElementById('inp_id_type').setAttribute('disabled', true);
                    syncSpouseField();
                } else if (mode === 'edit') {
                    document.getElementById('modalTitle').textContent = 'Edit Buyer Details';
                    document.getElementById('formMethod').value = 'PUT';
                    document.getElementById('saveButton').style.display = 'block';
                    document.getElementById('saveButton').textContent = 'Update Record';
                    readOnlyElements.forEach(el => el.removeAttribute('readonly'));
                    document.getElementById('inp_civil_status').removeAttribute('disabled');
                    document.getElementById('inp_id_type').removeAttribute('disabled');
                    syncSpouseField();
                }
            }

            document.getElementById('buyerModal').style.display = 'flex';
        }

        function viewBuyer(id) {
            openModal('view', id);
        }

        function editBuyer(id) {
            if (blockIfArchived(id)) return;
            openModal('edit', id);
        }

        function closeModal() {
            document.getElementById('buyerModal').style.display = 'none';
        }

        async function submitBuyerForm(e) {
            e.preventDefault();

            if (!e.target.reportValidity()) {
                return;
            }

            const validatedContact = validateBuyerEmailAndContact(
                document.getElementById('inp_email').value,
                document.getElementById('inp_contact_number').value
            );
            if (!validatedContact) {
                switchTab('tab-profile', document.querySelector('.tab-btn'));
                return;
            }

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            data.email = validatedContact.email;
            data.contact_number = validatedContact.contact_number;

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

                const result = await response.json().catch(() => ({}));

                if (response.ok && result.success) {
                    Swal.fire(result.message);
                    window.location.reload();
                } else {
                    Swal.fire(apiErrorMessage(result, 'Validation error. Please check your inputs.'));
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                }
            } catch (err) {
                Swal.fire('An error occurred. Check input constraints.');
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            }
        }

        function restoreBuyer(id) {
            if (!isArchivedBuyer(id)) {
                Swal.fire({ icon: 'info', title: 'Already active', text: 'This buyer is not cancelled.' });
                return;
            }
            Swal.fire({
                title: 'Restore Buyer?',
                text: 'This brings the buyer back to the active list. The lot is restored only if it is still free. A lapsed reservation deadline will be extended 30 days so they are not auto-cancelled again.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, restore'
            }).then(async (result) => {
                if (!result.isConfirmed) return;
                try {
                    const response = await fetch('/admin/buyer-master-list/' + id + '/restore', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                            'Accept': 'application/json'
                        }
                    });
                    const resJson = await response.json().catch(() => ({}));
                    if (response.ok && resJson.success) {
                        Swal.fire('Restored', resJson.message, 'success').then(() => window.location.reload());
                    } else {
                        Swal.fire('Cannot restore', resJson.message || 'Restore failed.', 'error');
                    }
                } catch (err) {
                    Swal.fire('Error', 'Error communicating with server.', 'error');
                }
            });
        }

        function deleteBuyer(id) {
            if (blockIfArchived(id)) return;
            Swal.fire({
                title: 'Archive Buyer?',
                text: "Archive this buyer? Their login will be deactivated. Open reservations are marked Cancelled (records kept). Downpayment and financing data stay on file.",
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
            if (blockIfArchived(id)) return;
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

        function openBuyerReportModal() {
            const currentStatus = "{{ request('status') === 'cancelled' ? 'cancelled' : 'active' }}";
            const currentBlock = "{{ request('block', '') }}";
            if (document.getElementById('reportBuyerStatusFilter')) {
                document.getElementById('reportBuyerStatusFilter').value = currentStatus;
            }
            if (document.getElementById('reportBuyerBlockFilter')) {
                document.getElementById('reportBuyerBlockFilter').value = currentBlock;
            }
            reloadBuyerReportPdf();
            document.getElementById('buyerReportModal').style.display = 'flex';
        }

        function closeBuyerReportModal() {
            document.getElementById('buyerReportModal').style.display = 'none';
        }

        function reloadBuyerReportPdf() {
            const status = document.getElementById('reportBuyerStatusFilter')?.value || 'active';
            const block = document.getElementById('reportBuyerBlockFilter')?.value || '';
            const sort = document.getElementById('reportBuyerSortFilter')?.value || 'name_asc';
            const base = "{{ route('admin.buyer-master-list.report.pdf') }}";
            const qs = `?status=${encodeURIComponent(status)}&block=${encodeURIComponent(block)}&sort=${encodeURIComponent(sort)}`;

            const frame = document.getElementById('buyerReportPdfFrame');
            if (frame) {
                frame.src = base + qs;
            }
            const dlBtn = document.getElementById('buyerReportDownloadBtn');
            if (dlBtn) {
                dlBtn.href = base + qs + '&download=1';
            }
        }

        function printBuyerReportPdfFrame() {
            const frame = document.getElementById('buyerReportPdfFrame');
            if (frame && frame.contentWindow) {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            }
        }
    </script>

    <!-- Official Buyer Masterlist PDF Report Modal -->
    <div id="buyerReportModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 2100; background: rgba(15,23,42,0.7); backdrop-filter: blur(8px); padding: 20px;">
        <div class="bill-modal-content" style="max-width: 1100px; width: 95%; max-height: 94vh; overflow: hidden; padding: 20px 24px; border-radius: 20px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); display: flex; flex-direction: column;">
            
            <!-- Controls Bar -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0;">
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Status</span>
                        <select id="reportBuyerStatusFilter" class="filter-select" onchange="reloadBuyerReportPdf()" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer;">
                            <option value="active" selected>Active Buyers</option>
                            <option value="cancelled">Cancelled Buyers</option>
                            <option value="all">All Buyers</option>
                        </select>
                    </div>
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Block</span>
                        <select id="reportBuyerBlockFilter" class="filter-select" onchange="reloadBuyerReportPdf()" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer;">
                            <option value="">All Blocks</option>
                            @for($i = 1; $i <= 18; $i++)
                                <option value="{{ $i }}">Block {{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Sort By</span>
                        <select id="reportBuyerSortFilter" class="filter-select" onchange="reloadBuyerReportPdf()" style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer;">
                            <option value="name_asc" selected>Alphabetical: Name (A to Z)</option>
                            <option value="name_desc">Alphabetical: Name (Z to A)</option>
                            <option value="date_asc">Date Registered: Earliest to Latest</option>
                            <option value="date_desc">Date Registered: Latest to Earliest</option>
                            <option value="id_asc">Buyer ID: Ascending (BML-1...)</option>
                            <option value="id_desc">Buyer ID: Descending</option>
                            <option value="tcp_desc">Contract Price: Highest First</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <a id="buyerReportDownloadBtn" href="{{ route('admin.buyer-master-list.report.pdf') }}?download=1" class="btn btn-primary" style="background: #0284c7; border: none; padding: 8px 16px; font-size: 12px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; color: #fff;">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download PDF
                    </a>
                    <button type="button" class="btn btn-outline" onclick="printBuyerReportPdfFrame()" style="background: #fff; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #334155;">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeBuyerReportModal()" style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 600; border-radius: 8px; cursor: pointer;">
                        ✕ Close
                    </button>
                </div>
            </div>

            <!-- Embedded Real PDF Viewer (Viewable in-page, no new tab) -->
            <div style="flex: 1; min-height: 0; width: 100%;">
                <iframe id="buyerReportPdfFrame" src="{{ route('admin.buyer-master-list.report.pdf') }}" style="width: 100%; height: 75vh; border: 1px solid #e2e8f0; border-radius: 10px; background: #525659;"></iframe>
            </div>

        </div>
    </div>
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