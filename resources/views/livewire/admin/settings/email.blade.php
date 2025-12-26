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
            <label class="mb-1">Template HTML (placeholder for drag-and-drop editor)</label>
            <textarea class="form-control" rows="8" wire:model.defer="email.template_html"></textarea>
            <small class="text-muted">You can later swap this UI for a true drag-and-drop builder; the HTML is stored in settings.</small>
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

