<x-filament-panels::page>
    @if ($opportunity = $this->focusedOpportunity)
        <x-filament::section>
            <x-slot name="heading">{{ $opportunity->title }}</x-slot>
            <x-slot name="description">Selected opportunity</x-slot>
            <div class="space-y-4">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-sm text-gray-500">Agency</dt><dd>{{ $opportunity->agency ?: 'Not provided' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Solicitation</dt><dd>{{ $opportunity->solicitation_number ?: 'Not provided' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Response deadline</dt><dd>{{ $opportunity->response_deadline?->format('M j, Y') ?: 'Not provided' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">NAICS / PSC</dt><dd>{{ $opportunity->naics_code ?: '—' }} / {{ $opportunity->psc_code ?: '—' }}</dd></div>
                </dl>
                <p class="whitespace-pre-line break-words">{{ $opportunity->description ?: 'No description provided.' }}</p>
                <div class="flex flex-wrap gap-3">
                    @if ($opportunity->is_favorite)
                        <x-filament::button wire:click="unfavorite({{ $opportunity->id }})" wire:loading.attr="disabled" color="gray">Remove favorite</x-filament::button>
                    @else
                        <x-filament::button wire:click="favorite({{ $opportunity->id }})" wire:loading.attr="disabled">Add to favorites</x-filament::button>
                    @endif
                    @if ($sourceUrl = \App\Filament\Pages\SamFavorites::sourceUrl($opportunity))
                        <x-filament::button tag="a" :href="$sourceUrl" target="_blank" rel="noopener noreferrer" color="gray">View on SAM.gov</x-filament::button>
                    @endif
                    <x-filament::button tag="a" :href="\App\Filament\Pages\SamInsightsDashboard::getUrl(panel: 'admin')" color="gray">Back to SAM Insights</x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endif
    {{ $this->table }}
</x-filament-panels::page>
