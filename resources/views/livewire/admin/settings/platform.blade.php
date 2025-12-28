<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Platform Module Management</h4>
            <p class="text-muted mb-0">Enable or disable entire platform modules. Disabled modules will be hidden from all users.</p>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-outline-success" wire:click="enableAllPlatformModules" wire:loading.attr="disabled">
                <i class="fas fa-toggle-on me-1"></i> Enable All
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="disableAllPlatformModules" wire:loading.attr="disabled"
                    onclick="return confirm('Are you sure you want to disable all platform modules?')">
                <i class="fas fa-toggle-off me-1"></i> Disable All
            </button>
        </div>
    </div>

    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Warning:</strong> Disabling platform modules will immediately hide them from all users including admins. 
        Proceed with caution.
    </div>

    @php
        $groupedModules = [];
        foreach ($platformModuleDefinitions ?? [] as $key => $module) {
            $category = $module['category'];
            if (!isset($groupedModules[$category])) {
                $groupedModules[$category] = [];
            }
            $groupedModules[$category][$key] = $module;
        }
    @endphp

    <div class="row">
        @foreach($groupedModules as $category => $modules)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">
                            @php
                                $categoryIcon = match($category) {
                                    'core' => 'fas fa-cube',
                                    'communication' => 'fas fa-comments',
                                    'analytics' => 'fas fa-chart-bar',
                                    'projects' => 'fas fa-project-diagram',
                                    'ai' => 'fas fa-robot',
                                    'marketing' => 'fas fa-bullhorn',
                                    'account' => 'fas fa-handshake',
                                    'engagement' => 'fas fa-heart',
                                    'advanced' => 'fas fa-cogs',
                                    default => 'fas fa-folder',
                                };
                            @endphp
                            <i class="{{ $categoryIcon }} me-2 text-primary"></i>
                            {{ $platformCategoryLabels[$category] ?? ucfirst($category) }}
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach($modules as $key => $module)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-medium">{{ $module['name'] }}</span>
                                        <br>
                                        <small class="text-muted">{{ $module['description'] }}</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" 
                                               id="module_{{ $key }}"
                                               wire:click="togglePlatformModule('{{ $key }}')"
                                               {{ ($platformModules[$key] ?? true) ? 'checked' : '' }}
                                               wire:loading.attr="disabled">
                                        <label class="form-check-label visually-hidden" for="module_{{ $key }}">Toggle {{ $module['name'] }}</label>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        <div class="card bg-light">
            <div class="card-body">
                <h6 class="mb-3"><i class="fas fa-info-circle me-2"></i>How Platform Modules Work</h6>
                <ul class="mb-0 small text-muted">
                    <li><strong>Core Modules:</strong> Essential features like Contracts, Invoices, and Documents. Disabling these hides them from all navigation and routes.</li>
                    <li><strong>Communication:</strong> Messaging and meeting features. Disable if you don't need internal communication tools.</li>
                    <li><strong>AI Features:</strong> AI-powered features like document analysis and contract generation. Disable to reduce AI costs or if not needed.</li>
                    <li><strong>Marketing:</strong> Proposals, brand monitoring, and social media tools. Disable if not using marketing features.</li>
                    <li><strong>Advanced:</strong> Webhooks, automation, and cloud storage integrations. Disable for simpler deployments.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Module Status Summary -->
    <div class="mt-4">
        <div class="row text-center">
            @php
                $enabledCount = collect($platformModules ?? [])->filter()->count();
                $totalCount = count($platformModuleDefinitions ?? []);
                $disabledCount = $totalCount - $enabledCount;
            @endphp
            <div class="col-md-4">
                <div class="border rounded p-3">
                    <h3 class="text-primary mb-0">{{ $totalCount }}</h3>
                    <small class="text-muted">Total Modules</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-3">
                    <h3 class="text-success mb-0">{{ $enabledCount }}</h3>
                    <small class="text-muted">Enabled</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-3">
                    <h3 class="text-danger mb-0">{{ $disabledCount }}</h3>
                    <small class="text-muted">Disabled</small>
                </div>
            </div>
        </div>
    </div>
</div>
