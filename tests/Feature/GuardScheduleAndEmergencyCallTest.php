<?php

namespace Tests\Feature;

use App\Models\GuardAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuardScheduleAndEmergencyCallTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Admin']);
        Role::firstOrCreate(['name' => 'Security Guard']);
        Role::firstOrCreate(['name' => 'Resident']);
    }

    public function test_guard_account_calculates_on_duty_correctly_for_day_shift(): void
    {
        $guardUser = User::factory()->create([
            'name' => 'Officer Reyes',
            'contact_number' => '09171234567',
        ]);
        $guardUser->assignRole('Security Guard');

        $guardAccount = GuardAccount::updateOrCreate(
            ['user_id' => $guardUser->id],
            [
                'schedule_days' => ['Monday', 'Wednesday', 'Friday'],
                'shift_type' => 'day',
                'shift_start' => '06:00:00',
                'shift_end' => '18:00:00',
            ]
        );

        // Test on Friday at 10:00 AM (Should be on duty)
        $fridayMorning = Carbon::parse('2026-10-16 10:00:00', 'Asia/Manila');
        $this->assertTrue($guardAccount->isOnDuty($fridayMorning));

        // Test on Friday at 7:00 PM (Should be off duty - shift ended at 18:00)
        $fridayNight = Carbon::parse('2026-10-16 19:00:00', 'Asia/Manila');
        $this->assertFalse($guardAccount->isOnDuty($fridayNight));

        // Test on Saturday at 10:00 AM (Should be off duty - not scheduled on Saturday)
        $saturdayMorning = Carbon::parse('2026-10-17 10:00:00', 'Asia/Manila');
        $this->assertFalse($guardAccount->isOnDuty($saturdayMorning));
    }

    public function test_guard_account_handles_overnight_cross_midnight_shifts(): void
    {
        $guardUser = User::factory()->create([
            'name' => 'Night Guard Santos',
            'contact_number' => '09281234567',
        ]);
        $guardUser->assignRole('Security Guard');

        $guardAccount = GuardAccount::updateOrCreate(
            ['user_id' => $guardUser->id],
            [
                'schedule_days' => ['Friday'],
                'shift_type' => 'night',
                'shift_start' => '18:00:00',
                'shift_end' => '06:00:00',
            ]
        );

        // Friday 22:00 (Evening portion of Friday night shift - Should be ON DUTY)
        $fridayEvening = Carbon::parse('2026-10-16 22:00:00', 'Asia/Manila');
        $this->assertTrue($guardAccount->isOnDuty($fridayEvening));

        // Saturday 03:00 AM (Early morning rollover portion of Friday night shift - Should be ON DUTY)
        $saturdayEarly = Carbon::parse('2026-10-17 03:00:00', 'Asia/Manila');
        $this->assertTrue($guardAccount->isOnDuty($saturdayEarly));

        // Saturday 08:00 AM (After night shift ends at 06:00 - Should be OFF DUTY)
        $saturdayDay = Carbon::parse('2026-10-17 08:00:00', 'Asia/Manila');
        $this->assertFalse($guardAccount->isOnDuty($saturdayDay));
    }

    public function test_get_on_duty_guard_emergency_contact_resolves_active_guard(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Boss',
            'contact_number' => '09990001111',
        ]);
        $admin->assignRole('Admin');

        $guardUser = User::factory()->create([
            'name' => 'Officer Dela Cruz',
            'contact_number' => '09187654321',
        ]);
        $guardUser->assignRole('Security Guard');

        GuardAccount::updateOrCreate(
            ['user_id' => $guardUser->id],
            [
                'schedule_days' => ['Friday'],
                'shift_type' => 'day',
                'shift_start' => '06:00:00',
                'shift_end' => '18:00:00',
            ]
        );

        // When Friday 14:00 (2:00 PM), guard is on duty
        $fridayAfternoon = Carbon::parse('2026-10-16 14:00:00', 'Asia/Manila');
        $contact = getOnDutyGuardEmergencyContact($fridayAfternoon);

        $this->assertTrue($contact['is_guard']);
        $this->assertSame('Officer Dela Cruz', $contact['name']);
        $this->assertSame('09187654321', $contact['phone']);
    }

    public function test_get_on_duty_guard_emergency_contact_falls_back_to_admin_when_no_guards(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Boss',
            'contact_number' => '09990001111',
        ]);
        $admin->assignRole('Admin');

        $contact = getOnDutyGuardEmergencyContact();
        $this->assertFalse($contact['is_guard']);
        $this->assertSame('09990001111', $contact['phone']);
    }

    public function test_admin_can_create_guard_with_schedule_via_api(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->postJson('/admin/users', [
            'first_name' => 'Ramon',
            'last_name' => 'Magsaysay',
            'email' => 'ramon.guard@gmail.com',
            'contact_number' => '09175551234',
            'role' => 'Security Guard',
            'status' => 'Active',
            'password' => 'Secret123!',
            'schedule_days' => ['Monday', 'Tuesday', 'Wednesday'],
            'shift_type' => 'night',
            'shift_start' => '18:00:00',
            'shift_end' => '06:00:00',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $user = User::where('email', 'ramon.guard@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Security Guard'));

        $guardAccount = $user->guardAccount;
        $this->assertNotNull($guardAccount);
        $this->assertSame(['Monday', 'Tuesday', 'Wednesday'], $guardAccount->schedule_days);
        $this->assertSame('night', $guardAccount->shift_type);
    }

    public function test_two_guards_alternating_day_and_night_shifts_dynamically_swap_duty(): void
    {
        // Guard 1: Mon-Wed Day (06:00-18:00), Thu-Sat Night (18:00-06:00)
        $guard1 = User::factory()->create([
            'name' => 'Officer Alpha',
            'contact_number' => '09171111111',
        ]);
        $guard1->assignRole('Security Guard');
        GuardAccount::updateOrCreate(
            ['user_id' => $guard1->id],
            [
                'schedule_days' => [
                    'Monday' => 'day',
                    'Tuesday' => 'day',
                    'Wednesday' => 'day',
                    'Thursday' => 'night',
                    'Friday' => 'night',
                    'Saturday' => 'night',
                    'Sunday' => 'off',
                ],
            ]
        );

        // Guard 2: Mon-Wed Night (18:00-06:00), Thu-Sat Day (06:00-18:00)
        $guard2 = User::factory()->create([
            'name' => 'Officer Bravo',
            'contact_number' => '09172222222',
        ]);
        $guard2->assignRole('Security Guard');
        GuardAccount::updateOrCreate(
            ['user_id' => $guard2->id],
            [
                'schedule_days' => [
                    'Monday' => 'night',
                    'Tuesday' => 'night',
                    'Wednesday' => 'night',
                    'Thursday' => 'day',
                    'Friday' => 'day',
                    'Saturday' => 'day',
                    'Sunday' => 'off',
                ],
            ]
        );

        // 1. Tuesday 10:00 AM (Daytime): Guard 1 is Day Shift, Guard 2 is off
        $tuesdayDay = Carbon::parse('2026-10-13 10:00:00', 'Asia/Manila');
        $this->assertTrue($guard1->guardAccount->isOnDuty($tuesdayDay));
        $this->assertFalse($guard2->guardAccount->isOnDuty($tuesdayDay));
        $contact = getOnDutyGuardEmergencyContact($tuesdayDay);
        $this->assertSame('Officer Alpha', $contact['name']);
        $this->assertSame('09171111111', $contact['phone']);
        $this->assertSame('Security Guard on Duty (Day Shift)', $contact['role_label']);

        // 2. Tuesday 20:00 PM (Nighttime): Guard 2 is Night Shift, Guard 1 is off
        $tuesdayNight = Carbon::parse('2026-10-13 20:00:00', 'Asia/Manila');
        $this->assertFalse($guard1->guardAccount->isOnDuty($tuesdayNight));
        $this->assertTrue($guard2->guardAccount->isOnDuty($tuesdayNight));
        $contact = getOnDutyGuardEmergencyContact($tuesdayNight);
        $this->assertSame('Officer Bravo', $contact['name']);
        $this->assertSame('09172222222', $contact['phone']);
        $this->assertSame('Security Guard on Duty (Night Shift)', $contact['role_label']);

        // 3. Friday 10:00 AM (Daytime): Guard 2 is Day Shift, Guard 1 is off
        $fridayDay = Carbon::parse('2026-10-16 10:00:00', 'Asia/Manila');
        $this->assertFalse($guard1->guardAccount->isOnDuty($fridayDay));
        $this->assertTrue($guard2->guardAccount->isOnDuty($fridayDay));
        $contact = getOnDutyGuardEmergencyContact($fridayDay);
        $this->assertSame('Officer Bravo', $contact['name']);
        $this->assertSame('09172222222', $contact['phone']);
        $this->assertSame('Security Guard on Duty (Day Shift)', $contact['role_label']);

        // 4. Friday 20:00 PM (Nighttime): Guard 1 is Night Shift, Guard 2 is off
        $fridayNight = Carbon::parse('2026-10-16 20:00:00', 'Asia/Manila');
        $this->assertTrue($guard1->guardAccount->isOnDuty($fridayNight));
        $this->assertFalse($guard2->guardAccount->isOnDuty($fridayNight));
        $contact = getOnDutyGuardEmergencyContact($fridayNight);
        $this->assertSame('Officer Alpha', $contact['name']);
        $this->assertSame('09171111111', $contact['phone']);
        $this->assertSame('Security Guard on Duty (Night Shift)', $contact['role_label']);
    }

    public function test_admin_can_save_alternating_weekly_schedule_via_api(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $alternatingSchedule = [
            'Monday' => 'day',
            'Tuesday' => 'day',
            'Wednesday' => 'day',
            'Thursday' => 'night',
            'Friday' => 'night',
            'Saturday' => 'night',
            'Sunday' => 'off',
        ];

        $response = $this->actingAs($admin)->postJson('/admin/users', [
            'first_name' => 'Mateo',
            'last_name' => 'Guard',
            'email' => 'mateo.guard@gmail.com',
            'contact_number' => '09183334444',
            'role' => 'Security Guard',
            'status' => 'Active',
            'password' => 'Secret123!',
            'schedule_days' => $alternatingSchedule,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $user = User::where('email', 'mateo.guard@gmail.com')->first();
        $this->assertNotNull($user);
        $guardAccount = $user->guardAccount;
        $this->assertNotNull($guardAccount);
        $this->assertSame($alternatingSchedule, $guardAccount->schedule_days);
        $this->assertSame('day', $guardAccount->getShiftForDay('Monday')['shift']);
        $this->assertSame('night', $guardAccount->getShiftForDay('Friday')['shift']);
        $this->assertNull($guardAccount->getShiftForDay('Sunday'));

        // Test updating schedule via PUT
        $updatedSchedule = [
            'Monday' => 'night',
            'Tuesday' => 'night',
            'Wednesday' => 'off',
            'Thursday' => 'day',
            'Friday' => 'day',
            'Saturday' => 'off',
            'Sunday' => 'off',
        ];

        $updateResponse = $this->actingAs($admin)->putJson("/admin/users/{$user->id}", [
            'first_name' => 'Mateo',
            'last_name' => 'Guard',
            'email' => 'mateo.guard@gmail.com',
            'contact_number' => '09183334444',
            'role' => 'Security Guard',
            'schedule_days' => $updatedSchedule,
        ]);

        $updateResponse->assertOk();
        $guardAccount->refresh();
        $this->assertSame($updatedSchedule, $guardAccount->schedule_days);
        $this->assertSame('night', $guardAccount->getShiftForDay('Monday')['shift']);
        $this->assertSame('day', $guardAccount->getShiftForDay('Thursday')['shift']);
    }

    public function test_resident_incidents_view_renders_on_duty_guard_button(): void
    {
        $guard = User::factory()->create([
            'name' => 'Gate Guard Bert',
            'contact_number' => '09179998888',
        ]);
        $guard->assignRole('Security Guard');
        GuardAccount::updateOrCreate(
            ['user_id' => $guard->id],
            [
                'schedule_days' => [now('Asia/Manila')->format('l')],
                'shift_type' => '24_hours',
                'shift_start' => '00:00:00',
                'shift_end' => '23:59:00',
            ]
        );

        $resident = User::factory()->create();
        $resident->assignRole('Resident');

        $response = $this->actingAs($resident)->get('/resident/incidents');
        $response->assertOk();
        $response->assertSee('Gate Guard Bert');
        $response->assertSee('09179998888');
        $response->assertSee('tel:09179998888', false);
    }

    public function test_guard_per_day_custom_shift_times_and_formatted_schedule(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $perDaySchedule = [
            'Monday' => [
                'shift' => 'day',
                'start' => '06:00:00',
                'end' => '18:00:00',
            ],
            'Thursday' => [
                'shift' => 'night',
                'start' => '18:00:00',
                'end' => '06:00:00',
            ],
        ];

        $response = $this->actingAs($admin)->postJson('/admin/users', [
            'first_name' => 'Carlo',
            'last_name' => 'Diaz',
            'email' => 'carlo.guard@gmail.com',
            'contact_number' => '09174445555',
            'role' => 'Security Guard',
            'status' => 'Active',
            'password' => 'Secret123!',
            'schedule_days' => $perDaySchedule,
            'shift_type' => 'day',
            'shift_start' => '06:00:00',
            'shift_end' => '18:00:00',
        ]);

        $response->assertOk();
        $user = User::where('email', 'carlo.guard@gmail.com')->first();
        $this->assertNotNull($user);

        $guardAccount = $user->guardAccount;
        $this->assertNotNull($guardAccount);

        // Monday at 10:00 AM -> ON DUTY (Day Shift)
        $mondayDay = Carbon::parse('2026-10-12 10:00:00', 'Asia/Manila');
        $this->assertTrue($guardAccount->isOnDuty($mondayDay));

        // Monday at 8:00 PM -> OFF DUTY
        $mondayNight = Carbon::parse('2026-10-12 20:00:00', 'Asia/Manila');
        $this->assertFalse($guardAccount->isOnDuty($mondayNight));

        // Thursday at 10:00 AM -> OFF DUTY (Scheduled for Night Shift on Thursday)
        $thursdayDay = Carbon::parse('2026-10-15 10:00:00', 'Asia/Manila');
        $this->assertFalse($guardAccount->isOnDuty($thursdayDay));

        // Thursday at 8:00 PM -> ON DUTY (Night Shift)
        $thursdayNight = Carbon::parse('2026-10-15 20:00:00', 'Asia/Manila');
        $this->assertTrue($guardAccount->isOnDuty($thursdayNight));

        // Formatted schedule reflects both Monday and Thursday shift times
        $this->assertSame(
            'Mon: Day (06:00 AM - 06:00 PM) • Thu: Night (06:00 PM - 06:00 AM)',
            $guardAccount->formatted_schedule
        );
    }
}
