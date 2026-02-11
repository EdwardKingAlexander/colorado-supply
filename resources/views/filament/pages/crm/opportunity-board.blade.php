<x-terminal-page
    footer-left="CRM // OPPORTUNITY BOARD"
    footer-center="SESSION {{ strtoupper(substr(md5(session()->getId()), 0, 8)) }}"
    footer-right="OPERATOR {{ auth()->user()?->name ?? 'UNKNOWN' }}"
>
    <x-slot:banner>
        DEFENSE LOGISTICS INTELLIGENCE SYSTEM <span class="t-sep">//</span> OPPORTUNITY BOARD
    </x-slot:banner>

    <div class="crm-board-shell" x-data="opportunityBoard">
        <div class="t-panel t-scanlines t-glow-hover" style="animation-delay: 0.05s">
            <div class="t-panel-corner t-panel-corner--tl"></div>
            <div class="t-panel-corner t-panel-corner--tr"></div>
            <div class="t-panel-corner t-panel-corner--bl"></div>
            <div class="t-panel-corner t-panel-corner--br"></div>

            <div class="t-panel-header">
                <div class="t-panel-header-icon">
                    <x-heroicon-o-squares-2x2 class="w-5 h-5" />
                </div>
                <div>
                    <h2 class="t-panel-title">PIPELINE COMMAND BOARD</h2>
                    <p class="t-panel-subtitle">Drag opportunities between stages to update forecast state in real time.</p>
                </div>
            </div>

            <div class="t-divider"></div>

            <div class="crm-stats-row">
                <div class="t-stat t-stat--accent">
                    <div class="t-stat-value">${{ number_format($metrics['total_pipeline'] ?? 0, 2) }}</div>
                    <div class="t-stat-label">TOTAL PIPELINE</div>
                </div>
                <div class="t-stat t-stat--success">
                    <div class="t-stat-value">${{ number_format($metrics['total_forecast'] ?? 0, 2) }}</div>
                    <div class="t-stat-label">WEIGHTED FORECAST</div>
                </div>
                <div class="t-stat t-stat--warning">
                    <div class="t-stat-value">${{ number_format($metrics['expected_close_this_month'] ?? 0, 2) }}</div>
                    <div class="t-stat-label">EXPECTED THIS MONTH</div>
                </div>
                <div class="t-stat {{ ($metrics['win_rate'] ?? 0) >= 50 ? 't-stat--success' : 't-stat--danger' }}">
                    <div class="t-stat-value">{{ number_format($metrics['win_rate'] ?? 0, 1) }}%</div>
                    <div class="t-stat-label">WIN RATE (90D)</div>
                </div>
            </div>
        </div>

        <div class="t-panel" style="animation-delay: 0.1s">
            <div class="t-panel-corner t-panel-corner--tl"></div>
            <div class="t-panel-corner t-panel-corner--tr"></div>
            <div class="t-panel-corner t-panel-corner--bl"></div>
            <div class="t-panel-corner t-panel-corner--br"></div>

            <div class="crm-filter-grid">
                <div>
                    <label class="crm-filter-label">PIPELINE</label>
                    <select wire:model.live="pipelineId" class="crm-select">
                        @foreach($this->getPipelines() as $pipeline)
                            <option value="{{ $pipeline->id }}">{{ $pipeline->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="crm-filter-label">OWNER</label>
                    <select wire:model.live="filters.owner_id" class="crm-select">
                        <option value="">All Owners</option>
                        @foreach($this->getOwners() as $owner)
                            <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="crm-filter-label">STATUS</label>
                    <select wire:model.live="filters.status" class="crm-select">
                        <option value="open">Open</option>
                        <option value="won">Won</option>
                        <option value="lost">Lost</option>
                        <option value="">All</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="t-panel t-scanlines" style="animation-delay: 0.15s">
            <div class="t-panel-corner t-panel-corner--tl"></div>
            <div class="t-panel-corner t-panel-corner--tr"></div>
            <div class="t-panel-corner t-panel-corner--bl"></div>
            <div class="t-panel-corner t-panel-corner--br"></div>

            <div class="t-panel-header">
                <div class="t-panel-header-icon">
                    <x-heroicon-o-arrow-path-rounded-square class="w-5 h-5" />
                </div>
                <div>
                    <h2 class="t-panel-title">KANBAN STAGES</h2>
                    <p class="t-panel-subtitle">Drag cards across stage columns to update lifecycle position.</p>
                </div>
            </div>

            <div class="t-divider"></div>

            <div class="crm-board-scroll">
                <div class="crm-stage-grid" style="min-width: min-content;">
                    @foreach($stages as $stage)
                        @php
                            $stageState = $stage->is_won ? 'won' : ($stage->is_lost ? 'lost' : 'open');
                            $stageCount = $opportunities->get($stage->id)?->count() ?? 0;
                            $stageAmount = $opportunities->get($stage->id)?->sum('amount') ?? 0;
                            $stageForecast = $opportunities->get($stage->id)?->sum(fn($o) => $o->forecast_amount) ?? 0;
                        @endphp
                        <div class="crm-stage crm-stage--{{ $stageState }}" data-stage-id="{{ $stage->id }}" x-init="initSortable($el)">
                            <div class="crm-stage-header">
                                <div class="crm-stage-title-row">
                                    <h3 class="crm-stage-title">{{ $stage->name }}</h3>
                                    @if($stage->is_won)
                                        <x-heroicon-o-check-circle class="h-5 w-5 text-green-500" />
                                    @elseif($stage->is_lost)
                                        <x-heroicon-o-x-circle class="h-5 w-5 text-red-500" />
                                    @endif
                                </div>
                                <div class="crm-stage-meta">
                                    <span>{{ $stageCount }} deals</span>
                                    <span>${{ number_format($stageAmount, 0) }}</span>
                                    <span class="crm-stage-forecast">F: ${{ number_format($stageForecast, 0) }}</span>
                                </div>
                            </div>

                            <div class="crm-stage-body sortable-container">
                                @foreach($opportunities->get($stage->id) ?? [] as $opportunity)
                                    <div class="crm-opp-card" data-opportunity-id="{{ $opportunity->id }}">
                                        <div>
                                            <div class="crm-opp-title">{{ $opportunity->title }}</div>
                                            <div class="crm-opp-customer">{{ $opportunity->customer->name }}</div>
                                        </div>

                                        <div class="crm-opp-financial">
                                            <div class="crm-opp-amount">${{ number_format($opportunity->amount, 0) }}</div>
                                            <div class="crm-opp-prob">{{ $opportunity->probability_effective }}%</div>
                                        </div>

                                        <div class="crm-opp-meta">
                                            <span>
                                                @if($opportunity->expected_close_date)
                                                    {{ \Carbon\Carbon::parse($opportunity->expected_close_date)->format('M d') }}
                                                @else
                                                    TBD
                                                @endif
                                            </span>
                                            <span class="crm-owner-avatar">{{ strtoupper(substr($opportunity->owner->name ?? '?', 0, 1)) }}</span>
                                        </div>

                                        @if($opportunity->score)
                                            <div class="crm-opp-score">
                                                @for($i = 1; $i <= 5; $i++)
                                                    @if($i <= $opportunity->score)
                                                        <x-heroicon-s-star class="h-3 w-3 text-amber-400" />
                                                    @else
                                                        <x-heroicon-o-star class="h-3 w-3 text-gray-300 dark:text-gray-600" />
                                                    @endif
                                                @endfor
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div
        x-data="{ open: false, opportunityId: null, stageId: null, lostReasonId: null }"
        x-on:open-lost-reason-modal.window="open = true; opportunityId = $event.detail.opportunityId; stageId = $event.detail.stageId;"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
    >
        <div class="flex min-h-screen items-center justify-center px-4">
            <div class="fixed inset-0 bg-black/50" @click="open = false"></div>

            <div class="crm-modal">
                <h3 class="crm-modal-title">SELECT LOST REASON</h3>

                <div class="mt-4">
                    <select x-model="lostReasonId" class="crm-select">
                        <option value="">-- Select Reason --</option>
                        @foreach(\App\Models\LostReason::where('active', true)->get() as $reason)
                            <option value="{{ $reason->id }}">{{ $reason->label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="crm-modal-actions">
                    <button @click="open = false" class="crm-btn crm-btn--muted">CANCEL</button>
                    <button
                        @click="if (lostReasonId) { $wire.markAsLost(opportunityId, lostReasonId); open = false; lostReasonId = null; }"
                        class="crm-btn crm-btn--danger"
                    >
                        MARK LOST
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('opportunityBoard', () => ({
                    sortables: [],

                    initSortable(el) {
                        const sortable = Sortable.create(el.querySelector('.sortable-container'), {
                            group: 'opportunities',
                            animation: 150,
                            ghostClass: 'opacity-50',
                            dragClass: 'rotate-2',
                            onEnd: (evt) => {
                                const opportunityId = evt.item.dataset.opportunityId;
                                const newStageId = evt.to.closest('[data-stage-id]').dataset.stageId;

                                @this.call('moveOpportunity', opportunityId, newStageId);
                            }
                        });

                        this.sortables.push(sortable);
                    }
                }));
            });
        </script>
    @endpush

    <style>
        .crm-board-shell {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .crm-stats-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
        }

        .crm-stats-row .t-stat {
            flex: 1;
            min-width: 170px;
        }

        .crm-filter-grid {
            display: grid;
            gap: 0.9rem;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        @media (min-width: 768px) {
            .crm-filter-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .crm-filter-label {
            display: block;
            margin-bottom: 0.35rem;
            font-family: var(--t-font-display);
            font-size: 0.56rem;
            font-weight: 600;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #9ca3af;
        }

        .dark .crm-filter-label {
            color: var(--t-text-dim);
        }

        .crm-select {
            width: 100%;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #111827;
            padding: 0.5rem 0.65rem;
            font-size: 0.78rem;
            font-family: var(--t-font-mono);
            letter-spacing: 0.03em;
        }

        .dark .crm-select {
            border-color: var(--t-border);
            background: var(--t-surface-2);
            color: #e6edf3;
        }

        .crm-board-scroll {
            overflow-x: auto;
            padding-bottom: 0.35rem;
        }

        .crm-stage-grid {
            display: inline-flex;
            gap: 0.85rem;
            align-items: flex-start;
        }

        .crm-stage {
            width: 320px;
            border: 1px solid #e5e7eb;
            background: #f8fafc;
            min-height: 420px;
        }

        .dark .crm-stage {
            border-color: var(--t-border);
            background: var(--t-surface-2);
        }

        .crm-stage--won {
            box-shadow: inset 0 0 0 1px rgba(0, 230, 118, 0.2);
        }

        .crm-stage--lost {
            box-shadow: inset 0 0 0 1px rgba(255, 23, 68, 0.2);
        }

        .crm-stage-header {
            border-bottom: 1px solid #e5e7eb;
            padding: 0.75rem;
            background: #f3f4f6;
        }

        .dark .crm-stage-header {
            border-bottom-color: var(--t-border);
            background: rgba(255, 255, 255, 0.02);
        }

        .crm-stage-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .crm-stage-title {
            margin: 0;
            font-family: var(--t-font-display);
            font-size: 0.64rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: #111827;
        }

        .dark .crm-stage-title {
            color: #e6edf3;
        }

        .crm-stage-meta {
            margin-top: 0.4rem;
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            font-family: var(--t-font-mono);
            font-size: 0.64rem;
            color: #6b7280;
        }

        .dark .crm-stage-meta {
            color: var(--t-text-dim);
        }

        .crm-stage-forecast {
            color: var(--t-accent-light);
        }

        .dark .crm-stage-forecast {
            color: var(--t-cyan);
        }

        .crm-stage-body {
            min-height: 100px;
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
            padding: 0.55rem;
        }

        .crm-opp-card {
            cursor: move;
            border: 1px solid #e5e7eb;
            background: #fff;
            padding: 0.65rem;
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
            transition: border-color 0.15s ease, transform 0.15s ease;
        }

        .dark .crm-opp-card {
            border-color: var(--t-border);
            background: var(--t-surface);
        }

        .crm-opp-card:hover {
            border-color: var(--t-accent-light);
            transform: translateY(-1px);
        }

        .dark .crm-opp-card:hover {
            border-color: var(--t-cyan-dim);
        }

        .crm-opp-title {
            font-size: 0.76rem;
            font-weight: 600;
            color: #111827;
        }

        .dark .crm-opp-title {
            color: #e6edf3;
        }

        .crm-opp-customer {
            margin-top: 0.2rem;
            font-size: 0.66rem;
            color: #6b7280;
        }

        .dark .crm-opp-customer {
            color: var(--t-text-dim);
        }

        .crm-opp-financial {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.6rem;
        }

        .crm-opp-amount {
            font-family: var(--t-font-mono);
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--t-green);
        }

        .crm-opp-prob {
            border: 1px solid rgba(2, 119, 189, 0.25);
            color: var(--t-accent-light);
            font-family: var(--t-font-mono);
            font-size: 0.62rem;
            font-weight: 600;
            padding: 0.18rem 0.4rem;
        }

        .dark .crm-opp-prob {
            border-color: var(--t-cyan-dim);
            color: var(--t-cyan);
            background: var(--t-cyan-glow);
        }

        .crm-opp-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.62rem;
            color: #9ca3af;
            gap: 0.5rem;
        }

        .dark .crm-opp-meta {
            color: var(--t-text-dim);
        }

        .crm-owner-avatar {
            width: 1.35rem;
            height: 1.35rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: var(--t-font-mono);
            font-size: 0.58rem;
            font-weight: 600;
            border: 1px solid #d1d5db;
            color: #6b7280;
            background: #f3f4f6;
        }

        .dark .crm-owner-avatar {
            border-color: var(--t-border);
            color: #c9d1d9;
            background: var(--t-surface-2);
        }

        .crm-opp-score {
            display: flex;
            gap: 0.1rem;
            align-items: center;
        }

        .crm-modal {
            position: relative;
            z-index: 50;
            width: 100%;
            max-width: 420px;
            border: 1px solid #e5e7eb;
            background: #fff;
            padding: 1rem;
        }

        .dark .crm-modal {
            border-color: var(--t-border);
            background: var(--t-surface);
        }

        .crm-modal-title {
            margin: 0;
            font-family: var(--t-font-display);
            font-size: 0.66rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: #111827;
        }

        .dark .crm-modal-title {
            color: #e6edf3;
        }

        .crm-modal-actions {
            margin-top: 1rem;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .crm-btn {
            border: 1px solid #d1d5db;
            background: #fff;
            padding: 0.4rem 0.7rem;
            font-family: var(--t-font-display);
            font-size: 0.55rem;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #374151;
        }

        .dark .crm-btn {
            border-color: var(--t-border);
            background: var(--t-surface-2);
            color: #d1d5db;
        }

        .crm-btn--danger {
            border-color: var(--t-red-dim);
            color: var(--t-red);
        }

        .dark .crm-btn--danger {
            background: rgba(255, 23, 68, 0.08);
        }
    </style>
</x-terminal-page>
