<?php

namespace App\Console\Commands;

use App\Mcp\Servers\ChromeDevTools\Skills\MultiPageScrapeSkill;
use App\Models\ScrapedProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ScrapeCompetitorCatalog extends Command
{
    protected $signature = 'scrape:catalog
                            {vendor : Vendor domain or config name (e.g., competitor.com or nsn-parts)}
                            {--urls= : Comma-separated list of URLs to scrape}
                            {--file= : Path to file containing URLs (one per line)}
                            {--sitemap= : URL to sitemap.xml to parse}
                            {--pages= : Page range for pagination (e.g., 1-50)}
                            {--limit=1000 : Maximum number of products to scrape}
                            {--delay=3000 : Delay between requests in milliseconds}
                            {--resume : Resume from previous scraping session}
                            {--session : Use saved login session cookies}
                            {--export : Export results to CSV after completion}';

    protected $description = 'Scrape competitor product catalogs (supports mil-spec parts sites). See documentation/automation/SCRAPING_QUICK_REFERENCE.md for examples';

    protected MultiPageScrapeSkill $skill;

    protected int $totalScraped = 0;

    protected int $totalFailed = 0;

    public function handle(): int
    {
        $vendor = $this->argument('vendor');
        $this->skill = new MultiPageScrapeSkill;

        $this->info("🚀 Starting catalog scrape for: {$vendor}");
        $this->newLine();

        // Get vendor configuration
        $config = $this->getVendorConfig($vendor);

        // Generate URL list
        $urls = $this->generateUrlList($vendor, $config);

        if ($urls->isEmpty()) {
            $this->error('No URLs found to scrape. Please provide --urls, --file, --sitemap, or --pages option.');

            return self::FAILURE;
        }

        // Apply limit
        $limit = (int) $this->option('limit');
        if ($urls->count() > $limit) {
            $this->warn("Limiting scrape to {$limit} products (found {$urls->count()})");
            $urls = $urls->take($limit);
        }

        $this->info("📋 Found {$urls->count()} products to scrape");
        $this->newLine();

        // Confirm before large scrapes
        if ($urls->count() > 100 && ! $this->option('resume')) {
            if (! $this->confirm('This will scrape '.$urls->count().' products. Continue?', true)) {
                return self::SUCCESS;
            }
        }

        // Start scraping
        $startTime = now();
        $this->info('⏳ Scraping in progress...');
        $this->newLine();

        $result = $this->skill->scrape([
            'urls' => $urls->toArray(),
            'use_session' => $this->option('session'),
            'resume' => $this->option('resume'),
            'save_html' => false, // Saves disk space for large batches
            'delay_ms' => (int) $this->option('delay'),
            'title_selector' => $config['title_selector'] ?? null,
            'sku_selector' => $config['sku_selector'] ?? null,
            'price_selector' => $config['price_selector'] ?? null,
        ]);

        $duration = $startTime->diffForHumans(now(), true);

        // Save results to database
        $this->saveResultsToDatabase($result, $vendor);

        // Display summary
        $this->displaySummary($result, $duration);

        // Export if requested
        if ($this->option('export')) {
            $this->exportResults($vendor);
        }

        // Show next steps
        $this->showNextSteps();

        return self::SUCCESS;
    }

    protected function getVendorConfig(string $vendor): array
    {
        $configPath = config_path('scraping.php');

        if (! File::exists($configPath)) {
            return [
                'domain' => $vendor,
                'url_pattern' => null,
                'title_selector' => null,
                'sku_selector' => null,
                'price_selector' => null,
            ];
        }

        $configs = config('scraping.vendors', []);

        // Check if vendor matches a config key
        if (isset($configs[$vendor])) {
            return $configs[$vendor];
        }

        // Check if vendor matches a domain
        foreach ($configs as $config) {
            if (($config['domain'] ?? null) === $vendor) {
                return $config;
            }
        }

        // Return default config
        return [
            'domain' => $vendor,
            'url_pattern' => null,
            'title_selector' => null,
            'sku_selector' => null,
            'price_selector' => null,
        ];
    }

    protected function generateUrlList(string $vendor, array $config): \Illuminate\Support\Collection
    {
        // Priority 1: Explicit URL list
        if ($urlList = $this->option('urls')) {
            $this->line('📍 Using provided URL list');

            return collect(explode(',', $urlList))->map(fn ($url) => trim($url));
        }

        // Priority 2: File with URLs
        if ($filePath = $this->option('file')) {
            $this->line('📁 Reading URLs from file: '.$filePath);

            if (! File::exists($filePath)) {
                $this->error("File not found: {$filePath}");

                return collect();
            }

            return collect(File::lines($filePath))
                ->map(fn ($line) => trim($line))
                ->filter(fn ($line) => ! empty($line) && str_starts_with($line, 'http'));
        }

        // Priority 3: Sitemap parsing
        if ($sitemapUrl = $this->option('sitemap')) {
            $this->line('🗺️  Parsing sitemap: '.$sitemapUrl);

            return $this->parseSitemap($sitemapUrl);
        }

        // Priority 4: Pagination pattern
        if ($pageRange = $this->option('pages')) {
            $this->line('📄 Generating URLs from pagination');

            return $this->generatePaginatedUrls($vendor, $config, $pageRange);
        }

        return collect();
    }

    protected function parseSitemap(string $sitemapUrl): \Illuminate\Support\Collection
    {
        try {
            $xml = simplexml_load_file($sitemapUrl);

            if (! $xml) {
                $this->error('Failed to parse sitemap');

                return collect();
            }

            $urls = collect();

            // Handle sitemap index (links to other sitemaps)
            if (isset($xml->sitemap)) {
                foreach ($xml->sitemap as $sitemap) {
                    $subSitemapUrl = (string) $sitemap->loc;
                    $this->line("  ↳ Found sub-sitemap: {$subSitemapUrl}");
                    $urls = $urls->merge($this->parseSitemap($subSitemapUrl));
                }
            }

            // Handle regular sitemap (product URLs)
            if (isset($xml->url)) {
                foreach ($xml->url as $url) {
                    $urls->push((string) $url->loc);
                }
            }

            return $urls;
        } catch (\Exception $e) {
            $this->error("Sitemap parsing failed: {$e->getMessage()}");

            return collect();
        }
    }

    protected function generatePaginatedUrls(string $vendor, array $config, string $pageRange): \Illuminate\Support\Collection
    {
        [$start, $end] = explode('-', $pageRange);
        $start = (int) $start;
        $end = (int) $end;

        $urlPattern = $config['url_pattern'] ?? "https://{$vendor}/products?page={page}";

        $urls = MultiPageScrapeSkill::generatePaginatedUrls($urlPattern, $start, $end);

        return collect($urls);
    }

    protected function saveResultsToDatabase(array $result, string $vendor): void
    {
        $this->line('💾 Saving to database...');

        $bar = $this->output->createProgressBar(count($result['products'] ?? []));
        $bar->start();

        foreach ($result['products'] as $product) {
            if (! $product) {
                $bar->advance();

                continue;
            }

            try {
                // Check if already exists
                $existing = ScrapedProduct::where('source_url', $product['url'] ?? null)->first();

                if ($existing) {
                    // Update existing record
                    $existing->update([
                        'title' => $product['title'] ?? $existing->title,
                        'sku' => $product['sku'] ?? $existing->sku,
                        'price' => $product['price'] ?? $existing->price,
                        'price_numeric' => $product['price_numeric'] ?? $existing->price_numeric,
                        'raw_data' => array_merge($existing->raw_data ?? [], ['last_scraped' => now()->toIso8601String()]),
                    ]);
                } else {
                    // Create new record
                    ScrapedProduct::create([
                        'source_url' => $product['url'] ?? null,
                        'vendor_domain' => ScrapedProduct::extractVendorDomain($product['url'] ?? ''),
                        'title' => $product['title'] ?? null,
                        'sku' => $product['sku'] ?? null,
                        'price' => $product['price'] ?? null,
                        'price_numeric' => $product['price_numeric'] ?? null,
                        'status' => 'pending',
                        'raw_data' => $product,
                    ]);
                }

                $this->totalScraped++;
            } catch (\Exception $e) {
                $this->totalFailed++;
                // Continue processing
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function displaySummary(array $result, string $duration): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('                    SCRAPING COMPLETE                  ');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total URLs Processed', $result['summary']['processed'] ?? 0],
                ['✓ Successfully Scraped', $result['summary']['successful'] ?? 0],
                ['✗ Failed', $result['summary']['failed'] ?? 0],
                ['💾 Saved to Database', $this->totalScraped],
                ['⏱  Duration', $duration],
            ]
        );

        $this->newLine();

        if (! empty($result['errors'])) {
            $this->warn('⚠️  Failed URLs:');
            foreach (array_slice($result['errors'], 0, 5) as $error) {
                $this->line("  • {$error['url']}: {$error['error']}");
            }
            if (count($result['errors']) > 5) {
                $this->line('  ... and '.(count($result['errors']) - 5).' more');
            }
            $this->newLine();
        }
    }

    protected function exportResults(string $vendor): void
    {
        $this->line('📊 Exporting to CSV...');

        $filename = 'scraped-'.str_replace('.', '-', $vendor).'-'.now()->format('Y-m-d-His').'.csv';
        $filepath = storage_path("app/{$filename}");

        $this->skill->exportToCsv($filepath);

        $this->info("✓ Exported to: {$filepath}");
        $this->newLine();
    }

    protected function showNextSteps(): void
    {
        $this->info('📋 Next Steps:');
        $this->line('  1. Review scraped data in Product Import Wizard');
        $this->line('  2. Filter by vendor and status to see new products');
        $this->line('  3. Export as CSV for price analysis');
        $this->line('  4. Bulk import products with your desired markup');
        $this->newLine();
        $this->line('  💡 Tip: Run with --resume flag to continue interrupted scrapes');
        $this->line('  📖 Full documentation: documentation/automation/COMPETITOR_SCRAPING_GUIDE.md');
        $this->line('  ⚡ Quick reference: documentation/automation/SCRAPING_QUICK_REFERENCE.md');
        $this->newLine();
    }
}
