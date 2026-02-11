@php
    $documents = $this->getDocuments();
    $stats = $this->getDocumentStats();
@endphp

<x-terminal-page
    footer-left="SAM AUTOMATION // CONTRACT DOCUMENTS"
    footer-center="SESSION {{ strtoupper(substr(md5(session()->getId()), 0, 8)) }}"
    footer-right="OPERATOR {{ auth()->user()?->name ?? 'UNKNOWN' }}"
>
    <x-slot:banner>
        DEFENSE LOGISTICS INTELLIGENCE SYSTEM <span class="t-sep">//</span> CONTRACT DOCUMENTS REGISTRY
    </x-slot:banner>

    <div class="contract-docs-shell">
        <div class="contract-docs-stats">
            <div class="t-stat t-stat--accent">
                <div class="t-stat-value">{{ number_format($stats['total']) }}</div>
                <div class="t-stat-label">TOTAL DOCUMENTS</div>
            </div>
            <div class="t-stat {{ $stats['pending'] > 0 ? 't-stat--warning' : '' }}">
                <div class="t-stat-value">{{ number_format($stats['pending']) }}</div>
                <div class="t-stat-label">PENDING</div>
            </div>
            <div class="t-stat {{ ($stats['processing'] ?? 0) > 0 ? 't-stat--warning' : '' }}">
                <div class="t-stat-value">{{ number_format($stats['processing'] ?? 0) }}</div>
                <div class="t-stat-label">PROCESSING</div>
            </div>
            <div class="t-stat t-stat--success">
                <div class="t-stat-value">{{ number_format($stats['parsed']) }}</div>
                <div class="t-stat-label">COMPLETED</div>
            </div>
            <div class="t-stat {{ $stats['failed'] > 0 ? 't-stat--danger' : '' }}">
                <div class="t-stat-value">{{ number_format($stats['failed']) }}</div>
                <div class="t-stat-label">FAILED</div>
            </div>
            <div class="t-stat {{ $stats['with_cui'] > 0 ? 't-stat--warning' : '' }}">
                <div class="t-stat-value">{{ number_format($stats['with_cui']) }}</div>
                <div class="t-stat-label">CUI FLAGGED</div>
            </div>
        </div>

        <div class="contract-docs-main-grid">
            <div class="t-panel t-scanlines" style="animation-delay: 0.1s">
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
                        <p class="t-panel-subtitle">{{ $documents->total() }} {{ Str::plural('document', $documents->total()) }} tracked for parsing and compliance review.</p>
                    </div>
                </div>

                <div class="t-divider"></div>

                @if($documents->count() > 0)
                    <div class="contract-docs-table-wrap">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-800">
                                    <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide px-6 py-3">Document</th>
                                    <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide px-6 py-3 hidden lg:table-cell">Type</th>
                                    <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide px-6 py-3 hidden md:table-cell">Size</th>
                                    <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide px-6 py-3">Status</th>
                                    <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide px-6 py-3 hidden sm:table-cell">Uploaded</th>
                                    <th class="text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide px-6 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($documents as $doc)
                                    @php
                                        $statusConfig = match($doc->status) {
                                            'pending' => ['class' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', 'label' => 'Pending'],
                                            'processing' => ['class' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', 'label' => 'Processing'],
                                            'parsed' => ['class' => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400', 'label' => 'Complete'],
                                            'failed' => ['class' => 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400', 'label' => 'Failed'],
                                            default => ['class' => 'bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-400', 'label' => 'Unknown'],
                                        };
                                    @endphp

                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate" title="{{ $doc->original_filename }}">
                                                    {{ Str::limit($doc->original_filename, 40) }}
                                                </p>
                                                @if($doc->opportunity)
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">
                                                        {{ Str::limit($doc->opportunity->title, 35) }}
                                                    </p>
                                                @endif
                                                @if($doc->cui_detected)
                                                    <span class="inline-flex items-center mt-1.5 px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                                                        CUI
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 hidden lg:table-cell">
                                            <span class="text-sm text-gray-600 dark:text-gray-300">
                                                {{ $doc->document_type ? (\App\Models\ContractDocument::getTypeOptions()[$doc->document_type] ?? $doc->document_type) : '--' }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 hidden md:table-cell">
                                            <span class="text-sm text-gray-600 dark:text-gray-300 tabular-nums">{{ $doc->formatted_file_size }}</span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium {{ $statusConfig['class'] }}">
                                                {{ $statusConfig['label'] }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 hidden sm:table-cell">
                                            <div class="text-sm text-gray-600 dark:text-gray-300">
                                                {{ $doc->uploaded_at?->format('M j, Y') }}
                                            </div>
                                            <div class="text-xs text-gray-400 dark:text-gray-500">
                                                {{ $doc->uploaded_at?->format('g:i A') }}
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 text-right">
                                            @if($doc->status === 'failed')
                                                <button
                                                    wire:click="parseDocument({{ $doc->id }})"
                                                    wire:loading.attr="disabled"
                                                    class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-500 dark:hover:text-blue-400"
                                                >
                                                    Retry
                                                </button>
                                            @elseif($doc->status === 'processing')
                                                <span class="text-sm text-gray-400 dark:text-gray-500">Processing...</span>
                                            @elseif($doc->status === 'parsed')
                                                <span class="text-sm text-green-600 dark:text-green-500">Ready</span>
                                            @else
                                                <span class="text-sm text-gray-400 dark:text-gray-500">Queued</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-800">
                        {{ $documents->links() }}
                    </div>
                @else
                    <div class="t-empty">
                        <div class="t-empty-title">NO DOCUMENTS UPLOADED</div>
                        <div class="t-empty-text">Upload your first contract document to begin automated parsing and analysis.</div>
                    </div>
                @endif
            </div>

            <div class="contract-docs-support-stack">
                <div class="t-panel t-glow-hover" style="animation-delay: 0.2s">
                    <div class="t-panel-corner t-panel-corner--tl"></div>
                    <div class="t-panel-corner t-panel-corner--tr"></div>
                    <div class="t-panel-corner t-panel-corner--bl"></div>
                    <div class="t-panel-corner t-panel-corner--br"></div>

                    <div class="t-panel-header">
                        <div class="t-panel-header-icon">
                            <x-heroicon-o-arrow-up-tray class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="t-panel-title">UPLOAD DOCUMENTS</h2>
                            <p class="t-panel-subtitle">RFPs, RFQs, IFBs, amendments, and attachments.</p>
                        </div>
                    </div>

                    <div class="t-divider"></div>

                    <div class="upload-zone border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg p-4 mb-6 hover:border-blue-400 dark:hover:border-blue-500 transition-colors">
                        {{ $this->form }}
                    </div>

                    <button
                        wire:click="upload"
                        wire:loading.attr="disabled"
                        class="w-full py-3 px-4 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span wire:loading.remove wire:target="upload">Upload &amp; Process</span>
                        <span wire:loading wire:target="upload">Processing...</span>
                    </button>

                    <div class="mt-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Accepted formats</p>
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400">PDF</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">DOCX</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400">XLSX</span>
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Maximum 50MB per file, up to 10 files.</p>
                    </div>
                </div>

                <div class="t-card t-glow-hover">
                    <div class="t-card-header">
                        <h2 class="t-card-title">AUTOMATION LINKS</h2>
                    </div>
                    <a href="{{ \App\Filament\Pages\FetchSamControlPanel::getUrl() }}" class="t-row">
                        <div>
                            <div class="contract-docs-link-title">SAM Control Panel</div>
                            <div class="contract-docs-link-meta">Fetch SAM opportunities and refresh the registry source feed.</div>
                        </div>
                        <span class="contract-docs-link-cta">OPEN</span>
                    </a>
                    <a href="{{ \App\Filament\Pages\SamInsightsDashboard::getUrl() }}" class="t-row">
                        <div>
                            <div class="contract-docs-link-title">SAM Insights</div>
                            <div class="contract-docs-link-meta">Review ingestion, parse, and embedding readiness in one place.</div>
                        </div>
                        <span class="contract-docs-link-cta">OPEN</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <style>
        .contract-docs-shell {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .contract-docs-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
        }

        .contract-docs-stats .t-stat {
            flex: 1;
            min-width: 135px;
        }

        .contract-docs-main-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1.25rem;
        }

        .contract-docs-support-stack {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .contract-docs-table-wrap {
            overflow-x: auto;
            border: 1px solid #e5e7eb;
        }

        .dark .contract-docs-table-wrap {
            border-color: var(--t-border);
        }

        .contract-docs-link-title {
            font-family: var(--t-font-display);
            font-size: 0.64rem;
            letter-spacing: 0.12em;
            color: #0f172a;
        }

        .dark .contract-docs-link-title {
            color: var(--t-cyan);
        }

        .contract-docs-link-meta {
            margin-top: 0.2rem;
            font-size: 0.72rem;
            color: #64748b;
        }

        .dark .contract-docs-link-meta {
            color: var(--t-text-dim);
        }

        .contract-docs-link-cta {
            font-family: var(--t-font-display);
            font-size: 0.58rem;
            letter-spacing: 0.16em;
            color: var(--t-accent-light);
        }

        .dark .contract-docs-link-cta {
            color: var(--t-cyan);
        }

        .contract-docs-shell .upload-zone .fi-fo-file-upload {
            border: none !important;
            background: transparent !important;
            padding: 0 !important;
        }

        .contract-docs-shell .upload-zone .filepond--root {
            margin-bottom: 0;
        }

        .contract-docs-shell .upload-zone .filepond--panel-root {
            background-color: transparent;
        }
    </style>
</x-terminal-page>
