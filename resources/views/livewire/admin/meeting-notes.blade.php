<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Meeting Notes</h2>
            <div class="text-muted small">Upload audio → transcript → summary + action items.</div>
        </div>
        <button class="btn btn-outline-secondary" wire:click="refreshLatest">Refresh</button>
    </div>

    <div class="card mb-3">
        <div class="card-header"><div class="card-title mb-0">Transcribe & Summarize</div></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-12">
                    <label class="form-label">Meeting audio</label>
                    <input type="file" class="form-control" wire:model="audio" accept="audio/*">
                    @error('audio') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Purpose (optional)</label>
                    <input class="form-control" wire:model="purpose" placeholder="e.g. Weekly status update">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Participants (optional)</label>
                    <input class="form-control" wire:model="participants" placeholder="Jane, John, Client Rep">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" wire:click="queueTranscription" wire:loading.attr="disabled">
                        Queue transcription
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title mb-0">Latest Result</div>
            @if($latestTask)
                <div class="text-muted small">Task #{{ $latestTask->id }} · {{ $latestTask->status }}</div>
            @endif
        </div>
        <div class="card-body">
            @if(!$output)
                <div class="text-muted">No output yet.</div>
            @else
                <pre class="bg-light p-3 rounded" style="white-space: pre-wrap;">{{ json_encode($output, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
            @endif
        </div>
    </div>
</div>

