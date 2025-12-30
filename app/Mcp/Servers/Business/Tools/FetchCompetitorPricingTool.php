<?php

namespace App\Mcp\Servers\Business\Tools;

use App\Mcp\Servers\Tool;
use App\Models\Product;

class FetchCompetitorPricingTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'fetch-competitor-pricing';

    /**
     * The tool's description.
     */
    protected string $description = 'Compare competitor pricing from scrape data with internal database prices';

    /**
     * Define the tool's input schema.
     */
    protected function inputSchema(): array
    {
        return [
            'scrape_source' => [
                'type' => 'string',
                'description' => 'Path to scrape data file (default: ChromeDevTools/State/scrape-progress.json)',
                'required' => false,
            ],
            'sku_field' => [
                'type' => 'string',
                'description' => 'Database field to match SKU against (default: sku)',
                'required' => false,
            ],
            'price_field' => [
                'type' => 'string',
                'description' => 'Database field containing our price (default: price)',
                'required' => false,
            ],
            'min_difference_percent' => [
                'type' => 'number',
                'description' => 'Only include products with price difference above this % (default: 0)',
                'required' => false,
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Limit number of results returned (default: all)',
                'required' => false,
            ],
        ];
    }

    /**
     * Execute the tool.
     */
    public function execute(array $inputs): string
    {
        $scrapeSource = $inputs['scrape_source'] ?? null;
        $skuField = $inputs['sku_field'] ?? 'sku';
        $priceField = $inputs['price_field'] ?? 'price';
        $minDifferencePercent = $inputs['min_difference_percent'] ?? 0;
        $limit = $inputs['limit'] ?? null;

        try {
            // Load scrape data
            $scrapeData = $this->loadScrapeData($scrapeSource);

            if (empty($scrapeData)) {
                return json_encode([
                    'success' => false,
                    'error' => 'No scrape data found',
                    'hint' => 'Run MultiPageScrapeSkill first to generate scrape data',
                ], JSON_PRETTY_PRINT);
            }

            // Process comparisons
            $comparisons = $this->compareWithDatabase(
                $scrapeData,
                $skuField,
                $priceField,
                $minDifferencePercent
            );

            // Apply limit if specified
            if ($limit) {
                $comparisons = array_slice($comparisons, 0, $limit);
            }

            // Generate summary statistics
            $summary = $this->generateSummary($comparisons);

            return json_encode([
                'success' => true,
                'summary' => $summary,
                'comparisons' => $comparisons,
                'total_compared' => count($comparisons),
                'scrape_source' => $scrapeSource ?? 'default',
            ], JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ], JSON_PRETTY_PRINT);
        }
    }

    /**
     * Load scrape data from file.
     */
    protected function loadScrapeData(?string $source = null): array
    {
        // Default to ChromeDevTools scrape progress
        if (! $source) {
            $source = app_path('Mcp/Servers/ChromeDevTools/State/scrape-progress.json');
        }

        if (! file_exists($source)) {
            return [];
        }

        $data = json_decode(file_get_contents($source), true);

        if (! isset($data['results'])) {
            return [];
        }

        // Extract successful products from results
        $products = [];
        foreach ($data['results'] as $result) {
            if ($result['success'] && isset($result['product'])) {
                $product = $result['product'];
                if (isset($product['sku']) && isset($product['price_numeric'])) {
                    $products[] = $product;
                }
            }
        }

        return $products;
    }

    /**
     * Compare scrape data with database prices.
     */
    protected function compareWithDatabase(
        array $scrapeData,
        string $skuField,
        string $priceField,
        float $minDifferencePercent
    ): array {
        $comparisons = [];

        foreach ($scrapeData as $scrapedProduct) {
            $sku = $scrapedProduct['sku'];
            $competitorPrice = $scrapedProduct['price_numeric'];

            // Find matching product in database
            $dbProduct = Product::where($skuField, $sku)->first();

            if (! $dbProduct) {
                // SKU not found in database
                $comparisons[] = [
                    'sku' => $sku,
                    'title' => $scrapedProduct['title'] ?? null,
                    'competitor_price' => $competitorPrice,
                    'our_price' => null,
                    'difference' => null,
                    'difference_percent' => null,
                    'cheaper_source' => 'competitor',
                    'status' => 'not_in_database',
                    'action' => 'Consider adding this product',
                ];

                continue;
            }

            $ourPrice = $dbProduct->{$priceField};

            if ($ourPrice === null || $ourPrice <= 0) {
                // No price set in database
                $comparisons[] = [
                    'sku' => $sku,
                    'title' => $scrapedProduct['title'] ?? $dbProduct->name ?? null,
                    'competitor_price' => $competitorPrice,
                    'our_price' => null,
                    'difference' => null,
                    'difference_percent' => null,
                    'cheaper_source' => 'competitor',
                    'status' => 'no_price_set',
                    'action' => 'Set pricing for this product',
                ];

                continue;
            }

            // Calculate difference
            $difference = $ourPrice - $competitorPrice;
            $differencePercent = ($difference / $competitorPrice) * 100;

            // Skip if below minimum difference threshold
            if (abs($differencePercent) < $minDifferencePercent) {
                continue;
            }

            // Determine cheaper source
            $cheaperSource = $difference > 0 ? 'competitor' : 'us';
            $action = $this->suggestAction($differencePercent);

            $comparisons[] = [
                'sku' => $sku,
                'title' => $scrapedProduct['title'] ?? $dbProduct->name ?? null,
                'competitor_price' => $competitorPrice,
                'our_price' => $ourPrice,
                'difference' => round($difference, 2),
                'difference_percent' => round($differencePercent, 2),
                'cheaper_source' => $cheaperSource,
                'status' => 'compared',
                'action' => $action,
            ];
        }

        // Sort by absolute difference percent (largest differences first)
        usort($comparisons, function ($a, $b) {
            $aDiff = abs($a['difference_percent'] ?? 0);
            $bDiff = abs($b['difference_percent'] ?? 0);

            return $bDiff <=> $aDiff;
        });

        return $comparisons;
    }

    /**
     * Suggest action based on price difference.
     */
    protected function suggestAction(float $differencePercent): string
    {
        if ($differencePercent > 20) {
            return 'Consider lowering price - competitor is significantly cheaper';
        } elseif ($differencePercent > 10) {
            return 'Review pricing - competitor is cheaper';
        } elseif ($differencePercent > 0) {
            return 'Monitor - competitor is slightly cheaper';
        } elseif ($differencePercent > -10) {
            return 'Competitive - our price is slightly better';
        } elseif ($differencePercent > -20) {
            return 'Good position - we are cheaper';
        } else {
            return 'Consider raising price - we may be underpricing';
        }
    }

    /**
     * Generate summary statistics.
     */
    protected function generateSummary(array $comparisons): array
    {
        $total = count($comparisons);
        $weCheaper = 0;
        $theyCheaper = 0;
        $notInDb = 0;
        $noPriceSet = 0;
        $avgDifference = 0;
        $maxDifference = null;
        $minDifference = null;

        foreach ($comparisons as $comparison) {
            if ($comparison['status'] === 'not_in_database') {
                $notInDb++;

                continue;
            }

            if ($comparison['status'] === 'no_price_set') {
                $noPriceSet++;

                continue;
            }

            if ($comparison['cheaper_source'] === 'us') {
                $weCheaper++;
            } else {
                $theyCheaper++;
            }

            $diffPercent = $comparison['difference_percent'];
            $avgDifference += $diffPercent;

            if ($maxDifference === null || $diffPercent > $maxDifference) {
                $maxDifference = $diffPercent;
            }

            if ($minDifference === null || $diffPercent < $minDifference) {
                $minDifference = $diffPercent;
            }
        }

        $compared = $total - $notInDb - $noPriceSet;

        return [
            'total_products' => $total,
            'successfully_compared' => $compared,
            'not_in_database' => $notInDb,
            'no_price_set' => $noPriceSet,
            'we_are_cheaper' => $weCheaper,
            'competitor_cheaper' => $theyCheaper,
            'avg_price_difference_percent' => $compared > 0 ? round($avgDifference / $compared, 2) : 0,
            'max_difference_percent' => $maxDifference ? round($maxDifference, 2) : null,
            'min_difference_percent' => $minDifference ? round($minDifference, 2) : null,
        ];
    }
}
