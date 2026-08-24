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
    <style>
        :root {
            --res-primary: #059669; /* Emerald 600 */
            --res-primary-soft: #ecfdf5;
        }
        .sidebar-logo p { color: var(--res-primary); font-weight: 700; }
        .nav-item.active { background: var(--res-primary-soft); color: var(--res-primary); border-left: 4px solid var(--res-primary); }
    </style>
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
                <span class="nav-icon">🏠</span>
                <span>My Dashboard</span>
            </a>
            
            <div class="nav-section-label">Utilities & Payments</div>
            <a href="/resident/electricity" class="nav-item {{ request()->is('resident/electricity') ? 'active' : '' }}">
                <span class="nav-icon">⚡</span>
                <span>Electricity Bill</span>
            </a>
            <a href="/resident/water" class="nav-item {{ request()->is('resident/water') ? 'active' : '' }}">
                <span class="nav-icon">💧</span>
                <span>Water Bill</span>
            </a>

            <div class="nav-section-label">Security & Access</div>
            <a href="/resident/visitors" class="nav-item {{ request()->is('resident/visitors') ? 'active' : '' }}">
                <span class="nav-icon">🔑</span>
                <span>Visitor PINs</span>
            </a>
            
            <div class="nav-section-label">Community</div>
            <a href="/resident/incidents" class="nav-item {{ request()->is('resident/incidents') ? 'active' : '' }}">
                <span class="nav-icon">📋</span>
                <span>Report Incident</span>
            </a>

            <div class="nav-section-label">Account</div>
            <a href="/resident/profile" class="nav-item {{ request()->is('resident/profile') ? 'active' : '' }}">
                <span class="nav-icon">👤</span>
                <span>My Profile</span>
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <a href="javascript:void(0)" class="nav-item" onclick="openLogoutModal()">
                <span class="nav-icon">🚪</span>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Custom Logout Modal -->
    <div id="logoutModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 9999; background: rgba(15,23,42,0.6); backdrop-filter: blur(8px);">
        <div class="bill-modal-content" style="max-width: 400px; text-align: center; padding: 40px 32px; border-radius: 28px;">
            <div style="width: 64px; height: 64px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </div>
            <h2 style="font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 12px;">Ending Session?</h2>
            <p style="font-size: 14px; color: #64748b; line-height: 1.6; margin-bottom: 32px;">Are you sure you want to log out? Any unsaved changes in your profile or reports may be lost.</p>
            <div style="display: flex; gap: 12px;">
                <button class="btn btn-outline" style="flex: 1; justify-content: center; padding: 14px;" onclick="closeLogoutModal()">Cancel</button>
                <a href="/login" class="btn btn-primary" style="flex: 1; justify-content: center; padding: 14px; background: #ef4444; border-color: #ef4444; text-decoration: none;">Yes, Log Out</a>
            </div>
        </div>
    </div>

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
                    <div style="position: absolute; top: 2px; right: 2px; width: 10px; height: 10px; background: #ef4444; border: 2px solid #fff; border-radius: 50%;"></div>
                </div>

                <div id="resLotBadge" style="background: var(--res-primary-soft); color: var(--res-primary); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                    @php $userLot = \Illuminate\Support\Facades\Auth::user()->lots->first(); @endphp
                    @if($userLot)
                        Block {{ $userLot->block }} Lot {{ $userLot->lot_number }}
                    @else
                        No Assigned Lot
                    @endif
                </div>
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
        if (new URLSearchParams(window.location.search).get('action') !== 'logout') {
            window.history.pushState(null, '', '?action=logout');
        }
        document.getElementById('logoutModal').style.display = 'flex';
    }

    function closeLogoutModal() {
        window.history.replaceState(null, '', window.location.pathname);
        document.getElementById('logoutModal').style.display = 'none';
    }

    window.addEventListener('DOMContentLoaded', () => {
        if (new URLSearchParams(window.location.search).get('action') === 'logout') {
            openLogoutModal();
        }
    });

</script>
</body>
</html>
