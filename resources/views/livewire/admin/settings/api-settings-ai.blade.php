<div>
    <div class="mb-4">
        <h5>AI Provider Configuration</h5>
        <p class="text-muted small">Configure API keys for AI services. All keys are encrypted before storage.</p>
    </div>

    <form wire:submit.prevent="saveAiSettings">
        {{-- Default Provider --}}
        <div class="row mb-4">
            <div class="col-md-6">
                <label class="form-label fw-bold">Default AI Provider</label>
                <select class="form-select" wire:model="ai.default_provider">
                    <option value="openai">OpenAI</option>
                    <option value="claude">Claude (Anthropic)</option>
                    <option value="gemini">Gemini (Google)</option>
                    <option value="grok">Grok (xAI)</option>
                    <option value="perplexity">Perplexity</option>
                    <option value="copilot">Copilot (Azure OpenAI)</option>
                    <option value="openrouter">OpenRouter</option>
                </select>
                <small class="text-muted">The primary provider used for AI features</small>
            </div>
        </div>

        <hr>

        {{-- OpenAI --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-robot text-success me-2"></i>
                    <h6 class="mb-0">OpenAI</h6>
                    @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'openai'])
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">API Key</label>
                <input type="password" class="form-control" wire:model="ai.openai_api_key" placeholder="sk-...">
            </div>
            <div class="col-md-6">
                <label class="form-label">Default Model</label>
                <select class="form-select" wire:model="ai.openai_default_model">
                    <option value="gpt-4o-mini">GPT-4o Mini (Fast, Cheap)</option>
                    <option value="gpt-4o">GPT-4o (Balanced)</option>
                    <option value="gpt-4-turbo">GPT-4 Turbo (Advanced)</option>
                    <option value="gpt-4">GPT-4 (Legacy)</option>
                    <option value="gpt-3.5-turbo">GPT-3.5 Turbo (Basic)</option>
                </select>
            </div>
        </div>

        {{-- Claude --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-brain text-purple me-2" style="color: #7C3AED;"></i>
                    <h6 class="mb-0">Claude (Anthropic)</h6>
                    @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'claude'])
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">API Key</label>
                <input type="password" class="form-control" wire:model="ai.claude_api_key" placeholder="sk-ant-...">
            </div>
            <div class="col-md-6">
                <label class="form-label">Default Model</label>
                <select class="form-select" wire:model="ai.claude_default_model">
                    <option value="claude-3-5-sonnet-latest">Claude 3.5 Sonnet (Recommended)</option>
                    <option value="claude-3-opus-latest">Claude 3 Opus (Most Capable)</option>
                    <option value="claude-3-haiku-20240307">Claude 3 Haiku (Fast, Cheap)</option>
                </select>
            </div>
        </div>

        {{-- Gemini --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fab fa-google text-primary me-2"></i>
                    <h6 class="mb-0">Gemini (Google)</h6>
                    @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'gemini'])
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">API Key</label>
                <input type="password" class="form-control" wire:model="ai.gemini_api_key" placeholder="AIza...">
            </div>
            <div class="col-md-6">
                <label class="form-label">Default Model</label>
                <select class="form-select" wire:model="ai.gemini_default_model">
                    <option value="gemini-2.0-flash-exp">Gemini 2.0 Flash (Experimental)</option>
                    <option value="gemini-1.5-pro">Gemini 1.5 Pro (Advanced)</option>
                    <option value="gemini-1.5-flash">Gemini 1.5 Flash (Fast)</option>
                    <option value="gemini-1.5-flash-8b">Gemini 1.5 Flash 8B (Fastest)</option>
                </select>
            </div>
        </div>

        {{-- Grok --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fab fa-x-twitter me-2"></i>
                    <h6 class="mb-0">Grok (xAI)</h6>
                    @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'grok'])
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">API Key</label>
                <input type="password" class="form-control" wire:model="ai.grok_api_key" placeholder="xai-...">
            </div>
            <div class="col-md-6">
                <label class="form-label">Default Model</label>
                <select class="form-select" wire:model="ai.grok_default_model">
                    <option value="grok-2-latest">Grok 2 Latest</option>
                    <option value="grok-2-1212">Grok 2 (2024-12-12)</option>
                    <option value="grok-2-vision-1212">Grok 2 Vision</option>
                    <option value="grok-beta">Grok Beta</option>
                </select>
            </div>
        </div>

        {{-- Perplexity --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-search text-info me-2"></i>
                    <h6 class="mb-0">Perplexity</h6>
                    @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'perplexity'])
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">API Key</label>
                <input type="password" class="form-control" wire:model="ai.perplexity_api_key" placeholder="pplx-...">
            </div>
            <div class="col-md-6">
                <label class="form-label">Default Model</label>
                <select class="form-select" wire:model="ai.perplexity_default_model">
                    <option value="sonar">Sonar (Web-Grounded)</option>
                    <option value="sonar-pro">Sonar Pro (Advanced)</option>
                </select>
            </div>
        </div>

        {{-- Copilot / Azure OpenAI --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fab fa-microsoft text-primary me-2"></i>
                    <h6 class="mb-0">Copilot / Azure OpenAI</h6>
                    @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'copilot'])
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">API Key</label>
                <input type="password" class="form-control" wire:model="ai.copilot_api_key" placeholder="Azure API Key">
            </div>
            <div class="col-md-4">
                <label class="form-label">Endpoint URL</label>
                <input type="text" class="form-control" wire:model="ai.copilot_endpoint" placeholder="https://your-resource.openai.azure.com">
            </div>
            <div class="col-md-4">
                <label class="form-label">Deployment Name</label>
                <input type="text" class="form-control" wire:model="ai.copilot_deployment" placeholder="gpt-4">
            </div>
        </div>

        {{-- OpenRouter --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-route text-warning me-2"></i>
                    <h6 class="mb-0">OpenRouter</h6>
                    @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'openrouter'])
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">API Key</label>
                <input type="password" class="form-control" wire:model="ai.openrouter_api_key" placeholder="sk-or-...">
            </div>
            <div class="col-md-6">
                <label class="form-label">Default Model</label>
                <input type="text" class="form-control" wire:model="ai.openrouter_default_model" placeholder="openai/gpt-4o-mini">
                <small class="text-muted">Format: provider/model-name</small>
            </div>
        </div>

        {{-- AskSage --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-hat-wizard text-secondary me-2"></i>
                    <h6 class="mb-0">AskSage</h6>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">API Key</label>
                <input type="password" class="form-control" wire:model="ai.asksage_api_key" placeholder="AskSage API Key">
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="saveAiSettings">
                    <i class="fas fa-save me-1"></i> Save AI Settings
                </span>
                <span wire:loading wire:target="saveAiSettings">
                    <i class="fas fa-spinner fa-spin me-1"></i> Saving...
                </span>
            </button>
        </div>
    </form>
</div>
