<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Create Request</h2>
        </div>
        <a href="{{ route('admin.requests.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form wire:submit.prevent="save" class="row g-3">
                <div class="col-12 col-lg-6">
                    <label class="form-label">Client</label>
                    <select class="form-select" wire:model.live="client_id" required>
                        <option value="">Select a client…</option>
                        @foreach($clientOptions as $c)
                            <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                        @endforeach
                    </select>
                    @error('client_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label">Assign immediately (optional)</label>
                    <select class="form-select" wire:model.live="assigned_to">
                        <option value="">Unassigned</option>
                        @foreach($staffOptions as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                    @error('assigned_to') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Title</label>
                    <input type="text" class="form-control" wire:model.live.debounce.350ms="title" maxlength="255" required>
                    @error('title') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-lg-4">
                    <label class="form-label">Type</label>
                    <select class="form-select" wire:model.live="type" required>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-lg-4">
                    <label class="form-label">Priority</label>
                    <select class="form-select" wire:model.live="priority" required>
                        @foreach($priorities as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('priority') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-lg-4">
                    <label class="form-label">Due date (optional)</label>
                    <input type="date" class="form-control" wire:model.live="due_date">
                    @error('due_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" rows="6" wire:model.live.debounce.350ms="description" required></textarea>
                    @error('description') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Internal notes (staff only)</label>
                    <textarea class="form-control" rows="3" wire:model.live.debounce.350ms="internal_note"></textarea>
                    @error('internal_note') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Attachments</label>
                    <input type="file" class="form-control" wire:model="files" multiple>
                    @error('files.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    <div class="text-muted small mt-1">Allowed: pdf, doc, docx, jpg, png. Max size per file: {{ (int) config('client-portal.max_upload_size', 10240) / 1024 }}MB</div>
                </div>

                <div class="col-12">
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" wire:model.live="notify_admins">
                        <span class="form-check-label">Notify admins (email)</span>
                    </label>
                    <label class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" wire:model.live="notify_assignee">
                        <span class="form-check-label">Notify assigned staff (email)</span>
                    </label>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>Create</span>
                        <span wire:loading>Saving…</span>
                    </button>
                    <a href="{{ route('admin.requests.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

