<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => 'nullable|string',
            'starred' => 'nullable|boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $query = $this->input('query');
            $starred = $this->input('starred');
            $tags = $this->input('tags', []);

            if ($query === null && $starred === null && empty($tags)) {
                $validator->errors()->add('filters', 'At least one filter (query, starred, or tags) must be provided.');
            }
        });
    }
}
