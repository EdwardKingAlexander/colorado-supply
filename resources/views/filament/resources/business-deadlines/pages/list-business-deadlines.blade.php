@php
    $totalDeadlines = \App\Models\BusinessDeadline::count();
    $overdueDeadlines = \App\Models\BusinessDeadline::overdue()
        ->orderBy('due_date')
        ->limit(5)
        ->get();
    $overdueCount = \App\Models\BusinessDeadline::overdue()->count();
    $dueSoonDeadlines = \App\Models\BusinessDeadline::upcoming(14)
        ->orderBy('due_date')
        ->limit(5)
        ->get();
    $dueSoonCount = \App\Models\BusinessDeadline::upcoming(14)->count();
    $completedCount = \App\Models\BusinessDeadline::completed()->count();
    $nextDeadlines = \App\Models\BusinessDeadline::upcoming(30)
        ->orderBy('due_date')
        ->limit(6)
        ->get();
@endphp

<x-terminal-page
    footer-left="BUSINESS HUB // DEADLINE TRACKER"
    footer-center="SESSION {{ strtoupper(substr(md5(session()->getId()), 0, 8)) }}"
    footer-right="OPERATOR {{ auth()->user()?->name ?? 'UNKNOWN' }}"
>
    <x-slot:banner>
        DEFENSE LOGISTICS INTELLIGENCE SYSTEM <span class="t-sep">//</span> DEADLINE OPERATIONS
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
                    <h1 class="bh-title">Business Deadlines</h1>
                    <p class="bh-subtitle">Stay ahead of filings, renewals, and recurring compliance obligations.</p>
                </div>

                <a href="{{ route('filament.admin.resources.business-deadlines.create') }}" class="t-action-link">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    ADD DEADLINE
                </a>
            </div>

            <div class="t-divider"></div>

            <div class="bh-stats-row">
                <div class="t-stat {{ $overdueCount ? 't-stat--danger' : '' }}">
                    <div class="t-stat-value">{{ $overdueCount }}</div>
                    <div class="t-stat-label">OVERDUE</div>
                </div>
                <div class="t-stat {{ $dueSoonCount ? 't-stat--warning' : '' }}">
                    <div class="t-stat-value">{{ $dueSoonCount }}</div>
                    <div class="t-stat-label">DUE 14D</div>
                </div>
                <div class="t-stat t-stat--success">
                    <div class="t-stat-value">{{ $completedCount }}</div>
                    <div class="t-stat-label">COMPLETED</div>
                </div>
                <div class="t-stat t-stat--accent">
                    <div class="t-stat-value">{{ $totalDeadlines }}</div>
                    <div class="t-stat-label">TOTAL</div>
                </div>
            </div>
        </div>

        @if($overdueCount || $dueSoonCount)
            <div class="bh-alert-grid">
                @if($overdueCount)
                    <div class="bh-alert-panel bh-alert-panel--danger">
                        <p class="bh-alert-title">{{ $overdueCount }} OVERDUE {{ \Illuminate\Support\Str::plural('DEADLINE', $overdueCount) }}</p>
                        <p class="bh-alert-meta">Immediate action needed.</p>
                        <div class="bh-alert-list">
                            @foreach($overdueDeadlines as $deadline)
                                <div class="bh-alert-item">
                                    <span class="bh-alert-item-title">{{ $deadline->title }}</span>
                                    <span class="bh-alert-item-meta bh-alert-item-meta--danger">{{ abs($deadline->daysUntilDue()) }}D OVERDUE</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($dueSoonCount)
                    <div class="bh-alert-panel bh-alert-panel--warning">
                        <p class="bh-alert-title">{{ $dueSoonCount }} {{ \Illuminate\Support\Str::plural('DEADLINE', $dueSoonCount) }} DUE SOON</p>
                        <p class="bh-alert-meta">Next 14 days.</p>
                        <div class="bh-alert-list">
                            @foreach($dueSoonDeadlines as $deadline)
                                <div class="bh-alert-item">
                                    <span class="bh-alert-item-title">{{ $deadline->title }}</span>
                                    <span class="bh-alert-item-meta bh-alert-item-meta--warning">{{ $deadline->due_date?->format('M j') ?? 'TBD' }}</span>
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
                            <x-heroicon-o-calendar-days class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="t-panel-title">DEADLINE TRACKER</h2>
                            <p class="t-panel-subtitle">Search, filter, and manage all compliance due dates from one terminal table.</p>
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
                        <h2 class="t-card-title">NEXT UP</h2>
                    </div>

                    @forelse($nextDeadlines as $deadline)
                        @php
                            $days = $deadline->daysUntilDue();
                        @endphp
                        <div class="t-row" style="align-items: flex-start;">
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
                                <div class="bh-row-title">{{ $deadline->due_date?->format('M j, Y') ?? 'TBD' }}</div>
                                <div class="bh-row-meta" style="justify-content: flex-end;">{{ $days }} days</div>
                                @if($deadline->external_url)
                                    <a href="{{ $deadline->external_url }}" target="_blank" class="bh-row-accent">FILE NOW</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="t-empty">
                            <div class="t-empty-title">ALL CAUGHT UP</div>
                            <div class="t-empty-text">No upcoming deadlines in the next 30 days.</div>
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
                        <a href="{{ route('filament.admin.resources.business-links.index') }}" class="bh-side-link bh-side-link--success">
                            <div>
                                <div class="bh-side-link-title">Quick Links</div>
                                <div class="bh-side-link-desc">Curate the portals your team uses most.</div>
                            </div>
                            <span class="bh-side-link-cta">OPEN</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-terminal-page>
