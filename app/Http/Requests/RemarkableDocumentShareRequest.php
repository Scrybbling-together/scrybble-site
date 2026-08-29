<?php

namespace App\Http\Requests;

use App\Models\Sync;
use Illuminate\Foundation\Http\FormRequest;

class RemarkableDocumentShareRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'feedback' => ['nullable', 'string'],
            'sync_id' => ['required', 'exists:sync,id'],
            'developer_access_consent_granted' => ['boolean'],
            'open_access_consent_granted' => ['boolean'],
        ];
    }

    public function authorize(): bool
    {
        $sync = Sync::find($this->input('sync_id'));
        if (! $sync) {
            return false;
        }

        return $this->user()->id === $sync->user_id;
    }
}
