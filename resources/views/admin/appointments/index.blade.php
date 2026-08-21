@extends('layouts.admin')

@section('title', 'Lot & Visit Inquiries')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">

<div class="bill-container fade-in">
    <div class="bill-header">
        <div class="bill-title">
            <h1>Lot & Visit Inquiries</h1>
            <p>Manage potential client appointments, schedule lot viewings, and track sales pipelines.</p>
        </div>
        <div class="bill-actions">
            <button class="btn btn-primary" onclick="openAddAptModal()">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 8px;"><path d="M12 4v16m8-8H4"/></svg>
                Schedule Walk-In
            </button>
        </div>
    </div>

    <!-- Collection Summary Dashboard style translated to Appointments -->
    <div style="background: #fff; border: 1px solid var(--bill-border); border-radius: 20px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <h2 style="font-size: 16px; font-weight: 800; color: #0f172a; margin-top: 0; margin-bottom: 24px;">Pipeline Metrics</h2>
        <div class="responsive-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="background: #f8fafc; padding: 12px 16px; border-radius: 12px; border-left: 4px solid var(--bill-primary); cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'" onclick="filterByMetric('all')">
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Total Active Inquiries</div>
                <div id="metricTotalActive" style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ count($appointments) }}</div>
            </div>
            <div style="background: #f8fafc; padding: 12px 16px; border-radius: 12px; border-left: 4px solid #f59e0b; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'" onclick="filterByMetric('Pending')">
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Awaiting Approval</div>
                <div id="metricAwaiting" style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ collect($appointments)->where('status', 'Pending')->count() }}</div>
            </div>
            <div style="background: #f8fafc; padding: 12px 16px; border-radius: 12px; border-left: 4px solid #10b981; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'" onclick="filterByMetric('Scheduled')">
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Scheduled Upcoming</div>
                <div id="metricScheduled" style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ collect($appointments)->where('status', 'Scheduled')->count() }}</div>
            </div>
            <div style="background: #f8fafc; padding: 12px 16px; border-radius: 12px; border-left: 4px solid #64748b; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'" onclick="filterByMetric('Completed')">
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Successfully Completed</div>
                <div id="metricCompleted" style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ collect($appointments)->where('status', 'Completed')->count() }}</div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-group">
            <span class="filter-label">Search Client</span>
            <input type="text" id="aptSearch" class="filter-select" placeholder="Name or Contact..." style="min-width: 200px;">
        </div>
        <div class="filter-group">
            <span class="filter-label">Status</span>
            <select id="statusFilter" class="filter-select">
                <option value="all">All Pipeline</option>
                <option value="Pending">Pending</option>
                <option value="Scheduled">Scheduled</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
            </select>
        </div>
        <div style="margin-left: auto;">
            <button class="btn btn-primary" onclick="applyAptFilters()">Apply Filters</button>
        </div>
    </div>

    <!-- Appointments Interactive List -->
    <div class="analytic-card">
        <div class="card-title">
            <span>Inquiry Pipeline</span>
        </div>
        <div class="bill-table-container">
            <table class="bill-table" id="aptTable">
                <thead>
                    <tr>
                        <th>Inquiry ID</th>
                        <th>Client Detail</th>
                        <th>Requested Schedule</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="aptTableBody">
                    @foreach($appointments as $apt)
                    <tr class="apt-row" data-id="{{ $apt['id'] }}" data-client="{{ strtolower($apt['client'] . ' ' . $apt['contact']) }}" data-status="{{ $apt['status'] }}" data-type="{{ isset($apt['type']) ? $apt['type'] : 'General Inquiry' }}" data-report="{{ isset($apt['report']) ? $apt['report'] : '' }}">
                        <td style="font-family: monospace; font-weight: 700; color: #64748b;">
                            {{ $apt['id'] }}
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #0f172a;">{{ $apt['client'] }}</div>
                            <div style="font-size: 12px; color: var(--bill-primary);">{{ $apt['contact'] }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #334155;">{{ date('M d, Y', strtotime($apt['date'])) }}</div>
                            <div style="font-size: 12px; color: #64748b;">{{ $apt['time'] }}</div>
                        </td>
                        <td>
                            @if($apt['status'] === 'Pending')
                                <span class="badge" style="background: #fef3c7; color: #d97706;">Pending</span>
                            @elseif($apt['status'] === 'Scheduled')
                                <span class="badge" style="background: #e0e7ff; color: #4f46e5;">Scheduled</span>
                            @elseif($apt['status'] === 'Completed')
                                <span class="badge badge-success">Completed</span>
                            @else
                                <span class="badge badge-danger">Cancelled</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-outline" style="padding: 6px 10px; font-size: 11px;" onclick="viewAptDetail(
                                this.closest('.apt-row')
                            )">Manage</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <!-- Pagination Controls -->
            <div id="paginationControls" style="display: flex; justify-content: flex-end; align-items: center; gap: 8px; margin-top: 16px; padding: 12px 16px; border-top: 1px solid var(--bill-border);">
                <span id="pageInfo" style="font-size: 13px; color: #64748b;">Showing -</span>
                <button class="btn btn-outline" style="padding: 6px 12px;" onclick="prevPage()">Prev</button>
                <button class="btn btn-outline" style="padding: 6px 12px;" onclick="nextPage()">Next</button>
            </div>
        </div>
    </div>

    <!-- Calendar View -->
    <div class="analytic-card" style="margin-top: 24px;">
        <div class="card-title" style="display: flex; justify-content: space-between; align-items: center;">
            <span>Appointments Calendar</span>
            <span style="font-size: 11px; font-weight: normal; color: #64748b; background: #f1f5f9; padding: 4px 10px; border-radius: 12px;">Tip: Click any empty space on a day to block/unblock it.</span>
        </div>
        <div id="calendar" style="margin-top: 16px;"></div>
    </div>
</div>

<!-- Appointment Modal -->
<div id="aptModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 1000; background: rgba(15,23,42,0.6); backdrop-filter: blur(8px);">
    <div class="bill-modal-content" style="max-width: 600px; width: 100%; border-radius: 20px; overflow: hidden; background: #fff;">
        <div class="modal-header" style="border-bottom: 1px solid var(--bill-border); padding: 24px; display: flex; justify-content: space-between;">
            <div>
                <span id="modalAptId" style="font-size: 11px; font-weight: 700; color: var(--bill-primary); font-family: monospace;">APT-1001</span>
                <h2 style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 4px;" id="modalClient">Client Name</h2>
            </div>
            <button class="ann-btn-icon" onclick="closeAptModal()" style="padding: 8px;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        
        <div style="padding: 24px; display: flex; flex-direction: column; gap: 20px; overflow-y: auto; max-height: 75vh;">
            <div class="responsive-grid grid-2">
                <div style="background: #f8fafc; padding: 16px; border-radius: 12px;">
                    <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Contact Info</span>
                    <div id="modalContact" style="font-size: 16px; font-weight: 600; color: #334155; margin-top: 4px;">Phone</div>
                    <div id="modalEmail" style="font-size: 14px; font-weight: 500; color: #64748b; margin-top: 2px;">Email</div>
                </div>
                <div style="background: #f8fafc; padding: 16px; border-radius: 12px;">
                    <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Status</span>
                    <div id="modalStatusBadge" style="margin-top: 4px;">-</div>
                </div>
            </div>

            <div style="background: #e0e7ff; padding: 16px; border-radius: 12px; border: 1px solid #c7d2fe;">
                <span style="font-size: 11px; color: #4f46e5; font-weight: 700; text-transform: uppercase;">Requested Appointment Scheduled</span>
                <div id="modalDateTime" style="font-size: 18px; font-weight: 800; color: #312e81; margin-top: 8px;">May 15 @ 10:00 AM</div>
                <div id="modalInquiryType" style="font-size: 13px; font-weight: 700; color: #4f46e5; margin-top: 8px;">Lot Viewing / Site Visit</div>
            </div>

            <div id="modalReportSection" style="display: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; margin-top: 8px;">
                <div style="background: #f1f5f9; padding: 12px 16px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" fill="none" stroke="#475569" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span style="font-size: 11px; color: #475569; font-weight: 800; text-transform: uppercase;">Visit Report</span>
                    </div>
                </div>
                <div style="padding: 16px;">
                    <p id="modalReportData" style="font-size: 13px; color: #334155; line-height: 1.6; margin: 0;">-</p>
                </div>
            </div>

            <div id="modalActions" style="display: flex; gap: 12px; margin-top: 10px;">
                <!-- Dynamically populated buttons -->
            </div>
        </div>
    </div>
</div>

<!-- Add Appointment Modal -->
<div id="addAptModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 1000; background: rgba(15,23,42,0.6); backdrop-filter: blur(8px);">
    <div class="bill-modal-content" style="max-width: 500px; width: 100%; border-radius: 20px; overflow: hidden; background: #fff;">
        <div class="modal-header" style="border-bottom: 1px solid var(--bill-border); padding: 24px; display: flex; justify-content: space-between;">
            <h2 style="font-size: 20px; font-weight: 800; color: #0f172a;">Schedule Manual Walk-In</h2>
            <button class="ann-btn-icon" onclick="closeAddAptModal()" style="padding: 8px;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="addAptForm" onsubmit="submitNewApt(event)" style="padding: 24px; display: flex; flex-direction: column; gap: 16px; overflow-y: auto; max-height: 75vh;">
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #64748b;">Client Name</label>
                <input type="text" id="addAptName" class="filter-select" required style="width: 100%; margin-top: 4px;">
            </div>
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #64748b;">Contact Infomation</label>
                <input type="text" id="addAptContact" class="filter-select" required style="width: 100%; margin-top: 4px;">
            </div>
            <div style="display: flex; gap: 12px;">
                <div style="flex: 1;">
                    <label style="font-size: 12px; font-weight: 700; color: #64748b;">Date</label>
                    <input type="date" id="addAptDate" class="filter-select" required style="width: 100%; margin-top: 4px;">
                </div>
                <div style="flex: 1;">
                    <label style="font-size: 12px; font-weight: 700; color: #64748b;">Time</label>
                    <input type="time" id="addAptTime" class="filter-select" required style="width: 100%; margin-top: 4px;">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="justify-content: center; padding: 12px; font-weight: 700;">Confirm Schedule Layout</button>
        </form>
    </div>
</div>

<!-- Complete / Visit Report Modal -->
<div id="reportModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 1050; background: rgba(15,23,42,0.6); backdrop-filter: blur(8px);">
    <div class="bill-modal-content" style="max-width: 500px; width: 100%; border-radius: 20px; overflow: hidden; background: #fff;">
        <div class="modal-header" style="border-bottom: 1px solid var(--bill-border); padding: 24px; background: #f8fafc;">
            <h2 style="font-size: 20px; font-weight: 800; color: #0f172a;">Visit Report</h2>
            <p style="font-size: 13px; color: #64748b; margin-top: 4px;">Log the details of the property visit before marking it as completed.</p>
        </div>
        <form id="reportForm" onsubmit="submitReport(event)" style="padding: 24px; display: flex; flex-direction: column; gap: 16px; overflow-y: auto; max-height: 75vh;">
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Report Details</label>
                <textarea id="reportNotes" class="filter-select" required style="width: 100%; margin-top: 8px; resize: vertical; padding: 16px; font-size: 14px; line-height: 1.5; border-radius: 12px; border: 1px solid #cbd5e1; background: #f8fafc;" rows="6" placeholder="Type your visit report here..."></textarea>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 8px;">
                <button type="button" class="btn btn-outline" style="flex: 1; justify-content: center; border-radius: 10px;" onclick="closeReportModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex: 2; justify-content: center; font-weight: 700; border-radius: 10px;">Save & Mark Completed</button>
            </div>
        </form>
    </div>
</div>

<!-- Cancel Appointment Modal -->
<div id="cancelModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 1050; background: rgba(15,23,42,0.6); backdrop-filter: blur(8px);">
    <div class="bill-modal-content" style="max-width: 450px; width: 100%; border-radius: 20px; overflow: hidden; background: #fff;">
        <div class="modal-header" style="border-bottom: 1px solid var(--bill-border); padding: 24px; background: #fef2f2;">
            <h2 style="font-size: 20px; font-weight: 800; color: #b91c1c;">Cancel Appointment</h2>
        </div>
        <form id="cancelForm" onsubmit="submitCancel(event)" style="padding: 24px; display: flex; flex-direction: column; gap: 16px; overflow-y: auto; max-height: 75vh;">
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #64748b;">Reason for Cancellation</label>
                <textarea id="cancelReason" class="filter-select" required style="width: 100%; margin-top: 4px; resize: vertical;" rows="3" placeholder="e.g. Schedule conflict, client backed out..."></textarea>
            </div>
            <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                <input type="checkbox" id="cancelEmail" checked style="width: 16px; height: 16px;">
                <label for="cancelEmail" style="font-size: 13px; font-weight: 600; color: #334155;">Dispatch automated cancellation email to client address.</label>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 16px;">
                <button type="button" class="btn btn-outline" style="flex: 1; justify-content: center;" onclick="closeCancelModal()">Go Back</button>
                <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center; background: var(--bill-danger); border-color: var(--bill-danger);">Confirm Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Blackout Date Modal -->
<div id="blackoutModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 1050; background: rgba(15,23,42,0.6); backdrop-filter: blur(8px);">
    <div class="bill-modal-content" style="max-width: 450px; width: 100%; border-radius: 20px; overflow: hidden; background: #fff;">
        <div class="modal-header" style="border-bottom: 1px solid var(--bill-border); padding: 24px; display: flex; justify-content: space-between;">
            <h2 style="font-size: 20px; font-weight: 800; color: #0f172a;">Mark Office Unavailable</h2>
            <button class="ann-btn-icon" onclick="closeBlackoutModal()" style="padding: 8px; background: none; border: none; cursor: pointer;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="blackoutForm" onsubmit="submitBlackoutDate(event)" style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #64748b;">Select Date to Block</label>
                <input type="date" id="blackoutDate" class="filter-select" required style="width: 100%; margin-top: 4px;">
            </div>
            <button type="submit" class="btn btn-primary" style="justify-content: center; padding: 12px; font-weight: 700; background: var(--bill-danger); border-color: var(--bill-danger);">Confirm Block Date</button>
        </form>
    </div>
</div>

<!-- Admin Success Confirmation Modal -->
<div id="adminSuccessModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 2000; background: rgba(15,23,42,0.8); backdrop-filter: blur(8px);">
    <div style="max-width: 500px; width: 100%; border-radius: 20px; background: #ffffff; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); display: flex; flex-direction: column; animation: modalFadeUp 0.4s ease; text-align: center;">
        
        <div style="padding: 40px 32px 32px; display: flex; flex-direction: column; align-items: center;">
            <div style="width: 72px; height: 72px; background: #dcfce7; color: #16a34a; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 24px;">
                <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
            </div>
            
            <h2 style="font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 12px;">Appointment Approved!</h2>
            
            <p style="font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 24px;">
                You have successfully accepted the property viewing for <strong id="successClientName" style="color: #0f172a;">Client</strong>. An automated email has been dispatched to their inbox containing the generated Gate Access PIN below.
            </p>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 32px; margin-bottom: 32px; width: 100%;">
                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px;">Generated PIN</div>
                <div id="successGeneratedPin" style="font-size: 32px; font-weight: 900; letter-spacing: 0.1em; color: #10b981; font-family: monospace;">123456</div>
            </div>

            <button onclick="document.getElementById('adminSuccessModal').style.display = 'none'" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px; font-size: 15px;">
                Done
            </button>
        </div>
    </div>
</div>


<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
<script>
    let currentAptRow = null; 
    let currentAptId = null;
    let allAppointments = [];
    let filteredAppointments = [];
    let currentPage = 1;
    const itemsPerPage = 5;
    let calendar;

    document.addEventListener('DOMContentLoaded', function() {
        renderAppointmentsFromStore();
    });

    function renderAppointmentsFromStore() {
        allAppointments = SubdivisionStore.getAppointments();
        applyAptFilters();
        initCalendar();
    }

    function renderTable() {
        const tbody = document.getElementById('aptTableBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const paginatedData = filteredAppointments.slice(startIndex, endIndex);

        paginatedData.forEach(apt => {
            const tr = document.createElement('tr');
            tr.className = 'apt-row';
            tr.dataset.id = apt.id;
            tr.dataset.client = (apt.client + ' ' + (apt.contact || '') + ' ' + (apt.email || '')).toLowerCase();
            tr.dataset.status = apt.status;
            tr.dataset.type = apt.type || 'Site Visit / Lot Viewing';
            tr.dataset.report = apt.report || '';
            tr.dataset.email = apt.email || '';
            tr.dataset.contact = apt.contact || '';

            let badgeHtml = '';
            if(apt.status === 'Pending') badgeHtml = `<span class="badge" style="background: #fef3c7; color: #d97706;">Pending</span>`;
            else if(apt.status === 'Scheduled') badgeHtml = `<span class="badge" style="background: #e0e7ff; color: #4f46e5;">Scheduled</span>`;
            else if(apt.status === 'Completed') badgeHtml = `<span class="badge badge-success">Completed</span>`;
            else badgeHtml = `<span class="badge badge-danger">Cancelled</span>`;

            tr.innerHTML = `
                <td style="font-family: monospace; font-weight: 700; color: #64748b;">${apt.id}</td>
                <td>
                    <div style="font-weight: 700; color: #0f172a;">${apt.client}</div>
                    <div style="font-size: 12px; color: var(--bill-primary);">${apt.contact || apt.email || 'N/A'}</div>
                </td>
                <td>
                    <div style="font-weight: 700; color: #334155;">${apt.date}</div>
                    <div style="font-size: 12px; color: #64748b;">${apt.time}</div>
                </td>
                <td>${badgeHtml}</td>
                <td>
                    <button class="btn btn-outline" style="padding: 6px 10px; font-size: 11px;" onclick="viewAptDetail(this.closest('.apt-row'))">Manage</button>
                </td>
            `;

            tbody.appendChild(tr);
        });

        const pageInfo = document.getElementById('pageInfo');
        if (pageInfo) {
            pageInfo.textContent = `Showing ${filteredAppointments.length > 0 ? startIndex + 1 : 0}-${Math.min(endIndex, filteredAppointments.length)} of ${filteredAppointments.length}`;
        }
        recalculateMetrics();
    }

    function prevPage() {
        if (currentPage > 1) {
            currentPage--;
            renderTable();
        }
    }

    function nextPage() {
        if (currentPage * itemsPerPage < filteredAppointments.length) {
            currentPage++;
            renderTable();
        }
    }

    function initCalendar() {
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;
        if (calendar) calendar.destroy();
        
        const evts = allAppointments.filter(apt => apt.status !== 'Cancelled').map(apt => {
            let d = new Date(apt.date + ' ' + (apt.time || ''));
            if(isNaN(d.getTime())) { d = new Date(apt.date); }
            return {
                title: apt.client + ' (' + apt.status + ')',
                start: d,
                color: apt.status === 'Pending' ? '#f59e0b' : (apt.status === 'Scheduled' ? '#4f46e5' : '#10b981')
            };
        });

        const blackouts = JSON.parse(localStorage.getItem('unavailableDates') || '[]');
        blackouts.forEach(dateStr => {
            evts.push({
                title: 'Unavailable',
                start: dateStr,
                allDay: true,
                color: '#fee2e2',
                textColor: '#b91c1c'
            });
        });

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            dayMaxEvents: 2, // Limits events per day and shows +x more
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            eventTimeFormat: {
                hour: 'numeric',
                minute: '2-digit',
                meridiem: 'short'
            },
            events: evts,
            dateClick: function(info) {
                const dateRaw = info.dateStr;
                
                // Past date validation
                const clickedDate = new Date(dateRaw + 'T00:00:00');
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (clickedDate < today) {
                    alert("You cannot modify availability for past dates.");
                    return;
                }

                // Need to offset timezone difference if user clicks, but dateStr is usually YYYY-MM-DD
                const formattedDate = clickedDate.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});

                let blackouts = JSON.parse(localStorage.getItem('unavailableDates') || '[]');
                if (blackouts.includes(dateRaw) || blackouts.includes(formattedDate)) {
                    if (confirm(`Remove blackout for ${formattedDate}?`)) {
                        blackouts = blackouts.filter(d => d !== dateRaw && d !== formattedDate);
                        localStorage.setItem('unavailableDates', JSON.stringify(blackouts));
                        initCalendar();
                    }
                } else {
                    openBlackoutModal(dateRaw);
                }
            },
            height: 500
        });
        calendar.render();
    }

    function generateAppointmentPin(aptId, clientName) {
        const pin = Math.floor(100000 + Math.random() * 900000).toString();
        
        // Save to localStorage for the Guard Portal to access
        let existingPins = JSON.parse(localStorage.getItem('appointmentPins') || '[]');
        existingPins.push({
            id: aptId,
            pin: pin,
            client: clientName,
            status: 'Valid'
        });
        localStorage.setItem('appointmentPins', JSON.stringify(existingPins));
        
        // Dispatch Live Gmail Approval Email
        const aptObj = SubdivisionStore.getAppointments().find(a => a.id === aptId);
        const emailToSend = (aptObj && aptObj.email) ? aptObj.email : 'eighty6pharmacy@gmail.com';
        const dateToSend = (aptObj && aptObj.date) ? aptObj.date : 'Upcoming Date';
        
        fetch('/api/send-approval-email', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({
                email: emailToSend,
                name: clientName,
                pin: pin,
                date: dateToSend
            })
        }).catch(err => console.error("Approval email dispatch error:", err));

        // Render Admin Success Modal
        document.getElementById('successClientName').textContent = clientName;
        document.getElementById('successGeneratedPin').textContent = pin;
        document.getElementById('adminSuccessModal').style.display = 'flex';
    }

    function filterByMetric(status) {
        document.getElementById('statusFilter').value = status;
        applyAptFilters();
    }

    function recalculateMetrics() {
        const total = allAppointments.length;
        const pending = allAppointments.filter(r => r.status === 'Pending').length;
        const scheduled = allAppointments.filter(r => r.status === 'Scheduled').length;
        const completed = allAppointments.filter(r => r.status === 'Completed').length;

        document.getElementById('metricTotalActive').textContent = total;
        document.getElementById('metricAwaiting').textContent = pending;
        document.getElementById('metricScheduled').textContent = scheduled;
        document.getElementById('metricCompleted').textContent = completed;
    }

    // Modal Add functions
    function openAddAptModal() {
        document.getElementById('addAptModal').style.display = 'flex';
    }
    
    function closeAddAptModal() {
        document.getElementById('addAptModal').style.display = 'none';
        document.getElementById('addAptForm').reset();
    }

    function submitNewApt(e) {
        e.preventDefault();
        
        const client = document.getElementById('addAptName').value;
        const contact = document.getElementById('addAptContact').value;
        const dateRaw = document.getElementById('addAptDate').value;
        const timeRaw = document.getElementById('addAptTime').value;

        // format date simply
        const dateObj = new Date(dateRaw);
        const formattedDate = dateObj.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});

        // format time simply
        const timeParts = timeRaw.split(':');
        const hs = parseInt(timeParts[0]);
        const ampms = hs >= 12 ? 'PM' : 'AM';
        const hsf = hs % 12 || 12;
        const formattedTime = `${hsf}:${timeParts[1]} ${ampms}`;

        const newId = 'APT-' + Math.floor(2000 + Math.random() * 9000);
        
        SubdivisionStore.addAppointment({
            id: newId,
            client: client,
            contact: contact,
            date: formattedDate,
            time: formattedTime,
            status: 'Scheduled',
            type: 'Manual Walk-In',
            notes: '',
            report: ''
        });

        renderAppointmentsFromStore();
        closeAddAptModal();
        
        generateAppointmentPin(newId, client);
        
        if(window.pushSystemNotification) {
            window.pushSystemNotification('New Walk-In Scheduled', `Added appointment ${newId} for ${client}.`, 'Just Now', false);
        }
    }

    function openBlackoutModal(prefillDate = '') {
        if (prefillDate) {
            document.getElementById('blackoutDate').value = prefillDate;
        }
        document.getElementById('blackoutModal').style.display = 'flex';
    }

    function closeBlackoutModal() {
        document.getElementById('blackoutModal').style.display = 'none';
        document.getElementById('blackoutForm').reset();
    }

    function submitBlackoutDate(e) {
        e.preventDefault();
        const dateRaw = document.getElementById('blackoutDate').value;
        const dateObj = new Date(dateRaw);
        const formattedDate = dateObj.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});

        // Validate if there are existing appointments on this date
        const existing = allAppointments.filter(apt => apt.status !== 'Cancelled' && (apt.date === formattedDate || apt.date === dateRaw));
        if (existing.length > 0) {
            alert(`Cannot mark ${formattedDate} as unavailable because there are ${existing.length} active appointment(s) scheduled for this date.`);
            return;
        }

        let blackouts = JSON.parse(localStorage.getItem('unavailableDates') || '[]');
        if (!blackouts.includes(formattedDate)) {
            blackouts.push(formattedDate);
            if(formattedDate !== dateRaw) {
                blackouts.push(dateRaw);
            }
            localStorage.setItem('unavailableDates', JSON.stringify(blackouts));
        }

        closeBlackoutModal();
        initCalendar(); // Refresh calendar to show the new blackout date
        if (window.pushSystemNotification) {
            window.pushSystemNotification('Date Blocked', `${formattedDate} marked as unavailable.`, 'Just Now', true);
        }
    }

    function applyAptFilters() {
        const search = document.getElementById('aptSearch').value.toLowerCase();
        const status = document.getElementById('statusFilter').value;
        
        filteredAppointments = allAppointments.filter(apt => {
            const clientMatch = (apt.client + ' ' + (apt.contact || '') + ' ' + (apt.email || '')).toLowerCase().includes(search);
            const statusMatch = status === 'all' || apt.status === status;
            return clientMatch && statusMatch;
        });

        currentPage = 1;
        renderTable();
    }

    function viewAptDetail(rowElement) {
        currentAptRow = rowElement;
        
        const id = rowElement.dataset.id || rowElement.cells[0].textContent.trim();
        currentAptId = id;
        const status = rowElement.dataset.status;
        const clientText = rowElement.cells[1].querySelector('div:first-child').textContent;
        const contactText = rowElement.dataset.contact || 'No Contact';
        const emailText = rowElement.dataset.email || 'No Email';
        
        let dateText = rowElement.cells[2].querySelector('div:first-child').textContent;
        let timeText = rowElement.cells[2].querySelector('div:last-child').textContent;

        const inquiryType = rowElement.dataset.type || 'Site Visit / Lot Viewing';
        const reportData = rowElement.dataset.report || '';

        document.getElementById('modalAptId').textContent = id;
        document.getElementById('modalClient').textContent = clientText;
        document.getElementById('modalContact').textContent = contactText;
        document.getElementById('modalEmail').textContent = emailText;
        
        // Parse the dynamic format
        document.getElementById('modalDateTime').textContent = `${dateText} at ${timeText}`;
        document.getElementById('modalInquiryType').textContent = inquiryType;

        const reportSect = document.getElementById('modalReportSection');
        if (reportData) {
            try {
                // Determine if it's the old complex JSON payload
                const r = JSON.parse(reportData);
                document.getElementById('modalReportData').textContent = r.notes || reportData;
            } catch(e) {
                // It's just a simple string now
                document.getElementById('modalReportData').textContent = reportData;
            }
            reportSect.style.display = 'block';
        } else {
            reportSect.style.display = 'none';
        }

        let badgeHtml = '';
        const actionsDiv = document.getElementById('modalActions');
        actionsDiv.innerHTML = '';

        if(status === 'Pending') {
            badgeHtml = `<span class="badge" style="background: #fef3c7; color: #d97706;">Pending</span>`;
            actionsDiv.innerHTML = `
                <button class="btn btn-primary" style="flex: 1; justify-content: center;" onclick="changeState('Scheduled', true)">Approve & Schedule Tour</button>
                <button class="btn btn-outline" style="flex: 1; justify-content: center; color: var(--bill-danger); border-color: #fca5a5;" onclick="openCancelModal()">Deny Inquiry</button>
            `;
        } else if(status === 'Scheduled') {
            badgeHtml = `<span class="badge" style="background: #e0e7ff; color: #4f46e5;">Scheduled</span>`;
            actionsDiv.innerHTML = `
                <button class="btn btn-success" style="flex: 1; justify-content: center;" onclick="openReportModal()">Generate Visit Report (Mark Complete)</button>
                <button class="btn btn-outline" style="flex: 1; justify-content: center; color: var(--bill-danger); border-color: #fca5a5;" onclick="openCancelModal()">Cancel Appt</button>
            `;
        } else if(status === 'Completed') {
            badgeHtml = `<span class="badge badge-success">Completed</span>`;
        } else {
            badgeHtml = `<span class="badge badge-danger">Cancelled</span>`;
        }

        document.getElementById('modalStatusBadge').innerHTML = badgeHtml;
        document.getElementById('aptModal').style.display = 'flex';
    }

    function closeAptModal() {
        document.getElementById('aptModal').style.display = 'none';
    }

    // Modal Triggers
    function openReportModal() {
        if(!currentAptRow) return;
        document.getElementById('reportForm').reset();
        document.getElementById('reportModal').style.display = 'flex';
    }

    function closeReportModal() {
        document.getElementById('reportModal').style.display = 'none';
    }

    function submitReport(e) {
        e.preventDefault();
        const notes = document.getElementById('reportNotes').value;
        
        currentAptRow.dataset.report = notes;
        closeReportModal();
        changeState('Completed');
        
        if (window.pushSystemNotification) {
            window.pushSystemNotification("Visit Report Submitted", `Visit completed for ${currentAptId}.`, 'Just Now', true);
        }
    }

    function openCancelModal() {
        if(!currentAptRow) return;
        document.getElementById('cancelForm').reset();
        document.getElementById('cancelModal').style.display = 'flex';
    }

    function closeCancelModal() {
        document.getElementById('cancelModal').style.display = 'none';
    }

    function submitCancel(e) {
        e.preventDefault();
        const reason = document.getElementById('cancelReason').value;
        const sendEmail = document.getElementById('cancelEmail').checked;
        
        closeCancelModal();
        changeState('Cancelled');
        
        if (sendEmail && currentAptId) {
            const aptObj = SubdivisionStore.getAppointments().find(a => a.id === currentAptId);
            const emailToSend = (aptObj && aptObj.email) ? aptObj.email : 'eighty6pharmacy@gmail.com';
            const clientName = (aptObj && aptObj.client) ? aptObj.client : 'Valued Client';

            fetch('/api/send-cancellation-email', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    email: emailToSend,
                    name: clientName,
                    reason: reason
                })
            }).then(() => {
                alert(`Cancellation logged.\nAn automated email detailing "${reason}" has been dispatched to ${emailToSend}.`);
            }).catch(err => console.error("Cancellation email error:", err));
        }
    }

    function changeState(newState, generatePin = false) {
        if (!currentAptRow || !currentAptId) return;

        SubdivisionStore.updateAppointmentStatus(currentAptId, newState, {
            report: currentAptRow.dataset.report || ''
        });

        if (newState === 'Scheduled' && generatePin) {
            const clientName = currentAptRow.cells[1].querySelector('div:first-child').textContent;
            generateAppointmentPin(currentAptId, clientName);
        }

        renderAppointmentsFromStore();
        closeAptModal();
        
        if (newState === 'Scheduled' && window.pushSystemNotification) {
            window.pushSystemNotification("Pipeline Updated", `Inquiry ${currentAptId} shifted to ${newState}.`, 'Just Now', false);
        }
    }

    // Close modal on outside click
    window.onclick = function(event) {
        const modal = document.getElementById('aptModal');
        if (event.target == modal) closeAptModal();
    }
</script>
@endsection
