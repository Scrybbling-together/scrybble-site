<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Log;

readonly class CloudflareTurnstileService {
    public const string CLOUDFLARE_CHALLENGE_URL = "https://challenges.cloudflare.com/turnstile/v0/siteverify";

    /**
     * @return bool
     */
    public static function turnstileEnabled(): bool
    {
        return !is_null(Config::get("scrybble.cloudflare.site_key")) && !is_null(Config::get("scrybble.cloudflare.secret_key"));
    }

    public function verifyTurnstileToken(?string $token)
    {
        if (is_null($token) || $token === "") {
            return [
                'success' => false,
                'error' => ['Missing Cloudflare turnstile token']
            ];
        }
        try {
            $response = Http::asForm()->post(self::CLOUDFLARE_CHALLENGE_URL, [
                'secret' => config('scrybble.cloudflare.secret_key'),
                'response' => $token,
                'remoteip' => request()->ip()
            ]);

            return $response->json();
        } catch(Exception $e) {
            Log::error('Cloudflare Turnstile verification failed', [
                'error' => $e->getMessage(),
                'token' => substr($token, 0, 10) . '...',
            ]);

            return [
                'success' => false,
                'error' => ['internal-error']
            ];
        }
    }
}
