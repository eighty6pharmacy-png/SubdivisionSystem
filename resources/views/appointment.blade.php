@extends('layouts.app')

@section('title', 'Book an Appointment - Althesa')

@section('content')
<section class="appointment-section">
    <div class="appointment-container">
        <div class="appointment-header">
            <span class="section-label">House & Lot Inquiries</span>
            <h1 class="section-title">Book an Appointment</h1>
            <p class="section-description" style="margin: 0 auto;">Schedule a visit to explore our available lots and properties. Open to everyone — no account required.</p>
        </div>

        <div class="appointment-form-card">
            <!-- Conflict Error Alert Box -->
            <div id="appointmentError" style="display: none; background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 14px 18px; border-radius: 12px; font-size: 14px; font-weight: 600; margin-bottom: 20px;">
                ⚠️ CONFLICT: An appointment is already scheduled on this time and day. Please select a different date or time slot.
            </div>

            <form id="appointmentForm" novalidate>
                <div class="form-row" style="display: flex; gap: 12px; flex-wrap: nowrap; margin-bottom: 16px;">
                    <div class="form-group" style="flex: 1; min-width: 0; margin-bottom: 0;">
                        <label class="form-label" for="firstName">First Name</label>
                        <input type="text" class="form-input" id="firstName" placeholder="e.g. Juan" required>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 0; margin-bottom: 0;">
                        <label class="form-label" for="middleName">Middle Name</label>
                        <input type="text" class="form-input" id="middleName" placeholder="e.g. Santos" required>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 0; margin-bottom: 0;">
                        <label class="form-label" for="lastName">Last Name</label>
                        <input type="text" class="form-input" id="lastName" placeholder="e.g. Dela Cruz" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" class="form-input" id="email" placeholder="you@email.com" required autocomplete="email">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contact">Contact / Mobile Number</label>
                        <input type="tel" class="form-input" id="contact" placeholder="09XXXXXXXXX or +639XXXXXXXXX" required autocomplete="tel" inputmode="tel">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label" for="date">Preferred Date</label>
                        <input type="text" class="form-input" id="date" required placeholder="Select a date..." style="background-color: #fff;">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label" for="time">Preferred Time</label>
                        <select class="form-input" id="time" required disabled>
                            <option value="">Select Date First</option>
                        </select>
                    </div>
                </div>
                <div class="form-row" style="margin-top: 16px;">
                    <div class="form-group" style="margin-top: 8px;">
                        <div class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}"></div>
                    </div>
                </div>

                <div class="form-submit-row">
                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; justify-content: center;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        Confirm & Submit Appointment
                    </button>
                </div>
            </form>

            {{-- Success Message (hidden by default) --}}
            <div id="appointmentSuccess" style="display: none; text-align: center; padding: 40px 0;">
                <div style="width: 64px; height: 64px; background: #dcfce7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <svg width="28" height="28" fill="none" stroke="#22c55e" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <h3 style="font-size: 20px; font-weight: 700; color: #111827; margin-bottom: 8px;">Appointment Confirmed!</h3>
                <p style="font-size: 14px; color: #6b7280; max-width: 360px; margin: 0 auto 24px;">Thank you for scheduling a visit. Your appointment has been recorded and submitted for admin review.</p>
                <a href="/" class="btn btn-outline">Back to Home</a>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="{{ asset('css/views/appointment.css') }}">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[A-Za-z]{2,}$/;
        const PH_MOBILE_PATTERN = /^(09\d{9}|\+639\d{9}|639\d{9})$/;
        const availableHours = [
            "09:00 AM", "10:00 AM", "11:00 AM", "01:00 PM",
            "02:00 PM", "03:00 PM", "04:00 PM", "05:00 PM"
        ];
        const submitBtnDefaultHtml = document.querySelector('#appointmentForm button[type="submit"]').innerHTML;

        function manilaNow() {
            return new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
        }

        function todayYmd() {
            const n = manilaNow();
            const y = n.getFullYear();
            const m = String(n.getMonth() + 1).padStart(2, '0');
            const d = String(n.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }

        function slotDateTime(dateRaw, hour) {
            const parts = hour.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
            if (!parts) return null;
            let h = parseInt(parts[1], 10);
            const min = parseInt(parts[2], 10);
            const mer = parts[3].toUpperCase();
            if (mer === 'PM' && h !== 12) h += 12;
            if (mer === 'AM' && h === 12) h = 0;
            return new Date(`${dateRaw}T${String(h).padStart(2, '0')}:${String(min).padStart(2, '0')}:00`);
        }

        function isPastDate(dateRaw) {
            return dateRaw < todayYmd();
        }

        function slotLeadMs(dateRaw, hour) {
            const slot = slotDateTime(dateRaw, hour);
            if (!slot) return -1;
            return slot.getTime() - manilaNow().getTime();
        }

        function normalizePhone(raw) {
            return String(raw || '').replace(/[\s\-().]/g, '');
        }

        function showFormError(title, text) {
            Swal.fire({ icon: 'error', title, text });
        }

        function showFormWarning(title, text) {
            Swal.fire({ icon: 'warning', title, text });
        }

        function listFields(fields) {
            if (fields.length === 1) return fields[0];
            if (fields.length === 2) return fields[0] + ' and ' + fields[1];
            return fields.slice(0, -1).join(', ') + ', and ' + fields[fields.length - 1];
        }

        function resetSubmitButton() {
            const submitBtn = document.getElementById('appointmentForm').querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = submitBtnDefaultHtml;
            }
            if (typeof grecaptcha !== 'undefined') {
                grecaptcha.reset();
            }
        }

        fetch('/api/appointments/availability')
            .then(res => res.json())
            .then(data => {
                const blackouts = data.unavailable_dates || [];
                const takenTimeslots = data.taken_timeslots || [];
                flatpickr("#date", {
                    minDate: "today",
                    dateFormat: "Y-m-d",
                    allowInput: false,
                    disableMobile: true,
                    onDayCreate: function(dObj, dStr, fp, dayElem) {
                        const dateRaw = fp.formatDate(dayElem.dateObj, "Y-m-d");
                        const formattedDate = dayElem.dateObj.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});

                        if(blackouts.includes(dateRaw) || blackouts.includes(formattedDate)) {
                            dayElem.style.backgroundColor = '#fee2e2';
                            dayElem.style.color = '#b91c1c';
                            dayElem.style.fontWeight = 'bold';
                            dayElem.title = 'Office Unavailable';
                        }
                    },
                    onChange: function(selectedDates, dateStr, instance) {
                        if (selectedDates.length === 0) {
                            resetTimeDropdown();
                            return;
                        }
                        const formattedDate = selectedDates[0].toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});
                        const dateRaw = instance.formatDate(selectedDates[0], "Y-m-d");

                        if (isPastDate(dateRaw)) {
                            Swal.fire("Please select today or a future date.");
                            instance.clear();
                            resetTimeDropdown();
                            return;
                        }

                        if (blackouts.includes(dateRaw) || blackouts.includes(formattedDate)) {
                            Swal.fire("This date is unavailable. Please select another date.");
                            instance.clear();
                            resetTimeDropdown();
                        } else {
                            populateTimeDropdown(dateRaw, formattedDate);
                        }
                    }
                });

                const timeSelect = document.getElementById('time');

                function resetTimeDropdown() {
                    timeSelect.innerHTML = '<option value="">Select Date First</option>';
                    timeSelect.disabled = true;
                }

                function isTakenSlot(dateRaw, hour) {
                    return takenTimeslots.some(apt =>
                        (String(apt.date).split('T')[0] === dateRaw) &&
                        apt.time === hour
                    );
                }

                function populateTimeDropdown(dateRaw, formattedDate) {
                    timeSelect.innerHTML = '<option value="">Select a time...</option>';
                    timeSelect.disabled = false;

                    availableHours.forEach(hour => {
                        const isTaken = isTakenSlot(dateRaw, hour);
                        const lead = slotLeadMs(dateRaw, hour);

                        const option = document.createElement('option');
                        option.value = hour;
                        if (isTaken) {
                            option.textContent = `${hour} (Taken)`;
                            option.disabled = true;
                            option.style.color = '#dc2626';
                            option.style.backgroundColor = '#fef2f2';
                        } else if (lead <= 0) {
                            option.textContent = `${hour} (Past)`;
                            option.disabled = true;
                            option.style.color = '#6b7280';
                            option.style.backgroundColor = '#f3f4f6';
                        } else if (lead < 60 * 60 * 1000) {
                            return;
                        } else {
                            option.textContent = hour;
                        }
                        timeSelect.appendChild(option);
                    });
                }

                document.getElementById('appointmentForm').addEventListener('submit', function(e) {
                    e.preventDefault();

                    const firstName = document.getElementById('firstName').value.trim();
                    const middleName = document.getElementById('middleName').value.trim();
                    const lastName = document.getElementById('lastName').value.trim();
                    const email = document.getElementById('email').value.trim();
                    const contactRaw = document.getElementById('contact').value.trim();
                    const contact = normalizePhone(contactRaw);
                    const selectedDate = document.getElementById('date').value;
                    const selectedTime = document.getElementById('time').value;
                    const formattedDate = selectedDate
                        ? new Date(selectedDate + 'T00:00:00').toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})
                        : '';

                    const missing = [];
                    if (!firstName) missing.push('First Name');
                    if (!middleName) missing.push('Middle Name');
                    if (!lastName) missing.push('Last Name');
                    if (!email) missing.push('Email Address');
                    if (!contactRaw) missing.push('Contact / Mobile Number');
                    if (!selectedDate) missing.push('Preferred Date');
                    if (!selectedTime) missing.push('Preferred Time');

                    if (missing.length) {
                        showFormWarning(
                            missing.length === 1 ? 'Missing ' + missing[0] : 'Missing required fields',
                            'Please fill in ' + listFields(missing) + '.'
                        );
                        return;
                    }

                    if (!EMAIL_PATTERN.test(email)) {
                        showFormError('Invalid email', 'Please enter a real email address (any domain is fine, e.g. you@gmail.com or you@company.com).');
                        return;
                    }

                    if (!PH_MOBILE_PATTERN.test(contact)) {
                        showFormError('Invalid mobile number', 'Use a Philippine mobile number: 09XXXXXXXXX or +639XXXXXXXXX.');
                        return;
                    }

                    if (isPastDate(selectedDate)) {
                        showFormError('Invalid date', 'Preferred date cannot be in the past. Please select today or a later date.');
                        return;
                    }

                    if (blackouts.includes(selectedDate) || blackouts.includes(formattedDate)) {
                        showFormError('Unavailable date', 'This date is blocked by the office. Please choose another day.');
                        return;
                    }

                    if (!selectedTime || !availableHours.includes(selectedTime)) {
                        showFormError('Invalid time', 'Please select an available time slot.');
                        return;
                    }

                    const lead = slotLeadMs(selectedDate, selectedTime);
                    if (lead <= 0) {
                        showFormError('Invalid time', 'That time slot has already passed. Please choose a later time.');
                        return;
                    }
                    if (lead < 60 * 60 * 1000) {
                        showFormError('Invalid time', 'Please choose a time at least 1 hour from now.');
                        return;
                    }

                    if (isTakenSlot(selectedDate, selectedTime)) {
                        showFormError('Time already taken', 'An appointment is already scheduled for this date and time.');
                        return;
                    }

                    const recaptchaResponse = grecaptcha.getResponse();
                    if (!recaptchaResponse) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Please check the Recaptcha!',
                            text: 'You must complete the recaptcha to submit this form.'
                        });
                        return;
                    }

                    const submitBtn = this.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = 'Submitting...';

                    fetch('/api/appointments', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            first_name: firstName,
                            middle_name: middleName,
                            last_name: lastName,
                            contact_number: contact,
                            email: email,
                            date: selectedDate,
                            time: selectedTime,
                            type: 'Office Appointment',
                            notes: '',
                            recaptcha_token: grecaptcha.getResponse()
                        })
                    }).then(async (res) => {
                        const payload = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            const message = payload.message
                                || (payload.errors && Object.values(payload.errors).flat()[0])
                                || 'Please check the form and try again.';
                            showFormError('Could not book appointment', message);
                            resetSubmitButton();
                            return;
                        }

                        fetch('/api/send-appointment-email', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                email: email,
                                name: firstName + ' ' + (middleName ? middleName + ' ' : '') + lastName,
                                date: selectedDate,
                                time: selectedTime,
                                type: 'Office Appointment',
                                notes: ''
                            })
                        }).catch(err => console.error("Email dispatch error:", err));

                        document.getElementById('appointmentForm').style.display = 'none';
                        document.getElementById('appointmentSuccess').style.display = 'block';
                    }).catch(err => {
                        console.error("Database save error:", err);
                        showFormError('Could not book appointment', 'A network error occurred. Please try again.');
                        resetSubmitButton();
                    });
                });
            })
            .catch(err => console.error('Failed to load availability', err));
    });
</script>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endsection
