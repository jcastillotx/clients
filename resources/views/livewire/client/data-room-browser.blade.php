
<x-slot name="header">Data Rooms</x-slot>

<div class="row">
    {{-- Sidebar: Room List --}}
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-shield-alt mr-1"></i> Secure Data Rooms</h3>
            </div>
            <div class="list-group list-group-flush">
                @forelse($rooms as $r)
                    <button
                        wire:click="selectRoom({{ $r->id }})"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $roomId === $r->id ? 'active' : '' }}"
                    >
                        <span>
                            <i class="fas fa-lock mr-2"></i>
                            {{ $r->name }}
                        </span>
                        @if($r->isLocked())
                            <span class="badge badge-warning">Locked</span>
                        @else
                            <span class="badge badge-primary badge-pill">{{ $r->file_count }}</span>
                        @endif
                    </button>
                @empty
                    <div class="list-group-item text-muted">No data rooms available</div>
                @endforelse
            </div>
        </div>

        {{-- Security Info --}}
        @if($room)
            <div class="card mt-3">
                <div class="card-header bg-success text-white">
                    <h3 class="card-title mb-0"><i class="fas fa-shield-alt mr-1"></i> Security Status</h3>
                </div>
                <div class="card-body small">
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="fas fa-lock text-success mr-1"></i> Encryption</span>
                        <span class="badge badge-success">AES-256-GCM</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="fas fa-globe text-success mr-1"></i> Transit</span>
                        <span class="badge badge-success">TLS 1.3</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="fas fa-user-shield text-success mr-1"></i> 2FA Required</span>
                        <span class="badge badge-{{ $room->require_2fa ? 'success' : 'secondary' }}">
                            {{ $room->require_2fa ? 'Yes' : 'No' }}
                        </span>
                    </div>
                    <hr>
                    <div class="text-muted">
                        <small>SOC2 Type II Compliant</small>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Main Content: File Browser --}}
    <div class="col-md-9">
        @if($room)
            {{-- Room Header --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">
                            <i class="fas fa-folder-open mr-1"></i>
                            {{ $room->name }}
                        </h3>
                        @if($room->description)
                            <small class="text-muted">{{ $room->description }}</small>
                        @endif
                    </div>
                    <div class="btn-group">
                        @if($access && $access->can_upload)
                            <button wire:click="$set('showUploadModal', true)" class="btn btn-primary btn-sm">
                                <i class="fas fa-upload mr-1"></i> Upload
                            </button>
                            <button wire:click="$set('showNewFolderModal', true)" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-folder-plus mr-1"></i> New Folder
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Breadcrumbs --}}
                <div class="card-body border-bottom py-2">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 bg-transparent p-0">
                            <li class="breadcrumb-item">
                                <a href="#" wire:click.prevent="navigateToBreadcrumb(null)">
                                    <i class="fas fa-home"></i> Root
                                </a>
                            </li>
                            @foreach($breadcrumbs as $crumb)
                                <li class="breadcrumb-item">
                                    <a href="#" wire:click.prevent="navigateToBreadcrumb({{ $crumb->id }})">
                                        {{ $crumb->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                </div>

                {{-- Search & Actions --}}
                <div class="card-body border-bottom py-2">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="searchQuery"
                                    class="form-control"
                                    placeholder="Search files..."
                                >
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            @if(count($selectedFiles) > 0)
                                <span class="text-muted mr-2">{{ count($selectedFiles) }} selected</span>
                                @if($access && $access->can_download)
                                    <button class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-download mr-1"></i> Download
                                    </button>
                                @endif
                                @if($access && $access->can_delete)
                                    <button
                                        wire:click="deleteSelectedFiles"
                                        wire:confirm="Are you sure you want to delete the selected files?"
                                        class="btn btn-sm btn-outline-danger"
                                    >
                                        <i class="fas fa-trash mr-1"></i> Delete
                                    </button>
                                @endif
                                <button wire:click="clearSelection" class="btn btn-sm btn-outline-secondary">
                                    Clear
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- File List --}}
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 30px;">
                                        <input type="checkbox" wire:click="selectAllFiles">
                                    </th>
                                    <th>Name</th>
                                    <th>Size</th>
                                    <th>Uploaded</th>
                                    <th>By</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Navigate Up --}}
                                @if($folderId)
                                    <tr class="bg-light">
                                        <td></td>
                                        <td colspan="5">
                                            <a href="#" wire:click.prevent="navigateUp" class="text-muted">
                                                <i class="fas fa-level-up-alt mr-2"></i> ..
                                            </a>
                                        </td>
                                    </tr>
                                @endif

                                {{-- Folders --}}
                                @foreach($folders as $folder)
                                    <tr>
                                        <td></td>
                                        <td>
                                            <a href="#" wire:click.prevent="openFolder({{ $folder->id }})" class="text-dark">
                                                <i class="fas fa-folder text-warning mr-2"></i>
                                                {{ $folder->name }}
                                            </a>
                                        </td>
                                        <td class="text-muted">—</td>
                                        <td class="text-muted">{{ $folder->created_at->format('M j, Y') }}</td>
                                        <td class="text-muted">{{ $folder->creator?->name ?? '—' }}</td>
                                        <td></td>
                                    </tr>
                                @endforeach

                                {{-- Files --}}
                                @foreach($files as $file)
                                    <tr>
                                        <td>
                                            <input
                                                type="checkbox"
                                                wire:click="toggleFileSelection({{ $file->id }})"
                                                @checked(in_array($file->id, $selectedFiles))
                                            >
                                        </td>
                                        <td>
                                            <i class="{{ $file->icon_class }} mr-2"></i>
                                            {{ $file->name }}
                                            @if($file->is_locked)
                                                <i class="fas fa-lock text-warning ml-1" title="Locked"></i>
                                            @endif
                                        </td>
                                        <td class="text-muted">{{ $file->human_file_size }}</td>
                                        <td class="text-muted">{{ $file->created_at->format('M j, Y') }}</td>
                                        <td class="text-muted">{{ $file->uploader?->name ?? '—' }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                @if($access && $access->can_download && $room->allow_download)
                                                    <button
                                                        wire:click="downloadFile({{ $file->id }})"
                                                        class="btn btn-outline-primary"
                                                        title="Download"
                                                    >
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                @endif
                                                @if($access && $access->can_delete)
                                                    <button
                                                        wire:click="deleteFile({{ $file->id }})"
                                                        wire:confirm="Are you sure you want to delete this file?"
                                                        class="btn btn-outline-danger"
                                                        title="Delete"
                                                    >
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                @if($folders->isEmpty() && $files->isEmpty())
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>
                                            This folder is empty
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Footer with Stats --}}
                <div class="card-footer text-muted small">
                    <i class="fas fa-file mr-1"></i> {{ $files->count() }} files
                    <span class="mx-2">|</span>
                    <i class="fas fa-folder mr-1"></i> {{ $folders->count() }} folders
                    <span class="mx-2">|</span>
                    <i class="fas fa-database mr-1"></i> {{ $room->human_total_size }}
                </div>
            </div>
        @else
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-lock fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Data Room Selected</h4>
                    <p class="text-muted">Select a data room from the list to view its contents.</p>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Upload Modal --}}
@if($showUploadModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-upload mr-1"></i> Upload Files</h5>
                    <button type="button" class="close" wire:click="$set('showUploadModal', false)">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small">
                        <i class="fas fa-shield-alt mr-1"></i>
                        Files are encrypted with AES-256 before upload and transmitted via TLS 1.3.
                    </div>
                    <div class="form-group">
                        <label>Select Files</label>
                        <input
                            type="file"
                            wire:model="uploadFiles"
                            multiple
                            class="form-control-file"
                        >
                        @error('uploadFiles.*') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    @if(count($uploadFiles) > 0)
                        <div class="mt-2">
                            <strong>Selected:</strong>
                            <ul class="mb-0 small">
                                @foreach($uploadFiles as $file)
                                    <li>{{ $file->getClientOriginalName() }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showUploadModal', false)">
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        wire:click="uploadFile"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="uploadFile">
                            <i class="fas fa-upload mr-1"></i> Upload
                        </span>
                        <span wire:loading wire:target="uploadFile">
                            <i class="fas fa-spinner fa-spin mr-1"></i> Encrypting & Uploading...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- New Folder Modal --}}
@if($showNewFolderModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-folder-plus mr-1"></i> New Folder</h5>
                    <button type="button" class="close" wire:click="$set('showNewFolderModal', false)">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Folder Name</label>
                        <input
                            type="text"
                            wire:model="newFolderName"
                            class="form-control"
                            placeholder="Enter folder name"
                        >
                        @error('newFolderName') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showNewFolderModal', false)">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="createFolder">
                        <i class="fas fa-folder-plus mr-1"></i> Create Folder
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Download Handler Script --}}
@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('downloadFile', (data) => {
            const link = document.createElement('a');
            link.href = 'data:' + data[0].mimeType + ';base64,' + data[0].content;
            link.download = data[0].filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    });
</script>
@endpush
