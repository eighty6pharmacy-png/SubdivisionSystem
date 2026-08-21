@extends('layouts.admin')

@section('title', 'GIS Property Mapping')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">
    <style>
        /* GIS Specific Styles */
        .gis-wrapper {
            display: grid;
            grid-template-columns: 280px 1fr 320px;
            gap: 0;
            height: calc(100vh - 120px);
            margin: -20px;
            background: #0f172a;
            overflow: hidden;
            border-radius: 0 0 24px 24px;
            position: relative;
        }

        /* Left Panel - Layers & Search */
        .gis-sidebar-left {
            background: rgba(15, 23, 42, 0.9);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            padding: 24px;
            color: #f8fafc;
            z-index: 10;
            backdrop-filter: blur(10px);
        }

        /* Center - Map Canvas */
        .gis-viewport {
            position: relative;
            overflow: hidden;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .map-container {
            width: 800px;
            height: 1100px;
            position: relative;
            transition: transform 0.3s ease-out;
            cursor: grab;
            transform-origin: center center;
        }

        .map-container:active {
            cursor: grabbing;
        }

        .map-base {
            width: 100%;
            height: 100%;
            background-image: url('{{ asset('images/site-plan.png') }}');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            position: relative;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            background-color: white;
        }

        /* Interactive Lot Hotspots */
        .lot-hotspot {
            position: absolute;
            width: 26px;
            height: 18px;
            background: rgba(255, 255, 255, 0.6);
            border: 1.5px solid #64748b;
            border-radius: 2px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            font-weight: 800;
            color: #0f172a;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .lot-hotspot:hover {
            background: #fff;
            transform: scale(1.4);
            z-index: 100;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

    /* Occupancy Layer Colors (SQUARES) */
    .view-occupancy .lot-hotspot.occupied { border-color: #000; background: #fff; color: #000; }
    .view-occupancy .lot-hotspot.vacant-house { border-color: #2563eb; background: #3b82f6; color: white; }
    .view-occupancy .lot-hotspot.vacant-lot { border-color: #d97706; background: #f59e0b; color: white; }
    .view-occupancy .lot-hotspot.reserved { border-color: #7c3aed; background: #8b5cf6; color: white; }
    .view-occupancy .lot-hotspot.construction { border-color: #dc2626; background: #ef4444; color: white; }

    /* Electricity Layer Colors (SQUARES) */
    .view-electricity .lot-hotspot.paid { border-color: #059669; background: #10b981; color: white; }
    .view-electricity .lot-hotspot.unpaid { border-color: #dc2626; background: #ef4444; color: white; }

    /* Water Layer Colors (SQUARES) */
    .view-water .lot-hotspot.paid { border-color: #0369a1; background: #0ea5e9; color: white; }
    .view-water .lot-hotspot.unpaid { border-color: #c2410c; background: #f97316; color: white; }

        /* Legend Styles - Moved to Right Sidebar */
        .legend-section {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }

        .legend-box {
            width: 28px;
            height: 18px;
            border-radius: 2px;
            border: 1.5px solid rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: white;
            font-weight: 800;
        }

    /* Right Panel - Details */
    .gis-sidebar-right {
        background: #fff;
        border-left: 1px solid #e2e8f0;
        padding: 24px;
        color: #0f172a;
        z-index: 10;
        box-shadow: -10px 0 30px rgba(0,0,0,0.02);
        display: flex;
        flex-direction: column;
    }

    .info-card {
        background: #f8fafc;
        padding: 20px;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        margin-bottom: 20px;
    }

    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-block;
        margin-top: 8px;
    }

    .badge-occupied { background: #f8fafc; color: #000; border: 1px solid #000; }
    .badge-vacant { background: #dbeafe; color: #1e40af; }
    .badge-vacant-house { background: #dbeafe; color: #1e40af; }
    .badge-vacant-lot { background: #fef3c7; color: #92400e; }
    .badge-reserved { background: #f3e8ff; color: #6b21a8; border: 1px solid #c084fc; }
    .badge-paid { background: #dcfce7; color: #166534; }
    .badge-unpaid { background: #fee2e2; color: #991b1b; }
    .badge-construction { background: #fee2e2; color: #991b1b; }
    .badge-under-construction { background: #fee2e2; color: #991b1b; }

        /* Floating Controls REMOVED - Moved to Sidebar */
        .gis-controls {
            display: none;
        }

        .control-group {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        input[type=range] {
            width: 120px;
            accent-color: var(--primary);
        }

        .layer-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 4px;
            border: 1px solid transparent;
            color: #64748b;
        }

        .layer-item:hover {
            background: #f1f5f9;
        }

        .layer-item.active {
            background: #fff;
            border-color: #e2e8f0;
            color: var(--primary);
            font-weight: 700;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
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

            <div
                style="background: #fff; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 16px;">
                <label
                    style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: 800; margin-bottom: 8px; display: block;">Search
                    Property</label>
                <input type="text" placeholder="e.g. Block 1, Lot 5"
                    style="width: 100%; border: 1px solid #e2e8f0; padding: 10px; border-radius: 8px; font-size: 13px;">
            </div>

            <div style="background: #fff; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <label
                    style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: 800; margin-bottom: 16px; display: block;">Map
                    Controls</label>

                <div style="margin-bottom: 16px;">
                    <div
                        style="display: flex; justify-content: space-between; font-size: 11px; color: #64748b; margin-bottom: 6px;">
                        <span>Rotation</span>
                        <span id="rotVal">42°</span>
                    </div>
                    <input type="range" id="rotateZ" min="-180" max="180" value="42" oninput="updateMapTransform()"
                        style="width: 100%;">
                </div>

                <div style="margin-bottom: 20px;">
                    <div
                        style="display: flex; justify-content: space-between; font-size: 11px; color: #64748b; margin-bottom: 6px;">
                        <span>Manual Zoom</span>
                        <span id="zoomVal">1.3x</span>
                    </div>
                    <input type="range" id="zoom" min="0.5" max="2.5" step="0.1" value="1.3" oninput="updateMapTransform()"
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
            <div class="map-container" id="mapContainer" style="transform: rotate(42deg) scale(1.3);">
                <div class="map-base view-occupancy" id="mapBase">
                    <!-- Distinct Examples -->
                    <div class="lot-hotspot occupied paid water-paid" style="top: 41.45%; left: 40.21%;"
                        onclick="selectLot('B1-L5', 'Juan Dela Cruz', 'Occupied', 'Paid', 'Paid')">8</div>
                    <div class="lot-hotspot construction paid water-unpaid" style="top: 44.73%; left: 40.67%; width: 19px;"
                        onclick="selectLot('B2-L12', 'Maria Santos', 'Under Construction', 'Paid', 'Unpaid')">16</div>
                    <div class="lot-hotspot vacant-house unpaid water-paid" style="top: 53.27%; left: 47.40%; width: 19px;"
                        onclick="selectLot('B3-L8', 'Ricardo Reyes', 'Vacant House', 'Unpaid', 'Paid')">15</div>
                    <div class="lot-hotspot reserved paid water-paid" style="top: 58.50%; left: 51.20%; width: 19.5px;"
                        onclick="selectLot('B5-L20', 'Marco Valdes (Deposit Paid)', 'Reserved', 'Paid', 'Paid')">20</div>
                    <div class="lot-hotspot vacant-lot paid water-unpaid" style="top: 64%; left: 55%; width: 19.5px;"
                        onclick="selectLot('B4-L15', 'Elena Gomez', 'Vacant Lot', 'Paid', 'Unpaid')">12</div>
                </div>
            </div>
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
                    <button class="btn btn-outline" style="flex: 1; padding: 8px; font-size: 11px; border-color: var(--primary); color: var(--primary); justify-content: center;">
                        <span style="margin-right: 4px;">✎</span> Edit Resident
                    </button>
                    <button class="btn btn-outline" style="flex: 1; padding: 8px; font-size: 11px; border-color: #64748b; color: #64748b; justify-content: center;">
                        View Profile
                    </button>
                </div>
            </div>

            <!-- Dynamic Section: Occupancy Info -->
            <div id="panelOccupancyOnly">
                <div class="info-card" style="background: white; border-style: dashed;">
                    <label style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: 800; margin-bottom: 12px; display: block;">Property Status</label>
                    <button class="btn btn-primary" style="width: 100%; padding: 10px; font-size: 11px; justify-content: center; background: #334155; border: none;">
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

    <script>
        const map = document.getElementById('mapContainer');
        const mapBase = document.getElementById('mapBase');
        const rotZInp = document.getElementById('rotateZ');
        const zoomInp = document.getElementById('zoom');

        function setLayer(layer, btn) {
            // Toggle active button
            document.querySelectorAll('.layer-item').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Toggle map class
            mapBase.classList.remove('view-occupancy', 'view-electricity', 'view-water');
            mapBase.classList.add('view-' + layer);

            // Toggle Legend
            document.getElementById('legendOccupancy').style.display = layer === 'occupancy' ? 'block' : 'none';
            document.getElementById('legendElectricity').style.display = layer === 'electricity' ? 'block' : 'none';
            document.getElementById('legendWater').style.display = layer === 'water' ? 'block' : 'none';

            // Toggle Panel Content Visibility
            if (document.getElementById('lotDetailsPanel').style.display === 'block') {
                document.getElementById('panelOccupancyOnly').style.display = layer === 'occupancy' ? 'block' : 'none';
                document.getElementById('panelBillingOnly').style.display = (layer === 'electricity' || layer === 'water') ? 'block' : 'none';
                if (layer === 'electricity' || layer === 'water') {
                    document.getElementById('panelBillingElecOnly').style.display = layer === 'electricity' ? 'block' : 'none';
                    document.getElementById('panelBillingWaterOnly').style.display = layer === 'water' ? 'block' : 'none';
                }
            }
            
            currentLayer = layer;
        }

        function updateMapTransform() {
            const rZ = rotZInp.value;
            const z = zoomInp.value;
            map.style.transform = `rotate(${rZ}deg) scale(${z})`;

            // UN-ROTATE Hotspots so they stay horizontal
            document.querySelectorAll('.lot-hotspot').forEach(hot => {
                hot.style.transform = `rotate(${-rZ}deg)`;
            });

            // Update Sidebar Labels
            document.getElementById('rotVal').innerText = rZ + '°';
            document.getElementById('zoomVal').innerText = parseFloat(z).toFixed(1) + 'x';
        }

        function resetMap() {
            rotZInp.value = 42;
            zoomInp.value = 1.3;
            updateMapTransform();
        }

        let currentLayer = 'occupancy';

        function selectLot(id, res, status, billing, water) {
            document.getElementById('lotDetailsDefault').style.display = 'none';
            document.getElementById('lotDetailsPanel').style.display = 'block';

            document.getElementById('detailLotId').innerText = id;
            document.getElementById('detailResident').innerText = res;
            
            // Setup Occupancy Badge
            const occBadge = document.getElementById('detailOccupancyBadge');
            occBadge.innerText = status;
            occBadge.className = 'status-badge ' + 'badge-' + status.toLowerCase().replace(/ /g, '-');

            // Setup Elec Billing Badge
            const billBadge = document.getElementById('detailBillingBadge');
            billBadge.innerText = billing;
            billBadge.className = 'status-badge ' + 'badge-' + billing.toLowerCase().replace(/ /g, '-');

            // Setup Water Billing Badge
            const waterBadge = document.getElementById('detailWaterBadge');
            waterBadge.innerText = water;
            waterBadge.className = 'status-badge ' + 'badge-' + water.toLowerCase().replace(/ /g, '-');

            // Initial Visibility
            document.getElementById('panelOccupancyOnly').style.display = currentLayer === 'occupancy' ? 'block' : 'none';
            document.getElementById('panelBillingOnly').style.display = (currentLayer === 'electricity' || currentLayer === 'water') ? 'block' : 'none';
            if (currentLayer === 'electricity' || currentLayer === 'water') {
                document.getElementById('panelBillingElecOnly').style.display = currentLayer === 'electricity' ? 'block' : 'none';
                document.getElementById('panelBillingWaterOnly').style.display = currentLayer === 'water' ? 'block' : 'none';
            }
        }

        function closeDetails() {
            document.getElementById('lotDetailsDefault').style.display = 'block';
            document.getElementById('lotDetailsPanel').style.display = 'none';
        }

        // Draggable Map Logic
        let isDragging = false;
        let startX, startY, scrollLeft, scrollTop;

        const viewport = document.getElementById('viewport');

        viewport.addEventListener('mousedown', (e) => {
            isDragging = true;
            viewport.classList.add('active');
            startX = e.pageX - viewport.offsetLeft;
            startY = e.pageY - viewport.offsetTop;
            scrollLeft = viewport.scrollLeft;
            scrollTop = viewport.scrollTop;
        });

        viewport.addEventListener('mouseleave', () => {
            isDragging = false;
        });

        viewport.addEventListener('mouseup', () => {
            isDragging = false;
        });

        viewport.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            e.preventDefault();
            const x = e.pageX - viewport.offsetLeft;
            const y = e.pageY - viewport.offsetTop;
            const walkX = (x - startX) * 2;
            const walkY = (y - startY) * 2;
            viewport.scrollLeft = scrollLeft - walkX;
            viewport.scrollTop = scrollTop - walkY;
        });

        viewport.addEventListener('wheel', (e) => {
            e.preventDefault();
            const delta = e.deltaY > 0 ? -0.1 : 0.1;
            let newZoom = parseFloat(zoomInp.value) + delta;
            newZoom = Math.min(Math.max(newZoom, 0.5), 2.5);
            zoomInp.value = newZoom;
            updateMapTransform();
        }, { passive: false });

        // Developer Helper: Shift + Click to get coordinates for lot-hotspot
        mapBase.addEventListener('mousedown', function(e) {
            if (e.shiftKey) {
                // Ensure we get coordinates relative to the mapBase
                let x = e.offsetX;
                let y = e.offsetY;

                // If clicked on a hotspot instead of the map directly, adjust coordinates
                if (e.target !== mapBase) {
                    x += e.target.offsetLeft;
                    y += e.target.offsetTop;
                }

                // Adjust for the center of the hotspot box (width: 26px, height: 18px)
                // We want the box to be centered on where the user clicked!
                x = x - 13; 
                y = y - 9;  

                const xPercent = ((x / mapBase.offsetWidth) * 100).toFixed(2);
                const yPercent = ((y / mapBase.offsetHeight) * 100).toFixed(2);
                
                const styleString = `top: ${yPercent}%; left: ${xPercent}%;`;
                console.log("Copied Coordinates: ", styleString);
                prompt("Copy these PERFECTLY CENTERED coordinates for your lot-hotspot:", styleString);
            }
        });

        updateMapTransform();
    </script>
@endsection