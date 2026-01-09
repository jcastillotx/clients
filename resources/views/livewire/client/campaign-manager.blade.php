<div class="container-fluid py-4">
    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">Campaign Manager</h1>
                    <p class="text-muted mb-0">Create and manage your marketing campaigns</p>
                </div>
                @if($activeTab === 'list')
                <button wire:click="createCampaign" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i> New Campaign
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    @if($activeTab === 'list')
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card h-100 border-left-primary shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Campaigns</div>
                            <div class="h4 mb-0 font-weight-bold">{{ $stats['total'] }}</div>
                        </div>
                        <i class="fas fa-bullhorn fa-2x text-primary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card h-100 border-left-success shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active</div>
                            <div class="h4 mb-0 font-weight-bold">{{ $stats['active'] }}</div>
                        </div>
                        <i class="fas fa-play-circle fa-2x text-success opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card h-100 border-left-info shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Planning</div>
                            <div class="h4 mb-0 font-weight-bold">{{ $stats['planning'] }}</div>
                        </div>
                        <i class="fas fa-drafting-compass fa-2x text-info opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card h-100 border-left-secondary shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Total Budget</div>
                            <div class="h4 mb-0 font-weight-bold">${{ number_format($stats['total_budget'], 2) }}</div>
                        </div>
                        <i class="fas fa-dollar-sign fa-2x text-secondary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Campaign List Tab --}}
    @if($activeTab === 'list')
        {{-- Filters --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-4 mb-2 mb-md-0">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search campaigns...">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <select wire:model.live="statusFilter" class="form-select form-select-sm" style="width: auto;">
                                <option value="all">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="planning">Planning</option>
                                <option value="paused">Paused</option>
                                <option value="completed">Completed</option>
                            </select>
                            <select wire:model.live="typeFilter" class="form-select form-select-sm" style="width: auto;">
                                <option value="all">All Types</option>
                                <option value="social">Social</option>
                                <option value="email">Email</option>
                                <option value="ppc">PPC</option>
                                <option value="content">Content</option>
                                <option value="seo">SEO</option>
                                <option value="launch">Launch</option>
                                <option value="event">Event</option>
                                <option value="seasonal">Seasonal</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Campaigns Table --}}
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Campaign</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Dates</th>
                                <th>Budget</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($campaigns as $campaign)
                                <tr>
                                    <td>
                                        <div class="font-weight-bold">{{ $campaign->campaign_name }}</div>
                                        @if($campaign->description)
                                            <small class="text-muted">{{ Str::limit($campaign->description, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $this->getTypeBadgeClass($campaign->campaign_type) }}">
                                            {{ ucfirst($campaign->campaign_type) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $this->getStatusBadgeClass($campaign->status) }}">
                                            {{ ucfirst($campaign->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($campaign->start_date)
                                            {{ $campaign->start_date->format('M d') }}
                                            @if($campaign->end_date)
                                                - {{ $campaign->end_date->format('M d, Y') }}
                                            @endif
                                        @else
                                            <span class="text-muted">Not scheduled</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($campaign->budget)
                                            ${{ number_format($campaign->budget, 2) }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button wire:click="editCampaign({{ $campaign->id }})" class="btn btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button wire:click="duplicateCampaign({{ $campaign->id }})" class="btn btn-outline-secondary" title="Duplicate">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                            <a href="{{ route('client.campaigns') }}?selectedCampaignId={{ $campaign->id }}&activeTab=detail" class="btn btn-outline-info" title="Analytics">
                                                <i class="fas fa-chart-line"></i>
                                            </a>
                                            <button wire:click="confirmDelete({{ $campaign->id }})" class="btn btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="py-4">
                                            <i class="fas fa-bullhorn fa-4x text-muted mb-3 opacity-25"></i>
                                            <h5 class="text-muted">No campaigns found</h5>
                                            <p class="text-muted mb-3">Get started by creating your first campaign</p>
                                            <button wire:click="createCampaign" class="btn btn-primary">
                                                <i class="fas fa-plus mr-1"></i> Create Campaign
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($campaigns->hasPages())
                <div class="card-footer">
                    {{ $campaigns->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- Create/Edit Form Tab --}}
    @if($activeTab === 'form')
        <div class="mb-3">
            <button wire:click="setTab('list')" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to Campaigns
            </button>
        </div>

        <div class="card shadow-sm">
            <div class="card-header py-3">
                <h5 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-{{ $editingCampaignId ? 'edit' : 'plus' }} mr-2"></i>
                    {{ $editingCampaignId ? 'Edit Campaign' : 'Create New Campaign' }}
                </h5>
            </div>
            <div class="card-body">
                <form wire:submit="saveCampaign">
                    <div class="row">
                        {{-- Campaign Name --}}
                        <div class="col-md-8 mb-3">
                            <label for="campaignName" class="form-label font-weight-bold">Campaign Name <span class="text-danger">*</span></label>
                            <input type="text" wire:model="campaignName" id="campaignName" class="form-control @error('campaignName') is-invalid @enderror" placeholder="Enter campaign name">
                            @error('campaignName')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Campaign Type --}}
                        <div class="col-md-4 mb-3">
                            <label for="campaignType" class="form-label font-weight-bold">Type <span class="text-danger">*</span></label>
                            <select wire:model="campaignType" id="campaignType" class="form-select @error('campaignType') is-invalid @enderror">
                                <option value="content">Content Marketing</option>
                                <option value="social">Social Media</option>
                                <option value="email">Email Marketing</option>
                                <option value="ppc">PPC / Paid Ads</option>
                                <option value="seo">SEO Campaign</option>
                                <option value="launch">Product Launch</option>
                                <option value="event">Event Campaign</option>
                                <option value="seasonal">Seasonal Campaign</option>
                            </select>
                            @error('campaignType')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        {{-- Description --}}
                        <div class="col-12 mb-3">
                            <label for="description" class="form-label font-weight-bold">Description</label>
                            <textarea wire:model="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Describe your campaign objectives and strategy..."></textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        {{-- Start Date --}}
                        <div class="col-md-3 mb-3">
                            <label for="startDate" class="form-label font-weight-bold">Start Date</label>
                            <input type="date" wire:model="startDate" id="startDate" class="form-control @error('startDate') is-invalid @enderror">
                            @error('startDate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- End Date --}}
                        <div class="col-md-3 mb-3">
                            <label for="endDate" class="form-label font-weight-bold">End Date</label>
                            <input type="date" wire:model="endDate" id="endDate" class="form-control @error('endDate') is-invalid @enderror">
                            @error('endDate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Budget --}}
                        <div class="col-md-3 mb-3">
                            <label for="budget" class="form-label font-weight-bold">Budget ($)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input type="number" wire:model="budget" id="budget" step="0.01" min="0" class="form-control @error('budget') is-invalid @enderror" placeholder="0.00">
                            </div>
                            @error('budget')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Status --}}
                        <div class="col-md-3 mb-3">
                            <label for="status" class="form-label font-weight-bold">Status <span class="text-danger">*</span></label>
                            <select wire:model="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="planning">Planning</option>
                                <option value="active">Active</option>
                                <option value="paused">Paused</option>
                                <option value="completed">Completed</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Campaign Goals --}}
                    <div class="mb-4">
                        <label class="form-label font-weight-bold">Campaign Goals</label>
                        <div class="input-group mb-2">
                            <input type="text" wire:model="newGoal" wire:keydown.enter.prevent="addGoal" class="form-control" placeholder="Add a goal (e.g., Increase brand awareness by 20%)">
                            <button type="button" wire:click="addGoal" class="btn btn-outline-primary">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                        @if(count($goals) > 0)
                            <div class="mt-2">
                                @foreach($goals as $index => $goal)
                                    <span class="badge badge-light border p-2 mr-2 mb-2">
                                        <i class="fas fa-bullseye text-primary mr-1"></i>
                                        {{ $goal }}
                                        <button type="button" wire:click="removeGoal({{ $index }})" class="btn btn-link btn-sm p-0 ml-2 text-danger">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <small class="text-muted">No goals added yet. Goals help track your campaign success.</small>
                        @endif
                    </div>

                    <hr>

                    {{-- Form Actions --}}
                    <div class="d-flex justify-content-between">
                        <button type="button" wire:click="setTab('list')" class="btn btn-outline-secondary">
                            <i class="fas fa-times mr-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> {{ $editingCampaignId ? 'Update Campaign' : 'Create Campaign' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-warning mr-2"></i>Delete Campaign</h5>
                        <button type="button" wire:click="cancelDelete" class="close">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this campaign? This action cannot be undone.</p>
                        <p class="text-muted small mb-0">All associated metrics and links will also be deleted.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="cancelDelete" class="btn btn-secondary">Cancel</button>
                        <button type="button" wire:click="deleteCampaign" class="btn btn-danger">
                            <i class="fas fa-trash mr-1"></i> Delete Campaign
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('styles')
<style>
.border-left-primary { border-left: 4px solid #4e73df !important; }
.border-left-success { border-left: 4px solid #1cc88a !important; }
.border-left-info { border-left: 4px solid #36b9cc !important; }
.border-left-secondary { border-left: 4px solid #858796 !important; }

.opacity-25 { opacity: 0.25; }
.opacity-50 { opacity: 0.5; }

.badge-pink { background-color: #ec4899; color: white; }
.badge-purple { background-color: #8b5cf6; color: white; }
.badge-orange { background-color: #f97316; color: white; }
.badge-teal { background-color: #14b8a6; color: white; }
.badge-indigo { background-color: #6366f1; color: white; }
.badge-cyan { background-color: #06b6d4; color: white; }

.gap-2 { gap: 0.5rem; }

.table td { vertical-align: middle; }
</style>
@endpush
</div>
