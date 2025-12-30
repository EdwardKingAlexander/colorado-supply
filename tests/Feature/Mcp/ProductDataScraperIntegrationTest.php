<?php

declare(strict_types=1);

use App\Mcp\Servers\ChromeDevTools\Services\BrowserService;
use App\Mcp\Servers\ChromeDevTools\Skills\MultiPageScrapeSkill;
use App\Mcp\Servers\ChromeDevTools\Tools\ProductDataScraperTool;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

afterEach(function () {
    // Clean up browser resources after each test
    BrowserService::closeBrowser();

    // Clean up test files
    $stateDir = app_path('Mcp/Servers/ChromeDevTools/State');
    $htmlCacheDir = $stateDir.'/html-cache';

    if (File::exists($htmlCacheDir)) {
        $files = File::files($htmlCacheDir);
        foreach ($files as $file) {
            if (Str::contains($file->getFilename(), 'test-')) {
                File::delete($file->getPathname());
            }
        }
    }
});

describe('ProductDataScraperTool integration', function () {
    it('can be called through MCP endpoint', function () {
        // Create a test HTML page
        $testHtml = <<<'HTML'
<!DOCTYPE html>
<html>
<head><title>Test Product</title></head>
<body>
    <h1>Test Product Name</h1>
    <div class="sku">SKU: TEST-123</div>
    <div class="price">$99.99</div>
</body>
</html>
HTML;

        // Create a temporary HTML file
        $tempFile = sys_get_temp_dir().'/test-product-'.uniqid().'.html';
        file_put_contents($tempFile, $testHtml);

        try {
            $response = $this->postJson('/mcp/chrome-devtools', [
                'jsonrpc' => '2.0',
                'id' => (string) Str::uuid(),
                'method' => 'tools/call',
                'params' => [
                    'name' => 'product-data-scraper',
                    'arguments' => [
                        'url' => 'file://'.$tempFile,
                        'save_html' => false,
                    ],
                ],
            ]);

            $response->assertOk();

            $body = $response->json();

            expect($body)->toHaveKey('result');
            expect($body['result'])->toHaveKey('content');

            // Parse the result
            $content = $body['result']['content'][0]['text'] ?? null;
            expect($content)->not->toBeNull();

            $result = json_decode($content, true);
            expect($result)->toHaveKey('success');
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    })->skip('Requires headless Chrome to be installed');

    it('returns error for invalid URL', function () {
        $response = $this->postJson('/mcp/chrome-devtools', [
            'jsonrpc' => '2.0',
            'id' => (string) Str::uuid(),
            'method' => 'tools/call',
            'params' => [
                'name' => 'product-data-scraper',
                'arguments' => [
                    'url' => 'http://invalid-domain-that-does-not-exist-12345.com',
                    'timeout' => 5000,
                    'save_html' => false,
                ],
            ],
        ]);

        $response->assertOk();

        $body = $response->json();

        expect($body)->toHaveKey('result');

        $content = $body['result']['content'][0]['text'] ?? null;
        $result = json_decode($content, true);

        expect($result)->toHaveKey('success');
        expect($result['success'])->toBeFalse();
        expect($result)->toHaveKey('error');
    })->skip('Requires headless Chrome to be installed');
});

describe('MultiPageScrapeSkill integration', function () {
    it('can scrape multiple pages', function () {
        // Create test HTML pages
        $testPages = [];
        for ($i = 1; $i <= 3; $i++) {
            $html = <<<HTML
<!DOCTYPE html>
<html>
<head><title>Test Product {$i}</title></head>
<body>
    <h1>Test Product {$i}</h1>
    <div class="sku">SKU: TEST-{$i}</div>
    <div class="price">$99.99</div>
</body>
</html>
HTML;

            $tempFile = sys_get_temp_dir().'/test-product-'.$i.'-'.uniqid().'.html';
            file_put_contents($tempFile, $html);
            $testPages[] = 'file://'.$tempFile;
        }

        try {
            $skill = new MultiPageScrapeSkill;
            $result = $skill->scrape([
                'urls' => $testPages,
                'use_session' => false,
                'save_html' => false,
                'delay_ms' => 100,
            ]);

            expect($result)->toHaveKey('success');
            expect($result['success'])->toBeTrue();
            expect($result)->toHaveKey('summary');
            expect($result['summary']['total_urls'])->toBe(3);
        } finally {
            // Clean up temp files
            foreach ($testPages as $url) {
                $file = str_replace('file://', '', $url);
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            // Clean up progress file
            $skill->clearProgress();
        }
    })->skip('Requires headless Chrome to be installed');
});

describe('HTML cache functionality', function () {
    it('saves HTML snapshots when save_html is true', function () {
        $testHtml = <<<'HTML'
<!DOCTYPE html>
<html>
<head><title>Cache Test</title></head>
<body>
    <h1>Cache Test Product</h1>
    <div class="sku">SKU: CACHE-TEST</div>
    <div class="price">$99.99</div>
</body>
</html>
HTML;

        $tempFile = sys_get_temp_dir().'/test-cache-'.uniqid().'.html';
        file_put_contents($tempFile, $testHtml);

        try {
            $tool = new ProductDataScraperTool;
            $resultJson = $tool->execute([
                'url' => 'file://'.$tempFile,
                'save_html' => true,
            ]);

            $result = json_decode($resultJson, true);

            if ($result['success']) {
                expect($result)->toHaveKey('html_cache');
                expect($result['html_cache'])->not->toBeNull();

                // Verify the cache file exists
                if ($result['html_cache']) {
                    expect(file_exists($result['html_cache']))->toBeTrue();

                    // Clean up cache file
                    unlink($result['html_cache']);
                }
            }
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    })->skip('Requires headless Chrome to be installed');

    it('does not save HTML when save_html is false', function () {
        $testHtml = <<<'HTML'
<!DOCTYPE html>
<html>
<head><title>No Cache Test</title></head>
<body>
    <h1>No Cache Test Product</h1>
    <div class="sku">SKU: NO-CACHE</div>
    <div class="price">$99.99</div>
</body>
</html>
HTML;

        $tempFile = sys_get_temp_dir().'/test-no-cache-'.uniqid().'.html';
        file_put_contents($tempFile, $testHtml);

        try {
            $tool = new ProductDataScraperTool;
            $resultJson = $tool->execute([
                'url' => 'file://'.$tempFile,
                'save_html' => false,
            ]);

            $result = json_decode($resultJson, true);

            if ($result['success']) {
                expect($result)->toHaveKey('html_cache');
                expect($result['html_cache'])->toBeNull();
            }
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    })->skip('Requires headless Chrome to be installed');
});

describe('tool registration', function () {
    it('is registered in ChromeDevToolsServer', function () {
        $reflection = new ReflectionClass(\App\Mcp\Servers\ChromeDevTools\ChromeDevToolsServer::class);
        $toolsProperty = $reflection->getProperty('tools');
        $toolsProperty->setAccessible(true);

        // Get the default value of the $tools property
        $tools = $toolsProperty->getDefaultValue();

        expect($tools)->toContain(ProductDataScraperTool::class);
    });
});

describe('McpDashboard integration', function () {
    it('can set product scraper form values', function () {
        $this->actingAs(\App\Models\Admin::factory()->create());

        $component = \Livewire\Livewire::test(\App\Filament\Pages\McpDashboard::class);

        $component
            ->set('productScraperForm.url', 'https://example.com/product')
            ->set('productScraperForm.use_session', false)
            ->set('productScraperForm.save_html', true);

        // Verify the form values were set
        $component->assertSet('productScraperForm.url', 'https://example.com/product');
        $component->assertSet('productScraperForm.use_session', false);
        $component->assertSet('productScraperForm.save_html', true);
    });
});
