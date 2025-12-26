<div class="row">
    <div class="col-md-6">
        <h5 class="mb-3">Company Information</h5>
        <div class="form-group">
            <label class="mb-1">Company name</label>
            <input class="form-control" wire:model.defer="general.company_name">
        </div>
        <div class="form-group">
            <label class="mb-1">Address</label>
            <textarea class="form-control" rows="3" wire:model.defer="general.address"></textarea>
        </div>
        <div class="form-group">
            <label class="mb-1">Phone</label>
            <input class="form-control" wire:model.defer="general.phone">
        </div>
        <div class="form-group">
            <label class="mb-1">Website</label>
            <input class="form-control" wire:model.defer="general.website">
        </div>
    </div>

    <div class="col-md-6">
        <h5 class="mb-3">Preferences</h5>
        <div class="form-group">
            <label class="mb-1">Timezone</label>
            <input class="form-control" wire:model.defer="general.timezone" placeholder="e.g. UTC, America/New_York">
        </div>
        <div class="form-group">
            <label class="mb-1">Business hours</label>
            <input class="form-control" wire:model.defer="general.business_hours">
        </div>
        <div class="form-group">
            <label class="mb-1">Default currency</label>
            <input class="form-control" wire:model.defer="general.currency" placeholder="e.g. USD">
        </div>
        <div class="form-group">
            <label class="mb-1">Date format</label>
            <input class="form-control" wire:model.defer="general.date_format" placeholder="e.g. M d, Y">
        </div>
        <div class="form-group">
            <label class="mb-1">Time format</label>
            <input class="form-control" wire:model.defer="general.time_format" placeholder="e.g. h:i A">
        </div>
        <div class="form-group">
            <label class="mb-1">Language</label>
            <input class="form-control" wire:model.defer="general.language" placeholder="e.g. en">
        </div>
    </div>
</div>

<button class="btn btn-primary" wire:click="saveGeneral">
    <i class="fas fa-save mr-1"></i> Save General Settings
</button>

