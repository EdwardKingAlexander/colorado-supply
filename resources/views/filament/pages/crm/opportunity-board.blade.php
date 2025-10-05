<x-filament-panels::page>
    <div class="space-y-6" x-data="opportunityBoard">
        {{-- Top Metrics --}}
        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pipeline</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                    ${{ number_format($metrics['total_pipeline'] ?? 0, 2) }}
                </div>
            </div>

            <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Weighted Forecast</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                    ${{ number_format($metrics['total_forecast'] ?? 0, 2) }}
                </div>
            </div>

            <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Expected This Month</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                    ${{ number_format($metrics['expected_close_this_month'] ?? 0, 2) }}
                </div>
            </div>

            <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Win Rate (90d)</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ number_format($metrics['win_rate'] ?? 0, 1) }}%
                </div>
            </div>
        </div>

        {{-- Filters and Pipeline Selector --}}
        <div class="flex items-center justify-between gap-4 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
            <div class="flex items-center gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Pipeline</label>
                    <select wire:model.live="pipelineId"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                        @foreach($this->getPipelines() as $pipeline)
                            <option value="{{ $pipeline->id }}">{{ $pipeline->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Owner</label>
                    <select wire:model.live="filters.owner_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                        <option value="">All Owners</option>
                        @foreach($this->getOwners() as $owner)
                            <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                    <select wire:model.live="filters.status"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                        <option value="open">Open</option>
                        <option value="won">Won</option>
                        <option value="lost">Lost</option>
                        <option value="">All</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Kanban Board --}}
        <div class="overflow-x-auto pb-4">
            <div class="inline-flex gap-4" style="min-width: min-content;">
                @foreach($stages as $stage)
                    <div class="flex w-80 flex-col rounded-lg bg-gray-50 dark:bg-gray-900"
                        data-stage-id="{{ $stage->id }}"
                        x-init="initSortable($el)">

                        {{-- Column Header --}}
                        <div class="rounded-t-lg bg-gray-100 p-4 dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-gray-900 dark:text-white">
                                    {{ $stage->name }}
                                </h3>
                                @if($stage->is_won)
                                    <x-heroicon-o-check-circle class="h-5 w-5 text-green-500" />
                                @elseif($stage->is_lost)
                                    <x-heroicon-o-x-circle class="h-5 w-5 text-red-500" />
                                @endif
                            </div>
                            <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                <div>{{ $opportunities->get($stage->id)?->count() ?? 0 }} deals</div>
                                <div class="font-medium">
                                    ${{ number_format($opportunities->get($stage->id)?->sum('amount') ?? 0, 0) }}
                                </div>
                                <div class="text-primary-600 dark:text-primary-400">
                                    Forecast: ${{ number_format($opportunities->get($stage->id)?->sum(fn($o) => $o->forecast_amount) ?? 0, 0) }}
                                </div>
                            </div>
                        </div>

                        {{-- Cards Container --}}
                        <div class="flex-1 space-y-2 p-2 sortable-container" style="min-height: 100px;">
                            @foreach($opportunities->get($stage->id) ?? [] as $opportunity)
                                <div class="cursor-move rounded-lg bg-white p-3 shadow-sm transition hover:shadow-md dark:bg-gray-800"
                                    data-opportunity-id="{{ $opportunity->id }}">

                                    <div class="mb-2">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $opportunity->title }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $opportunity->customer->name }}
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div class="text-lg font-semibold text-green-600 dark:text-green-400">
                                            ${{ number_format($opportunity->amount, 0) }}
                                        </div>
                                        <div class="rounded-full bg-primary-100 px-2 py-1 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                                            {{ $opportunity->probability_effective }}%
                                        </div>
                                    </div>

                                    <div class="mt-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                        <div>
                                            @if($opportunity->expected_close_date)
                                                {{ \Carbon\Carbon::parse($opportunity->expected_close_date)->format('M d') }}
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-200 text-xs font-medium dark:bg-gray-700">
                                                {{ substr($opportunity->owner->name, 0, 1) }}
                                            </div>
                                        </div>
                                    </div>

                                    @if($opportunity->score)
                                        <div class="mt-2">
                                            <div class="flex gap-1">
                                                @for($i = 1; $i <= 5; $i++)
                                                    @if($i <= $opportunity->score)
                                                        <x-heroicon-s-star class="h-3 w-3 text-yellow-400" />
                                                    @else
                                                        <x-heroicon-o-star class="h-3 w-3 text-gray-300" />
                                                    @endif
                                                @endfor
                                            </div>
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

    {{-- Lost Reason Modal --}}
    <div x-data="{
        open: false,
        opportunityId: null,
        stageId: null,
        lostReasonId: null
    }"
        x-on:open-lost-reason-modal.window="
            open = true;
            opportunityId = $event.detail.opportunityId;
            stageId = $event.detail.stageId;
        "
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;">

        <div class="flex min-h-screen items-center justify-center px-4">
            <div class="fixed inset-0 bg-black opacity-30" @click="open = false"></div>

            <div class="relative z-50 w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Select Lost Reason</h3>

                <div class="mt-4">
                    <select x-model="lostReasonId"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">-- Select Reason --</option>
                        @foreach(\App\Models\LostReason::where('active', true)->get() as $reason)
                            <option value="{{ $reason->id }}">{{ $reason->label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button @click="open = false"
                        class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancel
                    </button>
                    <button @click="
                        if (lostReasonId) {
                            $wire.markAsLost(opportunityId, lostReasonId);
                            open = false;
                            lostReasonId = null;
                        }
                    "
                        class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                        Mark as Lost
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
</x-filament-panels::page>
