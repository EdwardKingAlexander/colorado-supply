<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use App\Reporting\Definition\ReportDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_id' => fn (array $attributes) => User::find($attributes['user_id'])?->company_id,
            'name' => ucfirst($this->faker->words(3, true)),
            'description' => null,
            'dataset' => 'orders',
            'definition' => [
                'version' => ReportDefinition::VERSION,
                'dataset' => 'orders',
                'mode' => 'table',
                'columns' => [
                    ['key' => 'number', 'field' => 'order.order_number'],
                    ['key' => 'total', 'field' => 'order.grand_total', 'format' => 'currency'],
                ],
                'filters' => null,
                'sort' => [['column' => 'number', 'dir' => 'asc']],
                'limit' => 1000,
            ],
            'schema_version' => ReportDefinition::VERSION,
            'visibility' => Report::VISIBILITY_PRIVATE,
            'company_can_edit' => false,
        ];
    }

    public function sharedWithCompany(bool $editable = false): static
    {
        return $this->state(fn () => ['visibility' => Report::VISIBILITY_COMPANY, 'company_can_edit' => $editable]);
    }
}
