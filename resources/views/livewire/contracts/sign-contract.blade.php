<div class="space-y-4">
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-900">
        <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 flex-none text-amber-700" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
            </svg>
            <div>
                <div class="text-sm font-semibold">Important</div>
                <div class="mt-1 text-sm text-amber-800">
                    By signing electronically, you agree to be bound by the contract terms. Please review the document carefully before signing.
                </div>
            </div>
        </div>
    </div>

    @if($contract->file_path)
        <a href="{{ route('contracts.download', $contract) }}" target="_blank" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
            Review contract PDF
        </a>
    @endif

    <form wire:submit.prevent="sign" class="relative space-y-4">
        <!-- Submit overlay -->
        <div wire:loading.flex wire:target="sign" class="absolute inset-0 z-10 items-center justify-center rounded-2xl bg-white/70 backdrop-blur-sm">
            <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-lg ring-1 ring-black/5">
                <svg class="h-5 w-5 animate-spin text-slate-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span class="text-sm font-semibold text-slate-700">Signing...</span>
            </div>
        </div>

        <!-- Signature Mode Toggle -->
        <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
                <div class="flex items-center gap-4">
                    <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Signature Method</span>
                    <div class="flex rounded-lg border border-slate-200 bg-white p-0.5">
                        <button
                            type="button"
                            wire:click="setSignatureMode('draw')"
                            class="px-3 py-1.5 text-xs font-semibold rounded-md transition-colors {{ $signatureMode === 'draw' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900' }}"
                        >
                            Draw
                        </button>
                        <button
                            type="button"
                            wire:click="setSignatureMode('type')"
                            class="px-3 py-1.5 text-xs font-semibold rounded-md transition-colors {{ $signatureMode === 'type' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900' }}"
                        >
                            Type
                        </button>
                    </div>
                </div>
            </div>

            <div class="p-4">
                @if($signatureMode === 'draw')
                    <!-- Canvas Signature -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Draw your signature <span class="text-rose-600">*</span></label>
                        <div class="border border-slate-200 rounded-xl p-2 bg-white">
                            <canvas
                                id="contractSigPad"
                                width="600"
                                height="160"
                                style="width:100%; max-width:100%; height:160px; touch-action:none; cursor:crosshair;"
                                class="rounded-lg bg-slate-50"
                            ></canvas>
                        </div>
                        <div class="flex flex-wrap gap-3 mt-3">
                            <button
                                type="button"
                                id="sigClear"
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors"
                            >
                                Clear
                            </button>
                        </div>
                        @error('signatureData')
                            <div class="mt-2 flex items-start gap-2 text-xs font-medium text-rose-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 flex-none" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>
                @else
                    <!-- Typed Signature -->
                    <div>
                        <label for="signature" class="text-xs font-semibold text-slate-600">Your signature <span class="text-rose-600">*</span></label>
                        <div class="mt-1 text-xs text-slate-500">Type your full legal name as it appears on the contract.</div>
                        <input
                            id="signature"
                            type="text"
                            wire:model.live.debounce.250ms="signature"
                            class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-lg focus:border-slate-900 focus:ring-1 focus:ring-slate-900"
                            placeholder="Full legal name"
                            style="font-family: 'Brush Script MT', cursive;"
                        />
                        @error('signature')
                            <div class="mt-1 flex items-start gap-2 text-xs font-medium text-rose-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 flex-none" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    @if($signature)
                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold text-slate-600">Signature preview</div>
                            <div class="mt-2 text-center text-4xl text-slate-900" style="font-family: 'Brush Script MT', cursive;">
                                {{ $signature }}
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <div>
            <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <input type="checkbox" wire:model.live="agreeTerms" class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900" />
                <span class="text-sm text-slate-700">
                    I have read and agree to the terms and conditions of this contract. I understand this electronic signature has the same legal effect as a handwritten signature.
                </span>
            </label>
            @error('agreeTerms')
                <div class="mt-1 flex items-start gap-2 text-xs font-medium text-rose-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 flex-none" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ $message }}</span>
                </div>
            @enderror
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button
                type="submit"
                class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-300"
                wire:loading.attr="disabled"
                @disabled(($signatureMode === 'draw' && !$signatureData) || ($signatureMode === 'type' && !$signature) || !$agreeTerms)
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                    <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                </svg>
                <span wire:loading.remove wire:target="sign">Sign Contract</span>
                <span wire:loading wire:target="sign">Signing...</span>
            </button>
            <div class="text-xs text-slate-500">
                Your signature will be recorded with a timestamp and IP address.
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            (function () {
                const canvas = document.getElementById('contractSigPad');
                if (!canvas) return;

                const ctx = canvas.getContext('2d');
                let drawing = false;
                let last = null;

                // Set up canvas for high DPI displays
                const dpr = window.devicePixelRatio || 1;
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * dpr;
                canvas.height = rect.height * dpr;
                ctx.scale(dpr, dpr);
                canvas.style.width = rect.width + 'px';
                canvas.style.height = rect.height + 'px';

                function getPos(e) {
                    const rect = canvas.getBoundingClientRect();
                    const t = e.touches && e.touches[0];
                    const x = (t ? t.clientX : e.clientX) - rect.left;
                    const y = (t ? t.clientY : e.clientY) - rect.top;
                    return { x, y };
                }

                function start(e) {
                    drawing = true;
                    last = getPos(e);
                    e.preventDefault();
                }

                function move(e) {
                    if (!drawing) return;
                    const p = getPos(e);
                    ctx.lineWidth = 2;
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';
                    ctx.strokeStyle = '#1e293b';
                    ctx.beginPath();
                    ctx.moveTo(last.x, last.y);
                    ctx.lineTo(p.x, p.y);
                    ctx.stroke();
                    last = p;
                    // Update Livewire with signature data
                    try {
                        @this.set('signatureData', canvas.toDataURL('image/png'));
                    } catch (err) {
                        console.error('Failed to capture signature:', err);
                    }
                    e.preventDefault();
                }

                function end(e) {
                    drawing = false;
                    last = null;
                    if (e && e.preventDefault) e.preventDefault();
                }

                // Mouse events
                canvas.addEventListener('mousedown', start);
                canvas.addEventListener('mousemove', move);
                window.addEventListener('mouseup', end);

                // Touch events
                canvas.addEventListener('touchstart', start, { passive: false });
                canvas.addEventListener('touchmove', move, { passive: false });
                canvas.addEventListener('touchend', end, { passive: false });

                // Clear button
                const clearBtn = document.getElementById('sigClear');
                if (clearBtn) {
                    clearBtn.addEventListener('click', function () {
                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                        try {
                            @this.set('signatureData', '');
                        } catch (err) {
                            console.error('Failed to clear signature:', err);
                        }
                    });
                }

                // Re-initialize canvas when Livewire updates the DOM
                document.addEventListener('livewire:navigated', function () {
                    const newCanvas = document.getElementById('contractSigPad');
                    if (newCanvas && newCanvas !== canvas) {
                        // Re-attach events to new canvas
                        location.reload();
                    }
                });
            })();
        </script>
    @endpush
</div>
