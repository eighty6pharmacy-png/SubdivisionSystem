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
    @endphp

    <!-- GATE PASS CARD (Shown while waiting for guard validation) -->
    <div class="pass-container" id="passCardContainer" style="{{ $isEntered ? 'display: none;' : '' }}">
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

                    <p style="font-size: 12px; color: #64748b; line-height: 1.5; margin: 0;" id="instructionText">
                        Present this 6-digit PIN to the security guard upon arrival at the subdivision gate. Once validated, your navigation map will <strong>automatically unlock</strong>.
                    </p>
                @endif
            </div>
        </div>
    </div>

    <!-- ROUTING MAP VIEW (Automatically unlocks once status is 'Entered') -->
    <div class="routing-viewport" id="mapViewport" style="{{ $isEntered ? '' : 'display: none;' }}">
        <!-- Floating Destination Info -->
        <div class="route-info-bar">
            <div style="width: 38px; height: 38px; background: #2563eb; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: white;">📍</div>
            <div>
                <div class="dest-name" id="uiDestName">{{ $destination }}</div>
                <div class="dest-sub" id="uiHostName">Visitor: {{ $visitor->visitor_name }} (Host: {{ $hostName }})</div>
            </div>
        </div>

        <!-- Map Container -->
        <div id="mapContainer"></div>

        <!-- Floating Legend -->
        <div class="route-legend">
            <div class="legend-row">
                <div style="width: 12px; height: 12px; background: #10b981; border-radius: 50%; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"></div>
                Entrance Gate
            </div>
            <div class="legend-row">
                <div style="width: 12px; height: 12px; background: #2563eb; border-radius: 50%; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"></div>
                Your Destination ({{ $destination }})
            </div>
            <div class="legend-row">
                <div style="width: 18px; height: 3px; background: #2563eb; border-radius: 2px; opacity: 0.8; border-bottom: 2px dashed #fff;"></div>
                Suggested Route
            </div>
            <div class="legend-row">
                <div style="width: 18px; height: 3px; background: #ef4444; border-radius: 2px; opacity: 0.8; border-bottom: 2px dashed #fff;"></div>
                Alternative Route
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

        // Initialize GIS Dijkstra map
        initGISMap('mapContainer', {
            interactive: true,
            showRouting: true,
            endNode: endNode
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
                        statusPill.innerHTML = '✅ Gate Entry Verified! Loading Map...';
                    }

                    setTimeout(() => {
                        launchRoutingMap();
                    }, 800);
                } else if (data.status === 'Approved' && currentStatus === 'Pending') {
                    // Page was pending, now approved! Reload to show PIN card
                    location.reload();
                }
            })
            .catch(err => console.warn('Status check warning:', err));
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (currentStatus === 'Entered') {
            launchRoutingMap();
        } else if (accessToken && currentStatus !== 'Rejected') {
            // Poll every 3 seconds while waiting for guard validation
            pollInterval = setInterval(checkLiveStatus, 3000);
        }
    });
</script>
@endsection
