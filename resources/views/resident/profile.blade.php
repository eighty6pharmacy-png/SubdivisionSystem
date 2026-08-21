@extends('layouts.resident')

@section('title', 'My Profile Settings')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">

<div class="fade-in" style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 32px;">
        <h1 style="font-size: 28px; font-weight: 800; color: #0f172a;">Account Settings</h1>
        <p style="color: #64748b;">Manage your personal information and portal security.</p>
    </div>

    <!-- Personal Info -->
    <div class="analytic-card" style="padding: 32px; margin-bottom: 24px;">
        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 24px;">Personal Information</h3>
        <form class="responsive-grid grid-2">
            <div style="grid-column: span 2;">
                <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Full Name</label>
                <input type="text" id="profName" class="filter-select" style="width: 100%; margin-top: 8px;" value="Juan Dela Cruz">
            </div>
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Primary Email</label>
                <input type="email" id="profEmail" class="filter-select" style="width: 100%; margin-top: 8px;" value="juan@example.com">
            </div>
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Contact Number</label>
                <input type="text" id="profContact" class="filter-select" style="width: 100%; margin-top: 8px;" value="0917-123-4567">
            </div>
            <div style="grid-column: span 2; display: flex; align-items: center; justify-content: flex-end; gap: 16px; margin-top: 8px;">
                <span id="profStatus" style="font-size: 13px; font-weight: 600; color: #10b981; display: none;">✓ Profile Saved</span>
                <button type="button" class="btn btn-primary" style="padding: 12px 24px;" onclick="updateProfileMock()">
                    Save Profile Changes
                </button>
            </div>
        </form>
    </div>

    <!-- Gate Access PIN -->
    <div class="analytic-card" style="padding: 32px; margin-bottom: 24px; background: #f0fdfa; border-color: #5eead4;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div>
                <h3 style="font-size: 16px; font-weight: 800; color: #0f766e; margin-bottom: 4px;">Permanent Gate Access PIN</h3>
                <p style="font-size: 13px; color: #115e59;">Provide this PIN to the guard at the gate to verify your identity as a resident.</p>
            </div>
            <div style="background: #fff; padding: 16px 32px; border-radius: 12px; border: 2px dashed #14b8a6; text-align: center;">
                <span style="font-size: 32px; font-weight: 900; letter-spacing: 0.1em; color: #0f766e; font-family: monospace;">884219</span>
            </div>
        </div>
    </div>

    <!-- Security -->
    <div class="analytic-card" style="padding: 32px; margin-bottom: 24px;">
        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 24px;">Security & Password</h3>
        <form style="display: flex; flex-direction: column; gap: 20px;">
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">New Password</label>
                <input type="password" id="pwNew" class="filter-select" style="width: 100%; margin-top: 8px;" placeholder="••••••••">
            </div>
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Confirm New Password</label>
                <input type="password" id="pwConfirm" class="filter-select" style="width: 100%; margin-top: 8px;" placeholder="••••••••">
            </div>
            <div id="pwError" style="font-size: 12px; font-weight: 600; color: #ef4444; display: none;">Passwords do not match.</div>
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 16px;">
                <span id="pwStatus" style="font-size: 13px; font-weight: 600; color: #10b981; display: none;">✓ Security Updated</span>
                <button type="button" class="btn btn-outline" style="padding: 10px 20px;" onclick="updatePasswordMock()">
                    Update Password
                </button>
            </div>
        </form>
    </div>

    <!-- No Deactivate Option - Residents cannot delete their own accounts -->
</div>

<script>
    function updateProfileMock() {
        const name = document.getElementById('profName').value;
        const status = document.getElementById('profStatus');
        
        status.style.display = 'block';
        
        // Simulating global push notification if available
        if(window.pushSystemNotification) {
            window.pushSystemNotification('Profile Updated', 'Your personal details have been saved.', new Date().toLocaleTimeString());
        }
        
        setTimeout(() => {
            status.style.display = 'none';
        }, 3000);
    }

    function updatePasswordMock() {
        const p1 = document.getElementById('pwNew').value;
        const p2 = document.getElementById('pwConfirm').value;
        const err = document.getElementById('pwError');
        const stat = document.getElementById('pwStatus');

        err.style.display = 'none';
        stat.style.display = 'none';

        if(p1 === '') return;

        if (p1 !== p2) {
            err.style.display = 'block';
            return;
        }

        stat.style.display = 'block';
        document.getElementById('pwNew').value = '';
        document.getElementById('pwConfirm').value = '';

        setTimeout(() => stat.style.display = 'none', 3000);
    }

    function logoutSession() {
        if (confirm("Log out of your resident portal session?")) {
            window.location.href = '/login';
        }
    }
</script>

@endsection
