<x-terminal-page
    footer-left="AUTOMATION // MCP CONTROL PANEL"
    footer-center="SESSION {{ strtoupper(substr(md5(session()->getId()), 0, 8)) }}"
    footer-right="OPERATOR {{ auth()->user()?->name ?? 'UNKNOWN' }}"
>
    <x-slot:banner>
        DEFENSE LOGISTICS INTELLIGENCE SYSTEM <span class="t-sep">//</span> MCP CONTROL PANEL
    </x-slot:banner>

    <div class="mcp-shell">
    {{-- Quick Actions Section --}}
    <div class="mcp-main-grid">
        {{-- ChromeDevTools Actions --}}
        <x-filament::section>
            <x-slot name="heading">
                ChromeDevTools
            </x-slot>

            <x-slot name="description">
                Browser automation and web scraping tools
            </x-slot>

            <div class="space-y-3">
                <x-filament::button
                    wire:click="triggerVendorLogin"
                    icon="heroicon-o-lock-closed"
                    class="w-full"
                >
                    Vendor Portal Login
                </x-filament::button>

                <x-filament::button
                    wire:click="triggerProductScraper"
                    icon="heroicon-o-document-magnifying-glass"
                    class="w-full"
                >
                    Scrape Product Data
                </x-filament::button>

                <x-filament::button
                    href="{{ url('/mcp/chrome-devtools') }}"
                    target="_blank"
                    icon="heroicon-o-arrow-top-right-on-square"
                    color="gray"
                    class="w-full"
                >
                    View MCP Endpoint
                </x-filament::button>
            </div>
        </x-filament::section>

        {{-- Business Tools Actions --}}
        <x-filament::section>
            <x-slot name="heading">
                Business Intelligence
            </x-slot>

            <x-slot name="description">
                Pricing analysis and competitive intelligence
            </x-slot>

            <div class="space-y-3">
                <x-filament::button
                    wire:click="triggerCompetitorPricing"
                    icon="heroicon-o-chart-bar"
                    class="w-full"
                >
                    Analyze Competitor Pricing
                </x-filament::button>

                <x-filament::button
                    href="{{ url('/mcp/business') }}"
                    target="_blank"
                    icon="heroicon-o-arrow-top-right-on-square"
                    color="gray"
                    class="w-full"
                >
                    View MCP Endpoint
                </x-filament::button>
            </div>
        </x-filament::section>

        {{-- Status Overview --}}
        <x-filament::section>
            <x-slot name="heading">
                System Status
            </x-slot>

            <x-slot name="description">
                Current MCP server state
            </x-slot>

            <div class="space-y-3">
                @php
                    $session = $this->getChromeDevToolsSession();
                    $progress = $this->getChromeDevToolsProgress();
                @endphp

                <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <span class="text-sm font-medium">Session Active</span>
                    @if($session)
                        <x-filament::badge color="success">
                            Active
                        </x-filament::badge>
                    @else
                        <x-filament::badge color="gray">
                            Inactive
                        </x-filament::badge>
                    @endif
                </div>

                <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <span class="text-sm font-medium">Scrape Progress</span>
                    @if($progress)
                        <x-filament::badge color="primary">
                            {{ $progress['processed_count'] ?? 0 }} / {{ $progress['total_urls'] ?? 0 }}
                        </x-filament::badge>
                    @else
                        <x-filament::badge color="gray">
                            No Data
                        </x-filament::badge>
                    @endif
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- Session Information --}}
    @php
        $session = $this->getChromeDevToolsSession();
    @endphp

    @if($session)
        <x-filament::section class="mb-6">
            <x-slot name="heading">
                Active Session
            </x-slot>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Username</dt>
                    <dd class="mt-1 text-sm font-semibold">{{ $session['username'] ?? 'N/A' }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Login URL</dt>
                    <dd class="mt-1 text-sm font-semibold truncate">{{ $session['login_url'] ?? 'N/A' }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Logged In At</dt>
                    <dd class="mt-1 text-sm font-semibold">{{ isset($session['logged_in_at']) ? \Carbon\Carbon::parse($session['logged_in_at'])->diffForHumans() : 'N/A' }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cookies</dt>
                    <dd class="mt-1 text-sm font-semibold">{{ isset($session['cookies']) ? count($session['cookies']) : 0 }} stored</dd>
                </div>
            </div>
        </x-filament::section>
    @endif

    {{-- Scrape Progress --}}
    @php
        $progress = $this->getChromeDevToolsProgress();
    @endphp

    @if($progress)
        <x-filament::section class="mb-6">
            <x-slot name="heading">
                Recent Scrape Progress
            </x-slot>

            <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-2 lg:grid-cols-5">
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total URLs</dt>
                    <dd class="mt-1 text-2xl font-bold">{{ $progress['total_urls'] ?? 0 }}</dd>
                </div>

                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Processed</dt>
                    <dd class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $progress['processed_count'] ?? 0 }}</dd>
                </div>

                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Successful</dt>
                    <dd class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{{ $progress['success_count'] ?? 0 }}</dd>
                </div>

                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Failed</dt>
                    <dd class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">{{ $progress['error_count'] ?? 0 }}</dd>
                </div>

                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</dt>
                    <dd class="mt-1 text-sm font-semibold">{{ isset($progress['last_updated']) ? \Carbon\Carbon::parse($progress['last_updated'])->diffForHumans() : 'N/A' }}</dd>
                </div>
            </div>

            @if(isset($progress['results']) && count($progress['results']) > 0)
                <div class="mt-4">
                    <h4 class="mb-3 text-sm font-medium">Recent Results (Last 5)</h4>
                    <div class="overflow-hidden border rounded-lg dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">URL</th>
                                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Title</th>
                                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">SKU</th>
                                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Price</th>
                                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                                @foreach(array_slice($progress['results'], -5, 5, true) as $url => $result)
                                    <tr>
                                        <td class="px-4 py-3 text-sm truncate max-w-xs">
                                            <a href="{{ $url }}" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">
                                                {{ Str::limit($url, 50) }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm">{{ $result['product']['title'] ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm font-mono">{{ $result['product']['sku'] ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm font-semibold">{{ $result['product']['price'] ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            @if($result['success'])
                                                <x-filament::badge color="success">Success</x-filament::badge>
                                            @else
                                                <x-filament::badge color="danger">Failed</x-filament::badge>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </x-filament::section>
    @endif

    {{-- HTML Cache Files --}}
    @php
        $htmlFiles = $this->getHtmlCacheFiles();
    @endphp

    @if(count($htmlFiles) > 0)
        <x-filament::section>
            <x-slot name="heading">
                HTML Cache Files
            </x-slot>

            <x-slot name="description">
                Cached HTML files from scraping operations
            </x-slot>

            <div class="overflow-hidden border rounded-lg dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">File Name</th>
                            <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Size</th>
                            <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Modified</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                        @foreach(array_slice($htmlFiles, -10) as $file)
                            <tr>
                                <td class="px-4 py-3 text-sm font-mono">{{ $file['name'] }}</td>
                                <td class="px-4 py-3 text-sm">{{ number_format($file['size'] / 1024, 2) }} KB</td>
                                <td class="px-4 py-3 text-sm">{{ \Carbon\Carbon::createFromTimestamp($file['modified'])->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    <style>
        .mcp-shell {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .mcp-main-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1.25rem;
        }

        .mcp-shell .fi-section {
            border: 1px solid #e5e7eb;
            background: #fff;
            box-shadow: none;
        }

        .dark .mcp-shell .fi-section {
            border-color: var(--t-border);
            background: var(--t-surface);
        }

        .mcp-shell .fi-section-header {
            border-bottom: 1px solid #e5e7eb;
        }

        .dark .mcp-shell .fi-section-header {
            border-color: var(--t-border);
        }

        .mcp-shell .fi-section-header-heading {
            font-family: var(--t-font-display);
            letter-spacing: 0.12em;
            font-size: 0.66rem;
            text-transform: uppercase;
        }

        .mcp-shell .fi-section-header-description {
            font-family: var(--t-font-mono);
            font-size: 0.72rem;
        }

        .mcp-shell table thead {
            background: linear-gradient(90deg, rgba(2, 119, 189, 0.06), transparent 88%);
        }

        .dark .mcp-shell table thead {
            background: linear-gradient(90deg, var(--t-cyan-glow), transparent 88%);
        }
    </style>

    </div>

    {{-- Vendor Login Modal --}}
    <x-filament::modal
        id="vendor-login-modal"
        width="xl"
        heading="Vendor Portal Login"
        description="Provide the login target and credentials. Values are stored encrypted for reuse."
    >
        <form wire:submit.prevent="submitVendorLogin" class="space-y-6">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-filament::input.wrapper>
                        <label for="vendor-login-url" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            Login URL
                        </label>
                        <x-filament::input
                            id="vendor-login-url"
                            wire:model.defer="vendorLoginForm.url"
                            type="url"
                            required
                            placeholder="https://vendor.example.com/login"
                        />
                    </x-filament::input.wrapper>
                    @error('vendorLoginForm.url')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-filament::input.wrapper>
                        <label for="vendor-login-username" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            Username
                        </label>
                        <x-filament::input
                            id="vendor-login-username"
                            wire:model.defer="vendorLoginForm.username"
                            type="text"
                            required
                            placeholder="buyer@example.com"
                        />
                    </x-filament::input.wrapper>
                    @error('vendorLoginForm.username')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-filament::input.wrapper>
                        <label for="vendor-login-password" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            Password
                        </label>
                        <x-filament::input
                            id="vendor-login-password"
                            wire:model.defer="vendorLoginForm.password"
                            type="password"
                            placeholder="••••••••"
                        />
                    </x-filament::input.wrapper>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Leave blank to reuse the last saved password.
                    </p>
                    @error('vendorLoginForm.password')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-filament::input.wrapper>
                        <label for="vendor-login-username-selector" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            Username Selector (optional)
                        </label>
                        <x-filament::input
                            id="vendor-login-username-selector"
                            wire:model.defer="vendorLoginForm.username_selector"
                            type="text"
                            placeholder="#email"
                        />
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <x-filament::input.wrapper>
                        <label for="vendor-login-password-selector" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            Password Selector (optional)
                        </label>
                        <x-filament::input
                            id="vendor-login-password-selector"
                            wire:model.defer="vendorLoginForm.password_selector"
                            type="text"
                            placeholder="#password"
                        />
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <x-filament::input.wrapper>
                        <label for="vendor-login-submit-selector" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            Submit Selector (optional)
                        </label>
                        <x-filament::input
                            id="vendor-login-submit-selector"
                            wire:model.defer="vendorLoginForm.submit_selector"
                            type="text"
                            placeholder="button[type=submit]"
                        />
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <x-filament::input.wrapper>
                        <label for="vendor-login-timeout" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            Timeout (ms)
                        </label>
                        <x-filament::input
                            id="vendor-login-timeout"
                            wire:model.defer="vendorLoginForm.timeout"
                            type="number"
                            min="1000"
                            max="120000"
                        />
                    </x-filament::input.wrapper>
                    @error('vendorLoginForm.timeout')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-start justify-between gap-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Credentials are encrypted using Laravel's application key and stored in the MCP settings table.
                </p>

                <div class="flex gap-2">
                    <x-filament::button
                        type="button"
                        color="gray"
                        x-on:click="$dispatch('close-modal', { id: 'vendor-login-modal' })"
                    >
                        Cancel
                    </x-filament::button>

                    <x-filament::button
                        type="submit"
                        icon="heroicon-o-lock-closed"
                    >
                        Save &amp; Login
                    </x-filament::button>
                </div>
            </div>
        </form>
    </x-filament::modal>

    {{-- Product Scraper Modal --}}
    <x-filament::modal
        id="product-scraper-modal"
        width="lg"
        heading="Scrape Product Data"
        description="Provide a product or category URL and optional scraping preferences."
    >
        <form wire:submit.prevent="submitProductScraper" class="space-y-6">
            <div>
                <x-filament::input.wrapper>
                    <label for="product-scraper-url" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                        Starting URL
                    </label>
                    <x-filament::input
                        id="product-scraper-url"
                        wire:model.defer="productScraperForm.url"
                        type="url"
                        required
                        placeholder="https://vendor.example.com/catalog"
                    />
                </x-filament::input.wrapper>
                @error('productScraperForm.url')
                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-3">
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                    <input
                        type="checkbox"
                        wire:model.defer="productScraperForm.use_session"
                        class="text-primary-600 border-gray-300 rounded focus:ring-primary-500 dark:border-gray-600"
                    />
                    Reuse saved browser session
                </label>

                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                    <input
                        type="checkbox"
                        wire:model.defer="productScraperForm.save_html"
                        class="text-primary-600 border-gray-300 rounded focus:ring-primary-500 dark:border-gray-600"
                    />
                    Cache HTML snapshots for debugging
                </label>
            </div>

            <div class="flex justify-end gap-2">
                <x-filament::button
                    type="button"
                    color="gray"
                    x-on:click="$dispatch('close-modal', { id: 'product-scraper-modal' })"
                >
                    Cancel
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    icon="heroicon-o-document-magnifying-glass"
                >
                    Save &amp; Scrape
                </x-filament::button>
            </div>
        </form>
    </x-filament::modal>

    {{-- Competitor Pricing Modal --}}
    <x-filament::modal
        id="competitor-pricing-modal"
        width="lg"
        heading="Competitor Pricing Analysis"
        description="Control how the Business MCP tool loads scrape data and applies filters."
    >
        <form wire:submit.prevent="submitCompetitorPricing" class="space-y-6">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-filament::input.wrapper>
                        <label for="competitor-scrape-source" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            Scrape Source (optional)
                        </label>
                        <x-filament::input
                            id="competitor-scrape-source"
                            wire:model.defer="competitorPricingForm.scrape_source"
                            type="text"
                            placeholder="app/Mcp/Servers/ChromeDevTools/State/scrape-progress.json"
                        />
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <x-filament::input.wrapper>
                        <label for="competitor-sku-field" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            SKU Field
                        </label>
                        <x-filament::input
                            id="competitor-sku-field"
                            wire:model.defer="competitorPricingForm.sku_field"
                            type="text"
                            required
                        />
                    </x-filament::input.wrapper>
                    @error('competitorPricingForm.sku_field')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-filament::input.wrapper>
                        <label for="competitor-price-field" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            Price Field
                        </label>
                        <x-filament::input
                            id="competitor-price-field"
                            wire:model.defer="competitorPricingForm.price_field"
                            type="text"
                            required
                        />
                    </x-filament::input.wrapper>
                    @error('competitorPricingForm.price_field')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-filament::input.wrapper>
                        <label for="competitor-min-diff" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            Min Difference %
                        </label>
                        <x-filament::input
                            id="competitor-min-diff"
                            wire:model.defer="competitorPricingForm.min_difference_percent"
                            type="number"
                            min="0"
                            step="0.5"
                        />
                    </x-filament::input.wrapper>
                    @error('competitorPricingForm.min_difference_percent')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-filament::input.wrapper>
                        <label for="competitor-limit" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            Result Limit
                        </label>
                        <x-filament::input
                            id="competitor-limit"
                            wire:model.defer="competitorPricingForm.limit"
                            type="number"
                            min="1"
                            max="500"
                            placeholder="All"
                        />
                    </x-filament::input.wrapper>
                    @error('competitorPricingForm.limit')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <x-filament::button
                    type="button"
                    color="gray"
                    x-on:click="$dispatch('close-modal', { id: 'competitor-pricing-modal' })"
                >
                    Cancel
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    icon="heroicon-o-chart-bar"
                >
                    Save &amp; Analyze
                </x-filament::button>
            </div>
        </form>
    </x-filament::modal>

</x-terminal-page>
