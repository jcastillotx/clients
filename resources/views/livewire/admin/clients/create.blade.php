<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <h1 class="h3 mb-0">Add New Client</h1>
                    <p class="text-muted mb-0">Creates the client record and a client user account.</p>
                </div>
                <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    {{-- Flash Messages & Validation Errors --}}
    @include('partials.flash-messages')

    <form wire:submit.prevent="save">
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Client Mode Selection -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-plus mr-2"></i>Client Type</h3>
                    </div>
                    <div class="card-body">
                        <div class="btn-group btn-group-toggle w-100 mb-3" data-toggle="buttons">
                            <label class="btn btn-outline-primary @if($mode === 'new') active @endif" style="flex: 1;">
                                <input type="radio" wire:model.live="mode" value="new" autocomplete="off"> 
                                <i class="fas fa-plus-circle mr-1"></i> Create New Client
                            </label>
                            <label class="btn btn-outline-primary @if($mode === 'existing') active @endif" style="flex: 1;">
                                <input type="radio" wire:model.live="mode" value="existing" autocomplete="off"> 
                                <i class="fas fa-search mr-1"></i> Link Existing Client
                            </label>
                        </div>

                        @if($mode === 'existing')
                            <div class="form-group position-relative">
                                <label>Search Existing Clients</label>
                                <div class="input-group">
                                    <input type="text" 
                                        class="form-control" 
                                        wire:model.live.debounce.300ms="clientSearch" 
                                        placeholder="Type company name, contact name, or email..."
                                        @if($existing_client_id) readonly @endif>
                                    @if($existing_client_id)
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-danger" wire:click="clearClientSelection">
                                                <i class="fas fa-times"></i> Clear
                                            </button>
                                        </div>
                                    @endif
                                </div>
                                
                                {{-- Search Results Dropdown --}}
                                @if(!$existing_client_id && strlen($clientSearch) >= 2)
                                    <div class="dropdown-menu show w-100 mt-1" style="max-height: 300px; overflow-y: auto;">
                                        @forelse($existingClients as $client)
                                            <a href="#" class="dropdown-item" wire:click.prevent="selectExistingClient({{ $client->id }})">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong>{{ $client->company_name }}</strong>
                                                        @if($client->contact_name)
                                                            <br><small class="text-muted">{{ $client->contact_name }}</small>
                                                        @endif
                                                    </div>
                                                    <small class="text-muted">{{ $client->email }}</small>
                                                </div>
                                            </a>
                                        @empty
                                            <div class="dropdown-item text-muted">
                                                <i class="fas fa-info-circle mr-1"></i> No clients found matching "{{ $clientSearch }}"
                                            </div>
                                        @endforelse
                                    </div>
                                @endif

                                @if($existing_client_id)
                                    <div class="alert alert-success mt-2 mb-0">
                                        <i class="fas fa-check-circle mr-1"></i> 
                                        Client selected. You can now create a new user account for this client.
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Company Information -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-building mr-2"></i>Company Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Company Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                class="form-control @error('company_name') is-invalid @enderror" 
                                wire:model.live.debounce.300ms="company_name"
                                @if($mode === 'existing' && $existing_client_id) readonly @endif>
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
                                    <small class="text-muted">This email will be used to create the client user account.</small>
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="text" 
                                        class="form-control @error('phone') is-invalid @enderror phone-input" 
                                        wire:model.live.debounce.300ms="phone"
                                        placeholder="{{ $phoneFormat['placeholder'] ?? 'Phone number' }}"
                                        data-country="{{ $country }}">
                                    <small class="text-muted">Format: {{ $phoneFormat['placeholder'] ?? 'Phone number' }}</small>
                                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tier</label>
                                    <select class="form-control @error('tier') is-invalid @enderror" wire:model.live="tier">
                                        @foreach($tiers as $k => $label)
                                            <option value="{{ $k }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('tier') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select class="form-control @error('status') is-invalid @enderror" wire:model.live="status">
                                        @foreach($statuses as $k => $label)
                                            <option value="{{ $k }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Address -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>Address</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Country</label>
                                    <select class="form-control @error('country') is-invalid @enderror" wire:model.live="country">
                                        @foreach($countries as $code => $name)
                                            <option value="{{ $code }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Street Address</label>
                                    <input type="text" class="form-control" wire:model.live.debounce.300ms="address">
                                </div>
                            </div>
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
                                    <label>State/Province</label>
                                    @if($country === 'US')
                                        <select class="form-control @error('state') is-invalid @enderror" wire:model.live="state">
                                            <option value="">-- Select State --</option>
                                            @foreach($usStates as $code => $name)
                                                <option value="{{ $code }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="text" class="form-control @error('state') is-invalid @enderror" wire:model.live.debounce.300ms="state" placeholder="State/Province/Region">
                                    @endif
                                    @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ $country === 'US' ? 'ZIP Code' : 'Postal Code' }}</label>
                                    <input type="text" class="form-control" wire:model.live.debounce.300ms="zip_code">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Services & Features -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-cogs mr-2"></i>Services & Features</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Select which services this client has access to. These are in addition to tier-based features.</p>
                        
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
                                            <div class="card-body py-2" style="max-height: 250px; overflow-y: auto;">
                                                @foreach($servicesByCategory[$categoryKey] as $serviceKey => $service)
                                                    @php
                                                        $tierIncludes = in_array($serviceKey, $tierFeatures[$tier] ?? []);
                                                        $isSelected = in_array($serviceKey, $selectedServices ?? []);
                                                    @endphp
                                                    <div class="form-check mb-2" wire:key="service-{{ $categoryKey }}-{{ $serviceKey }}">
                                                        @if($tierIncludes)
                                                            {{-- Tier-included: show as checked & disabled without wire:model --}}
                                                            <input class="form-check-input" type="checkbox" 
                                                                id="service_create_{{ $serviceKey }}"
                                                                checked 
                                                                disabled>
                                                        @else
                                                            {{-- User-selectable: use wire:model --}}
                                                            <input class="form-check-input" type="checkbox" 
                                                                wire:model.live="selectedServices" 
                                                                value="{{ $serviceKey }}"
                                                                id="service_create_{{ $serviceKey }}">
                                                        @endif
                                                        <label class="form-check-label" for="service_create_{{ $serviceKey }}">
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
                    </div>
                </div>

                <!-- Business Profile -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-briefcase mr-2"></i>Business Profile</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Mission Statement</label>
                                    <textarea class="form-control" rows="3" wire:model.live.debounce.400ms="mission" placeholder="What is the company's core purpose?"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Vision Statement</label>
                                    <textarea class="form-control" rows="3" wire:model.live.debounce.400ms="vision" placeholder="What does the company aspire to become?"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Known Competitors</label>
                            <textarea class="form-control" rows="2" wire:model.live.debounce.400ms="competitors" placeholder="List main competitors, separated by commas"></textarea>
                            <small class="text-muted">Used for competitive analysis and brand monitoring.</small>
                        </div>
                    </div>
                </div>

                <!-- AI Marketing Strategy -->
                <div class="card card-outline card-purple">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-magic mr-2"></i>AI Marketing Strategy</h3>
                        <div class="card-tools">
                            <span class="badge badge-purple">AI Powered</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Generate a comprehensive marketing strategy based on the client's profile. Fill in mission, vision, and competitors for better results.</p>
                        
                        <button type="button" 
                            class="btn btn-purple mb-3"
                            wire:click="generateMarketingStrategy" 
                            wire:loading.attr="disabled"
                            wire:target="generateMarketingStrategy"
                            @if(empty($company_name)) disabled @endif>
                            <span wire:loading.remove wire:target="generateMarketingStrategy">
                                <i class="fas fa-bolt mr-1"></i> Generate Strategy
                            </span>
                            <span wire:loading wire:target="generateMarketingStrategy">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Generating...
                            </span>
                        </button>

                        @error('marketing_strategy')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror

                        @if($marketing_strategy)
                            <div class="card bg-light">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Generated Marketing Strategy</span>
                                    <button type="button" class="btn btn-sm btn-outline-danger" wire:click="$set('marketing_strategy', '')">
                                        <i class="fas fa-times"></i> Clear
                                    </button>
                                </div>
                                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                    {!! $marketing_strategy !!}
                                </div>
                            </div>
                        @else
                            <div class="text-center py-4 bg-light rounded">
                                <i class="fas fa-lightbulb fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">Click "Generate Strategy" to create an AI-powered marketing plan</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Billing -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-credit-card mr-2"></i>Billing</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-0">
                            <label>Stripe Customer ID</label>
                            <input type="text" class="form-control" wire:model.live.debounce.300ms="stripe_customer_id" placeholder="cus_...">
                            <small class="text-muted">Optional. Link to existing Stripe customer.</small>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-sticky-note mr-2"></i>Notes</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Client Notes</label>
                            <textarea class="form-control" rows="3" wire:model.live.debounce.400ms="notes" placeholder="General notes about the client..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Internal Notes -->
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-lock mr-2"></i>Internal Notes</h3>
                        <div class="card-tools">
                            <span class="badge badge-warning">Staff Only</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-0">
                            <textarea class="form-control" rows="4" wire:model.live.debounce.400ms="internal_notes" placeholder="Private notes not visible to the client..."></textarea>
                            <small class="text-muted">These notes are only visible to staff and admins.</small>
                        </div>
                    </div>
                </div>

                <!-- Account Settings -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-cog mr-2"></i>Account Settings</h3>
                    </div>
                    <div class="card-body">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="sendPasswordSetLink" wire:model.live="sendPasswordSetLink">
                            <label class="custom-control-label" for="sendPasswordSetLink">Send password set link</label>
                        </div>
                        <small class="text-muted d-block mt-1">If disabled, a temporary password will be emailed.</small>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary btn-block" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save">
                                <i class="fas fa-save mr-1"></i> Create Client
                            </span>
                            <span wire:loading wire:target="save">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Creating...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Phone mask patterns by country
    const phoneMasks = {
        'US': { pattern: '(###) ###-####', placeholder: '(555) 123-4567' },
        'CA': { pattern: '(###) ###-####', placeholder: '(555) 123-4567' },
        'GB': { pattern: '##### ######', placeholder: '07911 123456' },
        'AU': { pattern: '#### ### ###', placeholder: '0412 345 678' },
        'DE': { pattern: '#### ########', placeholder: '0151 12345678' },
        'FR': { pattern: '## ## ## ## ##', placeholder: '06 12 34 56 78' },
        'MX': { pattern: '## #### ####', placeholder: '55 1234 5678' },
        'BR': { pattern: '(##) #####-####', placeholder: '(11) 99999-9999' },
    };

    function applyPhoneMask(input, country) {
        const mask = phoneMasks[country];
        if (!mask) return;

        input.placeholder = mask.placeholder;
        
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            let formattedValue = '';
            let maskIndex = 0;
            
            for (let i = 0; i < value.length && maskIndex < mask.pattern.length; i++) {
                while (maskIndex < mask.pattern.length && mask.pattern[maskIndex] !== '#') {
                    formattedValue += mask.pattern[maskIndex];
                    maskIndex++;
                }
                if (maskIndex < mask.pattern.length) {
                    formattedValue += value[i];
                    maskIndex++;
                }
            }
            
            e.target.value = formattedValue;
            // Update Livewire model
            if (typeof Livewire !== 'undefined') {
                Livewire.find(input.closest('[wire\\:id]').getAttribute('wire:id')).set('phone', formattedValue);
            }
        });
    }

    // Initialize phone mask
    function initPhoneMask() {
        const phoneInput = document.querySelector('.phone-input');
        if (phoneInput) {
            const country = phoneInput.dataset.country || 'US';
            applyPhoneMask(phoneInput, country);
        }
    }

    initPhoneMask();

    // Re-initialize on Livewire updates
    if (typeof Livewire !== 'undefined') {
        Livewire.hook('morph.updated', () => {
            initPhoneMask();
        });
    }
});
</script>
@endpush
