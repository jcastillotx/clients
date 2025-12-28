<div class="space-y-6">
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-slate-900">AI Provider Configuration</h2>
        <p class="text-sm text-slate-500 mt-1">Configure API keys for AI services. All keys are encrypted before storage.</p>
    </div>

    <form wire:submit.prevent="saveAiSettings" class="space-y-8">
        <!-- Default Provider -->
        <div class="max-w-md">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Default AI Provider</label>
            <select wire:model="ai.default_provider" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                <option value="openai">OpenAI</option>
                <option value="claude">Claude (Anthropic)</option>
                <option value="gemini">Gemini (Google)</option>
                <option value="grok">Grok (xAI)</option>
                <option value="perplexity">Perplexity</option>
                <option value="copilot">Copilot (Azure OpenAI)</option>
                <option value="openrouter">OpenRouter</option>
            </select>
            <p class="mt-1.5 text-xs text-slate-500">The primary provider used for AI features</p>
        </div>

        <div class="border-t border-slate-200"></div>

        <!-- OpenAI -->
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM5.94 5.94a6 6 0 018.12 0l.84-.84a7 7 0 00-9.8 0l.84.84zm7.12 8.12a6 6 0 01-8.12 0l-.84.84a7 7 0 009.8 0l-.84-.84z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-900">OpenAI</h3>
                </div>
                @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'openai'])
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                    <input type="password" wire:model="ai.openai_api_key" placeholder="sk-..." class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Default Model</label>
                    <select wire:model="ai.openai_default_model" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        <option value="gpt-4o-mini">GPT-4o Mini (Fast, Cheap)</option>
                        <option value="gpt-4o">GPT-4o (Balanced)</option>
                        <option value="gpt-4-turbo">GPT-4 Turbo (Advanced)</option>
                        <option value="gpt-4">GPT-4 (Legacy)</option>
                        <option value="gpt-3.5-turbo">GPT-3.5 Turbo (Basic)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Claude -->
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM8 8a1 1 0 112 0v4a1 1 0 11-2 0V8zm5 0a1 1 0 112 0v4a1 1 0 11-2 0V8z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-900">Claude (Anthropic)</h3>
                </div>
                @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'claude'])
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                    <input type="password" wire:model="ai.claude_api_key" placeholder="sk-ant-..." class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Default Model</label>
                    <select wire:model="ai.claude_default_model" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        <option value="claude-3-5-sonnet-latest">Claude 3.5 Sonnet (Recommended)</option>
                        <option value="claude-3-opus-latest">Claude 3 Opus (Most Capable)</option>
                        <option value="claude-3-haiku-20240307">Claude 3 Haiku (Fast, Cheap)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Gemini -->
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2C5.582 2 2 5.582 2 10s3.582 8 8 8 8-3.582 8-8-3.582-8-8-8zm0 14c-3.314 0-6-2.686-6-6s2.686-6 6-6 6 2.686 6 6-2.686 6-6 6z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-900">Gemini (Google)</h3>
                </div>
                @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'gemini'])
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                    <input type="password" wire:model="ai.gemini_api_key" placeholder="AIza..." class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Default Model</label>
                    <select wire:model="ai.gemini_default_model" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        <option value="gemini-2.0-flash-exp">Gemini 2.0 Flash (Experimental)</option>
                        <option value="gemini-1.5-pro">Gemini 1.5 Pro (Advanced)</option>
                        <option value="gemini-1.5-flash">Gemini 1.5 Flash (Fast)</option>
                        <option value="gemini-1.5-flash-8b">Gemini 1.5 Flash 8B (Fastest)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Grok -->
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-slate-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM7 9a1 1 0 112 0 1 1 0 01-2 0zm5 0a1 1 0 112 0 1 1 0 01-2 0zm-5 4a5 5 0 008 0H7z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-900">Grok (xAI)</h3>
                </div>
                @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'grok'])
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                    <input type="password" wire:model="ai.grok_api_key" placeholder="xai-..." class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Default Model</label>
                    <select wire:model="ai.grok_default_model" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        <option value="grok-2-latest">Grok 2 Latest</option>
                        <option value="grok-2-1212">Grok 2 (2024-12-12)</option>
                        <option value="grok-2-vision-1212">Grok 2 Vision</option>
                        <option value="grok-beta">Grok Beta</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Perplexity -->
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-cyan-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-cyan-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-900">Perplexity</h3>
                </div>
                @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'perplexity'])
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                    <input type="password" wire:model="ai.perplexity_api_key" placeholder="pplx-..." class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Default Model</label>
                    <select wire:model="ai.perplexity_default_model" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        <option value="sonar">Sonar (Web-Grounded)</option>
                        <option value="sonar-pro">Sonar Pro (Advanced)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Copilot / Azure OpenAI -->
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 3h6v6H3V3zm8 0h6v6h-6V3zM3 11h6v6H3v-6zm8 0h6v6h-6v-6z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-900">Copilot / Azure OpenAI</h3>
                </div>
                @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'copilot'])
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                    <input type="password" wire:model="ai.copilot_api_key" placeholder="Azure API Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Endpoint URL</label>
                    <input type="text" wire:model="ai.copilot_endpoint" placeholder="https://your-resource.openai.azure.com" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Deployment Name</label>
                    <input type="text" wire:model="ai.copilot_deployment" placeholder="gpt-4" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
            </div>
        </div>

        <!-- OpenRouter -->
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2L2 6l8 4 8-4-8-4zM2 14l8 4 8-4M2 10l8 4 8-4"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-900">OpenRouter</h3>
                </div>
                @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'openrouter'])
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                    <input type="password" wire:model="ai.openrouter_api_key" placeholder="sk-or-..." class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Default Model</label>
                    <input type="text" wire:model="ai.openrouter_default_model" placeholder="openai/gpt-4o-mini" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                    <p class="mt-1.5 text-xs text-slate-500">Format: provider/model-name</p>
                </div>
            </div>
        </div>

        <!-- AskSage -->
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-slate-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM5 10a5 5 0 1110 0A5 5 0 015 10z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-900">AskSage</h3>
            </div>
            <div class="p-5 max-w-md">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                <input type="password" wire:model="ai.asksage_api_key" placeholder="AskSage API Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
            </div>
        </div>

        <div class="border-t border-slate-200 pt-6 flex justify-end">
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors flex items-center gap-2" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="saveAiSettings">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293z" />
                    </svg>
                    Save AI Settings
                </span>
                <span wire:loading wire:target="saveAiSettings" class="flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    Saving...
                </span>
            </button>
        </div>
    </form>
</div>
