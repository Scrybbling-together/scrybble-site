<?php

namespace Tests\Unit\Http\Middleware;

use App\Enums\DeploymentEnvironment;
use App\Http\Middleware\SelfHostedMiddleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class SelfHostedMiddlewareTest extends TestCase
{
    public function test_aborts_with_404_in_commercial_deployment()
    {
        config(['scrybble.deployment_environment' => DeploymentEnvironment::Commercial->value]);

        $this->expectException(NotFoundHttpException::class);

        (new SelfHostedMiddleware())->handle(new Request(), fn ($r) => $r);
    }

    public function test_passes_through_in_self_hosted_deployment()
    {
        config(['scrybble.deployment_environment' => DeploymentEnvironment::SelfHosted->value]);

        $request = new Request();
        $result = (new SelfHostedMiddleware())->handle($request, fn ($r) => 'next-called');

        $this->assertSame('next-called', $result);
    }
}
