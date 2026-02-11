<x-filament-panels::page>
    @include('filament.pages.partials.terminal-theme')

    <div class="t-terminal">
        <div class="t-grid-bg"></div>

        {{-- Classification banner --}}
        <div class="t-classification-bar">
            <span class="t-classification-dot"></span>
            DEFENSE LOGISTICS INTELLIGENCE SYSTEM <span class="t-sep">//</span> PART RECORD EDITOR
            <span class="t-classification-dot"></span>
        </div>

        {{-- Part summary readout --}}
        @if($this->record)
            <div class="t-panel" style="animation-delay: 0.05s; padding: 1rem 1.5rem;">
                <div class="t-panel-corner t-panel-corner--tl"></div>
                <div class="t-panel-corner t-panel-corner--tr"></div>
                <div class="t-panel-corner t-panel-corner--bl"></div>
                <div class="t-panel-corner t-panel-corner--br"></div>

                <div class="mspe-summary">
                    <div class="mspe-summary-nsn">
                        <span class="t-data-label">NSN</span>
                        <span class="t-data-value t-data-value--mono">{{ $this->record->nsn }}</span>
                    </div>
                    @if($this->record->description)
                        <div class="mspe-summary-desc">
                            <span class="t-data-label">DESCRIPTION</span>
                            <span class="t-data-value">{{ Str::limit($this->record->description, 80) }}</span>
                        </div>
                    @endif
                    @if($this->record->manufacturer)
                        <div class="mspe-summary-mfr">
                            <span class="t-data-label">MANUFACTURER</span>
                            <span class="t-data-value">{{ $this->record->manufacturer->name }}
                                @if($this->record->manufacturer->cage_code)
                                    <span class="mspe-cage-badge">{{ $this->record->manufacturer->cage_code }}</span>
                                @endif
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Form panel --}}
        <div class="t-panel t-glow-hover" style="animation-delay: 0.1s">
            <div class="t-panel-corner t-panel-corner--tl"></div>
            <div class="t-panel-corner t-panel-corner--tr"></div>
            <div class="t-panel-corner t-panel-corner--bl"></div>
            <div class="t-panel-corner t-panel-corner--br"></div>

            <div class="mspe-form-wrapper">
                {{ $this->content }}
            </div>
        </div>

        {{-- System footer --}}
        <div class="t-sys-footer">
            <span>PART EDITOR v1.0</span>
            <span class="t-sep">&bull;</span>
            <span>RECORD #{{ $this->record?->id ?? '—' }}</span>
            <span class="t-sep">&bull;</span>
            <span>OPERATOR: {{ auth()->user()?->name ?? 'UNKNOWN' }}</span>
        </div>
    </div>

    <style>
        .mspe-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            align-items: flex-end;
        }

        .mspe-summary-nsn {
            flex-shrink: 0;
        }

        .mspe-summary-desc {
            flex: 1;
            min-width: 200px;
        }

        .mspe-summary-mfr {
            flex-shrink: 0;
        }

        .mspe-cage-badge {
            display: inline-block;
            font-family: var(--t-font-mono);
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            padding: 0.15rem 0.4rem;
            margin-left: 0.4rem;
            border: 1px solid rgba(2, 119, 189, 0.25);
            color: var(--t-accent-light);
            vertical-align: middle;
        }

        .dark .mspe-cage-badge {
            border-color: var(--t-cyan-dim);
            color: var(--t-cyan);
            background: var(--t-cyan-glow);
            text-shadow: 0 0 8px var(--t-cyan-dim);
        }

        /* Form section overrides */
        .mspe-form-wrapper .fi-section {
            border: 1px solid #e5e7eb;
            border-radius: 0;
        }

        .dark .mspe-form-wrapper .fi-section {
            border-color: var(--t-border);
            background: transparent;
        }

        .dark .mspe-form-wrapper .fi-section-header {
            font-family: var(--t-font-display);
        }

        /* Relation managers terminal style */
        .mspe-form-wrapper .fi-ta-ctn {
            border-radius: 0;
        }

        .dark .mspe-form-wrapper .fi-ta-ctn {
            border-color: var(--t-border);
        }
    </style>
</x-filament-panels::page>
