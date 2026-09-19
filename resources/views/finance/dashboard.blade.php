@extends('layouts.finance')

@section('title', 'Finance Dashboard')

@section('content')
<link rel="stylesheet" href="{{ asset('css/views/finance-dashboard.css') }}">

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
        <div class="filter-label">Billing Cycle</div>
        <select id="cycleFilter" onchange="window.location.href='?cycle='+this.value" style="padding: 6px 14px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; color: var(--text-dark); background: var(--surface);">
            @if(empty($validCycles))
                <option value="" disabled selected>No Records</option>
            @else
                @foreach($validCycles as $c)
                    <option value="{{ $c['cycle'] }}" {{ request('cycle') == $c['cycle'] ? 'selected' : '' }}>
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
                $uniqueBlocks = collect($houses)->pluck('block')->unique()->sort();
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
    let ELEC_RATE = {{ \App\Models\Setting::where('key', 'elec_rate')->value('value') ?? 10 }};
    let WATER_RATE = {{ \App\Models\Setting::where('key', 'water_rate')->value('value') ?? 15 }};
    
    // Fetch rates dynamically to ensure they are always up-to-date
    fetch('/api/settings').then(r => r.json()).then(data => {
        if(data.elec_rate) ELEC_RATE = parseFloat(data.elec_rate);
        if(data.water_rate) WATER_RATE = parseFloat(data.water_rate);
    }).catch(e => console.error('Failed to load rates', e));
    
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
            
            if (currentHouseData.elec_status === 'Billed' && currentHouseData.curr_elec !== null && currentHouseData.curr_elec !== undefined) {
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
            
            if (currentHouseData.water_status === 'Billed' && currentHouseData.curr_water !== null && currentHouseData.curr_water !== undefined) {
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
        const currInput = document.getElementById('valCurr').value;

        if (currInput === '') {
            alert('Please enter a current reading.');
            return;
        }
        
        const curr = parseFloat(currInput) || 0;
        const prev = parseFloat(document.getElementById('valPrev').value) || 0;
        
        if (curr < prev) {
            alert(`Current reading (${curr}) cannot be lower than previous reading (${prev}).`);
            return;
        }

        let usage = curr - prev;

        const isElec = activeUtility === 'elec';
        const rate = isElec ? ELEC_RATE : WATER_RATE;
        const amount = usage * rate;
        const type = isElec ? 'electricity' : 'water';

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
                usage: usage,
                amount: amount,
                cycle: document.getElementById('cycleFilter').value
            })
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  const utilityName = activeUtility === 'elec' ? 'Electricity' : 'Water';
                  const verb = isUpdate ? 'Updated' : 'Generated';
                  
                  document.getElementById('successTitle').innerText = `Bill ${verb}`;
                  document.getElementById('successMessage').innerText = `The ${utilityName} bill has been successfully ${verb.toLowerCase()} for Block ${currentHouseData.block}, Lot ${currentHouseData.lot}.`;
                  
                  document.getElementById('successModalOverlay').classList.add('show');
                  document.getElementById('successModal').classList.add('show');

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
                  
                  if (window.pushSystemNotification) {
                      window.pushSystemNotification(
                          `${utilityName} Billed`,
                          `Block ${currentHouseData.block} Lot ${currentHouseData.lot} successfully billed.`,
                          'Just now'
                      );
                  }
              } else {
                  alert(data.message || 'Failed to update billing record.');
              }
          })
          .catch(err => {
              console.error(err);
              alert('Error communicating with server.');
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
