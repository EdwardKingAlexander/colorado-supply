<x-filament-panels::page>
    @php
        $totalLinks = \App\Models\BusinessLink::count();
        $activeLinks = \App\Models\BusinessLink::active()->count();
        $inactiveLinks = \App\Models\BusinessLink::where('is_active', false)->count();
        $categoryCount = \App\Models\BusinessLink::distinct()->count('category');
        $quickLinks = \App\Models\BusinessLink::active()
            ->ordered()
            ->get()
            ->groupBy(fn ($link) => $link->category->label());
    @endphp
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-base font-semibold text-gray-900 dark:text-white">Business links</h1>
                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">Curate the portals, tools, and partner sites your team uses daily.</p>
            </div>
            <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                <a href="{{ route('filament.admin.resources.business-links.create') }}" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-500">
                    Add link
                </a>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-md border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900 {{ $inactiveLinks ? 'border-amber-200 dark:border-amber-500/40' : '' }}">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Inactive</dt>
                <dd class="mt-1 text-2xl font-semibold tracking-tight {{ $inactiveLinks ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white' }}">{{ $inactiveLinks }}</dd>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Categories</dt>
                <dd class="mt-1 text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $categoryCount }}</dd>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Active</dt>
                <dd class="mt-1 text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $activeLinks }}</dd>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total</dt>
                <dd class="mt-1 text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $totalLinks }}</dd>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="overflow-hidden rounded-md border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
                    <div class="border-b border-gray-200 px-4 py-4 dark:border-white/10 sm:px-6">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Link registry</h2>
                    </div>
                    <div class="filament-table-clean">
                        {{ $this->content }}
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="overflow-hidden rounded-md border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
                    <div class="border-b border-gray-200 px-4 py-4 dark:border-white/10">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Active quick links</h2>
                    </div>
                    <div class="space-y-6 px-4 py-4 sm:px-6">
                        @forelse($quickLinks as $category => $links)
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">{{ $category }}</div>
                                <div class="mt-3 space-y-2">
                                    @foreach($links->take(4) as $link)
                                        <a href="{{ $link->url }}" target="_blank" class="block rounded-md border border-gray-200 bg-white p-3 text-sm text-gray-900 hover:bg-gray-50 dark:border-white/10 dark:bg-gray-900 dark:text-white dark:hover:bg-gray-800">
                                            <div class="font-semibold">{{ $link->name }}</div>
                                            @if($link->description)
                                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $link->description }}</div>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="py-8 text-center">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">No quick links yet</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add a few shortcuts for your team.</p>
                                <a href="{{ route('filament.admin.resources.business-links.create') }}" class="mt-4 inline-block rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-500">
                                    Add your first link
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="overflow-hidden rounded-md border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
                    <div class="border-b border-gray-200 px-4 py-4 dark:border-white/10">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Business hub</h2>
                    </div>
                    <nav class="divide-y divide-gray-200 dark:divide-white/10">
                        <a href="{{ \App\Filament\Pages\BusinessHubDashboard::getUrl() }}" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 sm:px-6">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Dashboard</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Compliance overview and alerts.</p>
                            </div>
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Open</span>
                        </a>
                        <a href="{{ route('filament.admin.resources.business-documents.index') }}" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 sm:px-6">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Documents</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Certificates, insurance, and compliance files.</p>
                            </div>
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Open</span>
                        </a>
                        <a href="{{ route('filament.admin.resources.business-deadlines.index') }}" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 sm:px-6">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Deadlines</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Keep filings and renewals on schedule.</p>
                            </div>
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Open</span>
                        </a>
                        <a href="{{ route('filament.admin.resources.business-links.index') }}" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 sm:px-6">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Quick links</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Curate the portals your team uses most.</p>
                            </div>
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Open</span>
                        </a>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Clean table styling to match reference */
        .filament-table-clean .fi-ta-ctn {
            background: transparent;
            border: none;
            box-shadow: none;
            border-radius: 0;
        }

        .filament-table-clean .fi-ta-header-ctn {
            border-bottom: 1px solid rgb(229 231 235);
        }

        .dark .filament-table-clean .fi-ta-header-ctn {
            border-color: rgb(255 255 255 / 0.1);
        }

        .filament-table-clean table {
            @apply min-w-full divide-y divide-gray-300 dark:divide-white/15;
        }

        .filament-table-clean th {
            @apply py-3.5 px-3 text-left text-sm font-semibold text-gray-900 dark:text-white;
        }

        .filament-table-clean tbody {
            @apply divide-y divide-gray-200 bg-white dark:divide-white/10 dark:bg-gray-900;
        }

        .filament-table-clean td {
            @apply whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400;
        }

        .filament-table-clean tr:hover {
            @apply bg-gray-50 dark:bg-gray-800;
        }
    </style>
</x-filament-panels::page>
