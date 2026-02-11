@php
    $totalLinks = \App\Models\BusinessLink::count();
    $activeLinks = \App\Models\BusinessLink::active()->count();
    $inactiveLinks = \App\Models\BusinessLink::where('is_active', false)->count();
    $categoryCount = \App\Models\BusinessLink::distinct()->count('category');
    $quickLinks = \App\Models\BusinessLink::active()
        ->ordered()
        ->get()
        ->groupBy(fn ($link) => $link->category->label());
@endphp

<x-terminal-page
    footer-left="BUSINESS HUB // QUICK LINKS"
    footer-center="SESSION {{ strtoupper(substr(md5(session()->getId()), 0, 8)) }}"
    footer-right="OPERATOR {{ auth()->user()?->name ?? 'UNKNOWN' }}"
>
    <x-slot:banner>
        DEFENSE LOGISTICS INTELLIGENCE SYSTEM <span class="t-sep">//</span> PORTAL LINK DIRECTORY
    </x-slot:banner>

    @include('filament.pages.partials.business-hub-styles')

    <div class="bh-shell">
        <div class="t-panel t-scanlines t-glow-hover" style="animation-delay: 0.05s">
            <div class="t-panel-corner t-panel-corner--tl"></div>
            <div class="t-panel-corner t-panel-corner--tr"></div>
            <div class="t-panel-corner t-panel-corner--bl"></div>
            <div class="t-panel-corner t-panel-corner--br"></div>

            <div class="bh-hero">
                <div>
                    <p class="bh-kicker">Business Hub</p>
                    <h1 class="bh-title">Business Links</h1>
                    <p class="bh-subtitle">Curate the portals, tools, and partner sites your team uses daily.</p>
                </div>

                <a href="{{ route('filament.admin.resources.business-links.create') }}" class="t-action-link">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    ADD LINK
                </a>
            </div>

            <div class="t-divider"></div>

            <div class="bh-stats-row">
                <div class="t-stat {{ $inactiveLinks ? 't-stat--warning' : '' }}">
                    <div class="t-stat-value">{{ $inactiveLinks }}</div>
                    <div class="t-stat-label">INACTIVE</div>
                </div>
                <div class="t-stat t-stat--accent">
                    <div class="t-stat-value">{{ $categoryCount }}</div>
                    <div class="t-stat-label">CATEGORIES</div>
                </div>
                <div class="t-stat t-stat--success">
                    <div class="t-stat-value">{{ $activeLinks }}</div>
                    <div class="t-stat-label">ACTIVE</div>
                </div>
                <div class="t-stat">
                    <div class="t-stat-value">{{ $totalLinks }}</div>
                    <div class="t-stat-label">TOTAL</div>
                </div>
            </div>
        </div>

        <div class="bh-main-grid">
            <div class="bh-primary-stack">
                <div class="t-panel t-scanlines t-glow-hover" style="animation-delay: 0.12s">
                    <div class="t-panel-corner t-panel-corner--tl"></div>
                    <div class="t-panel-corner t-panel-corner--tr"></div>
                    <div class="t-panel-corner t-panel-corner--bl"></div>
                    <div class="t-panel-corner t-panel-corner--br"></div>

                    <div class="t-panel-header">
                        <div class="t-panel-header-icon">
                            <x-heroicon-o-link class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="t-panel-title">LINK REGISTRY</h2>
                            <p class="t-panel-subtitle">Maintain portal URLs, category tags, and active status for your team.</p>
                        </div>
                    </div>

                    <div class="t-divider"></div>

                    <div class="bh-table-wrapper">
                        {{ $this->content }}
                    </div>
                </div>
            </div>

            <div class="bh-sidebar-stack">
                <div class="t-card t-glow-hover">
                    <div class="t-card-header">
                        <h2 class="t-card-title">ACTIVE QUICK LINKS</h2>
                    </div>
                    <div style="padding: 1rem;">
                        @forelse($quickLinks as $category => $links)
                            <div class="bh-quick-group">
                                <p class="bh-quick-label">{{ $category }}</p>
                                @foreach($links->take(4) as $link)
                                    <a href="{{ $link->url }}" target="_blank" class="bh-quick-item">
                                        <div class="bh-quick-name">{{ $link->name }}</div>
                                        @if($link->description)
                                            <div class="bh-quick-desc">{{ $link->description }}</div>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        @empty
                            <div class="t-empty">
                                <div class="t-empty-title">NO QUICK LINKS</div>
                                <div class="t-empty-text">Add a few shortcuts for your team.</div>
                                <a href="{{ route('filament.admin.resources.business-links.create') }}" class="t-card-link" style="display: inline-block; margin-top: 0.75rem;">ADD FIRST LINK</a>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="t-card t-glow-hover">
                    <div class="t-card-header">
                        <h2 class="t-card-title">BUSINESS HUB</h2>
                    </div>
                    <div style="padding: 1rem;">
                        <a href="{{ \App\Filament\Pages\BusinessHubDashboard::getUrl() }}" class="bh-side-link">
                            <div>
                                <div class="bh-side-link-title">Dashboard</div>
                                <div class="bh-side-link-desc">Compliance overview and alerts.</div>
                            </div>
                            <span class="bh-side-link-cta">OPEN</span>
                        </a>
                        <a href="{{ route('filament.admin.resources.business-documents.index') }}" class="bh-side-link">
                            <div>
                                <div class="bh-side-link-title">Documents</div>
                                <div class="bh-side-link-desc">Certificates, insurance, and compliance files.</div>
                            </div>
                            <span class="bh-side-link-cta">OPEN</span>
                        </a>
                        <a href="{{ route('filament.admin.resources.business-deadlines.index') }}" class="bh-side-link bh-side-link--warning">
                            <div>
                                <div class="bh-side-link-title">Deadlines</div>
                                <div class="bh-side-link-desc">Keep filings and renewals on schedule.</div>
                            </div>
                            <span class="bh-side-link-cta">OPEN</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-terminal-page>
