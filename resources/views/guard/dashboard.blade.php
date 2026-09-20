@extends('layouts.guard')

@section('title', 'Gate Scanner Operations')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">
<link rel="stylesheet" href="{{ asset('css/views/guard-dashboard.css') }}">

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

            <form class="guard-pin-form" id="guardPinForm" onsubmit="event.preventDefault(); validatePin(event);">
                <div class="guard-pin-digits" style="display: flex; justify-content: center; gap: 8px;">
                    <input type="text" id="pin-input" class="guard-pin-digit" maxlength="6" inputmode="numeric" autofocus style="width: 240px; text-align: center; letter-spacing: 8px; font-size: 24px; padding: 12px; font-weight: 700;">
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
            <span style="font-size: 13px; color: #64748b; font-weight: 600;" id="totalVisitorsCount">Total: 0</span>
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
                <tbody id="guardVisitorTableBody">
                    <!-- Populated by JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('js/dijkstra.js') }}"></script>
<script src="{{ asset('js/gis-map.js') }}"></script>
<script>
    // Pin input behavior
    const pinInput = document.getElementById('pin-input');
    const guardPinForm = document.getElementById('guardPinForm');

    pinInput.addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/[^0-9]/g, '');
        if (e.target.value.length === 6) {
            validatePin(new Event('submit', { cancelable: true, bubbles: true }));
        }
    });

    // Fetch and render daily log
    function loadDailyLog() {
        fetch('/api/visitors')
        .then(res => res.json())
        .then(visitors => {
            const tbody = document.getElementById('guardVisitorTableBody');
            if (!tbody) return;
            tbody.innerHTML = '';
            
            let count = 0;

            visitors.forEach(pin => {
                count++;
                const tr = document.createElement('tr');
                let badgeClass = '';
                if(pin.status === 'Entered') badgeClass = 'badge-success';
                if(pin.status === 'Pending' || pin.status === 'Awaiting Admin Approval') badgeClass = 'badge-warning';

                let arrivalHtml = '';
                if(pin.status === 'Entered' && pin.arrival_time) {
                    arrivalHtml = `<div style="font-size: 11px; color: #10b981; margin-top: 4px; font-weight: 700;">Entered: ${pin.arrival_time}</div>`;
                }

                tr.innerHTML = `
                    <td style="font-family: monospace; font-size: 12px; font-weight: 700; color: #64748b;">${pin.id}</td>
                    <td>
                        <div style="font-weight: 700; color: #0f172a;">${pin.visitor}</div>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 4px; font-weight: 500;">
                            ${pin.type === 'Walk-in' ? 'Address: ' + (pin.visitor_address || 'N/A') : 'Plate: ' + (pin.plate_number || 'N/A')}
                        </div>
                    </td>
                    <td>
                        <div style="background: var(--guard-primary-soft); color: var(--guard-primary); padding: 4px 10px; border-radius: 6px; display: inline-block; font-weight: 700; font-size: 12px;">Blk ${pin.block} Lot ${pin.lot}</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px;">${pin.host}</div>
                    </td>
                    <td style="font-size: 13px; color: #475569;">${pin.purpose}</td>
                    <td>
                        <span class="badge ${badgeClass}" style="padding: 4px 12px; border-radius: 20px;">${pin.status}</span>
                        ${arrivalHtml}
                    </td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('totalVisitorsCount').textContent = `Total: ${count}`;
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadDailyLog();
    });

    function validatePin(e) {
        if (e && e.preventDefault) e.preventDefault();
        const pin = document.getElementById('pin-input').value;
        const errBox = document.getElementById('pinErrorBox');
        const pInput = document.getElementById('pin-input');
        
        errBox.style.display = 'none';

        if (pin.length !== 6) {
            errBox.style.display = 'block';
            errBox.textContent = '❌ PLEASE ENTER A 6-DIGIT PIN';
            pInput.style.borderColor = '#ef4444';
            pInput.style.color = '#ef4444';
            pInput.style.background = '#fef2f2';
            setTimeout(() => { 
                pInput.style.borderColor = ''; 
                pInput.style.color = ''; 
                pInput.style.background = ''; 
            }, 1500);
            return;
        }

        fetch('/api/validate-pin', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ pin: pin })
        })
        .then(res => {
            if (!res.ok) throw new Error("Server Error");
            return res.json();
        })
        .then(data => {
            if (data.success) {
                // Map API response to renderSuccess format
                let block = 'N/A';
                let lot = 'N/A';
                let host = data.destination;
                
                if (data.destination && data.destination.includes('Block')) {
                    const parts = data.destination.split(', ');
                    if(parts.length === 2) {
                        block = parts[0].replace('Block ', '');
                        lot = parts[1].replace('Lot ', '');
                    }
                }
                
                let record = {
                    id: data.visitor_id,
                    db_id: data.db_id,
                    visitor: data.visitor_name,
                    block: block,
                    lot: lot,
                    host: host
                };

                // Automatically mark as entered
                fetch('/api/visitors/' + data.db_id + '/enter', {
                    method: 'PUT',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ plate_number: 'N/A' })
                }).then(() => {
                    renderSuccess(record, 'Visitor');
                    loadDailyLog(); // Refresh table immediately
                });
            } else {
                errBox.style.display = 'block';
                errBox.textContent = '❌ ' + (data.message || 'INVALID OR EXPIRED PIN');
                pInput.style.borderColor = '#ef4444';
                pInput.style.color = '#ef4444';
                pInput.style.background = '#fef2f2';
                setTimeout(() => { 
                    pInput.style.borderColor = ''; 
                    pInput.style.color = ''; 
                    pInput.style.background = ''; 
                }, 1500);
            }
        }).catch(err => {
            errBox.style.display = 'block';
            errBox.textContent = '❌ SERVER ERROR';
            console.error(err);
        });
    }

    function renderSuccess(record, type = 'Visitor') {
        const resultCard = document.getElementById('authResultCard');
        resultCard.onclick = null; // Remove the walk-in modal click
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
                    VISIT RECORDED & VALIDATED
                </div>
                <div style="font-size: 12px; font-family: monospace; background: rgba(0,0,0,0.15); padding: 4px 10px; border-radius: 12px;">ID: ${record.id}</div>
            </div>

            <div style="padding: 24px; width: 100%;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">${type === 'Resident' ? 'Resident Name' : 'Visitor'}</div>
                        <div style="font-size: 18px; font-weight: 800; color: #0f172a;">${record.visitor}</div>
                    </div>
                    <div style="background: var(--guard-primary-soft); padding: 12px; border-radius: 12px; border: 1px solid #dbeafe;">
                        <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Destination</div>
                        <div style="font-size: 13px; font-weight: 800; color: #1e3a8a;">Blk ${record.block} Lot ${record.lot}</div>
                    </div>
                </div>

                <div id="guardMapContainer" style="width: 100%; height: 250px; border-radius: 12px; margin-bottom: 20px; background: #f1f5f9; overflow: hidden; border: 1px solid #cbd5e1;"></div>

                <div style="margin-bottom: 20px;">
                    <label style="font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; display: block; margin-bottom: 8px;">Vehicle Plate Number (Optional)</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="plateNo" class="filter-select" style="flex: 1; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px;" placeholder="e.g. ABC 1234">
                        <button class="btn btn-secondary" onclick="updatePlate('${record.db_id || record.id}')" style="padding: 12px 20px; font-weight: 700; border-radius: 8px;">Save</button>
                    </div>
                </div>

                <button class="btn btn-primary" style="width: 100%; justify-content: center; background: ${headerColor}; border-color: ${headerColor}; padding: 14px;" onclick="location.reload()">
                    Next Visitor
                </button>
            </div>
        `;

        setTimeout(() => {
            initGISMap('guardMapContainer', {
                interactive: true,
                showRouting: true,
                endNode: record.block !== 'N/A' && record.lot !== 'N/A' ? `B${record.block} L${record.lot}` : null
            });
        }, 100);
    }

    function updatePlate(id) {
        const plate = document.getElementById('plateNo')?.value || 'N/A';
        fetch('/api/visitors/' + id + '/enter', {
            method: 'PUT',
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ plate_number: plate })
        }).then(res => res.json()).then(data => {
            if (data.success) {
                alert('Plate number updated!');
            }
        });
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

        fetch('/api/visitors', {
            method: 'POST',
            headers: { 
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                visitor_name: data.name,
                purpose: data.purpose,
                plate_number: data.plate_number,
                visitor_address: data.visitor_address,
                type: 'Walk-in',
                validity: 'Today'
            })
        }).then(res => res.json()).then(response => {
            if(response.success) {
                alert(`Walk-in recorded for ${data.name}.`);
                location.reload();
            } else {
                alert('Failed to record walk-in.');
            }
        });
    }

</script>


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
