<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Log In - Althesa Subdivision Management System">
    <title>Log In — Althesa</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/views/login.css') }}">
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

                <div class="login-forgot" onclick="openForgotModal()">Forgot your password?</div>

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
    async function doLogin() {
        const email = document.getElementById('email-input').value.trim();
        const password = document.getElementById('password-input').value;
        const errEl = document.getElementById('login-error');
        const btnText = document.getElementById('login-btn-text');
        const btn = document.getElementById('login-btn');

        errEl.style.display = 'none';

        if (!email && !password) {
            errEl.textContent = 'Please enter your email and password.';
            errEl.style.display = 'block';
            return;
        } else if (!email) {
            errEl.textContent = 'Please enter your email address.';
            errEl.style.display = 'block';
            return;
        } else if (!password) {
            errEl.textContent = 'Please enter your password.';
            errEl.style.display = 'block';
            return;
        }

        btnText.textContent = 'Authenticating...';
        btn.disabled = true;
        btn.style.opacity = '0.75';

        try {
            const response = await fetch('/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ email, password })
            });

            // Prevent crash if server returns HTML (e.g. 500 error or 419 Page Expired)
            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                throw new Error("Server configuration error. Please try refreshing the page.");
            }

            const data = await response.json();

            if (response.ok && data.success) {
                btnText.textContent = 'Success! Redirecting...';
                window.location.href = data.redirect;
            } else {
                throw new Error(data.message || 'Authentication failed.');
            }
        } catch (error) {
            errEl.textContent = error.message;
            errEl.style.display = 'block';
            btnText.textContent = 'Sign In →';
            btn.disabled = false;
            btn.style.opacity = '1';
        }
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
        if (e.key === 'Enter') {
            if (document.activeElement.id === 'email-input') {
                document.getElementById('password-input').focus();
            } else {
                doLogin();
            }
        }
    });
</script>

<!-- Forgot Password Modal -->
<div id="forgotPwModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--bg); width: 90%; max-width: 400px; border-radius: 16px; padding: 24px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin: 0; font-family: var(--font-display); font-size: 20px; color: var(--text-dark);">Reset Password</h3>
            <button onclick="closeForgotModal()" style="background: none; border: none; font-size: 20px; color: #94a3b8; cursor: pointer;">✕</button>
        </div>
        
        <div id="fp-step-1">
            <p style="font-size: 14px; color: var(--text-mid); margin-bottom: 16px;">Enter your email address and we'll send you a 6-digit OTP to reset your password.</p>
            <input type="email" id="fp-email" class="form-input" placeholder="you@example.com" style="width: 100%; box-sizing: border-box; margin-bottom: 16px; padding: 12px; border: 1px solid var(--border); border-radius: 8px;">
            <button class="login-submit" onclick="sendOtp()" id="fp-btn-send">Send OTP</button>
        </div>
        
        <div id="fp-step-2" style="display: none;">
            <p style="font-size: 14px; color: var(--text-mid); margin-bottom: 16px;">Enter the 6-digit OTP sent to your email.</p>
            <input type="text" id="fp-otp" class="form-input" placeholder="123456" style="width: 100%; box-sizing: border-box; margin-bottom: 16px; padding: 12px; border: 1px solid var(--border); border-radius: 8px; text-align: center; letter-spacing: 4px; font-weight: bold;">
            <button class="login-submit" onclick="verifyOtp()" id="fp-btn-verify">Verify OTP</button>
        </div>
        
        <div id="fp-step-3" style="display: none;">
            <p style="font-size: 14px; color: var(--text-mid); margin-bottom: 16px;">Set your new password.</p>
            <div class="input-icon-wrap" style="margin-bottom: 12px;">
                <input type="password" id="fp-new-pw" class="form-input" placeholder="New Password" style="width: 100%; box-sizing: border-box; padding: 12px; border: 1px solid var(--border); border-radius: 8px; padding-right: 40px;">
                <button type="button" class="input-show-pw" onclick="toggleFpPw('fp-new-pw', this)" style="right: 12px;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            <div class="input-icon-wrap" style="margin-bottom: 16px;">
                <input type="password" id="fp-confirm-pw" class="form-input" placeholder="Confirm New Password" style="width: 100%; box-sizing: border-box; padding: 12px; border: 1px solid var(--border); border-radius: 8px; padding-right: 40px;">
                <button type="button" class="input-show-pw" onclick="toggleFpPw('fp-confirm-pw', this)" style="right: 12px;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            <button class="login-submit" onclick="resetPassword()" id="fp-btn-reset">Reset Password</button>
        </div>
        
        <div id="fp-error" style="display: none; background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 8px; font-size: 13px; margin-top: 16px; text-align: center;"></div>
        <div id="fp-success" style="display: none; background: #d1fae5; color: #065f46; padding: 10px; border-radius: 8px; font-size: 13px; margin-top: 16px; text-align: center;"></div>
    </div>
</div>

<script>
    function openForgotModal() {
        document.getElementById('forgotPwModal').style.display = 'flex';
        document.getElementById('fp-step-1').style.display = 'block';
        document.getElementById('fp-step-2').style.display = 'none';
        document.getElementById('fp-step-3').style.display = 'none';
        document.getElementById('fp-email').value = '';
        document.getElementById('fp-error').style.display = 'none';
        document.getElementById('fp-success').style.display = 'none';
    }
    
    function closeForgotModal() {
        document.getElementById('forgotPwModal').style.display = 'none';
    }
    
    async function sendOtp() {
        const email = document.getElementById('fp-email').value.trim();
        if (!email) {
            showFpError("Please enter your email.");
            return;
        }
        
        const btn = document.getElementById('fp-btn-send');
        btn.textContent = 'Sending...';
        btn.disabled = true;
        hideFpError();
        
        try {
            const res = await fetch('/forgot-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') },
                body: JSON.stringify({ email })
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('fp-step-1').style.display = 'none';
                document.getElementById('fp-step-2').style.display = 'block';
                document.getElementById('fp-otp').value = '';
            } else {
                showFpError(data.message || "Failed to send OTP.");
            }
        } catch (e) {
            showFpError("An error occurred.");
        }
        btn.textContent = 'Send OTP';
        btn.disabled = false;
    }

    async function verifyOtp() {
        const email = document.getElementById('fp-email').value.trim();
        const otp = document.getElementById('fp-otp').value.trim();
        if (!otp) { showFpError("Please enter OTP."); return; }
        
        const btn = document.getElementById('fp-btn-verify');
        btn.textContent = 'Verifying...';
        btn.disabled = true;
        hideFpError();
        
        try {
            const res = await fetch('/verify-otp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') },
                body: JSON.stringify({ email, otp })
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('fp-step-2').style.display = 'none';
                document.getElementById('fp-step-3').style.display = 'block';
                document.getElementById('fp-new-pw').value = '';
            } else {
                showFpError(data.message || "Invalid OTP.");
            }
        } catch (e) {
            showFpError("An error occurred.");
        }
        btn.textContent = 'Verify OTP';
        btn.disabled = false;
    }

    async function resetPassword() {
        const email = document.getElementById('fp-email').value.trim();
        const otp = document.getElementById('fp-otp').value.trim();
        const password = document.getElementById('fp-new-pw').value;
        const confirmPassword = document.getElementById('fp-confirm-pw').value;
        if (!password || password.length < 8) { showFpError("Password must be at least 8 characters."); return; }
        if (password !== confirmPassword) { showFpError("Passwords do not match."); return; }
        
        const btn = document.getElementById('fp-btn-reset');
        btn.textContent = 'Resetting...';
        btn.disabled = true;
        hideFpError();
        
        try {
            const res = await fetch('/reset-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') },
                body: JSON.stringify({ email, otp, password })
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('fp-step-3').style.display = 'none';
                const successEl = document.getElementById('fp-success');
                successEl.textContent = "Password reset successfully! You can now log in.";
                successEl.style.display = 'block';
                setTimeout(() => { closeForgotModal(); }, 3000);
            } else {
                showFpError(data.message || "Failed to reset password.");
            }
        } catch (e) {
            showFpError("An error occurred.");
        }
        btn.textContent = 'Reset Password';
        btn.disabled = false;
    }
    
    function showFpError(msg) {
        const el = document.getElementById('fp-error');
        el.textContent = msg;
        el.style.display = 'block';
    }
    function hideFpError() {
        document.getElementById('fp-error').style.display = 'none';
    }
    
    function toggleFpPw(inputId, btn) {
        const inp = document.getElementById(inputId);
        if (inp.type === 'password') {
            inp.type = 'text';
            btn.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
        } else {
            inp.type = 'password';
            btn.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        }
    }
</script>
</body>
</html>
