<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\RMapiProcessRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\RmApiHostConfig;
use Tests\TestCase;

#[CoversClass(RMapiProcessRunner::class)]
final class RMapiHostTest extends TestCase
{
    private function aRunnerWithApiHost(?string $apiHost): RMapiProcessRunner
    {
        return new RMapiProcessRunner(
            binaryPath: 'binaries/rmapi',
            configPath: '/user/.rmapi-auth',
            cacheHome: '/user',
            workingDir: '/user',
            apiHost: $apiHost,
        );
    }

    #[DataProvider('provideHostValues')]
    public function test_host_is_normalized_when_read_from_config(?string $envValue, ?string $expected): void
    {
        RmApiHostConfig::set($this->app['config'], $envValue);

        $this->assertSame($expected, config('scrybble.rmapi.host'));
    }

    /**
     * @return array<string, array{0: string|null, 1: string|null}>
     */
    public static function provideHostValues(): array
    {
        return [
            'trailing slash is stripped' => ['https://fake.local/', 'https://fake.local'],
            'multiple trailing slashes stripped' => ['https://fake.local//', 'https://fake.local'],
            'no slash is unchanged' => ['https://fake.local', 'https://fake.local'],
            'empty string resolves to null' => ['', null],
            'unset resolves to null' => [null, null],
        ];
    }

    public function test_build_process_env_includes_rmapi_host_when_api_host_is_set(): void
    {
        $env = $this->aRunnerWithApiHost('https://fake.local')->buildProcessEnv();

        $this->assertSame('https://fake.local', $env['RMAPI_HOST']);
        $this->assertSame('/user/.rmapi-auth', $env['RMAPI_CONFIG']);   // guards existing behavior
        $this->assertSame('/user', $env['XDG_CACHE_HOME']);
    }

    public function test_build_process_env_omits_rmapi_host_when_api_host_is_null(): void
    {
        $env = $this->aRunnerWithApiHost(null)->buildProcessEnv();

        $this->assertArrayNotHasKey('RMAPI_HOST', $env);
        $this->assertSame('/user/.rmapi-auth', $env['RMAPI_CONFIG']);   // guards existing behavior
        $this->assertSame('/user', $env['XDG_CACHE_HOME']);
    }
}
