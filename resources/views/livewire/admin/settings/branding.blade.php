<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
    <!-- Left Column: Logos & Colors -->
    <div class="lg:col-span-3 space-y-6">
        <!-- Logos Card -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="text-base font-semibold text-slate-900">Logos & Images</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Company Logo -->
                    <div class="rounded-xl border border-slate-200 p-4">
                        <h4 class="text-sm font-semibold text-slate-900 mb-3">Company Logo</h4>
                        @if(!empty($branding['logo_path']))
                            <div class="mb-3 p-2 bg-slate-50 rounded-lg inline-block">
                                <img src="{{ asset('storage/' . $branding['logo_path']) }}" alt="Logo" class="max-h-14" onerror="this.style.display='none'">
                            </div>
                        @endif
                        <input type="file" wire:model="logo_upload" class="block w-full text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-900 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-slate-800 file:cursor-pointer file:transition-colors mb-2">
                        <p class="text-xs text-slate-500 mb-3">PNG/JPG/WEBP/SVG up to 2MB.</p>
                        <button type="button" wire:click="uploadLogo" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                            Upload
                        </button>
                    </div>

                    <!-- Login Logo -->
                    <div class="rounded-xl border border-slate-200 p-4">
                        <h4 class="text-sm font-semibold text-slate-900 mb-3">Login Logo</h4>
                        @if(!empty($branding['login_logo_path']))
                            <div class="mb-3 p-2 bg-slate-50 rounded-lg inline-block">
                                <img src="{{ asset('storage/' . $branding['login_logo_path']) }}" alt="Login Logo" class="max-h-14" onerror="this.style.display='none'">
                            </div>
                        @endif
                        <input type="file" wire:model="login_logo_upload" class="block w-full text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-900 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-slate-800 file:cursor-pointer file:transition-colors mb-2">
                        <p class="text-xs text-slate-500 mb-3">PNG/JPG/WEBP/SVG up to 2MB.</p>
                        <button type="button" wire:click="uploadLoginLogo" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                            Upload
                        </button>
                    </div>

                    <!-- Dashboard Logo -->
                    <div class="rounded-xl border border-slate-200 p-4">
                        <h4 class="text-sm font-semibold text-slate-900 mb-3">Dashboard Logo</h4>
                        @if(!empty($branding['dashboard_logo_path']))
                            <div class="mb-3 p-2 bg-slate-50 rounded-lg inline-block">
                                <img src="{{ asset('storage/' . $branding['dashboard_logo_path']) }}" alt="Dashboard Logo" class="max-h-8" onerror="this.style.display='none'">
                            </div>
                        @endif
                        <input type="file" wire:model="dashboard_logo_upload" class="block w-full text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-900 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-slate-800 file:cursor-pointer file:transition-colors mb-2">
                        <p class="text-xs text-slate-500 mb-3">PNG/JPG/WEBP/SVG up to 2MB.</p>
                        <button type="button" wire:click="uploadDashboardLogo" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                            Upload
                        </button>
                    </div>

                    <!-- Login Background -->
                    <div class="rounded-xl border border-slate-200 p-4">
                        <h4 class="text-sm font-semibold text-slate-900 mb-3">Login Background</h4>
                        @if(!empty($branding['login_background_path']))
                            <div class="mb-3 rounded-lg overflow-hidden">
                                <img src="{{ asset('storage/' . $branding['login_background_path']) }}" alt="Login Background" class="h-14 w-full object-cover" onerror="this.style.display='none'">
                            </div>
                        @endif
                        <input type="file" wire:model="login_background_upload" class="block w-full text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-900 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-slate-800 file:cursor-pointer file:transition-colors mb-2">
                        <p class="text-xs text-slate-500 mb-3">PNG/JPG/WEBP up to 5MB.</p>
                        <button type="button" wire:click="uploadLoginBackground" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                            Upload
                        </button>
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
