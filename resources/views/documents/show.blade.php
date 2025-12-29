<x-app-layout>
    <x-slot name="header">Document: {{ $document->title }}</x-slot>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Document Details</h3>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Title</dt>
                        <dd class="col-sm-8">{{ $document->title }}</dd>

                        <dt class="col-sm-4">Original Filename</dt>
                        <dd class="col-sm-8">{{ $document->original_filename }}</dd>

                        <dt class="col-sm-4">Category</dt>
                        <dd class="col-sm-8">
                            <span class="badge badge-secondary">{{ $document->category_label }}</span>
                        </dd>

                        <dt class="col-sm-4">File Size</dt>
                        <dd class="col-sm-8">{{ $document->human_file_size }}</dd>

                        <dt class="col-sm-4">Uploaded By</dt>
                        <dd class="col-sm-8">{{ $document->uploader?->name ?? 'Unknown' }}</dd>

                        <dt class="col-sm-4">Upload Date</dt>
                        <dd class="col-sm-8">{{ $document->created_at->format('M d, Y h:i A') }}</dd>
                    </dl>

                    @if($document->description)
                    <hr>
                    <h5>Description</h5>
                    <p>{{ $document->description }}</p>
                    @endif
                </div>
                <div class="card-footer">
                    <a href="{{ route('documents.download', $document) }}" class="btn btn-primary">
                        <i class="fas fa-download mr-1"></i> Download
                    </a>
                    <a href="{{ route('documents.ai', $document) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-robot mr-1"></i> AI Analysis
                    </a>
                    <a href="{{ route('documents.chat', $document) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-comments mr-1"></i> Chat
                    </a>
                    <a href="{{ route('documents.summarize', $document) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-align-left mr-1"></i> Summarize
                    </a>
                    @if($document->isPdf() || $document->isImage())
                    <a href="{{ route('documents.view', $document) }}" class="btn btn-outline-primary" target="_blank">
                        <i class="fas fa-eye mr-1"></i> View in Browser
                    </a>
                    @endif
                </div>
            </div>

            <!-- Preview for images -->
            @if($document->isImage())
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Preview</h3>
                </div>
                <div class="card-body text-center">
                    <img src="{{ route('documents.view', $document) }}" alt="{{ $document->title }}" class="img-fluid" style="max-height: 500px;">
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Actions</h3>
                </div>
                <div class="card-body">
                    <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Documents
                    </a>
                    @php
                        $canDelete = app(\App\Services\Documents\DocumentAccessService::class)->canDelete(auth()->user(), $document);
                    @endphp
                    @if($canDelete)
                    <form action="{{ route('documents.destroy', $document) }}" method="POST" class="mt-2" onsubmit="return confirm('Are you sure you want to delete this document? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-block">
                            <i class="fas fa-trash mr-1"></i> Delete Document
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">File Info</h3>
                </div>
                <div class="card-body text-center">
                    <i class="{{ $document->icon_class }} fa-5x mb-3"></i>
                    <h5>{{ strtoupper($document->extension) }} File</h5>
                    <p class="text-muted mb-0">{{ $document->human_file_size }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
