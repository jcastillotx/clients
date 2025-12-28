@php
    $storageLink = public_path('storage');
    $storageTarget = storage_path('app/public');
    $storageLinkExists = file_exists($storageLink);
    $storageLinkOk = is_link($storageLink) && realpath($storageLink) === realpath($storageTarget);
    $storageTargetWritable = is_dir($storageTarget) && is_writable($storageTarget);
@endphp

@if(! $storageLinkOk)
    <div class="alert alert-warning mb-4">
        <strong>Public storage link is missing or misconfigured.</strong>
        <div class="mt-2">
            Admin uploads (logos/backgrounds) are saved to <code>{{ $storageTarget }}</code> but are referenced via <code>/storage/...</code>.
            Create the symlink on the server:
        </div>
        <pre class="mt-2 mb-0"><code>php artisan storage:link</code></pre>
        <div class="mt-2 text-muted">
            Current: <code>{{ $storageLink }}</code> {{ $storageLinkExists ? '(exists)' : '(missing)' }}.
            Target writable: {{ $storageTargetWritable ? 'yes' : 'no' }}.
        </div>
        <div class="mt-2">
            Note: this app also includes a Laravel fallback route for <code>/storage/*</code> when the symlink can't be created.
        </div>
    </div>
@endif

<!-- Current Configuration Notice -->
<div class="alert alert-info mb-4">
    <div class="d-flex align-items-start">
        <i class="fas fa-info-circle mt-1 mr-2"></i>
        <div>
            <strong>Local Storage Active</strong>
            <p class="mb-0 mt-1">Files are currently stored on the local server filesystem. You can configure external storage providers below for future use.</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <h5 class="mb-3">
            <i class="fas fa-cog mr-1"></i> Upload Settings
        </h5>
        <div class="form-group">
            <label class="mb-1">Default storage provider</label>
            <select class="form-control" wire:model="storage.default_provider">
                <option value="local">Local Disk</option>
                <option value="s3">Amazon S3</option>
                <option value="do-spaces">DigitalOcean Spaces</option>
                <option value="r2">Cloudflare R2</option>
                <option value="b2">Backblaze B2</option>
                <option value="minio">MinIO (Self-hosted)</option>
            </select>
            <small class="text-muted">Local disk stores files on the server. External providers require additional configuration via environment variables.</small>
        </div>
        <div class="form-group">
            <label class="mb-1">Maximum upload file size (MB)</label>
            <input type="number" class="form-control" wire:model="storage.max_upload_mb" min="1">
            <small class="text-muted">Note: PHP's upload_max_filesize and post_max_size must also be configured on the server.</small>
        </div>
        <div class="form-group">
            <label class="mb-1">Allowed file types (comma separated)</label>
            <input class="form-control" wire:model="storage.allowed_file_types">
            <small class="text-muted">e.g., pdf,jpg,jpeg,png,doc,docx,xls,xlsx</small>
        </div>
        <div class="form-group">
            <label class="mb-1">Retention policy (days)</label>
            <input type="number" class="form-control" wire:model="storage.retention_days" min="0">
            <small class="text-muted">0 = keep forever. (Enforcement can be implemented via a scheduled cleanup job.)</small>
        </div>
    </div>

    <div class="col-md-6">
        <h5 class="mb-3">
            <i class="fas fa-users mr-1"></i> Quota per Client Tier (GB)
        </h5>
        <div class="form-group">
            <label class="mb-1">Basic</label>
            <input type="number" class="form-control" wire:model="storage.quota_basic_gb" min="0">
        </div>
        <div class="form-group">
            <label class="mb-1">Standard</label>
            <input type="number" class="form-control" wire:model="storage.quota_standard_gb" min="0">
        </div>
        <div class="form-group">
            <label class="mb-1">Premium</label>
            <input type="number" class="form-control" wire:model="storage.quota_premium_gb" min="0">
        </div>
        <div class="form-group">
            <label class="mb-1">Enterprise</label>
            <input type="number" class="form-control" wire:model="storage.quota_enterprise_gb" min="0">
        </div>

        <h5 class="mt-4 mb-3">
            <i class="fas fa-cloud-upload-alt mr-1"></i> Backup
        </h5>
        <div class="custom-control custom-switch mb-2">
            <input type="checkbox" class="custom-control-input" id="backup_enabled_sys" wire:model="storage.backup_enabled">
            <label class="custom-control-label" for="backup_enabled_sys">Enable backup</label>
        </div>
        <div class="form-group">
            <label class="mb-1">Backup provider</label>
            <select class="form-control" wire:model="storage.backup_provider">
                <option value="local">Local Disk</option>
                <option value="s3">Amazon S3</option>
                <option value="do-spaces">DigitalOcean Spaces</option>
                <option value="r2">Cloudflare R2</option>
                <option value="b2">Backblaze B2</option>
            </select>
        </div>
    </div>
</div>

<hr class="my-4">

<!-- Storage Categories -->
<h5 class="mb-3">
    <i class="fas fa-folder mr-1"></i> Storage Locations
</h5>
<p class="text-muted mb-3">All file categories are currently using local storage. Configure environment variables to enable external storage for specific categories.</p>

<div class="row">
    <div class="col-md-4 mb-3">
        <div class="card border-secondary">
            <div class="card-body p-3">
                <h6 class="card-title mb-2"><i class="fas fa-paperclip mr-1"></i> Attachments</h6>
                <p class="card-text text-muted small mb-2">Request attachments and uploads</p>
                <span class="badge badge-success"><i class="fas fa-server mr-1"></i> Local</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-secondary">
            <div class="card-body p-3">
                <h6 class="card-title mb-2"><i class="fas fa-file-alt mr-1"></i> Documents</h6>
                <p class="card-text text-muted small mb-2">Client documents and shared files</p>
                <span class="badge badge-success"><i class="fas fa-server mr-1"></i> Local</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-secondary">
            <div class="card-body p-3">
                <h6 class="card-title mb-2"><i class="fas fa-file-signature mr-1"></i> Contracts</h6>
                <p class="card-text text-muted small mb-2">Contract PDFs and signed documents</p>
                <span class="badge badge-success"><i class="fas fa-server mr-1"></i> Local</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-secondary">
            <div class="card-body p-3">
                <h6 class="card-title mb-2"><i class="fas fa-file-invoice-dollar mr-1"></i> Invoices</h6>
                <p class="card-text text-muted small mb-2">Generated invoice PDFs</p>
                <span class="badge badge-success"><i class="fas fa-server mr-1"></i> Local</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-secondary">
            <div class="card-body p-3">
                <h6 class="card-title mb-2"><i class="fas fa-chart-bar mr-1"></i> Reports</h6>
                <p class="card-text text-muted small mb-2">Generated report files</p>
                <span class="badge badge-success"><i class="fas fa-server mr-1"></i> Local</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-secondary">
            <div class="card-body p-3">
                <h6 class="card-title mb-2"><i class="fas fa-download mr-1"></i> Exports</h6>
                <p class="card-text text-muted small mb-2">Data export files</p>
                <span class="badge badge-success"><i class="fas fa-server mr-1"></i> Local</span>
            </div>
        </div>
    </div>
</div>

<hr class="my-4">

<!-- External Provider Configuration Guide -->
<div class="card bg-light">
    <div class="card-body">
        <h5 class="card-title"><i class="fas fa-cloud mr-1"></i> External Storage Providers</h5>
        <p class="card-text text-muted">To use external storage providers, configure the following environment variables in your <code>.env</code> file:</p>
        
        <div class="accordion" id="storageProvidersAccordion">
            <!-- S3 -->
            <div class="card mb-2">
                <div class="card-header p-2" id="s3Header">
                    <button class="btn btn-link btn-sm text-left" type="button" data-toggle="collapse" data-target="#s3Collapse">
                        <i class="fab fa-aws mr-1"></i> Amazon S3
                    </button>
                </div>
                <div id="s3Collapse" class="collapse" data-parent="#storageProvidersAccordion">
                    <div class="card-body">
<pre class="mb-0 small bg-dark text-light p-2 rounded"><code>AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket</code></pre>
                    </div>
                </div>
            </div>
            
            <!-- DigitalOcean Spaces -->
            <div class="card mb-2">
                <div class="card-header p-2">
                    <button class="btn btn-link btn-sm text-left" type="button" data-toggle="collapse" data-target="#doSpacesCollapse">
                        <i class="fab fa-digital-ocean mr-1"></i> DigitalOcean Spaces
                    </button>
                </div>
                <div id="doSpacesCollapse" class="collapse" data-parent="#storageProvidersAccordion">
                    <div class="card-body">
<pre class="mb-0 small bg-dark text-light p-2 rounded"><code>DO_SPACES_KEY=your-key
DO_SPACES_SECRET=your-secret
DO_SPACES_REGION=nyc3
DO_SPACES_BUCKET=your-bucket
DO_SPACES_ENDPOINT=https://nyc3.digitaloceanspaces.com</code></pre>
                    </div>
                </div>
            </div>
            
            <!-- Cloudflare R2 -->
            <div class="card mb-2">
                <div class="card-header p-2">
                    <button class="btn btn-link btn-sm text-left" type="button" data-toggle="collapse" data-target="#r2Collapse">
                        <i class="fas fa-cloud mr-1"></i> Cloudflare R2
                    </button>
                </div>
                <div id="r2Collapse" class="collapse" data-parent="#storageProvidersAccordion">
                    <div class="card-body">
<pre class="mb-0 small bg-dark text-light p-2 rounded"><code>R2_ACCESS_KEY_ID=your-key
R2_SECRET_ACCESS_KEY=your-secret
R2_BUCKET=your-bucket
R2_ENDPOINT=https://your-account-id.r2.cloudflarestorage.com</code></pre>
                    </div>
                </div>
            </div>
            
            <!-- Backblaze B2 -->
            <div class="card mb-2">
                <div class="card-header p-2">
                    <button class="btn btn-link btn-sm text-left" type="button" data-toggle="collapse" data-target="#b2Collapse">
                        <i class="fas fa-cloud mr-1"></i> Backblaze B2
                    </button>
                </div>
                <div id="b2Collapse" class="collapse" data-parent="#storageProvidersAccordion">
                    <div class="card-body">
<pre class="mb-0 small bg-dark text-light p-2 rounded"><code>B2_KEY_ID=your-key
B2_APPLICATION_KEY=your-secret
B2_REGION=us-west-002
B2_BUCKET=your-bucket
B2_ENDPOINT=https://s3.us-west-002.backblazeb2.com</code></pre>
                    </div>
                </div>
            </div>
            
            <!-- MinIO -->
            <div class="card mb-2">
                <div class="card-header p-2">
                    <button class="btn btn-link btn-sm text-left" type="button" data-toggle="collapse" data-target="#minioCollapse">
                        <i class="fas fa-server mr-1"></i> MinIO (Self-hosted)
                    </button>
                </div>
                <div id="minioCollapse" class="collapse" data-parent="#storageProvidersAccordion">
                    <div class="card-body">
<pre class="mb-0 small bg-dark text-light p-2 rounded"><code>MINIO_KEY=your-key
MINIO_SECRET=your-secret
MINIO_REGION=us-east-1
MINIO_BUCKET=your-bucket
MINIO_ENDPOINT=http://localhost:9000</code></pre>
                    </div>
                </div>
            </div>
        </div>
        
        <p class="text-muted mt-3 mb-0 small">
            <i class="fas fa-info-circle mr-1"></i>
            After configuring environment variables, update the default provider setting above and save.
        </p>
    </div>
</div>

<div class="mt-6">
    <button type="button" wire:click="saveStorage" wire:loading.attr="disabled" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors flex items-center gap-2">
        <span wire:loading.remove wire:target="saveStorage">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z" />
            </svg>
            Save Storage Settings
        </span>
        <span wire:loading wire:target="saveStorage">
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Saving...
        </span>
    </button>
</div>
