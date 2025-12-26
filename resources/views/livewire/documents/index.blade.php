<div class="space-y-5" x-data="{ uploadOpen: false }">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm text-slate-500">Documents</div>
            <div class="text-xl font-semibold text-slate-900">Your documents</div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="w-full sm:w-64">
                <input
                    wire:model.live.debounce.250ms="search"
                    type="text"
                    placeholder="Search by title…"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900"
                />
            </div>

            <select wire:model.live="category" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900" title="Filter documents by category">
                <option value="all">All categories</option>
                <option value="contract">Contract</option>
                <option value="deliverable">Deliverable</option>
                <option value="report">Report</option>
                <option value="misc">Misc</option>
            </select>

            @if($canUpload)
                <button type="button" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800" x-on:click="uploadOpen = true">
                    Upload
                </button>
            @endif
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Uploaded By</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Actions</th>
                    </tr>
                </thead>
                <!-- Skeleton rows while loading -->
                <tbody wire:loading.delay class="divide-y divide-slate-100">
                    @for($i = 0; $i < 8; $i++)
                        <tr>
                            <td class="px-4 py-4">
                                <div class="h-4 w-64 max-w-[18rem] animate-pulse rounded bg-slate-200"></div>
                                <div class="mt-2 h-3 w-40 animate-pulse rounded bg-slate-200"></div>
                            </td>
                            <td class="px-4 py-4"><div class="h-6 w-24 animate-pulse rounded-full bg-slate-200"></div></td>
                            <td class="px-4 py-4"><div class="h-4 w-32 animate-pulse rounded bg-slate-200"></div></td>
                            <td class="px-4 py-4"><div class="h-4 w-24 animate-pulse rounded bg-slate-200"></div></td>
                            <td class="px-4 py-4"><div class="ml-auto h-8 w-40 animate-pulse rounded bg-slate-200"></div></td>
                        </tr>
                    @endfor
                </tbody>

                <tbody wire:loading.remove class="divide-y divide-slate-100">
                    @forelse($documents as $document)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-sm font-semibold text-slate-900">
                                <a href="{{ route('documents.show', $document) }}" class="hover:underline">
                                    {{ $document->title }}
                                </a>
                                <div class="mt-0.5 text-xs text-slate-500">{{ $document->original_filename }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                    {{ $document->category_label }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ $document->uploader?->name ?? 'Unknown' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ $document->created_at->format('M d, Y') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <div class="flex justify-end gap-2">
                                    @if($document->isPdf() || $document->isImage())
                                        <a href="{{ route('documents.view', $document) }}" target="_blank" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 sm:py-1.5 sm:text-xs">
                                            Preview
                                        </a>
                                    @else
                                        <span class="cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-400 sm:py-1.5 sm:text-xs" title="Preview available for PDFs and images">
                                            Preview
                                        </span>
                                    @endif
                                    <a href="{{ route('documents.download', $document) }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800 sm:py-1.5 sm:text-xs">
                                        Download
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500">
                                No documents found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 bg-white px-4 py-3">
            {{ $documents->links() }}
        </div>
    </div>

    <!-- Upload modal -->
    <div
        x-show="uploadOpen"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
        style="display:none;"
        x-on:click.self="uploadOpen = false"
    >
        <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div class="text-sm font-semibold text-slate-900">Upload document</div>
                <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" x-on:click="uploadOpen = false" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div class="px-5 py-4">
                <livewire:documents.document-upload />
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('document-uploaded', () => {
            // Close modal (Alpine state)
            document.querySelector('[x-data]')?.__x?.$data && (document.querySelector('[x-data]')?.__x?.$data.uploadOpen = false);
        });
    });
</script>
@endpush

