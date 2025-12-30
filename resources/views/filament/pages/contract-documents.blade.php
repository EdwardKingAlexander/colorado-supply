<x-filament-panels::page>
    @php
        $documents = $this->getDocuments();
        $stats = $this->getDocumentStats();
    @endphp

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Upload Section (Left) --}}
        <div class="lg:col-span-2">
            <x-filament::section>
                <x-slot name="heading">
                    Upload Documents
                </x-slot>

                <x-slot name="description">
                    Upload RFPs, RFQs, IFBs, amendments, and attachments for parsing and analysis
                </x-slot>

                <div class="space-y-6">
                    {{ $this->form }}

                    <div class="flex justify-end pt-4 border-t dark:border-gray-700">
                        <x-filament::button
                            wire:click="upload"
                            wire:loading.attr="disabled"
                            icon="heroicon-o-cloud-arrow-up"
                        >
                            <span wire:loading.remove wire:target="upload">Upload Documents</span>
                            <span wire:loading wire:target="upload">Uploading...</span>
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>

            {{-- Documents List --}}
            <x-filament::section class="mt-6">
                <x-slot name="heading">
                    Recent Documents
                </x-slot>

                <x-slot name="description">
                    {{ $documents->total() }} document(s) uploaded
                </x-slot>

                @if($documents->count() > 0)
                    <div class="overflow-hidden border rounded-lg dark:border-gray-700">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Document</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Type</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Status</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Size</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Uploaded</th>
                                    <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($documents as $doc)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                @php
                                                    $iconClass = match(true) {
                                                        str_contains($doc->mime_type, 'pdf') => 'text-red-500',
                                                        str_contains($doc->mime_type, 'word') => 'text-blue-500',
                                                        str_contains($doc->mime_type, 'sheet') || str_contains($doc->mime_type, 'excel') => 'text-green-500',
                                                        default => 'text-gray-500',
                                                    };
                                                @endphp
                                                <svg class="w-8 h-8 {{ $iconClass }}" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/>
                                                </svg>
                                                <div>
                                                    <p class="font-medium text-gray-900 dark:text-gray-100 truncate max-w-xs" title="{{ $doc->original_filename }}">
                                                        {{ Str::limit($doc->original_filename, 40) }}
                                                    </p>
                                                    @if($doc->opportunity)
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                                            {{ Str::limit($doc->opportunity->title, 35) }}
                                                        </p>
                                                    @endif
                                                    @if($doc->cui_detected)
                                                        <span class="inline-flex items-center px-2 py-0.5 mt-1 text-xs font-medium rounded bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                                            CUI Detected
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($doc->document_type)
                                                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                    {{ \App\Models\ContractDocument::getTypeOptions()[$doc->document_type] ?? $doc->document_type }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @php
                                                $statusColors = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                    'processing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                                    'parsed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                    'failed' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                    'archived' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                                ];
                                            @endphp
                                            <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full {{ $statusColors[$doc->status] ?? $statusColors['pending'] }}">
                                                {{ ucfirst($doc->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                            {{ $doc->formatted_file_size }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                            <div>{{ $doc->uploaded_at?->format('M j, Y') }}</div>
                                            <div class="text-xs text-gray-400">{{ $doc->uploaded_at?->format('g:i A') }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                @if($doc->status === 'failed')
                                                    <button
                                                        wire:click="parseDocument({{ $doc->id }})"
                                                        wire:loading.attr="disabled"
                                                        class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-orange-700 bg-orange-100 rounded hover:bg-orange-200 dark:bg-orange-900/30 dark:text-orange-400 dark:hover:bg-orange-900/50"
                                                        title="Retry parsing"
                                                    >
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                        </svg>
                                                        Retry
                                                    </button>
                                                @elseif($doc->status === 'parsed')
                                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs text-green-600 dark:text-green-400">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                        Done
                                                    </span>
                                                @elseif($doc->status === 'processing')
                                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs text-blue-600 dark:text-blue-400">
                                                        <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Parsing...
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs text-gray-500 dark:text-gray-400">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        Queued
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $documents->links() }}
                    </div>
                @else
                    <div class="py-8 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                            No documents uploaded yet
                        </p>
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                            Upload your first document using the form above
                        </p>
                    </div>
                @endif
            </x-filament::section>
        </div>

        {{-- Status Panel (Right) --}}
        <div class="lg:col-span-1">
            {{-- Quick Stats --}}
            <x-filament::section class="mb-6">
                <x-slot name="heading">
                    Statistics
                </x-slot>

                <div class="grid grid-cols-2 gap-4">
                    <div class="p-3 text-center rounded-lg bg-gray-50 dark:bg-gray-800">
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['total'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total</p>
                    </div>
                    <div class="p-3 text-center rounded-lg bg-yellow-50 dark:bg-yellow-900/20">
                        <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-400">{{ $stats['pending'] + ($stats['processing'] ?? 0) }}</p>
                        <p class="text-xs text-yellow-600 dark:text-yellow-500">Pending/Processing</p>
                    </div>
                    <div class="p-3 text-center rounded-lg bg-green-50 dark:bg-green-900/20">
                        <p class="text-2xl font-bold text-green-700 dark:text-green-400">{{ $stats['parsed'] }}</p>
                        <p class="text-xs text-green-600 dark:text-green-500">Parsed</p>
                    </div>
                    <div class="p-3 text-center rounded-lg bg-red-50 dark:bg-red-900/20">
                        <p class="text-2xl font-bold text-red-700 dark:text-red-400">{{ $stats['failed'] }}</p>
                        <p class="text-xs text-red-600 dark:text-red-500">Failed</p>
                    </div>
                </div>

                @if($stats['with_cui'] > 0)
                    <div class="mt-4 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-amber-800 dark:text-amber-200">{{ $stats['with_cui'] }} document(s) with CUI</p>
                                <p class="text-xs text-amber-600 dark:text-amber-400">Restricted access may apply</p>
                            </div>
                        </div>
                    </div>
                @endif
            </x-filament::section>

            {{-- Phase Status --}}
            <x-filament::section class="mb-6">
                <x-slot name="heading">
                    Implementation Status
                </x-slot>

                <div class="space-y-3">
                    <div class="flex items-center gap-3 p-3 rounded-lg bg-green-50 dark:bg-green-900/20">
                        <svg class="flex-shrink-0 w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-green-800 dark:text-green-200">Phase 1: Foundation</p>
                            <p class="text-xs text-green-600 dark:text-green-400">Complete</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                        <div class="flex-shrink-0 w-2 h-2 rounded-full bg-blue-500 animate-pulse"></div>
                        <div>
                            <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Phase 2: Parsing</p>
                            <p class="text-xs text-blue-600 dark:text-blue-400">In Progress</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <div class="flex-shrink-0 w-2 h-2 rounded-full bg-gray-400"></div>
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Phase 3: Extraction</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Not Started</p>
                        </div>
                    </div>
                </div>
            </x-filament::section>

            {{-- Supported Formats --}}
            <x-filament::section>
                <x-slot name="heading">
                    Supported Formats
                </x-slot>

                <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-2 p-2 rounded bg-gray-50 dark:bg-gray-800">
                        <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/>
                        </svg>
                        <span class="text-gray-700 dark:text-gray-300">PDF Documents</span>
                        <span class="ml-auto text-xs text-gray-500">Native & Scanned</span>
                    </div>
                    <div class="flex items-center gap-2 p-2 rounded bg-gray-50 dark:bg-gray-800">
                        <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/>
                        </svg>
                        <span class="text-gray-700 dark:text-gray-300">Word Documents</span>
                        <span class="ml-auto text-xs text-gray-500">.docx, .doc</span>
                    </div>
                    <div class="flex items-center gap-2 p-2 rounded bg-gray-50 dark:bg-gray-800">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/>
                        </svg>
                        <span class="text-gray-700 dark:text-gray-300">Excel Spreadsheets</span>
                        <span class="ml-auto text-xs text-gray-500">.xlsx, .xls</span>
                    </div>
                </div>

                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    Maximum file size: 50MB per file
                </p>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
