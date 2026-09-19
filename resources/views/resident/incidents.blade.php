@extends('layouts.resident')

@section('title', 'Community Incident Reports')

@section('content')
<link rel="stylesheet" href="{{ asset('css/resident-incidents.css') }}">

<div class="fade-in">
    <!-- Header Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
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
            <div style="font-size: 24px; font-weight: 800; color: #3b82f6; margin-top: 4px;">{{ collect($incidents)->whereIn('status', ['pending', 'progress'])->count() }}</div>
        </div>
        <div class="stat-box">
            <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Resolved</div>
            <div style="font-size: 24px; font-weight: 800; color: #10b981; margin-top: 4px;">{{ collect($incidents)->where('status', 'resolved')->count() }}</div>
        </div>
    </div>

    <!-- Incident List -->
    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
            <h2 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">My Reported Incidents</h2>
        </div>
        <div id="residentIncidentList" style="padding: 12px 24px;">
            @forelse($incidents as $inc)
            <div class="incident-card">
                <div class="incident-info">
                    <span style="font-size: 11px; font-weight: 700; color: #3b82f6; text-transform: uppercase;">{{ $inc['type'] }}</span>
                    <h3>{{ $inc['sub'] }}</h3>
                    <p>{{ \Illuminate\Support\Str::limit($inc['desc'], 80) }}</p>
                    <div style="margin-top: 12px; display: flex; align-items: center; gap: 12px; font-size: 12px; color: #94a3b8; font-weight: 600;">
                        <span>Ref: {{ $inc['id'] }}</span>
                        <span>•</span>
                        <span>Just Now</span>
                    </div>
                </div>
                <div class="incident-status">
                    <span class="badge-status badge-{{ $inc['status'] }}">{{ ucfirst($inc['status']) }}</span>
                    <button class="btn btn-outline" onclick="openDetailsModal('{{ $inc['id'] }}', '{{ $inc['sub'] }}', '{{ $inc['type'] }}', '{{ $inc['status'] }}', '{{ $inc['desc'] }}')" style="padding: 4px 12px; font-size: 11px; border-radius: 6px;">View Details</button>
                </div>
            </div>
            @empty
            <div style="padding: 48px; text-align: center; color: #64748b;">
                <div style="font-size: 40px; margin-bottom: 16px;">🔍</div>
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
                            <span class="category-icon">🛡️</span>
                            <span class="category-label">Security</span>
                        </div>
                        <div class="category-opt" onclick="selectCategory(this, 'Maintenance')">
                            <span class="category-icon">🛠️</span>
                            <span class="category-label">Maintenance</span>
                        </div>
                        <div class="category-opt" onclick="selectCategory(this, 'Utilities')">
                            <span class="category-icon">💡</span>
                            <span class="category-label">Utilities</span>
                        </div>
                        <div class="category-opt" onclick="selectCategory(this, 'Others')">
                            <span class="category-icon">📁</span>
                            <span class="category-label">Others</span>
                        </div>
                    </div>
                    <input type="hidden" id="reportType">
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
                    <label>Attach Photos (Optional)</label>
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
            </div>

            <!-- Success State -->
            <div id="formSuccess" style="display: none; text-align: center; padding: 24px 0;">
                <div style="width: 64px; height: 64px; background: #d1fae5; color: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                </div>
                <h3 style="font-size: 20px; font-weight: 800; color: #1e293b; margin: 0;">Report Submitted</h3>
                <p style="color: #64748b; margin-top: 8px;">Our maintenance and security team will review your report shortly. Your reference ID is <span style="font-family: monospace; font-weight: 800; color: #0f172a;">INC-{{ rand(2000, 2999) }}</span>.</p>
            </div>
        </div>
        <div class="modal-footer" id="modalFooter">
            <button class="btn btn-outline" onclick="closeReportModal()">Cancel</button>
            <button class="btn btn-primary" onclick="submitReport()" style="background: var(--res-primary); border-color: var(--res-primary); padding: 10px 24px; font-weight: 700;">Submit Report</button>
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
                <span id="detStatus" class="badge-status badge-pending">Pending</span>
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

            <div style="margin-bottom: 24px;">
                <h4 style="font-size: 12px; text-transform: uppercase; color: #94a3b8; margin-bottom: 12px;">Timeline & Updates</h4>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div style="display: flex; gap: 12px;">
                        <div style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; margin-top: 6px; flex-shrink: 0;"></div>
                        <div>
                            <div style="font-size: 13px; font-weight: 700; color: #1e293b;">Report Received</div>
                            <div style="font-size: 12px; color: #64748b;">Administrative review is in progress.</div>
                            <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Just Now</div>
                        </div>
                    </div>
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
    // Inject DB data
    const dbIncidents = @json($incidents);
    // Map DB fields to what JS expects
    const mappedIncidents = dbIncidents.map(inc => {
        let parsedPhotos = [];
        if (inc.image_url) {
            try {
                parsedPhotos = JSON.parse(inc.image_url);
            } catch(e) {
                parsedPhotos = [inc.image_url];
            }
        }

        return {
            id: inc.id.toString(),
            sub: inc.subject,
            type: inc.type,
            date: inc.created_at,
            desc: inc.description,
            status: inc.status.toLowerCase(),
            photos: parsedPhotos
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
                    <div style="font-size: 40px; margin-bottom: 16px;">🔍</div>
                    <h3 style="font-size: 18px; font-weight: 700; color: #1e293b; margin: 0;">No Incidents Found</h3>
                    <p style="margin-top: 8px;">You haven't reported any issues yet. Click "Report New Issue" to get started.</p>
                </div>
            `;
            return;
        }

        incidents.forEach(inc => {
            const card = document.createElement('div');
            card.className = 'incident-card';
            const statusClass = inc.status || 'pending';
            card.innerHTML = `
                <div class="incident-info">
                    <span style="font-size: 11px; font-weight: 700; color: #3b82f6; text-transform: uppercase;">${inc.type}</span>
                    <h3>${inc.sub}</h3>
                    <p>${inc.desc}</p>
                    <div style="margin-top: 12px; display: flex; align-items: center; gap: 12px; font-size: 12px; color: #94a3b8; font-weight: 600;">
                        <span>Ref: ${inc.id.substring(0, 8)}</span>
                        <span>•</span>
                        <span>${inc.date ? new Date(inc.date).toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric', hour:'numeric', minute:'2-digit'}) : 'Just Now'}</span>
                    </div>
                </div>
                <div class="incident-status">
                    <span class="badge-status badge-${statusClass}">${statusClass.charAt(0).toUpperCase() + statusClass.slice(1)}</span>
                    <button class="btn btn-outline" onclick="openDetailsModal('${inc.id}')" style="padding: 4px 12px; font-size: 11px; border-radius: 6px;">View Details</button>
                </div>
            `;
            container.appendChild(card);
        });
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
        document.getElementById('uploadText').style.display = 'block';
        document.getElementById('uploadPreview').style.display = 'none';
        uploadedPhotosBase64 = [];
        document.querySelectorAll('.category-opt').forEach(opt => opt.classList.remove('selected'));
        document.getElementById('reportType').value = '';
    }

    function closeReportModal() {
        window.history.replaceState(null, '', window.location.pathname);
        document.getElementById('reportModal').style.display = 'none';
    }

    function selectCategory(el, category) {
        document.querySelectorAll('.category-opt').forEach(opt => opt.classList.remove('selected'));
        el.classList.add('selected');
        document.getElementById('reportType').value = category;
    }

    let uploadedPhotosBase64 = [];
    const MAX_PHOTOS = 3;
    const MAX_SIZE_MB = 5;

    function handleFilesUpload(input) {
        if (!input.files || input.files.length === 0) return;
        
        for (let i = 0; i < input.files.length; i++) {
            if (uploadedPhotosBase64.length >= MAX_PHOTOS) {
                alert(`You can only upload a maximum of ${MAX_PHOTOS} photos.`);
                break;
            }
            
            const file = input.files[i];
            if (file.size > MAX_SIZE_MB * 1024 * 1024) {
                alert(`File ${file.name} exceeds the ${MAX_SIZE_MB}MB limit.`);
                continue;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                uploadedPhotosBase64.push(e.target.result);
                renderPreview();
            };
            reader.readAsDataURL(file);
        }
        
        input.value = ''; // Reset input to allow selecting the same file again if needed
    }

    function renderPreview() {
        const preview = document.getElementById('uploadPreview');
        const text = document.getElementById('uploadText');
        
        if (uploadedPhotosBase64.length > 0) {
            text.style.display = 'none';
            preview.style.display = 'flex';
            
            preview.innerHTML = uploadedPhotosBase64.map((base64, index) => `
                <div style="position: relative; display: inline-block; margin: 4px;">
                    <img src="${base64}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 2px solid #e2e8f0;">
                    <button type="button" onclick="removePhoto(event, ${index})" style="position: absolute; top: -6px; right: -6px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 10px; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">✕</button>
                </div>
            `).join('');
        } else {
            text.style.display = 'block';
            preview.style.display = 'none';
            preview.innerHTML = '';
        }
    }

    function removePhoto(event, index) {
        event.stopPropagation();
        uploadedPhotosBase64.splice(index, 1);
        renderPreview();
    }

    let isSubmittingReport = false;
    async function submitReport() {
        if (isSubmittingReport) return;
        
        const subject = document.getElementById('reportSubject').value.trim();
        const type = document.getElementById('reportType').value;
        const desc = document.getElementById('reportDesc').value.trim();

        if (!type) { alert('Please select a category.'); return; }
        if (!subject) { alert('Please enter a subject.'); return; }
        if (!desc) { alert('Please enter a description.'); return; }

        isSubmittingReport = true;
        const submitBtn = document.querySelector('#formStep1 .btn-primary');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            submitBtn.style.opacity = '0.7';
        }

        try {
            const response = await fetch('/resident/incidents', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    subject: subject,
                    type: type,
                    description: desc,
                    photos: uploadedPhotosBase64
                })
            });

            const result = await response.json();
            if (result.success) {
                document.getElementById('formStep1').style.display = 'none';
                document.getElementById('formSuccess').style.display = 'block';
                document.getElementById('modalFooter').innerHTML = '<button class="btn btn-primary" onclick="window.location.reload()" style="background: var(--res-primary); border-color: var(--res-primary); width: 100%; justify-content: center;">Done</button>';
            } else {
                alert('Failed to submit report.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Report';
                    submitBtn.style.opacity = '1';
                }
                isSubmittingReport = false;
            }
        } catch (error) {
            console.error(error);
            alert('An error occurred.');
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

        document.getElementById('detId').textContent = inc.id;
        document.getElementById('detSub').textContent = inc.sub;
        document.getElementById('detType').textContent = inc.type;
        document.getElementById('detDesc').textContent = inc.desc;
        
        const statusEl = document.getElementById('detStatus');
        const statusClass = inc.status || 'pending';
        statusEl.className = 'badge-status badge-' + statusClass;
        statusEl.textContent = statusClass.charAt(0).toUpperCase() + statusClass.slice(1);
        
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
        
        document.getElementById('detailsModal').style.display = 'flex';
    }

    function closeDetailsModal() {
        window.history.replaceState(null, '', window.location.pathname);
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
            // Give mappedIncidents a moment to be available
            setTimeout(() => {
                openDetailsModal(params.get('incident'));
            }, 100);
        }
    });
</script>
@endsection
