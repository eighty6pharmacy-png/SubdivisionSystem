@extends('layouts.app')

@section('title', 'Visitor PIN Verification | Althesa Residences')

@section('content')
<section style="min-height: 80vh; display: flex; align-items: center; justify-content: center; background: #f8fafc; position: relative; overflow: hidden;">
    <!-- Background Pattern -->
    <div style="position: absolute; inset: 0; background-image: radial-gradient(rgba(0,0,0,0.05) 1px, transparent 1px); background-size: 30px 30px;"></div>

    <div style="position: relative; z-index: 2; text-align: center; width: 100%; max-width: 480px; padding: 0 24px;">
        <!-- Lock Icon -->
        <div style="width: 80px; height: 80px; background: white; border: 1px solid #e2e8f0; border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 32px; font-size: 32px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            🔐
        </div>

        <h1 style="font-size: 32px; font-weight: 800; color: #0f172a; margin-bottom: 12px; letter-spacing: -0.5px;">Visitor Verification</h1>
        <p style="color: #64748b; font-size: 15px; line-height: 1.6; margin-bottom: 40px;">
            Enter the 6-digit PIN provided by your host resident to access the subdivision routing guide.
        </p>

        <!-- PIN Input Card -->
        <div style="background: white; padding: 40px 32px; border-radius: 24px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
            <label style="color: #64748b; font-size: 10px; text-transform: uppercase; letter-spacing: 2px; font-weight: 800; margin-bottom: 20px; display: block;">Enter Access Code</label>
            
            <div style="display: flex; gap: 8px; justify-content: center; margin-bottom: 24px;" id="pinBoxes">
                <input type="text" maxlength="1" class="pin-digit" data-index="0" autofocus
                    style="width: 52px; height: 64px; background: #f1f5f9; border: 2px solid #cbd5e1; color: #0f172a; font-size: 24px; font-weight: 800; text-align: center; border-radius: 12px; outline: none; transition: all 0.2s;">
                <input type="text" maxlength="1" class="pin-digit" data-index="1"
                    style="width: 52px; height: 64px; background: #f1f5f9; border: 2px solid #cbd5e1; color: #0f172a; font-size: 24px; font-weight: 800; text-align: center; border-radius: 12px; outline: none; transition: all 0.2s;">
                <input type="text" maxlength="1" class="pin-digit" data-index="2"
                    style="width: 52px; height: 64px; background: #f1f5f9; border: 2px solid #cbd5e1; color: #0f172a; font-size: 24px; font-weight: 800; text-align: center; border-radius: 12px; outline: none; transition: all 0.2s;">
                <input type="text" maxlength="1" class="pin-digit" data-index="3"
                    style="width: 52px; height: 64px; background: #f1f5f9; border: 2px solid #cbd5e1; color: #0f172a; font-size: 24px; font-weight: 800; text-align: center; border-radius: 12px; outline: none; transition: all 0.2s;">
                <input type="text" maxlength="1" class="pin-digit" data-index="4"
                    style="width: 52px; height: 64px; background: #f1f5f9; border: 2px solid #cbd5e1; color: #0f172a; font-size: 24px; font-weight: 800; text-align: center; border-radius: 12px; outline: none; transition: all 0.2s;">
                <input type="text" maxlength="1" class="pin-digit" data-index="5"
                    style="width: 52px; height: 64px; background: #f1f5f9; border: 2px solid #cbd5e1; color: #0f172a; font-size: 24px; font-weight: 800; text-align: center; border-radius: 12px; outline: none; transition: all 0.2s;">
            </div>

            <p id="pinError" style="color: #ef4444; font-size: 13px; font-weight: 600; margin-bottom: 16px; display: none;">❌ Invalid PIN. Please check with your host or the guard.</p>
            <p id="pinSuccess" style="color: #10b981; font-size: 13px; font-weight: 600; margin-bottom: 16px; display: none;">✅ PIN Verified! Redirecting to routing guide...</p>

            <button onclick="verifyPin()" class="btn btn-primary" id="verifyBtn"
                style="width: 100%; padding: 16px; font-size: 15px; font-weight: 800; border-radius: 14px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                🔓 Verify & Navigate
            </button>
        </div>

        <p style="color: #64748b; font-size: 12px; margin-top: 24px; line-height: 1.5;">
            Don't have a PIN? Ask the resident you're visiting to generate one through their portal, or contact the guard house.
        </p>
    </div>
</section>
@endsection

@section('scripts')
<script>
    const digits = document.querySelectorAll('.pin-digit');
    
    // Auto-focus next input on entry
    digits.forEach((input, idx) => {
        input.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value && idx < 5) {
                digits[idx + 1].focus();
            }
            // Auto-verify when all 6 digits are entered
            if (idx === 5 && this.value) {
                verifyPin();
            }
        });
        
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !this.value && idx > 0) {
                digits[idx - 1].focus();
            }
        });

        input.addEventListener('focus', function() {
            this.style.borderColor = '#2563eb';
            this.style.boxShadow = '0 0 0 3px rgba(37, 99, 235, 0.15)';
        });

        input.addEventListener('blur', function() {
            this.style.borderColor = '#cbd5e1';
            this.style.boxShadow = 'none';
        });
    });

    function verifyPin() {
        const pin = Array.from(digits).map(d => d.value).join('');
        const error = document.getElementById('pinError');
        const success = document.getElementById('pinSuccess');

        if (pin.length < 6) {
            error.textContent = '⚠️ Please enter all 6 digits.';
            error.style.display = 'block';
            success.style.display = 'none';
            return;
        }

        // Valid PINs from web.php simulation data
        const validPins = ['111111'];

        if (validPins.includes(pin)) {
            error.style.display = 'none';
            success.style.display = 'block';
            digits.forEach(d => { d.style.borderColor = '#10b981'; d.disabled = true; });
            document.getElementById('verifyBtn').disabled = true;
            document.getElementById('verifyBtn').textContent = 'Redirecting...';

            setTimeout(() => {
                window.location.href = '/routing-guide';
            }, 1500);
        } else {
            error.textContent = '❌ Invalid PIN. Please check with your host or the guard.';
            error.style.display = 'block';
            success.style.display = 'none';
            digits.forEach(d => { d.style.borderColor = '#ef4444'; d.value = ''; });
            setTimeout(() => {
                digits.forEach(d => { d.style.borderColor = '#cbd5e1'; });
                error.style.display = 'none';
                digits[0].focus();
            }, 2500);
        }
    }
</script>
@endsection
