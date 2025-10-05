<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filters --}}
        <form wire:submit="generateReport">
            {{ $this->form }}

            <div class="mt-4 flex gap-4">
                <x-filament::button type="submit">
                    Generate Report
                </x-filament::button>

                <x-filament::button color="gray" wire:click="exportCsv">
                    Export CSV
                </x-filament::button>
            </div>
        </form>

        {{-- Key Metrics --}}
        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Opportunities</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ $data['metrics']['total_opportunities'] ?? 0 }}
                </div>
                <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    ${{ number_format($data['metrics']['total_value'] ?? 0, 0) }}
                </div>
            </div>

            <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Won Deals</div>
                <div class="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400">
                    {{ $data['metrics']['won_count'] ?? 0 }}
                </div>
                <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    ${{ number_format($data['metrics']['won_value'] ?? 0, 0) }}
                </div>
            </div>

            <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Win Rate</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ number_format($data['metrics']['win_rate'] ?? 0, 1) }}%
                </div>
            </div>

            <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Avg Sales Cycle</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ number_format($data['metrics']['avg_sales_cycle'] ?? 0, 0) }}
                </div>
                <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">days</div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Value by Stage --}}
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Value by Stage</h3>
                <div class="mt-4 space-y-2">
                    @foreach($data['value_by_stage'] ?? [] as $stage)
                        <div class="border-b pb-2 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ $stage['stage_name'] }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $stage['count'] }} opportunities
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        ${{ number_format($stage['total_value'], 0) }}
                                    </div>
                                    <div class="text-sm text-primary-600 dark:text-primary-400">
                                        Forecast: ${{ number_format($stage['forecast_value'], 0) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Forecast by Month --}}
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Forecast by Month</h3>
                <div class="mt-4 space-y-2">
                    @foreach($data['forecast_by_month'] ?? [] as $month)
                        <div class="border-b pb-2 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ $month['month'] }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $month['count'] }} opportunities
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        ${{ number_format($month['value'], 0) }}
                                    </div>
                                    <div class="text-sm text-primary-600 dark:text-primary-400">
                                        Forecast: ${{ number_format($month['forecast'], 0) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Conversion Rates --}}
        @if(count($data['conversion_rates'] ?? []) > 0)
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Stage Conversion Rates</h3>
                <div class="mt-4 space-y-3">
                    @foreach($data['conversion_rates'] ?? [] as $conversion)
                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $conversion['from_stage'] }} → {{ $conversion['to_stage'] }}
                                </div>
                            </div>
                            <div class="w-32">
                                <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div class="h-2 rounded-full bg-primary-600"
                                        style="width: {{ $conversion['conversion_rate'] }}%"></div>
                                </div>
                            </div>
                            <div class="w-16 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $conversion['conversion_rate'] }}%
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Owner Leaderboard --}}
        <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Owner Leaderboard</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Owner
                            </th>
                            <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Total Opps
                            </th>
                            <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Won
                            </th>
                            <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Won Value
                            </th>
                            <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Open Value
                            </th>
                            <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Win Rate
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        @foreach($data['owner_leaderboard'] ?? [] as $owner)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $owner['owner_name'] }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-400">
                                    {{ $owner['total_opportunities'] }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-400">
                                    {{ $owner['won_count'] }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-green-600 dark:text-green-400">
                                    ${{ number_format($owner['won_value'], 0) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-400">
                                    ${{ number_format($owner['open_value'], 0) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-900 dark:text-white">
                                    {{ number_format($owner['win_rate'], 1) }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
