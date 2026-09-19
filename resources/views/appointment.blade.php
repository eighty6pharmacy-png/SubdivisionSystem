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

            <form id="appointmentForm" onsubmit="handleAppointmentSubmit(event)">
                <div class="form-group">
                    <label class="form-label" for="fullName">Full Name</label>
                    <input type="text" class="form-input" id="fullName" placeholder="e.g. Juan Dela Cruz" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" class="form-input" id="email" placeholder="you@email.com" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contact">Contact / Mobile Number</label>
                        <input type="tel" class="form-input" id="contact" placeholder="+63 9XX XXX XXXX" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="inquiryType">Inquiry Type</label>
                    <select class="form-input" id="inquiryType" required>
                        <option value="Site Visit / Lot Viewing" selected>Site Visit / Lot Viewing</option>
                        <option value="Architectural Consultation">Architectural Consultation</option>
                        <option value="Price Negotiation">Price Negotiation</option>
                        <option value="General Inquiry">General Inquiry</option>
                    </select>
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
        fetch('/api/appointments/availability')
            .then(res => res.json())
            .then(data => {
                const blackouts = data.unavailable_dates || [];
                const takenTimeslots = data.taken_timeslots || [];

                flatpickr("#date", {
                    minDate: "today",
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
                        
                        if (blackouts.includes(dateRaw) || blackouts.includes(formattedDate)) {
                            alert("This date is unavailable. Please select another date.");
                            instance.clear();
                            resetTimeDropdown();
                        } else {
                            populateTimeDropdown(dateRaw, formattedDate);
                        }
                    }
                });

                const timeSelect = document.getElementById('time');
                const availableHours = [
                    "09:00 AM", "10:00 AM", "11:00 AM", "01:00 PM", 
                    "02:00 PM", "03:00 PM", "04:00 PM", "05:00 PM"
                ];

                function resetTimeDropdown() {
                    timeSelect.innerHTML = '<option value="">Select Date First</option>';
                    timeSelect.disabled = true;
                }

                function populateTimeDropdown(dateRaw, formattedDate) {
                    timeSelect.innerHTML = '<option value="">Select a time...</option>';
                    timeSelect.disabled = false;

                    availableHours.forEach(hour => {
                        const isTaken = takenTimeslots.some(apt => 
                            (apt.date.split('T')[0] === dateRaw) && 
                            apt.time === hour
                        );

                        const option = document.createElement('option');
                        option.value = hour;
                        if (isTaken) {
                            option.textContent = `${hour} (Taken)`;
                            option.disabled = true;
                            option.style.color = '#dc2626';
                            option.style.backgroundColor = '#fef2f2';
                        } else {
                            option.textContent = hour;
                        }
                        timeSelect.appendChild(option);
                    });
                }
                
                document.getElementById('appointmentForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const fullName = document.getElementById('fullName').value;
                    const email = document.getElementById('email').value;
                    const contact = document.getElementById('contact').value;
                    const inquiryType = document.getElementById('inquiryType').value;
                    const selectedDate = document.getElementById('date').value;
                    const selectedTime = document.getElementById('time').value;

                    // Save to PostgreSQL backend
                    fetch('/api/appointments', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            client_name: fullName,
                            contact_number: contact,
                            email: email,
                            date: selectedDate,
                            time: selectedTime,
                            type: inquiryType,
                            notes: ''
                        })
                    }).then(() => {
                        // Send notification email
                        fetch('/api/send-appointment-email', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                email: email,
                                name: fullName,
                                date: selectedDate,
                                time: selectedTime,
                                type: inquiryType,
                                notes: ''
                            })
                        }).catch(err => console.error("Email dispatch error:", err));

                        document.getElementById('appointmentForm').style.display = 'none';
                        document.getElementById('appointmentSuccess').style.display = 'block';
                    }).catch(err => console.error("Database save error:", err));
                });
            })
            .catch(err => console.error('Failed to load availability', err));
    });
</script>
@endsection
