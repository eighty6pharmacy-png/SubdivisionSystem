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
                    <button class="btn btn-outline" style="width: 100%; padding: 8px; font-size: 11px; border-color: #10b981; color: #059669; justify-content: center; background: #fff;">
                        <span style="margin-right: 4px;">✔</span> Manual Elec. Override
                    </button>
                </div>

                <div class="info-card" id="panelBillingWaterOnly" style="background: #f0f9ff; border-color: #bae6fd;">
                    <label style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #0369a1; font-weight: 800; margin-bottom: 12px; display: block;">Water Status</label>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <span style="font-weight: 700; font-size: 13px;">Water Bill</span>
                        <div id="detailWaterBadge" class="status-badge badge-paid">Paid</div>
                    </div>
                    <button class="btn btn-outline" style="width: 100%; padding: 8px; font-size: 11px; border-color: #0ea5e9; color: #0369a1; justify-content: center; background: #fff;">
                        <span style="margin-right: 4px;">✔</span> Manual Water Override
                    </button>
                </div>
                <button class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px; border-radius: 12px; font-weight: 700; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);">Open Full Billing Ledger</button>
            </div>
        </div>
    </aside>
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
                        const dbLot = dbLots[key] || { owner: 'Unassigned', status: 'Vacant Lot', electricity_status: 'unpaid', water_status: 'unpaid', id: null, user_id: null };

                        layer.bindTooltip(`Block ${b}, Lot ${l} - ${dbLot.owner}`);
                        layer.on('click', () => {
                            selectLot(key, dbLot.owner, dbLot.status, dbLot.electricity_status, dbLot.water_status, dbLot.id, dbLot.user_id);
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

        function selectLot(id, res, status, billing, water, dbLotId, userId) {
            document.getElementById('lotDetailsDefault').style.display = 'none';
            document.getElementById('lotDetailsPanel').style.display = 'block';

            document.getElementById('detailLotId').innerText = id;
            document.getElementById('detailResident').innerText = res;
            
            const occBadge = document.getElementById('detailOccupancyBadge');
            occBadge.innerText = status;
            occBadge.className = 'status-badge ' + 'badge-' + status.toLowerCase().replace(/ /g, '-');

            const billBadge = document.getElementById('detailBillingBadge');
            billBadge.innerText = billing;
            billBadge.className = 'status-badge ' + 'badge-' + billing.toLowerCase().replace(/ /g, '-');

            const waterBadge = document.getElementById('detailWaterBadge');
            waterBadge.innerText = water;
            waterBadge.className = 'status-badge ' + 'badge-' + water.toLowerCase().replace(/ /g, '-');

            document.getElementById('panelOccupancyOnly').style.display = currentLayer === 'occupancy' ? 'block' : 'none';
            document.getElementById('panelBillingOnly').style.display = (currentLayer === 'electricity' || currentLayer === 'water') ? 'block' : 'none';
            if (currentLayer === 'electricity' || currentLayer === 'water') {
                document.getElementById('panelBillingElecOnly').style.display = currentLayer === 'electricity' ? 'block' : 'none';
                document.getElementById('panelBillingWaterOnly').style.display = currentLayer === 'water' ? 'block' : 'none';
            }

            const btnEdit = document.getElementById('btnEditResident');
            const btnProfile = document.getElementById('btnViewProfile');
            const btnUpdate = document.getElementById('btnUpdateOccupancy');
            
            if (userId) {
                if (btnEdit) btnEdit.onclick = () => window.location.href = `/admin/users?edit=${userId}`;
                if (btnProfile) btnProfile.onclick = () => window.location.href = `/admin/users?view=${userId}`;
            } else {
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
                    document.getElementById('occUpdateStatus').value = status === 'Unassigned' ? 'Vacant Lot' : status;
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