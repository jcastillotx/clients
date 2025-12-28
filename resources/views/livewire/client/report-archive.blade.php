
    <x-slot name="header">Report archive</x-slot>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-archive mr-1"></i> Delivered reports</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Range</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deliveries as $d)
                        @php $m = (array) ($d->meta ?? []); @endphp
                        <tr>
                            <td class="text-muted">{{ $d->generated_at?->toDateString() ?? $d->created_at?->toDateString() }}</td>
                            <td>{{ $d->category ?? '—' }}</td>
                            <td class="text-muted">{{ ($m['start'] ?? '—') . ' → ' . ($m['end'] ?? '—') }}</td>
                            <td><span class="badge badge-{{ $d->status === 'sent' ? 'success' : ($d->status === 'failed' ? 'danger' : 'secondary') }}">{{ $d->status }}</span></td>
                            <td class="text-right">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ $d->download_url }}" target="_blank" rel="noopener">Download</a>
                            </td>
                        </tr>
                    @endforeach
                    @if($deliveries->isEmpty())
                        <tr><td colspan="5" class="text-muted p-3">No reports delivered yet.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

