{{-- Updated: 2025-11-30 --}}
<x-filament-panels::page>
    <div>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Form Section (Left) --}}
        <div class="lg:col-span-2">
            <x-filament::section>
                <x-slot name="heading">
                    Query Configuration
                </x-slot>

                <x-slot name="description">
                    Configure SAM.gov query parameters to fetch federal contract opportunities
                </x-slot>

                <div class="space-y-6">
                    {{ $this->form }}

                    <div class="flex flex-col gap-3 pt-4 border-t dark:border-gray-700 sm:flex-row sm:justify-between sm:items-center">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Results will be displayed in the summary panel and saved to the opportunities feed.
                        </p>

                        <div class="flex gap-2">
                            <x-filament::button
                                wire:click="executeFetch"
                                wire:loading.attr="disabled"
                                icon="heroicon-o-magnifying-glass"
                                size="xl"
                            >
                                <span wire:loading.remove wire:target="executeFetch">Fetch Opportunities</span>
                                <span wire:loading wire:target="executeFetch">Fetching...</span>
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Results Summary (Right) --}}
        <div class="lg:col-span-1" x-data="{ showSpinner: false }">
            {{-- Queue Worker (manual) --}}
            <x-filament::section class="mb-6">
                <x-slot name="heading">
                    Queue Worker (manual)
                </x-slot>

                <x-slot name="description">
                    Run this in a terminal to process queued fetch jobs
                </x-slot>

                <div class="space-y-3 text-sm">
                    <div>
                        <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">Command</p>
                        <div class="mt-1 rounded-md bg-gray-100 dark:bg-gray-800 px-3 py-2 font-mono text-xs text-gray-900 dark:text-gray-100">
                            php artisan queue:work --tries=3 --timeout=600
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Stop with Ctrl+C in that terminal. Log: storage/logs/queue-worker.log</p>
                    </div>

                    @if(!empty($this->queueLogTail))
                        <div>
                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">Recent log lines</p>
                            <div class="mt-1 rounded-md bg-gray-100 dark:bg-gray-800 p-2 text-[11px] font-mono text-gray-900 dark:text-gray-100 space-y-1 max-h-48 overflow-y-auto">
                                @foreach($this->queueLogTail as $line)
                                    <div>{{ $line }}</div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </x-filament::section>

            @php
                $result = $this->getLastResult();
            @endphp

            {{-- Loading State --}}
            <div x-show="showSpinner" x-cloak>
                <x-filament::section class="mb-6">
                    <div class="flex items-center gap-3 p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                        <svg class="flex-shrink-0 w-6 h-6 text-blue-600 dark:text-blue-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <div>
                            <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-200">Fetching Opportunities...</h3>
                            <p class="mt-1 text-xs text-blue-700 dark:text-blue-300">This may take 30-60 seconds. Results will appear automatically.</p>
                        </div>
                    </div>
                </x-filament::section>
            </div>

            @if($result)
                {{-- Error Display --}}
                @if(isset($result['error']) && $result['error'])
                    <x-filament::section class="mb-6">
                        <div class="p-4 rounded-lg border-2 border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-900/30">
                            <div class="flex items-start gap-3">
                                <svg class="flex-shrink-0 w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="flex-1">
                                    <h3 class="text-sm font-bold text-red-800 dark:text-red-200">Fetch Failed</h3>
                                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $result['error'] }}</p>
                                    <p class="mt-2 text-xs text-red-600 dark:text-red-400">Check the queue worker logs for more details, or try reducing the number of NAICS codes.</p>
                                </div>
                            </div>
                        </div>
                    </x-filament::section>
                @endif

                {{-- Summary Stats --}}
                <x-filament::section class="mb-6">
                    <x-slot name="heading">
                        Query Summary
                    </x-slot>

                    <x-slot name="description">
                        Last fetched: {{ $result['fetched_at'] ? \Carbon\Carbon::parse($result['fetched_at'])->diffForHumans() : 'Never' }}
                    </x-slot>

                    <div class="space-y-3">
                        <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Records (before dedup)</dt>
                            <dd class="mt-2 text-3xl font-bold">{{ $result['summary']['total_records'] ?? 0 }}</dd>
                        </div>

                        <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                            <dt class="text-xs font-medium text-blue-600 dark:text-blue-400">After Deduplication</dt>
                            <dd class="mt-2 text-3xl font-bold text-blue-700 dark:text-blue-300">{{ $result['summary']['total_after_dedup'] ?? 0 }}</dd>
                            @if(($result['summary']['duplicates_removed'] ?? 0) > 0)
                                <dd class="mt-1 text-xs text-blue-600 dark:text-blue-400">
                                    {{ $result['summary']['duplicates_removed'] }} duplicates removed
                                </dd>
                            @endif
                        </div>

                        <div class="p-4 rounded-lg bg-indigo-50 dark:bg-indigo-900/20">
                            <dt class="text-xs font-medium text-indigo-600 dark:text-indigo-400">Displayed</dt>
                            <dd class="mt-2 text-3xl font-bold text-indigo-700 dark:text-indigo-300">{{ $result['summary']['returned'] ?? 0 }}</dd>
                            <dd class="mt-1 text-xs text-indigo-600 dark:text-indigo-400">Limit {{ $result['summary']['limit'] ?? 'n/a' }}</dd>
                        </div>

                        <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/20">
                            <dt class="text-xs font-medium text-green-600 dark:text-green-400">Cache Hit Rate</dt>
                            <dd class="mt-2 text-3xl font-bold text-green-700 dark:text-green-300">{{ $result['summary']['cache_hit_rate'] ?? '0%' }}</dd>
                        </div>
                    </div>
                </x-filament::section>

                {{-- Opportunities List --}}
                @if(isset($result['opportunities']) && count($result['opportunities']) > 0)
                    @php
                        $ops = $result['opportunities'] ?? [];

                        // Filters
                        $filters = [
                            'q' => request()->get('q', ''),
                            'notice' => request()->get('notice', ''),
                            'naics' => request()->get('naics', ''),
                            'state' => request()->get('state', ''),
                            'set_aside' => request()->get('set_aside', ''),
                        ];

                        $exportFilters = array_filter(
                            $filters,
                            fn ($value) => $value !== '' && $value !== null
                        );

                        $filtered = array_filter($ops, function ($opp) use ($filters) {
                            $title = $opp['title'] ?? '';
                            $agency = $opp['agency_name'] ?? '';
                            $sol = $opp['solicitation_number'] ?? '';
                            $notice = $opp['notice_type'] ?? '';
                            $naics = $opp['naics_code'] ?? '';
                            $state = $opp['state_code'] ?? '';
                            $setAside = $opp['set_aside_type'] ?? '';

                            if ($filters['q']) {
                                $haystack = strtolower($title.' '.$agency.' '.$sol);
                                if (! str_contains($haystack, strtolower($filters['q']))) {
                                    return false;
                                }
                            }

                            if ($filters['notice'] && strcasecmp($notice, $filters['notice']) !== 0) {
                                return false;
                            }

                            if ($filters['naics'] && strcasecmp($naics, $filters['naics']) !== 0) {
                                return false;
                            }

                            if ($filters['state'] && strcasecmp($state, $filters['state']) !== 0) {
                                return false;
                            }

                            if ($filters['set_aside'] && strcasecmp($setAside, $filters['set_aside']) !== 0) {
                                return false;
                            }

                            return true;
                        });

                        // Dropdown options
                        $noticeOptions = collect($ops)->pluck('notice_type')->filter()->unique()->sort()->values()->all();
                        $naicsOptions = collect($ops)->pluck('naics_code')->filter()->unique()->sort()->values()->all();
                        $stateOptions = collect($ops)->pluck('state_code')->filter()->unique()->sort()->values()->all();
                        $setAsideOptions = collect($ops)->pluck('set_aside_type')->filter()->unique()->sort()->values()->all();

                        $perPage = 25;
                        $page = max((int) request()->get('ops_page', 1), 1);
                        $total = count($filtered);
                        $pages = max(1, (int) ceil($total / $perPage));
                        $page = min($page, $pages);
                        $offset = ($page - 1) * $perPage;
                        $slice = array_slice($filtered, $offset, $perPage);
                    @endphp

                    <x-filament::section class="mt-6">
                        <x-slot name="heading">
                            Fetched Opportunities ({{ $total }})
                        </x-slot>

                        {{-- Filters --}}
                        <style>
                            .sam-filters {
                                display: grid;
                                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                                gap: 0.5rem;
                                margin-bottom: 0.75rem;
                                align-items: end;
                            }
                            .sam-filter-label {
                                font-size: 0.75rem;
                                color: #475569;
                                margin-bottom: 0.25rem;
                                display: block;
                                font-weight: 600;
                            }
                            .sam-filter-input, .sam-filter-select {
                                width: 100%;
                                border: 1px solid #e2e8f0;
                                border-radius: 8px;
                                padding: 0.45rem 0.6rem;
                                font-size: 0.9rem;
                                background: #fff;
                            }
                            .sam-filter-actions {
                                display: flex;
                                gap: 0.5rem;
                                justify-content: flex-end;
                            }
                            .sam-btn {
                                border-radius: 10px;
                                padding: 0.55rem 0.9rem;
                                font-weight: 700;
                                border: 1px solid transparent;
                                cursor: pointer;
                                transition: all 120ms ease;
                            }
                            .sam-btn-primary { background: #1d4ed8; color: #fff; }
                            .sam-btn-primary:hover { background: #1e40af; }
                            .sam-btn-ghost { background: #f8fafc; color: #0f172a; border-color: #e2e8f0; }
                            .sam-btn-ghost:hover { background: #e2e8f0; }
                            .dark .sam-filter-label { color: #cbd5e1; }
                            .dark .sam-filter-input, .dark .sam-filter-select { background: #0f172a; border-color: #1f2937; color: #e2e8f0; }
                            .dark .sam-btn-ghost { background: #0f172a; color: #e2e8f0; border-color: #1f2937; }
                            .dark .sam-btn-ghost:hover { background: #111827; }
                        </style>

                        <form method="GET" class="sam-filters">
                            <div>
                                <label class="sam-filter-label" for="q">Search</label>
                                <input id="q" name="q" value="{{ $filters['q'] }}" class="sam-filter-input" placeholder="Title, agency, solicitation">
                            </div>
                            <div>
                                <label class="sam-filter-label" for="notice">Notice</label>
                                <select id="notice" name="notice" class="sam-filter-select">
                                    <option value="">All</option>
                                    @foreach($noticeOptions as $opt)
                                        <option value="{{ $opt }}" @selected($filters['notice'] === $opt)>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="sam-filter-label" for="naics">NAICS</label>
                                <select id="naics" name="naics" class="sam-filter-select">
                                    <option value="">All</option>
                                    @foreach($naicsOptions as $opt)
                                        <option value="{{ $opt }}" @selected($filters['naics'] === $opt)>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="sam-filter-label" for="state">State</label>
                                <select id="state" name="state" class="sam-filter-select">
                                    <option value="">All</option>
                                    @foreach($stateOptions as $opt)
                                        <option value="{{ $opt }}" @selected($filters['state'] === $opt)>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="sam-filter-label" for="set_aside">Set-Aside</label>
                                <select id="set_aside" name="set_aside" class="sam-filter-select">
                                    <option value="">All</option>
                                    @foreach($setAsideOptions as $opt)
                                        <option value="{{ $opt }}" @selected($filters['set_aside'] === $opt)>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sam-filter-actions">
                                <button type="submit" class="sam-btn sam-btn-primary">Apply</button>
                                <a href="{{ route('admin.sam-opportunities.export', $exportFilters) }}" class="sam-btn sam-btn-ghost">Export Excel</a>
                                <a href="{{ request()->url() }}" class="sam-btn sam-btn-ghost">Reset</a>
                            </div>
                            <input type="hidden" name="ops_page" value="1">
                        </form>

                        <div class="sam-table-container">
                            <style>
                                .sam-table-container {
                                    border: 1px solid #e5e7eb;
                                    border-radius: 12px;
                                    overflow: hidden;
                                    background: #fff;
                                    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
                                }
                                .sam-table {
                                    width: 100%;
                                    border-collapse: collapse;
                                    font-size: 0.925rem;
                                }
                                .sam-table thead {
                                    background: linear-gradient(90deg, #f8fafc 0%, #eef2ff 100%);
                                    text-transform: uppercase;
                                    font-size: 0.75rem;
                                    letter-spacing: 0.02em;
                                    color: #334155;
                                }
                                .sam-table th,
                                .sam-table td {
                                    padding: 0.75rem 1rem;
                                    vertical-align: top;
                                }
                                .sam-table tbody tr:nth-child(every) { background: #fff; }
                                .sam-table tbody tr:hover {
                                    background: #f1f5f9;
                                    transition: background 120ms ease;
                                }
                                .sam-link {
                                    color: #2563eb;
                                    font-weight: 600;
                                    text-decoration: none;
                                }
                                .sam-link:hover {
                                    color: #1d4ed8;
                                    text-decoration: underline;
                                }
                                .sam-badge {
                                    display: inline-flex;
                                    align-items: center;
                                    border-radius: 9999px;
                                    padding: 0.35rem 0.65rem;
                                    font-size: 0.7rem;
                                    font-weight: 700;
                                    background: #ede9fe;
                                    color: #6b21a8;
                                }
                                .sam-badge-green {
                                    background: #dcfce7;
                                    color: #166534;
                                    border-radius: 0.5rem;
                                }
                                .sam-meta {
                                    color: #475569;
                                }
                                .sam-mono {
                                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                                    font-size: 0.8rem;
                                }
                                .dark .sam-table-container { background: #0f172a; border-color: #1f2937; box-shadow: 0 8px 24px rgba(0,0,0,0.4); }
                                .dark .sam-table thead { background: linear-gradient(90deg, #111827 0%, #1f2937 100%); color: #e5e7eb; }
                                .dark .sam-table tbody tr:hover { background: #111827; }
                                .dark .sam-link { color: #93c5fd; }
                                .dark .sam-link:hover { color: #bfdbfe; }
                                .dark .sam-badge { background: #312e81; color: #c7d2fe; }
                                .dark .sam-badge-green { background: #064e3b; color: #bbf7d0; }
                                .dark .sam-meta { color: #cbd5e1; }
                            </style>

                            <table class="sam-table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Solicitation</th>
                                        <th>Notice</th>
                                        <th>Posted</th>
                                        <th>Due</th>
                                        <th>NAICS</th>
                                        <th>Set-Aside</th>
                                        <th>Agency</th>
                                        <th>State</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($slice as $opp)
                                        <tr>
                                            <td>
                                                <div class="leading-snug">
                                                    @if(!empty($opp['sam_url']))
                                                        <a href="{{ $opp['sam_url'] }}" target="_blank" class="sam-link">{{ $opp['title'] }}</a>
                                                    @else
                                                        {{ $opp['title'] }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="sam-mono sam-meta">
                                                @if(!empty($opp['sam_url']))
                                                    <a href="{{ $opp['sam_url'] }}" target="_blank" class="sam-link">{{ $opp['solicitation_number'] }}</a>
                                                @else
                                                    {{ $opp['solicitation_number'] }}
                                                @endif
                                            </td>
                                            <td>
                                                <span class="sam-badge">{{ $opp['notice_type'] }}</span>
                                            </td>
                                            <td class="sam-meta">{{ $opp['posted_date'] ? \Carbon\Carbon::parse($opp['posted_date'])->format('M d, Y') : '—' }}</td>
                                            <td class="sam-meta">{{ $opp['response_deadline'] ? \Carbon\Carbon::parse($opp['response_deadline'])->format('M d, Y') : '—' }}</td>
                                            <td class="sam-mono sam-meta">{{ $opp['naics_code'] }}</td>
                                            <td>
                                                @if(!empty($opp['set_aside_type']))
                                                    <span class="sam-badge sam-badge-green">{{ $opp['set_aside_type'] }}</span>
                                                @else
                                                    <span class="sam-meta">—</span>
                                                @endif
                                            </td>
                                            <td class="sam-meta">
                                                <div class="line-clamp-2">{{ $opp['agency_name'] }}</div>
                                            </td>
                                            <td class="sam-meta">{{ $opp['state_code'] ?? '—' }}</td>
                                        </tr>
                                        <tr class="sm:hidden" data-testid="sam-naics-mobile">
                                            <td colspan="9" class="bg-slate-50/60 px-4 py-2 dark:bg-slate-900/40">
                                                <div class="flex flex-wrap items-center gap-2 text-[11px]">
                                                    <span class="uppercase tracking-wide text-slate-500 dark:text-slate-400">NAICS</span>
                                                    <span class="sam-meta sam-mono">{{ $opp['naics_code'] ?: 'N/A' }}</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <style>
                            .sam-pagination {
                                display: flex;
                                align-items: center;
                                justify-content: space-between;
                                margin-top: 0.75rem;
                                color: #475569;
                                font-size: 0.82rem;
                            }
                            .sam-page-controls {
                                display: inline-flex;
                                align-items: center;
                                gap: 0.35rem;
                                background: #f8fafc;
                                border: 1px solid #e2e8f0;
                                border-radius: 9999px;
                                padding: 0.25rem 0.5rem;
                            }
                            .sam-page-btn {
                                display: inline-flex;
                                align-items: center;
                                gap: 0.25rem;
                                padding: 0.35rem 0.7rem;
                                border-radius: 9999px;
                                font-weight: 600;
                                border: 1px solid transparent;
                                color: #0f172a;
                                text-decoration: none;
                                transition: all 120ms ease;
                            }
                            .sam-page-btn:hover {
                                background: #e2e8f0;
                            }
                            .sam-page-btn.disabled {
                                opacity: 0.45;
                                cursor: not-allowed;
                                background: transparent;
                            }
                            .sam-page-pill {
                                padding: 0.35rem 0.9rem;
                                border-radius: 9999px;
                                background: #e0f2fe;
                                color: #0ea5e9;
                                font-weight: 700;
                            }
                            .dark .sam-pagination { color: #cbd5e1; }
                            .dark .sam-page-controls { background: #0f172a; border-color: #1f2937; }
                            .dark .sam-page-btn { color: #e2e8f0; }
                            .dark .sam-page-btn:hover { background: #111827; }
                            .dark .sam-page-pill { background: #1d4ed8; color: #bfdbfe; }
                        </style>

                        <div class="sam-pagination">
                            <div>
                                Showing {{ $offset + 1 }}–{{ min($offset + $perPage, $total) }} of {{ $total }}
                            </div>
                            @if($pages > 1)
                                @php
                                    $baseUrl = request()->url();
                                    $prev = $page > 1 ? $baseUrl.'?ops_page='.($page - 1) : null;
                                    $next = $page < $pages ? $baseUrl.'?ops_page='.($page + 1) : null;
                                @endphp
                                <div class="sam-page-controls">
                                    <a href="{{ $prev ?? '#' }}" class="sam-page-btn {{ $prev ? '' : 'disabled' }}">Prev</a>
                                    <span class="sam-page-pill">Page {{ $page }} / {{ $pages }}</span>
                                    <a href="{{ $next ?? '#' }}" class="sam-page-btn {{ $next ? '' : 'disabled' }}">Next</a>
                                </div>
                            @endif
                        </div>
                    </x-filament::section>
                @endif

                {{-- Performance Breakdown --}}
                @if(isset($result['performance']))
                    <x-filament::section>
                        <x-slot name="heading">
                            Performance Metrics
                        </x-slot>

                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Total Duration</span>
                                <span class="font-semibold">{{ $result['performance']['total_duration_ms'] ?? 'N/A' }} ms</span>
                            </div>

                            @if(isset($result['summary']['successful_naics_count']))
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Successful NAICS</span>
                                    <span class="font-semibold text-green-600 dark:text-green-400">{{ $result['summary']['successful_naics_count'] }}</span>
                                </div>
                            @endif

                            @if(isset($result['summary']['failed_naics_count']) && $result['summary']['failed_naics_count'] > 0)
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Failed NAICS</span>
                                    <span class="font-semibold text-red-600 dark:text-red-400">{{ $result['summary']['failed_naics_count'] }}</span>
                                </div>
                            @endif

                            @if(isset($result['performance']['cache_hits']))
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Cache Hits</span>
                                    <span class="font-semibold">{{ $result['performance']['cache_hits'] }}</span>
                                </div>
                            @endif

                            @if(isset($result['performance']['api_calls']))
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">API Calls</span>
                                    <span class="font-semibold">{{ $result['performance']['api_calls'] }}</span>
                                </div>
                            @endif
                        </div>
                    </x-filament::section>
                @endif

                {{-- Query Parameters (for debugging) --}}
                @if(isset($result['query']))
                    <x-filament::section class="mt-6">
                        <x-slot name="heading">
                            Query Parameters Used
                        </x-slot>

                        <x-slot name="description">
                            Parameters sent to SAM.gov API
                        </x-slot>

                        <div class="grid grid-cols-1 gap-2 text-xs">
                            <div class="flex justify-between p-2 rounded bg-gray-50 dark:bg-gray-800">
                                <span class="font-medium text-gray-600 dark:text-gray-400">Date Range:</span>
                                <span class="font-mono text-gray-900 dark:text-gray-100">{{ $result['query']['date_range'] ?? 'N/A' }}</span>
                            </div>

                            <div class="flex justify-between p-2 rounded bg-gray-50 dark:bg-gray-800">
                                <span class="font-medium text-gray-600 dark:text-gray-400">State:</span>
                                <span class="font-mono text-gray-900 dark:text-gray-100">{{ $result['query']['state_code'] ?? 'Nationwide' }}</span>
                            </div>

                            @if(!empty($result['query']['naics_codes']))
                                <div class="p-2 rounded bg-gray-50 dark:bg-gray-800">
                                    <span class="font-medium text-gray-600 dark:text-gray-400">NAICS Codes ({{ count($result['query']['naics_codes']) }}):</span>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @foreach($result['query']['naics_codes'] as $naics)
                                            <span class="px-2 py-1 text-xs font-mono rounded bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">{{ $naics }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if(!empty($result['query']['keywords']))
                                <div class="flex justify-between p-2 rounded bg-gray-50 dark:bg-gray-800">
                                    <span class="font-medium text-gray-600 dark:text-gray-400">Keywords:</span>
                                    <span class="font-mono text-gray-900 dark:text-gray-100">
                                        @if(is_array($result['query']['keywords']))
                                            {{ implode(', ', $result['query']['keywords']) }}
                                        @else
                                            {{ $result['query']['keywords'] }}
                                        @endif
                                    </span>
                                </div>
                            @endif

                            @if(!empty($result['query']['notice_types']))
                                <div class="p-2 rounded bg-gray-50 dark:bg-gray-800">
                                    <span class="font-medium text-gray-600 dark:text-gray-400">Notice Types:</span>
                                    <div class="mt-1 text-xs text-gray-700 dark:text-gray-300">
                                        {{ implode(', ', $result['query']['notice_types']) }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </x-filament::section>
                @endif

                {{-- Failed NAICS Details --}}
                @if(!empty($result['summary']['failed_naics']))
                    <x-filament::section class="mt-6">
                        <x-slot name="heading">
                            Failed NAICS Codes
                        </x-slot>

                        <x-slot name="description">
                            Detailed error information for troubleshooting
                        </x-slot>

                        <div class="space-y-3">
                            @foreach($result['summary']['failed_naics'] as $failed)
                                <div class="p-3 rounded-lg border-2 border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20">
                                    <div class="flex items-start justify-between mb-2">
                                        <span class="text-sm font-mono font-semibold text-red-700 dark:text-red-300">NAICS: {{ $failed['naics'] }}</span>
                                        @if(isset($failed['status_code']) && $failed['status_code'])
                                            <x-filament::badge color="danger" size="xs">HTTP {{ $failed['status_code'] }}</x-filament::badge>
                                        @endif
                                    </div>

                                    <p class="mt-1 text-sm font-medium text-red-800 dark:text-red-300">{{ $failed['error'] ?? ($failed['message'] ?? 'Unknown error') }}</p>

                                    @if(isset($failed['error_type']))
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                                            Type: <span class="font-mono">{{ $failed['error_type'] }}</span>
                                        </p>
                                    @endif

                                    @if(isset($failed['response_body']) && !empty($failed['response_body']))
                                        <details class="mt-2">
                                            <summary class="text-xs text-red-600 dark:text-red-400 cursor-pointer hover:text-red-700 dark:hover:text-red-300">
                                                Show API Response
                                            </summary>
                                            <pre class="mt-2 p-2 text-xs bg-red-100 dark:bg-red-950 rounded overflow-x-auto">{{ $failed['response_body'] }}</pre>
                                        </details>
                                    @endif

                                    {{-- Helpful hints based on error type --}}
                                    @if(isset($failed['status_code']))
                                        <div class="mt-2 pt-2 border-t border-red-200 dark:border-red-800">
                                            <p class="text-xs text-red-700 dark:text-red-400">
                                                @if($failed['status_code'] == 401)
                                                    Check that SAM_API_KEY is set correctly in your .env file.
                                                @elseif($failed['status_code'] == 429)
                                                    Too many requests. The system will retry automatically.
                                                @elseif($failed['status_code'] >= 500)
                                                    SAM.gov server issue. Try again in a few minutes.
                                                @elseif($failed['status_code'] == 404)
                                                    API endpoint may have changed. Check SAM.gov API documentation.
                                                @else
                                                    Contact support if this error persists.
                                                @endif
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- General troubleshooting tips --}}
                        <div class="mt-4 p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                            <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Common Solutions:</h4>
                            <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1 list-disc list-inside">
                                <li>Check your internet connection</li>
                                <li>Check that SAM_API_KEY is set correctly in your .env file.</li>
                                <li>Try reducing the number of NAICS codes</li>
                                <li>SAM.gov may be experiencing downtime</li>
                            </ul>
                        </div>
                    </x-filament::section>
                @endif
            @else
                {{-- No Data State --}}
                <x-filament::section>
                    <x-slot name="heading">
                        Results Summary
                    </x-slot>

                    <div class="py-12 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-4 text-sm font-medium text-gray-900 dark:text-gray-100">No Results Yet</h3>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Configure your query and click "Fetch Opportunities" to see results here
                        </p>
                    </div>
                </x-filament::section>
            @endif
        </div>
    </div>

    <x-filament-actions::modals />
    </div>{{-- End outer wrapper --}}

    <script>
        // Direct script tag - bypassing @script directive
        console.log('SAM Control Panel JavaScript loaded (direct script tag).');

        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing SAM fetch polling...');

            let pollingInterval = null;
            let pollCount = 0;
            const MAX_POLL_COUNT = 120; // 120 polls * 3 seconds = 6 minutes timeout
            const statusMessages = [
                'Connecting to SAM.gov API...',
                'Fetching opportunities from NAICS codes...',
                'Processing results...',
                'Removing duplicates...',
                'Applying filters...',
                'Almost done...',
                'Finalizing results...'
            ];

            // Wait for Livewire to be ready
            document.addEventListener('livewire:initialized', function() {
                console.log('Livewire initialized');
                console.log('$wire object:', typeof $wire !== 'undefined' ? 'exists' : 'MISSING');

                // Expose test function to console for manual testing
                window.testSamPolling = function() {
                    console.log('Manual test: Triggering start-polling event');
                    const component = Livewire.find(document.querySelector('[wire\:id]').getAttribute('wire:id'));
                    if (component) {
                        component.dispatch('start-polling');
                    } else {
                        console.error('Livewire component not found');
                    }
                };
                console.log('Tip: Run window.testSamPolling() in console to manually test polling');
            });

            // Listen for Livewire event to start polling
            Livewire.on('start-polling', () => {
                console.log('Start polling event received');

                // Show spinner using Alpine
                Alpine.store('spinner', true);
                const spinnerEl = document.querySelector('[x-data*="showSpinner"]');
                if (spinnerEl && spinnerEl.__x) {
                    spinnerEl.__x.$data.showSpinner = true;
                }

                if (pollingInterval) {
                    clearInterval(pollingInterval);
                }

                pollCount = 0;

                // Poll every 3 seconds
                pollingInterval = setInterval(() => {
                    pollCount++;

                    // Check if we've exceeded the timeout
                    if (pollCount > MAX_POLL_COUNT) {
                        clearInterval(pollingInterval);
                        pollingInterval = null;
                        pollCount = 0;

                        // Hide spinner
                        const spinnerEl = document.querySelector('[x-data*="showSpinner"]');
                        if (spinnerEl && spinnerEl.__x) {
                            spinnerEl.__x.$data.showSpinner = false;
                        }

                        // Show timeout notification
                        new FilamentNotification()
                            .title('Fetch Timeout')
                            .body('The fetch operation is taking longer than expected. The job may still be running in the background. Check the queue worker logs or refresh the page in a few minutes.')
                            .danger()
                            .persistent()
                            .send();

                        return;
                    }

                    // Update status message every 10 seconds (every ~3 polls)
                    if (pollCount % 3 === 0) {
                        const messageIndex = Math.min(Math.floor(pollCount / 3) - 1, statusMessages.length - 1);
                        if (messageIndex >= 0) {
                            console.log('SAM Fetch Status:', statusMessages[messageIndex]);
                        }
                    }

                    const component = Livewire.find(document.querySelector('[wire\:id]').getAttribute('wire:id'));
                    if (component) {
                        component.call('checkFetchStatus').then((isComplete) => {
                            console.log('Poll check - isComplete:', isComplete, 'pollCount:', pollCount);

                            if (isComplete && pollingInterval) {
                                console.log('Fetch complete! Reloading page in 1 second...');

                                clearInterval(pollingInterval);
                                pollingInterval = null;
                                pollCount = 0;

                                // Hide spinner
                                const spinnerEl = document.querySelector('[x-data*="showSpinner"]');
                                if (spinnerEl && spinnerEl.__x) {
                                    spinnerEl.__x.$data.showSpinner = false;
                                }

                                // Reload page to show new results
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            }
                        });
                    }
                }, 3000);
            });

            // Listen for stop polling event
            Livewire.on('stop-polling', () => {
                console.log('Stop polling event received');
                if (pollingInterval) {
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                    pollCount = 0;
                }

                // Hide spinner
                const spinnerEl = document.querySelector('[x-data*="showSpinner"]');
                if (spinnerEl && spinnerEl.__x) {
                    spinnerEl.__x.$data.showSpinner = false;
                }
            });

            // Clean up on page unload
            window.addEventListener('beforeunload', () => {
                if (pollingInterval) {
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                    pollCount = 0;
                }
            });
        }); // End DOMContentLoaded
    </script>
</x-filament-panels::page>
