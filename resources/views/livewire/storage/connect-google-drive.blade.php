<div class="max-w-3xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <p class="text-sm text-slate-500">Storage</p>
            <h1 class="text-2xl font-semibold text-slate-900">Connect Google Drive</h1>
            <p class="text-sm text-slate-500 mt-1">OAuth tokens are stored encrypted. Refresh tokens are used automatically.</p>
        </div>
        <a href="{{ route('admin.storage') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
            Back
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 mb-6">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <p class="text-sm text-emerald-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 mb-6">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-600 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <p class="text-sm text-rose-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <!-- Configuration Card -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            <h2 class="text-base font-semibold text-slate-900">Configuration</h2>
        </div>
        <div class="p-6 space-y-5">
            @if($isAdmin)
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Client</label>
                    <select wire:model.live="client_id" wire:change="refreshConnection" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        <option value="">Select a client…</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                        @endforeach
                    </select>
                    @error('client_id')
                        <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>
            @else
                <input type="hidden" wire:model="client_id">
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Base Folder ID</label>
                    <input type="text" wire:model.live.debounce.350ms="folder_id" placeholder="e.g. 1AbCDefGhIJKlmn..." class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors font-mono">
                    @error('folder_id')
                        <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                    <p class="mt-1.5 text-xs text-slate-500">Leave empty to use Drive root. You can paste a folder ID from a Drive URL.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Sync Mode</label>
                    <select wire:model.live="sync_mode" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        <option value="bidirectional">Bidirectional</option>
                        <option value="upload_only">Upload only</option>
                        <option value="download_only">Download only</option>
                    </select>
                    @error('sync_mode')
                        <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Share Base Folder With</label>
                    <input type="text" wire:model.live.debounce.350ms="share_folder_with" placeholder="team@company.com, ops@company.com" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                    @error('share_folder_with')
                        <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                    <p class="mt-1.5 text-xs text-slate-500">Comma-separated emails. Requires base folder ID set.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Share Role</label>
                    <select wire:model.live="share_role" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        <option value="writer">Writer</option>
                        <option value="reader">Reader</option>
                    </select>
                    @error('share_role')
                        <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>
            </div>

            <div class="pt-2">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" wire:model.live="is_primary" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                    <span class="text-sm text-slate-700">Set as primary storage</span>
                </label>
            </div>

            <div class="flex flex-wrap gap-3 pt-3">
                <a href="{{ $authorizeUrl }}" id="gdrive-connect-btn" data-oauth-url="{{ $authorizeUrl }}" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition-colors flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24">
                        <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    </svg>
                    Connect to Google Drive
                </a>
                <button type="button" wire:click="saveSettings" wire:loading.attr="disabled" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                    <span wire:loading.remove wire:target="saveSettings">Save Settings</span>
                    <span wire:loading wire:target="saveSettings">Saving…</span>
                </button>
                @if($connection_id)
                    <a href="{{ route('admin.storage.google-drive.browse', ['connection' => $connection_id]) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                        Browse Google Drive
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Status Card -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
            <h2 class="text-base font-semibold text-slate-900">Connection Status</h2>
        </div>
        <div class="p-6">
            @if($connection_id)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Connection ID</p>
                        <p class="text-sm font-semibold text-slate-900">#{{ $connection_id }} · <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ $status ?: 'unknown' }}</span></p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Account Email</p>
                        <p class="text-sm font-semibold text-slate-900">{{ $account_email ?: '—' }}</p>
                    </div>
                </div>
            @else
                <div class="text-center py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                    <p class="text-sm text-slate-500">Not connected yet. Click "Connect to Google Drive" to start.</p>
                </div>
            @endif
        </div>
    </div>

    <script>
        (function () {
            const btn = document.getElementById('gdrive-connect-btn');
            if (!btn) return;

            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const url = btn.getAttribute('data-oauth-url');
                if (!url) return;

                const w = 600;
                const h = 750;
                const left = (window.screen.width / 2) - (w / 2);
                const top = (window.screen.height / 2) - (h / 2);
                window.open(url, 'gdrive_oauth', `width=${w},height=${h},top=${top},left=${left}`);
            });

            window.addEventListener('message', function (event) {
                if (event.origin !== window.location.origin) return;
                if (!event.data || event.data.type !== 'gdrive_oauth_success') return;
                @this.refreshConnection();
            });
        })();
    </script>
</div>
