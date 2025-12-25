<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Reports</div>
            <h2 class="page-title mb-0">Custom Report Builder</h2>
            <div class="text-muted small">Pick a metric + date range, export it, and optionally save/schedule it.</div>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.reports') }}">Back</a>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Builder</div></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Report name</label>
                        <input class="form-control" wire:model.live="name" placeholder="e.g. Monthly revenue summary">
                        @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Metric</label>
                        <select class="form-select" wire:model.live="metric">
                            <option value="revenue_by_month">Revenue by month</option>
                            <option value="requests_by_status">Requests by status</option>
                            <option value="storage_usage_by_client">Storage usage by client</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">From</label>
                            <input type="date" class="form-control" wire:model.live="from">
                            @error('from')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label">To</label>
                            <input type="date" class="form-control" wire:model.live="to">
                            @error('to')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button class="btn btn-outline-secondary" wire:click="export('csv')">Export CSV</button>
                        <button class="btn btn-outline-secondary" wire:click="export('xlsx')">Export Excel</button>
                        <button class="btn btn-outline-secondary" wire:click="export('pdf')">Export PDF</button>
                    </div>

                    <hr class="my-3">

                    <div class="mb-3">
                        <label class="form-label">Schedule</label>
                        <select class="form-select" wire:model.live="schedule">
                            <option value="none">No schedule</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                        @error('schedule')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Recipients (comma-separated emails)</label>
                        <textarea class="form-control" rows="3" wire:model.live="recipients" placeholder="ops@example.com, finance@example.com"></textarea>
                        @error('recipients')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        <div class="text-muted small mt-1">Scheduled delivery sends a CSV attachment.</div>
                    </div>

                    <button class="btn btn-primary" wire:click="saveTemplate">Save template</button>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Preview</div></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                        <tr>
                            @foreach($headings as $h)
                                <th>{{ $h }}</th>
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($rows as $r)
                            <tr>
                                @foreach($r as $cell)
                                    <td class="text-muted">{{ is_scalar($cell) ? $cell : json_encode($cell) }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ max(1, count($headings)) }}" class="text-center text-muted py-4">No data.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

