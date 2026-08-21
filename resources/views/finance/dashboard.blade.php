@extends('layouts.finance')

@section('title', 'Finance Dashboard')

@section('content')
<style>
    .finance-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .utility-tabs {
        display: flex;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 4px;
        margin-bottom: 24px;
        width: fit-content;
    }

    .utility-tab {
        padding: 10px 24px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 14px;
        color: var(--text-mid);
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .utility-tab:hover {
        color: var(--text-dark);
    }

    .utility-tab.active {
        background: var(--accent);
        color: var(--white);
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.25);
    }
    
    .filter-group {
        margin-bottom: 16px;
    }

    .filter-label {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-mid);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .filter-container {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .filter-btn {
        padding: 6px 14px;
        border-radius: 20px;
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--text-mid);
        font-weight: 500;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .filter-btn:hover {
        background: var(--border);
    }
    
    .filter-btn.active {
        background: var(--text-dark);
        color: var(--white);
        border-color: var(--text-dark);
    }

    .house-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }

    .house-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 20px;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .house-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        border-color: var(--accent);
    }

    .house-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 16px;
    }

    .house-title {
        font-family: var(--font-display);
        font-size: 18px;
        font-weight: 600;
        color: var(--text-dark);
    }

    .house-resident {
        font-size: 13px;
        color: var(--text-mid);
        margin-top: 4px;
    }

    .status-badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-pending { background: #fef3c7; color: #92400e; }
    .status-billed { background: #d1fae5; color: #065f46; }

    .house-metrics {
        display: flex;
        align-items: center;
        gap: 12px;
        padding-top: 16px;
        border-top: 1px dashed var(--border);
    }

    .metric {
        display: flex;
        flex-direction: column;
    }

    .metric-label {
        font-size: 11px;
        color: var(--text-light);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .metric-value {
        font-size: 15px;
        font-weight: 600;
        color: var(--text-dark);
    }

    /* Right Panel Overlay */
    .reading-panel-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.4);
        backdrop-filter: blur(4px);
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s;
    }
    .reading-panel-overlay.show { opacity: 1; visibility: visible; }

    .reading-panel {
        position: fixed;
        top: 0; right: -450px; bottom: 0;
        width: 100%; max-width: 450px;
        background: var(--bg);
        z-index: 1001;
        box-shadow: -4px 0 24px rgba(0,0,0,0.1);
        transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
    }
    .reading-panel.open { right: 0; }

    .panel-header {
        padding: 24px;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--surface);
    }

    .panel-header h3 {
        font-family: var(--font-display);
        font-size: 20px;
        color: var(--text-dark);
        margin: 0;
    }

    .close-panel-btn {
        background: none;
        border: none;
        color: var(--text-mid);
        cursor: pointer;
        padding: 4px;
        display: flex;
        border-radius: 4px;
    }

    .close-panel-btn:hover {
        background: var(--border);
        color: var(--text-dark);
    }

    .panel-body {
        padding: 24px;
        overflow-y: auto;
        flex: 1;
    }

    .reading-section {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .reading-section h4 {
        margin: 0 0 16px 0;
        font-size: 15px;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .reading-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 16px;
    }

    .input-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .input-group label {
        font-size: 12px;
        font-weight: 500;
        color: var(--text-mid);
    }

    .form-input.readonly {
        background: var(--bg);
        border-color: var(--border);
        color: var(--text-mid);
        cursor: not-allowed;
    }

    .computation-box {
        background: var(--bg);
        border-radius: 8px;
        padding: 16px;
        margin-top: 16px;
    }

    .calc-row {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        color: var(--text-mid);
        margin-bottom: 8px;
    }

    .calc-row.total {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px dashed var(--border);
        font-size: 16px;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 0;
    }

    .panel-footer {
        padding: 20px 24px;
        background: var(--surface);
        border-top: 1px solid var(--border);
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }

    .btn-submit {
        background: var(--accent);
        color: var(--white);
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
    }

    .btn-submit:hover { background: var(--accent-hover); }

    /* Success Modal */
    .success-modal-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(4px);
        z-index: 2000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s;
    }
    .success-modal-overlay.show { opacity: 1; visibility: visible; }

    .success-modal {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -40%) scale(0.95);
        background: #ffffff;
        width: 100%;
        max-width: 400px;
        border-radius: 16px;
        padding: 32px;
        text-align: center;
        z-index: 2001;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 20px 40px rgba(0,0,0,0.4);
    }
    .success-modal.show {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
        visibility: visible;
    }
    .success-icon {
        width: 64px;
        height: 64px;
        background: #d1fae5;
        color: #059669;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
    }
    .success-title {
        font-family: var(--font-display);
        font-size: 20px;
        color: #111827;
        margin: 0 0 12px 0;
    }
    .success-message {
        font-size: 14px;
        color: #4b5563;
        line-height: 1.6;
        margin: 0 0 24px 0;
    }
    .btn-success-close {
        width: 100%;
        padding: 12px;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-weight: 600;
        color: #374151;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-success-close:hover {
        background: #e5e7eb;
    }
</style>

<div class="finance-header">
    <div>
        <h2 style="font-family: var(--font-display); font-size: 24px; color: var(--text-dark); margin-bottom: 8px;">Meter Reading Entry</h2>
        <p style="color: var(--text-mid); font-size: 14px;">Select a utility mode and manage billing inputs.</p>
    </div>
</div>

<div class="utility-tabs">
    <div class="utility-tab active" id="tab-elec" onclick="switchUtility('elec')">⚡ Electricity</div>
    <div class="utility-tab" id="tab-water" onclick="switchUtility('water')">💧 Water</div>
</div>

<div class="card mb-4" style="padding: 16px; display: flex; flex-direction: column; gap: 16px;">
    <div class="filter-group" style="margin:0;">
        <div class="filter-label">Status Filter</div>
        <div class="filter-container" id="statusFilters">
            <button class="filter-btn active" data-status="all" onclick="setFilter('status', 'all')">All Statuses</button>
            <button class="filter-btn" data-status="pending" onclick="setFilter('status', 'pending')">Pending Only</button>
            <button class="filter-btn" data-status="billed" onclick="setFilter('status', 'billed')">Billed Only</button>
        </div>
    </div>
    
    <div class="filter-group" style="margin:0;">
        <div class="filter-label">Block Filter</div>
        <div class="filter-container" id="blockFilters">
            <button class="filter-btn active" data-block="all" onclick="setFilter('block', 'all')">All Blocks</button>
            @for($i = 1; $i <= 8; $i++)
                <button class="filter-btn" data-block="{{$i}}" onclick="setFilter('block', '{{$i}}')">Block {{$i}}</button>
            @endfor
        </div>
    </div>
</div>

<div class="house-grid" id="houseGrid">
    @foreach($houses as $house)
        <div class="house-card" 
             data-house="{{ json_encode($house) }}"
             data-block="{{ $house['block'] }}" 
             data-elec-status="{{ strtolower($house['elec_status']) }}"
             data-water-status="{{ strtolower($house['water_status']) }}"
             onclick="openReadingPanel(this)">
            <div class="house-header">
                <div>
                    <div class="house-title">Block {{ $house['block'] }}, Lot {{ $house['lot'] }}</div>
                    <div class="house-resident">👤 {{ $house['resident'] }}</div>
                </div>
                <!-- Status badge dynamically updated via JS -->
                <span class="status-badge status-{{ strtolower($house['elec_status']) }} house-badge">{{ $house['elec_status'] }}</span>
            </div>
            
            <div class="house-metrics">
                <div class="metric house-prev-metric">
                    <span class="metric-label">Prev. Elec</span>
                    <span class="metric-value">{{ $house['prev_elec'] }} kWh</span>
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Sliding Panel -->
<div class="reading-panel-overlay" id="panelOverlay" onclick="closeReadingPanel()"></div>
<div class="reading-panel" id="readingPanel">
    <div class="panel-header">
        <div>
            <h3 id="panelHouseTitle">Block -, Lot -</h3>
            <div style="font-size: 13px; color: var(--text-mid); margin-top: 4px;" id="panelResident">Resident</div>
        </div>
        <button class="close-panel-btn" onclick="closeReadingPanel()">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
    </div>

    <div class="panel-body">
        <div class="reading-section">
            <h4 id="panelUtilityTitle">⚡ Electricity Reading</h4>
            <div class="reading-grid">
                <div class="input-group">
                    <label id="lblPrev">Previous Reading (kWh)</label>
                    <input type="text" class="form-input readonly" id="valPrev" readonly>
                </div>
                <div class="input-group">
                    <label id="lblCurr">Current Reading (kWh)</label>
                    <input type="number" class="form-input" id="valCurr" placeholder="Enter reading" oninput="calculateBill()">
                </div>
            </div>
            <div class="computation-box">
                <div class="calc-row">
                    <span>Usage (Difference)</span>
                    <span id="calcUsage">0</span>
                </div>
                <div class="calc-row">
                    <span>Rate</span>
                    <span id="calcRate">₱10.00</span>
                </div>
                <div class="calc-row total">
                    <span>Total Bill</span>
                    <span id="calcTotal">₱0.00</span>
                </div>
            </div>
        </div>
    </div>

    <div class="panel-footer">
        <button class="btn btn-outline" onclick="closeReadingPanel()">Cancel</button>
        <button class="btn-submit" id="btnSubmitBill" onclick="submitReading()">Generate Bill</button>
    </div>
</div>

<!-- Success Modal -->
<div class="success-modal-overlay" id="successModalOverlay" onclick="closeSuccessModal()"></div>
<div class="success-modal" id="successModal">
    <div class="success-icon">
        <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
    </div>
    <h3 class="success-title" id="successTitle">Bill Generated</h3>
    <p class="success-message" id="successMessage">The system has updated the resident and admin ledgers for Block -, Lot -.</p>
    <button class="btn-success-close" onclick="closeSuccessModal()">Done</button>
</div>

@endsection

@push('scripts')
<script>
    const ELEC_RATE = 10;
    const WATER_RATE = 15;
    
    let activeUtility = 'elec'; // 'elec' or 'water'
    let filterStatus = 'all';
    let filterBlock = 'all';
    let currentHouseCard = null;
    let currentHouseData = null;

    // Initialize UI on load
    document.addEventListener('DOMContentLoaded', () => {
        applyFiltersAndSort();
    });

    function switchUtility(utility) {
        activeUtility = utility;
        
        // Update tabs
        document.getElementById('tab-elec').classList.toggle('active', utility === 'elec');
        document.getElementById('tab-water').classList.toggle('active', utility === 'water');

        // Update all cards visually
        const cards = document.querySelectorAll('.house-card');
        cards.forEach(card => {
            const data = JSON.parse(card.dataset.house);
            const badge = card.querySelector('.house-badge');
            const metric = card.querySelector('.house-prev-metric');
            
            if (utility === 'elec') {
                const stat = data.elec_status.toLowerCase();
                badge.className = `status-badge status-${stat} house-badge`;
                badge.innerText = data.elec_status;
                metric.innerHTML = `<span class="metric-label">Prev. Elec</span><span class="metric-value">${data.prev_elec} kWh</span>`;
            } else {
                const stat = data.water_status.toLowerCase();
                badge.className = `status-badge status-${stat} house-badge`;
                badge.innerText = data.water_status;
                metric.innerHTML = `<span class="metric-label">Prev. Water</span><span class="metric-value">${data.prev_water} m³</span>`;
            }
        });

        applyFiltersAndSort();
    }

    function setFilter(type, value) {
        if (type === 'status') {
            filterStatus = value;
            document.querySelectorAll('#statusFilters .filter-btn').forEach(b => b.classList.remove('active'));
            document.querySelector(`#statusFilters .filter-btn[data-status="${value}"]`).classList.add('active');
        } else {
            filterBlock = value;
            document.querySelectorAll('#blockFilters .filter-btn').forEach(b => b.classList.remove('active'));
            document.querySelector(`#blockFilters .filter-btn[data-block="${value}"]`).classList.add('active');
        }
        applyFiltersAndSort();
    }

    function applyFiltersAndSort() {
        const grid = document.getElementById('houseGrid');
        let cards = Array.from(grid.querySelectorAll('.house-card'));
        
        const statusAttr = activeUtility === 'elec' ? 'data-elec-status' : 'data-water-status';

        // Sort DOM nodes: Pending first, then Billed. If same status, keep block/lot order.
        cards.sort((a, b) => {
            const statA = a.getAttribute(statusAttr);
            const statB = b.getAttribute(statusAttr);
            if (statA === statB) return 0;
            return statA === 'pending' ? -1 : 1;
        });

        // Re-append sorted nodes to grid
        cards.forEach(card => grid.appendChild(card));

        cards.forEach(card => {
            const cardStatus = card.getAttribute(statusAttr);
            const cardBlock = card.getAttribute('data-block');
            
            let showStatus = (filterStatus === 'all' || cardStatus === filterStatus);
            let showBlock = (filterBlock === 'all' || cardBlock === filterBlock);

            if (showStatus && showBlock) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    function openReadingPanel(cardElement) {
        currentHouseCard = cardElement;
        currentHouseData = JSON.parse(cardElement.dataset.house);
        
        document.getElementById('panelHouseTitle').innerText = `Block ${currentHouseData.block}, Lot ${currentHouseData.lot}`;
        document.getElementById('panelResident').innerText = `👤 ${currentHouseData.resident}`;
        
        const isElec = activeUtility === 'elec';
        const title = document.getElementById('panelUtilityTitle');
        const lblPrev = document.getElementById('lblPrev');
        const lblCurr = document.getElementById('lblCurr');
        const valPrev = document.getElementById('valPrev');
        const valCurr = document.getElementById('valCurr');
        const calcRate = document.getElementById('calcRate');
        const btnSubmit = document.getElementById('btnSubmitBill');

        if (isElec) {
            title.innerHTML = '<span style="color: #eab308;">⚡</span> Electricity Reading';
            lblPrev.innerText = 'Previous Reading (kWh)';
            lblCurr.innerText = 'Current Reading (kWh)';
            valPrev.value = currentHouseData.prev_elec;
            
            if (currentHouseData.elec_status === 'Billed' && currentHouseData.curr_elec) {
                valCurr.value = currentHouseData.curr_elec;
                btnSubmit.innerText = 'Update Bill';
            } else {
                valCurr.value = '';
                btnSubmit.innerText = 'Generate Bill';
            }
            calcRate.innerText = `₱${ELEC_RATE.toFixed(2)}`;
        } else {
            title.innerHTML = '<span style="color: #3b82f6;">💧</span> Water Reading';
            lblPrev.innerText = 'Previous Reading (m³)';
            lblCurr.innerText = 'Current Reading (m³)';
            valPrev.value = currentHouseData.prev_water;
            
            if (currentHouseData.water_status === 'Billed' && currentHouseData.curr_water) {
                valCurr.value = currentHouseData.curr_water;
                btnSubmit.innerText = 'Update Bill';
            } else {
                valCurr.value = '';
                btnSubmit.innerText = 'Generate Bill';
            }
            calcRate.innerText = `₱${WATER_RATE.toFixed(2)}`;
        }
        
        calculateBill();

        document.getElementById('panelOverlay').classList.add('show');
        document.getElementById('readingPanel').classList.add('open');
    }

    function closeReadingPanel() {
        document.getElementById('panelOverlay').classList.remove('show');
        document.getElementById('readingPanel').classList.remove('open');
        currentHouseCard = null;
        currentHouseData = null;
    }

    function calculateBill() {
        const prev = parseFloat(document.getElementById('valPrev').value) || 0;
        const curr = parseFloat(document.getElementById('valCurr').value) || 0;
        let usage = curr - prev;
        if (usage < 0) usage = 0;
        
        const isElec = activeUtility === 'elec';
        const rate = isElec ? ELEC_RATE : WATER_RATE;
        const unit = isElec ? 'kWh' : 'm³';
        const total = usage * rate;
        
        document.getElementById('calcUsage').innerText = `${usage} ${unit}`;
        document.getElementById('calcTotal').innerText = `₱${total.toFixed(2)}`;
    }

    function submitReading() {
        const curr = document.getElementById('valCurr').value;

        if (!curr) {
            alert('Please enter a current reading.');
            return;
        }

        const btn = document.getElementById('btnSubmitBill');
        const isUpdate = btn.innerText === 'Update Bill';
        const origText = btn.innerText;
        btn.innerText = 'Processing...';
        btn.disabled = true;

        setTimeout(() => {
            const utilityName = activeUtility === 'elec' ? 'Electricity' : 'Water';
            const verb = isUpdate ? 'Updated' : 'Generated';
            
            document.getElementById('successTitle').innerText = `Bill ${verb}`;
            document.getElementById('successMessage').innerText = `The ${utilityName} bill has been successfully ${verb.toLowerCase()} for Block ${currentHouseData.block}, Lot ${currentHouseData.lot}.`;
            
            document.getElementById('successModalOverlay').classList.add('show');
            document.getElementById('successModal').classList.add('show');

            btn.innerText = origText;
            btn.disabled = false;
            
            // Update local data so UI reflects changes immediately
            if (activeUtility === 'elec') {
                currentHouseData.elec_status = 'Billed';
                currentHouseData.curr_elec = parseFloat(curr);
                currentHouseCard.setAttribute('data-elec-status', 'billed');
            } else {
                currentHouseData.water_status = 'Billed';
                currentHouseData.curr_water = parseFloat(curr);
                currentHouseCard.setAttribute('data-water-status', 'billed');
            }
            
            // Re-stringify the data object and save to DOM
            currentHouseCard.dataset.house = JSON.stringify(currentHouseData);
            
            // Re-apply visually
            switchUtility(activeUtility);
            
            closeReadingPanel();
        }, 600);
    }

    function closeSuccessModal() {
        document.getElementById('successModalOverlay').classList.remove('show');
        document.getElementById('successModal').classList.remove('show');
    }
</script>
@endpush
