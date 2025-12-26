<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Storage</div>
            <h2 class="page-title mb-0">Connect Google Drive</h2>
            <div class="text-muted small">OAuth tokens are stored encrypted. Refresh tokens are used automatically.</div>
        </div>
        <a href="{{ route('admin.storage') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                @if($isAdmin)
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Client</label>
                        <select class="form-select" wire:model.live="client_id" wire:change="refreshConnection">
                            <option value="">Select a client…</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                            @endforeach
                        </select>
                        @error('client_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                @else
                    <input type="hidden" wire:model="client_id">
                @endif

                <div class="col-12 col-lg-6">
                    <label class="form-label">Base folder ID (optional)</label>
                    <input type="text" class="form-control" wire:model.live.debounce.350ms="folder_id" placeholder="e.g. 1AbCDefGhIJKlmn...">
                    @error('folder_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    <div class="text-muted small mt-1">Leave empty to use Drive root. (You can paste a folder ID from a Drive URL.)</div>
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label">Sync mode</label>
                    <select class="form-select" wire:model.live="sync_mode">
                        <option value="bidirectional">Bidirectional</option>
                        <option value="upload_only">Upload only</option>
                        <option value="download_only">Download only</option>
                    </select>
                    @error('sync_mode') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label">Share base folder with team (optional)</label>
                    <input type="text" class="form-control" wire:model.live.debounce.350ms="share_folder_with" placeholder="team@company.com, ops@company.com">
                    @error('share_folder_with') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    <div class="text-muted small mt-1">Comma-separated emails. Requires base folder ID set.</div>
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label">Share role</label>
                    <select class="form-select" wire:model.live="share_role">
                        <option value="writer">Writer</option>
                        <option value="reader">Reader</option>
                    </select>
                    @error('share_role') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" wire:model.live="is_primary">
                        <span class="form-check-label">Set as primary storage</span>
                    </label>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2">
                    <a
                        href="{{ $authorizeUrl }}"
                        class="btn btn-primary"
                        id="gdrive-connect-btn"
                        data-oauth-url="{{ $authorizeUrl }}"
                    >
                        Connect to Google Drive
                    </a>
                    <button type="button" class="btn btn-outline-secondary" wire:click="saveSettings" wire:loading.attr="disabled">
                        Save settings
                    </button>

                    @if($connection_id)
                        <a class="btn btn-outline-primary" href="{{ route('admin.storage.google-drive.browse', ['connection' => $connection_id]) }}">
                            Browse Google Drive
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title mb-0">Status</div>
        </div>
        <div class="card-body">
            @if($connection_id)
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <div>
                        <div class="text-muted small">Connection</div>
                        <div class="fw-semibold">#{{ $connection_id }} · {{ $status ?: 'unknown' }}</div>
                    </div>
                    <div>
                        <div class="text-muted small">Account email</div>
                        <div class="fw-semibold">{{ $account_email ?: '—' }}</div>
                    </div>
                </div>
            @else
                <div class="text-muted">Not connected yet.</div>
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

