@extends('layouts.resident')

@section('title', 'All Notifications')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">

<div class="fade-in">
    <div style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="font-size: 28px; font-weight: 800; color: #0f172a; margin: 0;">Notifications</h1>
            <p style="color: #64748b; margin-top: 8px;">Stay updated with property alerts, guest entries, and community news.</p>
        </div>
        <button class="btn btn-outline" style="font-size: 13px; padding: 10px 20px;">Mark all as read</button>
    </div>

    <div class="analytic-card" style="padding: 0; overflow: hidden;">
        <div style="background: #f8fafc; padding: 16px 24px; border-bottom: 1px solid var(--bill-border); display: flex; gap: 24px;">
            <button style="background: none; border: none; font-weight: 700; color: #0f172a; border-bottom: 2px solid #059669; padding-bottom: 8px; cursor: pointer;">All Alerts</button>
            <button style="background: none; border: none; font-weight: 600; color: #64748b; padding-bottom: 8px; cursor: pointer;">Security</button>
            <button style="background: none; border: none; font-weight: 600; color: #64748b; padding-bottom: 8px; cursor: pointer;">Billing</button>
        </div>

        <div style="display: flex; flex-direction: column;">
            @php
                $overrides = session('visitor_overrides', []);
            @endphp
            
            @foreach($overrides as $id => $data)
                <div style="padding: 24px; border-bottom: 1px solid #f1f5f9; background: #f0fdf4; display: flex; gap: 16px;">
                    <div style="width: 40px; height: 40px; background: #dcfce7; color: #166534; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <h4 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Visitor Entered Subdivison</h4>
                            <span style="font-size: 12px; color: #94a3b8; font-weight: 600;">Just now</span>
                        </div>
                        <p style="font-size: 14px; color: #475569; margin: 6px 0 0 0;">Your registered guest (${id}) has successfully passed through the Main Gate at {{ $data['arrival_time'] }}.</p>
                    </div>
                </div>
            @endforeach

            <!-- Hardcoded History Notifications -->
            <div style="padding: 24px; border-bottom: 1px solid #f1f5f9; display: flex; gap: 16px;">
                <div style="width: 40px; height: 40px; background: #dbeafe; color: #1e40af; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.407 2.67 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.407-2.67-1M12 16v1m-7-4a7 7 0 1114 0 7 7 0 01-14 0z"/></svg>
                </div>
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <h4 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Payment Confirmed</h4>
                        <span style="font-size: 12px; color: #94a3b8; font-weight: 600;">2 hours ago</span>
                    </div>
                    <p style="font-size: 14px; color: #475569; margin: 6px 0 0 0;">Your GCash payment for Electricity Bill (EB-001) has been settled and verified by the finance office.</p>
                </div>
            </div>

            <div style="padding: 24px; border-bottom: 1px solid #f1f5f9; display: flex; gap: 16px;">
                <div style="width: 40px; height: 40px; background: #fef3c7; color: #92400e; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <h4 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Scheduled Maintenance</h4>
                        <span style="font-size: 12px; color: #94a3b8; font-weight: 600;">1 day ago</span>
                    </div>
                    <p style="font-size: 14px; color: #475569; margin: 6px 0 0 0;">Road repaving near Block B will occur this Friday. Please plan accordingly to avoid vehicle obstruction.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
