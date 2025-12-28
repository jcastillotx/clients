<div class="row">
    <div class="col-md-6">
        <h5 class="mb-3">Logos</h5>
        @if(!empty($branding['logo_path']))
            <div class="mb-2">
                <img src="{{ asset('storage/' . $branding['logo_path']) }}" alt="Logo" style="max-height: 80px;" onerror="this.style.display='none'">
            </div>
        @endif
        <div class="form-group">
            <label class="mb-1">Upload company logo</label>
            <input type="file" class="form-control" wire:model="logo_upload">
            <small class="text-muted">PNG/JPG/WEBP/SVG up to 2MB.</small>
        </div>
        <button class="btn btn-outline-primary mb-3" wire:click="uploadLogo">
            <i class="fas fa-upload mr-1"></i> Upload Logo
        </button>

        <hr class="my-3">

        <h6 class="mb-2">Login logo</h6>
        @if(!empty($branding['login_logo_path']))
            <div class="mb-2">
                <img src="{{ asset('storage/' . $branding['login_logo_path']) }}" alt="Login Logo" style="max-height: 80px;" onerror="this.style.display='none'">
            </div>
        @endif
        <div class="form-group">
            <label class="mb-1">Upload login logo</label>
            <input type="file" class="form-control" wire:model="login_logo_upload">
            <small class="text-muted">PNG/JPG/WEBP/SVG up to 2MB.</small>
        </div>
        <button class="btn btn-outline-primary mb-3" wire:click="uploadLoginLogo">
            <i class="fas fa-upload mr-1"></i> Upload Login Logo
        </button>

        <h6 class="mb-2">Dashboard logo (/admin)</h6>
        @if(!empty($branding['dashboard_logo_path']))
            <div class="mb-2">
                <img src="{{ asset('storage/' . $branding['dashboard_logo_path']) }}" alt="Dashboard Logo" style="max-height: 40px;" onerror="this.style.display='none'">
            </div>
        @endif
        <div class="form-group">
            <label class="mb-1">Upload dashboard logo</label>
            <input type="file" class="form-control" wire:model="dashboard_logo_upload">
            <small class="text-muted">PNG/JPG/WEBP/SVG up to 2MB.</small>
        </div>
        <button class="btn btn-outline-primary mb-3" wire:click="uploadDashboardLogo">
            <i class="fas fa-upload mr-1"></i> Upload Dashboard Logo
        </button>

        <h6 class="mb-2">Login background</h6>
        @if(!empty($branding['login_background_path']))
            <div class="mb-2">
                <img src="{{ asset('storage/' . $branding['login_background_path']) }}" alt="Login Background" style="max-height: 120px; max-width: 100%; object-fit: cover;" onerror="this.style.display='none'">
            </div>
        @endif
        <div class="form-group">
            <label class="mb-1">Upload login background image</label>
            <input type="file" class="form-control" wire:model="login_background_upload">
            <small class="text-muted">PNG/JPG/WEBP up to 5MB.</small>
        </div>
        <button class="btn btn-outline-primary mb-3" wire:click="uploadLoginBackground">
            <i class="fas fa-upload mr-1"></i> Upload Login Background
        </button>

        <h5 class="mt-4 mb-3">Brand Colors</h5>
        <div class="form-group">
            <label class="mb-1">Primary</label>
            <input type="color" class="form-control form-control-color" wire:model.defer="branding.color_primary">
        </div>
        <div class="form-group">
            <label class="mb-1">Secondary</label>
            <input type="color" class="form-control form-control-color" wire:model.defer="branding.color_secondary">
        </div>
        <div class="form-group">
            <label class="mb-1">Accent</label>
            <input type="color" class="form-control form-control-color" wire:model.defer="branding.color_accent">
        </div>

        <h5 class="mt-4 mb-3">Button Colors</h5>
        <div class="form-group">
            <label class="mb-1">Primary button</label>
            <input type="color" class="form-control form-control-color" wire:model.defer="branding.button_primary">
        </div>
        <div class="form-group">
            <label class="mb-1">Primary button hover</label>
            <input type="color" class="form-control form-control-color" wire:model.defer="branding.button_primary_hover">
        </div>
        <div class="form-group">
            <label class="mb-1">Secondary button</label>
            <input type="color" class="form-control form-control-color" wire:model.defer="branding.button_secondary">
        </div>
        <div class="form-group">
            <label class="mb-1">Secondary button hover</label>
            <input type="color" class="form-control form-control-color" wire:model.defer="branding.button_secondary_hover">
        </div>
    </div>

    <div class="col-md-6">
        <h5 class="mb-3">Templates & Domain</h5>
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

        <h5 class="mt-4 mb-3">Site HTML (Injected throughout the site)</h5>
        <div class="form-group">
            <label class="mb-1">Header HTML (inside &lt;head&gt;)</label>
            <textarea class="form-control" rows="4" wire:model.defer="branding.site_header_html" placeholder="e.g. analytics scripts, meta tags"></textarea>
        </div>
        <div class="form-group">
            <label class="mb-1">Footer HTML (before &lt;/body&gt;)</label>
            <textarea class="form-control" rows="4" wire:model.defer="branding.site_footer_html" placeholder="e.g. chat widgets, scripts"></textarea>
        </div>

        <div class="form-group">
            <label class="mb-1">Custom domain</label>
            <input class="form-control" wire:model.defer="branding.custom_domain" placeholder="portal.yourcompany.com">
        </div>
    </div>
</div>

<button class="btn btn-primary" wire:click="saveBranding">
    <i class="fas fa-save mr-1"></i> Save Branding
</button>

