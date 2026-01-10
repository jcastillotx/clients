<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-palette mr-2"></i>
                        AI Brand Guidelines Generator
                    </h3>
                </div>
                <div class="card-body">
                    @if (session()->has('message'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            {{ session('message') }}
                        </div>
                    @endif

                    @if (session()->has('error'))
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Tabs -->
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link @if($activeTab === 'upload') active @endif"
                               wire:click="$set('activeTab', 'upload')"
                               role="tab">
                                <i class="fas fa-upload"></i> Upload Assets
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @if($activeTab === 'results') active @endif"
                               wire:click="$set('activeTab', 'results')"
                               role="tab"
                               @if(!$guidelines) disabled @endif>
                                <i class="fas fa-file-alt"></i> Guidelines
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @if($activeTab === 'colors') active @endif"
                               wire:click="$set('activeTab', 'colors')"
                               role="tab"
                               @if(!$colorPalette) disabled @endif>
                                <i class="fas fa-palette"></i> Color Palette
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @if($activeTab === 'code') active @endif"
                               wire:click="$set('activeTab', 'code')"
                               role="tab"
                               @if(!$tailwindConfig) disabled @endif>
                                <i class="fas fa-code"></i> Tailwind Config
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content mt-3">
                        <!-- Upload Tab -->
                        <div class="tab-pane @if($activeTab === 'upload') active @endif">
                            <form wire:submit.prevent="generateGuidelines">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Brand Name *</label>
                                            <input type="text"
                                                   class="form-control @error('brandName') is-invalid @enderror"
                                                   wire:model="brandName"
                                                   placeholder="Enter brand name">
                                            @error('brandName') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Industry *</label>
                                            <input type="text"
                                                   class="form-control @error('industry') is-invalid @enderror"
                                                   wire:model="industry"
                                                   placeholder="e.g., Technology, Healthcare, Fashion">
                                            @error('industry') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Target Audience *</label>
                                            <input type="text"
                                                   class="form-control @error('targetAudience') is-invalid @enderror"
                                                   wire:model="targetAudience"
                                                   placeholder="e.g., Young professionals, Small businesses, B2B clients">
                                            @error('targetAudience') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Logo File *</label>
                                            <input type="file"
                                                   class="form-control @error('logoFile') is-invalid @enderror"
                                                   wire:model="logoFile"
                                                   accept="image/*">
                                            @error('logoFile') <span class="invalid-feedback">{{ $message }}</span> @enderror

                                            @if ($logoFile)
                                                <div class="mt-2">
                                                    <img src="{{ $logoFile->temporaryUrl() }}"
                                                         class="img-thumbnail"
                                                         style="max-height: 150px;">
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Additional Brand Assets (Optional)</label>
                                            <input type="file"
                                                   class="form-control"
                                                   wire:model="additionalAssets"
                                                   accept="image/*"
                                                   multiple>
                                            <small class="form-text text-muted">
                                                Upload mockups, color palettes, or other brand materials
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <button type="submit"
                                            class="btn btn-primary"
                                            wire:loading.attr="disabled"
                                            wire:target="generateGuidelines,logoFile,additionalAssets">
                                        <span wire:loading.remove wire:target="generateGuidelines">
                                            <i class="fas fa-magic"></i> Generate Guidelines with AI
                                        </span>
                                        <span wire:loading wire:target="generateGuidelines">
                                            <i class="fas fa-spinner fa-spin"></i> Analyzing...
                                        </span>
                                    </button>

                                    @if($guidelines)
                                        <button type="button"
                                                class="btn btn-secondary"
                                                wire:click="reset">
                                            <i class="fas fa-redo"></i> Start Over
                                        </button>
                                    @endif
                                </div>
                            </form>
                        </div>

                        <!-- Guidelines Tab -->
                        <div class="tab-pane @if($activeTab === 'results') active @endif">
                            @if($guidelines)
                                <div class="mb-3">
                                    <button class="btn btn-success" wire:click="downloadGuidelines">
                                        <i class="fas fa-download"></i> Download Guidelines
                                    </button>

                                    <div class="badge badge-info ml-2">
                                        Tokens: {{ number_format($tokensUsed) }}
                                    </div>
                                    <div class="badge badge-warning ml-2">
                                        Cost: ${{ number_format($cost, 4) }}
                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-body">
                                        <div class="markdown-content">
                                            {!! \Illuminate\Support\Str::markdown($guidelines) !!}
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    Upload your brand assets to generate guidelines.
                                </div>
                            @endif
                        </div>

                        <!-- Color Palette Tab -->
                        <div class="tab-pane @if($activeTab === 'colors') active @endif">
                            @if($colorPalette)
                                <div class="row">
                                    @if(isset($colorPalette['primary']))
                                        <div class="col-md-4">
                                            <h5>Primary Colors</h5>
                                            @foreach($colorPalette['primary'] as $color)
                                                <div class="d-flex align-items-center mb-2">
                                                    <div style="width: 50px; height: 50px; background: {{ $color['hex'] }}; border: 1px solid #ddd;"></div>
                                                    <div class="ml-3">
                                                        <strong>{{ $color['name'] ?? 'Color' }}</strong><br>
                                                        <code>{{ $color['hex'] }}</code>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if(isset($colorPalette['secondary']))
                                        <div class="col-md-4">
                                            <h5>Secondary Colors</h5>
                                            @foreach($colorPalette['secondary'] as $color)
                                                <div class="d-flex align-items-center mb-2">
                                                    <div style="width: 50px; height: 50px; background: {{ $color['hex'] }}; border: 1px solid #ddd;"></div>
                                                    <div class="ml-3">
                                                        <strong>{{ $color['name'] ?? 'Color' }}</strong><br>
                                                        <code>{{ $color['hex'] }}</code>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if(isset($colorPalette['accents']))
                                        <div class="col-md-4">
                                            <h5>Accent Colors</h5>
                                            @foreach($colorPalette['accents'] as $color)
                                                <div class="d-flex align-items-center mb-2">
                                                    <div style="width: 50px; height: 50px; background: {{ $color['hex'] }}; border: 1px solid #ddd;"></div>
                                                    <div class="ml-3">
                                                        <strong>{{ $color['name'] ?? 'Color' }}</strong><br>
                                                        <code>{{ $color['hex'] }}</code>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                @if(isset($colorPalette['harmony']))
                                    <div class="mt-4">
                                        <h5>Color Harmony</h5>
                                        <p>{{ $colorPalette['harmony'] }}</p>
                                    </div>
                                @endif

                                @if(isset($colorPalette['accessibility']))
                                    <div class="mt-3">
                                        <h5>Accessibility Notes</h5>
                                        <div class="alert alert-info">
                                            {{ json_encode($colorPalette['accessibility'], JSON_PRETTY_PRINT) }}
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div class="alert alert-info">
                                    Generate guidelines first to extract color palette.
                                </div>
                            @endif
                        </div>

                        <!-- Tailwind Config Tab -->
                        <div class="tab-pane @if($activeTab === 'code') active @endif">
                            @if($tailwindConfig)
                                <div class="mb-3">
                                    <button class="btn btn-success" wire:click="downloadTailwindConfig">
                                        <i class="fas fa-download"></i> Download Config
                                    </button>
                                </div>

                                <pre><code class="language-javascript">{{ $tailwindConfig }}</code></pre>
                            @else
                                <div class="alert alert-info">
                                    Generate guidelines first to create Tailwind configuration.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.markdown-content {
    line-height: 1.6;
}

.markdown-content h1 {
    font-size: 2rem;
    margin-top: 2rem;
    margin-bottom: 1rem;
    border-bottom: 2px solid #dee2e6;
    padding-bottom: 0.5rem;
}

.markdown-content h2 {
    font-size: 1.5rem;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}

.markdown-content ul {
    margin-left: 1.5rem;
}

.markdown-content code {
    background: #f8f9fa;
    padding: 0.2rem 0.4rem;
    border-radius: 3px;
    font-size: 90%;
}
</style>
