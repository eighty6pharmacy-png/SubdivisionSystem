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
                <input type="text" id="profName" class="filter-select" style="width: 100%; margin-top: 8px; background-color: #f1f5f9; cursor: not-allowed;" value="{{ \Illuminate\Support\Facades\Auth::user()->name }}" readonly>
            </div>
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Primary Email</label>
                <input type="email" id="profEmail" class="filter-select" style="width: 100%; margin-top: 8px; background-color: #f1f5f9; cursor: not-allowed;" value="{{ \Illuminate\Support\Facades\Auth::user()->email }}" readonly>
            </div>
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Contact Number</label>
                <input type="text" id="profContact" class="filter-select" style="width: 100%; margin-top: 8px; background-color: #f1f5f9; cursor: not-allowed;" value="{{ \Illuminate\Support\Facades\Auth::user()->contact_number }}" readonly>
            </div>
        </form>
    </div>



    <!-- Security -->
    <div class="analytic-card" style="padding: 32px; margin-bottom: 24px;">
        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 24px;">Security & Password</h3>
        <form id="passwordUpdateForm" onsubmit="updateResidentPassword(event)" style="display: flex; flex-direction: column; gap: 20px;">
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Current Password</label>
                <input type="password" id="pwCurrent" class="filter-select" style="width: 100%; margin-top: 8px;" placeholder="••••••••" required>
            </div>
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">New Password (min. 8 characters)</label>
                <input type="password" id="pwNew" class="filter-select" style="width: 100%; margin-top: 8px;" placeholder="••••••••" minlength="8" required>
            </div>
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Confirm New Password</label>
                <input type="password" id="pwConfirm" class="filter-select" style="width: 100%; margin-top: 8px;" placeholder="••••••••" minlength="8" required>
            </div>
            <div id="pwError" style="font-size: 12px; font-weight: 600; color: #ef4444; display: none;"></div>
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 16px;">
                <button type="submit" id="btnUpdatePw" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700; background: var(--res-primary); border-color: var(--res-primary);">
                    Update Password
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    async function updateResidentPassword(e) {
        e.preventDefault();
        const currentPw = document.getElementById('pwCurrent').value;
        const newPw = document.getElementById('pwNew').value;
        const confirmPw = document.getElementById('pwConfirm').value;
        const err = document.getElementById('pwError');
        const btn = document.getElementById('btnUpdatePw');

        err.style.display = 'none';
        err.textContent = '';

        if (newPw !== confirmPw) {
            err.textContent = 'New passwords do not match.';
            err.style.display = 'block';
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Updating...';

        try {
            const res = await fetch('/resident/profile/password', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    current_password: currentPw,
                    password: newPw,
                    password_confirmation: confirmPw
                })
            });

            const data = await res.json();

            if (res.ok && data.success) {
                document.getElementById('passwordUpdateForm').reset();
                Swal.fire({
                    icon: 'success',
                    title: 'Password Updated',
                    text: 'Your account password has been updated securely.',
                    confirmButtonColor: '#059669'
                });
            } else {
                err.textContent = data.message || 'Unable to update password. Please check your current password.';
                err.style.display = 'block';
            }
        } catch(errObj) {
            console.error(errObj);
            err.textContent = 'A network error occurred. Please try again.';
            err.style.display = 'block';
        } finally {
            btn.disabled = false;
            btn.textContent = 'Update Password';
        }
    }
</script>

@endsection
