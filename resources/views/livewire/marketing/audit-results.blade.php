<div>
    <h2 class="mb-3">Website Audit Results</h2>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>URL</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Score</th>
                        <th>Started</th>
                        <th>Completed</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($audits as $a)
                        <tr>
                            <td>#{{ $a->id }}</td>
                            <td style="max-width: 360px; overflow: hidden; text-overflow: ellipsis;">
                                {{ $a->website_url }}
                            </td>
                            <td>{{ $a->audit_type }}</td>
                            <td>{{ $a->status }}</td>
                            <td>{{ $a->score ?? '—' }}</td>
                            <td>{{ optional($a->started_at)->toDateTimeString() }}</td>
                            <td>{{ optional($a->completed_at)->toDateTimeString() }}</td>
                            <td class="text-right">
                                @if($a->status === 'completed')
                                    <a class="btn btn-outline-primary btn-sm"
                                       href="{{ route('admin.marketing.website-audits.pdf', ['websiteAudit' => $a->id]) }}">
                                        PDF
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-muted">No audits yet.</td></tr>
                    @endforelse
                </tbody>
            </table>

            {{ $audits->links() }}
        </div>
    </div>
</div>

