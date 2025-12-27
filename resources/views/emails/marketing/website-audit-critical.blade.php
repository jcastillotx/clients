@extends('emails._layout')

@section('content')
    <h2 style="margin: 0 0 12px 0;">New critical website audit issues</h2>
    <p style="margin: 0 0 10px 0;">
        Website: <strong>{{ $websiteUrl }}</strong><br>
        Audit ID: <strong>#{{ $auditId }}</strong>
    </p>

    @if(!empty($scores))
        <p style="margin: 0 0 12px 0;">
            Overall score: <strong>{{ $scores['overall'] ?? '—' }}</strong>
            (SEO {{ $scores['seo'] ?? '—' }}, Performance {{ $scores['performance'] ?? '—' }}, Accessibility {{ $scores['accessibility'] ?? '—' }})
        </p>
    @endif

    <h3 style="margin: 16px 0 8px 0;">New critical issues</h3>
    <ul style="margin: 0; padding-left: 18px;">
        @foreach((array) $issues as $i)
            <li style="margin: 0 0 8px 0;">
                <strong>{{ $i['category'] ?? 'general' }} · {{ $i['issue_type'] ?? 'issue' }}</strong><br>
                {{ $i['description'] ?? '' }}
                @if(!empty($i['affected_url']))
                    <br><span style="color:#555;">Affected: {{ $i['affected_url'] }}</span>
                @endif
            </li>
        @endforeach
    </ul>

    <p style="margin: 16px 0 0 0; color:#555;">
        This alert is sent when newly-detected critical issues appear compared to the previous completed audit.
    </p>
@endsection

