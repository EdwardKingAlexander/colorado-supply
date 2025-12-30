<?php

namespace App\Mcp\Servers\ChromeDevTools\Skills;

use App\Mcp\Servers\ChromeDevTools\Tools\ProductDataScraperTool;

/**
 * Multi-Page Scraping Skill
 *
 * This skill demonstrates how to:
 * - Loop through paginated product URLs
 * - Call ProductDataScraperTool for each page
 * - Save incremental progress for resumability
 * - Handle errors and continue processing
 *
 * Usage Example:
 * ```php
 * $skill = new MultiPageScrapeSkill();
 * $result = $skill->scrape([
 *     'urls' => [
 *         'https://vendor.com/products/1',
 *         'https://vendor.com/products/2',
 *         'https://vendor.com/products/3',
 *     ],
 *     'use_session' => true,
 *     'resume' => true
 * ]);
 * ```
 */
class MultiPageScrapeSkill
{
    protected string $progressFile;

    protected array $results = [];

    protected int $successCount = 0;

    protected int $errorCount = 0;

    public function __construct()
    {
        $this->progressFile = __DIR__.'/../State/scrape-progress.json';
    }

    /**
     * Scrape multiple product pages with progress tracking.
     *
     * @param  array  $config  Configuration options
     * @return array Results summary
     */
    public function scrape(array $config): array
    {
        $urls = $config['urls'] ?? [];
        $useSession = $config['use_session'] ?? true;
        $resume = $config['resume'] ?? true;
        $titleSelector = $config['title_selector'] ?? null;
        $skuSelector = $config['sku_selector'] ?? null;
        $priceSelector = $config['price_selector'] ?? null;
        $saveHtml = $config['save_html'] ?? true;
        $delayMs = $config['delay_ms'] ?? 1000; // Delay between requests

        // Load progress if resuming
        if ($resume) {
            $this->loadProgress();
        }

        // Get list of URLs to process
        $urlsToProcess = $this->getUnprocessedUrls($urls);

        // Scrape each URL
        foreach ($urlsToProcess as $index => $url) {
            try {
                // Build scraper input
                $scraperInput = [
                    'url' => $url,
                    'use_session' => $useSession,
                    'save_html' => $saveHtml,
                ];

                // Add custom selectors if provided
                if ($titleSelector) {
                    $scraperInput['title_selector'] = $titleSelector;
                }
                if ($skuSelector) {
                    $scraperInput['sku_selector'] = $skuSelector;
                }
                if ($priceSelector) {
                    $scraperInput['price_selector'] = $priceSelector;
                }

                // Call the ProductDataScraperTool
                $scraper = new ProductDataScraperTool;
                $resultJson = $scraper->execute($scraperInput);
                $result = json_decode($resultJson, true);

                // Store result
                $this->addResult($url, $result);

                if ($result['success']) {
                    $this->successCount++;
                } else {
                    $this->errorCount++;
                }

                // Save progress after each page
                $this->saveProgress($urls);

                // Delay between requests to be polite
                if ($index < count($urlsToProcess) - 1) {
                    usleep($delayMs * 1000);
                }
            } catch (\Exception $e) {
                // Log error and continue
                $this->addResult($url, [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'url' => $url,
                ]);
                $this->errorCount++;
                $this->saveProgress($urls);
            }
        }

        // Generate final summary
        return $this->generateSummary($urls);
    }

    /**
     * Load previous progress from state file.
     */
    protected function loadProgress(): void
    {
        if (! file_exists($this->progressFile)) {
            return;
        }

        try {
            $progress = json_decode(file_get_contents($this->progressFile), true);

            if (isset($progress['results'])) {
                $this->results = $progress['results'];
                $this->successCount = $progress['success_count'] ?? 0;
                $this->errorCount = $progress['error_count'] ?? 0;
            }
        } catch (\Exception $e) {
            // Failed to load progress, start fresh
        }
    }

    /**
     * Save current progress to state file.
     */
    protected function saveProgress(array $allUrls): void
    {
        $progress = [
            'total_urls' => count($allUrls),
            'processed_count' => count($this->results),
            'success_count' => $this->successCount,
            'error_count' => $this->errorCount,
            'last_updated' => now()->toIso8601String(),
            'timestamp' => time(),
            'results' => $this->results,
        ];

        file_put_contents($this->progressFile, json_encode($progress, JSON_PRETTY_PRINT));
    }

    /**
     * Get URLs that haven't been processed yet.
     */
    protected function getUnprocessedUrls(array $urls): array
    {
        $processedUrls = array_keys($this->results);

        return array_filter($urls, function ($url) use ($processedUrls) {
            return ! in_array($url, $processedUrls);
        });
    }

    /**
     * Add a result to the collection.
     */
    protected function addResult(string $url, array $result): void
    {
        $this->results[$url] = [
            'url' => $url,
            'success' => $result['success'] ?? false,
            'product' => $result['product'] ?? null,
            'error' => $result['error'] ?? null,
            'scraped_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Generate final summary of scraping operation.
     */
    protected function generateSummary(array $allUrls): array
    {
        // Extract successful products
        $products = [];
        $errors = [];

        foreach ($this->results as $url => $result) {
            if ($result['success']) {
                $products[] = $result['product'];
            } else {
                $errors[] = [
                    'url' => $url,
                    'error' => $result['error'],
                ];
            }
        }

        return [
            'success' => true,
            'summary' => [
                'total_urls' => count($allUrls),
                'processed' => count($this->results),
                'successful' => $this->successCount,
                'failed' => $this->errorCount,
            ],
            'products' => $products,
            'errors' => $errors,
            'progress_file' => $this->progressFile,
            'completed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Clear saved progress (start fresh).
     */
    public function clearProgress(): bool
    {
        if (file_exists($this->progressFile)) {
            return unlink($this->progressFile);
        }

        return true;
    }

    /**
     * Get current progress status.
     */
    public function getProgress(): ?array
    {
        if (! file_exists($this->progressFile)) {
            return null;
        }

        return json_decode(file_get_contents($this->progressFile), true);
    }

    /**
     * Export results to a CSV file.
     */
    public function exportToCsv(string $outputPath): string
    {
        $csv = fopen($outputPath, 'w');

        // Write header
        fputcsv($csv, ['URL', 'Title', 'SKU', 'Price', 'Price Numeric', 'Status', 'Error']);

        // Write data
        foreach ($this->results as $result) {
            $product = $result['product'] ?? [];
            fputcsv($csv, [
                $result['url'],
                $product['title'] ?? '',
                $product['sku'] ?? '',
                $product['price'] ?? '',
                $product['price_numeric'] ?? '',
                $result['success'] ? 'Success' : 'Failed',
                $result['error'] ?? '',
            ]);
        }

        fclose($csv);

        return $outputPath;
    }

    /**
     * Generate URLs for pagination patterns.
     *
     * Example: generatePaginatedUrls('https://vendor.com/products?page={page}', 1, 10)
     */
    public static function generatePaginatedUrls(string $urlPattern, int $startPage, int $endPage): array
    {
        $urls = [];

        for ($page = $startPage; $page <= $endPage; $page++) {
            $urls[] = str_replace('{page}', $page, $urlPattern);
        }

        return $urls;
    }

    /**
     * Generate URLs from a list of SKUs or product IDs.
     *
     * Example: generateUrlsFromIds('https://vendor.com/products/{id}', ['12345', '67890'])
     */
    public static function generateUrlsFromIds(string $urlPattern, array $ids): array
    {
        return array_map(function ($id) use ($urlPattern) {
            return str_replace('{id}', $id, $urlPattern);
        }, $ids);
    }
}
