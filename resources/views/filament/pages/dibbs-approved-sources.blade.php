<x-filament-panels::page>
    <x-filament::section>
        <p class="text-sm text-gray-600 dark:text-gray-300">
            Companies DLA lists as approved sources on open RFQs in your product codes, most RFQs first.
            Many DLA buys only accept an approved source's exact part. If you can buy from one of these companies,
            <strong>Add as manufacturer</strong>: their RFQs then show as matches, appear in the digest, and pre-fill the
            approved part when you quote. Use <strong>Look up company names</strong> to fill in names from SAM.gov
            (one request covers the whole list; DLA's file usually leaves names blank).
        </p>
    </x-filament::section>

    {{ $this->table }}
</x-filament-panels::page>
