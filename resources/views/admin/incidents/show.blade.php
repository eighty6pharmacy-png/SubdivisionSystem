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
                <h1 style="font-size: 24px; font-weight: 700; color: var(--incident-text-main); margin-top: 4px;">{{ $inc['id'] }} — {{ $inc['sub'] }}</h1>
                <div class="incident-meta" style="margin-top: 8px;">
                    <span class="badge-premium badge-{{ $inc['status'] }}">{{ ucfirst($inc['status']) === 'Pending' ? 'Pending Review' : ucfirst($inc['status']) }}</span>
                    <span>&bull;</span>
                    <span>Reported by: **{{ $inc['res'] }}**</span>
                    <span>&bull;</span>
                    <span>{{ rand(5, 59) }} minutes ago</span>
                </div>
            </div>
        </div>

        <div class="analysis-body">
            <div class="analysis-main-content">
                <div class="media-gallery">
                    <h3 style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--incident-text-sub); margin-bottom: 16px;">Evidence & Media</h3>
                    @if(!empty($inc['img']))
                        <div class="media-main" style="background: #f1f5f9; position: relative; min-height: 200px;">
                            <img src="{{ $inc['img'] }}{{ str_contains($inc['img'], '?') ? '&' : '?' }}auto=format&fit=crop&q=80&w=1200" 
                                 onerror="this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;font-weight:700;color:#94a3b8;\'>No Photo</div>'"
                                 alt="Incident Evidence">
                        </div>
                    @else
                        <div class="media-main" style="display: flex; align-items: center; justify-content: center; background: #f8fafc; border: 1px solid #e2e8f0; height: 160px; border-radius: 12px;">
                            <span style="font-weight: 700; color: #94a3b8; font-size: 14px;">No Photo</span>
                        </div>
                    @endif
                </div>

                <div style="margin-bottom: 40px;">
                    <h3 style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--incident-text-sub); margin-bottom: 16px;">Description</h3>
                    <p style="font-size: 15px; color: var(--incident-text-main); line-height: 1.7; background: #f8fafc; padding: 24px; border-radius: 16px; border: 1px solid var(--border);">
                        "{{ $inc['desc'] }}"
                    </p>
                </div>

                <div style="margin-bottom: 40px;">
                    <h3 style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--incident-text-sub); margin-bottom: 16px;">Admin Notes</h3>
                    <textarea style="width: 100%; min-height: 100px; padding: 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical; font-family: inherit; color: var(--incident-text-main);" placeholder="Add internal notes about this incident (visible to admin only)..."></textarea>
                    <div style="display: flex; justify-content: flex-end; margin-top: 12px;">
                        <button class="btn btn-primary" style="background: var(--incident-accent); color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer;" onclick="alert('Notes saved.')">Save Notes</button>
                    </div>
                </div>
            </div>

            <div class="analysis-sidebar">
                <div class="sidebar-section">
                    <h3>Incident Details</h3>
                    <div class="detail-item">
                        <span class="detail-label">Status</span>
                        <select class="form-input" style="width: 100%; border-radius: 8px; padding: 8px;" onchange="updateStatus(this.value)">
                            <option {{ $inc['status'] == 'pending' ? 'selected' : '' }}>Open / Pending</option>
                            <option {{ $inc['status'] == 'progress' ? 'selected' : '' }}>In Progress</option>
                            <option {{ $inc['status'] == 'resolved' ? 'selected' : '' }}>Resolved</option>
                        </select>
                    </div>
                    <div style="margin-top: 12px;">
                        <button class="btn btn-primary" style="width: 100%; background: var(--incident-accent); color: white; border: none; padding: 10px; border-radius: 8px; font-weight: 700; cursor: pointer;" onclick="alert('Status updated.')">Update Status</button>
                    </div>
                </div>

                <div class="sidebar-section">
                    <h3>Reporter Information</h3>
                    <div class="detail-item">
                        <span class="detail-label">Identity</span>
                        <span class="detail-value" style="color: var(--incident-text-main); font-weight: 700;">{{ $inc['res'] }}</span>
                    </div>
                </div>

                <div class="sidebar-section">
                    <h3>Timeline</h3>
                    <div style="display: flex; flex-direction: column; gap: 12px; font-size: 12px; color: var(--incident-text-sub);">
                        <div style="display: flex; justify-content: space-between;">
                            <span>Logged</span>
                            <span>{{ date('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function updateStatus(val) { /* live update hook */ }
</script>
@endsection

