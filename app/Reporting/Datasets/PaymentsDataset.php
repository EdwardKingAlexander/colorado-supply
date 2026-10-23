<?php

namespace App\Reporting\Datasets;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\User;
use App\Reporting\Fields\Field;
use App\Reporting\Fields\OrderFields;
use App\Reporting\FieldType;
use Illuminate\Database\Query\Builder;

/**
 * Gateway ids, failure codes/messages and meta are deliberately absent.
 */
class PaymentsDataset extends Dataset
{
    public function key(): string
    {
        return 'payments';
    }

    public function label(): string
    {
        return 'Payments';
    }

    public function description(): string
    {
        return 'One row per payment attempt, with its order.';
    }

    public function alias(): string
    {
        return 'pay';
    }

    protected function table(): string
    {
        return 'payments';
    }

    protected function requiredJoins(): array
    {
        return ['order'];
    }

    protected function joins(): array
    {
        return OrderFields::joins('pay');
    }

    protected function defineFields(): array
    {
        $payment = fn (string $key, string $label, FieldType $type, string $expression, array $options = []) => new Field(
            "payment.{$key}", $label, 'Payment', $type, $expression, options: $options,
        );

        return [
            $payment('method', 'Payment method', FieldType::Enum, 'pay.method', Field::enumOptions(PaymentMethod::class)),
            $payment('status', 'Payment status', FieldType::Enum, 'pay.status', Field::enumOptions(PaymentStatus::class)),
            $payment('amount', 'Amount', FieldType::Money, 'pay.amount'),
            $payment('currency', 'Currency', FieldType::String, 'pay.currency'),
            $payment('created_at', 'Payment date', FieldType::DateTime, 'pay.created_at'),
            $payment('paid_at', 'Paid at', FieldType::DateTime, 'pay.paid_at'),
            $payment('failed_at', 'Failed at', FieldType::DateTime, 'pay.failed_at'),
            $payment('refunded_at', 'Refunded at', FieldType::DateTime, 'pay.refunded_at'),
            ...OrderFields::fields(['order']),
        ];
    }

    public function applyScope(Builder $query, User $user): void
    {
        $this->scopeToOrders($query, $user);
    }
}
