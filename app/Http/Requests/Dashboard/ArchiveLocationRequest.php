<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArchiveLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')?->can('delete', $this->route('location')) === true;
    }

    public function rules(): array
    {
        return [
            'child_strategy' => ['nullable', Rule::in(['promote', 'archive_subtree'])],
        ];
    }
}
