<x-filament-panels::page>
    @php
        $totalDocuments = \App\Models\BusinessDocument::count();
        $activeDocuments = \App\Models\BusinessDocument::active()->count();
        $expiredQuery = \App\Models\BusinessDocument::query()
            ->where('status', \App\Enums\DocumentStatus::Expired)
            ->orWhere(function ($query) {
                $query->whereNotNull('expiration_date')
                    ->where('expiration_date', '<', now());
            });
        $expiredDocuments = (clone $expiredQuery)
            ->orderBy('expiration_date', 'desc')
            ->limit(5)
            ->get();
        $expiredCount = (clone $expiredQuery)->count();
        $expiringDocuments = \App\Models\BusinessDocument::expiringSoon(30)
            ->orderBy('expiration_date')
            ->limit(5)
            ->get();
        $expiringCount = \App\Models\BusinessDocument::expiringSoon(30)->count();
    @endphp

    <div class="px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-base font-semibold text-gray-900 dark:text-white">Business documents</h1>
                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">Track certificates, licenses, and compliance files across your organization.</p>
            </div>
            <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                <a href="{{ route('filament.admin.resources.business-documents.create') }}" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-500">
                    Add document
                </a>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-md border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900 {{ $expiredCount ? 'border-red-200 dark:border-red-500/40' : '' }}">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Expired</dt>
                <dd class="mt-1 text-2xl font-semibold tracking-tight {{ $expiredCount ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ $expiredCount }}</dd>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900 {{ $expiringCount ? 'border-amber-200 dark:border-amber-500/40' : '' }}">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Expiring Soon</dt>
                <dd class="mt-1 text-2xl font-semibold tracking-tight {{ $expiringCount ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white' }}">{{ $expiringCount }}</dd>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Active</dt>
                <dd class="mt-1 text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $activeDocuments }}</dd>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total</dt>
                <dd class="mt-1 text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $totalDocuments }}</dd>
            </div>
        </div>

        @if($expiredCount || $expiringCount)
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @if($expiredCount)
                    <div class="rounded-md border border-red-200 bg-red-50 p-4 dark:border-red-500/40 dark:bg-red-900/20">
                        <p class="text-sm font-semibold text-red-800 dark:text-red-200">{{ $expiredCount }} expired {{ Str::plural('document', $expiredCount) }}</p>
                        <p class="mt-1 text-sm text-red-700 dark:text-red-300">Immediate action required.</p>
                        <ul class="mt-3 space-y-1 text-sm text-red-700 dark:text-red-300">
                            @foreach($expiredDocuments as $document)
                                <li>{{ $document->name }} <span class="text-red-600 dark:text-red-300">({{ $document->expiration_date?->diffForHumans() ?? 'Expired' }})</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($expiringCount)
                    <div class="rounded-md border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/40 dark:bg-amber-900/20">
                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">{{ $expiringCount }} {{ Str::plural('document', $expiringCount) }} expiring soon</p>
                        <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">Next 30 days.</p>
                        <ul class="mt-3 space-y-1 text-sm text-amber-700 dark:text-amber-300">
                            @foreach($expiringDocuments as $document)
                                <li>{{ $document->name }} <span class="text-amber-600 dark:text-amber-400">({{ $document->expiration_date?->format('M j, Y') }})</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="overflow-hidden rounded-md border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
                    <div class="border-b border-gray-200 px-4 py-4 dark:border-white/10 sm:px-6">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Document registry</h2>
                    </div>
                    <div class="filament-table-clean">
                        {{ $this->content }}
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="overflow-hidden rounded-md border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
                    <div class="border-b border-gray-200 px-4 py-4 dark:border-white/10">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Expiring soon</h2>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse($expiringDocuments as $document)
                            @php
                                $days = $document->daysUntilExpiration();
                                $isUrgent = $days !== null && $days <= 14;
                            @endphp
                            <div class="px-4 py-3 sm:px-6">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $document->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $document->type->label() }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold {{ $isUrgent ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400' }}">
                                            {{ $document->expiration_date?->format('M j') }}
                                        </p>
                                        @if($days !== null)
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $days }}d left</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="px-4 py-8 text-center">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">All clear</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No documents expiring in the next 30 days.</p>
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
                                <p class="text-xs text-gray-500 dark:text-gray-400">Overview and alerts</p>
                            </div>
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Open</span>
                        </a>
                        <a href="{{ route('filament.admin.resources.business-deadlines.index') }}" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 sm:px-6">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Deadlines</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Filings and renewals</p>
                            </div>
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Open</span>
                        </a>
                        <a href="{{ route('filament.admin.resources.business-links.index') }}" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 sm:px-6">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Quick links</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Portals and resources</p>
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
