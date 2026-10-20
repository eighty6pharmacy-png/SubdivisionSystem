<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Visitor;
use App\Models\Lot;
use App\Models\UserLot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VisitorPassWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles if Spatie is used
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Resident', 'guard_name' => 'web']);
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Security Guard', 'guard_name' => 'web']);
        }
    }

    public function test_resident_can_create_visitor_request_with_optional_contact_number(): void
    {
        $resident = User::factory()->create(['name' => 'Maria Santos']);
        $resident->assignRole('Resident');

        $response = $this->actingAs($resident)->postJson('/api/visitors', [
            'visitor_name' => 'Juan Dela Cruz',
            'contact_number' => '09171234567',
            'purpose' => 'Family Visit',
            'validity' => 'Today',
            'type' => 'Pre-registered',
            'plate_number' => 'ABC-1234',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('visitors', [
            'visitor_name' => 'Juan Dela Cruz',
            'contact_number' => '09171234567',
            'status' => 'Pending',
        ]);

        $visitor = Visitor::where('visitor_name', 'Juan Dela Cruz')->first();
        $this->assertNotNull($visitor->access_token);
        $this->assertSame(32, strlen($visitor->access_token));
    }

    public function test_admin_approval_sends_sms_and_generates_pin(): void
    {
        Http::fake([
            'api.textbee.dev/*' => Http::response(['status' => 'success'], 200),
        ]);

        $admin = User::factory()->create(['name' => 'Admin User']);
        $admin->assignRole('Admin');

        $resident = User::factory()->create([
            'name' => 'Maria Santos',
            'contact_number' => '09181112222',
        ]);
        $resident->assignRole('Resident');

        $visitor = Visitor::create([
            'resident_id' => $resident->id,
            'host_name' => $resident->name,
            'visitor_name' => 'Mark Driver',
            'contact_number' => '09193334444',
            'purpose' => 'Delivery',
            'validity' => 'Today',
            'status' => 'Pending',
            'type' => 'Pre-registered',
            'access_token' => 'sample_token_12345678901234567890',
        ]);

        $response = $this->actingAs($admin)->putJson("/api/visitors/{$visitor->id}/approve");
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Exactly ONE SMS should be sent to TextBee directly for the visitor (no duplicates)
        $textBeeRequests = collect(\Illuminate\Support\Facades\Http::recorded())->filter(function ($pair) {
            return str_contains($pair[0]->url(), 'api.textbee.dev');
        });
        $this->assertCount(1, $textBeeRequests);
        $this->assertTrue(in_array('09193334444', $textBeeRequests->first()[0]['recipients']));

        $visitor->refresh();
        $this->assertSame('Approved', $visitor->status);
        $this->assertNotNull($visitor->pin);
        $this->assertSame(6, strlen($visitor->pin));
    }

    public function test_admin_approval_sends_single_sms_to_resident_when_visitor_contact_is_empty(): void
    {
        Http::fake([
            'api.textbee.dev/*' => Http::response(['status' => 'success'], 200),
            'onesignal.com/*' => Http::response(['id' => 'fake_onesignal_id'], 200),
        ]);

        $admin = User::factory()->create(['name' => 'Admin User']);
        $admin->assignRole('Admin');

        $resident = User::factory()->create([
            'name' => 'Maria Santos',
            'contact_number' => '09181112222',
        ]);
        $resident->assignRole('Resident');

        $visitor = Visitor::create([
            'resident_id' => $resident->id,
            'host_name' => $resident->name,
            'visitor_name' => 'John Guest',
            'contact_number' => null, // No visitor contact number provided
            'purpose' => 'Family Visit',
            'validity' => 'Today',
            'status' => 'Pending',
            'type' => 'Pre-registered',
            'access_token' => 'sample_token_empty_contact_12345',
        ]);

        $response = $this->actingAs($admin)->putJson("/api/visitors/{$visitor->id}/approve");
        $response->assertStatus(200);

        // Exactly ONE SMS should be sent to TextBee for the resident host
        $textBeeRequests = collect(\Illuminate\Support\Facades\Http::recorded())->filter(function ($pair) {
            return str_contains($pair[0]->url(), 'api.textbee.dev');
        });
        $this->assertCount(1, $textBeeRequests);
        $this->assertTrue(in_array('09181112222', $textBeeRequests->first()[0]['recipients']));
    }

    public function test_direct_pass_view_loads_for_approved_visitor_without_requiring_pin_input(): void
    {
        $resident = User::factory()->create(['name' => 'Cardo Dalisay']);
        $resident->assignRole('Resident');

        $lot = Lot::create([
            'block' => 3,
            'lot_number' => 7,
            'status' => 'Occupied',
        ]);
        $resident->lots()->attach($lot->id);

        $visitor = Visitor::create([
            'resident_id' => $resident->id,
            'host_name' => $resident->name,
            'visitor_name' => 'Visitor Guest',
            'contact_number' => '09171234567',
            'pin' => '654321',
            'access_token' => 'test_direct_token_abc_123',
            'purpose' => 'Social',
            'validity' => 'Today',
            'status' => 'Approved',
            'type' => 'Pre-registered',
        ]);

        // Unauthenticated visitor clicks the SMS link
        $response = $this->get("/v/{$visitor->access_token}");

        $response->assertStatus(200);
        $response->assertSee('Althesa Residences');
        $response->assertSee('Digital Visitor Gate Pass');
        $response->assertSee('654321'); // Shows PIN for guard
        $response->assertSee('Block 3, Lot 7'); // Shows destination
        $response->assertSee('Awaiting Guard Check-In');
    }

    public function test_direct_pass_unlocks_map_when_guard_marks_entered(): void
    {
        $resident = User::factory()->create(['name' => 'Cardo Dalisay']);
        $resident->assignRole('Resident');

        $lot = Lot::create([
            'block' => 2,
            'lot_number' => 15,
            'status' => 'Occupied',
        ]);
        $resident->lots()->attach($lot->id);

        $visitor = Visitor::create([
            'resident_id' => $resident->id,
            'host_name' => $resident->name,
            'visitor_name' => 'Courier Express',
            'pin' => '888999',
            'access_token' => 'entered_visitor_token_xyz',
            'purpose' => 'Package Drop',
            'validity' => 'Today',
            'status' => 'Entered',
            'arrival_time' => '02:30 PM',
            'type' => 'Pre-registered',
        ]);

        // Direct status API check
        $statusResponse = $this->getJson("/api/v/{$visitor->access_token}/status");
        $statusResponse->assertStatus(200);
        $statusResponse->assertJson([
            'success' => true,
            'status' => 'Entered',
            'destination' => 'Block 2, Lot 15',
            'endNode' => 'B2 L15',
        ]);

        // When opened on visitor browser after guard validated
        $viewResponse = $this->get("/v/{$visitor->access_token}");
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Block 2, Lot 15');
        $viewResponse->assertSee('mapContainer');
    }

    public function test_direct_pass_returns_404_for_invalid_token(): void
    {
        $response = $this->get('/v/invalid_non_existent_token');
        $response->assertStatus(404);
        $response->assertSee('Visitor Pass Not Found');
    }
}
