<div class="row">
    <div class="col-md-6">
        <h5 class="mb-3">Defaults</h5>
        <div class="custom-control custom-switch mb-2">
            <input type="checkbox" class="custom-control-input" id="notif_admin_email" wire:model.defer="notifications.admin_email">
            <label class="custom-control-label" for="notif_admin_email">Admin email notifications enabled</label>
        </div>
        <div class="custom-control custom-switch mb-3">
            <input type="checkbox" class="custom-control-input" id="notif_client_email_default" wire:model.defer="notifications.client_email_default">
            <label class="custom-control-label" for="notif_client_email_default">Client email notifications default</label>
        </div>

        <h5 class="mt-4 mb-3">Slack / Teams</h5>
        <div class="form-group">
            <label class="mb-1">Slack webhook URL</label>
            <input class="form-control" wire:model.defer="notifications.slack_webhook_url">
        </div>
        <div class="form-group">
            <label class="mb-1">Teams webhook URL</label>
            <input class="form-control" wire:model.defer="notifications.teams_webhook_url">
        </div>
        <small class="text-muted">Integration execution is stored/configured here; event wiring can be added per module.</small>
    </div>

    <div class="col-md-6">
        <h5 class="mb-3">Push / SMS</h5>
        <div class="custom-control custom-switch mb-2">
            <input type="checkbox" class="custom-control-input" id="push_enabled" wire:model.defer="notifications.push_enabled">
            <label class="custom-control-label" for="push_enabled">Push notifications enabled</label>
        </div>
        <div class="custom-control custom-switch mb-3">
            <input type="checkbox" class="custom-control-input" id="sms_enabled" wire:model.defer="notifications.sms_enabled">
            <label class="custom-control-label" for="sms_enabled">SMS alerts enabled</label>
        </div>

        <h6 class="mt-3">Twilio (optional)</h6>
        <div class="form-group">
            <label class="mb-1">Account SID</label>
            <input class="form-control" wire:model.defer="notifications.twilio_sid">
        </div>
        <div class="form-group">
            <label class="mb-1">Auth token</label>
            <input type="password" class="form-control" wire:model.defer="notifications.twilio_token">
        </div>
        <div class="form-group">
            <label class="mb-1">From number</label>
            <input class="form-control" wire:model.defer="notifications.twilio_from" placeholder="+15551234567">
        </div>
    </div>
</div>

<button class="btn btn-primary" wire:click="saveNotifications">
    <i class="fas fa-save mr-1"></i> Save Notification Settings
</button>

