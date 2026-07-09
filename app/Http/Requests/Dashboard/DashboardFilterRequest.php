<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web') !== null || $this->user('admin') !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'range' => ['nullable', Rule::in(['this_month', 'last_30_days', 'quarter_to_date', 'year_to_date', 'last_12_months', 'custom'])],
            'start_date' => ['nullable', 'date', 'required_if:range,custom'],
            'end_date' => ['nullable', 'date', 'required_if:range,custom', 'after_or_equal:start_date'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'sublocation_id' => ['nullable', 'integer', 'exists:locations,id'],
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->validated();
    }
}
