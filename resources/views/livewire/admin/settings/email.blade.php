<form wire:submit.prevent="saveEmail" class="vstack gap-3">
    <div>
        <div class="h3 mb-1">SMTP configuration</div>
        <div class="text-muted small">Saved to the database. Password is stored encrypted.</div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <label class="form-label">From address</label>
            <input class="form-control" wire:model.defer="state.mail.from_address">
            @error('state.mail.from_address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">From name</label>
            <input class="form-control" wire:model.defer="state.mail.from_name">
            @error('state.mail.from_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">SMTP host</label>
            <input class="form-control" wire:model.defer="state.mail.smtp.host">
            @error('state.mail.smtp.host')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Port</label>
            <input type="number" class="form-control" wire:model.defer="state.mail.smtp.port">
            @error('state.mail.smtp.port')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Encryption</label>
            <select class="form-select" wire:model.defer="state.mail.smtp.encryption">
                <option value="">None</option>
                <option value="tls">TLS</option>
                <option value="ssl">SSL</option>
            </select>
            @error('state.mail.smtp.encryption')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Username</label>
            <input class="form-control" wire:model.defer="state.mail.smtp.username">
            @error('state.mail.smtp.username')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Password</label>
            <input class="form-control" type="password" wire:model.defer="state.mail.smtp.password" autocomplete="new-password">
            @error('state.mail.smtp.password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>

    <hr class="my-2">

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">Email signature</div>
            <textarea class="form-control" rows="6" wire:model.defer="state.mail.signature" placeholder="Best regards,&#10;Company name"></textarea>
            @error('state.mail.signature')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">Notification preferences</div>
            <div class="text-muted small mb-2">Choose which events trigger email notifications by default.</div>
            <div class="vstack gap-2">
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" wire:model.defer="state.mail.notify_events.request_created">
                    <span class="form-check-label">Request created</span>
                </label>
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" wire:model.defer="state.mail.notify_events.request_updated">
                    <span class="form-check-label">Request updated</span>
                </label>
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" wire:model.defer="state.mail.notify_events.invoice_created">
                    <span class="form-check-label">Invoice created</span>
                </label>
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" wire:model.defer="state.mail.notify_events.payment_received">
                    <span class="form-check-label">Payment received</span>
                </label>
            </div>
        </div>
    </div>

    <hr class="my-2">

    <div>
        <div class="h3 mb-1">Email templates editor (drag-and-drop)</div>
        <div class="text-muted small">Minimal block-based editor: drag blocks to reorder, then edit HTML content.</div>
    </div>

    <div class="card bg-transparent border" id="email-blocks-editor">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button class="btn btn-outline-secondary btn-sm" type="button" wire:click="addEmailBlock('header')">Add header block</button>
                <button class="btn btn-outline-secondary btn-sm" type="button" wire:click="addEmailBlock('body')">Add body block</button>
                <button class="btn btn-outline-secondary btn-sm" type="button" wire:click="addEmailBlock('footer')">Add footer block</button>
            </div>

            <div class="vstack gap-2">
                @forelse($emailTemplateBlocks as $i => $block)
                    <div class="border rounded p-2 email-block"
                         draggable="true"
                         data-block-index="{{ $i }}">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                            <div class="fw-semibold">Block #{{ $i + 1 }} · {{ strtoupper($block['type'] ?? 'BODY') }}</div>
                            <button class="btn btn-outline-danger btn-sm" type="button" wire:click="removeEmailBlock({{ $i }})">Remove</button>
                        </div>
                        <textarea class="form-control font-monospace" rows="5" wire:model.defer="emailTemplateBlocks.{{ $i }}.content"></textarea>
                    </div>
                @empty
                    <div class="text-muted">No blocks yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <hr class="my-2">

    <div>
        <div class="h3 mb-1">Test email</div>
        <div class="text-muted small">Uses the SMTP values currently filled in above (not necessarily saved yet).</div>
    </div>

    <div class="row g-3 align-items-end">
        <div class="col-12 col-md-6">
            <label class="form-label">Send test email to</label>
            <input class="form-control" wire:model.defer="testEmailTo" placeholder="you@example.com">
            @error('testEmailTo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-6 d-flex gap-2">
            <button class="btn btn-outline-primary" type="button" wire:click="sendTestEmail">Send test email</button>
            <button class="btn btn-primary ms-auto" type="submit">Save email settings</button>
        </div>
    </div>
</form>

@push('scripts')
    <script>
        (function () {
            let draggingIndex = null;

            function getBlocks() {
                return Array.from(document.querySelectorAll('#email-blocks-editor .email-block'));
            }

            function bindDnD() {
                const blocks = getBlocks();
                blocks.forEach((el) => {
                    el.addEventListener('dragstart', (e) => {
                        draggingIndex = parseInt(el.dataset.blockIndex || '0', 10);
                        e.dataTransfer && (e.dataTransfer.effectAllowed = 'move');
                    });
                    el.addEventListener('dragover', (e) => e.preventDefault());
                    el.addEventListener('drop', (e) => {
                        e.preventDefault();
                        const dropIndex = parseInt(el.dataset.blockIndex || '0', 10);
                        if (draggingIndex === null || dropIndex === draggingIndex) return;

                        const order = Array.from({ length: blocks.length }, (_, idx) => idx);
                        const [moved] = order.splice(draggingIndex, 1);
                        order.splice(dropIndex, 0, moved);

                        draggingIndex = null;
                        @this.call('reorderEmailBlocks', order);
                    });
                });
            }

            document.addEventListener('livewire:init', () => {
                bindDnD();
                Livewire.hook('morph.updated', () => bindDnD());
            });
        })();
    </script>
@endpush

