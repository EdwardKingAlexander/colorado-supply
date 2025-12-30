<?php

namespace App\Filament\Pages;

use App\Support\McpClient;
use App\Support\McpSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;
use UnitEnum;

class McpDashboard extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-command-line';

    protected string $view = 'filament.pages.mcp-dashboard';

    protected static UnitEnum|string|null $navigationGroup = 'Automation';

    protected static ?string $title = 'MCP Control Panel';

    protected static ?int $navigationSort = 1;

    protected const VENDOR_LOGIN_KEY = 'vendor-portal-login';

    protected const PRODUCT_SCRAPER_KEY = 'product-data-scraper';

    protected const COMPETITOR_PRICING_KEY = 'fetch-competitor-pricing';

    public array $vendorLoginForm = [];

    public array $productScraperForm = [];

    public array $competitorPricingForm = [];

    protected ?string $storedVendorPassword = null;

    public function mount(): void
    {
        // Load state data on mount
        $this->loadStateData();
    }

    protected function loadStateData(): void
    {
        $this->prepareVendorLoginForm();
        $this->prepareProductScraperForm();
        $this->prepareCompetitorPricingForm();
    }

    protected function prepareVendorLoginForm(): void
    {
        $settings = McpSettings::for(self::VENDOR_LOGIN_KEY);

        $this->storedVendorPassword = $settings['password'] ?? null;

        $this->vendorLoginForm = [
            'url' => $settings['url'] ?? '',
            'username' => $settings['username'] ?? '',
            'password' => '',
            'username_selector' => $settings['username_selector'] ?? '',
            'password_selector' => $settings['password_selector'] ?? '',
            'submit_selector' => $settings['submit_selector'] ?? '',
            'timeout' => $settings['timeout'] ?? 30000,
        ];
    }

    protected function prepareProductScraperForm(): void
    {
        $settings = McpSettings::for(self::PRODUCT_SCRAPER_KEY, [
            'use_session' => true,
            'save_html' => true,
        ]);

        $this->productScraperForm = [
            'url' => $settings['url'] ?? '',
            'use_session' => (bool) ($settings['use_session'] ?? true),
            'save_html' => (bool) ($settings['save_html'] ?? true),
        ];
    }

    protected function prepareCompetitorPricingForm(): void
    {
        $settings = McpSettings::for(self::COMPETITOR_PRICING_KEY);

        $this->competitorPricingForm = [
            'scrape_source' => $settings['scrape_source'] ?? '',
            'sku_field' => $settings['sku_field'] ?? 'sku',
            'price_field' => $settings['price_field'] ?? 'price',
            'min_difference_percent' => $settings['min_difference_percent'] ?? 5,
            'limit' => $settings['limit'] ?? null,
        ];
    }

    /**
     * Get ChromeDevTools scrape progress.
     */
    public function getChromeDevToolsProgress(): ?array
    {
        $file = app_path('Mcp/Servers/ChromeDevTools/State/scrape-progress.json');

        if (! file_exists($file)) {
            return null;
        }

        return json_decode(file_get_contents($file), true);
    }

    /**
     * Get ChromeDevTools session info.
     */
    public function getChromeDevToolsSession(): ?array
    {
        $file = app_path('Mcp/Servers/ChromeDevTools/State/session.json');

        if (! file_exists($file)) {
            return null;
        }

        return json_decode(file_get_contents($file), true);
    }

    /**
     * Get HTML cache files.
     */
    public function getHtmlCacheFiles(): array
    {
        $dir = app_path('Mcp/Servers/ChromeDevTools/State/html-cache');

        if (! is_dir($dir)) {
            return [];
        }

        $files = glob($dir.'/*.html');

        return array_map(function ($file) {
            return [
                'name' => basename($file),
                'size' => filesize($file),
                'modified' => filemtime($file),
                'path' => $file,
            ];
        }, $files);
    }

    /**
     * Actions for the page header.
     */
    protected function getHeaderActions(): array
    {
        // Get current store status
        $settings = McpSettings::for('store-settings', ['enabled' => true]);
        $storeEnabled = $settings['enabled'] ?? true;

        return [
            Action::make('toggleStore')
                ->label($storeEnabled ? 'Disable Store' : 'Enable Store')
                ->icon($storeEnabled ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                ->color($storeEnabled ? 'warning' : 'success')
                ->requiresConfirmation()
                ->modalHeading($storeEnabled ? 'Disable Store?' : 'Enable Store?')
                ->modalDescription($storeEnabled
                    ? 'This will make the store inaccessible to all users except admins. Regular users will see a 403 error.'
                    : 'This will make the store accessible to all authenticated users again.')
                ->action(function () use ($storeEnabled) {
                    try {
                        McpSettings::put('store-settings', ['enabled' => ! $storeEnabled], 'Store availability settings');

                        Notification::make()
                            ->title($storeEnabled ? 'Store Disabled' : 'Store Enabled')
                            ->body($storeEnabled
                                ? 'The store is now only accessible to admins.'
                                : 'The store is now accessible to all users.')
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('clearScrapeProgress')
                ->label('Clear Scrape Progress')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $file = app_path('Mcp/Servers/ChromeDevTools/State/scrape-progress.json');
                    if (file_exists($file)) {
                        unlink($file);
                        Notification::make()
                            ->title('Scrape progress cleared')
                            ->success()
                            ->send();
                    }
                }),

            Action::make('clearSession')
                ->label('Clear Session')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $file = app_path('Mcp/Servers/ChromeDevTools/State/session.json');
                    if (file_exists($file)) {
                        unlink($file);
                        Notification::make()
                            ->title('Session cleared')
                            ->success()
                            ->send();
                    }
                }),
        ];
    }

    /**
     * Trigger vendor login tool.
     */
    public function triggerVendorLogin(): void
    {
        $this->dispatch('open-modal', id: 'vendor-login-modal');
    }

    /**
     * Trigger product scraper tool.
     */
    public function triggerProductScraper(): void
    {
        $this->dispatch('open-modal', id: 'product-scraper-modal');
    }

    /**
     * Trigger competitor pricing tool.
     */
    public function triggerCompetitorPricing(): void
    {
        $this->dispatch('open-modal', id: 'competitor-pricing-modal');
    }

    /**
     * Handle vendor login submission.
     */
    public function submitVendorLogin(): void
    {
        $data = Validator::make($this->vendorLoginForm, [
            'url' => ['required', 'url'],
            'username' => ['required', 'string'],
            'password' => ['nullable', 'string'],
            'username_selector' => ['nullable', 'string'],
            'password_selector' => ['nullable', 'string'],
            'submit_selector' => ['nullable', 'string'],
            'timeout' => ['nullable', 'integer', 'min:1000', 'max:120000'],
        ], [], [
            'url' => 'login URL',
        ])->validate();

        if (blank($data['password'] ?? null)) {
            if (blank($this->storedVendorPassword)) {
                throw ValidationException::withMessages([
                    'vendorLoginForm.password' => 'Password is required the first time you run the vendor login.',
                ]);
            }

            $data['password'] = $this->storedVendorPassword;
        }

        $data['timeout'] = $data['timeout'] ?? 30000;

        try {
            McpSettings::put(self::VENDOR_LOGIN_KEY, $data, 'Vendor portal login settings');
        } catch (Throwable $e) {
            Notification::make()
                ->title('Unable to Save Vendor Settings')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->storedVendorPassword = $data['password'];
        $this->vendorLoginForm['password'] = '';

        $this->executeVendorLogin($data);
    }

    /**
     * Handle product scraper submission.
     */
    public function submitProductScraper(): void
    {
        $data = Validator::make($this->productScraperForm, [
            'url' => ['required', 'url'],
            'use_session' => ['boolean'],
            'save_html' => ['boolean'],
        ], [], [
            'url' => 'product URL',
        ])->validate();

        $data['use_session'] = (bool) ($data['use_session'] ?? true);
        $data['save_html'] = (bool) ($data['save_html'] ?? true);

        try {
            McpSettings::put(self::PRODUCT_SCRAPER_KEY, $data, 'Product scraper defaults');
        } catch (Throwable $e) {
            Notification::make()
                ->title('Unable to Save Scraper Settings')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->executeProductScraper($data);
    }

    /**
     * Handle competitor pricing submission.
     */
    public function submitCompetitorPricing(): void
    {
        $data = Validator::make($this->competitorPricingForm, [
            'scrape_source' => ['nullable', 'string'],
            'sku_field' => ['required', 'string'],
            'price_field' => ['required', 'string'],
            'min_difference_percent' => ['nullable', 'numeric', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ], [], [
            'sku_field' => 'SKU field',
            'price_field' => 'price field',
        ])->validate();

        $data['min_difference_percent'] = isset($data['min_difference_percent'])
            ? (float) $data['min_difference_percent']
            : null;
        $data['limit'] = isset($data['limit']) ? (int) $data['limit'] : null;

        try {
            McpSettings::put(self::COMPETITOR_PRICING_KEY, $data, 'Competitor pricing defaults');
        } catch (Throwable $e) {
            Notification::make()
                ->title('Unable to Save Pricing Settings')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->executeCompetitorPricing($data);
    }

    /**
     * Execute vendor login via MCP.
     */
    protected function executeVendorLogin(array $payload): void
    {
        try {
            $arguments = [
                'url' => $payload['url'],
                'username' => $payload['username'],
                'password' => $payload['password'],
            ];

            foreach (['username_selector', 'password_selector', 'submit_selector'] as $field) {
                if (filled($payload[$field] ?? null)) {
                    $arguments[$field] = $payload[$field];
                }
            }

            if (! empty($payload['timeout'])) {
                $arguments['timeout'] = (int) $payload['timeout'];
            }

            $this->callChromeDevToolsTool('vendor-portal-login', $arguments);

            Notification::make()
                ->title('Login Successful')
                ->body('Session saved to State/session.json')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Login Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Execute product scraper via MCP.
     */
    protected function executeProductScraper(array $payload): void
    {
        try {
            $result = $this->callChromeDevToolsTool('product-data-scraper', [
                'url' => $payload['url'],
                'use_session' => (bool) ($payload['use_session'] ?? true),
                'save_html' => (bool) ($payload['save_html'] ?? true),
            ]);

            $resultText = $this->extractMcpText($result);
            $resultData = json_decode($resultText, true);

            if ($resultData && ($resultData['success'] ?? false)) {
                // Save to database
                $scrapedProduct = \App\Models\ScrapedProduct::createFromToolResult(
                    $payload['url'],
                    $resultData
                );

                Notification::make()
                    ->title('Product Scraped Successfully')
                    ->body('Data extracted and saved. View it in the Product Import Wizard.')
                    ->success()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->button()
                            ->url(\App\Filament\Resources\ScrapedProducts\ScrapedProductResource::getUrl('index')),
                    ])
                    ->send();
            } else {
                Notification::make()
                    ->title('Scraping Completed')
                    ->body($resultData['error'] ?? 'No data could be extracted')
                    ->warning()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Scraping Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Execute competitor pricing comparison via MCP.
     */
    protected function executeCompetitorPricing(array $payload): void
    {
        try {
            $arguments = [
                'sku_field' => $payload['sku_field'],
                'price_field' => $payload['price_field'],
            ];

            if (filled($payload['scrape_source'] ?? null)) {
                $arguments['scrape_source'] = $payload['scrape_source'];
            }

            if (! is_null($payload['min_difference_percent'])) {
                $arguments['min_difference_percent'] = (float) $payload['min_difference_percent'];
            }

            if (! is_null($payload['limit'])) {
                $arguments['limit'] = (int) $payload['limit'];
            }

            $result = $this->callBusinessTool('fetch-competitor-pricing', $arguments);
            $text = $this->extractMcpText($result);

            Notification::make()
                ->title('Competitor Pricing Analysis Complete')
                ->body($text ?? 'Report generated successfully.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    protected function callChromeDevToolsTool(string $tool, array $arguments): array
    {
        return $this->callMcpTool('/mcp/chrome-devtools', $tool, $arguments);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    protected function callBusinessTool(string $tool, array $arguments): array
    {
        return $this->callMcpTool('/mcp/business', $tool, $arguments);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    protected function callMcpTool(string $endpoint, string $tool, array $arguments): array
    {
        return app(McpClient::class)->callTool($endpoint, $tool, $arguments);
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeMcpJson(array $payload): array
    {
        $text = $this->extractMcpText($payload);

        if ($text === null) {
            throw new \RuntimeException('MCP response missing textual content.');
        }

        try {
            return json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Unable to decode MCP response: '.$e->getMessage(), 0, $e);
        }
    }

    protected function extractMcpText(array $payload): ?string
    {
        return app(McpClient::class)->extractText($payload);
    }
}
