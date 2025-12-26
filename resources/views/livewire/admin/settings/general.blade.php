<form wire:submit.prevent="saveGeneral" class="vstack gap-3">
    <div>
        <div class="h3 mb-1">Company information</div>
        <div class="text-muted small">Name, address, phone, website.</div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <label class="form-label">Company name</label>
            <input class="form-control" wire:model.defer="state.company.name">
            @error('state.company.name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Website</label>
            <input class="form-control" wire:model.defer="state.company.website" placeholder="https://example.com">
            @error('state.company.website')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Phone</label>
            <input class="form-control" wire:model.defer="state.company.phone">
            @error('state.company.phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label">Address</label>
            <textarea class="form-control" rows="3" wire:model.defer="state.company.address"></textarea>
            @error('state.company.address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>

    <hr class="my-2">

    <div>
        <div class="h3 mb-1">Business hours &amp; locale</div>
        <div class="text-muted small">Timezone, currency, date/time formats, language.</div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-4">
            <label class="form-label">Timezone</label>
            <input class="form-control" wire:model.defer="state.business.timezone" placeholder="UTC">
            @error('state.business.timezone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label">Default currency</label>
            <input class="form-control" wire:model.defer="state.locale.default_currency" placeholder="USD">
            @error('state.locale.default_currency')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label">Language</label>
            <input class="form-control" wire:model.defer="state.locale.language" placeholder="en">
            @error('state.locale.language')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Date format</label>
            <input class="form-control" wire:model.defer="state.locale.date_format" placeholder="Y-m-d">
            @error('state.locale.date_format')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Time format</label>
            <input class="form-control" wire:model.defer="state.locale.time_format" placeholder="H:i">
            @error('state.locale.time_format')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="card bg-transparent border">
        <div class="card-body">
            <div class="fw-semibold mb-2">Business hours</div>
            @php($days = ['mon'=>'Mon','tue'=>'Tue','wed'=>'Wed','thu'=>'Thu','fri'=>'Fri','sat'=>'Sat','sun'=>'Sun'])
            <div class="row g-2">
                @foreach($days as $k => $label)
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="border rounded p-2">
                            <div class="fw-semibold mb-1">{{ $label }}</div>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="time" class="form-control" wire:model.defer="state.business.hours.{{ $k }}.0" @disabled(empty($state['business.hours'][$k]))>
                                <span class="text-muted">to</span>
                                <input type="time" class="form-control" wire:model.defer="state.business.hours.{{ $k }}.1" @disabled(empty($state['business.hours'][$k]))>
                            </div>
                            <label class="form-check mt-2">
                                <input class="form-check-input" type="checkbox"
                                       @checked(!empty($state['business.hours'][$k]))
                                       wire:click="$set('state.business.hours.{{ $k }}', @js(!empty($state['business.hours'][$k]) ? null : ['09:00','17:00']))">
                                <span class="form-check-label">Open</span>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button class="btn btn-primary" type="submit">Save general settings</button>
    </div>
</form>

