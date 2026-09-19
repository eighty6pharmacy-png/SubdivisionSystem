@extends('layouts.app')

@section('title', 'Subdivision Routing Guide | Althesa Residences')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="{{ asset('css/views/visitor-routing.css') }}">

<div class="routing-viewport" id="viewport">
    <!-- Floating Destination Info -->
    <div class="route-info-bar">
        <div style="width: 38px; height: 38px; background: #2563eb; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;">📍</div>
        <div>
            <div class="dest-name" id="uiDestName">Block N/A, Lot N/A</div>
            <div class="dest-sub" id="uiHostName">Host: Loading...</div>
        </div>
    </div>

    <!-- Map -->
    <div id="mapContainer"></div>

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
            <div style="width: 18px; height: 3px; background: #2563eb; border-radius: 2px; opacity: 0.8; border-bottom: 2px dashed #fff;"></div>
            Suggested Route
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
    document.addEventListener('DOMContentLoaded', () => {
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
            endNode: endNode
        });
    });
</script>
@endsection
