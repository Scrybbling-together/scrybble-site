<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\RemarkableDocumentShare;
use App\Models\Sync;
use Illuminate\Foundation\Http\FormRequest;

class DebugBundleAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Sync $sync */
        $sync = $this->route('sync');
        $user = $this->user();

        // Admin (user ID 1) can access any sync
        if ($user?->id === 1) {
            return true;
        }

        // Owner can access their own syncs
        if ($user && $sync->user_id === $user->id) {
            return true;
        }

        // Anyone can access publicly shared syncs
        $hasOpenAccess = RemarkableDocumentShare::where('sync_id', $sync->id)
            ->where('open_access_consent_granted', true)
            ->exists();

        if ($hasOpenAccess) {
            return true;
        }

        return false;
    }

    public function rules(): array
    {
        return [];
    }
}
