<?php

namespace App\Mcp\Servers\ChromeDevTools\Tools;

use App\Mcp\Servers\ChromeDevTools\Services\BrowserService;
use App\Mcp\Servers\Tool;
use Illuminate\Support\Facades\Log;

class ProductDataScraperTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'product-data-scraper';

    /**
     * The tool's description.
     */
    protected string $description = 'Scrape product data (title, SKU, price, NSN, CAGE code, mil-spec) from vendor product pages with intelligent auto-detection';

    /**
     * Define the tool's input schema.
     */
    protected function inputSchema(): array
    {
        return [
            'url' => [
                'type' => 'string',
                'description' => 'The URL of the product page to scrape',
                'required' => true,
            ],
            'use_session' => [
                'type' => 'boolean',
                'description' => 'Load saved session cookies from State/session.json (default: false)',
                'required' => false,
            ],
            'save_html' => [
                'type' => 'boolean',
                'description' => 'Save HTML snapshot to State/html-cache/ for debugging (default: true)',
                'required' => false,
            ],
            'title_selector' => [
                'type' => 'string',
                'description' => 'Custom CSS selector for product title (optional - uses auto-detection if not provided)',
                'required' => false,
            ],
            'sku_selector' => [
                'type' => 'string',
                'description' => 'Custom CSS selector for product SKU (optional - uses auto-detection if not provided)',
                'required' => false,
            ],
            'price_selector' => [
                'type' => 'string',
                'description' => 'Custom CSS selector for product price (optional - uses auto-detection if not provided)',
                'required' => false,
            ],
            'nsn_selector' => [
                'type' => 'string',
                'description' => 'Custom CSS selector for NSN (National Stock Number) (optional - uses auto-detection if not provided)',
                'required' => false,
            ],
            'cage_selector' => [
                'type' => 'string',
                'description' => 'Custom CSS selector for CAGE code (optional - uses auto-detection if not provided)',
                'required' => false,
            ],
            'milspec_selector' => [
                'type' => 'string',
                'description' => 'Custom CSS selector for mil-spec designation (optional - uses auto-detection if not provided)',
                'required' => false,
            ],
            'timeout' => [
                'type' => 'integer',
                'description' => 'Timeout in milliseconds (default: 30000)',
                'required' => false,
            ],
        ];
    }

    /**
     * Execute the tool.
     */
    public function execute(array $inputs): string
    {
        $url = $inputs['url'];
        $useSession = $inputs['use_session'] ?? false;
        $saveHtml = $inputs['save_html'] ?? true;
        $titleSelector = $inputs['title_selector'] ?? null;
        $skuSelector = $inputs['sku_selector'] ?? null;
        $priceSelector = $inputs['price_selector'] ?? null;
        $nsnSelector = $inputs['nsn_selector'] ?? null;
        $cageSelector = $inputs['cage_selector'] ?? null;
        $milspecSelector = $inputs['milspec_selector'] ?? null;
        $timeout = $inputs['timeout'] ?? 30000;

        try {
            // Use shared browser service
            $page = BrowserService::getPage();

            // Load session cookies if requested
            if ($useSession) {
                $this->loadSessionCookies($page);
            }

            // Navigate to product page
            $page->navigate($url)->waitForNavigation('networkIdle', $timeout);

            // Wait for page to be ready
            $this->waitForPageReady($page, $timeout);

            // Extract product data
            $product = $this->extractProductData($page, $titleSelector, $skuSelector, $priceSelector, $nsnSelector, $cageSelector, $milspecSelector);

            // Save HTML snapshot if requested
            $htmlPath = null;
            if ($saveHtml) {
                $htmlPath = $this->saveHtmlSnapshot($page, $url);
            }

            // Check if any data was extracted
            if (empty($product['title']) && empty($product['sku']) && empty($product['price'])) {
                return json_encode([
                    'success' => false,
                    'error' => 'No product data could be extracted from the page',
                    'url' => $url,
                    'product' => $product,
                    'html_cache' => $htmlPath,
                ], JSON_PRETTY_PRINT);
            }

            return json_encode([
                'success' => true,
                'url' => $url,
                'product' => $product,
                'html_cache' => $htmlPath,
                'scraped_at' => now()->toIso8601String(),
            ], JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            Log::error('Product scraping failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'url' => $url,
            ], JSON_PRETTY_PRINT);
        }
    }

    /**
     * Load session cookies from State/session.json.
     */
    protected function loadSessionCookies($page): void
    {
        $sessionFile = __DIR__.'/../State/session.json';

        if (! file_exists($sessionFile)) {
            Log::warning('Session file not found, skipping cookie load', [
                'file' => $sessionFile,
            ]);

            return;
        }

        try {
            $sessionData = json_decode(file_get_contents($sessionFile), true);
            $cookies = $sessionData['cookies'] ?? [];

            if (empty($cookies)) {
                return;
            }

            // Build JavaScript to set cookies
            $cookieScript = $this->buildCookieScript($cookies);
            $page->evaluate($cookieScript);

            Log::info('Session cookies loaded', [
                'cookie_count' => count($cookies),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to load session cookies', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build JavaScript to set cookies.
     */
    protected function buildCookieScript(array $cookies): string
    {
        $cookieStatements = [];

        foreach ($cookies as $name => $value) {
            $nameEscaped = addslashes($name);
            $valueEscaped = addslashes($value);
            $cookieStatements[] = "document.cookie = '{$nameEscaped}={$valueEscaped}; path=/';";
        }

        return implode("\n", $cookieStatements);
    }

    /**
     * Wait for the page to be fully ready.
     */
    protected function waitForPageReady($page, int $timeout): void
    {
        $startTime = microtime(true);
        $timeoutSeconds = $timeout / 1000;

        while (microtime(true) - $startTime < $timeoutSeconds) {
            $ready = $page->evaluate('document.readyState === "complete"')->getReturnValue();

            if ($ready) {
                // Give it a moment for any dynamic content
                usleep(500000); // 500ms

                return;
            }

            usleep(100000); // 100ms
        }
    }

    /**
     * Extract product data from the page.
     */
    protected function extractProductData($page, ?string $titleSelector, ?string $skuSelector, ?string $priceSelector, ?string $nsnSelector, ?string $cageSelector, ?string $milspecSelector): array
    {
        // Build extraction script
        $extractionScript = $this->buildExtractionScript($titleSelector, $skuSelector, $priceSelector, $nsnSelector, $cageSelector, $milspecSelector);

        // Execute extraction
        $result = $page->evaluate($extractionScript)->getReturnValue();

        // Parse result
        if (is_string($result)) {
            $result = json_decode($result, true);
        }

        return [
            'title' => $this->cleanText($result['title'] ?? null),
            'sku' => $this->cleanText($result['sku'] ?? null),
            'price' => $this->cleanText($result['price'] ?? null),
            'price_numeric' => $this->extractNumericPrice($result['price'] ?? null),
            'nsn' => $this->validateNsn($this->cleanText($result['nsn'] ?? null)),
            'cage_code' => $this->validateCageCode($this->cleanText($result['cage_code'] ?? null)),
            'milspec' => $this->validateMilspec($this->cleanText($result['milspec'] ?? null)),
        ];
    }

    /**
     * Build JavaScript extraction script.
     */
    protected function buildExtractionScript(?string $titleSelector, ?string $skuSelector, ?string $priceSelector, ?string $nsnSelector, ?string $cageSelector, ?string $milspecSelector): string
    {
        // Build selector lists with custom selectors first, then auto-detection patterns
        $titleSelectors = $titleSelector
            ? "['".addslashes($titleSelector)."']"
            : "['h1', '[itemprop=\"name\"]', '.product-title', '.product-name', 'h1.title', '.product-detail-title', '[data-product-name]']";

        $skuSelectors = $skuSelector
            ? "['".addslashes($skuSelector)."']"
            : "['[itemprop=\"sku\"]', '.sku', '.product-code', '.product-sku', '[data-sku]', '[data-product-sku]', '.part-number', '.item-number']";

        $priceSelectors = $priceSelector
            ? "['".addslashes($priceSelector)."']"
            : "['[itemprop=\"price\"]', '.price', '.product-price', '.price-value', '[data-price]', '.sale-price', '.current-price', '.offer-price', 'span.price', 'div.price']";

        $nsnSelectors = $nsnSelector
            ? "['".addslashes($nsnSelector)."']"
            : "['.nsn', '.nsn-number', '[data-nsn]', '.national-stock-number', '[aria-label*=\"NSN\"]']";

        $cageSelectors = $cageSelector
            ? "['".addslashes($cageSelector)."']"
            : "['.cage-code', '.cage', '[data-cage]', '[aria-label*=\"CAGE\"]']";

        $milspecSelectors = $milspecSelector
            ? "['".addslashes($milspecSelector)."']"
            : "['.mil-spec', '.mil-std', '.specification', '[data-milspec]', '.compliance']";

        return <<<JAVASCRIPT
(function() {
    try {
        // Helper function to find element by selectors
        function findBySelectors(selectors) {
            for (const selector of selectors) {
                const element = document.querySelector(selector);
                if (element) {
                    return element.textContent || element.innerText || element.getAttribute('content') || '';
                }
            }
            return null;
        }

        // Helper function to find SKU in text content
        function findSkuInText() {
            const bodyText = document.body.innerText || '';

            // Look for common SKU patterns
            const patterns = [
                /SKU[:\s]+([A-Z0-9-]+)/i,
                /Part\s*#[:\s]+([A-Z0-9-]+)/i,
                /Item\s*#[:\s]+([A-Z0-9-]+)/i,
                /Model[:\s]+([A-Z0-9-]+)/i,
                /Product\s*Code[:\s]+([A-Z0-9-]+)/i
            ];

            for (const pattern of patterns) {
                const match = bodyText.match(pattern);
                if (match && match[1]) {
                    return match[1].trim();
                }
            }

            return null;
        }

        // Helper function to find NSN in text content
        function findNsnInText() {
            const bodyText = document.body.innerText || '';

            // NSN format: 1234-56-789-0123 (4-2-3-4 digits)
            const patterns = [
                /NSN[:\s]+(\d{4}-\d{2}-\d{3}-\d{4})/i,
                /National\s+Stock\s+Number[:\s]+(\d{4}-\d{2}-\d{3}-\d{4})/i,
                /(\d{4}-\d{2}-\d{3}-\d{4})/
            ];

            for (const pattern of patterns) {
                const match = bodyText.match(pattern);
                if (match && match[1]) {
                    return match[1].trim();
                }
            }

            return null;
        }

        // Helper function to find CAGE code in text content
        function findCageInText() {
            const bodyText = document.body.innerText || '';

            // CAGE code format: 5 alphanumeric characters
            const patterns = [
                /CAGE\s*Code[:\s]+([A-Z0-9]{5})/i,
                /CAGE[:\s]+([A-Z0-9]{5})/i,
                /Manufacturer\s*Code[:\s]+([A-Z0-9]{5})/i
            ];

            for (const pattern of patterns) {
                const match = bodyText.match(pattern);
                if (match && match[1]) {
                    return match[1].trim().toUpperCase();
                }
            }

            return null;
        }

        // Helper function to find mil-spec in text content
        function findMilspecInText() {
            const bodyText = document.body.innerText || '';

            // Mil-spec patterns: MIL-STD-810G, MIL-SPEC-1234, etc.
            const patterns = [
                /MIL-(STD|SPEC|PRF|DTL|HDBK)-\d+[A-Z]?/i,
                /Mil\s*Spec[:\s]+(MIL-[A-Z]+-\d+[A-Z]?)/i,
                /Military\s*Specification[:\s]+(MIL-[A-Z]+-\d+[A-Z]?)/i
            ];

            for (const pattern of patterns) {
                const match = bodyText.match(pattern);
                if (match) {
                    return (match[1] || match[0]).trim().toUpperCase();
                }
            }

            return null;
        }

        // Extract title
        const titleSelectors = {$titleSelectors};
        let title = findBySelectors(titleSelectors);

        // Extract SKU
        const skuSelectors = {$skuSelectors};
        let sku = findBySelectors(skuSelectors);

        // If SKU not found by selectors, try finding it in text
        if (!sku) {
            sku = findSkuInText();
        }

        // Extract price
        const priceSelectors = {$priceSelectors};
        let price = findBySelectors(priceSelectors);

        // Extract NSN
        const nsnSelectors = {$nsnSelectors};
        let nsn = findBySelectors(nsnSelectors);

        // If NSN not found by selectors, try finding it in text
        if (!nsn) {
            nsn = findNsnInText();
        }

        // Extract CAGE code
        const cageSelectors = {$cageSelectors};
        let cage_code = findBySelectors(cageSelectors);

        // If CAGE not found by selectors, try finding it in text
        if (!cage_code) {
            cage_code = findCageInText();
        }

        // Extract mil-spec
        const milspecSelectors = {$milspecSelectors};
        let milspec = findBySelectors(milspecSelectors);

        // If mil-spec not found by selectors, try finding it in text
        if (!milspec) {
            milspec = findMilspecInText();
        }

        return JSON.stringify({
            title: title,
            sku: sku,
            price: price,
            nsn: nsn,
            cage_code: cage_code,
            milspec: milspec
        });
    } catch (error) {
        return JSON.stringify({
            title: null,
            sku: null,
            price: null,
            nsn: null,
            cage_code: null,
            milspec: null,
            error: error.message
        });
    }
})();
JAVASCRIPT;
    }

    /**
     * Clean extracted text.
     */
    protected function cleanText(?string $text): ?string
    {
        if (empty($text)) {
            return null;
        }

        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        // Trim
        $text = trim($text);

        return $text ?: null;
    }

    /**
     * Extract numeric price value from price string.
     */
    protected function extractNumericPrice(?string $priceText): ?float
    {
        if (empty($priceText)) {
            return null;
        }

        // Remove currency symbols and common text
        $priceText = preg_replace('/[^0-9.,\s]/', '', $priceText);

        // Remove spaces
        $priceText = str_replace(' ', '', $priceText);

        // Handle different decimal separators
        // If there are multiple periods or commas, assume the last one is the decimal separator
        $periodCount = substr_count($priceText, '.');
        $commaCount = substr_count($priceText, ',');

        if ($periodCount > 1 && $commaCount === 0) {
            // European format with periods as thousands separator: 1.234.56
            // This is unusual, so we'll treat the last period as decimal
            $lastPeriod = strrpos($priceText, '.');
            $priceText = str_replace('.', '', substr($priceText, 0, $lastPeriod)).'.'.substr($priceText, $lastPeriod + 1);
        } elseif ($commaCount > 1 && $periodCount === 0) {
            // Format with commas as thousands separator: 1,234,56
            $lastComma = strrpos($priceText, ',');
            $priceText = str_replace(',', '', substr($priceText, 0, $lastComma)).'.'.substr($priceText, $lastComma + 1);
        } elseif ($periodCount === 1 && $commaCount === 1) {
            // Determine which is the decimal separator based on position
            $periodPos = strpos($priceText, '.');
            $commaPos = strpos($priceText, ',');

            if ($periodPos < $commaPos) {
                // Format: 1.234,56 (European)
                $priceText = str_replace('.', '', $priceText);
                $priceText = str_replace(',', '.', $priceText);
            } else {
                // Format: 1,234.56 (US)
                $priceText = str_replace(',', '', $priceText);
            }
        } elseif ($commaCount === 1 && $periodCount === 0) {
            // Could be European decimal or thousands separator
            // If there are 2 digits after comma, treat as decimal
            if (preg_match('/,\d{2}$/', $priceText)) {
                $priceText = str_replace(',', '.', $priceText);
            } else {
                // Otherwise treat as thousands separator
                $priceText = str_replace(',', '', $priceText);
            }
        }

        // Convert to float
        $price = (float) $priceText;

        return $price > 0 ? $price : null;
    }

    /**
     * Validate and normalize NSN (National Stock Number).
     */
    protected function validateNsn(?string $nsn): ?string
    {
        if (empty($nsn)) {
            return null;
        }

        // NSN format: 1234-56-789-0123 (4-2-3-4 digits)
        if (preg_match('/^(\d{4})-?(\d{2})-?(\d{3})-?(\d{4})$/', $nsn, $matches)) {
            return "{$matches[1]}-{$matches[2]}-{$matches[3]}-{$matches[4]}";
        }

        return null;
    }

    /**
     * Validate and normalize CAGE code.
     */
    protected function validateCageCode(?string $cage): ?string
    {
        if (empty($cage)) {
            return null;
        }

        // CAGE code format: 5 alphanumeric characters
        $cage = strtoupper(preg_replace('/[^A-Z0-9]/', '', $cage));

        if (preg_match('/^[A-Z0-9]{5}$/', $cage)) {
            return $cage;
        }

        return null;
    }

    /**
     * Validate and normalize mil-spec designation.
     */
    protected function validateMilspec(?string $milspec): ?string
    {
        if (empty($milspec)) {
            return null;
        }

        // Mil-spec format: MIL-STD-810G, MIL-SPEC-1234, etc.
        if (preg_match('/^MIL-(STD|SPEC|PRF|DTL|HDBK)-\d+[A-Z]?$/i', $milspec)) {
            return strtoupper($milspec);
        }

        return null;
    }

    /**
     * Save HTML snapshot to State/html-cache/.
     */
    protected function saveHtmlSnapshot($page, string $url): ?string
    {
        try {
            $cacheDir = __DIR__.'/../State/html-cache';

            // Ensure cache directory exists
            if (! file_exists($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }

            // Get HTML content
            $html = $page->evaluate('document.documentElement.outerHTML')->getReturnValue();

            // Generate filename from URL and timestamp
            $urlHash = md5($url);
            $timestamp = now()->format('Y-m-d_His');
            $filename = "{$timestamp}_{$urlHash}.html";
            $filepath = $cacheDir.'/'.$filename;

            // Save HTML
            file_put_contents($filepath, $html);

            Log::info('HTML snapshot saved', [
                'url' => $url,
                'file' => $filename,
                'size' => strlen($html),
            ]);

            return $filepath;
        } catch (\Exception $e) {
            Log::warning('Failed to save HTML snapshot', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
