<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Dashboard — Althesa</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="admin-body">

<div class="admin-layout">
    <!-- Sidebar Overlay for Mobile -->
    <div id="sidebarOverlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="mainSidebar">
        <div class="sidebar-logo">
            <div class="logo-icon" style="background: var(--finance-primary);">
                <img src="{{ asset('images/logo.png') }}" alt="Althesa Icon" style="width: 24px; height: 24px; object-fit: contain;">
            </div>
            <h2>Althesa</h2>
            <p>Finance Portal</p>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section-label">Main</div>
            <a href="/finance/dashboard" class="nav-item {{ request()->is('finance/dashboard') ? 'active' : '' }}">
                <span class="nav-icon">📊</span>
                <span>Dashboard & Meter Reading</span>
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <a href="/login" class="nav-item" onclick="return confirm('Are you sure you want to log out?')">
                <span class="nav-icon">🚪</span>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="admin-main">
        <!-- Topbar -->
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 12px;">
                <button class="hamburger-btn" onclick="toggleSidebar()" aria-label="Toggle Navigation">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="topbar-title">@yield('title', 'Dashboard')</div>
            </div>
            <div class="topbar-actions">
                <!-- Functional Notification Dropdown -->
                <div class="notification-container" id="notifContainer" onclick="toggleNotifications(event)">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                    <span class="notif-dot" id="globalNotifDot" style="display: none;"></span>

                    <div class="notification-dropdown" id="notifDropdown">
                        <div class="notification-header">
                            <span>System Notifications</span>
                            <span id="unreadCount" style="color: var(--accent);">0 New</span>
                        </div>
                        <div class="notification-list" id="notifList">
                            <!-- Injected dynamically by module JS -->
                            <div class="notification-empty">No new notifications</div>
                        </div>
                    </div>
                </div>

                <div style="font-size:13px; color:var(--text-mid); font-weight: 500;">Finance Officer</div>
                <div class="topbar-avatar">FO</div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="page-content">
            @yield('content')
        </main>
    </div>
</div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        }

        // Global Notification Logic
        function toggleNotifications(event) {
            const dropdown = document.getElementById('notifDropdown');
            dropdown.classList.toggle('active');
            event.stopPropagation();
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const container = document.getElementById('notifContainer');
            const dropdown = document.getElementById('notifDropdown');
            if (container && !container.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });

        // Window exposed function to allow specific modules (like Billing) to push notifications
        window.pushSystemNotification = function(title, message, time, isUnread = true) {
            const list = document.getElementById('notifList');
            const dot = document.getElementById('globalNotifDot');
            const count = document.getElementById('unreadCount');
            
            // Remove empty state if present
            const emptyState = list.querySelector('.notification-empty');
            if (emptyState) emptyState.remove();

            // Create item
            const item = document.createElement('div');
            item.className = `notification-item ${isUnread ? 'unread' : ''}`;
            item.innerHTML = `
                <div class="notification-title">${title}</div>
                <div>${message}</div>
                <div class="notification-time">${time}</div>
            `;
            
            // Prepend
            list.insertBefore(item, list.firstChild);

            if (isUnread) {
                dot.style.display = 'block';
                let current = parseInt(count.innerText) || 0;
                count.innerText = (current + 1) + ' New';
            }
        };
    </script>
    @stack('scripts')
</body>
</html>
