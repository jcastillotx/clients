<x-app-layout>
    <x-slot name="header">Storage Conflicts</x-slot>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <input class="form-control" placeholder="Search filename..." wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-md-3">
                    <select class="form-control" wire:model.live="resolution">
                        <option value="unresolved">Unresolved</option>
                        <option value="prefer_primary">Resolved (prefer primary)</option>
                        <option value="prefer_newest">Resolved (prefer newest)</option>
                        <option value="kept_both">Resolved (kept both)</option>
                    </select>
                </div>
                <div class="col-md-3 text-muted d-flex align-items-center justify-content-end">
                    Click a row to review candidates.
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-1"></i> Conflicts</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Candidates</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($conflicts as $c)
                                    <tr style="cursor:pointer" class="{{ $selectedConflictId === $c->id ? 'table-info' : '' }}"
                                        wire:click="select({{ $c->id }})">
                                        <td class="font-weight-bold">{{ $c->filename }}</td>
                                        <td>{{ is_array($c->candidates) ? count($c->candidates) : 0 }}</td>
                                        <td>
                                            <span class="badge badge-{{ $c->resolution === 'unresolved' ? 'warning' : 'success' }}">
                                                {{ $c->resolution }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-muted text-center py-4">No conflicts found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($conflicts->hasPages())
                    <div class="card-footer">
                        {{ $conflicts->links() }}
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-gavel mr-1"></i> Review & Resolve</h3>
                </div>
                <div class="card-body">
                    @php
                        $selected = $conflicts->firstWhere('id', $selectedConflictId);
                    @endphp

                    @if(!$selected)
                        <div class="text-muted">Select a conflict to view candidates.</div>
                    @else
                        <div class="mb-2">
                            <div class="text-muted">Filename</div>
                            <div class="h5 mb-0">{{ $selected->filename }}</div>
                            @if($selected->notes)
                                <div class="text-muted mt-1">{{ $selected->notes }}</div>
                            @endif
                        </div>

                        <hr>

                        <div class="mb-2 text-muted">Candidates</div>
                        @foreach((array) $selected->candidates as $cand)
                            @php
                                $cid = (int) ($cand['connection_id'] ?? 0);
                                $path = (string) ($cand['path'] ?? '');
                            @endphp
                            <div class="border rounded p-2 mb-2 {{ ($chosen_connection_id === $cid && $chosen_path === $path) ? 'bg-light' : '' }}">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div><strong>Connection #{{ $cid }}</strong></div>
                                        <div class="text-muted small">{{ $path }}</div>
                                        <div class="text-muted small">
                                            Size: {{ $cand['size_bytes'] ?? 0 }} bytes • Modified: {{ $cand['modified_at'] ?? '—' }}
                                        </div>
                                    </div>
                                    <div class="ml-2">
                                        <button class="btn btn-sm btn-outline-primary"
                                                wire:click="chooseCandidate({{ $selected->id }}, {{ $cid }}, @js($path))">
                                            Choose
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <button class="btn btn-primary" wire:click="resolveChosen" @disabled(!$chosen_connection_id)>
                            <i class="fas fa-check mr-1"></i> Resolve with chosen
                        </button>

                        <hr>

                        <div class="text-muted mb-2">Or apply a rule (also saves it in Storage Settings)</div>
                        <div class="btn-group">
                            <button class="btn btn-outline-secondary" wire:click="applyRuleToSelected('prefer_primary')">Prefer primary</button>
                            <button class="btn btn-outline-secondary" wire:click="applyRuleToSelected('prefer_newest')">Prefer newest</button>
                            <button class="btn btn-outline-secondary" wire:click="applyRuleToSelected('keep_both')">Keep both</button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

