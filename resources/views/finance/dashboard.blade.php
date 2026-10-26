@extends('layouts.finance')

@section('title', 'Finance Dashboard')

@section('content')
<link rel="stylesheet" href="{{ asset('css/views/finance-dashboard.css') }}">

<div class="finance-header">
    <div>
        <h2 style="font-family: var(--font-display); font-size: 24px; color: var(--text-dark); margin-bottom: 8px;">Meter Reading Entry</h2>
        <p style="color: var(--text-mid); font-size: 14px;">Select a utility mode and manage meter inputs.</p>
    </div>
    <div class="finance-summary-pills" style="display: flex; gap: 10px; flex-wrap: wrap;">
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 6px 14px; font-size: 12px; display: flex; align-items: center; gap: 6px;">
            <span style="color: var(--text-mid);">Total Meters:</span>
            <strong id="counterTotal" style="color: var(--text-dark); font-size: 14px;">0</strong>
        </div>
        <div style="background: #fef3c7; border: 1px solid #fde68a; border-radius: 8px; padding: 6px 14px; font-size: 12px; display: flex; align-items: center; gap: 6px;">
            <span style="color: #92400e;">Pending:</span>
            <strong id="counterPending" style="color: #92400e; font-size: 14px;">0</strong>
        </div>
        <div style="background: #d1fae5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 6px 14px; font-size: 12px; display: flex; align-items: center; gap: 6px;">
            <span style="color: #065f46;">Billed:</span>
            <strong id="counterBilled" style="color: #065f46; font-size: 14px;">0</strong>
        </div>
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
        <div class="filter-label">Billing Cycle</div>
        <select id="cycleFilter" onchange="fetchCycle(this.value)" style="padding: 6px 14px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; color: var(--text-dark); background: var(--surface);">
            @if(empty($validCycles))
                <option value="" disabled selected>No Records</option>
            @else
                @foreach($validCycles as $c)
                    <option value="{{ $c['cycle'] }}" {{ ($currentCycle ?? request('cycle')) == $c['cycle'] ? 'selected' : '' }}>
                        {{ $c['label'] }}
                    </option>
                @endforeach
            @endif
        </select>
    </div>
    <div class="filter-group" style="margin:0;">
        <div class="filter-label">Block Filter</div>
        <select id="blockFilter" onchange="setFilter('block', this.value)" style="padding: 6px 14px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; color: var(--text-dark); background: var(--surface);">
            <option value="all">All Blocks</option>
            @php
                $uniqueBlocks = collect($houses)->pluck('block')->unique()->sort(fn($a, $b) => strnatcasecmp((string)$a, (string)$b));
            @endphp
            @foreach($uniqueBlocks as $b)
                <option value="{{ $b }}">Block {{ $b }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="house-grid" id="houseGrid">
    @foreach($houses as $house)
        <div class="house-card" 
             data-house="{{ json_encode($house) }}"
             data-block="{{ $house['block'] }}" 
             data-lot="{{ $house['lot'] }}"
             data-elec-status="{{ strtolower($house['elec_status']) }}"
             data-water-status="{{ strtolower($house['water_status']) }}"
             data-provider="{{ $house['provider_managed'] ? '1' : '0' }}"
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
                    <span class="metric-value">{{ number_format((float)($house['prev_elec'] ?? 0), 2) }} kWh</span>
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
        <div id="panelAlertBaseline" style="display: none; padding: 12px 14px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; margin-bottom: 16px; font-size: 13px; color: #1e40af;">
            ℹ️ <strong>Initial Meter Baseline:</strong> No prior reading recorded for this lot. Please input the starting physical meter reading as the Previous Reading.
        </div>

        <div class="reading-section">
            <h4 id="panelUtilityTitle">⚡ Electricity Reading</h4>
            <div class="reading-grid">
                <div class="input-group">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <label id="lblPrev">Previous Reading (kWh)</label>
                        <button type="button" id="btnTogglePrevEdit" onclick="togglePrevEdit()" style="background: none; border: none; font-size: 11px; color: var(--accent); cursor: pointer; text-decoration: underline; padding: 0;">Unlock / Edit</button>
                    </div>
                    <input type="number" step="any" class="form-input readonly" id="valPrev" readonly oninput="calculateBill()">
                    <span id="prevEditNotice" style="font-size: 10px; color: #64748b; margin-top: 2px; display: none;">Editable baseline or meter adjustment</span>
                </div>
                <div class="input-group">
                    <label id="lblCurr">Current Reading (kWh)</label>
                    <input type="number" step="any" class="form-input" id="valCurr" placeholder="Enter reading" oninput="calculateBill()">
                </div>
            </div>

            <div class="computation-box">
                <div class="calc-row">
                    <span>Usage (Difference)</span>
                    <span id="calcUsage" style="font-weight: 600;">0</span>
                </div>
                <div class="calc-row">
                    <span>Tariff Rate</span>
                    <span id="calcRate">₱10.00</span>
                </div>
                <div class="calc-row" id="tierBreakdownRow" style="display: none; font-size: 12px; color: #64748b; background: rgba(0,0,0,0.02); padding: 4px 8px; border-radius: 4px;">
                    <span id="tierBreakdownLabel">Minimum Base</span>
                    <span id="tierBreakdownValue">₱250.00</span>
                </div>
                <div class="calc-row">
                    <span>Current Charges</span>
                    <span id="calcCurrentCharges" style="font-weight: 600; color: var(--text-dark);">₱0.00</span>
                </div>
                <div class="calc-row" id="arrearsRow" style="display: none; color: #dc2626;">
                    <span>Previous Unpaid Balance</span>
                    <span id="calcArrears">₱0.00</span>
                </div>
                <div class="calc-row total">
                    <span>Total Amount Due</span>
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
    <p class="success-message" id="successMessage">The system has updated the billing record for Block -, Lot -.</p>
    <button class="btn-success-close" onclick="closeSuccessModal()">Done</button>
</div>

@endsection

@push('scripts')
<script>
    let ELEC_RATE = {{ \App\Models\Setting::where('key', 'elec_rate')->value('value') ?? 10 }};
    let WATER_RATE = {{ \App\Models\Setting::where('key', 'water_rate')->value('value') ?? 15 }};
    let WATER_MIN_M3 = {{ \App\Models\Setting::where('key', 'water_min_m3')->value('value') ?? 10 }};
    let WATER_MIN_RATE = {{ \App\Models\Setting::where('key', 'water_min_rate')->value('value') ?? 250 }};
    
    // Fetch rates dynamically to ensure they are always up-to-date
    fetch('/api/settings').then(r => r.json()).then(data => {
        if(data.elec_rate) ELEC_RATE = parseFloat(data.elec_rate);
        if(data.water_rate) WATER_RATE = parseFloat(data.water_rate);
        if(data.water_min_m3) WATER_MIN_M3 = parseFloat(data.water_min_m3);
        if(data.water_min_rate) WATER_MIN_RATE = parseFloat(data.water_min_rate);
    }).catch(e => console.error('Failed to load rates', e));
    
    let activeUtility = 'elec'; // 'elec' or 'water'
    let filterStatus = 'all';
    let filterBlock = 'all';
    let currentHouseCard = null;
    let currentHouseData = null;

    // Initialize UI on load
    document.addEventListener('DOMContentLoaded', () => {
        applyFiltersAndSort();
        updateSummaryCounters();
    });

    function fetchCycle(cycle) {
        document.getElementById('houseGrid').style.opacity = '0.5';
        const prevBlock = filterBlock;
        fetch('?cycle=' + encodeURIComponent(cycle))
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Update URL silently
                window.history.pushState({}, '', '?cycle=' + encodeURIComponent(cycle));
                
                // Replace grid
                const newGrid = doc.getElementById('houseGrid');
                if (newGrid) {
                    document.getElementById('houseGrid').innerHTML = newGrid.innerHTML;
                }
                document.getElementById('houseGrid').style.opacity = '1';
                
                // Refresh block filter options based on new data if needed
                const newBlockFilter = doc.getElementById('blockFilter');
                if (newBlockFilter) {
                    document.getElementById('blockFilter').innerHTML = newBlockFilter.innerHTML;
                    const options = Array.from(document.getElementById('blockFilter').options).map(o => o.value);
                    if (options.includes(prevBlock)) {
                        document.getElementById('blockFilter').value = prevBlock;
                        filterBlock = prevBlock;
                    } else {
                        document.getElementById('blockFilter').value = 'all';
                        filterBlock = 'all';
                    }
                }
                
                // Re-apply UI state
                switchUtility(activeUtility);
            })
            .catch(err => {
                console.error(err);
                document.getElementById('houseGrid').style.opacity = '1';
            });
    }

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
                metric.innerHTML = `<span class="metric-label">Prev. Elec</span><span class="metric-value">${Number(data.prev_elec || 0).toFixed(2)} kWh</span>`;
            } else {
                const stat = data.water_status.toLowerCase();
                badge.className = `status-badge status-${stat} house-badge`;
                badge.innerText = data.water_status;
                metric.innerHTML = `<span class="metric-label">Prev. Water</span><span class="metric-value">${Number(data.prev_water || 0).toFixed(2)} m³</span>`;
            }
        });

        applyFiltersAndSort();
        updateSummaryCounters();
    }

    function setFilter(type, value) {
        if (type === 'status') {
            filterStatus = value;
            document.querySelectorAll('#statusFilters .filter-btn').forEach(b => b.classList.remove('active'));
            document.querySelector(`#statusFilters .filter-btn[data-status="${value}"]`).classList.add('active');
        } else {
            filterBlock = value;
        }
        applyFiltersAndSort();
        updateSummaryCounters();
    }

    function updateSummaryCounters() {
        const cards = Array.from(document.querySelectorAll('.house-card'));
        const statusAttr = activeUtility === 'elec' ? 'data-elec-status' : 'data-water-status';

        let total = 0;
        let pending = 0;
        let billed = 0;

        cards.forEach(card => {
            const cardProvider = card.getAttribute('data-provider');
            // CASURECO lots are not counted in electricity
            if (activeUtility === 'elec' && cardProvider === '1') {
                return;
            }
            total++;
            const stat = (card.getAttribute(statusAttr) || '').toLowerCase();
            if (stat === 'billed') billed++;
            else pending++;
        });

        document.getElementById('counterTotal').innerText = total;
        document.getElementById('counterPending').innerText = pending;
        document.getElementById('counterBilled').innerText = billed;
    }

    function applyFiltersAndSort() {
        const grid = document.getElementById('houseGrid');
        let cards = Array.from(grid.querySelectorAll('.house-card'));
        
        const statusAttr = activeUtility === 'elec' ? 'data-elec-status' : 'data-water-status';

        // Sort DOM nodes formally: by Block, and then by Lot number from lowest to highest
        cards.sort((a, b) => {
            const blockA = a.getAttribute('data-block') || '';
            const blockB = b.getAttribute('data-block') || '';
            const cmpBlock = blockA.localeCompare(blockB, undefined, { numeric: true, sensitivity: 'base' });
            if (cmpBlock !== 0) return cmpBlock;

            const lotA = a.getAttribute('data-lot') || '';
            const lotB = b.getAttribute('data-lot') || '';
            return lotA.localeCompare(lotB, undefined, { numeric: true, sensitivity: 'base' });
        });

        // Re-append sorted nodes to grid
        cards.forEach(card => grid.appendChild(card));

        cards.forEach(card => {
            const cardStatus = card.getAttribute(statusAttr);
            const cardBlock = card.getAttribute('data-block');
            const cardProvider = card.getAttribute('data-provider');
            
            let showStatus = (filterStatus === 'all' || cardStatus === filterStatus);
            let showBlock = (filterBlock === 'all' || cardBlock === filterBlock);
            let showProvider = (activeUtility === 'water') || (activeUtility === 'elec' && cardProvider === '0');

            if (showStatus && showBlock && showProvider) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    function togglePrevEdit() {
        const valPrev = document.getElementById('valPrev');
        const btn = document.getElementById('btnTogglePrevEdit');
        const notice = document.getElementById('prevEditNotice');
        
        if (valPrev.hasAttribute('readonly')) {
            valPrev.removeAttribute('readonly');
            valPrev.classList.remove('readonly');
            valPrev.focus();
            btn.innerText = 'Lock';
            notice.style.display = 'block';
        } else {
            valPrev.setAttribute('readonly', 'readonly');
            valPrev.classList.add('readonly');
            btn.innerText = 'Unlock / Edit';
            notice.style.display = 'none';
        }
        calculateBill();
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
        const btnSubmit = document.getElementById('btnSubmitBill');
        const btnTogglePrev = document.getElementById('btnTogglePrevEdit');
        const notice = document.getElementById('prevEditNotice');
        const alertBaseline = document.getElementById('panelAlertBaseline');

        const status = isElec ? currentHouseData.elec_status : currentHouseData.water_status;
        const isBilled = status === 'Billed';
        const isBaseline = isElec 
            ? (currentHouseData.is_baseline_elec || currentHouseData.prev_elec == 0) 
            : (currentHouseData.is_baseline_water || currentHouseData.prev_water == 0);
        const prevVal = isElec ? currentHouseData.prev_elec : currentHouseData.prev_water;
        const currVal = isElec ? currentHouseData.curr_elec : currentHouseData.curr_water;

        valPrev.value = prevVal ?? 0;
        valCurr.value = (currVal !== null && currVal !== undefined && currVal !== '') ? currVal : '';

        if (isElec) {
            title.innerHTML = '<span style="color: #eab308;">⚡</span> Electricity Reading';
            lblPrev.innerText = 'Previous Reading (kWh)';
            lblCurr.innerText = 'Current Reading (kWh)';
        } else {
            title.innerHTML = '<span style="color: #3b82f6;">💧</span> Water Reading';
            lblPrev.innerText = 'Previous Reading (m³)';
            lblCurr.innerText = 'Current Reading (m³)';
        }

        valCurr.removeAttribute('readonly');
        valCurr.classList.remove('readonly');
        btnSubmit.disabled = false;
        btnSubmit.style.opacity = '1';

        if (isBilled && currVal !== null && currVal !== undefined && currVal !== '') {
            btnSubmit.innerText = 'Update Bill';
        } else {
            btnSubmit.innerText = 'Generate Bill';
        }

        if (isBaseline) {
            alertBaseline.style.display = 'block';
            valPrev.removeAttribute('readonly');
            valPrev.classList.remove('readonly');
            btnTogglePrev.style.display = 'none';
            notice.style.display = 'block';
            notice.innerText = 'Input initial starting meter baseline';
        } else {
            alertBaseline.style.display = 'none';
            valPrev.setAttribute('readonly', 'readonly');
            valPrev.classList.add('readonly');
            btnTogglePrev.style.display = 'inline-block';
            btnTogglePrev.innerText = 'Unlock / Edit';
            notice.style.display = 'none';
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
        const unit = isElec ? 'kWh' : 'm³';
        document.getElementById('calcUsage').innerText = `${usage.toFixed(2)} ${unit}`;
        
        let currentCharges = 0;
        const tierRow = document.getElementById('tierBreakdownRow');

        if (isElec) {
            currentCharges = usage * ELEC_RATE;
            document.getElementById('calcRate').innerText = `₱${ELEC_RATE.toFixed(2)} / kWh`;
            tierRow.style.display = 'none';
        } else {
            if (usage <= WATER_MIN_M3) {
                currentCharges = WATER_MIN_RATE;
                tierRow.style.display = 'flex';
                document.getElementById('tierBreakdownLabel').innerText = `Base charge (up to ${WATER_MIN_M3.toFixed(2)} m³)`;
                document.getElementById('tierBreakdownValue').innerText = `₱${WATER_MIN_RATE.toFixed(2)}`;
                document.getElementById('calcRate').innerText = `Base ₱${WATER_MIN_RATE.toFixed(2)} + ₱${WATER_RATE.toFixed(2)}/m³ excess`;
            } else {
                const excess = usage - WATER_MIN_M3;
                const excessCost = excess * WATER_RATE;
                currentCharges = WATER_MIN_RATE + excessCost;
                tierRow.style.display = 'flex';
                document.getElementById('tierBreakdownLabel').innerText = `Base (₱${WATER_MIN_RATE.toFixed(2)}) + Excess (${excess.toFixed(2)} m³ x ₱${WATER_RATE.toFixed(2)})`;
                document.getElementById('tierBreakdownValue').innerText = `₱${currentCharges.toFixed(2)}`;
                document.getElementById('calcRate').innerText = `Base ₱${WATER_MIN_RATE.toFixed(2)} + ₱${WATER_RATE.toFixed(2)}/m³ excess`;
            }
        }
        
        document.getElementById('calcCurrentCharges').innerText = `₱${currentCharges.toFixed(2)}`;

        const arrears = isElec 
            ? (parseFloat(currentHouseData?.prev_balance_elec) || 0) 
            : (parseFloat(currentHouseData?.prev_balance_water) || 0);

        const arrearsRow = document.getElementById('arrearsRow');
        if (arrears > 0) {
            arrearsRow.style.display = 'flex';
            document.getElementById('calcArrears').innerText = `₱${arrears.toFixed(2)}`;
        } else {
            arrearsRow.style.display = 'none';
        }

        const totalDue = currentCharges + arrears;
        document.getElementById('calcTotal').innerText = `₱${totalDue.toFixed(2)}`;
    }

    function submitReading() {
        const currInput = document.getElementById('valCurr').value;

        if (currInput === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Input Required',
                text: 'Please enter a current reading.'
            });
            return;
        }
        
        const curr = parseFloat(currInput);
        const prev = parseFloat(document.getElementById('valPrev').value) || 0;
        
        if (curr < prev) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Reading',
                text: `Current reading (${curr}) cannot be lower than previous reading (${prev}).`
            });
            return;
        }

        const isElec = activeUtility === 'elec';
        const type = isElec ? 'electricity' : 'water';
        const cycleVal = document.getElementById('cycleFilter').value;

        const btn = document.getElementById('btnSubmitBill');
        const isUpdate = btn.innerText === 'Update Bill';
        const origText = btn.innerText;
        btn.innerText = 'Processing...';
        btn.disabled = true;

        fetch('/finance/api/billing/reading', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                type: type,
                lot: currentHouseData.lot,
                block: currentHouseData.block,
                previous_reading: prev,
                current_reading: curr,
                cycle: cycleVal
            })
        }).then(response => response.json().then(data => ({ status: response.status, body: data })))
          .then(({ status, body }) => {
              if (body.success) {
                  const utilityName = isElec ? 'Electricity' : 'Water';
                  const verb = isUpdate ? 'Updated' : 'Generated';
                  const houseBlock = currentHouseData.block;
                  const houseLot = currentHouseData.lot;
                  const amountFormatted = Number(body.bill?.amount || 0).toFixed(2);
                  
                  document.getElementById('successTitle').innerText = `Bill ${verb}`;
                  document.getElementById('successMessage').innerText = `The ${utilityName} bill has been successfully ${verb.toLowerCase()} for Block ${houseBlock}, Lot ${houseLot}. Current charges: ₱${amountFormatted}.`;
                  
                  document.getElementById('successModalOverlay').classList.add('show');
                  document.getElementById('successModal').classList.add('show');

                  // Update local data so UI reflects changes immediately
                  if (isElec) {
                      currentHouseData.elec_status = 'Billed';
                      currentHouseData.prev_elec = body.bill.previous_reading;
                      currentHouseData.curr_elec = body.bill.current_reading;
                      currentHouseData.is_baseline_elec = false;
                      currentHouseCard.setAttribute('data-elec-status', 'billed');
                  } else {
                      currentHouseData.water_status = 'Billed';
                      currentHouseData.prev_water = body.bill.previous_reading;
                      currentHouseData.curr_water = body.bill.current_reading;
                      currentHouseData.is_baseline_water = false;
                      currentHouseCard.setAttribute('data-water-status', 'billed');
                  }
                  
                  // Re-stringify the data object and save to DOM
                  currentHouseCard.dataset.house = JSON.stringify(currentHouseData);
                  
                  // Re-apply visually
                  switchUtility(activeUtility);
                  
                  if (window.pushSystemNotification) {
                      window.pushSystemNotification(
                          `${utilityName} Billed`,
                          `Block ${houseBlock} Lot ${houseLot} successfully billed.`,
                          'Just now'
                      );
                  }

                  closeReadingPanel();
              } else {
                  Swal.fire({
                      icon: 'error',
                      title: 'Submission Failed',
                      text: body.message || 'Failed to update billing record.'
                  });
              }
          })
          .catch(err => {
              console.error("Billing operation exception:", err);
              Swal.fire({
                  icon: 'error',
                  title: 'Error',
                  text: 'An unexpected server error occurred.'
              });
          }).finally(() => {
              btn.innerText = origText;
              btn.disabled = false;
          });
    }

    function closeSuccessModal() {
        document.getElementById('successModalOverlay').classList.remove('show');
        document.getElementById('successModal').classList.remove('show');
    }
</script>
@endpush
