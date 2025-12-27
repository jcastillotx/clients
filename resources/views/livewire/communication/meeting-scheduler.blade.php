<x-app-layout>
    <x-slot name="header">Meetings</x-slot>

    @if($mode === 'client')
        <div class="row">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-calendar-plus mr-1"></i> Request a meeting</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="mb-1">Type</label>
                            <select class="form-control" wire:model.defer="meetingType">
                                <option value="kickoff">kickoff</option>
                                <option value="strategy">strategy</option>
                                <option value="review">review</option>
                                <option value="other">other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="mb-1">Title</label>
                            <input class="form-control" wire:model.defer="title" placeholder="Kickoff call">
                        </div>
                        <div class="form-group">
                            <label class="mb-1">Related request (optional)</label>
                            <select class="form-control" wire:model.defer="requestId">
                                <option value="">None</option>
                                @foreach($requests as $r)
                                    <option value="{{ $r->id }}">#{{ $r->id }} — {{ $r->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="col">
                                <label class="mb-1">Preferred time</label>
                                <input type="datetime-local" class="form-control" wire:model.defer="scheduledAt">
                            </div>
                            <div class="col">
                                <label class="mb-1">Duration (min)</label>
                                <input type="number" class="form-control" wire:model.defer="durationMinutes">
                            </div>
                        </div>
                        <div class="form-group mt-2">
                            <label class="mb-1">Agenda (optional)</label>
                            <textarea class="form-control" rows="3" wire:model.defer="agenda"></textarea>
                        </div>
                        <button class="btn btn-primary" wire:click="create">
                            <i class="fas fa-paper-plane mr-1"></i> Submit request
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-calendar mr-1"></i> Your meetings</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>When</th>
                                    <th>Link</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($meetings as $m)
                                    <tr>
                                        <td>{{ $m->title }}</td>
                                        <td>{{ $m->meeting_type }}</td>
                                        <td><span class="badge badge-secondary">{{ $m->status }}</span></td>
                                        <td class="text-muted">{{ $m->scheduled_at?->toDateTimeString() ?? '—' }}</td>
                                        <td>
                                            @if($m->meeting_link)
                                                <a href="{{ $m->meeting_link }}" target="_blank" rel="noopener">Open</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                @if($meetings->isEmpty())
                                    <tr><td colspan="5" class="text-muted p-3">No meetings yet.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-calendar-alt mr-1"></i> Meeting requests</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>When</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($meetings as $m)
                                    <tr>
                                        <td>{{ $m->client?->company_name }}</td>
                                        <td>{{ $m->title }}</td>
                                        <td>{{ $m->meeting_type }}</td>
                                        <td>{{ $m->status }}</td>
                                        <td class="text-muted">{{ $m->scheduled_at?->toDateTimeString() ?? '—' }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" wire:click="edit({{ $m->id }})">Edit</button>
                                        </td>
                                    </tr>
                                @endforeach
                                @if($meetings->isEmpty())
                                    <tr><td colspan="6" class="text-muted p-3">No meetings.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($editingMeetingId)
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-edit mr-1"></i> Edit meeting</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3">
                                    <label class="mb-1">Status</label>
                                    <select class="form-control" wire:model.defer="status">
                                        <option value="requested">requested</option>
                                        <option value="scheduled">scheduled</option>
                                        <option value="completed">completed</option>
                                        <option value="cancelled">cancelled</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="mb-1">Type</label>
                                    <select class="form-control" wire:model.defer="meetingType">
                                        <option value="kickoff">kickoff</option>
                                        <option value="strategy">strategy</option>
                                        <option value="review">review</option>
                                        <option value="other">other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="mb-1">Title</label>
                                    <input class="form-control" wire:model.defer="title">
                                </div>
                            </div>
                            <div class="form-row mt-2">
                                <div class="col-md-4">
                                    <label class="mb-1">Scheduled at</label>
                                    <input type="datetime-local" class="form-control" wire:model.defer="scheduledAt">
                                </div>
                                <div class="col-md-2">
                                    <label class="mb-1">Duration</label>
                                    <input type="number" class="form-control" wire:model.defer="durationMinutes">
                                </div>
                                <div class="col-md-6">
                                    <label class="mb-1">Meeting link</label>
                                    <input class="form-control" wire:model.defer="meetingLink" placeholder="https://zoom.us/j/...">
                                </div>
                            </div>
                            <div class="form-group mt-2">
                                <label class="mb-1">Agenda</label>
                                <textarea class="form-control" rows="3" wire:model.defer="agenda"></textarea>
                            </div>
                            <button class="btn btn-primary" wire:click="saveAdmin"><i class="fas fa-save mr-1"></i> Save</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</x-app-layout>

