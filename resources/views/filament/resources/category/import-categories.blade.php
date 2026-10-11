<x-filament-panels::page>
    <form wire:submit="analyze">
        {{ $this->form }}

        <div class="mt-4 flex gap-3">
            @foreach ($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </form>

    @if ($preview !== null)
        <x-filament::section class="mt-6">
            <x-slot name="heading">Preview</x-slot>
            <x-slot name="description">Dry run — nothing has been saved yet.</x-slot>

            <div class="flex flex-wrap gap-6 text-sm">
                <div>
                    <div class="text-2xl font-bold text-success-600">{{ $preview['newCount'] }}</div>
                    <div class="text-gray-500">New categories</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-600">{{ $preview['reuseCount'] }}</div>
                    <div class="text-gray-500">Existing (reused)</div>
                </div>
                <div>
                    <div class="text-2xl font-bold {{ $preview['errorCount'] > 0 ? 'text-danger-600' : 'text-gray-400' }}">
                        {{ $preview['errorCount'] }}
                    </div>
                    <div class="text-gray-500">Errors</div>
                </div>
            </div>

            @if ($preview['errorCount'] > 0)
                <div class="mt-4">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-1 pr-4">Row</th>
                                <th class="py-1 pr-4">Problem</th>
                                <th class="py-1">Values</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($preview['errors'] as $error)
                                <tr class="border-t border-gray-100 dark:border-gray-700">
                                    <td class="py-1 pr-4 font-mono">{{ $error['row'] }}</td>
                                    <td class="py-1 pr-4 text-danger-600">{{ $error['message'] }}</td>
                                    <td class="py-1 text-gray-500">
                                        {{ collect($error['values'] ?? [])->filter()->map(fn ($v, $k) => "$k: $v")->join(', ') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="mt-6">
                @if ($preview['isValid'] && $preview['newCount'] > 0)
                    <x-filament::button wire:click="confirm" color="success" icon="heroicon-o-check">
                        Confirm & import {{ $preview['newCount'] }} categories
                    </x-filament::button>
                @elseif ($preview['isValid'] && $preview['newCount'] === 0)
                    <p class="text-sm text-gray-500">Nothing new to import — every category already exists.</p>
                @else
                    <p class="text-sm text-danger-600">Fix the errors above and re-upload before importing.</p>
                @endif
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
