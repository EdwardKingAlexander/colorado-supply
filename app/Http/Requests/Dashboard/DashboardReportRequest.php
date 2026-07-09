<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Validation\Rule;

class DashboardReportRequest extends DashboardFilterRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'group_by' => ['nullable', Rule::in(['month', 'location', 'sublocation', 'product', 'order'])],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return array_merge(['group_by' => 'month'], $this->validated());
    }
}
