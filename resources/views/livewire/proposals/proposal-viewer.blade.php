<x-app-layout>
    <x-slot name="header">Proposal</x-slot>

    @php
        $cover = (array)($content['cover'] ?? []);
        $sections = (array)($content['sections'] ?? []);
        $tiers = (array)($pricing['tiers'] ?? []);
        $addons = (array)($pricing['addons'] ?? []);
    @endphp

    <div class="card mb-3">
        <div class="card-body">
            <div class="text-muted small">{{ $cover['prepared_by'] ?? config('app.name') }}</div>
            <h2 class="mb-0">{{ $cover['title'] ?? $proposal->title }}</h2>
            <div class="text-muted">Prepared for: <strong>{{ $cover['prepared_for'] ?? '' }}</strong></div>
            <div class="text-muted small">Proposal #: {{ $proposal->proposal_number }} @if($proposal->valid_until) · Valid until {{ $proposal->valid_until->toDateString() }} @endif</div>
        </div>
    </div>

    @foreach($sections as $s)
        @php
            $st = is_array($s) ? (string)($s['title'] ?? '') : '';
            $body = is_array($s) ? (string)($s['body'] ?? '') : '';
        @endphp
        @if($st !== '')
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">{{ $st }}</h3>
                </div>
                <div class="card-body">
                    @if(trim($body) !== '')
                        <div style="white-space: pre-wrap;">{{ $body }}</div>
                    @else
                        <div class="text-muted">Coming soon…</div>
                    @endif

                    @if(strtolower($st) === 'pricing options')
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="font-weight-bold mb-2">Choose a tier</div>
                                @foreach($tiers as $key => $t)
                                    <label class="d-block">
                                        <input type="radio" wire:model="selectedTier" value="{{ $key }}">
                                        <strong>{{ $t['label'] ?? ucfirst($key) }}</strong>
                                        <span class="text-muted">— ${{ number_format((float)($t['amount'] ?? 0), 2) }}</span>
                                    </label>
                                @endforeach
                                @if(empty($tiers))
                                    <div class="text-muted">No tiers configured yet.</div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <div class="font-weight-bold mb-2">Optional add-ons</div>
                                @if(empty($addons))
                                    <div class="text-muted">No add-ons.</div>
                                @else
                                    @foreach($addons as $a)
                                        @php
                                            $k = (string)($a['key'] ?? '');
                                        @endphp
                                        @if($k)
                                            <label class="d-block">
                                                <input type="checkbox" wire:model="selectedAddons" value="{{ $k }}">
                                                {{ $a['label'] ?? $k }}
                                                @if(!empty($a['description']))
                                                    <div class="text-muted small">{{ $a['description'] }}</div>
                                                @endif
                                            </label>
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        <div class="mt-3 d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">Estimated total</div>
                                <div class="h4 mb-0">${{ number_format($total, 2) }}</div>
                            </div>
                            <div class="d-flex" style="gap: 8px;">
                                <button class="btn btn-outline-secondary" wire:click="saveSelection">Save selection</button>
                                <button class="btn btn-success" wire:click="accept">Accept proposal</button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endforeach
</x-app-layout>

