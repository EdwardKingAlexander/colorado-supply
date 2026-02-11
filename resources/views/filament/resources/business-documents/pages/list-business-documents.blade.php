@php
    $totalDocuments = \App\Models\BusinessDocument::count();
    $activeDocuments = \App\Models\BusinessDocument::active()->count();
    $expiredQuery = \App\Models\BusinessDocument::query()
        ->where('status', \App\Enums\DocumentStatus::Expired)
        ->orWhere(function ($query) {
            $query->whereNotNull('expiration_date')
                ->where('expiration_date', '<', now());
        });
    $expiredDocuments = (clone $expiredQuery)
        ->orderBy('expiration_date', 'desc')
        ->limit(5)
        ->get();
    $expiredCount = (clone $expiredQuery)->count();
    $expiringDocuments = \App\Models\BusinessDocument::expiringSoon(30)
        ->orderBy('expiration_date')
        ->limit(5)
        ->get();
    $expiringCount = \App\Models\BusinessDocument::expiringSoon(30)->count();
@endphp

<x-terminal-page
    footer-left="BUSINESS HUB // DOCUMENT REGISTRY"
    footer-center="SESSION {{ strtoupper(substr(md5(session()->getId()), 0, 8)) }}"
    footer-right="OPERATOR {{ auth()->user()?->name ?? 'UNKNOWN' }}"
>
    <x-slot:banner>
        DEFENSE LOGISTICS INTELLIGENCE SYSTEM <span class="t-sep">//</span> DOCUMENT CONTROL
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
                    <h1 class="bh-title">Business Documents</h1>
                    <p class="bh-subtitle">Track certificates, licenses, and compliance files across your organization.</p>
                </div>

                <a href="{{ route('filament.admin.resources.business-documents.create') }}" class="t-action-link">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    ADD DOCUMENT
                </a>
            </div>

            <div class="t-divider"></div>

            <div class="bh-stats-row">
                <div class="t-stat {{ $expiredCount ? 't-stat--danger' : '' }}">
                    <div class="t-stat-value">{{ $expiredCount }}</div>
                    <div class="t-stat-label">EXPIRED</div>
                </div>
                <div class="t-stat {{ $expiringCount ? 't-stat--warning' : '' }}">
                    <div class="t-stat-value">{{ $expiringCount }}</div>
                    <div class="t-stat-label">EXPIRING SOON</div>
                </div>
                <div class="t-stat t-stat--success">
                    <div class="t-stat-value">{{ $activeDocuments }}</div>
                    <div class="t-stat-label">ACTIVE</div>
                </div>
                <div class="t-stat t-stat--accent">
                    <div class="t-stat-value">{{ $totalDocuments }}</div>
                    <div class="t-stat-label">TOTAL</div>
                </div>
            </div>
        </div>

        @if($expiredCount || $expiringCount)
            <div class="bh-alert-grid">
                @if($expiredCount)
                    <div class="bh-alert-panel bh-alert-panel--danger">
                        <p class="bh-alert-title">{{ $expiredCount }} EXPIRED {{ Str::plural('DOCUMENT', $expiredCount) }}</p>
                        <p class="bh-alert-meta">Immediate renewal required.</p>
                        <div class="bh-alert-list">
                            @foreach($expiredDocuments as $document)
                                <div class="bh-alert-item">
                                    <span class="bh-alert-item-title">{{ $document->name }}</span>
                                    <span class="bh-alert-item-meta bh-alert-item-meta--danger">{{ $document->expiration_date?->diffForHumans() ?? 'EXPIRED' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($expiringCount)
                    <div class="bh-alert-panel bh-alert-panel--warning">
                        <p class="bh-alert-title">{{ $expiringCount }} {{ Str::plural('DOCUMENT', $expiringCount) }} EXPIRING SOON</p>
                        <p class="bh-alert-meta">Next 30 days.</p>
                        <div class="bh-alert-list">
                            @foreach($expiringDocuments as $document)
                                <div class="bh-alert-item">
                                    <span class="bh-alert-item-title">{{ $document->name }}</span>
                                    <span class="bh-alert-item-meta bh-alert-item-meta--warning">{{ $document->expiration_date?->format('M j, Y') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <div class="bh-main-grid">
            <div class="bh-primary-stack">
                <div class="t-panel t-scanlines t-glow-hover" style="animation-delay: 0.12s">
                    <div class="t-panel-corner t-panel-corner--tl"></div>
                    <div class="t-panel-corner t-panel-corner--tr"></div>
                    <div class="t-panel-corner t-panel-corner--bl"></div>
                    <div class="t-panel-corner t-panel-corner--br"></div>

                    <div class="t-panel-header">
                        <div class="t-panel-header-icon">
                            <x-heroicon-o-document-text class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="t-panel-title">DOCUMENT REGISTRY</h2>
                            <p class="t-panel-subtitle">Audit all document records, status tags, and expiry windows in one table.</p>
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
                        <h2 class="t-card-title">EXPIRING SOON</h2>
                    </div>

                    @forelse($expiringDocuments as $document)
                        @php
                            $days = $document->daysUntilExpiration();
                            $isUrgent = $days !== null && $days <= 14;
                        @endphp
                        <div class="t-row" style="justify-content: space-between;">
                            <div style="min-width: 0; flex: 1;">
                                <div class="bh-row-title">{{ $document->name }}</div>
                                <div class="bh-row-meta">{{ $document->type->label() }}</div>
                            </div>
                            <div style="text-align: right; flex-shrink: 0;">
                                <div class="bh-row-title {{ $isUrgent ? 'bh-row-danger' : 'bh-row-warning' }}">{{ $document->expiration_date?->format('M j') }}</div>
                                @if($days !== null)
                                    <div class="bh-row-meta" style="justify-content: flex-end;">{{ $days }}D LEFT</div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="t-empty">
                            <div class="t-empty-title">ALL CLEAR</div>
                            <div class="t-empty-text">No documents expiring in the next 30 days.</div>
                        </div>
                    @endforelse
                </div>

                <div class="t-card t-glow-hover">
                    <div class="t-card-header">
                        <h2 class="t-card-title">BUSINESS HUB</h2>
                    </div>
                    <div style="padding: 1rem;">
                        <a href="{{ \App\Filament\Pages\BusinessHubDashboard::getUrl() }}" class="bh-side-link">
                            <div>
                                <div class="bh-side-link-title">Dashboard</div>
                                <div class="bh-side-link-desc">Overview and alerts.</div>
                            </div>
                            <span class="bh-side-link-cta">OPEN</span>
                        </a>
                        <a href="{{ route('filament.admin.resources.business-deadlines.index') }}" class="bh-side-link bh-side-link--warning">
                            <div>
                                <div class="bh-side-link-title">Deadlines</div>
                                <div class="bh-side-link-desc">Filings and renewals.</div>
                            </div>
                            <span class="bh-side-link-cta">OPEN</span>
                        </a>
                        <a href="{{ route('filament.admin.resources.business-links.index') }}" class="bh-side-link bh-side-link--success">
                            <div>
                                <div class="bh-side-link-title">Quick Links</div>
                                <div class="bh-side-link-desc">Portals and resources.</div>
                            </div>
                            <span class="bh-side-link-cta">OPEN</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-terminal-page>
