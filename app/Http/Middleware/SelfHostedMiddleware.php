<?php


namespace App\Http\Middleware;

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
        if (config('scrybble.deployment_environment') !== 'self-hosted') {
            abort(404);
        }

        return $next($request);
    }
}
