<div>
    <div class="text-muted mb-2">Version history</div>
    <div class="table-responsive">
        <table class="table table-sm table-striped mb-0">
            <thead>
                <tr>
                    <th>Version</th>
                    <th>Filename</th>
                    <th>Size</th>
                    <th class="text-right">Download</th>
                </tr>
            </thead>
            <tbody>
                @forelse($versions as $v)
                    <tr>
                        <td>v{{ $v->version }}</td>
                        <td class="text-muted">{{ $v->original_filename }}</td>
                        <td>{{ number_format($v->file_size / 1024, 2) }} KB</td>
                        <td class="text-right">
                            @if($v->download_url)
                                <a class="btn btn-xs btn-outline-secondary" href="{{ $v->download_url }}">
                                    <i class="fas fa-download"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted text-center py-2">No versions recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

