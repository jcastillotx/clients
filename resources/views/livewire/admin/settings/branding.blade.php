<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
    <!-- Left Column: Logos & Colors -->
    <div class="lg:col-span-3 space-y-6">
        <!-- Logos Card -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="text-base font-semibold text-slate-900">Logos & Images</h3>
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
                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="uploadLogo" wire:loading.attr="disabled" wire:target="logo_upload,uploadLogo">
                                <span wire:loading.remove wire:target="logo_upload,uploadLogo"><i class="fas fa-upload mr-1"></i> Upload</span>
                                <span wire:loading wire:target="logo_upload"><i class="fas fa-spinner fa-spin mr-1"></i> Uploading…</span>
                                <span wire:loading wire:target="uploadLogo"><i class="fas fa-spinner fa-spin mr-1"></i> Saving…</span>
                            </button>
                            @error('logo') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
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
                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="uploadLoginLogo" wire:loading.attr="disabled" wire:target="login_logo_upload,uploadLoginLogo">
                                <span wire:loading.remove wire:target="login_logo_upload,uploadLoginLogo"><i class="fas fa-upload mr-1"></i> Upload</span>
                                <span wire:loading wire:target="login_logo_upload"><i class="fas fa-spinner fa-spin mr-1"></i> Uploading…</span>
                                <span wire:loading wire:target="uploadLoginLogo"><i class="fas fa-spinner fa-spin mr-1"></i> Saving…</span>
                            </button>
                            @error('logo') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
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
                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="uploadDashboardLogo" wire:loading.attr="disabled" wire:target="dashboard_logo_upload,uploadDashboardLogo">
                                <span wire:loading.remove wire:target="dashboard_logo_upload,uploadDashboardLogo"><i class="fas fa-upload mr-1"></i> Upload</span>
                                <span wire:loading wire:target="dashboard_logo_upload"><i class="fas fa-spinner fa-spin mr-1"></i> Uploading…</span>
                                <span wire:loading wire:target="uploadDashboardLogo"><i class="fas fa-spinner fa-spin mr-1"></i> Saving…</span>
                            </button>
                            @error('logo') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
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
                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="uploadLoginBackground" wire:loading.attr="disabled" wire:target="login_background_upload,uploadLoginBackground">
                                <span wire:loading.remove wire:target="login_background_upload,uploadLoginBackground"><i class="fas fa-upload mr-1"></i> Upload</span>
                                <span wire:loading wire:target="login_background_upload"><i class="fas fa-spinner fa-spin mr-1"></i> Uploading…</span>
                                <span wire:loading wire:target="uploadLoginBackground"><i class="fas fa-spinner fa-spin mr-1"></i> Saving…</span>
                            </button>
                            @error('bg') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colors Card -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="text-base font-semibold text-slate-900">Colors</h3>
            </div>
            <div class="p-6 space-y-6">
                <div>
                    <h4 class="text-sm font-semibold text-slate-700 mb-3">Brand Colors</h4>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Primary</label>
                            <input type="color" wire:model.defer="branding.color_primary" class="w-full h-10 rounded-xl border border-slate-300 cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Secondary</label>
                            <input type="color" wire:model.defer="branding.color_secondary" class="w-full h-10 rounded-xl border border-slate-300 cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Accent</label>
                            <input type="color" wire:model.defer="branding.color_accent" class="w-full h-10 rounded-xl border border-slate-300 cursor-pointer">
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-200 pt-6">
                    <h4 class="text-sm font-semibold text-slate-700 mb-3">Button Colors</h4>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Primary</label>
                            <input type="color" wire:model.defer="branding.button_primary" class="w-full h-10 rounded-xl border border-slate-300 cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Primary Hover</label>
                            <input type="color" wire:model.defer="branding.button_primary_hover" class="w-full h-10 rounded-xl border border-slate-300 cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Secondary</label>
                            <input type="color" wire:model.defer="branding.button_secondary" class="w-full h-10 rounded-xl border border-slate-300 cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Secondary Hover</label>
                            <input type="color" wire:model.defer="branding.button_secondary_hover" class="w-full h-10 rounded-xl border border-slate-300 cursor-pointer">
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">These update the global button styling across the site.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: HTML Injection & Templates -->
    <div class="lg:col-span-2 space-y-6">
        <!-- HTML Injection Card -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="text-base font-semibold text-slate-900">HTML Injection</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Header HTML (inside &lt;head&gt;)</label>
                    <textarea rows="4" wire:model.defer="branding.site_header_html" placeholder="Analytics, meta tags, verification codes…" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 font-mono placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors resize-y"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Footer HTML (before &lt;/body&gt;)</label>
                    <textarea rows="4" wire:model.defer="branding.site_footer_html" placeholder="Chat widgets, scripts…" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 font-mono placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors resize-y"></textarea>
                </div>
                <p class="text-xs text-slate-500">This HTML is injected on all pages (client + admin).</p>
            </div>
        </div>

        <!-- Templates & Domain Card -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="text-base font-semibold text-slate-900">Templates & Domain</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Invoice Template</label>
                    <select wire:model.defer="branding.invoice_template" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        <option value="default">Default</option>
                        <option value="modern">Modern</option>
                        <option value="minimal">Minimal</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Email Header HTML</label>
                    <textarea rows="3" wire:model.defer="branding.email_header_html" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 font-mono placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors resize-y"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Email Footer HTML</label>
                    <textarea rows="3" wire:model.defer="branding.email_footer_html" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 font-mono placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors resize-y"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Custom Domain</label>
                    <input type="text" wire:model.defer="branding.custom_domain" placeholder="portal.yourcompany.com" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
            </div>
        </div>

        <button type="button" wire:click="saveBranding" wire:loading.attr="disabled" class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors flex items-center justify-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z" />
            </svg>
            <span wire:loading.remove wire:target="saveBranding">Save Branding</span>
            <span wire:loading wire:target="saveBranding">Saving…</span>
        </button>
    </div>
</div>
