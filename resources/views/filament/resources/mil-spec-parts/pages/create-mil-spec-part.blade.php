<x-filament-panels::page>
    @include('filament.pages.partials.terminal-theme')

    <div class="t-terminal">
        <div class="t-grid-bg"></div>

        {{-- Classification banner --}}
        <div class="t-classification-bar">
            <span class="t-classification-dot"></span>
            DEFENSE LOGISTICS INTELLIGENCE SYSTEM <span class="t-sep">//</span> NEW PART ENTRY
            <span class="t-classification-dot"></span>
        </div>

        {{-- Form panel --}}
        <div class="t-panel t-glow-hover" style="animation-delay: 0.1s">
            <div class="t-panel-corner t-panel-corner--tl"></div>
            <div class="t-panel-corner t-panel-corner--tr"></div>
            <div class="t-panel-corner t-panel-corner--bl"></div>
            <div class="t-panel-corner t-panel-corner--br"></div>

            <div class="t-panel-header">
                <div class="t-panel-header-icon">
                    <x-heroicon-o-plus-circle class="w-5 h-5" />
                </div>
                <div>
                    <h2 class="t-panel-title">REGISTER NEW PART</h2>
                    <p class="t-panel-subtitle">Enter NSN, description, and manufacturer data to catalog a new mil-spec part in the registry.</p>
                </div>
            </div>

            <div class="t-divider"></div>

            <div class="mspc-form-wrapper">
                {{ $this->content }}
            </div>
        </div>

        {{-- Tip panel --}}
        <div class="t-panel" style="animation-delay: 0.2s; padding: 1rem 1.5rem;">
            <div class="t-panel-corner t-panel-corner--tl"></div>
            <div class="t-panel-corner t-panel-corner--tr"></div>
            <div class="t-panel-corner t-panel-corner--bl"></div>
            <div class="t-panel-corner t-panel-corner--br"></div>

            <div class="mspc-tip">
                <x-heroicon-o-light-bulb class="w-4 h-4 mspc-tip-icon" />
                <span>Use the <a href="{{ \App\Filament\Pages\FetchNsnData::getUrl() }}" class="t-action-link" style="display: inline; padding: 0; border: none; font-size: inherit; letter-spacing: inherit;">NSN Query Terminal</a> to auto-populate part data from DLIS sources.</span>
            </div>
        </div>

        {{-- System footer --}}
        <div class="t-sys-footer">
            <span>PART REGISTRY v1.0</span>
            <span class="t-sep">&bull;</span>
            <span>OPERATOR: {{ auth()->user()?->name ?? 'UNKNOWN' }}</span>
        </div>
    </div>

    <style>
        /* Form section overrides */
        .mspc-form-wrapper .fi-section {
            border: 1px solid #e5e7eb;
            border-radius: 0;
        }

        .dark .mspc-form-wrapper .fi-section {
            border-color: var(--t-border);
            background: transparent;
        }

        .dark .mspc-form-wrapper .fi-section-header {
            font-family: var(--t-font-display);
        }

        .mspc-tip {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: #6b7280;
        }

        .dark .mspc-tip {
            color: var(--t-text-dim);
        }

        .mspc-tip-icon {
            flex-shrink: 0;
            color: var(--t-accent-light);
        }

        .dark .mspc-tip-icon {
            color: var(--t-cyan);
        }

        .mspc-tip .t-action-link {
            font-family: inherit;
            font-weight: 600;
        }
    </style>
</x-filament-panels::page>
