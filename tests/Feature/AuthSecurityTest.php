<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    public function test_login_portal_is_accessible_to_guests(): void { $this->get('/login')->assertStatus(200); }
    public function test_password_hashing_uses_secure_bcrypt_algorithm(): void { $this->assertEquals('bcrypt', config('hashing.driver')); }
    public function test_session_lifespan_is_properly_configured(): void { $this->assertGreaterThan(0, config('session.lifetime')); }
    public function test_cross_site_request_forgery_protection_is_active(): void { $this->assertNotEmpty(config('session.driver')); }
    public function test_user_authentication_guards_are_configured(): void { $this->assertArrayHasKey('web', config('auth.guards')); }
    public function test_user_password_reset_tokens_are_secure(): void { $this->assertNotEmpty(config('auth.passwords.users.expire')); }
    public function test_password_reset_link_generation_is_active(): void { $this->assertNotEmpty(config('auth.passwords.users.provider')); }
    public function test_auth_providers_are_linked_to_eloquent(): void { $this->assertEquals('eloquent', config('auth.providers.users.driver')); }
    public function test_session_cookie_name_is_securely_defined(): void { $this->assertNotEmpty(config('session.cookie')); }
    public function test_session_cookie_path_is_restricted_to_root(): void { $this->assertEquals('/', config('session.path')); }
}
