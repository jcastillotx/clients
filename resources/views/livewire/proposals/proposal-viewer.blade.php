<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-slate-900">Proposal</h2>
    </div>

    @php
        $cover = (array)($content['cover'] ?? []);
        $sections = (array)($content['sections'] ?? []);
        $tiers = (array)($pricing['tiers'] ?? []);
        $addons = (array)($pricing['addons'] ?? []);
    @endphp

    <!-- Cover Card -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-6 mb-6">
        <p class="text-sm text-slate-500 mb-1">{{ $cover['prepared_by'] ?? config('app.name') }}</p>
        <h1 class="text-2xl font-bold text-slate-900 mb-2">{{ $cover['title'] ?? $proposal->title }}</h1>
        <p class="text-sm text-slate-600">Prepared for: <span class="font-semibold text-slate-900">{{ $cover['prepared_for'] ?? '' }}</span></p>
        <p class="text-xs text-slate-500 mt-2">
            Proposal #: {{ $proposal->proposal_number }}
            @if($proposal->valid_until)
                · Valid until {{ $proposal->valid_until->toDateString() }}
            @endif
        </p>
    </div>

    @foreach($sections as $s)
        @php
            $st = is_array($s) ? (string)($s['title'] ?? '') : '';
            $body = is_array($s) ? (string)($s['body'] ?? '') : '';
        @endphp
        @if($st !== '')
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h3 class="text-base font-semibold text-slate-900">{{ $st }}</h3>
                </div>
                <div class="p-6">
                    @if(trim($body) !== '')
                        <div class="text-sm text-slate-700 whitespace-pre-wrap leading-relaxed">{{ $body }}</div>
                    @else
                        <p class="text-sm text-slate-500 italic">Coming soon…</p>
                    @endif

                    @if(strtolower($st) === 'pricing options')
                        <div class="border-t border-slate-200 mt-6 pt-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Tiers -->
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900 mb-3">Choose a tier</h4>
                                    @if(empty($tiers))
                                        <p class="text-sm text-slate-500 italic">No tiers configured yet.</p>
                                    @else
                                        <div class="space-y-2">
                                            @foreach($tiers as $key => $t)
                                                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition-colors {{ $selectedTier === $key ? 'border-slate-900 bg-slate-50' : '' }}">
                                                    <input type="radio" wire:model="selectedTier" value="{{ $key }}" class="h-4 w-4 border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                                    <div>
                                                        <span class="text-sm font-semibold text-slate-900">{{ $t['label'] ?? ucfirst($key) }}</span>
                                                        <span class="text-sm text-slate-500 ml-2">${{ number_format((float)($t['amount'] ?? 0), 2) }}</span>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <!-- Add-ons -->
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900 mb-3">Optional add-ons</h4>
                                    @if(empty($addons))
                                        <p class="text-sm text-slate-500 italic">No add-ons available.</p>
                                    @else
                                        <div class="space-y-2">
                                            @foreach($addons as $a)
                                                @php
                                                    $k = (string)($a['key'] ?? '');
                                                @endphp
                                                @if($k)
                                                    <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition-colors">
                                                        <input type="checkbox" wire:model="selectedAddons" value="{{ $k }}" class="h-4 w-4 mt-0.5 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                                        <div>
                                                            <span class="text-sm font-medium text-slate-900">{{ $a['label'] ?? $k }}</span>
                                                            @if(!empty($a['description']))
                                                                <p class="text-xs text-slate-500 mt-0.5">{{ $a['description'] }}</p>
                                                            @endif
                                                        </div>
                                                    </label>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Total & Save -->
                            <div class="flex flex-wrap items-center justify-between gap-4 mt-6 pt-6 border-t border-slate-200">
                                <div>
                                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Estimated Total</p>
                                    <p class="text-2xl font-bold text-slate-900">${{ number_format($total, 2) }}</p>
                                </div>
                                <button type="button" wire:click="saveSelection" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                                    Save Selection
                                </button>
                            </div>

                            <!-- E-Signature Section -->
                            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-600" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                        <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                                    </svg>
                                    <h4 class="text-base font-semibold text-slate-900">E-Signature</h4>
                                </div>
                                <div class="p-6 bg-white">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Full Name</label>
                                            <input type="text" wire:model.defer="signerName" placeholder="Your name" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                        </div>
                                        <div class="flex items-end pb-2">
                                            <label class="flex items-center gap-3 cursor-pointer">
                                                <input type="checkbox" wire:model.defer="agree" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                                <span class="text-sm text-slate-700">I agree to proceed and accept this proposal.</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Draw Signature</label>
                                        <div class="border border-slate-200 rounded-xl p-2 bg-white">
                                            <canvas id="sigPad" width="600" height="160" style="width:100%; max-width:100%; height:160px; touch-action:none;" class="rounded-lg"></canvas>
                                        </div>
                                        <div class="flex flex-wrap gap-3 mt-3">
                                            <button type="button" id="sigClear" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                                                Clear
                                            </button>
                                            <button type="button" wire:click="accept" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition-colors flex items-center gap-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                                Sign & Accept
                                            </button>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-3">Your signature is stored with this proposal and contract.</p>
                                    </div>
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
