<x-filament-panels::page>
    @include('filament.pages.partials.terminal-theme')

    <div class="t-terminal" x-data="{ showResult: {{ isset($this->result) ? 'true' : 'false' }} }">

        {{-- Ambient grid background --}}
        <div class="t-grid-bg"></div>

        {{-- Classification banner --}}
        <div class="t-classification-bar">
            <span class="t-classification-dot"></span>
            DEFENSE LOGISTICS INTELLIGENCE SYSTEM <span class="t-sep">//</span> NSN QUERY TERMINAL
            <span class="t-classification-dot"></span>
        </div>

        {{-- Main query panel --}}
        <div class="t-panel t-scanlines t-glow-hover" style="animation-delay: 0.1s">
            <div class="t-panel-corner t-panel-corner--tl"></div>
            <div class="t-panel-corner t-panel-corner--tr"></div>
            <div class="t-panel-corner t-panel-corner--bl"></div>
            <div class="t-panel-corner t-panel-corner--br"></div>

            <div class="t-panel-header">
                <div class="t-panel-header-icon">
                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                </div>
                <div>
                    <h2 class="t-panel-title">NSN QUERY</h2>
                    <p class="t-panel-subtitle">National Stock Number Lookup &mdash; Enter NSN to retrieve FLIS data, manufacturer CAGE, and part identification.</p>
                </div>
            </div>

            <div class="t-divider"></div>

            <div class="t-form-area">
                {{ $this->form }}
            </div>

            <div class="t-actions">
                @foreach($this->getFormActions() as $action)
                    {{ $action }}
                @endforeach
            </div>

            <div class="t-panel-footer">
                <span>SRC: NSNDEPOT.COM</span>
                <span class="t-sep">//</span>
                <span>CAGE SUPP: NSNLOOKUP.COM</span>
                <span class="t-sep">//</span>
                <span>CRAWL4AI v0.8</span>
            </div>
        </div>

        {{-- Results panel --}}
        @if (isset($this->result))
            <div class="t-panel {{ $this->result['success'] ? 't-panel--success' : 't-panel--error' }}" style="animation-delay: 0.25s">
                <div class="t-panel-corner t-panel-corner--tl"></div>
                <div class="t-panel-corner t-panel-corner--tr"></div>
                <div class="t-panel-corner t-panel-corner--bl"></div>
                <div class="t-panel-corner t-panel-corner--br"></div>

                {{-- Status header --}}
                <div class="t-status-bar">
                    @if ($this->result['success'])
                        <div class="t-status-indicator t-status-indicator--ok">
                            <span class="t-status-pulse"></span>
                            <x-heroicon-s-check-circle class="w-4 h-4" />
                            <span>QUERY SUCCESSFUL</span>
                        </div>
                    @else
                        <div class="t-status-indicator t-status-indicator--fail">
                            <span class="t-status-pulse t-status-pulse--fail"></span>
                            <x-heroicon-s-x-circle class="w-4 h-4" />
                            <span>QUERY FAILED</span>
                        </div>
                    @endif
                    <span class="t-timestamp">{{ now()->format('d M Y H:i:s') }} UTC</span>
                </div>

                <div class="t-divider"></div>

                {{-- Message --}}
                <p class="t-result-message">{{ $this->result['message'] }}</p>

                {{-- Data readout --}}
                @if (!empty($this->result['nsn']))
                    <div class="t-readout">
                        <div class="t-readout-header">
                            <x-heroicon-o-document-magnifying-glass class="w-4 h-4" />
                            <span>FLIS DATA READOUT</span>
                        </div>

                        <div class="t-data-grid">
                            <div class="t-data-cell t-data-cell--highlight">
                                <span class="t-data-label">NSN</span>
                                <span class="t-data-value t-data-value--mono">{{ $this->result['nsn'] }}</span>
                            </div>

                            @if (!empty($this->result['description']))
                                <div class="t-data-cell t-data-cell--wide">
                                    <span class="t-data-label">ITEM DESCRIPTION</span>
                                    <span class="t-data-value">{{ $this->result['description'] }}</span>
                                </div>
                            @endif

                            @if (!empty($this->result['manufacturer']))
                                <div class="t-data-cell">
                                    <span class="t-data-label">MANUFACTURER / CAGE</span>
                                    <span class="t-data-value">{{ $this->result['manufacturer'] }}</span>
                                </div>
                            @endif
                        </div>

                        @if (!empty($this->result['mil_spec_part_id']))
                            <div class="t-action-link-wrapper">
                                <a href="{{ \App\Filament\Resources\MilSpecParts\MilSpecPartResource::getUrl('edit', ['record' => $this->result['mil_spec_part_id']]) }}"
                                   class="t-action-link">
                                    <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
                                    ACCESS PART RECORD
                                    <span class="t-action-link-arrow">&rarr;</span>
                                </a>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Error detail --}}
                @if ($this->result['error'])
                    <div class="t-error-block">
                        <div class="t-error-header">
                            <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                            DIAGNOSTIC
                        </div>
                        <p class="t-error-text">{{ $this->result['error'] }}</p>
                    </div>
                @endif
            </div>
        @endif

        {{-- System info footer --}}
        <div class="t-sys-footer">
            <span>DLIS TERMINAL v2.1</span>
            <span class="t-sep">&bull;</span>
            <span>SESSION {{ strtoupper(substr(md5(session()->getId()), 0, 8)) }}</span>
            <span class="t-sep">&bull;</span>
            <span>OPERATOR: {{ auth()->user()?->name ?? 'UNKNOWN' }}</span>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
