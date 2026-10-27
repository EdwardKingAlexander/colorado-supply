<x-filament-panels::page>
    @php($profile = $this->getProfile())

    <x-filament::section>
        <div class="text-sm" data-testid="dibbs-profile-status">
            @if ($profile->isConfirmed())
                <span class="font-semibold text-success-600 dark:text-success-400">Confirmed</span>
                by {{ $profile->confirmer?->name ?? 'unknown' }} on {{ $profile->confirmed_at->format('M j, Y g:i a') }}.
                Batch quote export uses these answers on every quote.
            @else
                <span class="font-semibold text-warning-600 dark:text-warning-400">Not confirmed.</span>
                Batch quote export is blocked until every answer is entered and confirmed.
            @endif
        </div>
    </x-filament::section>

    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit" icon="heroicon-o-check">Save and confirm</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
