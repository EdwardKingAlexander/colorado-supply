<x-filament-panels::page>
    <p class="text-sm text-gray-600 dark:text-gray-300">
        Choose how you hear about store activity. Everything starts on; turning a channel off here affects only you.
    </p>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit" icon="heroicon-o-check">Save</x-filament::button>
    </form>
</x-filament-panels::page>
