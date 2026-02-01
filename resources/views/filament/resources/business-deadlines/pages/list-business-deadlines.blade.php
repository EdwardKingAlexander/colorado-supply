<x-filament-panels::page>
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

    @push('styles')
        @include('filament.pages.partials.business-hub-styles')
    @endpush

    <div class="bh-dashboard">
        <div class="bh-shell space-y-6">
            <div class="bh-hero">
                <div>
                    <div class="bh-eyebrow">Business Hub</div>
                    <div class="bh-title">Business deadlines</div>
                    <div class="bh-subtitle">Stay ahead of filings, renewals, and recurring compliance obligations.</div>
                </div>
                <div class="bh-stat-row">
                    <div class="bh-stat {{ $overdueCount ? 'bh-stat-danger' : '' }}">
                        <div class="bh-stat-value">{{ $overdueCount }}</div>
                        <div class="bh-stat-label">Overdue</div>
                    </div>
                    <div class="bh-stat {{ $dueSoonCount ? 'bh-stat-warning' : '' }}">
                        <div class="bh-stat-value">{{ $dueSoonCount }}</div>
                        <div class="bh-stat-label">Due 14d</div>
                    </div>
                    <div class="bh-stat">
                        <div class="bh-stat-value">{{ $completedCount }}</div>
                        <div class="bh-stat-label">Completed</div>
                    </div>
                    <div class="bh-stat bh-stat-accent">
                        <div class="bh-stat-value">{{ $totalDeadlines }}</div>
                        <div class="bh-stat-label">Total</div>
                    </div>
                </div>
            </div>

            @if($overdueCount || $dueSoonCount)
                <div class="bh-alert-grid">
                    @if($overdueCount)
                        <div class="bh-alert-panel bh-alert-danger">
                            <div class="bh-alert-title">
                                {{ $overdueCount }} overdue {{ \Illuminate\Support\Str::plural('deadline', $overdueCount) }}
                            </div>
                            <div class="bh-alert-meta">Immediate action needed</div>
                            <div class="bh-alert-list">
                                @foreach($overdueDeadlines as $deadline)
                                    <div class="bh-alert-item">
                                        <span class="bh-alert-item-title">{{ $deadline->title }}</span>
                                        <span class="bh-alert-item-meta">{{ abs($deadline->daysUntilDue()) }}d overdue</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($dueSoonCount)
                        <div class="bh-alert-panel bh-alert-warning">
                            <div class="bh-alert-title">
                                {{ $dueSoonCount }} {{ \Illuminate\Support\Str::plural('deadline', $dueSoonCount) }} due soon
                            </div>
                            <div class="bh-alert-meta">Next 14 days</div>
                            <div class="bh-alert-list">
                                @foreach($dueSoonDeadlines as $deadline)
                                    <div class="bh-alert-item">
                                        <span class="bh-alert-item-title">{{ $deadline->title }}</span>
                                        <span class="bh-alert-item-meta bh-alert-item-meta-warning">{{ $deadline->due_date?->format('M j') ?? 'TBD' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bh-card">
                        <div class="bh-card-header">
                            <h2 class="bh-card-title">Deadline tracker</h2>
                            <a href="{{ route('filament.admin.resources.business-deadlines.create') }}" class="bh-link">
                                Add deadline
                            </a>
                        </div>
                        <div class="bh-table">
                            {{ $this->content }}
                        </div>
                    </div>
                </div>
                <div class="space-y-6">
                    <div class="bh-card">
                        <div class="bh-card-header">
                            <h2 class="bh-card-title">Next up</h2>
                        </div>

                        @forelse($nextDeadlines as $deadline)
                            @php
                                $days = $deadline->daysUntilDue();
                                $urgency = $days <= 7 ? 'urgent' : ($days <= 14 ? 'warning' : 'safe');
                            @endphp
                            <div class="bh-deadline-row">
                                <div class="bh-deadline-badge bh-deadline-badge-{{ $urgency }}">
                                    <span class="bh-deadline-days bh-deadline-days-{{ $urgency }}">{{ $days }}</span>
                                    <span class="bh-deadline-label">days</span>
                                </div>
                                <div class="bh-deadline-content">
                                    <div class="bh-deadline-title">{{ $deadline->title }}</div>
                                    <div class="bh-deadline-meta">
                                        <span>{{ $deadline->category->label() }}</span>
                                        @if($deadline->recurrence->value !== 'once')
                                            <span class="bh-meta-dot"></span>
                                            <span>{{ $deadline->recurrence->label() }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="bh-deadline-date">
                                    <div class="bh-deadline-date-value">{{ $deadline->due_date?->format('M j, Y') ?? 'TBD' }}</div>
                                    @if($deadline->external_url)
                                        <a href="{{ $deadline->external_url }}" target="_blank" class="bh-deadline-date-action">
                                            File now
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="bh-empty">
                                <div class="bh-empty-title">All caught up</div>
                                <div class="bh-empty-text">No upcoming deadlines in the next 30 days.</div>
                            </div>
                        @endforelse
                    </div>

                    <div class="bh-card">
                        <div class="bh-nav-card">
                            <div class="bh-nav-label">Manage</div>
                            <a href="{{ \App\Filament\Pages\BusinessHubDashboard::getUrl() }}" class="bh-nav-item bh-nav-docs">
                                <div class="bh-nav-content">
                                    <div class="bh-nav-title">Dashboard</div>
                                    <div class="bh-nav-desc">Compliance overview and alerts.</div>
                                </div>
                                <div class="bh-nav-cta">Open</div>
                            </a>
                            <a href="{{ route('filament.admin.resources.business-documents.index') }}" class="bh-nav-item bh-nav-docs">
                                <div class="bh-nav-content">
                                    <div class="bh-nav-title">Documents</div>
                                    <div class="bh-nav-desc">Certificates, insurance, and compliance files.</div>
                                </div>
                                <div class="bh-nav-cta">Open</div>
                            </a>
                            <a href="{{ route('filament.admin.resources.business-deadlines.index') }}" class="bh-nav-item bh-nav-deadlines">
                                <div class="bh-nav-content">
                                    <div class="bh-nav-title">Deadlines</div>
                                    <div class="bh-nav-desc">Keep filings and renewals on schedule.</div>
                                </div>
                                <div class="bh-nav-cta">Open</div>
                            </a>
                            <a href="{{ route('filament.admin.resources.business-links.index') }}" class="bh-nav-item bh-nav-links">
                                <div class="bh-nav-content">
                                    <div class="bh-nav-title">Quick links</div>
                                    <div class="bh-nav-desc">Curate the portals your team uses most.</div>
                                </div>
                                <div class="bh-nav-cta">Open</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
