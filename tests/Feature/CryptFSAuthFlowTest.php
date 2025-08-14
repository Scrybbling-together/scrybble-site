<?php

namespace Tests\Feature;

use App\Models\User;
use App\modules\CryptFS\Services\CryptFSKeyService;
use App\modules\CryptFS\Services\CryptFSSessionService;
use Cache;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class CryptFSAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private CryptFSKeyService $keyService;
    private CryptFSSessionService $sessionService;
    private string $testPassword = 'test-password-123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->keyService = app(CryptFSKeyService::class);
        $this->sessionService = app(CryptFSSessionService::class);

        // Clear any existing Redis data for this user
        $this->clearRedisForUser($this->user);
    }

    protected function tearDown(): void
    {
        $this->clearRedisForUser($this->user);
        parent::tearDown();
    }


    private function authorizeUsingDeviceFlow(): array
    {
        $clientRepository = app(ClientRepository::class);
        $deviceClient = $clientRepository->createDeviceAuthorizationGrantClient(
            name: 'Test Device Client',
            confidential: false,
            user: $this->user
        );

        $deviceCodeResponse = $this->post('/oauth/device/code', [
            'client_id' => $deviceClient->id,
            'scope' => '',
        ]);

        $deviceCodeResponse->assertStatus(200);
        $deviceCodeData = $deviceCodeResponse->json();

        $this->assertArrayHasKey('device_code', $deviceCodeData);
        $this->assertArrayHasKey('user_code', $deviceCodeData);
        $this->assertArrayHasKey('verification_uri', $deviceCodeData);
        $this->assertArrayHasKey('interval', $deviceCodeData);
        $this->assertArrayHasKey('expires_in', $deviceCodeData);

        $deviceCode = $deviceCodeData['device_code'];
        $userCode = $deviceCodeData['user_code'];
        $verificationUri = $deviceCodeData['verification_uri'];

        $this->assertGuest();
        $this->followingRedirects()->post('/login', [
            'email' => 'user@test.com',
            'password' => 'password'
        ])->assertSuccessful();
        $this->assertAuthenticated();

        $userCodeResponse = $this->followingRedirects()->get($verificationUri . '?user_code=' . $userCode);
        $userCodeResponse->assertSuccessful();
        $authorizationResponse = $this->followingRedirects()->post('/oauth/device/authorize', [
            'client_id' => $deviceClient->id,
            'state' => '',
            '_token' => session('token'),
            'auth_token' => session('authToken')
        ]);
        $authorizationResponse->assertSuccessful();

        $tokenResponse = $this->post('/oauth/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
            'client_id' => $deviceClient->id,
            'device_code' => $deviceCode,
        ]);

        $tokenResponse->assertStatus(200);
        $tokenData = $tokenResponse->json();

        $this->assertArrayHasKey('access_token', $tokenData);
        $this->assertArrayHasKey('refresh_token', $tokenData);
        $this->assertArrayHasKey('expires_in', $tokenData);
        $this->assertArrayHasKey('token_type', $tokenData);
        $this->assertEquals('Bearer', $tokenData['token_type']);

        return [
            "access_token" => $tokenData['access_token'],
            "token_response" => $tokenResponse
        ];
    }


    public function test_oauth_token_endpoint_includes_encryption_key_header()
    {
        $flowData = $this->authorizeUsingDeviceFlow();
        $pendingKey = $this->keyService->getPendingKey($this->user);
        $this->assertNotNull($pendingKey);
        $this->assertEquals($pendingKey, $flowData['token_response']->headers->get('X-Encryption-Key'));
    }

    public function test_key_confirmation_with_valid_key_succeeds()
    {
        $flowData = $this->authorizeUsingDeviceFlow();
        $this->assertNotNull($this->keyService->getPendingKey($this->user));
        $accessToken = $flowData['access_token'];
        $encryptionKey = $flowData['token_response']->headers->get('X-Encryption-Key');

        $this->assertAuthenticated();
        $response = $this->get('/api/sync/confirm-encryption-key', [
            'X-Encryption-Key' => $encryptionKey,
            'Authorization' => "Bearer $accessToken",
            'Accept' => "application/json"
        ]);

        $response->assertStatus(200);

        $this->user->refresh();
        $this->assertFalse($this->user->cryptFS->key_needs_derivation);
        $this->assertNull($this->keyService->getPendingKey($this->user));
    }

    public function test_key_confirmation_with_invalid_key_fails()
    {
        $flowData = $this->authorizeUsingDeviceFlow();
        $this->assertNotNull($this->keyService->getPendingKey($this->user));
        $accessToken = $flowData['access_token'];

        $this->assertAuthenticated();
        $response = $this->get('/api/sync/confirm-encryption-key', [
            'X-Encryption-Key' => 'invalid-key',
            'accept' => 'application/json',
            'Authorization' => "Bearer $accessToken"
        ]);

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Invalid encryption key']);

        $this->user->refresh();
        $this->assertTrue($this->user->cryptFS->key_needs_derivation);
        $this->assertNotNull($this->keyService->getPendingKey($this->user));
    }


    private function clearRedisForUser(User $user): void
    {
        $patterns = [
            "cryptfs:pending_derived_key:{$user->id}",
            "cryptfs:key-derivation:{$user->id}",
            "cryptfs:key_confirmation:{$user->id}",
            "cryptfs:mounting:{$user->id}",
            "crypto_session:user_{$user->id}"
        ];

        foreach ($patterns as $pattern) {
            Redis::del($pattern);
        }
    }
}
