<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreConnectedGumroadLicenseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'license' => [
                'filled',
                'string',
            ],
        ];
    }

    public function authorize(): bool
    {
        return Auth::user() !== null;
    }
}
