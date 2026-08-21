@extends('layouts.admin')

@section('title', 'Visitor Access Approvals')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">

<div class="bill-container fade-in">
    <div class="bill-header">
        <div class="bill-title">
            <h1>Visitor Access Approvals</h1>
            <p>Review resident visitor requests, generate verification PINs, and forward access clearance to Gate Security.</p>
        </div>
    </div>

    <!-- Stats Summary Row -->
    <div class="responsive-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px;">
        <div style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid #f59e0b;">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Pending Admin Approval</div>
            <div id="statPendingCount" style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">2</div>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid #10b981;">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Approved & Forwarded to Guard</div>
            <div id="statApprovedCount" style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ collect($pins)->where('status', 'Pending')->count() }}</div>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid var(--bill-border); border-left: 4px solid #3b82f6;">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Entered Today</div>
            <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ collect($pins)->where('status', 'Entered')->count() }}</div>
        </div>
    </div>

    <!-- Visitor Approvals Table -->
    <div class="analytic-card" style="padding: 0; overflow: hidden; border-radius: 16px; border: 1px solid #e2e8f0;">
        <div style="padding: 20px 24px; border-bottom: 1px solid var(--bill-border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Resident Visitor Access Queue</h3>
        </div>
        <div class="bill-table-container">
            <table class="bill-table" id="adminVisitorTable">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Resident / Host</th>
                        <th>Visitor Details</th>
                        <th>Visit Date</th>
                        <th>Approval Status</th>
                        <th>Verification Code</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="adminVisitorTableBody">
                    <!-- Pre-populated Pending Requests requiring Admin Approval -->
                    <tr class="visitor-req-row" data-id="REQ-8001">
                        <td style="font-family: monospace; font-weight: 700; color: #64748b;">REQ-8001</td>
                        <td>
                            <div style="font-weight: 700; color: #0f172a;">Juan Dela Cruz</div>
                            <div style="font-size: 12px; color: var(--bill-primary);">Block 1, Lot 5</div>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #0f172a;">Mark Sanchez (Contractor)</div>
                            <div style="font-size: 12px; color: #64748b;">Roof Inspection & Repair</div>
                        </td>
                        <td style="font-size: 13px; color: #64748b; font-weight: 600;">
                            {{ date('M d, Y') }}
                        </td>
                        <td>
                            <span class="badge status-badge" style="background: #fef3c7; color: #b45309; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 11px;">
                                AWAITING ADMIN APPROVAL
                            </span>
                        </td>
                        <td class="pin-cell" style="font-family: monospace; font-size: 16px; font-weight: 800; color: #059669; letter-spacing: 2px;">
                            <span style="color: #94a3b8; font-size: 13px; font-weight: 500;">Pending Code</span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;" class="action-cell">
                                <button class="btn btn-primary" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; background: #059669;" onclick="approveVisitorRequest('REQ-8001', 'Juan Dela Cruz', 'Mark Sanchez (Contractor)', '1', '5', 'Roof Inspection & Repair', this)">
                                    ✓ Approve & Gen Code
                                </button>
                                <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; color: #ef4444; border-color: #fee2e2;" onclick="rejectVisitorRequest('REQ-8001', this)">
                                    ✕ Reject
                                </button>
                            </div>
                        </td>
                    </tr>

                    <tr class="visitor-req-row" data-id="REQ-8002">
                        <td style="font-family: monospace; font-weight: 700; color: #64748b;">REQ-8002</td>
                        <td>
                            <div style="font-weight: 700; color: #0f172a;">Maria Santos</div>
                            <div style="font-size: 12px; color: var(--bill-primary);">Block 2, Lot 12</div>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #0f172a;">Shopee Express Courier</div>
                            <div style="font-size: 12px; color: #64748b;">Parcel Delivery</div>
                        </td>
                        <td style="font-size: 13px; color: #64748b; font-weight: 600;">
                            {{ date('M d, Y') }}
                        </td>
                        <td>
                            <span class="badge status-badge" style="background: #fef3c7; color: #b45309; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 11px;">
                                AWAITING ADMIN APPROVAL
                            </span>
                        </td>
                        <td class="pin-cell" style="font-family: monospace; font-size: 16px; font-weight: 800; color: #059669; letter-spacing: 2px;">
                            <span style="color: #94a3b8; font-size: 13px; font-weight: 500;">Pending Code</span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;" class="action-cell">
                                <button class="btn btn-primary" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; background: #059669;" onclick="approveVisitorRequest('REQ-8002', 'Maria Santos', 'Shopee Express Courier', '2', '12', 'Parcel Delivery', this)">
                                    ✓ Approve & Gen Code
                                </button>
                                <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; color: #ef4444; border-color: #fee2e2;" onclick="rejectVisitorRequest('REQ-8002', this)">
                                    ✕ Reject
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- Already approved items -->
                    @foreach($pins as $pin)
                    <tr>
                        <td style="font-family: monospace; font-weight: 700; color: #64748b;">{{ $pin['id'] }}</td>
                        <td>
                            <div style="font-weight: 700; color: #0f172a;">{{ $pin['host'] }}</div>
                            <div style="font-size: 12px; color: var(--bill-primary);">Block {{ $pin['block'] }}, Lot {{ $pin['lot'] }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #0f172a;">{{ $pin['visitor'] }}</div>
                            <div style="font-size: 12px; color: #64748b;">{{ $pin['purpose'] }}</div>
                        </td>
                        <td style="font-size: 13px; color: #64748b; font-weight: 600;">
                            {{ date('M d, Y') }}
                        </td>
                        <td>
                            <span class="badge" style="background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 11px;">
                                APPROVED & FORWARDED TO GUARD
                            </span>
                        </td>
                        <td style="font-family: monospace; font-size: 16px; font-weight: 800; color: #059669; letter-spacing: 2px;">
                            {{ $pin['pin'] }}
                        </td>
                        <td>
                            <span style="font-size: 12px; color: #10b981; font-weight: 700;">Forwarded to Gate ✓</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        renderAdminVisitorTable();
    });

    function renderAdminVisitorTable() {
        const visitors = SubdivisionStore.getVisitors();
        const tbody = document.getElementById('adminVisitorTableBody');
        if (!tbody) return;

        tbody.innerHTML = '';

        let pendingCount = 0;
        let approvedCount = 0;
        let enteredCount = 0;

        visitors.forEach(v => {
            const tr = document.createElement('tr');
            tr.className = 'visitor-req-row';
            tr.dataset.id = v.id;

            let statusBadge = '';
            let codeDisplay = '';
            let actionHtml = '';

            if (v.status === 'Pending' || v.status === 'Awaiting Admin Approval') {
                pendingCount++;
                statusBadge = `<span class="badge status-badge" style="background: #fef3c7; color: #b45309; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 11px;">AWAITING ADMIN APPROVAL</span>`;
                codeDisplay = `<span style="color: #94a3b8; font-size: 13px; font-weight: 500;">Pending Code</span>`;
                actionHtml = `
                    <div style="display: flex; gap: 8px;" class="action-cell">
                        <button class="btn btn-primary" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; background: #059669;" onclick="approveVisitorRequest('${v.id}', '${(v.host || 'Resident').replace(/'/g, "\\'")}', '${v.visitor.replace(/'/g, "\\'")}', '${v.block || 1}', '${v.lot || 1}', '${(v.purpose || '').replace(/'/g, "\\'")}')">
                            ✓ Approve & Gen Code
                        </button>
                        <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; color: #ef4444; border-color: #fee2e2;" onclick="rejectVisitorRequest('${v.id}')">
                            ✕ Reject
                        </button>
                    </div>
                `;
            } else if (v.status === 'Approved') {
                approvedCount++;
                statusBadge = `<span class="badge" style="background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 11px;">APPROVED & FORWARDED TO GUARD</span>`;
                codeDisplay = `<span style="color:#059669; font-family:monospace; font-size:16px; font-weight:800; letter-spacing:2px;">${v.pin || '123456'}</span>`;
                actionHtml = `<span style="font-size: 12px; color: #10b981; font-weight: 700;">Forwarded to Gate ✓</span>`;
            } else if (v.status === 'Entered') {
                enteredCount++;
                statusBadge = `<span class="badge" style="background: #e0e7ff; color: #4f46e5; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 11px;">ENTERED GATE</span>`;
                codeDisplay = `<span style="color:#4f46e5; font-family:monospace; font-size:16px; font-weight:800; letter-spacing:2px;">${v.pin || 'USED'}</span>`;
                actionHtml = `<span style="font-size: 12px; color: #4f46e5; font-weight: 700;">Entered Gate ✓</span>`;
            } else {
                statusBadge = `<span class="badge" style="background: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 11px;">REJECTED / EXPIRED</span>`;
                codeDisplay = `<span style="color:#94a3b8; font-size:13px;">-</span>`;
                actionHtml = `<span style="font-size: 12px; color: #ef4444; font-weight: 700;">Rejected</span>`;
            }

            tr.innerHTML = `
                <td style="font-family: monospace; font-weight: 700; color: #64748b;">${v.id}</td>
                <td>
                    <div style="font-weight: 700; color: #0f172a;">${v.host || 'Resident'}</div>
                    <div style="font-size: 12px; color: var(--bill-primary);">Block ${v.block || 1}, Lot ${v.lot || 1}</div>
                </td>
                <td>
                    <div style="font-weight: 700; color: #0f172a;">${v.visitor}</div>
                    <div style="font-size: 12px; color: #64748b;">${v.purpose}</div>
                </td>
                <td style="font-size: 13px; color: #64748b; font-weight: 600;">${v.validity || 'Today'}</td>
                <td>${statusBadge}</td>
                <td>${codeDisplay}</td>
                <td>${actionHtml}</td>
            `;

            tbody.appendChild(tr);
        });

        const pendingEl = document.getElementById('statPendingCount');
        const approvedEl = document.getElementById('statApprovedCount');
        if (pendingEl) pendingEl.innerText = pendingCount;
        if (approvedEl) approvedEl.innerText = approvedCount;
    }

    function approveVisitorRequest(reqId, hostName, visitorName, block, lot, purpose) {
        const verificationCode = Math.floor(100000 + Math.random() * 900000).toString();
        
        SubdivisionStore.updateVisitorStatus(reqId, 'Approved', verificationCode);
        renderAdminVisitorTable();

        // Dispatch Live Gmail Email
        fetch('/api/send-visitor-pin-email', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({
                email: 'eighty6pharmacy@gmail.com',
                visitor_name: visitorName,
                resident_name: hostName,
                pin: verificationCode,
                date: new Date().toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})
            })
        }).catch(err => console.error("Visitor PIN email dispatch error:", err));

        if (window.pushSystemNotification) {
            window.pushSystemNotification("Visitor Approved", `Verification Code ${verificationCode} generated for ${visitorName} and sent to resident & security guard.`, "Just now", true);
        }

        alert(`✓ VISITOR APPROVED!\n\nGenerated Verification Code: ${verificationCode}\n- Dispatched via email to eighty6pharmacy@gmail.com\n- Forwarded to Security Guard Portal for entry verification.`);
    }

    function rejectVisitorRequest(reqId) {
        if (confirm(`Are you sure you want to reject visitor request ${reqId}?`)) {
            SubdivisionStore.updateVisitorStatus(reqId, 'Rejected');
            renderAdminVisitorTable();
        }
    }
</script>
@endsection
