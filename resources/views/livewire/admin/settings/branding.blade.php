<form wire:submit.prevent="saveBranding" class="vstack gap-3">
    <div>
        <div class="h3 mb-1">Branding</div>
        <div class="text-muted small">Logo, brand colors, invoice template style, email header/footer, custom domain.</div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">Company logo</div>
            <div class="text-muted small mb-2">Upload a logo (PNG/JPG). Stored on the public disk.</div>

            @php($logoPath = $state['branding.logo_path'] ?? '')
            @if($logoPath)
                <div class="mb-2">
                    <div class="text-muted small">Current logo</div>
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($logoPath) }}" alt="Logo" style="max-height: 60px;">
                </div>
            @endif

            <input type="file" class="form-control" wire:model="logoUpload" accept="image/*">
            @error('logoUpload')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">Brand colors</div>
            <div class="row g-2">
                <div class="col-12 col-md-4">
                    <label class="form-label">Primary</label>
                    <input type="color" class="form-control form-control-color" wire:model.defer="state.branding.primary">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Secondary</label>
                    <input type="color" class="form-control form-control-color" wire:model.defer="state.branding.secondary">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Accent</label>
                    <input type="color" class="form-control form-control-color" wire:model.defer="state.branding.accent">
                </div>
            </div>
        </div>
    </div>

    <hr class="my-2">

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">Invoice template design</div>
            <select class="form-select" wire:model.defer="state.branding.invoice_template">
                <option value="default">Default</option>
                <option value="modern">Modern</option>
                <option value="minimal">Minimal</option>
            </select>
            @error('state.branding.invoice_template')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">Custom domain for client portal</div>
            <input class="form-control" wire:model.defer="state.branding.custom_domain" placeholder="portal.example.com">
            @error('state.branding.custom_domain')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            <div class="text-muted small mt-1">DNS + SSL setup is external to this app.</div>
        </div>
    </div>

    <hr class="my-2">

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">Email header customization (HTML)</div>
            <textarea class="form-control font-monospace" rows="8" wire:model.defer="state.branding.email_header_html" placeholder="<div>...</div>"></textarea>
        </div>
        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">Email footer customization (HTML)</div>
            <textarea class="form-control font-monospace" rows="8" wire:model.defer="state.branding.email_footer_html" placeholder="<div>...</div>"></textarea>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button class="btn btn-primary" type="submit">Save branding</button>
    </div>
</form>

