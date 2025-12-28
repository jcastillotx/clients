<div>
    <h2 class="mb-3">Feedback</h2>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-poll mr-1"></i> {{ $survey->name }}</h3>
        </div>
        <div class="card-body">
            @if($response->submitted_at)
                <div class="alert alert-success mb-0">Thanks — feedback submitted.</div>
            @else
                @if($survey->description)
                    <div class="text-muted mb-3">{{ $survey->description }}</div>
                @endif

                @foreach($questions as $q)
                    <div class="form-group">
                        <label class="mb-1 font-weight-bold">
                            {{ $q->prompt }}
                            @if($q->is_required)<span class="text-danger">*</span>@endif
                        </label>

                        @if($q->type === 'nps')
                            <select class="form-control" wire:model.defer="answers.{{ $q->id }}">
                                <option value="">Select…</option>
                                @for($i=0;$i<=10;$i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        @elseif($q->type === 'rating')
                            <select class="form-control" wire:model.defer="answers.{{ $q->id }}">
                                <option value="">Select…</option>
                                @for($i=1;$i<=5;$i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        @else
                            <textarea class="form-control" rows="3" wire:model.defer="answers.{{ $q->id }}"></textarea>
                        @endif
                    </div>
                @endforeach

                <button class="btn btn-primary" wire:click="submit">
                    <i class="fas fa-paper-plane mr-1"></i> Submit feedback
                </button>
            @endif
        </div>
    </div>
</div>

