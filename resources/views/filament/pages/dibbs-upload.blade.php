<x-filament-panels::page>
    @php($health = app(\App\Services\Dibbs\DibbsHealth::class)->status())

    <x-filament::section>
        <x-slot name="heading">Ingestion health</x-slot>

        <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4" data-testid="dibbs-health">
            <div>
                <div class="text-gray-500 dark:text-gray-400">Latest RFQ file</div>
                <div class="font-semibold {{ $health['stale'] ? 'text-danger-600 dark:text-danger-400' : '' }}">
                    {{ $health['latest_file']?->posting_date?->format('D, M j') ?? 'None yet' }}
                    @if ($health['business_days_behind'] !== null)
                        <span class="font-normal text-gray-500 dark:text-gray-400">· {{ $health['business_days_behind'] }} business day(s) ago</span>
                    @endif
                </div>
            </div>
            <div>
                <div class="text-gray-500 dark:text-gray-400">Last automatic download</div>
                <div class="font-semibold">
                    @if ($health['last_run'])
                        {{ \Carbon\Carbon::parse($health['last_run']['at'])->diffForHumans() }}
                        · <span class="{{ $health['last_run']['ok'] ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">{{ $health['last_run']['ok'] ? 'ok' : 'failed' }}</span>
                    @else
                        Never
                    @endif
                </div>
            </div>
            <div>
                <div class="text-gray-500 dark:text-gray-400">Consecutive failures</div>
                <div class="font-semibold {{ $health['consecutive_failures'] > 0 ? 'text-danger-600 dark:text-danger-400' : '' }}">{{ $health['consecutive_failures'] }}</div>
            </div>
            <div>
                <div class="text-gray-500 dark:text-gray-400">Next automatic download</div>
                <div class="font-semibold">{{ $health['enabled'] ? $health['next_run']->setTimezone('America/Denver')->format('D, M j g:i a T') : 'Disabled (DIBBS_ENABLED=false)' }}</div>
            </div>
        </div>

        @if ($health['stale'])
            <p class="mt-3 text-sm text-danger-600 dark:text-danger-400">
                DIBBS data is behind. If the automatic download keeps failing, download the day's files from DIBBS and upload them below.
                @if (! empty($health['last_run']['error'])) Last error: {{ $health['last_run']['error'] }} @endif
            </p>
        @endif
    </x-filament::section>

    <form wire:submit="import">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit" icon="heroicon-o-arrow-up-tray" wire:loading.attr="disabled" wire:target="import">
                <span wire:loading.remove wire:target="import">Import files</span>
                <span wire:loading wire:target="import">Importing…</span>
            </x-filament::button>
        </div>
    </form>

    <x-filament::section class="mt-6">
        <x-slot name="heading">Recent DIBBS files</x-slot>
        <x-slot name="description">Downloaded automatically each morning, or uploaded here. Identical files are only imported once.</x-slot>

        @php($files = $this->getRecentFiles())

        @if ($files->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">No DIBBS files yet.</p>
        @else
            <div class="space-y-2">
                @foreach ($files as $file)
                    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-800" data-testid="dibbs-file-row">
                        <div class="min-w-0">
                            <span class="font-mono font-semibold">{{ $file->name }}</span>
                            <span class="text-gray-500 dark:text-gray-400">· {{ $file->posting_date?->format('M j, Y') ?? 'unknown date' }} · {{ $file->source }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-gray-600 dark:text-gray-300">{{ number_format($file->row_count) }} rows</span>
                            @if ($file->error_count > 0)
                                <span class="text-warning-600 dark:text-warning-400">{{ $file->error_count }} row problems</span>
                            @endif
                            <x-filament::badge :color="match ($file->status) { 'imported' => 'success', 'failed' => 'danger', default => 'gray' }">
                                {{ $file->status }}
                            </x-filament::badge>
                        </div>
                        @if ($file->error)
                            <div class="w-full text-xs text-danger-600 dark:text-danger-400">{{ $file->error }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
