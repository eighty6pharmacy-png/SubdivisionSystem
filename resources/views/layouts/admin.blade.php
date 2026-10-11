<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard — Althesa</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script src="{{ asset('js/subdivision-store.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <!-- Sidebar Overlay for Mobile -->
        <div id="sidebarOverlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <!-- Sidebar -->
        <aside class="sidebar" id="mainSidebar">
            <div class="sidebar-logo">
                <div class="logo-icon" style="background: var(--admin-primary);">
                    <img src="{{ asset('images/logo.png') }}" alt="Althesa Icon"
                        style="width: 24px; height: 24px; object-fit: contain;">
                </div>
                <h2>Althesa</h2>
                <p>Admin Portal</p>
            </div>

            <nav class="sidebar-nav" aria-label="Admin navigation">
                <div class="nav-section">
                    <div class="nav-section-label">Main</div>
                    <a href="/admin/dashboard" class="nav-item {{ request()->is('admin/dashboard') ? 'active' : '' }}">
                        <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        <span class="nav-item-text">Dashboard Overview</span>
                    </a>
                    <a href="/admin/users" class="nav-item {{ request()->is('admin/users*') ? 'active' : '' }}">
                        <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span class="nav-item-text">User Management</span>
                    </a>
                    <a href="/admin/buyer-master-list" class="nav-item {{ request()->is('admin/buyer-master-list*') ? 'active' : '' }}">
                        <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        <span class="nav-item-text">Buyer Master List</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-label">Finance &amp; Utilities</div>
                    <a href="/admin/reservation-fee" class="nav-item {{ request()->is('admin/reservation-fee*') ? 'active' : '' }}">
                        <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="nav-item-text">Reservation Fee</span>
                    </a>
                    <a href="/admin/downpayment-fee" class="nav-item {{ request()->is('admin/downpayment-fee*') ? 'active' : '' }}">
                        <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span class="nav-item-text">Downpayment</span>
                    </a>
                    <a href="/admin/financing" class="nav-item {{ request()->is('admin/financing*') ? 'active' : '' }}">
                        <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span class="nav-item-text">Finance</span>
                    </a>
                    <a href="/admin/billing" class="nav-item {{ request()->is('admin/billing*') ? 'active' : '' }}">
                        <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span class="nav-item-text">Electricity</span>
                    </a>
                    <a href="/admin/water" class="nav-item {{ request()->is('admin/water*') ? 'active' : '' }}">
                        <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-4 6-6 9-6 12a6 6 0 1012 0c0-3-2-6-6-12z"/></svg>
                        <span class="nav-item-text">Water</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-label">Property Management</div>
                    <a href="/admin/gis" class="nav-item {{ request()->is('admin/gis*') ? 'active' : '' }}">
                        <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        <span class="nav-item-text">Property GIS Monitoring</span>
                    </a>
                </div>

                <div class="nav-section nav-section--last">
                    <div class="nav-section-label">Community</div>
                    <a href="/admin/incidents" class="nav-item nav-item--badge {{ request()->is('admin/incidents*') ? 'active' : '' }}">
                        <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span class="nav-item-text">Incident Reporting</span>
                        @if(isset($sidebarCounts) && $sidebarCounts['incidents'] > 0)
                            <span class="nav-badge">{{ $sidebarCounts['incidents'] }}</span>
                        @endif
                    </a>
                    <a href="/admin/announcements" class="nav-item {{ request()->is('admin/announcements*') ? 'active' : '' }}">
                        <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        <span class="nav-item-text">Announcements</span>
                    </a>
                    <a href="/admin/appointments" class="nav-item nav-item--badge {{ request()->is('admin/appointments*') ? 'active' : '' }}">
                        <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="nav-item-text">Appointment Management</span>
                        @if(isset($sidebarCounts) && $sidebarCounts['appointments'] > 0)
                            <span class="nav-badge">{{ $sidebarCounts['appointments'] }}</span>
                        @endif
                    </a>
                    <a href="/admin/visitors" class="nav-item nav-item--badge {{ request()->is('admin/visitors*') ? 'active' : '' }}">
                        <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        <span class="nav-item-text">Visitor Approvals</span>
                        @if(isset($sidebarCounts) && $sidebarCounts['visitors'] > 0)
                            <span class="nav-badge">{{ $sidebarCounts['visitors'] }}</span>
                        @endif
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <a href="#" class="nav-item nav-item--logout" onclick="openLogoutModal(); return false;">
                    <svg class="nav-icon-svg" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span class="nav-item-text">Logout</span>
                </a>
                <form id="logout-form" action="/logout" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="admin-main">
            <!-- Topbar -->
            <header class="topbar">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <button class="hamburger-btn" onclick="toggleSidebar()" aria-label="Toggle Navigation">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div class="topbar-title">@yield('title', 'Dashboard')</div>
                </div>
                <div class="topbar-actions" style="display: flex; align-items: center; gap: 16px;">
                    <!-- Functional Notification Dropdown -->
                    <div class="notification-container" id="notifContainer" onclick="toggleNotifications(event)">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9" />
                            <path d="M13.73 21a2 2 0 01-3.46 0" />
                        </svg>
                        <span class="notif-dot" id="globalNotifDot" style="display: {{ (isset($adminUnreadCount) && $adminUnreadCount > 0) ? 'block' : 'none' }};"></span>

                        <div class="notification-dropdown" id="notifDropdown">
                            <div class="notification-header">
                                <span>System Notifications</span>
                                <span id="unreadCount" style="color: var(--accent);">{{ $adminUnreadCount ?? 0 }} New</span>
                            </div>
                            <div class="notification-list" id="notifList">
                                @if(isset($adminNotifications) && count($adminNotifications) > 0)
                                    @foreach($adminNotifications as $notif)
                                        <div class="notification-item {{ is_null($notif->read_at) ? 'unread' : '' }}">
                                            <div class="notification-title">{{ $notif->data['title'] ?? 'Notification' }}</div>
                                            <div>{{ $notif->data['message'] ?? '' }}</div>
                                            <div class="notification-time">{{ $notif->created_at->diffForHumans() }}</div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="notification-empty">No new notifications</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div style="font-size:13px; color:var(--text-mid); font-weight: 500;">Admin User</div>
                    <div class="topbar-avatar">AU</div>
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
            
            if (dropdown.classList.contains('active')) {
                fetch('/api/notifications/mark-read', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                }).then(() => {
                    document.getElementById('unreadCount').innerText = '0 New';
                    document.getElementById('globalNotifDot').style.display = 'none';
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                    });
                });
            }
            
            event.stopPropagation();
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function (event) {
            const container = document.getElementById('notifContainer');
            const dropdown = document.getElementById('notifDropdown');
            if (container && !container.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });

        // Window exposed function to allow specific modules (like Billing) to push notifications
        window.pushSystemNotification = function (title, message, time, isUnread = true) {
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
    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
    <!-- Global Loader -->
    <div id="global-loader" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 4px; background: rgba(59, 130, 246, 0.2); z-index: 999999;">
        <div style="height: 100%; background: #3b82f6; width: 30%; animation: loading-bar 1.5s infinite ease-in-out;"></div>
    </div>
    <style>
        @keyframes loading-bar {
            0% { transform: translateX(-100%); width: 30%; }
            50% { width: 50%; }
            100% { transform: translateX(350%); width: 30%; }
        }
        .loading-cursor { cursor: wait !important; }
        .fade-out-page { opacity: 0.5; pointer-events: none; transition: opacity 0.2s ease; }
    </style>
    
    <script>
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
                    try{OneSignal.logout().finally(function(){document.getElementById('logout-form').submit();});}catch(e){document.getElementById('logout-form').submit();}
                }else{
                    document.getElementById('logout-form').submit();
                }
            }
        });
    }
    function closeLogoutModal() {}

        // Add page transition effects
        document.addEventListener('DOMContentLoaded', () => {
            const links = document.querySelectorAll('a.nav-item');
            links.forEach(link => {
                if(!link.getAttribute('onclick') && link.getAttribute('href') !== '#') {
                    link.addEventListener('click', function(e) {
                        if (e.ctrlKey || e.metaKey || e.shiftKey) return; 
                        document.getElementById('global-loader').style.display = 'block';
                        const pageContent = document.querySelector('.page-content');
                        if (pageContent) pageContent.classList.add('fade-out-page');
                        document.body.classList.add('loading-cursor');
                    });
                }
            });
        });
    </script>
</body>

</html>
