<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Storage</div>
            <h2 class="page-title mb-0">Connect Dropbox</h2>
            <div class="text-muted small">OAuth access tokens are stored encrypted.</div>
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
                    <label class="form-label">Folder path (optional)</label>
                    <input type="text" class="form-control" wire:model.live.debounce.350ms="folder_path" placeholder="e.g. Clients/Acme">
                    @error('folder_path') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    <div class="text-muted small mt-1">Limits browsing/uploads to a folder inside Dropbox.</div>
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
                        id="dropbox-connect-btn"
                        data-oauth-url="{{ $authorizeUrl }}"
                    >
                        Connect to Dropbox
                    </a>
                    <button type="button" class="btn btn-outline-secondary" wire:click="saveSettings" wire:loading.attr="disabled">
                        Save settings
                    </button>

                    @if($connection_id)
                        <a class="btn btn-outline-primary" href="{{ route('admin.storage.dropbox.browse', ['connection' => $connection_id]) }}">
                            Browse Dropbox
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
            const btn = document.getElementById('dropbox-connect-btn');
            if (!btn) return;

            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const url = btn.getAttribute('data-oauth-url');
                if (!url) return;

                const w = 600;
                const h = 750;
                const left = (window.screen.width / 2) - (w / 2);
                const top = (window.screen.height / 2) - (h / 2);
                window.open(url, 'dropbox_oauth', `width=${w},height=${h},top=${top},left=${left}`);
            });

            window.addEventListener('message', function (event) {
                if (event.origin !== window.location.origin) return;
                if (!event.data || event.data.type !== 'dropbox_oauth_success') return;
                @this.refreshConnection();
            });
        })();
    </script>
</div>

