<?php

namespace Tests\Feature;

use App\Services\CloudflareTurnstileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_registration_succeeds_when_turnstile_is_disabled()
    {
        Config::set('scrybble.cloudflare.secret_key');
        Config::set('scrybble.cloudflare.site_key');
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertStatus(302);
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com'
        ]);
    }

    public function test_user_registration_fails_when_registering_with_invalid_credentials()
    {
        Config::set('scrybble.cloudflare.secret_key');
        Config::set('scrybble.cloudflare.site_key');
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'different',
            'password_confirmation' => 'diferent',
        ]);
        $response->assertSessionHasErrors();
        $response->assertStatus(302);
        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com'
        ]);
    }

    public function test_user_registration_fails_when_missing_cloudflare_token_when_turnstile_is_enabled()
    {
        Config::set("scrybble.cloudflare.secret_key", 'test-secret-key');
        Config::set("scrybble.cloudflare.site_key", 'test-site-key');
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'a_valid_password',
            'password_confirmation' => 'a_valid_password',
        ]);
        $response->assertSessionHasErrorsIn("cf-turnstile-response");
        $response->assertStatus(302);
        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com'
        ]);
    }

    public function test_user_registration_fails_when_turnstile_validation_fails()
    {
        Config::set('scrybble.cloudflare.site_key', 'test-site-key');
        Config::set('scrybble.cloudflare.secret_key', 'test-secret-key');

        Http::fake([
            CloudflareTurnstileService::CLOUDFLARE_CHALLENGE_URL => Http::response([
                'success' => false,
                'error-codes' => ['invalid-turnstile-token']
            ])]
        );

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'a_valid_password',
            'password_confirmation' => 'a_valid_password',
            'cf-turnstile-response' => 'abc'
        ];

        $response = $this->post('/register', $userData);

        $response->assertStatus(302);
        $response->assertSessionHasErrorsIn('cf-turnstile-response');

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com'
        ]);
    }
}
