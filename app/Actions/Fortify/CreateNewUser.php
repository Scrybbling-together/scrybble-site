<?php

namespace App\Actions\Fortify;

use App\Http\Middleware\VerifyTurnstileToken;
use App\Models\User;
use App\Services\CloudflareTurnstileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array  $input
     * @return User|JsonResponse
     */
    public function create(array $input): User|JsonResponse
    {
        $cloudflareService = new CloudflareTurnstileService();

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
            ...($cloudflareService::turnstileEnabled() ? [
                'cf-turnstile-response' => ['required', new VerifyTurnstileToken($cloudflareService)]
            ] : [])

        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
    }

}
