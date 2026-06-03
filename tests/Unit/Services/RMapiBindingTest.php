<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\RMapi;
use ArgumentCountError;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers how {@see RMapi} is constructed and resolved from the container.
 */
final class RMapiBindingTest extends TestCase
{
    use RefreshDatabase;

    public function test_RMapi_constructor_requires_a_user(): void
    {
        $this->expectException(ArgumentCountError::class);
        new RMapi();
    }

    public function test_RMapi_binding_is_scoped_per_request(): void
    {
        Storage::fake('efs');

        Auth::login(User::factory()->create());
        $rmapiA = app(RMapi::class);

        $this->app->forgetScopedInstances();

        Auth::login(User::factory()->create());
        $rmapiB = app(RMapi::class);

        $this->assertNotSame($rmapiA, $rmapiB);
    }
}