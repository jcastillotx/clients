<x-app-layout>
    <x-slot name="header">Reports</x-slot>

    @php
        $brandName = $whiteLabel?->company_name ?: (auth()->user()?->client?->company_name ?? config('app.name'));
        $primary = $whiteLabel?->primary_color ?: '#3c8dbc';
        $footer = $whiteLabel?->footer_text ?: '';
    @endphp

    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Executive dashboard</div>
                    <h3 class="mb-0">{{ $brandName }}</h3>
                </div>
                <div>
                    <span class="badge" style="background: {{ $primary }}; color: #fff;">White-labeled</span>
                </div>
            </div>
            <hr>

            <div class="row">
                <div class="col-md-6">
                    <h5>Snapshot</h5>
                    <div class="text-muted small">Showing only configured metrics.</div>
                </div>
                <div class="col-md-6 text-md-right">
                    <div class="text-muted small">Last updated: {{ now()->toDateTimeString() }}</div>
                </div>
            </div>

            <pre class="mt-3" style="max-height: 320px; overflow:auto; background:#f8f9fa; padding:12px; border-radius:10px;">{{ json_encode($payload, JSON_PRETTY_PRINT) }}</pre>

            @if($footer)
                <div class="mt-3 text-muted small">{{ $footer }}</div>
            @endif
        </div>
    </div>
</x-app-layout>

