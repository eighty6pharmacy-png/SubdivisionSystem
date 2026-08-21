@extends('layouts.resident')

@section('title', 'Visitor Management')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">

<div class="fade-in">
    <div style="margin-bottom: 32px;">
        <h1 style="font-size: 28px; font-weight: 800; color: #0f172a; margin: 0;">Visitor PIN Generation</h1>
        <p style="color: #64748b; margin-top: 8px;">Pre-register guests to generate a 6-digit access PIN and expedite their entry at the main gate.</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;">
        
        <!-- Visitor Request Form -->
        <div class="analytic-card" style="padding: 24px; position: sticky; top: 24px;">
            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 20px;">Request Visitor Access</h3>
            <form id="generatePinForm" onsubmit="generateNewPin(event)" style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Visitor / Company Name</label>
                    <input type="text" id="visName" class="filter-select" required style="width: 100%; margin-top: 4px;" placeholder="e.g. Grab Delivery, John Doe">
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Purpose of Visit</label>
                    <input type="text" id="visPurpose" class="filter-select" required style="width: 100%; margin-top: 4px;" placeholder="e.g. Food Delivery, Family Visit">
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Expected Visit Date</label>
                    <input type="date" id="visDate" class="filter-select" required style="width: 100%; margin-top: 4px;" value="{{ date('Y-m-d') }}">
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">* Requires Admin Approval. Code will be generated upon approval.</div>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; font-weight: 700; margin-top: 8px;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px;"><path d="M12 4v16m8-8H4"/></svg>
                    Submit Access Request
                </button>
            </form>

            <div id="pinResultArea" style="display: none; margin-top: 24px; padding: 20px; background: #fffbeb; border-radius: 16px; border: 1px dashed #f59e0b; text-align: center; position: relative;">
                <p style="font-size: 12px; font-weight: 700; color: #b45309; text-transform: uppercase; margin: 0 0 8px 0;">Request Status</p>
                <div style="font-size: 14px; font-weight: 700; color: #92400e;">⏳ Awaiting Admin Approval</div>
                <p style="font-size: 12px; color: #78350f; margin: 8px 0 0 0;">Once Admin approves your request, your 6-digit verification PIN will be generated and dispatched here & to the security guard.</p>
            </div>
        </div>

        <!-- History Ledger -->
        <div class="analytic-card" style="padding: 0; overflow: hidden;">
            <div style="padding: 24px 24px 16px 24px; border-bottom: 1px solid var(--bill-border);">
                <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">My Visitor Log</h3>
            </div>
            <div class="bill-table-container">
                <table class="bill-table" id="pinTable">
                    <thead>
                        <tr>
                            <th>Verification Code</th>
                            <th>Visitor Details</th>
                            <th>Visit Date</th>
                            <th>Approval Status</th>
                        </tr>
                    </thead>
                    <tbody id="pinTableBody">
                        @foreach($pins as $pin)
                        <tr>
                            <td style="font-family: monospace; font-size: 16px; font-weight: 800; color: #059669; letter-spacing: 1px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    {{ $pin['pin'] }}
                                    <button onclick="copyToClipboard('{{ $pin['pin'] }}')" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 2px;" title="Copy PIN">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    </button>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #0f172a;">{{ $pin['visitor'] }}</div>
                                <div style="font-size: 12px; color: #64748b;">{{ $pin['purpose'] }}</div>
                            </td>
                            <td style="font-size: 13px; color: #64748b; font-weight: 600;">
                                {{ date('M d, Y', strtotime($pin['validity'] === 'Today' ? date('Y-m-d') : ($pin['validity'] === 'Tomorrow' ? date('Y-m-d', strtotime('+1 day')) : $pin['validity']))) }}
                            </td>
                            <td>
                                @if($pin['status'] === 'Pending')
                                    <span class="badge badge-success" style="padding: 4px 12px; border-radius: 20px;">Approved & Sent to Guard</span>
                                @elseif($pin['status'] === 'Entered')
                                    <span class="badge badge-success" style="padding: 4px 12px; border-radius: 20px; background:#dcfce7; color:#166534;">Entered Gate</span>
                                @else
                                    <span class="badge" style="background:#f1f5f9; color:#64748b; border: 1px solid #e2e8f0; padding: 4px 12px; border-radius: 20px;">Expired</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        renderResidentVisitors();
    });

    function renderResidentVisitors() {
        const visitors = SubdivisionStore.getVisitors();
        const tbody = document.getElementById('pinTableBody');
        if (!tbody) return;

        tbody.innerHTML = '';
        visitors.forEach(pin => {
            const tr = document.createElement('tr');
            let statusBadge = '';
            let codeDisplay = '';

            if(pin.status === 'Pending' || pin.status === 'Awaiting Admin Approval') {
                tr.style.background = '#fffbeb';
                codeDisplay = `<span style="font-family: monospace; font-size: 13px; font-weight: 700; color: #b45309;">Pending Approval</span>`;
                statusBadge = `<span class="badge" style="background:#fef3c7; color:#b45309; padding: 4px 12px; border-radius: 20px; font-weight:700;">Awaiting Admin Approval</span>`;
            } else if(pin.status === 'Approved') {
                codeDisplay = `
                    <div style="display: flex; align-items: center; gap: 8px; font-family: monospace; font-size: 16px; font-weight: 800; color: #059669;">
                        ${pin.pin || 'Approved'}
                        ${pin.pin ? `<button onclick="copyToClipboard('${pin.pin}')" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 2px;" title="Copy PIN"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></button>` : ''}
                    </div>`;
                statusBadge = `<span class="badge badge-success" style="padding: 4px 12px; border-radius: 20px;">Approved & Sent to Guard</span>`;
            } else if(pin.status === 'Entered') {
                codeDisplay = `<span style="font-family: monospace; font-size: 14px; font-weight: 700; color: #166534;">${pin.pin || 'USED'}</span>`;
                statusBadge = `<span class="badge badge-success" style="padding: 4px 12px; border-radius: 20px; background:#dcfce7; color:#166534;">Entered Gate</span>`;
            } else {
                codeDisplay = `<span style="font-family: monospace; font-size: 13px; color: #94a3b8;">${pin.pin || 'EXPIRED'}</span>`;
                statusBadge = `<span class="badge" style="background:#f1f5f9; color:#64748b; border: 1px solid #e2e8f0; padding: 4px 12px; border-radius: 20px;">Expired</span>`;
            }

            tr.innerHTML = `
                <td>${codeDisplay}</td>
                <td>
                    <div style="font-weight: 700; color: #0f172a;">${pin.visitor}</div>
                    <div style="font-size: 12px; color: #64748b;">${pin.purpose}</div>
                </td>
                <td style="font-size: 13px; color: #64748b; font-weight: 600;">${pin.validity}</td>
                <td>${statusBadge}</td>
            `;

            tbody.appendChild(tr);
        });
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            if (window.pushSystemNotification) {
                window.pushSystemNotification("PIN Copied", "PIN code " + text + " copied to clipboard.", "Just now", false);
            }
        });
    }

    function generateNewPin(e) {
        e.preventDefault();
        
        const name = document.getElementById('visName').value;
        const purpose = document.getElementById('visPurpose').value;
        const visitDateStr = document.getElementById('visDate').value;
        
        const visitDate = new Date(visitDateStr);
        const formattedDate = visitDate.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
        
        const reqId = 'VIS-' + Math.floor(3000 + Math.random() * 1000);

        SubdivisionStore.addVisitor({
            id: reqId,
            pin: null,
            visitor: name,
            host: 'Juan Dela Cruz (Resident)',
            block: '1',
            lot: '5',
            purpose: purpose,
            validity: formattedDate,
            status: 'Pending'
        });

        document.getElementById('pinResultArea').style.display = 'block';
        renderResidentVisitors();

        document.getElementById('visName').value = '';
        document.getElementById('visPurpose').value = '';
        
        if (window.pushSystemNotification) {
            window.pushSystemNotification("Request Submitted", `Visitor request for ${name} sent to Admin for approval.`, "Just now", true);
        }

        alert(`✓ VISITOR ACCESS REQUEST SUBMITTED!\n\nYour request for ${name} has been routed to Subdivision Admin for approval.\nOnce approved, your 6-digit verification code will be generated and forwarded to the security guard.`);
    }
</script>


<style>
    @media (max-width: 900px) {
        div[style*="display: grid; grid-template-columns: 1fr 2fr"] {
            grid-template-columns: 1fr !important;
        }
        .analytic-card[style*="position: sticky"] {
            position: relative !important;
            top: 0 !important;
        }
    }
</style>
@endsection
