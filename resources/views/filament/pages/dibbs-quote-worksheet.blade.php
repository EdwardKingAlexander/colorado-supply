<x-filament-panels::page>
    <x-filament::section>
        <p class="text-sm text-gray-600 dark:text-gray-300">
            Price the RFQs you're pursuing, fix anything flagged under <strong>Ready?</strong>, then <strong>Review &amp; export</strong>.
            The export fills your answers into DLA's own batch quote rows and downloads a file you upload in DIBBS yourself.
            Quotes with exceptions, foreign end products or alternate parts aren't supported here; quote those in DIBBS directly.
        </p>
    </x-filament::section>

    {{ $this->table }}
</x-filament-panels::page>
