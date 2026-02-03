<div>
    <x-page-header heading="Staff How-To Guides">
        <x-slot name="right">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Staff Guides</li>
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

            <!-- Search and Filters -->
            <div class="card card-outline card-primary">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="input-group">
                                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search guides...">
                                    <div class="input-group-append">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select wire:model.live="categoryFilter" class="form-control">
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat['id'] }}">{{ $cat['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select wire:model.live="tierFilter" class="form-control">
                                <option value="">All Service Tiers</option>
                                @foreach($serviceTiers as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 text-right">
                            @can('manage settings')
                            <a href="{{ route('admin.staff-guides.manage') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-cog"></i> Manage
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>

            <!-- Guides Grid -->
            <div class="row">
                @forelse($guides as $guide)
                    <div class="col-lg-4 col-md-6">
                        <div class="card card-outline {{ $guide->service_tier ? 'card-' . match($guide->service_tier) {
                            'local_seo' => 'info',
                            'growth_seo' => 'success',
                            'authority_seo' => 'warning',
                            'onboarding' => 'primary',
                            default => 'secondary'
                        } : 'card-secondary' }}" style="cursor: pointer;" wire:click="viewGuide({{ $guide->id }})">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="{{ $guide->category->icon ?? 'fas fa-book' }} mr-2"></i>
                                    {{ $guide->title }}
                                </h3>
                                @if($guide->price)
                                    <div class="card-tools">
                                        <span class="badge badge-light">{{ $guide->formatted_price }}/mo</span>
                                    </div>
                                @endif
                            </div>
                            <div class="card-body">
                                @if($guide->summary)
                                    <p class="text-muted mb-2">{{ Str::limit($guide->summary, 100) }}</p>
                                @endif
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge badge-secondary">{{ $guide->category->name ?? 'Uncategorized' }}</span>
                                    @if($guide->service_tier)
                                        <span class="badge badge-{{ match($guide->service_tier) {
                                            'local_seo' => 'info',
                                            'growth_seo' => 'success',
                                            'authority_seo' => 'warning',
                                            'onboarding' => 'primary',
                                            default => 'secondary'
                                        } }}">{{ $serviceTiers[$guide->service_tier] ?? $guide->service_tier }}</span>
                                    @endif
                                </div>
                                @if($guide->checklist && count($guide->checklist) > 0)
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-tasks"></i> {{ count($guide->checklist) }} checklist items
                                        </small>
                                    </div>
                                @endif
                            </div>
                            <div class="card-footer bg-white">
                                <small class="text-muted">
                                    <i class="fas fa-eye"></i> {{ $guide->views->count() }} views
                                    @if($guide->commitment)
                                        &bull; {{ $guide->commitment }}
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                                <h4>No guides found</h4>
                                <p class="text-muted">
                                    @if($search || $categoryFilter || $tierFilter)
                                        Try adjusting your search or filters.
                                    @else
                                        No how-to guides have been created yet.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($guides->hasPages())
                <div class="d-flex justify-content-center">
                    {{ $guides->links() }}
                </div>
            @endif
        </div>
    </section>

    <!-- Guide Detail Modal -->
    @if($selectedGuide)
        <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-{{ match($selectedGuide->service_tier) {
                        'local_seo' => 'info',
                        'growth_seo' => 'success',
                        'authority_seo' => 'warning',
                        'onboarding' => 'primary',
                        default => 'secondary'
                    } }} {{ in_array($selectedGuide->service_tier, ['authority_seo']) ? 'text-dark' : 'text-white' }}">
                        <h5 class="modal-title">
                            <i class="{{ $selectedGuide->category->icon ?? 'fas fa-book' }} mr-2"></i>
                            {{ $selectedGuide->title }}
                        </h5>
                        <button type="button" class="close {{ in_array($selectedGuide->service_tier, ['authority_seo']) ? 'text-dark' : 'text-white' }}" wire:click="closeGuide">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Service Info Banner -->
                        @if($selectedGuide->price || $selectedGuide->commitment)
                            <div class="alert alert-{{ match($selectedGuide->service_tier) {
                                'local_seo' => 'info',
                                'growth_seo' => 'success',
                                'authority_seo' => 'warning',
                                'onboarding' => 'primary',
                                default => 'secondary'
                            } }} d-flex justify-content-between align-items-center">
                                <div>
                                    @if($selectedGuide->service_tier)
                                        <strong>{{ $serviceTiers[$selectedGuide->service_tier] ?? $selectedGuide->service_tier }}</strong>
                                    @endif
                                </div>
                                <div>
                                    @if($selectedGuide->price)
                                        <span class="h4 mb-0">{{ $selectedGuide->formatted_price }}</span>
                                        <span class="text-muted">/ month</span>
                                    @endif
                                    @if($selectedGuide->commitment)
                                        <span class="badge badge-dark ml-2">{{ $selectedGuide->commitment }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Summary -->
                        @if($selectedGuide->summary)
                            <div class="lead mb-4">{{ $selectedGuide->summary }}</div>
                        @endif

                        <!-- Main Content -->
                        <div class="guide-content mb-4">
                            {!! nl2br(e($selectedGuide->content)) !!}
                        </div>

                        <!-- Checklist Section -->
                        @if($selectedGuide->checklist && count($selectedGuide->checklist) > 0)
                            <div class="card card-outline card-primary mt-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-tasks mr-2"></i>
                                        Service Delivery Checklist
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush">
                                        @foreach($selectedGuide->checklist as $index => $item)
                                            <li class="list-group-item">
                                                <div class="d-flex align-items-start">
                                                    <span class="badge badge-primary mr-3" style="min-width: 28px;">{{ $index + 1 }}</span>
                                                    <div class="flex-grow-1">
                                                        @if(is_array($item))
                                                            <strong>{{ $item['title'] ?? $item['task'] ?? 'Task' }}</strong>
                                                            @if(isset($item['description']))
                                                                <p class="mb-0 text-muted small">{{ $item['description'] }}</p>
                                                            @endif
                                                            @if(isset($item['notes']))
                                                                <p class="mb-0 text-info small"><i class="fas fa-info-circle"></i> {{ $item['notes'] }}</p>
                                                            @endif
                                                        @else
                                                            {{ $item }}
                                                        @endif
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        <!-- Meta Info -->
                        <div class="border-top pt-3 mt-4">
                            <div class="row text-muted small">
                                <div class="col-md-4">
                                    <i class="fas fa-folder mr-1"></i>
                                    Category: {{ $selectedGuide->category->name ?? 'Uncategorized' }}
                                </div>
                                <div class="col-md-4">
                                    <i class="fas fa-user mr-1"></i>
                                    Author: {{ $selectedGuide->author->name ?? 'System' }}
                                </div>
                                <div class="col-md-4">
                                    <i class="fas fa-calendar mr-1"></i>
                                    Updated: {{ $selectedGuide->updated_at->format('M j, Y') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeGuide">Close</button>
                        <button type="button" class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print mr-1"></i> Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('styles')
<style>
    .guide-content {
        white-space: pre-wrap;
        line-height: 1.8;
    }
    .card[wire\:click] {
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .card[wire\:click]:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    @media print {
        .modal-footer, .btn-close, .close {
            display: none !important;
        }
        .modal {
            position: relative !important;
            display: block !important;
            background: white !important;
        }
        .modal-dialog {
            max-width: 100% !important;
            margin: 0 !important;
        }
    }
</style>
@endpush
