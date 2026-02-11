@php
    $overdueDeadlines = $this->getOverdueDeadlines();
    $upcomingDeadlines = $this->getUpcomingDeadlines();
    $expiringDocuments = $this->getExpiringDocuments();
    $expiredDocuments = $this->getExpiredDocuments();
    $quickLinks = $this->getQuickLinks();
    $quickLinkCount = $quickLinks->flatten(1)->count();
    $urgentUpcoming = $upcomingDeadlines->filter(fn ($deadline) => $deadline->due_date && $deadline->due_date->lte(now()->addDays(14)));
    $expiringSoon = $expiringDocuments->filter(fn ($document) => $document->expiration_date && $document->expiration_date->lte(now()->addDays(30)));
@endphp

<x-terminal-page
    footer-left="BUSINESS HUB v2.0"
    footer-center="SESSION {{ strtoupper(substr(md5(session()->getId()), 0, 8)) }}"
    footer-right="OPERATOR {{ auth()->user()?->name ?? 'UNKNOWN' }}"
>
    <x-slot:banner>
        DEFENSE LOGISTICS INTELLIGENCE SYSTEM <span class="t-sep">//</span> BUSINESS HUB COMMAND
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
                    <h1 class="bh-title">Compliance Command Center</h1>
                    <p class="bh-subtitle">Monitor filings, renewal windows, and critical documentation across your locations.</p>
                </div>
                <div class="bh-stats-row">
                    <div class="t-stat {{ $overdueDeadlines->isNotEmpty() ? 't-stat--danger' : '' }}">
                        <div class="t-stat-value">{{ $overdueDeadlines->count() }}</div>
                        <div class="t-stat-label">OVERDUE</div>
                    </div>
                    <div class="t-stat {{ $urgentUpcoming->isNotEmpty() ? 't-stat--warning' : '' }}">
                        <div class="t-stat-value">{{ $urgentUpcoming->count() }}</div>
                        <div class="t-stat-label">DUE IN 14D</div>
                    </div>
                    <div class="t-stat {{ $expiringSoon->isNotEmpty() ? 't-stat--warning' : '' }}">
                        <div class="t-stat-value">{{ $expiringSoon->count() }}</div>
                        <div class="t-stat-label">EXPIRING 30D</div>
                    </div>
                    <div class="t-stat t-stat--accent">
                        <div class="t-stat-value">{{ $quickLinkCount }}</div>
                        <div class="t-stat-label">QUICK LINKS</div>
                    </div>
                </div>
            </div>
        </div>

        @if($overdueDeadlines->isNotEmpty() || $expiredDocuments->isNotEmpty())
            <div class="bh-alert-grid">
                @if($overdueDeadlines->isNotEmpty())
                    <div class="bh-alert-panel bh-alert-panel--danger">
                        <p class="bh-alert-title">{{ $overdueDeadlines->count() }} OVERDUE {{ Str::plural('DEADLINE', $overdueDeadlines->count()) }}</p>
                        <p class="bh-alert-meta">Immediate action needed.</p>
                        <div class="bh-alert-list">
                            @foreach($overdueDeadlines->take(5) as $deadline)
                                <div class="bh-alert-item">
                                    <span class="bh-alert-item-title">{{ $deadline->title }}</span>
                                    <span class="bh-alert-item-meta bh-alert-item-meta--danger">{{ abs($deadline->daysUntilDue()) }}D OVERDUE</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($expiredDocuments->isNotEmpty())
                    <div class="bh-alert-panel bh-alert-panel--danger">
                        <p class="bh-alert-title">{{ $expiredDocuments->count() }} EXPIRED {{ Str::plural('DOCUMENT', $expiredDocuments->count()) }}</p>
                        <p class="bh-alert-meta">Review and renew immediately.</p>
                        <div class="bh-alert-list">
                            @foreach($expiredDocuments->take(5) as $document)
                                <div class="bh-alert-item">
                                    <span class="bh-alert-item-title">{{ $document->name }}</span>
                                    <span class="bh-alert-item-meta bh-alert-item-meta--danger">{{ $document->expiration_date?->diffForHumans() }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <div class="bh-main-grid">
            <div class="bh-primary-stack">
                <div class="t-card t-glow-hover">
                    <div class="t-card-header">
                        <h2 class="t-card-title">UPCOMING DEADLINES</h2>
                        <a href="{{ route('filament.admin.resources.business-deadlines.index') }}" class="t-card-link">VIEW ALL</a>
                    </div>

                    @forelse($upcomingDeadlines as $deadline)
                        @php
                            $days = max($deadline->daysUntilDue(), 0);
                            $urgency = $days <= 7 ? 'danger' : ($days <= 14 ? 'warning' : 'safe');
                        @endphp
                        <div class="t-row">
                            <div class="bh-day-pill bh-day-pill--{{ $urgency }}">
                                <span class="bh-day-value bh-day-value--{{ $urgency }}">{{ $days }}</span>
                                <span class="bh-day-label">DAYS</span>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div class="bh-row-title">{{ $deadline->title }}</div>
                                <div class="bh-row-meta">
                                    <span>{{ $deadline->category->label() }}</span>
                                    @if($deadline->recurrence->value !== 'once')
                                        <span class="bh-row-meta-dot"></span>
                                        <span>{{ $deadline->recurrence->label() }}</span>
                                    @endif
                                </div>
                            </div>
                            <div style="text-align: right; flex-shrink: 0;">
                                <div class="bh-row-title">{{ $deadline->due_date->format('M j, Y') }}</div>
                                @if($deadline->external_url)
                                    <a href="{{ $deadline->external_url }}" target="_blank" class="bh-row-accent">FILE NOW</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="t-empty">
                            <div class="t-empty-title">ALL CAUGHT UP</div>
                            <div class="t-empty-text">No deadlines in the next 30 days.</div>
                        </div>
                    @endforelse
                </div>

                @if($expiringDocuments->isNotEmpty())
                    <div class="t-card t-glow-hover">
                        <div class="t-card-header">
                            <h2 class="t-card-title">DOCUMENTS EXPIRING SOON</h2>
                            <a href="{{ route('filament.admin.resources.business-documents.index') }}" class="t-card-link">VIEW ALL</a>
                        </div>

                        @foreach($expiringDocuments as $document)
                            @php
                                $days = $document->daysUntilExpiration();
                                $urgencyClass = $days <= 14 ? 'bh-row-danger' : 'bh-row-warning';
                            @endphp
                            <div class="t-row">
                                <div style="flex: 1; min-width: 0;">
                                    <div class="bh-row-title">{{ $document->name }}</div>
                                    <div class="bh-row-meta">
                                        <span>{{ $document->type->label() }}</span>
                                        @if($document->issuing_authority)
                                            <span class="bh-row-meta-dot"></span>
                                            <span>{{ $document->issuing_authority }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div style="text-align: right; flex-shrink: 0;">
                                    <div class="bh-row-title {{ $urgencyClass }}">{{ $document->expiration_date->format('M j, Y') }}</div>
                                    <div class="bh-row-meta" style="justify-content: flex-end;">{{ $days }}D LEFT</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bh-sidebar-stack">
                <div class="t-card t-glow-hover">
                    <div class="t-card-header">
                        <h2 class="t-card-title">QUICK LINKS</h2>
                        <a href="{{ route('filament.admin.resources.business-links.index') }}" class="t-card-link">MANAGE</a>
                    </div>
                    <div style="padding: 1rem;">
                        @forelse($quickLinks as $category => $links)
                            <div class="bh-quick-group">
                                <p class="bh-quick-label">{{ $category }}</p>
                                @foreach($links as $link)
                                    <a href="{{ $link->url }}" target="_blank" class="bh-quick-item">
                                        <div class="bh-quick-name">{{ $link->name }}</div>
                                        @if($link->description)
                                            <div class="bh-quick-desc">{{ $link->description }}</div>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        @empty
                            <div class="t-empty" style="padding: 1.75rem 1rem;">
                                <div class="t-empty-title">NO QUICK LINKS</div>
                                <div class="t-empty-text">Add shortcuts for your team.</div>
                                <a href="{{ route('filament.admin.resources.business-links.create') }}" class="t-card-link" style="display: inline-block; margin-top: 0.75rem;">ADD FIRST LINK</a>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="t-card t-glow-hover">
                    <div class="t-card-header">
                        <h2 class="t-card-title">MANAGE MODULES</h2>
                    </div>
                    <div style="padding: 1rem;">
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
                        <a href="{{ route('filament.admin.resources.business-links.index') }}" class="bh-side-link bh-side-link--success">
                            <div>
                                <div class="bh-side-link-title">Quick Links</div>
                                <div class="bh-side-link-desc">Curate mission-critical portals.</div>
                            </div>
                            <span class="bh-side-link-cta">OPEN</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-terminal-page>
