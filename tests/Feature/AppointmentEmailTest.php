<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AppointmentEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_one_character_local_part_is_blocked(): void
    {
        $response = $this->postJson('/api/appointments', [
            'email' => 'f@hjgk.com',
            'first_name' => 'Juan',
            'last_name' => 'Cruz',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Please enter a valid email address. The name before @ must be at least 2 characters.');
    }

    public function test_malformed_email_is_blocked(): void
    {
        $this->postJson('/api/appointments', [
            'email' => 'notanemail',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Please enter a valid email address.');
    }

    public function test_blank_email_is_blocked(): void
    {
        $this->postJson('/api/appointments', [
            'email' => '   ',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Please enter an email address.');
    }

    public function test_domain_without_mail_servers_is_blocked(): void
    {
        $this->postJson('/api/appointments', [
            'email' => 'juan@not-a-real-domain-12345.com',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Please enter a valid email address that can receive mail.');
    }

    public function test_deliverable_email_is_not_rejected_as_invalid(): void
    {
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false], 200),
        ]);

        $response = $this->postJson('/api/appointments', [
            'email' => 'juan@gmail.com',
            'recaptcha_token' => 'test-token',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'reCAPTCHA verification failed. Please try again.');
    }

    public function test_email_update_blocks_short_local_part(): void
    {
        $this->putJson('/api/appointments/1/email', [
            'email' => 'a@gmail.com',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Please enter a valid email address. The name before @ must be at least 2 characters.');
    }
}
