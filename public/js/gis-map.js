// Common GIS Map Initialization Script with Real-World Georeferencing & Live Satellite Calibration

// Inject CSS to remove the Leaflet interactive focus outline and style calibration UI
const gisCalibrationStyle = document.createElement('style');
gisCalibrationStyle.innerHTML = `
    path.leaflet-interactive:focus {
        outline: none;
    }
    .gis-calib-panel {
        position: absolute;
        top: 14px;
        left: 56px;
        z-index: 1001;
        background: rgba(15, 23, 42, 0.94);
        backdrop-filter: blur(12px);
        color: #f8fafc;
        border: 1px solid rgba(148, 163, 184, 0.25);
        border-radius: 16px;
        padding: 14px 16px;
        width: 330px;
        max-width: calc(100vw - 80px);
        max-height: calc(100% - 28px);
        overflow-y: auto;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.35);
        font-family: system-ui, -apple-system, sans-serif;
        font-size: 12px;
        user-select: none;
    }
    .gis-calib-panel.collapsed {
        width: auto;
        padding: 8px 12px;
    }
    .gis-calib-panel.collapsed .gis-calib-body {
        display: none;
    }
    .gis-calib-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        cursor: pointer;
    }
    .gis-calib-title {
        font-weight: 800;
        font-size: 13px;
        color: #38bdf8;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .gis-calib-row {
        margin-top: 10px;
    }
    .gis-calib-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 11px;
        font-weight: 700;
        color: #cbd5e1;
        margin-bottom: 4px;
    }
    .gis-calib-controls {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .gis-calib-controls input[type="range"] {
        flex: 1;
        accent-color: #38bdf8;
        cursor: pointer;
    }
    .gis-calib-num {
        width: 66px;
        background: #1e293b;
        color: #f8fafc;
        border: 1px solid #475569;
        border-radius: 6px;
        padding: 3px 6px;
        font-size: 11px;
        font-family: monospace;
        text-align: right;
    }
    .gis-calib-btn {
        background: #1e293b;
        color: #e2e8f0;
        border: 1px solid #475569;
        border-radius: 8px;
        padding: 5px 8px;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.15s;
    }
    .gis-calib-btn:hover {
        background: #334155;
        border-color: #64748b;
    }
    .gis-calib-btn.primary {
        background: #0284c7;
        border-color: #38bdf8;
        color: #ffffff;
    }
    .gis-calib-btn.primary:hover {
        background: #0369a1;
    }
    .gis-calib-btn.success {
        background: #059669;
        border-color: #34d399;
        color: #ffffff;
    }
    .gis-calib-btn.success:hover {
        background: #047857;
    }
    .gis-nudge-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 4px;
        margin-top: 4px;
    }
`;
document.head.appendChild(gisCalibrationStyle);

// ============================================================================
// REAL-WORLD REFERENCE ANCHORS (Google Maps & Subdivision Front-Road Centerlines)
// Full IEEE-754 double precision — NEVER rounded off!
// ============================================================================
const REF_COS_LAT = Math.cos((13.5458 * Math.PI) / 180);

const SUBDIVISION_REF = {
    // Local GeoJSON coordinate of the Subdivision Entrance Road (Main Gate)
    localEntrance: {
        lng: -0.0010908483152571864,
        lat: -0.0023152983534520453
    },
    // Block 11 Lot 24 exact Front-Road centerline (middle of front house & road width)
    localB11L24: {
        lng: -0.001908377245807,
        lat: -0.001679753258945
    },
    // Block 17 Lot 12 exact Front-Road centerline (middle of front house & road width)
    localB17L12: {
        lng: 0.000046863333262,
        lat: -0.001584657839152
    },
    // Real-World GPS coordinates on Google Maps (Zone 1, Tagbong, Pili, Camarines Sur)
    realEntrance: {
        lat: 13.545990203879006,
        lng: 123.25622351397625
    },
    realB11L24: {
        lat: 13.5463071,
        lng: 123.2572407
    },
    realB17L12: {
        lat: 13.544580,
        lng: 123.256132
    }
};

const PIN_PALETTE = ['#10b981', '#f97316', '#06b6d4', '#eab308', '#ec4899', '#3b82f6', '#ef4444', '#a855f7'];

// All 8 On-Site Field Front-Road Ground-Truth Coordinates Measured in Althesa Village
// Positioned on the FRONT ROAD (middle of road width in front of the house/corner), NOT house interior!
const DEFAULT_GOOGLE_POINTS = [
    {
        id: 'pin_entrance',
        type: 'entrance',
        label: 'Pin 1: Entrance Road (Main Gate)',
        localLng: -0.0010908483152571864,
        localLat: -0.0023152983534520453,
        lat: 13.545990203879006,
        lng: 123.25622351397625,
        latStr: '13.545990203879006',
        lngStr: '123.25622351397625',
        color: '#10b981' // Green
    },
    {
        id: 'pin_b11_l24',
        type: 'house',
        block: 11,
        lot: 24,
        label: 'Pin 2: B11 L24 Front Road',
        localLng: -0.001908377245807,
        localLat: -0.001679753258945,
        lat: 13.5463071,
        lng: 123.2572407,
        latStr: '13.5463071',
        lngStr: '123.2572407',
        color: '#f97316' // Orange
    },
    {
        id: 'pin_b17_l2',
        type: 'house',
        block: 17,
        lot: 2,
        label: 'Pin 3: B17 L2 Front Road',
        localLng: -0.000164838351231,
        localLat: -0.002178142030027,
        lat: 13.545166,
        lng: 123.255778,
        latStr: '13.545166',
        lngStr: '123.255778',
        color: '#06b6d4' // Cyan
    },
    {
        id: 'pin_b2_l20',
        type: 'house',
        block: 2,
        lot: 20,
        label: 'Pin 4: B2 L20 Front Road',
        localLng: -0.00085107818293,
        localLat: -0.001989597340581,
        lat: 13.545618,
        lng: 123.256359,
        latStr: '13.545618',
        lngStr: '123.256359',
        color: '#eab308' // Yellow
    },
    {
        id: 'pin_b14_l5',
        type: 'house',
        block: 14,
        lot: 5,
        label: 'Pin 5: B14 L5 Front Road',
        localLng: -0.001519070601766,
        localLat: -0.001305633938415,
        lat: 13.5457494,
        lng: 123.2573201,
        latStr: '13.5457494',
        lngStr: '123.2573201',
        color: '#ec4899' // Pink
    },
    {
        id: 'pin_b12_l16',
        type: 'house',
        block: 12,
        lot: 16,
        customRoadPoint: true,
        label: 'Pin 6: B12 L16 Corner Road (B11 L11-12)',
        localLng: -0.00072658905474,
        localLat: -0.000583679430655,
        lat: 13.5446878,
        lng: 123.2574593,
        latStr: '13.5446878',
        lngStr: '123.2574593',
        color: '#3b82f6' // Blue
    },
    {
        id: 'pin_b8_l19',
        type: 'house',
        block: 8,
        lot: 19,
        customRoadPoint: true,
        label: 'Pin 7: B8 L19 Side / Main Straight Road',
        localLng: -0.000404283495089,
        localLat: -0.000459889989941,
        lat: 13.5443830,
        lng: 123.2573396,
        latStr: '13.5443830',
        lngStr: '123.2573396',
        color: '#ef4444' // Red
    },
    {
        id: 'pin_b17_l12',
        type: 'house',
        block: 17,
        lot: 12,
        label: 'Pin 8: B17 L12 Front Road',
        localLng: 0.000046863333262,
        localLat: -0.001584657839152,
        lat: 13.544580,
        lng: 123.256132,
        latStr: '13.544580',
        lngStr: '123.256132',
        color: '#a855f7' // Purple
    }
];

/**
 * Computes the exact 2D polygon area centroid in full double precision
 */
function computePolygonMiddle(ring) {
    if (!Array.isArray(ring) || ring.length === 0) return null;
    if (ring.length < 4) {
        const xs = ring.map(p => p[0]);
        const ys = ring.map(p => p[1]);
        return {
            lng: (Math.min(...xs) + Math.max(...xs)) / 2,
            lat: (Math.min(...ys) + Math.max(...ys)) / 2
        };
    }
    let area2 = 0;
    let cx = 0;
    let cy = 0;
    const n = ring.length - 1;
    for (let i = 0; i < n; i++) {
        const x0 = ring[i][0], y0 = ring[i][1];
        const x1 = ring[i + 1][0], y1 = ring[i + 1][1];
        const cross = x0 * y1 - x1 * y0;
        area2 += cross;
        cx += (x0 + x1) * cross;
        cy += (y0 + y1) * cross;
    }
    if (Math.abs(area2) < 1e-18) {
        const pts = ring.slice(0, -1);
        return {
            lng: pts.reduce((s, p) => s + p[0], 0) / pts.length,
            lat: pts.reduce((s, p) => s + p[1], 0) / pts.length
        };
    }
    return {
        lng: cx / (3 * area2),
        lat: cy / (3 * area2)
    };
}

/**
 * Resolves the exact local FRONT-ROAD centerline coordinate for a reference point
 * (middle of the road width in front of the house/corner, NEVER inside the house!)
 */
function resolveLocalFrontRoadCoord(pt, idx, localFrontRoads = {}) {
    if (!pt) return SUBDIVISION_REF.localEntrance;
    if (idx === 0 || pt.type === 'entrance') {
        return SUBDIVISION_REF.localEntrance;
    }
    if (pt.customRoadPoint && Number.isFinite(Number(pt.localLng)) && Number.isFinite(Number(pt.localLat))) {
        return { lng: Number(pt.localLng), lat: Number(pt.localLat) };
    }
    if (pt.block !== undefined && pt.lot !== undefined && localFrontRoads[`B${pt.block} L${pt.lot}`]) {
        return localFrontRoads[`B${pt.block} L${pt.lot}`];
    }
    if (Number.isFinite(Number(pt.localLng)) && Number.isFinite(Number(pt.localLat))) {
        return { lng: Number(pt.localLng), lat: Number(pt.localLat) };
    }
    return SUBDIVISION_REF.localB11L24;
}

/**
 * Surgically removes ONLY the outer road encircling the subdivision (ring 0 and ring 1[131..283])
 * from buffered_road_design.geojson while preserving the entire road from the Entrance Gate
 * all the way inside the subdivision (including all 12 internal block holes).
 */
function stripOuterEncirclingRoad(bufferedGeoJSON) {
    if (!bufferedGeoJSON || !bufferedGeoJSON.features) return bufferedGeoJSON;
    const cloned = JSON.parse(JSON.stringify(bufferedGeoJSON));
    cloned.features.forEach(f => {
        if (f.geometry && f.geometry.type === 'MultiPolygon' && Array.isArray(f.geometry.coordinates)) {
            f.geometry.coordinates = f.geometry.coordinates.map(polyRings => {
                // If original unstripped file (14 rings where ring 1 has 287 pts) is loaded from cache:
                if (polyRings.length === 14 && polyRings[1] && polyRings[1].length === 287) {
                    const r1 = polyRings[1];
                    const internalOuter = [...r1.slice(0, 131), ...r1.slice(284)];
                    const internalHoles = polyRings.slice(2);
                    return [internalOuter, ...internalHoles];
                }
                return polyRings;
            });
        }
    });
    return cloned;
}

/**
 * Solves the exact transformation (N-Point Least-Squares Affine across ALL Front-Road Alignment Points,
 * or 2-Point Similarity if only 2 points exist).
 * Uses the FRONT-ROAD centerline coordinates (middle of the road in front of the house/corner).
 * Full 64-bit IEEE-754 precision — NEVER rounded off!
 */
function solveSimilarityFromAlignmentPoints(
    ptsList = DEFAULT_GOOGLE_POINTS,
    localFrontRoads = {},
    flipX = false,
    flipY = false
) {
    if (!Array.isArray(ptsList) || ptsList.length < 2) return null;

    const alignPts = ptsList.filter(p => p && p.type !== 'record' && Number.isFinite(Number(p.lat)) && Number.isFinite(Number(p.lng)));
    const activePts = alignPts.length >= 2 ? alignPts : ptsList.slice(0, 2);

    const locOrigin = SUBDIVISION_REF.localEntrance;
    const refLat = Number(activePts[0].lat) || SUBDIVISION_REF.realEntrance.lat;
    const cosLat = Math.cos((refLat * Math.PI) / 180);

    // Build paired local (rawDx, rawDy) and real-world (E, N) vectors for all active front-road points
    const pairs = activePts.map((pt, idx) => {
        const loc = resolveLocalFrontRoadCoord(pt, idx, localFrontRoads);
        const dx = (loc.lng - locOrigin.lng) * (flipX ? -1 : 1);
        const dy = (loc.lat - locOrigin.lat) * (flipY ? -1 : 1);
        const E = Number(pt.lng) * cosLat;
        const N = Number(pt.lat);
        return { dx, dy, E, N };
    });

    if (pairs.length >= 3) {
        const n = pairs.length;
        let sdx = 0, sdy = 0, sE = 0, sN = 0;
        for (let i = 0; i < n; i++) {
            sdx += pairs[i].dx;
            sdy += pairs[i].dy;
            sE += pairs[i].E;
            sN += pairs[i].N;
        }
        const cdx = sdx / n, cdy = sdy / n, cE = sE / n, cN = sN / n;

        let Sxx = 0, Syy = 0, Sxy = 0;
        let SxE = 0, SyE = 0, SxN = 0, SyN = 0;
        for (let i = 0; i < n; i++) {
            const x = pairs[i].dx - cdx;
            const y = pairs[i].dy - cdy;
            const u = pairs[i].E - cE;
            const v = pairs[i].N - cN;
            Sxx += x * x;
            Syy += y * y;
            Sxy += x * y;
            SxE += x * u;
            SyE += y * u;
            SxN += x * v;
            SyN += y * v;
        }

        const det = Sxx * Syy - Sxy * Sxy;
        if (Math.abs(det) > 1e-16) {
            const A = (SxE * Syy - SyE * Sxy) / det;
            const B = (Sxx * SyE - Sxy * SxE) / det;
            const C = (SxN * Syy - SyN * Sxy) / det;
            const D = (Sxx * SyN - Sxy * SxN) / det;

            const anchorLng = (cE - A * cdx - B * cdy) / cosLat;
            const anchorLat = cN - C * cdx - D * cdy;

            const scaleX = Math.hypot(A, C);
            const scaleY = Math.hypot(B, D);
            const rotRad = Math.atan2(C, A);
            let rotationDeg = (-rotRad * 180) / Math.PI;
            while (rotationDeg > 180) rotationDeg -= 360;
            while (rotationDeg < -180) rotationDeg += 360;

            return {
                calibVersion: 9,
                anchorLat: anchorLat,
                anchorLng: anchorLng,
                rotationDeg: rotationDeg,
                scale: (scaleX + scaleY) / 2,
                scaleX: scaleX,
                scaleY: scaleY,
                flipX: Boolean(flipX),
                flipY: Boolean(flipY),
                useAffine: true,
                affine: { A, B, C, D },
                googlePoints: JSON.parse(JSON.stringify(ptsList)),
                opacity: 0.92,
                showBgFill: true,
                baseLayer: 'street'
            };
        }
    }

    // 2-point similarity fallback
    const dx2 = pairs[1].dx - pairs[0].dx;
    const dy2 = pairs[1].dy - pairs[0].dy;
    const dE2 = pairs[1].E - pairs[0].E;
    const dN2 = pairs[1].N - pairs[0].N;
    const d2 = dx2 * dx2 + dy2 * dy2;
    if (d2 === 0) return null;
    const a = (dE2 * dx2 + dN2 * dy2) / d2;
    const b = (dN2 * dx2 - dE2 * dy2) / d2;
    const scale = Math.hypot(a, b);
    const radCCW = Math.atan2(b, a);
    let rotationDeg = (-radCCW * 180) / Math.PI;
    while (rotationDeg > 180) rotationDeg -= 360;
    while (rotationDeg < -180) rotationDeg += 360;

    return {
        calibVersion: 9,
        anchorLat: Number(activePts[0].lat),
        anchorLng: Number(activePts[0].lng),
        rotationDeg: rotationDeg,
        scale: scale,
        scaleX: scale,
        scaleY: scale,
        flipX: Boolean(flipX),
        flipY: Boolean(flipY),
        useAffine: false,
        googlePoints: JSON.parse(JSON.stringify(ptsList)),
        opacity: 0.92,
        showBgFill: true,
        baseLayer: 'street'
    };
}

// Default 8-Point Front-Road Ground-Truth Calibration (Least-Squares Affine Fit across all 8 road points)
const DEFAULT_CALIBRATION = {
    calibVersion: 9,
    anchorLat: 13.545990203879006,
    anchorLng: 123.25622351397625,
    rotationDeg: 125.31910332697669,
    scale: 1.005492902042514,
    scaleX: 1.0198247666117957,
    scaleY: 0.9911610374732323,
    flipX: false,
    flipY: false,
    useAffine: true,
    affine: {
        A: -0.5895909920359387,
        B: 0.8083493751609164,
        C: -0.8321207945390973,
        D: -0.573560362894903
    },
    googlePoints: JSON.parse(JSON.stringify(DEFAULT_GOOGLE_POINTS)),
    opacity: 0.92,
    showBgFill: true,
    baseLayer: 'street'
};

function loadSavedCalibration(serverSettings = {}) {
    let saved = null;
    if (serverSettings && serverSettings.gis_calibration) {
        try {
            const parsed = typeof serverSettings.gis_calibration === 'string'
                ? JSON.parse(serverSettings.gis_calibration)
                : serverSettings.gis_calibration;
            if (parsed && parsed.calibVersion === 9) {
                saved = parsed;
            }
        } catch (e) {
            console.warn('Invalid server gis_calibration JSON:', e);
        }
    }
    if (!saved) {
        try {
            const localRaw = localStorage.getItem('subdivision_gis_calibration_v9');
            if (localRaw) {
                const parsedLocal = JSON.parse(localRaw);
                if (parsedLocal && parsedLocal.calibVersion === 9) {
                    saved = parsedLocal;
                }
            }
        } catch (e) {
            // ignore
        }
    }
    const merged = Object.assign({}, JSON.parse(JSON.stringify(DEFAULT_CALIBRATION)), saved || {});
    if (merged.scaleX === undefined) merged.scaleX = merged.scale || 1.0;
    if (merged.scaleY === undefined) merged.scaleY = merged.scale || 1.0;
    if (!Array.isArray(merged.googlePoints) || merged.googlePoints.length === 0) {
        merged.googlePoints = JSON.parse(JSON.stringify(DEFAULT_GOOGLE_POINTS));
    }
    return merged;
}

/**
 * Transforms a single local GeoJSON [lng, lat] coordinate pair into real-world [lng, lat]
 * Supports independent Width (scaleX) & Height (scaleY), Rotation, Flip, and Exact 3-Point Affine
 * Preserves full 64-bit IEEE-754 precision (never rounds off!)
 */
function transformCoordPair(coord, calib) {
    if (!Array.isArray(coord) || coord.length < 2) return coord;
    const x = coord[0];
    const y = coord[1];

    // If coordinate is already in real-world range (lat > 5), return as-is
    if (Math.abs(y) > 1 && Math.abs(x) > 1) {
        return [x, y];
    }

    const locA = SUBDIVISION_REF.localEntrance;
    const rawDx = (x - locA.lng) * (calib.flipX ? -1 : 1);
    const rawDy = (y - locA.lat) * (calib.flipY ? -1 : 1);
    const cosLat = Math.cos((calib.anchorLat * Math.PI) / 180);

    if (calib.useAffine && calib.affine) {
        const dE = calib.affine.A * rawDx + calib.affine.B * rawDy;
        const dN = calib.affine.C * rawDx + calib.affine.D * rawDy;
        return [calib.anchorLng + dE / cosLat, calib.anchorLat + dN];
    }

    const scaleX = calib.scaleX !== undefined ? Number(calib.scaleX) : (Number(calib.scale) || 1.0);
    const scaleY = calib.scaleY !== undefined ? Number(calib.scaleY) : (Number(calib.scale) || 1.0);

    const sxDx = rawDx * scaleX;
    const syDy = rawDy * scaleY;

    const rad = (-calib.rotationDeg * Math.PI) / 180;
    const cosR = Math.cos(rad);
    const sinR = Math.sin(rad);

    const dE = sxDx * cosR - syDy * sinR;
    const dN = sxDx * sinR + syDy * cosR;

    const realLat = calib.anchorLat + dN;
    const realLng = calib.anchorLng + dE / cosLat;

    return [realLng, realLat];
}

function transformGeometryCoords(coords, calib) {
    if (!Array.isArray(coords) || coords.length === 0) return coords;
    if (typeof coords[0] === 'number') {
        return transformCoordPair(coords, calib);
    }
    return coords.map(c => transformGeometryCoords(c, calib));
}

/**
 * Deep-clones and transforms an entire GeoJSON FeatureCollection into real-world coordinates
 */
function transformGeoJSON(geojson, calib) {
    if (!geojson || !geojson.features) return geojson;
    return {
        type: 'FeatureCollection',
        name: geojson.name,
        features: geojson.features.map(f => ({
            type: 'Feature',
            properties: Object.assign({}, f.properties),
            geometry: f.geometry ? {
                type: f.geometry.type,
                coordinates: transformGeometryCoords(f.geometry.coordinates, calib)
            } : null
        }))
    };
}

async function initGISMap(mapElementId, options = {}) {
    const config = Object.assign({
        interactive: true,
        showRouting: false,
        interactiveRouting: false,
        startNode: null,
        endNode: null,
        adminView: false,
        showCalibration: true, // Enabled so we can visually verify & align on satellite view
    }, options);

    // Load Raw GeoJSONs, Database Lots, and Saved Settings in parallel
    const [rawAmenities, rawBufferedRoads, rawHouses, rawNetwork, rawEntrance, rawHouseNodes, lotsData, appSettings] = await Promise.all([
        fetch('/gis-data/amenities.geojson').then(r => r.json()).catch(() => ({ features: [] })),
        fetch('/gis-data/buffered_road_design.geojson').then(r => r.json()).catch(() => ({ features: [] })),
        fetch('/gis-data/houses.geojson').then(r => r.json()).catch(() => ({ features: [] })),
        fetch('/gis-data/roads.geojson').then(r => r.json()).catch(() => ({ features: [] })),
        fetch('/gis-data/entrance_point.geojson').then(r => r.json()).catch(() => ({})),
        fetch('/gis-data/house_nodes.geojson').then(r => r.json()).catch(() => ({})),
        fetch('/api/lots').then(r => r.ok ? r.json() : []).catch(() => []),
        fetch('/api/settings').then(r => r.ok ? r.json() : {}).catch(() => ({}))
    ]);

    // Ensure ONLY the outer encircling road is stripped while keeping the entrance-to-inside road polygon
    const internalBufferedRoads = stripOuterEncirclingRoad(rawBufferedRoads);

    // Precompute exact 2D polygon middle (area centroid) AND exact Front-Road Centerline point for EVERY house
    const localHouseMiddles = {};
    if (rawHouses && Array.isArray(rawHouses.features)) {
        rawHouses.features.forEach(f => {
            const b = f.properties && f.properties.block_num;
            const l = f.properties && f.properties.lot_number;
            if (b !== undefined && l !== undefined && f.geometry) {
                const ring = f.geometry.type === 'MultiPolygon'
                    ? (f.geometry.coordinates[0] && f.geometry.coordinates[0][0])
                    : f.geometry.coordinates[0];
                const mid = computePolygonMiddle(ring);
                if (mid) {
                    localHouseMiddles[`B${b} L${l}`] = {
                        block: Number(b),
                        lot: Number(l),
                        lng: mid.lng,
                        lat: mid.lat
                    };
                }
            }
        });
    }

    // Precompute exact Front-Road Centerline Coordinate (middle of road width in front of each house)
    // using the road-side endpoint of each house's connector in roads.geojson
    const localHouseFrontRoad = {};
    const rawConnectors = (rawNetwork && Array.isArray(rawNetwork.features))
        ? rawNetwork.features.filter(f => !f.properties || f.properties.name === null)
        : [];
    if (rawHouseNodes && Array.isArray(rawHouseNodes.features)) {
        rawHouseNodes.features.forEach(hn => {
            const b = hn.properties && hn.properties.block_num;
            const l = hn.properties && hn.properties.lot_number;
            if (b === undefined || l === undefined || !hn.geometry || !Array.isArray(hn.geometry.coordinates)) return;
            const [hx, hy] = hn.geometry.coordinates;
            let minD = Infinity;
            let roadPt = null;
            rawConnectors.forEach(c => {
                if (!c.geometry || !c.geometry.coordinates) return;
                const line = c.geometry.type === 'MultiLineString' ? c.geometry.coordinates[0] : c.geometry.coordinates;
                if (!line || line.length < 2) return;
                const p0 = line[0], p1 = line[line.length - 1];
                const d0 = Math.hypot(p0[0] - hx, p0[1] - hy);
                const d1 = Math.hypot(p1[0] - hx, p1[1] - hy);
                if (d0 < minD) { minD = d0; roadPt = p1; }
                if (d1 < minD) { minD = d1; roadPt = p0; }
            });
            if (roadPt) {
                localHouseFrontRoad[`B${b} L${l}`] = {
                    block: Number(b),
                    lot: Number(l),
                    lng: roadPt[0],
                    lat: roadPt[1]
                };
            }
        });
    }

    let calib = loadSavedCalibration(appSettings);

    // Ensure every reference point in calib.googlePoints has valid colors and string representations
    if (!Array.isArray(calib.googlePoints) || calib.googlePoints.length === 0) {
        calib.googlePoints = JSON.parse(JSON.stringify(DEFAULT_GOOGLE_POINTS));
    }
    calib.googlePoints.forEach((pt, idx) => {
        if (!pt.color) pt.color = PIN_PALETTE[idx % PIN_PALETTE.length];
        if (pt.latStr === undefined) pt.latStr = String(pt.lat);
        if (pt.lngStr === undefined) pt.lngStr = String(pt.lng);
    });

    // Undo / Redo History Stack (supports Ctrl+Z and Ctrl+Y)
    const undoStack = [];
    const redoStack = [];
    const MAX_HISTORY = 60;

    function pushUndoSnapshot() {
        undoStack.push(JSON.parse(JSON.stringify(calib)));
        if (undoStack.length > MAX_HISTORY) undoStack.shift();
        redoStack.length = 0;
        updateUndoRedoButtons();
    }

    let updateUndoRedoButtons = function () {};
    let editingPointId = null;
    let clickMapForPinMode = false;
    let pickHouseForPointMode = false;

    window.globalDbLots = {};
    if (lotsData && Array.isArray(lotsData)) {
        lotsData.forEach(lot => {
            window.globalDbLots[`B${lot.block} L${lot.lot_number}`] = lot;
        });
    }

    // Initialize Leaflet Map at Real-World Subdivision Coordinates (North-Up bearing: 0 for true satellite alignment)
    const map = L.map(mapElementId, {
        zoomControl: config.interactive,
        dragging: config.interactive,
        scrollWheelZoom: config.interactive,
        doubleClickZoom: config.interactive,
        maxZoom: 22,
        rotate: true,
        bearing: config.bearing !== undefined ? config.bearing : 0,
        rotateControl: {
            closeOnZeroBearing: false,
            position: 'topleft'
        },
    }).setView([calib.anchorLat, calib.anchorLng], 18);

    // Real-World Base Tile Layers (Always use crisp high-DPI retina tiles up to maxNativeZoom: 22 so Google Maps never gets blurry)
    const googleStreetLayer = L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}&scale=2', {
        maxZoom: 22,
        maxNativeZoom: 22, // Google supports native crisp vector tiles up to zoom 22!
        tileSize: 256,
        zoomOffset: 0,
        detectRetina: true,
        attribution: '&copy; Google Maps'
    });

    const googleHybridLayer = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}&scale=2', {
        maxZoom: 22,
        maxNativeZoom: 22,
        tileSize: 256,
        zoomOffset: 0,
        detectRetina: true,
        attribution: '&copy; Google Satellite'
    });

    const esriSatelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 22,
        maxNativeZoom: 19,
        attribution: '&copy; Esri World Imagery'
    });

    const osmStreetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 22,
        maxNativeZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    });

    let currentBaseLayerMode = 'google_street';
    let activeTileLayer = null;
    function setBaseTileMode(mode) {
        currentBaseLayerMode = mode;
        calib.baseLayer = mode;
        if (activeTileLayer) {
            map.removeLayer(activeTileLayer);
            activeTileLayer = null;
        }
        if (mode === 'google' || mode === 'google_street') {
            activeTileLayer = googleStreetLayer.addTo(map);
        } else if (mode === 'satellite') {
            activeTileLayer = googleHybridLayer.addTo(map);
        } else if (mode === 'esri') {
            activeTileLayer = esriSatelliteLayer.addTo(map);
        } else if (mode === 'street') {
            activeTileLayer = osmStreetLayer.addTo(map);
        } else {
            activeTileLayer = googleStreetLayer.addTo(map);
        }
    }

    // Expose toggle function globally for UI buttons
    window.toggleGISBaseLayer = function () {
        const nextMode = (currentBaseLayerMode === 'satellite') ? 'google_street' : 'satellite';
        setBaseTileMode(nextMode);
        const btn = document.getElementById('btnToggleMapMode');
        if (btn) {
            btn.innerHTML = (nextMode === 'satellite') ? '🗺️ Google Maps' : '🛰️ Satellite';
        }
        return nextMode;
    };

    // Visitor view uses clean Google Maps street view by default!
    // Admin calibration view defaults to satellite unless specified
    const initialTileMode = config.showCalibration ? (calib.baseLayer || 'satellite') : 'google_street';
    setBaseTileMode(initialTileMode);

    // Layer Groups that get rebuilt whenever calibration sliders change
    const gisLayerGroup = L.layerGroup().addTo(map);
    const routingLayerGroup = L.layerGroup().addTo(map);
    const osrmLayerGroup = L.layerGroup().addTo(map);
    const liveNavGroup = L.layerGroup().addTo(map);
    const refMarkersGroup = L.layerGroup().addTo(map);

    let liveNavTracker = null;

    let routeStartHouse = null;
    let routeEndHouse = null;
    let houseLayer = null;
    let initialFitDone = false;
    let isDraggingSubdivision = false;
    let dragSubdivisionMode = false;

    function getStyles() {
        const polyOpacity = calib.opacity !== undefined ? Number(calib.opacity) : 1.0;
        return {
            roadDesign: {
                stroke: true,
                weight: 0.8,
                color: '#bfc8d4',
                fillColor: '#aab9c9', // Uniform grey road color matching outside Google Maps highway
                fillOpacity: 1.0
            },
            house: (feature) => {
                const b = feature.properties.block_num;
                const l = feature.properties.lot_number;
                const key = `B${b} L${l}`;
                const dbLot = window.globalDbLots ? window.globalDbLots[key] : null;

                let fillColor = '#f8fafc';
                let color = '#cbd5e1';
                let weight = 0.65;

                if (dbLot && config.adminView) {
                    if (dbLot.status === 'Occupied') { fillColor = '#ffffff'; color = '#000000'; }
                    else if (dbLot.status === 'Vacant House') { fillColor = '#3b82f6'; color = '#2563eb'; }
                    else if (dbLot.status === 'Vacant Lot') { fillColor = '#f59e0b'; color = '#d97706'; }
                    else if (dbLot.status === 'Reserved') { fillColor = '#8b5cf6'; color = '#7c3aed'; }
                    else if (dbLot.status === 'Under Construction') { fillColor = '#ef4444'; color = '#dc2626'; }
                }

                // Highlight alignment reference houses ONLY in admin calibration mode (never on visitor pin link page)
                if (config.showCalibration) {
                    const pts = Array.isArray(calib.googlePoints) ? calib.googlePoints : [];
                    for (let idx = 1; idx < pts.length; idx++) {
                        const pt = pts[idx];
                        if (pt && pt.type !== 'record' && pt.block !== undefined && pt.lot !== undefined && Number(b) === Number(pt.block) && Number(l) === Number(pt.lot)) {
                            const c = pt.color || PIN_PALETTE[idx % PIN_PALETTE.length];
                            fillColor = c + '33';
                            color = c;
                            weight = 2.2;
                            break;
                        }
                    }
                }

                return {
                    color: color,
                    weight: weight,
                    fillColor: fillColor,
                    fillOpacity: polyOpacity
                };
            },
            amenity: (feature) => {
                const name = feature.properties['Building name'] || feature.properties.name || feature.properties.NAME || 'Unknown';
                const lower = name.toLowerCase();

                // Step 2: Remove land background polygons so real-world satellite / street maps show through clearly
                const isBg = lower.includes('background') || lower.includes('bachground') || lower.includes('grass');
                if (isBg) {
                    return {
                        color: 'transparent',
                        weight: 0,
                        fillColor: 'transparent',
                        fillOpacity: 0,
                        interactive: false
                    };
                }

                let fillColor = '#e7e8eb';
                let borderColor = '#94a3b8';
                if (lower.includes('guard')) {
                    fillColor = '#f8fafc';
                    borderColor = '#cbd5e1';
                } else if (lower.includes('open')) {
                    fillColor = '#eae0d5';
                } else if (lower.includes('basketball')) {
                    fillColor = '#d97706';
                }

                return {
                    color: borderColor,
                    weight: 1,
                    fillColor: fillColor,
                    fillOpacity: polyOpacity
                };
            },
            route: {
                color: '#2563eb',
                weight: 6,
                opacity: 0.95,
                lineCap: 'round',
                lineJoin: 'round'
            },
            altRoute: {
                color: '#9333ea',
                weight: 5,
                opacity: 0.85,
                lineCap: 'round',
                lineJoin: 'round'
            },
            osrmHighway: {
                color: '#2563eb',
                weight: 6,
                opacity: 0.95,
                lineCap: 'round',
                lineJoin: 'round'
            },
            subdivisionRoute: {
                color: '#2563eb',
                weight: 6,
                opacity: 0.95,
                lineCap: 'round',
                lineJoin: 'round'
            },
            liveInsideActive: {
                color: '#2563eb',
                weight: 6,
                opacity: 0.95,
                lineCap: 'round',
                lineJoin: 'round'
            }
        };
    }

    function renderAllGISLayers() {
        gisLayerGroup.clearLayers();
        routingLayerGroup.clearLayers();

        const styles = getStyles();

        // Transform all GeoJSONs using current calibration
        const amenities = transformGeoJSON(rawAmenities, calib);
        const bufferedRoads = transformGeoJSON(internalBufferedRoads, calib);
        const houses = transformGeoJSON(rawHouses, calib);
        const network = transformGeoJSON(rawNetwork, calib);
        const entrance = transformGeoJSON(rawEntrance, calib);
        const houseNodes = transformGeoJSON(rawHouseNodes, calib);

        // Filter out land background polygons so real-world satellite and street maps show through
        const visibleAmenities = {
            type: 'FeatureCollection',
            features: (amenities && Array.isArray(amenities.features))
                ? amenities.features.filter(f => {
                    const n = ((f.properties && (f.properties['Building name'] || f.properties.name || f.properties.NAME)) || '').toLowerCase();
                    return !(n.includes('background') || n.includes('bachground') || n.includes('grass'));
                })
                : []
        };

        L.geoJSON(visibleAmenities, { style: styles.amenity }).addTo(gisLayerGroup);
        L.geoJSON(bufferedRoads, { style: styles.roadDesign }).addTo(gisLayerGroup);

        function getHouseNodeCoord(block, lot) {
            if (!houseNodes || !houseNodes.features) return null;
            for (let feature of houseNodes.features) {
                if (feature.properties.block_num == block && feature.properties.lot_number == lot) {
                    const c = feature.geometry.coordinates;
                    return L.latLng(c[1], c[0]);
                }
            }
            return null;
        }

        houseLayer = L.geoJSON(houses, {
            style: styles.house,
            onEachFeature: (feature, layer) => {
                if (feature.properties.block_num && feature.properties.lot_number) {
                    const b = feature.properties.block_num;
                    const l = feature.properties.lot_number;
                    const houseId = `B${b} L${l}`;
                    let refTag = '';
                    if (config.showCalibration) {
                        const pts = Array.isArray(calib.googlePoints) ? calib.googlePoints : [];
                        for (let idx = 1; idx < pts.length; idx++) {
                            const pt = pts[idx];
                            if (pt && pt.type !== 'record' && pt.block !== undefined && pt.lot !== undefined && Number(b) === Number(pt.block) && Number(l) === Number(pt.lot)) {
                                refTag = ` (🎯 Front Road Pin ${idx + 1})`;
                                break;
                            }
                        }
                    }
                    layer.bindTooltip(`Block ${b}, Lot ${l}${refTag}`);

                    if (config.endNode && houseId === config.endNode) {
                        layer.setStyle({ fillColor: '#2563eb', color: '#1d4ed8', weight: 2.5, fillOpacity: 0.95 });
                    }

                    layer.on('click', () => {
                        if (pickHouseForPointMode && window.__onPickHouseForRefPoint) {
                            window.__onPickHouseForRefPoint(b, l);
                            return;
                        }

                        if (config.interactiveRouting && !dragSubdivisionMode && !clickMapForPinMode) {
                            let coord = getHouseNodeCoord(b, l);
                            if (!coord) coord = layer.getBounds().getCenter();

                            if (!routeStartHouse) {
                                routeStartHouse = { id: houseId, coord: coord, block: b, lot: l };
                                layer.setStyle({ fillColor: '#10b981', color: '#059669', weight: 2.5 });
                            } else if (!routeEndHouse) {
                                routeEndHouse = { id: houseId, coord: coord, block: b, lot: l };
                                layer.setStyle({ fillColor: '#2563eb', color: '#1d4ed8', weight: 2.5 });
                                buildAndDrawRouting(network, entrance, houseNodes, styles);
                            } else {
                                houseLayer.resetStyle();
                                routingLayerGroup.clearLayers();
                                routeStartHouse = { id: houseId, coord: coord, block: b, lot: l };
                                routeEndHouse = null;
                                layer.setStyle({ fillColor: '#10b981', color: '#059669', weight: 2.5 });
                            }
                        }
                    });
                }
            }
        }).addTo(gisLayerGroup);

        if (!initialFitDone && houseLayer.getBounds && houseLayer.getBounds().isValid()) {
            initialFitDone = true;
            map.fitBounds(houseLayer.getBounds(), { padding: [30, 30] });
        }

        // Draw exact FRONT-ROAD dots on the subdivision road centerline for ALL active alignment landmarks (all 8 points!)
        if (config.showCalibration) {
            const pts = Array.isArray(calib.googlePoints) ? calib.googlePoints : [];
            const landmarks = [];

            pts.forEach((pt, idx) => {
                if (!pt || pt.type === 'record') return;
                const color = pt.color || PIN_PALETTE[idx % PIN_PALETTE.length];
                const loc = resolveLocalFrontRoadCoord(pt, idx, localHouseFrontRoad);
                let label = pt.label || `Pin ${idx + 1}`;
                if (idx === 0 || pt.type === 'entrance') {
                    label = `Subdivision Entrance Road (Matches Pin 1)`;
                } else if (pt.block !== undefined && pt.lot !== undefined) {
                    label = `Subdivision B${pt.block} L${pt.lot} Front Road (Matches Pin ${idx + 1})`;
                }
                landmarks.push({
                    label,
                    local: loc,
                    color
                });
            });

            landmarks.forEach(lm => {
                const transformed = transformCoordPair([lm.local.lng, lm.local.lat], calib);
                const dot = L.circleMarker([transformed[1], transformed[0]], {
                    radius: 5.5,
                    fillColor: '#ffffff',
                    color: lm.color,
                    weight: 2.5,
                    fillOpacity: 1,
                    interactive: true
                }).addTo(gisLayerGroup);
                dot.bindTooltip(`🛣️ <b>${lm.label}</b><br><span style="font-family:monospace; font-size:10px;">${transformed[1]}, ${transformed[0]}</span>`, { direction: 'top' });
            });

            if (dragSubdivisionMode) {
                renderResizeBoxAndHandles();
            }
        }

        if (config.showRouting && network && !isDraggingSubdivision) {
            buildAndDrawRouting(network, entrance, houseNodes, styles);
        }
    }

    // Local unscaled bounding box of the internal subdivision (relative to localEntrance)
    function getSubdivisionLocalBox() {
        const locA = SUBDIVISION_REF.localEntrance;
        const minX = -0.002085, maxX = 0.000711;
        const minY = -0.002344, maxY = 0.000005;
        const dx1 = (minX - locA.lng) * (calib.flipX ? -1 : 1);
        const dx2 = (maxX - locA.lng) * (calib.flipX ? -1 : 1);
        const dy1 = (minY - locA.lat) * (calib.flipY ? -1 : 1);
        const dy2 = (maxY - locA.lat) * (calib.flipY ? -1 : 1);
        return {
            minDx: Math.min(dx1, dx2),
            maxDx: Math.max(dx1, dx2),
            minDy: Math.min(dy1, dy2),
            maxDy: Math.max(dy1, dy2)
        };
    }

    function localDxDyToLatLng(rawDx, rawDy, c = calib) {
        const cosLat = Math.cos((c.anchorLat * Math.PI) / 180);
        if (c.useAffine && c.affine) {
            const dE = c.affine.A * rawDx + c.affine.B * rawDy;
            const dN = c.affine.C * rawDx + c.affine.D * rawDy;
            return L.latLng(c.anchorLat + dN, c.anchorLng + dE / cosLat);
        }
        const sx = c.scaleX !== undefined ? Number(c.scaleX) : (Number(c.scale) || 1.0);
        const sy = c.scaleY !== undefined ? Number(c.scaleY) : (Number(c.scale) || 1.0);
        const sxDx = rawDx * sx;
        const syDy = rawDy * sy;
        const rad = (-c.rotationDeg * Math.PI) / 180;
        const cosR = Math.cos(rad);
        const sinR = Math.sin(rad);
        const dE = sxDx * cosR - syDy * sinR;
        const dN = sxDx * sinR + syDy * cosR;
        return L.latLng(c.anchorLat + dN, c.anchorLng + dE / cosLat);
    }

    function getCurrentResizeHandles() {
        const box = getSubdivisionLocalBox();
        const midDx = (box.minDx + box.maxDx) / 2;
        const midDy = (box.minDy + box.maxDy) / 2;
        return [
            { id: 'W',  label: '↔️ Drag Width (Left)',   dx: box.minDx, dy: midDy,     color: '#06b6d4', radius: 8 },
            { id: 'E',  label: '↔️ Drag Width (Right)',  dx: box.maxDx, dy: midDy,     color: '#06b6d4', radius: 8 },
            { id: 'N',  label: '↕️ Drag Height (Top)',   dx: midDx,     dy: box.maxDy, color: '#f59e0b', radius: 8 },
            { id: 'S',  label: '↕️ Drag Height (Bottom)',dx: midDx,     dy: box.minDy, color: '#f59e0b', radius: 8 },
            { id: 'NW', label: '⤡ Drag Width + Height', dx: box.minDx, dy: box.maxDy, color: '#ec4899', radius: 7 },
            { id: 'NE', label: '⤡ Drag Width + Height', dx: box.maxDx, dy: box.maxDy, color: '#ec4899', radius: 7 },
            { id: 'SE', label: '⤡ Drag Width + Height', dx: box.maxDx, dy: box.minDy, color: '#ec4899', radius: 7 },
            { id: 'SW', label: '⤡ Drag Width + Height', dx: box.minDx, dy: box.minDy, color: '#ec4899', radius: 7 }
        ].map(h => {
            const latlng = localDxDyToLatLng(h.dx, h.dy, calib);
            return Object.assign({}, h, { latlng });
        });
    }

    function renderResizeBoxAndHandles() {
        const box = getSubdivisionLocalBox();
        const sw = localDxDyToLatLng(box.minDx, box.minDy, calib);
        const se = localDxDyToLatLng(box.maxDx, box.minDy, calib);
        const ne = localDxDyToLatLng(box.maxDx, box.maxDy, calib);
        const nw = localDxDyToLatLng(box.minDx, box.maxDy, calib);

        // Oriented Resize Bounding Box
        L.polyline([sw, se, ne, nw, sw], {
            color: '#38bdf8',
            weight: 2,
            dashArray: '6, 6',
            opacity: 0.95,
            interactive: false
        }).addTo(gisLayerGroup);

        // 8 Interactive Drag Handles (Width, Height, and Corners)
        const handles = getCurrentResizeHandles();
        handles.forEach(h => {
            const marker = L.circleMarker(h.latlng, {
                radius: h.radius,
                fillColor: h.color,
                color: '#ffffff',
                weight: 2.5,
                fillOpacity: 1
            }).addTo(gisLayerGroup);
            marker.bindTooltip(h.label, { direction: 'top' });
        });
    }

    function buildAndDrawRouting(network, entrance, houseNodes, styles) {
        routingLayerGroup.clearLayers();
        const graph = new DijkstraGraph();
        let coordinatesMap = new Map();
        let segments = [];
        let lineEndpoints = [];

        function projectPointToSegment(pt, v, w) {
            const l2 = Math.pow(v[0] - w[0], 2) + Math.pow(v[1] - w[1], 2);
            if (l2 === 0) return { point: v, dist: map.distance(pt, v) };
            let t = ((pt[0] - v[0]) * (w[0] - v[0]) + (pt[1] - v[1]) * (w[1] - v[1])) / l2;
            t = Math.max(0, Math.min(1, t));
            const proj = [v[0] + t * (w[0] - v[0]), v[1] + t * (w[1] - v[1])];
            return { point: proj, dist: map.distance(pt, proj) };
        }

        // Filter network to ONLY the 18 named street centerlines (exclude the 323 house-centroid connectors!)
        // This guarantees the solid blue route stays 100% in the middle of the road with zero spaghetti hooks into houses.
        const streetOnlyNetwork = {
            type: 'FeatureCollection',
            features: (network && Array.isArray(network.features))
                ? network.features.filter(f => f.properties && f.properties.name !== null)
                : []
        };

        L.geoJSON(streetOnlyNetwork, {
            onEachFeature: (feature) => {
                const addCoordsToGraph = (coords) => {
                    if (coords.length === 0) return;
                    lineEndpoints.push(coords[0].join(','));
                    lineEndpoints.push(coords[coords.length - 1].join(','));

                    for (let i = 0; i < coords.length - 1; i++) {
                        const p1 = coords[i];
                        const p2 = coords[i + 1];
                        const id1 = p1.join(',');
                        const id2 = p2.join(',');

                        coordinatesMap.set(id1, [p1[1], p1[0]]);
                        coordinatesMap.set(id2, [p2[1], p2[0]]);

                        if (!graph.nodes.has(id1)) graph.addNode(id1);
                        if (!graph.nodes.has(id2)) graph.addNode(id2);

                        const dist = map.distance(coordinatesMap.get(id1), coordinatesMap.get(id2));
                        graph.addEdge(id1, id2, dist);

                        segments.push({ id1, id2, p1: [p1[1], p1[0]], p2: [p2[1], p2[0]] });
                    }
                };

                if (feature.geometry.type === 'LineString') {
                    addCoordsToGraph(feature.geometry.coordinates);
                } else if (feature.geometry.type === 'MultiLineString') {
                    feature.geometry.coordinates.forEach(coords => addCoordsToGraph(coords));
                }
            }
        });

        const SNAP_TOLERANCE = 3.5; // meters
        lineEndpoints.forEach(endId => {
            if (!coordinatesMap.has(endId)) return;
            const pt1 = coordinatesMap.get(endId);
            coordinatesMap.forEach((pt2, id2) => {
                if (endId !== id2) {
                    const d = map.distance(pt1, pt2);
                    if (d < SNAP_TOLERANCE) {
                        let exists = false;
                        if (graph.nodes.has(endId)) {
                            for (let e of graph.nodes.get(endId).edges) {
                                if (e.node === id2) { exists = true; break; }
                            }
                        }
                        if (!exists) graph.addEdge(endId, id2, d);
                    }
                }
            });
        });

        function snapPointToRoad(lat, lng, prefix, targetGraph) {
            const pt = [lat, lng];
            let minDist = Infinity;
            let closestSegment = null;
            let closestProj = null;

            segments.forEach(seg => {
                const res = projectPointToSegment(pt, seg.p1, seg.p2);
                if (res.dist < minDist) {
                    minDist = res.dist;
                    closestSegment = seg;
                    closestProj = res.point;
                }
            });

            if (closestSegment) {
                const roadNodeId = `${prefix}_road_${closestProj[0].toFixed(6)},${closestProj[1].toFixed(6)}`;
                coordinatesMap.set(roadNodeId, closestProj);
                if (!targetGraph.nodes.has(roadNodeId)) targetGraph.addNode(roadNodeId);

                const dist1 = map.distance(closestSegment.p1, closestProj);
                const dist2 = map.distance(closestProj, closestSegment.p2);

                targetGraph.addEdge(closestSegment.id1, roadNodeId, dist1);
                targetGraph.addEdge(roadNodeId, closestSegment.id2, dist2);

                return {
                    nodeId: roadNodeId,
                    point: closestProj,
                    dist: minDist
                };
            }
            return null;
        }

        function injectSnappedNode(lat, lng, prefix, targetGraph) {
            const snap = snapPointToRoad(lat, lng, prefix, targetGraph);
            return snap ? snap.nodeId : null;
        }

        // Live Moving GPS & OSRM Outside Highway Hybrid Controller
        function startLiveHybridNavigation(opts) {
            if (liveNavTracker) {
                liveNavTracker.destroy();
                liveNavTracker = null;
            }

            const entranceCoord = opts.entranceCoord;
            const destCoord = opts.destCoord;
            const baseGraph = opts.graph;
            const coordinatesMap = opts.coordinatesMap;
            const segments = opts.segments;
            const injectSnappedNode = opts.injectSnappedNode;
            const staticPathIds = opts.staticPathIds || [];
            const styles = opts.styles;
            const destinationName = opts.destinationName || 'Destination';

            osrmLayerGroup.clearLayers();
            liveNavGroup.clearLayers();
            routingLayerGroup.clearLayers();

            // 1. HUD element lookup or auto-creation
            let hud = document.getElementById('liveNavHud');
            if (!hud) {
                hud = document.createElement('div');
                hud.id = 'liveNavHud';
                hud.className = 'live-nav-hud';
                hud.innerHTML = `
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
                        Locating fastest route to ${destinationName}...
                    </div>
                    <div class="nav-hud-sub" id="hudSimTip">
                        🛰️ Acquiring live GPS signal...
                    </div>
                `;
                const container = document.getElementById(mapElementId) || map.getContainer();
                container.appendChild(hud);
            }

            // Respect collapsed state on initialization (default hidden on visitor view)
            const viewportEl = document.getElementById('mapViewport');
            const isUiCollapsedInit = viewportEl ? viewportEl.classList.contains('nav-ui-collapsed') : (window.innerWidth < 768);
            hud.style.display = isUiCollapsedInit ? 'none' : 'flex';

            const badgeEl = document.getElementById('hudNavBadge');
            const badgeTextEl = document.getElementById('hudBadgeText');
            const distValEl = document.getElementById('hudDistanceVal');
            const timeValEl = document.getElementById('hudTimeVal');
            const stepInstEl = document.getElementById('hudStepInstruction');
            const simTipEl = document.getElementById('hudSimTip');
            const recenterBtn = document.getElementById('hudRecenterBtn');

            function fmtDist(m) {
                if (m == null || isNaN(m)) return '--';
                if (m >= 1000) return (m / 1000).toFixed(1) + ' km';
                return Math.round(m) + ' m';
            }

            function fmtTime(s) {
                if (s == null || isNaN(s)) return '--';
                if (s < 60) return '< 1 min';
                if (s < 3600) return Math.round(s / 60) + ' min';
                const h = Math.floor(s / 3600);
                const m = Math.round((s % 3600) / 60);
                return `${h}h ${m}m`;
            }

            let liveGpsMarker = null;
            let accuracyCircle = null;
            let userPos = null;
            let autoFollow = true;
            let initialFitDone = false;

            // Immediate initial route preview from Entrance Gate to Destination House while GPS locks on
            if (staticPathIds && staticPathIds.length > 0) {
                const initLatLngs = staticPathIds.map(id => coordinatesMap.get(id)).filter(Boolean);
                let initDist = 0;
                for (let i = 0; i < initLatLngs.length - 1; i++) {
                    initDist += map.distance(initLatLngs[i], initLatLngs[i + 1]);
                }

                // Solid blue line strictly on the middle of the road
                L.polyline(initLatLngs, {
                    color: '#1d4ed8', weight: 8, opacity: 0.85, lineCap: 'round', lineJoin: 'round', interactive: false
                }).addTo(routingLayerGroup);
                L.polyline(initLatLngs, {
                    color: '#2563eb', weight: 5, opacity: 1, lineCap: 'round', lineJoin: 'round', interactive: false
                }).addTo(routingLayerGroup);

                // Dotted off-road walking line from road centerline to house (Google Maps style)
                const lastRoadPt = initLatLngs[initLatLngs.length - 1];
                if (lastRoadPt) {
                    const offRoadDist = map.distance(lastRoadPt, [destCoord.lat, destCoord.lng]);
                    initDist += offRoadDist;
                    if (offRoadDist > 1.5) {
                        L.polyline([lastRoadPt, [destCoord.lat, destCoord.lng]], {
                            color: '#ef4444', weight: 3.5, opacity: 0.9, dashArray: '3, 7', lineCap: 'round', interactive: false
                        }).addTo(routingLayerGroup);
                        L.circleMarker(lastRoadPt, {
                            radius: 3.5, fillColor: '#ffffff', color: '#ef4444', weight: 2, fillOpacity: 1
                        }).addTo(routingLayerGroup);
                    }
                }

                // Destination Marker
                L.circleMarker([destCoord.lat, destCoord.lng], {
                    radius: 9, fillColor: '#ef4444', color: '#ffffff', weight: 3, fillOpacity: 1
                }).addTo(routingLayerGroup).bindTooltip(`📍 Destination (${destinationName})`);

                if (distValEl) distValEl.textContent = fmtDist(initDist);
                if (timeValEl) timeValEl.textContent = fmtTime(initDist / 4.16);
                if (stepInstEl) stepInstEl.innerHTML = `Subdivision route to <strong>${destinationName}</strong> (waiting for GPS...)`;

                map.fitBounds(L.latLngBounds([entranceCoord, [destCoord.lat, destCoord.lng]]), { padding: [40, 40] });
            }

            let lastOSRMTime = 0;
            let lastOSRMCoord = null;
            let cachedOSRM = null;

            async function fetchOSRM(startLat, startLng, endLat, endLng) {
                const now = Date.now();
                if (lastOSRMCoord && cachedOSRM) {
                    const moved = map.distance([startLat, startLng], [lastOSRMCoord.lat, lastOSRMCoord.lng]);
                    if (moved < 20 && (now - lastOSRMTime) < 6000) {
                        return cachedOSRM;
                    }
                }

                try {
                    const url = `https://router.project-osrm.org/route/v1/driving/${startLng.toFixed(6)},${startLat.toFixed(6)};${endLng.toFixed(6)},${endLat.toFixed(6)}?overview=full&geometries=geojson`;
                    const res = await fetch(url);
                    if (res.ok) {
                        const data = await res.json();
                        if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                            const r = data.routes[0];
                            const rawLatLngs = r.geometry.coordinates.map(c => [c[1], c[0]]);
                            // Align OSRM's OpenStreetMap road centerline to Google Maps' road centerline at the subdivision entrance
                            let alignedLatLngs = rawLatLngs;
                            if (rawLatLngs.length > 0) {
                                const lastPt = rawLatLngs[rawLatLngs.length - 1];
                                const dLatEnd = endLat - lastPt[0];
                                const dLngEnd = endLng - lastPt[1];
                                const firstPt = rawLatLngs[0];
                                const totalSpan = map.distance(firstPt, lastPt);
                                alignedLatLngs = rawLatLngs.map((pt, idx) => {
                                    if (idx === rawLatLngs.length - 1) return [endLat, endLng];
                                    const distToEnd = map.distance(pt, lastPt);
                                    // Smoothly keep highway approach centered on Google Maps road and pin user start if far away
                                    const wEnd = Math.exp(-distToEnd / 450);
                                    const wStart = totalSpan > 35 ? Math.min(1, map.distance(pt, firstPt) / 25) : 1;
                                    const w = wEnd * wStart;
                                    return [pt[0] + dLatEnd * w, pt[1] + dLngEnd * w];
                                });
                            }
                            cachedOSRM = {
                                latlngs: alignedLatLngs,
                                distance: r.distance,
                                duration: r.duration
                            };
                            lastOSRMTime = now;
                            lastOSRMCoord = { lat: startLat, lng: startLng };
                            return cachedOSRM;
                        }
                    }
                } catch (err) {
                    console.warn('OSRM Highway routing notice:', err);
                }

                const directDist = map.distance([startLat, startLng], [endLat, endLng]);
                return {
                    latlngs: [[startLat, startLng], [endLat, endLng]],
                    distance: directDist,
                    duration: directDist / 8.33
                };
            }

            function checkIfInside(lat, lng) {
                // 1. Proximity to entrance gate
                if (map.distance([lat, lng], entranceCoord) <= 45) return true;

                // 2. Proximity to any internal subdivision road segment (tolerance for GPS jitter)
                const pt = [lat, lng];
                for (let i = 0; i < segments.length; i++) {
                    const proj = projectPointToSegment(pt, segments[i].p1, segments[i].p2);
                    if (proj.dist <= 35) return true;
                }

                // 3. Proximity to destination house
                if (map.distance([lat, lng], [destCoord.lat, destCoord.lng]) <= 50) return true;

                return false;
            }

            // Point of View (POV) Directional Guide (Compass & Moving Bearing)
            let currentHeading = null;
            let lastCoordForHeading = null;

            function calculateBearing(lat1, lon1, lat2, lon2) {
                const toRad = Math.PI / 180;
                const dLon = (lon2 - lon1) * toRad;
                const y = Math.sin(dLon) * Math.cos(lat2 * toRad);
                const x = Math.cos(lat1 * toRad) * Math.sin(lat2 * toRad) -
                          Math.sin(lat1 * toRad) * Math.cos(lat2 * toRad) * Math.cos(dLon);
                return (Math.atan2(y, x) * 180 / Math.PI + 360) % 360;
            }

            function updateHeadingCone(h) {
                if (h == null || isNaN(h)) return;
                currentHeading = (h % 360 + 360) % 360;
                const cone = document.getElementById('userHeadingCone');
                if (cone) {
                    cone.style.transform = `rotate(${currentHeading}deg)`;
                }
            }

            function onDeviceOrientation(e) {
                let h = null;
                if (e.webkitCompassHeading !== undefined && e.webkitCompassHeading !== null) {
                    h = e.webkitCompassHeading;
                } else if (e.alpha !== null && e.alpha !== undefined) {
                    h = 360 - e.alpha;
                }
                if (h !== null && !isNaN(h)) updateHeadingCone(h);
            }
            if (window.DeviceOrientationEvent) {
                window.addEventListener('deviceorientation', onDeviceOrientation, false);
            }

            async function handlePosition(lat, lng, accuracy, headingVal) {
                userPos = { lat, lng, accuracy };

                // Update heading from GPS or motion bearing
                if (headingVal !== null && headingVal !== undefined && !isNaN(headingVal)) {
                    updateHeadingCone(headingVal);
                } else if (lastCoordForHeading) {
                    const d = map.distance([lastCoordForHeading.lat, lastCoordForHeading.lng], [lat, lng]);
                    if (d > 2.0) {
                        const b = calculateBearing(lastCoordForHeading.lat, lastCoordForHeading.lng, lat, lng);
                        updateHeadingCone(b);
                        lastCoordForHeading = { lat, lng };
                    }
                } else {
                    lastCoordForHeading = { lat, lng };
                }

                const beaconIcon = L.divIcon({
                    className: 'subdivision-live-marker-container',
                    html: `
                        <div class="subdivision-live-beacon">
                            <div class="user-heading-cone" id="userHeadingCone" style="transform: rotate(${currentHeading || 0}deg);">
                                <svg width="60" height="60" viewBox="0 0 60 60" style="display:block;">
                                    <defs>
                                        <radialGradient id="userBeamGlow" cx="50%" cy="50%" r="50%">
                                            <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.6" />
                                            <stop offset="100%" stop-color="#3b82f6" stop-opacity="0.0" />
                                        </radialGradient>
                                    </defs>
                                    <path d="M 30 30 L 12 2 A 32 32 0 0 1 48 2 Z" fill="url(#userBeamGlow)" />
                                </svg>
                            </div>
                            <div class="pulse-ring"></div>
                            <div class="center-dot"></div>
                        </div>
                    `,
                    iconSize: [60, 60],
                    iconAnchor: [30, 30]
                });

                if (!liveGpsMarker) {
                    liveGpsMarker = L.marker([lat, lng], { icon: beaconIcon, zIndexOffset: 1000 }).addTo(liveNavGroup);
                    accuracyCircle = L.circle([lat, lng], {
                        radius: Math.min(accuracy || 10, 45),
                        color: '#3b82f6',
                        weight: 1,
                        fillColor: '#93c5fd',
                        fillOpacity: 0.18
                    }).addTo(liveNavGroup);
                } else {
                    liveGpsMarker.setLatLng([lat, lng]);
                    accuracyCircle.setLatLng([lat, lng]);
                    accuracyCircle.setRadius(Math.min(accuracy || 10, 45));
                }

                liveGpsMarker.bindTooltip('📍 Your Live GPS Location', { direction: 'top', offset: [0, -10] });

                if (simTipEl) {
                    simTipEl.innerHTML = '🛰️ Live High-Precision GPS Active • Tracking in real-time';
                }

                if (autoFollow) {
                    map.panTo([lat, lng], { animate: true });
                }

                // Destination distance check (< 12m)
                const distToDest = map.distance([lat, lng], [destCoord.lat, destCoord.lng]);
                if (distToDest < 12) {
                    osrmLayerGroup.clearLayers();
                    routingLayerGroup.clearLayers();

                    if (badgeEl) {
                        badgeEl.className = 'nav-hud-badge arrived';
                        badgeTextEl.textContent = '🎉 You Have Arrived!';
                    }
                    if (distValEl) distValEl.textContent = '0 m';
                    if (timeValEl) timeValEl.textContent = '0 min';
                    if (stepInstEl) stepInstEl.innerHTML = `Welcome to <strong>${destinationName}</strong>! You have reached your destination.`;

                    L.circle([destCoord.lat, destCoord.lng], {
                        radius: 12, color: '#16a34a', weight: 3, fillColor: '#86efac', fillOpacity: 0.5
                    }).addTo(routingLayerGroup);
                    return;
                }

                const inside = checkIfInside(lat, lng);

                if (!inside) {
                    // OUTSIDE HIGHWAY ROUTING (OSRM to Highway-Subdivision Junction + Internal Dijkstra to Destination)
                    if (badgeEl) {
                        badgeEl.className = 'nav-hud-badge';
                        badgeTextEl.textContent = '🚗 Highway Navigation';
                    }

                    // Compute highway-junction-to-destination Dijkstra path along internal road network centerline
                    // Main Road starts at local [-0.00108116845, -0.00247300864] where it meets the exact centerline of Tagbong Pili Camarines Sur Rd
                    const mainRoadJunc = transformCoordPair([-0.00108116845, -0.00247300864], calib);
                    const highwayEntryCoord = [mainRoadJunc[1], mainRoadJunc[0]];

                    let tempGraph = baseGraph.clone();
                    const gateSnap = snapPointToRoad(highwayEntryCoord[0], highwayEntryCoord[1], 'gate', tempGraph);
                    const destSnap = snapPointToRoad(destCoord.lat, destCoord.lng, 'dest', tempGraph);
                    const pathIds = (gateSnap && destSnap) ? tempGraph.dijkstra(gateSnap.nodeId, destSnap.nodeId) : [];

                    let internalRoadDist = 0;
                    const internalRoadLatLngs = [];
                    if (pathIds && pathIds.length > 0) {
                        pathIds.forEach(id => {
                            const pt = coordinatesMap.get(id);
                            if (pt) internalRoadLatLngs.push(pt);
                        });
                    } else if (gateSnap && destSnap) {
                        internalRoadLatLngs.push(gateSnap.point, destSnap.point);
                    }

                    const osrmTarget = internalRoadLatLngs.length > 0 ? internalRoadLatLngs[0] : highwayEntryCoord;
                    const osrm = await fetchOSRM(lat, lng, osrmTarget[0], osrmTarget[1]);

                    for (let i = 0; i < internalRoadLatLngs.length - 1; i++) {
                        internalRoadDist += map.distance(internalRoadLatLngs[i], internalRoadLatLngs[i + 1]);
                    }
                    const dOffDist = destSnap ? destSnap.dist : 0;
                    const totalInternalDist = internalRoadDist + dOffDist;

                    // MERGE INTO ONE CONTINUOUS SEAMLESS ROAD ROUTE (Middle of outside road -> Middle of subdivision road)
                    const fullRouteLatLngs = [];
                    if (osrm && Array.isArray(osrm.latlngs)) {
                        osrm.latlngs.forEach(pt => fullRouteLatLngs.push(pt));
                    }
                    internalRoadLatLngs.forEach((pt, idx) => {
                        if (idx === 0 && fullRouteLatLngs.length > 0) {
                            const prev = fullRouteLatLngs[fullRouteLatLngs.length - 1];
                            if (map.distance(prev, pt) < 0.5) return;
                        }
                        fullRouteLatLngs.push(pt);
                    });

                    routingLayerGroup.clearLayers();
                    osrmLayerGroup.clearLayers();

                    // Formal Google Maps / OSRM navigation dual-layer solid line on road
                    L.polyline(fullRouteLatLngs, {
                        color: '#1d4ed8',
                        weight: 8,
                        opacity: 0.95,
                        lineCap: 'round',
                        lineJoin: 'round',
                        interactive: false
                    }).addTo(routingLayerGroup);

                    L.polyline(fullRouteLatLngs, {
                        color: '#2563eb',
                        weight: 5,
                        opacity: 1,
                        lineCap: 'round',
                        lineJoin: 'round',
                        interactive: false
                    }).addTo(routingLayerGroup);

                    // Off-road walking dotted line to destination house (Google Maps style)
                    if (destSnap && destSnap.dist > 1.8) {
                        L.polyline([destSnap.point, [destCoord.lat, destCoord.lng]], {
                            color: '#ef4444',
                            weight: 3.5,
                            opacity: 0.9,
                            dashArray: '3, 7',
                            lineCap: 'round',
                            interactive: false
                        }).addTo(routingLayerGroup);

                        L.circleMarker(destSnap.point, {
                            radius: 3.5, fillColor: '#ffffff', color: '#ef4444', weight: 2, fillOpacity: 1
                        }).addTo(routingLayerGroup);
                    }

                    // Dest Marker
                    L.circleMarker([destCoord.lat, destCoord.lng], {
                        radius: 9, fillColor: '#ef4444', color: '#ffffff', weight: 3, fillOpacity: 1
                    }).addTo(routingLayerGroup).bindTooltip('📍 Destination (' + destinationName + ')');

                    const totalDist = (osrm ? osrm.distance : map.distance([lat, lng], entranceCoord)) + totalInternalDist;
                    const totalTime = (osrm ? osrm.duration : 60) + (totalInternalDist / 4.16);

                    if (distValEl) distValEl.textContent = fmtDist(totalDist);
                    if (timeValEl) timeValEl.textContent = fmtTime(totalTime);
                    if (stepInstEl) stepInstEl.innerHTML = `Head along Minalabac-Pili Road towards <strong>Althesa Entrance Gate</strong> (${fmtDist(osrm ? osrm.distance : 0)})`;

                    if (!initialFitDone) {
                        initialFitDone = true;
                        map.fitBounds(L.latLngBounds([[lat, lng], entranceCoord, [destCoord.lat, destCoord.lng]]), { padding: [45, 45] });
                    }

                } else {
                    // INSIDE SUBDIVISION NAVIGATION (Google Maps Architecture: Clean Road Polyline + Dotted Walking Guides)
                    osrmLayerGroup.clearLayers();
                    routingLayerGroup.clearLayers();

                    if (badgeEl) {
                        badgeEl.className = 'nav-hud-badge inside';
                        badgeTextEl.textContent = '🏡 Inside Althesa Village';
                    }

                    let liveGraph = baseGraph.clone();
                    const userSnap = snapPointToRoad(lat, lng, 'u', liveGraph);
                    const destSnap = snapPointToRoad(destCoord.lat, destCoord.lng, 'd', liveGraph);

                    const livePathIds = (userSnap && destSnap) ? liveGraph.dijkstra(userSnap.nodeId, destSnap.nodeId) : [];

                    const roadLatLngs = [];
                    if (livePathIds && livePathIds.length > 0) {
                        livePathIds.forEach(id => {
                            const pt = coordinatesMap.get(id);
                            if (pt) roadLatLngs.push(pt);
                        });
                    } else if (userSnap && destSnap) {
                        roadLatLngs.push(userSnap.point, destSnap.point);
                    }

                    let roadDist = 0;
                    for (let i = 0; i < roadLatLngs.length - 1; i++) {
                        roadDist += map.distance(roadLatLngs[i], roadLatLngs[i + 1]);
                    }
                    const uOffDist = userSnap ? userSnap.dist : 0;
                    const dOffDist = destSnap ? destSnap.dist : 0;
                    const remainingDist = roadDist + uOffDist + dOffDist;

                    // 1. Solid Navigation Route ONLY along the road network (No loops or spaghetti)
                    if (roadLatLngs.length > 0) {
                        L.polyline(roadLatLngs, {
                            color: '#1d4ed8',
                            weight: 8,
                            opacity: 0.95,
                            lineCap: 'round',
                            lineJoin: 'round',
                            interactive: false
                        }).addTo(routingLayerGroup);

                        L.polyline(roadLatLngs, {
                            color: '#2563eb',
                            weight: 5,
                            opacity: 1,
                            lineCap: 'round',
                            lineJoin: 'round',
                            interactive: false
                        }).addTo(routingLayerGroup);
                    }

                    // 2. Off-Road Walking Dotted Line from User to Road (Google Maps style)
                    if (userSnap && userSnap.dist > 1.8) {
                        L.polyline([[lat, lng], userSnap.point], {
                            color: '#2563eb',
                            weight: 3.5,
                            opacity: 0.9,
                            dashArray: '3, 7',
                            lineCap: 'round',
                            interactive: false
                        }).addTo(routingLayerGroup);

                        L.circleMarker(userSnap.point, {
                            radius: 3.5,
                            fillColor: '#ffffff',
                            color: '#2563eb',
                            weight: 2,
                            fillOpacity: 1
                        }).addTo(routingLayerGroup);
                    }

                    // 3. Off-Road Walking Dotted Line from Road to Destination House (Google Maps style)
                    if (destSnap && destSnap.dist > 1.8) {
                        L.polyline([destSnap.point, [destCoord.lat, destCoord.lng]], {
                            color: '#ef4444',
                            weight: 3.5,
                            opacity: 0.9,
                            dashArray: '3, 7',
                            lineCap: 'round',
                            interactive: false
                        }).addTo(routingLayerGroup);

                        L.circleMarker(destSnap.point, {
                            radius: 3.5,
                            fillColor: '#ffffff',
                            color: '#ef4444',
                            weight: 2,
                            fillOpacity: 1
                        }).addTo(routingLayerGroup);
                    }

                    // 4. Destination Marker at House
                    L.circleMarker([destCoord.lat, destCoord.lng], {
                        radius: 9, fillColor: '#ef4444', color: '#ffffff', weight: 3, fillOpacity: 1
                    }).addTo(routingLayerGroup).bindTooltip('📍 Destination (' + destinationName + ')');

                    const remainingDuration = remainingDist / 4.16;

                    if (distValEl) distValEl.textContent = fmtDist(remainingDist);
                    if (timeValEl) timeValEl.textContent = fmtTime(remainingDuration);
                    if (stepInstEl) stepInstEl.innerHTML = `Follow internal street towards <strong>${destinationName}</strong> (${fmtDist(remainingDist)})`;

                    if (!initialFitDone) {
                        initialFitDone = true;
                        map.setView([lat, lng], 19);
                    }
                }
            }

            function onRecenterClick(e) {
                e.stopPropagation();
                autoFollow = true;
                if (userPos) {
                    map.panTo([userPos.lat, userPos.lng], { animate: true });
                }
            }

            function onMapDragStart() {
                autoFollow = false;
            }

            if (recenterBtn) {
                recenterBtn.addEventListener('click', onRecenterClick);
            }
            map.on('dragstart', onMapDragStart);

            let watchId = null;
            if (navigator.geolocation) {
                watchId = navigator.geolocation.watchPosition(
                    (pos) => {
                        handlePosition(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy, pos.coords.heading);
                    },
                    (err) => {
                        console.warn('Geolocation notice:', err.message);
                        if (!userPos) {
                            if (badgeEl) {
                                badgeEl.className = 'nav-hud-badge';
                                badgeTextEl.textContent = '📍 Waiting for GPS';
                            }
                            if (simTipEl) {
                                simTipEl.innerHTML = '⚠️ Waiting for GPS signal • Ensure location is allowed (HTTPS required on mobile)';
                            }
                        }
                    },
                    { enableHighAccuracy: true, timeout: 12000, maximumAge: 3000 }
                );
            } else {
                if (simTipEl) {
                    simTipEl.innerHTML = '⚠️ Geolocation not supported on this device/browser';
                }
            }

            liveNavTracker = {
                destroy: () => {
                    if (watchId !== null && navigator.geolocation) {
                        navigator.geolocation.clearWatch(watchId);
                    }
                    if (recenterBtn) {
                        recenterBtn.removeEventListener('click', onRecenterClick);
                    }
                    if (window.DeviceOrientationEvent) {
                        window.removeEventListener('deviceorientation', onDeviceOrientation, false);
                    }
                    map.off('dragstart', onMapDragStart);
                    osrmLayerGroup.clearLayers();
                    liveNavGroup.clearLayers();
                }
            };
        }

        // Case 1: Fixed destination house (Guard/Visitor view)
        if (config.endNode) {
            let entranceCoord = [calib.anchorLat, calib.anchorLng];
            if (entrance && entrance.features && entrance.features.length > 0) {
                const c = entrance.features[0].geometry.coordinates;
                entranceCoord = [c[1], c[0]];
            }

            let startNodeId = injectSnappedNode(entranceCoord[0], entranceCoord[1], 'start', graph);

            let destCoord = null;
            if (houseNodes && houseNodes.features) {
                for (let feature of houseNodes.features) {
                    if (feature.properties.block_num && feature.properties.lot_number) {
                        const id = `B${feature.properties.block_num} L${feature.properties.lot_number}`;
                        if (id === config.endNode) {
                            const c = feature.geometry.coordinates;
                            destCoord = L.latLng(c[1], c[0]);
                            break;
                        }
                    }
                }
            }

            if (!destCoord && houseLayer) {
                houseLayer.eachLayer(layer => {
                    if (layer.feature.properties && layer.feature.properties.block_num) {
                        const id = `B${layer.feature.properties.block_num} L${layer.feature.properties.lot_number}`;
                        if (id === config.endNode) {
                            destCoord = layer.getBounds().getCenter();
                        }
                    }
                });
            }

            if (startNodeId && destCoord) {
                let tempGraph = graph.clone();
                let endNodeId = injectSnappedNode(destCoord.lat, destCoord.lng, 'end', tempGraph);

                const pathNodeIds = tempGraph.dijkstra(startNodeId, endNodeId);
                const altPathNodeIds = tempGraph.alternativePath(startNodeId, endNodeId);

                // Step 3 & 4: Live Hybrid Navigation (OSRM highway + live snapped Dijkstra)
                if (config.liveNavigation) {
                    startLiveHybridNavigation({
                        entranceCoord: entranceCoord,
                        destCoord: destCoord,
                        graph: graph,
                        coordinatesMap: coordinatesMap,
                        segments: segments,
                        injectSnappedNode: injectSnappedNode,
                        staticPathIds: pathNodeIds,
                        styles: styles,
                        destinationName: config.destinationName || config.endNode
                    });
                } else {
                    // Static fallback rendering for guard dashboard (Solid line on road + dotted off-road to house)
                    if (altPathNodeIds.length > 0) {
                        const altLatlngs = altPathNodeIds.map(id => coordinatesMap.get(id)).filter(Boolean);
                        L.polyline(altLatlngs, styles.altRoute).addTo(routingLayerGroup);
                    }

                    if (pathNodeIds.length > 0) {
                        const latlngs = pathNodeIds.map(id => coordinatesMap.get(id)).filter(Boolean);
                        const routeLine = L.polyline(latlngs, styles.route).addTo(routingLayerGroup);

                        const lastRoadPt = latlngs[latlngs.length - 1];
                        if (lastRoadPt && map.distance(lastRoadPt, [destCoord.lat, destCoord.lng]) > 1.5) {
                            L.polyline([lastRoadPt, [destCoord.lat, destCoord.lng]], {
                                color: '#2563eb', weight: 3.5, opacity: 0.9, dashArray: '3, 7', lineCap: 'round', interactive: false
                            }).addTo(routingLayerGroup);
                            L.circleMarker(lastRoadPt, {
                                radius: 3.5, fillColor: '#ffffff', color: '#2563eb', weight: 2, fillOpacity: 1
                            }).addTo(routingLayerGroup);
                        }

                        L.circleMarker([destCoord.lat, destCoord.lng], {
                            radius: 8, fillColor: '#2563eb', color: '#fff', weight: 3, fillOpacity: 1
                        }).addTo(routingLayerGroup).bindTooltip('Destination (' + config.endNode + ')');

                        if (!config.interactive) {
                            map.fitBounds(routeLine.getBounds(), { padding: [30, 30] });
                        }
                    }
                }
            }
        }

        // Case 2: Interactive house-to-house routing (Solid line on road + dotted off-road to houses)
        if (config.interactiveRouting && routeStartHouse && routeEndHouse) {
            let startCoord = null;
            let endCoord = null;
            if (houseNodes && houseNodes.features) {
                for (let f of houseNodes.features) {
                    if (f.properties.block_num == routeStartHouse.block && f.properties.lot_number == routeStartHouse.lot) {
                        startCoord = L.latLng(f.geometry.coordinates[1], f.geometry.coordinates[0]);
                    }
                    if (f.properties.block_num == routeEndHouse.block && f.properties.lot_number == routeEndHouse.lot) {
                        endCoord = L.latLng(f.geometry.coordinates[1], f.geometry.coordinates[0]);
                    }
                }
            }
            if (!startCoord) startCoord = routeStartHouse.coord;
            if (!endCoord) endCoord = routeEndHouse.coord;

            let tempGraph = graph.clone();
            let sNode = injectSnappedNode(startCoord.lat, startCoord.lng, 'dynamicStart', tempGraph);
            let eNode = injectSnappedNode(endCoord.lat, endCoord.lng, 'dynamicEnd', tempGraph);

            const altPathNodeIds = tempGraph.alternativePath(sNode, eNode);
            if (altPathNodeIds.length > 0) {
                const altLatlngs = altPathNodeIds.map(id => coordinatesMap.get(id)).filter(Boolean);
                L.polyline(altLatlngs, styles.altRoute).addTo(routingLayerGroup);
            }

            const pathNodeIds = tempGraph.dijkstra(sNode, eNode);
            if (pathNodeIds.length > 0) {
                const latlngs = pathNodeIds.map(id => coordinatesMap.get(id)).filter(Boolean);
                L.polyline(latlngs, styles.route).addTo(routingLayerGroup);

                if (latlngs[0] && map.distance([startCoord.lat, startCoord.lng], latlngs[0]) > 1.5) {
                    L.polyline([[startCoord.lat, startCoord.lng], latlngs[0]], {
                        color: '#10b981', weight: 3.5, opacity: 0.9, dashArray: '3, 7', lineCap: 'round', interactive: false
                    }).addTo(routingLayerGroup);
                }
                const lastPt = latlngs[latlngs.length - 1];
                if (lastPt && map.distance(lastPt, [endCoord.lat, endCoord.lng]) > 1.5) {
                    L.polyline([lastPt, [endCoord.lat, endCoord.lng]], {
                        color: '#2563eb', weight: 3.5, opacity: 0.9, dashArray: '3, 7', lineCap: 'round', interactive: false
                    }).addTo(routingLayerGroup);
                }

                L.circleMarker([startCoord.lat, startCoord.lng], {
                    radius: 6, fillColor: '#10b981', color: '#fff', weight: 2, fillOpacity: 1
                }).addTo(routingLayerGroup).bindTooltip('Start (' + routeStartHouse.id + ')');

                L.circleMarker([endCoord.lat, endCoord.lng], {
                    radius: 8, fillColor: '#2563eb', color: '#fff', weight: 3, fillOpacity: 1
                }).addTo(routingLayerGroup).bindTooltip('Destination (' + routeEndHouse.id + ')');
            }
        }
    }

    // Render Stationary Real-World Reference Pins on Google Maps (NEVER move when dragging the subdivision overlay)
    function renderCalibrationReferencePins() {
        refMarkersGroup.clearLayers();
        if (!config.showCalibration) return;

        const pts = Array.isArray(calib.googlePoints) ? calib.googlePoints : [];

        pts.forEach((pt, idx) => {
            const isAlignmentPin = pt.type !== 'record';
            const color = pt.color || PIN_PALETTE[idx % PIN_PALETTE.length];
            const latStr = pt.latStr !== undefined ? pt.latStr : String(pt.lat);
            const lngStr = pt.lngStr !== undefined ? pt.lngStr : String(pt.lng);

            // Google Maps Outer target ring
            const marker = L.circleMarker([pt.lat, pt.lng], {
                radius: isAlignmentPin ? 8.5 : 6.5,
                fillColor: color,
                color: '#ffffff',
                weight: isAlignmentPin ? 2.5 : 1.5,
                fillOpacity: 0.95
            }).addTo(refMarkersGroup);

            // Inner center dot
            L.circleMarker([pt.lat, pt.lng], {
                radius: isAlignmentPin ? 3.5 : 2.5,
                fillColor: '#ffffff',
                color: color,
                weight: 1.5,
                fillOpacity: 1,
                interactive: false
            }).addTo(refMarkersGroup);

            let labelText = pt.label || `Pin ${idx + 1}`;
            if (idx === 0 || pt.type === 'entrance') {
                labelText = `Pin 1: Entrance Road (Main Gate)`;
            } else if (pt.block !== undefined && pt.lot !== undefined && !pt.label) {
                labelText = `Pin ${idx + 1}: B${pt.block} L${pt.lot} Front Road`;
            }

            marker.bindTooltip(
                `📍 <b>${labelText}</b><br><span style="font-family:monospace; font-size:10px;">${latStr}, ${lngStr}</span><br><span style="font-size:9px; color:#64748b;">${isAlignmentPin ? '🎯 Front-Road Alignment Pin (Click to edit)' : '📝 Record Pin (Click to edit)'}</span>`,
                { direction: 'top', offset: [0, -6] }
            );

            // Click pin to populate edit form
            marker.on('click', () => {
                if (window.__onSelectGooglePinForEdit) {
                    window.__onSelectGooglePinForEdit(pt.id);
                }
            });
        });
    }

    // Build On-Screen Calibration & Reference Points Manager Panel inside the Map Container
    if (config.showCalibration) {
        const containerEl = document.getElementById(mapElementId);
        if (containerEl) {
            containerEl.style.position = 'relative';
            const panel = document.createElement('div');
            panel.className = 'gis-calib-panel';
            panel.style.width = '365px';
            panel.innerHTML = `
                <div class="gis-calib-header" id="gisCalibToggle">
                    <div class="gis-calib-title">
                        <span>🛰️</span>
                        <span>Satellite Map Alignment</span>
                    </div>
                    <button type="button" class="gis-calib-btn" id="gisCalibCollapseBtn" style="padding:2px 7px; font-size:10px;">Hide</button>
                </div>
                <div class="gis-calib-body" id="gisCalibBody">
                    <!-- Undo (Ctrl+Z) / Redo (Ctrl+Y) / Reset Default Toolbar -->
                    <div class="gis-calib-row" style="display:grid; grid-template-columns:1.2fr 1fr 1fr; gap:5px; margin-top:8px;">
                        <button type="button" class="gis-calib-btn" id="btnCalibUndo" title="Undo last change (Ctrl+Z)" disabled style="opacity:0.5;">↩️ Undo (0)</button>
                        <button type="button" class="gis-calib-btn" id="btnCalibRedo" title="Redo (Ctrl+Y)" disabled style="opacity:0.5;">↪️ Redo</button>
                        <button type="button" class="gis-calib-btn" id="btnCalibResetDefault" title="Reset to Default 8 Front-Road Alignment Points">🔄 Default (8 Pts)</button>
                    </div>

                    <!-- Google Maps Reference Pins Manager (Add / Edit / Delete - Full Precision, No Rounding) -->
                    <div class="gis-calib-row" style="background:rgba(30,41,59,0.75); border:1px solid #334155; border-radius:10px; padding:8px;">
                        <div class="gis-calib-label" style="margin-bottom:6px;">
                            <span style="color:#38bdf8;">📍 Front-Road Reference Pins</span>
                            <span style="font-size:10px; color:#94a3b8;">All 8 Road Points Active</span>
                        </div>
                        <div id="googlePointsList" style="display:flex; flex-direction:column; gap:5px; max-height:185px; overflow-y:auto; margin-bottom:8px;"></div>

                        <!-- Add / Edit Google Maps Pin Form -->
                        <div style="border-top:1px solid #334155; padding-top:7px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                                <span id="lblGooglePinMode" style="font-size:10px; font-weight:700; color:#94a3b8;">➕ Add Road Pin</span>
                                <div style="display:flex; gap:3px;">
                                    <button type="button" class="gis-calib-btn" id="btnPickHouseOnMap" style="display:none; padding:2px 6px; font-size:10px;" title="Click any house on the map to select its Front Road">🏠 Pick House</button>
                                    <button type="button" class="gis-calib-btn" id="btnClickMapForPin" style="padding:2px 6px; font-size:10px;" title="Click anywhere on the satellite view to capture exact GPS">🖱️ Map Click</button>
                                </div>
                            </div>
                            <!-- Dynamic Block & Lot for Front-Road Alignment Pins -->
                            <div id="rowDynamicBlockLot" style="display:none; grid-template-columns:1fr 1fr; gap:4px; margin-bottom:5px;">
                                <div>
                                    <span style="font-size:9.5px; color:#94a3b8; display:block; margin-bottom:2px;">Block # (Front Road)</span>
                                    <input type="number" id="inpGooglePinBlock" min="1" placeholder="Block #" style="width:100%; box-sizing:border-box; background:#0f172a; color:#f8fafc; border:1px solid #475569; border-radius:6px; padding:4px 6px; font-size:11px;">
                                </div>
                                <div>
                                    <span style="font-size:9.5px; color:#94a3b8; display:block; margin-bottom:2px;">Lot # (Front Road)</span>
                                    <input type="number" id="inpGooglePinLot" min="1" placeholder="Lot #" style="width:100%; box-sizing:border-box; background:#0f172a; color:#f8fafc; border:1px solid #475569; border-radius:6px; padding:4px 6px; font-size:11px;">
                                </div>
                            </div>
                            <div id="rowPinLabel" style="margin-bottom:5px;">
                                <input type="text" id="inpGooglePinLabel" placeholder="Pin Name / Landmark" style="width:100%; box-sizing:border-box; background:#0f172a; color:#f8fafc; border:1px solid #475569; border-radius:6px; padding:4px 6px; font-size:11px;">
                            </div>
                            <div style="display:flex; gap:4px; margin-bottom:5px;">
                                <input type="text" id="inpGooglePinGPS" placeholder="Exact lat, lng (no rounding)" style="flex:1; background:#0f172a; color:#f8fafc; border:1px solid #475569; border-radius:6px; padding:4px 6px; font-size:11px; font-family:monospace;">
                                <button type="button" class="gis-calib-btn" id="btnUseMyLiveGPS" title="Get live GPS from device (full precision)" style="padding:4px 6px; font-size:10px;">📍 My GPS</button>
                            </div>
                            <div style="display:flex; gap:5px;">
                                <button type="button" class="gis-calib-btn primary" id="btnSubmitGooglePin" style="flex:1; padding:5px;">➕ Add Pin</button>
                                <button type="button" class="gis-calib-btn" id="btnCancelEditGooglePin" style="display:none; padding:5px 8px;">Cancel</button>
                            </div>
                        </div>
                    </div>

                    <!-- Subdivision Overlay Alignment Controls -->
                    <div class="gis-calib-row">
                        <div class="gis-calib-label">
                            <span>Subdivision Overlay Alignment</span>
                            <span id="lblCalibAnchor" style="font-family:monospace; font-size:9.5px;">${calib.anchorLat}, ${calib.anchorLng}</span>
                        </div>
                        <div style="display:grid; grid-template-columns:1.45fr 1fr; gap:6px;">
                            <button type="button" class="gis-calib-btn" id="btnToggleDragSub" style="padding:7px 8px;">✋ Drag & Stretch: OFF</button>
                            <button type="button" class="gis-calib-btn primary" id="btnSnapToPins" title="Automatically align subdivision using all 8 Front-Road Points">🎯 Align All 8 Road Pts</button>
                        </div>
                        <div style="font-size:10px; color:#94a3b8; margin-top:4px; line-height:1.3;">
                            💡 All 8 points sit on the middle of the front road (never inside houses).
                        </div>
                    </div>

                    <!-- Base Map Switcher -->
                    <div class="gis-calib-row">
                        <div class="gis-calib-label"><span>Base Map Layer</span></div>
                        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:4px;">
                            <button type="button" class="gis-calib-btn" data-basemap="satellite">🛰️ Google Sat</button>
                            <button type="button" class="gis-calib-btn" data-basemap="street">🗺️ Street</button>
                            <button type="button" class="gis-calib-btn" data-basemap="none">⬜ Blank</button>
                        </div>
                    </div>

                    <!-- Rotation Slider -->
                    <div class="gis-calib-row">
                        <div class="gis-calib-label">
                            <span>Rotation Angle (°)</span>
                            <span id="lblCalibRot">${Number(calib.rotationDeg).toFixed(4)}°</span>
                        </div>
                        <div class="gis-calib-controls">
                            <button type="button" class="gis-calib-btn" data-rot-step="-0.5">-0.5°</button>
                            <input type="range" id="rngCalibRot" min="-180" max="180" step="0.05" value="${calib.rotationDeg}">
                            <button type="button" class="gis-calib-btn" data-rot-step="0.5">+0.5°</button>
                            <input type="number" id="numCalibRot" class="gis-calib-num" step="0.01" value="${calib.rotationDeg}">
                        </div>
                    </div>

                    <!-- Width (scaleX) Slider -->
                    <div class="gis-calib-row">
                        <div class="gis-calib-label">
                            <span style="color:#38bdf8;">↔️ Width Multiplier (X)</span>
                            <span id="lblCalibScaleX">${Number(calib.scaleX ?? calib.scale).toFixed(5)}x</span>
                        </div>
                        <div class="gis-calib-controls">
                            <button type="button" class="gis-calib-btn" data-scalex-step="-0.01">-1%</button>
                            <input type="range" id="rngCalibScaleX" min="0.25" max="2.50" step="0.002" value="${calib.scaleX ?? calib.scale}">
                            <button type="button" class="gis-calib-btn" data-scalex-step="0.01">+1%</button>
                            <input type="number" id="numCalibScaleX" class="gis-calib-num" step="0.001" value="${calib.scaleX ?? calib.scale}">
                        </div>
                    </div>

                    <!-- Height (scaleY) Slider -->
                    <div class="gis-calib-row">
                        <div class="gis-calib-label">
                            <span style="color:#fbbf24;">↕️ Height Multiplier (Y)</span>
                            <span id="lblCalibScaleY">${Number(calib.scaleY ?? calib.scale).toFixed(5)}x</span>
                        </div>
                        <div class="gis-calib-controls">
                            <button type="button" class="gis-calib-btn" data-scaley-step="-0.01">-1%</button>
                            <input type="range" id="rngCalibScaleY" min="0.25" max="2.50" step="0.002" value="${calib.scaleY ?? calib.scale}">
                            <button type="button" class="gis-calib-btn" data-scaley-step="0.01">+1%</button>
                            <input type="number" id="numCalibScaleY" class="gis-calib-num" step="0.001" value="${calib.scaleY ?? calib.scale}">
                        </div>
                    </div>

                    <!-- Opacity Slider -->
                    <div class="gis-calib-row">
                        <div class="gis-calib-label">
                            <span>House/Road Opacity (See Roofs)</span>
                            <span id="lblCalibOpacity">${Math.round((calib.opacity ?? 0.78) * 100)}%</span>
                        </div>
                        <div class="gis-calib-controls">
                            <input type="range" id="rngCalibOpacity" min="0.10" max="1.00" step="0.05" value="${calib.opacity ?? 0.78}">
                        </div>
                    </div>

                    <!-- Flip / Mirror & Background Fill Checkboxes -->
                    <div class="gis-calib-row" style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
                        <label style="display:flex; align-items:center; gap:5px; cursor:pointer; color:#e2e8f0;">
                            <input type="checkbox" id="chkCalibFlipX" ${calib.flipX ? 'checked' : ''}> Flip L/R
                        </label>
                        <label style="display:flex; align-items:center; gap:5px; cursor:pointer; color:#e2e8f0;">
                            <input type="checkbox" id="chkCalibFlipY" ${calib.flipY ? 'checked' : ''}> Flip U/D
                        </label>
                        <label style="display:flex; align-items:center; gap:5px; cursor:pointer; color:#e2e8f0;">
                            <input type="checkbox" id="chkCalibBgFill" ${calib.showBgFill !== false ? 'checked' : ''}> Show Land BG
                        </label>
                    </div>

                    <!-- Save & Copy Actions -->
                    <div class="gis-calib-row" style="display:flex; gap:6px; margin-top:12px;">
                        <button type="button" class="gis-calib-btn success" id="btnSaveCalib" style="flex:1; padding:7px;">💾 Save Alignment</button>
                        <button type="button" class="gis-calib-btn" id="btnCopyCalib" title="Copy full-precision calibration JSON">📋 Copy</button>
                    </div>
                    <div id="calibSaveStatus" style="font-size:11px; color:#34d399; margin-top:6px; display:none; text-align:center; font-weight:700;"></div>
                </div>
            `;

            // Prevent map clicks/drags when interacting with calibration panel
            L.DomEvent.disableClickPropagation(panel);
            L.DomEvent.disableScrollPropagation(panel);
            containerEl.appendChild(panel);

            const rngRot = panel.querySelector('#rngCalibRot');
            const numRot = panel.querySelector('#numCalibRot');
            const lblRot = panel.querySelector('#lblCalibRot');

            const rngScaleX = panel.querySelector('#rngCalibScaleX');
            const numScaleX = panel.querySelector('#numCalibScaleX');
            const lblScaleX = panel.querySelector('#lblCalibScaleX');

            const rngScaleY = panel.querySelector('#rngCalibScaleY');
            const numScaleY = panel.querySelector('#numCalibScaleY');
            const lblScaleY = panel.querySelector('#lblCalibScaleY');

            const rngOpacity = panel.querySelector('#rngCalibOpacity');
            const lblOpacity = panel.querySelector('#lblCalibOpacity');

            const chkFlipX = panel.querySelector('#chkCalibFlipX');
            const chkFlipY = panel.querySelector('#chkCalibFlipY');
            const chkBgFill = panel.querySelector('#chkCalibBgFill');
            const lblAnchor = panel.querySelector('#lblCalibAnchor');
            const statusEl = panel.querySelector('#calibSaveStatus');
            const btnToggleDragSub = panel.querySelector('#btnToggleDragSub');

            const btnUndo = panel.querySelector('#btnCalibUndo');
            const btnRedo = panel.querySelector('#btnCalibRedo');
            const btnResetDefault = panel.querySelector('#btnCalibResetDefault');

            const googlePointsListEl = panel.querySelector('#googlePointsList');
            const lblGooglePinMode = panel.querySelector('#lblGooglePinMode');
            const btnClickMapForPin = panel.querySelector('#btnClickMapForPin');
            const btnPickHouseOnMap = panel.querySelector('#btnPickHouseOnMap');
            const rowDynamicBlockLot = panel.querySelector('#rowDynamicBlockLot');
            const inpGooglePinBlock = panel.querySelector('#inpGooglePinBlock');
            const inpGooglePinLot = panel.querySelector('#inpGooglePinLot');
            const rowPinLabel = panel.querySelector('#rowPinLabel');
            const inpGooglePinLabel = panel.querySelector('#inpGooglePinLabel');
            const inpGooglePinGPS = panel.querySelector('#inpGooglePinGPS');
            const btnUseMyLiveGPS = panel.querySelector('#btnUseMyLiveGPS');
            const btnSubmitGooglePin = panel.querySelector('#btnSubmitGooglePin');
            const btnCancelEditGooglePin = panel.querySelector('#btnCancelEditGooglePin');

            function showStatusMsg(msg, color = '#34d399', autoHideMs = 3500) {
                statusEl.textContent = msg;
                statusEl.style.color = color;
                statusEl.style.display = 'block';
                if (autoHideMs > 0) {
                    setTimeout(() => {
                        if (statusEl.textContent === msg) statusEl.style.display = 'none';
                    }, autoHideMs);
                }
            }

            updateUndoRedoButtons = function () {
                if (btnUndo) {
                    btnUndo.disabled = undoStack.length === 0;
                    btnUndo.style.opacity = undoStack.length === 0 ? '0.45' : '1';
                    btnUndo.textContent = `↩️ Undo (${undoStack.length})`;
                }
                if (btnRedo) {
                    btnRedo.disabled = redoStack.length === 0;
                    btnRedo.style.opacity = redoStack.length === 0 ? '0.45' : '1';
                }
            };

            function performUndo() {
                if (undoStack.length === 0) {
                    showStatusMsg('ℹ️ Nothing to undo', '#94a3b8', 2000);
                    return;
                }
                redoStack.push(JSON.parse(JSON.stringify(calib)));
                calib = undoStack.pop();
                updateUndoRedoButtons();
                renderGooglePointsListUI();
                renderCalibrationReferencePins();
                syncUIWithCalib(false);
                showStatusMsg(`↩️ Undid last change (${undoStack.length} left)`, '#38bdf8', 2500);
            }

            function performRedo() {
                if (redoStack.length === 0) return;
                undoStack.push(JSON.parse(JSON.stringify(calib)));
                calib = redoStack.pop();
                updateUndoRedoButtons();
                renderGooglePointsListUI();
                renderCalibrationReferencePins();
                syncUIWithCalib(false);
                showStatusMsg('↪️ Redid change', '#38bdf8', 2500);
            }

            btnUndo.addEventListener('click', performUndo);
            btnRedo.addEventListener('click', performRedo);

            // Reset to Default Alignment & Default 8 Front-Road Google Points
            btnResetDefault.addEventListener('click', () => {
                pushUndoSnapshot();
                calib = JSON.parse(JSON.stringify(DEFAULT_CALIBRATION));
                calib.googlePoints = JSON.parse(JSON.stringify(DEFAULT_GOOGLE_POINTS));
                cancelGooglePinEditMode();
                setClickMapForPinMode(false);
                setPickHouseMode(false);
                renderGooglePointsListUI();
                renderCalibrationReferencePins();
                syncUIWithCalib(true);
                showStatusMsg('🔄 Reverted to Default 8 Front-Road Points & Alignment!', '#34d399', 3000);
            });

            // Global Ctrl+Z (Undo) and Ctrl+Y / Ctrl+Shift+Z (Redo) keyboard shortcuts
            window.addEventListener('keydown', (e) => {
                const tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
                if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
                if ((e.ctrlKey || e.metaKey) && !e.shiftKey && e.key.toLowerCase() === 'z') {
                    e.preventDefault();
                    performUndo();
                } else if ((e.ctrlKey || e.metaKey) && (e.key.toLowerCase() === 'y' || (e.shiftKey && e.key.toLowerCase() === 'z'))) {
                    e.preventDefault();
                    performRedo();
                }
            });

            // Click anywhere on Google Map to grab exact GPS coordinates (no rounding!)
            function onMapClickForPin(e) {
                if (!clickMapForPinMode) return;
                const latStr = String(e.latlng.lat);
                const lngStr = String(e.latlng.lng);
                inpGooglePinGPS.value = `${latStr}, ${lngStr}`;
                if (!inpGooglePinLabel.value.trim()) {
                    inpGooglePinLabel.value = `Pin ${calib.googlePoints.length + 1}`;
                }
                setClickMapForPinMode(false);
                inpGooglePinLabel.focus();
                showStatusMsg(`📍 Point captured from Google Maps: ${latStr}, ${lngStr}`, '#34d399', 3500);
            }

            function setClickMapForPinMode(enabled) {
                clickMapForPinMode = Boolean(enabled);
                if (clickMapForPinMode) {
                    if (dragSubdivisionMode) setDragSubdivisionMode(false);
                    if (pickHouseForPointMode) setPickHouseMode(false);
                    containerEl.style.cursor = 'crosshair';
                    btnClickMapForPin.classList.add('success');
                    btnClickMapForPin.textContent = '🖱️ Click Map...';
                    showStatusMsg('🖱️ Click anywhere on Google Satellite view to capture exact GPS coordinates', '#38bdf8', 0);
                    map.on('click', onMapClickForPin);
                } else {
                    containerEl.style.cursor = '';
                    btnClickMapForPin.classList.remove('success');
                    btnClickMapForPin.textContent = '🖱️ Click on Map';
                    map.off('click', onMapClickForPin);
                    if (statusEl.textContent.includes('Click anywhere on Google Satellite')) {
                        statusEl.style.display = 'none';
                    }
                }
            }

            btnClickMapForPin.addEventListener('click', () => {
                setClickMapForPinMode(!clickMapForPinMode);
            });

            // Live device GPS capture (Full unrounded IEEE-754 precision for site visit in Althesa Village)
            btnUseMyLiveGPS.addEventListener('click', () => {
                if (!navigator.geolocation) {
                    showStatusMsg('⚠️ Geolocation is not supported by this browser', '#fbbf24', 3500);
                    return;
                }
                showStatusMsg('📡 Acquiring high-precision device GPS...', '#38bdf8', 0);
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        const latStr = String(pos.coords.latitude);
                        const lngStr = String(pos.coords.longitude);
                        inpGooglePinGPS.value = `${latStr}, ${lngStr}`;
                        if (!inpGooglePinLabel.value.trim()) {
                            inpGooglePinLabel.value = `Field GPS (${new Date().toLocaleTimeString()})`;
                        }
                        showStatusMsg(`📍 GPS captured (±${Math.round(pos.coords.accuracy)}m): ${latStr}, ${lngStr}`, '#34d399', 4000);
                        inpGooglePinLabel.focus();
                    },
                    (err) => {
                        showStatusMsg(`⚠️ GPS error: ${err.message}`, '#ef4444', 4000);
                    },
                    { enableHighAccuracy: true, maximumAge: 0, timeout: 15000 }
                );
            });

            function setPickHouseMode(enabled) {
                pickHouseForPointMode = Boolean(enabled);
                if (pickHouseForPointMode) {
                    if (clickMapForPinMode) setClickMapForPinMode(false);
                    if (dragSubdivisionMode) setDragSubdivisionMode(false);
                    if (btnPickHouseOnMap) {
                        btnPickHouseOnMap.classList.add('success');
                        btnPickHouseOnMap.textContent = '🏠 Click House...';
                    }
                    showStatusMsg('🏠 Click on any house polygon on the map to select its Front Road', '#38bdf8', 0);
                } else {
                    if (btnPickHouseOnMap) {
                        btnPickHouseOnMap.classList.remove('success');
                        btnPickHouseOnMap.textContent = '🏠 Pick House';
                    }
                    if (statusEl.textContent.includes('Click on any house polygon')) {
                        statusEl.style.display = 'none';
                    }
                }
            }

            if (btnPickHouseOnMap) {
                btnPickHouseOnMap.addEventListener('click', () => {
                    setPickHouseMode(!pickHouseForPointMode);
                });
            }

            window.__onPickHouseForRefPoint = function (b, l) {
                if (inpGooglePinBlock) inpGooglePinBlock.value = b;
                if (inpGooglePinLot) inpGooglePinLot.value = l;
                inpGooglePinLabel.value = `B${b} L${l} Front Road`;
                setPickHouseMode(false);
                showStatusMsg(`🛣️ Selected Front Road of Block ${b} Lot ${l}`, '#34d399', 3000);
            };

            function cancelGooglePinEditMode() {
                editingPointId = null;
                setPickHouseMode(false);
                if (rowDynamicBlockLot) rowDynamicBlockLot.style.display = 'none';
                if (btnPickHouseOnMap) btnPickHouseOnMap.style.display = 'none';
                if (rowPinLabel) rowPinLabel.style.display = 'block';
                lblGooglePinMode.textContent = '➕ Add Road Pin';
                lblGooglePinMode.style.color = '#94a3b8';
                btnSubmitGooglePin.textContent = '➕ Add Pin';
                btnCancelEditGooglePin.style.display = 'none';
                inpGooglePinLabel.value = '';
                inpGooglePinGPS.value = '';
                if (inpGooglePinBlock) inpGooglePinBlock.value = '';
                if (inpGooglePinLot) inpGooglePinLot.value = '';
            }

            btnCancelEditGooglePin.addEventListener('click', cancelGooglePinEditMode);

            window.__onSelectGooglePinForEdit = function (id) {
                const idx = (calib.googlePoints || []).findIndex(p => p.id === id);
                if (idx === -1) return;
                const pt = calib.googlePoints[idx];
                editingPointId = pt.id;
                setPickHouseMode(false);

                inpGooglePinGPS.value = `${pt.latStr !== undefined ? pt.latStr : pt.lat}, ${pt.lngStr !== undefined ? pt.lngStr : pt.lng}`;
                btnCancelEditGooglePin.style.display = 'inline-block';

                if (idx === 0 || pt.type === 'entrance') {
                    // Pin 1: Entrance Road Anchor
                    if (rowDynamicBlockLot) rowDynamicBlockLot.style.display = 'none';
                    if (btnPickHouseOnMap) btnPickHouseOnMap.style.display = 'none';
                    if (rowPinLabel) rowPinLabel.style.display = 'block';
                    inpGooglePinLabel.value = pt.label || 'Pin 1: Entrance Road (Main Gate)';
                    lblGooglePinMode.textContent = '✏️ Editing Pin 1 (Entrance Road)';
                    lblGooglePinMode.style.color = '#10b981';
                    btnSubmitGooglePin.textContent = '💾 Update Entrance & Align';
                } else if (pt.type !== 'record') {
                    // Front-Road Alignment Pin (Pins 2..8)
                    if (rowDynamicBlockLot) rowDynamicBlockLot.style.display = 'grid';
                    if (btnPickHouseOnMap) btnPickHouseOnMap.style.display = 'inline-block';
                    if (rowPinLabel) rowPinLabel.style.display = 'block';
                    if (inpGooglePinBlock) inpGooglePinBlock.value = pt.block !== undefined ? pt.block : 11;
                    if (inpGooglePinLot) inpGooglePinLot.value = pt.lot !== undefined ? pt.lot : 24;
                    inpGooglePinLabel.value = pt.label || `Pin ${idx + 1}: B${pt.block} L${pt.lot} Front Road`;
                    lblGooglePinMode.textContent = `✏️ Editing Front-Road Pin ${idx + 1}`;
                    lblGooglePinMode.style.color = pt.color || '#38bdf8';
                    btnSubmitGooglePin.textContent = `💾 Update Pin ${idx + 1} & Align`;
                } else {
                    // Survey Record Pin
                    if (rowDynamicBlockLot) rowDynamicBlockLot.style.display = 'none';
                    if (btnPickHouseOnMap) btnPickHouseOnMap.style.display = 'none';
                    if (rowPinLabel) rowPinLabel.style.display = 'block';
                    inpGooglePinLabel.value = pt.label || `Record Pin ${idx + 1}`;
                    lblGooglePinMode.textContent = `✏️ Editing Record Pin ${idx + 1}`;
                    lblGooglePinMode.style.color = '#38bdf8';
                    btnSubmitGooglePin.textContent = '💾 Update Record Pin';
                }
                inpGooglePinGPS.focus();
            };

            // Render Google Maps Reference Pins List (All 8 Front-Road Alignment Pins)
            function renderGooglePointsListUI() {
                if (!googlePointsListEl) return;
                googlePointsListEl.innerHTML = '';
                const pts = Array.isArray(calib.googlePoints) ? calib.googlePoints : [];

                if (pts.length === 0) {
                    googlePointsListEl.innerHTML = `<div style="font-size:11px; color:#94a3b8; text-align:center; padding:6px;">No Google Maps pins. Click 🖱️ Map Click or 🔄 Default.</div>`;
                    return;
                }

                pts.forEach((pt, idx) => {
                    const isAlignmentPin = pt.type !== 'record';
                    const color = pt.color || PIN_PALETTE[idx % PIN_PALETTE.length];
                    const latStr = pt.latStr !== undefined ? pt.latStr : String(pt.lat);
                    const lngStr = pt.lngStr !== undefined ? pt.lngStr : String(pt.lng);

                    let titleText = pt.label || `Pin ${idx + 1}`;
                    let badgeText = isAlignmentPin ? `ROAD PIN ${idx + 1}` : `SURVEY`;
                    let badgeColor = isAlignmentPin ? color : '#64748b';
                    if (idx === 0 || pt.type === 'entrance') {
                        titleText = pt.label || `Pin 1: Entrance Road (Main Gate)`;
                        badgeText = 'ENTRANCE ROAD';
                        badgeColor = '#10b981';
                    }

                    const item = document.createElement('div');
                    item.style.cssText = `display:flex; align-items:center; justify-content:space-between; gap:6px; background:${isAlignmentPin ? '#0b1329' : '#0f172a'}; border:1px solid ${isAlignmentPin ? color + '88' : '#334155'}; border-radius:7px; padding:5px 7px;`;
                    item.innerHTML = `
                        <div style="min-width:0; flex:1;">
                            <div style="font-size:11px; font-weight:700; color:#f8fafc; display:flex; align-items:center; gap:5px;">
                                <span style="display:inline-block; width:9px; height:9px; border-radius:50%; background:${color}; flex-shrink:0;"></span>
                                <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${titleText}</span>
                                <span style="font-size:8.5px; background:${badgeColor}22; color:${badgeColor}; border:1px solid ${badgeColor}66; padding:1px 4px; border-radius:3px; margin-left:auto; flex-shrink:0;">${badgeText}</span>
                            </div>
                            <div style="font-size:10px; font-family:monospace; color:#cbd5e1; word-break:break-all; line-height:1.3; margin-top:2px;">
                                ${latStr}, ${lngStr}
                            </div>
                        </div>
                        <div style="display:flex; gap:4px; flex-shrink:0;">
                            <button type="button" class="gis-calib-btn" data-edit-pin="${pt.id}" style="padding:3px 6px; font-size:10px;" title="Edit coordinates or Block & Lot">✏️</button>
                            ${idx < 8 ? '' : `<button type="button" class="gis-calib-btn" data-del-pin="${pt.id}" style="padding:3px 6px; font-size:10px; color:#fca5a5;" title="Delete pin">🗑️</button>`}
                        </div>
                    `;
                    googlePointsListEl.appendChild(item);
                });

                // Wire Edit buttons
                googlePointsListEl.querySelectorAll('[data-edit-pin]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        window.__onSelectGooglePinForEdit(btn.getAttribute('data-edit-pin'));
                    });
                });

                // Wire Delete buttons
                googlePointsListEl.querySelectorAll('[data-del-pin]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const id = btn.getAttribute('data-del-pin');
                        pushUndoSnapshot();
                        calib.googlePoints = calib.googlePoints.filter(p => p.id !== id);
                        if (editingPointId === id) cancelGooglePinEditMode();
                        renderGooglePointsListUI();
                        renderCalibrationReferencePins();
                        localStorage.setItem('subdivision_gis_calibration_v9', JSON.stringify(calib));
                        showStatusMsg('🗑️ Pin deleted (Ctrl+Z to undo)', '#fbbf24', 3000);
                    });
                });
            }

            // High-Precision Alignment Calculator using ALL Front-Road Alignment Points (all 8 points!)
            function alignToFirst3Points(showFeedback = true) {
                if (!Array.isArray(calib.googlePoints) || calib.googlePoints.length < 2) {
                    if (showFeedback) showStatusMsg('⚠️ Need at least 2 Front-Road Alignment Points!', '#fbbf24', 3500);
                    return false;
                }
                const solved = solveSimilarityFromAlignmentPoints(
                    calib.googlePoints,
                    localHouseFrontRoad,
                    calib.flipX,
                    calib.flipY
                );
                if (!solved) {
                    if (showFeedback) showStatusMsg('⚠️ Could not calculate alignment from road points!', '#ef4444', 3500);
                    return false;
                }
                calib.anchorLat = solved.anchorLat;
                calib.anchorLng = solved.anchorLng;
                calib.rotationDeg = solved.rotationDeg;
                calib.scale = solved.scale;
                calib.scaleX = solved.scaleX;
                calib.scaleY = solved.scaleY;
                calib.useAffine = solved.useAffine;
                calib.affine = solved.affine;
                syncUIWithCalib(false);

                const activeCount = calib.googlePoints.filter(p => p && p.type !== 'record').length;
                if (showFeedback) {
                    showStatusMsg(`🎯 Aligned Subdivision to all ${activeCount} Front-Road Points!`, '#34d399', 3500);
                }
                return true;
            }

            // Add or Update Pin on Google Maps (Full IEEE-754 precision, no rounding!)
            btnSubmitGooglePin.addEventListener('click', () => {
                const rawGPS = (inpGooglePinGPS.value || '').trim();
                const parts = rawGPS.split(/[,\s]+/).filter(Boolean);
                if (parts.length < 2) {
                    showStatusMsg('⚠️ Enter exact coordinates as: lat, lng (or click 🖱️ Map Click)', '#fbbf24', 4000);
                    return;
                }
                const latStr = parts[0];
                const lngStr = parts[1];
                const lat = Number(latStr);
                const lng = Number(lngStr);

                if (!Number.isFinite(lat) || !Number.isFinite(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) {
                    showStatusMsg('⚠️ Invalid GPS coordinates! Please check latitude and longitude.', '#ef4444', 4000);
                    return;
                }

                pushUndoSnapshot();
                if (!Array.isArray(calib.googlePoints)) calib.googlePoints = [];

                if (editingPointId) {
                    const idx = calib.googlePoints.findIndex(p => p.id === editingPointId);
                    if (idx !== -1) {
                        const existing = calib.googlePoints[idx];
                        if (idx === 0 || existing.type === 'entrance') {
                            // Updating Entrance Road Anchor (Pin 1)
                            calib.googlePoints[idx] = Object.assign({}, existing, {
                                type: 'entrance',
                                label: inpGooglePinLabel.value.trim() || 'Pin 1: Entrance Road (Main Gate)',
                                lat,
                                lng,
                                latStr,
                                lngStr
                            });
                            alignToFirst3Points(false);
                            showStatusMsg('✅ Entrance Road Pin updated & Subdivision Aligned!', '#34d399', 3500);
                        } else if (existing.type !== 'record') {
                            // Updating Front-Road Alignment Pin (Pins 2..8)
                            const blockVal = parseInt(inpGooglePinBlock ? inpGooglePinBlock.value : '', 10);
                            const lotVal = parseInt(inpGooglePinLot ? inpGooglePinLot.value : '', 10);
                            const finalBlock = Number.isInteger(blockVal) && blockVal > 0 ? blockVal : (existing.block || 11);
                            const finalLot = Number.isInteger(lotVal) && lotVal > 0 ? lotVal : (existing.lot || 24);
                            const blockChanged = (finalBlock !== existing.block || finalLot !== existing.lot);

                            const roadLoc = localHouseFrontRoad[`B${finalBlock} L${finalLot}`];
                            if (!roadLoc) {
                                showStatusMsg(`⚠️ Warning: Block ${finalBlock} Lot ${finalLot} not found in subdivision!`, '#fbbf24', 4000);
                            }

                            const label = inpGooglePinLabel.value.trim() || `Pin ${idx + 1}: B${finalBlock} L${finalLot} Front Road`;
                            calib.googlePoints[idx] = Object.assign({}, existing, {
                                type: 'house',
                                block: finalBlock,
                                lot: finalLot,
                                customRoadPoint: blockChanged ? false : existing.customRoadPoint,
                                localLng: (!blockChanged && existing.customRoadPoint) ? existing.localLng : (roadLoc ? roadLoc.lng : existing.localLng),
                                localLat: (!blockChanged && existing.customRoadPoint) ? existing.localLat : (roadLoc ? roadLoc.lat : existing.localLat),
                                label: label,
                                lat,
                                lng,
                                latStr,
                                lngStr
                            });
                            alignToFirst3Points(false);
                            showStatusMsg(`✅ Pin ${idx + 1} updated & Subdivision Aligned to all 8 Front-Road Points!`, '#34d399', 4000);
                        } else {
                            // Updating Survey Record Pin
                            const label = inpGooglePinLabel.value.trim() || `Record Pin ${idx + 1}`;
                            calib.googlePoints[idx] = Object.assign({}, existing, {
                                type: 'record',
                                label,
                                lat,
                                lng,
                                latStr,
                                lngStr
                            });
                            showStatusMsg(`✅ Record Pin ${idx + 1} updated!`, '#38bdf8', 3500);
                        }
                    }
                } else {
                    // Adding a new pin
                    const label = inpGooglePinLabel.value.trim() || `Record Pin ${calib.googlePoints.length + 1}`;
                    const color = PIN_PALETTE[calib.googlePoints.length % PIN_PALETTE.length];
                    calib.googlePoints.push({
                        id: 'pin_' + Date.now() + '_' + Math.random().toString(36).slice(2, 6),
                        type: 'record',
                        label,
                        lat,
                        lng,
                        latStr,
                        lngStr,
                        color
                    });
                    showStatusMsg(`✅ Pin added!`, '#38bdf8', 3500);
                }

                cancelGooglePinEditMode();
                renderGooglePointsListUI();
                renderCalibrationReferencePins();
                renderAllGISLayers();
                localStorage.setItem('subdivision_gis_calibration_v9', JSON.stringify(calib));
            });

            function syncUIWithCalib(redrawRefPins = false) {
                calib.calibVersion = 9;
                if (calib.scaleX === undefined) calib.scaleX = calib.scale || 1.0;
                if (calib.scaleY === undefined) calib.scaleY = calib.scale || 1.0;

                rngRot.value = calib.rotationDeg;
                numRot.value = calib.rotationDeg;
                lblRot.textContent = `${Number(calib.rotationDeg).toFixed(4)}°`;

                rngScaleX.value = calib.scaleX;
                numScaleX.value = calib.scaleX;
                lblScaleX.textContent = `${Number(calib.scaleX).toFixed(5)}x`;

                rngScaleY.value = calib.scaleY;
                numScaleY.value = calib.scaleY;
                lblScaleY.textContent = `${Number(calib.scaleY).toFixed(5)}x`;

                rngOpacity.value = calib.opacity ?? 0.78;
                lblOpacity.textContent = `${Math.round((calib.opacity ?? 0.78) * 100)}%`;

                chkFlipX.checked = Boolean(calib.flipX);
                chkFlipY.checked = Boolean(calib.flipY);
                if (chkBgFill) chkBgFill.checked = calib.showBgFill !== false;
                lblAnchor.textContent = `${calib.anchorLat}, ${calib.anchorLng}`;

                localStorage.setItem('subdivision_gis_calibration_v9', JSON.stringify(calib));
                if (redrawRefPins) renderCalibrationReferencePins();
                renderAllGISLayers();
            }

            // Toggle Whole Subdivision Drag & Stretch Mode (while Google Maps reference pins stay fixed in place!)
            function setDragSubdivisionMode(enabled) {
                dragSubdivisionMode = Boolean(enabled);
                if (dragSubdivisionMode) {
                    if (clickMapForPinMode) setClickMapForPinMode(false);
                    if (pickHouseForPointMode) setPickHouseMode(false);
                    map.dragging.disable();
                    containerEl.style.cursor = 'move';
                    btnToggleDragSub.textContent = '✋ Drag & Stretch: ON';
                    btnToggleDragSub.classList.add('success');
                    showStatusMsg('✋ Drag handles to stretch Width/Height, or drag inside to move! (Ctrl+Z to undo)', '#38bdf8', 0);
                } else {
                    if (config.interactive) map.dragging.enable();
                    containerEl.style.cursor = '';
                    btnToggleDragSub.textContent = '✋ Drag & Stretch: OFF';
                    btnToggleDragSub.classList.remove('success');
                    statusEl.style.display = 'none';
                }
                renderAllGISLayers();
            }

            btnToggleDragSub.addEventListener('click', () => {
                setDragSubdivisionMode(!dragSubdivisionMode);
            });

            // Automatically Align Subdivision using Pins 1, 2, and 3 (Least-Squares Closed Form)
            panel.querySelector('#btnSnapToPins').addEventListener('click', () => {
                pushUndoSnapshot();
                alignToFirst3Points(true);
            });

            // Mouse & Touch Handlers for Whole Subdivision Dragging AND 8-Handle Width/Height Stretching
            let dragStartLatLng = null;
            let dragStartCalib = null;
            let activeResizeHandle = null;
            let dragRafPending = false;
            let dragMoved = false;

            function findHitResizeHandle(containerPt) {
                const handles = getCurrentResizeHandles();
                let best = null;
                let bestDist = 22; // 22px grab radius
                handles.forEach(h => {
                    const hPt = map.latLngToContainerPoint(h.latlng);
                    const dist = Math.hypot(hPt.x - containerPt.x, hPt.y - containerPt.y);
                    if (dist <= bestDist) {
                        bestDist = dist;
                        best = h;
                    }
                });
                return best;
            }

            function onSubDragStart(clientX, clientY, targetEl) {
                if (!dragSubdivisionMode) return false;
                if (panel.contains(targetEl)) return false;
                const rect = containerEl.getBoundingClientRect();
                const pt = L.point(clientX - rect.left, clientY - rect.top);
                const hitHandle = findHitResizeHandle(pt);

                activeResizeHandle = hitHandle ? hitHandle.id : null;
                dragStartLatLng = map.containerPointToLatLng(pt);
                dragStartCalib = JSON.parse(JSON.stringify(calib));
                dragStartCalib.box = getSubdivisionLocalBox();
                dragMoved = false;
                isDraggingSubdivision = true;
                return true;
            }

            function onSubDragMove(clientX, clientY) {
                if (!isDraggingSubdivision || !dragStartLatLng || !dragStartCalib) return;
                const rect = containerEl.getBoundingClientRect();
                const pt = L.point(clientX - rect.left, clientY - rect.top);
                const curLatLng = map.containerPointToLatLng(pt);

                if (!dragMoved) {
                    // Push initial pre-drag state onto Undo stack on first movement
                    undoStack.push(JSON.parse(JSON.stringify(dragStartCalib)));
                    if (undoStack.length > MAX_HISTORY) undoStack.shift();
                    redoStack.length = 0;
                    updateUndoRedoButtons();
                    dragMoved = true;
                }

                if (activeResizeHandle) {
                    // Stretch Width (scaleX) and/or Height (scaleY) while locking the opposite edge in place!
                    calib.useAffineMatrix = false;
                    const dE = (curLatLng.lng - dragStartCalib.anchorLng) * REF_COS_LAT;
                    const dN = curLatLng.lat - dragStartCalib.anchorLat;

                    const rad = (-dragStartCalib.rotationDeg * Math.PI) / 180;
                    const cosR = Math.cos(rad);
                    const sinR = Math.sin(rad);

                    // Mouse position in Subdivision's oriented Width (uX) and Height (uY) frame
                    const uX = dE * cosR + dN * sinR;
                    const uY = -dE * sinR + dN * cosR;

                    const box = dragStartCalib.box;
                    const localW = box.maxDx - box.minDx;
                    const localH = box.maxDy - box.minDy;
                    const sx0 = dragStartCalib.scaleX !== undefined ? Number(dragStartCalib.scaleX) : Number(dragStartCalib.scale);
                    const sy0 = dragStartCalib.scaleY !== undefined ? Number(dragStartCalib.scaleY) : Number(dragStartCalib.scale);

                    let newSX = sx0;
                    let newSY = sy0;
                    let shiftUX = 0;
                    let shiftUY = 0;

                    if (activeResizeHandle.includes('E')) {
                        const uLeft = box.minDx * sx0;
                        newSX = Math.max(0.20, Math.min(3.50, (uX - uLeft) / localW));
                        shiftUX = box.minDx * (sx0 - newSX); // Keep Left edge fixed
                    } else if (activeResizeHandle.includes('W')) {
                        const uRight = box.maxDx * sx0;
                        newSX = Math.max(0.20, Math.min(3.50, (uRight - uX) / localW));
                        shiftUX = box.maxDx * (sx0 - newSX); // Keep Right edge fixed
                    }

                    if (activeResizeHandle.includes('N')) {
                        const uBottom = box.minDy * sy0;
                        newSY = Math.max(0.20, Math.min(3.50, (uY - uBottom) / localH));
                        shiftUY = box.minDy * (sy0 - newSY); // Keep Bottom edge fixed
                    } else if (activeResizeHandle.includes('S')) {
                        const uTop = box.maxDy * sy0;
                        newSY = Math.max(0.20, Math.min(3.50, (uTop - uY) / localH));
                        shiftUY = box.maxDy * (sy0 - newSY); // Keep Top edge fixed
                    }

                    const shiftDE = shiftUX * cosR - shiftUY * sinR;
                    const shiftDN = shiftUX * sinR + shiftUY * cosR;

                    calib.scaleX = newSX;
                    calib.scaleY = newSY;
                    calib.scale = (newSX + newSY) / 2;
                    calib.anchorLat = dragStartCalib.anchorLat + shiftDN;
                    calib.anchorLng = dragStartCalib.anchorLng + shiftDE / REF_COS_LAT;

                    lblScaleX.textContent = `${newSX.toFixed(5)}x`;
                    lblScaleY.textContent = `${newSY.toFixed(5)}x`;
                    rngScaleX.value = newSX;
                    numScaleX.value = newSX;
                    rngScaleY.value = newSY;
                    numScaleY.value = newSY;
                    lblAnchor.textContent = `${calib.anchorLat}, ${calib.anchorLng}`;
                } else {
                    // Move whole subdivision overlay while GPS pins stay locked in place
                    const dLat = curLatLng.lat - dragStartLatLng.lat;
                    const dLng = curLatLng.lng - dragStartLatLng.lng;

                    calib.anchorLat = dragStartCalib.anchorLat + dLat;
                    calib.anchorLng = dragStartCalib.anchorLng + dLng;
                    if (calib.useAffineMatrix && calib.affineMatrix && dragStartCalib.affineMatrix) {
                        calib.affineMatrix.a0 = dragStartCalib.affineMatrix.a0 + dLat;
                        calib.affineMatrix.b0 = dragStartCalib.affineMatrix.b0 + dLng;
                    }

                    lblAnchor.textContent = `${calib.anchorLat}, ${calib.anchorLng}`;
                }

                if (!dragRafPending) {
                    dragRafPending = true;
                    requestAnimationFrame(() => {
                        dragRafPending = false;
                        // Notice we ONLY re-render the subdivision GIS layers; refMarkersGroup stays 100% fixed!
                        renderAllGISLayers();
                    });
                }
            }

            function onSubDragEnd() {
                if (!isDraggingSubdivision) return;
                isDraggingSubdivision = false;
                activeResizeHandle = null;
                dragStartLatLng = null;
                dragStartCalib = null;
                dragMoved = false;
                syncUIWithCalib(false);
            }

            containerEl.addEventListener('mousedown', (e) => {
                if (e.button !== 0) return;
                if (onSubDragStart(e.clientX, e.clientY, e.target)) {
                    e.preventDefault();
                }
            });
            window.addEventListener('mousemove', (e) => {
                if (isDraggingSubdivision) {
                    onSubDragMove(e.clientX, e.clientY);
                } else if (dragSubdivisionMode) {
                    const rect = containerEl.getBoundingClientRect();
                    const pt = L.point(e.clientX - rect.left, e.clientY - rect.top);
                    const hit = findHitResizeHandle(pt);
                    if (hit) {
                        containerEl.style.cursor = (hit.id === 'W' || hit.id === 'E') ? 'ew-resize'
                            : (hit.id === 'N' || hit.id === 'S') ? 'ns-resize' : 'nwse-resize';
                    } else {
                        containerEl.style.cursor = 'move';
                    }
                }
            });
            window.addEventListener('mouseup', () => {
                if (isDraggingSubdivision) {
                    onSubDragEnd();
                }
            });

            containerEl.addEventListener('touchstart', (e) => {
                if (e.touches && e.touches.length === 1) {
                    const t = e.touches[0];
                    if (onSubDragStart(t.clientX, t.clientY, e.target)) {
                        e.preventDefault();
                    }
                }
            }, { passive: false });
            window.addEventListener('touchmove', (e) => {
                if (isDraggingSubdivision && e.touches && e.touches.length === 1) {
                    const t = e.touches[0];
                    onSubDragMove(t.clientX, t.clientY);
                }
            }, { passive: false });
            window.addEventListener('touchend', () => {
                if (isDraggingSubdivision) {
                    onSubDragEnd();
                }
            });

            // Collapse/Expand
            panel.querySelector('#gisCalibToggle').addEventListener('click', () => {
                panel.classList.toggle('collapsed');
                const btn = panel.querySelector('#gisCalibCollapseBtn');
                btn.textContent = panel.classList.contains('collapsed') ? 'Expand' : 'Hide';
            });

            // Base Map buttons
            panel.querySelectorAll('[data-basemap]').forEach(btn => {
                btn.addEventListener('click', () => {
                    setBaseTileMode(btn.getAttribute('data-basemap'));
                    localStorage.setItem('subdivision_gis_calibration_v9', JSON.stringify(calib));
                });
            });

            // Rotation controls (with Undo snapshot on start of interaction)
            rngRot.addEventListener('mousedown', () => pushUndoSnapshot());
            rngRot.addEventListener('touchstart', () => pushUndoSnapshot(), { passive: true });
            rngRot.addEventListener('input', (e) => {
                calib.useAffine = false;
                calib.rotationDeg = parseFloat(e.target.value) || 0;
                syncUIWithCalib(false);
            });
            numRot.addEventListener('change', (e) => {
                pushUndoSnapshot();
                calib.useAffine = false;
                calib.rotationDeg = Number(e.target.value) || 0;
                syncUIWithCalib(false);
            });
            panel.querySelectorAll('[data-rot-step]').forEach(btn => {
                btn.addEventListener('click', () => {
                    pushUndoSnapshot();
                    calib.useAffine = false;
                    const step = parseFloat(btn.getAttribute('data-rot-step'));
                    calib.rotationDeg = calib.rotationDeg + step;
                    syncUIWithCalib(false);
                });
            });

            // Width (scaleX) controls
            rngScaleX.addEventListener('mousedown', () => pushUndoSnapshot());
            rngScaleX.addEventListener('touchstart', () => pushUndoSnapshot(), { passive: true });
            rngScaleX.addEventListener('input', (e) => {
                calib.useAffine = false;
                calib.scaleX = parseFloat(e.target.value) || 1.0;
                calib.scale = (calib.scaleX + (calib.scaleY || 1.0)) / 2;
                syncUIWithCalib(false);
            });
            numScaleX.addEventListener('change', (e) => {
                pushUndoSnapshot();
                calib.useAffine = false;
                calib.scaleX = Number(e.target.value) || 1.0;
                calib.scale = (calib.scaleX + (calib.scaleY || 1.0)) / 2;
                syncUIWithCalib(false);
            });
            panel.querySelectorAll('[data-scalex-step]').forEach(btn => {
                btn.addEventListener('click', () => {
                    pushUndoSnapshot();
                    calib.useAffine = false;
                    const step = parseFloat(btn.getAttribute('data-scalex-step'));
                    calib.scaleX = Math.max(0.2, (calib.scaleX || 1.0) + step);
                    calib.scale = (calib.scaleX + (calib.scaleY || 1.0)) / 2;
                    syncUIWithCalib(false);
                });
            });

            // Height (scaleY) controls
            rngScaleY.addEventListener('mousedown', () => pushUndoSnapshot());
            rngScaleY.addEventListener('touchstart', () => pushUndoSnapshot(), { passive: true });
            rngScaleY.addEventListener('input', (e) => {
                calib.useAffine = false;
                calib.scaleY = parseFloat(e.target.value) || 1.0;
                calib.scale = ((calib.scaleX || 1.0) + calib.scaleY) / 2;
                syncUIWithCalib(false);
            });
            numScaleY.addEventListener('change', (e) => {
                pushUndoSnapshot();
                calib.useAffine = false;
                calib.scaleY = Number(e.target.value) || 1.0;
                calib.scale = ((calib.scaleX || 1.0) + calib.scaleY) / 2;
                syncUIWithCalib(false);
            });
            panel.querySelectorAll('[data-scaley-step]').forEach(btn => {
                btn.addEventListener('click', () => {
                    pushUndoSnapshot();
                    calib.useAffine = false;
                    const step = parseFloat(btn.getAttribute('data-scaley-step'));
                    calib.scaleY = Math.max(0.2, (calib.scaleY || 1.0) + step);
                    calib.scale = ((calib.scaleX || 1.0) + calib.scaleY) / 2;
                    syncUIWithCalib(false);
                });
            });

            // Opacity controls
            rngOpacity.addEventListener('input', (e) => {
                calib.opacity = parseFloat(e.target.value) || 0.78;
                syncUIWithCalib(false);
            });

            // Flip & BG Fill checkboxes
            chkFlipX.addEventListener('change', (e) => {
                pushUndoSnapshot();
                calib.flipX = e.target.checked;
                syncUIWithCalib(false);
            });
            chkFlipY.addEventListener('change', (e) => {
                pushUndoSnapshot();
                calib.flipY = e.target.checked;
                syncUIWithCalib(false);
            });
            if (chkBgFill) {
                chkBgFill.addEventListener('change', (e) => {
                    calib.showBgFill = e.target.checked;
                    syncUIWithCalib(false);
                });
            }

            // Save Alignment to Server & LocalStorage (full precision, no rounding)
            panel.querySelector('#btnSaveCalib').addEventListener('click', () => {
                calib.calibVersion = 9;
                localStorage.setItem('subdivision_gis_calibration_v9', JSON.stringify(calib));
                fetch('/api/settings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ gis_calibration: calib })
                })
                .then(r => r.json())
                .then(() => {
                    showStatusMsg('✅ Full-precision alignment saved for all maps!', '#34d399', 3500);
                })
                .catch(() => {
                    showStatusMsg('✅ Saved to browser storage (full precision)!', '#34d399', 3500);
                });
            });

            // Copy JSON config (full precision)
            panel.querySelector('#btnCopyCalib').addEventListener('click', () => {
                const jsonStr = JSON.stringify(calib, null, 2);
                navigator.clipboard.writeText(jsonStr).then(() => {
                    showStatusMsg('📋 Full-precision JSON copied!', '#34d399', 3000);
                });
            });

            // Render initial Google Maps Reference Points list & Undo button state
            renderGooglePointsListUI();
            updateUndoRedoButtons();
        }
    }

    // Initial render
    renderCalibrationReferencePins();
    renderAllGISLayers();

    map.clearRouting = function () {
        if (houseLayer) houseLayer.resetStyle();
        routingLayerGroup.clearLayers();
        routeStartHouse = null;
        routeEndHouse = null;
    };

    return map;
}

function findClosestNode(targetLatLng, coordinatesMap, map) {
    let closestId = null;
    let minDist = Infinity;
    for (let [id, latlng] of coordinatesMap.entries()) {
        const dist = map.distance(targetLatLng, latlng);
        if (dist < minDist) {
            minDist = dist;
            closestId = id;
        }
    }
    return closestId;
}
