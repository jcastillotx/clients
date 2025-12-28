<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <h1 class="h3 mb-0">Edit Client</h1>
                    <p class="text-muted mb-0">{{ $client->company_name }}</p>
                </div>
                <div>
                    <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-outline-secondary mr-2">
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </a>
                    <button type="button" class="btn btn-outline-warning" wire:click="sendPasswordReset">
                        <i class="fas fa-key mr-1"></i> Send Password Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash Messages & Validation Errors --}}
    @include('partials.flash-messages')

    <!-- Tabs -->
    <div class="card">
        <div class="card-header p-0">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $tab === 'overview' ? 'active' : '' }}" wire:click.prevent="$set('tab', 'overview')">
                        <i class="fas fa-user mr-1"></i> Overview
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $tab === 'profile' ? 'active' : '' }}" wire:click.prevent="$set('tab', 'profile')">
                        <i class="fas fa-briefcase mr-1"></i> Business Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $tab === 'services' ? 'active' : '' }}" wire:click.prevent="$set('tab', 'services')">
                        <i class="fas fa-cogs mr-1"></i> Services
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $tab === 'activity' ? 'active' : '' }}" wire:click.prevent="$set('tab', 'activity')">
                        <i class="fas fa-history mr-1"></i> Activity
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <!-- Overview Tab -->
            @if($tab === 'overview')
                <form wire:submit.prevent="saveOverview">
                    <div class="row">
                        <div class="col-lg-8">
                            <!-- Basic Information -->
                            <h5 class="mb-3"><i class="fas fa-info-circle mr-2 text-muted"></i>Basic Information</h5>
                            
                            <div class="form-group">
                                <label>Company Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('company_name') is-invalid @enderror" wire:model.live.debounce.300ms="company_name">
                                @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Contact Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('contact_name') is-invalid @enderror" wire:model.live.debounce.300ms="contact_name">
                                        @error('contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" wire:model.live.debounce.300ms="email">
                                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Phone</label>
                                        <input type="text" class="form-control" wire:model.live.debounce.300ms="phone">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Tier</label>
                                        <select class="form-control" wire:model.live="tier">
                                            @foreach($tiers as $k => $label)
                                                <option value="{{ $k }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select class="form-control" wire:model.live="status">
                                            @foreach($statuses as $k => $label)
                                                <option value="{{ $k }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h5 class="mb-3"><i class="fas fa-map-marker-alt mr-2 text-muted"></i>Address</h5>

                            <div class="form-group">
                                <label>Street Address</label>
                                <input type="text" class="form-control" wire:model.live.debounce.300ms="address">
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>City</label>
                                        <input type="text" class="form-control" wire:model.live.debounce.300ms="city">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>State</label>
                                        <input type="text" class="form-control" wire:model.live.debounce.300ms="state">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>ZIP Code</label>
                                        <input type="text" class="form-control" wire:model.live.debounce.300ms="zip_code">
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h5 class="mb-3"><i class="fas fa-credit-card mr-2 text-muted"></i>Billing</h5>

                            <div class="form-group">
                                <label>Stripe Customer ID</label>
                                <input type="text" class="form-control" wire:model.live.debounce.300ms="stripe_customer_id" placeholder="cus_...">
                            </div>

                            <hr>
                            <h5 class="mb-3"><i class="fas fa-sticky-note mr-2 text-muted"></i>Notes</h5>

                            <div class="form-group">
                                <label>Client Notes</label>
                                <textarea class="form-control" rows="2" wire:model.live.debounce.400ms="notes"></textarea>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <!-- Internal Notes -->
                            <div class="card card-outline card-warning">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><i class="fas fa-lock mr-2"></i>Internal Notes</h5>
                                    <span class="badge badge-warning float-right">Staff Only</span>
                                </div>
                                <div class="card-body">
                                    <textarea class="form-control" rows="4" wire:model.live.debounce.400ms="internal_notes" placeholder="Private notes not visible to the client..."></textarea>
                                    <small class="text-muted d-block mt-2">These notes are only visible to staff and admins.</small>
                                </div>
                            </div>

                            <!-- Linked User -->
                            <div class="card card-outline card-info">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><i class="fas fa-user-circle mr-2"></i>Linked User</h5>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0">
                                        <strong>{{ $primaryUser?->name ?? 'No user linked' }}</strong><br>
                                        <span class="text-muted">{{ $primaryUser?->email ?? '' }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <button type="submit" class="btn btn-primary btn-block" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="saveOverview">
                                    <i class="fas fa-save mr-1"></i> Save Changes
                                </span>
                                <span wire:loading wire:target="saveOverview">
                                    <i class="fas fa-spinner fa-spin mr-1"></i> Saving...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            @endif

            <!-- Business Profile Tab -->
            @if($tab === 'profile')
                <div class="row">
                    <div class="col-lg-8">
                        <h5 class="mb-3"><i class="fas fa-briefcase mr-2 text-muted"></i>Business Profile</h5>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Mission Statement</label>
                                    <textarea class="form-control" rows="4" wire:model.live.debounce.400ms="mission" placeholder="What is the company's core purpose?"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Vision Statement</label>
                                    <textarea class="form-control" rows="4" wire:model.live.debounce.400ms="vision" placeholder="What does the company aspire to become?"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Known Competitors</label>
                            <textarea class="form-control" rows="2" wire:model.live.debounce.400ms="competitors" placeholder="List main competitors, separated by commas"></textarea>
                            <small class="text-muted">Used for competitive analysis and brand monitoring.</small>
                        </div>

                        <button type="button" class="btn btn-primary" wire:click="saveProfile" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="saveProfile">
                                <i class="fas fa-save mr-1"></i> Save Profile
                            </span>
                            <span wire:loading wire:target="saveProfile">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Saving...
                            </span>
                        </button>
                    </div>

                    <div class="col-lg-4">
                        <!-- AI Marketing Strategy -->
                        <div class="card card-outline card-purple">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fas fa-magic mr-2"></i>AI Marketing Strategy</h5>
                                <span class="badge badge-purple float-right">AI Powered</span>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-3">Generate a comprehensive marketing strategy based on the client's profile.</p>

                                @if($client->marketing_strategy_generated_at)
                                    <p class="text-muted small">Last generated: {{ $client->marketing_strategy_generated_at->diffForHumans() }}</p>
                                @endif

                                <button type="button" 
                                    class="btn btn-purple btn-block mb-3"
                                    wire:click="generateMarketingStrategy" 
                                    wire:loading.attr="disabled"
                                    wire:target="generateMarketingStrategy">
                                    <span wire:loading.remove wire:target="generateMarketingStrategy">
                                        <i class="fas fa-bolt mr-1"></i> {{ $marketing_strategy ? 'Regenerate' : 'Generate' }} Strategy
                                    </span>
                                    <span wire:loading wire:target="generateMarketingStrategy">
                                        <i class="fas fa-spinner fa-spin mr-1"></i> Generating...
                                    </span>
                                </button>

                                @error('marketing_strategy')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror

                                @if($marketing_strategy)
                                    <div class="card bg-light mb-0">
                                        <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                            <span class="text-muted small">Marketing Strategy</span>
                                            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="$set('marketing_strategy', '')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                            {!! $marketing_strategy !!}
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center py-4 bg-light rounded">
                                        <i class="fas fa-lightbulb fa-2x text-muted mb-2"></i>
                                        <p class="text-muted small mb-0">Click "Generate Strategy" to create an AI-powered marketing plan</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Services Tab -->
            @if($tab === 'services')
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Tier Features:</strong> The client's tier (<span class="font-weight-bold">{{ ucfirst($tier) }}</span>) includes certain features by default. Additional services checked below will be added on top of tier features.
                </div>

                @php
                    $categories = [
                        'core' => 'Core Features',
                        'brand_monitoring' => 'Brand Monitoring',
                        'ai' => 'AI Features',
                        'advanced' => 'Advanced Features',
                        'collaboration' => 'Collaboration',
                        'research' => 'Research & Consultation',
                    ];
                @endphp

                <div class="row">
                    @foreach($categories as $categoryKey => $categoryLabel)
                        @if(isset($servicesByCategory[$categoryKey]))
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card card-outline card-secondary h-100 mb-0">
                                    <div class="card-header py-2">
                                        <h5 class="card-title mb-0">{{ $categoryLabel }}</h5>
                                    </div>
                                    <div class="card-body py-2" style="max-height: 300px; overflow-y: auto;">
                                        @foreach($servicesByCategory[$categoryKey] as $serviceKey => $service)
                                            @php
                                                $tierIncludes = in_array($serviceKey, $tierFeatures[$tier] ?? []);
                                                $isSelected = in_array($serviceKey, $selectedServices);
                                            @endphp
                                            <div class="form-check mb-2" wire:key="service-{{ $categoryKey }}-{{ $serviceKey }}">
                                                @if($tierIncludes)
                                                    {{-- Tier-included: show as checked & disabled without wire:model --}}
                                                    <input class="form-check-input" type="checkbox" 
                                                        id="service_{{ $serviceKey }}"
                                                        checked 
                                                        disabled>
                                                @else
                                                    {{-- User-selectable: use wire:model --}}
                                                    <input class="form-check-input" type="checkbox" 
                                                        wire:model.live="selectedServices" 
                                                        value="{{ $serviceKey }}"
                                                        id="service_{{ $serviceKey }}">
                                                @endif
                                                <label class="form-check-label" for="service_{{ $serviceKey }}">
                                                    {{ $service['name'] }}
                                                    @if($tierIncludes)
                                                        <span class="badge badge-info ml-1">Tier</span>
                                                    @elseif($isSelected)
                                                        <span class="badge badge-success ml-1">Added</span>
                                                    @endif
                                                </label>
                                                <small class="d-block text-muted">{{ $service['description'] }}</small>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                <button type="button" class="btn btn-primary mt-3" wire:click="saveServices" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveServices">
                        <i class="fas fa-save mr-1"></i> Save Services
                    </span>
                    <span wire:loading wire:target="saveServices">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Saving...
                    </span>
                </button>
            @endif

            <!-- Activity Tab -->
            @if($tab === 'activity')
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>When</th>
                                <th>User</th>
                                <th>Log</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activities as $a)
                                <tr>
                                    <td class="text-muted">{{ $a->created_at?->diffForHumans() }}</td>
                                    <td>{{ $a->user?->name ?? 'System' }}</td>
                                    <td><span class="badge badge-secondary">{{ $a->log_name }}</span></td>
                                    <td>{{ $a->description }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <i class="fas fa-clock fa-2x text-muted mb-2"></i>
                                        <p class="text-muted mb-0">No activity yet.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($activities->hasPages())
                    <div class="mt-3">
                        {{ $activities->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Set Password Modal -->
    @if($showPasswordModal ?? false)
        <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Set Password</h5>
                        <button type="button" class="close" wire:click="$set('showPasswordModal', false)">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" class="form-control @error('newPassword') is-invalid @enderror" wire:model="newPassword" autocomplete="new-password">
                            @error('newPassword') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" class="form-control" wire:model="newPasswordConfirmation" autocomplete="new-password">
                        </div>
                        <small class="text-muted">Password must be at least 8 characters.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showPasswordModal', false)">Cancel</button>
                        <button type="button" class="btn btn-warning" wire:click="setPassword" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="setPassword">Set Password</span>
                            <span wire:loading wire:target="setPassword">Saving...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
    .btn-purple {
        background-color: #6f42c1;
        border-color: #6f42c1;
        color: #fff;
    }
    .btn-purple:hover {
        background-color: #5a32a3;
        border-color: #5a32a3;
        color: #fff;
    }
    .badge-purple {
        background-color: #6f42c1;
        color: #fff;
    }
    .card-outline.card-purple {
        border-top: 3px solid #6f42c1;
    }
</style>
