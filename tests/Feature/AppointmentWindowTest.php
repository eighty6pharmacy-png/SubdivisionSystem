<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AppointmentWindowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_same_day_slot_must_be_at_least_one_hour_ahead(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-13 10:20:00', 'Asia/Manila'));

        $this->assertSame(
            'That time slot has already passed. Please choose a later time.',
            appointmentBookingError('2026-10-13', '10:00 AM')
        );
        $this->assertSame(
            'Please choose a time at least 1 hour from now.',
            appointmentBookingError('2026-10-13', '11:00 AM')
        );
        $this->assertNull(appointmentBookingError('2026-10-13', '01:00 PM'));
        $this->assertNull(appointmentBookingError('2026-10-14', '09:00 AM'));
    }

    public function test_exactly_one_hour_ahead_is_still_open(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-13 10:00:00', 'Asia/Manila'));

        $this->assertNull(appointmentBookingError('2026-10-13', '11:00 AM'));
    }

    public function test_past_open_appointment_is_cancelled_and_emailed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-13 12:00:00', 'Asia/Manila'));
        Mail::shouldReceive('send')->once()->andReturnNull();

        $past = Appointment::create([
            'first_name' => 'Ana',
            'middle_name' => 'Reyes',
            'last_name' => 'Cruz',
            'contact_number' => '09171234567',
            'email' => 'ana@example.com',
            'type' => 'Site Visit',
            'date' => '2026-10-13',
            'time' => '11:00 AM',
            'status' => AppointmentStatus::Pending->value,
        ]);
        $later = Appointment::create([
            'first_name' => 'Ben',
            'middle_name' => 'Santos',
            'last_name' => 'Lopez',
            'contact_number' => '09181234567',
            'email' => 'ben@example.com',
            'type' => 'Site Visit',
            'date' => '2026-10-13',
            'time' => '02:00 PM',
            'status' => AppointmentStatus::Scheduled->value,
        ]);
        $finished = Appointment::create([
            'first_name' => 'Cara',
            'middle_name' => 'Diaz',
            'last_name' => 'Lim',
            'contact_number' => '09191234567',
            'email' => 'cara@example.com',
            'type' => 'Site Visit',
            'date' => '2026-10-13',
            'time' => '09:00 AM',
            'status' => AppointmentStatus::Completed->value,
        ]);

        expirePastAppointments();

        $this->assertSame(AppointmentStatus::Cancelled->value, $past->fresh()->status);
        $this->assertSame(AppointmentStatus::Scheduled->value, $later->fresh()->status);
        $this->assertSame(AppointmentStatus::Completed->value, $finished->fresh()->status);
    }
}
