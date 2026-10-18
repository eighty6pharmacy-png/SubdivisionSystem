@extends('layouts.guard')

@section('title', 'Gate Scanner Operations')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">
<link rel="stylesheet" href="{{ asset('css/views/guard-dashboard.css') }}">

<style>
    .responsive-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }
    @media (max-width: 768px) {
        .responsive-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
<div class="fade-in">

    @php
        $currentGuardAccount = auth()->user()?->guardAccount;
        $guardOnDuty = $currentGuardAccount ? $currentGuardAccount->isOnDuty() : false;
        $guardSchedule = $currentGuardAccount ? $currentGuardAccount->formatted_schedule : 'No Schedule Set';
    @endphp
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 14px 20px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 36px; height: 36px; border-radius: 10px; background: {{ $guardOnDuty ? '#dcfce7' : '#f1f5f9' }}; color: {{ $guardOnDuty ? '#15803d' : '#64748b' }}; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                {{ $guardOnDuty ? '🛡️' : '⏳' }}
            </div>
            <div>
                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Duty Schedule & Emergency Station</div>
                <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-top: 1px;">{{ $guardSchedule }}</div>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            @if($guardOnDuty)
                <span style="font-size: 11px; font-weight: 800; background: #dcfce7; color: #15803d; padding: 6px 12px; border-radius: 20px; border: 1px solid #bbf7d0; display: inline-flex; align-items: center; gap: 6px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #22c55e; display: inline-block;"></span>
                    Active on Duty (Resident Hotline Active)
                </span>
            @else
                <span style="font-size: 11px; font-weight: 700; background: #f1f5f9; color: #64748b; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 6px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #94a3b8; display: inline-block;"></span>
                    Off Duty
                </span>
            @endif
        </div>
    </div>

    <div class="responsive-grid">

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

        <!-- Right Column: Operations Panel -->
        <div id="rightColContainer">
            <div id="walkinTriggerCard" class="analytic-card walkin-card" onclick="openWalkInModal()" style="padding: 32px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; height: 100%;">
                <div style="width: 56px; height: 56px; background: #dbeafe; color: #1e40af; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                    <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
                </div>
                <h3 style="font-size: 18px; font-weight: 800; color: #1e3a8a; margin: 0;">Record Walk-in Entry</h3>
                <p style="font-size: 13px; color: #64748b; margin-top: 8px;">For visitors without a generated PIN code.</p>
                <button type="button" class="btn btn-outline" style="margin-top: 16px; font-weight: 700; pointer-events: none;">Open Walk-in Form</button>
            </div>

            <div id="authResultCard" class="analytic-card" style="display: none; padding: 0; overflow: hidden;"></div>
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
                        <th>Address (Walk-in)</th>
                        <th>Plate Number</th>
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
<script src="https://unpkg.com/leaflet-rotate@0.2.8/dist/leaflet-rotate.js"></script>
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
        .then(res => {
            if (!res.ok) throw new Error('Failed to fetch visitors');
            return res.json();
        })
        .then(visitors => {
            const tbody = document.getElementById('guardVisitorTableBody');
            if (!tbody) return;
            tbody.innerHTML = '';
            
            const todayStr = new Date().toISOString().split('T')[0];

            // Filter for visitors relevant to today:
            // 1. Entered today
            // 2. Pre-registered/Approved with validity today
            // 3. Or validity marked 'Today'
            const todaysVisitors = visitors.filter(pin => {
                if (pin.status === 'Entered' && pin.date === todayStr) return true;
                if (pin.validity === 'Today') return true;
                if (pin.date === todayStr) return true;
                if (pin.status === 'Approved' && (!pin.validity || pin.validity === 'Today' || pin.validity.includes(todayStr))) return true;
                return false;
            });

            if (todaysVisitors.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 32px; color: #94a3b8; font-weight: 600;">No expected or logged visitors for today.</td></tr>`;
                document.getElementById('totalVisitorsCount').textContent = 'Today: 0';
                return;
            }

            todaysVisitors.forEach(pin => {
                let badgeClass = 'badge-secondary';
                if (pin.status === 'Entered') badgeClass = 'badge-success';
                else if (pin.status === 'Approved') badgeClass = 'badge-info';
                else if (pin.status === 'Pending' || pin.status === 'Awaiting Admin Approval') badgeClass = 'badge-warning';
                else if (pin.status === 'Rejected') badgeClass = 'badge-danger';

                let arrivalHtml = '';
                if (pin.status === 'Entered' && pin.arrival_time) {
                    arrivalHtml = `<div style="font-size: 11px; color: #10b981; margin-top: 4px; font-weight: 700;">Entered: ${pin.arrival_time}</div>`;
                }

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="font-family: monospace; font-size: 12px; font-weight: 700; color: #64748b;">${pin.id}</td>
                    <td>
                        <div style="font-weight: 700; color: #0f172a;">${pin.visitor}</div>
                        ${pin.contact_number ? `<div style="font-size: 11px; color: #0284c7; font-weight: 600;">📞 ${pin.contact_number}</div>` : ''}
                        <div style="font-size: 11px; font-weight: 800; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px; background: ${pin.type === 'Walk-in' ? '#eff6ff; color: #2563eb;' : '#f0fdf4; color: #16a34a;'}">
                            ${pin.type || 'PIN Auth'}
                        </div>
                    </td>
                    <td>
                        <div style="font-size: 12px; color: #64748b; font-weight: 500;">
                            ${pin.type === 'Walk-in' ? (pin.visitor_address || 'N/A') : (pin.visitor_address || 'N/A')}
                        </div>
                    </td>
                    <td>
                        <div style="font-family: monospace; font-weight: 700; background: #f8fafc; padding: 4px 8px; border: 1px solid #e2e8f0; border-radius: 4px; display: inline-block;">
                            ${pin.plate_number || 'N/A'}
                        </div>
                    </td>
                    <td>
                        <div style="background: var(--guard-primary-soft); color: var(--guard-primary); padding: 4px 10px; border-radius: 6px; display: inline-block; font-weight: 700; font-size: 12px;">
                            ${(pin.block !== 'N/A' && pin.lot !== 'N/A') ? `Blk ${pin.block} Lot ${pin.lot}` : (pin.host || 'General')}
                        </div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Host: ${pin.host || 'N/A'}</div>
                    </td>
                    <td style="font-size: 13px; color: #475569;">${pin.purpose}</td>
                    <td>
                        <span class="badge ${badgeClass}" style="padding: 4px 12px; border-radius: 20px;">${pin.status}</span>
                        ${arrivalHtml}
                    </td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('totalVisitorsCount').textContent = `Today: ${todaysVisitors.length}`;
        })
        .catch(err => {
            console.error('Failed to load daily log:', err);
            const tbody = document.getElementById('guardVisitorTableBody');
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px; color: #ef4444; font-weight: 600;">Failed to load daily visitor log. Check connection.</td></tr>`;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadDailyLog();
        if (new URLSearchParams(window.location.search).get('action') === 'walkin') {
            openWalkInModal();
        }
    });

    function validatePin(e) {
        if (e && e.preventDefault) e.preventDefault();
        const pInput = document.getElementById('pin-input');
        const pin = pInput.value.trim();
        const errBox = document.getElementById('pinErrorBox');
        
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

        const verifyBtn = document.getElementById('verifyBtn');
        const origBtnText = verifyBtn.innerHTML;
        verifyBtn.disabled = true;
        verifyBtn.innerHTML = 'Validating...';

        fetch('/api/validate-pin', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ pin: pin, source: 'guard' })
        })
        .then(res => {
            if (!res.ok) throw new Error("Server Error");
            return res.json();
        })
        .then(data => {
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = origBtnText;

            if (data.success) {
                let record = {
                    id: data.visitor_id,
                    db_id: data.db_id,
                    visitor: data.visitor_name,
                    host: data.host_name || 'Resident Host',
                    block: data.block || 'N/A',
                    lot: data.lot || 'N/A',
                    destination: data.destination || 'N/A',
                    status: data.status,
                    arrival_time: data.arrival_time,
                    plate_number: data.plate_number || '',
                    is_appointment: data.is_appointment,
                    appointment_type: data.appointment_type,
                    appointment_time: data.appointment_time
                };

                // If already marked entered, do not overwrite arrival timestamp
                if (data.status === 'Entered') {
                    renderSuccess(record, data.is_appointment ? 'Appointment' : 'Visitor', true);
                    return;
                }

                // If first time entering, mark entered
                let enterUrl = '/api/visitors/' + data.db_id + '/enter';
                let bodyData = { plate_number: 'N/A' };
                
                if (data.is_appointment) {
                    enterUrl = '/api/appointments/' + (data.appointment_id || data.db_id) + '/status';
                    bodyData = { status: 'Completed', report: 'Arrived at Gate' };
                }

                fetch(enterUrl, {
                    method: 'PUT',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify(bodyData)
                })
                .then(res => res.json())
                .then(enterRes => {
                    if (data.is_appointment && enterRes.visitor_id) {
                        record.db_id = enterRes.visitor_id;
                        record.id = enterRes.visitor_id;
                    }
                    if (enterRes.arrival_time) {
                        record.arrival_time = enterRes.arrival_time;
                    }
                    record.status = 'Entered';
                    renderSuccess(record, data.is_appointment ? 'Appointment' : 'Visitor', false);
                    loadDailyLog();
                })
                .catch(err => {
                    console.error('Error marking entered:', err);
                    renderSuccess(record, data.is_appointment ? 'Appointment' : 'Visitor', false);
                    loadDailyLog();
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
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = origBtnText;
            errBox.style.display = 'block';
            errBox.textContent = '❌ SERVER ERROR';
            console.error(err);
        });
    }

    function renderSuccess(record, type = 'Visitor', isAlreadyEntered = false) {
        const walkinCard = document.getElementById('walkinTriggerCard');
        const resultCard = document.getElementById('authResultCard');
        
        if (walkinCard) walkinCard.style.display = 'none';
        if (resultCard) resultCard.style.display = 'block';

        let headerColor = '#10b981'; // green for visitor
        if (type === 'Resident') headerColor = '#3b82f6';
        if (type === 'Appointment') headerColor = '#8b5cf6';

        const statusNote = isAlreadyEntered 
            ? `(Previously Entered at ${record.arrival_time || 'Earlier'})` 
            : `(Entry Verified Just Now)`;

        resultCard.innerHTML = `
            <div style="background: ${headerColor}; color: white; padding: 16px 24px; border-radius: 16px 16px 0 0; width: 100%; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 15px;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                    <span>VISIT VERIFIED ${statusNote}</span>
                </div>
                <div style="font-size: 12px; font-family: monospace; background: rgba(0,0,0,0.2); padding: 4px 10px; border-radius: 12px;">ID: ${record.id}</div>
            </div>

            <div style="padding: 24px; width: 100%;">
                <div class="responsive-grid" style="margin-bottom: 20px;">
                    <div>
                        <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">${type === 'Resident' ? 'Resident Name' : 'Visitor Name'}</div>
                        <div style="font-size: 18px; font-weight: 800; color: #0f172a;">${record.visitor}</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Host: <strong>${record.host}</strong></div>
                    </div>
                    <div style="background: var(--guard-primary-soft); padding: 12px; border-radius: 12px; border: 1px solid #dbeafe;">
                        <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Destination</div>
                        <div style="font-size: 14px; font-weight: 800; color: #1e3a8a;">
                            ${(record.block !== 'N/A' && record.lot !== 'N/A') ? `Block ${record.block}, Lot ${record.lot}` : record.destination}
                        </div>
                    </div>
                </div>

                <div id="mapWrapper" style="display: ${type === 'Appointment' ? 'none' : 'block'}; position: relative; margin-bottom: 20px;">
                    <div id="guardMapContainer" style="width: 100%; height: 250px; border-radius: 12px; background: #f1f5f9; overflow: hidden; border: 1px solid #cbd5e1; z-index: 1;"></div>
                    <button type="button" onclick="toggleFullScreenMap()" style="position: absolute; top: 10px; right: 10px; z-index: 1000; background: white; border: none; padding: 8px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); cursor: pointer; display: flex; align-items: center; justify-content: center;">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                    </button>
                </div>

                <div id="appointmentWrapper" style="display: ${type === 'Appointment' ? 'block' : 'none'}; background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                    <div style="font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 8px;">Appointment Details</div>
                    <div style="font-size: 13px; color: #0f172a;">
                        <strong>Type:</strong> ${record.appointment_type || 'General'} <br>
                        <strong>Time:</strong> ${record.appointment_time || 'N/A'}
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; display: block; margin-bottom: 8px;">Vehicle Plate Number (Optional)</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="plateNo" class="filter-select" style="flex: 1; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px;" placeholder="e.g. ABC 1234" value="${(record.plate_number && record.plate_number !== 'N/A') ? record.plate_number : ''}">
                        <button type="button" id="savePlateBtn" class="btn btn-secondary" onclick="updatePlate('${record.db_id}')" style="padding: 12px 20px; font-weight: 700; border-radius: 8px;">Save</button>
                    </div>
                </div>

                <button type="button" class="btn btn-primary" style="width: 100%; justify-content: center; background: ${headerColor}; border-color: ${headerColor}; padding: 14px; font-weight: 700;" onclick="resetScannerView()">
                    Done / Next Visitor
                </button>
            </div>
        `;

        // Safely initialize GIS map with routing
        setTimeout(() => {
            if (type !== 'Appointment') {
                const container = document.getElementById('guardMapContainer');
                if (container) {
                    if (window.activeGuardMap) {
                        try { window.activeGuardMap.remove(); } catch(e) {}
                        window.activeGuardMap = null;
                    }
                    if (container._leaflet_id) {
                        container._leaflet_id = null;
                    }
                    const targetNode = (record.block !== 'N/A' && record.lot !== 'N/A') ? `B${record.block} L${record.lot}` : null;
                    initGISMap('guardMapContainer', {
                        interactive: true,
                        showRouting: !!targetNode,
                        endNode: targetNode
                    }).then(mapInstance => {
                        window.activeGuardMap = mapInstance;
                    }).catch(err => {
                        console.warn('GIS routing init notice:', err);
                    });
                }
            }
        }, 120);
    }

    function resetScannerView() {
        if (window.activeGuardMap) {
            try { window.activeGuardMap.remove(); } catch(e) {}
            window.activeGuardMap = null;
        }
        const resultCard = document.getElementById('authResultCard');
        const walkinCard = document.getElementById('walkinTriggerCard');
        if (resultCard) {
            resultCard.style.display = 'none';
            resultCard.innerHTML = '';
        }
        if (walkinCard) {
            walkinCard.style.display = 'flex';
        }
        const pinInput = document.getElementById('pin-input');
        if (pinInput) {
            pinInput.value = '';
            pinInput.focus();
        }
        const errBox = document.getElementById('pinErrorBox');
        if (errBox) errBox.style.display = 'none';
    }

    function toggleFullScreenMap() {
        const wrapper = document.getElementById('mapWrapper');
        if (!document.fullscreenElement) {
            if (wrapper.requestFullscreen) {
                wrapper.requestFullscreen();
            } else if (wrapper.webkitRequestFullscreen) { /* Safari */
                wrapper.webkitRequestFullscreen();
            } else if (wrapper.msRequestFullscreen) { /* IE11 */
                wrapper.msRequestFullscreen();
            }
            
            const container = document.getElementById('guardMapContainer');
            if (container) {
                container.style.height = '100vh';
                container.style.borderRadius = '0';
                container.style.border = 'none';
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) { /* Safari */
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) { /* IE11 */
                document.msExitFullscreen();
            }
            
            const container = document.getElementById('guardMapContainer');
            if (container) {
                container.style.height = '250px';
                container.style.borderRadius = '12px';
                container.style.border = '1px solid #cbd5e1';
            }
        }
        
        // Let leaflet recalculate its size
        setTimeout(() => {
            window.dispatchEvent(new Event('resize'));
        }, 200);
    }
    
    document.addEventListener('fullscreenchange', () => {
        const container = document.getElementById('guardMapContainer');
        if (container) {
            if (!document.fullscreenElement) {
                container.style.height = '250px';
                container.style.borderRadius = '12px';
                container.style.border = '1px solid #cbd5e1';
            } else {
                container.style.height = '100vh';
                container.style.borderRadius = '0';
                container.style.border = 'none';
            }
        }
    });

    function updatePlate(id) {
        const plate = document.getElementById('plateNo')?.value?.trim() || 'N/A';
        const btn = document.getElementById('savePlateBtn');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Saving...';
        }

        fetch('/api/visitors/' + id + '/enter', {
            method: 'PUT',
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ plate_number: plate })
        })
        .then(res => res.json())
        .then(data => {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Save';
            }
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Plate Number Updated',
                    text: `Vehicle plate recorded as ${plate}.`,
                    timer: 2000,
                    showConfirmButton: false
                });
                loadDailyLog();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    text: data.message || 'Could not update plate number.'
                });
            }
        })
        .catch(err => {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Save';
            }
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Server communication error.'
            });
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
        const btn = e.target.querySelector('button[type="submit"]');
        const origText = btn ? btn.innerText : 'Register Entry';
        if (btn) { btn.disabled = true; btn.innerText = 'Registering...'; }

        const data = {
            name: document.getElementById('wName').value.trim(),
            purpose: document.getElementById('wPurpose').value.trim(),
            destination: document.getElementById('wDestination')?.value?.trim() || 'Main Gate Walk-in',
            visitor_address: document.getElementById('wOrigin')?.value?.trim() || 'N/A',
            plate_number: document.getElementById('wPlate')?.value?.trim() || 'N/A'
        };

        fetch('/api/visitors', {
            method: 'POST',
            headers: { 
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                visitor_name: data.name,
                host_name: data.destination,
                purpose: data.purpose,
                plate_number: data.plate_number,
                visitor_address: data.visitor_address,
                type: 'Walk-in',
                validity: 'Today'
            })
        })
        .then(res => res.json())
        .then(response => {
            if (btn) { btn.disabled = false; btn.innerText = origText; }
            if (response.success) {
                closeWalkInModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Walk-in Recorded',
                    text: `Walk-in entry logged for ${data.name}.`,
                    timer: 2000,
                    showConfirmButton: false
                });
                document.getElementById('wName').value = '';
                document.getElementById('wPurpose').value = '';
                if (document.getElementById('wDestination')) document.getElementById('wDestination').value = '';
                document.getElementById('wOrigin').value = '';
                document.getElementById('wPlate').value = '';
                loadDailyLog();
            } else {
                Swal.fire('Failed', 'Could not record walk-in entry.', 'error');
            }
        })
        .catch(err => {
            if (btn) { btn.disabled = false; btn.innerText = origText; }
            Swal.fire('Error', 'Server connection error.', 'error');
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
                <label style="font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; display:block; margin-bottom:6px;">Visiting Resident / Destination (Block & Lot)</label>
                <input type="text" id="wDestination" class="filter-select" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px;" placeholder="e.g. Block 3 Lot 5 / Maria Santos">
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
