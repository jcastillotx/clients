<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Logos & Images</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="font-weight-bold mb-2">Company logo</div>
                            @if(!empty($branding['logo_path']))
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $branding['logo_path']) }}" alt="Logo" style="max-height: 56px;" onerror="this.style.display='none'">
                                </div>
                            @endif
                            <input type="file" class="form-control mb-2" wire:model="logo_upload">
                            <small class="text-muted d-block mb-2">PNG/JPG/WEBP/SVG up to 2MB.</small>
                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="uploadLogo">
                                <i class="fas fa-upload mr-1"></i> Upload
                            </button>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="font-weight-bold mb-2">Login logo</div>
                            @if(!empty($branding['login_logo_path']))
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $branding['login_logo_path']) }}" alt="Login Logo" style="max-height: 56px;" onerror="this.style.display='none'">
                                </div>
                            @endif
                            <input type="file" class="form-control mb-2" wire:model="login_logo_upload">
                            <small class="text-muted d-block mb-2">PNG/JPG/WEBP/SVG up to 2MB.</small>
                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="uploadLoginLogo">
                                <i class="fas fa-upload mr-1"></i> Upload
                            </button>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="font-weight-bold mb-2">Dashboard logo (/admin)</div>
                            @if(!empty($branding['dashboard_logo_path']))
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $branding['dashboard_logo_path']) }}" alt="Dashboard Logo" style="max-height: 32px;" onerror="this.style.display='none'">
                                </div>
                            @endif
                            <input type="file" class="form-control mb-2" wire:model="dashboard_logo_upload">
                            <small class="text-muted d-block mb-2">PNG/JPG/WEBP/SVG up to 2MB.</small>
                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="uploadDashboardLogo">
                                <i class="fas fa-upload mr-1"></i> Upload
                            </button>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="font-weight-bold mb-2">Login background</div>
                            @if(!empty($branding['login_background_path']))
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $branding['login_background_path']) }}" alt="Login Background" style="height: 56px; width: 100%; object-fit: cover;" onerror="this.style.display='none'">
                                </div>
                            @endif
                            <input type="file" class="form-control mb-2" wire:model="login_background_upload">
                            <small class="text-muted d-block mb-2">PNG/JPG/WEBP up to 5MB.</small>
                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="uploadLoginBackground">
                                <i class="fas fa-upload mr-1"></i> Upload
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Colors</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label class="mb-1">Primary</label>
                        <input type="color" class="form-control form-control-color" wire:model.defer="branding.color_primary">
                    </div>
                    <div class="col-md-4">
                        <label class="mb-1">Secondary</label>
                        <input type="color" class="form-control form-control-color" wire:model.defer="branding.color_secondary">
                    </div>
                    <div class="col-md-4">
                        <label class="mb-1">Accent</label>
                        <input type="color" class="form-control form-control-color" wire:model.defer="branding.color_accent">
                    </div>
                </div>

                <hr>

                <div class="font-weight-bold mb-2">Buttons</div>
                <div class="row">
                    <div class="col-md-3">
                        <label class="mb-1">Primary</label>
                        <input type="color" class="form-control form-control-color" wire:model.defer="branding.button_primary">
                    </div>
                    <div class="col-md-3">
                        <label class="mb-1">Primary hover</label>
                        <input type="color" class="form-control form-control-color" wire:model.defer="branding.button_primary_hover">
                    </div>
                    <div class="col-md-3">
                        <label class="mb-1">Secondary</label>
                        <input type="color" class="form-control form-control-color" wire:model.defer="branding.button_secondary">
                    </div>
                    <div class="col-md-3">
                        <label class="mb-1">Secondary hover</label>
                        <input type="color" class="form-control form-control-color" wire:model.defer="branding.button_secondary_hover">
                    </div>
                </div>
                <small class="text-muted d-block mt-2">These update the global button styling across the site.</small>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">HTML Injection</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="mb-1">Header HTML (inside &lt;head&gt;)</label>
                    <textarea class="form-control" rows="6" wire:model.defer="branding.site_header_html" placeholder="Analytics, meta tags, verification codes…"></textarea>
                </div>
                <div class="form-group">
                    <label class="mb-1">Footer HTML (before &lt;/body&gt;)</label>
                    <textarea class="form-control" rows="6" wire:model.defer="branding.site_footer_html" placeholder="Chat widgets, scripts…"></textarea>
                </div>
                <small class="text-muted">This HTML is injected on all pages (client + admin).</small>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Templates & Domain</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="mb-1">Invoice template</label>
                    <select class="form-control" wire:model.defer="branding.invoice_template">
                        <option value="default">Default</option>
                        <option value="modern">Modern</option>
                        <option value="minimal">Minimal</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="mb-1">Email header HTML</label>
                    <textarea class="form-control" rows="4" wire:model.defer="branding.email_header_html"></textarea>
                </div>
                <div class="form-group">
                    <label class="mb-1">Email footer HTML</label>
                    <textarea class="form-control" rows="4" wire:model.defer="branding.email_footer_html"></textarea>
                </div>

                <div class="form-group mb-0">
                    <label class="mb-1">Custom domain</label>
                    <input class="form-control" wire:model.defer="branding.custom_domain" placeholder="portal.yourcompany.com">
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-primary btn-block" wire:click="saveBranding" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="saveBranding"><i class="fas fa-save mr-1"></i> Save Branding</span>
            <span wire:loading wire:target="saveBranding">Saving…</span>
        </button>
    </div>
</div>

