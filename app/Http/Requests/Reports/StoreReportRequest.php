<?php

namespace App\Http\Requests\Reports;

use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')?->can('create', Report::class) === true;
    }

    /**
     * The definition's own structure is checked by ReportDefinitionValidator.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'definition' => ['required', 'array'],
            'visibility' => ['sometimes', Rule::in([Report::VISIBILITY_PRIVATE, Report::VISIBILITY_COMPANY])],
            'company_can_edit' => ['sometimes', 'boolean'],
        ];
    }
}
