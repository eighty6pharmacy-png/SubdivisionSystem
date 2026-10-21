@extends('layouts.admin')

@section('title', 'GIS Property Mapping')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">
    <link rel="stylesheet" href="{{ asset('css/views/admin-gis.css') }}">
    <style>
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>

    <div class="gis-wrapper fade-in" id="gisMain">
        <!-- Left Sidebar -->
        <aside class="gis-sidebar-left" style="background: #f8fafc; color: #0f172a; border-right: 1px solid #e2e8f0;">
            <h2 style="font-size: 16px; font-weight: 800; margin-bottom: 24px; color: var(--primary);">Navigation Layers
            </h2>

            <div style="margin-bottom: 32px;">
                <div class="layer-item active" onclick="setLayer('occupancy', this)">
                    <span style="font-size: 16px;">🏠</span>
                    <span>Occupancy Status</span>
                </div>
                <div class="layer-item" onclick="setLayer('electricity', this)">
                    <span style="font-size: 16px;">⚡</span>
                    <span>Electricity Status</span>
                </div>
                <div class="layer-item" onclick="setLayer('water', this)">
                    <span style="font-size: 16px;">💧</span>
                    <span>Water Status</span>
                </div>
            </div>

            <div
                style="background: #fff; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 16px;">
                <label
                    style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: 800; margin-bottom: 16px; display: block;">Map
                    Legend</label>

                <div id="legendOccupancy">
                    <div class="legend-item">
                        <div class="legend-box" style="background: #22c55e; border-color: #16a34a; color: #fff;">Lot</div>
                        <span>Occupied</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-box" style="background: #3b82f6; border-color: #2563eb;">Lot</div> <span>Vacant
                            House</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-box" style="background: #f59e0b; border-color: #d97706;">Lot</div> <span>Vacant
                            Lot</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-box" style="background: #8b5cf6; border-color: #7c3aed;">Lot</div>
                        <span>Reserved Lot</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-box" style="background: #ef4444; border-color: #dc2626;">Lot</div>
                        <span>Under Construction</span>
                    </div>
                </div>

                <div id="legendElectricity" style="display: none;">
                    <div class="legend-item">
                        <div class="legend-box" style="background: #10b981; border-color: #059669;">Lot</div> <span>Elec. Paid</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-box" style="background: #ef4444; border-color: #dc2626;">Lot</div> <span>Elec. Unpaid</span>
                    </div>
                </div>

                <div id="legendWater" style="display: none;">
                    <div class="legend-item">
                        <div class="legend-box" style="background: #0ea5e9; border-color: #0369a1;">Lot</div> <span>Water Paid</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-box" style="background: #f97316; border-color: #c2410c;">Lot</div> <span>Water Unpaid</span>
                    </div>
                </div>
            </div>


            <div style="background: #fff; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <label
                    style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: 800; margin-bottom: 16px; display: block;">Map
                    Controls</label>

                <div style="margin-bottom: 16px;">
                    <div
                        style="display: flex; justify-content: space-between; font-size: 11px; color: #64748b; margin-bottom: 6px;">
                        <span>Rotation</span>
                        <span id="rotVal">164°</span>
                    </div>
                    <input type="range" id="rotateZ" min="-180" max="180" value="164" oninput="updateMapTransform()"
                        style="width: 100%;">
                </div>

                <div style="margin-bottom: 20px;">
                    <div
                        style="display: flex; justify-content: space-between; font-size: 11px; color: #64748b; margin-bottom: 6px;">
                        <span>Manual Zoom</span>
                        <span id="zoomVal">18.0z</span>
                    </div>
                    <input type="range" id="zoom" min="16" max="21" step="0.1" value="18" oninput="updateMapTransform()"
                        style="width: 100%;">
                </div>

                <button class="btn btn-outline"
                    style="width: 100%; justify-content: center; padding: 10px; font-size: 12px; border-radius: 8px;"
                    onclick="resetMap()">Reset View</button>
                <p style="font-size: 10px; color: #94a3b8; margin-top: 12px; text-align: center;">Tip: Scroll mouse to zoom
                </p>
            </div>
        </aside>

        <!-- Main Viewport -->
        <main class="gis-viewport" id="viewport" style="position: relative;">
            <!-- Loading Overlay -->
            <div id="mapLoadingOverlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.8); z-index: 2000; display: flex; flex-direction: column; justify-content: center; align-items: center; backdrop-filter: blur(4px);">
                <div style="width: 40px; height: 40px; border: 4px solid #e2e8f0; border-top: 4px solid #334155; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <p style="margin-top: 16px; font-weight: 700; color: #64748b;">Loading Map Data...</p>
            </div>
            <div id="adminMapContainer" class="map-container"></div>
        </main>

        <!-- Right Sidebar -->
        <aside class="gis-sidebar-right" style="overflow-y: auto; max-height: 100%;">
            <div id="lotDetailsDefault">
                <h2 style="font-size: 18px; font-weight: 800; margin-bottom: 12px;">Property Details</h2>
                <div
                    style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px dashed #cbd5e1; text-align: center; color: #64748b; font-size: 13px;">
                    Click a property marker on the map to view info.
                </div>
            </div>

        <div id="lotDetailsPanel" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <h2 style="font-size: 24px; font-weight: 800; color: var(--primary); margin-bottom: 4px;" id="detailLotId">B1-L5</h2>
                    <p style="font-size: 12px; color: #64748b; font-weight: 600;">Subdivision Property Information</p>
                </div>
                <button onclick="closeDetails()" style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; transition: 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">✕</button>
            </div>

            <!-- Resident Info (Always Shown) -->
            <div class="info-card">
                <label style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: 800; margin-bottom: 12px; display: block;">Primary Resident</label>
                <div style="font-size: 18px; font-weight: 800; margin-bottom: 4px;" id="detailResident">Juan Dela Cruz</div>
                <div id="detailOccupancyBadge" class="status-badge">Occupied</div>
                
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0; display: flex; gap: 8px;">
                    <button id="btnEditResident" class="btn btn-outline" style="flex: 1; padding: 8px; font-size: 11px; border-color: var(--primary); color: var(--primary); justify-content: center;">
                        <span style="margin-right: 4px;">✎</span> Edit Resident
                    </button>
                    <button id="btnViewProfile" class="btn btn-outline" style="flex: 1; padding: 8px; font-size: 11px; border-color: #64748b; color: #64748b; justify-content: center;">
                        View Profile
                    </button>
                </div>
            </div>

            <!-- Dynamic Section: Occupancy Info -->
            <div id="panelOccupancyOnly">
                <div class="info-card" style="background: white; border-style: dashed;">
                    <label style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: 800; margin-bottom: 12px; display: block;">Property Status</label>
                    <button id="btnUpdateOccupancy" class="btn btn-primary" style="width: 100%; padding: 10px; font-size: 11px; justify-content: center; background: #334155; border: none;">
                        <span style="margin-right: 4px;">🔄</span> Update Occupancy Status
                    </button>
                    <div id="occupancyLockedMsg" style="display: none; padding: 10px; font-size: 11px; text-align: center; color: #64748b; background: #f8fafc; border-radius: 8px;">
                        <span style="margin-right: 4px;">🔒</span> Occupancy locked (Resident assigned)
                    </div>
                </div>
            </div>

            <!-- Lot Property Specifications & Titling Info -->
            <div class="info-card" style="background: #ffffff; border: 1px solid #e2e8f0; margin-top: 12px;">
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: 800; margin: 0;">Lot Specifications & Titling</label>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 12px; margin-bottom: 10px;">
                    <div style="background: #f8fafc; padding: 8px 10px; border-radius: 8px; grid-column: span 2;">
                        <div style="color: #64748b; font-size: 10px; font-weight: 700; text-transform: uppercase;">Total Contract Price (TCP)</div>
                        <div id="detailContractPrice" style="font-weight: 800; color: #0f172a; font-size: 15px; margin-top: 2px;">—</div>
                    </div>
                    <div style="background: #f8fafc; padding: 8px 10px; border-radius: 8px;">
                        <div style="color: #64748b; font-size: 10px; font-weight: 700; text-transform: uppercase;">Lot Area</div>
                        <div id="detailLotArea" style="font-weight: 700; color: #0f172a; margin-top: 2px;">—</div>
                    </div>
                    <div style="background: #f8fafc; padding: 8px 10px; border-radius: 8px;">
                        <div style="color: #64748b; font-size: 10px; font-weight: 700; text-transform: uppercase;">Floor Area</div>
                        <div id="detailFloorArea" style="font-weight: 700; color: #0f172a; margin-top: 2px;">—</div>
                    </div>
                    <div style="background: #f8fafc; padding: 8px 10px; border-radius: 8px;">
                        <div style="color: #64748b; font-size: 10px; font-weight: 700; text-transform: uppercase;">TCT No.</div>
                        <div id="detailTctNo" style="font-weight: 700; color: #0f172a; margin-top: 2px; word-break: break-all;">—</div>
                    </div>
                    <div style="background: #f8fafc; padding: 8px 10px; border-radius: 8px;">
                        <div style="color: #64748b; font-size: 10px; font-weight: 700; text-transform: uppercase;">PID</div>
                        <div id="detailPid" style="font-weight: 700; color: #0f172a; margin-top: 2px; word-break: break-all;">—</div>
                    </div>
                    <div style="background: #f8fafc; padding: 8px 10px; border-radius: 8px; grid-column: span 2;">
                        <div style="color: #64748b; font-size: 10px; font-weight: 700; text-transform: uppercase;">Tax Dec No.</div>
                        <div id="detailTaxDecNo" style="font-weight: 700; color: #0f172a; margin-top: 2px; word-break: break-all;">—</div>
                    </div>
                    <div style="background: #f8fafc; padding: 8px 10px; border-radius: 8px; grid-column: span 2; min-width: 0; overflow: hidden;">
                        <div style="color: #64748b; font-size: 10px; font-weight: 700; text-transform: uppercase;">Description</div>
                        <div id="detailDescription" style="font-weight: 600; color: #334155; margin-top: 2px; word-break: break-word; overflow-wrap: anywhere; white-space: pre-wrap; line-height: 1.45; max-height: 90px; overflow-y: auto;">—</div>
                    </div>
                </div>
                <div style="display: flex; gap: 8px; margin-top: 14px;">
                    <button id="btnEditLotDetails" class="btn btn-outline" style="flex: 1; padding: 10px 8px; font-size: 12px; justify-content: center; border-color: #3b82f6; color: #2563eb; font-weight: 700; border-radius: 10px; white-space: nowrap;">
                        <span style="margin-right: 4px;">✎</span> Edit Info
                    </button>
                    <button id="btnSampleComputation" class="btn" style="flex: 1; padding: 10px 8px; font-size: 12px; justify-content: center; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; border: none; font-weight: 700; border-radius: 10px; box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.25); cursor: pointer; transition: all 0.2s; white-space: nowrap;">
                        <span style="margin-right: 4px;">📊</span> Computation
                    </button>
                </div>
            </div>

            <!-- Dynamic Section: Billing Info -->
            <div id="panelBillingOnly" style="display: none;">
                <div class="info-card" id="panelBillingElecOnly" style="background: #f0fdf4; border-color: #bbf7d0;">
                    <label style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #166534; font-weight: 800; margin-bottom: 12px; display: block;">Electricity Status</label>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <span style="font-weight: 700; font-size: 13px;">Elec. Bill</span>
                        <div id="detailBillingBadge" class="status-badge badge-paid">Paid</div>
                    </div>
                </div>

                <div class="info-card" id="panelBillingWaterOnly" style="background: #f0f9ff; border-color: #bae6fd;">
                    <label style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #0369a1; font-weight: 800; margin-bottom: 12px; display: block;">Water Status</label>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <span style="font-weight: 700; font-size: 13px;">Water Bill</span>
                        <div id="detailWaterBadge" class="status-badge badge-paid">Paid</div>
                    </div>
                </div>
            </div>
        </div>
    </aside>
    </div>

    <!-- View User Modal -->
    <div id="viewUserModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 3000; background: rgba(15,23,42,0.6); backdrop-filter: blur(8px);">
        <div class="bill-modal-content" style="max-width: 450px; width: 90%; padding: 32px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div class="modal-header" style="padding: 0; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="display: flex; gap: 16px; align-items: center;">
                    <div id="viewUserInitials" style="width: 56px; height: 56px; border-radius: 16px; background: #e0e7ff; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #4338ca; font-size: 20px;">
                        J
                    </div>
                    <div>
                        <h2 id="viewUserName" style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0;">Name</h2>
                        <p id="viewUserRole" style="font-size: 13px; font-weight: 700; color: var(--primary); margin-top: 2px;">Role</p>
                    </div>
                </div>
                <button class="btn btn-outline" onclick="document.getElementById('viewUserModal').style.display='none'" style="border: none; padding: 4px; color: #64748b;">
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
                    <div>
                        <div style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Member Since</div>
                        <div id="viewUserJoined" style="font-size: 13px; font-weight: 600; color: #334155; margin-top: 4px;">Date</div>
                    </div>
                </div>
            </div>

            <button class="btn btn-outline" style="width: 100%; justify-content: center; padding: 12px; margin-top: 24px; border-radius: 12px; font-weight: 700;" onclick="document.getElementById('viewUserModal').style.display='none'">Close Profile</button>
        </div>
    </div>

    <!-- Occupancy Update Modal -->
    <div id="occupancyUpdateModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 3000;">
        <div class="bill-modal-content" style="max-width: 400px; padding: 24px; border-radius: 20px;">
            <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 16px; color: #0f172a;">Update Occupancy Status</h3>
            <input type="hidden" id="occUpdateBlock">
            <input type="hidden" id="occUpdateLot">
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">New Status</label>
                <select id="occUpdateStatus" class="filter-select" style="width: 100%; padding: 12px;">
                    <option value="Occupied">Occupied</option>
                    <option value="Vacant House">Vacant House</option>
                    <option value="Vacant Lot">Vacant Lot</option>
                    <option value="Reserved">Reserved</option>
                    <option value="Under Construction">Under Construction</option>
                </select>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button class="btn btn-outline" style="flex:1;" onclick="document.getElementById('occupancyUpdateModal').style.display='none'">Cancel</button>
                <button class="btn btn-primary" id="btnSaveOccupancy" style="flex:1;" onclick="updateOccupancyStatus()">Save Update</button>
            </div>
        </div>
    </div>

    <!-- Lot Property Info Update Modal -->
    <div id="lotDetailsUpdateModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 3000; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px);">
        <div class="bill-modal-content" style="max-width: 480px; width: 92%; padding: 28px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h3 id="lotModalTitle" style="font-size: 18px; font-weight: 800; margin: 0; color: #0f172a;">Edit Lot Property Info</h3>
                    <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">Update area measurements and titling identifiers for this lot.</p>
                </div>
                <button onclick="document.getElementById('lotDetailsUpdateModal').style.display='none'" style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer;">✕</button>
            </div>

            <input type="hidden" id="lotEditBlock">
            <input type="hidden" id="lotEditNumber">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">Lot Area (sqm)</label>
                    <input type="number" step="0.01" min="0" id="lotEditLotArea" placeholder="e.g. 120.00"
                        onkeydown="filterPositiveNumberKeydown(event)"
                        oninput="sanitizePositiveNumber(this)"
                        style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">Floor Area (sqm)</label>
                    <input type="number" step="0.01" min="0" id="lotEditFloorArea" placeholder="e.g. 60.00"
                        onkeydown="filterPositiveNumberKeydown(event)"
                        oninput="sanitizePositiveNumber(this)"
                        style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">TCT No.</label>
                    <input type="text" id="lotEditTctNo" placeholder="e.g. T-123456" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">PID (Property ID)</label>
                    <input type="text" id="lotEditPid" placeholder="e.g. PID-001" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px;">
                </div>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">Total Contract Price (TCP) ₱</label>
                <input type="text" id="lotEditContractPrice" placeholder="e.g. 2,500,000"
                    inputmode="numeric" autocomplete="off"
                    onkeydown="filterTcpKeydown(event)"
                    oninput="handleLotEditContractPriceInput(this)"
                    style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; font-weight: 700; color: #0f172a;">
                <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Used as default TCP in Downpayment contracts and Sample Computations.</div>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">Tax Declaration No.</label>
                <input type="text" id="lotEditTaxDecNo" placeholder="e.g. TD-2026-0001" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">Property Description</label>
                <textarea id="lotEditDescription" rows="2" placeholder="e.g. Single Attached / Corner Lot" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; resize: vertical;"></textarea>
            </div>

            <div style="display: flex; gap: 12px;">
                <button class="btn btn-outline" style="flex:1; justify-content: center;" onclick="document.getElementById('lotDetailsUpdateModal').style.display='none'">Cancel</button>
                <button class="btn btn-primary" id="btnSaveLotDetails" style="flex:1; justify-content: center;" onclick="updateLotDetails()">Save Lot Details</button>
            </div>
        </div>
    </div>

    <!-- Sample Computation Modal (Identical to Downpayment Calculator) -->
    <div id="sampleComputationModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 3100; background: rgba(15,23,42,0.6); backdrop-filter: blur(6px);">
        <div class="bill-modal-content" style="max-width: 520px; width: 92%; padding: 32px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <h3 id="sampleModalTitle" style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0;">DP Sample Computation</h3>
                    <p id="sampleModalSubtitle" style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Block -, Lot -</p>
                </div>
                <button onclick="document.getElementById('sampleComputationModal').style.display='none'" style="background: none; border: none; font-size: 26px; line-height: 1; cursor: pointer; color: #64748b;">&times;</button>
            </div>

            <div style="padding: 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; margin-bottom: 24px;">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Unit Price (TCP) ₱</label>
                        <input type="text" id="sampleCalcTcp" class="filter-select"
                            style="width: 100%; padding: 12px; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px;" placeholder="2,000,000"
                            inputmode="numeric" autocomplete="off"
                            onkeydown="filterTcpKeydown(event)"
                            oninput="handleSampleTcpInput(this)">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">DP Percentage %</label>
                        <input type="number" id="sampleCalcDpPercent" class="filter-select"
                            style="width: 100%; padding: 12px; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px;" value="30" min="1" max="100"
                            onkeydown="filterPositiveNumberKeydown(event)"
                            oninput="sanitizePositiveNumber(this, 100); calculateSampleAmortization();">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: var(--bill-primary, #2563eb); margin-bottom: 8px;">Total DP Amount ₱</label>
                        <input type="text" id="sampleCalcDpAmount" class="filter-select"
                            style="width: 100%; padding: 12px; background: #e2e8f0; font-weight: 800; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px;" readonly>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Months to Pay</label>
                        <input type="number" id="sampleCalcMonths" class="filter-select"
                            style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px;" value="24" min="1" max="120"
                            onkeydown="filterPositiveNumberKeydown(event)"
                            oninput="sanitizePositiveNumber(this); calculateSampleAmortization();">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr; margin-top: 16px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px;">Penalty %</label>
                        <input type="number" id="sampleCalcPenalty" class="filter-select"
                            style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px;" value="0" min="0" max="100"
                            onkeydown="filterPositiveNumberKeydown(event)"
                            oninput="sanitizePositiveNumber(this, 100); calculateSampleAmortization();">
                    </div>
                </div>
                <div style="margin-top: 16px; border-top: 1px dashed #cbd5e1; padding-top: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: 8px; text-transform: uppercase;">Calculated Monthly Amortization</label>
                    <div id="sampleCalcAmortization" style="font-size: 32px; font-weight: 800; color: var(--bill-primary, #2563eb);">₱0.00</div>
                </div>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="button" class="btn btn-outline" style="flex: 1; justify-content: center; padding: 12px; font-weight: 700; border-radius: 10px;" onclick="document.getElementById('sampleComputationModal').style.display='none'">Close</button>
            </div>
        </div>
    </div>


    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-rotate@0.2.8/dist/leaflet-rotate.js"></script>
    <script>
        let currentLayer = 'occupancy';
        let map;
        let houseLayer;
        let housesGeoJson;

        let dbLots = {};

        function getLotStyle(feature) {
            const b = feature.properties.block_num;
            const l = feature.properties.lot_number;
            const key = `B${b} L${l}`;
            const dbLot = dbLots[key] || { owner: 'Unassigned', status: 'Vacant Lot', electricity_status: 'unpaid', water_status: 'unpaid', isNotConnected: true };

            let fillColor = '#e7e8eb';
            let color = '#94a3b8';

            if (currentLayer === 'occupancy') {
                if (dbLot.status === 'Reserved') {
                    fillColor = '#8b5cf6';
                    color = '#7c3aed';
                } else if (dbLot.status === 'Occupied' || (dbLot.user_id && dbLot.owner !== 'Unassigned')) {
                    fillColor = '#22c55e';
                    color = '#16a34a';
                } else if (dbLot.status === 'Vacant House') {
                    fillColor = '#3b82f6';
                    color = '#2563eb';
                } else if (dbLot.status === 'Under Construction') {
                    fillColor = '#ef4444';
                    color = '#dc2626';
                } else if (dbLot.status === 'Vacant Lot' || dbLot.status === 'Available') {
                    fillColor = '#f59e0b';
                    color = '#d97706';
                } else {
                    fillColor = '#e7e8eb';
                    color = '#94a3b8';
                }
            } else if (currentLayer === 'electricity') {
                if (dbLot.isNotConnected) { fillColor = '#94a3b8'; color = '#64748b'; }
                else if (dbLot.electricity_status === 'paid') { fillColor = '#10b981'; color = '#059669'; }
                else { fillColor = '#ef4444'; color = '#dc2626'; }
            } else if (currentLayer === 'water') {
                if (dbLot.isNotConnected) { fillColor = '#94a3b8'; color = '#64748b'; }
                else if (dbLot.water_status === 'paid') { fillColor = '#0ea5e9'; color = '#0369a1'; }
                else { fillColor = '#f97316'; color = '#c2410c'; }
            }

            return {
                fillColor: fillColor,
                color: color,
                weight: 1,
                fillOpacity: 1
            };
        }

        async function initAdminMap() {
            const overlay = document.getElementById('mapLoadingOverlay');
            try {
                map = L.map('adminMapContainer', { 
                    zoomSnap: 0.1,
                    rotate: true,
                    bearing: 164,
                    rotateControl: {
                        closeOnZeroBearing: false,
                        position: 'topleft'
                    }
                }).setView([13.6268, 123.1906], 18);

                const [amenities, roads, houses, lotsData] = await Promise.all([
                    fetch('/gis-data/amenities.geojson').then(r => r.json()).catch(() => null),
                    fetch('/gis-data/buffered_road_design.geojson').then(r => r.json()).catch(() => null),
                    fetch('/gis-data/houses.geojson').then(r => r.json()).catch(() => ({})),
                    fetch('/api/lots').then(r => r.ok ? r.json() : []).catch(() => [])
                ]);

                if (lotsData && Array.isArray(lotsData)) {
                    lotsData.forEach(lot => {
                        dbLots[`B${lot.block} L${lot.lot_number}`] = lot;
                    });
                }

                housesGeoJson = houses;

                // Amenities Layer
                if (amenities) {
                    L.geoJSON(amenities, {
                        style: (feature) => {
                            const name = feature.properties.name || feature.properties.NAME || 'Unknown';
                            let fillColor = '#e7e8eb';
                            if (name.toLowerCase().includes('guard')) fillColor = '#e11d48';
                            else if (name.toLowerCase().includes('open')) fillColor = '#eae0d5';
                            else if (name.toLowerCase().includes('basketball')) fillColor = '#d97706';
                            else if (name.toLowerCase().includes('grass')) fillColor = '#a7f3d0';
                            else if (name.toLowerCase().includes('background')) fillColor = '#f6f5f5';

                            return { color: '#cbd5e1', weight: 1, fillColor: fillColor, fillOpacity: 1 };
                        }
                    }).addTo(map);
                }

                // Roads Layer
                if (roads) {
                    L.geoJSON(roads, {
                        style: { color: '#94a3b8', weight: 1, fillColor: '#cbd5e1', fillOpacity: 1 }
                    }).addTo(map);
                }

                // Houses Layer
                if (houses && houses.features) {
                    houseLayer = L.geoJSON(houses, {
                        style: getLotStyle,
                        onEachFeature: (feature, layer) => {
                            if (feature.properties.block_num && feature.properties.lot_number) {
                                const b = feature.properties.block_num;
                                const l = feature.properties.lot_number;
                                const key = `B${b} L${l}`;
                                const dbLot = dbLots[key] || { owner: 'Unassigned', status: 'Vacant Lot', electricity_status: 'unpaid', water_status: 'unpaid', id: null, user_id: null, block: b, lot_number: l };

                                layer.bindTooltip(`Block ${b}, Lot ${l} - ${dbLot.owner}`);
                                layer.on('click', () => {
                                    selectLot(key, dbLot);
                                });
                            }
                        }
                    }).addTo(map);

                    if (houseLayer.getBounds && houseLayer.getBounds().isValid()) {
                        map.fitBounds(houseLayer.getBounds(), { padding: [30, 30] });
                    }
                }

                setTimeout(() => {
                    const z = map.getZoom();
                    const zoomInp = document.getElementById('zoom');
                    const zoomVal = document.getElementById('zoomVal');
                    if (zoomInp) zoomInp.value = z.toFixed(1);
                    if (zoomVal) zoomVal.innerText = z.toFixed(1) + 'z';
                }, 200);
            } catch (err) {
                console.error('Failed to load GIS map:', err);
            } finally {
                if (overlay) overlay.style.display = 'none';
            }
        }

        function setLayer(layer, btn) {
            document.querySelectorAll('.layer-item').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            document.getElementById('legendOccupancy').style.display = layer === 'occupancy' ? 'block' : 'none';
            document.getElementById('legendElectricity').style.display = layer === 'electricity' ? 'block' : 'none';
            document.getElementById('legendWater').style.display = layer === 'water' ? 'block' : 'none';

            if (document.getElementById('lotDetailsPanel').style.display === 'block') {
                document.getElementById('panelOccupancyOnly').style.display = layer === 'occupancy' ? 'block' : 'none';
                document.getElementById('panelBillingOnly').style.display = (layer === 'electricity' || layer === 'water') ? 'block' : 'none';
                if (layer === 'electricity' || layer === 'water') {
                    document.getElementById('panelBillingElecOnly').style.display = layer === 'electricity' ? 'block' : 'none';
                    document.getElementById('panelBillingWaterOnly').style.display = layer === 'water' ? 'block' : 'none';
                }
            }
            
            currentLayer = layer;
            if (houseLayer) {
                houseLayer.setStyle(getLotStyle);
            }
        }

        function selectLot(id, dbLot) {
            document.getElementById('lotDetailsDefault').style.display = 'none';
            document.getElementById('lotDetailsPanel').style.display = 'block';

            document.getElementById('detailLotId').innerText = id;
            document.getElementById('detailResident').innerText = dbLot.owner;
            
            const occBadge = document.getElementById('detailOccupancyBadge');
            occBadge.innerText = dbLot.status;
            occBadge.className = 'status-badge ' + 'badge-' + dbLot.status.toLowerCase().replace(/ /g, '-');

            const billBadge = document.getElementById('detailBillingBadge');
            billBadge.innerText = dbLot.electricity_status;
            billBadge.className = 'status-badge ' + 'badge-' + dbLot.electricity_status.toLowerCase().replace(/ /g, '-');

            const waterBadge = document.getElementById('detailWaterBadge');
            waterBadge.innerText = dbLot.water_status;
            waterBadge.className = 'status-badge ' + 'badge-' + dbLot.water_status.toLowerCase().replace(/ /g, '-');

            document.getElementById('panelOccupancyOnly').style.display = currentLayer === 'occupancy' ? 'block' : 'none';
            document.getElementById('panelBillingOnly').style.display = (currentLayer === 'electricity' || currentLayer === 'water') ? 'block' : 'none';
            if (currentLayer === 'electricity' || currentLayer === 'water') {
                document.getElementById('panelBillingElecOnly').style.display = currentLayer === 'electricity' ? 'block' : 'none';
                document.getElementById('panelBillingWaterOnly').style.display = currentLayer === 'water' ? 'block' : 'none';
            }

            const btnEdit = document.getElementById('btnEditResident');
            const btnProfile = document.getElementById('btnViewProfile');
            const btnUpdate = document.getElementById('btnUpdateOccupancy');
            const lockedMsg = document.getElementById('occupancyLockedMsg');
            
            if (dbLot.user_id) {
                if (btnUpdate) btnUpdate.style.display = 'none';
                if (lockedMsg) lockedMsg.style.display = 'block';
                const formattedUserId = 'USR-' + String(dbLot.user_id).padStart(4, '0');
                if (btnEdit) btnEdit.onclick = () => window.location.href = `/admin/users?edit=${formattedUserId}`;
                if (btnProfile) btnProfile.onclick = () => {
                    document.getElementById('viewUserInitials').innerText = dbLot.owner.charAt(0).toUpperCase();
                    document.getElementById('viewUserName').innerText = dbLot.owner;
                    document.getElementById('viewUserRole').innerText = dbLot.user_role || 'Resident';
                    document.getElementById('viewUserId').innerText = formattedUserId;
                    document.getElementById('viewUserStatus').innerText = 'Active';
                    document.getElementById('viewUserStatus').style.color = '#10b981';
                    
                    document.getElementById('viewUserEmail').innerText = dbLot.user_email || 'N/A';
                    document.getElementById('viewUserContact').innerText = dbLot.user_contact || 'N/A';
                    document.getElementById('viewUserJoined').innerText = dbLot.user_joined || 'N/A';

                    const propContainer = document.getElementById('viewUserPropertyContainer');
                    if (dbLot.block && dbLot.lot_number) {
                        document.getElementById('viewUserProperty').innerText = `Block ${dbLot.block}, Lot ${dbLot.lot_number}`;
                        propContainer.style.display = 'block';
                    } else {
                        propContainer.style.display = 'none';
                    }
                    document.getElementById('viewUserModal').style.display = 'flex';
                };
            } else {
                if (btnUpdate) btnUpdate.style.display = 'flex';
                if (lockedMsg) lockedMsg.style.display = 'none';
                if (btnEdit) btnEdit.onclick = () => Swal.fire('No resident assigned to this lot.');
                if (btnProfile) btnProfile.onclick = () => Swal.fire('No resident assigned to this lot.');
            }
            if (btnUpdate) {
                btnUpdate.onclick = () => {
                    const parts = id.replace('B','').replace('L','').split(' ');
                    const block = parts[0];
                    const lot = parts[1];
                    document.getElementById('occUpdateBlock').value = block;
                    document.getElementById('occUpdateLot').value = lot;
                    document.getElementById('occUpdateStatus').value = dbLot.status === 'Unassigned' ? 'Vacant Lot' : dbLot.status;
                    document.getElementById('occupancyUpdateModal').style.display = 'flex';
                };
            }

            // Populate Lot Specifications & Titling
            const cp = dbLot.contract_price;
            const hasPrice = (cp !== null && cp !== undefined && cp !== '' && Number(cp) > 0);
            document.getElementById('detailContractPrice').innerText = hasPrice
                ? '₱' + Number(cp).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                : '—';
            document.getElementById('detailLotArea').innerText = (dbLot.lot_area !== null && dbLot.lot_area !== undefined && dbLot.lot_area !== '') ? `${parseFloat(dbLot.lot_area).toFixed(2)} sqm` : '—';
            document.getElementById('detailFloorArea').innerText = (dbLot.floor_area !== null && dbLot.floor_area !== undefined && dbLot.floor_area !== '') ? `${parseFloat(dbLot.floor_area).toFixed(2)} sqm` : '—';
            document.getElementById('detailTctNo').innerText = dbLot.tct_no || '—';
            document.getElementById('detailPid').innerText = dbLot.pid || '—';
            document.getElementById('detailTaxDecNo').innerText = dbLot.tax_dec_no || '—';
            document.getElementById('detailDescription').innerText = dbLot.description || '—';

            const parts = id.replace('B','').replace('L','').split(' ');
            const block = parts[0];
            const lot = parts[1];

            const openLotEditModal = () => {
                document.getElementById('lotModalTitle').innerText = `Edit Lot Info — Block ${block}, Lot ${lot}`;
                document.getElementById('lotEditBlock').value = block;
                document.getElementById('lotEditNumber').value = lot;
                const cpVal = dbLot.contract_price;
                document.getElementById('lotEditContractPrice').value = (cpVal !== null && cpVal !== undefined && cpVal !== '' && Number(cpVal) > 0)
                    ? Number(cpVal).toLocaleString('en-US')
                    : '';
                document.getElementById('lotEditLotArea').value = dbLot.lot_area ?? '';
                document.getElementById('lotEditFloorArea').value = dbLot.floor_area ?? '';
                document.getElementById('lotEditTctNo').value = dbLot.tct_no ?? '';
                document.getElementById('lotEditPid').value = dbLot.pid ?? '';
                document.getElementById('lotEditTaxDecNo').value = dbLot.tax_dec_no ?? '';
                document.getElementById('lotEditDescription').value = dbLot.description ?? '';
                document.getElementById('lotDetailsUpdateModal').style.display = 'flex';
            };

            const btnEditLotDetails = document.getElementById('btnEditLotDetails');
            if (btnEditLotDetails) btnEditLotDetails.onclick = openLotEditModal;

            const btnSample = document.getElementById('btnSampleComputation');
            if (btnSample) {
                btnSample.innerHTML = '<span style="margin-right: 4px;">📊</span> Computation';
                if (hasPrice) {
                    btnSample.style.opacity = '1';
                    btnSample.style.background = 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)';
                    btnSample.onclick = () => openSampleComputation(id, dbLot);
                } else {
                    btnSample.style.opacity = '0.75';
                    btnSample.style.background = '#64748b';
                    btnSample.onclick = () => {
                        Swal.fire({
                            icon: 'warning',
                            title: 'No Contract Price on Lot',
                            text: `Block ${block}, Lot ${lot} does not have a contract price yet. Please edit the lot info to input a Total Contract Price (TCP) before generating a computation.`,
                            showCancelButton: true,
                            confirmButtonColor: '#3b82f6',
                            confirmButtonText: '✎ Edit Info Now',
                            cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                openLotEditModal();
                            }
                        });
                    };
                }
            }
        }

        function closeDetails() {
            document.getElementById('lotDetailsDefault').style.display = 'block';
            document.getElementById('lotDetailsPanel').style.display = 'none';
        }

        function resetMap() {
            if (houseLayer) {
                map.fitBounds(houseLayer.getBounds(), { padding: [30, 30] });
                setTimeout(() => {
                    const z = map.getZoom();
                    const zoomInp = document.getElementById('zoom');
                    const zoomVal = document.getElementById('zoomVal');
                    if (zoomInp) zoomInp.value = z.toFixed(1);
                    if (zoomVal) zoomVal.innerText = z.toFixed(1) + 'z';
                }, 200);
            }
            const rotZInp = document.getElementById('rotateZ');
            if (rotZInp) rotZInp.value = 164; 
            updateMapTransform();
        }

        function updateMapTransform() {
            const rotZInp = document.getElementById('rotateZ');
            const zoomInp = document.getElementById('zoom');
            
            if (!rotZInp || !zoomInp) return;

            const rZ = parseFloat(rotZInp.value);
            const z = parseFloat(zoomInp.value);
            
            if (map) {
                map.setZoom(z);
                if (map.setBearing) {
                    map.setBearing(rZ);
                }
            }

            // Update Sidebar Labels
            const rotVal = document.getElementById('rotVal');
            const zoomVal = document.getElementById('zoomVal');
            if (rotVal) rotVal.innerText = rZ + '°';
            if (zoomVal) zoomVal.innerText = z.toFixed(1) + 'z';
        }

        async function updateOccupancyStatus() {
            const block = document.getElementById('occUpdateBlock').value;
            const lot = document.getElementById('occUpdateLot').value;
            const status = document.getElementById('occUpdateStatus').value;
            const btnSave = document.getElementById('btnSaveOccupancy');
            
            try {
                // UI Loading state
                btnSave.disabled = true;
                btnSave.innerText = 'Saving...';

                const res = await fetch('/admin/gis/update-occupancy', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ block: block, lot: lot, status: status })
                });
                
                if (res.ok) {
                    // Update state locally without reloading the page
                    const key = `B${block} L${lot}`;
                    if(dbLots[key]) {
                        dbLots[key].status = status;
                        if(dbLots[key].isNotConnected === undefined) dbLots[key].isNotConnected = true;
                    } else {
                        dbLots[key] = { owner: 'Unassigned', status: status, electricity_status: 'unpaid', water_status: 'unpaid', block: block, lot_number: lot, isNotConnected: true };
                    }
                    
                    // Re-render map layer styling instantly
                    if(houseLayer) houseLayer.setStyle(getLotStyle);
                    
                    // Update sidebar if this lot is currently selected
                    if(document.getElementById('detailLotId').innerText === key) {
                        selectLot(key, dbLots[key]);
                    }
                    
                    document.getElementById('occupancyUpdateModal').style.display = 'none';
                } else {
                    Swal.fire('Failed to update occupancy status.');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('An error occurred.');
            } finally {
                // Reset UI
                btnSave.disabled = false;
                btnSave.innerText = 'Save Update';
            }
        }

        async function updateLotDetails() {
            const block = document.getElementById('lotEditBlock').value;
            const lot = document.getElementById('lotEditNumber').value;
            const rawCp = document.getElementById('lotEditContractPrice').value.replace(/,/g, '').trim();
            const rawLa = document.getElementById('lotEditLotArea').value.trim();
            const rawFa = document.getElementById('lotEditFloorArea').value.trim();

            if (rawCp && (isNaN(rawCp) || parseFloat(rawCp) < 0)) {
                Swal.fire({ icon: 'warning', title: 'Invalid Price', text: 'Contract price cannot be negative.' });
                return;
            }
            if (rawLa && parseFloat(rawLa) < 0) {
                Swal.fire({ icon: 'warning', title: 'Invalid Area', text: 'Lot area cannot be negative.' });
                return;
            }
            if (rawFa && parseFloat(rawFa) < 0) {
                Swal.fire({ icon: 'warning', title: 'Invalid Area', text: 'Floor area cannot be negative.' });
                return;
            }

            const payload = {
                block: block,
                lot: lot,
                contract_price: rawCp ? parseFloat(rawCp) : null,
                lot_area: rawLa ? parseFloat(rawLa) : null,
                floor_area: rawFa ? parseFloat(rawFa) : null,
                tct_no: document.getElementById('lotEditTctNo').value.trim() || null,
                pid: document.getElementById('lotEditPid').value.trim() || null,
                tax_dec_no: document.getElementById('lotEditTaxDecNo').value.trim() || null,
                description: document.getElementById('lotEditDescription').value.trim() || null,
            };

            const btnSave = document.getElementById('btnSaveLotDetails');
            try {
                btnSave.disabled = true;
                btnSave.innerText = 'Saving...';

                const res = await fetch('/admin/gis/update-lot-details', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    const key = `B${block} L${lot}`;
                    if (!dbLots[key]) {
                        dbLots[key] = {
                            owner: 'Unassigned',
                            status: data.lot?.status || 'Vacant Lot',
                            electricity_status: 'unpaid',
                            water_status: 'unpaid',
                            block: block,
                            lot_number: lot,
                            isNotConnected: true
                        };
                    }
                    dbLots[key].contract_price = data.lot.contract_price;
                    dbLots[key].lot_area = data.lot.lot_area;
                    dbLots[key].floor_area = data.lot.floor_area;
                    dbLots[key].tct_no = data.lot.tct_no;
                    dbLots[key].pid = data.lot.pid;
                    dbLots[key].tax_dec_no = data.lot.tax_dec_no;
                    dbLots[key].description = data.lot.description;

                    if (document.getElementById('detailLotId').innerText === key) {
                        selectLot(key, dbLots[key]);
                    }

                    document.getElementById('lotDetailsUpdateModal').style.display = 'none';
                    Swal.fire({
                        icon: 'success',
                        title: 'Lot Details Updated',
                        text: `Block ${block}, Lot ${lot} property specifications saved.`,
                        timer: 1800,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error', data.message || 'Failed to update lot property details.', 'error');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'An error occurred while saving lot details.', 'error');
            } finally {
                btnSave.disabled = false;
                btnSave.innerText = 'Save Lot Details';
            }
        }

        // Input validation & formatting helpers
        function filterTcpKeydown(e) {
            if (['Backspace', 'Tab', 'ArrowLeft', 'ArrowRight', 'Delete', 'Enter'].includes(e.key)) {
                return;
            }
            if (e.ctrlKey || e.metaKey) {
                return;
            }
            // Block negative '-', '+', 'e', 'E', and letters
            if (['-', '+', 'e', 'E'].includes(e.key) || /^[a-zA-Z]$/.test(e.key)) {
                e.preventDefault();
                return;
            }
            if (!/^\d$/.test(e.key)) {
                e.preventDefault();
            }
        }

        function filterPositiveNumberKeydown(e) {
            if (['Backspace', 'Tab', 'ArrowLeft', 'ArrowRight', 'Delete', 'Enter'].includes(e.key)) {
                return;
            }
            if (e.ctrlKey || e.metaKey) {
                return;
            }
            if (['-', '+', 'e', 'E'].includes(e.key) || /^[a-zA-Z]$/.test(e.key)) {
                e.preventDefault();
                return;
            }
        }

        function sanitizePositiveNumber(input, max = null) {
            let val = parseFloat(input.value);
            if (isNaN(val) || val < 0) {
                input.value = '';
            } else if (max !== null && val > max) {
                input.value = max;
            }
        }

        function handleLotEditContractPriceInput(input) {
            let rawDigits = input.value.replace(/\D/g, '');
            if (rawDigits !== '') {
                input.value = Number(rawDigits).toLocaleString('en-US');
            } else {
                input.value = '';
            }
        }

        // Sample Computation Logic (Downpayment Calculator)
        function openSampleComputation(id, dbLot) {
            const parts = id.replace('B','').replace('L','').split(' ');
            const block = parts[0];
            const lot = parts[1];

            document.getElementById('sampleModalTitle').innerText = `DP Sample Computation`;
            const lotStatus = dbLot.status || 'Available';
            document.getElementById('sampleModalSubtitle').innerText = `Block ${block}, Lot ${lot} • ${lotStatus}`;

            const basePrice = (dbLot.contract_price !== null && dbLot.contract_price !== undefined && Number(dbLot.contract_price) > 0)
                ? Number(dbLot.contract_price)
                : 0;

            const tcpInput = document.getElementById('sampleCalcTcp');
            tcpInput.value = basePrice > 0 ? basePrice.toLocaleString('en-US') : '';
            document.getElementById('sampleCalcDpPercent').value = 30;
            document.getElementById('sampleCalcMonths').value = 24;
            document.getElementById('sampleCalcPenalty').value = 0;

            calculateSampleAmortization();
            document.getElementById('sampleComputationModal').style.display = 'flex';
        }

        function handleSampleTcpInput(input) {
            let rawDigits = input.value.replace(/\D/g, '');
            if (rawDigits !== '') {
                input.value = Number(rawDigits).toLocaleString('en-US');
            } else {
                input.value = '';
            }
            calculateSampleAmortization();
        }

        function calculateSampleAmortization() {
            const tcpStr = document.getElementById('sampleCalcTcp').value.replace(/,/g, '');
            const tcp = parseFloat(tcpStr) || 0;
            const percent = parseFloat(document.getElementById('sampleCalcDpPercent').value) || 0;
            const months = parseInt(document.getElementById('sampleCalcMonths').value) || 1;

            const dpAmount = tcp * (percent / 100);
            const amortization = months > 0 ? (dpAmount / months) : 0;

            document.getElementById('sampleCalcDpAmount').value = dpAmount > 0 
                ? dpAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                : '0.00';
            document.getElementById('sampleCalcAmortization').textContent = `₱${amortization.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }

        // Initialize defaults properly
        document.addEventListener('DOMContentLoaded', () => {
            const rotZInp = document.getElementById('rotateZ');
            if (rotZInp) rotZInp.value = 164;
            
            initAdminMap().then(() => {
                updateMapTransform();
            });
        });
    </script>
@endsection