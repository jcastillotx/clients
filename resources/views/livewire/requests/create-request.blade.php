<div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">New Service Request</h3>
        </div>
        <form wire:submit="save">
            <div class="card-body">
                <div class="form-group">
                    <label for="title">Title <span class="text-danger">*</span></label>
                    <input type="text" 
                           wire:model="title" 
                           id="title" 
                           class="form-control @error('title') is-invalid @enderror"
                           placeholder="Brief summary of your request">
                    @error('title')
                    <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="description">Description <span class="text-danger">*</span></label>
                    <textarea wire:model="description" 
                              id="description" 
                              rows="6" 
                              class="form-control @error('description') is-invalid @enderror"
                              placeholder="Provide detailed information about your request..."></textarea>
                    @error('description')
                    <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="type">Request Type</label>
                            <select wire:model="type" id="type" class="form-control @error('type') is-invalid @enderror">
                                @foreach($types as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="priority">Priority</label>
                            <select wire:model="priority" id="priority" class="form-control @error('priority') is-invalid @enderror">
                                @foreach($priorities as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('priority')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="due_date">Preferred Due Date</label>
                            <input type="date" 
                                   wire:model="due_date" 
                                   id="due_date" 
                                   class="form-control @error('due_date') is-invalid @enderror"
                                   min="{{ now()->addDay()->format('Y-m-d') }}">
                            @error('due_date')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="attachments">Attachments</label>
                    <div class="custom-file">
                        <input type="file" 
                               wire:model="attachments" 
                               id="attachments" 
                               class="custom-file-input @error('attachments.*') is-invalid @enderror"
                               multiple>
                        <label class="custom-file-label" for="attachments">Choose files</label>
                    </div>
                    @error('attachments.*')
                    <span class="text-danger small">{{ $message }}</span>
                    @enderror
                    <small class="form-text text-muted">
                        Max file size: {{ config('client-portal.max_upload_size') / 1024 }}MB. 
                        Allowed types: {{ implode(', ', config('client-portal.allowed_file_types')) }}
                    </small>
                </div>

                @if(count($attachments) > 0)
                <div class="form-group">
                    <label>Selected Files:</label>
                    <ul class="list-group">
                        @foreach($attachments as $index => $attachment)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-file mr-2"></i>
                                {{ $attachment->getClientOriginalName() }}
                                <small class="text-muted ml-2">
                                    ({{ number_format($attachment->getSize() / 1024, 2) }} KB)
                                </small>
                            </span>
                            <button type="button" 
                                    wire:click="removeAttachment({{ $index }})" 
                                    class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-times"></i>
                            </button>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">
                        <i class="fas fa-paper-plane mr-1"></i> Submit Request
                    </span>
                    <span wire:loading wire:target="save">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Submitting...
                    </span>
                </button>
                <a href="{{ route('requests.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
