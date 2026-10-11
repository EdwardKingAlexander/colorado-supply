<x-filament-panels::page>
    <style>
        .publog { display: grid; gap: 1.25rem; min-width: 0; }
        .publog form, .publog-card { display: grid; gap: 1rem; }
        .publog-actions { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; }
        .publog-card { padding: 1.25rem; border: 1px solid #cbd5e1; border-radius: .75rem; min-width: 0; overflow-wrap: anywhere; }
        .dark .publog-card { border-color: #475569; }
        .publog-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 1rem; }
        .publog h2 { font-size: 1.125rem; font-weight: 650; }
        .publog h3 { font-weight: 650; }
        .publog small, .publog dt { font-size: .875rem; opacity: .8; }
        .publog dl { display: grid; gap: .5rem; }
        .publog-warning { border-left: 4px solid #d97706; padding: .75rem; }
        .publog summary { cursor: pointer; padding: .5rem 0; }
        .publog code { overflow-wrap: anywhere; }
        @media (max-width: 640px) { .publog-grid { grid-template-columns: minmax(0,1fr); } .publog-card { padding: 1rem; } }
    </style>
    <div class="publog">
        <form wire:submit="fetchNsnData">
            {{ $this->form }}
            <div class="publog-actions">
                <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="fetchNsnData">Search PUB LOG</x-filament::button>
                <span wire:loading role="status">Searching catalog…</span>
            </div>
        </form>
        @if (!$result)
            <x-filament::section heading="From reference to verification">
                <p>Search the catalog, review all part/CAGE references, then open CAGE Search to check the company. Contact its sales team to confirm production and authorized distributors before treating it as a current source.</p>
                <p>No messages are sent by this lookup.</p>
            </x-filament::section>
        @else
            <div role="status" aria-live="polite" class="publog-card">
                <h2>{{ match ($result['status']) { 'match' => 'Catalog matches', 'no_match' => 'No catalog matches', 'catalog_unavailable' => 'Catalog not available', 'invalid_input' => 'Check your search', default => 'Lookup temporarily unavailable' } }}</h2>
                <p>{{ $result['message'] }}</p>
                @if ($source = $result['source'])
                    <p>PUB LOG release <strong>{{ $source['release_date'] }}</strong> · {{ strtoupper($result['type']) }}: <code>{{ $result['query'] }}</code></p>
                    @if ($source['stale'])
                        <p class="publog-warning">This catalog is older than {{ config('nsn_lookup.stale_after_days') }} days. Verify current company and part status before sourcing.</p>
                    @endif
                    <details>
                        <summary>Release data quality</summary>
                        <p>Unresolved CAGE references: {{ $source['warnings']['unresolved_cage_references'] ?? 'Not recorded' }}. Orphan item references: {{ $source['warnings']['orphan_references'] ?? 'Not recorded' }}.</p>
                        <p>Nonstandard CAGE source rows: {{ array_sum($source['warnings']['invalid_cage_source_rows'] ?? []) }}. Source anomalies remain visible below.</p>
                    </details>
                @endif
            </div>
            @if ($result['items'])
                <h2>Item identification</h2>
                <div class="publog-grid">
                    @foreach ($result['items'] as $item)
                        <article class="publog-card" wire:key="item-{{ $item['id'] }}">
                            <h3>{{ $item['formatted_nsn'] }} · {{ $item['item_name'] }}</h3>
                            <dl>
                                <div><dt>NIIN / FSC / item name code</dt><dd>{{ $item['niin'] }} / {{ $item['fsc'] }} / {{ $item['inc'] ?: 'Not supplied' }}</dd></div>
                                <div><dt>End item</dt><dd>{{ $item['end_item_name'] ?: 'Not supplied' }}</dd></div>
                                <div><dt>Cancelled NIIN (source value)</dt><dd>{{ $item['cancelled_niin'] ?: 'Not supplied' }}</dd></div>
                            </dl>
                            @if (in_array($result['type'], ['part', 'cage']))
                                <x-filament::button color="gray" wire:click="inspectItem('{{ $item['niin'] }}')">View all references and status for this item</x-filament::button>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
            @if ($result['companies'])
                <h2>Company snapshots · not yet verified in CAGE Search</h2>
                <div class="publog-grid">
                    @foreach ($result['companies'] as $company)
                        <article class="publog-card" wire:key="company-{{ $company['id'] }}">
                            <h3>{{ $company['summary']['COMPANY'] ?? $company['address']['COMPANY_NAME'] ?? 'Company identity not supplied' }}</h3>
                            <p>CAGE <code>{{ $company['cage_code'] }}</code> · status code {{ $company['summary']['CAGE_STATUS'] ?? 'Not supplied' }}</p>
                            <p>{{ implode(', ', array_filter(array_intersect_key($company['address'] ?? [], array_flip(['STREET_ADDRESS_1', 'STREET_ADDRESS_2', 'STREET_ADDRESS_3', 'CITY', 'STATE', 'ZIP', 'COUNTRY'])))) }}</p>
                            @if (!$company['code_valid'])
                                <p class="publog-warning">Source anomaly: this is not a valid five-character CAGE code. Verify the source manually.</p>
                            @else
                                <x-filament::button tag="a" href="https://cage.dla.mil/Search" target="_blank" rel="noopener noreferrer" color="gray">Open CAGE Search</x-filament::button>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
            @if ($result['references'])
                <h2>Part / CAGE references · page {{ $result['page'] }}</h2>
                <p>Reference relationships do not confirm current manufacture, stock, or distributor authorization.</p>
                @foreach ($result['references'] as $reference)
                    @php($item = collect($result['items'])->firstWhere('niin', $reference['niin']))
                    @php($company = collect($result['companies'])->firstWhere('cage_code', $reference['cage_code']))
                    <article class="publog-card" wire:key="reference-{{ $reference['id'] }}">
                        <h3>{{ $reference['part_number'] }}</h3>
                        <p>NSN {{ $item['formatted_nsn'] ?? 'Item unresolved' }} · CAGE <code>{{ $reference['cage_code'] }}</code></p>
                        @if (!$company || !$company['summary'])
                            <p class="publog-warning">Company identity is unresolved in this release. The reference has been preserved.</p>
                        @endif
                        <div class="publog-actions">
                            @if ($reference['cage_code_valid'])
                                <x-filament::button tag="a" href="https://cage.dla.mil/Search" target="_blank" rel="noopener noreferrer" color="gray">Open CAGE Search</x-filament::button>
                                <span x-data="{ copied: false, failed: false, code: @js($reference['cage_code']) }">
                                    <x-filament::button color="gray" x-on:click="copied = false; failed = false; Promise.resolve().then(() => navigator.clipboard.writeText(code)).then(() => { copied = true }).catch(() => { failed = true })">Copy CAGE code</x-filament::button>
                                    <small x-show="copied" x-cloak role="status">Copied</small>
                                    <small x-show="failed" x-cloak role="status">Select and copy the code above.</small>
                                </span>
                            @else
                                <p class="publog-warning">Source anomaly: invalid CAGE format. CAGE Search handoff is unavailable.</p>
                            @endif
                            @if ($item && $item['curated_id'])
                                <x-filament::button tag="a" :href="\App\Filament\Resources\MilSpecParts\MilSpecPartResource::getUrl('edit', ['record' => $item['curated_id']])" color="gray">Review existing curated part</x-filament::button>
                            @elseif ($item)
                                <x-filament::button tag="a" :href="\App\Filament\Resources\MilSpecParts\MilSpecPartResource::getUrl('create', ['catalog_reference' => $reference['id']])" color="gray">Review as new curated part</x-filament::button>
                            @endif
                            @php($sourcing = $this->sourcingCases[$reference['id']] ?? null)
                            @if ($sourcing)
                                <x-filament::button tag="a" :href="$sourcing['url']" color="primary">{{ $sourcing['open'] ? 'Open sourcing case' : 'View closed case' }}</x-filament::button>
                            @elseif ($item && $reference['cage_code_valid'])
                                <x-filament::button color="primary" wire:click="startSourcingCase({{ $reference['id'] }})" wire:loading.attr="disabled" wire:target="startSourcingCase({{ $reference['id'] }})">Start sourcing case</x-filament::button>
                            @endif
                        </div>
                        @if ($sourcing)
                            <p class="publog-sourcing" data-state="{{ $sourcing['state'] }}">
                                Sourcing case: {{ $sourcing['status'] }} ·
                                @if ($sourcing['answered_on'] && $sourcing['state'] !== 'unknown')
                                    <strong>{{ $sourcing['production'] }}</strong> - recorded response {{ $sourcing['answered_on'] }}
                                @elseif ($sourcing['state'] === 'conflicting')
                                    <strong>{{ $sourcing['production'] }}</strong>
                                @else
                                    No confirmation yet
                                @endif
                            </p>
                        @endif
                        <details>
                            <summary>Source codes and meanings</summary>
                            <dl>
                                @foreach ($reference['codes'] as $field => $code)
                                    @if ($code !== '' && $code !== null)
                                        <div><dt>{{ $field }}: {{ $code }}</dt>
                                            <dd>
                                                @forelse ($reference['code_meanings'][$field] as $meaning)
                                                    <p><strong>{{ $meaning['literal'] }}</strong> {{ $meaning['description'] }}</p>
                                                @empty
                                                    Meaning not available in this release; code is not interpreted.
                                                @endforelse
                                            </dd>
                                        </div>
                                    @endif
                                @endforeach
                            </dl>
                            <small>V_FLIS_PART.CSV · source row {{ $reference['source_row'] }}</small>
                        </details>
                    </article>
                @endforeach
            @endif
            @if ($result['source'] && ($result['page'] > 1 || $result['has_more']))
                <nav class="publog-actions" aria-label="Reference pages">
                    <x-filament::button color="gray" wire:click="changePage(-1)" :disabled="$result['page'] <= 1" wire:loading.attr="disabled">Previous references</x-filament::button>
                    <span>Page {{ $result['page'] }}</span>
                    <x-filament::button color="gray" wire:click="changePage(1)" :disabled="!$result['has_more']" wire:loading.attr="disabled">Next references</x-filament::button>
                </nav>
            @endif
            @if ($result['relationships'])
                <details class="publog-card">
                    <summary>Catalog status and replacement context · page {{ $result['relationship_page'] }}</summary>
                    <p>Source status is catalog context, not confirmation of current production.</p>
                    @foreach ($result['relationships'] as $relationship)
                        <div wire:key="relationship-{{ $relationship['id'] }}">
                            <h3>{{ ucfirst($relationship['kind']) }}</h3>
                            @foreach ($relationship['data'] as $field => $value)
                                @if ($value !== '' && $value !== null)<p>{{ $field }}: {{ $value }}</p>@endif
                            @endforeach
                        </div>
                    @endforeach
                    <nav class="publog-actions" aria-label="Status context pages">
                        <x-filament::button color="gray" wire:click="changePage(-1, true)" :disabled="$result['relationship_page'] <= 1" wire:loading.attr="disabled">Previous status</x-filament::button>
                        <x-filament::button color="gray" wire:click="changePage(1, true)" :disabled="!$result['relationships_have_more']" wire:loading.attr="disabled">Next status</x-filament::button>
                    </nav>
                </details>
            @endif
        @endif
    </div>
</x-filament-panels::page>
