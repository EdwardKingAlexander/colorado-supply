<x-filament-panels::page>
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

    @push('styles')
        @include('filament.pages.partials.business-hub-styles')
    @endpush

    <div class="bh-dashboard">
        <div class="bh-shell space-y-6">
            <div class="bh-hero">
                <div>
                    <div class="bh-eyebrow">Business Hub</div>
                    <div class="bh-title">Business documents</div>
                    <div class="bh-subtitle">Track certificates, licenses, and compliance files across your organization.</div>
                </div>
                <div class="bh-stat-row">
                    <div class="bh-stat {{ $expiredCount ? 'bh-stat-danger' : '' }}">
                        <div class="bh-stat-value">{{ $expiredCount }}</div>
                        <div class="bh-stat-label">Expired</div>
                    </div>
                    <div class="bh-stat {{ $expiringCount ? 'bh-stat-warning' : '' }}">
                        <div class="bh-stat-value">{{ $expiringCount }}</div>
                        <div class="bh-stat-label">Expiring 30d</div>
                    </div>
                    <div class="bh-stat">
                        <div class="bh-stat-value">{{ $activeDocuments }}</div>
                        <div class="bh-stat-label">Active</div>
                    </div>
                    <div class="bh-stat bh-stat-accent">
                        <div class="bh-stat-value">{{ $totalDocuments }}</div>
                        <div class="bh-stat-label">Total</div>
                    </div>
                </div>
            </div>

            @if($expiredCount || $expiringCount)
                <div class="bh-alert-grid">
                    @if($expiredCount)
                        <div class="bh-alert-panel bh-alert-danger">
                            <div class="bh-alert-title">
                                {{ $expiredCount }} expired {{ \Illuminate\Support\Str::plural('document', $expiredCount) }}
                            </div>
                            <div class="bh-alert-meta">Immediate renewal needed</div>
                            <div class="bh-alert-list">
                                @foreach($expiredDocuments as $document)
                                    <div class="bh-alert-item">
                                        <span class="bh-alert-item-title">{{ $document->name }}</span>
                                        <span class="bh-alert-item-meta">{{ $document->expiration_date?->diffForHumans() ?? 'Expired' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($expiringCount)
                        <div class="bh-alert-panel bh-alert-warning">
                            <div class="bh-alert-title">
                                {{ $expiringCount }} {{ \Illuminate\Support\Str::plural('document', $expiringCount) }} expiring soon
                            </div>
                            <div class="bh-alert-meta">Next 30 days</div>
                            <div class="bh-alert-list">
                                @foreach($expiringDocuments as $document)
                                    <div class="bh-alert-item">
                                        <span class="bh-alert-item-title">{{ $document->name }}</span>
                                        <span class="bh-alert-item-meta bh-alert-item-meta-warning">{{ $document->expiration_date?->format('M j') ?? 'TBD' }}</span>
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
                            <h2 class="bh-card-title">Document registry</h2>
                            <a href="{{ route('filament.admin.resources.business-documents.create') }}" class="bh-link">
                                Add document
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
                            <h2 class="bh-card-title">Expiring soon</h2>
                        </div>

                        @forelse($expiringDocuments as $document)
                            @php
                                $days = $document->daysUntilExpiration();
                                $urgency = $days !== null && $days <= 14 ? 'urgent' : ($days !== null && $days <= 30 ? 'warning' : 'normal');
                            @endphp
                            <div class="bh-doc-row">
                                <div class="bh-doc-info">
                                    <div class="bh-doc-name">{{ $document->name }}</div>
                                    <div class="bh-doc-meta">
                                        <span>{{ $document->type->label() }}</span>
                                        @if($document->issuing_authority)
                                            <span class="bh-meta-dot"></span>
                                            <span>{{ $document->issuing_authority }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="bh-doc-expiry">
                                    <div class="bh-doc-expiry-date bh-doc-expiry-{{ $urgency }}">
                                        {{ $document->expiration_date?->format('M j, Y') ?? 'TBD' }}
                                    </div>
                                    @if($days !== null)
                                        <div class="bh-doc-expiry-days">{{ $days }}d left</div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="bh-empty">
                                <div class="bh-empty-title">No renewals coming up</div>
                                <div class="bh-empty-text">You're clear for the next 30 days.</div>
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
