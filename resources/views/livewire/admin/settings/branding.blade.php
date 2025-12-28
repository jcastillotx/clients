@php
    $isSuperAdmin = auth()->user()?->hasRole('super_admin') ?? false;
@endphp

@if(!$isSuperAdmin)
    <div class="alert alert-warning">
        <i class="fas fa-lock mr-2"></i>
        <strong>Access Restricted:</strong> Only Super Admins can modify branding settings.
    </div>
@else
    <div class="row">
        <!-- Left Column: Logos & Colors -->
        <div class="col-lg-8">
            <!-- Logos Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-images mr-2"></i>Logos & Images</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Company Logo -->
                        <div class="col-md-6 mb-3">
                            <div class="card card-outline card-secondary h-100 mb-0">
                                <div class="card-header py-2">
                                    <h5 class="card-title mb-0">Company Logo</h5>
                                </div>
                                <div class="card-body">
                                    @if(!empty($branding['logo_path']))
                                        <div class="mb-2 p-2 bg-light rounded text-center">
                                            <img src="{{ asset('storage/' . $branding['logo_path']) }}" alt="Logo" style="max-height: 60px; max-width: 100%;" onerror="this.style.display='none'">
                                        </div>
                                    @else
                                        <div class="mb-2 p-3 bg-light rounded text-center text-muted">
                                            <i class="fas fa-image fa-2x"></i>
                                            <small class="d-block mt-1">No logo uploaded</small>
                                        </div>
                                    @endif
                                    <div class="custom-file mb-2">
                                        <input type="file" class="custom-file-input" id="logoUpload" wire:model="logo_upload">
                                        <label class="custom-file-label" for="logoUpload">Choose file...</label>
                                    </div>
                                    <small class="text-muted d-block mb-2">PNG/JPG/WEBP/SVG up to 2MB</small>
                                    <button type="button" class="btn btn-outline-primary btn-sm" wire:click="uploadLogo" wire:loading.attr="disabled" wire:target="logo_upload,uploadLogo">
                                        <span wire:loading.remove wire:target="logo_upload,uploadLogo"><i class="fas fa-upload mr-1"></i> Upload</span>
                                        <span wire:loading wire:target="logo_upload"><i class="fas fa-spinner fa-spin mr-1"></i> Uploading...</span>
                                        <span wire:loading wire:target="uploadLogo"><i class="fas fa-spinner fa-spin mr-1"></i> Saving...</span>
                                    </button>
                                    @error('logo') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Login Logo -->
                        <div class="col-md-6 mb-3">
                            <div class="card card-outline card-secondary h-100 mb-0">
                                <div class="card-header py-2">
                                    <h5 class="card-title mb-0">Login Page Logo</h5>
                                </div>
                                <div class="card-body">
                                    @if(!empty($branding['login_logo_path']))
                                        <div class="mb-2 p-2 bg-light rounded text-center">
                                            <img src="{{ asset('storage/' . $branding['login_logo_path']) }}" alt="Login Logo" style="max-height: 60px; max-width: 100%;" onerror="this.style.display='none'">
                                        </div>
                                    @else
                                        <div class="mb-2 p-3 bg-light rounded text-center text-muted">
                                            <i class="fas fa-sign-in-alt fa-2x"></i>
                                            <small class="d-block mt-1">Uses main logo</small>
                                        </div>
                                    @endif
                                    <div class="custom-file mb-2">
                                        <input type="file" class="custom-file-input" id="loginLogoUpload" wire:model="login_logo_upload">
                                        <label class="custom-file-label" for="loginLogoUpload">Choose file...</label>
                                    </div>
                                    <small class="text-muted d-block mb-2">PNG/JPG/WEBP/SVG up to 2MB</small>
                                    <button type="button" class="btn btn-outline-primary btn-sm" wire:click="uploadLoginLogo" wire:loading.attr="disabled" wire:target="login_logo_upload,uploadLoginLogo">
                                        <span wire:loading.remove wire:target="login_logo_upload,uploadLoginLogo"><i class="fas fa-upload mr-1"></i> Upload</span>
                                        <span wire:loading wire:target="login_logo_upload"><i class="fas fa-spinner fa-spin mr-1"></i> Uploading...</span>
                                        <span wire:loading wire:target="uploadLoginLogo"><i class="fas fa-spinner fa-spin mr-1"></i> Saving...</span>
                                    </button>
                                    @error('logo') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Dashboard Logo -->
                        <div class="col-md-6 mb-3">
                            <div class="card card-outline card-secondary h-100 mb-0">
                                <div class="card-header py-2">
                                    <h5 class="card-title mb-0">Sidebar/Dashboard Logo</h5>
                                </div>
                                <div class="card-body">
                                    @if(!empty($branding['dashboard_logo_path']))
                                        <div class="mb-2 p-2 bg-dark rounded text-center">
                                            <img src="{{ asset('storage/' . $branding['dashboard_logo_path']) }}" alt="Dashboard Logo" style="max-height: 40px; max-width: 100%;" onerror="this.style.display='none'">
                                        </div>
                                    @else
                                        <div class="mb-2 p-3 bg-dark rounded text-center text-white-50">
                                            <i class="fas fa-tachometer-alt fa-2x"></i>
                                            <small class="d-block mt-1">Uses main logo</small>
                                        </div>
                                    @endif
                                    <div class="custom-file mb-2">
                                        <input type="file" class="custom-file-input" id="dashboardLogoUpload" wire:model="dashboard_logo_upload">
                                        <label class="custom-file-label" for="dashboardLogoUpload">Choose file...</label>
                                    </div>
                                    <small class="text-muted d-block mb-2">PNG/JPG/WEBP/SVG up to 2MB</small>
                                    <button type="button" class="btn btn-outline-primary btn-sm" wire:click="uploadDashboardLogo" wire:loading.attr="disabled" wire:target="dashboard_logo_upload,uploadDashboardLogo">
                                        <span wire:loading.remove wire:target="dashboard_logo_upload,uploadDashboardLogo"><i class="fas fa-upload mr-1"></i> Upload</span>
                                        <span wire:loading wire:target="dashboard_logo_upload"><i class="fas fa-spinner fa-spin mr-1"></i> Uploading...</span>
                                        <span wire:loading wire:target="uploadDashboardLogo"><i class="fas fa-spinner fa-spin mr-1"></i> Saving...</span>
                                    </button>
                                    @error('logo') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Login Background -->
                        <div class="col-md-6 mb-3">
                            <div class="card card-outline card-secondary h-100 mb-0">
                                <div class="card-header py-2">
                                    <h5 class="card-title mb-0">Login Background</h5>
                                </div>
                                <div class="card-body">
                                    @if(!empty($branding['login_background_path']))
                                        <div class="mb-2 rounded overflow-hidden" style="height: 60px;">
                                            <img src="{{ asset('storage/' . $branding['login_background_path']) }}" alt="Login Background" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'">
                                        </div>
                                    @else
                                        <div class="mb-2 p-3 bg-light rounded text-center text-muted">
                                            <i class="fas fa-image fa-2x"></i>
                                            <small class="d-block mt-1">No background</small>
                                        </div>
                                    @endif
                                    <div class="custom-file mb-2">
                                        <input type="file" class="custom-file-input" id="loginBgUpload" wire:model="login_background_upload">
                                        <label class="custom-file-label" for="loginBgUpload">Choose file...</label>
                                    </div>
                                    <small class="text-muted d-block mb-2">PNG/JPG/WEBP up to 5MB</small>
                                    <button type="button" class="btn btn-outline-primary btn-sm" wire:click="uploadLoginBackground" wire:loading.attr="disabled" wire:target="login_background_upload,uploadLoginBackground">
                                        <span wire:loading.remove wire:target="login_background_upload,uploadLoginBackground"><i class="fas fa-upload mr-1"></i> Upload</span>
                                        <span wire:loading wire:target="login_background_upload"><i class="fas fa-spinner fa-spin mr-1"></i> Uploading...</span>
                                        <span wire:loading wire:target="uploadLoginBackground"><i class="fas fa-spinner fa-spin mr-1"></i> Saving...</span>
                                    </button>
                                    @error('bg') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Brand Colors Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-palette mr-2"></i>Brand Colors</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Primary Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" wire:model.lazy="branding.color_primary" style="height: 38px; padding: 2px;">
                                <input type="text" class="form-control" wire:model.lazy="branding.color_primary" placeholder="#007bff">
                            </div>
                            <small class="text-muted">Main brand color for buttons, links</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Secondary Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" wire:model.lazy="branding.color_secondary" style="height: 38px; padding: 2px;">
                                <input type="text" class="form-control" wire:model.lazy="branding.color_secondary" placeholder="#6c757d">
                            </div>
                            <small class="text-muted">Secondary actions, muted elements</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Accent Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" wire:model.lazy="branding.color_accent" style="height: 38px; padding: 2px;">
                                <input type="text" class="form-control" wire:model.lazy="branding.color_accent" placeholder="#28a745">
                            </div>
                            <small class="text-muted">Highlights, success states</small>
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3"><i class="fas fa-square mr-2"></i>Button Colors</h5>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Primary Button</label>
                            <div class="input-group input-group-sm">
                                <input type="color" class="form-control form-control-color" wire:model.lazy="branding.button_primary" style="height: 31px; padding: 2px;">
                                <input type="text" class="form-control" wire:model.lazy="branding.button_primary">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Primary Hover</label>
                            <div class="input-group input-group-sm">
                                <input type="color" class="form-control form-control-color" wire:model.lazy="branding.button_primary_hover" style="height: 31px; padding: 2px;">
                                <input type="text" class="form-control" wire:model.lazy="branding.button_primary_hover">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Secondary Button</label>
                            <div class="input-group input-group-sm">
                                <input type="color" class="form-control form-control-color" wire:model.lazy="branding.button_secondary" style="height: 31px; padding: 2px;">
                                <input type="text" class="form-control" wire:model.lazy="branding.button_secondary">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Secondary Hover</label>
                            <div class="input-group input-group-sm">
                                <input type="color" class="form-control form-control-color" wire:model.lazy="branding.button_secondary_hover" style="height: 31px; padding: 2px;">
                                <input type="text" class="form-control" wire:model.lazy="branding.button_secondary_hover">
                            </div>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div class="mt-3 p-3 bg-light rounded">
                        <label class="font-weight-bold mb-2">Preview</label>
                        <div class="d-flex flex-wrap gap-2" style="gap: 0.5rem;">
                            <button type="button" class="btn" style="background-color: {{ $branding['button_primary'] ?: $branding['color_primary'] }}; color: #fff;">Primary Button</button>
                            <button type="button" class="btn" style="background-color: {{ $branding['button_secondary'] ?: $branding['color_secondary'] }}; color: #fff;">Secondary Button</button>
                            <button type="button" class="btn" style="background-color: {{ $branding['color_accent'] }}; color: #fff;">Accent Button</button>
                            <a href="#" style="color: {{ $branding['color_primary'] }};">Link Text</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Templates & Customization -->
        <div class="col-lg-4">
            <!-- Quick Presets -->
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-magic mr-2"></i>Color Presets</h3>
                </div>
                <div class="card-body p-2">
                    <div class="row no-gutters">
                        @php
                            $presets = [
                                'blue' => ['name' => 'Blue', 'color' => '#007bff'],
                                'indigo' => ['name' => 'Indigo', 'color' => '#6610f2'],
                                'purple' => ['name' => 'Purple', 'color' => '#6f42c1'],
                                'teal' => ['name' => 'Teal', 'color' => '#20c997'],
                                'green' => ['name' => 'Green', 'color' => '#28a745'],
                                'orange' => ['name' => 'Orange', 'color' => '#fd7e14'],
                                'red' => ['name' => 'Red', 'color' => '#dc3545'],
                                'dark' => ['name' => 'Dark', 'color' => '#343a40'],
                            ];
                        @endphp
                        @foreach($presets as $key => $preset)
                            <div class="col-3 p-1">
                                <button type="button" 
                                    class="btn btn-block btn-sm" 
                                    style="background-color: {{ $preset['color'] }}; color: #fff; height: 40px;"
                                    wire:click="applyColorPreset('{{ $key }}')"
                                    title="{{ $preset['name'] }}">
                                    <i class="fas fa-check" style="opacity: 0;"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted d-block mt-2 text-center">Click to apply a color preset</small>
                </div>
            </div>

            <!-- Templates & Domain Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i>Templates</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Invoice Template</label>
                        <select class="form-control" wire:model.lazy="branding.invoice_template">
                            <option value="default">Default</option>
                            <option value="modern">Modern</option>
                            <option value="minimal">Minimal</option>
                            <option value="classic">Classic</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Custom Domain</label>
                        <input type="text" class="form-control" wire:model.lazy="branding.custom_domain" placeholder="portal.yourcompany.com">
                        <small class="text-muted">Configure DNS CNAME to point to this server</small>
                    </div>
                </div>
            </div>

            <!-- HTML Injection Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-code mr-2"></i>Custom HTML</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Header HTML <small class="text-muted">(inside &lt;head&gt;)</small></label>
                        <textarea class="form-control" rows="3" wire:model.lazy="branding.site_header_html" placeholder="Analytics, meta tags, verification codes..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Footer HTML <small class="text-muted">(before &lt;/body&gt;)</small></label>
                        <textarea class="form-control" rows="3" wire:model.lazy="branding.site_footer_html" placeholder="Chat widgets, tracking scripts..."></textarea>
                    </div>
                    <hr>
                    <div class="form-group">
                        <label>Email Header HTML</label>
                        <textarea class="form-control" rows="2" wire:model.lazy="branding.email_header_html" placeholder="Custom email header..."></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label>Email Footer HTML</label>
                        <textarea class="form-control" rows="2" wire:model.lazy="branding.email_footer_html" placeholder="Custom email footer, legal text..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <button type="button" class="btn btn-primary btn-lg btn-block" wire:click="saveBranding" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="saveBranding">
                    <i class="fas fa-save mr-2"></i>Save Branding Settings
                </span>
                <span wire:loading wire:target="saveBranding">
                    <i class="fas fa-spinner fa-spin mr-2"></i>Saving...
                </span>
            </button>
        </div>
    </div>
@endif
