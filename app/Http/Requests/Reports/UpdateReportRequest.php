<?php

namespace App\Http\Requests\Reports;

use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');
        $report = $this->route('report');

        // Hidden reports 404 rather than 403, so ids can't be probed.
        abort_unless($user?->can('view', $report) === true, 404);

        return $user->can('update', $report);
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'definition' => ['sometimes', 'required', 'array'],
            'visibility' => ['sometimes', Rule::in([Report::VISIBILITY_PRIVATE, Report::VISIBILITY_COMPANY])],
            'company_can_edit' => ['sometimes', 'boolean'],
        ];
    }
}
