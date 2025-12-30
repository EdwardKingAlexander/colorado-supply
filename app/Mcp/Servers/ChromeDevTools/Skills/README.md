# ChromeDevTools Skills

This directory contains reusable skills that demonstrate complex workflows by combining multiple MCP tools.

## What Are Skills?

Skills are **reusable patterns** that show how to:
- Combine multiple tools into workflows
- Handle state and persistence
- Implement error recovery
- Build higher-level abstractions

Unlike Tools (which are called by MCP clients), Skills are **used within your Laravel application** or **referenced by agents** building complex automations.

## Available Skills

### MultiPageScrapeSkill

**Purpose**: Scrape product data from multiple URLs with progress tracking and resumability.

**Features**:
- Loop through paginated URLs or product lists
- Call ProductDataScraperTool for each URL
- Save incremental progress to `State/scrape-progress.json`
- Resume interrupted scrapes
- Export results to CSV
- Polite scraping with configurable delays

**Usage Example**:
```php
use App\Mcp\Servers\ChromeDevTools\Skills\MultiPageScrapeSkill;

$skill = new MultiPageScrapeSkill();

// Scrape multiple product URLs
$result = $skill->scrape([
    'urls' => [
        'https://vendor.com/products/12345',
        'https://vendor.com/products/67890',
        'https://vendor.com/products/11111',
    ],
    'use_session' => true,      // Load cookies from session.json
    'resume' => true,            // Resume from previous run
    'save_html' => true,         // Cache HTML for debugging
    'delay_ms' => 2000,          // Wait 2s between requests
]);

// Export to CSV
$skill->exportToCsv(storage_path('app/scraped-products.csv'));
```

**Helper Methods**:
```php
// Generate paginated URLs
$urls = MultiPageScrapeSkill::generatePaginatedUrls(
    'https://vendor.com/catalog?page={page}',
    1,
    10
);

// Generate URLs from SKU list
$urls = MultiPageScrapeSkill::generateUrlsFromIds(
    'https://vendor.com/products/{id}',
    ['SKU-001', 'SKU-002', 'SKU-003']
);

// Check progress
$progress = $skill->getProgress();

// Clear progress and start fresh
$skill->clearProgress();
```

## Creating New Skills

When you discover a useful pattern:

1. **Create a new PHP class** in this directory
2. **Add comprehensive documentation** in docblocks
3. **Include usage examples** in comments
4. **Update SKILL.md** in the parent directory
5. **Handle errors gracefully**
6. **Save state for resumability** when appropriate

### Skill Template

```php
<?php

namespace App\Mcp\Servers\ChromeDevTools\Skills;

/**
 * [Skill Name]
 *
 * Purpose: [What this skill does]
 *
 * Usage Example:
 * ```php
 * $skill = new YourSkill();
 * $result = $skill->execute($config);
 * ```
 */
class YourSkill
{
    public function execute(array $config): array
    {
        // Your implementation
    }
}
```

## Best Practices

1. **State Management**: Use `State/` directory for persistence
2. **Error Handling**: Always catch exceptions and continue when possible
3. **Progress Tracking**: Save incremental progress for long operations
4. **Resource Cleanup**: Close connections, free memory
5. **Polite Scraping**: Add delays, respect robots.txt
6. **Documentation**: Document all parameters and return values
7. **Testability**: Write code that can be unit tested

## Integration with MCP Tools

Skills can call MCP Tools directly:

```php
use App\Mcp\Servers\ChromeDevTools\Tools\ProductDataScraperTool;

$tool = new ProductDataScraperTool();
$result = $tool->execute([
    'url' => 'https://example.com/product',
]);
```

## State Files

Skills save state to `../State/` directory:
- `scrape-progress.json` - Multi-page scrape progress
- Add your own state files as needed

All state files are gitignored by default for security.
