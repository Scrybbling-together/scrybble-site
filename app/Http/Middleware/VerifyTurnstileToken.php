<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;
use App\Services\CloudflareTurnstileService;
use Symfony\Component\HttpFoundation\Response;

class VerifyTurnstileToken implements ValidationRule
{
    protected CloudflareTurnstileService $turnstileService;

    public function __construct(CloudflareTurnstileService $turnstileService)
    {
        $this->turnstileService = $turnstileService;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response) $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {

        return $next($request);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $request = request();
        $token = $request->input('cf-turnstile-response');

        $result = $this->turnstileService->verifyTurnstileToken($token);

        if (!$result['success']) {
            $fail($result['error-codes'][0] ?? "Failed to validate cloudflare turnstile token");
        }
    }
}
