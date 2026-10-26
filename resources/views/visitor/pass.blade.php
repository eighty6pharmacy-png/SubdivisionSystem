@extends('layouts.app')

@section('title', 'Visitor Access Pass | Althesa Residences')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="{{ asset('css/views/visitor-routing.css') }}">

<style>
    .pass-container {
        min-height: calc(100vh - 80px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background: #f8fafc;
        position: relative;
    }
    .pass-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
        width: 100%;
        max-width: 480px;
        overflow: hidden;
        text-align: center;
        position: relative;
        z-index: 10;
        animation: fadeIn 0.3s ease-out;
    }
    .pass-header {
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #ffffff;
        padding: 28px 24px 20px;
    }
    .pass-header h2 {
        margin: 0;
        font-size: 20px;
        font-weight: 800;
        letter-spacing: -0.3px;
    }
    .pass-header p {
        margin: 6px 0 0;
        font-size: 13px;
        color: #e0f2fe;
    }
    .pass-body {
        padding: 28px 24px;
    }
    .pin-display-box {
        background: #f0f9ff;
        border: 2px dashed #0284c7;
        border-radius: 18px;
        padding: 20px;
        margin-bottom: 24px;
        position: relative;
    }
    .pin-digits {
        font-family: monospace;
        font-size: 38px;
        font-weight: 900;
        letter-spacing: 8px;
        color: #0369a1;
        margin: 8px 0;
    }
    .pass-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 24px;
        text-align: left;
    }
    .pass-info-item {
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        border-radius: 12px;
        padding: 10px 14px;
    }
    .pass-info-label {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: 0.5px;
    }
    .pass-info-value {
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
        margin-top: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .waiting-indicator {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        background: #fef3c7;
        color: #92400e;
        margin-bottom: 16px;
    }
    .pulse-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #f59e0b;
        animation: pulseAnimation 1.5s infinite;
    }
    @keyframes pulseAnimation {
        0% { transform: scale(0.95); opacity: 0.7; }
        50% { transform: scale(1.4); opacity: 1; }
        100% { transform: scale(0.95); opacity: 0.7; }
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

@if ($notFound)
    <div class="pass-container">
        <div class="pass-card" style="padding: 40px 24px;">
            <div style="font-size: 48px; margin-bottom: 12px;">🔍</div>
            <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0 0 8px;">Visitor Pass Not Found</h2>
            <p style="font-size: 14px; color: #64748b; line-height: 1.5; margin: 0 0 24px;">This visitor pass link is invalid or has expired. Please ask your resident host to generate a new pass.</p>
            <a href="/" class="btn btn-outline" style="text-decoration: none; padding: 10px 20px; border-radius: 12px; font-weight: 700;">Return Home</a>
        </div>
    </div>
@else
    @php
        $isEntered = ($visitor->status === 'Entered');
        $isPending = ($visitor->status === 'Pending' || $visitor->status === 'Awaiting Admin Approval');
        $isRejected = ($visitor->status === 'Rejected');
        // Step 5: Direct instant access to routing map for visitors without guard validation
        $canViewMap = !$isRejected;
    @endphp

    <!-- GATE PASS CARD (Can be toggled while viewing map) -->
    <div class="pass-container" id="passCardContainer" style="{{ $canViewMap ? 'display: none;' : '' }}">
        <div class="pass-card">
            <div class="pass-header">
                <h2>Althesa Residences</h2>
                <p>Digital Visitor Gate Pass</p>
            </div>

            <div class="pass-body">
                @if ($isPending)
                    <div style="padding: 20px 0;">
                        <div style="font-size: 40px; margin-bottom: 12px;">⏳</div>
                        <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0 0 8px;">Awaiting Approval</h3>
                        <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin: 0 0 16px;">
                            Your visitor pass request has been submitted by <strong>{{ $hostName }}</strong> and is waiting for administrator approval.
                        </p>
                        <div class="waiting-indicator">
                            <span class="pulse-dot"></span> Checking status automatically...
                        </div>
                        <button type="button" onclick="showMapViewport()" class="btn btn-primary" style="width: 100%; margin-top: 14px; padding: 12px 18px; border-radius: 12px; font-weight: 800; border: none; cursor: pointer;">
                            🗺️ View Live Route Map
                        </button>
                    </div>
                @elseif ($isRejected)
                    <div style="padding: 20px 0;">
                        <div style="font-size: 40px; margin-bottom: 12px;">❌</div>
                        <h3 style="font-size: 18px; font-weight: 800; color: #991b1b; margin: 0 0 8px;">Pass Declined</h3>
                        <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin: 0;">
                            This visitor access request was not approved by administration. Please coordinate with your resident host.
                        </p>
                    </div>
                @else
                    <!-- Approved - Show PIN to Guard -->
                    <div class="waiting-indicator" id="statusPill">
                        <span class="pulse-dot"></span> Awaiting Guard Check-In
                    </div>

                    <div class="pin-display-box">
                        <span style="font-size: 11px; font-weight: 800; color: #0284c7; text-transform: uppercase; letter-spacing: 1px;">Show this PIN at Gate 1</span>
                        <div class="pin-digits" id="pinDisplay">{{ $visitor->pin ?? '------' }}</div>
                        <button type="button" onclick="copyPin('{{ $visitor->pin }}')" style="background: none; border: none; color: #0284c7; font-size: 12px; font-weight: 700; cursor: pointer; padding: 4px 8px; text-decoration: underline;">
                            📋 Copy PIN Code
                        </button>
                    </div>

                    <div class="pass-info-grid">
                        <div class="pass-info-item">
                            <div class="pass-info-label">Visitor</div>
                            <div class="pass-info-value" title="{{ $visitor->visitor_name }}">{{ $visitor->visitor_name }}</div>
                        </div>
                        <div class="pass-info-item">
                            <div class="pass-info-label">Host</div>
                            <div class="pass-info-value" title="{{ $hostName }}">{{ $hostName }}</div>
                        </div>
                        <div class="pass-info-item" style="grid-column: span 2;">
                            <div class="pass-info-label">Destination</div>
                            <div class="pass-info-value" style="color: #0284c7;">📍 {{ $destination }}</div>
                        </div>
                        <div class="pass-info-item">
                            <div class="pass-info-label">Valid Date</div>
                            <div class="pass-info-value">{{ $visitor->validity }}</div>
                        </div>
                        <div class="pass-info-item">
                            <div class="pass-info-label">Vehicle Plate</div>
                            <div class="pass-info-value">{{ $visitor->plate_number ?: 'N/A' }}</div>
                        </div>
                    </div>

                    <p style="font-size: 12px; color: #64748b; line-height: 1.5; margin: 0 0 16px;" id="instructionText">
                        Present this 6-digit PIN to the security guard upon arrival at the subdivision gate.
                    </p>
                    <button type="button" onclick="showMapViewport()" class="btn btn-primary" style="width: 100%; padding: 12px 18px; border-radius: 12px; font-weight: 800; border: none; cursor: pointer;">
                        🗺️ Back to Live Route Map
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- ROUTING MAP VIEW (Unlocked immediately for visitors) -->
    <div class="routing-viewport nav-ui-collapsed" id="mapViewport" style="{{ $canViewMap ? '' : 'display: none;' }}">
        <!-- Floating Destination Info -->
        <div class="route-info-bar">
            <div class="dest-badge-icon">📍</div>
            <div class="dest-text-group">
                <div class="dest-name" id="uiDestName">{{ $destination }}</div>
                <div class="dest-sub" id="uiHostName">Visitor: {{ $visitor->visitor_name }} • PIN: <strong>{{ $visitor->pin ?? 'N/A' }}</strong></div>
            </div>
            <div class="route-bar-actions">
                <button type="button" onclick="showPassCard()" class="btn-pin-pill" title="View Gate Pass & PIN">
                    🎟️ PIN
                </button>
                <button type="button" id="btnCollapseUi" onclick="toggleNavUiCollapse()" class="btn-directions-toggle" title="Toggle directions HUD">
                    <span id="collapseLabel">Directions</span> <span id="collapseIcon">▼</span>
                </button>
            </div>
        </div>

        <!-- Live Navigation Turn-by-Turn & Status HUD (Hidden by default, expands on toggle) -->
        <div id="liveNavHud" class="live-nav-hud" style="display: none;">
            <div class="nav-hud-top">
                <div class="nav-hud-badge" id="hudNavBadge">
                    <span class="nav-pulse-dot"></span> <span id="hudBadgeText">Connecting to GPS...</span>
                </div>
                <div class="hud-top-actions">
                    <button type="button" id="btnToggleMapMode" onclick="toggleMapLayerMode()" class="hud-tool-btn" title="Toggle Satellite layer">
                        🛰️ Satellite
                    </button>
                    <button type="button" id="hudRecenterBtn" class="hud-recenter-btn" title="Center map on your location">
                        🎯 Recenter
                    </button>
                </div>
            </div>
            <div class="nav-hud-metrics">
                <div class="nav-metric">
                    <div class="nav-metric-label">DISTANCE</div>
                    <div class="nav-metric-val" id="hudDistanceVal">--</div>
                </div>
                <div class="nav-metric-sep"></div>
                <div class="nav-metric">
                    <div class="nav-metric-label">EST. TIME</div>
                    <div class="nav-metric-val" id="hudTimeVal">--</div>
                </div>
            </div>
            <div class="nav-hud-step" id="hudStepInstruction">
                Locating fastest route to {{ $destination }}...
            </div>
            <div class="nav-hud-sub" id="hudSimTip">
                🛰️ Acquiring live GPS signal...
            </div>
        </div>

        <!-- Map Container -->
        <div id="mapContainer"></div>

        <!-- Floating Legend (Collapsible, collapsed by default) -->
        <div class="route-legend" id="routeLegend" onclick="toggleLegendCollapse()">
            <div style="display: flex; justify-content: space-between; align-items: center; cursor: pointer; gap: 8px;">
                <span style="font-size: 11px; font-weight: 800; color: #0f172a;">🗺️ Legend</span>
                <span id="legendChevron" style="font-size: 10px; color: #64748b;">▶</span>
            </div>
            <div class="legend-items" id="legendItems" style="display: none;">
                <div class="legend-row">
                    <div style="width: 12px; height: 12px; background: #2563eb; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 0 2px #2563eb;"></div>
                    Your Location (Live GPS)
                </div>
                <div class="legend-row">
                    <div style="width: 12px; height: 12px; background: #10b981; border-radius: 50%; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"></div>
                    Entrance Gate
                </div>
                <div class="legend-row">
                    <div style="width: 12px; height: 12px; background: #ef4444; border-radius: 50%; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"></div>
                    Destination ({{ $destination }})
                </div>
                <div class="legend-row">
                    <div style="width: 22px; height: 6px; background: #2563eb; border-radius: 3px; border: 1.5px solid #1d4ed8;"></div>
                    Navigation Route (OSRM)
                </div>
            </div>
        </div>
    </div>
@endif
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-rotate@0.2.8/dist/leaflet-rotate.js"></script>
<script src="{{ asset('js/dijkstra.js') }}"></script>
<script src="{{ asset('js/gis-map.js') }}"></script>

<script>
    const accessToken = "{{ $token ?? '' }}";
    let currentStatus = "{{ $visitor->status ?? '' }}";
    const endNode = @json($endNode ?? null);
    let mapInitialized = false;
    let pollInterval = null;

    function copyPin(code) {
        if (!code) return;
        navigator.clipboard.writeText(code).then(() => {
            alert('PIN ' + code + ' copied to clipboard!');
        }).catch(() => {
            const temp = document.createElement('textarea');
            temp.value = code;
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
            alert('PIN ' + code + ' copied to clipboard!');
        });
    }

    let isUiCollapsed = true;
    function toggleNavUiCollapse() {
        isUiCollapsed = !isUiCollapsed;
        const viewport = document.getElementById('mapViewport');
        const hud = document.getElementById('liveNavHud');
        const label = document.getElementById('collapseLabel');
        const icon = document.getElementById('collapseIcon');

        if (viewport) {
            viewport.classList.toggle('nav-ui-collapsed', isUiCollapsed);
        }
        if (hud) {
            hud.style.display = isUiCollapsed ? 'none' : 'flex';
        }
        if (label) {
            label.textContent = isUiCollapsed ? 'Directions' : 'Hide';
        }
        if (icon) {
            icon.textContent = isUiCollapsed ? '▼' : '▲';
        }
    }

    function toggleLegendCollapse() {
        const items = document.getElementById('legendItems');
        const chev = document.getElementById('legendChevron');
        if (items) {
            const isHidden = items.style.display === 'none';
            items.style.display = isHidden ? 'flex' : 'none';
            if (chev) chev.textContent = isHidden ? '▼' : '▶';
        }
    }

    function toggleMapLayerMode() {
        if (window.toggleGISBaseLayer) {
            window.toggleGISBaseLayer();
        }
    }

    function launchRoutingMap() {
        if (mapInitialized) return;
        mapInitialized = true;

        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }

        const passCardContainer = document.getElementById('passCardContainer');
        const mapViewport = document.getElementById('mapViewport');

        if (passCardContainer) passCardContainer.style.display = 'none';
        if (mapViewport) mapViewport.style.display = 'block';

        // Initialize GIS Dijkstra map with Live Hybrid Navigation
        initGISMap('mapContainer', {
            interactive: true,
            showRouting: true,
            endNode: endNode,
            destinationName: "{{ $destination ?? '' }}",
            visitorName: "{{ $visitor?->visitor_name ?? '' }}",
            hostName: "{{ $hostName ?? '' }}",
            showCalibration: false,
            liveNavigation: true
        });
    }

    function checkLiveStatus() {
        if (!accessToken || currentStatus === 'Entered' || currentStatus === 'Rejected') return;

        fetch(`/api/v/${accessToken}/status`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                if (data.status === 'Entered') {
                    currentStatus = 'Entered';
                    const statusPill = document.getElementById('statusPill');
                    if (statusPill) {
                        statusPill.style.background = '#dcfce7';
                        statusPill.style.color = '#166534';
                        statusPill.innerHTML = '✅ Gate Entry Verified!';
                    }
                } else if (data.status === 'Approved' && currentStatus === 'Pending') {
                    currentStatus = 'Approved';
                    location.reload();
                }
            })
            .catch(err => console.warn('Status check warning:', err));
    }

    function showPassCard() {
        const passCardContainer = document.getElementById('passCardContainer');
        const mapViewport = document.getElementById('mapViewport');
        if (mapViewport) mapViewport.style.display = 'none';
        if (passCardContainer) passCardContainer.style.display = 'flex';
    }

    function showMapViewport() {
        const passCardContainer = document.getElementById('passCardContainer');
        const mapViewport = document.getElementById('mapViewport');
        if (passCardContainer) passCardContainer.style.display = 'none';
        if (mapViewport) mapViewport.style.display = 'block';
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (currentStatus !== 'Rejected') {
            launchRoutingMap();
        }
        if (accessToken && currentStatus === 'Pending') {
            pollInterval = setInterval(checkLiveStatus, 5000);
        }
    });
</script>
@endsection
