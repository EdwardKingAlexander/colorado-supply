<x-filament-panels::page>
    @php
        $documents = $this->getDocuments();
        $stats = $this->getDocumentStats();
    @endphp

    <div class="contract-docs-page">
        {{-- Summary Statistics --}}
        <div class="mb-8">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                {{-- Total --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Total Documents</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</p>
                </div>

                {{-- Pending --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Pending</p>
                    <p class="text-2xl font-semibold text-amber-600 dark:text-amber-500">{{ number_format($stats['pending']) }}</p>
                </div>

                {{-- Processing --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Processing</p>
                    <p class="text-2xl font-semibold text-blue-600 dark:text-blue-500">{{ number_format($stats['processing'] ?? 0) }}</p>
                </div>

                {{-- Parsed --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Completed</p>
                    <p class="text-2xl font-semibold text-green-600 dark:text-green-500">{{ number_format($stats['parsed']) }}</p>
                </div>

                {{-- Failed --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Failed</p>
                    <p class="text-2xl font-semibold text-red-600 dark:text-red-500">{{ number_format($stats['failed']) }}</p>
                </div>

                {{-- CUI Flagged --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5 {{ $stats['with_cui'] > 0 ? 'border-l-4 border-l-amber-500' : '' }}">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">CUI Flagged</p>
                    <p class="text-2xl font-semibold {{ $stats['with_cui'] > 0 ? 'text-amber-600 dark:text-amber-500' : 'text-gray-400' }}">{{ number_format($stats['with_cui']) }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
            {{-- Upload Section --}}
            <div class="xl:col-span-4">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg sticky top-6">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Upload Documents</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">RFPs, RFQs, IFBs, Amendments, and Attachments</p>
                    </div>

                    <div class="p-6">
                        <div class="upload-zone border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg p-4 mb-6 hover:border-blue-400 dark:hover:border-blue-500 transition-colors">
                            {{ $this->form }}
                        </div>

                        <button
                            wire:click="upload"
                            wire:loading.attr="disabled"
                            class="w-full py-3 px-4 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span wire:loading.remove wire:target="upload">Upload & Process</span>
                            <span wire:loading wire:target="upload">Processing...</span>
                        </button>
                    </div>

                    <div class="px-6 pb-6">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Accepted formats</p>
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400">PDF</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">DOCX</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400">XLSX</span>
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Maximum 50MB per file, up to 10 files</p>
                    </div>
                </div>
            </div>

            {{-- Documents Table --}}
            <div class="xl:col-span-8">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Document Registry</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $documents->total() }} {{ Str::plural('document', $documents->total()) }}</p>
                        </div>
                    </div>

                    @if($documents->count() > 0)
                        <div class="overflow-x-auto">
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

                                            $fileExtension = strtoupper(pathinfo($doc->original_filename, PATHINFO_EXTENSION));
                                        @endphp

                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                            {{-- Document Name --}}
                                            <td class="px-6 py-4">
                                                <div class="flex items-start gap-3">
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
                                                </div>
                                            </td>

                                            {{-- Document Type --}}
                                            <td class="px-6 py-4 hidden lg:table-cell">
                                                <span class="text-sm text-gray-600 dark:text-gray-300">
                                                    {{ $doc->document_type ? (\App\Models\ContractDocument::getTypeOptions()[$doc->document_type] ?? $doc->document_type) : '—' }}
                                                </span>
                                            </td>

                                            {{-- File Size --}}
                                            <td class="px-6 py-4 hidden md:table-cell">
                                                <span class="text-sm text-gray-600 dark:text-gray-300 tabular-nums">{{ $doc->formatted_file_size }}</span>
                                            </td>

                                            {{-- Status --}}
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium {{ $statusConfig['class'] }}">
                                                    {{ $statusConfig['label'] }}
                                                </span>
                                            </td>

                                            {{-- Uploaded Date --}}
                                            <td class="px-6 py-4 hidden sm:table-cell">
                                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                                    {{ $doc->uploaded_at?->format('M j, Y') }}
                                                </div>
                                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                                    {{ $doc->uploaded_at?->format('g:i A') }}
                                                </div>
                                            </td>

                                            {{-- Actions --}}
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

                        {{-- Pagination --}}
                        <div class="p-6 border-t border-gray-200 dark:border-gray-800">
                            {{ $documents->links() }}
                        </div>
                    @else
                        {{-- Empty State --}}
                        <div class="p-12 text-center">
                            <h3 class="text-base font-medium text-gray-900 dark:text-white mb-1">No documents uploaded</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                                Upload your first contract document to begin automated parsing and analysis.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        .contract-docs-page .upload-zone .fi-fo-file-upload {
            border: none !important;
            background: transparent !important;
            padding: 0 !important;
        }

        .contract-docs-page .upload-zone .filepond--root {
            margin-bottom: 0;
        }

        .contract-docs-page .upload-zone .filepond--panel-root {
            background-color: transparent;
        }
    </style>
</x-filament-panels::page>
