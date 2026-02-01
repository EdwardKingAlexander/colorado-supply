<x-filament-panels::page>
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

    @push('styles')
        @include('filament.pages.partials.business-hub-styles')
    @endpush

    <div class="bh-dashboard">
        <div class="bh-shell space-y-6">
            <div class="bh-hero">
                <div>
                    <div class="bh-eyebrow">Business Hub</div>
                    <div class="bh-title">Business links</div>
                    <div class="bh-subtitle">Curate the portals, tools, and partner sites your team uses daily.</div>
                </div>
                <div class="bh-stat-row">
                    <div class="bh-stat {{ $inactiveLinks ? 'bh-stat-warning' : '' }}">
                        <div class="bh-stat-value">{{ $inactiveLinks }}</div>
                        <div class="bh-stat-label">Inactive</div>
                    </div>
                    <div class="bh-stat">
                        <div class="bh-stat-value">{{ $categoryCount }}</div>
                        <div class="bh-stat-label">Categories</div>
                    </div>
                    <div class="bh-stat">
                        <div class="bh-stat-value">{{ $activeLinks }}</div>
                        <div class="bh-stat-label">Active</div>
                    </div>
                    <div class="bh-stat bh-stat-accent">
                        <div class="bh-stat-value">{{ $totalLinks }}</div>
                        <div class="bh-stat-label">Total</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bh-card">
                        <div class="bh-card-header">
                            <h2 class="bh-card-title">Link registry</h2>
                            <a href="{{ route('filament.admin.resources.business-links.create') }}" class="bh-link">
                                Add link
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
                            <h2 class="bh-card-title">Active quick links</h2>
                        </div>
                        <div class="bh-quick-links">
                            @forelse($quickLinks as $category => $links)
                                <div class="bh-quick-link-group">
                                    <div class="bh-quick-link-label">{{ $category }}</div>
                                    <div class="bh-quick-link-list">
                                        @foreach($links->take(4) as $link)
                                            <a href="{{ $link->url }}" target="_blank" class="bh-quick-link-item">
                                                <div class="bh-quick-link-name">{{ $link->name }}</div>
                                                @if($link->description)
                                                    <div class="bh-quick-link-desc">{{ $link->description }}</div>
                                                @endif
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <div class="bh-empty" style="padding: 28px 16px;">
                                    <div class="bh-empty-title">No quick links yet</div>
                                    <div class="bh-empty-text">Add a few shortcuts for your team.</div>
                                    <a href="{{ route('filament.admin.resources.business-links.create') }}" class="bh-link" style="display: inline-block; margin-top: 10px;">
                                        Add your first link
                                    </a>
                                </div>
                            @endforelse
                        </div>
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
