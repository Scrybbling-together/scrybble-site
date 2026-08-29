<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class isAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check()) {
            abort(403, 'Unauthorized action');
        }

        $isAdmin = auth()->user()->id === 1;
        $isLocalEnv = config('app.env') === 'local';

        if ($isAdmin || $isLocalEnv) {
            return $next($request);
        }

        abort(403, 'Unauthorized action');
    }
}
