@extends('layouts.app')

@section('title', 'Subdivision Routing Guide | Althesa Residences')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="{{ asset('css/views/visitor-routing.css') }}?v={{ filemtime(public_path('css/views/visitor-routing.css')) }}">

<div class="routing-viewport nav-ui-collapsed" id="mapViewport">
    <!-- Floating Destination Info -->
    <div class="route-info-bar">
        <div class="dest-badge-icon">📍</div>
        <div class="dest-text-group">
            <div class="dest-name" id="uiDestName">Block N/A, Lot N/A</div>
            <div class="dest-sub" id="uiHostName">Host: Loading...</div>
        </div>
        <div class="route-bar-actions">
            <button type="button" id="btnStartNav" onclick="toggleStartNavigation()" class="btn-start-nav" title="Start turn-by-turn directions">
                🧭 Start
            </button>
            <button type="button" id="btnCollapseUi" onclick="toggleNavUiCollapse()" class="btn-directions-toggle" title="Toggle directions & steps">
                <span id="collapseLabel">Steps</span> <span id="collapseIcon">▼</span>
            </button>
        </div>
    </div>

    <!-- Google Maps Style Active Turn-by-Turn Banner -->
    <div id="activeNavBanner" class="active-nav-banner">
        <div class="active-nav-main">
            <div class="active-nav-arrow" id="activeNavArrow">⬆️</div>
            <div class="active-nav-text">
                <div class="active-nav-inst" id="activeNavInst" style="font-size: 16px;">Calculating route...</div>
            </div>
        </div>
        <div class="active-nav-subrow">
            <span id="activeNavNext">Then: Follow route to destination</span>
            <div class="active-nav-controls">
                <button type="button" id="btnHeadingUp" onclick="toggleHeadingUpMode()" class="active-nav-btn" title="Rotate map to match direction">🧭 North</button>
                <button type="button" onclick="toggleStartNavigation(false)" class="active-nav-btn stop">✕ Exit</button>
            </div>
        </div>
    </div>

    <!-- Live Navigation Turn-by-Turn & Status HUD -->
    <div id="liveNavHud" class="live-nav-hud" style="display: none;">
        <div class="nav-hud-top">
            <div class="nav-hud-badge" id="hudNavBadge">
                <span class="nav-pulse-dot"></span> <span id="hudBadgeText">Connecting to GPS...</span>
            </div>
            <div class="hud-top-actions">
                <button type="button" onclick="if(window.toggleGISBaseLayer)window.toggleGISBaseLayer()" class="hud-tool-btn" title="Toggle Satellite layer">
                    🛰️ Satellite
                </button>
                <button type="button" id="hudRecenterBtn" class="hud-recenter-btn" title="Center map on your location">
                    🎯 Recenter
                </button>
            </div>
        </div>
        <div class="nav-hud-metrics">
            <div class="nav-metric">
                <div class="nav-metric-label">EST. TIME</div>
                <div class="nav-metric-val" id="hudTimeVal">--</div>
            </div>
        </div>
        <div class="nav-hud-step" id="hudStepInstruction">
            Locating fastest route...
        </div>
        <div class="nav-hud-sub" id="hudSimTip">
            🛰️ Acquiring live GPS signal...
        </div>
        <div class="nav-steps-section" id="navStepsSection">
            <div class="nav-steps-header" onclick="toggleStepsList()">
                <span>📋 Step-by-Step Guide (<span id="navStepsCount">0</span>)</span>
                <span id="navStepsChevron">▼</span>
            </div>
            <div class="nav-steps-list" id="navStepsList"></div>
        </div>
    </div>

    <!-- Map -->
    <div id="mapContainer"></div>

    <!-- Active Navigation Bottom Bar -->
    <div id="activeNavBottom" class="active-nav-bottom">
        <div class="active-nav-eta">
            <div class="active-nav-eta-time" id="activeNavEtaTime">-- min</div>
            <div class="active-nav-eta-dist" id="activeNavEtaDist">Destination</div>
        </div>
        <div class="active-nav-bottom-actions">
            <button type="button" onclick="openStepsFromActive()" class="hud-tool-btn" title="View all steps">📋 Steps</button>
            <button type="button" onclick="document.getElementById('hudRecenterBtn')?.click()" class="hud-recenter-btn" title="Recenter">🎯</button>
            <button type="button" onclick="toggleStartNavigation(false)" class="btn-start-nav is-active" style="padding: 5px 10px; font-size: 11px;">Exit</button>
        </div>
    </div>

    <!-- Floating Legend -->
    <div class="route-legend">
        <div class="legend-row">
            <div style="width: 12px; height: 12px; background: #2563eb; border-radius: 50%; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"></div>
            Your Destination
        </div>
        <div class="legend-row">
            <div style="width: 18px; height: 3px; background: #2563eb; border-radius: 2px; opacity: 0.9;"></div>
            Suggested Route
        </div>
        <div class="legend-row">
            <div style="width: 18px; height: 3px; background: #64748b; border-radius: 2px; opacity: 0.85;"></div>
            Alternative Route
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-rotate@0.2.8/dist/leaflet-rotate.js"></script>
<script src="{{ asset('js/dijkstra.js') }}?v={{ filemtime(public_path('js/dijkstra.js')) }}"></script>
<script src="{{ asset('js/gis-map.js') }}?v={{ filemtime(public_path('js/gis-map.js')) }}"></script>
<script>
    let isUiCollapsed = true;
    function toggleNavUiCollapse(forceOpen) {
        isUiCollapsed = (typeof forceOpen === 'boolean') ? !forceOpen : !isUiCollapsed;
        const viewport = document.getElementById('mapViewport');
        const hud = document.getElementById('liveNavHud');
        const label = document.getElementById('collapseLabel');
        const icon = document.getElementById('collapseIcon');
        if (viewport) viewport.classList.toggle('nav-ui-collapsed', isUiCollapsed);
        if (hud) hud.style.display = isUiCollapsed ? 'none' : 'flex';
        if (label) label.textContent = isUiCollapsed ? 'Steps' : 'Hide';
        if (icon) icon.textContent = isUiCollapsed ? '▼' : '▲';
    }
    function toggleStartNavigation(forceState) {
        if (window.toggleGISStartNavigation) window.toggleGISStartNavigation(forceState);
    }
    function toggleHeadingUpMode() {
        if (window.toggleGISHeadingUp) window.toggleGISHeadingUp();
    }
    function openStepsFromActive() {
        toggleNavUiCollapse(true);
        const hud = document.getElementById('liveNavHud');
        if (hud) hud.style.top = '126px';
        const list = document.getElementById('navStepsList');
        const chev = document.getElementById('navStepsChevron');
        if (list) list.style.display = 'flex';
        if (chev) chev.textContent = '▼';
    }
    function toggleStepsList() {
        const list = document.getElementById('navStepsList');
        const chev = document.getElementById('navStepsChevron');
        if (list) {
            const isHidden = list.style.display === 'none';
            list.style.display = isHidden ? 'flex' : 'none';
            if (chev) chev.textContent = isHidden ? '▼' : '▶';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const tokenParam = urlParams.get('token');
        if (tokenParam) {
            window.location.href = `/v/${tokenParam}`;
            return;
        }

        const ctx = JSON.parse(localStorage.getItem('visitor_routing_context') || '{}');
        
        // Update UI
        if (ctx && ctx.destination) {
            document.getElementById('uiDestName').textContent = ctx.destination;
            document.getElementById('uiHostName').textContent = 'Visitor: ' + (ctx.visitor_name || 'Guest');
        }

        // Format destination to match "BX LX"
        let endNode = null;
        if (ctx.destination && ctx.destination.includes('Block')) {
            const parts = ctx.destination.split(', ');
            if(parts.length === 2) {
                const b = parts[0].replace('Block ', '');
                const l = parts[1].replace('Lot ', '');
                endNode = `B${b} L${l}`;
            }
        }

        // Initialize GIS Map
        initGISMap('mapContainer', {
            interactive: true,
            showRouting: true,
            endNode: endNode,
            destinationName: ctx.destination || endNode,
            visitorName: ctx.visitor_name || 'Guest',
            showCalibration: false,
            liveNavigation: true
        });
    });
</script>
@endsection
