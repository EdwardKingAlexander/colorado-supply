<x-filament-panels::page>
    <form wire:submit="analyze">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">Preview import</x-filament::button>
        </div>
    </form>

    @if ($preview !== null)
        <x-filament::section class="mt-6">
            <x-slot name="heading">Preview</x-slot>
            <x-slot name="description">Dry run. Nothing has been saved yet.</x-slot>

            <div class="flex flex-wrap gap-6 text-sm">
                @foreach ([
                    ['New parts', $preview['new_parts'], 'text-success-600'],
                    ['Updated parts', $preview['updated_parts'], 'text-gray-600 dark:text-gray-300'],
                    ['New manufacturers', $preview['new_manufacturers'], 'text-gray-600 dark:text-gray-300'],
                    ['New suppliers', $preview['new_suppliers'], 'text-gray-600 dark:text-gray-300'],
                    ['Errors', count($preview['errors']), count($preview['errors']) ? 'text-danger-600' : 'text-gray-400'],
                ] as [$label, $value, $color])
                    <div>
                        <div class="text-2xl font-bold {{ $color }}">{{ $value }}</div>
                        <div class="text-gray-500 dark:text-gray-400">{{ $label }}</div>
                    </div>
                @endforeach
            </div>

            @if (count($preview['errors']) > 0)
                <div class="mt-4 space-y-1 text-sm" data-testid="catalog-import-errors">
                    @foreach ($preview['errors'] as $error)
                        <div><span class="font-mono text-gray-500">Row {{ $error['row'] }}:</span> <span class="text-danger-600">{{ $error['message'] }}</span></div>
                    @endforeach
                </div>
            @endif

            <div class="mt-6">
                @if ($preview['is_valid'])
                    <x-filament::button wire:click="confirm" color="success" icon="heroicon-o-check">
                        Confirm & import {{ $preview['new_parts'] + $preview['updated_parts'] }} parts
                    </x-filament::button>
                @else
                    <p class="text-sm text-danger-600">Fix the errors above and re-upload before importing.</p>
                @endif
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
