<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserMiddleNameMandatoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Finance Officer', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Security Guard', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Resident', 'guard_name' => 'web']);
    }

    public function test_creating_user_without_middle_name_is_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->postJson('/admin/users', [
            'first_name' => 'Juan',
            'middle_name' => '',
            'last_name' => 'Dela Cruz',
            'email' => 'juan.test@gmail.com',
            'contact_number' => '09171234567',
            'role' => 'Finance Officer',
            'status' => 'Active',
            'password' => 'Secret123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['middle_name']);
    }

    public function test_creating_user_with_middle_initial_succeeds(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->postJson('/admin/users', [
            'first_name' => 'Juan',
            'middle_name' => 'D.',
            'last_name' => 'Santos',
            'email' => 'juan.santos@gmail.com',
            'contact_number' => '09171234567',
            'role' => 'Finance Officer',
            'status' => 'Active',
            'password' => 'Secret123!',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('users', [
            'email' => 'juan.santos@gmail.com',
            'first_name' => 'Juan',
            'middle_name' => 'D.',
            'last_name' => 'Santos',
        ]);
    }

    public function test_creating_user_with_full_middle_name_succeeds(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->postJson('/admin/users', [
            'first_name' => 'Maria',
            'middle_name' => 'Mercado',
            'last_name' => 'Reyes',
            'email' => 'maria.reyes@gmail.com',
            'contact_number' => '09181234567',
            'role' => 'Finance Officer',
            'status' => 'Active',
            'password' => 'Secret123!',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('users', [
            'email' => 'maria.reyes@gmail.com',
            'first_name' => 'Maria',
            'middle_name' => 'Mercado',
            'last_name' => 'Reyes',
        ]);
    }

    public function test_updating_user_without_middle_name_is_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $user = User::factory()->create([
            'first_name' => 'Pedro',
            'middle_name' => 'G.',
            'last_name' => 'Cruz',
            'email' => 'pedro.cruz@gmail.com',
            'contact_number' => '09191234567',
        ]);
        $user->assignRole('Finance Officer');

        $response = $this->actingAs($admin)->putJson("/admin/users/{$user->id}", [
            'first_name' => 'Pedro',
            'middle_name' => '',
            'last_name' => 'Cruz',
            'email' => 'pedro.cruz@gmail.com',
            'contact_number' => '09191234567',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['middle_name']);
    }

    public function test_updating_user_with_middle_name_or_initial_succeeds(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $user = User::factory()->create([
            'first_name' => 'Pedro',
            'middle_name' => 'G.',
            'last_name' => 'Cruz',
            'email' => 'pedro.cruz@gmail.com',
            'contact_number' => '09191234567',
        ]);
        $user->assignRole('Finance Officer');

        $response = $this->actingAs($admin)->putJson("/admin/users/{$user->id}", [
            'first_name' => 'Pedro',
            'middle_name' => 'Gonzales',
            'last_name' => 'Cruz',
            'email' => 'pedro.cruz@gmail.com',
            'contact_number' => '09191234567',
        ]);

        $response->assertOk();
        $this->assertEquals('Gonzales', $user->fresh()->middle_name);
    }
}
