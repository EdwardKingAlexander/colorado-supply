<x-terminal-page
    footer-left="SAM INTEL // INSIGHTS DASHBOARD"
    footer-center="SESSION {{ strtoupper(substr(md5(session()->getId()), 0, 8)) }}"
    footer-right="OPERATOR {{ auth()->user()?->name ?? 'UNKNOWN' }}"
>
    <x-slot:banner>
        DEFENSE LOGISTICS INTELLIGENCE SYSTEM <span class="t-sep">//</span> SAM INTELLIGENCE DASHBOARD
    </x-slot:banner>

    @php
        $opportunityCount = \App\Models\SamOpportunity::count();
        $needsAttention = \App\Models\SamOpportunityDocumentParse::where('status', '!=', 'success')->count();
        $documentCount = \App\Models\SamOpportunityDocument::count();
        $parseCount = \App\Models\SamOpportunityDocumentParse::where('status', 'success')->count();
    @endphp

    <div class="sam-insights-shell">
        <div class="sam-insights-stats">
            <div class="t-stat t-stat--accent">
                <div class="t-stat-value">{{ number_format($opportunityCount) }}</div>
                <div class="t-stat-label">OPPORTUNITIES TRACKED</div>
            </div>
            <div class="t-stat {{ $documentCount > 0 ? 't-stat--success' : '' }}">
                <div class="t-stat-value">{{ number_format($documentCount) }}</div>
                <div class="t-stat-label">DOCUMENTS INGESTED</div>
            </div>
            <div class="t-stat {{ $parseCount > 0 ? 't-stat--success' : 't-stat--warning' }}">
                <div class="t-stat-value">{{ number_format($parseCount) }}</div>
                <div class="t-stat-label">PARSE OPERATIONS</div>
            </div>
            <div class="t-stat {{ $needsAttention > 0 ? 't-stat--danger' : 't-stat--success' }}">
                <div class="t-stat-value">{{ number_format($needsAttention) }}</div>
                <div class="t-stat-label">ITEMS NEEDING REVIEW</div>
            </div>
        </div>

        <div class="sam-insights-main-grid">
            <div class="t-panel t-scanlines" style="animation-delay: 0.1s">
                <div class="t-panel-corner t-panel-corner--tl"></div>
                <div class="t-panel-corner t-panel-corner--tr"></div>
                <div class="t-panel-corner t-panel-corner--bl"></div>
                <div class="t-panel-corner t-panel-corner--br"></div>

                <div class="t-panel-header">
                    <div class="t-panel-header-icon">
                        <x-heroicon-o-chart-bar class="w-5 h-5" />
                    </div>
                    <div>
                        <h2 class="t-panel-title">PIPELINE INTELLIGENCE MATRIX</h2>
                        <p class="t-panel-subtitle">Track favorites, document coverage, parse throughput, chunking, and embedding readiness across opportunities.</p>
                    </div>
                </div>

                <div class="t-divider"></div>

                <div class="sam-insights-table-wrapper">
                    {{ $this->table }}
                </div>
            </div>

            <div class="sam-insights-support-stack">
                <div class="t-card t-glow-hover">
                    <div class="t-card-header">
                        <h2 class="t-card-title">SAM OPERATIONS</h2>
                    </div>
                    <a href="{{ \App\Filament\Pages\FetchSamControlPanel::getUrl() }}" class="t-row">
                        <div>
                            <div class="sam-support-title">Control Panel</div>
                            <div class="sam-support-meta">Dispatch SAM.gov fetch jobs and monitor queue output.</div>
                        </div>
                        <span class="sam-support-cta">OPEN</span>
                    </a>
                    <a href="{{ \App\Filament\Pages\ContractDocuments::getUrl() }}" class="t-row">
                        <div>
                            <div class="sam-support-title">Contract Documents</div>
                            <div class="sam-support-meta">Upload and parse opportunity documents for downstream analysis.</div>
                        </div>
                        <span class="sam-support-cta">OPEN</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <style>
        .sam-insights-shell {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .sam-insights-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
        }

        .sam-insights-stats .t-stat {
            flex: 1;
            min-width: 170px;
        }

        .sam-insights-main-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1.25rem;
        }

        .sam-insights-support-stack {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .sam-insights-table-wrapper .fi-ta-header-toolbar {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 0.75rem;
        }

        .sam-insights-table-wrapper .fi-ta-header-toolbar > :nth-child(1),
        .sam-insights-table-wrapper .fi-ta-header-toolbar > :nth-child(2) {
            width: 100%;
        }

        .sam-insights-table-wrapper .fi-ta-header-toolbar > :nth-child(2) {
            order: -1;
            margin-inline-start: 0 !important;
            justify-content: flex-start;
        }

        .sam-insights-table-wrapper .fi-ta-header-toolbar > :nth-child(2) .fi-ta-search-field {
            width: 100%;
            max-width: none;
            flex: 1 1 auto;
        }

        .sam-support-title {
            font-family: var(--t-font-display);
            font-size: 0.65rem;
            letter-spacing: 0.12em;
            color: #0f172a;
        }

        .dark .sam-support-title {
            color: var(--t-cyan);
        }

        .sam-support-meta {
            margin-top: 0.2rem;
            font-size: 0.72rem;
            color: #64748b;
        }

        .dark .sam-support-meta {
            color: var(--t-text-dim);
        }

        .sam-support-cta {
            font-family: var(--t-font-display);
            font-size: 0.58rem;
            letter-spacing: 0.16em;
            color: var(--t-accent-light);
        }

        .dark .sam-support-cta {
            color: var(--t-cyan);
        }
    </style>
</x-terminal-page>
