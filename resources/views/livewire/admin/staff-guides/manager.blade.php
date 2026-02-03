<div>
    <x-page-header heading="Manage Staff Guides">
        <x-slot name="right">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.staff-guides') }}">Staff Guides</a></li>
                <li class="breadcrumb-item active">Manage</li>
            </ol>
        </x-slot>
    </x-page-header>

    <section class="content">
        <div class="container-fluid">
            @if(session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            @if(session()->has('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            <div class="row">
                <!-- Categories Card -->
                <div class="col-md-4">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Categories</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-primary btn-sm" wire:click="openCategoryModal">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                @forelse($categories as $cat)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="{{ $cat->icon }} mr-2 text-muted"></i>
                                            {{ $cat->name }}
                                            <span class="badge badge-secondary ml-1">{{ $cat->guides->count() }}</span>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" wire:click="openEditCategoryModal({{ $cat->id }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" wire:click="deleteCategory({{ $cat->id }})" wire:confirm="Are you sure you want to delete this category?">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </li>
                                @empty
                                    <li class="list-group-item text-center text-muted">
                                        No categories yet
                                    </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Guides Card -->
                <div class="col-md-8">
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h3 class="card-title">Guides</h3>
                            <div class="card-tools">
                                <div class="input-group input-group-sm mr-2" style="width: 200px; display: inline-flex;">
                                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search...">
                                </div>
                                <button type="button" class="btn btn-success btn-sm" wire:click="openCreateModal" @if($categories->isEmpty()) disabled title="Create a category first" @endif>
                                    <i class="fas fa-plus"></i> New Guide
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Service Tier</th>
                                        <th>Status</th>
                                        <th width="120">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($guides as $guide)
                                        <tr>
                                            <td>
                                                <strong>{{ $guide->title }}</strong>
                                                @if($guide->price)
                                                    <br><small class="text-muted">{{ $guide->formatted_price }}/mo</small>
                                                @endif
                                            </td>
                                            <td>
                                                <i class="{{ $guide->category->icon ?? 'fas fa-folder' }} mr-1"></i>
                                                {{ $guide->category->name ?? '-' }}
                                            </td>
                                            <td>
                                                @if($guide->service_tier)
                                                    <span class="badge badge-{{ match($guide->service_tier) {
                                                        'local_seo' => 'info',
                                                        'growth_seo' => 'success',
                                                        'authority_seo' => 'warning',
                                                        'onboarding' => 'primary',
                                                        default => 'secondary'
                                                    } }}">{{ $serviceTiers[$guide->service_tier] ?? $guide->service_tier }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($guide->is_published)
                                                    <span class="badge badge-success">Published</span>
                                                @else
                                                    <span class="badge badge-secondary">Draft</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" wire:click="openEditModal({{ $guide->id }})">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" wire:click="deleteGuide({{ $guide->id }})" wire:confirm="Are you sure you want to delete this guide?">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                <i class="fas fa-book-open fa-2x mb-2"></i>
                                                <p class="mb-0">No guides found</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($guides->hasPages())
                            <div class="card-footer">
                                {{ $guides->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Guide Modal -->
    @if($showModal)
        <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-book mr-2"></i>
                            {{ $editingGuideId ? 'Edit Guide' : 'Create New Guide' }}
                        </h5>
                        <button type="button" class="close text-white" wire:click="closeModal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <form wire:submit="saveGuide">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>Title <span class="text-danger">*</span></label>
                                        <input type="text" wire:model.live="title" class="form-control @error('title') is-invalid @enderror" placeholder="e.g., Local SEO Foundation Service Guide">
                                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Slug</label>
                                        <input type="text" wire:model="slug" class="form-control @error('slug') is-invalid @enderror" placeholder="auto-generated">
                                        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Category <span class="text-danger">*</span></label>
                                        <select wire:model="category_id" class="form-control @error('category_id') is-invalid @enderror">
                                            <option value="">Select category...</option>
                                            @foreach($categories as $cat)
                                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Service Tier</label>
                                        <select wire:model="service_tier" class="form-control">
                                            @foreach($serviceTiers as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Price ($/mo)</label>
                                        <input type="number" wire:model="price" class="form-control" step="0.01" min="0" placeholder="0.00">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Commitment</label>
                                        <input type="text" wire:model="commitment" class="form-control" placeholder="e.g., 3-month">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Summary</label>
                                <textarea wire:model="summary" class="form-control" rows="2" placeholder="Brief description shown in the card view..."></textarea>
                                @error('summary') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <label>Content <span class="text-danger">*</span></label>
                                <textarea wire:model="content" class="form-control @error('content') is-invalid @enderror" rows="10" placeholder="Full guide content..."></textarea>
                                @error('content') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <label>
                                    Checklist (JSON)
                                    <small class="text-muted">- Array of items or objects with title/description</small>
                                </label>
                                <textarea wire:model="checklistJson" class="form-control font-monospace" rows="8" placeholder='[
  {"title": "Step 1", "description": "Description here"},
  {"title": "Step 2", "description": "Another step"},
  "Simple text item"
]'></textarea>
                                <small class="text-muted">
                                    Format: Array of strings or objects with "title", "description", and optional "notes" keys.
                                </small>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" wire:model="is_published" class="custom-control-input" id="is_published">
                                    <label class="custom-control-label" for="is_published">Published</label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save mr-1"></i>
                                {{ $editingGuideId ? 'Update Guide' : 'Create Guide' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Category Modal -->
    @if($showCategoryModal)
        <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-folder mr-2"></i>
                            {{ $editingCategoryId ? 'Edit Category' : 'Create Category' }}
                        </h5>
                        <button type="button" class="close text-white" wire:click="closeCategoryModal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <form wire:submit="saveCategory">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Name <span class="text-danger">*</span></label>
                                <input type="text" wire:model.live="cat_name" class="form-control @error('cat_name') is-invalid @enderror" placeholder="e.g., SEO Services">
                                @error('cat_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <label>Slug</label>
                                <input type="text" wire:model="cat_slug" class="form-control @error('cat_slug') is-invalid @enderror" placeholder="auto-generated">
                                @error('cat_slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <label>Icon (FontAwesome class)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="{{ $cat_icon }}"></i></span>
                                    </div>
                                    <input type="text" wire:model.live="cat_icon" class="form-control" placeholder="fas fa-book">
                                </div>
                                <small class="text-muted">Examples: fas fa-search-dollar, fas fa-chart-line, fas fa-tools</small>
                            </div>

                            <div class="form-group">
                                <label>Description</label>
                                <textarea wire:model="cat_description" class="form-control" rows="2" placeholder="Optional description..."></textarea>
                            </div>

                            <div class="form-group">
                                <label>Sort Order</label>
                                <input type="number" wire:model="cat_sort_order" class="form-control" min="0" style="width: 100px;">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeCategoryModal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i>
                                {{ $editingCategoryId ? 'Update' : 'Create' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

@push('styles')
<style>
    .font-monospace {
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 0.875rem;
    }
</style>
@endpush
