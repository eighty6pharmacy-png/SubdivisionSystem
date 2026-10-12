<?php

namespace Tests\Feature;

use App\Jobs\SendNewUserCredentialsJob;
use App\Models\Lot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserImportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('Admin', 'web');
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'secret123',
        ]);
        $user->assignRole('Admin');

        return $user;
    }

    private function csvUpload(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('users.csv', $content);
    }

    private function baseHeader(): string
    {
        return "external_id,first_name,last_name,email,contact_number,role,block,lot,buyer_master_list_id\n";
    }

    public function test_import_requires_admin_authentication(): void
    {
        $csv = $this->baseHeader()."1001,Juan,Dela Cruz,juan@example.test,09171234567,Resident,1,2,\n";

        $this->post('/admin/users/import', [
            'csv_file' => $this->csvUpload($csv),
        ])->assertRedirect('/login');
    }

    public function test_import_creates_resident_with_external_id(): void
    {
        Queue::fake();
        Role::findOrCreate('Resident', 'web');
        $admin = $this->admin();

        $csv = $this->baseHeader()."1001,Juan,Dela Cruz,juan@example.test,09171234567,Resident,5,12,\n";

        $response = $this->actingAs($admin)->post('/admin/users/import', [
            'csv_file' => $this->csvUpload($csv),
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'created' => 1,
            'updated' => 0,
        ]);

        $this->assertDatabaseHas('users', [
            'external_id' => '1001',
            'email' => 'juan@example.test',
        ]);

        Queue::assertPushed(SendNewUserCredentialsJob::class);
    }

    public function test_import_upserts_by_external_id(): void
    {
        Queue::fake();
        Role::findOrCreate('Resident', 'web');
        $admin = $this->admin();

        User::create([
            'external_id' => '1001',
            'name' => 'Old Name',
            'email' => 'old@example.test',
            'contact_number' => '09171234567',
            'password' => 'secret123',
            'status' => 'Active',
        ])->assignRole('Resident');

        $csv = $this->baseHeader()."1001,Juan,Dela Cruz,juan@example.test,09181234567,Resident,5,12,\n";

        $response = $this->actingAs($admin)->post('/admin/users/import', [
            'csv_file' => $this->csvUpload($csv),
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'created' => 0,
            'updated' => 1,
        ]);

        $this->assertDatabaseHas('users', [
            'external_id' => '1001',
            'email' => 'juan@example.test',
            'contact_number' => '09181234567',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
        ]);

        Queue::assertNothingPushed();
    }

    public function test_import_rejects_duplicate_external_id_in_csv(): void
    {
        $admin = $this->admin();
        $csv = $this->baseHeader()
            ."1001,Juan,Dela Cruz,juan@example.test,09171234567,Resident,1,2,\n"
            ."1001,Pedro,Santos,pedro@example.test,09181234567,Resident,3,4,\n";

        $response = $this->actingAs($admin)->post('/admin/users/import', [
            'csv_file' => $this->csvUpload($csv),
        ]);

        $response->assertOk()->assertJsonPath('created', 1);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Duplicate external_id', $errors[0]);
    }

    public function test_import_rejects_duplicate_email_in_csv(): void
    {
        $admin = $this->admin();
        $csv = $this->baseHeader()
            ."1001,Juan,Dela Cruz,same@example.test,09171234567,Resident,1,2,\n"
            ."1002,Pedro,Santos,same@example.test,09181234567,Resident,3,4,\n";

        $response = $this->actingAs($admin)->post('/admin/users/import', [
            'csv_file' => $this->csvUpload($csv),
        ]);

        $response->assertOk()->assertJsonPath('created', 1);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Duplicate email', $errors[0]);
    }

    public function test_import_rejects_occupied_lot(): void
    {
        Role::findOrCreate('Resident', 'web');
        $admin = $this->admin();

        $lot = Lot::create(['block' => '9', 'lot_number' => '1', 'status' => 'Occupied']);
        $occupant = User::create([
            'name' => 'Occupant',
            'email' => 'occ@example.test',
            'password' => 'secret123',
            'status' => 'Active',
        ]);
        $occupant->assignRole('Resident');
        $occupant->lots()->attach($lot->id);

        $csv = $this->baseHeader()."2002,New,Resident,new@example.test,09171234567,Resident,9,1,\n";

        $response = $this->actingAs($admin)->post('/admin/users/import', [
            'csv_file' => $this->csvUpload($csv),
        ]);

        $response->assertOk()->assertJsonPath('created', 0);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('already occupied', $errors[0]);
    }
}
