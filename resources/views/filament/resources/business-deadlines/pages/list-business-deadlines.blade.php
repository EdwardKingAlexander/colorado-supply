<x-filament-panels::page>
    @php
        $totalDeadlines = \App\Models\BusinessDeadline::count();
        $overdueDeadlines = \App\Models\BusinessDeadline::overdue()
            ->orderBy('due_date')
            ->limit(5)
            ->get();
        $overdueCount = \App\Models\BusinessDeadline::overdue()->count();
        $dueSoonDeadlines = \App\Models\BusinessDeadline::upcoming(14)
            ->orderBy('due_date')
            ->limit(5)
            ->get();
        $dueSoonCount = \App\Models\BusinessDeadline::upcoming(14)->count();
        $completedCount = \App\Models\BusinessDeadline::completed()->count();
        $nextDeadlines = \App\Models\BusinessDeadline::upcoming(30)
            ->orderBy('due_date')
            ->limit(6)
            ->get();
    @endphp
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-base font-semibold text-gray-900 dark:text-white">Business deadlines</h1>
                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">Stay ahead of filings, renewals, and recurring compliance obligations.</p>
            </div>
            <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                <a href="{{ route('filament.admin.resources.business-deadlines.create') }}" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-500">
                    Add deadline
                </a>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-md border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900 {{ $overdueCount ? 'border-red-200 dark:border-red-500/40' : '' }}">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Overdue</dt>
                <dd class="mt-1 text-2xl font-semibold tracking-tight {{ $overdueCount ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ $overdueCount }}</dd>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900 {{ $dueSoonCount ? 'border-amber-200 dark:border-amber-500/40' : '' }}">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Due 14d</dt>
                <dd class="mt-1 text-2xl font-semibold tracking-tight {{ $dueSoonCount ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white' }}">{{ $dueSoonCount }}</dd>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Completed</dt>
                <dd class="mt-1 text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $completedCount }}</dd>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total</dt>
                <dd class="mt-1 text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $totalDeadlines }}</dd>
            </div>
        </div>

        @if($overdueCount || $dueSoonCount)
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @if($overdueCount)
                    <div class="rounded-md border border-red-200 bg-red-50 p-4 dark:border-red-500/40 dark:bg-red-900/20">
                        <p class="text-sm font-semibold text-red-800 dark:text-red-200">
                            {{ $overdueCount }} overdue {{ \Illuminate\Support\Str::plural('deadline', $overdueCount) }}
                        </p>
                        <p class="mt-1 text-sm text-red-700 dark:text-red-300">Immediate action needed.</p>
                        <div class="mt-3 space-y-2 text-sm text-red-700 dark:text-red-300">
                            @foreach($overdueDeadlines as $deadline)
                                <div class="flex items-center justify-between gap-4">
                                    <span>{{ $deadline->title }}</span>
                                    <span class="text-xs font-semibold">{{ abs($deadline->daysUntilDue()) }}d overdue</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($dueSoonCount)
                    <div class="rounded-md border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/40 dark:bg-amber-900/20">
                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                            {{ $dueSoonCount }} {{ \Illuminate\Support\Str::plural('deadline', $dueSoonCount) }} due soon
                        </p>
                        <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">Next 14 days.</p>
                        <div class="mt-3 space-y-2 text-sm text-amber-700 dark:text-amber-300">
                            @foreach($dueSoonDeadlines as $deadline)
                                <div class="flex items-center justify-between gap-4">
                                    <span>{{ $deadline->title }}</span>
                                    <span class="text-xs font-semibold">{{ $deadline->due_date?->format('M j') ?? 'TBD' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="overflow-hidden rounded-md border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
                    <div class="border-b border-gray-200 px-4 py-4 dark:border-white/10 sm:px-6">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Deadline tracker</h2>
                    </div>
                    <div class="filament-table-clean">
                        {{ $this->content }}
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="overflow-hidden rounded-md border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
                    <div class="border-b border-gray-200 px-4 py-4 dark:border-white/10">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Next up</h2>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse($nextDeadlines as $deadline)
                            @php
                                $days = $deadline->daysUntilDue();
                            @endphp
                            <div class="px-4 py-4 sm:px-6">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $deadline->title }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            <span>{{ $deadline->category->label() }}</span>
                                            @if($deadline->recurrence->value !== 'once')
                                                <span class="mx-2 text-gray-300 dark:text-gray-600">|</span>
                                                <span>{{ $deadline->recurrence->label() }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $deadline->due_date?->format('M j, Y') ?? 'TBD' }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $days }} days</p>
                                        @if($deadline->external_url)
                                            <a href="{{ $deadline->external_url }}" target="_blank" class="mt-2 inline-block text-xs font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                File now
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="px-4 py-8 text-center">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">All caught up</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No upcoming deadlines in the next 30 days.</p>
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
