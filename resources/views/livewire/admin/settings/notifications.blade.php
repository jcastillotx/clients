<form wire:submit.prevent="saveNotifications" class="vstack gap-3">
    <div>
        <div class="h3 mb-1">Notification settings</div>
        <div class="text-muted small">Admin defaults, client defaults, Slack/Teams, push, SMS (Twilio optional).</div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">Admin notification preferences</div>
            <div class="vstack gap-2">
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" wire:model.defer="state.notify.admin_events.new_request">
                    <span class="form-check-label">New request</span>
                </label>
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" wire:model.defer="state.notify.admin_events.payment_failed">
                    <span class="form-check-label">Payment failed</span>
                </label>
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" wire:model.defer="state.notify.admin_events.storage_quota_80">
                    <span class="form-check-label">Storage quota warning (80%)</span>
                </label>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">Client default preferences</div>
            <div class="vstack gap-2">
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" wire:model.defer="state.notify.client_defaults.request_updates">
                    <span class="form-check-label">Request updates</span>
                </label>
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" wire:model.defer="state.notify.client_defaults.invoice_updates">
                    <span class="form-check-label">Invoice updates</span>
                </label>
            </div>
        </div>
    </div>

    <hr class="my-2">

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">Slack integration</div>
            <label class="form-label">Incoming webhook URL</label>
            <input class="form-control" wire:model.defer="state.notify.slack.webhook" placeholder="https://hooks.slack.com/...">
        </div>
        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">Teams integration</div>
            <label class="form-label">Incoming webhook URL</label>
            <input class="form-control" wire:model.defer="state.notify.teams.webhook" placeholder="https://outlook.office.com/webhook/...">
        </div>
    </div>

    <hr class="my-2">

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">Push notifications</div>
            <label class="form-check mt-2">
                <input class="form-check-input" type="checkbox" wire:model.defer="state.notify.push.enabled">
                <span class="form-check-label">Enable push notifications</span>
            </label>
            <div class="text-muted small mt-1">Provider configuration is application-specific.</div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">SMS alerts (Twilio optional)</div>
            <label class="form-check mt-2">
                <input class="form-check-input" type="checkbox" wire:model.defer="state.notify.sms.enabled">
                <span class="form-check-label">Enable SMS alerts</span>
            </label>
            <div class="row g-2 mt-2">
                <div class="col-12">
                    <label class="form-label">Twilio SID</label>
                    <input class="form-control" wire:model.defer="state.notify.sms.twilio_sid">
                </div>
                <div class="col-12">
                    <label class="form-label">Twilio token</label>
                    <input class="form-control" type="password" wire:model.defer="state.notify.sms.twilio_token" autocomplete="new-password">
                    <div class="text-muted small mt-1">Stored encrypted.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">From number</label>
                    <input class="form-control" wire:model.defer="state.notify.sms.from" placeholder="+15551234567">
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button class="btn btn-primary" type="submit">Save notification settings</button>
    </div>
</form>

