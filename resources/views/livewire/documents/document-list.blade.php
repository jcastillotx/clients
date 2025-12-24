<div>
    <!-- Filters -->
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-md-0">
                        <input type="text" 
                               wire:model.live.debounce.300ms="search" 
                               class="form-control" 
                               placeholder="Search documents...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select wire:model.live="category" class="form-control">
                        <option value="">All Categories</option>
                        @foreach($categories as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    @if($search || $category)
                    <button wire:click="clearFilters" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-times mr-1"></i> Clear
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Document Grid -->
    <div class="row">
        @forelse($documents as $document)
        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="{{ $document->icon_class }} fa-4x mb-3"></i>
                    <h5 class="card-title">{{ Str::limit($document->title, 25) }}</h5>
                    <p class="card-text text-muted small mb-2">
                        {{ $document->original_filename }}
                    </p>
                    <p class="card-text">
                        <span class="badge badge-secondary">{{ $document->category_label }}</span>
                        <span class="text-muted small ml-2">{{ $document->human_file_size }}</span>
                    </p>
                </div>
                <div class="card-footer text-center">
                    <a href="{{ route('documents.show', $document) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye"></i> View
                    </a>
                    <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-download"></i>
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                    <h4>No Documents Found</h4>
                    <p class="text-muted">
                        @if($search || $category)
                        No documents match your search criteria.
                        <button wire:click="clearFilters" class="btn btn-link p-0">Clear filters</button>
                        @else
                        You don't have any documents yet.
                        @endif
                    </p>
                </div>
            </div>
        </div>
        @endforelse
    </div>

    @if($documents->hasPages())
    <div class="d-flex justify-content-center">
        {{ $documents->links() }}
    </div>
    @endif
</div>
