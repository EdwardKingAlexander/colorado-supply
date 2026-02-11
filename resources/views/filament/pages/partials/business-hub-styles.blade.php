@include('filament.pages.partials.terminal-theme')

@once
<style>
    .bh-shell {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .bh-hero {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
    }

    .bh-kicker {
        margin: 0;
        font-family: var(--t-font-display);
        font-size: 0.58rem;
        font-weight: 600;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        color: var(--t-accent-light);
    }

    .dark .bh-kicker {
        color: var(--t-cyan);
    }

    .bh-title {
        margin: 0.35rem 0 0;
        font-family: var(--t-font-display);
        font-size: 1.15rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #111827;
    }

    .dark .bh-title {
        color: #f0f6fc;
    }

    .bh-subtitle {
        margin: 0.45rem 0 0;
        max-width: 44rem;
        font-size: 0.74rem;
        line-height: 1.55;
        color: #6b7280;
    }

    .dark .bh-subtitle {
        color: var(--t-text-dim);
    }

    .bh-stats-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
    }

    .bh-stats-row .t-stat {
        flex: 1;
        min-width: 110px;
    }

    .bh-alert-grid {
        display: grid;
        gap: 0.75rem;
    }

    @media (min-width: 1024px) {
        .bh-alert-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    .bh-alert-panel {
        border: 1px solid #e5e7eb;
        border-left-width: 4px;
        padding: 0.9rem 1rem;
        background: white;
        animation: t-slide-up 0.4s ease-out both;
    }

    .dark .bh-alert-panel {
        border-color: var(--t-border);
        background: var(--t-surface);
    }

    .bh-alert-panel--danger {
        border-left-color: var(--t-red);
        background: rgba(255, 23, 68, 0.03);
    }

    .dark .bh-alert-panel--danger {
        background: rgba(255, 23, 68, 0.08);
    }

    .bh-alert-panel--warning {
        border-left-color: var(--t-amber);
        background: rgba(255, 171, 0, 0.03);
    }

    .dark .bh-alert-panel--warning {
        background: rgba(255, 171, 0, 0.08);
    }

    .bh-alert-title {
        margin: 0;
        font-family: var(--t-font-display);
        font-size: 0.64rem;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: #111827;
    }

    .dark .bh-alert-title {
        color: #f0f6fc;
    }

    .bh-alert-meta {
        margin-top: 0.25rem;
        font-size: 0.68rem;
        color: #6b7280;
    }

    .dark .bh-alert-meta {
        color: var(--t-text-dim);
    }

    .bh-alert-list {
        margin-top: 0.65rem;
        display: grid;
        gap: 0.4rem;
    }

    .bh-alert-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.7rem;
        padding-top: 0.4rem;
        border-top: 1px solid #f3f4f6;
        font-size: 0.7rem;
    }

    .dark .bh-alert-item {
        border-top-color: #1a2332;
    }

    .bh-alert-item:first-child {
        padding-top: 0;
        border-top: none;
    }

    .bh-alert-item-title {
        min-width: 0;
        color: #374151;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .dark .bh-alert-item-title {
        color: #c9d1d9;
    }

    .bh-alert-item-meta {
        flex-shrink: 0;
        font-family: var(--t-font-mono);
        font-size: 0.64rem;
        font-weight: 600;
        letter-spacing: 0.04em;
    }

    .bh-alert-item-meta--danger {
        color: var(--t-red);
    }

    .bh-alert-item-meta--warning {
        color: var(--t-amber);
    }

    .bh-main-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1.25rem;
    }

    .bh-primary-stack,
    .bh-sidebar-stack {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        min-width: 0;
    }

    .bh-day-pill {
        width: 62px;
        height: 62px;
        flex-shrink: 0;
        border: 1px solid #e5e7eb;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: white;
    }

    .dark .bh-day-pill {
        border-color: var(--t-border);
        background: var(--t-surface-2);
    }

    .bh-day-pill--danger {
        border-color: var(--t-red-dim);
        background: rgba(255, 23, 68, 0.05);
    }

    .dark .bh-day-pill--danger {
        background: rgba(255, 23, 68, 0.1);
    }

    .bh-day-pill--warning {
        border-color: var(--t-amber-dim);
        background: rgba(255, 171, 0, 0.05);
    }

    .dark .bh-day-pill--warning {
        background: rgba(255, 171, 0, 0.1);
    }

    .bh-day-pill--safe {
        border-color: var(--t-green-dim);
        background: rgba(0, 230, 118, 0.04);
    }

    .dark .bh-day-pill--safe {
        background: rgba(0, 230, 118, 0.09);
    }

    .bh-day-value {
        font-family: var(--t-font-mono);
        font-size: 1rem;
        font-weight: 700;
        line-height: 1;
    }

    .bh-day-value--danger {
        color: var(--t-red);
    }

    .bh-day-value--warning {
        color: var(--t-amber);
    }

    .bh-day-value--safe {
        color: var(--t-green);
    }

    .bh-day-label {
        margin-top: 0.2rem;
        font-family: var(--t-font-display);
        font-size: 0.5rem;
        font-weight: 600;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #9ca3af;
    }

    .dark .bh-day-label {
        color: var(--t-text-dim);
    }

    .bh-row-title {
        font-size: 0.78rem;
        font-weight: 600;
        color: #111827;
    }

    .dark .bh-row-title {
        color: #e6edf3;
    }

    .bh-row-meta {
        margin-top: 0.2rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
        font-size: 0.66rem;
        color: #9ca3af;
    }

    .dark .bh-row-meta {
        color: var(--t-text-dim);
    }

    .bh-row-meta-dot {
        width: 4px;
        height: 4px;
        border-radius: 999px;
        background: currentColor;
        opacity: 0.65;
    }

    .bh-row-accent {
        color: var(--t-accent-light);
        font-size: 0.66rem;
        text-decoration: none;
        font-weight: 600;
    }

    .dark .bh-row-accent {
        color: var(--t-cyan);
    }

    .bh-row-accent:hover {
        text-decoration: underline;
    }

    .bh-row-warning {
        color: var(--t-amber);
        font-family: var(--t-font-mono);
        font-size: 0.64rem;
    }

    .bh-row-danger {
        color: var(--t-red);
        font-family: var(--t-font-mono);
        font-size: 0.64rem;
    }

    .bh-quick-group + .bh-quick-group {
        margin-top: 0.9rem;
    }

    .bh-quick-label {
        margin: 0 0 0.55rem;
        font-family: var(--t-font-display);
        font-size: 0.54rem;
        font-weight: 600;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: #9ca3af;
    }

    .dark .bh-quick-label {
        color: var(--t-text-dim);
    }

    .bh-quick-item {
        display: block;
        border: 1px solid #e5e7eb;
        padding: 0.65rem 0.75rem;
        text-decoration: none;
        background: #f9fafb;
        transition: all 0.16s ease;
    }

    .dark .bh-quick-item {
        border-color: var(--t-border);
        background: var(--t-surface-2);
    }

    .bh-quick-item + .bh-quick-item {
        margin-top: 0.45rem;
    }

    .bh-quick-item:hover {
        border-color: var(--t-accent-light);
        background: rgba(2, 119, 189, 0.03);
    }

    .dark .bh-quick-item:hover {
        border-color: var(--t-cyan-dim);
        background: var(--t-cyan-glow);
    }

    .bh-quick-name {
        font-size: 0.74rem;
        font-weight: 600;
        color: #111827;
    }

    .dark .bh-quick-name {
        color: #e6edf3;
    }

    .bh-quick-desc {
        margin-top: 0.15rem;
        font-size: 0.64rem;
        color: #9ca3af;
    }

    .dark .bh-quick-desc {
        color: var(--t-text-dim);
    }

    .bh-side-link {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.7rem;
        border: 1px solid #e5e7eb;
        padding: 0.75rem 0.85rem 0.75rem 1rem;
        text-decoration: none;
        background: white;
        transition: all 0.16s ease;
    }

    .dark .bh-side-link {
        border-color: var(--t-border);
        background: var(--t-surface);
    }

    .bh-side-link::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 3px;
        background: var(--t-accent-light);
    }

    .dark .bh-side-link::before {
        background: var(--t-cyan);
    }

    .bh-side-link + .bh-side-link {
        margin-top: 0.5rem;
    }

    .bh-side-link:hover {
        border-color: var(--t-accent-light);
        background: rgba(2, 119, 189, 0.03);
    }

    .dark .bh-side-link:hover {
        border-color: var(--t-cyan-dim);
        background: var(--t-cyan-glow);
    }

    .bh-side-link--warning::before {
        background: var(--t-amber);
    }

    .bh-side-link--success::before {
        background: var(--t-green);
    }

    .bh-side-link-title {
        font-size: 0.74rem;
        font-weight: 600;
        color: #111827;
    }

    .dark .bh-side-link-title {
        color: #e6edf3;
    }

    .bh-side-link-desc {
        margin-top: 0.15rem;
        font-size: 0.63rem;
        color: #9ca3af;
    }

    .dark .bh-side-link-desc {
        color: var(--t-text-dim);
    }

    .bh-side-link-cta {
        flex-shrink: 0;
        font-family: var(--t-font-display);
        font-size: 0.52rem;
        font-weight: 600;
        letter-spacing: 0.16em;
        color: #9ca3af;
    }

    .dark .bh-side-link-cta {
        color: var(--t-text-dim);
    }

    .bh-side-link:hover .bh-side-link-cta {
        color: var(--t-accent-light);
    }

    .dark .bh-side-link:hover .bh-side-link-cta {
        color: var(--t-cyan);
    }

    .bh-table-wrapper .fi-ta-ctn {
        background: transparent;
        border: none;
        box-shadow: none;
        border-radius: 0;
    }

    .bh-table-wrapper .fi-ta-header-ctn,
    .bh-table-wrapper .fi-ta-filters-before-content-ctn,
    .bh-table-wrapper .fi-ta-filters-above-content-ctn,
    .bh-table-wrapper .fi-ta-filters-below-content,
    .bh-table-wrapper .fi-ta-filter-indicators {
        border-color: #e5e7eb;
    }

    .dark .bh-table-wrapper .fi-ta-header-ctn,
    .dark .bh-table-wrapper .fi-ta-filters-before-content-ctn,
    .dark .bh-table-wrapper .fi-ta-filters-above-content-ctn,
    .dark .bh-table-wrapper .fi-ta-filters-below-content,
    .dark .bh-table-wrapper .fi-ta-filter-indicators {
        border-color: var(--t-border);
    }

    .bh-table-wrapper .fi-ta-header,
    .bh-table-wrapper .fi-ta-main,
    .bh-table-wrapper .fi-ta-content-ctn,
    .bh-table-wrapper .fi-ta-content,
    .bh-table-wrapper .fi-ta-empty-state {
        background: transparent;
    }

    /* Move search bar above the table toolbar */
    .bh-table-wrapper .fi-ta-header-toolbar {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 0.75rem;
    }

    .bh-table-wrapper .fi-ta-header-toolbar > :nth-child(1),
    .bh-table-wrapper .fi-ta-header-toolbar > :nth-child(2) {
        width: 100%;
    }

    .bh-table-wrapper .fi-ta-header-toolbar > :nth-child(2) {
        order: -1;
        margin-inline-start: 0 !important;
        justify-content: flex-start;
    }

    .bh-table-wrapper .fi-ta-header-toolbar > :nth-child(2) .fi-ta-search-field {
        width: 100%;
        max-width: none;
        flex: 1 1 auto;
    }

    @media (max-width: 768px) {
        .bh-title {
            font-size: 0.95rem;
        }

        .bh-stats-row .t-stat {
            min-width: 92px;
        }

        .bh-day-pill {
            width: 54px;
            height: 54px;
        }

        .bh-day-value {
            font-size: 0.88rem;
        }
    }
</style>
@endonce
