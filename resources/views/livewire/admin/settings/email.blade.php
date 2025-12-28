<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Left Column: SMTP & From -->
    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="text-base font-semibold text-slate-900">SMTP Configuration</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Host</label>
                    <input type="text" wire:model.defer="email.smtp_host" placeholder="smtp.example.com" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Port</label>
                    <input type="number" wire:model.defer="email.smtp_port" placeholder="587" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Username</label>
                    <input type="text" wire:model.defer="email.smtp_username" placeholder="user@example.com" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Password</label>
                    <input type="password" wire:model.defer="email.smtp_password" placeholder="••••••••" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Encryption</label>
                    <select wire:model.defer="email.smtp_encryption" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        <option value="tls">TLS</option>
                        <option value="ssl">SSL</option>
                        <option value="">None</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="text-base font-semibold text-slate-900">From Address</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">From Email</label>
                    <input type="email" wire:model.defer="email.from_address" placeholder="hello@example.com" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">From Name</label>
                    <input type="text" wire:model.defer="email.from_name" placeholder="Your Company" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Content & Notifications -->
    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="text-base font-semibold text-slate-900">Email Content</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Signature</label>
                    <textarea rows="3" wire:model.defer="email.signature" placeholder="Best regards,&#10;Your Team" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors resize-y"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Email Template</label>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="openEmailBuilder" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
                            </svg>
                            Drag-and-Drop Builder
                        </button>
                        <button type="button" onclick="document.getElementById('rawTemplate').classList.toggle('hidden')" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                            View Raw HTML
                        </button>
                    </div>
                    <div id="rawTemplate" class="hidden mt-3">
                        <textarea rows="6" wire:model.defer="email.template_html" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 font-mono placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors resize-y"></textarea>
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500">Builder saves both design JSON + HTML. Raw HTML is optional.</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="text-base font-semibold text-slate-900">Notification Preferences</h3>
            </div>
            <div class="p-6 space-y-3">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" wire:model.defer="email.events_invoice_paid" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                    <span class="text-sm text-slate-700">Invoice paid</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" wire:model.defer="email.events_request_created" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                    <span class="text-sm text-slate-700">Request created</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" wire:model.defer="email.events_contract_signed" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                    <span class="text-sm text-slate-700">Contract signed</span>
                </label>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="text-base font-semibold text-slate-900">Test Email</h3>
            </div>
            <div class="p-6">
                <div class="flex">
                    <input type="email" wire:model.defer="test_email_to" placeholder="test@example.com" class="flex-1 rounded-l-xl border border-r-0 border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                    <button type="button" wire:click="sendTestEmail" class="rounded-r-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
                        Send Test
                    </button>
                </div>
                <p class="mt-1.5 text-xs text-slate-500">Requires mailer configuration in `.env`.</p>
            </div>
        </div>
    </div>
</div>

<div class="mt-6">
    <button type="button" wire:click="saveEmail" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
            <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z" />
        </svg>
        Save Email Settings
    </button>
</div>

<!-- Drag & drop email builder modal -->
<div class="fixed inset-0 z-50 hidden" id="emailBuilderModal" role="dialog" aria-modal="true" wire:ignore.self>
    <div class="flex items-center justify-center min-h-screen">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="$('#emailBuilderModal').addClass('hidden')"></div>
        <div class="relative w-full max-w-[95vw] h-[85vh] bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Email Template Builder</h3>
                <button type="button" onclick="$('#emailBuilderModal').addClass('hidden')" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div class="h-[calc(100%-120px)]" wire:ignore>
                <div id="email-builder" style="height: 100%;"></div>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-3">
                <button type="button" onclick="$('#emailBuilderModal').addClass('hidden')" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                    Close
                </button>
                <button type="button" id="saveEmailTemplateBtn" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition-colors flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z" />
                    </svg>
                    Save Template
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <!-- Unlayer Email Editor (drag-and-drop) -->
    <script src="https://editor.unlayer.com/embed.js"></script>
    <script>
        (function () {
            let initialized = false;
            let pendingDesign = null;

            function initEditor(design) {
                pendingDesign = design || null;
                if (!initialized) {
                    unlayer.init({
                        id: 'email-builder',
                        displayMode: 'email'
                    });
                    initialized = true;
                }
                if (pendingDesign) {
                    try { unlayer.loadDesign(pendingDesign); } catch (e) {}
                } else {
                    try { unlayer.loadDesign({ body: { rows: [] } }); } catch (e) {}
                }
            }

            window.addEventListener('open-email-builder', function (e) {
                const design = (e.detail && e.detail.design) ? e.detail.design : null;
                document.getElementById('emailBuilderModal').classList.remove('hidden');
                setTimeout(function () { initEditor(design); }, 150);
            });

            document.addEventListener('click', function (evt) {
                if (evt.target && evt.target.id === 'saveEmailTemplateBtn') {
                    if (!initialized) return;
                    unlayer.exportHtml(function (data) {
                        const design = data.design;
                        const html = data.html;
                        @this.call('saveEmailTemplate', design, html);
                        document.getElementById('emailBuilderModal').classList.add('hidden');
                    });
                }
            });
        })();
    </script>
@endpush
