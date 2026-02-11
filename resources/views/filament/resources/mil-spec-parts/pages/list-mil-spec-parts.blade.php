<x-filament-panels::page>
    @include('filament.pages.partials.terminal-theme')

    @php
        $totalParts = \App\Models\MilSpecPart::count();
        $withManufacturer = \App\Models\MilSpecPart::whereNotNull('manufacturer_id')->count();
        $totalManufacturers = \App\Models\Manufacturer::count();
        $recentParts = \App\Models\MilSpecPart::with('manufacturer')
            ->latest()
            ->limit(5)
            ->get();
        $withSuppliers = \App\Models\MilSpecPart::has('suppliers')->count();
    @endphp

    <div class="t-terminal">
        <div class="t-grid-bg"></div>

        {{-- Classification banner --}}
        <div class="t-classification-bar">
            <span class="t-classification-dot"></span>
            DEFENSE LOGISTICS INTELLIGENCE SYSTEM <span class="t-sep">//</span> NSN PARTS REGISTRY
            <span class="t-classification-dot"></span>
        </div>

        {{-- Stats row --}}
        <div class="nsn-stats-row" style="animation-delay: 0.05s">
            <div class="t-stat {{ $totalParts === 0 ? '' : 't-stat--accent' }}">
                <div class="t-stat-value">{{ $totalParts }}</div>
                <div class="t-stat-label">CATALOGED PARTS</div>
            </div>
            <div class="t-stat">
                <div class="t-stat-value">{{ $withManufacturer }}</div>
                <div class="t-stat-label">WITH MFR DATA</div>
            </div>
            <div class="t-stat">
                <div class="t-stat-value">{{ $totalManufacturers }}</div>
                <div class="t-stat-label">MANUFACTURERS</div>
            </div>
            <div class="t-stat {{ $withSuppliers ? 't-stat--success' : '' }}">
                <div class="t-stat-value">{{ $withSuppliers }}</div>
                <div class="t-stat-label">WITH SUPPLIERS</div>
            </div>
        </div>

        {{-- Main content grid --}}
        <div class="nsn-content-grid">
            {{-- Table panel (main) --}}
            <div class="nsn-table-area">
                <div class="t-panel t-scanlines" style="animation-delay: 0.1s">
                    <div class="t-panel-corner t-panel-corner--tl"></div>
                    <div class="t-panel-corner t-panel-corner--tr"></div>
                    <div class="t-panel-corner t-panel-corner--bl"></div>
                    <div class="t-panel-corner t-panel-corner--br"></div>

                    <div class="t-panel-header">
                        <div class="t-panel-header-icon">
                            <x-heroicon-o-cube class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="t-panel-title">PARTS REGISTRY</h2>
                            <p class="t-panel-subtitle">Mil-spec parts cataloged from NSN lookups and manual entries.</p>
                        </div>
                    </div>

                    <div class="t-divider"></div>

                    <div class="nsn-table-wrapper">
                        {{ $this->content }}
                    </div>
                </div>
            </div>

            {{-- Stacked support panels --}}
            <div class="nsn-sidebar">
                {{-- Recent additions --}}
                <div class="t-panel" style="animation-delay: 0.2s">
                    <div class="t-panel-corner t-panel-corner--tl"></div>
                    <div class="t-panel-corner t-panel-corner--tr"></div>
                    <div class="t-panel-corner t-panel-corner--bl"></div>
                    <div class="t-panel-corner t-panel-corner--br"></div>

                    <div class="t-card-header" style="padding: 0 0 0.75rem 0; margin-bottom: 0;">
                        <h2 class="t-card-title">RECENT ADDITIONS</h2>
                    </div>

                    @forelse($recentParts as $part)
                        <div class="t-row" style="padding-left: 0; padding-right: 0;">
                            <div style="flex: 1; min-width: 0;">
                                <div class="nsn-recent-nsn">{{ $part->nsn }}</div>
                                <div class="nsn-recent-desc">{{ Str::limit($part->description, 40) }}</div>
                                @if($part->manufacturer)
                                    <div class="nsn-recent-mfr">{{ $part->manufacturer->name }}</div>
                                @endif
                            </div>
                            <a href="{{ \App\Filament\Resources\MilSpecParts\MilSpecPartResource::getUrl('edit', ['record' => $part->id]) }}"
                               class="nsn-recent-link">
                                <x-heroicon-o-arrow-top-right-on-square class="w-3.5 h-3.5" />
                            </a>
                        </div>
                    @empty
                        <div class="t-empty" style="padding: 1.5rem 0;">
                            <div class="t-empty-title">NO PARTS YET</div>
                            <div class="t-empty-text">Use NSN Query to fetch your first part.</div>
                        </div>
                    @endforelse
                </div>

                {{-- Quick actions --}}
                <div class="t-panel" style="animation-delay: 0.3s">
                    <div class="t-panel-corner t-panel-corner--tl"></div>
                    <div class="t-panel-corner t-panel-corner--tr"></div>
                    <div class="t-panel-corner t-panel-corner--bl"></div>
                    <div class="t-panel-corner t-panel-corner--br"></div>

                    <div class="t-card-header" style="padding: 0 0 0.75rem 0; margin-bottom: 0;">
                        <h2 class="t-card-title">NSN PROCUREMENT</h2>
                    </div>

                    <div class="nsn-nav-list">
                        <a href="{{ \App\Filament\Pages\FetchNsnData::getUrl() }}" class="nsn-nav-item">
                            <div class="nsn-nav-item-icon nsn-nav-item-icon--cyan">
                                <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div class="nsn-nav-item-title">NSN Query Terminal</div>
                                <div class="nsn-nav-item-desc">Fetch part data from DLIS sources.</div>
                            </div>
                            <span class="nsn-nav-item-cta">OPEN</span>
                        </a>
                        <a href="{{ route('filament.admin.resources.manufacturers.index') }}" class="nsn-nav-item">
                            <div class="nsn-nav-item-icon nsn-nav-item-icon--green">
                                <x-heroicon-o-building-office class="w-4 h-4" />
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div class="nsn-nav-item-title">Manufacturers</div>
                                <div class="nsn-nav-item-desc">CAGE codes and company registry.</div>
                            </div>
                            <span class="nsn-nav-item-cta">OPEN</span>
                        </a>
                        <a href="{{ route('filament.admin.resources.suppliers.index') }}" class="nsn-nav-item">
                            <div class="nsn-nav-item-icon nsn-nav-item-icon--amber">
                                <x-heroicon-o-truck class="w-4 h-4" />
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div class="nsn-nav-item-title">Suppliers</div>
                                <div class="nsn-nav-item-desc">Vendor and supply chain contacts.</div>
                            </div>
                            <span class="nsn-nav-item-cta">OPEN</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- System footer --}}
        <div class="t-sys-footer">
            <span>NSN REGISTRY v1.0</span>
            <span class="t-sep">&bull;</span>
            <span>SESSION {{ strtoupper(substr(md5(session()->getId()), 0, 8)) }}</span>
            <span class="t-sep">&bull;</span>
            <span>OPERATOR: {{ auth()->user()?->name ?? 'UNKNOWN' }}</span>
        </div>
    </div>

    <style>
        /* --- Page-specific styles --- */

        .nsn-stats-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            margin-bottom: 1.25rem;
            animation: t-slide-up 0.4s ease-out both;
        }

        .nsn-stats-row .t-stat {
            flex: 1;
            min-width: 100px;
        }

        .nsn-content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1.25rem;
        }

        .nsn-table-area {
            min-width: 0;
        }

        .nsn-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        /* Table overrides */
        .nsn-table-wrapper .fi-ta-ctn {
            background: transparent;
            border: none;
            box-shadow: none;
            border-radius: 0;
        }

        .dark .nsn-table-wrapper .fi-ta-ctn {
            background: transparent;
        }

        .nsn-table-wrapper .fi-ta-header-ctn {
            border-bottom: 1px solid #e5e7eb;
        }

        .dark .nsn-table-wrapper .fi-ta-header-ctn {
            border-color: var(--t-border);
        }

        .nsn-table-wrapper .fi-ta-header,
        .nsn-table-wrapper .fi-ta-main,
        .nsn-table-wrapper .fi-ta-content-ctn,
        .nsn-table-wrapper .fi-ta-content,
        .nsn-table-wrapper .fi-ta-empty-state {
            background: transparent;
        }

        /* Move search bar above the table toolbar */
        .nsn-table-wrapper .fi-ta-header-toolbar {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 0.75rem;
        }

        .nsn-table-wrapper .fi-ta-header-toolbar > :nth-child(1),
        .nsn-table-wrapper .fi-ta-header-toolbar > :nth-child(2) {
            width: 100%;
        }

        .nsn-table-wrapper .fi-ta-header-toolbar > :nth-child(2) {
            order: -1;
            margin-inline-start: 0 !important;
            justify-content: flex-start;
        }

        .nsn-table-wrapper .fi-ta-header-toolbar > :nth-child(2) .fi-ta-search-field {
            width: 100%;
            max-width: none;
            flex: 1 1 auto;
        }

        /* Recent parts in sidebar */
        .nsn-recent-nsn {
            font-family: var(--t-font-mono);
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            color: var(--t-accent-light);
        }

        .dark .nsn-recent-nsn {
            color: var(--t-cyan);
            text-shadow: 0 0 12px var(--t-cyan-dim);
        }

        .nsn-recent-desc {
            font-size: 0.7rem;
            color: #6b7280;
            margin-top: 0.15rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark .nsn-recent-desc {
            color: var(--t-text-dim);
        }

        .nsn-recent-mfr {
            font-size: 0.6rem;
            font-weight: 500;
            color: #9ca3af;
            margin-top: 0.15rem;
            letter-spacing: 0.02em;
        }

        .dark .nsn-recent-mfr {
            color: var(--t-text-dim);
        }

        .nsn-recent-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            flex-shrink: 0;
            border: 1px solid #e5e7eb;
            color: #9ca3af;
            transition: all 0.2s ease;
        }

        .dark .nsn-recent-link {
            border-color: var(--t-border);
            color: var(--t-text-dim);
        }

        .nsn-recent-link:hover {
            color: var(--t-accent-light);
            border-color: var(--t-accent-light);
        }

        .dark .nsn-recent-link:hover {
            color: var(--t-cyan);
            border-color: var(--t-cyan-dim);
            box-shadow: 0 0 8px var(--t-cyan-glow);
        }

        /* Navigation items */
        .nsn-nav-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .nsn-nav-item {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.65rem 0.5rem;
            border: 1px solid transparent;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .nsn-nav-item:hover {
            border-color: #e5e7eb;
            background: #f9fafb;
        }

        .dark .nsn-nav-item:hover {
            border-color: var(--t-border);
            background: var(--t-surface-2);
        }

        .nsn-nav-item-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            flex-shrink: 0;
            border: 1px solid;
        }

        .nsn-nav-item-icon--cyan {
            border-color: rgba(2, 119, 189, 0.25);
            color: var(--t-accent-light);
        }
        .dark .nsn-nav-item-icon--cyan {
            border-color: var(--t-cyan-dim);
            color: var(--t-cyan);
            background: var(--t-cyan-glow);
        }

        .nsn-nav-item-icon--green {
            border-color: rgba(0, 230, 118, 0.25);
            color: #16a34a;
        }
        .dark .nsn-nav-item-icon--green {
            border-color: var(--t-green-dim);
            color: var(--t-green);
            background: rgba(0, 230, 118, 0.08);
        }

        .nsn-nav-item-icon--amber {
            border-color: rgba(255, 171, 0, 0.25);
            color: #d97706;
        }
        .dark .nsn-nav-item-icon--amber {
            border-color: var(--t-amber-dim);
            color: var(--t-amber);
            background: rgba(255, 171, 0, 0.08);
        }

        .nsn-nav-item-title {
            font-size: 0.8rem;
            font-weight: 600;
            color: #111827;
        }

        .dark .nsn-nav-item-title {
            color: #e6edf3;
        }

        .nsn-nav-item-desc {
            font-size: 0.65rem;
            color: #9ca3af;
            margin-top: 0.1rem;
        }

        .dark .nsn-nav-item-desc {
            color: var(--t-text-dim);
        }

        .nsn-nav-item-cta {
            font-family: var(--t-font-display);
            font-size: 0.55rem;
            font-weight: 600;
            letter-spacing: 0.15em;
            color: #9ca3af;
            flex-shrink: 0;
        }

        .dark .nsn-nav-item-cta {
            color: var(--t-text-dim);
        }

        .nsn-nav-item:hover .nsn-nav-item-cta {
            color: var(--t-accent-light);
        }

        .dark .nsn-nav-item:hover .nsn-nav-item-cta {
            color: var(--t-cyan);
        }
    </style>
</x-filament-panels::page>
