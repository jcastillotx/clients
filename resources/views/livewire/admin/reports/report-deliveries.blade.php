<div>
    <div class="page-pretitle">Admin</div>
    <h2 class="page-title">Report deliveries</h2>
    <div class="text-muted mb-3">Archive of generated PDFs for scheduled reports.</div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Generated</th>
                        <th>Status</th>
                        <th>Category</th>
                        <th>Client</th>
                        <th>Recipients</th>
                        <th>Download</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deliveries as $d)
                        <tr>
                            <td class="text-muted">{{ $d->generated_at?->toDateTimeString() ?? $d->created_at?->toDateTimeString() }}</td>
                            <td>
                                <span class="badge bg-{{ $d->status === 'sent' ? 'success' : ($d->status === 'failed' ? 'danger' : 'secondary') }}">{{ $d->status }}</span>
                            </td>
                            <td>{{ $d->category ?? '—' }}</td>
                            <td>{{ $d->client?->company_name ?? '—' }}</td>
                            <td class="text-muted small">{{ is_array($d->recipients) ? implode(', ', $d->recipients) : '—' }}</td>
                            <td>
                                <a href="{{ $d->download_url }}" target="_blank" rel="noopener">Download</a>
                            </td>
                        </tr>
                    @endforeach
                    @if($deliveries->isEmpty())
                        <tr><td colspan="6" class="text-muted p-3">No deliveries yet.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

