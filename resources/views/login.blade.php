<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Log In - Althesa Subdivision Management System">
    <title>Log In — Althesa</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        /* =============================================
           LOGIN PAGE FULL SCREEN SPLIT
           ============================================= */
        body.login-body {
            margin: 0;
            padding: 0;
            background: var(--bg);
        }
        .login-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        /* Left panel */
        .login-visual {
            background: linear-gradient(155deg, var(--primary) 0%, #0a1c14 100%);
            background: linear-gradient(155deg, #1a2e4a 0%, #0e1b2d 100%);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px;
        }

        .login-visual-bg {
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle at 80% 10%, rgba(59,130,246,0.08) 0%, transparent 50%),
                radial-gradient(circle at 10% 90%, rgba(37,99,235,0.15) 0%, transparent 45%);
            pointer-events: none;
        }

        .login-visual-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
            background-size: 50px 50px;
        }

        .login-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            z-index: 2;
            text-decoration: none;
        }

        .login-logo-img {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            object-fit: contain;
            margin: 0;
            box-shadow: none;
            background: rgba(255, 255, 255, 0.1);
            padding: 4px;
        }

        .login-logo-text {
            font-family: var(--font-display);
            font-size: 20px;
            color: var(--white);
        }

        .login-visual-body {
            position: relative;
            z-index: 2;
        }

        .login-visual-body h2 {
            font-family: var(--font-display);
            font-size: 38px;
            color: var(--white);
            line-height: 1.15;
            margin-bottom: 16px;
        }

        .login-visual-body h2 em {
            color: var(--accent);
            font-style: italic;
        }

        .login-visual-body p {
            color: rgba(255,255,255,0.55);
            font-size: 15px;
            line-height: 1.7;
            max-width: 340px;
        }

        .login-info-cards {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 36px;
        }

        .login-info-card {
            display: flex;
            align-items: center;
            gap: 14px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 14px 18px;
        }

        .login-info-card .icon {
            font-size: 20px;
            width: 36px;
            text-align: center;
        }

        .login-info-card .text {
            display: flex;
            flex-direction: column;
        }

        .login-info-card .text strong {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: rgba(255,255,255,0.85);
        }

        .login-info-card .text span {
            font-size: 12px;
            color: rgba(255,255,255,0.4);
        }

        .login-visual-footer {
            position: relative;
            z-index: 2;
            font-size: 12px;
            color: rgba(255,255,255,0.2);
        }

        /* Right panel */
        .login-form-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px;
            background: var(--bg);
        }

        .login-form-container {
            width: 100%;
            max-width: 400px;
        }

        .login-form-header {
            margin-bottom: 36px;
        }

        .login-form-header h1 {
            font-family: var(--font-display);
            font-size: 30px;
            color: var(--text-dark);
            margin-bottom: 6px;
        }

        .login-form-header p {
            font-size: 14px;
            color: var(--text-mid);
        }

        .login-role-toggle {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            position: relative;
            background: var(--border);
            border-radius: 10px;
            padding: 4px;
            margin-bottom: 16px;
            isolation: isolate; /* Create stacking context */
        }

        .role-indicator {
            position: absolute;
            top: 4px;
            bottom: 4px;
            left: 4px;
            width: calc(25% - 2px);
            background: var(--primary);
            border-radius: 7px;
            transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
            z-index: 1;
            box-shadow: 0 2px 8px rgba(26, 46, 74, 0.25);
        }

        .role-btn {
            flex: 1;
            padding: 10px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-mid);
            text-align: center;
            cursor: pointer;
            transition: color 0.3s;
            border: none;
            background: none;
            position: relative;
            z-index: 2; /* Sit above the indicator */
        }

        .role-btn.active {
            color: var(--white);
        }

        .input-icon-wrap {
            position: relative;
        }

        .input-icon-wrap .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            pointer-events: none;
        }

        .input-icon-wrap .form-input {
            padding-left: 42px;
        }

        .input-show-pw {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            cursor: pointer;
            color: var(--text-light);
            background: none;
            border: none;
            padding: 0;
            line-height: 1;
        }

        .login-forgot {
            text-align: right;
            font-size: 13px;
            color: var(--accent);
            font-weight: 500;
            cursor: pointer;
            transition: color 0.2s;
        }

        .login-forgot:hover { text-decoration: underline; }

        .login-submit {
            width: 100%;
            padding: 13px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            background: var(--accent);
            color: var(--white);
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .login-submit:active {
            transform: scale(0.98);
        }

        .login-submit:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.25);
        }

        .login-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-light);
            font-size: 12px;
            margin: 20px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .login-divider::before,
        .login-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .login-back {
            text-align: center;
            font-size: 13px;
            color: var(--text-mid);
            margin-top: 16px;
        }

        .login-back a {
            color: var(--accent);
            font-weight: 600;
            transition: color 0.2s;
        }

        .login-back a:hover { text-decoration: underline; }

        @media (max-width: 768px) {
            .login-page { grid-template-columns: 1fr; }
            .login-visual { display: none; }
        }
    </style>
</head>
<body class="login-body">

<div class="login-page">

    <div class="login-visual">
        <div class="login-visual-bg"></div>
        <div class="login-visual-grid"></div>
        <a href="/" class="login-logo">
            <img src="{{ asset('images/logo.png') }}" alt="Althesa Logo" class="login-logo-img">
            <span class="login-logo-text">Althesa</span>
        </a>
        <div class="login-visual-body">
            <h2>Your Community,<br><em>Your Portal</em></h2>
            <p>Manage dues, schedule appointments, and stay connected with everything happening in Althesa.</p>
            <div class="login-info-cards">
                <div class="login-info-card">
                    <div class="icon">💳</div>
                    <div class="text"><strong>Pay Monthly Dues</strong><span>Via GCash — fast and secure</span></div>
                </div>
                <div class="login-info-card">
                    <div class="icon">📋</div>
                    <div class="text"><strong>File Complaints</strong><span>Track status in real-time</span></div>
                </div>
                <div class="login-info-card">
                    <div class="icon">🗓️</div>
                    <div class="text"><strong>Schedule Appointments</strong><span>Book office visits</span></div>
                </div>
            </div>
        </div>

        <div class="login-visual-footer">Secure login · Althesa © {{ date('Y') }}</div>
    </div>

    <div class="login-form-panel">
        <div class="login-form-container fade-up">
            <div class="login-form-header">
                <h1>Welcome Back</h1>
                <p>Sign in to your account to continue</p>
            </div>

            <!-- Error message -->
            <div id="login-error" style="display:none; background:#fee2e2; color:#991b1b; padding:10px 14px; border-radius:8px; font-size:13.5px; font-weight:500; margin-bottom:16px;"></div>

            <div style="display:flex; flex-direction:column; gap:16px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Email Address</label>
                    <div class="input-icon-wrap">
                        <span class="input-icon">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </span>
                        <input type="email" id="email-input" class="form-input" placeholder="you@example.com">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Password</label>
                    <div class="input-icon-wrap">
                        <span class="input-icon">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                        </span>
                        <input type="password" id="password-input" class="form-input" placeholder="Enter your password">
                        <button type="button" class="input-show-pw" id="show-pw">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="login-forgot">Forgot your password?</div>

                <button class="login-submit" id="login-btn" onclick="doLogin()">
                    <span id="login-btn-text">Sign In →</span>
                </button>

                <div style="height: 16px;"></div>

                <a href="/appointment" class="btn btn-outline" style="width:100%; justify-content:center; text-decoration: none; border-color: #e2e8f0;">
                    📅 Book an Office Visit as Guest
                </a>
            </div>

            <div class="login-back"><a href="/">← Back to Homepage</a></div>
        </div>
    </div>
</div>

<script>
    function doLogin() {
        const email = document.getElementById('email-input').value.trim().toLowerCase();
        const password = document.getElementById('password-input').value;
        const errEl = document.getElementById('login-error');
        const btnText = document.getElementById('login-btn-text');
        const btn = document.getElementById('login-btn');

        errEl.style.display = 'none';

        if (!email || !password) {
            errEl.textContent = 'Please enter your email and password.';
            errEl.style.display = 'block';
            return;
        }

        btnText.textContent = 'Signing in...';
        btn.disabled = true;
        btn.style.opacity = '0.75';

        setTimeout(() => {
            btnText.textContent = 'Authenticating...';
            
            setTimeout(() => {
                let target = '/resident/dashboard'; // Default resident portal
                
                if (email.includes('admin')) {
                    target = '/admin/dashboard';
                } else if (email.includes('guard') || email.includes('security')) {
                    target = '/guard/dashboard';
                } else if (email.includes('finance') || email.includes('cpa') || email.includes('officer')) {
                    target = '/finance/dashboard';
                }
                
                window.location.href = target;
            }, 800);
        }, 1200);
    }

    // Toggle password visibility
    document.getElementById('show-pw').addEventListener('click', () => {
        const inp = document.getElementById('password-input');
        const btn = document.getElementById('show-pw');
        
        if (inp.type === 'password') {
            inp.type = 'text';
            btn.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
        } else {
            inp.type = 'password';
            btn.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        }
    });

    // Enter key support
    document.addEventListener('keydown', e => {
        if (e.key === 'Enter') doLogin();
    });
</script>
</body>
</html>
