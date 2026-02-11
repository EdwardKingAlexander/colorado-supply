{{--
    Terminal Theme — Defense Logistics Command UI
    Shared CSS foundation for all backend pages.

    Usage: @include('filament.pages.partials.terminal-theme')
    Or use the <x-terminal-page> component which includes this automatically.
--}}

@once
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=IBM+Plex+Mono:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
    /* ============================================
       TERMINAL THEME — Defense Logistics Command UI
       Shared design tokens & component styles
       ============================================ */

    /* --- Design Tokens --- */
    :root {
        /* Core palette */
        --t-cyan: #00e5ff;
        --t-cyan-dim: #00e5ff40;
        --t-cyan-glow: #00e5ff20;
        --t-amber: #ffab00;
        --t-amber-dim: #ffab0040;
        --t-green: #00e676;
        --t-green-dim: #00e67640;
        --t-red: #ff1744;
        --t-red-dim: #ff174440;

        /* Surfaces (dark defaults) */
        --t-bg: #0a0e14;
        --t-surface: #0d1117;
        --t-surface-2: #151b23;
        --t-border: #1e2a3a;

        /* Text */
        --t-text: #c9d1d9;
        --t-text-dim: #6b7b8d;

        /* Typography */
        --t-font-display: 'Orbitron', sans-serif;
        --t-font-mono: 'IBM Plex Mono', monospace;

        /* Light mode accent (replaces cyan) */
        --t-accent-light: #0277bd;
    }

    /* --- Terminal Container --- */
    .t-terminal {
        position: relative;
        padding: 0;
        font-family: var(--t-font-mono);
        color: var(--t-text);
    }

    /* --- Ambient Grid Background --- */
    .t-grid-bg {
        position: absolute;
        inset: 0;
        pointer-events: none;
        opacity: 0.03;
        background-image:
            linear-gradient(var(--t-cyan) 1px, transparent 1px),
            linear-gradient(90deg, var(--t-cyan) 1px, transparent 1px);
        background-size: 40px 40px;
        mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%);
        -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%);
    }

    .dark .t-grid-bg { opacity: 0.04; }

    /* --- Classification Banner --- */
    .t-classification-bar {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        margin-bottom: 1.5rem;
        font-family: var(--t-font-display);
        font-size: 0.6rem;
        font-weight: 600;
        letter-spacing: 0.25em;
        text-transform: uppercase;
        color: var(--t-accent-light);
        border: 1px solid rgba(2, 119, 189, 0.19);
        background: linear-gradient(135deg, rgba(2, 119, 189, 0.06), transparent 60%);
        animation: t-fade-in 0.6s ease-out both;
    }

    .dark .t-classification-bar {
        color: var(--t-cyan);
        border-color: var(--t-cyan-dim);
        background: linear-gradient(135deg, var(--t-cyan-glow), transparent 60%);
    }

    .t-classification-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
        animation: t-blink 2s ease-in-out infinite;
    }

    .t-sep {
        opacity: 0.35;
        font-weight: 400;
    }

    /* --- Panel --- */
    .t-panel {
        position: relative;
        padding: 1.5rem;
        margin-bottom: 1.25rem;
        border: 1px solid #e5e7eb;
        background: white;
        animation: t-slide-up 0.5s ease-out both;
    }

    .dark .t-panel {
        background: var(--t-surface);
        border-color: var(--t-border);
    }

    /* Panel state variants */
    .t-panel--success .t-panel-corner { border-color: var(--t-green-dim); }
    .t-panel--error .t-panel-corner { border-color: var(--t-red-dim); }

    /* --- HUD Corner Brackets --- */
    .t-panel-corner {
        position: absolute;
        width: 12px;
        height: 12px;
        pointer-events: none;
        border-color: rgba(2, 119, 189, 0.19);
    }

    .dark .t-panel-corner {
        border-color: var(--t-cyan-dim);
    }

    .t-panel-corner--tl { top: -1px; left: -1px; border-top: 2px solid; border-left: 2px solid; }
    .t-panel-corner--tr { top: -1px; right: -1px; border-top: 2px solid; border-right: 2px solid; }
    .t-panel-corner--bl { bottom: -1px; left: -1px; border-bottom: 2px solid; border-left: 2px solid; }
    .t-panel-corner--br { bottom: -1px; right: -1px; border-bottom: 2px solid; border-right: 2px solid; }

    /* --- Panel Header --- */
    .t-panel-header {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .t-panel-header-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        flex-shrink: 0;
        border: 1px solid rgba(2, 119, 189, 0.19);
        color: var(--t-accent-light);
    }

    .dark .t-panel-header-icon {
        border-color: var(--t-cyan-dim);
        color: var(--t-cyan);
        background: var(--t-cyan-glow);
    }

    .t-panel-title {
        font-family: var(--t-font-display);
        font-size: 0.85rem;
        font-weight: 700;
        letter-spacing: 0.15em;
        color: #111827;
        margin: 0;
    }

    .dark .t-panel-title {
        color: #f0f6fc;
    }

    .t-panel-subtitle {
        font-size: 0.7rem;
        color: #6b7280;
        margin-top: 0.25rem;
        line-height: 1.5;
        letter-spacing: 0.01em;
    }

    .dark .t-panel-subtitle {
        color: var(--t-text-dim);
    }

    /* --- Divider --- */
    .t-divider {
        height: 1px;
        margin: 1rem 0;
        background: linear-gradient(90deg, transparent, #d1d5db 30%, #d1d5db 70%, transparent);
    }

    .dark .t-divider {
        background: linear-gradient(90deg, transparent, var(--t-border) 30%, var(--t-border) 70%, transparent);
    }

    /* --- Form Area Overrides --- */
    .t-form-area {
        position: relative;
    }

    .dark .t-form-area input[type="text"],
    .dark .t-form-area input[type="search"] {
        font-family: var(--t-font-mono) !important;
        letter-spacing: 0.08em;
    }

    /* --- Actions Row --- */
    .t-actions {
        display: flex;
        gap: 0.75rem;
        margin-top: 1rem;
    }

    /* --- Panel Footer --- */
    .t-panel-footer {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
        padding-top: 0.75rem;
        border-top: 1px dashed #e5e7eb;
        font-size: 0.6rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #9ca3af;
    }

    .dark .t-panel-footer {
        border-top-color: var(--t-border);
        color: var(--t-text-dim);
    }

    /* --- Status Bar --- */
    .t-status-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .t-status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-family: var(--t-font-display);
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.15em;
    }

    .t-status-indicator--ok { color: var(--t-green); }
    .t-status-indicator--fail { color: var(--t-red); }
    .t-status-indicator--warn { color: var(--t-amber); }

    .t-status-pulse {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--t-green);
        box-shadow: 0 0 8px var(--t-green);
        animation: t-pulse 1.5s ease-in-out infinite;
    }

    .t-status-pulse--fail {
        background: var(--t-red);
        box-shadow: 0 0 8px var(--t-red);
    }

    .t-status-pulse--warn {
        background: var(--t-amber);
        box-shadow: 0 0 8px var(--t-amber);
    }

    .t-timestamp {
        font-size: 0.65rem;
        letter-spacing: 0.08em;
        color: #9ca3af;
    }

    .dark .t-timestamp { color: var(--t-text-dim); }

    /* --- Result Message --- */
    .t-result-message {
        font-size: 0.75rem;
        line-height: 1.6;
        color: #6b7280;
        margin: 0.75rem 0;
    }

    .dark .t-result-message { color: var(--t-text-dim); }

    /* --- Data Readout Block --- */
    .t-readout {
        border: 1px solid #e5e7eb;
        padding: 1rem;
        margin-top: 1rem;
        position: relative;
    }

    .dark .t-readout {
        border-color: var(--t-border);
        background: var(--t-surface-2);
    }

    .t-readout::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
    }

    .t-panel--success .t-readout::before {
        background: linear-gradient(90deg, transparent, var(--t-green-dim), transparent);
    }

    .t-readout-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-family: var(--t-font-display);
        font-size: 0.6rem;
        font-weight: 600;
        letter-spacing: 0.2em;
        color: var(--t-accent-light);
        margin-bottom: 1rem;
    }

    .dark .t-readout-header {
        color: var(--t-cyan);
    }

    /* --- Data Grid --- */
    .t-data-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0;
    }

    @media (min-width: 640px) {
        .t-data-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    .t-data-cell {
        padding: 0.75rem 0;
        border-bottom: 1px solid #f3f4f6;
    }

    .dark .t-data-cell {
        border-bottom-color: #1a2332;
    }

    .t-data-cell--wide,
    .t-data-cell--highlight {
        grid-column: 1 / -1;
    }

    .t-data-label {
        display: block;
        font-family: var(--t-font-display);
        font-size: 0.55rem;
        font-weight: 500;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: #9ca3af;
        margin-bottom: 0.35rem;
    }

    .dark .t-data-label {
        color: var(--t-text-dim);
    }

    .t-data-value {
        display: block;
        font-size: 0.8rem;
        font-weight: 500;
        color: #111827;
        letter-spacing: 0.02em;
    }

    .dark .t-data-value {
        color: #e6edf3;
    }

    .t-data-value--mono {
        font-family: var(--t-font-mono);
        font-size: 1.1rem;
        font-weight: 600;
        letter-spacing: 0.12em;
        color: var(--t-accent-light);
    }

    .dark .t-data-value--mono {
        color: var(--t-cyan);
        text-shadow: 0 0 20px var(--t-cyan-dim);
    }

    /* --- Action Link --- */
    .t-action-link-wrapper {
        margin-top: 1rem;
        padding-top: 0.75rem;
        border-top: 1px dashed #e5e7eb;
    }

    .dark .t-action-link-wrapper {
        border-top-color: var(--t-border);
    }

    .t-action-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-family: var(--t-font-display);
        font-size: 0.65rem;
        font-weight: 600;
        letter-spacing: 0.15em;
        color: var(--t-accent-light);
        text-decoration: none;
        padding: 0.5rem 1rem;
        border: 1px solid rgba(2, 119, 189, 0.19);
        transition: all 0.2s ease;
    }

    .dark .t-action-link {
        color: var(--t-cyan);
        border-color: var(--t-cyan-dim);
        background: var(--t-cyan-glow);
    }

    .t-action-link:hover {
        background: rgba(2, 119, 189, 0.06);
        border-color: var(--t-accent-light);
    }

    .dark .t-action-link:hover {
        background: var(--t-cyan-dim);
        border-color: var(--t-cyan);
        box-shadow: 0 0 15px var(--t-cyan-glow);
    }

    .t-action-link-arrow {
        transition: transform 0.2s ease;
    }

    .t-action-link:hover .t-action-link-arrow {
        transform: translateX(3px);
    }

    /* --- Error Block --- */
    .t-error-block {
        margin-top: 1rem;
        padding: 0.75rem 1rem;
        border: 1px solid var(--t-red-dim);
        background: rgba(255, 23, 68, 0.03);
    }

    .dark .t-error-block {
        background: rgba(255, 23, 68, 0.04);
    }

    .t-error-header {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-family: var(--t-font-display);
        font-size: 0.6rem;
        font-weight: 600;
        letter-spacing: 0.15em;
        color: var(--t-red);
        margin-bottom: 0.5rem;
    }

    .t-error-text {
        font-size: 0.75rem;
        color: #ef5350;
        line-height: 1.5;
        word-break: break-word;
    }

    /* --- System Footer --- */
    .t-sys-footer {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding: 0.75rem;
        font-size: 0.55rem;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: #d1d5db;
        opacity: 0.5;
        animation: t-fade-in 0.8s ease-out 0.4s both;
    }

    .dark .t-sys-footer {
        color: var(--t-text-dim);
    }

    /* --- Stat Cards (terminal style) --- */
    .t-stat {
        padding: 0.65rem 0.85rem;
        border: 1px solid #e5e7eb;
        background: white;
    }

    .dark .t-stat {
        border-color: var(--t-border);
        background: var(--t-surface);
    }

    .t-stat-value {
        font-family: var(--t-font-mono);
        font-size: 1.25rem;
        font-weight: 600;
        color: #111827;
    }

    .dark .t-stat-value {
        color: #e6edf3;
    }

    .t-stat-label {
        font-family: var(--t-font-display);
        font-size: 0.5rem;
        font-weight: 500;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: #9ca3af;
    }

    .dark .t-stat-label {
        color: var(--t-text-dim);
    }

    .t-stat--danger {
        border-color: var(--t-red-dim);
        background: rgba(255, 23, 68, 0.03);
    }
    .dark .t-stat--danger {
        background: rgba(255, 23, 68, 0.06);
    }
    .t-stat--danger .t-stat-value { color: var(--t-red); }

    .t-stat--warning {
        border-color: var(--t-amber-dim);
        background: rgba(255, 171, 0, 0.03);
    }
    .dark .t-stat--warning {
        background: rgba(255, 171, 0, 0.06);
    }
    .t-stat--warning .t-stat-value { color: var(--t-amber); }

    .t-stat--success {
        border-color: var(--t-green-dim);
        background: rgba(0, 230, 118, 0.03);
    }
    .dark .t-stat--success {
        background: rgba(0, 230, 118, 0.06);
    }
    .t-stat--success .t-stat-value { color: var(--t-green); }

    .t-stat--accent {
        border-color: rgba(2, 119, 189, 0.25);
        background: rgba(2, 119, 189, 0.03);
    }
    .dark .t-stat--accent {
        border-color: var(--t-cyan-dim);
        background: var(--t-cyan-glow);
    }
    .dark .t-stat--accent .t-stat-value { color: var(--t-cyan); }

    /* --- Card (terminal version of bh-card) --- */
    .t-card {
        background: white;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        position: relative;
    }

    .dark .t-card {
        background: var(--t-surface);
        border-color: var(--t-border);
    }

    .t-card-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .dark .t-card-header {
        border-bottom-color: var(--t-border);
    }

    .t-card-title {
        font-family: var(--t-font-display);
        font-size: 0.6rem;
        font-weight: 600;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: #9ca3af;
    }

    .dark .t-card-title {
        color: var(--t-text-dim);
    }

    .t-card-link {
        font-family: var(--t-font-display);
        font-size: 0.6rem;
        font-weight: 600;
        letter-spacing: 0.1em;
        color: var(--t-accent-light);
        text-decoration: none;
        text-transform: uppercase;
    }

    .dark .t-card-link {
        color: var(--t-cyan);
    }

    .t-card-link:hover {
        text-decoration: underline;
    }

    /* --- Row (for lists inside cards) --- */
    .t-row {
        padding: 0.85rem 1.25rem;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: background 0.15s ease;
    }

    .dark .t-row {
        border-bottom-color: #1a2332;
    }

    .t-row:last-child {
        border-bottom: none;
    }

    .t-row:hover {
        background: #f9fafb;
    }

    .dark .t-row:hover {
        background: var(--t-surface-2);
    }

    /* --- Empty State --- */
    .t-empty {
        padding: 2.5rem 1.25rem;
        text-align: center;
    }

    .t-empty-title {
        font-family: var(--t-font-display);
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.1em;
        color: #6b7280;
    }

    .dark .t-empty-title {
        color: var(--t-text-dim);
    }

    .t-empty-text {
        font-size: 0.7rem;
        color: #9ca3af;
        margin-top: 0.25rem;
    }

    .dark .t-empty-text {
        color: var(--t-text-dim);
    }

    /* --- Scan Line Effect (dark mode only) --- */
    .t-scanlines::after {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        opacity: 0;
        background: repeating-linear-gradient(
            0deg,
            transparent,
            transparent 2px,
            rgba(0, 229, 255, 0.008) 2px,
            rgba(0, 229, 255, 0.008) 4px
        );
        z-index: 1;
    }

    .dark .t-scanlines::after {
        opacity: 1;
    }

    /* --- Glow on hover (dark mode only) --- */
    .t-glow-hover {
        transition: box-shadow 0.3s ease;
    }

    .dark .t-glow-hover:hover {
        box-shadow: 0 0 30px -10px var(--t-cyan-glow);
    }

    /* --- Filament Table Overrides (inside terminal pages) --- */
    .t-terminal .fi-ta-ctn {
        background: transparent;
        border: none;
        box-shadow: none;
    }

    .dark .t-terminal .fi-ta-ctn {
        background: transparent;
    }

    .t-terminal .fi-ta-header-ctn,
    .t-terminal .fi-ta-filters-before-content-ctn,
    .t-terminal .fi-ta-filters-above-content-ctn {
        border-color: #e5e7eb;
    }

    .dark .t-terminal .fi-ta-header-ctn,
    .dark .t-terminal .fi-ta-filters-before-content-ctn,
    .dark .t-terminal .fi-ta-filters-above-content-ctn {
        border-color: var(--t-border);
    }

    .t-terminal .fi-ta-header,
    .t-terminal .fi-ta-main,
    .t-terminal .fi-ta-content-ctn,
    .t-terminal .fi-ta-content,
    .t-terminal .fi-ta-empty-state {
        background: transparent;
    }

    /* --- Global Filament Shell Overrides (Phase 8) --- */
    .t-admin-terminal {
        font-family: var(--t-font-mono);
        color: #0f172a;
        background:
            radial-gradient(1200px 600px at 85% -10%, rgba(2, 119, 189, 0.07), transparent 60%),
            linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
    }

    .dark .t-admin-terminal {
        color: var(--t-text);
        background:
            radial-gradient(1200px 640px at 85% -10%, rgba(0, 229, 255, 0.09), transparent 60%),
            linear-gradient(180deg, #090d14 0%, #070b12 100%);
    }

    .t-admin-terminal .fi-layout {
        position: relative;
        isolation: isolate;
    }

    .t-admin-terminal .fi-layout::before {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        opacity: 0.04;
        background-image:
            linear-gradient(var(--t-accent-light) 1px, transparent 1px),
            linear-gradient(90deg, var(--t-accent-light) 1px, transparent 1px);
        background-size: 44px 44px;
        z-index: -1;
    }

    .dark .t-admin-terminal .fi-layout::before {
        opacity: 0.06;
        background-image:
            linear-gradient(var(--t-cyan) 1px, transparent 1px),
            linear-gradient(90deg, var(--t-cyan) 1px, transparent 1px);
    }

    /* Sidebar */
    .t-admin-terminal .fi-sidebar.fi-main-sidebar {
        position: relative;
        border-right: 1px solid rgba(2, 119, 189, 0.28);
        background:
            linear-gradient(180deg, rgba(2, 119, 189, 0.06), rgba(2, 119, 189, 0.01)),
            #f8fafc;
        box-shadow: inset -1px 0 0 rgba(2, 119, 189, 0.08);
    }

    .dark .t-admin-terminal .fi-sidebar.fi-main-sidebar {
        border-right-color: var(--t-border);
        background:
            linear-gradient(180deg, rgba(0, 229, 255, 0.08), rgba(0, 229, 255, 0.01)),
            #090f17;
        box-shadow: inset -1px 0 0 rgba(0, 229, 255, 0.08);
    }

    .t-admin-terminal .fi-sidebar.fi-main-sidebar::after {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        opacity: 0.04;
        background: repeating-linear-gradient(
            0deg,
            transparent 0,
            transparent 2px,
            rgba(2, 119, 189, 0.2) 2px,
            rgba(2, 119, 189, 0.2) 3px
        );
    }

    .dark .t-admin-terminal .fi-sidebar.fi-main-sidebar::after {
        opacity: 0.1;
        background: repeating-linear-gradient(
            0deg,
            transparent 0,
            transparent 2px,
            rgba(0, 229, 255, 0.14) 2px,
            rgba(0, 229, 255, 0.14) 3px
        );
    }

    .t-admin-terminal .fi-sidebar-group-label,
    .t-admin-terminal .fi-sidebar-item-label {
        font-family: var(--t-font-display);
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .t-admin-terminal .fi-sidebar-group-label {
        font-size: 0.57rem;
    }

    .t-admin-terminal .fi-sidebar-item-label {
        font-size: 0.6rem;
    }

    .t-admin-terminal .fi-sidebar-item-btn {
        border: 1px solid transparent;
        border-radius: 0;
        transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
    }

    .t-admin-terminal .fi-sidebar-item-btn:hover {
        border-color: rgba(2, 119, 189, 0.3);
        background: rgba(2, 119, 189, 0.08);
    }

    .dark .t-admin-terminal .fi-sidebar-item-btn:hover {
        border-color: var(--t-cyan-dim);
        background: rgba(0, 229, 255, 0.08);
    }

    .t-admin-terminal .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        border-color: rgba(2, 119, 189, 0.4);
        background: rgba(2, 119, 189, 0.12);
        box-shadow: 0 0 18px -12px rgba(2, 119, 189, 0.5);
    }

    .dark .t-admin-terminal .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        border-color: var(--t-cyan-dim);
        background: rgba(0, 229, 255, 0.12);
        box-shadow: 0 0 20px -10px rgba(0, 229, 255, 0.45);
    }

    /* Topbar and page header */
    .t-admin-terminal .fi-topbar {
        position: relative;
        border-bottom: 1px solid rgba(2, 119, 189, 0.22);
        background: linear-gradient(180deg, rgba(2, 119, 189, 0.09), rgba(2, 119, 189, 0.03));
        padding-top: 1rem;
        backdrop-filter: blur(6px);
    }

    .dark .t-admin-terminal .fi-topbar {
        border-bottom-color: var(--t-border);
        background: linear-gradient(180deg, rgba(0, 229, 255, 0.11), rgba(0, 229, 255, 0.03));
    }

    .t-admin-terminal .fi-topbar::before {
        content: 'CLASSIFICATION: CONTROLLED UNCLASSIFIED // OPERATIONAL USE';
        position: absolute;
        top: 0.1rem;
        left: 1rem;
        font-family: var(--t-font-display);
        font-size: 0.52rem;
        font-weight: 600;
        letter-spacing: 0.18em;
        color: var(--t-accent-light);
        opacity: 0.82;
    }

    .dark .t-admin-terminal .fi-topbar::before {
        color: var(--t-cyan);
        opacity: 0.9;
    }

    .t-admin-terminal .fi-breadcrumbs-item-label {
        font-family: var(--t-font-display);
        font-size: 0.55rem;
        letter-spacing: 0.14em;
        text-transform: uppercase;
    }

    .t-admin-terminal .fi-header-heading {
        font-family: var(--t-font-display);
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .t-admin-terminal .fi-header-subheading {
        font-family: var(--t-font-mono);
        letter-spacing: 0.04em;
    }

    /* Dashboard shell */
    .t-admin-terminal .t-dashboard-band {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.55rem;
        margin-bottom: 1rem;
        padding: 0.45rem 0.75rem;
        border: 1px solid rgba(2, 119, 189, 0.24);
        background: rgba(2, 119, 189, 0.07);
        font-family: var(--t-font-display);
        font-size: 0.55rem;
        font-weight: 600;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: var(--t-accent-light);
    }

    .dark .t-admin-terminal .t-dashboard-band {
        border-color: var(--t-cyan-dim);
        background: rgba(0, 229, 255, 0.07);
        color: var(--t-cyan);
    }

    .t-admin-terminal .t-dashboard-band-dot {
        width: 7px;
        height: 7px;
        border-radius: 9999px;
        background: var(--t-green);
        box-shadow: 0 0 8px var(--t-green);
        animation: t-pulse 1.6s ease-in-out infinite;
    }

    .t-admin-terminal .t-dashboard-band-sep {
        opacity: 0.4;
    }

    .t-admin-terminal .fi-page-dashboard .fi-wi-widget {
        position: relative;
        border: 1px solid rgba(2, 119, 189, 0.22);
        background: rgba(255, 255, 255, 0.72);
        overflow: hidden;
    }

    .dark .t-admin-terminal .fi-page-dashboard .fi-wi-widget {
        border-color: var(--t-border);
        background: rgba(13, 17, 23, 0.84);
    }

    .t-admin-terminal .fi-page-dashboard .fi-wi-widget::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(2, 119, 189, 0.55), transparent);
    }

    .dark .t-admin-terminal .fi-page-dashboard .fi-wi-widget::before {
        background: linear-gradient(90deg, transparent, rgba(0, 229, 255, 0.55), transparent);
    }

    .t-admin-terminal .fi-page-dashboard .fi-wi-stats-overview-stat {
        border: 1px solid rgba(2, 119, 189, 0.2);
        background: rgba(2, 119, 189, 0.04);
        border-radius: 0;
    }

    .dark .t-admin-terminal .fi-page-dashboard .fi-wi-stats-overview-stat {
        border-color: rgba(0, 229, 255, 0.18);
        background: rgba(0, 229, 255, 0.06);
    }

    .t-admin-terminal .fi-page-dashboard .fi-wi-stats-overview-stat-label {
        font-family: var(--t-font-display);
        font-size: 0.54rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .t-admin-terminal .fi-page-dashboard .fi-wi-stats-overview-stat-value {
        font-family: var(--t-font-mono);
        letter-spacing: 0.05em;
    }

    /* Global form treatment */
    .t-admin-terminal .fi-section {
        border-radius: 0;
        border: 1px solid rgba(2, 119, 189, 0.2);
        background: rgba(255, 255, 255, 0.72);
    }

    .dark .t-admin-terminal .fi-section {
        border-color: var(--t-border);
        background: rgba(13, 17, 23, 0.86);
    }

    .t-admin-terminal .fi-section-header-heading,
    .t-admin-terminal .fi-fo-field-label-content {
        font-family: var(--t-font-display);
        letter-spacing: 0.1em;
        text-transform: uppercase;
        font-size: 0.6rem;
    }

    .t-admin-terminal .fi-input-wrp {
        border: 1px solid rgba(2, 119, 189, 0.24);
        border-radius: 0;
        background: rgba(255, 255, 255, 0.82);
        box-shadow: none;
    }

    .dark .t-admin-terminal .fi-input-wrp {
        border-color: var(--t-border);
        background: rgba(9, 15, 23, 0.9);
    }

    .t-admin-terminal .fi-input-wrp:focus-within,
    .t-admin-terminal .fi-input-wrp.fi-focused {
        border-color: rgba(2, 119, 189, 0.46);
        box-shadow: 0 0 0 1px rgba(2, 119, 189, 0.24);
    }

    .dark .t-admin-terminal .fi-input-wrp:focus-within,
    .dark .t-admin-terminal .fi-input-wrp.fi-focused {
        border-color: var(--t-cyan-dim);
        box-shadow: 0 0 0 1px rgba(0, 229, 255, 0.24);
    }

    .t-admin-terminal .fi-input,
    .t-admin-terminal .fi-select-input,
    .t-admin-terminal textarea,
    .t-admin-terminal .fi-fo-date-time-picker-display-text-input {
        font-family: var(--t-font-mono);
        letter-spacing: 0.05em;
        border-radius: 0;
    }

    .t-admin-terminal .fi-input::placeholder,
    .t-admin-terminal .fi-select-input::placeholder,
    .t-admin-terminal textarea::placeholder {
        letter-spacing: 0.08em;
    }

    .t-admin-terminal .fi-btn {
        border-radius: 0;
        font-family: var(--t-font-display);
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }

    .t-admin-terminal .choices__inner,
    .t-admin-terminal .choices__list--dropdown,
    .t-admin-terminal .choices__list[aria-expanded] {
        border-radius: 0 !important;
        border-color: rgba(2, 119, 189, 0.24) !important;
        background: rgba(255, 255, 255, 0.95) !important;
    }

    .dark .t-admin-terminal .choices__inner,
    .dark .t-admin-terminal .choices__list--dropdown,
    .dark .t-admin-terminal .choices__list[aria-expanded] {
        border-color: var(--t-border) !important;
        background: #0b121c !important;
    }

    .t-admin-terminal .fi-fo-date-time-picker-panel,
    .t-admin-terminal .fi-fo-file-upload,
    .t-admin-terminal .fi-fo-file-upload-input-ctn {
        border-radius: 0;
        border-color: rgba(2, 119, 189, 0.24);
    }

    .dark .t-admin-terminal .fi-fo-date-time-picker-panel,
    .dark .t-admin-terminal .fi-fo-file-upload,
    .dark .t-admin-terminal .fi-fo-file-upload-input-ctn {
        border-color: var(--t-border);
        background: #0b121c;
    }

    .t-admin-terminal .fi-checkbox-input,
    .t-admin-terminal .fi-radio-input {
        accent-color: var(--t-accent-light);
    }

    .dark .t-admin-terminal .fi-checkbox-input,
    .dark .t-admin-terminal .fi-radio-input {
        accent-color: var(--t-cyan);
    }

    /* Simple layout / auth pages */
    .t-admin-terminal .fi-simple-layout {
        position: relative;
        min-height: 100vh;
        overflow: hidden;
        background:
            radial-gradient(1000px 500px at 50% -10%, rgba(2, 119, 189, 0.1), transparent 58%),
            linear-gradient(180deg, #edf2f8 0%, #e7edf5 100%);
    }

    .dark .t-admin-terminal .fi-simple-layout {
        background:
            radial-gradient(1000px 500px at 50% -10%, rgba(0, 229, 255, 0.14), transparent 58%),
            linear-gradient(180deg, #05080f 0%, #070b12 100%);
    }

    .t-admin-terminal .t-simple-grid-bg {
        position: absolute;
        inset: 0;
        pointer-events: none;
        opacity: 0.05;
        background-image:
            linear-gradient(var(--t-accent-light) 1px, transparent 1px),
            linear-gradient(90deg, var(--t-accent-light) 1px, transparent 1px);
        background-size: 48px 48px;
    }

    .dark .t-admin-terminal .t-simple-grid-bg {
        opacity: 0.08;
        background-image:
            linear-gradient(var(--t-cyan) 1px, transparent 1px),
            linear-gradient(90deg, var(--t-cyan) 1px, transparent 1px);
    }

    .t-admin-terminal .t-simple-boot-seq {
        position: absolute;
        top: 1.4rem;
        left: 50%;
        transform: translateX(-50%);
        display: grid;
        gap: 0.16rem;
        text-align: center;
        font-family: var(--t-font-mono);
        font-size: 0.62rem;
        letter-spacing: 0.12em;
        color: var(--t-accent-light);
        z-index: 1;
        opacity: 0.72;
    }

    .dark .t-admin-terminal .t-simple-boot-seq {
        color: var(--t-cyan);
        opacity: 0.82;
    }

    .t-admin-terminal .t-simple-boot-seq > div {
        animation: t-boot-line 3.6s steps(18, end) infinite;
        white-space: nowrap;
        overflow: hidden;
        border-right: 1px solid currentColor;
        max-width: 0;
    }

    .t-admin-terminal .t-simple-boot-seq > div:nth-child(2) {
        animation-delay: 0.35s;
    }

    .t-admin-terminal .t-simple-boot-seq > div:nth-child(3) {
        animation-delay: 0.7s;
    }

    .t-admin-terminal .fi-simple-main {
        border-radius: 0;
        border: 1px solid rgba(2, 119, 189, 0.32);
        background: rgba(255, 255, 255, 0.87);
        box-shadow: 0 20px 40px -30px rgba(2, 119, 189, 0.45);
    }

    .dark .t-admin-terminal .fi-simple-main {
        border-color: var(--t-border);
        background: rgba(9, 15, 23, 0.9);
        box-shadow: 0 18px 40px -26px rgba(0, 229, 255, 0.4);
    }

    .t-admin-terminal .fi-simple-header-heading {
        font-family: var(--t-font-display);
        letter-spacing: 0.14em;
        text-transform: uppercase;
    }

    .t-admin-terminal .fi-simple-header-subheading {
        font-family: var(--t-font-mono);
        letter-spacing: 0.05em;
    }

    /* --- Animations --- */
    @keyframes t-fade-in {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes t-slide-up {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes t-pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(0.85); }
    }

    @keyframes t-blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }

    @keyframes t-boot-line {
        0% { max-width: 0; opacity: 0; }
        12% { opacity: 1; }
        45% { max-width: 36ch; opacity: 1; }
        70% { max-width: 36ch; opacity: 1; }
        100% { max-width: 0; opacity: 0; }
    }
</style>
@endonce
