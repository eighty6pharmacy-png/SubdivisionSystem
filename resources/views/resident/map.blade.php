@extends('layouts.resident')

@section('title', 'Community Map')

@section('content')
<div class="fade-in">
    <div style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <h1 style="font-size: 32px; font-weight: 800; color: #0f172a; margin: 0;">Interactive Map</h1>
            <p style="color: #64748b; margin-top: 8px; font-size: 15px;">Click on any two houses to calculate the shortest path (blue) and an alternative route (red).</p>
        </div>
        <div style="display: flex; gap: 16px; align-items: flex-end;">
            <div style="background: white; padding: 10px 16px; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; min-width: 200px;">
                <div style="display: flex; justify-content: space-between; font-size: 11px; color: #64748b; font-weight: 600; margin-bottom: 6px;">
                    <span>Map Rotation</span>
                    <span id="rotVal">-15°</span>
                </div>
                <input type="range" id="rotateZ" min="-180" max="180" value="-15" oninput="updateMapBearing(this.value)" style="width: 100%; accent-color: #3b82f6;">
            </div>
            <button class="btn btn-secondary" onclick="resetMap()" style="padding: 10px 20px; font-weight: 600; border-radius: 8px; height: 100%; display: inline-flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Reset Route
            </button>
        </div>
    </div>

    <div style="background: white; padding: 16px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div id="residentMapContainer" style="width: 100%; height: 600px; border-radius: 12px; background: #f8fafc; border: 1px solid #cbd5e1; z-index: 1;"></div>
    </div>
</div>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-rotate@0.2.8/dist/leaflet-rotate.js"></script>
<script src="{{ asset('js/dijkstra.js') }}"></script>
<script src="{{ asset('js/gis-map.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', async () => {
        // Initialize GIS map with interactive routing enabled
        window.gisMap = await initGISMap('residentMapContainer', {
            interactive: true,
            showRouting: true,
            interactiveRouting: true // Enables point-to-point clicking
        });
    });

    function updateMapBearing(value) {
        document.getElementById('rotVal').innerText = value + '°';
        if (window.gisMap) {
            window.gisMap.setBearing(value);
        }
    }

    function resetMap() {
        if (window.gisMap && typeof window.gisMap.clearRouting === 'function') {
            window.gisMap.clearRouting();
        } else {
            location.reload();
        }
    }
</script>
@endsection
