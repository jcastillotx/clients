<div>
    <form wire:submit="save">
        <div class="form-group">
            <label for="title">Title <span class="text-danger">*</span></label>
            <input type="text" 
                   wire:model="title" 
                   id="title" 
                   class="form-control @error('title') is-invalid @enderror"
                   placeholder="Document title">
            @error('title')
            <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea wire:model="description" 
                      id="description" 
                      rows="3" 
                      class="form-control @error('description') is-invalid @enderror"
                      placeholder="Optional description..."></textarea>
            @error('description')
            <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="category">Category</label>
            <select wire:model="category" id="category" class="form-control @error('category') is-invalid @enderror">
                @foreach($categories as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('category')
            <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="file">File <span class="text-danger">*</span></label>
            <div class="custom-file">
                <input type="file" 
                       wire:model="file" 
                       id="file" 
                       class="custom-file-input @error('file') is-invalid @enderror">
                <label class="custom-file-label" for="file">
                    {{ $file ? $file->getClientOriginalName() : 'Choose file' }}
                </label>
            </div>
            @error('file')
            <span class="text-danger small">{{ $message }}</span>
            @enderror
            <small class="form-text text-muted">
                Max size: {{ config('client-portal.max_upload_size') / 1024 }}MB
            </small>
        </div>

        <div wire:loading wire:target="file" class="mb-3">
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%">
                    Uploading...
                </div>
            </div>
        </div>

        <div class="form-group mb-0">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">
                    <i class="fas fa-upload mr-1"></i> Upload Document
                </span>
                <span wire:loading wire:target="save">
                    <i class="fas fa-spinner fa-spin mr-1"></i> Uploading...
                </span>
            </button>
            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
        </div>
    </form>
</div>
