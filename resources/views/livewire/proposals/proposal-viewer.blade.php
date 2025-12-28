<div>
    <h2 class="mb-3">Proposal</h2>

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
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-signature mr-1"></i> E-signature</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="col-md-6">
                                        <label class="mb-1">Full name</label>
                                        <input class="form-control" wire:model.defer="signerName" placeholder="Your name">
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <label class="form-check mb-0">
                                            <input class="form-check-input" type="checkbox" wire:model.defer="agree">
                                            <span class="form-check-label">I agree to proceed and accept this proposal.</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="mb-1">Draw signature</label>
                                    <div class="border rounded p-2" style="background:#fff;">
                                        <canvas id="sigPad" width="600" height="160" style="width:100%; max-width:100%; height:160px; touch-action:none;"></canvas>
                                    </div>
                                    <div class="d-flex mt-2" style="gap:8px;">
                                        <button type="button" class="btn btn-outline-secondary" id="sigClear">Clear</button>
                                        <button class="btn btn-success" wire:click="accept"><i class="fas fa-check mr-1"></i> Sign & accept</button>
                                    </div>
                                    <div class="text-muted small mt-2">Your signature is stored with this proposal and contract.</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endforeach

    @push('scripts')
        <script>
            (function () {
                const canvas = document.getElementById('sigPad');
                if (!canvas) return;
                const ctx = canvas.getContext('2d');
                let drawing = false;
                let last = null;

                function getPos(e) {
                    const rect = canvas.getBoundingClientRect();
                    const t = e.touches && e.touches[0];
                    const x = (t ? t.clientX : e.clientX) - rect.left;
                    const y = (t ? t.clientY : e.clientY) - rect.top;
                    return { x, y };
                }
                function start(e) { drawing = true; last = getPos(e); e.preventDefault(); }
                function move(e) {
                    if (!drawing) return;
                    const p = getPos(e);
                    ctx.lineWidth = 2;
                    ctx.lineCap = 'round';
                    ctx.strokeStyle = '#111';
                    ctx.beginPath();
                    ctx.moveTo(last.x, last.y);
                    ctx.lineTo(p.x, p.y);
                    ctx.stroke();
                    last = p;
                    try { @this.set('signatureData', canvas.toDataURL('image/png')); } catch (e) {}
                    e.preventDefault();
                }
                function end(e) { drawing = false; last = null; e && e.preventDefault && e.preventDefault(); }

                canvas.addEventListener('mousedown', start);
                canvas.addEventListener('mousemove', move);
                window.addEventListener('mouseup', end);
                canvas.addEventListener('touchstart', start, { passive: false });
                canvas.addEventListener('touchmove', move, { passive: false });
                canvas.addEventListener('touchend', end, { passive: false });

                const clearBtn = document.getElementById('sigClear');
                if (clearBtn) {
                    clearBtn.addEventListener('click', function () {
                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                        try { @this.set('signatureData', ''); } catch (e) {}
                    });
                }
            })();
        </script>
    @endpush
</div>

