<x-terminal-page
    footer-left="CRM // FORECAST REPORT"
    footer-center="SESSION {{ strtoupper(substr(md5(session()->getId()), 0, 8)) }}"
    footer-right="OPERATOR {{ auth()->user()?->name ?? 'UNKNOWN' }}"
>
    <x-slot:banner>
        DEFENSE LOGISTICS INTELLIGENCE SYSTEM <span class="t-sep">//</span> PIPELINE FORECAST REPORT
    </x-slot:banner>

    <div class="crm-report-shell">
        <div class="t-panel t-scanlines t-glow-hover" style="animation-delay: 0.05s">
            <div class="t-panel-corner t-panel-corner--tl"></div>
            <div class="t-panel-corner t-panel-corner--tr"></div>
            <div class="t-panel-corner t-panel-corner--bl"></div>
            <div class="t-panel-corner t-panel-corner--br"></div>

            <div class="t-panel-header">
                <div class="t-panel-header-icon">
                    <x-heroicon-o-presentation-chart-line class="w-5 h-5" />
                </div>
                <div>
                    <h2 class="t-panel-title">REPORT CONTROLS</h2>
                    <p class="t-panel-subtitle">Set report filters and generate current pipeline analytics snapshots.</p>
                </div>
            </div>

            <div class="t-divider"></div>

            <form wire:submit="generateReport" class="crm-report-form">
                {{ $this->form }}

                <div class="crm-report-actions">
                    <x-filament::button type="submit">
                        Generate Report
                    </x-filament::button>

                    <x-filament::button color="gray" wire:click="exportCsv">
                        Export CSV
                    </x-filament::button>
                </div>
            </form>
        </div>

        <div class="crm-report-stats">
            <div class="t-stat t-stat--accent">
                <div class="t-stat-value">{{ $data['metrics']['total_opportunities'] ?? 0 }}</div>
                <div class="t-stat-label">TOTAL OPPORTUNITIES</div>
                <div class="crm-stat-sub">${{ number_format($data['metrics']['total_value'] ?? 0, 0) }}</div>
            </div>

            <div class="t-stat t-stat--success">
                <div class="t-stat-value">{{ $data['metrics']['won_count'] ?? 0 }}</div>
                <div class="t-stat-label">WON DEALS</div>
                <div class="crm-stat-sub">${{ number_format($data['metrics']['won_value'] ?? 0, 0) }}</div>
            </div>

            <div class="t-stat {{ ($data['metrics']['win_rate'] ?? 0) >= 50 ? 't-stat--success' : 't-stat--warning' }}">
                <div class="t-stat-value">{{ number_format($data['metrics']['win_rate'] ?? 0, 1) }}%</div>
                <div class="t-stat-label">WIN RATE</div>
            </div>

            <div class="t-stat">
                <div class="t-stat-value">{{ number_format($data['metrics']['avg_sales_cycle'] ?? 0, 0) }}</div>
                <div class="t-stat-label">AVG SALES CYCLE</div>
                <div class="crm-stat-sub">days</div>
            </div>
        </div>

        <div class="crm-report-grid">
            <div class="t-card t-glow-hover">
                <div class="t-card-header">
                    <h3 class="t-card-title">VALUE BY STAGE</h3>
                </div>

                @forelse($data['value_by_stage'] ?? [] as $stage)
                    <div class="t-row">
                        <div style="min-width: 0; flex: 1;">
                            <div class="crm-row-title">{{ $stage['stage_name'] }}</div>
                            <div class="crm-row-meta">{{ $stage['count'] }} opportunities</div>
                        </div>
                        <div style="text-align: right; flex-shrink: 0;">
                            <div class="crm-row-title">${{ number_format($stage['total_value'], 0) }}</div>
                            <div class="crm-row-forecast">Forecast: ${{ number_format($stage['forecast_value'], 0) }}</div>
                        </div>
                    </div>
                @empty
                    <div class="t-empty">
                        <div class="t-empty-title">NO STAGE DATA</div>
                        <div class="t-empty-text">Generate report data to view stage values.</div>
                    </div>
                @endforelse
            </div>

            <div class="t-card t-glow-hover">
                <div class="t-card-header">
                    <h3 class="t-card-title">FORECAST BY MONTH</h3>
                </div>

                @forelse($data['forecast_by_month'] ?? [] as $month)
                    <div class="t-row">
                        <div style="min-width: 0; flex: 1;">
                            <div class="crm-row-title">{{ $month['month'] }}</div>
                            <div class="crm-row-meta">{{ $month['count'] }} opportunities</div>
                        </div>
                        <div style="text-align: right; flex-shrink: 0;">
                            <div class="crm-row-title">${{ number_format($month['value'], 0) }}</div>
                            <div class="crm-row-forecast">Forecast: ${{ number_format($month['forecast'], 0) }}</div>
                        </div>
                    </div>
                @empty
                    <div class="t-empty">
                        <div class="t-empty-title">NO MONTHLY DATA</div>
                        <div class="t-empty-text">Generate report data to view forecast values.</div>
                    </div>
                @endforelse
            </div>
        </div>

        @if(count($data['conversion_rates'] ?? []) > 0)
            <div class="t-panel" style="animation-delay: 0.1s">
                <div class="t-panel-corner t-panel-corner--tl"></div>
                <div class="t-panel-corner t-panel-corner--tr"></div>
                <div class="t-panel-corner t-panel-corner--bl"></div>
                <div class="t-panel-corner t-panel-corner--br"></div>

                <div class="t-panel-header">
                    <div class="t-panel-header-icon">
                        <x-heroicon-o-arrow-trending-up class="w-5 h-5" />
                    </div>
                    <div>
                        <h2 class="t-panel-title">STAGE CONVERSION RATES</h2>
                        <p class="t-panel-subtitle">Transition efficiency across adjacent pipeline stages.</p>
                    </div>
                </div>

                <div class="t-divider"></div>

                <div class="crm-conversion-list">
                    @foreach($data['conversion_rates'] ?? [] as $conversion)
                        <div class="crm-conversion-row">
                            <div class="crm-conversion-label">{{ $conversion['from_stage'] }} -> {{ $conversion['to_stage'] }}</div>
                            <div class="crm-conversion-bar-wrap">
                                <div class="crm-conversion-bar" style="width: {{ $conversion['conversion_rate'] }}%"></div>
                            </div>
                            <div class="crm-conversion-value">{{ $conversion['conversion_rate'] }}%</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="t-panel t-scanlines" style="animation-delay: 0.15s">
            <div class="t-panel-corner t-panel-corner--tl"></div>
            <div class="t-panel-corner t-panel-corner--tr"></div>
            <div class="t-panel-corner t-panel-corner--bl"></div>
            <div class="t-panel-corner t-panel-corner--br"></div>

            <div class="t-panel-header">
                <div class="t-panel-header-icon">
                    <x-heroicon-o-users class="w-5 h-5" />
                </div>
                <div>
                    <h2 class="t-panel-title">OWNER LEADERBOARD</h2>
                    <p class="t-panel-subtitle">Performance summary by owner for won and open pipeline value.</p>
                </div>
            </div>

            <div class="t-divider"></div>

            <div class="crm-table-wrap">
                <table class="crm-table">
                    <thead>
                        <tr>
                            <th>Owner</th>
                            <th class="text-right">Total Opps</th>
                            <th class="text-right">Won</th>
                            <th class="text-right">Won Value</th>
                            <th class="text-right">Open Value</th>
                            <th class="text-right">Win Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['owner_leaderboard'] ?? [] as $owner)
                            <tr>
                                <td>{{ $owner['owner_name'] }}</td>
                                <td class="text-right">{{ $owner['total_opportunities'] }}</td>
                                <td class="text-right">{{ $owner['won_count'] }}</td>
                                <td class="text-right crm-money-good">${{ number_format($owner['won_value'], 0) }}</td>
                                <td class="text-right">${{ number_format($owner['open_value'], 0) }}</td>
                                <td class="text-right">{{ number_format($owner['win_rate'], 1) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="crm-empty-cell">No leaderboard data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        .crm-report-shell {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .crm-report-form {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }

        .crm-report-actions {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
        }

        .crm-report-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
        }

        .crm-report-stats .t-stat {
            flex: 1;
            min-width: 180px;
        }

        .crm-stat-sub {
            margin-top: 0.2rem;
            font-size: 0.66rem;
            color: #9ca3af;
            font-family: var(--t-font-mono);
        }

        .dark .crm-stat-sub {
            color: var(--t-text-dim);
        }

        .crm-report-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
        }

        @media (min-width: 1024px) {
            .crm-report-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .crm-row-title {
            font-size: 0.76rem;
            font-weight: 600;
            color: #111827;
        }

        .dark .crm-row-title {
            color: #e6edf3;
        }

        .crm-row-meta {
            margin-top: 0.18rem;
            font-size: 0.66rem;
            color: #9ca3af;
        }

        .dark .crm-row-meta {
            color: var(--t-text-dim);
        }

        .crm-row-forecast {
            margin-top: 0.18rem;
            font-size: 0.66rem;
            color: var(--t-accent-light);
            font-family: var(--t-font-mono);
        }

        .dark .crm-row-forecast {
            color: var(--t-cyan);
        }

        .crm-conversion-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .crm-conversion-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 280px) auto;
            align-items: center;
            gap: 0.8rem;
        }

        .crm-conversion-label {
            font-size: 0.7rem;
            color: #374151;
        }

        .dark .crm-conversion-label {
            color: #d1d5db;
        }

        .crm-conversion-bar-wrap {
            height: 0.5rem;
            width: 100%;
            border: 1px solid #e5e7eb;
            background: #f3f4f6;
        }

        .dark .crm-conversion-bar-wrap {
            border-color: var(--t-border);
            background: #111827;
        }

        .crm-conversion-bar {
            height: 100%;
            background: var(--t-accent-light);
        }

        .dark .crm-conversion-bar {
            background: var(--t-cyan);
            box-shadow: 0 0 12px var(--t-cyan-dim);
        }

        .crm-conversion-value {
            font-family: var(--t-font-mono);
            font-size: 0.68rem;
            color: #111827;
        }

        .dark .crm-conversion-value {
            color: #e6edf3;
        }

        .crm-table-wrap {
            overflow-x: auto;
        }

        .crm-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
        }

        .crm-table th {
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            padding: 0.65rem 0.75rem;
            font-family: var(--t-font-display);
            font-size: 0.54rem;
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #9ca3af;
        }

        .dark .crm-table th {
            border-color: var(--t-border);
            color: var(--t-text-dim);
        }

        .crm-table td {
            border-bottom: 1px solid #f3f4f6;
            padding: 0.65rem 0.75rem;
            font-size: 0.75rem;
            color: #374151;
        }

        .dark .crm-table td {
            border-color: #1a2332;
            color: #d1d5db;
        }

        .crm-table tbody tr:hover {
            background: #f9fafb;
        }

        .dark .crm-table tbody tr:hover {
            background: var(--t-surface-2);
        }

        .crm-money-good {
            color: var(--t-green) !important;
            font-family: var(--t-font-mono);
        }

        .crm-empty-cell {
            text-align: center;
            color: #9ca3af;
            padding: 1rem 0.75rem !important;
        }

        .dark .crm-empty-cell {
            color: var(--t-text-dim);
        }

        @media (max-width: 768px) {
            .crm-conversion-row {
                grid-template-columns: 1fr;
                gap: 0.4rem;
            }

            .crm-conversion-value {
                justify-self: end;
            }
        }
    </style>
</x-terminal-page>
