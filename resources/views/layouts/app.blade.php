<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Althesa - Modern Subdivision Management System">
    <title>@yield('title', 'Althesa - Subdivision Management System')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/subdivision-store.js') }}"></script>
    @yield('head')
</head>
<body>
    <div class="page-wrapper">

        {{-- Navbar --}}
        <nav class="navbar" id="navbar">
            <div class="navbar-inner">
                <a href="/" class="navbar-brand">
                    <img src="{{ asset('images/logo.png') }}" alt="Althesa Logo" class="navbar-logo-img">
                    <span class="navbar-brand-text">Althesa</span>
                </a>

                <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
                    <span></span><span></span><span></span>
                </button>

                <div class="navbar-nav" id="navMenu">
                    <a href="/" class="nav-link {{ request()->is('/') ? 'active' : '' }}">Home</a>
                    <a href="/appointment" class="nav-link {{ request()->is('appointment') ? 'active' : '' }}">
                        <span class="nav-link-icon">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            Appointment
                        </span>
                    </a>
                    <a href="/visitor-pin" class="nav-link {{ request()->is('visitor-pin') ? 'active' : '' }}">
                        <span class="nav-link-icon">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                            Visitor PIN
                        </span>
                    </a>
                    <a href="/login" class="btn btn-primary btn-sm" style="margin-left: 8px;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                        Log In
                    </a>
                </div>
            </div>
        </nav>

        {{-- Page Content --}}
        <main class="page-content">
            @yield('content')
        </main>

        {{-- Footer --}}
        <footer class="footer">
            <p class="footer-text">&copy; {{ date('Y') }} <strong>Althesa</strong>. Subdivision Management System. All rights reserved.</p>
        </footer>

    </div>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 10) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Mobile nav toggle
        document.getElementById('navToggle').addEventListener('click', function() {
            document.getElementById('navMenu').classList.toggle('active');
        });
    </script>
    @yield('scripts')
    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>
