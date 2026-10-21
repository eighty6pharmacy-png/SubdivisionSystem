<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Resident Portal — Althesa</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script src="{{ asset('js/subdivision-store.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="{{ asset('css/views/layout-resident.css') }}">
    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
    <script>
      window.OneSignalDeferred = window.OneSignalDeferred || [];
      OneSignalDeferred.push(async function(OneSignal) {
        await OneSignal.init({
          appId: "{{ config('services.onesignal.app_id') }}",
          allowLocalhostAsSecureOrigin: true,
        });
        
        OneSignal.Slidedown.promptPush();

        @if(auth()->check())
            OneSignal.login("{{ auth()->user()->id }}");
        @endif
      });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="admin-body">

<div class="admin-layout">
    <div id="sidebarOverlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Global Toast Container -->
    <div id="toastContainer" style="position: fixed; top: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 12px;"></div>

    <!-- Resident Sidebar -->
    <aside class="sidebar" id="mainSidebar">
        <div class="sidebar-logo">
            <div class="logo-icon" style="background: var(--res-primary);">
                <img src="{{ asset('images/logo.png') }}" alt="Althesa Icon" style="width: 24px; height: 24px; object-fit: contain;">
            </div>
            <h2>Althesa</h2>
            <p>Resident Portal</p>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section-label">My Home</div>
            <a href="/resident/dashboard" class="nav-item {{ request()->is('resident/dashboard') ? 'active' : '' }}">
                <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span class="nav-item-text">My Dashboard</span>
            </a>
            
            <div class="nav-section-label">Utilities &amp; Payments</div>
            <a href="/resident/electricity" class="nav-item {{ request()->is('resident/electricity') ? 'active' : '' }}">
                <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span class="nav-item-text">Electricity Bill</span>
            </a>
            <a href="/resident/water" class="nav-item {{ request()->is('resident/water') ? 'active' : '' }}">
                <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-4 6-6 9-6 12a6 6 0 1012 0c0-3-2-6-6-12z"/></svg>
                <span class="nav-item-text">Water Bill</span>
            </a>

            <div class="nav-section-label">Security &amp; Access</div>
            <a href="/resident/visitors" class="nav-item {{ request()->is('resident/visitors') ? 'active' : '' }}">
                <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                <span class="nav-item-text">Visitor PINs</span>
            </a>
            <a href="/resident/map" class="nav-item {{ request()->is('resident/map') ? 'active' : '' }}">
                <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                <span class="nav-item-text">Interactive Map</span>
            </a>
            
            <div class="nav-section-label">Community</div>
            <a href="/resident/incidents" class="nav-item {{ request()->is('resident/incidents') ? 'active' : '' }}">
                <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span class="nav-item-text">Report Incident</span>
            </a>

            <div class="nav-section-label">Account</div>
            <a href="/resident/profile" class="nav-item {{ request()->is('resident/profile') ? 'active' : '' }}">
                <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span class="nav-item-text">My Profile</span>
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <a href="javascript:void(0)" class="nav-item nav-item--logout" onclick="openLogoutModal()">
                <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span class="nav-item-text">Logout</span>
            </a>
            <form id="resident-logout-form" action="/logout" method="POST" style="display: none;">
                @csrf
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="admin-main">
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 12px;">
                <button class="hamburger-btn" onclick="toggleSidebar()">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="topbar-title">@yield('title', 'Welcome')</div>
            </div>
            <div class="topbar-actions" style="display: flex; align-items: center; gap: 16px;">
                
                <!-- Notification Bell -->
                <div style="position: relative; cursor: pointer; padding: 4px;" onclick="window.location.href='/resident/notifications'" title="View Notifications">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color: #64748b;"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    @if(\Illuminate\Support\Facades\Auth::user()->unreadNotifications->count() > 0)
                        <div style="position: absolute; top: 2px; right: 2px; width: 10px; height: 10px; background: #ef4444; border: 2px solid #fff; border-radius: 50%;"></div>
                    @endif
                </div>

                @php
                    $resLots = \Illuminate\Support\Facades\Auth::user()->lots;
                    $currentLotId = session('resident_selected_lot_id', $resLots->first()?->id);
                @endphp
                @if($resLots->count() > 1)
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Property:</span>
                        <select id="topbarLotSwitch" onchange="switchResidentLot(this.value)" style="background: var(--res-primary-soft); color: var(--res-primary); font-size: 12px; font-weight: 700; border: 1px solid rgba(5, 150, 105, 0.3); border-radius: 20px; padding: 4px 12px; cursor: pointer; outline: none;">
                            @foreach($resLots as $l)
                                <option value="{{ $l->id }}" {{ $currentLotId == $l->id ? 'selected' : '' }}>
                                    Block {{ $l->block }} Lot {{ $l->lot_number }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <script>
                        function switchResidentLot(lotId) {
                            const url = new URL(window.location.href);
                            url.searchParams.set('lot_id', lotId);
                            window.location.href = url.toString();
                        }
                    </script>
                @elseif($resLots->count() === 1)
                    <div id="resLotBadge" style="background: var(--res-primary-soft); color: var(--res-primary); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                        Block {{ $resLots->first()->block }} Lot {{ $resLots->first()->lot_number }}
                    </div>
                @else
                    <div id="resLotBadge" style="background: var(--res-primary-soft); color: var(--res-primary); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                        No Assigned Lot
                    </div>
                @endif
            </div>
        </header>

        <main class="content-body" style="padding: 32px;">
            @yield('content')
        </main>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('mainSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
    }

    function downloadHistoryItem(ref, type, period, amount, date) {
        let content = "ALTHESA PROPERTY MANAGEMENT - OFFICIAL RECEIPT\n";
        content += "==============================================\n\n";
        content += `Reference No: ${ref}\n`;
        content += `Billing Period: ${period}\n`;
        content += `Type:           ${type}\n`;
        content += `Amount Paid:    ₱${parseFloat(amount).toLocaleString()}\n`;
        content += `Payment Date:   ${date}\n`;
        content += "Status:         PAID / SETTLED\n\n";
        content += "==============================================\n";
        content += "Thank you for being a responsible resident!";

        const blob = new Blob([content], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Receipt_${ref}.txt`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        pushSystemNotification("Download Started", `Receipt for ${period} ${type} is downloading.`, "Just now", false);
    }

    function pushSystemNotification(title, message, time, isSuccess = true) {
        // 1. Show Toast for immediate feedback (Global)
        const toastContainer = document.getElementById('toastContainer');
        if (toastContainer) {
            const toast = document.createElement('div');
            toast.style.background = '#fff';
            toast.style.padding = '16px';
            toast.style.borderRadius = '12px';
            toast.style.boxShadow = '0 10px 15px -3px rgba(0,0,0,0.1)';
            toast.style.borderLeft = `4px solid ${isSuccess ? '#10b981' : '#3b82f6'}`;
            toast.style.minWidth = '300px';
            toast.style.display = 'flex';
            toast.style.gap = '12px';
            toast.style.marginBottom = '8px';
            
            toast.innerHTML = `
                <div style="width: 24px; height: 24px; background: ${isSuccess ? '#dcfce7' : '#dbeafe'}; color: ${isSuccess ? '#166534' : '#1e40af'}; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div style="font-size: 13px; font-weight: 700; color: #0f172a;">${title}</div>
                    <div style="font-size: 12px; color: #475569; margin-top: 2px;">${message}</div>
                </div>
            `;
            toastContainer.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }

        // 2. Update Dashboard Panel if we are on it
        const panel = document.querySelector('[style*="max-height: 300px; overflow-y: auto;"]');
        if (panel) {
            const div = document.createElement('div');
            div.style.padding = '16px 24px';
            div.style.borderBottom = '1px solid #f1f5f9';
            div.style.background = '#f0fdf4'; 
            div.style.transition = 'background 2s ease';
            
            const iconBg = isSuccess ? '#dcfce7' : '#dbeafe';
            const iconColor = isSuccess ? '#166534' : '#1e40af';
            const svg = isSuccess 
                ? '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                : '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';

            div.innerHTML = `
                <div style="display: flex; gap: 12px;">
                    <div style="width: 32px; height: 32px; background: ${iconBg}; color: ${iconColor}; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        ${svg}
                    </div>
                    <div>
                        <div style="font-size: 13px; font-weight: 700; color: #0f172a;">${title}</div>
                        <div style="font-size: 12px; color: #475569; margin-top: 2px;">${message}</div>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">${time}</div>
                    </div>
                </div>
            `;
            
            panel.insertBefore(div, panel.firstChild);
            setTimeout(() => { div.style.background = '#f8fafc'; }, 2000);
        }
    }

        function openLogoutModal() {
        Swal.fire({
            title: 'Log out?',
            text: 'Are you sure you want to log out of your account? You will need to log in again to access the system.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Yes, Log out'
        }).then((result) => {
            if (result.isConfirmed) {
                if(window.OneSignal){
                    try {
                        OneSignal.logout().finally(function(){document.getElementById('resident-logout-form').submit();});
                    } catch(e) {
                        document.getElementById('resident-logout-form').submit();
                    }
                }else{
                    document.getElementById('resident-logout-form').submit();
                }
            }
        });
    }
    function closeLogoutModal() {}

    window.addEventListener('DOMContentLoaded', () => {
        if (new URLSearchParams(window.location.search).get('action') === 'logout') {
            openLogoutModal();
        }
    });

</script>
    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
    @yield('scripts')
</body>
</html>
