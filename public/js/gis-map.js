// Common GIS Map Initialization Script

// Inject CSS to remove the Leaflet interactive focus outline (removes black border on houses)
const style = document.createElement('style');
style.innerHTML = `
    path.leaflet-interactive:focus {
        outline: none;
    }
`;
document.head.appendChild(style);

async function initGISMap(mapElementId, options = {}) {
    // Default options
    const config = Object.assign({
        interactive: true,
        showRouting: false,
        interactiveRouting: false, // New feature for residents
        startNode: null, // Entrance node ID or House ID
        endNode: null, // House node ID (Block-Lot)
        adminView: false, // Flag to enable admin color logic
    }, options);

    const map = L.map(mapElementId, {
        zoomControl: config.interactive,
        dragging: config.interactive,
        scrollWheelZoom: config.interactive,
        doubleClickZoom: config.interactive,
        rotate: true,
        bearing: config.bearing !== undefined ? config.bearing : -15,
        rotateControl: {
            closeOnZeroBearing: false,
            position: 'topleft'
        },
    }).setView([13.6268, 123.1906], 18); // Default coords, will adjust bounds later

    // Base Style Configurations
    const styles = {
        bufferedRoad: {
            color: '#94a3b8',
            weight: 1,
            fillColor: '#cbd5e1',
            fillOpacity: 1
        },
        house: (feature) => {
            const b = feature.properties.block_num;
            const l = feature.properties.lot_number;
            const key = `B${b} L${l}`;
            const dbLot = window.globalDbLots ? window.globalDbLots[key] : null;

            let fillColor = '#e7e8eb'; // default
            let color = '#94a3b8';

            if (dbLot && config.adminView) {
                if (dbLot.status === 'Occupied') { fillColor = '#ffffff'; color = '#000000'; }
                else if (dbLot.status === 'Vacant House') { fillColor = '#3b82f6'; color = '#2563eb'; }
                else if (dbLot.status === 'Vacant Lot') { fillColor = '#f59e0b'; color = '#d97706'; }
                else if (dbLot.status === 'Reserved') { fillColor = '#8b5cf6'; color = '#7c3aed'; }
                else if (dbLot.status === 'Under Construction') { fillColor = '#ef4444'; color = '#dc2626'; }
            }

            return {
                color: color,
                weight: 1,
                fillColor: fillColor,
                fillOpacity: 1
            };
        },
        amenity: (feature) => {
            const name = feature.properties.name || feature.properties.NAME || 'Unknown';
            let fillColor = '#e7e8eb'; // default
            if (name.toLowerCase().includes('guard')) fillColor = '#e11d48'; // GuardHouse
            else if (name.toLowerCase().includes('open')) fillColor = '#eae0d5'; // OpenSpace
            else if (name.toLowerCase().includes('basketball')) fillColor = '#d97706'; // BasketballCourt
            else if (name.toLowerCase().includes('grass')) fillColor = '#a7f3d0'; // Grass
            else if (name.toLowerCase().includes('background')) fillColor = '#f6f5f5'; // Backgrounds

            return {
                color: '#cbd5e1',
                weight: 1,
                fillColor: fillColor,
                fillOpacity: 1
            };
        },
        route: {
            color: '#2563eb',
            weight: 6,
            opacity: 0.8,
            dashArray: '10, 10',
            lineJoin: 'round'
        }
    };

    // Load GeoJSONs and Database Lots
    const [amenities, roads, houses, network, entrance, houseNodes, lotsData] = await Promise.all([
        fetch('/gis-data/amenities.geojson').then(r => r.json()),
        fetch('/gis-data/buffered_road_design.geojson').then(r => r.json()),
        fetch('/gis-data/houses.geojson').then(r => r.json()),
        fetch('/gis-data/roads.geojson').then(r => r.json()),
        fetch('/gis-data/entrance_point.geojson').then(r => r.json()).catch(e => ({})),
        fetch('/gis-data/house_nodes.geojson').then(r => r.json()).catch(e => ({})),
        fetch('/api/lots').then(r => r.ok ? r.json() : []).catch(e => [])
    ]);

    window.globalDbLots = {};
    if (lotsData && Array.isArray(lotsData)) {
        lotsData.forEach(lot => {
            window.globalDbLots[`B${lot.block} L${lot.lot_number}`] = lot;
        });
    }

    // Add layers to map
    const amenityLayer = L.geoJSON(amenities, { style: styles.amenity }).addTo(map);
    const roadLayer = L.geoJSON(roads, { style: styles.bufferedRoad }).addTo(map);

    // Add houses and attach tooltips
    let routeStartHouse = null;
    let routeEndHouse = null;
    let currentRouteLine = null;
    let currentMarkers = [];

    function getHouseNodeCoord(block, lot) {
        if (!houseNodes || !houseNodes.features) return null;
        for (let feature of houseNodes.features) {
            if (feature.properties.block_num == block && feature.properties.lot_number == lot) {
                const c = feature.geometry.coordinates;
                return L.latLng(c[1], c[0]); // Leaflet uses Lat, Lng
            }
        }
        return null;
    }

    const houseLayer = L.geoJSON(houses, {
        style: styles.house,
        onEachFeature: (feature, layer) => {
            if (feature.properties.block_num && feature.properties.lot_number) {
                const houseId = `B${feature.properties.block_num} L${feature.properties.lot_number}`;
                layer.bindTooltip(`Block ${feature.properties.block_num}, Lot ${feature.properties.lot_number}`);

                // If it's the destination house, highlight it
                if (config.endNode && houseId === config.endNode) {
                    layer.setStyle({ fillColor: '#2563eb', color: '#1d4ed8', weight: 2 });
                }

                if (config.interactiveRouting) {
                    layer.on('click', () => {
                        let coord = getHouseNodeCoord(feature.properties.block_num, feature.properties.lot_number);
                        if (!coord) coord = layer.getBounds().getCenter();

                        if (!routeStartHouse) {
                            routeStartHouse = { id: houseId, coord: coord };
                            layer.setStyle({ fillColor: '#10b981', color: '#059669', weight: 2 });
                        } else if (!routeEndHouse) {
                            routeEndHouse = { id: houseId, coord: coord };
                            layer.setStyle({ fillColor: '#2563eb', color: '#1d4ed8', weight: 2 });
                            drawInteractiveRoute();
                        } else {
                            // Reset
                            houseLayer.resetStyle();
                            if (currentRouteLine) map.removeLayer(currentRouteLine);
                            currentMarkers.forEach(m => map.removeLayer(m));
                            currentMarkers = [];
                            routeStartHouse = { id: houseId, coord: coord };
                            routeEndHouse = null;
                            layer.setStyle({ fillColor: '#10b981', color: '#059669', weight: 2 });
                        }
                    });
                }
            }
        }
    }).addTo(map);

    // Auto-fit map to houses bounds
    map.fitBounds(houseLayer.getBounds());

    // Routing Logic
    if (config.showRouting && network) {
        const graph = new DijkstraGraph();
        let coordinatesMap = new Map();

        // Helper: Project point to line segment
        function projectPointToSegment(pt, v, w) {
            const l2 = Math.pow(v[0] - w[0], 2) + Math.pow(v[1] - w[1], 2);
            if (l2 === 0) return { point: v, dist: map.distance(pt, v) };
            
            let t = ((pt[0] - v[0]) * (w[0] - v[0]) + (pt[1] - v[1]) * (w[1] - v[1])) / l2;
            t = Math.max(0, Math.min(1, t));
            
            const proj = [ v[0] + t * (w[0] - v[0]), v[1] + t * (w[1] - v[1]) ];
            return { point: proj, dist: map.distance(pt, proj) };
        }

        // Build Graph from network LineStrings/MultiLineStrings
        let segments = [];
        let lineEndpoints = [];
        
        L.geoJSON(network, {
            onEachFeature: (feature, layer) => {
                const addCoordsToGraph = (coords) => {
                    if(coords.length === 0) return;
                    lineEndpoints.push(coords[0].join(','));
                    lineEndpoints.push(coords[coords.length-1].join(','));
                    
                    for (let i = 0; i < coords.length - 1; i++) {
                        const p1 = coords[i];
                        const p2 = coords[i+1];
                        const id1 = p1.join(',');
                        const id2 = p2.join(',');
                        
                        coordinatesMap.set(id1, [p1[1], p1[0]]); // Leaflet uses Lat, Lng
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
                    feature.geometry.coordinates.forEach(coords => {
                        addCoordsToGraph(coords);
                    });
                }
            }
        });

        // Bridge gaps up to ~2 meters for disconnected GIS data
        const SNAP_TOLERANCE = 2; // meters
        lineEndpoints.forEach(endId => {
            if(!coordinatesMap.has(endId)) return;
            const pt1 = coordinatesMap.get(endId);
            coordinatesMap.forEach((pt2, id2) => {
                if (endId !== id2) {
                    const d = map.distance(pt1, pt2);
                    if (d < SNAP_TOLERANCE) {
                        let exists = false;
                        if (graph.nodes.has(endId)) {
                            for (let e of graph.nodes.get(endId).edges) {
                                if (e.node === id2) {
                                    exists = true;
                                    break;
                                }
                            }
                        }
                        if (!exists) {
                            graph.addEdge(endId, id2, d);
                        }
                    }
                }
            });
        });

        // Function to inject a dynamic node at the closest projected point on the road
        function injectSnappedNode(lat, lng, prefix, targetGraph) {
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
                const newId = `${prefix}_${closestProj[0].toFixed(6)},${closestProj[1].toFixed(6)}`;
                coordinatesMap.set(newId, closestProj);
                if(!targetGraph.nodes.has(newId)) targetGraph.addNode(newId);
                
                const dist1 = map.distance(closestSegment.p1, closestProj);
                const dist2 = map.distance(closestProj, closestSegment.p2);
                
                targetGraph.addEdge(closestSegment.id1, newId, dist1);
                targetGraph.addEdge(newId, closestSegment.id2, dist2);
                
                const targetId = `${prefix}_target`;
                coordinatesMap.set(targetId, pt);
                targetGraph.addNode(targetId);
                targetGraph.addEdge(targetId, newId, minDist);
                return targetId;
            }
            return null;
        }

        // If there's an endNode provided automatically (Guard/Visitor view)
        if (config.endNode) {
            // Find Start Node (Guard House / Entrance approximation)
            let entranceCoord = [13.626296, 123.190161]; // Default fallback
            if (entrance && entrance.features && entrance.features.length > 0) {
                const c = entrance.features[0].geometry.coordinates;
                entranceCoord = [c[1], c[0]]; // GeoJSON is [Lng, Lat], Leaflet uses [Lat, Lng]
            }

            let startNodeId = injectSnappedNode(entranceCoord[0], entranceCoord[1], 'start', graph);

            // Find End Node (Destination House)
            let destCoord = null;
            if (houseNodes && houseNodes.features) {
                for (let feature of houseNodes.features) {
                    if (feature.properties.block_num && feature.properties.lot_number) {
                        const id = `B${feature.properties.block_num} L${feature.properties.lot_number}`;
                        if (id === config.endNode) {
                            const c = feature.geometry.coordinates;
                            destCoord = L.latLng(c[1], c[0]); // y is lat, x is lng
                            break;
                        }
                    }
                }
            }

            if (!destCoord) {
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

                // Run Dijkstra
                const pathNodeIds = tempGraph.dijkstra(startNodeId, endNodeId);

                if (pathNodeIds.length > 0) {
                    const latlngs = pathNodeIds.map(id => coordinatesMap.get(id));
                    // Add a final segment to the actual house center
                    latlngs.push([destCoord.lat, destCoord.lng]);

                    const routeLine = L.polyline(latlngs, styles.route).addTo(map);

                    // Add Markers
                    L.circleMarker(coordinatesMap.get(startNodeId), { radius: 6, fillColor: '#10b981', color: '#fff', weight: 2, fillOpacity: 1 }).addTo(map).bindTooltip('Entrance Gate');
                    L.circleMarker([destCoord.lat, destCoord.lng], { radius: 8, fillColor: '#2563eb', color: '#fff', weight: 3, fillOpacity: 1 }).addTo(map).bindTooltip('Destination');

                    if (!config.interactive) {
                        map.fitBounds(routeLine.getBounds(), { padding: [30, 30] });
                    }
                }
            }
        }

        // Expose interactive routing draw function
        function drawInteractiveRoute() {
            if (!routeStartHouse || !routeEndHouse || !network) return;

            if (currentRouteLine) map.removeLayer(currentRouteLine);
            currentMarkers.forEach(m => map.removeLayer(m));
            currentMarkers = [];

            let tempGraph = graph.clone();
            let sNode = injectSnappedNode(routeStartHouse.coord.lat, routeStartHouse.coord.lng, 'dynamicStart', tempGraph);
            let eNode = injectSnappedNode(routeEndHouse.coord.lat, routeEndHouse.coord.lng, 'dynamicEnd', tempGraph);

            const pathNodeIds = tempGraph.dijkstra(sNode, eNode);
            if (pathNodeIds.length > 0) {
                const latlngs = pathNodeIds.map(id => coordinatesMap.get(id));
                latlngs.unshift([routeStartHouse.coord.lat, routeStartHouse.coord.lng]);
                latlngs.push([routeEndHouse.coord.lat, routeEndHouse.coord.lng]);

                currentRouteLine = L.polyline(latlngs, styles.route).addTo(map);

                const m1 = L.circleMarker([routeStartHouse.coord.lat, routeStartHouse.coord.lng], { radius: 6, fillColor: '#10b981', color: '#fff', weight: 2, fillOpacity: 1 }).addTo(map).bindTooltip('Start');
                const m2 = L.circleMarker([routeEndHouse.coord.lat, routeEndHouse.coord.lng], { radius: 8, fillColor: '#2563eb', color: '#fff', weight: 3, fillOpacity: 1 }).addTo(map).bindTooltip('Destination');
                currentMarkers.push(m1, m2);
            }
        }
    }

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
