<div class="max-w-lg mx-auto">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-slate-900">Two-Factor Authentication</h2>
        <p class="text-sm text-slate-500 mt-1">Add an extra layer of security to your account</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-600" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <h3 class="text-base font-semibold text-slate-900">2FA Settings</h3>
        </div>
        <div class="p-6">
            @if($confirmed)
                <!-- 2FA Enabled State -->
                <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 mb-5">
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-emerald-900">2FA is enabled for your account</p>
                            <p class="text-sm text-emerald-700 mt-0.5">Your account has an extra layer of security.</p>
                        </div>
                    </div>
                </div>

                @if(!empty($recoveryCodes))
                    <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 mb-5">
                        <div class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-amber-900 mb-2">Recovery Codes</p>
                                <p class="text-sm text-amber-700 mb-3">Store these somewhere safe. Each can be used once if you lose access to your authenticator.</p>
                                <ul class="space-y-1">
                                    @foreach($recoveryCodes as $c)
                                        <li class="font-mono text-sm bg-white px-3 py-1.5 rounded-lg border border-amber-200 inline-block mr-2 mb-2">{{ $c }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <button type="button" wire:click="disable" class="rounded-lg border border-rose-300 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50 transition-colors flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    Disable 2FA
                </button>
            @else
                <!-- 2FA Setup State -->
                <div class="rounded-xl bg-blue-50 border border-blue-200 p-4 mb-5">
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm text-blue-800">Scan the QR code with an authenticator app (like Google Authenticator, Authy, or 1Password), then enter the 6-digit code to confirm.</p>
                    </div>
                </div>

                @if($qrUrl)
                    <div class="mb-5 flex justify-center">
                        <div class="p-4 bg-white rounded-xl border border-slate-200 shadow-sm">
                            <img src="{{ $qrUrl }}" alt="2FA QR code" class="w-48 h-48">
                        </div>
                    </div>
                @endif

                <div class="mb-5">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Manual Setup Key</label>
                    <div class="flex">
                        <input type="text" value="{{ $secret }}" readonly class="flex-1 rounded-l-xl border border-r-0 border-slate-300 px-3 py-2.5 text-sm text-slate-900 font-mono bg-slate-50">
                        <button type="button" onclick="navigator.clipboard.writeText('{{ $secret }}').then(() => alert('Copied!'))" class="px-4 py-2.5 rounded-r-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                            Copy
                        </button>
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500">Use this key if you can't scan the QR code.</p>
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Verification Code</label>
                    <input type="text" wire:model="code" placeholder="Enter 6-digit code" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors" autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]*" maxlength="6">
                    @error('code')
                        <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <button type="button" wire:click="confirm" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Confirm 2FA
                </button>
            @endif
        </div>
    </div>
</div>
