<?php

namespace App\Reporting;

use App\Reporting\Datasets\Dataset;
use App\Reporting\Datasets\OrderLinesDataset;
use App\Reporting\Datasets\OrdersDataset;
use App\Reporting\Datasets\PaymentsDataset;
use App\Reporting\Datasets\QuoteLinesDataset;
use App\Reporting\Datasets\QuotesDataset;
use App\Reporting\Datasets\ShipmentsDataset;
use Illuminate\Container\Attributes\Singleton;
use InvalidArgumentException;

#[Singleton]
final class DatasetRegistry
{
    /** @var array<string, Dataset> */
    private array $datasets = [];

    /**
     * @param  ?iterable<Dataset>  $datasets  defaults to every built-in dataset
     */
    public function __construct(?iterable $datasets = null)
    {
        $datasets ??= [
            new OrdersDataset,
            new OrderLinesDataset,
            new PaymentsDataset,
            new ShipmentsDataset,
            new QuotesDataset,
            new QuoteLinesDataset,
        ];

        foreach ($datasets as $dataset) {
            $this->datasets[$dataset->key()] = $dataset;
        }
    }

    /**
     * @return array<string, Dataset>
     */
    public function all(): array
    {
        return $this->datasets;
    }

    public function find(string $key): ?Dataset
    {
        return $this->datasets[$key] ?? null;
    }

    public function get(string $key): Dataset
    {
        return $this->find($key) ?? throw new InvalidArgumentException("Unknown report dataset [{$key}].");
    }
}
