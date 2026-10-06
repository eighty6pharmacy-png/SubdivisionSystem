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

            <nav class="sidebar-nav">
                <div class="nav-section-label">Main</div>
                <a href="/admin/dashboard" class="nav-item {{ request()->is('admin/dashboard') ? 'active' : '' }}">

                    <span>Dashboard Overview</span>
                </a>
                <a href="/admin/users" class="nav-item {{ request()->is('admin/users*') ? 'active' : '' }}">

                    <span>User Management</span>
                </a>

                <a href="/admin/buyer-master-list"
                    class="nav-item {{ request()->is('admin/buyer-master-list*') ? 'active' : '' }}">

                    <span>Buyer Master List</span>
                </a>

                <div class="nav-section-label">Finance & Utilities</div>
                <a href="/admin/billing" class="nav-item {{ request()->is('admin/billing*') ? 'active' : '' }}">

                    <span>Billing with Electricity</span>
                </a>
                <a href="/admin/water" class="nav-item {{ request()->is('admin/water*') ? 'active' : '' }}">

                    <span>Water</span>
                </a>
                <a href="/admin/reservation-fee"
                    class="nav-item {{ request()->is('admin/reservation-fee*') ? 'active' : '' }}">

                    <span>Reservation Fee</span>
                </a>
                <a href="/admin/downpayment-fee"
                    class="nav-item {{ request()->is('admin/downpayment-fee*') ? 'active' : '' }}">

                    <span>Downpayment Fee</span>
                </a>
                <a href="/admin/financing"
                    class="nav-item {{ request()->is('admin/financing*') ? 'active' : '' }}">

                    <span>Loans & Addt'l Fees</span>
                </a>
                <div class="nav-section-label">Property Management</div>
                <a href="/admin/gis" class="nav-item {{ request()->is('admin/gis*') ? 'active' : '' }}">

                    <span>Property GIS Monitoring</span>
                </a>

                <div class="nav-section-label">Community</div>
                <a href="/admin/incidents" class="nav-item {{ request()->is('admin/incidents*') ? 'active' : '' }}" style="display: flex; justify-content: space-between; align-items: center;">
                    <div>

                        <span>Incident Reporting</span>
                    </div>
                    @if(isset($sidebarCounts) && $sidebarCounts['incidents'] > 0)
                        <span style="background: var(--danger); color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold;">{{ $sidebarCounts['incidents'] }}</span>
                    @endif
                </a>
                <a href="/admin/announcements"
                    class="nav-item {{ request()->is('admin/announcements*') ? 'active' : '' }}">

                    <span>Announcements</span>
                </a>
                <a href="/admin/appointments"
                    class="nav-item {{ request()->is('admin/appointments*') ? 'active' : '' }}" style="display: flex; justify-content: space-between; align-items: center;">
                    <div>

                        <span>Appointment Management</span>
                    </div>
                    @if(isset($sidebarCounts) && $sidebarCounts['appointments'] > 0)
                        <span style="background: var(--danger); color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold;">{{ $sidebarCounts['appointments'] }}</span>
                    @endif
                </a>
                <a href="/admin/visitors" class="nav-item {{ request()->is('admin/visitors*') ? 'active' : '' }}" style="display: flex; justify-content: space-between; align-items: center;">
                    <div>

                        <span>Visitor Approvals</span>
                    </div>
                    @if(isset($sidebarCounts) && $sidebarCounts['visitors'] > 0)
                        <span style="background: var(--danger); color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold;">{{ $sidebarCounts['visitors'] }}</span>
                    @endif
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="#" class="nav-item" onclick="openLogoutModal(); return false;">

                    <span>Logout</span>
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
