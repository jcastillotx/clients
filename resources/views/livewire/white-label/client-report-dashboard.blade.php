<div>
    <h2 class="mb-3">Reports</h2>

    @php
        $brandName = $whiteLabel?->company_name ?: (auth()->user()?->client?->company_name ?? config('app.name'));
        $primary = $whiteLabel?->primary_color ?: '#3c8dbc';
        $footer = $whiteLabel?->footer_text ?: '';
        $tables = (array)($payload['tables'] ?? []);
    @endphp

    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Executive dashboard</div>
                    <div class="h4 mb-0">{{ $brandName }}</div>
                    <div class="text-muted small">
                        Showing only configured metrics.
                        @if(!empty($payload['meta']['start']) && !empty($payload['meta']['end']))
                            · Range: {{ $payload['meta']['start'] }} → {{ $payload['meta']['end'] }}
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <span class="badge" style="background: {{ $primary }}; color: #fff;">White-labeled</span>
                    <div class="mt-2">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('client.reports.archive') }}">
                            <i class="fas fa-archive mr-1"></i> Archive
                        </a>
                    </div>
                    <div class="text-muted small mt-1">Updated: {{ now()->toDateTimeString() }}</div>
                </div>
            </div>
        </div>
    </div>

    @include('livewire.admin.reports._tables', ['tables' => $tables])

    @if($footer)
        <div class="text-muted small mt-2">{{ $footer }}</div>
    @endif
</div>

