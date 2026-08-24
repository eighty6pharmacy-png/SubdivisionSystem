@extends('layouts.guard')

@section('title', 'Gate Scanner Operations')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">
<style>
    /* PIN Inputs for Guard */
    .guard-pin-form {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 100%;
        max-width: 480px;
        margin: 0 auto;
    }

    .guard-pin-digits {
        display: flex;
        gap: 12px;
        margin-bottom: 24px;
        justify-content: center;
        width: 100%;
    }

    .guard-pin-digit {
        width: 60px;
        height: 70px;
        font-size: 32px;
        font-weight: 800;
        text-align: center;
        border: 2px solid var(--border);
        border-radius: 12px;
        background: #fff;
        color: var(--guard-primary);
        transition: all 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .guard-pin-digit:focus {
        outline: none;
        border-color: var(--guard-accent);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        transform: translateY(-2px);
    }

    .walkin-card {
        border: 2px dashed #3b82f6;
        background: #f0f9ff;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .walkin-card:hover {
        background: #e0f2fe;
        transform: translateY(-2px);
    }
</style>

<div class="fade-in">

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">

        <!-- Left Column: PIN Scanner -->
        <div class="analytic-card" style="padding: 32px; display: flex; flex-direction: column; align-items: center;">
            <div style="width: 56px; height: 56px; background: var(--guard-primary-soft); color: var(--guard-accent); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 8px;">Visitor Authorization</h2>
            <p style="color: #64748b; font-size: 14px; text-align: center; margin-bottom: 32px; max-width: 300px;">
                Input the 6-digit access PIN provided by the guest to verify entry and unlock routing.
            </p>

            <form class="guard-pin-form" onsubmit="validatePin(event)">
                <div class="guard-pin-digits">
                    <input type="text" class="guard-pin-digit" maxlength="1" id="pin-0" inputmode="numeric" autofocus>
                    <input type="text" class="guard-pin-digit" maxlength="1" id="pin-1" inputmode="numeric">
                    <input type="text" class="guard-pin-digit" maxlength="1" id="pin-2" inputmode="numeric">
                    <input type="text" class="guard-pin-digit" maxlength="1" id="pin-3" inputmode="numeric">
                    <input type="text" class="guard-pin-digit" maxlength="1" id="pin-4" inputmode="numeric">
                    <input type="text" class="guard-pin-digit" maxlength="1" id="pin-5" inputmode="numeric">
                </div>

                <div id="pinErrorBox" style="display: none; width: 100%; padding: 12px; background: #fee2e2; border: 1px solid #f87171; color: #991b1b; border-radius: 8px; font-size: 13px; font-weight: 600; text-align: center; margin-bottom: 16px;">
                    ❌ INVALID OR EXPIRED PIN
                </div>

                <button type="submit" id="verifyBtn" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 16px; font-size: 16px; font-weight: 700; background: var(--guard-primary);">
                    Validate Access
                </button>
            </form>
        </div>

        <!-- Right Column: Walk-in Registration -->
        <div id="authResultCard" class="analytic-card walkin-card" onclick="openWalkInModal()" style="padding: 32px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
            <div style="width: 56px; height: 56px; background: #dbeafe; color: #1e40af; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
            </div>
            <h3 style="font-size: 18px; font-weight: 800; color: #1e3a8a; margin: 0;">Record Walk-in Entry</h3>
            <p style="font-size: 13px; color: #64748b; margin-top: 8px;">For visitors without a generated PIN code.</p>
        </div>

    </div>

    <!-- Daily Visitor Log -->
    <div class="analytic-card" style="padding: 0; overflow: hidden; margin-top: 24px;">
        <div style="padding: 20px 24px; border-bottom: 1px solid var(--bill-border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Today's Expected Visitors</h3>
            <span style="font-size: 13px; color: #64748b; font-weight: 600;">Total: {{ count($pins) }}</span>
        </div>
        <div class="bill-table-container">
            <table class="bill-table" id="dailyLogTable">
                <thead>
                    <tr>
                        <th>Visit ID</th>
                        <th>Visitor Name</th>
                        <th>Host Property</th>
                        <th>Purpose</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pins as $index => $pin)
                    <tr>
                        <td style="font-family: monospace; font-size: 12px; font-weight: 700; color: #64748b;">
                            {{ $pin['id'] }}
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #0f172a;">{{ $pin['visitor'] }}</div>
                        </td>
                        <td>
                            <div style="background: var(--guard-primary-soft); color: var(--guard-primary); padding: 4px 10px; border-radius: 6px; display: inline-block; font-weight: 700; font-size: 12px;">
                                Blk {{ $pin['block'] }} Lot {{ $pin['lot'] }}
                            </div>
                            <div style="font-size: 11px; color: #64748b; margin-top: 4px;">{{ $pin['host'] }}</div>
                        </td>
                        <td style="font-size: 13px; color: #475569;">
                            {{ $pin['purpose'] }}
                        </td>
                        <td>
                            <span class="badge {{ $pin['status'] === 'Entered' ? 'badge-success' : ($pin['status'] === 'Pending' ? 'badge-warning' : '') }}" style="padding: 4px 12px; border-radius: 20px;">
                                {{ $pin['status'] }}
                            </span>
                            @if($pin['status'] === 'Entered' && isset($pin['arrival_time']))
                                <div style="font-size: 11px; color: #10b981; margin-top: 4px; font-weight: 700;">Entered: {{ $pin['arrival_time'] }}</div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Pin inputs behavior
    const digits = document.querySelectorAll('.guard-pin-digit');
    digits.forEach((digit, idx) => {
        digit.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
            if (e.target.value && idx < digits.length - 1) digits[idx + 1].focus();
        });
        digit.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !e.target.value && idx > 0) digits[idx - 1].focus();
        });
        digit.addEventListener('paste', function(e) {
            e.preventDefault();
            const pasted = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
            pasted.split('').forEach((char, i) => { if (digits[i]) digits[i].value = char; });
            if (pasted.length > 0) digits[Math.min(pasted.length, digits.length - 1)].focus();
        });
    });

    const staticPins = {!! json_encode($pins) !!};
    const adminApprovedPins = JSON.parse(localStorage.getItem('approved_visitor_pins') || '[]');
    const mockDatabase = [...adminApprovedPins, ...staticPins];
    const mockUsers = {!! json_encode($users ?? []) !!};

    // Prepend Admin-approved visitor PINs to table on page load
    document.addEventListener('DOMContentLoaded', () => {
        const tbody = document.querySelector('#dailyLogTable tbody');
        if (tbody && adminApprovedPins.length > 0) {
            adminApprovedPins.forEach(pin => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="font-family: monospace; font-size: 12px; font-weight: 700; color: #64748b;">${pin.id}</td>
                    <td><div style="font-weight: 700; color: #0f172a;">${pin.visitor}</div></td>
                    <td>
                        <div style="background: var(--guard-primary-soft); color: var(--guard-primary); padding: 4px 10px; border-radius: 6px; display: inline-block; font-weight: 700; font-size: 12px;">Blk ${pin.block} Lot ${pin.lot}</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px;">${pin.host}</div>
                    </td>
                    <td style="font-size: 13px; color: #475569;">${pin.purpose}</td>
                    <td><span class="badge badge-warning" style="padding: 4px 12px; border-radius: 20px; background:#fef3c7; color:#b45309;">Approved (PIN: ${pin.pin})</span></td>
                `;
                tbody.insertBefore(tr, tbody.firstChild);
            });
        }
    });

    function validatePin(e) {
        e.preventDefault();
        const pin = Array.from(digits).map(d => d.value).join('');
        const errBox = document.getElementById('pinErrorBox');
        
        errBox.style.display = 'none';

        if (pin.length !== 6) {
            errBox.style.display = 'block';
            errBox.textContent = '❌ PLEASE ENTER A 6-DIGIT PIN';
            return;
        }

        let record = null;
        let recordType = 'Visitor';

        // 1. Search SubdivisionStore for visitor PINs
        const storeVisitors = SubdivisionStore.getVisitors();
        const storeRecord = storeVisitors.find(p => p.pin === pin && p.status !== 'Expired');
        if (storeRecord) {
            record = storeRecord;
            recordType = 'Visitor';
        }

        // 2. Search mock visitor database
        if (!record) {
            const visitorRecord = mockDatabase.find(p => p.pin === pin && p.status !== 'Expired');
            if (visitorRecord) {
                record = visitorRecord;
                recordType = 'Visitor';
            }
        }

        // 3. Search appointment pins from localStorage
        if (!record) {
            let apptPins = JSON.parse(localStorage.getItem('appointmentPins') || '[]');
            const apptRecord = apptPins.find(p => p.pin === pin && p.status !== 'Expired');
            if (apptRecord) {
                record = {
                    id: apptRecord.id,
                    visitor: apptRecord.client,
                    block: 'N/A',
                    lot: 'N/A',
                    host: 'Sales Office (Property Viewing)',
                    purpose: 'Scheduled Appointment'
                };
                recordType = 'Appointment';
            }
        }

        // 4. Search Resident PINs
        if (!record && mockUsers) {
            const resident = mockUsers.find(u => u.pin === pin && u.role === 'Resident');
            if (resident) {
                const parts = resident.meta.split(', ');
                let block = 'N/A', lot = 'N/A';
                if (parts.length === 2) {
                    block = parts[0].replace('Block ', '');
                    lot = parts[1].replace('Lot ', '');
                }
                record = {
                    id: resident.id,
                    visitor: resident.name,
                    block: block,
                    lot: lot,
                    host: 'Resident Self-Entry',
                    purpose: 'Returning Resident'
                };
                recordType = 'Resident';
            }
        }

        if (record) {
            // Success! Render GIS and Details
            renderSuccess(record, recordType);
        } else {
            // Failed
            errBox.style.display = 'block';
            errBox.textContent = '❌ ACCESS DENIED: INVALID OR EXPIRED PIN';
            digits.forEach(d => {
                d.style.borderColor = '#ef4444';
                d.style.color = '#ef4444';
                d.style.background = '#fef2f2';
                setTimeout(() => { 
                    d.style.borderColor = ''; 
                    d.style.color = ''; 
                    d.style.background = ''; 
                }, 1500);
            });
        }
    }

    function renderSuccess(record, type = 'Visitor') {
        const resultCard = document.getElementById('authResultCard');
        resultCard.style.padding = '0';
        resultCard.style.border = 'none';
        resultCard.style.background = '#fff';
        
        let headerColor = '#10b981'; // green for visitor
        if (type === 'Resident') headerColor = '#3b82f6'; // blue for resident
        if (type === 'Appointment') headerColor = '#8b5cf6'; // purple for appointment

        resultCard.innerHTML = `
            <div style="background: ${headerColor}; color: white; padding: 16px 24px; border-radius: 20px 20px 0 0; width: 100%; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 16px;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                    ${type.toUpperCase()} PIN VALIDATED
                </div>
                <div style="font-size: 12px; font-family: monospace; background: rgba(0,0,0,0.15); padding: 4px 10px; border-radius: 12px;">ID: ${record.id}</div>
            </div>

            <div style="padding: 24px; width: 100%;">
                <div style="margin-bottom: 20px;">
                    <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">${type === 'Resident' ? 'Resident Name' : 'Visitor'}</div>
                    <div style="font-size: 20px; font-weight: 800; color: #0f172a;">${record.visitor}</div>
                </div>

                <div style="background: var(--guard-primary-soft); padding: 16px; border-radius: 12px; border: 1px solid #dbeafe; margin-bottom: 20px;">
                    <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Destination</div>
                    <div style="font-size: 14px; font-weight: 800; color: #1e3a8a;">Blk ${record.block} Lot ${record.lot} - ${record.host}</div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; display: block; margin-bottom: 8px;">Vehicle Plate Number (Optional)</label>
                    <input type="text" id="plateNo" class="filter-select" style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px;" placeholder="e.g. ABC 1234">
                </div>

                <button class="btn btn-primary" style="width: 100%; justify-content: center; background: ${headerColor}; border-color: ${headerColor}; padding: 14px;" onclick="markEntered('${record.id}')">
                    Approve Entry
                </button>
            </div>
        `;
    }

    function markEntered(id) {
        const plate = document.getElementById('plateNo')?.value || 'N/A';
        const now = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        SubdivisionStore.updateVisitorStatus(id, 'Entered');
        alert(`Visitor approved! Plate: ${plate}. Entry logged at ${now}.`);
        location.reload();
    }

    function openWalkInModal() {
        if (new URLSearchParams(window.location.search).get('action') !== 'walkin') {
            window.history.pushState(null, '', '?action=walkin');
        }
        document.getElementById('walkinModal').style.display = 'flex';
    }

    function closeWalkInModal() {
        window.history.replaceState(null, '', window.location.pathname);
        document.getElementById('walkinModal').style.display = 'none';
    }

    function submitWalkIn(e) {
        e.preventDefault();
        const data = {
            name: document.getElementById('wName').value,
            purpose: document.getElementById('wPurpose').value,
            visitor_address: document.getElementById('wOrigin').value || 'N/A',
            plate_number: document.getElementById('wPlate').value || 'N/A'
        };

        fetch('/simulate/walk-in', {
            method: 'POST',
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        }).then(() => {
            alert(`Walk-in recorded for ${data.name}.`);
            location.reload();
        });
    }

</script>

<style>
    @media (max-width: 900px) {
        div[style*="display: grid; grid-template-columns: 1fr 1fr"] { grid-template-columns: 1fr !important; }
    }
    @media (max-width: 480px) {
        .guard-pin-digit { width: 45px; height: 55px; font-size: 24px; }
        .guard-pin-digits { gap: 8px; }
    }
</style>
<!-- Walk-in Registration Modal -->
<div id="walkinModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div class="analytic-card" style="width:100%; max-width:500px; padding:0; overflow:hidden;">
        <div style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="font-size:18px; font-weight:800; color:#0f172a; margin:0;">Walk-in Registration</h3>
            <button onclick="closeWalkInModal()" style="background:none; border:none; font-size:24px; color:#94a3b8; cursor:pointer;">&times;</button>
        </div>
        <form onsubmit="submitWalkIn(event)" style="padding:24px; display:flex; flex-direction:column; gap:16px;">
            <div>
                <label style="font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; display:block; margin-bottom:6px;">Visitor Full Name</label>
                <input type="text" id="wName" required class="filter-select" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px;">
            </div>
            <div>
                <label style="font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; display:block; margin-bottom:6px;">Purpose of Visit</label>
                <input type="text" id="wPurpose" required class="filter-select" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px;" placeholder="e.g. Delivery, Guest, Maintenance">
            </div>
            <div>
                <label style="font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; display:block; margin-bottom:6px;">Visitor's Home Address (Optional)</label>
                <input type="text" id="wOrigin" class="filter-select" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px;" placeholder="Visitor's home address">
            </div>
            <div>
                <label style="font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; display:block; margin-bottom:6px;">Vehicle Plate Number (Optional)</label>
                <input type="text" id="wPlate" class="filter-select" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px;" placeholder="ABC 1234">
            </div>
            <div style="margin-top:8px; display:flex; gap:12px;">
                <button type="button" onclick="closeWalkInModal()" class="btn btn-outline" style="flex:1; justify-content:center;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center; background:var(--guard-primary);">Register Entry</button>
            </div>
        </form>
    </div>
</div>
@endsection
