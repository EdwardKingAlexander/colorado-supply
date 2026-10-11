@php($files = $getRecord()->files)
<div class="flex flex-wrap gap-2">
    @forelse ($files as $file)
        <x-filament::button size="sm" color="gray" icon="heroicon-o-arrow-down-tray" wire:click="downloadEvidence({{ $file->id }})" wire:key="evidence-{{ $file->id }}">
            {{ $file->original_name }} <span class="text-xs opacity-70">({{ \Illuminate\Support\Number::fileSize($file->size) }})</span>
        </x-filament::button>
    @empty
        <span class="text-sm text-gray-500">None attached</span>
    @endforelse
</div>
