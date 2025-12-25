<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Requests · Kanban</h2>
            <div class="text-muted small">Drag cards between columns to change status.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.requests.create') }}" class="btn btn-primary">Create Request</a>
            <button type="button" class="btn btn-outline-secondary" wire:click="$set('viewMode','table')">Table</button>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-12 col-lg-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" placeholder="Title, description, client…" wire:model.live.debounce.350ms="search">
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label">Client (search)</label>
                    <input type="text" class="form-control" placeholder="Start typing…" wire:model.live.debounce.350ms="clientSearch">
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label">Client</label>
                    <select class="form-select" wire:model.live="clientId">
                        <option value="">All clients</option>
                        @foreach($clientOptions as $c)
                            <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label">Assigned to</label>
                    <select class="form-select" wire:model.live="assignedTo">
                        <option value="">Anyone</option>
                        @foreach($staffOptions as $u)
                            <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        @foreach($columns as $status)
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">{{ $statusLabels[$status] ?? $status }}</div>
                    </div>
                    <div
                        class="card-body"
                        style="min-height: 180px"
                        ondragover="event.preventDefault()"
                        ondrop="window.__adminRequestDrop(event, '{{ $status }}')"
                    >
                        @forelse(($boards[$status] ?? []) as $r)
                            <div
                                class="card mb-2"
                                draggable="true"
                                ondragstart="window.__adminRequestDrag(event, {{ $r->id }})"
                            >
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div class="fw-semibold">
                                            <a href="{{ route('admin.requests.show', $r) }}" class="text-reset text-decoration-none">
                                                #{{ $r->id }} · {{ $r->title }}
                                            </a>
                                        </div>
                                        <div class="text-muted small">{{ $r->due_date?->format('m/d') }}</div>
                                    </div>
                                    <div class="text-muted small">
                                        {{ $r->client?->company_name ?? ('Client #' . $r->client_id) }}
                                    </div>
                                    <div class="d-flex flex-wrap gap-1 mt-2">
                                        <span class="badge bg-{{ $r->status_color }}">{{ $statusLabels[$r->status] ?? $r->status }}</span>
                                        {!! $r->priority_badge !!}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted small">No requests.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @push('scripts')
        <script>
            (function () {
                window.__adminDraggedRequestId = null;
                window.__adminRequestDrag = function (e, id) {
                    window.__adminDraggedRequestId = id;
                    try { e.dataTransfer.setData('text/plain', String(id)); } catch (err) {}
                };
                window.__adminRequestDrop = function (e, status) {
                    e.preventDefault();
                    const id = window.__adminDraggedRequestId || Number(e.dataTransfer.getData('text/plain'));
                    if (!id) return;
                    try {
                        @this.call('moveRequest', Number(id), String(status));
                    } catch (err) {}
                };
            })();
        </script>
    @endpush
</div>

