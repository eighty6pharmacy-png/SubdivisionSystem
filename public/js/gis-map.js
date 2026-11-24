// Common GIS Map Initialization Script with Real-World Georeferencing

// Inject CSS to remove the Leaflet interactive focus outline
const gisMapStyle = document.createElement('style');
gisMapStyle.innerHTML = `
    .leaflet-container {
        background-color: #e2e8f0 !important;
    }
    .leaflet-tile {
        transition: opacity 0.15s ease-in-out;
    }
    path.leaflet-interactive:focus {
        outline: none;
    }
`;
document.head.appendChild(gisMapStyle);

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
        lat: 13.545993723078043,
        lng: 123.25616571120268
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
        localLng: -0.001061776210629,
        localLat: -0.002363611841677,
        lat: 13.545993723078043,
        lng: 123.25616571120268,
        latStr: '13.545993723078043',
        lngStr: '123.25616571120268',
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
        fixedStartNode: null,
        onRouteSelected: null,
        startNode: null,
        endNode: null,
        adminView: false,
    }, options);

    // Load Raw GeoJSONs, Database Lots, and Saved Settings in parallel (revalidate cache on deploy)
    const [rawAmenities, rawBufferedRoads, rawHouses, rawNetwork, rawEntrance, rawHouseNodes, lotsData, appSettings] = await Promise.all([
        fetch('/gis-data/amenities.geojson', { cache: 'no-cache' }).then(r => r.json()).catch(() => ({ features: [] })),
        fetch('/gis-data/buffered_road_design.geojson', { cache: 'no-cache' }).then(r => r.json()).catch(() => ({ features: [] })),
        fetch('/gis-data/houses.geojson', { cache: 'no-cache' }).then(r => r.json()).catch(() => ({ features: [] })),
        fetch('/gis-data/roads.geojson', { cache: 'no-cache' }).then(r => r.json()).catch(() => ({ features: [] })),
        fetch('/gis-data/entrance_point.geojson', { cache: 'no-cache' }).then(r => r.json()).catch(() => ({})),
        fetch('/gis-data/house_nodes.geojson', { cache: 'no-cache' }).then(r => r.json()).catch(() => ({})),
        fetch('/api/lots', { cache: 'no-cache' }).then(r => r.ok ? r.json() : []).catch(() => []),
        fetch('/api/settings', { cache: 'no-cache' }).then(r => r.ok ? r.json() : {}).catch(() => ({}))
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

    window.globalDbLots = {};
    if (lotsData && Array.isArray(lotsData)) {
        lotsData.forEach(lot => {
            window.globalDbLots[`B${lot.block} L${lot.lot_number}`] = lot;
        });
    }

    // Ensure L.Renderer (SVG/Canvas) keeps leaflet-zoom-animated on Leaflet 1.9.4+ with leaflet-rotate
    // and does NOT re-project/reposition on every mid-gesture 'rotate' frame (prevents floating over basemap tiles)
    if (typeof L !== 'undefined' && L.Renderer && !L.Renderer.prototype.__althesaRotatePatched) {
        L.Renderer.prototype.__althesaRotatePatched = true;
        const origRendererOnAdd = L.Renderer.prototype.onAdd;
        L.Renderer.prototype.onAdd = function () {
            origRendererOnAdd.apply(this, arguments);
            if (this._map && this._map._rotate && this._zoomAnimated && this._container) {
                this._container.classList.add('leaflet-zoom-animated');
            }
        };
        const origGetEvents = L.Renderer.prototype.getEvents;
        L.Renderer.prototype.getEvents = function () {
            const events = origGetEvents.apply(this, arguments);
            if (events && events.rotate) {
                delete events.rotate;
                events.rotateend = this._update;
            }
            return events;
        };
    }

    // Initialize Leaflet Map at Real-World Subdivision Coordinates (North-Up bearing: 0 for true satellite alignment)
    const map = L.map(mapElementId, {
        preferCanvas: true,
        renderer: L.canvas({ padding: 0.6 }),
        zoomControl: config.interactive,
        dragging: config.interactive,
        scrollWheelZoom: config.interactive,
        doubleClickZoom: config.interactive,
        touchZoom: config.interactive ? 'center' : false,
        zoomSnap: 0,
        zoomDelta: 0.5,
        maxZoom: 22,
        rotate: true,
        touchRotate: false, // Disabled leaflet-rotate's hyper-sensitive TouchRotate; handled below with deadband
        shiftKeyRotate: true,
        bearing: config.bearing !== undefined ? config.bearing : 0,
        rotateControl: {
            closeOnZeroBearing: false,
            position: 'topleft'
        },
    }).setView([calib.anchorLat, calib.anchorLng], 18);

    // Clean 2-finger gesture arbiter: separates pinch-zoom from intentional twist-rotate (>10 deg)
    // and prevents TouchGestures / dragging from fighting setBearing()
    if (config.interactive) {
        let multiTouchActive = false;
        let multiTouchCooldownTimer = null;
        let gestureMode = null; // null | 'zoom' | 'rotate'
        let startDist = 0;
        let startAngle = 0;
        let startBearing = 0;
        let rotateRafId = null;
        let pendingBearing = null;
        const mapEl = map.getContainer();

        const getTouchMetrics = (t1, t2) => {
            const dx = t2.clientX - t1.clientX;
            const dy = t2.clientY - t1.clientY;
            return {
                dist: Math.hypot(dx, dy),
                angle: Math.atan2(dy, dx) * (180 / Math.PI)
            };
        };

        const normAngleDiff = (a, b) => ((a - b + 540) % 360) - 180;

        const onMapTouchStart = (e) => {
            if (e.touches && e.touches.length >= 2) {
                multiTouchActive = true;
                gestureMode = null;
                if (multiTouchCooldownTimer) {
                    clearTimeout(multiTouchCooldownTimer);
                    multiTouchCooldownTimer = null;
                }
                if (map.dragging && map.dragging.enabled()) {
                    map.dragging.disable();
                }
                if (typeof map._stop === 'function') {
                    map._stop();
                }
                const m = getTouchMetrics(e.touches[0], e.touches[1]);
                startDist = m.dist;
                startAngle = m.angle;
                startBearing = (typeof map.getBearing === 'function') ? map.getBearing() : 0;
            }
        };

        const onMapTouchMove = (e) => {
            if (!multiTouchActive || !e.touches || e.touches.length < 2) return;
            const m = getTouchMetrics(e.touches[0], e.touches[1]);
            const dDist = Math.abs(m.dist - startDist);
            const dAngle = normAngleDiff(m.angle, startAngle);
            const ROTATE_DEADBAND_DEG = 10;
            const ZOOM_THRESHOLD_PX = 45;

            if (!gestureMode) {
                if (dDist >= ZOOM_THRESHOLD_PX) {
                    gestureMode = 'zoom';
                } else if (Math.abs(dAngle) >= ROTATE_DEADBAND_DEG) {
                    gestureMode = 'rotate';
                    if (map.touchZoom) {
                        if (typeof map.touchZoom._onTouchEnd === 'function') {
                            map.touchZoom._onTouchEnd();
                        }
                        if (map.touchZoom.enabled()) {
                            map.touchZoom.disable();
                        }
                    }
                }
            }

            if (gestureMode === 'rotate' && typeof map.setBearing === 'function') {
                const effectiveAngle = dAngle - Math.sign(dAngle) * ROTATE_DEADBAND_DEG;
                pendingBearing = startBearing + effectiveAngle;
                if (!rotateRafId) {
                    rotateRafId = requestAnimationFrame(() => {
                        rotateRafId = null;
                        if (pendingBearing !== null && typeof map.setBearing === 'function') {
                            map.setBearing(pendingBearing);
                        }
                    });
                }
            }
        };

        const onMapTouchEnd = (e) => {
            if (multiTouchActive && (!e.touches || e.touches.length === 0)) {
                const wasRotating = (gestureMode === 'rotate');
                gestureMode = null;
                pendingBearing = null;
                if (rotateRafId) {
                    cancelAnimationFrame(rotateRafId);
                    rotateRafId = null;
                }
                if (wasRotating) {
                    map.fire('rotateend');
                }
                if (multiTouchCooldownTimer) clearTimeout(multiTouchCooldownTimer);
                multiTouchCooldownTimer = setTimeout(() => {
                    multiTouchActive = false;
                    if (config.interactive && map.touchZoom && !map.touchZoom.enabled()) {
                        map.touchZoom.enable();
                    }
                    if (config.interactive && map.dragging && !map.dragging.enabled()) {
                        map.dragging.enable();
                    }
                }, 120);
            }
        };

        mapEl.addEventListener('touchstart', onMapTouchStart, { passive: true, capture: true });
        mapEl.addEventListener('touchmove', onMapTouchMove, { passive: true, capture: true });
        mapEl.addEventListener('touchend', onMapTouchEnd, { passive: true, capture: true });
        mapEl.addEventListener('touchcancel', onMapTouchEnd, { passive: true, capture: true });
    }

    // Real-World Base Tile Layers (Always use crisp high-DPI retina tiles with scale=2)
    const googleStreetLayer = L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}&scale=2', {
        maxZoom: 22,
        maxNativeZoom: 20,
        tileSize: 256,
        zoomOffset: 0,
        detectRetina: false, // scale=2 already delivers 512px retina tiles
        keepBuffer: 8,       // Retain 8 tiles outside viewport in memory for instant panning
        updateWhenIdle: false, // Render tiles continuously while dragging
        updateWhenZooming: true,
        attribution: '&copy; Google Maps'
    });

    const googleHybridLayer = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}&scale=2', {
        maxZoom: 22,
        maxNativeZoom: 20,
        tileSize: 256,
        zoomOffset: 0,
        detectRetina: false,
        keepBuffer: 8,
        updateWhenIdle: false,
        updateWhenZooming: true,
        attribution: '&copy; Google Satellite'
    });

    const esriSatelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 22,
        maxNativeZoom: 19,
        keepBuffer: 8,
        updateWhenIdle: false,
        updateWhenZooming: true,
        attribution: '&copy; Esri World Imagery'
    });

    const osmStreetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 22,
        maxNativeZoom: 19,
        keepBuffer: 8,
        updateWhenIdle: false,
        updateWhenZooming: true,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors'
    });

    const cartoVoyagerLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        maxZoom: 22,
        maxNativeZoom: 19,
        subdomains: 'abcd',
        keepBuffer: 8,
        updateWhenIdle: false,
        updateWhenZooming: true,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions" target="_blank">CARTO</a>'
    });

    let currentBaseLayerMode = 'street';
    let activeTileLayer = null;
    function setBaseTileMode(mode) {
        currentBaseLayerMode = mode;
        calib.baseLayer = mode;
        if (activeTileLayer) {
            map.removeLayer(activeTileLayer);
            activeTileLayer = null;
        }
        if (mode === 'osm') {
            activeTileLayer = osmStreetLayer.addTo(map);
        } else if (mode === 'voyager') {
            activeTileLayer = cartoVoyagerLayer.addTo(map);
        } else if (mode === 'satellite') {
            activeTileLayer = googleHybridLayer.addTo(map);
        } else if (mode === 'esri') {
            activeTileLayer = esriSatelliteLayer.addTo(map);
        } else if (mode === 'street' || mode === 'google' || mode === 'google_street') {
            activeTileLayer = googleStreetLayer.addTo(map);
        } else if (mode === 'none') {
            activeTileLayer = null;
        } else {
            activeTileLayer = googleStreetLayer.addTo(map);
        }
    }

    // Expose toggle function globally for UI buttons
    window.toggleGISBaseLayer = function () {
        const nextMode = (currentBaseLayerMode === 'satellite') ? 'street' : 'satellite';
        setBaseTileMode(nextMode);
        const btn = document.getElementById('btnToggleMapMode');
        if (btn) {
            btn.innerHTML = (nextMode === 'satellite') ? '🗺️ Street Map' : '🛰️ Satellite';
        }
        return nextMode;
    };

    // Navigation and visitor views use clean OpenStreetMap street view by default!
    // Ensures outside highway routing (OSRM) and basemap are 100% synchronized on road centerlines.
    setBaseTileMode('street');

    const gisLayerGroup = L.layerGroup().addTo(map);
    const routingLayerGroup = L.layerGroup().addTo(map);
    const osrmLayerGroup = L.layerGroup().addTo(map);
    const liveNavGroup = L.layerGroup().addTo(map);

    let liveNavTracker = null;

    let routeStartHouse = null;
    let routeEndHouse = null;
    let houseLayer = null;
    let initialFitDone = false;

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

                let fillColor = '#e7e8eb';
                let color = '#94a3b8';
                let weight = 1;

                if (dbLot && config.adminView) {
                    if (dbLot.status === 'Occupied') { fillColor = '#ffffff'; color = '#000000'; }
                    else if (dbLot.status === 'Vacant House') { fillColor = '#3b82f6'; color = '#2563eb'; }
                    else if (dbLot.status === 'Vacant Lot') { fillColor = '#f59e0b'; color = '#d97706'; }
                    else if (dbLot.status === 'Reserved') { fillColor = '#8b5cf6'; color = '#7c3aed'; }
                    else if (dbLot.status === 'Under Construction') { fillColor = '#ef4444'; color = '#dc2626'; }
                }

                return {
                    color: color,
                    weight: weight,
                    fillColor: fillColor,
                    fillOpacity: 1.0
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
                color: '#64748b',
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

        let fixedStartLayer = null;

        houseLayer = L.geoJSON(houses, {
            style: styles.house,
            onEachFeature: (feature, layer) => {
                if (feature.properties.block_num && feature.properties.lot_number) {
                    const b = feature.properties.block_num;
                    const l = feature.properties.lot_number;
                    const houseId = `B${b} L${l}`;
                    const isFixedStart = config.fixedStartNode && houseId === config.fixedStartNode;

                    layer.bindTooltip(isFixedStart ? `Your House (Block ${b}, Lot ${l})` : `Block ${b}, Lot ${l}`);

                    if (isFixedStart) {
                        fixedStartLayer = layer;
                        let startCoord = getHouseNodeCoord(b, l);
                        if (!startCoord) startCoord = layer.getBounds().getCenter();
                        routeStartHouse = { id: houseId, coord: startCoord, block: b, lot: l };
                        layer.setStyle({ fillColor: '#10b981', color: '#059669', weight: 2.5, fillOpacity: 0.95 });
                    } else if (config.endNode && houseId === config.endNode) {
                        layer.setStyle({ fillColor: '#2563eb', color: '#1d4ed8', weight: 2.5, fillOpacity: 0.95 });
                    }

                    layer.on('click', () => {
                        if (config.interactiveRouting) {
                            let coord = getHouseNodeCoord(b, l);
                            if (!coord) coord = layer.getBounds().getCenter();

                            if (config.fixedStartNode && routeStartHouse) {
                                if (houseId === routeStartHouse.id) {
                                    return;
                                }
                                houseLayer.resetStyle();
                                if (fixedStartLayer) {
                                    fixedStartLayer.setStyle({ fillColor: '#10b981', color: '#059669', weight: 2.5, fillOpacity: 0.95 });
                                }
                                routeEndHouse = { id: houseId, coord: coord, block: b, lot: l };
                                layer.setStyle({ fillColor: '#2563eb', color: '#1d4ed8', weight: 2.5, fillOpacity: 0.95 });
                                buildAndDrawRouting(network, entrance, houseNodes, styles);
                                if (typeof config.onRouteSelected === 'function') {
                                    config.onRouteSelected({ start: routeStartHouse, end: routeEndHouse });
                                }
                                return;
                            }

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

        if (config.showRouting && network) {
            buildAndDrawRouting(network, entrance, houseNodes, styles);
        }

        if (config.fixedStartNode && routeStartHouse && !routeEndHouse) {
            L.circleMarker([routeStartHouse.coord.lat, routeStartHouse.coord.lng], {
                radius: 7, fillColor: '#10b981', color: '#fff', weight: 2.5, fillOpacity: 1
            }).addTo(routingLayerGroup).bindTooltip(`Your House (${routeStartHouse.id})`);
        }

        map._fixedStartLayer = fixedStartLayer;
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

        function coordToKey(p) {
            return Number(p[0]).toFixed(8) + ',' + Number(p[1]).toFixed(8);
        }

        L.geoJSON(streetOnlyNetwork, {
            onEachFeature: (feature) => {
                const addCoordsToGraph = (coords) => {
                    if (coords.length === 0) return;
                    const firstId = coordToKey(coords[0]);
                    const lastId = coordToKey(coords[coords.length - 1]);
                    lineEndpoints.push(firstId);
                    lineEndpoints.push(lastId);

                    for (let i = 0; i < coords.length - 1; i++) {
                        const p1 = coords[i];
                        const p2 = coords[i + 1];
                        const id1 = coordToKey(p1);
                        const id2 = coordToKey(p2);

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

        // Snap street endpoints together at intersections and unify their junction points
        // so turn routes stay 100% on street centerlines without diagonal shortcut chords across curbs
        const SNAP_TOLERANCE = 3.5; // meters
        lineEndpoints.forEach(endId => {
            if (!coordinatesMap.has(endId)) return;
            const pt1 = coordinatesMap.get(endId);
            lineEndpoints.forEach(id2 => {
                if (endId !== id2 && coordinatesMap.has(id2)) {
                    const pt2 = coordinatesMap.get(id2);
                    const d = map.distance(pt1, pt2);
                    if (d < SNAP_TOLERANCE) {
                        const midPt = [(pt1[0] + pt2[0]) / 2, (pt1[1] + pt2[1]) / 2];
                        coordinatesMap.set(endId, midPt);
                        coordinatesMap.set(id2, midPt);

                        let exists = false;
                        if (graph.nodes.has(endId)) {
                            for (let e of graph.nodes.get(endId).edges) {
                                if (e.node === id2) { exists = true; break; }
                            }
                        }
                        if (!exists) graph.addEdge(endId, id2, 0.01);
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
                    dist: minDist,
                    segId1: closestSegment.id1,
                    segId2: closestSegment.id2
                };
            }
            return null;
        }

        function connectSameSegmentSnaps(snapA, snapB, targetGraph) {
            if (!snapA || !snapB || !targetGraph) return;
            if (snapA.segId1 === snapB.segId1 && snapA.segId2 === snapB.segId2) {
                targetGraph.addEdge(snapA.nodeId, snapB.nodeId, map.distance(snapA.point, snapB.point));
            }
        }

        function computeSubdivisionHull() {
            const pts = [];
            segments.forEach(s => {
                pts.push(s.p1, s.p2);
            });
            if (houseLayer) {
                houseLayer.eachLayer(layer => {
                    if (layer.getBounds) {
                        const b = layer.getBounds();
                        pts.push([b.getSouth(), b.getWest()], [b.getSouth(), b.getEast()], [b.getNorth(), b.getEast()], [b.getNorth(), b.getWest()]);
                    }
                });
            }
            if (pts.length < 3) return [];
            const sorted = pts.slice().sort((a, b) => (a[0] === b[0] ? a[1] - b[1] : a[0] - b[0]));
            const cross = (o, a, b) => (a[0] - o[0]) * (b[1] - o[1]) - (a[1] - o[1]) * (b[0] - o[0]);
            const lower = [];
            for (let i = 0; i < sorted.length; i++) {
                const p = sorted[i];
                while (lower.length >= 2 && cross(lower[lower.length - 2], lower[lower.length - 1], p) <= 0) lower.pop();
                lower.push(p);
            }
            const upper = [];
            for (let i = sorted.length - 1; i >= 0; i--) {
                const p = sorted[i];
                while (upper.length >= 2 && cross(upper[upper.length - 2], upper[upper.length - 1], p) <= 0) upper.pop();
                upper.push(p);
            }
            lower.pop();
            upper.pop();
            return lower.concat(upper);
        }

        function injectSnappedNode(lat, lng, prefix, targetGraph) {
            const snap = snapPointToRoad(lat, lng, prefix, targetGraph);
            return snap ? snap.nodeId : null;
        }

        // Google Maps-style dotted grey walking connector (off-road to/from street)
        function drawGoogleDottedConnector(fromPt, toPt, targetGroup) {
            if (!fromPt || !toPt) return;
            L.polyline([fromPt, toPt], {
                color: '#5f6368',
                weight: 4,
                opacity: 0.9,
                dashArray: '1, 8',
                lineCap: 'round',
                interactive: false,
                className: 'gmaps-offroad-dots'
            }).addTo(targetGroup);

            L.circleMarker(toPt, {
                radius: 4,
                fillColor: '#9aa0a6',
                color: '#ffffff',
                weight: 2,
                fillOpacity: 1,
                interactive: false
            }).addTo(targetGroup);
        }

        // Google Maps-style red teardrop location pin on the destination house
        function createGoogleDestPinMarker(latlng, tooltipText) {
            const pinIcon = L.divIcon({
                className: 'gmaps-dest-pin-icon',
                html: `<svg width="32" height="40" viewBox="0 0 32 40" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter:drop-shadow(0 2px 4px rgba(0,0,0,0.35));display:block;">
                    <path d="M16 1C8.268 1 2 7.268 2 15c0 9.75 14 23.5 14 23.5S30 24.75 30 15C30 7.268 23.732 1 16 1z" fill="#EA4335" stroke="#B31412" stroke-width="1.5"/>
                    <circle cx="16" cy="15" r="5.5" fill="#7A0C06" fill-opacity="0.22"/>
                    <circle cx="16" cy="15" r="4.5" fill="#FFFFFF"/>
                </svg>`,
                iconSize: [32, 40],
                iconAnchor: [16, 39],
                tooltipAnchor: [0, -36]
            });
            const m = L.marker(latlng, { icon: pinIcon, zIndexOffset: 900 }).addTo(routingLayerGroup);
            if (tooltipText) m.bindTooltip(tooltipText, { direction: 'top' });
            return m;
        }

        // Smooths intersection turns with a natural vehicle turning radius (prevents sharp needle/wiggle artifacts)
        function smoothTurnRoute(rawCoords, maxRadius = 3.5) {
            if (!rawCoords || rawCoords.length < 3) return rawCoords || [];
            
            // 1. Deduplicate consecutive identical / near-identical vertices (< 0.4m)
            const coords = [rawCoords[0]];
            for (let i = 1; i < rawCoords.length; i++) {
                const d = map.distance(coords[coords.length - 1], rawCoords[i]);
                if (d >= 0.4 || i === rawCoords.length - 1) {
                    coords.push(rawCoords[i]);
                }
            }
            if (coords.length < 3) return coords;

            const result = [coords[0]];
            for (let i = 1; i < coords.length - 1; i++) {
                const pPrev = coords[i - 1];
                const pCurr = coords[i];
                const pNext = coords[i + 1];
                
                const d1 = map.distance(pPrev, pCurr);
                const d2 = map.distance(pCurr, pNext);
                
                const v1 = [pCurr[0] - pPrev[0], pCurr[1] - pPrev[1]];
                const v2 = [pNext[0] - pCurr[0], pNext[1] - pCurr[1]];
                const dot = v1[0] * v2[0] + v1[1] * v2[1];
                const mag1 = Math.hypot(v1[0], v1[1]);
                const mag2 = Math.hypot(v2[0], v2[1]);
                if (mag1 < 1e-7 || mag2 < 1e-7) continue;
                
                const cosAngle = Math.max(-1, Math.min(1, dot / (mag1 * mag2)));
                const angleDeg = Math.acos(cosAngle) * (180 / Math.PI);
                
                // Smooth turns where turn angle exceeds 22 degrees
                if (angleDeg > 22 && d1 > 2.0 && d2 > 2.0) {
                    const r = Math.min(maxRadius, d1 * 0.42, d2 * 0.42);
                    const t1 = 1 - (r / d1);
                    const t2 = r / d2;
                    
                    const q1 = [pPrev[0] + t1 * v1[0], pPrev[1] + t1 * v1[1]];
                    const q2 = [pCurr[0] + t2 * v2[0], pCurr[1] + t2 * v2[1]];
                    
                    const mid = [
                        0.25 * q1[0] + 0.5 * pCurr[0] + 0.25 * q2[0],
                        0.25 * q1[1] + 0.5 * pCurr[1] + 0.25 * q2[1]
                    ];
                    
                    result.push(q1, mid, q2);
                } else {
                    result.push(pCurr);
                }
            }
            result.push(coords[coords.length - 1]);
            return result;
        }

        // Live Moving GPS & OSRM Outside Highway Hybrid Controller
        function startLiveHybridNavigation(opts) {
            if (liveNavTracker) {
                liveNavTracker.destroy();
                liveNavTracker = null;
            }

            const entranceCoord = opts.entranceCoord;
            const destCoord = opts.destCoord;
            const destFrontRoadCoord = opts.destFrontRoadCoord;
            const destHouseRing = Array.isArray(opts.destHouseRing) ? opts.destHouseRing : null;
            const destHouseLayer = opts.destHouseLayer || null;
            const baseGraph = opts.graph;
            const coordinatesMap = opts.coordinatesMap;
            const segments = opts.segments;
            const injectSnappedNode = opts.injectSnappedNode;
            const staticPathIds = opts.staticPathIds || [];
            const staticAltPathIds = opts.staticAltPathIds || [];
            const styles = opts.styles;
            const destinationName = opts.destinationName || 'Destination';
            const subdivisionHull = opts.subdivisionHull || [];
            let preferAlternative = false;

            osrmLayerGroup.clearLayers();
            liveNavGroup.clearLayers();
            routingLayerGroup.clearLayers();

            // Dedicated layer for the focused House + Front-of-House arrival zone (persists across route redraws)
            const destZoneGroup = L.layerGroup().addTo(liveNavGroup);

            function pointInLocalPoly(pt, poly) {
                if (!poly || poly.length < 3) return false;
                let inside = false;
                for (let i = 0, j = poly.length - 1; i < poly.length; j = i++) {
                    const xi = poly[i].x, yi = poly[i].y;
                    const xj = poly[j].x, yj = poly[j].y;
                    if (((yi > pt.y) !== (yj > pt.y)) && (pt.x < (xj - xi) * (pt.y - yi) / (yj - yi) + xi)) {
                        inside = !inside;
                    }
                }
                return inside;
            }

            function distPointToLocalSeg(pt, a, b) {
                const dx = b.x - a.x;
                const dy = b.y - a.y;
                const l2 = dx * dx + dy * dy;
                if (l2 === 0) return Math.hypot(pt.x - a.x, pt.y - a.y);
                let t = ((pt.x - a.x) * dx + (pt.y - a.y) * dy) / l2;
                t = Math.max(0, Math.min(1, t));
                return Math.hypot(pt.x - (a.x + t * dx), pt.y - (a.y + t * dy));
            }

            function distToLocalPoly(pt, poly) {
                if (!poly || poly.length < 3) return Infinity;
                if (pointInLocalPoly(pt, poly)) return 0;
                let minD = Infinity;
                for (let i = 0, j = poly.length - 1; i < poly.length; j = i++) {
                    const d = distPointToLocalSeg(pt, poly[j], poly[i]);
                    if (d < minD) minD = d;
                }
                return minD;
            }

            function nearestPointOnLocalPoly(pt, poly) {
                if (!poly || poly.length < 3) return { x: 0, y: 0 };
                let bestPt = { x: 0, y: 0 };
                let minD = Infinity;
                for (let i = 0, j = poly.length - 1; i < poly.length; j = i++) {
                    const a = poly[j];
                    const b = poly[i];
                    const dx = b.x - a.x;
                    const dy = b.y - a.y;
                    const l2 = dx * dx + dy * dy;
                    let t = l2 === 0 ? 0 : ((pt.x - a.x) * dx + (pt.y - a.y) * dy) / l2;
                    t = Math.max(0, Math.min(1, t));
                    const nx = a.x + t * dx;
                    const ny = a.y + t * dy;
                    const d = Math.hypot(pt.x - nx, pt.y - ny);
                    if (d < minD) {
                        minD = d;
                        bestPt = { x: nx, y: ny };
                    }
                }
                return bestPt;
            }

            // Build a focused House + Front-of-House arrival zone aligned to the lot's own frontage
            function buildDestinationArrivalZone(centerCoord, frontRoadCoord, houseRingLatLngs) {
                const M_PER_DEG_LAT = 111320;
                const M_PER_DEG_LNG = 111320 * Math.cos((centerCoord.lat * Math.PI) / 180);
                const toLocalM = (lat, lng) => ({
                    x: (lng - centerCoord.lng) * M_PER_DEG_LNG,
                    y: (lat - centerCoord.lat) * M_PER_DEG_LAT
                });
                const fromLocalM = (x, y) => [
                    centerCoord.lat + (y / M_PER_DEG_LAT),
                    centerCoord.lng + (x / M_PER_DEG_LNG)
                ];

                let housePolyM = [];
                if (Array.isArray(houseRingLatLngs) && houseRingLatLngs.length >= 3) {
                    housePolyM = houseRingLatLngs
                        .filter(pt => Array.isArray(pt) && Number.isFinite(pt[0]) && Number.isFinite(pt[1]))
                        .map(pt => toLocalM(pt[0], pt[1]));
                    if (housePolyM.length > 3) {
                        const first = housePolyM[0];
                        const last = housePolyM[housePolyM.length - 1];
                        if (Math.hypot(first.x - last.x, first.y - last.y) < 0.05) {
                            housePolyM.pop();
                        }
                    }
                }

                // Resolve front-road vector pointing from house center straight toward its street frontage
                let roadTarget = frontRoadCoord;
                if (!roadTarget && segments && segments.length > 0) {
                    const pt = [centerCoord.lat, centerCoord.lng];
                    let bestDist = Infinity;
                    let bestProj = null;
                    for (let i = 0; i < segments.length; i++) {
                        const res = projectPointToSegment(pt, segments[i].p1, segments[i].p2);
                        if (res.dist < bestDist) {
                            bestDist = res.dist;
                            bestProj = res.point;
                        }
                    }
                    if (bestProj) {
                        roadTarget = { lat: bestProj[0], lng: bestProj[1] };
                    }
                }

                let frontPadPolyM = [];
                let frontPadLatLngs = [];

                if (roadTarget && Number.isFinite(roadTarget.lat) && Number.isFinite(roadTarget.lng)) {
                    const roadM = toLocalM(roadTarget.lat, roadTarget.lng);
                    const roadDist = Math.hypot(roadM.x, roadM.y);

                    if (roadDist > 0.5) {
                        const fwd = { x: roadM.x / roadDist, y: roadM.y / roadDist };
                        const right = { x: fwd.y, y: -fwd.x };
                        const mkLocalPt = (u, w) => ({
                            x: u * fwd.x + w * right.x,
                            y: u * fwd.y + w * right.y
                        });

                        let padWMin = -3.5;
                        let padWMax = 3.5;
                        let padBackU = 1.5;
                        let padFrontU = Math.min(Math.max(roadDist + 0.8, 4.5), 8.0);

                        if (housePolyM.length >= 3) {
                            const proj = housePolyM.map(v => ({
                                u: v.x * fwd.x + v.y * fwd.y,
                                w: v.x * right.x + v.y * right.y
                            }));
                            const maxU = Math.max(...proj.map(p => p.u));
                            const allWMin = Math.min(...proj.map(p => p.w));
                            const allWMax = Math.max(...proj.map(p => p.w));
                            const frontVerts = proj.filter(p => p.u >= maxU - 1.5);

                            if (frontVerts.length >= 2) {
                                padWMin = Math.min(...frontVerts.map(p => p.w));
                                padWMax = Math.max(...frontVerts.map(p => p.w));
                                if (padWMax - padWMin < 3.0) {
                                    padWMin = allWMin;
                                    padWMax = allWMax;
                                }
                            } else {
                                padWMin = allWMin;
                                padWMax = allWMax;
                            }

                            // Inset slightly from lot side edges so the frontage pad stays strictly in front of THIS lot
                            const padWidth = padWMax - padWMin;
                            if (padWidth > 3.5) {
                                const inset = Math.min(0.35, (padWidth - 3.0) / 2);
                                padWMin += inset;
                                padWMax -= inset;
                            }

                            padBackU = Math.max(0, maxU - 0.35);
                            padFrontU = Math.min(Math.max(roadDist + 0.8, maxU + 2.8), maxU + 5.5);
                        }

                        frontPadPolyM = [
                            mkLocalPt(padBackU, padWMin),
                            mkLocalPt(padBackU, padWMax),
                            mkLocalPt(padFrontU, padWMax),
                            mkLocalPt(padFrontU, padWMin)
                        ];
                        frontPadLatLngs = frontPadPolyM.map(p => fromLocalM(p.x, p.y));
                    }
                }

                // Fallback compact box only if neither house polygon nor road frontage could be derived
                if (housePolyM.length < 3 && frontPadPolyM.length < 3) {
                    const half = 4.0;
                    frontPadPolyM = [
                        { x: -half, y: -half },
                        { x: half, y: -half },
                        { x: half, y: half },
                        { x: -half, y: half }
                    ];
                    frontPadLatLngs = frontPadPolyM.map(p => fromLocalM(p.x, p.y));
                }

                const houseLatLngs = housePolyM.map(p => fromLocalM(p.x, p.y));

                return {
                    toLocalM,
                    housePolyM,
                    frontPadPolyM,
                    houseLatLngs,
                    frontPadLatLngs,
                    getDistanceToZone: (lat, lng) => {
                        const ptM = toLocalM(lat, lng);
                        const dHouse = housePolyM.length >= 3 ? distToLocalPoly(ptM, housePolyM) : Infinity;
                        const dFront = frontPadPolyM.length >= 3 ? distToLocalPoly(ptM, frontPadPolyM) : Infinity;
                        const minD = Math.min(dHouse, dFront);
                        return Number.isFinite(minD) ? minD : map.distance([lat, lng], [centerCoord.lat, centerCoord.lng]);
                    },
                    getDistanceToHouse: (lat, lng) => {
                        const ptM = toLocalM(lat, lng);
                        const dHouse = housePolyM.length >= 3 ? distToLocalPoly(ptM, housePolyM) : Infinity;
                        return Number.isFinite(dHouse) ? dHouse : map.distance([lat, lng], [centerCoord.lat, centerCoord.lng]);
                    },
                    clampToHouse: (lat, lng) => {
                        if (housePolyM.length < 3) {
                            return [centerCoord.lat, centerCoord.lng];
                        }
                        const ptM = toLocalM(lat, lng);
                        if (pointInLocalPoly(ptM, housePolyM)) {
                            return [lat, lng];
                        }
                        const edgePt = nearestPointOnLocalPoly(ptM, housePolyM);
                        // Nudge slightly inward toward house centroid (0,0) so the marker stays cleanly inside the house
                        const lenToCenter = Math.hypot(edgePt.x, edgePt.y);
                        if (lenToCenter > 0.4) {
                            const nudge = Math.min(0.4, lenToCenter * 0.25);
                            const scale = (lenToCenter - nudge) / lenToCenter;
                            return fromLocalM(edgePt.x * scale, edgePt.y * scale);
                        }
                        return [centerCoord.lat, centerCoord.lng];
                    }
                };
            }

            const destArrivalZone = buildDestinationArrivalZone(destCoord, destFrontRoadCoord, destHouseRing);
            let lastRenderedZoneArrived = null;

            function renderDestinationZone(arrivedState) {
                if (lastRenderedZoneArrived === arrivedState) return;
                lastRenderedZoneArrived = arrivedState;

                destZoneGroup.clearLayers();

                // Highlight the destination house polygon itself (blue while approaching, emerald green on arrival)
                if (destHouseLayer && typeof destHouseLayer.setStyle === 'function') {
                    if (arrivedState) {
                        destHouseLayer.setStyle({
                            fillColor: '#22c55e',
                            color: '#15803d',
                            weight: 3,
                            fillOpacity: 0.72
                        });
                    } else {
                        destHouseLayer.setStyle({
                            fillColor: '#2563eb',
                            color: '#1d4ed8',
                            weight: 2.5,
                            fillOpacity: 0.95
                        });
                    }
                } else if (destArrivalZone.houseLatLngs.length >= 3) {
                    L.polygon(destArrivalZone.houseLatLngs, {
                        color: arrivedState ? '#15803d' : '#1d4ed8',
                        weight: arrivedState ? 3 : 2.5,
                        fillColor: arrivedState ? '#22c55e' : '#2563eb',
                        fillOpacity: arrivedState ? 0.65 : 0.45,
                        interactive: false
                    }).addTo(destZoneGroup);
                }

                // Render the compact Front-of-House arrival pad directly in front of the house (replaces giant circle radar)
                if (destArrivalZone.frontPadLatLngs.length >= 3) {
                    L.polygon(destArrivalZone.frontPadLatLngs, arrivedState ? {
                        color: '#16a34a',
                        weight: 2.5,
                        fillColor: '#86efac',
                        fillOpacity: 0.45,
                        interactive: false
                    } : {
                        color: '#2563eb',
                        weight: 1.8,
                        dashArray: '5, 5',
                        fillColor: '#93c5fd',
                        fillOpacity: 0.24,
                        interactive: false
                    }).addTo(destZoneGroup);
                }
            }

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
                            <button type="button" id="hudAltRouteBtn" class="hud-alt-route-btn" style="display:none;" title="Switch to alternative route">
                                ⇄ Alt Route
                            </button>
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
            const altRouteBtn = document.getElementById('hudAltRouteBtn');

            function fmtDist(m) {
                if (m == null || isNaN(m)) return '--';
                if (m >= 1000) return (m / 1000).toFixed(1) + ' km';
                return Math.round(m) + ' m';
            }

            function fmtTime(s) {
                if (s == null || isNaN(s)) return '--';
                if (s <= 0) return '0 min';
                if (s < 60) return '1 min';
                if (s < 3600) return Math.round(s / 60) + ' min';
                const h = Math.floor(s / 3600);
                const m = Math.round((s % 3600) / 60);
                return m > 0 ? `${h} hr ${m} min` : `${h} hr`;
            }

            // Google Maps-style realistic ETA estimator (accounts for urban traffic, intersections, & subdivision speed)
            function estimateGoogleMapsDuration(osrmDurationSec, osrmDistanceM, internalDistM, walkDistM = 0, stepCount = 0) {
                let hwyTime = 0;
                if (osrmDistanceM > 0) {
                    const rawOsrm = Number.isFinite(osrmDurationSec) && osrmDurationSec > 0
                        ? osrmDurationSec
                        : (osrmDistanceM / 8.33);
                    // OSRM free-flow is ~30-35% faster than real-world urban/suburban traffic + turn delays
                    const turnPenalty = Math.min(stepCount, 12) * 8;
                    const urbanFloor = osrmDistanceM / 10.5; // ~38 km/h realistic suburban cap
                    hwyTime = Math.max(rawOsrm * 1.35 + turnPenalty, urbanFloor);
                }
                const internalTime = internalDistM > 0 ? (internalDistM / 3.0) : 0; // ~10.8 km/h inside residential streets
                const walkTime = walkDistM > 0 ? (walkDistM / 1.35) : 0;
                return hwyTime + internalTime + walkTime;
            }

            let liveGpsMarker = null;
            let accuracyCircle = null;
            let userPos = null;
            let autoFollow = true;
            let initialFitDone = false;
            let isNavStarted = false;
            let isHeadingUp = false;
            let currentNavSteps = [];
            let activeStepIdx = 0;
            let lastTotalDist = 0;
            let lastTotalTime = 0;

            function getCardinalDir(b) {
                const dirs = ['north', 'northeast', 'east', 'southeast', 'south', 'southwest', 'west', 'northwest'];
                return dirs[Math.round(((b % 360) + 360) % 360 / 45) % 8];
            }

            function getManeuverIcon(type, modifier) {
                if (type === 'arrive') return '🏁';
                if (type === 'roundabout' || type === 'rotary') return '🔄';
                if (modifier === 'uturn') return '⤵️';
                if (modifier === 'sharp right' || modifier === 'right') return '↱';
                if (modifier === 'slight right') return '↗️';
                if (modifier === 'sharp left' || modifier === 'left') return '↰';
                if (modifier === 'slight left') return '↖️';
                return '⬆️';
            }

            function parseOSRMSteps(routeObj) {
                if (!routeObj || !Array.isArray(routeObj.legs) || !routeObj.legs[0] || !Array.isArray(routeObj.legs[0].steps)) {
                    return [];
                }
                const out = [];
                routeObj.legs[0].steps.forEach((s) => {
                    const m = s.maneuver || {};
                    const type = m.type || 'continue';
                    const mod = m.modifier || '';
                    const road = (s.name && s.name.trim()) ? s.name.trim() : '';
                    const loc = Array.isArray(m.location) ? [m.location[1], m.location[0]] : null;
                    const icon = getManeuverIcon(type, mod);
                    let inst = '';

                    if (type === 'depart') {
                        const dir = m.bearing_after !== undefined ? getCardinalDir(m.bearing_after) : '';
                        inst = road ? `Head ${dir || 'straight'} on ${road}` : `Head ${dir || 'towards main road'}`;
                    } else if (type === 'arrive') {
                        inst = 'Reach Althesa Entrance Gate';
                    } else if (type === 'roundabout' || type === 'rotary') {
                        const exitN = m.exit ? `take exit ${m.exit}` : 'continue';
                        inst = road ? `At roundabout, ${exitN} onto ${road}` : `At roundabout, ${exitN}`;
                    } else if (type === 'fork') {
                        inst = `Keep ${mod || 'straight'}${road ? ' onto ' + road : ''}`;
                    } else if (type === 'merge') {
                        inst = `Merge ${mod || ''}${road ? ' onto ' + road : ''}`.trim();
                    } else if (mod) {
                        const modCap = mod.charAt(0).toUpperCase() + mod.slice(1);
                        const verb = (mod === 'straight') ? 'Continue straight' : (mod === 'uturn' ? 'Make a U-turn' : `Turn ${mod}`);
                        inst = road ? `${verb} onto ${road}` : `${modCap}`;
                    } else {
                        inst = road ? `Continue onto ${road}` : 'Continue straight';
                    }

                    if (s.distance >= 2 || type === 'arrive') {
                        out.push({
                            icon,
                            instruction: inst,
                            distance: s.distance || 0,
                            location: loc,
                            source: 'osrm'
                        });
                    }
                });
                return out;
            }

            function buildInternalSteps(rawLatLngs, destPoint, destLabel, includeArrival = true) {
                if (!Array.isArray(rawLatLngs) || rawLatLngs.length < 2) return [];
                const steps = [];
                // Filter out micro-duplicate points (< 2m) to avoid false turn angles
                const pts = [rawLatLngs[0]];
                for (let i = 1; i < rawLatLngs.length; i++) {
                    if (map.distance(pts[pts.length - 1], rawLatLngs[i]) >= 2.0 || i === rawLatLngs.length - 1) {
                        pts.push(rawLatLngs[i]);
                    }
                }
                if (pts.length < 2) return [];

                let segBearing = calculateBearing(pts[0][0], pts[0][1], pts[1][0], pts[1][1]);
                let accDist = map.distance(pts[0], pts[1]);
                let currentIcon = '⬆️';
                let currentInst = `Head ${getCardinalDir(segBearing)} on subdivision street`;
                let currentLoc = pts[0];

                for (let i = 1; i < pts.length - 1; i++) {
                    const nextDist = map.distance(pts[i], pts[i + 1]);
                    if (nextDist < 1.5) continue;
                    const nextBearing = calculateBearing(pts[i][0], pts[i][1], pts[i + 1][0], pts[i + 1][1]);
                    const delta = ((nextBearing - segBearing + 540) % 360) - 180;

                    if (Math.abs(delta) >= 22) {
                        steps.push({
                            icon: currentIcon,
                            instruction: currentInst,
                            distance: accDist,
                            location: currentLoc,
                            source: 'internal'
                        });
                        accDist = nextDist;
                        currentLoc = pts[i];
                        if (delta >= 120) {
                            currentIcon = '↱'; currentInst = 'Sharp right on subdivision street';
                        } else if (delta >= 55) {
                            currentIcon = '↱'; currentInst = 'Turn right';
                        } else if (delta >= 22) {
                            currentIcon = '↗️'; currentInst = 'Slight right';
                        } else if (delta <= -120) {
                            currentIcon = '↰'; currentInst = 'Sharp left on subdivision street';
                        } else if (delta <= -55) {
                            currentIcon = '↰'; currentInst = 'Turn left';
                        } else {
                            currentIcon = '↖️'; currentInst = 'Slight left';
                        }
                    } else {
                        accDist += nextDist;
                    }
                    segBearing = nextBearing;
                }

                steps.push({
                    icon: currentIcon,
                    instruction: currentInst,
                    distance: accDist,
                    location: currentLoc,
                    source: 'internal'
                });

                if (destPoint) {
                    const lastPt = pts[pts.length - 1];
                    const offDist = map.distance(lastPt, [destPoint.lat, destPoint.lng]);
                    if (offDist > 2.0) {
                        steps.push({
                            icon: '🚶',
                            instruction: `Walk to ${destLabel}`,
                            distance: offDist,
                            location: lastPt,
                            source: 'internal'
                        });
                    }
                }

                if (includeArrival) {
                    steps.push({
                        icon: '🏁',
                        instruction: `Arrive at ${destLabel}`,
                        distance: 0,
                        location: destPoint ? [destPoint.lat, destPoint.lng] : pts[pts.length - 1],
                        source: 'internal'
                    });
                }

                return steps;
            }

            function renderNavStepsUI(steps, totalDist, totalTimeSec, curLat = null, curLng = null) {
                currentNavSteps = Array.isArray(steps) ? steps : [];
                lastTotalDist = totalDist || 0;
                lastTotalTime = totalTimeSec || 0;

                // Determine active step & live distance to next maneuver
                activeStepIdx = 0;
                let distToNext = currentNavSteps[0] ? currentNavSteps[0].distance : 0;

                if (curLat !== null && curLng !== null && currentNavSteps.length > 1) {
                    for (let i = 0; i < currentNavSteps.length - 1; i++) {
                        const s = currentNavSteps[i];
                        const nextS = currentNavSteps[i + 1];
                        if (nextS && nextS.location) {
                            const dToTurn = map.distance([curLat, curLng], nextS.location);
                            if (dToTurn < 12 && i + 1 < currentNavSteps.length - 1) {
                                activeStepIdx = i + 1;
                            } else {
                                distToNext = dToTurn;
                                break;
                            }
                        }
                    }
                }

                const curStep = currentNavSteps[activeStepIdx] || { icon: '⬆️', instruction: `Head towards ${destinationName}`, distance: totalDist };
                const nextStep = currentNavSteps[activeStepIdx + 1] || null;

                // Update Active Navigation Top Banner & Bottom Bar
                const bannerArrow = document.getElementById('activeNavArrow');
                const bannerMeters = document.getElementById('activeNavMeters');
                const bannerInst = document.getElementById('activeNavInst');
                const bannerNext = document.getElementById('activeNavNext');
                const etaTimeEl = document.getElementById('activeNavEtaTime');
                const etaDistEl = document.getElementById('activeNavEtaDist');

                if (bannerArrow) bannerArrow.textContent = curStep.icon;
                if (bannerMeters) bannerMeters.style.display = 'none';
                if (bannerInst) bannerInst.textContent = curStep.instruction;
                if (bannerNext) {
                    bannerNext.textContent = nextStep
                        ? `Then: ${nextStep.icon} ${nextStep.instruction}`
                        : `Destination: ${destinationName}`;
                }
                if (etaTimeEl) etaTimeEl.textContent = fmtTime(totalTimeSec);
                if (etaDistEl) etaDistEl.textContent = `${destinationName}`;

                // Update Step-by-Step list in HUD
                const countEl = document.getElementById('navStepsCount');
                const listEl = document.getElementById('navStepsList');
                if (countEl) countEl.textContent = String(currentNavSteps.length);
                if (listEl) {
                    listEl.innerHTML = '';
                    currentNavSteps.forEach((step, idx) => {
                        const row = document.createElement('div');
                        row.className = 'nav-step-item' + (idx === activeStepIdx ? ' is-active' : (idx < activeStepIdx ? ' is-done' : ''));
                        row.innerHTML = `
                            <div class="nav-step-icon">${step.icon}</div>
                            <div class="nav-step-body">
                                <div class="nav-step-text">${step.instruction}</div>
                            </div>
                        `;
                        if (step.location) {
                            row.addEventListener('click', (e) => {
                                e.stopPropagation();
                                autoFollow = false;
                                map.setView(step.location, Math.max(map.getZoom(), 18), { animate: true });
                            });
                        }
                        listEl.appendChild(row);
                    });
                }
            }

            function applyActiveNavState(active) {
                isNavStarted = active;
                const viewport = document.getElementById('mapViewport');
                const banner = document.getElementById('activeNavBanner');
                const bottomBar = document.getElementById('activeNavBottom');
                const startBtn = document.getElementById('btnStartNav');
                const hud = document.getElementById('liveNavHud');

                if (viewport) viewport.classList.toggle('is-nav-active', active);
                if (banner) banner.classList.toggle('is-visible', active);
                if (bottomBar) bottomBar.classList.toggle('is-visible', active);
                if (startBtn) {
                    startBtn.classList.toggle('is-active', active);
                    startBtn.innerHTML = active ? '⏹ Stop' : '🧭 Start';
                }

                if (active) {
                    autoFollow = true;
                    // Enable Heading-Up POV mode automatically when starting active navigation
                    if (typeof map.setBearing === 'function') {
                        isHeadingUp = true;
                        const hBtn = document.getElementById('btnHeadingUp');
                        if (hBtn) hBtn.textContent = '🧭 Heading';
                    }
                    if (typeof requestDeviceOrientationPermission === 'function') {
                        requestDeviceOrientationPermission();
                    }
                    // Collapse HUD dropdown when entering active turn-by-turn mode so the map is unobstructed
                    if (typeof window.toggleNavUiCollapse === 'function') {
                        window.toggleNavUiCollapse(false);
                    } else if (hud) {
                        hud.style.display = 'none';
                    }
                    const focusTarget = userPos
                        ? [userPos.lat, userPos.lng]
                        : (currentNavSteps[0] && currentNavSteps[0].location ? currentNavSteps[0].location : entranceCoord);
                    const targetZoom = (userPos && !checkIfInside(userPos.lat, userPos.lng)) ? 17 : 19;
                    if (focusTarget) {
                        map.setView(focusTarget, targetZoom, { animate: true });
                        lastPanCoord = focusTarget;
                    }
                    if (isHeadingUp && currentHeading !== null && typeof map.setBearing === 'function') {
                        map.setBearing(-currentHeading);
                        syncHeadingConeToMapBearing();
                    }
                } else {
                    if (hud) hud.style.top = '';
                    if (isHeadingUp && typeof map.setBearing === 'function') {
                        isHeadingUp = false;
                        map.setBearing(0);
                        syncHeadingConeToMapBearing();
                        const hBtn = document.getElementById('btnHeadingUp');
                        if (hBtn) hBtn.textContent = '🧭 North';
                    }
                }
            }

            window.toggleGISStartNavigation = function (forceState) {
                const nextState = (typeof forceState === 'boolean') ? forceState : !isNavStarted;
                applyActiveNavState(nextState);
            };

            window.toggleGISHeadingUp = function () {
                isHeadingUp = !isHeadingUp;
                const hBtn = document.getElementById('btnHeadingUp');
                if (hBtn) hBtn.textContent = isHeadingUp ? '🧭 Heading' : '🧭 North';
                if (isHeadingUp && typeof requestDeviceOrientationPermission === 'function') {
                    requestDeviceOrientationPermission();
                }
                if (typeof map.setBearing === 'function') {
                    if (isHeadingUp && currentHeading !== null) {
                        map.setBearing(-currentHeading);
                    } else {
                        map.setBearing(0);
                    }
                    syncHeadingConeToMapBearing();
                }
            };

            function getRouteStrokeWidth(z) {
                const zoom = (z !== undefined && z !== null) ? z : map.getZoom();
                if (zoom >= 20) return { activeCore: 10.0, activeCasing: 12.4, altCore: 8.0, altCasing: 10.2 };
                if (zoom >= 19) return { activeCore: 8.5,  activeCasing: 10.8, altCore: 6.8, altCasing: 8.8 };
                if (zoom >= 18) return { activeCore: 7.2,  activeCasing: 9.4,  altCore: 5.8, altCasing: 7.8 };
                if (zoom >= 17) return { activeCore: 6.0,  activeCasing: 8.0,  altCore: 4.8, altCasing: 6.6 };
                if (zoom >= 15) return { activeCore: 5.0,  activeCasing: 7.0,  altCore: 4.0, altCasing: 5.8 };
                if (zoom >= 13) return { activeCore: 4.2,  activeCasing: 6.0,  altCore: 3.4, altCasing: 5.0 };
                return { activeCore: 3.5, activeCasing: 5.2, altCore: 2.8, altCasing: 4.4 };
            }

            // Dual Route Visual Renderer (Vibrant blue active route + clickable slate/grey alternative route with Google Maps-style minutes pill)
            function renderDualRoute(primaryLatLngs, altLatLngs, onToggle, routeMeta = {}) {
                const hasAlt = Array.isArray(altLatLngs) && altLatLngs.length > 1;
                const altMinutesText = routeMeta.secondaryTimeText || '';

                if (altRouteBtn) {
                    if (hasAlt) {
                        altRouteBtn.style.display = 'inline-flex';
                        altRouteBtn.classList.toggle('active', preferAlternative);
                        altRouteBtn.innerHTML = preferAlternative
                            ? `✓ Alt Active${altMinutesText ? ` (${altMinutesText})` : ''}`
                            : `⇄ Alt Route${altMinutesText ? ` (${altMinutesText})` : ''}`;
                        altRouteBtn.onclick = (e) => {
                            e.stopPropagation();
                            preferAlternative = !preferAlternative;
                            if (onToggle) onToggle();
                        };
                    } else {
                        altRouteBtn.style.display = 'none';
                        altRouteBtn.onclick = null;
                    }
                }

                const rawActive = (preferAlternative && hasAlt) ? altLatLngs : primaryLatLngs;
                const rawSecondary = (preferAlternative && hasAlt) ? primaryLatLngs : (hasAlt ? altLatLngs : null);
                
                const activeCoords = smoothTurnRoute(rawActive, 3.5);
                const secondaryCoords = rawSecondary ? smoothTurnRoute(rawSecondary, 3.5) : null;
                const strokeW = getRouteStrokeWidth(map.getZoom());

                // 1. Draw Alternative / Secondary route underneath in Google Maps grey
                if (secondaryCoords && secondaryCoords.length > 1) {
                    const casing = L.polyline(secondaryCoords, {
                        color: '#70757a',
                        weight: strokeW.altCasing,
                        opacity: 0.9,
                        lineCap: 'round',
                        lineJoin: 'round',
                        className: 'route-alt-polyline route-alt-casing'
                    }).addTo(routingLayerGroup);

                    const core = L.polyline(secondaryCoords, {
                        color: '#bdc1c6',
                        weight: strokeW.altCore,
                        opacity: 0.98,
                        lineCap: 'round',
                        lineJoin: 'round',
                        className: 'route-alt-polyline route-alt-core'
                    }).addTo(routingLayerGroup);

                    const tipText = `🔄 <b>Alternative Route${altMinutesText ? ` (${altMinutesText})` : ''}</b><br><span style="font-size:10px; color:#cbd5e1;">Click or tap to select this route</span>`;
                    casing.bindTooltip(tipText, { sticky: true });
                    core.bindTooltip(tipText, { sticky: true });

                    const handleAltClick = (e) => {
                        L.DomEvent.stopPropagation(e);
                        preferAlternative = !preferAlternative;
                        if (onToggle) onToggle();
                    };

                    casing.on('click', handleAltClick);
                    core.on('click', handleAltClick);

                    // Google Maps-style floating duration pill on the alternative route (no emoji)
                    if (altMinutesText) {
                        const midIdx = Math.floor(secondaryCoords.length * 0.45);
                        const midPt = secondaryCoords[midIdx] || secondaryCoords[0];
                        if (midPt) {
                            const pillIcon = L.divIcon({
                                className: 'alt-route-duration-pill',
                                html: `<div style="background:#ffffff; color:#1e293b; border:1.5px solid #94a3b8; border-radius:999px; padding:3px 10px; font-size:11px; font-weight:800; white-space:nowrap; box-shadow:0 2px 8px rgba(15,23,42,0.25); cursor:pointer; display:inline-flex; align-items:center;">${altMinutesText}</div>`,
                                iconSize: [0, 0],
                                iconAnchor: [28, 12]
                            });
                            const pillMarker = L.marker(midPt, { icon: pillIcon, zIndexOffset: 650 }).addTo(routingLayerGroup);
                            pillMarker.on('click', handleAltClick);
                        }
                    }
                }

                // 2. Draw Active / Suggested route on top in Google Maps deep blue (#1F3BFF) with thin dark border (#0B1033)
                if (activeCoords && activeCoords.length > 1) {
                    L.polyline(activeCoords, {
                        color: '#0b1033',
                        weight: strokeW.activeCasing,
                        opacity: 0.9,
                        lineCap: 'round',
                        lineJoin: 'round',
                        interactive: false,
                        className: 'route-active-casing'
                    }).addTo(routingLayerGroup);

                    L.polyline(activeCoords, {
                        color: '#1f3bff',
                        weight: strokeW.activeCore,
                        opacity: 1,
                        lineCap: 'round',
                        lineJoin: 'round',
                        interactive: false,
                        className: 'route-active-core'
                    }).addTo(routingLayerGroup);
                }

                return activeCoords;
            }

            // Dynamically scale route stroke widths on map zoom so the line always fits the road width
            const onZoomEndUpdateWeights = () => {
                const sw = getRouteStrokeWidth(map.getZoom());
                routingLayerGroup.eachLayer(layer => {
                    if (layer instanceof L.Polyline && !(layer instanceof L.Polygon)) {
                        const cls = (layer.options && layer.options.className) || '';
                        if (cls.includes('route-active-casing')) {
                            layer.setStyle({ weight: sw.activeCasing });
                        } else if (cls.includes('route-active-core')) {
                            layer.setStyle({ weight: sw.activeCore });
                        } else if (cls.includes('route-alt-casing')) {
                            layer.setStyle({ weight: sw.altCasing });
                        } else if (cls.includes('route-alt-core')) {
                            layer.setStyle({ weight: sw.altCore });
                        }
                    }
                });
            };
            map.on('zoomend', onZoomEndUpdateWeights);

            // Immediate initial route preview while GPS locks on
            function drawInitialPreview() {
                routingLayerGroup.clearLayers();
                if (!staticPathIds || staticPathIds.length === 0) return;

                const initLatLngs = staticPathIds.map(id => coordinatesMap.get(id)).filter(Boolean);
                const altInitLatLngs = (staticAltPathIds && staticAltPathIds.length > 0)
                    ? staticAltPathIds.map(id => coordinatesMap.get(id)).filter(Boolean)
                    : null;

                const activeLatLngs = renderDualRoute(initLatLngs, altInitLatLngs, () => {
                    drawInitialPreview();
                });

                let initDist = 0;
                for (let i = 0; i < activeLatLngs.length - 1; i++) {
                    initDist += map.distance(activeLatLngs[i], activeLatLngs[i + 1]);
                }

                // Dotted grey walking line from road centerline to house (Google Maps style)
                const lastRoadPt = activeLatLngs[activeLatLngs.length - 1];
                if (lastRoadPt) {
                    const offRoadDist = map.distance(lastRoadPt, [destCoord.lat, destCoord.lng]);
                    initDist += offRoadDist;
                    if (offRoadDist > 1.5) {
                        drawGoogleDottedConnector([destCoord.lat, destCoord.lng], lastRoadPt, routingLayerGroup);
                    }
                }

                // Google Maps Red Location Pin on Destination House
                createGoogleDestPinMarker([destCoord.lat, destCoord.lng], `📍 Destination (${destinationName})`);

                if (distValEl) distValEl.textContent = fmtDist(initDist);
                if (timeValEl) timeValEl.textContent = fmtTime(initDist / 4.16);
                if (stepInstEl) {
                    const rTitle = (preferAlternative && altInitLatLngs) ? 'Alternative subdivision route' : 'Subdivision route';
                    stepInstEl.innerHTML = `${rTitle} to <strong>${destinationName}</strong> (waiting for GPS...)`;
                }

                const rawPreviewPts = (preferAlternative && altInitLatLngs) ? altInitLatLngs : initLatLngs;
                const previewSteps = buildInternalSteps(rawPreviewPts, destCoord, destinationName, true);
                renderNavStepsUI(previewSteps, initDist, initDist / 4.16);

                if (!initialFitDone) {
                    map.fitBounds(L.latLngBounds([entranceCoord, [destCoord.lat, destCoord.lng]]), { padding: [40, 40] });
                }
            }

            drawInitialPreview();
            renderDestinationZone(false);

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

                function alignRouteCoords(rawCoords) {
                    const rawLatLngs = rawCoords.map(c => [c[1], c[0]]);
                    if (!rawLatLngs || rawLatLngs.length === 0) return [];
                    // Keep highway coordinates untouched so the polyline stays 100% dead-center on the outside road pavement!
                    return rawLatLngs;
                }

                function parseRouteData(data) {
                    if (data && data.code === 'Ok' && data.routes && data.routes.length > 0) {
                        const r0 = data.routes[0];
                        const alignedLatLngs = alignRouteCoords(r0.geometry.coordinates);

                        let altOSRM = null;
                        if (data.routes.length > 1) {
                            const r1 = data.routes[1];
                            altOSRM = {
                                latlngs: alignRouteCoords(r1.geometry.coordinates),
                                distance: r1.distance,
                                duration: r1.duration,
                                steps: parseOSRMSteps(r1)
                            };
                        }

                        cachedOSRM = {
                            latlngs: alignedLatLngs,
                            distance: r0.distance,
                            duration: r0.duration,
                            steps: parseOSRMSteps(r0),
                            alternative: altOSRM
                        };
                        lastOSRMTime = now;
                        lastOSRMCoord = { lat: startLat, lng: startLng };
                        return cachedOSRM;
                    }
                    return null;
                }

                // 1. First priority: Fetch via Laravel Highway Route Proxy (Free, high-speed cached endpoint)
                try {
                    const proxyUrl = `/api/navigation/highway-route?origin_lat=${startLat.toFixed(6)}&origin_lng=${startLng.toFixed(6)}&dest_lat=${endLat.toFixed(6)}&dest_lng=${endLng.toFixed(6)}`;
                    const res = await fetch(proxyUrl, { headers: { 'Accept': 'application/json' } });
                    if (res.ok) {
                        const data = await res.json();
                        const parsed = parseRouteData(data);
                        if (parsed) return parsed;
                    }
                } catch (err) {
                    console.warn('Highway Navigation Proxy fallback notice:', err);
                }

                // 2. Direct Fallback: Client-side OSRM driving query
                try {
                    const url = `https://router.project-osrm.org/route/v1/driving/${startLng.toFixed(6)},${startLat.toFixed(6)};${endLng.toFixed(6)},${endLat.toFixed(6)}?overview=full&geometries=geojson&alternatives=3&steps=true`;
                    const res = await fetch(url);
                    if (res.ok) {
                        const data = await res.json();
                        const parsed = parseRouteData(data);
                        if (parsed) return parsed;
                    }
                } catch (err) {
                    console.warn('OSRM Highway routing notice:', err);
                }

                const directDist = map.distance([startLat, startLng], [endLat, endLng]);
                return {
                    latlngs: [[startLat, startLng], [endLat, endLng]],
                    distance: directDist,
                    duration: directDist / 8.33,
                    alternative: null
                };
            }

            function pointInSubdivisionHull(lat, lng) {
                if (!subdivisionHull || subdivisionHull.length < 3) return false;
                let inside = false;
                for (let i = 0, j = subdivisionHull.length - 1; i < subdivisionHull.length; j = i++) {
                    const xi = subdivisionHull[i][0], yi = subdivisionHull[i][1];
                    const xj = subdivisionHull[j][0], yj = subdivisionHull[j][1];
                    if (((yi > lng) !== (yj > lng)) && (lat < (xj - xi) * (lng - yi) / (yj - yi) + xi)) {
                        inside = !inside;
                    }
                }
                return inside;
            }

            let wasInsideSubdivision = false;
            function checkIfInside(lat, lng) {
                const enterPad = wasInsideSubdivision ? 6 : 0; // hysteresis to prevent boundary flickering
                // 0. Inside the subdivision perimeter polygon (prevents GPS drift inside blocks from triggering highway OSRM)
                if (pointInSubdivisionHull(lat, lng)) {
                    wasInsideSubdivision = true;
                    return true;
                }

                // 1. Proximity to entrance gate (tight when entering from highway so passing traffic stays on OSRM)
                const gateRadius = wasInsideSubdivision ? 18 : 12;
                if (map.distance([lat, lng], entranceCoord) <= gateRadius) {
                    wasInsideSubdivision = true;
                    return true;
                }

                // 2. Proximity to any internal subdivision road segment
                const pt = [lat, lng];
                const segLimit = (wasInsideSubdivision ? 20 : 14) + enterPad;
                for (let i = 0; i < segments.length; i++) {
                    const proj = projectPointToSegment(pt, segments[i].p1, segments[i].p2);
                    if (proj.dist <= segLimit) {
                        wasInsideSubdivision = true;
                        return true;
                    }
                }

                // 3. Proximity to destination house
                if (map.distance([lat, lng], [destCoord.lat, destCoord.lng]) <= (28 + enterPad)) {
                    wasInsideSubdivision = true;
                    return true;
                }

                wasInsideSubdivision = false;
                return false;
            }

            // Point of View (POV) Directional Guide (Damped Compass & Camera Bearing to prevent body-movement wiggle)
            let currentHeading = null;
            let cameraHeading = null;
            let targetHeading = null;
            let lastAppliedCameraHeading = null;
            let lastCameraBearingMs = 0;
            let lastCoordForHeading = null;
            let hasAbsoluteCompass = false;
            let hasCompassSensor = false;
            let lastHeadingFrameMs = 0;
            let headingRafId = null;

            function calculateBearing(lat1, lon1, lat2, lon2) {
                const toRad = Math.PI / 180;
                const dLon = (lon2 - lon1) * toRad;
                const y = Math.sin(dLon) * Math.cos(lat2 * toRad);
                const x = Math.cos(lat1 * toRad) * Math.sin(lat2 * toRad) -
                          Math.sin(lat1 * toRad) * Math.cos(lat2 * toRad) * Math.cos(dLon);
                return (Math.atan2(y, x) * 180 / Math.PI + 360) % 360;
            }

            function shortestAngleDiff(target, current) {
                return ((target - current + 540) % 360) - 180;
            }

            function getConeScreenAngle() {
                if (currentHeading === null) return 0;
                // leaflet-rotate counter-rotates L.marker icons to stay upright on screen,
                // so add map.getBearing() to keep the cone pointing in the true compass direction.
                const mapBearing = (typeof map.getBearing === 'function') ? map.getBearing() : 0;
                return ((currentHeading + mapBearing) % 360 + 360) % 360;
            }

            function syncHeadingConeToMapBearing() {
                const cone = document.getElementById('userHeadingCone');
                if (cone && currentHeading !== null) {
                    cone.style.transform = `rotate(${getConeScreenAngle().toFixed(2)}deg)`;
                }
            }

            function maybeUpdateMapBearing(forceImmediate = false) {
                if (!isHeadingUp || typeof map.setBearing !== 'function' || currentHeading === null) return;
                const nowMs = performance.now();
                if (!forceImmediate && (nowMs - lastCameraBearingMs) < 300) return;

                const refHeading = lastAppliedCameraHeading !== null ? lastAppliedCameraHeading : currentHeading;
                const diff = Math.abs(shortestAngleDiff(currentHeading, refHeading));
                if (forceImmediate || lastAppliedCameraHeading === null || diff >= 8.0) {
                    lastAppliedCameraHeading = currentHeading;
                    lastCameraBearingMs = nowMs;
                    map.setBearing(-currentHeading);
                    syncHeadingConeToMapBearing();
                }
            }

            function stepHeadingAnimation(nowMs) {
                headingRafId = null;
                if (targetHeading === null) return;

                if (currentHeading === null) {
                    currentHeading = targetHeading;
                    cameraHeading = targetHeading;
                } else {
                    const dt = lastHeadingFrameMs ? Math.min((nowMs - lastHeadingFrameMs) / 1000, 0.1) : 0.016;
                    const diff = shortestAngleDiff(targetHeading, currentHeading);

                    // Smooth cone (200ms time constant, 1.5 deg deadband) — cheap GPU transform only
                    if (Math.abs(diff) < 1.5) {
                        currentHeading = targetHeading;
                    } else {
                        const smoothAlpha = 1 - Math.exp(-dt / 0.20);
                        currentHeading = ((currentHeading + diff * smoothAlpha) % 360 + 360) % 360;
                    }

                    if (Math.abs(shortestAngleDiff(targetHeading, currentHeading)) >= 1.5) {
                        headingRafId = requestAnimationFrame(stepHeadingAnimation);
                    }
                }
                lastHeadingFrameMs = nowMs;
                syncHeadingConeToMapBearing();
                maybeUpdateMapBearing(false);
            }

            function updateHeadingCone(h) {
                if (h == null || isNaN(h)) return;
                const normalized = ((h % 360) + 360) % 360;
                if (targetHeading === null) {
                    targetHeading = normalized;
                    currentHeading = normalized;
                    cameraHeading = normalized;
                    syncHeadingConeToMapBearing();
                    maybeUpdateMapBearing(true);
                    return;
                }
                // Low-pass filter raw sensor jitter before updating targetHeading
                const delta = shortestAngleDiff(normalized, targetHeading);
                if (Math.abs(delta) < 2.5) return;
                targetHeading = ((targetHeading + delta * 0.30) % 360 + 360) % 360;
                if (!headingRafId) {
                    lastHeadingFrameMs = performance.now();
                    headingRafId = requestAnimationFrame(stepHeadingAnimation);
                }
            }

            function getScreenOrientationAngle() {
                if (window.screen && window.screen.orientation && typeof window.screen.orientation.angle === 'number') {
                    return window.screen.orientation.angle;
                }
                if (typeof window.orientation === 'number') {
                    return window.orientation;
                }
                return 0;
            }

            // 3D tilt-compensated compass heading from Z-X'-Y'' Euler angles (alpha, beta, gamma)
            // Smoothly handles flat, tilted, upright, and upside-down phone orientations
            function computeTiltCompensatedHeading(alpha, beta, gamma) {
                const toRad = Math.PI / 180;
                const a = alpha * toRad;
                const b = (beta || 0) * toRad;
                const g = (gamma || 0) * toRad;

                const cA = Math.cos(a), sA = Math.sin(a);
                const cB = Math.cos(b), sB = Math.sin(b);
                const cG = Math.cos(g), sG = Math.sin(g);

                // Device +Y (top of phone) projected onto world East/North
                const yEast = -sA * cB;
                const yNorth = cA * cB;
                const yHoriz = Math.hypot(yEast, yNorth);

                // Device -Z (back of phone) projected onto world East/North (used when phone is held upright)
                const zEast = -cA * sG - sA * sB * cG;
                const zNorth = -sA * sG + cA * sB * cG;
                const zHoriz = Math.hypot(zEast, zNorth);

                // Use whichever axis has stronger horizontal projection (prevents gimbal flip at any tilt/upside-down)
                let east, north;
                if (yHoriz >= 0.35) {
                    east = yEast;
                    north = yNorth;
                } else if (zHoriz >= 1e-4) {
                    east = zEast;
                    north = zNorth;
                } else {
                    return null;
                }

                return (Math.atan2(east, north) * 180 / Math.PI + 360) % 360;
            }

            // Quaternion-based 3D heading for AbsoluteOrientationSensor (60Hz, zero gimbal lock at any angle)
            function computeHeadingFromQuaternion(q) {
                if (!q || q.length < 4) return null;
                const x = q[0], y = q[1], z = q[2], w = q[3];
                // Rotate device +Y = (0, 1, 0) into world ENU
                const yEast  = 2 * (x * y - w * z);
                const yNorth = 1 - 2 * (x * x + z * z);
                // Rotate device -Z = (0, 0, -1) into world ENU
                const zEast  = -2 * (x * z + w * y);
                const zNorth = -2 * (y * z - w * x);

                const yHoriz = Math.hypot(yEast, yNorth);
                const zHoriz = Math.hypot(zEast, zNorth);
                let east, north;
                if (yHoriz >= 0.35) {
                    east = yEast;
                    north = yNorth;
                } else if (zHoriz >= 1e-4) {
                    east = zEast;
                    north = zNorth;
                } else {
                    return null;
                }
                return (Math.atan2(east, north) * 180 / Math.PI + 360) % 360;
            }

            let absSensorInstance = null;
            let relativeCompassOffset = 0;
            let lastRelativeRawHeading = null;
            const sensorStartMs = Date.now();

            function onDeviceOrientation(e) {
                if (absSensorInstance) return; // AbsoluteOrientationSensor is already driving at 60Hz
                let h = null;
                if (e.webkitCompassHeading !== undefined && e.webkitCompassHeading !== null) {
                    if (typeof e.webkitCompassAccuracy === 'number' && (e.webkitCompassAccuracy < 0 || e.webkitCompassAccuracy > 50)) {
                        return;
                    }
                    hasAbsoluteCompass = true;
                    hasCompassSensor = true;
                    h = e.webkitCompassHeading;
                } else if (e.type === 'deviceorientationabsolute' || e.absolute === true) {
                    if (e.alpha !== null && e.alpha !== undefined) {
                        hasAbsoluteCompass = true;
                        hasCompassSensor = true;
                        h = computeTiltCompensatedHeading(e.alpha, e.beta, e.gamma);
                    }
                } else if (!hasAbsoluteCompass && e.alpha !== null && e.alpha !== undefined && (Date.now() - sensorStartMs > 600)) {
                    // Fallback for Android devices that only emit relative deviceorientation:
                    // keeps the cone 100% responsive to turns and aligns to GPS course when moving.
                    const rawRel = computeTiltCompensatedHeading(e.alpha, e.beta, e.gamma);
                    if (rawRel !== null) {
                        hasCompassSensor = true;
                        lastRelativeRawHeading = rawRel;
                        h = (rawRel + relativeCompassOffset + 360) % 360;
                    }
                }
                if (h !== null && !isNaN(h)) {
                    h = (h + getScreenOrientationAngle() + 360) % 360;
                    updateHeadingCone(h);
                }
            }

            let orientationListenersAttached = false;
            function attachOrientationListeners() {
                if (orientationListenersAttached) return;
                orientationListenersAttached = true;

                // 1. Prefer W3C AbsoluteOrientationSensor (true North, 15Hz, zero gimbal lock)
                if ('AbsoluteOrientationSensor' in window) {
                    try {
                        const sensor = new window.AbsoluteOrientationSensor({ frequency: 15, referenceFrame: 'screen' });
                        sensor.addEventListener('reading', () => {
                            const h = computeHeadingFromQuaternion(sensor.quaternion);
                            if (h !== null && !isNaN(h)) {
                                hasAbsoluteCompass = true;
                                hasCompassSensor = true;
                                updateHeadingCone(h);
                            }
                        });
                        sensor.addEventListener('error', () => {
                            absSensorInstance = null;
                        });
                        sensor.start();
                        absSensorInstance = sensor;
                    } catch (err) {
                        absSensorInstance = null;
                    }
                }

                // 2. Fallback to DeviceOrientationEvent
                if (window.DeviceOrientationEvent) {
                    if ('ondeviceorientationabsolute' in window) {
                        window.addEventListener('deviceorientationabsolute', onDeviceOrientation, false);
                    }
                    window.addEventListener('deviceorientation', onDeviceOrientation, false);
                }
            }

            function requestDeviceOrientationPermission() {
                if (typeof DeviceOrientationEvent !== 'undefined' && typeof DeviceOrientationEvent.requestPermission === 'function') {
                    DeviceOrientationEvent.requestPermission()
                        .then(state => {
                            if (state === 'granted') {
                                attachOrientationListeners();
                            }
                        })
                        .catch(() => {});
                } else {
                    attachOrientationListeners();
                }
            }

            // Attach immediately on Android/Desktop, and on first touch for iOS 13+ permission
            if (typeof DeviceOrientationEvent !== 'undefined' && typeof DeviceOrientationEvent.requestPermission !== 'function') {
                attachOrientationListeners();
            } else {
                const onFirstTouchForCompass = () => {
                    requestDeviceOrientationPermission();
                };
                map.getContainer().addEventListener('touchstart', onFirstTouchForCompass, { once: true, passive: true });
                map.getContainer().addEventListener('click', onFirstTouchForCompass, { once: true, passive: true });
            }
            map.on('rotate', syncHeadingConeToMapBearing);

            // Professional Navigation Stability & Anti-Jumping State
            let lastAcceptedPos = null;
            let lastAcceptedTime = 0;
            let isArrived = false;
            let wasArrivedRendered = false;
            let lastPanCoord = null;
            let latestPositionSeq = 0;
            const ROAD_CORRIDOR_METERS = 8.5; // Street-width + normal in-car GPS tolerance inside subdivision
            const ARRIVAL_ENTER_BUFFER_M = 3.0; // Inside destination house/frontage pad or within 3m of its edge
            const ARRIVAL_EXIT_BUFFER_M = 9.0;  // Exit arrival and resume routing once >9m away from house/frontage
            const GUARD_HOUSE_ENTER_M = 1.0;    // Must be inside destination house polygon (or within 1m of wall)
            const HOUSE_EXIT_BUFFER_M = 8.0;    // Must move >8m outside the house polygon to unlock indoor GPS lock
            const HOUSE_EXIT_STREAK_REQUIRED = 3; // Consecutive accurate readings outside house before unlocking
            let isInsideHouse = false;
            let hasEnteredHouse = false;
            let hasLeftHouse = false;
            let outsideHouseStreak = 0;

            function updateLiveGpsVisuals(displayCoord, rawCoord, accuracy) {
                const clampedRadius = Math.min(accuracy || 10, 45);
                if (!liveGpsMarker) {
                    const beaconIcon = L.divIcon({
                        className: 'subdivision-live-marker-container',
                        html: `
                            <div class="subdivision-live-beacon">
                                <div class="user-heading-cone" id="userHeadingCone" style="transform: rotate(${getConeScreenAngle()}deg);">
                                    <svg width="84" height="84" viewBox="0 0 84 84" style="display:block;">
                                        <defs>
                                            <radialGradient id="userBeamGlow" cx="50%" cy="50%" r="50%">
                                                <stop offset="0%" stop-color="#4285F4" stop-opacity="0.58" />
                                                <stop offset="65%" stop-color="#4285F4" stop-opacity="0.22" />
                                                <stop offset="100%" stop-color="#4285F4" stop-opacity="0.0" />
                                            </radialGradient>
                                        </defs>
                                        <path d="M 42 42 L 13 6 A 44 44 0 0 1 71 6 Z" fill="url(#userBeamGlow)" />
                                    </svg>
                                </div>
                                <div class="pulse-ring"></div>
                                <div class="center-dot"></div>
                            </div>
                        `,
                        iconSize: [84, 84],
                        iconAnchor: [42, 42]
                    });
                    liveGpsMarker = L.marker(displayCoord, { icon: beaconIcon, zIndexOffset: 1000 }).addTo(liveNavGroup);
                    liveGpsMarker.bindTooltip('📍 Your Live Location', { direction: 'top', offset: [0, -10] });
                    accuracyCircle = L.circle(rawCoord, {
                        radius: clampedRadius,
                        color: '#3b82f6',
                        weight: 1,
                        fillColor: '#93c5fd',
                        fillOpacity: 0.18,
                        interactive: false
                    }).addTo(liveNavGroup);
                    syncHeadingConeToMapBearing();
                } else {
                    liveGpsMarker.setLatLng(displayCoord);
                    if (accuracyCircle) {
                        accuracyCircle.setLatLng(rawCoord);
                        accuracyCircle.setRadius(clampedRadius);
                    }
                }
            }

            function streamVisitorLocation(curLat, curLng, forceSend = false) {
                if (!config.visitorToken) return;
                const nowTs = Date.now();

                if (forceSend || !window._lastLocationSendTime || (nowTs - window._lastLocationSendTime >= 3500)) {
                    window._lastLocationSendTime = nowTs;
                    fetch('/api/visitor/location', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            token: config.visitorToken,
                            lat: curLat,
                            lng: curLng,
                            is_at_destination: hasEnteredHouse,
                            has_left_house: hasLeftHouse
                        })
                    })
                    .then(res => res.ok ? res.json() : null)
                    .then(data => {
                        if (data && data.stopped) {
                            if (watchId !== null && navigator.geolocation) {
                                navigator.geolocation.clearWatch(watchId);
                                watchId = null;
                            }
                            if (badgeEl) {
                                badgeEl.className = 'nav-hud-badge';
                                if (badgeTextEl) badgeTextEl.textContent = '🚪 Visit Completed';
                            }
                            if (simTipEl) {
                                simTipEl.innerHTML = '🚪 Gate exit recorded • Live GPS sharing turned off';
                            }
                        }
                    })
                    .catch(() => {});
                }
            }

            async function handlePosition(rawLat, rawLng, rawAccuracy, headingVal) {
                if (!Number.isFinite(rawLat) || !Number.isFinite(rawLng)) return;
                const mySeq = ++latestPositionSeq;
                const now = Date.now();

                // 1. INITIAL & SEVERE ACCURACY GATING (never hard-freeze on normal 30-65m near-house readings)
                if (!lastAcceptedPos && rawAccuracy && rawAccuracy > 100) {
                    if (simTipEl) {
                        simTipEl.innerHTML = `🛰️ Refining GPS signal (±${Math.round(rawAccuracy)}m)...`;
                    }
                    return; // Wait for a usable initial fix instead of anchoring to a coarse cell/Wi-Fi jump
                }
                if (lastAcceptedPos && rawAccuracy && rawAccuracy > 65) {
                    if (simTipEl) {
                        simTipEl.innerHTML = `⚠️ Weak GPS signal (±${Math.round(rawAccuracy)}m) • Holding steady position`;
                    }
                    if (accuracyCircle) {
                        accuracyCircle.setRadius(Math.min(rawAccuracy, 45));
                    }
                    return; // Ignore extreme cell-tower noise only
                }

                // 2. SPEED / TELEPORTATION SANITY CHECK & ACCURACY-AWARE EMA SMOOTHING
                let curLat = rawLat;
                let curLng = rawLng;
                if (lastAcceptedPos) {
                    const jumpM = map.distance([lastAcceptedPos.lat, lastAcceptedPos.lng], [rawLat, rawLng]);
                    const dt = (now - lastAcceptedTime) / 1000;
                    if (dt > 0.4 && dt < 12) {
                        const speed = jumpM / dt;
                        // Allow normal highway driving speeds outside; suppress impossible multipath spikes
                        const maxReasonableSpeed = wasInsideSubdivision ? 18.0 : 45.0;
                        if (speed > maxReasonableSpeed && jumpM > 35) {
                            if (simTipEl) {
                                simTipEl.innerHTML = '🛰️ Suppressing GPS multipath spike...';
                            }
                            return;
                        }
                    }

                    // Smooth jitter without freezing: gentle damping when stationary/arrived or indoors, responsive when moving
                    let baseAlpha;
                    if (isArrived && jumpM < 1.5) {
                        baseAlpha = 0.18;
                    } else if (jumpM < 3.0) {
                        baseAlpha = 0.28;
                    } else if (jumpM < 12.0) {
                        baseAlpha = 0.48;
                    } else {
                        baseAlpha = 0.68;
                    }
                    const accScale = (rawAccuracy && rawAccuracy > 30) ? 0.35 : ((rawAccuracy && rawAccuracy > 18) ? 0.7 : 1.0);
                    const alpha = Math.max(0.12, baseAlpha * accScale);
                    curLat = lastAcceptedPos.lat + alpha * (rawLat - lastAcceptedPos.lat);
                    curLng = lastAcceptedPos.lng + alpha * (rawLng - lastAcceptedPos.lng);
                }
                lastAcceptedPos = { lat: curLat, lng: curLng };
                lastAcceptedTime = now;
                userPos = { lat: curLat, lng: curLng, accuracy: rawAccuracy };

                // 2B. HOUSE-ENTRY DETECTION & INDOOR GPS LOCK (keeps live GPS inside the house)
                let forceGuardPing = false;
                if (destCoord) {
                    const rawDistToHouse = destArrivalZone.getDistanceToHouse(rawLat, rawLng);
                    const curDistToHouse = destArrivalZone.getDistanceToHouse(curLat, curLng);
                    if (!isInsideHouse) {
                        if ((curDistToHouse <= GUARD_HOUSE_ENTER_M || rawDistToHouse <= GUARD_HOUSE_ENTER_M) && (!rawAccuracy || rawAccuracy <= 40)) {
                            isInsideHouse = true;
                            outsideHouseStreak = 0;
                            if (!hasEnteredHouse) {
                                hasEnteredHouse = true;
                                forceGuardPing = true;
                            }
                            const clamped = destArrivalZone.clampToHouse(curLat, curLng);
                            curLat = clamped[0];
                            curLng = clamped[1];
                            lastAcceptedPos = { lat: curLat, lng: curLng };
                            userPos = { lat: curLat, lng: curLng, accuracy: rawAccuracy };
                        }
                    } else {
                        if (rawDistToHouse > HOUSE_EXIT_BUFFER_M && (!rawAccuracy || rawAccuracy <= 20)) {
                            outsideHouseStreak++;
                        } else {
                            outsideHouseStreak = 0;
                        }

                        if (outsideHouseStreak >= HOUSE_EXIT_STREAK_REQUIRED) {
                            isInsideHouse = false;
                            outsideHouseStreak = 0;
                            if (hasEnteredHouse && !hasLeftHouse) {
                                hasLeftHouse = true;
                                forceGuardPing = true;
                            }
                        } else {
                            const clamped = destArrivalZone.clampToHouse(curLat, curLng);
                            curLat = clamped[0];
                            curLng = clamped[1];
                            lastAcceptedPos = { lat: curLat, lng: curLng };
                            userPos = { lat: curLat, lng: curLng, accuracy: rawAccuracy };
                        }
                    }
                }

                // 3. HEADING / COMPASS CALIBRATION (stays active even after arrival)
                let gpsCourse = null;
                if (headingVal !== null && headingVal !== undefined && !isNaN(headingVal)) {
                    gpsCourse = headingVal;
                } else if (lastCoordForHeading) {
                    const d = map.distance([lastCoordForHeading.lat, lastCoordForHeading.lng], [curLat, curLng]);
                    if (d > 4.0) {
                        gpsCourse = calculateBearing(lastCoordForHeading.lat, lastCoordForHeading.lng, curLat, curLng);
                        lastCoordForHeading = { lat: curLat, lng: curLng };
                    }
                } else {
                    lastCoordForHeading = { lat: curLat, lng: curLng };
                }

                if (gpsCourse !== null) {
                    if (!hasCompassSensor) {
                        updateHeadingCone(gpsCourse);
                    } else if (!hasAbsoluteCompass && lastRelativeRawHeading !== null) {
                        // Align relative compass to true-North GPS course while moving
                        relativeCompassOffset = ((gpsCourse - lastRelativeRawHeading) + 360) % 360;
                    }
                }

                // 4. HOUSE + FRONT-OF-HOUSE ARRIVAL EVALUATION (Live marker NEVER freezes)
                const distToZone = destArrivalZone.getDistanceToZone(curLat, curLng);
                if (!isArrived) {
                    if (isInsideHouse || (distToZone <= ARRIVAL_ENTER_BUFFER_M && (!rawAccuracy || rawAccuracy <= 35))) {
                        isArrived = true;
                    }
                } else if (!isInsideHouse && distToZone > ARRIVAL_EXIT_BUFFER_M && (!rawAccuracy || rawAccuracy <= 35)) {
                    isArrived = false;
                    wasArrivedRendered = false;
                }

                if (isArrived) {
                    wasInsideSubdivision = true;
                    renderDestinationZone(true);

                    if (!wasArrivedRendered) {
                        wasArrivedRendered = true;
                        osrmLayerGroup.clearLayers();
                        routingLayerGroup.clearLayers();
                        createGoogleDestPinMarker([destCoord.lat, destCoord.lng], '📍 Destination (' + destinationName + ')');
                    }

                    // Always keep the live GPS beacon moving smoothly in front of / inside the house
                    const arrivedCoord = [curLat, curLng];
                    updateLiveGpsVisuals(arrivedCoord, arrivedCoord, rawAccuracy);

                    if (badgeEl && !badgeEl.classList.contains('arrived')) {
                        badgeEl.className = 'nav-hud-badge arrived';
                        badgeTextEl.textContent = '🎉 You Have Arrived!';
                    }
                    if (distValEl) distValEl.textContent = '0 m';
                    if (timeValEl) timeValEl.textContent = '0 min';
                    if (stepInstEl) stepInstEl.innerHTML = `Welcome to <strong>${destinationName}</strong>! You are right in front of your destination.`;
                    if (simTipEl) {
                        simTipEl.innerHTML = (rawAccuracy && rawAccuracy > 30)
                            ? `📍 Arrived at ${destinationName} • Live GPS active (±${Math.round(rawAccuracy)}m)`
                            : `📍 Arrived at ${destinationName} • Live GPS active`;
                    }
                    renderNavStepsUI(
                        [{ icon: '🏁', instruction: `Arrived at ${destinationName}`, distance: 0, location: [destCoord.lat, destCoord.lng] }],
                        0,
                        0,
                        curLat,
                        curLng
                    );

                    if (!initialFitDone) {
                        initialFitDone = true;
                        map.setView(arrivedCoord, 20);
                        lastPanCoord = arrivedCoord;
                    } else if (autoFollow && (!lastPanCoord || map.distance(lastPanCoord, arrivedCoord) > 2.5)) {
                        map.panTo(arrivedCoord, { animate: true });
                        lastPanCoord = arrivedCoord;
                    }

                    streamVisitorLocation(curLat, curLng, forceGuardPing);
                    return;
                }

                // En route: keep destination house + front-of-house pad highlighted in approach mode
                renderDestinationZone(false);

                const inside = checkIfInside(curLat, curLng);

                if (!inside) {
                    // OUTSIDE HIGHWAY ROUTING (OSRM to Highway-Subdivision Junction + Internal Dijkstra to Destination)
                    if (badgeEl) {
                        badgeEl.className = 'nav-hud-badge';
                        badgeTextEl.textContent = '🚗 Highway Navigation';
                    }

                    const mainRoadJunc = transformCoordPair([-0.001033611863708, -0.002273077993799], calib);
                    const highwayEntryCoord = [mainRoadJunc[1], mainRoadJunc[0]];

                    let tempGraph = baseGraph.clone();
                    const gateSnap = snapPointToRoad(highwayEntryCoord[0], highwayEntryCoord[1], 'gate', tempGraph);
                    const targetDestRoad = destFrontRoadCoord || destCoord;
                    const destSnap = snapPointToRoad(targetDestRoad.lat, targetDestRoad.lng, 'dest', tempGraph);
                    connectSameSegmentSnaps(gateSnap, destSnap, tempGraph);
                    
                    const pathIds = (gateSnap && destSnap) ? tempGraph.dijkstra(gateSnap.nodeId, destSnap.nodeId) : [];
                    const altPathIds = (gateSnap && destSnap) ? tempGraph.alternativePath(gateSnap.nodeId, destSnap.nodeId) : [];

                    const internalRoadLatLngs = [];
                    if (pathIds && pathIds.length > 0) {
                        pathIds.forEach(id => {
                            const pt = coordinatesMap.get(id);
                            if (pt) internalRoadLatLngs.push(pt);
                        });
                    } else if (gateSnap && destSnap) {
                        internalRoadLatLngs.push(gateSnap.point, destSnap.point);
                    }

                    const internalAltLatLngs = [];
                    if (altPathIds && altPathIds.length > 0) {
                        altPathIds.forEach(id => {
                            const pt = coordinatesMap.get(id);
                            if (pt) internalAltLatLngs.push(pt);
                        });
                    }

                    const osrmTarget = internalRoadLatLngs.length > 0 ? internalRoadLatLngs[0] : highwayEntryCoord;
                    const osrm = await fetchOSRM(curLat, curLng, osrmTarget[0], osrmTarget[1]);
                    if (mySeq !== latestPositionSeq) return; // Discard stale async OSRM response

                    // MERGE INTO CONTINUOUS PRIMARY ROAD ROUTE
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

                    // MERGE CONTINUOUS ALTERNATIVE ROUTE (Highway detour OR Internal alternative street)
                    let fullAltLatLngs = null;
                    if (osrm && osrm.alternative && Array.isArray(osrm.alternative.latlngs)) {
                        fullAltLatLngs = [];
                        osrm.alternative.latlngs.forEach(pt => fullAltLatLngs.push(pt));
                        const connectingInternal = (internalAltLatLngs.length > 0) ? internalAltLatLngs : internalRoadLatLngs;
                        connectingInternal.forEach((pt, idx) => {
                            if (idx === 0 && fullAltLatLngs.length > 0) {
                                const prev = fullAltLatLngs[fullAltLatLngs.length - 1];
                                if (map.distance(prev, pt) < 0.5) return;
                            }
                            fullAltLatLngs.push(pt);
                        });
                    } else if (internalAltLatLngs.length > 0) {
                        fullAltLatLngs = [];
                        if (osrm && Array.isArray(osrm.latlngs)) {
                            osrm.latlngs.forEach(pt => fullAltLatLngs.push(pt));
                        }
                        internalAltLatLngs.forEach((pt, idx) => {
                            if (idx === 0 && fullAltLatLngs.length > 0) {
                                const prev = fullAltLatLngs[fullAltLatLngs.length - 1];
                                if (map.distance(prev, pt) < 0.5) return;
                            }
                            fullAltLatLngs.push(pt);
                        });
                    }

                    routingLayerGroup.clearLayers();
                    osrmLayerGroup.clearLayers();

                    // Compute Google Maps-style driving duration (realistic OSRM road duration + internal subdivision time)
                    const calcPathDist = (pts) => {
                        let d = 0;
                        for (let i = 0; i < pts.length - 1; i++) d += map.distance(pts[i], pts[i + 1]);
                        return d;
                    };
                    const internalPrimaryDist = calcPathDist(internalRoadLatLngs);
                    const internalAltDist = internalAltLatLngs.length > 0 ? calcPathDist(internalAltLatLngs) : internalPrimaryDist;
                    const primaryHwyDist = (osrm && Number.isFinite(osrm.distance)) ? osrm.distance : calcPathDist(osrm?.latlngs || []);
                    const primaryHwyDur = (osrm && Number.isFinite(osrm.duration)) ? osrm.duration : (primaryHwyDist / 8.33);
                    const primaryHwySteps = (osrm && Array.isArray(osrm.steps)) ? osrm.steps.length : 0;

                    const altHwyDist = (osrm && osrm.alternative && Number.isFinite(osrm.alternative.distance)) ? osrm.alternative.distance : primaryHwyDist;
                    const altHwyDur = (osrm && osrm.alternative && Number.isFinite(osrm.alternative.duration)) ? osrm.alternative.duration : primaryHwyDur;
                    const altHwySteps = (osrm && osrm.alternative && Array.isArray(osrm.alternative.steps)) ? osrm.alternative.steps.length : primaryHwySteps;

                    const primaryTotalTime = estimateGoogleMapsDuration(primaryHwyDur, primaryHwyDist, internalPrimaryDist, 0, primaryHwySteps);
                    const altTotalTime = fullAltLatLngs
                        ? estimateGoogleMapsDuration(
                            altHwyDur,
                            altHwyDist,
                            (osrm && osrm.alternative ? internalPrimaryDist : internalAltDist),
                            0,
                            altHwySteps
                        )
                        : null;
                    const secondaryTimeText = fullAltLatLngs
                        ? fmtTime(preferAlternative ? primaryTotalTime : altTotalTime)
                        : '';

                    const activeRoute = renderDualRoute(fullRouteLatLngs, fullAltLatLngs, () => {
                        handlePosition(rawLat, rawLng, rawAccuracy, headingVal);
                    }, { secondaryTimeText });

                    // Outside subdivision roads: NEVER magnet/snap user marker to highway; keep true filtered GPS position
                    const displayCoord = [curLat, curLng];
                    const roadEntryPt = (activeRoute && activeRoute.length > 0) ? activeRoute[0] : null;
                    const offRoadGap = roadEntryPt ? map.distance(displayCoord, roadEntryPt) : 0;
                    if (roadEntryPt && offRoadGap > 4.0) {
                        drawGoogleDottedConnector(displayCoord, roadEntryPt, routingLayerGroup);
                    }

                    updateLiveGpsVisuals(displayCoord, [curLat, curLng], rawAccuracy);

                    if (simTipEl) {
                        if (offRoadGap > 6.0) {
                            simTipEl.innerHTML = '🚶 Head to nearest road • Dotted line shows walking path';
                        } else {
                            simTipEl.innerHTML = (preferAlternative && fullAltLatLngs)
                                ? '🛰️ Highway Navigation • Alternative route selected'
                                : '🛰️ Highway Navigation Active • En route to Althesa Residences';
                        }
                    }

                    if (autoFollow) {
                        if (!lastPanCoord || map.distance(lastPanCoord, displayCoord) > 4.0) {
                            map.panTo(displayCoord, { animate: true });
                            lastPanCoord = displayCoord;
                        }
                    }

                    // Dotted grey walking line to house (Google Maps style)
                    if (destSnap && map.distance(destSnap.point, [destCoord.lat, destCoord.lng]) > 1.8) {
                        drawGoogleDottedConnector([destCoord.lat, destCoord.lng], destSnap.point, routingLayerGroup);
                    }

                    createGoogleDestPinMarker([destCoord.lat, destCoord.lng], '📍 Destination (' + destinationName + ')');

                    // Compute active distance and duration
                    let totalDist = offRoadGap > 4.0 ? offRoadGap : 0;
                    for (let i = 0; i < activeRoute.length - 1; i++) {
                        totalDist += map.distance(activeRoute[i], activeRoute[i + 1]);
                    }
                    const dOffDist = destSnap ? map.distance(destSnap.point, [destCoord.lat, destCoord.lng]) : 0;
                    totalDist += dOffDist;
                    const totalTime = (preferAlternative && altTotalTime !== null)
                        ? altTotalTime + ((offRoadGap > 4.0 ? offRoadGap : 0) / 1.4) + (dOffDist / 1.4)
                        : primaryTotalTime + ((offRoadGap > 4.0 ? offRoadGap : 0) / 1.4) + (dOffDist / 1.4);

                    if (distValEl) distValEl.textContent = fmtDist(totalDist);
                    if (timeValEl) timeValEl.textContent = fmtTime(totalTime);
                    if (stepInstEl) {
                        const hwyPrefix = (preferAlternative && fullAltLatLngs) ? 'Alternative: ' : '';
                        stepInstEl.innerHTML = `${hwyPrefix}Head towards <strong>Althesa Entrance Gate</strong>`;
                    }

                    // Build combined OSRM highway steps + internal subdivision steps
                    const useAltHwy = preferAlternative && osrm && osrm.alternative;
                    const hwySteps = useAltHwy
                        ? (osrm.alternative.steps || [])
                        : ((osrm && osrm.steps) ? osrm.steps : []);
                    const rawIntPts = (preferAlternative && internalAltLatLngs.length > 0) ? internalAltLatLngs : internalRoadLatLngs;
                    const intSteps = buildInternalSteps(rawIntPts, destCoord, destinationName, true);
                    const combinedSteps = (hwySteps.length > 0)
                        ? [...hwySteps, ...intSteps]
                        : [
                            { icon: '🚗', instruction: 'Head towards Althesa Entrance Gate', distance: osrm ? osrm.distance : totalDist, location: [curLat, curLng], source: 'osrm' },
                            ...intSteps
                        ];
                    renderNavStepsUI(combinedSteps, totalDist, totalTime, curLat, curLng);

                    if (!initialFitDone) {
                        initialFitDone = true;
                        map.fitBounds(L.latLngBounds([displayCoord, entranceCoord, [destCoord.lat, destCoord.lng]]), { padding: [45, 45] });
                    }

                } else {
                    // INSIDE SUBDIVISION NAVIGATION (Google Maps Architecture: Clean Road Polyline + Dotted Off-Road Connector)
                    osrmLayerGroup.clearLayers();
                    routingLayerGroup.clearLayers();

                    if (badgeEl) {
                        badgeEl.className = 'nav-hud-badge inside';
                        badgeTextEl.textContent = '🏡 Inside Althesa Village';
                    }

                    let liveGraph = baseGraph.clone();
                    const userSnap = snapPointToRoad(curLat, curLng, 'u', liveGraph);
                    const targetDestRoad = destFrontRoadCoord || destCoord;
                    const destSnap = snapPointToRoad(targetDestRoad.lat, targetDestRoad.lng, 'd', liveGraph);
                    connectSameSegmentSnaps(userSnap, destSnap, liveGraph);

                    const livePathIds = (userSnap && destSnap) ? liveGraph.dijkstra(userSnap.nodeId, destSnap.nodeId) : [];
                    const liveAltPathIds = (userSnap && destSnap) ? liveGraph.alternativePath(userSnap.nodeId, destSnap.nodeId) : [];

                    const roadLatLngs = [];
                    if (livePathIds && livePathIds.length > 0) {
                        livePathIds.forEach(id => {
                            const pt = coordinatesMap.get(id);
                            if (pt) roadLatLngs.push(pt);
                        });
                    } else if (userSnap && destSnap) {
                        roadLatLngs.push(userSnap.point, destSnap.point);
                    }

                    const altRoadLatLngs = [];
                    if (liveAltPathIds && liveAltPathIds.length > 0) {
                        liveAltPathIds.forEach(id => {
                            const pt = coordinatesMap.get(id);
                            if (pt) altRoadLatLngs.push(pt);
                        });
                    }

                    const calcInternalDist = (pts) => {
                        let d = 0;
                        for (let i = 0; i < pts.length - 1; i++) d += map.distance(pts[i], pts[i + 1]);
                        return d;
                    };
                    const primaryIntDist = calcInternalDist(roadLatLngs);
                    const altIntDist = altRoadLatLngs.length > 1 ? calcInternalDist(altRoadLatLngs) : 0;
                    const secondaryIntTimeText = altRoadLatLngs.length > 1
                        ? fmtTime(estimateGoogleMapsDuration(0, 0, preferAlternative ? primaryIntDist : altIntDist))
                        : '';

                    // Render active + alternative routes
                    const activeRoadLatLngs = renderDualRoute(roadLatLngs, altRoadLatLngs, () => {
                        handlePosition(rawLat, rawLng, rawAccuracy, headingVal);
                    }, { secondaryTimeText: secondaryIntTimeText });

                    // MAP MATCHING: Lock to internal subdivision road when within the driving street corridor
                    const isOnRoad = userSnap && (userSnap.dist <= ROAD_CORRIDOR_METERS) && (!rawAccuracy || rawAccuracy <= 25);
                    const displayCoord = (isOnRoad && userSnap) ? userSnap.point : [curLat, curLng];

                    updateLiveGpsVisuals(displayCoord, [curLat, curLng], rawAccuracy);

                    if (simTipEl) {
                        const altNotice = (preferAlternative && altRoadLatLngs.length > 0) ? ' • Alt route' : '';
                        simTipEl.innerHTML = isOnRoad
                            ? `🛣️ Road-Lock Active • Street centerline${altNotice}`
                            : `🚶 Walking Guide Active • Dotted line to street${altNotice}`;
                    }

                    if (autoFollow) {
                        if (!lastPanCoord || map.distance(lastPanCoord, displayCoord) > 3.0) {
                            map.panTo(displayCoord, { animate: true });
                            lastPanCoord = displayCoord;
                        }
                    }

                    let roadDist = 0;
                    for (let i = 0; i < activeRoadLatLngs.length - 1; i++) {
                        roadDist += map.distance(activeRoadLatLngs[i], activeRoadLatLngs[i + 1]);
                    }
                    const uOffDist = (!isOnRoad && userSnap) ? userSnap.dist : 0;
                    const dOffDist = destSnap ? map.distance(destSnap.point, [destCoord.lat, destCoord.lng]) : 0;
                    const remainingDist = roadDist + uOffDist + dOffDist;

                    // 2. Google Maps-style Off-Road Dotted Line when off the subdivision road corridor
                    if (!isOnRoad && userSnap && userSnap.dist > 2.5) {
                        drawGoogleDottedConnector([curLat, curLng], userSnap.point, routingLayerGroup);
                    }

                    // 3. Off-Road Walking Dotted Grey Line from Front Road to Destination House Lot
                    if (destSnap && map.distance(destSnap.point, [destCoord.lat, destCoord.lng]) > 1.8) {
                        drawGoogleDottedConnector([destCoord.lat, destCoord.lng], destSnap.point, routingLayerGroup);
                    }

                    // 4. Google Maps Red Location Pin at House
                    createGoogleDestPinMarker([destCoord.lat, destCoord.lng], '📍 Destination (' + destinationName + ')');

                    const remainingDuration = estimateGoogleMapsDuration(0, 0, roadDist, uOffDist + dOffDist);
                    if (distValEl) distValEl.textContent = fmtDist(remainingDist);
                    if (timeValEl) timeValEl.textContent = fmtTime(remainingDuration);
                    if (stepInstEl) {
                        const pathTypeLabel = (preferAlternative && altRoadLatLngs.length > 0) ? 'Alternative street' : 'Follow internal street';
                        stepInstEl.innerHTML = `${pathTypeLabel} towards <strong>${destinationName}</strong>`;
                    }

                    const rawActiveInternal = (preferAlternative && altRoadLatLngs.length > 0) ? altRoadLatLngs : roadLatLngs;
                    const insideSteps = [];
                    if (!isOnRoad && userSnap && userSnap.dist > ROAD_CORRIDOR_METERS) {
                        insideSteps.push({
                            icon: '🚶',
                            instruction: 'Head to the subdivision street',
                            distance: userSnap.dist,
                            location: [curLat, curLng],
                            source: 'internal'
                        });
                    }
                    insideSteps.push(...buildInternalSteps(rawActiveInternal, destCoord, destinationName, true));
                    renderNavStepsUI(insideSteps, remainingDist, remainingDuration, curLat, curLng);

                    if (!initialFitDone) {
                        initialFitDone = true;
                        map.setView(displayCoord, 19);
                    }
                }

                // If visitorToken is provided, stream live coordinates & arrival status to guard portal
                streamVisitorLocation(curLat, curLng, forceGuardPing);
            }

            function onRecenterClick(e) {
                e.stopPropagation();
                autoFollow = true;
                if (liveGpsMarker) {
                    const pos = liveGpsMarker.getLatLng();
                    map.panTo([pos.lat, pos.lng], { animate: true });
                    lastPanCoord = [pos.lat, pos.lng];
                } else if (userPos) {
                    map.panTo([userPos.lat, userPos.lng], { animate: true });
                    lastPanCoord = [userPos.lat, userPos.lng];
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
                    { enableHighAccuracy: true, timeout: 12000, maximumAge: 1000 }
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
                    if (headingRafId) {
                        cancelAnimationFrame(headingRafId);
                        headingRafId = null;
                    }
                    if (recenterBtn) {
                        recenterBtn.removeEventListener('click', onRecenterClick);
                    }
                    if (absSensorInstance && typeof absSensorInstance.stop === 'function') {
                        try { absSensorInstance.stop(); } catch (e) {}
                        absSensorInstance = null;
                    }
                    if (window.DeviceOrientationEvent) {
                        window.removeEventListener('deviceorientationabsolute', onDeviceOrientation, false);
                        window.removeEventListener('deviceorientation', onDeviceOrientation, false);
                    }
                    map.off('rotate', syncHeadingConeToMapBearing);
                    map.off('dragstart', onMapDragStart);
                    map.off('zoomend', onZoomEndUpdateWeights);
                    if (destHouseLayer && typeof destHouseLayer.setStyle === 'function') {
                        destHouseLayer.setStyle({ fillColor: '#2563eb', color: '#1d4ed8', weight: 2.5, fillOpacity: 0.95 });
                    }
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

            // Match the exact destination house polygon layer (choosing closest to destCoord if duplicate B/L exists)
            let destHouseLayer = null;
            let destHouseRing = null;
            if (houseLayer) {
                let bestMatchDist = Infinity;
                houseLayer.eachLayer(layer => {
                    if (layer.feature && layer.feature.properties && layer.feature.properties.block_num) {
                        const id = `B${layer.feature.properties.block_num} L${layer.feature.properties.lot_number}`;
                        if (id === config.endNode) {
                            const center = layer.getBounds().getCenter();
                            if (!destCoord) {
                                destCoord = center;
                            }
                            const d = map.distance([destCoord.lat, destCoord.lng], [center.lat, center.lng]);
                            if (d < bestMatchDist) {
                                bestMatchDist = d;
                                destHouseLayer = layer;
                            }
                        }
                    }
                });

                if (destHouseLayer && destHouseLayer.feature && destHouseLayer.feature.geometry) {
                    const geom = destHouseLayer.feature.geometry;
                    const rawRing = geom.type === 'MultiPolygon'
                        ? (geom.coordinates[0] && geom.coordinates[0][0])
                        : (geom.type === 'Polygon' ? geom.coordinates[0] : null);
                    if (Array.isArray(rawRing) && rawRing.length >= 3) {
                        destHouseRing = rawRing
                            .filter(c => Array.isArray(c) && c.length >= 2)
                            .map(c => [c[1], c[0]]);
                    }
                }
            }

            let destRoadPoint = null;
            if (config.endNode && localHouseFrontRoad && localHouseFrontRoad[config.endNode]) {
                const lfr = localHouseFrontRoad[config.endNode];
                const trans = transformCoordPair([lfr.lng, lfr.lat], calib);
                const candidateRoadPt = L.latLng(trans[1], trans[0]);
                // Guard against duplicate block/lot mismatch by confirming road point is within 25m of destCoord
                if (!destCoord || map.distance([destCoord.lat, destCoord.lng], [candidateRoadPt.lat, candidateRoadPt.lng]) <= 25) {
                    destRoadPoint = candidateRoadPt;
                }
            }
            if (!destRoadPoint && destCoord && segments.length > 0) {
                const destPt = [destCoord.lat, destCoord.lng];
                let minRoadD = Infinity;
                let bestRoadProj = null;
                for (let i = 0; i < segments.length; i++) {
                    const res = projectPointToSegment(destPt, segments[i].p1, segments[i].p2);
                    if (res.dist < minRoadD) {
                        minRoadD = res.dist;
                        bestRoadProj = res.point;
                    }
                }
                if (bestRoadProj) {
                    destRoadPoint = L.latLng(bestRoadProj[0], bestRoadProj[1]);
                }
            }

            if (startNodeId && destCoord) {
                let tempGraph = graph.clone();
                const targetDestSnap = destRoadPoint || destCoord;
                let endNodeId = injectSnappedNode(targetDestSnap.lat, targetDestSnap.lng, 'end', tempGraph);

                const pathNodeIds = tempGraph.dijkstra(startNodeId, endNodeId);
                const altPathNodeIds = tempGraph.alternativePath(startNodeId, endNodeId);

                // Step 3 & 4: Live Hybrid Navigation (OSRM highway + live snapped Dijkstra)
                if (config.liveNavigation) {
                    startLiveHybridNavigation({
                        entranceCoord: entranceCoord,
                        destCoord: destCoord,
                        destFrontRoadCoord: destRoadPoint,
                        destHouseRing: destHouseRing,
                        destHouseLayer: destHouseLayer,
                        graph: graph,
                        coordinatesMap: coordinatesMap,
                        segments: segments,
                        injectSnappedNode: injectSnappedNode,
                        staticPathIds: pathNodeIds,
                        staticAltPathIds: altPathNodeIds,
                        styles: styles,
                        destinationName: config.destinationName || config.endNode,
                        subdivisionHull: computeSubdivisionHull()
                    });
                } else {
                    // Static fallback rendering for guard dashboard (Solid line on road + dotted off-road to house)
                    if (altPathNodeIds.length > 0) {
                        const rawAlt = altPathNodeIds.map(id => coordinatesMap.get(id)).filter(Boolean);
                        const altLatlngs = smoothTurnRoute(rawAlt, 3.5);
                        L.polyline(altLatlngs, styles.altRoute).addTo(routingLayerGroup);
                    }

                    if (pathNodeIds.length > 0) {
                        const rawLatlngs = pathNodeIds.map(id => coordinatesMap.get(id)).filter(Boolean);
                        const latlngs = smoothTurnRoute(rawLatlngs, 3.5);
                        const routeLine = L.polyline(latlngs, styles.route).addTo(routingLayerGroup);

                        const lastRoadPt = latlngs[latlngs.length - 1];
                        if (lastRoadPt && map.distance(lastRoadPt, [destCoord.lat, destCoord.lng]) > 1.8) {
                            drawGoogleDottedConnector([destCoord.lat, destCoord.lng], lastRoadPt, routingLayerGroup);
                        }

                        createGoogleDestPinMarker([destCoord.lat, destCoord.lng], 'Destination (' + config.endNode + ')');

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
                const rawAlt = altPathNodeIds.map(id => coordinatesMap.get(id)).filter(Boolean);
                const altLatlngs = smoothTurnRoute(rawAlt, 3.5);
                L.polyline(altLatlngs, styles.altRoute).addTo(routingLayerGroup);
            }

            const pathNodeIds = tempGraph.dijkstra(sNode, eNode);
            if (pathNodeIds.length > 0) {
                const rawLatlngs = pathNodeIds.map(id => coordinatesMap.get(id)).filter(Boolean);
                const latlngs = smoothTurnRoute(rawLatlngs, 3.5);
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

    renderAllGISLayers();

    map.clearRouting = function () {
        if (houseLayer) houseLayer.resetStyle();
        routingLayerGroup.clearLayers();
        routeEndHouse = null;
        if (config.fixedStartNode && routeStartHouse) {
            if (map._fixedStartLayer) {
                map._fixedStartLayer.setStyle({ fillColor: '#10b981', color: '#059669', weight: 2.5, fillOpacity: 0.95 });
            }
            L.circleMarker([routeStartHouse.coord.lat, routeStartHouse.coord.lng], {
                radius: 7, fillColor: '#10b981', color: '#fff', weight: 2.5, fillOpacity: 1
            }).addTo(routingLayerGroup).bindTooltip(`Your House (${routeStartHouse.id})`);
        } else {
            routeStartHouse = null;
        }
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
