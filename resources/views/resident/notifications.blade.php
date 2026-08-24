@extends('layouts.resident')

@section('title', 'All Notifications')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">

<div class="fade-in">
    <div style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="font-size: 28px; font-weight: 800; color: #0f172a; margin: 0;">Notifications</h1>
            <p style="color: #64748b; margin-top: 8px;">Stay updated with community announcements and alerts.</p>
        </div>
    </div>

    <div class="analytic-card" style="padding: 0; overflow: hidden;">
        <div style="background: #f8fafc; padding: 16px 24px; border-bottom: 1px solid var(--bill-border); display: flex; gap: 24px;">
            <button style="background: none; border: none; font-weight: 700; color: #0f172a; border-bottom: 2px solid #059669; padding-bottom: 8px; cursor: pointer;">All Notifications</button>
        </div>

        <div style="display: flex; flex-direction: column;">
            @if(count($announcements) === 0)
                <div style="padding: 64px 24px; text-align: center; color: #94a3b8;">
                    <div style="font-size: 40px; margin-bottom: 16px;">🔔</div>
                    <div style="font-size: 16px; font-weight: 600;">No notifications yet</div>
                    <div style="font-size: 13px; margin-top: 8px;">New announcements and alerts will appear here.</div>
                </div>
            @endif

            @foreach($announcements as $ann)
                <div style="padding: 24px; border-bottom: 1px solid #f1f5f9; display: flex; gap: 16px;">
                    <div style="width: 40px; height: 40px; background: #eff6ff; color: #2563eb; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <h4 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">{{ $ann->title }}</h4>
                            <span style="font-size: 12px; color: #94a3b8; font-weight: 600; white-space: nowrap; margin-left: 12px;">{{ $ann->created_at->diffForHumans() }}</span>
                        </div>
                        <p style="font-size: 14px; color: #475569; margin: 6px 0 0 0; line-height: 1.5;">{{ $ann->content }}</p>
                        <div style="display: flex; gap: 12px; margin-top: 8px; align-items: center;">
                            <span style="font-size: 10px; font-weight: 700; color: #3b82f6; text-transform: uppercase; background: #eff6ff; padding: 2px 8px; border-radius: 6px;">{{ $ann->category }}</span>
                            <span style="font-size: 11px; color: #94a3b8;">Posted by {{ $ann->author }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
