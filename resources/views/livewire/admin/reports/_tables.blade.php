@php
    $tables = $tables ?? [];
@endphp

@foreach($tables as $name => $rows)
    @php
        $rows = is_array($rows) ? $rows : [];
        $first = $rows[0] ?? null;
        $headings = is_array($first) ? array_keys($first) : [];
    @endphp
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-table mr-1"></i> {{ $name }}</h3>
        </div>
        <div class="card-body p-0">
            @if(empty($rows))
                <div class="p-3 text-muted">(no data)</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                @foreach($headings as $h)
                                    <th>{{ str_replace('Sla', 'SLA', \Illuminate\Support\Str::headline($h)) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    @foreach($headings as $h)
                                        <td>
                                            @php $v = is_array($row) ? ($row[$h] ?? null) : null; @endphp
                                            @php $hk = strtolower((string) $h); @endphp
                                            @if(is_numeric($v) && (str_contains($hk, 'revenue') || str_contains($hk, 'amount') || str_contains($hk, 'profit') || str_contains($hk, 'cost')))
                                                @money($v)
                                            @else
                                                {{ is_array($v) ? json_encode($v) : $v }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endforeach

