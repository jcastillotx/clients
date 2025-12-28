<div>
    <h2 class="mb-3">Proposal analytics</h2>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-bar mr-1"></i> Proposal Analytics</h3>
        </div>
        <div class="card-body">
            @if(!$proposal)
                <div class="text-muted">Select a proposal.</div>
            @else
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="text-muted small">{{ $proposal->proposal_number }}</div>
                        <div class="h4 mb-0">{{ $proposal->title }}</div>
                    </div>
                    <div>
                        <span class="badge badge-secondary">{{ $proposal->status }}</span>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-4">
                        <div class="info-box">
                            <span class="info-box-icon bg-info"><i class="fas fa-eye"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Views</span>
                                <span class="info-box-number">{{ $proposal->views->count() }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-mouse-pointer"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Selections</span>
                                <span class="info-box-number">{{ $proposal->selections->count() }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box">
                            <span class="info-box-icon bg-secondary"><i class="fas fa-calendar"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Sent</span>
                                <span class="info-box-number">{{ $proposal->sent_at ? $proposal->sent_at->toDateString() : '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-eye mr-1"></i> Recent views</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Viewed at</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($proposal->views->sortByDesc('viewed_at')->take(20) as $v)
                                    <tr>
                                        <td>{{ $v->viewed_at?->toDateTimeString() }}</td>
                                        <td>{{ $v->ip_address }}</td>
                                    </tr>
                                @endforeach
                                @if($proposal->views->isEmpty())
                                    <tr><td colspan="2" class="text-muted p-3">No views yet.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-mouse-pointer mr-1"></i> Selections</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Created</th>
                                    <th>Tier</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($proposal->selections->sortByDesc('created_at')->take(20) as $s)
                                    <tr>
                                        <td>{{ $s->created_at?->toDateTimeString() }}</td>
                                        <td>{{ $s->selected_tier }}</td>
                                        <td>${{ number_format((float)($s->total_amount ?? 0), 2) }}</td>
                                    </tr>
                                @endforeach
                                @if($proposal->selections->isEmpty())
                                    <tr><td colspan="3" class="text-muted p-3">No selections yet.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

