<?php


namespace App\Http\Middleware;

use App\Enums\DeploymentEnvironment;
use Closure;
use Illuminate\Http\Request;

class SelfHostedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (!DeploymentEnvironment::current()->isSelfHosted()) {
            abort(404);
        }

        return $next($request);
    }
}
