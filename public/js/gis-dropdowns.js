window.gisBlockLotMap = null;

async function fetchGisBlockLotMap() {
    if (window.gisBlockLotMap) return window.gisBlockLotMap;
    try {
        const response = await fetch('/gis-data/houses.geojson');
        const data = await response.json();
        const map = {};
        data.features.forEach(f => {
            const b = f.properties.block_num;
            const l = f.properties.lot_number;
            if (b !== null && l !== null && b !== undefined && l !== undefined) {
                if (!map[b]) map[b] = [];
                if (!map[b].includes(l)) map[b].push(l);
            }
        });
        
        // Sort blocks and lots
        const sortedMap = {};
        Object.keys(map).map(Number).sort((a,b)=>a-b).forEach(b => {
            sortedMap[b] = map[b].sort((a,b)=>a-b);
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
        lotSelect.innerHTML = lots.map(l => `<option value="${l}">${lotPrefix}${l}</option>`).join('');
    };

    blockSelect.addEventListener('change', updateLots);

    if (currentBlock && map[currentBlock]) {
        blockSelect.value = currentBlock;
    } else {
        blockSelect.selectedIndex = 0;
    }
    
    updateLots();
    
    // Check if the currentLot exists in the selected block
    if (currentLot && (map[blockSelect.value] || []).includes(Number(currentLot))) {
        lotSelect.value = currentLot;
    }
}
