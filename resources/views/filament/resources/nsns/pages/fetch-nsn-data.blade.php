<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Fetch National Stock Number (NSN) Data
            </x-slot>

            <x-slot name="description">
                Enter an NSN to fetch and save its manufacturer, supplier, and procurement history data.
            </x-slot>

            {{ $this->form }}

            <div class="mt-4">
                {{-- Render the form actions --}}
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
                <p><strong>Status:</strong> {{ $this->result['success'] ? 'Success' : 'Failed' }}</p>
                <p><strong>Message:</strong> {{ $this->result['message'] }}</p>
                @if ($this->result['error'])
                    <p><strong>Error:</strong> {{ $this->result['error'] }}</p>
                @endif
            </x-filament::section>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>