<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit mr-2"></i>
                        Create Social Media Post
                    </h3>
                    <div class="card-tools">
                        <button wire:click="toggleAIPanel" type="button" class="btn btn-sm btn-primary">
                            <i class="fas fa-robot mr-1"></i>
                            {{ $show_ai_panel ? 'Hide' : 'Show' }} AI Assistant
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle mr-2"></i>
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <form>
                        <!-- Client Selection -->
                        <div class="form-group">
                            <label for="client_id">Client <span class="text-danger">*</span></label>
                            <select wire:model="client_id" id="client_id" class="form-control @error('client_id') is-invalid @enderror">
                                <option value="">Select Client</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                                @endforeach
                            </select>
                            @error('client_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <!-- Platform Selection -->
                        <div class="form-group">
                            <label for="platform">Platform <span class="text-danger">*</span></label>
                            <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                                @foreach($platforms as $key => $label)
                                    <label class="btn btn-outline-primary flex-fill {{ $platform === $key ? 'active' : '' }}">
                                        <input type="radio" wire:model="platform" value="{{ $key }}" autocomplete="off">
                                        <i class="{{ $this->getPlatformIcon($key) }} mr-1"></i>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            @error('platform') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <!-- Title -->
                        <div class="form-group">
                            <label for="title">Post Title (Internal) <span class="text-danger">*</span></label>
                            <input wire:model.defer="title" type="text" id="title" class="form-control @error('title') is-invalid @enderror" placeholder="Internal title for this post">
                            @error('title') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <!-- Content Text -->
                        <div class="form-group">
                            <label for="content_text">Post Content <span class="text-danger">*</span></label>
                            <textarea wire:model="content_text" id="content_text" rows="8" class="form-control @error('content_text') is-invalid @enderror" placeholder="Write your post content..."></textarea>
                            <small class="form-text {{ $character_count > $character_limit ? 'text-danger' : 'text-muted' }}">
                                {{ $character_count }} / {{ number_format($character_limit) }} characters
                                @if($character_count > $character_limit)
                                    <span class="text-danger font-weight-bold">- Exceeds limit!</span>
                                @endif
                            </small>
                            @error('content_text') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <!-- AI Tools -->
                        <div class="mb-3">
                            <button wire:click.prevent="generateHashtags" type="button" class="btn btn-sm btn-outline-primary mr-2">
                                <i class="fas fa-hashtag mr-1"></i>
                                Generate Hashtags
                            </button>
                            <button wire:click.prevent="analyzeContent" type="button" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-chart-line mr-1"></i>
                                Analyze Content
                            </button>
                        </div>

                        <!-- Hashtags -->
                        <div class="form-group">
                            <label for="hashtags">Hashtags</label>
                            <input wire:model.defer="hashtags" type="text" id="hashtags" class="form-control" placeholder="#marketing #socialmedia">
                            <small class="form-text text-muted">Separate with spaces, include # symbol</small>
                        </div>

                        <!-- Campaign Tag -->
                        <div class="form-group">
                            <label for="campaign_tag">Campaign Tag</label>
                            <input wire:model.defer="campaign_tag" type="text" id="campaign_tag" class="form-control" placeholder="spring-campaign-2025">
                        </div>

                        <!-- Scheduled Date -->
                        <div class="form-group">
                            <label for="scheduled_for">Schedule For (Optional)</label>
                            <input wire:model.defer="scheduled_for" type="datetime-local" id="scheduled_for" class="form-control">
                            <small class="form-text text-muted">Leave empty to save as draft</small>
                        </div>

                        <!-- Action Buttons -->
                        <div class="form-group">
                            <button wire:click.prevent="saveDraft" type="button" class="btn btn-secondary" {{ $saving ? 'disabled' : '' }}>
                                <i class="fas fa-save mr-1"></i>
                                Save as Draft
                            </button>
                            <button wire:click.prevent="submitForApproval" type="button" class="btn btn-primary ml-2" {{ $saving ? 'disabled' : '' }}>
                                <i class="fas fa-paper-plane mr-1"></i>
                                Submit for Client Approval
                            </button>
                            @if($saving)
                                <span class="ml-2"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- AI Assistant Panel -->
        <div class="col-md-4">
            @if($show_ai_panel)
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-robot mr-2"></i>
                            AI Content Generator
                        </h3>
                    </div>

                    <div class="card-body">
                        @if(session('ai_success'))
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle mr-1"></i>
                                {{ session('ai_success') }}
                            </div>
                        @endif

                        @if(session('ai_error'))
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                {{ session('ai_error') }}
                            </div>
                        @endif

                        <!-- AI Prompt -->
                        <div class="form-group">
                            <label>What do you want to post about?</label>
                            <textarea wire:model.defer="ai_prompt" rows="4" class="form-control" placeholder="E.g., Announce our new product launch..."></textarea>
                        </div>

                        <!-- Tone Selection -->
                        <div class="form-group">
                            <label>Tone</label>
                            <select wire:model.defer="ai_tone" class="form-control">
                                @foreach($tones as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Target Audience -->
                        <div class="form-group">
                            <label>Target Audience (Optional)</label>
                            <input wire:model.defer="ai_target_audience" type="text" class="form-control" placeholder="Small business owners">
                        </div>

                        <!-- Keywords -->
                        <div class="form-group">
                            <label>Keywords (Optional)</label>
                            <input wire:model.defer="ai_keywords" type="text" class="form-control" placeholder="innovation, growth, success">
                            <small class="form-text text-muted">Comma-separated</small>
                        </div>

                        <!-- Options -->
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input wire:model.defer="ai_include_hashtags" type="checkbox" class="custom-control-input" id="ai_include_hashtags">
                                <label class="custom-control-label" for="ai_include_hashtags">Include Hashtags</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input wire:model.defer="ai_include_emoji" type="checkbox" class="custom-control-input" id="ai_include_emoji">
                                <label class="custom-control-label" for="ai_include_emoji">Include Emojis</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input wire:model.defer="ai_include_cta" type="checkbox" class="custom-control-input" id="ai_include_cta">
                                <label class="custom-control-label" for="ai_include_cta">Include Call-to-Action</label>
                            </div>
                        </div>

                        <!-- Generate Button -->
                        <button wire:click="generateWithAI" type="button" class="btn btn-primary btn-block" {{ $ai_generating ? 'disabled' : '' }}>
                            @if($ai_generating)
                                <i class="fas fa-spinner fa-spin mr-1"></i>
                                Generating...
                            @else
                                <i class="fas fa-magic mr-1"></i>
                                Generate Content
                            @endif
                        </button>

                        <!-- AI Variations -->
                        @if(count($ai_variations) > 0)
                            <div class="mt-4">
                                <h5>Select a Variation:</h5>
                                @foreach($ai_variations as $index => $variation)
                                    <div class="card mb-2">
                                        <div class="card-body p-3">
                                            <p class="mb-2 small">{{ $variation['content'] }}</p>
                                            @if(!empty($variation['hashtags']))
                                                <div class="mb-2">
                                                    @foreach($variation['hashtags'] as $hashtag)
                                                        <span class="badge badge-secondary">#{{ $hashtag }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                            <button wire:click="useAIVariation({{ $index }})" type="button" class="btn btn-sm btn-success btn-block">
                                                <i class="fas fa-check mr-1"></i>
                                                Use This One
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Helper function to get platform icon
    function getPlatformIcon(platform) {
        const icons = {
            'facebook': 'fab fa-facebook',
            'instagram': 'fab fa-instagram',
            'linkedin': 'fab fa-linkedin',
            'x': 'fab fa-x-twitter',
            'twitter': 'fab fa-x-twitter',
            'tiktok': 'fab fa-tiktok',
            'pinterest': 'fab fa-pinterest'
        };
        return icons[platform] || 'fas fa-share-alt';
    }

    // Expose function to component
    window.livewire.on('showAnalysis', (analysis) => {
        let message = `Content Analysis:\n\n`;
        message += `Sentiment: ${analysis.sentiment}\n`;
        message += `Engagement Score: ${analysis.engagement_score}/10\n`;
        message += `Readability Score: ${analysis.readability_score}/10\n\n`;

        if (analysis.strengths && analysis.strengths.length > 0) {
            message += `Strengths:\n${analysis.strengths.map(s => `• ${s}`).join('\n')}\n\n`;
        }

        if (analysis.improvements && analysis.improvements.length > 0) {
            message += `Suggestions:\n${analysis.improvements.map(i => `• ${i}`).join('\n')}`;
        }

        alert(message);
    });
</script>
@endpush
