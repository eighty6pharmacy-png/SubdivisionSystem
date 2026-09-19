window.gisBlockLotMap = null;

async function fetchGisBlockLotMap() {
    if (window.gisBlockLotMap) return window.gisBlockLotMap;
    try {
        const [geojsonResp, apiResp] = await Promise.all([
            fetch('/gis-data/houses.geojson').catch(e => ({})),
            fetch('/api/lots').then(r => r.ok ? r.json() : []).catch(e => [])
        ]);
        
        let data = {};
        if (geojsonResp && typeof geojsonResp.json === 'function') {
            data = await geojsonResp.json().catch(e => ({}));
        }

        const apiData = apiResp || [];
        
        // Build a quick lookup for api data
        const dbLots = {};
        if (Array.isArray(apiData)) {
            apiData.forEach(lot => {
                dbLots[`B${lot.block} L${lot.lot_number}`] = lot;
            });
        }

        const map = {};
        if (data.features) {
            data.features.forEach(f => {
                const b = f.properties.block_num;
                const l = f.properties.lot_number;
                if (b !== null && l !== null && b !== undefined && l !== undefined) {
                    if (!map[b]) map[b] = [];
                    
                    const key = `B${b} L${l}`;
                    const dbLot = dbLots[key];
                    
                    // Determine if available. It's unavailable if occupied/reserved/sold or user_id is set.
                    let available = true;
                    if (dbLot) {
                        if (dbLot.status !== 'Available' && dbLot.status !== 'Vacant Lot') {
                            available = false;
                        }
                        if (dbLot.isNotConnected === false) { // has a user
                            available = false;
                        }
                    }
                    
                    const existingLot = map[b].find(item => item.lot === l);
                    if (!existingLot) {
                        map[b].push({ lot: l, available: available });
                    }
                }
            });
        }
        
        // Sort blocks and lots
        const sortedMap = {};
        const blockKeys = Object.keys(map).sort((a, b) => {
            const numA = parseInt(a);
            const numB = parseInt(b);
            if (!isNaN(numA) && !isNaN(numB)) return numA - numB;
            return a.localeCompare(b);
        });

        blockKeys.forEach(b => {
            sortedMap[b] = map[b].sort((a, b) => {
                const numA = parseInt(a.lot);
                const numB = parseInt(b.lot);
                if (!isNaN(numA) && !isNaN(numB)) return numA - numB;
                return String(a.lot).localeCompare(String(b.lot));
            });
        });
        
        window.gisBlockLotMap = sortedMap;
        return sortedMap;
    } catch(e) {
        console.error('Failed to load GIS blocks/lots', e);
        return null;
    }
}

async function bindGisDropdowns(blockSelectId, lotSelectId, options = {}) {
    const map = await fetchGisBlockLotMap();
    if (!map) return;

    const blockSelect = document.getElementById(blockSelectId);
    const lotSelect = document.getElementById(lotSelectId);
    if (!blockSelect || !lotSelect) return;

    const blockPrefix = options.blockPrefix || '';
    const lotPrefix = options.lotPrefix || '';
    const currentBlock = options.currentBlock;
    const currentLot = options.currentLot;

    // Populate Blocks
    blockSelect.innerHTML = Object.keys(map).map(b => `<option value="${b}">${blockPrefix}${b}</option>`).join('');

    const updateLots = () => {
        const b = blockSelect.value;
        const lots = map[b] || [];
        lotSelect.innerHTML = lots.map(lObj => {
            // Is it the currently selected lot for this existing resident/reservation?
            const isCurrent = (currentLot == lObj.lot);
            // It's selectable if it's available or if it's the current lot being edited
            const isAvail = lObj.available || isCurrent;
            const disabled = isAvail ? '' : 'disabled';
            const color = isAvail ? '' : 'color: #94a3b8; background-color: #f1f5f9;';
            return `<option value="${lObj.lot}" ${disabled} style="${color}">${lotPrefix}${lObj.lot}</option>`;
        }).join('');
    };

    blockSelect.addEventListener('change', updateLots);

    if (currentBlock && map[currentBlock]) {
        blockSelect.value = currentBlock;
    } else {
        blockSelect.selectedIndex = 0;
    }
    
    updateLots();
    
    // Check if the currentLot exists in the selected block
    if (currentLot && (map[blockSelect.value] || []).find(l => String(l.lot) === String(currentLot))) {
        lotSelect.value = currentLot;
    }
}
