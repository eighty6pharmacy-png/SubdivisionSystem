<?php

namespace Tests\Feature;

use App\Jobs\SendNewUserCredentialsJob;
use App\Mail\NewUserCredentialsMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NewUserCredentialsMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_credentials_email_includes_introduction_website_links_and_credentials_for_all_roles(): void
    {
        $roles = [
            'Admin' => 'administrator account',
            'Resident' => 'resident account',
            'Buyer' => 'Buyer Portal',
            'Security Guard' => 'Security Portal',
            'Finance Officer' => 'Finance Portal',
        ];

        foreach ($roles as $roleName => $expectedIntroSnippet) {
            Role::findOrCreate($roleName, 'web');

            $user = User::create([
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => strtolower(str_replace(' ', '.', $roleName)).'@example.test',
                'password' => 'TempPass123!',
                'status' => 'Active',
            ]);
            $user->assignRole($roleName);

            $mailable = new NewUserCredentialsMail($user, 'TempPass123!');
            $rendered = $mailable->render();

            $this->assertStringContainsString('Welcome to Your New Account', $rendered);
            $this->assertStringContainsString($roleName.' Account', $rendered);
            $this->assertStringContainsString($expectedIntroSnippet, $rendered);
            $this->assertStringContainsString($user->email, $rendered);
            $this->assertStringContainsString('TempPass123!', $rendered);
            $this->assertStringContainsString(url('/'), $rendered);
            $this->assertStringContainsString(url('/login'), $rendered);
        }
    }

    public function test_send_new_user_credentials_job_sends_mailable(): void
    {
        Mail::fake();
        Role::findOrCreate('Resident', 'web');

        $user = User::create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.test',
            'password' => 'Alth123456!',
            'status' => 'Active',
        ]);
        $user->assignRole('Resident');

        (new SendNewUserCredentialsJob($user, 'Alth123456!'))->handle();

        Mail::assertSent(NewUserCredentialsMail::class, function (NewUserCredentialsMail $mail) use ($user) {
            return $mail->hasTo($user->email)
                && $mail->roleName === 'Resident'
                && $mail->password === 'Alth123456!';
        });
    }

    public function test_admin_user_creation_sends_new_user_credentials_mail(): void
    {
        Mail::fake();
        Role::findOrCreate('Admin', 'web');
        Role::findOrCreate('Finance Officer', 'web');

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.test',
            'password' => 'secret123',
        ]);
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->postJson('/admin/users', [
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'last_name' => 'Reyes',
            'email' => 'maria.reyes.test@gmail.com',
            'contact_number' => '09171234567',
            'role' => 'Finance Officer',
            'status' => 'Active',
            'password' => 'InitPass123!',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        Mail::assertSent(NewUserCredentialsMail::class, function (NewUserCredentialsMail $mail) {
            return $mail->hasTo('maria.reyes.test@gmail.com')
                && $mail->roleName === 'Finance Officer'
                && $mail->password === 'InitPass123!';
        });
    }
}
