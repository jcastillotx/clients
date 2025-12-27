New critical website audit issues

Website: {{ $websiteUrl }}
Audit ID: #{{ $auditId }}

@if(!empty($scores))
Overall score: {{ $scores['overall'] ?? '—' }}
SEO: {{ $scores['seo'] ?? '—' }} | Performance: {{ $scores['performance'] ?? '—' }} | Accessibility: {{ $scores['accessibility'] ?? '—' }}
@endif

New critical issues:
@foreach((array) $issues as $i)
- {{ $i['category'] ?? 'general' }} · {{ $i['issue_type'] ?? 'issue' }}: {{ $i['description'] ?? '' }}@if(!empty($i['affected_url'])) ({{ $i['affected_url'] }})@endif
@endforeach

This alert is sent when newly-detected critical issues appear compared to the previous completed audit.

