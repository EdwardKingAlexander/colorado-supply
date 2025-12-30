# ChromeDevTools Server Skills

This document tracks reusable skills and patterns for browser automation tasks.

## Available Skills

Skills are saved as PHP files in the `Skills/` directory and can be referenced by agents for complex operations.

### Core Patterns

**Vendor Portal Authentication**
Tool: `vendor-portal-login`

Authenticate to vendor portals and persist session cookies for subsequent operations. The tool automatically:
- Detects common login form patterns (username/email/password fields)
- Fills credentials and submits the form
- Saves cookies to `State/session.json`
- Maintains logged-in state across tool invocations

Example usage:
```json
{
  "url": "https://vendor.example.com/login",
  "username": "your-username",
  "password": "your-password"
}
```

Saved session includes:
- All cookies from the authenticated session
- Login timestamp
- Current URL after login
- Username for reference

**Product Data Scraping**
Tool: `product-data-scraper`

Extract structured product data (title, SKU, price) from vendor product pages with intelligent auto-detection. The tool:
- Automatically detects common product data patterns
- Supports custom CSS selectors for non-standard pages
- Loads session cookies for authenticated scraping
- Saves raw HTML to `State/html-cache/` for debugging
- Returns structured JSON data

Example usage (auto-detect):
```json
{
  "url": "https://vendor.example.com/products/12345"
}
```

Example usage (custom selectors):
```json
{
  "url": "https://vendor.example.com/products/12345",
  "title_selector": "h1.product-name",
  "sku_selector": "[data-product-sku]",
  "price_selector": ".price-value"
}
```

Example usage (with authentication):
```json
{
  "url": "https://vendor.example.com/products/12345",
  "use_session": true
}
```

Extracted data includes:
- Product title
- SKU/part number
- Price (formatted string)
- Price (numeric value for calculations)
- Path to cached HTML file

**Composite Workflow: Login + Scrape Multiple Products**
1. Use `vendor-portal-login` to authenticate
2. Use `product-data-scraper` multiple times with `use_session: true`
3. All scrapes reuse the same browser and session
4. HTML cache tracks all scraped pages

## Advanced Skills

### MultiPageScrapeSkill
Location: `Skills/MultiPageScrapeSkill.php`

**Purpose**: Automate scraping of multiple product pages with progress tracking and resumability.

**Features**:
- Loop through paginated URLs or product lists
- Automatic progress tracking in `State/scrape-progress.json`
- Resume interrupted scrapes from last position
- Export results to CSV
- Polite scraping with configurable delays
- Error handling with detailed reporting

**Usage in Laravel**:
```php
use App\Mcp\Servers\ChromeDevTools\Skills\MultiPageScrapeSkill;

$skill = new MultiPageScrapeSkill();

// Scrape multiple pages with resume capability
$result = $skill->scrape([
    'urls' => [
        'https://vendor.com/products/12345',
        'https://vendor.com/products/67890',
        'https://vendor.com/products/11111',
    ],
    'use_session' => true,      // Use saved login cookies
    'resume' => true,            // Resume from last position
    'save_html' => true,         // Cache HTML for debugging
    'delay_ms' => 2000,          // 2 second delay between requests
]);

// Export to CSV
$skill->exportToCsv(storage_path('app/products.csv'));
```

**Helper Methods**:
```php
// Generate paginated URLs
$urls = MultiPageScrapeSkill::generatePaginatedUrls(
    'https://vendor.com/catalog?page={page}',
    1,    // start page
    50    // end page
);

// Generate URLs from SKU list
$skus = ['SKU-001', 'SKU-002', 'SKU-003'];
$urls = MultiPageScrapeSkill::generateUrlsFromIds(
    'https://vendor.com/products/{id}',
    $skus
);
```

**Complete Workflow Example**:
```php
// Step 1: Login to vendor portal
$loginTool = new VendorPortalLoginTool();
$loginTool->execute([
    'url' => 'https://vendor.com/login',
    'username' => config('vendors.username'),
    'password' => config('vendors.password'),
]);

// Step 2: Generate URLs for pages 1-20
$urls = MultiPageScrapeSkill::generatePaginatedUrls(
    'https://vendor.com/catalog?page={page}',
    1,
    20
);

// Step 3: Scrape all pages with resume capability
$skill = new MultiPageScrapeSkill();
$result = $skill->scrape([
    'urls' => $urls,
    'use_session' => true,
    'resume' => true,
    'delay_ms' => 3000,
]);

// Step 4: Export to CSV
$csvPath = $skill->exportToCsv(storage_path('app/vendor-catalog.csv'));

// Result summary
echo "Scraped {$result['summary']['successful']} products\n";
echo "Failed: {$result['summary']['failed']}\n";
echo "CSV exported to: {$csvPath}\n";
```

**Progress Tracking**:

If the scrape is interrupted, progress is saved to `State/scrape-progress.json`:
```json
{
  "total_urls": 100,
  "processed_count": 45,
  "success_count": 42,
  "error_count": 3,
  "last_updated": "2025-11-09T11:15:00+00:00",
  "results": { ... }
}
```

Simply run the same command again with `'resume' => true` to continue from where you left off.

**Navigation + Screenshot**
Navigate to a URL and capture a screenshot in one operation.

**Login + Action**
Authenticate to a website and perform actions while maintaining session state.

**Data Extraction Workflow**
Navigate to multiple pages, extract data, and aggregate results efficiently.

**Multi-Page Screenshots**
Capture screenshots of multiple pages in sequence using the shared browser instance.

## Creating New Skills

When you discover a useful pattern:

1. Save the reusable function in `Skills/[SkillName].php`
2. Document it here with:
   - Name and description
   - Usage example
   - Input/output specification
   - Any prerequisites

## State Management

Skills can use the State directory to:
- Cache authentication cookies
- Store session data
- Save intermediate results
- Track progress across operations

## Best Practices

- Use BrowserService for all browser operations
- Clean up resources after complex operations
- Document any assumptions or requirements
- Handle errors gracefully
- Log important operations for debugging
