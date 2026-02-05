<div>
    <h2 class="mb-3">Meetings</h2>

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
                            <select class="form-control" wire:model="meetingType">
                                <option value="kickoff">kickoff</option>
                                <option value="strategy">strategy</option>
                                <option value="review">review</option>
                                <option value="other">other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="mb-1">Title</label>
                            <input class="form-control" wire:model="title" placeholder="Kickoff call">
                        </div>
                        <div class="form-group">
                            <label class="mb-1">Related request (optional)</label>
                            <select class="form-control" wire:model="requestId">
                                <option value="">None</option>
                                @foreach($requests as $r)
                                    <option value="{{ $r->id }}">#{{ $r->id }} — {{ $r->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="col">
                                <label class="mb-1">Preferred time</label>
                                <input type="datetime-local" class="form-control" wire:model="scheduledAt">
                            </div>
                            <div class="col">
                                <label class="mb-1">Duration (min)</label>
                                <input type="number" class="form-control" wire:model="durationMinutes">
                            </div>
                        </div>
                        <div class="form-group mt-2">
                            <label class="mb-1">Agenda (optional)</label>
                            <textarea class="form-control" rows="3" wire:model="agenda"></textarea>
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
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="min-w-48">Title</th>
                                        <th class="min-w-28">Type</th>
                                        <th class="min-w-28">Status</th>
                                        <th class="min-w-44">When</th>
                                        <th class="min-w-20">Link</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($meetings as $m)
                                        <tr>
                                            <td class="font-medium">{{ $m->title }}</td>
                                            <td class="capitalize">{{ $m->meeting_type }}</td>
                                            <td>
                                                @php
                                                    $statusClasses = match($m->status) {
                                                        'scheduled' => 'bg-green-100 text-green-800',
                                                        'completed' => 'bg-blue-100 text-blue-800',
                                                        'cancelled' => 'bg-red-100 text-red-800',
                                                        default => 'bg-slate-100 text-slate-800'
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClasses }}">
                                                    {{ $m->status }}
                                                </span>
                                            </td>
                                            <td class="text-slate-600">{{ $m->scheduled_at?->format('M d, Y g:i A') ?? '—' }}</td>
                                            <td>
                                                @if($m->meeting_link)
                                                    <a href="{{ $m->meeting_link }}" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-800 hover:underline">
                                                        Open
                                                    </a>
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if($meetings->isEmpty())
                                        <tr><td colspan="5" class="text-center text-slate-500 py-8">No meetings yet.</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
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
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="min-w-48">Client</th>
                                        <th class="min-w-48">Title</th>
                                        <th class="min-w-28">Type</th>
                                        <th class="min-w-28">Status</th>
                                        <th class="min-w-44">When</th>
                                        <th class="w-24"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($meetings as $m)
                                        <tr>
                                            <td class="font-medium">{{ $m->client?->company_name }}</td>
                                            <td>{{ $m->title }}</td>
                                            <td class="capitalize">{{ $m->meeting_type }}</td>
                                            <td>
                                                @php
                                                    $statusClasses = match($m->status) {
                                                        'scheduled' => 'bg-green-100 text-green-800',
                                                        'completed' => 'bg-blue-100 text-blue-800',
                                                        'cancelled' => 'bg-red-100 text-red-800',
                                                        default => 'bg-slate-100 text-slate-800'
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClasses }}">
                                                    {{ $m->status }}
                                                </span>
                                            </td>
                                            <td class="text-slate-600">{{ $m->scheduled_at?->format('M d, Y g:i A') ?? '—' }}</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" wire:click="edit({{ $m->id }})">Edit</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @if($meetings->isEmpty())
                                    <tr><td colspan="6" class="text-center text-slate-500 py-8">No meetings.</td></tr>
                                @endif
                            </tbody>
                        </table>
                        </div>
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
                                    <select class="form-control" wire:model="status">
                                        <option value="requested">requested</option>
                                        <option value="scheduled">scheduled</option>
                                        <option value="completed">completed</option>
                                        <option value="cancelled">cancelled</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="mb-1">Type</label>
                                    <select class="form-control" wire:model="meetingType">
                                        <option value="kickoff">kickoff</option>
                                        <option value="strategy">strategy</option>
                                        <option value="review">review</option>
                                        <option value="other">other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="mb-1">Title</label>
                                    <input class="form-control" wire:model="title">
                                </div>
                            </div>
                            <div class="form-row mt-2">
                                <div class="col-md-4">
                                    <label class="mb-1">Scheduled at</label>
                                    <input type="datetime-local" class="form-control" wire:model="scheduledAt">
                                </div>
                                <div class="col-md-2">
                                    <label class="mb-1">Duration</label>
                                    <input type="number" class="form-control" wire:model="durationMinutes">
                                </div>
                                <div class="col-md-6">
                                    <label class="mb-1">Meeting link</label>
                                    <input class="form-control" wire:model="meetingLink" placeholder="https://zoom.us/j/...">
                                </div>
                            </div>
                            <div class="form-group mt-2">
                                <label class="mb-1">Agenda</label>
                                <textarea class="form-control" rows="3" wire:model="agenda"></textarea>
                            </div>
                            <button class="btn btn-primary" wire:click="saveAdmin"><i class="fas fa-save mr-1"></i> Save</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

