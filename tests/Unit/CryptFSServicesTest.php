<?php

namespace Tests\Unit;

use App\Models\User;
use App\modules\CryptFS\Services\CryptFSKeyService;
use App\modules\CryptFS\Services\CryptFSSessionService;
use Cache;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class CryptFSServicesTest extends TestCase
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

        $this->clearRedisForUser($this->user);
    }

    protected function tearDown(): void
    {
        $this->clearRedisForUser($this->user);
        parent::tearDown();
    }

    public function test_new_user_needs_key_derivation()
    {
        $this->assertTrue($this->keyService->needsKeyDerivation($this->user));
        $this->assertNull($this->keyService->getPendingKey($this->user));
    }

    public function test_key_derivation_creates_salt_and_stores_pending_key()
    {
        $derivedKey = $this->keyService->deriveKey($this->user, $this->testPassword);

        $this->assertNotNull($derivedKey);
        $this->assertEquals($derivedKey, $this->keyService->getPendingKey($this->user));

        $this->user->refresh();
        $this->assertNotNull($this->user->cryptFS);
        $this->assertNotNull($this->user->cryptFS->encryption_key_salt);
        $this->assertTrue($this->user->cryptFS->key_needs_derivation);
    }

    public function test_key_derivation_locks_prevent_race_conditions()
    {
        $lockKey = "cryptfs:key-derivation:{$this->user->id}";

        $this->assertFalse(boolval(Redis::exists($lockKey)));

        $this->expectException(LockTimeoutException::class);
        Cache::Lock($lockKey)->get(
            fn () => $this->sessionService->withKeyDerivationLock($this->user, fn () => true)
        );
    }

    public function test_session_management_throughout_flow()
    {
        $this->assertFalse($this->sessionService->hasActiveSession($this->user));

        $this->sessionService->createSession($this->user);
        $this->assertTrue($this->sessionService->hasActiveSession($this->user));

        $this->sessionService->updateSession($this->user);
        $this->assertTrue($this->sessionService->hasActiveSession($this->user));

        $this->sessionService->destroySession($this->user);
        $this->assertFalse($this->sessionService->hasActiveSession($this->user));
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