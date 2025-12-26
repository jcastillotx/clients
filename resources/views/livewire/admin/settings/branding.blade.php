<div class="row">
    <div class="col-md-6">
        <h5 class="mb-3">Logo</h5>
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

        <h5 class="mt-4 mb-3">Brand Colors</h5>
        <div class="form-group">
            <label class="mb-1">Primary</label>
            <input class="form-control" wire:model.defer="branding.color_primary">
        </div>
        <div class="form-group">
            <label class="mb-1">Secondary</label>
            <input class="form-control" wire:model.defer="branding.color_secondary">
        </div>
        <div class="form-group">
            <label class="mb-1">Accent</label>
            <input class="form-control" wire:model.defer="branding.color_accent">
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
        <div class="form-group">
            <label class="mb-1">Custom domain</label>
            <input class="form-control" wire:model.defer="branding.custom_domain" placeholder="portal.yourcompany.com">
        </div>
    </div>
</div>

<button class="btn btn-primary" wire:click="saveBranding">
    <i class="fas fa-save mr-1"></i> Save Branding
</button>

