<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Vendor Scraping Configurations
    |--------------------------------------------------------------------------
    |
    | Define vendor-specific scraping configurations for competitor catalogs.
    | Each vendor can have custom CSS selectors, URL patterns, and settings.
    |
    | Configuration Options:
    | - domain: The vendor's domain name (e.g., 'competitor.com')
    | - url_pattern: Pagination URL pattern with {page} placeholder
    | - title_selector: CSS selector for product title/name
    | - sku_selector: CSS selector for SKU/part number
    | - price_selector: CSS selector for price
    | - nsn_selector: CSS selector for NSN (National Stock Number)
    | - cage_selector: CSS selector for CAGE code
    | - milspec_selector: CSS selector for mil-spec designation
    |
    */

    'vendors' => [

        // Example: Generic NSN Parts Vendor
        'nsn-parts' => [
            'domain' => 'nsn-parts.com',
            'url_pattern' => 'https://nsn-parts.com/catalog?page={page}',
            'title_selector' => '[itemprop="name"], .product-title, h1.title',
            'sku_selector' => '[itemprop="sku"], .part-number, .sku',
            'price_selector' => '[itemprop="price"], .price, .product-price',
            'nsn_selector' => '.nsn-number, [data-nsn], .national-stock-number',
            'cage_selector' => '.cage-code, [data-cage]',
            'milspec_selector' => '.mil-spec, .specification',
        ],

        // Example: Military Surplus Parts
        'mil-surplus' => [
            'domain' => 'milsurplus-parts.com',
            'url_pattern' => 'https://milsurplus-parts.com/products/page/{page}',
            'title_selector' => 'h1.product-name, .item-title',
            'sku_selector' => '.part-num, .item-number',
            'price_selector' => 'span.price, .product-price',
            'nsn_selector' => '.nsn, [aria-label="NSN"]',
            'cage_selector' => '.cage-code',
        ],

        // Example: Aerospace Components Supplier
        'aero-components' => [
            'domain' => 'aerocomponents.com',
            'url_pattern' => 'https://aerocomponents.com/browse?p={page}',
            'title_selector' => '[data-product-name], h1.product-title',
            'sku_selector' => '[data-part-number], .pn',
            'price_selector' => '[data-price], .pricing',
            'nsn_selector' => '[data-nsn]',
            'milspec_selector' => '.compliance, .mil-std',
        ],

        // Example: Generic Competitor (No mil-spec)
        'generic-competitor' => [
            'domain' => 'competitor.com',
            'url_pattern' => 'https://competitor.com/products?page={page}',
            'title_selector' => 'h1[itemprop="name"], .product-name',
            'sku_selector' => '[itemprop="sku"], .product-sku',
            'price_selector' => '[itemprop="price"], .product-price',
        ],

        // Example: Industrial Supplies
        'industrial-supply' => [
            'domain' => 'industrialsupply.com',
            'url_pattern' => 'https://industrialsupply.com/catalog/p{page}',
            'title_selector' => 'h1.product-heading',
            'sku_selector' => '.manufacturer-part-number, .mpn',
            'price_selector' => '.your-price, .sale-price',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Default Selectors
    |--------------------------------------------------------------------------
    |
    | Fallback selectors used when vendor-specific selectors are not defined
    | or when scraping a vendor without a configuration.
    |
    */

    'default_selectors' => [
        'title' => [
            '[itemprop="name"]',
            'h1.product-title',
            'h1.product-name',
            '.product-title',
            'h1',
        ],
        'sku' => [
            '[itemprop="sku"]',
            '.product-sku',
            '.part-number',
            '.sku',
            '[data-sku]',
            '[data-part-number]',
        ],
        'price' => [
            '[itemprop="price"]',
            '.product-price',
            '.price',
            '[data-price]',
            '.sale-price',
        ],
        'nsn' => [
            '.nsn',
            '.nsn-number',
            '[data-nsn]',
            '.national-stock-number',
            '[aria-label*="NSN"]',
        ],
        'cage' => [
            '.cage-code',
            '[data-cage]',
            '.cage',
            '[aria-label*="CAGE"]',
        ],
        'milspec' => [
            '.mil-spec',
            '.mil-std',
            '.specification',
            '[data-milspec]',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scraping Defaults
    |--------------------------------------------------------------------------
    |
    | Default settings for scraping operations.
    |
    */

    'defaults' => [
        'delay_ms' => 3000,           // Delay between requests (milliseconds)
        'timeout_ms' => 30000,        // Page load timeout (milliseconds)
        'save_html' => true,          // Save HTML snapshots
        'use_session' => false,       // Use saved session cookies by default
        'max_retries' => 3,           // Maximum retry attempts for failed requests
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting to avoid overwhelming vendor servers.
    |
    */

    'rate_limiting' => [
        'enabled' => true,
        'requests_per_minute' => 20,
        'adaptive' => true,            // Slow down if errors are detected
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Validation Patterns
    |--------------------------------------------------------------------------
    |
    | Regular expressions for validating extracted data.
    |
    */

    'validation_patterns' => [
        'nsn' => '/^\d{4}-\d{2}-\d{3}-\d{4}$/',                    // Format: 1234-56-789-0123
        'cage' => '/^[A-Z0-9]{5}$/',                               // 5-character alphanumeric
        'milspec' => '/^MIL-(STD|SPEC|PRF|DTL|HDBK)-\d+[A-Z]?$/', // Format: MIL-STD-810G
    ],
];
