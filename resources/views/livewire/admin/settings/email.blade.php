<div class="row">
    <!-- Left Column: Mail Provider & Configuration -->
    <div class="col-lg-7">
        <!-- Mail Provider Selection -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-mail-bulk mr-2"></i>Mail Provider</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Select Email Provider</label>
                    <select wire:model.live="email.provider" class="form-control">
                        <option value="sendmail">PHP Mail (Sendmail)</option>
                        <option value="smtp">Custom SMTP</option>
                        <option value="gmail">Gmail SMTP</option>
                        <option value="outlook">Outlook / Office 365</option>
                        <option value="mailgun">Mailgun</option>
                        <option value="brevo">Brevo (Sendinblue)</option>
                    </select>
                    <small class="text-muted">Choose your preferred email delivery method</small>
                </div>

                <!-- Provider Info Callouts -->
                @if(($email['provider'] ?? 'sendmail') === 'sendmail')
                    <div class="callout callout-info">
                        <h5><i class="fas fa-info-circle mr-1"></i> PHP Mail</h5>
                        <p class="mb-0">Uses your server's built-in mail function. Simple but may have deliverability issues. No additional configuration required.</p>
                    </div>
                @elseif(($email['provider'] ?? '') === 'gmail')
                    <div class="callout callout-warning">
                        <h5><i class="fab fa-google mr-1"></i> Gmail SMTP</h5>
                        <p class="mb-2">To use Gmail SMTP, you need to:</p>
                        <ol class="mb-0 pl-3">
                            <li>Enable 2-Factor Authentication on your Google Account</li>
                            <li>Generate an <a href="https://myaccount.google.com/apppasswords" target="_blank">App Password</a></li>
                            <li>Use your Gmail address as username and App Password as password</li>
                        </ol>
                    </div>
                @elseif(($email['provider'] ?? '') === 'outlook')
                    <div class="callout callout-info">
                        <h5><i class="fab fa-microsoft mr-1"></i> Outlook / Office 365</h5>
                        <p class="mb-0">Use your Microsoft/Outlook email address and password. For Office 365 business accounts, you may need to enable SMTP AUTH in your admin settings.</p>
                    </div>
                @elseif(($email['provider'] ?? '') === 'mailgun')
                    <div class="callout callout-success">
                        <h5><i class="fas fa-envelope-open-text mr-1"></i> Mailgun</h5>
                        <p class="mb-0">Professional email delivery service. Get your API key and domain from your <a href="https://app.mailgun.com/" target="_blank">Mailgun Dashboard</a>.</p>
                    </div>
                @elseif(($email['provider'] ?? '') === 'brevo')
                    <div class="callout callout-primary">
                        <h5><i class="fas fa-paper-plane mr-1"></i> Brevo (Sendinblue)</h5>
                        <p class="mb-0">Transactional email service with great deliverability. Get your SMTP credentials from your <a href="https://app.brevo.com/settings/keys/smtp" target="_blank">Brevo Dashboard</a>.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- SMTP Configuration (shown for smtp, gmail, outlook, brevo) -->
        @if(in_array($email['provider'] ?? 'sendmail', ['smtp', 'gmail', 'outlook', 'brevo']))
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-server mr-2"></i>SMTP Configuration</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>SMTP Host</label>
                            @if(($email['provider'] ?? '') === 'gmail')
                                <input type="text" class="form-control" value="smtp.gmail.com" readonly>
                                <input type="hidden" wire:model="email.smtp_host" value="smtp.gmail.com">
                            @elseif(($email['provider'] ?? '') === 'outlook')
                                <input type="text" class="form-control" value="smtp.office365.com" readonly>
                                <input type="hidden" wire:model="email.smtp_host" value="smtp.office365.com">
                            @elseif(($email['provider'] ?? '') === 'brevo')
                                <input type="text" class="form-control" value="smtp-relay.brevo.com" readonly>
                                <input type="hidden" wire:model="email.smtp_host" value="smtp-relay.brevo.com">
                            @else
                                <input type="text" wire:model="email.smtp_host" class="form-control" placeholder="smtp.example.com">
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Port</label>
                            @if(($email['provider'] ?? '') === 'gmail')
                                <input type="number" class="form-control" value="587" readonly>
                                <input type="hidden" wire:model="email.smtp_port" value="587">
                            @elseif(($email['provider'] ?? '') === 'outlook')
                                <input type="number" class="form-control" value="587" readonly>
                                <input type="hidden" wire:model="email.smtp_port" value="587">
                            @elseif(($email['provider'] ?? '') === 'brevo')
                                <input type="number" class="form-control" value="587" readonly>
                                <input type="hidden" wire:model="email.smtp_port" value="587">
                            @else
                                <input type="number" wire:model="email.smtp_port" class="form-control" placeholder="587">
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>
                                @if(($email['provider'] ?? '') === 'brevo')
                                    SMTP Login (Email)
                                @else
                                    Username / Email
                                @endif
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                </div>
                                <input type="text" wire:model="email.smtp_username" class="form-control" placeholder="user@example.com">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>
                                @if(($email['provider'] ?? '') === 'gmail')
                                    App Password
                                @elseif(($email['provider'] ?? '') === 'brevo')
                                    SMTP Key
                                @else
                                    Password
                                @endif
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                </div>
                                <input type="password" wire:model="email.smtp_password" class="form-control" placeholder="••••••••">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Encryption</label>
                    <select wire:model="email.smtp_encryption" class="form-control" @if(in_array($email['provider'] ?? '', ['gmail', 'outlook', 'brevo'])) disabled @endif>
                        <option value="tls">TLS (Recommended)</option>
                        <option value="ssl">SSL</option>
                        <option value="">None</option>
                    </select>
                    @if(in_array($email['provider'] ?? '', ['gmail', 'outlook', 'brevo']))
                        <input type="hidden" wire:model="email.smtp_encryption" value="tls">
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Mailgun Configuration -->
        @if(($email['provider'] ?? '') === 'mailgun')
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cog mr-2"></i>Mailgun Configuration</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Mailgun Domain</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-globe"></i></span>
                        </div>
                        <input type="text" wire:model="email.mailgun_domain" class="form-control" placeholder="mg.yourdomain.com">
                    </div>
                    <small class="text-muted">Your Mailgun sending domain</small>
                </div>
                <div class="form-group">
                    <label>Mailgun API Key</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                        </div>
                        <input type="password" wire:model="email.mailgun_secret" class="form-control" placeholder="key-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                    </div>
                    <small class="text-muted">Your private API key from Mailgun</small>
                </div>
                <div class="form-group">
                    <label>Mailgun Region</label>
                    <select wire:model="email.mailgun_endpoint" class="form-control">
                        <option value="api.mailgun.net">US Region (api.mailgun.net)</option>
                        <option value="api.eu.mailgun.net">EU Region (api.eu.mailgun.net)</option>
                    </select>
                </div>
            </div>
        </div>
        @endif

        <!-- From Address -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-id-card mr-2"></i>From Address</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>From Email</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                </div>
                                <input type="email" wire:model="email.from_address" class="form-control" placeholder="hello@example.com">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>From Name</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-building"></i></span>
                                </div>
                                <input type="text" wire:model="email.from_name" class="form-control" placeholder="Your Company">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Content, Notifications & Testing -->
    <div class="col-lg-5">
        <!-- Email Content -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i>Email Content</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Email Signature</label>
                    <textarea rows="3" wire:model="email.signature" class="form-control" placeholder="Best regards,&#10;Your Team"></textarea>
                </div>
                <div class="form-group">
                    <label>Email Template</label>
                    <div class="btn-group mb-2">
                        <button type="button" wire:click="openEmailBuilder" class="btn btn-outline-secondary">
                            <i class="fas fa-magic mr-1"></i> Drag-and-Drop Builder
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-toggle="collapse" data-target="#rawTemplateCollapse">
                            <i class="fas fa-code mr-1"></i> Raw HTML
                        </button>
                    </div>
                    <div class="collapse" id="rawTemplateCollapse">
                        <textarea rows="6" wire:model="email.template_html" class="form-control font-monospace" placeholder="<html>...</html>"></textarea>
                    </div>
                    <small class="text-muted">Customize your email template design</small>
                </div>
            </div>
        </div>

        <!-- Notification Preferences -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bell mr-2"></i>Notification Preferences</h3>
            </div>
            <div class="card-body">
                <div class="custom-control custom-switch mb-2">
                    <input type="checkbox" class="custom-control-input" id="eventsInvoicePaid" wire:model="email.events_invoice_paid">
                    <label class="custom-control-label" for="eventsInvoicePaid">Invoice paid notifications</label>
                </div>
                <div class="custom-control custom-switch mb-2">
                    <input type="checkbox" class="custom-control-input" id="eventsRequestCreated" wire:model="email.events_request_created">
                    <label class="custom-control-label" for="eventsRequestCreated">Request created notifications</label>
                </div>
                <div class="custom-control custom-switch mb-2">
                    <input type="checkbox" class="custom-control-input" id="eventsContractSigned" wire:model="email.events_contract_signed">
                    <label class="custom-control-label" for="eventsContractSigned">Contract signed notifications</label>
                </div>
            </div>
        </div>

        <!-- Test Email -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-paper-plane mr-2"></i>Test Email</h3>
            </div>
            <div class="card-body">
                <div class="input-group">
                    <input type="email" wire:model="test_email_to" class="form-control" placeholder="test@example.com">
                    <div class="input-group-append">
                        <button type="button" wire:click="sendTestEmail" class="btn btn-info" wire:loading.attr="disabled" wire:target="sendTestEmail">
                            <span wire:loading.remove wire:target="sendTestEmail"><i class="fas fa-paper-plane mr-1"></i> Send</span>
                            <span wire:loading wire:target="sendTestEmail"><i class="fas fa-spinner fa-spin mr-1"></i> Sending...</span>
                        </button>
                    </div>
                </div>
                <small class="text-muted">Send a test email to verify your configuration</small>
            </div>
        </div>

        <!-- Provider Quick Reference -->
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Quick Reference</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Provider</th>
                            <th>Host</th>
                            <th>Port</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><i class="fab fa-google text-danger mr-1"></i> Gmail</td>
                            <td><code>smtp.gmail.com</code></td>
                            <td>587</td>
                        </tr>
                        <tr>
                            <td><i class="fab fa-microsoft text-primary mr-1"></i> Outlook</td>
                            <td><code>smtp.office365.com</code></td>
                            <td>587</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-envelope text-warning mr-1"></i> Mailgun</td>
                            <td><code>smtp.mailgun.org</code></td>
                            <td>587</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-paper-plane text-info mr-1"></i> Brevo</td>
                            <td><code>smtp-relay.brevo.com</code></td>
                            <td>587</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Save Button -->
<div class="mt-6">
    <button type="button" wire:click="saveEmail" wire:loading.attr="disabled" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors flex items-center gap-2">
        <span wire:loading.remove wire:target="saveEmail">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z" />
            </svg>
            Save Email Settings
        </span>
        <span wire:loading wire:target="saveEmail">
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Saving...
        </span>
    </button>
</div>

<!-- Drag & drop email builder modal -->
<div class="modal fade" id="emailBuilderModal" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-xl" role="document" style="max-width: 95vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-magic mr-2"></i>Email Template Builder</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0" style="height: 70vh;" wire:ignore>
                <div id="email-builder" style="height: 100%;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" id="saveEmailTemplateBtn" class="btn btn-primary">
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
                    try { unlayer.loadDesign({ body: { rows: [] } }); } catch (e) {}
                }
            }

            document.addEventListener('livewire:init', () => {
                Livewire.on('open-email-builder', (data) => {
                    const design = data.design || null;
                    $('#emailBuilderModal').modal('show');
                    setTimeout(function () { initEditor(design); }, 150);
                });
            });

            document.addEventListener('click', function (evt) {
                if (evt.target && evt.target.id === 'saveEmailTemplateBtn') {
                    if (!initialized) return;
                    unlayer.exportHtml(function (data) {
                        const design = data.design;
                        const html = data.html;
                        @this.call('saveEmailTemplate', design, html);
                        $('#emailBuilderModal').modal('hide');
                    });
                }
            });
        })();
    </script>
@endpush
