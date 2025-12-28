@php
    $isSuperAdmin = auth()->user()?->hasRole('super_admin') ?? false;
@endphp

@if(!$isSuperAdmin)
    <div class="alert alert-warning">
        <i class="fas fa-lock mr-2"></i>
        <strong>Access Restricted:</strong> Only Super Admins can modify branding settings.
    </div>
@else
    <!-- Success/Error Notifications -->
    @if(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" id="brandingSuccessAlert">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <i class="fas fa-check-circle mr-2"></i>
            <strong>Success!</strong> {{ session('success') }}
        </div>
    @endif
    @if(session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <i class="fas fa-exclamation-circle mr-2"></i>
            <strong>Error!</strong> {{ session('error') }}
        </div>
    @endif
    
    <!-- Toast Container for Image Uploads -->
    <div id="uploadToastContainer" class="position-fixed" style="top: 20px; right: 20px; z-index: 1060;"></div>
    
    <!-- Top Save Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0"><i class="fas fa-paint-brush mr-2 text-primary"></i>Branding Settings</h4>
            <small class="text-muted">Customize your platform's appearance</small>
        </div>
        <button type="button" class="btn btn-primary btn-lg" wire:click="saveBranding" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="saveBranding">
                <i class="fas fa-save mr-2"></i>Save Branding Settings
            </span>
            <span wire:loading wire:target="saveBranding">
                <i class="fas fa-spinner fa-spin mr-2"></i>Saving...
            </span>
        </button>
    </div>
    
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
                            <div class="card card-outline card-secondary h-100 mb-0" wire:loading.class="border-primary" wire:target="logo_upload,uploadLogo">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Company Logo</h5>
                                    @if(!empty($branding['logo_path']))
                                        <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Uploaded</span>
                                    @endif
                                </div>
                                <div class="card-body">
                                    @if(!empty($branding['logo_path']))
                                        <div class="mb-2 p-2 bg-light rounded text-center position-relative">
                                            <img src="{{ asset('storage/' . $branding['logo_path']) }}?{{ time() }}" 
                                                alt="Logo" 
                                                class="img-thumbnail brand-preview-thumb" 
                                                style="max-height: 60px; max-width: 100%; cursor: pointer;" 
                                                data-toggle="modal" 
                                                data-target="#imagePreviewModal"
                                                data-image="{{ asset('storage/' . $branding['logo_path']) }}"
                                                data-title="Company Logo"
                                                onerror="this.style.display='none'">
                                            <small class="d-block text-muted mt-1"><i class="fas fa-search-plus mr-1"></i>Click to preview</small>
                                        </div>
                                    @else
                                        <div class="mb-2 p-3 bg-light rounded text-center text-muted">
                                            <i class="fas fa-image fa-2x"></i>
                                            <small class="d-block mt-1">No logo uploaded</small>
                                        </div>
                                    @endif
                                    
                                    <div class="input-group mb-2">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="logoUpload" wire:model="logo_upload" accept="image/*">
                                            <label class="custom-file-label" for="logoUpload">
                                                @if($logo_upload)
                                                    {{ $logo_upload->getClientOriginalName() }}
                                                @else
                                                    Choose file...
                                                @endif
                                            </label>
                                        </div>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-primary" wire:click="uploadLogo" wire:loading.attr="disabled" wire:target="logo_upload,uploadLogo" @if(!$logo_upload) disabled @endif>
                                                <span wire:loading.remove wire:target="logo_upload,uploadLogo"><i class="fas fa-upload"></i></span>
                                                <span wire:loading wire:target="logo_upload,uploadLogo"><i class="fas fa-spinner fa-spin"></i></span>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted">PNG/JPG/WEBP/SVG up to 2MB</small>
                                    
                                    <!-- Upload Progress -->
                                    <div wire:loading wire:target="logo_upload" class="mt-2">
                                        <div class="progress" style="height: 4px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 100%"></div>
                                        </div>
                                        <small class="text-primary">Uploading file...</small>
                                    </div>
                                    
                                    @error('logo') <div class="alert alert-danger mt-2 py-1 px-2 mb-0"><small>{{ $message }}</small></div> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Login Logo -->
                        <div class="col-md-6 mb-3">
                            <div class="card card-outline card-secondary h-100 mb-0" wire:loading.class="border-primary" wire:target="login_logo_upload,uploadLoginLogo">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Login Page Logo</h5>
                                    @if(!empty($branding['login_logo_path']))
                                        <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Uploaded</span>
                                    @endif
                                </div>
                                <div class="card-body">
                                    @if(!empty($branding['login_logo_path']))
                                        <div class="mb-2 p-2 bg-light rounded text-center">
                                            <img src="{{ asset('storage/' . $branding['login_logo_path']) }}?{{ time() }}" 
                                                alt="Login Logo" 
                                                class="img-thumbnail brand-preview-thumb" 
                                                style="max-height: 60px; max-width: 100%; cursor: pointer;" 
                                                data-toggle="modal" 
                                                data-target="#imagePreviewModal"
                                                data-image="{{ asset('storage/' . $branding['login_logo_path']) }}"
                                                data-title="Login Page Logo"
                                                onerror="this.style.display='none'">
                                            <small class="d-block text-muted mt-1"><i class="fas fa-search-plus mr-1"></i>Click to preview</small>
                                        </div>
                                    @else
                                        <div class="mb-2 p-3 bg-light rounded text-center text-muted">
                                            <i class="fas fa-sign-in-alt fa-2x"></i>
                                            <small class="d-block mt-1">Uses main logo</small>
                                        </div>
                                    @endif
                                    
                                    <div class="input-group mb-2">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="loginLogoUpload" wire:model="login_logo_upload" accept="image/*">
                                            <label class="custom-file-label" for="loginLogoUpload">
                                                {{ $login_logo_upload ? $login_logo_upload->getClientOriginalName() : 'Choose file...' }}
                                            </label>
                                        </div>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-primary" wire:click="uploadLoginLogo" wire:loading.attr="disabled" wire:target="login_logo_upload,uploadLoginLogo" @if(!$login_logo_upload) disabled @endif>
                                                <span wire:loading.remove wire:target="login_logo_upload,uploadLoginLogo"><i class="fas fa-upload"></i></span>
                                                <span wire:loading wire:target="login_logo_upload,uploadLoginLogo"><i class="fas fa-spinner fa-spin"></i></span>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted">PNG/JPG/WEBP/SVG up to 2MB</small>
                                    
                                    <div wire:loading wire:target="login_logo_upload" class="mt-2">
                                        <div class="progress" style="height: 4px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 100%"></div>
                                        </div>
                                        <small class="text-primary">Uploading file...</small>
                                    </div>
                                    
                                    @error('login_logo') <div class="alert alert-danger mt-2 py-1 px-2 mb-0"><small>{{ $message }}</small></div> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Dashboard Logo -->
                        <div class="col-md-6 mb-3">
                            <div class="card card-outline card-secondary h-100 mb-0" wire:loading.class="border-primary" wire:target="dashboard_logo_upload,uploadDashboardLogo">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Sidebar/Dashboard Logo</h5>
                                    @if(!empty($branding['dashboard_logo_path']))
                                        <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Uploaded</span>
                                    @endif
                                </div>
                                <div class="card-body">
                                    @if(!empty($branding['dashboard_logo_path']))
                                        <div class="mb-2 p-2 bg-dark rounded text-center">
                                            <img src="{{ asset('storage/' . $branding['dashboard_logo_path']) }}?{{ time() }}" 
                                                alt="Dashboard Logo" 
                                                class="img-thumbnail brand-preview-thumb" 
                                                style="max-height: 40px; max-width: 100%; cursor: pointer; background: transparent; border: none;" 
                                                data-toggle="modal" 
                                                data-target="#imagePreviewModal"
                                                data-image="{{ asset('storage/' . $branding['dashboard_logo_path']) }}"
                                                data-title="Sidebar/Dashboard Logo"
                                                onerror="this.style.display='none'">
                                            <small class="d-block text-white-50 mt-1"><i class="fas fa-search-plus mr-1"></i>Click to preview</small>
                                        </div>
                                    @else
                                        <div class="mb-2 p-3 bg-dark rounded text-center text-white-50">
                                            <i class="fas fa-tachometer-alt fa-2x"></i>
                                            <small class="d-block mt-1">Uses main logo</small>
                                        </div>
                                    @endif
                                    
                                    <div class="input-group mb-2">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="dashboardLogoUpload" wire:model="dashboard_logo_upload" accept="image/*">
                                            <label class="custom-file-label" for="dashboardLogoUpload">
                                                {{ $dashboard_logo_upload ? $dashboard_logo_upload->getClientOriginalName() : 'Choose file...' }}
                                            </label>
                                        </div>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-primary" wire:click="uploadDashboardLogo" wire:loading.attr="disabled" wire:target="dashboard_logo_upload,uploadDashboardLogo" @if(!$dashboard_logo_upload) disabled @endif>
                                                <span wire:loading.remove wire:target="dashboard_logo_upload,uploadDashboardLogo"><i class="fas fa-upload"></i></span>
                                                <span wire:loading wire:target="dashboard_logo_upload,uploadDashboardLogo"><i class="fas fa-spinner fa-spin"></i></span>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted">PNG/JPG/WEBP/SVG up to 2MB</small>
                                    
                                    <div wire:loading wire:target="dashboard_logo_upload" class="mt-2">
                                        <div class="progress" style="height: 4px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 100%"></div>
                                        </div>
                                        <small class="text-primary">Uploading file...</small>
                                    </div>
                                    
                                    @error('dashboard_logo') <div class="alert alert-danger mt-2 py-1 px-2 mb-0"><small>{{ $message }}</small></div> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Login Background -->
                        <div class="col-md-6 mb-3">
                            <div class="card card-outline card-secondary h-100 mb-0" wire:loading.class="border-primary" wire:target="login_background_upload,uploadLoginBackground">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Login Background</h5>
                                    @if(!empty($branding['login_background_path']))
                                        <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Uploaded</span>
                                    @endif
                                </div>
                                <div class="card-body">
                                    @if(!empty($branding['login_background_path']))
                                        <div class="mb-2 rounded overflow-hidden position-relative" style="height: 60px; cursor: pointer;"
                                            data-toggle="modal" 
                                            data-target="#imagePreviewModal"
                                            data-image="{{ asset('storage/' . $branding['login_background_path']) }}"
                                            data-title="Login Background">
                                            <img src="{{ asset('storage/' . $branding['login_background_path']) }}?{{ time() }}" 
                                                alt="Login Background" 
                                                style="width: 100%; height: 100%; object-fit: cover;" 
                                                onerror="this.parentElement.style.display='none'">
                                            <div class="position-absolute" style="top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.5); border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-search-plus text-white"></i>
                                            </div>
                                        </div>
                                    @else
                                        <div class="mb-2 p-3 bg-light rounded text-center text-muted">
                                            <i class="fas fa-image fa-2x"></i>
                                            <small class="d-block mt-1">No background</small>
                                        </div>
                                    @endif
                                    
                                    <div class="input-group mb-2">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="loginBgUpload" wire:model="login_background_upload" accept="image/*">
                                            <label class="custom-file-label" for="loginBgUpload">
                                                {{ $login_background_upload ? $login_background_upload->getClientOriginalName() : 'Choose file...' }}
                                            </label>
                                        </div>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-primary" wire:click="uploadLoginBackground" wire:loading.attr="disabled" wire:target="login_background_upload,uploadLoginBackground" @if(!$login_background_upload) disabled @endif>
                                                <span wire:loading.remove wire:target="login_background_upload,uploadLoginBackground"><i class="fas fa-upload"></i></span>
                                                <span wire:loading wire:target="login_background_upload,uploadLoginBackground"><i class="fas fa-spinner fa-spin"></i></span>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted">PNG/JPG/WEBP up to 5MB</small>
                                    
                                    <div wire:loading wire:target="login_background_upload" class="mt-2">
                                        <div class="progress" style="height: 4px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 100%"></div>
                                        </div>
                                        <small class="text-primary">Uploading file...</small>
                                    </div>
                                    
                                    @error('bg') <div class="alert alert-danger mt-2 py-1 px-2 mb-0"><small>{{ $message }}</small></div> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Favicon -->
                        <div class="col-md-6 mb-3">
                            <div class="card card-outline card-secondary h-100 mb-0" wire:loading.class="border-primary" wire:target="favicon_upload,uploadFavicon">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Favicon</h5>
                                    @if(!empty($branding['favicon_path']))
                                        <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Uploaded</span>
                                    @endif
                                </div>
                                <div class="card-body">
                                    @if(!empty($branding['favicon_path']))
                                        <div class="mb-2 p-2 bg-light rounded text-center">
                                            <img src="{{ asset('storage/' . $branding['favicon_path']) }}?{{ time() }}" 
                                                alt="Favicon" 
                                                class="img-thumbnail brand-preview-thumb" 
                                                style="max-height: 32px; max-width: 32px; cursor: pointer;" 
                                                data-toggle="modal" 
                                                data-target="#imagePreviewModal"
                                                data-image="{{ asset('storage/' . $branding['favicon_path']) }}"
                                                data-title="Favicon"
                                                onerror="this.style.display='none'">
                                            <small class="d-block text-muted mt-1"><i class="fas fa-search-plus mr-1"></i>Click to preview</small>
                                        </div>
                                    @else
                                        <div class="mb-2 p-3 bg-light rounded text-center text-muted">
                                            <i class="fas fa-globe fa-2x"></i>
                                            <small class="d-block mt-1">Default favicon</small>
                                        </div>
                                    @endif
                                    
                                    <div class="input-group mb-2">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="faviconUpload" wire:model="favicon_upload" accept="image/*,.ico">
                                            <label class="custom-file-label" for="faviconUpload">
                                                {{ $favicon_upload ? $favicon_upload->getClientOriginalName() : 'Choose file...' }}
                                            </label>
                                        </div>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-primary" wire:click="uploadFavicon" wire:loading.attr="disabled" wire:target="favicon_upload,uploadFavicon" @if(!$favicon_upload) disabled @endif>
                                                <span wire:loading.remove wire:target="favicon_upload,uploadFavicon"><i class="fas fa-upload"></i></span>
                                                <span wire:loading wire:target="favicon_upload,uploadFavicon"><i class="fas fa-spinner fa-spin"></i></span>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted">ICO/PNG 32x32 or 64x64</small>
                                    
                                    <div wire:loading wire:target="favicon_upload" class="mt-2">
                                        <div class="progress" style="height: 4px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 100%"></div>
                                        </div>
                                        <small class="text-primary">Uploading file...</small>
                                    </div>
                                    
                                    @error('favicon') <div class="alert alert-danger mt-2 py-1 px-2 mb-0"><small>{{ $message }}</small></div> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Document Logo -->
                        <div class="col-md-6 mb-3">
                            <div class="card card-outline card-secondary h-100 mb-0" wire:loading.class="border-primary" wire:target="document_logo_upload,uploadDocumentLogo">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Document/Invoice Logo</h5>
                                    @if(!empty($branding['document_logo_path']))
                                        <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Uploaded</span>
                                    @endif
                                </div>
                                <div class="card-body">
                                    @if(!empty($branding['document_logo_path']))
                                        <div class="mb-2 p-2 bg-white border rounded text-center">
                                            <img src="{{ asset('storage/' . $branding['document_logo_path']) }}?{{ time() }}" 
                                                alt="Document Logo" 
                                                class="img-thumbnail brand-preview-thumb" 
                                                style="max-height: 50px; max-width: 100%; cursor: pointer; border: none;" 
                                                data-toggle="modal" 
                                                data-target="#imagePreviewModal"
                                                data-image="{{ asset('storage/' . $branding['document_logo_path']) }}"
                                                data-title="Document/Invoice Logo"
                                                onerror="this.style.display='none'">
                                            <small class="d-block text-muted mt-1"><i class="fas fa-search-plus mr-1"></i>Click to preview</small>
                                        </div>
                                    @else
                                        <div class="mb-2 p-3 bg-light rounded text-center text-muted">
                                            <i class="fas fa-file-invoice fa-2x"></i>
                                            <small class="d-block mt-1">Uses main logo</small>
                                        </div>
                                    @endif
                                    
                                    <div class="input-group mb-2">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="documentLogoUpload" wire:model="document_logo_upload" accept="image/*">
                                            <label class="custom-file-label" for="documentLogoUpload">
                                                {{ $document_logo_upload ? $document_logo_upload->getClientOriginalName() : 'Choose file...' }}
                                            </label>
                                        </div>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-primary" wire:click="uploadDocumentLogo" wire:loading.attr="disabled" wire:target="document_logo_upload,uploadDocumentLogo" @if(!$document_logo_upload) disabled @endif>
                                                <span wire:loading.remove wire:target="document_logo_upload,uploadDocumentLogo"><i class="fas fa-upload"></i></span>
                                                <span wire:loading wire:target="document_logo_upload,uploadDocumentLogo"><i class="fas fa-spinner fa-spin"></i></span>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted">PNG/JPG for invoices & documents</small>
                                    
                                    <div wire:loading wire:target="document_logo_upload" class="mt-2">
                                        <div class="progress" style="height: 4px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 100%"></div>
                                        </div>
                                        <small class="text-primary">Uploading file...</small>
                                    </div>
                                    
                                    @error('document_logo') <div class="alert alert-danger mt-2 py-1 px-2 mb-0"><small>{{ $message }}</small></div> @enderror
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
                                <input type="color" class="form-control form-control-color" wire:model.lazy="branding.color_primary" style="height: 38px; padding: 2px; width: 50px;">
                                <input type="text" class="form-control" wire:model.lazy="branding.color_primary" placeholder="#007bff">
                            </div>
                            <small class="text-muted">Main brand color for buttons, links</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Secondary Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" wire:model.lazy="branding.color_secondary" style="height: 38px; padding: 2px; width: 50px;">
                                <input type="text" class="form-control" wire:model.lazy="branding.color_secondary" placeholder="#6c757d">
                            </div>
                            <small class="text-muted">Secondary actions, muted elements</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Accent Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" wire:model.lazy="branding.color_accent" style="height: 38px; padding: 2px; width: 50px;">
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
                                <input type="color" class="form-control form-control-color" wire:model.lazy="branding.button_primary" style="height: 31px; padding: 2px; width: 40px;">
                                <input type="text" class="form-control" wire:model.lazy="branding.button_primary">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Primary Hover</label>
                            <div class="input-group input-group-sm">
                                <input type="color" class="form-control form-control-color" wire:model.lazy="branding.button_primary_hover" style="height: 31px; padding: 2px; width: 40px;">
                                <input type="text" class="form-control" wire:model.lazy="branding.button_primary_hover">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Secondary Button</label>
                            <div class="input-group input-group-sm">
                                <input type="color" class="form-control form-control-color" wire:model.lazy="branding.button_secondary" style="height: 31px; padding: 2px; width: 40px;">
                                <input type="text" class="form-control" wire:model.lazy="branding.button_secondary">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Secondary Hover</label>
                            <div class="input-group input-group-sm">
                                <input type="color" class="form-control form-control-color" wire:model.lazy="branding.button_secondary_hover" style="height: 31px; padding: 2px; width: 40px;">
                                <input type="text" class="form-control" wire:model.lazy="branding.button_secondary_hover">
                            </div>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div class="mt-3 p-3 bg-light rounded">
                        <label class="font-weight-bold mb-2">Live Preview</label>
                        <div class="d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                            <button type="button" class="btn" style="background-color: {{ $branding['button_primary'] ?? $branding['color_primary'] ?? '#3b82f6' }}; color: #fff;">Primary Button</button>
                            <button type="button" class="btn" style="background-color: {{ $branding['button_secondary'] ?? $branding['color_secondary'] ?? '#64748b' }}; color: #fff;">Secondary Button</button>
                            <button type="button" class="btn" style="background-color: {{ $branding['color_accent'] ?? '#0ea5e9' }}; color: #fff;">Accent Button</button>
                            <a href="#" onclick="return false;" style="color: {{ $branding['color_primary'] ?? '#3b82f6' }};">Link Text</a>
                            <span class="badge" style="background-color: {{ $branding['color_primary'] ?? '#3b82f6' }}; color: #fff;">Badge</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar & Navbar Colors -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-columns mr-2"></i>Sidebar & Navbar Colors</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Sidebar Background</label>
                            <div class="input-group input-group-sm">
                                <input type="color" class="form-control form-control-color" wire:model.lazy="branding.sidebar_bg" style="height: 31px; padding: 2px; width: 40px;">
                                <input type="text" class="form-control" wire:model.lazy="branding.sidebar_bg">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Sidebar Text</label>
                            <div class="input-group input-group-sm">
                                <input type="color" class="form-control form-control-color" wire:model.lazy="branding.sidebar_text" style="height: 31px; padding: 2px; width: 40px;">
                                <input type="text" class="form-control" wire:model.lazy="branding.sidebar_text">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Sidebar Hover</label>
                            <div class="input-group input-group-sm">
                                <input type="color" class="form-control form-control-color" wire:model.lazy="branding.sidebar_hover" style="height: 31px; padding: 2px; width: 40px;">
                                <input type="text" class="form-control" wire:model.lazy="branding.sidebar_hover">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Sidebar Active</label>
                            <div class="input-group input-group-sm">
                                <input type="color" class="form-control form-control-color" wire:model.lazy="branding.sidebar_active" style="height: 31px; padding: 2px; width: 40px;">
                                <input type="text" class="form-control" wire:model.lazy="branding.sidebar_active">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Navbar Background</label>
                            <div class="input-group input-group-sm">
                                <input type="color" class="form-control form-control-color" wire:model.lazy="branding.navbar_bg" style="height: 31px; padding: 2px; width: 40px;">
                                <input type="text" class="form-control" wire:model.lazy="branding.navbar_bg">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Navbar Text</label>
                            <div class="input-group input-group-sm">
                                <input type="color" class="form-control form-control-color" wire:model.lazy="branding.navbar_text" style="height: 31px; padding: 2px; width: 40px;">
                                <input type="text" class="form-control" wire:model.lazy="branding.navbar_text">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Content Background</label>
                            <div class="input-group input-group-sm">
                                <input type="color" class="form-control form-control-color" wire:model.lazy="branding.content_bg" style="height: 31px; padding: 2px; width: 40px;">
                                <input type="text" class="form-control" wire:model.lazy="branding.content_bg">
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar Preview -->
                    <div class="mt-2">
                        <label class="font-weight-bold mb-2">Sidebar Preview</label>
                        <div class="d-flex" style="height: 120px; border-radius: 4px; overflow: hidden; border: 1px solid #ddd;">
                            <div style="width: 200px; background-color: {{ $branding['sidebar_bg'] ?? '#1e293b' }}; padding: 10px;">
                                <div style="color: {{ $branding['sidebar_text'] ?? '#94a3b8' }}; font-size: 12px; margin-bottom: 8px;">NAVIGATION</div>
                                <div style="background-color: {{ $branding['sidebar_active'] ?? '#3b82f6' }}; color: #fff; padding: 8px 12px; border-radius: 4px; font-size: 13px; margin-bottom: 4px;">
                                    <i class="fas fa-home mr-2"></i> Dashboard
                                </div>
                                <div style="color: {{ $branding['sidebar_text'] ?? '#94a3b8' }}; padding: 8px 12px; font-size: 13px; cursor: pointer;" 
                                    onmouseover="this.style.backgroundColor='{{ $branding['sidebar_hover'] ?? '#334155' }}'"
                                    onmouseout="this.style.backgroundColor='transparent'">
                                    <i class="fas fa-users mr-2"></i> Clients
                                </div>
                            </div>
                            <div style="flex: 1; background-color: {{ $branding['content_bg'] ?? '#f8fafc' }};">
                                <div style="background-color: {{ $branding['navbar_bg'] ?? '#ffffff' }}; color: {{ $branding['navbar_text'] ?? '#1e293b' }}; padding: 10px 15px; font-size: 13px; border-bottom: 1px solid #e2e8f0;">
                                    <i class="fas fa-bars mr-3"></i> Navbar
                                </div>
                                <div style="padding: 15px;">
                                    <div style="background: #fff; padding: 10px; border-radius: 4px; font-size: 12px; color: #666; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                        Content Area
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button (Left Column - Mobile Friendly) -->
            <div class="card card-primary d-lg-none">
                <div class="card-body">
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
                                </button>
                                <small class="d-block text-center text-muted" style="font-size: 10px;">{{ $preset['name'] }}</small>
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted d-block mt-2 text-center">Click to apply a color preset</small>
                </div>
            </div>

            <!-- Platform Identity -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-building mr-2"></i>Platform Identity</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Platform Name</label>
                        <input type="text" class="form-control" wire:model.lazy="branding.platform_name" placeholder="My Agency Portal">
                        <small class="text-muted">Displayed in browser title and header</small>
                    </div>
                    <div class="form-group">
                        <label>Company Name</label>
                        <input type="text" class="form-control" wire:model.lazy="branding.company_name" placeholder="My Agency Inc.">
                    </div>
                    <div class="form-group mb-0">
                        <label>Tagline</label>
                        <input type="text" class="form-control" wire:model.lazy="branding.tagline" placeholder="Your trusted partner">
                    </div>
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
                    <h3 class="card-title"><i class="fas fa-code mr-2"></i>Custom HTML & CSS</h3>
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
                    <div class="form-group">
                        <label>Custom CSS</label>
                        <textarea class="form-control font-monospace" rows="4" wire:model.lazy="branding.custom_css" placeholder=".my-class { color: red; }" style="font-family: monospace; font-size: 12px;"></textarea>
                        <small class="text-muted">Advanced: Add custom CSS overrides</small>
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

            <!-- Save Button (Right Column) -->
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

    <!-- Save Actions Bar -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="callout callout-info">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <div class="mb-2 mb-md-0">
                        <h5 class="mb-1"><i class="fas fa-save mr-2"></i>Save Your Changes</h5>
                        <p class="text-muted mb-0">Click the button to apply your branding settings across the platform.</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary btn-lg px-5" wire:click="saveBranding" wire:loading.attr="disabled" wire:loading.class="disabled">
                            <span wire:loading.remove wire:target="saveBranding">
                                <i class="fas fa-save mr-2"></i>Save Branding Settings
                            </span>
                            <span wire:loading wire:target="saveBranding">
                                <i class="fas fa-spinner fa-spin mr-2"></i>Saving...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" role="dialog" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imagePreviewModalLabel">Image Preview</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center p-4">
                    <img id="previewModalImage" src="" alt="Preview" class="img-fluid" style="max-height: 70vh;">
                </div>
                <div class="modal-footer">
                    <a id="previewModalDownload" href="" download class="btn btn-outline-primary">
                        <i class="fas fa-download mr-1"></i> Download
                    </a>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Image preview modal handler
        document.addEventListener('DOMContentLoaded', function() {
            $('#imagePreviewModal').on('show.bs.modal', function (event) {
                var trigger = $(event.relatedTarget);
                var imageUrl = trigger.data('image');
                var title = trigger.data('title') || 'Image Preview';
                
                var modal = $(this);
                modal.find('.modal-title').text(title);
                modal.find('#previewModalImage').attr('src', imageUrl);
                modal.find('#previewModalDownload').attr('href', imageUrl);
            });
        });

        // Update custom file input labels
        document.querySelectorAll('.custom-file-input').forEach(function(input) {
            input.addEventListener('change', function(e) {
                var fileName = e.target.files[0] ? e.target.files[0].name : 'Choose file...';
                var label = e.target.nextElementSibling;
                if (label) {
                    label.textContent = fileName;
                }
            });
        });

        // Toast notification for image uploads
        function showUploadToast(type, message, isSuccess = true) {
            var container = document.getElementById('uploadToastContainer');
            if (!container) return;
            
            var toastId = 'toast-' + Date.now();
            var bgClass = isSuccess ? 'bg-success' : 'bg-danger';
            var icon = isSuccess ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            var toastHtml = `
                <div id="${toastId}" class="toast show ${bgClass} text-white" role="alert" aria-live="assertive" aria-atomic="true" style="min-width: 300px;">
                    <div class="toast-header ${bgClass} text-white">
                        <i class="fas ${icon} mr-2"></i>
                        <strong class="mr-auto">${type}</strong>
                        <small class="text-white-50">just now</small>
                        <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast" aria-label="Close" onclick="this.closest('.toast').remove()">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="toast-body">
                        <i class="fas fa-check mr-2"></i>${message}
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', toastHtml);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                var toast = document.getElementById(toastId);
                if (toast) {
                    toast.classList.remove('show');
                    setTimeout(function() { toast.remove(); }, 300);
                }
            }, 5000);
        }

        // Listen for Livewire 3 events
        document.addEventListener('livewire:init', function() {
            Livewire.on('image-uploaded', function(data) {
                showUploadToast(data.type, data.message, true);
            });

            Livewire.on('branding-saved', function(data) {
                showUploadToast('Branding Settings', data.message, true);
            });
        });

        // Auto-hide success alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            var successAlert = document.getElementById('brandingSuccessAlert');
            if (successAlert) {
                setTimeout(function() {
                    $(successAlert).alert('close');
                }, 5000);
            }
        });
    </script>
    @endpush
@endif
