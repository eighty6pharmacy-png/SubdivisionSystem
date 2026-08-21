@extends('layouts.app')

@section('title', 'Subdivision Routing Guide | Althesa Residences')

@section('content')
<style>
    .routing-viewport {
        position: relative;
        overflow: hidden;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        height: calc(100vh - 80px);
        width: 100%;
        user-select: none;
        -webkit-user-select: none;
        touch-action: none;
    }

    .map-container {
        position: relative;
        transition: transform 0.2s ease-out;
        cursor: grab;
        transform-origin: center center;
        display: inline-block;
        will-change: transform;
    }

    .map-container.dragging {
        transition: none !important;
    }

    .map-container:active {
        cursor: grabbing;
    }

    .map-base {
        position: relative;
        display: inline-block;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        background-color: white;
        line-height: 0;
    }

    .map-image {
        width: 100%;
        height: auto;
        display: block;
        pointer-events: none;
        -webkit-user-drag: none;
    }

    /* ===== MARKERS (Simple Dots) ===== */

    .dest-marker {
        position: absolute;
        width: 16px;
        height: 16px;
        background: #2563eb;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.4);
        transform: translate(-50%, -50%);
        z-index: 10;
    }

    .gate-marker {
        position: absolute;
        width: 16px;
        height: 16px;
        background: #10b981;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
        transform: translate(-50%, -50%);
        z-index: 10;
    }

    /* ===== ROUTE PATH ===== */
    .routing-svg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 5;
    }

    .route-path {
        fill: none;
        stroke: #2563eb;
        stroke-width: 4;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-dasharray: 12 8;
        animation: routeDash 1.5s linear infinite;
        filter: drop-shadow(0 2px 4px rgba(37, 99, 235, 0.3));
    }

    @keyframes routeDash {
        from { stroke-dashoffset: 40; }
        to { stroke-dashoffset: 0; }
    }

    /* ===== FLOATING INFO BAR ===== */
    .route-info-bar {
        position: absolute;
        top: 16px;
        right: 16px;
        z-index: 20;
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(12px);
        padding: 12px 24px;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .route-info-bar .dest-name {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
    }

    .route-info-bar .dest-sub {
        font-size: 11px;
        color: #64748b;
        font-weight: 600;
    }

    /* ===== FLOATING LEGEND ===== */
    .route-legend {
        position: absolute;
        bottom: 16px;
        left: 16px;
        z-index: 20;
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(12px);
        padding: 14px 18px;
        border-radius: 14px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        border: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .legend-row {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 11px;
        color: #64748b;
        font-weight: 600;
    }

    /* ===== MOBILE ADAPTATIONS ===== */
    @media (max-width: 640px) {
        .route-info-bar {
            width: calc(100% - 32px);
            padding: 10px 16px;
            top: 12px;
        }
        .route-info-bar .dest-name { font-size: 13px; }
        .route-legend {
            bottom: 12px;
            left: 12px;
            padding: 10px 14px;
            gap: 6px;
        }
        .legend-row { font-size: 10px; }
    }
</style>

<div class="routing-viewport" id="viewport">
    <!-- Floating Destination Info -->
    <div class="route-info-bar">
        <div style="width: 38px; height: 38px; background: #2563eb; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;">📍</div>
        <div>
            <div class="dest-name">Block 3, Lot 6</div>
            <div class="dest-sub">Host: Juan Dela Cruz</div>
        </div>
    </div>

    <!-- Map -->
    <div class="map-container" id="mapContainer">
        <div class="map-base" id="mapBase">
            <img src="{{ asset('images/site-plan.png') }}" class="map-image">

            <!-- ============================================== -->
            <!-- ROUTE PATH (SVG)                                -->
            <!-- Edit the "d" attribute to change the route.     -->
            <!-- Coordinates are in pixels relative to image size. -->
            <!-- Format: M startX,startY L x,y L x,y ...        -->
            <!-- ============================================== -->
            <svg class="routing-svg" viewBox="0 0 800 1100" preserveAspectRatio="none" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
                <path class="route-path" d="M 310,380 L 435, 541 L 324, 636 L 314, 623 " />
            </svg>

            <!-- ============================================== -->
            <!-- GATE MARKER (Entrance)                          -->
            <!-- Edit top/left % to reposition on the map.       -->
            <!-- ============================================== -->
            <div class="gate-marker" style="top: 34.2%; left: 38.6%;"></div>

            <!-- ============================================== -->
            <!-- DESTINATION MARKER                              -->
            <!-- Edit top/left % to reposition on the map.       -->
            <!-- ============================================== -->
            <div class="dest-marker" style="top: 56.7%; left: 39.2%;"></div>

        </div>
    </div>

    <!-- Floating Legend -->
    <div class="route-legend">
        <div class="legend-row">
            <div style="width: 12px; height: 12px; background: #10b981; border-radius: 50%; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"></div>
            Entrance Gate
        </div>
        <div class="legend-row">
            <div style="width: 12px; height: 12px; background: #2563eb; border-radius: 50%; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"></div>
            Your Destination
        </div>
        <div class="legend-row">
            <div style="width: 18px; height: 3px; background: #2563eb; border-radius: 2px;"></div>
            Suggested Route
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const map = document.getElementById('mapContainer');
    const viewport = document.getElementById('viewport');

    // ==========================================
    // DEFAULT VIEW: 222deg (Site tilt + flip)
    // Back to previous angle as requested.
    // ==========================================
    let currentZoom = window.innerWidth < 640 ? 0.6 : 1.2;
    let currentRotation = 222;
    let translateX = 0;
    let translateY = 0;

    function updateTransform() {
        map.style.transform = `translate(${translateX}px, ${translateY}px) rotate(${currentRotation}deg) scale(${currentZoom})`;
    }

    // Draggable Map (free movement)
    let isDragging = false;
    let startX, startY, initialTx, initialTy;

    viewport.addEventListener('mousedown', (e) => {
        isDragging = true;
        map.classList.add('dragging');
        startX = e.pageX;
        startY = e.pageY;
        initialTx = translateX;
        initialTy = translateY;
    });

    viewport.addEventListener('mouseleave', () => { 
        isDragging = false; 
        map.classList.remove('dragging');
    });
    
    viewport.addEventListener('mouseup', () => { 
        isDragging = false; 
        map.classList.remove('dragging');
    });

    viewport.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        e.preventDefault();
        const walkX = e.pageX - startX;
        const walkY = e.pageY - startY;
        translateX = initialTx + walkX;
        translateY = initialTy + walkY;
        updateTransform();
    });

    // Scroll/Pinch to Zoom
    viewport.addEventListener('wheel', (e) => {
        e.preventDefault();
        const delta = e.deltaY > 0 ? -0.1 : 0.1;
        currentZoom = Math.min(Math.max(currentZoom + delta, 0.3), 3.0);
        updateTransform();
    }, { passive: false });

    // Touch support for mobile pinch-to-zoom
    let lastTouchDist = 0;
    viewport.addEventListener('touchstart', (e) => {
        if (e.touches.length === 2) {
            lastTouchDist = Math.hypot(
                e.touches[0].pageX - e.touches[1].pageX,
                e.touches[0].pageY - e.touches[1].pageY
            );
        } else if (e.touches.length === 1) {
            isDragging = true;
            map.classList.add('dragging');
            startX = e.touches[0].pageX;
            startY = e.touches[0].pageY;
            initialTx = translateX;
            initialTy = translateY;
        }
    }, { passive: false });

    viewport.addEventListener('touchmove', (e) => {
        if (e.touches.length === 2) {
            e.preventDefault();
            const dist = Math.hypot(
                e.touches[0].pageX - e.touches[1].pageX,
                e.touches[0].pageY - e.touches[1].pageY
            );
            const delta = (dist - lastTouchDist) * 0.005;
            currentZoom = Math.min(Math.max(currentZoom + delta, 0.3), 3.0);
            lastTouchDist = dist;
            updateTransform();
        } else if (e.touches.length === 1 && isDragging) {
            e.preventDefault();
            const walkX = e.touches[0].pageX - startX;
            const walkY = e.touches[0].pageY - startY;
            translateX = initialTx + walkX;
            translateY = initialTy + walkY;
            updateTransform();
        }
    }, { passive: false });

    viewport.addEventListener('touchend', () => { 
        isDragging = false; 
        map.classList.remove('dragging');
    });

    updateTransform();
</script>
@endsection
