<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Fetch National Stock Number (NSN) Data
            </x-slot>

            <x-slot name="description">
                Enter an NSN to fetch and save its manufacturer and part data from external sources.
            </x-slot>

            {{ $this->form }}

            <div class="mt-4 flex gap-3">
                @foreach($this->getFormActions() as $action)
                    {{ $action }}
                @endforeach
            </div>
        </x-filament::section>

        @if (isset($this->result))
            <x-filament::section>
                <x-slot name="heading">
                    Last Operation Result
                </x-slot>

                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        @if ($this->result['success'])
                            <x-heroicon-o-check-circle class="h-5 w-5 text-success-500" />
                            <span class="font-medium text-success-600 dark:text-success-400">Success</span>
                        @else
                            <x-heroicon-o-x-circle class="h-5 w-5 text-danger-500" />
                            <span class="font-medium text-danger-600 dark:text-danger-400">Failed</span>
                        @endif
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $this->result['message'] }}
                    </p>

                    @if (!empty($this->result['nsn']))
                        <div class="mt-4 rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">NSN</dt>
                                    <dd class="text-sm font-mono text-gray-900 dark:text-gray-100">{{ $this->result['nsn'] }}</dd>
                                </div>
                                @if (!empty($this->result['description']))
                                    <div class="sm:col-span-2">
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Description</dt>
                                        <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $this->result['description'] }}</dd>
                                    </div>
                                @endif
                                @if (!empty($this->result['manufacturer']))
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Manufacturer</dt>
                                        <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $this->result['manufacturer'] }}</dd>
                                    </div>
                                @endif
                            </dl>

                            @if (!empty($this->result['mil_spec_part_id']))
                                <div class="mt-4">
                                    <a href="{{ \App\Filament\Resources\MilSpecParts\MilSpecPartResource::getUrl('edit', ['record' => $this->result['mil_spec_part_id']]) }}"
                                       class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                        <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" />
                                        View Part Details
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($this->result['error'])
                        <div class="mt-2 rounded-md bg-danger-50 dark:bg-danger-900/20 p-3">
                            <p class="text-sm text-danger-700 dark:text-danger-400">
                                <strong>Error:</strong> {{ $this->result['error'] }}
                            </p>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
