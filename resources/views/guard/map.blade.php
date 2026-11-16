@extends('layouts.guard')

@section('title', 'Subdivision Radar & Live Visitor Monitoring')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">
<link rel="stylesheet" href="{{ asset('css/views/guard-dashboard.css') }}">

<style>
    .radar-page-grid {
        display: grid;
        grid-template-columns: 1fr 360px;
        gap: 20px;
        min-height: 640px;
    }
    @media (max-width: 992px) {
        .radar-page-grid {
            grid-template-columns: 1fr;
        }
    }
    .visitor-pulse-marker {
        position: relative;
        width: 32px;
        height: 32px;
    }
    .visitor-pulse-beacon {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #2563eb;
        border: 3px solid #ffffff;
        box-shadow: 0 0 10px rgba(37,99,235,0.7);
        position: absolute;
        top: 7px;
        left: 7px;
        z-index: 2;
    }
    .visitor-pulse-beacon.arrived {
        background: #16a34a;
        box-shadow: 0 0 12px rgba(22,163,74,0.9);
    }
    .visitor-pulse-ring {
        position: absolute;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(37,99,235,0.35);
        animation: radarPing 1.8s cubic-bezier(0, 0, 0.2, 1) infinite;
        z-index: 1;
    }
    .visitor-pulse-beacon.arrived + .visitor-pulse-ring {
        background: rgba(22,163,74,0.4);
    }
    @keyframes radarPing {
        75%, 100% {
            transform: scale(2.2);
            opacity: 0;
        }
    }
</style>

<div class="fade-in">
    <!-- Header -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 16px 24px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                🛰️
            </div>
            <div>
                <h1 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 10px;">
                    Subdivision Live Visitor Radar
                    <span id="pusherStatusBadgeDedicated" style="font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 14px; background: #1e293b; color: #94a3b8; border: 1px solid #334155;">
                        Connecting...
                    </span>
                </h1>
                <p style="color: #64748b; font-size: 13px; margin-top: 3px;">
                    Live GPS monitoring of entered visitors and gate authorization tracking.
                </p>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <span id="activeVisitorsDedicatedCount" style="font-size: 13px; font-weight: 700; background: #0f172a; color: #38bdf8; padding: 8px 16px; border-radius: 20px; border: 1px solid #334155;">
                0 Active Visitors
            </span>
            <button type="button" onclick="centerDedicatedMap()" class="btn btn-secondary" style="padding: 8px 14px; font-size: 13px; font-weight: 700; border-radius: 8px;">
                🎯 Center Map
            </button>
            <a href="/guard/dashboard" class="btn btn-primary" style="padding: 8px 16px; font-size: 13px; font-weight: 700; border-radius: 8px; background: var(--guard-primary);">
                🛡️ Gate Scanner
            </a>
        </div>
    </div>

    <!-- Arrival Notice Banner -->
    <div id="arrivalNoticeBannerDedicated" style="display: none; padding: 14px 24px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; color: #065f46; font-size: 14px; font-weight: 700; margin-bottom: 20px; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 20px;">🎉</span>
            <span id="arrivalNoticeTextDedicated">Visitor has arrived at their destination!</span>
        </div>
        <button type="button" onclick="dismissArrivalNoticeDedicated()" style="background: none; border: none; font-size: 18px; cursor: pointer; color: #047857;">✕</button>
    </div>

    <!-- Main Grid -->
    <div class="radar-page-grid">
        <!-- Map Canvas -->
        <div class="analytic-card" style="padding: 0; overflow: hidden; position: relative; border-radius: 16px; border: 1px solid #cbd5e1; min-height: 640px; background: #0b1120;">
            <div id="guardDedicatedMap" style="width: 100%; height: 640px; min-height: 640px;"></div>

            <!-- Legend Overlay -->
            <div style="position: absolute; bottom: 20px; left: 20px; z-index: 1000; background: rgba(15, 23, 42, 0.90); backdrop-filter: blur(8px); padding: 10px 16px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.12); color: white; font-size: 12px; display: flex; gap: 16px; align-items: center;">
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #3b82f6; display: inline-block;"></span>
                    En Route
                </div>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #22c55e; display: inline-block;"></span>
                    Arrived at Destination
                </div>
            </div>
        </div>

        <!-- Sidebar Panel: Active Visitors -->
        <div class="analytic-card" style="padding: 0; overflow: hidden; border-radius: 16px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; height: 640px;">
            <div style="padding: 18px 20px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;">Visitors In-Transit</h3>
                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Today's authorized entries</div>
                </div>
                <span id="activeVisitorsSideBadge" style="font-size: 11px; font-weight: 800; background: #e0f2fe; color: #0369a1; padding: 3px 10px; border-radius: 12px;">0 Active</span>
            </div>

            <div id="dedicatedVisitorsList" style="flex: 1; overflow-y: auto; padding: 14px;">
                <div style="text-align: center; padding: 48px 16px; color: #94a3b8; font-size: 13px; font-weight: 600;">
                    No visitors currently moving inside the subdivision.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-rotate@0.2.8/dist/leaflet-rotate.js"></script>
<script src="{{ asset('js/dijkstra.js') }}"></script>
<script src="{{ asset('js/gis-map.js') }}"></script>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const PUSHER_KEY = @json(config('broadcasting.connections.pusher.key'));
    const PUSHER_CLUSTER = @json(config('broadcasting.connections.pusher.options.cluster', 'ap1'));
    let dedicatedMapInstance = null;
    let liveVisitorMarkers = new Map();
    let hasAlertedFor = new Set();

    function playArrivalChime() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const now = ctx.currentTime;
            
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(587.33, now);
            gain1.gain.setValueAtTime(0.25, now);
            gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start(now);
            osc1.stop(now + 0.35);

            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(880.00, now + 0.15);
            gain2.gain.setValueAtTime(0.28, now + 0.15);
            gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.65);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.start(now + 0.15);
            osc2.stop(now + 0.65);
        } catch(e) {}
    }

    function dismissArrivalNoticeDedicated() {
        const b = document.getElementById('arrivalNoticeBannerDedicated');
        if (b) b.style.display = 'none';
    }

    function showArrivalNoticeDedicated(text) {
        const b = document.getElementById('arrivalNoticeBannerDedicated');
        const t = document.getElementById('arrivalNoticeTextDedicated');
        if (b && t) {
            t.textContent = text;
            b.style.display = 'flex';
        }
    }

    async function initDedicatedMap() {
        const container = document.getElementById('guardDedicatedMap');
        if (!container) return;

        try {
            dedicatedMapInstance = await initGISMap('guardDedicatedMap', {
                interactive: true,
                showRouting: false,
                showCalibration: false,
                liveNavigation: false
            });
            setTimeout(() => {
                if (dedicatedMapInstance) dedicatedMapInstance.invalidateSize();
            }, 300);
            fetchLiveVisitors();
            initPusher();
        } catch (err) {
            console.warn('Dedicated map init error:', err);
            initPusher();
        }
    }

    function centerDedicatedMap() {
        if (!dedicatedMapInstance) return;
        if (liveVisitorMarkers.size > 0) {
            const group = [];
            liveVisitorMarkers.forEach(item => {
                const latlng = item.marker.getLatLng();
                group.push([latlng.lat, latlng.lng]);
            });
            dedicatedMapInstance.fitBounds(L.latLngBounds(group), { padding: [60, 60], maxZoom: 19 });
        } else {
            dedicatedMapInstance.setView([13.5855, 123.2730], 18);
        }
    }

    function focusVisitorOnMap(visitorId) {
        const item = liveVisitorMarkers.get(Number(visitorId)) || liveVisitorMarkers.get(String(visitorId));
        if (item && dedicatedMapInstance) {
            const pos = item.marker.getLatLng();
            dedicatedMapInstance.setView([pos.lat, pos.lng], 19, { animate: true });
            item.marker.openPopup();
        }
    }

    function onVisitorLocationUpdate(vis) {
        if (!vis || !vis.lat || !vis.lng) return;
        const vId = vis.visitor_id;

        const isArrived = !!vis.is_at_destination;
        const beaconClass = isArrived ? 'visitor-pulse-beacon arrived' : 'visitor-pulse-beacon';
        const customHtml = `
            <div class="visitor-pulse-marker">
                <div class="${beaconClass}"></div>
                <div class="visitor-pulse-ring"></div>
            </div>
        `;
        const pulseIcon = L.divIcon({
            html: customHtml,
            className: '',
            iconSize: [32, 32],
            iconAnchor: [16, 16]
        });

        if (isArrived && !hasAlertedFor.has(vId)) {
            hasAlertedFor.add(vId);
            playArrivalChime();
            showArrivalNoticeDedicated(`🎉 Visitor ${vis.visitor_name} has arrived at destination (${vis.destination})!`);
        }

        const popupContent = `
            <div style="font-family: sans-serif; font-size: 13px; min-width: 190px;">
                <div style="font-weight: 800; color: #0f172a; font-size: 14px; margin-bottom: 4px;">${vis.visitor_name}</div>
                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Destination:</div>
                <div style="color: #1e40af; font-weight: 700;">${vis.destination}</div>
                <div style="font-size: 12px; color: #475569; margin-top: 4px;">Host: <strong>${vis.host_name}</strong></div>
                ${vis.plate_number && vis.plate_number !== 'N/A' ? `<div style="font-size: 12px; color: #475569;">Plate: <code style="background:#f1f5f9; padding:2px 4px; border-radius:4px;">${vis.plate_number}</code></div>` : ''}
                <div style="margin-top: 6px; padding-top: 6px; border-top: 1px solid #e2e8f0; font-size: 11px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 800; color: ${isArrived ? '#16a34a' : '#2563eb'};">
                        ${isArrived ? '✅ Arrived at House' : '🚗 En Route'}
                    </span>
                    <span style="color: #94a3b8;">${vis.last_ping_at || 'Just now'}</span>
                </div>
            </div>
        `;

        if (liveVisitorMarkers.has(vId)) {
            const entry = liveVisitorMarkers.get(vId);
            entry.marker.setLatLng([vis.lat, vis.lng]);
            entry.marker.setIcon(pulseIcon);
            entry.marker.setPopupContent(popupContent);
            entry.data = vis;
        } else if (dedicatedMapInstance) {
            const m = L.marker([vis.lat, vis.lng], { icon: pulseIcon }).addTo(dedicatedMapInstance);
            m.bindPopup(popupContent);
            m.bindTooltip(`📍 ${vis.visitor_name} (${isArrived ? 'Arrived' : 'En Route'})`, { direction: 'top', offset: [0, -12] });
            liveVisitorMarkers.set(vId, { marker: m, data: vis });
        }

        renderDedicatedVisitorsList();
    }

    function renderDedicatedVisitorsList() {
        const listEl = document.getElementById('dedicatedVisitorsList');
        const countEl = document.getElementById('activeVisitorsDedicatedCount');
        const sideBadgeEl = document.getElementById('activeVisitorsSideBadge');
        if (!listEl) return;

        const totalActive = liveVisitorMarkers.size;
        if (countEl) countEl.textContent = `${totalActive} Live Visitor${totalActive === 1 ? '' : 's'}`;
        if (sideBadgeEl) sideBadgeEl.textContent = `${totalActive} Active`;

        if (totalActive === 0) {
            listEl.innerHTML = `
                <div style="text-align: center; padding: 48px 16px; color: #94a3b8; font-size: 13px; font-weight: 600;">
                    No visitors currently moving inside the subdivision.
                </div>
            `;
            return;
        }

        let html = '';
        liveVisitorMarkers.forEach((item, id) => {
            const d = item.data;
            const isArrived = !!d.is_at_destination;
            const statusBg = isArrived ? '#dcfce7' : '#eff6ff';
            const statusColor = isArrived ? '#166534' : '#1e40af';
            const statusLabel = isArrived ? '✅ Arrived at House' : '🚗 En Route';

            html += `
                <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; margin-bottom: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                        <div>
                            <div style="font-weight: 800; font-size: 14px; color: #0f172a;">${d.visitor_name}</div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                📍 <strong>${d.destination}</strong>
                            </div>
                        </div>
                        <span style="font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 12px; background: ${statusBg}; color: ${statusColor}; white-space: nowrap;">
                            ${statusLabel}
                        </span>
                    </div>
                    <div style="margin-top: 8px; font-size: 11px; color: #64748b; display: flex; justify-content: space-between; align-items: center;">
                        <span>Host: <strong>${d.host_name}</strong></span>
                        <span>${d.last_ping_at || 'Just now'}</span>
                    </div>
                    <button type="button" onclick="focusVisitorOnMap('${d.visitor_id}')" style="width: 100%; margin-top: 10px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 12px; font-size: 12px; font-weight: 700; color: #334155; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
                        🎯 Focus on Map
                    </button>
                </div>
            `;
        });

        listEl.innerHTML = html;
    }

    function fetchLiveVisitors() {
        fetch('/api/guard/live-visitors')
            .then(res => res.json())
            .then(visitors => {
                if (Array.isArray(visitors)) {
                    visitors.forEach(v => {
                        if (v.lat && v.lng) {
                            onVisitorLocationUpdate(v);
                        }
                    });
                }
            })
            .catch(err => console.warn('Fetch live visitors notice:', err));
    }

    function initPusher() {
        const badge = document.getElementById('pusherStatusBadgeDedicated');

        if (!PUSHER_KEY || PUSHER_KEY === 'your_key') {
            if (badge) {
                badge.textContent = '● Auto-Polling Active';
                badge.style.background = '#0284c7';
                badge.style.color = '#e0f2fe';
                badge.style.borderColor = '#38bdf8';
            }
            setInterval(fetchLiveVisitors, 6000);
            return;
        }

        try {
            const pusher = new Pusher(PUSHER_KEY, {
                cluster: PUSHER_CLUSTER || 'ap1',
                forceTLS: true
            });

            pusher.connection.bind('connected', () => {
                if (badge) {
                    badge.textContent = '● Live Pusher Connected';
                    badge.style.background = '#166534';
                    badge.style.color = '#bbf7d0';
                    badge.style.borderColor = '#22c55e';
                }
            });

            pusher.connection.bind('disconnected', () => {
                if (badge) {
                    badge.textContent = '● Pusher Reconnecting...';
                    badge.style.background = '#854d0e';
                    badge.style.color = '#fef08a';
                    badge.style.borderColor = '#eab308';
                }
            });

            const channel = pusher.subscribe('guard-monitoring');
            channel.bind('VisitorLocationUpdated', function(data) {
                onVisitorLocationUpdate(data);
            });

            setInterval(fetchLiveVisitors, 12000);
        } catch(err) {
            console.warn('Pusher initialization notice:', err);
            setInterval(fetchLiveVisitors, 6000);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        initDedicatedMap();
    });
</script>
@endsection
