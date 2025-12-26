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
                <span class="text-sm font-semibold text-slate-700">Signing…</span>
            </div>
        </div>

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
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ $message }}</span>
                </div>
            @enderror
        </div>

        @if($signature)
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold text-slate-600">Signature preview</div>
                <div class="mt-2 text-center text-4xl text-slate-900" style="font-family: 'Brush Script MT', cursive;">
                    {{ $signature }}
                </div>
            </div>
        @endif

        <div>
            <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <input type="checkbox" wire:model.live="agreeTerms" class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900" />
                <span class="text-sm text-slate-700">
                    I have read and agree to the terms and conditions of this contract. I understand this electronic signature has the same legal effect as a handwritten signature.
                </span>
            </label>
            @error('agreeTerms')
                <div class="mt-1 flex items-start gap-2 text-xs font-medium text-rose-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
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
                @disabled(!$signature || !$agreeTerms)
            >
                <span wire:loading.remove wire:target="sign">Sign contract</span>
                <span wire:loading wire:target="sign">Signing…</span>
            </button>
            <div class="text-xs text-slate-500" title="Your signature is recorded with a timestamp and IP address for verification.">
                Your signature will be recorded with a timestamp and IP address.
            </div>
        </div>
    </form>
</div>
