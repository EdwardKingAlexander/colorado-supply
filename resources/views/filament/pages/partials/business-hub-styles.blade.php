<style>
    @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap');

    .bh-dashboard {
        --bh-ink: 15 23 42;
        --bh-muted: 71 85 105;
        --bh-line: 226 232 240;
        --bh-surface: 255 255 255;
        --bh-surface-2: 248 250 252;
        --bh-accent: 2 132 199;
        --bh-danger: 220 38 38;
        --bh-warning: 217 119 6;
        --bh-success: 21 128 61;
        font-family: 'Space Grotesk', system-ui, sans-serif;
        color: rgb(var(--bh-ink));
    }

    .dark .bh-dashboard {
        --bh-ink: 226 232 240;
        --bh-muted: 148 163 184;
        --bh-line: 51 65 85;
        --bh-surface: 15 23 42;
        --bh-surface-2: 30 41 59;
    }

    .bh-shell {
        background:
            radial-gradient(circle at 12% 0%, rgba(2, 132, 199, 0.12), transparent 55%),
            radial-gradient(circle at 88% 12%, rgba(217, 119, 6, 0.12), transparent 45%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.92), rgba(248, 250, 252, 0.95));
        border: 1px solid rgb(var(--bh-line));
        border-radius: 24px;
        padding: 24px;
    }

    .dark .bh-shell {
        background:
            radial-gradient(circle at 12% 0%, rgba(14, 116, 144, 0.2), transparent 60%),
            radial-gradient(circle at 88% 12%, rgba(180, 83, 9, 0.2), transparent 50%),
            linear-gradient(180deg, rgba(15, 23, 42, 0.96), rgba(30, 41, 59, 0.96));
    }

    .bh-shell > * {
        animation: bh-rise 0.4s ease-out both;
    }

    .bh-shell > *:nth-child(2) { animation-delay: 0.05s; }
    .bh-shell > *:nth-child(3) { animation-delay: 0.1s; }
    .bh-shell > *:nth-child(4) { animation-delay: 0.15s; }

    @keyframes bh-rise {
        from {
            opacity: 0;
            transform: translateY(6px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .bh-hero {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
    }

    .bh-eyebrow {
        font-size: 10px;
        letter-spacing: 0.35em;
        text-transform: uppercase;
        color: rgb(var(--bh-muted));
    }

    .bh-title {
        font-size: 24px;
        font-weight: 600;
        letter-spacing: -0.02em;
        margin-top: 6px;
    }

    .bh-subtitle {
        font-size: 13px;
        color: rgb(var(--bh-muted));
        max-width: 520px;
        margin-top: 6px;
    }

    .bh-stat-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .bh-stat {
        background: rgb(var(--bh-surface));
        border: 1px solid rgb(var(--bh-line));
        border-radius: 14px;
        padding: 10px 14px;
        min-width: 120px;
    }

    .bh-stat-value {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 18px;
        font-weight: 600;
    }

    .bh-stat-label {
        font-size: 10px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgb(var(--bh-muted));
    }

    .bh-stat-danger {
        border-color: rgba(220, 38, 38, 0.35);
        background: linear-gradient(135deg, rgba(220, 38, 38, 0.12), rgba(220, 38, 38, 0.04));
    }

    .bh-stat-warning {
        border-color: rgba(217, 119, 6, 0.35);
        background: linear-gradient(135deg, rgba(217, 119, 6, 0.12), rgba(217, 119, 6, 0.04));
    }

    .bh-stat-accent {
        border-color: rgba(2, 132, 199, 0.35);
        background: linear-gradient(135deg, rgba(2, 132, 199, 0.12), rgba(2, 132, 199, 0.04));
    }

    .bh-alert-grid {
        display: grid;
        gap: 12px;
    }

    @media (min-width: 1024px) {
        .bh-alert-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    .bh-alert-panel {
        border: 1px solid rgb(var(--bh-line));
        border-left: 4px solid rgb(var(--bh-accent));
        border-radius: 16px;
        padding: 16px 18px;
        background: rgb(var(--bh-surface));
    }

    .bh-alert-danger {
        border-left-color: rgb(var(--bh-danger));
        background: linear-gradient(135deg, rgba(220, 38, 38, 0.08), rgba(220, 38, 38, 0.02));
    }

    .dark .bh-alert-danger {
        background: linear-gradient(135deg, rgba(220, 38, 38, 0.2), rgba(220, 38, 38, 0.05));
    }

    .bh-alert-warning {
        border-left-color: rgb(var(--bh-warning));
        background: linear-gradient(135deg, rgba(217, 119, 6, 0.08), rgba(217, 119, 6, 0.02));
    }

    .dark .bh-alert-warning {
        background: linear-gradient(135deg, rgba(217, 119, 6, 0.2), rgba(217, 119, 6, 0.05));
    }

    .bh-alert-title {
        font-size: 14px;
        font-weight: 600;
        color: rgb(var(--bh-ink));
    }

    .bh-alert-meta {
        font-size: 12px;
        color: rgb(var(--bh-muted));
        margin-top: 4px;
    }

    .bh-alert-list {
        margin-top: 12px;
    }

    .bh-alert-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        font-size: 12px;
        padding: 6px 0;
        border-top: 1px solid rgb(var(--bh-line) / 0.6);
    }

    .bh-alert-item:first-child {
        border-top: none;
    }

    .bh-alert-item-title {
        color: rgb(var(--bh-ink));
    }

    .bh-alert-item-meta {
        font-family: 'IBM Plex Mono', monospace;
        font-weight: 600;
        color: rgb(var(--bh-danger));
    }

    .bh-alert-item-meta-warning {
        color: rgb(var(--bh-warning));
    }

    .bh-alert-item-meta-accent {
        color: rgb(var(--bh-accent));
    }

    .bh-card {
        background: rgb(var(--bh-surface));
        border: 1px solid rgb(var(--bh-line));
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 12px 30px -28px rgba(15, 23, 42, 0.45);
    }

    .bh-card-header {
        padding: 18px 22px;
        border-bottom: 1px solid rgb(var(--bh-line));
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .bh-card-title {
        font-size: 12px;
        letter-spacing: 0.24em;
        text-transform: uppercase;
        color: rgb(var(--bh-muted));
        font-weight: 600;
    }

    .bh-link {
        font-size: 12px;
        font-weight: 600;
        color: rgb(var(--bh-accent));
        text-decoration: none;
    }

    .bh-link:hover {
        text-decoration: underline;
    }

    .bh-deadline-row {
        padding: 16px 22px;
        border-bottom: 1px solid rgb(var(--bh-line));
        display: flex;
        align-items: center;
        gap: 16px;
        transition: background 0.15s ease;
    }

    .bh-deadline-row:last-child {
        border-bottom: none;
    }

    .bh-deadline-row:hover {
        background: rgb(var(--bh-surface-2));
    }

    .bh-deadline-badge {
        width: 60px;
        height: 60px;
        border-radius: 14px;
        border: 1px solid rgb(var(--bh-line));
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-family: 'IBM Plex Mono', monospace;
    }

    .bh-deadline-badge-urgent {
        border-color: rgba(220, 38, 38, 0.35);
        background: rgba(220, 38, 38, 0.08);
    }

    .bh-deadline-badge-warning {
        border-color: rgba(217, 119, 6, 0.35);
        background: rgba(217, 119, 6, 0.08);
    }

    .bh-deadline-badge-safe {
        border-color: rgba(21, 128, 61, 0.35);
        background: rgba(21, 128, 61, 0.08);
    }

    .bh-deadline-days {
        font-size: 18px;
        font-weight: 700;
        line-height: 1;
    }

    .bh-deadline-days-urgent { color: rgb(var(--bh-danger)); }
    .bh-deadline-days-warning { color: rgb(var(--bh-warning)); }
    .bh-deadline-days-safe { color: rgb(var(--bh-success)); }

    .bh-deadline-label {
        font-size: 9px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        margin-top: 3px;
        color: rgb(var(--bh-muted));
    }

    .bh-deadline-content {
        flex: 1;
        min-width: 0;
    }

    .bh-deadline-title {
        font-size: 14px;
        font-weight: 600;
        color: rgb(var(--bh-ink));
        margin-bottom: 4px;
    }

    .bh-deadline-meta {
        font-size: 12px;
        color: rgb(var(--bh-muted));
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .bh-meta-dot {
        width: 4px;
        height: 4px;
        border-radius: 999px;
        background: rgb(var(--bh-muted));
    }

    .bh-deadline-date {
        text-align: right;
        flex-shrink: 0;
    }

    .bh-deadline-date-value {
        font-size: 13px;
        font-weight: 600;
        color: rgb(var(--bh-ink));
    }

    .bh-deadline-date-action {
        font-size: 12px;
        color: rgb(var(--bh-accent));
        text-decoration: none;
        font-weight: 600;
    }

    .bh-deadline-date-action:hover {
        text-decoration: underline;
    }

    .bh-doc-row {
        padding: 14px 22px;
        border-bottom: 1px solid rgb(var(--bh-line));
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: background 0.15s ease;
    }

    .bh-doc-row:last-child {
        border-bottom: none;
    }

    .bh-doc-row:hover {
        background: rgb(var(--bh-surface-2));
    }

    .bh-doc-info {
        flex: 1;
        min-width: 0;
    }

    .bh-doc-name {
        font-size: 13px;
        font-weight: 600;
        color: rgb(var(--bh-ink));
        margin-bottom: 4px;
    }

    .bh-doc-meta {
        font-size: 12px;
        color: rgb(var(--bh-muted));
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .bh-doc-expiry {
        text-align: right;
        flex-shrink: 0;
    }

    .bh-doc-expiry-date {
        font-size: 12px;
        font-weight: 600;
    }

    .bh-doc-expiry-urgent { color: rgb(var(--bh-danger)); }
    .bh-doc-expiry-warning { color: rgb(var(--bh-warning)); }
    .bh-doc-expiry-normal { color: rgb(var(--bh-ink)); }

    .bh-doc-expiry-days {
        font-size: 11px;
        color: rgb(var(--bh-muted));
        font-family: 'IBM Plex Mono', monospace;
    }

    .bh-empty {
        padding: 36px 22px;
        text-align: center;
    }

    .bh-empty-title {
        font-size: 14px;
        font-weight: 600;
        color: rgb(var(--bh-ink));
        margin-bottom: 4px;
    }

    .bh-empty-text {
        font-size: 12px;
        color: rgb(var(--bh-muted));
    }

    .bh-quick-links {
        padding: 16px 18px 20px;
    }

    .bh-quick-link-group {
        margin-bottom: 18px;
    }

    .bh-quick-link-group:last-child {
        margin-bottom: 0;
    }

    .bh-quick-link-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.18em;
        color: rgb(var(--bh-muted));
        padding: 0 6px;
        margin-bottom: 10px;
    }

    .bh-quick-link-list {
        display: grid;
        gap: 8px;
    }

    .bh-quick-link-item {
        display: block;
        padding: 12px 14px;
        border-radius: 12px;
        text-decoration: none;
        border: 1px solid transparent;
        background: rgb(var(--bh-surface-2));
        transition: all 0.15s ease;
    }

    .bh-quick-link-item:hover {
        border-color: rgb(var(--bh-line));
        transform: translateY(-1px);
    }

    .bh-quick-link-name {
        font-size: 13px;
        font-weight: 600;
        color: rgb(var(--bh-ink));
        margin-bottom: 4px;
    }

    .bh-quick-link-desc {
        font-size: 11px;
        color: rgb(var(--bh-muted));
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .bh-nav-card {
        padding: 16px 18px 20px;
    }

    .bh-nav-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.2em;
        color: rgb(var(--bh-muted));
        margin-bottom: 12px;
        padding-left: 8px;
    }

    .bh-nav-item {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 14px 16px 14px 22px;
        border-radius: 14px;
        border: 1px solid rgb(var(--bh-line));
        text-decoration: none;
        background: rgb(var(--bh-surface));
        transition: background 0.15s ease;
        margin-bottom: 8px;
    }

    .bh-nav-item:last-child {
        margin-bottom: 0;
    }

    .bh-nav-item::before {
        content: '';
        position: absolute;
        left: 10px;
        top: 12px;
        bottom: 12px;
        width: 3px;
        border-radius: 4px;
        background: rgb(var(--bh-accent));
    }

    .bh-nav-item:hover {
        background: rgb(var(--bh-surface-2));
    }

    .bh-nav-docs::before { background: rgb(var(--bh-accent)); }
    .bh-nav-deadlines::before { background: rgb(var(--bh-warning)); }
    .bh-nav-links::before { background: rgb(var(--bh-success)); }

    .bh-nav-title {
        font-size: 14px;
        font-weight: 600;
        color: rgb(var(--bh-ink));
    }

    .bh-nav-desc {
        font-size: 12px;
        color: rgb(var(--bh-muted));
        margin-top: 2px;
    }

    .bh-nav-cta {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: rgb(var(--bh-accent));
    }

    .bh-nav-deadlines .bh-nav-cta { color: rgb(var(--bh-warning)); }
    .bh-nav-links .bh-nav-cta { color: rgb(var(--bh-success)); }

    .bh-table .fi-ta-ctn {
        background: transparent;
        border: none;
        box-shadow: none;
    }

    .bh-table .fi-ta-header-ctn,
    .bh-table .fi-ta-filters-before-content-ctn,
    .bh-table .fi-ta-filters-above-content-ctn,
    .bh-table .fi-ta-filters-below-content,
    .bh-table .fi-ta-filter-indicators {
        border-color: rgb(var(--bh-line));
    }

    .bh-table .fi-ta-header,
    .bh-table .fi-ta-main,
    .bh-table .fi-ta-content-ctn,
    .bh-table .fi-ta-content,
    .bh-table .fi-ta-empty-state {
        background: transparent;
    }

    @media (max-width: 640px) {
        .bh-shell {
            padding: 18px;
        }

        .bh-title {
            font-size: 20px;
        }

        .bh-stat {
            min-width: 100px;
        }
    }
</style>
