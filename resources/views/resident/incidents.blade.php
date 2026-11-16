@extends('layouts.resident')

@section('title', 'Community Incident Reports')

@section('content')
<link rel="stylesheet" href="{{ asset('css/resident-incidents.css') }}">
<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<div class="fade-in">
@php
    $emergencyNumber = !empty($dutyContact['phone']) ? $dutyContact['phone'] : ($adminPhone ?? null);
    $contactName = $dutyContact['name'] ?? null;
    $roleLabel = $dutyContact['role_label'] ?? 'Emergency Hotline';
    $isGuard = !empty($dutyContact['is_guard']);
@endphp
@if(!empty($emergencyNumber))
<a href="tel:{{ $emergencyNumber }}" id="emergencyCallBtn" style="
    display: inline-flex;
    align-items: center;
    gap: 12px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #fff;
    font-size: 14px;
    font-weight: 800;
    padding: 12px 24px;
    border-radius: 14px;
    text-decoration: none;
    margin-bottom: 24px;
    box-shadow: 0 6px 24px rgba(239,68,68,0.4);
    border: 2px solid #b91c1c;
    letter-spacing: 0.01em;
    animation: emergencyPulse 1.6s ease-in-out infinite;
    width: fit-content;
    transition: all 0.2s ease;
">
    <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
        </svg>
    </div>
    <div style="display: flex; flex-direction: column; text-align: left; line-height: 1.25;">
        <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; opacity: 0.95; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {{ $roleLabel }}
        </span>
        <span style="font-size: 15px; font-weight: 800; margin-top: 2px;">
            @if($contactName) {{ $contactName }} &bull; @endif{{ $emergencyNumber }}
        </span>
    </div>
</a>
<style>
@keyframes emergencyPulse {
    0%, 100% { box-shadow: 0 4px 20px rgba(239,68,68,0.45); transform: scale(1); }
    50% { box-shadow: 0 6px 32px rgba(239,68,68,0.75); transform: scale(1.025); }
}
#emergencyCallBtn:hover {
    transform: translateY(-2px) scale(1.03);
    box-shadow: 0 8px 30px rgba(220,38,38,0.6);
}
</style>
@endif
    <!-- Header Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 800; color: #0f172a; margin: 0;">Community Support</h1>
            <p style="color: #64748b; margin-top: 4px;">Track your reported issues and help maintain our shared environment.</p>
        </div>
        <button class="btn btn-primary" onclick="openReportModal()" style="background: var(--res-primary); border-color: var(--res-primary); padding: 12px 24px; font-weight: 700; border-radius: 12px; display: flex; align-items: center; gap: 8px;">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
            Report New Issue
        </button>
    </div>

    <!-- Stats Row -->
    <div class="stats-grid">
        <div class="stat-box">
            <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Reported</div>
            <div style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ count($incidents) }}</div>
        </div>
        <div class="stat-box">
            <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Active Issues</div>
            <div style="font-size: 24px; font-weight: 800; color: #3b82f6; margin-top: 4px;">{{ collect($incidents)->where('status', '!=', 'resolved')->count() }}</div>
        </div>
        <div class="stat-box">
            <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Resolved</div>
            <div style="font-size: 24px; font-weight: 800; color: #10b981; margin-top: 4px;">{{ collect($incidents)->where('status', 'resolved')->count() }}</div>
        </div>
    </div>

    <!-- Incident List & Calendar View Container -->
    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">My Reported Incidents</h2>
                <p style="font-size: 12px; color: #64748b; margin: 2px 0 0 0;">View your past reports in a list or track them on the interactive monthly calendar.</p>
            </div>
            <div style="display: inline-flex; background: #e2e8f0; padding: 3px; border-radius: 10px; gap: 3px;">
                <button type="button" id="resTabListBtn" onclick="switchResidentView('list')" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; font-size: 12px; font-weight: 700; border-radius: 7px; border: none; cursor: pointer; transition: all 0.15s; background: #fff; color: #0f172a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    List View
                </button>
                <button type="button" id="resTabCalBtn" onclick="switchResidentView('calendar')" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; font-size: 12px; font-weight: 700; border-radius: 7px; border: none; cursor: pointer; transition: all 0.15s; background: transparent; color: #64748b;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Calendar View
                </button>
            </div>
        </div>

        <!-- Resident Calendar View -->
        <div id="residentIncidentCalendar" style="display: none; padding: 20px 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" class="btn btn-outline" onclick="resCalPrev()" style="padding: 6px 12px; font-size: 13px; font-weight: 700;">‹ Prev</button>
                    <button type="button" class="btn btn-outline" onclick="resCalToday()" style="padding: 6px 12px; font-size: 13px; font-weight: 700;">Today</button>
                    <button type="button" class="btn btn-outline" onclick="resCalNext()" style="padding: 6px 12px; font-size: 13px; font-weight: 700;">Next ›</button>
                    <span id="resCalMonthTitle" style="font-size: 18px; font-weight: 800; color: #0f172a; margin-left: 8px;"></span>
                </div>
                <div style="display: flex; gap: 8px; font-size: 12px; align-items: center;">
                    <span style="display: inline-flex; align-items: center; gap: 4px; font-weight: 700; color: #2563eb;">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #3b82f6;"></span> In Progress
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 4px; font-weight: 700; color: #059669; margin-left: 8px;">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span> Resolved
                    </span>
                </div>
            </div>
            <div class="res-cal-grid" id="resCalGrid">
                <!-- JS populated -->
            </div>
        </div>

        <div id="residentIncidentList" style="padding: 12px 24px;">
            @forelse($incidents as $inc)
            @php
                $incSub = is_array($inc) ? ($inc['subject'] ?? ($inc['sub'] ?? 'Untitled Incident')) : ($inc->subject ?? 'Untitled Incident');
                $incDesc = is_array($inc) ? ($inc['description'] ?? ($inc['desc'] ?? '')) : ($inc->description ?? '');
                $incType = is_array($inc) ? ($inc['type'] ?? 'General') : ($inc->type ?? 'General');
                $incId = is_array($inc) ? ($inc['id'] ?? '') : ($inc->id ?? '');
                $incStatus = strtolower(is_array($inc) ? ($inc['status'] ?? 'progress') : ($inc->status ?? 'progress'));
                if ($incStatus === 'pending') $incStatus = 'progress';
                $statusLabel = $incStatus === 'resolved' ? 'Resolved' : 'In Progress';
                $incDate = is_array($inc) ? ($inc['created_at'] ?? null) : ($inc->created_at ?? null);
                $timeAgo = $incDate ? \Carbon\Carbon::parse($incDate)->diffForHumans() : 'Recently';
                $refDisplay = is_numeric($incId) ? 'INC-' . str_pad($incId, 5, '0', STR_PAD_LEFT) : $incId;
            @endphp
            <div class="incident-card">
                <div class="incident-info">
                    <span style="font-size: 11px; font-weight: 700; color: #3b82f6; text-transform: uppercase;">{{ $incType }}</span>
                    <h3>{{ $incSub }}</h3>
                    <p>{{ \Illuminate\Support\Str::limit($incDesc, 80) }}</p>
                    <div style="margin-top: 12px; display: flex; align-items: center; gap: 12px; font-size: 12px; color: #94a3b8; font-weight: 600;">
                        <span>Ref: {{ $refDisplay }}</span>
                        <span>•</span>
                        <span>{{ $timeAgo }}</span>
                    </div>
                </div>
                <div class="incident-status">
                    <span class="badge-status badge-{{ $incStatus }}">{{ $statusLabel }}</span>
                    <button class="btn btn-outline" onclick="openDetailsModal('{{ $incId }}')" style="padding: 4px 12px; font-size: 11px; border-radius: 6px;">View Details</button>
                </div>
            </div>
            @empty
            <div style="padding: 48px; text-align: center; color: #64748b;">
                <div style="width: 56px; height: 56px; border-radius: 14px; background: #f1f5f9; color: #94a3b8; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                </div>
                <h3 style="font-size: 18px; font-weight: 700; color: #1e293b; margin: 0;">No Incidents Found</h3>
                <p style="margin-top: 8px;">You haven't reported any issues yet. Click "Report New Issue" to get started.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Report New Incident Modal -->
<div id="reportModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Report Community Issue</h2>
            <button onclick="closeReportModal()" style="background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer;">✕</button>
        </div>
        <div class="modal-body">
            <!-- Step 1: Select Category -->
            <div id="formStep1">
                <div class="form-group">
                    <label>What type of issue is this?</label>
                    <div class="category-grid">
                        <div class="category-opt" onclick="selectCategory(this, 'Security')">
                            <span class="category-icon" style="color: #3b82f6;">
                                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin: 0 auto 4px; display: block;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            </span>
                            <span class="category-label">Security</span>
                        </div>
                        <div class="category-opt" onclick="selectCategory(this, 'Maintenance')">
                            <span class="category-icon" style="color: #f59e0b;">
                                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin: 0 auto 4px; display: block;"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
                            </span>
                            <span class="category-label">Maintenance</span>
                        </div>
                        <div class="category-opt" onclick="selectCategory(this, 'Utilities')">
                            <span class="category-icon" style="color: #10b981;">
                                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin: 0 auto 4px; display: block;"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </span>
                            <span class="category-label">Utilities</span>
                        </div>
                        <div class="category-opt" onclick="selectCategory(this, 'Others')">
                            <span class="category-icon" style="color: #64748b;">
                                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin: 0 auto 4px; display: block;"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                            </span>
                            <span class="category-label">Others</span>
                        </div>
                    </div>
                    <input type="hidden" id="reportType">
                </div>

                <!-- Incident Date & Time with Calendar Picker -->
                <div class="form-group" style="margin-top: 14px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label style="margin: 0; font-size: 13px; font-weight: 700; color: #1e293b;">Incident Date &amp; Time</label>
                        <span style="font-size: 11px; color: #64748b; font-weight: 600;">When did this incident happen?</span>
                    </div>
                    <div style="display: flex; gap: 6px; margin-bottom: 8px; flex-wrap: wrap;">
                        <button type="button" class="incident-time-preset-btn active" id="presetNow" onclick="setIncidentTimePreset('now')">⚡ Just Now</button>
                        <button type="button" class="incident-time-preset-btn" id="preset1h" onclick="setIncidentTimePreset('1h')">🕒 1 Hour Ago</button>
                        <button type="button" class="incident-time-preset-btn" id="presetMorning" onclick="setIncidentTimePreset('today_morning')">🌅 Earlier Today</button>
                        <button type="button" class="incident-time-preset-btn" id="presetYesterday" onclick="setIncidentTimePreset('yesterday')">📅 Yesterday</button>
                    </div>
                    <div style="position: relative;">
                        <input type="datetime-local" id="reportIncidentDateTime" class="form-input" style="padding: 10px 14px; font-size: 13px; font-weight: 600; width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff;" max="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label>Incident Subject</label>
                    <input type="text" id="reportSubject" class="form-input" placeholder="e.g., Streetlight out near Gate 1">
                </div>
                <div class="form-group">
                    <label>Detailed Description</label>
                    <textarea id="reportDesc" class="form-textarea" placeholder="Please provide as much information as possible..."></textarea>
                </div>

                <div class="form-group">
                    <label>Attach Photos (Optional — Max 3 photos, 5MB each)</label>
                    <input type="file" id="reportPhotos" accept="image/*" multiple style="display: none;" onchange="handleFilesUpload(this)">
                    <div class="photo-upload-zone" onclick="document.getElementById('reportPhotos').click()" style="cursor: pointer;">
                        <div id="uploadText">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin: 0 auto 8px; display: block;"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M16 8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Click to upload photos
                        </div>
                        <div id="uploadPreview" style="display: none; flex-wrap: wrap; gap: 8px; margin-top: 12px; justify-content: center;">
                            <!-- JS injected previews -->
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Attach Video (Optional — Max 1 video, up to 100MB, duration &le; 60s)</label>
                    <input type="file" id="reportVideo" accept="video/mp4,video/webm,video/quicktime,video/*" style="display: none;" onchange="handleVideoUpload(this)">
                    <div class="photo-upload-zone" id="videoUploadZone" onclick="document.getElementById('reportVideo').click()" style="cursor: pointer; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 14px; text-align: center; background: #f8fafc; transition: all 0.2s;">
                        <div id="videoUploadText">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin: 0 auto 6px; display: block; color: #3b82f6;"><path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span style="font-weight: 700; color: #1e293b; font-size: 13px;">Click to upload video evidence</span>
                            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">MP4, WebM, MOV</div>
                        </div>

                        <!-- In-Browser Video Compression Progress (Option 2) -->
                        <div id="videoCompressingState" style="display: none; text-align: left; background: #fff; padding: 12px 14px; border-radius: 8px; border: 1px solid #bfdbfe;" onclick="event.stopPropagation()">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 14px; height: 14px; border: 2px solid #3b82f6; border-top-color: transparent; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
                                    <span id="compressStatusText" style="font-size: 12px; font-weight: 700; color: #1e40af;">Compressing video... 0%</span>
                                </div>
                                <button type="button" onclick="cancelVideoCompression(event)" style="background: none; border: none; font-size: 11px; font-weight: 700; color: #ef4444; cursor: pointer;">✕ Cancel</button>
                            </div>
                            <div style="width: 100%; height: 6px; background: #e2e8f0; border-radius: 999px; overflow: hidden;">
                                <div id="compressProgressBar" style="width: 0%; height: 100%; background: #3b82f6; transition: width 0.2s ease;"></div>
                            </div>
                            <div style="font-size: 10.5px; color: #64748b; margin-top: 6px; display: flex; justify-content: space-between;">
                                <span>Optimizing resolution & bitrate</span>
                                <span id="compressSizeEstimate">Original: -- MB</span>
                            </div>
                        </div>

                        <div id="videoUploadPreview" style="display: none; align-items: center; justify-content: space-between; background: #fff; padding: 10px 14px; border-radius: 8px; border: 1px solid #e2e8f0;" onclick="event.stopPropagation()">
                            <div style="display: flex; align-items: center; gap: 10px; text-align: left; overflow: hidden;">
                                <div style="width: 36px; height: 36px; border-radius: 8px; background: #eff6ff; display: flex; align-items: center; justify-content: center; color: #3b82f6; flex-shrink: 0;">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                </div>
                                <div style="overflow: hidden;">
                                    <div id="videoFileName" style="font-size: 13px; font-weight: 700; color: #0f172a; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; max-width: 250px;">video.mp4</div>
                                    <div id="videoMeta" style="font-size: 11px; color: #64748b;">0s &bull; 0 MB</div>
                                </div>
                            </div>
                            <button type="button" onclick="removeVideo(event)" style="background: #ef4444; color: #fff; border: none; border-radius: 6px; padding: 6px 12px; font-size: 11px; font-weight: 700; cursor: pointer;">Remove</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success State -->
            <div id="formSuccess" style="display: none; text-align: center; padding: 24px 0;">
                <div style="width: 64px; height: 64px; background: #d1fae5; color: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                </div>
                <h3 style="font-size: 20px; font-weight: 800; color: #1e293b; margin: 0;">Report Submitted</h3>
                <p style="color: #64748b; margin-top: 8px;">Our maintenance and security team will review your report shortly. Your reference ID is <span id="successReferenceId" style="font-family: monospace; font-weight: 800; color: #0f172a;">INC-...</span>.</p>
            </div>
        </div>
        <div class="modal-footer" id="modalFooter">
            <button class="btn btn-outline" onclick="closeReportModal()">Cancel</button>
            <button class="btn btn-primary" id="btnSubmitReport" onclick="submitReport()" style="background: var(--res-primary); border-color: var(--res-primary); padding: 10px 24px; font-weight: 700;">Submit Report</button>
        </div>
    </div>
</div>

<!-- View Details Modal (UI ONLY) -->
<div id="detailsModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <div>
                <span id="detType" style="font-size: 11px; font-weight: 700; color: var(--res-primary); text-transform: uppercase;">MAINTENANCE</span>
                <h2 id="detSub" style="margin-top: 4px;">Incident Details</h2>
            </div>
            <button onclick="closeDetailsModal()" style="background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer;">✕</button>
        </div>
        <div class="modal-body" style="padding-top: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div style="font-size: 13px; color: #64748b;">Reference ID: <strong id="detId" style="color: #0f172a;">INC-1001</strong></div>
                <span id="detStatus" class="badge-status badge-progress">In Progress</span>
            </div>

            <div style="margin-bottom: 24px;">
                <h4 style="font-size: 12px; text-transform: uppercase; color: #94a3b8; margin-bottom: 8px;">Description</h4>
                <p id="detDesc" style="font-size: 14px; color: #1e293b; line-height: 1.6; background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;"></p>
            </div>

            <div style="margin-bottom: 24px;" id="detPhotosContainer">
                <h4 style="font-size: 12px; text-transform: uppercase; color: #94a3b8; margin-bottom: 8px;">Attached Photos</h4>
                <div id="detPhotos" style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 8px;">
                    <!-- JS injected -->
                </div>
            </div>

            <div style="margin-bottom: 24px; display: none;" id="detVideoContainer">
                <h4 style="font-size: 12px; text-transform: uppercase; color: #94a3b8; margin-bottom: 8px;">Attached Video Evidence</h4>
                <div id="detVideoWrapper" style="border-radius: 12px; overflow: hidden; background: #000;">
                    <!-- JS injected video -->
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <h4 style="font-size: 12px; text-transform: uppercase; color: #94a3b8; margin-bottom: 12px;">Timeline & Updates</h4>
                <div id="detTimelineContainer" style="display: flex; flex-direction: column; gap: 16px;">
                    <!-- JS injected timeline -->
                </div>
            </div>

        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" style="width: 100%; justify-content: center;" onclick="closeDetailsModal()">Close Detail View</button>
        </div>
    </div>
</div>

<!-- Fullscreen Image Viewer -->
<div id="fullscreenImageViewer" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 9999; align-items: center; justify-content: center; flex-direction: column;">
    <button onclick="closeFullscreenImage()" style="position: absolute; top: 20px; right: 20px; background: none; border: none; color: white; font-size: 36px; cursor: pointer;">&times;</button>
    <img id="fullscreenImage" src="" style="max-width: 90%; max-height: 90%; object-fit: contain; border-radius: 8px;">
</div>

<script>
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Inject DB data
    const dbIncidents = @json($incidents);
    // Map DB fields to what JS expects
    const mappedIncidents = (dbIncidents || []).map(inc => {
        let parsedPhotos = [];
        if (inc.image_url) {
            try {
                parsedPhotos = typeof inc.image_url === 'string' ? JSON.parse(inc.image_url) : inc.image_url;
            } catch(e) {
                parsedPhotos = [inc.image_url];
            }
        }
        if (!Array.isArray(parsedPhotos)) {
            parsedPhotos = parsedPhotos ? [parsedPhotos] : [];
        }

        const rawId = (inc.id || '').toString();
        const refId = inc.reference_id || (rawId && !isNaN(rawId) ? 'INC-' + String(rawId).padStart(5, '0') : (rawId ? 'INC-' + rawId : ''));

        return {
            id: rawId,
            refId: refId,
            sub: inc.subject || inc.sub || 'Untitled Incident',
            type: inc.type || 'General',
            date: inc.created_at || inc.date,
            updated: inc.updated_at,
            desc: inc.description || inc.desc || 'No description provided.',
            status: ((inc.status || 'progress').toLowerCase() === 'pending' ? 'progress' : (inc.status || 'progress').toLowerCase()),
            photos: parsedPhotos,
            video: inc.video_url || inc.video || null,
        };
    });

    document.addEventListener('DOMContentLoaded', () => {
        renderResidentIncidents();
    });

    function renderResidentIncidents() {
        const incidents = mappedIncidents;
        const container = document.getElementById('residentIncidentList');
        if (!container) return;

        container.innerHTML = '';
        if (incidents.length === 0) {
            container.innerHTML = `
                <div style="padding: 48px; text-align: center; color: #64748b;">
                    <div style="width: 56px; height: 56px; border-radius: 14px; background: #f1f5f9; color: #94a3b8; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                        <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 700; color: #1e293b; margin: 0;">No Incidents Found</h3>
                    <p style="margin-top: 8px;">You haven't reported any issues yet. Click "Report New Issue" to get started.</p>
                </div>
            `;
            return;
        }

        incidents.forEach(inc => {
            const card = document.createElement('div');
            card.className = 'incident-card';
            const statusClass = inc.status === 'resolved' ? 'resolved' : 'progress';
            const statusLabel = statusClass === 'resolved' ? 'Resolved' : 'In Progress';
            const shortDesc = inc.desc.length > 80 ? inc.desc.substring(0, 80) + '...' : inc.desc;
            
            const mediaBadges = [];
            if (inc.photos && inc.photos.length > 0) {
                mediaBadges.push(`<span>📷 ${inc.photos.length} photo${inc.photos.length > 1 ? 's' : ''}</span>`);
            }
            if (inc.video) {
                mediaBadges.push(`<span>🎥 Video</span>`);
            }
            const mediaInfo = mediaBadges.length > 0 ? `<span>•</span>${mediaBadges.join(' ')}` : '';

            card.innerHTML = `
                <div class="incident-info">
                    <span style="font-size: 11px; font-weight: 700; color: #3b82f6; text-transform: uppercase;">${escapeHtml(inc.type)}</span>
                    <h3>${escapeHtml(inc.sub)}</h3>
                    <p>${escapeHtml(shortDesc)}</p>
                    <div style="margin-top: 12px; display: flex; align-items: center; gap: 12px; font-size: 12px; color: #94a3b8; font-weight: 600; flex-wrap: wrap;">
                        <span>Ref: ${escapeHtml(inc.refId || inc.id)}</span>
                        <span>•</span>
                        <span>${inc.date ? new Date(inc.date).toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric', hour:'numeric', minute:'2-digit'}) : 'Recently'}</span>
                        ${mediaInfo}
                    </div>
                </div>
                <div class="incident-status">
                    <span class="badge-status badge-${statusClass}">${escapeHtml(statusLabel)}</span>
                    <button class="btn btn-outline" onclick="openDetailsModal('${inc.id}')" style="padding: 4px 12px; font-size: 11px; border-radius: 6px;">View Details</button>
                </div>
            `;
            container.appendChild(card);
        });
    }

    function setIncidentTimePreset(preset) {
        document.querySelectorAll('.incident-time-preset-btn').forEach(btn => btn.classList.remove('active'));
        const input = document.getElementById('reportIncidentDateTime');
        if (!input) return;
        const now = new Date();
        let target = new Date();

        if (preset === 'now') {
            document.getElementById('presetNow')?.classList.add('active');
            target = now;
        } else if (preset === '1h') {
            document.getElementById('preset1h')?.classList.add('active');
            target = new Date(now.getTime() - 60 * 60 * 1000);
        } else if (preset === 'today_morning') {
            document.getElementById('presetMorning')?.classList.add('active');
            target.setHours(9, 0, 0, 0);
            if (target > now) target = new Date(now.getTime() - 2 * 60 * 60 * 1000);
        } else if (preset === 'yesterday') {
            document.getElementById('presetYesterday')?.classList.add('active');
            target.setDate(target.getDate() - 1);
        }

        const yyyy = target.getFullYear();
        const mm = String(target.getMonth() + 1).padStart(2, '0');
        const dd = String(target.getDate()).padStart(2, '0');
        const hh = String(target.getHours()).padStart(2, '0');
        const min = String(target.getMinutes()).padStart(2, '0');
        input.value = `${yyyy}-${mm}-${dd}T${hh}:${min}`;
    }

    let resCalDate = new Date();
    let resCurrentView = 'list';

    function switchResidentView(mode) {
        resCurrentView = mode;
        const listEl = document.getElementById('residentIncidentList');
        const calEl = document.getElementById('residentIncidentCalendar');
        const btnList = document.getElementById('resTabListBtn');
        const btnCal = document.getElementById('resTabCalBtn');

        if (mode === 'calendar') {
            listEl.style.display = 'none';
            calEl.style.display = 'block';
            btnList.style.background = 'transparent';
            btnList.style.color = '#64748b';
            btnList.style.boxShadow = 'none';
            btnCal.style.background = '#fff';
            btnCal.style.color = '#0f172a';
            btnCal.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
            renderResidentCalendar();
        } else {
            listEl.style.display = 'block';
            calEl.style.display = 'none';
            btnCal.style.background = 'transparent';
            btnCal.style.color = '#64748b';
            btnCal.style.boxShadow = 'none';
            btnList.style.background = '#fff';
            btnList.style.color = '#0f172a';
            btnList.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
        }
    }

    function resCalPrev() {
        resCalDate.setMonth(resCalDate.getMonth() - 1);
        renderResidentCalendar();
    }

    function resCalNext() {
        resCalDate.setMonth(resCalDate.getMonth() + 1);
        renderResidentCalendar();
    }

    function resCalToday() {
        resCalDate = new Date();
        renderResidentCalendar();
    }

    function renderResidentCalendar() {
        const titleEl = document.getElementById('resCalMonthTitle');
        const gridEl = document.getElementById('resCalGrid');
        if (!gridEl) return;

        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        const year = resCalDate.getFullYear();
        const month = resCalDate.getMonth();

        if (titleEl) {
            titleEl.textContent = `${monthNames[month]} ${year}`;
        }

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const startDayIndex = firstDay.getDay();
        const totalDays = lastDay.getDate();
        const prevMonthLastDay = new Date(year, month, 0).getDate();

        const incidentsByDate = {};
        (mappedIncidents || []).forEach(inc => {
            if (!inc.date) return;
            const d = new Date(inc.date);
            if (isNaN(d.getTime())) return;
            const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
            if (!incidentsByDate[key]) incidentsByDate[key] = [];
            incidentsByDate[key].push(inc);
        });

        const dow = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        let html = dow.map(d => `<div class="res-cal-dow">${d}</div>`).join('');

        const today = new Date();
        const isCurrentMonth = today.getFullYear() === year && today.getMonth() === month;

        for (let i = startDayIndex - 1; i >= 0; i--) {
            const dNum = prevMonthLastDay - i;
            html += `<div class="res-cal-day other-month"><div class="res-cal-day-num">${dNum}</div></div>`;
        }

        for (let d = 1; d <= totalDays; d++) {
            const dateKey = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const isToday = isCurrentMonth && today.getDate() === d;
            const dayIncidents = incidentsByDate[dateKey] || [];

            let incChips = '';
            dayIncidents.slice(0, 3).forEach(inc => {
                const isResolved = (inc.status || '').toLowerCase() === 'resolved';
                const bg = isResolved ? '#d1fae5' : '#dbeafe';
                const color = isResolved ? '#065f46' : '#1e40af';
                const border = isResolved ? '#a7f3d0' : '#bfdbfe';
                incChips += `
                    <div onclick="event.stopPropagation(); openDetailsModal('${inc.id}')" style="background: ${bg}; border: 1px solid ${border}; color: ${color}; padding: 2px 6px; border-radius: 6px; font-size: 10px; font-weight: 700; display: flex; align-items: center; gap: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-top: 2px; cursor: pointer;" title="${escapeHtml(inc.sub)}">
                        <span style="width: 5px; height: 5px; border-radius: 50%; background: ${isResolved ? '#10b981' : '#3b82f6'}; flex-shrink: 0;"></span>
                        <span style="overflow: hidden; text-overflow: ellipsis;">${escapeHtml(inc.sub)}</span>
                    </div>
                `;
            });

            if (dayIncidents.length > 3) {
                incChips += `<div style="font-size: 9px; font-weight: 800; color: #3b82f6; margin-top: 2px;">+${dayIncidents.length - 3} more</div>`;
            }

            html += `
                <div class="res-cal-day ${isToday ? 'today' : ''}" onclick="if(${dayIncidents.length}>0) openDetailsModal('${dayIncidents[0].id}')">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div class="res-cal-day-num">${d}</div>
                        ${dayIncidents.length > 0 ? `<span style="font-size: 9px; font-weight: 800; background: #eff6ff; color: #1d4ed8; padding: 1px 5px; border-radius: 999px;">${dayIncidents.length}</span>` : ''}
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 2px; overflow: hidden;">
                        ${incChips}
                    </div>
                </div>
            `;
        }

        const currentCells = startDayIndex + totalDays;
        const totalCellsNeeded = currentCells > 35 ? 42 : 35;
        const remaining = totalCellsNeeded - currentCells;
        for (let r = 1; r <= remaining; r++) {
            html += `<div class="res-cal-day other-month"><div class="res-cal-day-num">${r}</div></div>`;
        }

        gridEl.innerHTML = html;
    }

    function openReportModal() {
        if (new URLSearchParams(window.location.search).get('action') !== 'report') {
            window.history.pushState(null, '', '?action=report');
        }
        document.getElementById('reportModal').style.display = 'flex';
        document.getElementById('formStep1').style.display = 'block';
        document.getElementById('formSuccess').style.display = 'none';
        document.getElementById('modalFooter').style.display = 'flex';
        
        document.getElementById('reportSubject').value = '';
        document.getElementById('reportDesc').value = '';
        document.getElementById('reportPhotos').value = '';
        setIncidentTimePreset('now');
        selectedPhotoFiles = [];
        renderPhotoPreview();
        removeVideo();
        
        document.querySelectorAll('.category-opt').forEach(opt => opt.classList.remove('selected'));
        document.getElementById('reportType').value = '';
    }

    function closeReportModal() {
        window.history.replaceState(null, '', window.location.pathname);
        if (typeof cancelVideoCompression === 'function') {
            cancelVideoCompression();
        }
        document.getElementById('reportModal').style.display = 'none';
    }

    function selectCategory(el, category) {
        document.querySelectorAll('.category-opt').forEach(opt => opt.classList.remove('selected'));
        el.classList.add('selected');
        document.getElementById('reportType').value = category;
    }

    // Photo Upload Handling
    let selectedPhotoFiles = [];
    const MAX_PHOTOS = 3;
    const MAX_PHOTO_MB = 5;

    function handleFilesUpload(input) {
        if (!input.files || input.files.length === 0) return;
        const incoming = Array.from(input.files);
        
        for (const file of incoming) {
            if (selectedPhotoFiles.length >= MAX_PHOTOS) {
                Swal.fire({ icon: 'warning', text: `You can only upload a maximum of ${MAX_PHOTOS} photos.` });
                break;
            }
            if (file.size > MAX_PHOTO_MB * 1024 * 1024) {
                Swal.fire({ icon: 'warning', text: `File "${file.name}" exceeds the ${MAX_PHOTO_MB}MB limit.` });
                continue;
            }
            selectedPhotoFiles.push(file);
        }
        input.value = '';
        renderPhotoPreview();
    }

    function renderPhotoPreview() {
        const preview = document.getElementById('uploadPreview');
        const text = document.getElementById('uploadText');
        if (selectedPhotoFiles.length > 0) {
            text.style.display = 'none';
            preview.style.display = 'flex';
            preview.innerHTML = selectedPhotoFiles.map((file, index) => {
                const objectUrl = URL.createObjectURL(file);
                return `
                    <div style="position: relative; display: inline-block; margin: 4px;">
                        <img src="${objectUrl}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 2px solid #e2e8f0;">
                        <button type="button" onclick="removePhoto(event, ${index})" style="position: absolute; top: -6px; right: -6px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 10px; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">✕</button>
                    </div>
                `;
            }).join('');
        } else {
            text.style.display = 'block';
            preview.style.display = 'none';
            preview.innerHTML = '';
        }
    }

    function removePhoto(event, index) {
        event.stopPropagation();
        selectedPhotoFiles.splice(index, 1);
        renderPhotoPreview();
    }

    // ==========================================
    // Option 2: In-Browser Video Compression Engine
    // Downscales 1080p/4K phone clips to 720p HD (~1.3 Mbps)
    // Compresses raw videos up to 100MB down to ~3MB-8MB before sending
    // ==========================================
    let selectedVideoFile = null;
    let activeVideoCompression = null;
    let isCompressingVideo = false;
    let pendingSubmitAfterCompression = false;
    const COMPRESS_THRESHOLD_MB = 8;
    const MAX_RAW_VIDEO_MB = 100;
    const MAX_VIDEO_DURATION_SEC = 60;

    function updateCompressProgress(pct) {
        const bar = document.getElementById('compressProgressBar');
        const txt = document.getElementById('compressStatusText');
        if (bar) bar.style.width = pct + '%';
        if (txt) txt.textContent = `Compressing video... ${pct}%`;
    }

    function cancelVideoCompression(event) {
        if (event) event.stopPropagation();
        if (activeVideoCompression) {
            activeVideoCompression.cancel();
        }
        removeVideo();
    }

    function removeVideo(event) {
        if (event) event.stopPropagation();
        if (activeVideoCompression) {
            activeVideoCompression.cancel();
        }
        selectedVideoFile = null;
        isCompressingVideo = false;
        pendingSubmitAfterCompression = false;
        const input = document.getElementById('reportVideo');
        if (input) input.value = '';
        const txt = document.getElementById('videoUploadText');
        const comp = document.getElementById('videoCompressingState');
        const prev = document.getElementById('videoUploadPreview');
        if (txt) txt.style.display = 'block';
        if (comp) comp.style.display = 'none';
        if (prev) prev.style.display = 'none';
    }

    function getVideoDuration(file) {
        return new Promise((resolve) => {
            const video = document.createElement('video');
            video.preload = 'metadata';
            const url = URL.createObjectURL(file);
            video.src = url;
            video.onloadedmetadata = () => {
                URL.revokeObjectURL(url);
                resolve(video.duration || 0);
            };
            video.onerror = () => {
                URL.revokeObjectURL(url);
                resolve(0); // If browser metadata decode fails, resolve 0 and let server inspect
            };
        });
    }

    async function compressVideo(file, duration) {
        return new Promise(async (resolve, reject) => {
            const hasMediaRecorder = typeof window.MediaRecorder !== 'undefined';
            const hasCaptureStream = HTMLCanvasElement.prototype.captureStream || HTMLCanvasElement.prototype.mozCaptureStream;
            if (!hasMediaRecorder || !hasCaptureStream) {
                return reject(new Error('UNSUPPORTED_BROWSER'));
            }

            // Determine best supported mime type
            const candidateMimes = [
                'video/mp4;codecs=avc1,mp4a.40.2',
                'video/mp4',
                'video/webm;codecs=vp9,opus',
                'video/webm;codecs=vp8,opus',
                'video/webm'
            ];
            let chosenMime = '';
            for (const m of candidateMimes) {
                try {
                    if (MediaRecorder.isTypeSupported(m)) {
                        chosenMime = m;
                        break;
                    }
                } catch(e){}
            }
            if (!chosenMime) chosenMime = 'video/webm';

            let hiddenContainer = document.getElementById('videoCompressorHidden');
            if (!hiddenContainer) {
                hiddenContainer = document.createElement('div');
                hiddenContainer.id = 'videoCompressorHidden';
                hiddenContainer.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:1px;height:1px;opacity:0;pointer-events:none;overflow:hidden;';
                document.body.appendChild(hiddenContainer);
            }
            hiddenContainer.innerHTML = '';

            const video = document.createElement('video');
            video.playsInline = true;
            video.preload = 'auto';
            video.crossOrigin = 'anonymous';

            const blobUrl = URL.createObjectURL(file);
            video.src = blobUrl;
            hiddenContainer.appendChild(video);

            let audioCtx = null;
            let isCancelled = false;
            let watchdogTimer = null;
            let recorder = null;

            const cleanup = () => {
                if (watchdogTimer) clearTimeout(watchdogTimer);
                try { video.pause(); } catch(e){}
                try { if (recorder && recorder.state !== 'inactive') recorder.stop(); } catch(e){}
                try { if (audioCtx && audioCtx.state !== 'closed') audioCtx.close(); } catch(e){}
                try { URL.revokeObjectURL(blobUrl); } catch(e){}
                if (hiddenContainer) hiddenContainer.innerHTML = '';
                isCompressingVideo = false;
                activeVideoCompression = null;
            };

            activeVideoCompression = {
                cancel: () => {
                    isCancelled = true;
                    cleanup();
                    reject(new Error('CANCELLED'));
                }
            };

            await new Promise((res) => {
                if (video.readyState >= 1) return res();
                video.onloadedmetadata = () => res();
                video.onerror = () => res();
            });

            if (isCancelled) return;

            const vidDuration = video.duration || duration || 1;
            
            // Calculate target dimensions (max 1280px on longest dimension)
            let origW = video.videoWidth || 1280;
            let origH = video.videoHeight || 720;
            let targetW = origW;
            let targetH = origH;
            const maxDim = 1280;

            if (targetW > maxDim || targetH > maxDim) {
                if (targetW >= targetH) {
                    targetH = Math.round((targetH * maxDim) / targetW);
                    targetW = maxDim;
                } else {
                    targetW = Math.round((targetW * maxDim) / targetH);
                    targetH = maxDim;
                }
            }
            // Hardware encoders require even pixel dimensions
            targetW = targetW % 2 === 0 ? targetW : targetW - 1;
            targetH = targetH % 2 === 0 ? targetH : targetH - 1;

            const canvas = document.createElement('canvas');
            canvas.width = targetW;
            canvas.height = targetH;
            const ctx = canvas.getContext('2d', { alpha: false });

            const canvasStream = canvas.captureStream ? canvas.captureStream(30) : canvas.mozCaptureStream(30);

            // Audio track processing via Web Audio API
            let combinedStream = canvasStream;
            try {
                const AudioCtxClass = window.AudioContext || window.webkitAudioContext;
                if (AudioCtxClass) {
                    audioCtx = new AudioCtxClass();
                    if (audioCtx.state === 'suspended') {
                        await audioCtx.resume();
                    }
                    const source = audioCtx.createMediaElementSource(video);
                    const dest = audioCtx.createMediaStreamDestination();
                    source.connect(dest);
                    // Do NOT connect to audioCtx.destination to prevent speaker noise
                    const audioTracks = dest.stream.getAudioTracks();
                    if (audioTracks && audioTracks.length > 0) {
                        combinedStream = new MediaStream([
                            ...canvasStream.getVideoTracks(),
                            audioTracks[0]
                        ]);
                    }
                }
            } catch (audioErr) {
                console.warn('Audio capture setup failed, continuing with video only:', audioErr);
            }

            const recordedChunks = [];
            try {
                recorder = new MediaRecorder(combinedStream, {
                    mimeType: chosenMime,
                    videoBitsPerSecond: 1300000 // 1.3 Mbps
                });
            } catch (e) {
                try {
                    recorder = new MediaRecorder(combinedStream);
                } catch (recErr) {
                    cleanup();
                    return reject(recErr);
                }
            }

            recorder.ondataavailable = (e) => {
                if (e.data && e.data.size > 0) {
                    recordedChunks.push(e.data);
                }
            };

            recorder.onstop = () => {
                if (isCancelled) return;
                cleanup();
                const outExt = chosenMime.includes('mp4') ? 'mp4' : 'webm';
                const compressedBlob = new Blob(recordedChunks, { type: chosenMime });
                const finalFile = new File([compressedBlob], `incident_clip_${Date.now()}.${outExt}`, { type: chosenMime });
                resolve(finalFile);
            };

            const renderFrame = () => {
                if (isCancelled || video.paused || video.ended) return;
                ctx.drawImage(video, 0, 0, targetW, targetH);
                const pct = Math.min(99, Math.round((video.currentTime / vidDuration) * 100));
                updateCompressProgress(pct);

                if ('requestVideoFrameCallback' in video) {
                    video.requestVideoFrameCallback(renderFrame);
                } else {
                    requestAnimationFrame(renderFrame);
                }
            };

            video.ontimeupdate = () => {
                if (video.currentTime >= vidDuration - 0.25) {
                    if (!video.ended) {
                        video.pause();
                        video.onended();
                    }
                }
            };

            video.onended = () => {
                updateCompressProgress(100);
                setTimeout(() => {
                    if (recorder && recorder.state !== 'inactive') {
                        recorder.stop();
                    }
                }, 200);
            };

            // Watchdog failsafe timer
            watchdogTimer = setTimeout(() => {
                if (recorder && recorder.state !== 'inactive') {
                    console.warn('Compression watchdog safety stop triggered');
                    recorder.stop();
                }
            }, (vidDuration + 6) * 1000);

            recorder.start(250);

            // Attempt unmuted playback for sound capture; fallback to muted if browser blocks
            video.muted = false;
            let playPromise = video.play();
            if (playPromise !== undefined) {
                playPromise.then(() => {
                    renderFrame();
                }).catch(playErr => {
                    console.warn('Playback with audio blocked by autoplay, retrying muted:', playErr);
                    video.muted = true;
                    video.play().then(() => {
                        renderFrame();
                    }).catch(fatalErr => {
                        cleanup();
                        reject(fatalErr);
                    });
                });
            }
        });
    }

    async function handleVideoUpload(input) {
        if (!input.files || input.files.length === 0) return;
        const file = input.files[0];
        
        // 1. Raw file size check (100MB limit)
        if (file.size > MAX_RAW_VIDEO_MB * 1024 * 1024) {
            Swal.fire({ icon: 'warning', text: `Video file "${file.name}" exceeds the ${MAX_RAW_VIDEO_MB}MB limit.` });
            input.value = '';
            return;
        }

        // 2. Format check
        const validExtensions = ['mp4', 'webm', 'mov'];
        const ext = file.name.split('.').pop().toLowerCase();
        if (!validExtensions.includes(ext) && !file.type.startsWith('video/')) {
            Swal.fire({ icon: 'warning', text: 'Only MP4, WebM, and MOV video formats are allowed.' });
            input.value = '';
            return;
        }

        // 3. Duration check via HTML5 video element
        const duration = await getVideoDuration(file);
        if (duration > MAX_VIDEO_DURATION_SEC) {
            Swal.fire({ 
                icon: 'warning', 
                title: 'Video Too Long',
                text: `Video duration (${Math.round(duration)}s) exceeds the maximum allowed limit of ${MAX_VIDEO_DURATION_SEC} seconds. Please upload a short incident clip.` 
            });
            input.value = '';
            return;
        }

        const sizeMb = (file.size / (1024 * 1024)).toFixed(1);
        const durFormatted = duration > 0 ? Math.round(duration) + 's' : 'Clip';

        // 4. If file is already small (<= 8MB), accept immediately without compression
        if (file.size <= COMPRESS_THRESHOLD_MB * 1024 * 1024) {
            selectedVideoFile = file;
            document.getElementById('videoFileName').textContent = file.name;
            document.getElementById('videoMeta').innerHTML = `${durFormatted} &bull; <strong>${sizeMb} MB</strong> (Ready)`;
            document.getElementById('videoUploadText').style.display = 'none';
            document.getElementById('videoCompressingState').style.display = 'none';
            document.getElementById('videoUploadPreview').style.display = 'flex';
            input.value = '';
            return;
        }

        // 5. File is > 8MB: Compress in-browser
        const hasMediaRecorder = typeof window.MediaRecorder !== 'undefined';
        const hasCaptureStream = HTMLCanvasElement.prototype.captureStream || HTMLCanvasElement.prototype.mozCaptureStream;

        if (!hasMediaRecorder || !hasCaptureStream) {
            if (file.size <= 35 * 1024 * 1024) {
                selectedVideoFile = file;
                document.getElementById('videoFileName').textContent = file.name;
                document.getElementById('videoMeta').innerHTML = `${durFormatted} &bull; <strong>${sizeMb} MB</strong> (Direct upload)`;
                document.getElementById('videoUploadText').style.display = 'none';
                document.getElementById('videoCompressingState').style.display = 'none';
                document.getElementById('videoUploadPreview').style.display = 'flex';
                input.value = '';
                return;
            } else {
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'Browser Not Supported',
                    text: 'Your browser cannot compress large videos. Please choose a video under 35MB or use modern Chrome/Safari/Edge.' 
                });
                input.value = '';
                return;
            }
        }

        // Start compression UI
        isCompressingVideo = true;
        document.getElementById('videoUploadText').style.display = 'none';
        document.getElementById('videoUploadPreview').style.display = 'none';
        document.getElementById('videoCompressingState').style.display = 'block';
        document.getElementById('compressSizeEstimate').textContent = `Original: ${sizeMb} MB`;
        updateCompressProgress(0);

        try {
            const compressedFile = await compressVideo(file, duration);
            const compMb = (compressedFile.size / (1024 * 1024)).toFixed(1);
            
            if (compressedFile.size < file.size) {
                selectedVideoFile = compressedFile;
                const savedPct = Math.round((1 - (compressedFile.size / file.size)) * 100);
                document.getElementById('videoFileName').textContent = file.name;
                document.getElementById('videoMeta').innerHTML = `${durFormatted} &bull; <strong style="color: #10b981;">${compMb} MB</strong> <span style="color: #059669; font-size: 11px; font-weight: 700;">(${savedPct}% saved from ${sizeMb}MB)</span>`;
            } else {
                selectedVideoFile = (file.size <= 35 * 1024 * 1024) ? file : compressedFile;
                document.getElementById('videoFileName').textContent = file.name;
                document.getElementById('videoMeta').innerHTML = `${durFormatted} &bull; <strong>${(selectedVideoFile.size / (1024*1024)).toFixed(1)} MB</strong>`;
            }

            document.getElementById('videoCompressingState').style.display = 'none';
            document.getElementById('videoUploadPreview').style.display = 'flex';

            if (pendingSubmitAfterCompression) {
                pendingSubmitAfterCompression = false;
                submitReport();
            }
        } catch (err) {
            if (err && err.message === 'CANCELLED') {
                return;
            }
            console.error('Compression failed:', err);
            if (file.size <= 35 * 1024 * 1024) {
                selectedVideoFile = file;
                document.getElementById('videoFileName').textContent = file.name;
                document.getElementById('videoMeta').innerHTML = `${durFormatted} &bull; <strong>${sizeMb} MB</strong> (Original)`;
                document.getElementById('videoCompressingState').style.display = 'none';
                document.getElementById('videoUploadPreview').style.display = 'flex';
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Compression Error',
                    text: 'Could not compress video on this device. Please choose a smaller video clip (under 35MB).'
                });
                removeVideo();
            }
        } finally {
            input.value = '';
        }
    }

    let isSubmittingReport = false;
    async function submitReport() {
        if (isSubmittingReport) return;
        
        const subject = document.getElementById('reportSubject').value.trim();
        const type = document.getElementById('reportType').value;
        const desc = document.getElementById('reportDesc').value.trim();

        if (!type) { Swal.fire({ icon: 'warning', text: 'Please select a category.' }); return; }
        if (!subject) { Swal.fire({ icon: 'warning', text: 'Please enter a subject.' }); return; }
        if (!desc) { Swal.fire({ icon: 'warning', text: 'Please enter a description.' }); return; }

        if (isCompressingVideo) {
            pendingSubmitAfterCompression = true;
            const submitBtn = document.getElementById('btnSubmitReport') || document.querySelector('#modalFooter .btn-primary');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Optimizing video... submitting soon';
                submitBtn.style.opacity = '0.7';
            }
            return;
        }

        isSubmittingReport = true;
        const submitBtn = document.getElementById('btnSubmitReport') || document.querySelector('#modalFooter .btn-primary');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            submitBtn.style.opacity = '0.7';
        }

        const formData = new FormData();
        formData.append('subject', subject);
        formData.append('type', type);
        formData.append('description', desc);
        const incDateVal = document.getElementById('reportIncidentDateTime')?.value;
        if (incDateVal) {
            formData.append('incident_date', incDateVal);
        }
        selectedPhotoFiles.forEach(file => {
            formData.append('photos[]', file);
        });
        if (selectedVideoFile) {
            formData.append('video', selectedVideoFile, selectedVideoFile.name || 'incident_video.mp4');
        }

        try {
            const response = await fetch('/resident/incidents', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                document.getElementById('formStep1').style.display = 'none';
                document.getElementById('formSuccess').style.display = 'block';
                const refDisplay = result.reference_id || (result.id ? 'INC-' + String(result.id).padStart(5, '0') : 'INC-SUBMITTED');
                const refEl = document.getElementById('successReferenceId');
                if (refEl) refEl.textContent = refDisplay;
                document.getElementById('modalFooter').innerHTML = '<button class="btn btn-primary" onclick="window.location.reload()" style="background: var(--res-primary); border-color: var(--res-primary); width: 100%; justify-content: center;">Done</button>';
            } else {
                Swal.fire({ icon: 'error', text: result.message || 'Failed to submit report.' });
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Report';
                    submitBtn.style.opacity = '1';
                }
                isSubmittingReport = false;
            }
        } catch (error) {
            console.error(error);
            Swal.fire({ icon: 'error', text: 'An error occurred while submitting.' });
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Report';
                submitBtn.style.opacity = '1';
            }
            isSubmittingReport = false;
        }
    }

    function openDetailsModal(id) {
        if (new URLSearchParams(window.location.search).get('incident') !== id.toString()) {
            window.history.pushState(null, '', '?incident=' + id);
        }
        const incidents = mappedIncidents;
        const inc = incidents.find(i => i.id === id.toString());
        if (!inc) return;

        document.getElementById('detId').textContent = inc.refId || inc.id;
        document.getElementById('detSub').textContent = inc.sub;
        document.getElementById('detType').textContent = inc.type;
        document.getElementById('detDesc').textContent = inc.desc;
        
        const statusEl = document.getElementById('detStatus');
        const statusClass = inc.status === 'resolved' ? 'resolved' : 'progress';
        statusEl.className = 'badge-status badge-' + statusClass;
        statusEl.textContent = statusClass === 'resolved' ? 'Resolved' : 'In Progress';
        
        const photoContainer = document.getElementById('detPhotos');
        const photoWrapper = document.getElementById('detPhotosContainer');
        
        if (inc.photos && inc.photos.length > 0) {
            let photosHtml = '';
            inc.photos.forEach(p => {
                photosHtml += `<img src="${p}" onclick="openFullscreenImage('${p}')" style="height: 100px; width: 100px; object-fit: cover; border-radius: 8px; border: 1px solid #cbd5e1; flex-shrink: 0; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'" alt="Attachment">`;
            });
            photoContainer.innerHTML = photosHtml;
            photoWrapper.style.display = 'block';
        } else {
            photoWrapper.style.display = 'none';
        }

        const videoWrapper = document.getElementById('detVideoWrapper');
        const videoContainer = document.getElementById('detVideoContainer');
        if (inc.video) {
            videoWrapper.innerHTML = `
                <video src="${escapeHtml(inc.video)}" controls preload="metadata" style="width: 100%; max-height: 240px; display: block; border-radius: 8px; background: #000;">
                    Your browser does not support HTML5 video.
                </video>
            `;
            videoContainer.style.display = 'block';
        } else {
            videoWrapper.innerHTML = '';
            videoContainer.style.display = 'none';
        }
        
        const timelineContainer = document.getElementById('detTimelineContainer');
        let timelineHtml = '';
        
        // Always show created
        timelineHtml += `
            <div style="display: flex; gap: 12px;">
                <div style="width: 8px; height: 8px; background: #3b82f6; border-radius: 50%; margin-top: 6px; flex-shrink: 0;"></div>
                <div>
                    <div style="font-size: 13px; font-weight: 700; color: #1e293b;">Report Submitted</div>
                    <div style="font-size: 12px; color: #64748b;">Your incident report was received by the system.</div>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">${inc.date ? new Date(inc.date).toLocaleString('en-US', {month:'short', day:'numeric', year:'numeric', hour:'numeric', minute:'2-digit'}) : 'Unknown Date'}</div>
                </div>
            </div>
        `;

        if (statusClass === 'resolved') {
            let color = '#10b981';
            let title = 'Issue Resolved';
            let desc = 'The admin has marked this incident as resolved.';
            
            timelineHtml += `
                <div style="display: flex; gap: 12px;">
                    <div style="width: 8px; height: 8px; background: ${color}; border-radius: 50%; margin-top: 6px; flex-shrink: 0;"></div>
                    <div>
                        <div style="font-size: 13px; font-weight: 700; color: #1e293b;">${title}</div>
                        <div style="font-size: 12px; color: #64748b;">${desc}</div>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">${inc.updated ? new Date(inc.updated).toLocaleString('en-US', {month:'short', day:'numeric', year:'numeric', hour:'numeric', minute:'2-digit'}) : 'Unknown Date'}</div>
                    </div>
                </div>
            `;
        }
        
        timelineContainer.innerHTML = timelineHtml;
        document.getElementById('detailsModal').style.display = 'flex';
    }

    function closeDetailsModal() {
        window.history.replaceState(null, '', window.location.pathname);
        const v = document.querySelector('#detVideoWrapper video');
        if (v) v.pause();
        document.getElementById('detailsModal').style.display = 'none';
    }

    function openFullscreenImage(src) {
        document.getElementById('fullscreenImage').src = src;
        document.getElementById('fullscreenImageViewer').style.display = 'flex';
    }

    function closeFullscreenImage() {
        document.getElementById('fullscreenImageViewer').style.display = 'none';
    }

    window.addEventListener('DOMContentLoaded', () => {
        const params = new URLSearchParams(window.location.search);
        if (params.get('action') === 'report') {
            openReportModal();
        } else if (params.get('incident')) {
            setTimeout(() => {
                openDetailsModal(params.get('incident'));
            }, 100);
        }
    });
</script>
@endsection
