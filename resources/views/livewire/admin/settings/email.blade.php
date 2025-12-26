<div class="row">
    <div class="col-md-6">
        <h5 class="mb-3">SMTP Configuration</h5>
        <div class="form-group">
            <label class="mb-1">Host</label>
            <input class="form-control" wire:model.defer="email.smtp_host">
        </div>
        <div class="form-group">
            <label class="mb-1">Port</label>
            <input type="number" class="form-control" wire:model.defer="email.smtp_port">
        </div>
        <div class="form-group">
            <label class="mb-1">Username</label>
            <input class="form-control" wire:model.defer="email.smtp_username">
        </div>
        <div class="form-group">
            <label class="mb-1">Password</label>
            <input type="password" class="form-control" wire:model.defer="email.smtp_password">
        </div>
        <div class="form-group">
            <label class="mb-1">Encryption</label>
            <select class="form-control" wire:model.defer="email.smtp_encryption">
                <option value="tls">TLS</option>
                <option value="ssl">SSL</option>
                <option value="">None</option>
            </select>
        </div>

        <h5 class="mt-4 mb-3">From</h5>
        <div class="form-group">
            <label class="mb-1">From address</label>
            <input class="form-control" wire:model.defer="email.from_address">
        </div>
        <div class="form-group">
            <label class="mb-1">From name</label>
            <input class="form-control" wire:model.defer="email.from_name">
        </div>
    </div>

    <div class="col-md-6">
        <h5 class="mb-3">Email content</h5>
        <div class="form-group">
            <label class="mb-1">Signature</label>
            <textarea class="form-control" rows="3" wire:model.defer="email.signature"></textarea>
        </div>
        <div class="form-group">
            <label class="mb-1">Email template</label>
            <div class="d-flex" style="gap: 8px;">
                <button class="btn btn-outline-primary" wire:click="openEmailBuilder">
                    <i class="fas fa-magic mr-1"></i> Open drag-and-drop builder
                </button>
                <button class="btn btn-outline-secondary" type="button" data-toggle="collapse" data-target="#rawTemplate">
                    <i class="fas fa-code mr-1"></i> View raw HTML
                </button>
            </div>
            <div class="collapse mt-2" id="rawTemplate">
                <textarea class="form-control" rows="8" wire:model.defer="email.template_html"></textarea>
            </div>
            <small class="text-muted">Builder saves both design JSON + HTML. Raw HTML is optional.</small>
        </div>

        <h5 class="mt-4 mb-2">Notification preferences</h5>
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="evt_invoice_paid" wire:model.defer="email.events_invoice_paid">
            <label class="custom-control-label" for="evt_invoice_paid">Invoice paid</label>
        </div>
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="evt_request_created" wire:model.defer="email.events_request_created">
            <label class="custom-control-label" for="evt_request_created">Request created</label>
        </div>
        <div class="custom-control custom-checkbox mb-3">
            <input type="checkbox" class="custom-control-input" id="evt_contract_signed" wire:model.defer="email.events_contract_signed">
            <label class="custom-control-label" for="evt_contract_signed">Contract signed</label>
        </div>

        <h5 class="mt-4 mb-2">Test email</h5>
        <div class="input-group">
            <input class="form-control" placeholder="test@example.com" wire:model.defer="test_email_to">
            <div class="input-group-append">
                <button class="btn btn-outline-primary" wire:click="sendTestEmail">Send</button>
            </div>
        </div>
        <small class="text-muted">Requires mailer configuration in `.env`.</small>
    </div>
</div>

<button class="btn btn-primary" wire:click="saveEmail">
    <i class="fas fa-save mr-1"></i> Save Email Settings
</button>

<!-- Drag & drop email builder modal -->
<div class="modal fade" id="emailBuilderModal" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-xl" role="document" style="max-width: 95%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Email Template Builder</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0" wire:ignore>
                <div id="email-builder" style="height: 75vh;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveEmailTemplateBtn">
                    <i class="fas fa-save mr-1"></i> Save Template
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
                    // Start with blank template if no design exists.
                    try { unlayer.loadDesign({ body: { rows: [] } }); } catch (e) {}
                }
            }

            window.addEventListener('open-email-builder', function (e) {
                const design = (e.detail && e.detail.design) ? e.detail.design : null;
                $('#emailBuilderModal').modal('show');
                // Give modal time to render container
                setTimeout(function () { initEditor(design); }, 150);
            });

            document.addEventListener('click', function (evt) {
                if (evt.target && evt.target.id === 'saveEmailTemplateBtn') {
                    if (!initialized) return;
                    unlayer.exportHtml(function (data) {
                        const design = data.design;
                        const html = data.html;
                        // Save immediately server-side
                        @this.call('saveEmailTemplate', design, html);
                        $('#emailBuilderModal').modal('hide');
                    });
                }
            });
        })();
    </script>
@endpush

