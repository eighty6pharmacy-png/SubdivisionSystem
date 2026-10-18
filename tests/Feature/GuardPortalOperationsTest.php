<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\GuardAccount;
use App\Models\Lot;
use App\Models\User;
use App\Models\Visitor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuardPortalOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Admin']);
        Role::firstOrCreate(['name' => 'Security Guard']);
        Role::firstOrCreate(['name' => 'Resident']);
        Role::firstOrCreate(['name' => 'Buyer']);
    }

    public function test_guard_and_admin_can_access_guard_portal_while_others_are_blocked(): void
    {
        $guard = User::factory()->create(['name' => 'Officer Reyes']);
        $guard->assignRole('Security Guard');

        $admin = User::factory()->create(['name' => 'Admin Boss']);
        $admin->assignRole('Admin');

        $resident = User::factory()->create();
        $resident->assignRole('Resident');

        // Guest is redirected to login
        $this->get('/guard/dashboard')->assertRedirect('/login');
        $this->get('/guard/history')->assertRedirect('/login');

        // Resident is forbidden
        $this->actingAs($resident)->get('/guard/dashboard')->assertForbidden();
        $this->actingAs($resident)->get('/guard/history')->assertForbidden();

        // Guard can access
        $this->actingAs($guard)->get('/guard/dashboard')->assertOk()->assertSee('Officer Reyes');
        $this->actingAs($guard)->get('/guard/history')->assertOk();

        // Admin can supervise guard portal
        $this->actingAs($admin)->get('/guard/dashboard')->assertOk()->assertSee('Admin Boss');
        $this->actingAs($admin)->get('/guard/history')->assertOk();
    }

    public function test_visitor_pin_validation_and_idempotent_entry(): void
    {
        $guard = User::factory()->create(['name' => 'Officer Santos']);
        $guard->assignRole('Security Guard');

        $resident = User::factory()->create(['name' => 'Dr. Jose Rizal']);
        $resident->assignRole('Resident');

        $lot = Lot::create([
            'block' => 5,
            'lot_number' => 12,
            'status' => 'Occupied',
        ]);
        $resident->lots()->attach($lot->id);

        $visitor = Visitor::create([
            'resident_id' => $resident->id,
            'host_name' => $resident->name,
            'visitor_name' => 'Emilio Aguinaldo',
            'purpose' => 'Family Visit',
            'validity' => 'Today',
            'type' => 'Pre-registered',
            'status' => 'Approved',
            'pin' => '654321',
            'plate_number' => 'ABC 9999',
        ]);

        // 1. Validate PIN
        $valRes = $this->actingAs($guard)->postJson('/api/validate-pin', [
            'pin' => '654321',
            'source' => 'guard',
        ]);

        $valRes->assertOk();
        $valRes->assertJson([
            'success' => true,
            'visitor_name' => 'Emilio Aguinaldo',
            'host_name' => 'Dr. Jose Rizal',
            'block' => '5',
            'lot' => '12',
            'destination' => 'Block 5, Lot 12',
            'status' => 'Approved',
        ]);

        // 2. Mark entered
        $enterRes = $this->actingAs($guard)->putJson("/api/visitors/{$visitor->id}/enter", [
            'plate_number' => 'ABC 9999',
        ]);

        $enterRes->assertOk();
        $visitor->refresh();
        $this->assertSame('Entered', $visitor->status);
        $firstArrival = $visitor->arrival_time;
        $this->assertNotNull($firstArrival);
        $this->assertSame($guard->id, $visitor->guard_id);

        // 3. Re-entry simulation (e.g. guard re-scans 2 hours later or updates plate)
        // Ensure arrival timestamp is NOT overwritten!
        Carbon::setTestNow(now()->addHours(2));

        $updatePlateRes = $this->actingAs($guard)->putJson("/api/visitors/{$visitor->id}/enter", [
            'plate_number' => 'XYZ 8888',
        ]);

        $updatePlateRes->assertOk();
        $visitor->refresh();
        $this->assertSame('XYZ 8888', $visitor->plate_number);
        $this->assertSame($firstArrival, $visitor->arrival_time, 'Original arrival time was preserved');
        Carbon::setTestNow();
    }

    public function test_appointment_pin_validation_and_plate_update_flow(): void
    {
        $guard = User::factory()->create();
        $guard->assignRole('Security Guard');

        $apt = Appointment::create([
            'first_name' => 'Maria',
            'last_name' => 'Clara',
            'contact_number' => '09170001111',
            'email' => 'maria@example.com',
            'type' => 'Lot Viewing',
            'date' => now()->toDateString(),
            'time' => '02:00 PM',
            'status' => 'Scheduled',
            'pin' => '789123',
        ]);

        // 1. Guard validates appointment PIN
        $valRes = $this->actingAs($guard)->postJson('/api/validate-pin', [
            'pin' => '789123',
            'source' => 'guard',
        ]);

        $valRes->assertOk();
        $valRes->assertJson([
            'success' => true,
            'is_appointment' => true,
            'visitor_name' => 'Maria Clara',
            'destination' => 'Management Office',
        ]);

        // 2. Complete appointment at gate
        $statusRes = $this->actingAs($guard)->putJson("/api/appointments/{$apt->id}/status", [
            'status' => 'Completed',
            'report' => 'Arrived at Gate',
        ]);

        $statusRes->assertOk();
        $visitorId = $statusRes->json('visitor_id');
        $this->assertNotNull($visitorId, 'visitor_id returned from appointment status completion');

        $createdVis = Visitor::find($visitorId);
        $this->assertNotNull($createdVis);
        $this->assertSame('Maria Clara', $createdVis->visitor_name);
        $this->assertSame('Appointment', $createdVis->type);
        $this->assertSame('Entered', $createdVis->status);

        // 3. Guard updates vehicle plate number using returned visitor_id
        $plateRes = $this->actingAs($guard)->putJson("/api/visitors/{$visitorId}/enter", [
            'plate_number' => 'NDR 4567',
        ]);

        $plateRes->assertOk();
        $createdVis->refresh();
        $this->assertSame('NDR 4567', $createdVis->plate_number);
    }

    public function test_walk_in_registration_records_destination_and_enters_immediately(): void
    {
        $guard = User::factory()->create(['name' => 'Officer Luna']);
        $guard->assignRole('Security Guard');

        $response = $this->actingAs($guard)->postJson('/api/visitors', [
            'visitor_name' => 'Courier Express',
            'host_name' => 'Block 4 Lot 8 / Juan Dela Cruz',
            'purpose' => 'Package Delivery',
            'type' => 'Walk-in',
            'validity' => 'Today',
            'visitor_address' => 'Naga City',
            'plate_number' => 'DEL 1010',
        ]);

        $response->assertOk();
        $visId = $response->json('id');
        $this->assertNotNull($visId);

        $walkin = Visitor::find($visId);
        $this->assertNotNull($walkin);
        $this->assertSame('Entered', $walkin->status);
        $this->assertSame('Courier Express', $walkin->visitor_name);
        $this->assertSame('Block 4 Lot 8 / Juan Dela Cruz', $walkin->host_name);
        $this->assertSame('Officer Luna', $walkin->guard_name);
        $this->assertNotNull($walkin->arrival_time);
    }
}
