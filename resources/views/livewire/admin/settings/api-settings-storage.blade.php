<div>
    <p class="text-muted mb-4">
        Configure OAuth app credentials for cloud storage integrations. Once configured, clients can connect their
        Dropbox, Google Drive, or OneDrive accounts to share files with staff working on their accounts.
    </p>

    <div class="row">
        {{-- Dropbox --}}
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center">
                    <i class="fab fa-dropbox fa-lg text-primary me-2"></i>
                    <h5 class="mb-0">Dropbox</h5>
                    <div class="ms-auto">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="dropbox_enabled" wire:model="cloudStorage.dropbox_enabled">
                            <label class="custom-control-label" for="dropbox_enabled">Enabled</label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label class="form-label">App Key</label>
                        <input type="text" class="form-control" wire:model="cloudStorage.dropbox_app_key" placeholder="Enter Dropbox App Key">
                        <small class="text-muted">From your Dropbox App Console</small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">App Secret</label>
                        <input type="password" class="form-control" wire:model="cloudStorage.dropbox_app_secret" placeholder="Enter Dropbox App Secret">
                    </div>
                    <div class="alert alert-light small mb-0">
                        <strong>Redirect URI:</strong>
                        <code>{{ url('/storage/dropbox/callback') }}</code>
                        <br><small class="text-muted">Add this to your Dropbox app's OAuth 2 redirect URIs</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Google Drive --}}
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center">
                    <i class="fab fa-google-drive fa-lg text-warning me-2"></i>
                    <h5 class="mb-0">Google Drive</h5>
                    <div class="ms-auto">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="google_drive_enabled" wire:model="cloudStorage.google_drive_enabled">
                            <label class="custom-control-label" for="google_drive_enabled">Enabled</label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label class="form-label">Client ID</label>
                        <input type="text" class="form-control" wire:model="cloudStorage.google_drive_client_id" placeholder="Enter Google Client ID">
                        <small class="text-muted">From Google Cloud Console</small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Client Secret</label>
                        <input type="password" class="form-control" wire:model="cloudStorage.google_drive_client_secret" placeholder="Enter Google Client Secret">
                    </div>
                    <div class="alert alert-light small mb-0">
                        <strong>Redirect URI:</strong>
                        <code>{{ url('/storage/google/callback') }}</code>
                        <br><small class="text-muted">Add this to your Google OAuth consent screen's authorized redirect URIs</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- OneDrive --}}
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center">
                    <i class="fab fa-microsoft fa-lg text-info me-2"></i>
                    <h5 class="mb-0">OneDrive / SharePoint</h5>
                    <div class="ms-auto">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="onedrive_enabled" wire:model="cloudStorage.onedrive_enabled">
                            <label class="custom-control-label" for="onedrive_enabled">Enabled</label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label class="form-label">Application (Client) ID</label>
                        <input type="text" class="form-control" wire:model="cloudStorage.onedrive_client_id" placeholder="Enter Azure App Client ID">
                        <small class="text-muted">From Azure Portal App Registration</small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Client Secret</label>
                        <input type="password" class="form-control" wire:model="cloudStorage.onedrive_client_secret" placeholder="Enter Azure Client Secret">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Tenant ID <span class="text-muted">(optional)</span></label>
                        <input type="text" class="form-control" wire:model="cloudStorage.onedrive_tenant_id" placeholder="common">
                        <small class="text-muted">Use "common" for multi-tenant, or your specific tenant ID</small>
                    </div>
                    <div class="alert alert-light small mb-0">
                        <strong>Redirect URI:</strong>
                        <code>{{ url('/storage/onedrive/callback') }}</code>
                        <br><small class="text-muted">Add this to your Azure app's redirect URIs</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Info Card --}}
        <div class="col-lg-6 mb-4">
            <div class="card h-100 bg-light">
                <div class="card-header">
                    <i class="fas fa-info-circle me-2"></i>
                    <h5 class="mb-0">How It Works</h5>
                </div>
                <div class="card-body">
                    <ol class="mb-0">
                        <li class="mb-2">Configure your OAuth app credentials above</li>
                        <li class="mb-2">Enable the storage providers you want to offer</li>
                        <li class="mb-2">Clients can connect their accounts from their Storage page</li>
                        <li class="mb-2">Staff assigned to clients can browse and access shared files</li>
                        <li>Files sync bidirectionally between the portal and cloud storage</li>
                    </ol>
                    <hr>
                    <p class="small text-muted mb-0">
                        <strong>Note:</strong> Clients control which folders they share. Staff can only access
                        folders that clients have explicitly connected to the portal.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button type="button" class="btn btn-primary" wire:click="saveStorageSettings" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="saveStorageSettings">
                <i class="fas fa-save me-1"></i> Save Cloud Storage Settings
            </span>
            <span wire:loading wire:target="saveStorageSettings">
                <i class="fas fa-spinner fa-spin me-1"></i> Saving...
            </span>
        </button>
    </div>
</div>
