<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Portal — Althesa</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <style>
        :root {
            --guard-primary: #1e3a8a; /* Blue 900 */
            --guard-primary-soft: #eff6ff; /* Blue 50 */
            --guard-accent: #3b82f6; /* Blue 500 */
        }
        .sidebar-logo p { color: var(--guard-accent); font-weight: 700; }
        .nav-item.active { background: var(--guard-primary-soft); color: var(--guard-primary); border-left: 4px solid var(--guard-primary); }
    </style>
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
            <a href="/login" class="nav-item" onclick="return confirm('End Shift and Log out?')">
                <span class="nav-icon">🚪</span>
                <span>End Shift</span>
            </a>
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
</body>
</html>
