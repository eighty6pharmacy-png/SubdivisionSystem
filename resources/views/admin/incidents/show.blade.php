@extends('layouts.admin')

@section('title', 'Incident Analysis')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-incidents.css') }}">

<div class="incident-show-container fade-in">
    <div style="margin-bottom: 24px;">
        <a href="/admin/incidents" style="text-decoration: none; color: var(--incident-text-sub); font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Back to Incident List
        </a>
    </div>

    <div class="analysis-frame">
        <div class="analysis-header">
            <div>
                <span class="incident-type">{{ $inc['type'] }}</span>
                <h1 style="font-size: 24px; font-weight: 700; color: var(--incident-text-main); margin-top: 4px;">INC-{{ str_pad($inc['id'], 5, '0', STR_PAD_LEFT) }} — {{ $inc['sub'] }}</h1>
                <div class="incident-meta" style="margin-top: 8px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <span class="badge-premium badge-{{ strtolower($inc['status']) }}">{{ ucfirst($inc['status']) === 'Pending' ? 'Pending Review' : ucfirst($inc['status']) }}</span>
                    <span>&bull;</span>
                    <span>Reported by: <strong>{{ $inc['res'] }}</strong></span>
                    <span>&bull;</span>
                    <span>{{ \Carbon\Carbon::parse($inc['date'])->diffForHumans() }}</span>
                </div>
            </div>
        </div>

        <div class="analysis-body">
            <div class="analysis-main-content">
                <div class="media-gallery" style="margin-bottom: 32px;">
                    <h3 style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--incident-text-sub); margin-bottom: 16px;">Evidence & Media</h3>
                    
                    @if(!empty($inc['video']) || !empty($inc['video_url']))
                        <div style="margin-bottom: 16px; border-radius: 12px; overflow: hidden; background: #000; border: 1px solid #e2e8f0;">
                            <video src="{{ $inc['video'] ?? $inc['video_url'] }}" controls preload="metadata" style="width: 100%; max-height: 380px; display: block;">
                                Your browser does not support HTML5 video.
                            </video>
                        </div>
                    @endif

                    @if(!empty($inc['photos']) && count($inc['photos']) > 0)
                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            @foreach($inc['photos'] as $photo)
                                <a href="{{ $photo }}" target="_blank" style="display: block; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1;">
                                    <img src="{{ $photo }}" alt="Evidence Photo" style="height: 120px; width: 120px; object-fit: cover; transition: transform 0.2s; display: block;" onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform='scale(1)'">
                                </a>
                            @endforeach
                        </div>
                    @elseif(!empty($inc['img']) && empty($inc['video']) && empty($inc['video_url']))
                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <a href="{{ $inc['img'] }}" target="_blank" style="display: block; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1;">
                                <img src="{{ $inc['img'] }}" alt="Evidence Photo" style="height: 120px; width: 120px; object-fit: cover; display: block;">
                            </a>
                        </div>
                    @elseif(empty($inc['video']) && empty($inc['video_url']))
                        <div style="display: flex; align-items: center; justify-content: center; background: #f8fafc; border: 1px solid #e2e8f0; height: 100px; border-radius: 12px; color: #94a3b8; font-weight: 600; font-size: 13px;">
                            No Media Attached
                        </div>
                    @endif
                </div>

                <div style="margin-bottom: 32px;">
                    <h3 style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--incident-text-sub); margin-bottom: 12px;">Description</h3>
                    <p style="font-size: 15px; color: var(--incident-text-main); line-height: 1.7; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid var(--border); margin: 0;">
                        {{ $inc['desc'] }}
                    </p>
                </div>

                <div style="margin-bottom: 32px;">
                    <h3 style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--incident-text-sub); margin-bottom: 12px;">Admin Notes</h3>
                    <textarea id="adminNotesText" style="width: 100%; min-height: 110px; padding: 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; resize: vertical; font-family: inherit; color: var(--incident-text-main); background: #fff;" placeholder="Add internal notes about this incident (visible to administration only)...">{{ $inc['admin_notes'] ?? '' }}</textarea>
                    <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
                        <button id="btnSaveNotes" class="btn btn-primary" style="background: var(--incident-accent); color: white; border: none; padding: 10px 22px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer;" onclick="saveAdminNotes()">💾 Save Notes</button>
                    </div>
                </div>
            </div>

            <div class="analysis-sidebar">
                <div class="sidebar-section">
                    <h3>Incident Status</h3>
                    <div class="detail-item" style="margin-top: 10px;">
                        <span class="detail-label">Current Status</span>
                        <select id="statusSelect" class="form-input" style="width: 100%; border-radius: 8px; padding: 10px; border: 1px solid #cbd5e1; font-weight: 600; background: #fff; margin-top: 6px;">
                            <option value="pending" {{ strtolower($inc['status']) == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="progress" {{ strtolower($inc['status']) == 'progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="resolved" {{ strtolower($inc['status']) == 'resolved' ? 'selected' : '' }}>Resolved</option>
                        </select>
                    </div>
                    <div style="margin-top: 12px;">
                        <button id="btnUpdateStatus" class="btn btn-primary" style="width: 100%; background: linear-gradient(135deg, #059669, #10b981); color: white; border: none; padding: 11px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 13px;" onclick="saveStatusUpdate()">Update Status</button>
                    </div>
                </div>

                <div class="sidebar-section">
                    <h3>Reporter Information</h3>
                    <div class="detail-item">
                        <span class="detail-label">Identity / Resident</span>
                        <span class="detail-value" style="color: var(--incident-text-main); font-weight: 700;">{{ $inc['res'] }}</span>
                    </div>
                </div>

                <div class="sidebar-section">
                    <h3>Timeline</h3>
                    <div style="display: flex; flex-direction: column; gap: 12px; font-size: 12px; color: var(--incident-text-sub);">
                        <div style="display: flex; justify-content: space-between;">
                            <span>Reported Date</span>
                            <span style="font-weight: 600; color: #1e293b;">{{ \Carbon\Carbon::parse($inc['date'])->format('M d, Y h:i A') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const incidentId = "{{ $inc['id'] }}";

    async function saveStatusUpdate() {
        const select = document.getElementById('statusSelect');
        const btn = document.getElementById('btnUpdateStatus');
        const newStatus = select.value;

        btn.disabled = true;
        btn.textContent = 'Updating...';

        try {
            const response = await fetch(`/admin/incidents/${incidentId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            });

            const result = await response.json();
            if (result.success) {
                Swal.fire({ icon: 'success', text: 'Incident status updated successfully.' }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({ icon: 'error', text: result.message || 'Failed to update status.' });
                btn.disabled = false;
                btn.textContent = 'Update Status';
            }
        } catch (error) {
            console.error(error);
            Swal.fire({ icon: 'error', text: 'An error occurred while updating status.' });
            btn.disabled = false;
            btn.textContent = 'Update Status';
        }
    }

    async function saveAdminNotes() {
        const text = document.getElementById('adminNotesText').value;
        const btn = document.getElementById('btnSaveNotes');

        btn.disabled = true;
        btn.textContent = 'Saving...';

        try {
            const response = await fetch(`/admin/incidents/${incidentId}/notes`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ admin_notes: text })
            });

            const result = await response.json();
            if (result.success) {
                Swal.fire({ icon: 'success', text: 'Admin notes saved.' });
            } else {
                Swal.fire({ icon: 'error', text: result.message || 'Failed to save notes.' });
            }
        } catch (error) {
            console.error(error);
            Swal.fire({ icon: 'error', text: 'An error occurred while saving notes.' });
        } finally {
            btn.disabled = false;
            btn.textContent = '💾 Save Notes';
        }
    }
</script>
@endsection

