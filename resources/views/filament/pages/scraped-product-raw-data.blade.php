@include('filament.pages.partials.terminal-theme')

<div class="t-terminal">
    <div class="t-panel t-scanlines">
        <div class="t-panel-corner t-panel-corner--tl"></div>
        <div class="t-panel-corner t-panel-corner--tr"></div>
        <div class="t-panel-corner t-panel-corner--bl"></div>
        <div class="t-panel-corner t-panel-corner--br"></div>

        <div class="t-panel-header">
            <div class="t-panel-header-icon">
                <x-heroicon-o-code-bracket class="w-5 h-5" />
            </div>
            <div>
                <h2 class="t-panel-title">RAW SCRAPE PAYLOAD</h2>
                <p class="t-panel-subtitle">Captured JSON output from vendor scraping pipeline.</p>
            </div>
        </div>

        <div class="t-divider"></div>

        <div class="scraped-raw-wrap">
            <pre class="scraped-raw-pre"><code>{{ json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
        </div>
    </div>
</div>

<style>
    .scraped-raw-wrap {
        border: 1px solid #e5e7eb;
        background: #f8fafc;
        overflow: auto;
        max-height: min(65vh, 42rem);
    }

    .dark .scraped-raw-wrap {
        border-color: var(--t-border);
        background: rgba(2, 6, 23, 0.6);
    }

    .scraped-raw-pre {
        margin: 0;
        padding: 1rem;
        font-family: var(--t-font-mono);
        font-size: 0.72rem;
        line-height: 1.5;
        color: #1f2937;
        white-space: pre;
        word-break: normal;
    }

    .dark .scraped-raw-pre {
        color: #d1d5db;
    }
</style>
