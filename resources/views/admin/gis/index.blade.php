@extends('layouts.admin')

@section('title', 'GIS Property Mapping')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">
    <link rel="stylesheet" href="{{ asset('css/views/admin-gis.css') }}">

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
                        <div class="legend-box" style="background: #fff; border-color: #000; color: #000;">Lot</div>
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
        <main class="gis-viewport" id="viewport">
            <div id="adminMapContainer" class="map-container"></div>
        </main>

        <!-- Right Sidebar -->
        <aside class="gis-sidebar-right">
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
                    <option value="Available">Available</option>
                    <option value="Occupied">Occupied</option>
                    <option value="Vacant House">Vacant House</option>
                    <option value="Vacant Lot">Vacant Lot</option>
                    <option value="Reserved">Reserved</option>
                    <option value="Under Construction">Under Construction</option>
                </select>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button class="btn btn-outline" style="flex:1;" onclick="document.getElementById('occupancyUpdateModal').style.display='none'">Cancel</button>
                <button class="btn btn-primary" style="flex:1;" onclick="updateOccupancyStatus()">Save Update</button>
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
                if (dbLot.status === 'Occupied') { fillColor = '#ffffff'; color = '#000000'; }
                else if (dbLot.status === 'Vacant House') { fillColor = '#3b82f6'; color = '#2563eb'; }
                else if (dbLot.status === 'Vacant Lot') { fillColor = '#f59e0b'; color = '#d97706'; }
                else if (dbLot.status === 'Reserved') { fillColor = '#8b5cf6'; color = '#7c3aed'; }
                else if (dbLot.status === 'Under Construction') { fillColor = '#ef4444'; color = '#dc2626'; }
                else { fillColor = '#e7e8eb'; color = '#94a3b8'; }
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
                fetch('/gis-data/amenities.geojson').then(r => r.json()),
                fetch('/gis-data/buffered_road_design.geojson').then(r => r.json()),
                fetch('/gis-data/houses.geojson').then(r => r.json()).catch(e => ({})),
                fetch('/api/lots').then(r => r.ok ? r.json() : []).catch(e => [])
            ]);

            if (lotsData && Array.isArray(lotsData)) {
                lotsData.forEach(lot => {
                    dbLots[`B${lot.block} L${lot.lot_number}`] = lot;
                });
            }

            housesGeoJson = houses;

            // Amenities Layer
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

            // Roads Layer
            L.geoJSON(roads, {
                style: { color: '#94a3b8', weight: 1, fillColor: '#cbd5e1', fillOpacity: 1 }
            }).addTo(map);

            // Houses Layer
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

            map.fitBounds(houseLayer.getBounds(), { padding: [30, 30] });
            setTimeout(() => {
                const z = map.getZoom();
                const zoomInp = document.getElementById('zoom');
                const zoomVal = document.getElementById('zoomVal');
                if (zoomInp) zoomInp.value = z.toFixed(1);
                if (zoomVal) zoomVal.innerText = z.toFixed(1) + 'z';
            }, 200);
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
                if (btnEdit) btnEdit.onclick = () => alert('No resident assigned to this lot.');
                if (btnProfile) btnProfile.onclick = () => alert('No resident assigned to this lot.');
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
            
            try {
                const res = await fetch('/admin/gis/update-occupancy', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ block: block, lot: lot, status: status })
                });
                
                if (res.ok) {
                    window.location.reload();
                } else {
                    alert('Failed to update occupancy status.');
                }
            } catch (e) {
                console.error(e);
                alert('An error occurred.');
            }
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