@extends('layouts.resident')

@section('title', 'Guidance Directory')

@section('content')
@php
    $baseHouseId = isset($selectedLot) && $selectedLot
        ? ('B' . $selectedLot->block . ' L' . $selectedLot->lot_number)
        : null;
@endphp
<div class="fade-in">
    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 32px; font-weight: 800; color: #0f172a; margin: 0;">House Guidance Directory</h1>
            @if($baseHouseId)
                <p style="color: #64748b; margin-top: 8px; font-size: 15px;">
                    Your house (<strong style="color: #059669;">Block {{ $selectedLot->block }}, Lot {{ $selectedLot->lot_number }}</strong>) is set as your starting position. Click any other house on the map to start routing.
                </p>
            @else
                <p style="color: #64748b; margin-top: 8px; font-size: 15px;">
                    No house is assigned to your account yet. Click any two houses on the map to preview a route.
                </p>
            @endif
        </div>
        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            @if(isset($lots) && $lots->count() > 1 && isset($selectedLot))
                <div style="display: flex; align-items: center; gap: 8px; background: white; padding: 8px 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <label for="residentLotSelect" style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">My House:</label>
                    <select id="residentLotSelect" onchange="window.location.href='?lot_id=' + this.value" style="border: none; font-size: 13px; font-weight: 700; color: #0f172a; background: transparent; outline: none; cursor: pointer;">
                        @foreach($lots as $lot)
                            <option value="{{ $lot->id }}" {{ $lot->id === $selectedLot->id ? 'selected' : '' }}>
                                Block {{ $lot->block }}, Lot {{ $lot->lot_number }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <button class="btn btn-secondary" onclick="resetMap()" style="padding: 10px 20px; font-weight: 600; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Reset Route
            </button>
        </div>
    </div>

    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; background: white; padding: 12px 16px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 16px; font-size: 13px;">
        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 18px;">
            <span style="display: inline-flex; align-items: center; gap: 6px; color: #334155; font-weight: 600;">
                <span style="width: 12px; height: 12px; border-radius: 50%; background: #10b981; border: 2px solid #059669; display: inline-block;"></span>
                {{ $baseHouseId ? 'Your House (Block ' . $selectedLot->block . ', Lot ' . $selectedLot->lot_number . ')' : 'Start House' }}
            </span>
            <span style="display: inline-flex; align-items: center; gap: 6px; color: #334155; font-weight: 600;">
                <span style="width: 12px; height: 12px; border-radius: 50%; background: #2563eb; border: 2px solid #1d4ed8; display: inline-block;"></span>
                Destination: <strong id="routeDestLabel" style="color: #0f172a;">Click a house on the map</strong>
            </span>
        </div>
        <div style="display: flex; align-items: center; gap: 16px; color: #64748b; font-size: 12px; font-weight: 600;">
            <span style="display: inline-flex; align-items: center; gap: 6px;">
                <span style="width: 18px; height: 4px; border-radius: 2px; background: #2563eb; display: inline-block;"></span>
                Shortest Route
            </span>
            <span style="display: inline-flex; align-items: center; gap: 6px;">
                <span style="width: 18px; height: 4px; border-radius: 2px; background: #64748b; display: inline-block;"></span>
                Alternative Route
            </span>
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
<script src="{{ asset('js/dijkstra.js') }}?v={{ filemtime(public_path('js/dijkstra.js')) }}"></script>
<script src="{{ asset('js/gis-map.js') }}?v={{ filemtime(public_path('js/gis-map.js')) }}"></script>
<script>
    const residentBaseHouseId = @json($baseHouseId);

    document.addEventListener('DOMContentLoaded', async () => {
        window.gisMap = await initGISMap('residentMapContainer', {
            interactive: true,
            showRouting: true,
            interactiveRouting: true,
            bearing: -140,
            fixedStartNode: residentBaseHouseId,
            onRouteSelected: ({ end }) => {
                const destEl = document.getElementById('routeDestLabel');
                if (destEl && end) {
                    destEl.textContent = `Block ${end.block}, Lot ${end.lot}`;
                }
            }
        });
    });

    function resetMap() {
        const destEl = document.getElementById('routeDestLabel');
        if (destEl) {
            destEl.textContent = 'Click a house on the map';
        }
        if (window.gisMap && typeof window.gisMap.clearRouting === 'function') {
            window.gisMap.clearRouting();
        } else {
            location.reload();
        }
    }
</script>
@endsection
