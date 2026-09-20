<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Guard Security — Althesa</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/views/layout-guard.css') }}">
</head>
<body class="admin-body">

<div class="admin-layout">
    <div id="sidebarOverlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Guard Sidebar -->
    <aside class="sidebar" id="mainSidebar" style="border-right: 1px solid #e2e8f0;">
        <div class="sidebar-logo">
            <div class="logo-icon" style="background: var(--guard-primary);">
                <img src="{{ asset('images/logo.png') }}" alt="Althesa Icon" style="width: 24px; height: 24px; object-fit: contain;">
            </div>
            <h2>Althesa</h2>
            <p>Security Portal</p>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section-label">Operations</div>
            <a href="/guard/dashboard" class="nav-item {{ request()->is('guard/dashboard') ? 'active' : '' }}">
                <span class="nav-icon">🛡️</span>
                <span>Gate Scanner</span>
            </a>
            
            <div class="nav-section-label">Logs & Monitoring</div>
            <a href="/guard/history" class="nav-item {{ request()->is('guard/history') ? 'active' : '' }}">
                <span class="nav-icon">📋</span>
                <span>Visitors</span>
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <a href="#" class="nav-item" onclick="openLogoutModal(); return false;">
                <span class="nav-icon">🚪</span>
                <span>End Shift</span>
            </a>
            <form id="logout-form" action="/logout" method="POST" style="display: none;">
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
                <div class="topbar-title">@yield('title', 'Security Station')</div>
            </div>
            <div class="topbar-actions">
                <div style="background: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                    <span style="display: inline-block; width: 6px; height: 6px; background: #ef4444; border-radius: 50%; box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.3);"></span>
                    Station: Main Gate
                </div>
            </div>
        </header>

        <main class="content-body" style="padding: 32px; background: #f8fafc;">
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
</script>
    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
    <!-- Logout Confirmation Modal -->
    <div id="logoutConfirmModal" class="modal-overlay" style="display: none; align-items: center; justify-content: center; z-index: 99999; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px);">
        <div class="modal-content" style="background: #fff; max-width: 400px; width: 90%; border-radius: 20px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); overflow: hidden; transform: scale(0.95); transition: transform 0.2s ease-out;">
            <div style="padding: 24px; text-align: center;">
                <div style="width: 64px; height: 64px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                </div>
                <h3 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0 0 8px;">End Shift & Log out?</h3>
                <p style="font-size: 14px; color: #64748b; margin: 0;">Are you sure you want to end your shift and log out?</p>
            </div>
            <div style="padding: 16px 24px; background: #f8fafc; display: flex; gap: 12px; border-top: 1px solid #e2e8f0;">
                <button class="btn btn-outline" style="flex: 1; justify-content: center; padding: 12px; font-weight: 600;" onclick="closeLogoutModal()">Cancel</button>
                <button class="btn btn-primary" style="flex: 1; justify-content: center; padding: 12px; font-weight: 600; background: #ef4444; border-color: #ef4444;" onclick="document.getElementById('logout-form').submit();">Yes, End Shift</button>
            </div>
        </div>
    </div>
    <script>
        function openLogoutModal() {
            const modal = document.getElementById('logoutConfirmModal');
            modal.style.display = 'flex';
            setTimeout(() => { modal.firstElementChild.style.transform = 'scale(1)'; }, 10);
        }
        function closeLogoutModal() {
            const modal = document.getElementById('logoutConfirmModal');
            modal.firstElementChild.style.transform = 'scale(0.95)';
            setTimeout(() => { modal.style.display = 'none'; }, 200);
        }
    </script>
    
    @yield('scripts')
</body>
</html>
