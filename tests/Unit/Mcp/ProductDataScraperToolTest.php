<?php

declare(strict_types=1);

use App\Mcp\Servers\ChromeDevTools\Tools\ProductDataScraperTool;

beforeEach(function () {
    $this->tool = new ProductDataScraperTool;
});

describe('text cleaning', function () {
    it('removes excessive whitespace', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'cleanText');
        $method->setAccessible(true);

        $result = $method->invoke($this->tool, "Product  \n\n  Title  \t  Here");
        expect($result)->toBe('Product Title Here');
    });

    it('returns null for empty strings', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'cleanText');
        $method->setAccessible(true);

        expect($method->invoke($this->tool, ''))->toBeNull();
        expect($method->invoke($this->tool, '   '))->toBeNull();
        expect($method->invoke($this->tool, null))->toBeNull();
    });

    it('trims whitespace', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'cleanText');
        $method->setAccessible(true);

        $result = $method->invoke($this->tool, '  Trimmed Text  ');
        expect($result)->toBe('Trimmed Text');
    });
});

describe('numeric price extraction', function () {
    it('extracts US format prices', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'extractNumericPrice');
        $method->setAccessible(true);

        expect($method->invoke($this->tool, '$99.99'))->toBe(99.99);
        expect($method->invoke($this->tool, '$1,234.56'))->toBe(1234.56);
        expect($method->invoke($this->tool, 'USD 999.00'))->toBe(999.0);
    });

    it('extracts European format prices', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'extractNumericPrice');
        $method->setAccessible(true);

        expect($method->invoke($this->tool, '1.234,56 €'))->toBe(1234.56);
        expect($method->invoke($this->tool, '99,99'))->toBe(99.99);
    });

    it('handles prices without decimals', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'extractNumericPrice');
        $method->setAccessible(true);

        expect($method->invoke($this->tool, '$100'))->toBe(100.0);
        expect($method->invoke($this->tool, '1,000'))->toBe(1000.0);
    });

    it('handles prices with currency symbols', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'extractNumericPrice');
        $method->setAccessible(true);

        expect($method->invoke($this->tool, '€ 123.45'))->toBe(123.45);
        expect($method->invoke($this->tool, '¥ 1,234'))->toBe(1234.0);
        expect($method->invoke($this->tool, '£ 99.99'))->toBe(99.99);
    });

    it('returns null for invalid prices', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'extractNumericPrice');
        $method->setAccessible(true);

        expect($method->invoke($this->tool, ''))->toBeNull();
        expect($method->invoke($this->tool, null))->toBeNull();
        expect($method->invoke($this->tool, 'Call for price'))->toBeNull();
    });

    it('handles complex price formats', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'extractNumericPrice');
        $method->setAccessible(true);

        expect($method->invoke($this->tool, 'Price: $12,345.67'))->toBe(12345.67);
        expect($method->invoke($this->tool, 'From $49.99'))->toBe(49.99);
    });
});

describe('cookie script building', function () {
    it('generates valid cookie JavaScript', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'buildCookieScript');
        $method->setAccessible(true);

        $cookies = [
            'session_id' => 'abc123',
            'user_token' => 'xyz789',
        ];

        $script = $method->invoke($this->tool, $cookies);

        expect($script)->toContain("document.cookie = 'session_id=abc123; path=/';");
        expect($script)->toContain("document.cookie = 'user_token=xyz789; path=/';");
    });

    it('escapes special characters in cookies', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'buildCookieScript');
        $method->setAccessible(true);

        $cookies = [
            'test' => "value'with'quotes",
        ];

        $script = $method->invoke($this->tool, $cookies);

        expect($script)->toContain("\\'");
    });

    it('handles empty cookie array', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'buildCookieScript');
        $method->setAccessible(true);

        $script = $method->invoke($this->tool, []);

        expect($script)->toBe('');
    });
});

describe('extraction script generation', function () {
    it('uses custom selectors when provided', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'buildExtractionScript');
        $method->setAccessible(true);

        $script = $method->invoke(
            $this->tool,
            '.custom-title',
            '.custom-sku',
            '.custom-price'
        );

        expect($script)->toContain('.custom-title');
        expect($script)->toContain('.custom-sku');
        expect($script)->toContain('.custom-price');
    });

    it('includes auto-detection patterns when no custom selectors', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'buildExtractionScript');
        $method->setAccessible(true);

        $script = $method->invoke($this->tool, null, null, null);

        // Check for common auto-detection patterns (escaped in JavaScript)
        expect($script)->toContain('[itemprop=');
        expect($script)->toContain('name');
        expect($script)->toContain('sku');
        expect($script)->toContain('price');
        expect($script)->toContain('.product-title');
        expect($script)->toContain('.sku');
        expect($script)->toContain('.price');
    });

    it('generates valid JavaScript', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'buildExtractionScript');
        $method->setAccessible(true);

        $script = $method->invoke($this->tool, null, null, null);

        // Check for valid JavaScript structure
        expect($script)->toContain('function()');
        expect($script)->toContain('JSON.stringify');
        expect($script)->toContain('return');
    });

    it('includes SKU pattern matching in text', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'buildExtractionScript');
        $method->setAccessible(true);

        $script = $method->invoke($this->tool, null, null, null);

        // Check for SKU pattern matching
        expect($script)->toContain('findSkuInText');
        expect($script)->toContain('/SKU[:\s]+([A-Z0-9-]+)/i');
        expect($script)->toContain('/Part\s*#[:\s]+([A-Z0-9-]+)/i');
    });
});

describe('input schema', function () {
    it('defines required url parameter', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'inputSchema');
        $method->setAccessible(true);
        $schema = $method->invoke($this->tool);

        expect($schema)->toHaveKey('url');
        expect($schema['url']['required'])->toBeTrue();
        expect($schema['url']['type'])->toBe('string');
    });

    it('defines optional parameters', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'inputSchema');
        $method->setAccessible(true);
        $schema = $method->invoke($this->tool);

        expect($schema)->toHaveKey('use_session');
        expect($schema)->toHaveKey('save_html');
        expect($schema)->toHaveKey('title_selector');
        expect($schema)->toHaveKey('sku_selector');
        expect($schema)->toHaveKey('price_selector');
        expect($schema)->toHaveKey('timeout');

        expect($schema['use_session']['required'])->toBeFalse();
        expect($schema['save_html']['required'])->toBeFalse();
    });

    it('has proper descriptions for all parameters', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'inputSchema');
        $method->setAccessible(true);
        $schema = $method->invoke($this->tool);

        foreach ($schema as $param => $config) {
            expect($config)->toHaveKey('description');
            expect($config['description'])->not->toBeEmpty();
        }
    });
});

describe('tool properties', function () {
    it('has correct tool name', function () {
        $reflection = new ReflectionClass(ProductDataScraperTool::class);
        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setAccessible(true);

        expect($nameProperty->getValue($this->tool))->toBe('product-data-scraper');
    });

    it('has descriptive tool description', function () {
        $reflection = new ReflectionClass(ProductDataScraperTool::class);
        $descProperty = $reflection->getProperty('description');
        $descProperty->setAccessible(true);

        $description = $descProperty->getValue($this->tool);
        expect($description)->not->toBeEmpty();
        expect($description)->toContain('product');
    });
});

describe('price parsing edge cases', function () {
    it('handles prices with spaces', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'extractNumericPrice');
        $method->setAccessible(true);

        expect($method->invoke($this->tool, '1 234.56'))->toBe(1234.56);
        expect($method->invoke($this->tool, '$ 99.99'))->toBe(99.99);
    });

    it('handles multiple decimal separators correctly', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'extractNumericPrice');
        $method->setAccessible(true);

        // US format with thousands separator
        expect($method->invoke($this->tool, '1,234.56'))->toBe(1234.56);

        // European format with thousands separator
        expect($method->invoke($this->tool, '1.234,56'))->toBe(1234.56);
    });

    it('returns null for zero prices', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'extractNumericPrice');
        $method->setAccessible(true);

        expect($method->invoke($this->tool, '$0.00'))->toBeNull();
        expect($method->invoke($this->tool, '0'))->toBeNull();
    });

    it('handles prices with text prefix and suffix', function () {
        $method = new ReflectionMethod(ProductDataScraperTool::class, 'extractNumericPrice');
        $method->setAccessible(true);

        expect($method->invoke($this->tool, 'Starting at $99.99 each'))->toBe(99.99);
        expect($method->invoke($this->tool, 'Only €49,99 today'))->toBe(49.99);
    });
});
