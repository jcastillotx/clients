@php
    /** @var \App\Models\Request $request */
@endphp
STATEMENT OF WORK (SOW)

Client: {{ $clientName }}
Project: {{ $projectTitle }}
Date: {{ $date }}

## 1) Objectives
{{ $objectives }}

## 2) Deliverables
@foreach($deliverables as $d)
- {{ $d }}
@endforeach

## 3) Scope & Approach
{!! $scopeMarkdown !!}

## 4) Timeline
- Estimated duration (weeks): Low {{ $timeline['low'] }}, Mid {{ $timeline['mid'] }}, High {{ $timeline['high'] }}
@if(!empty($milestones))
Milestones:
@foreach($milestones as $m)
- {{ $m }}
@endforeach
@endif

## 5) Estimate (Hours & Cost)
Hourly rate: ${{ number_format($hourlyRate, 2) }}

Total hours (L/M/H): {{ $hours['low'] }} / {{ $hours['mid'] }} / {{ $hours['high'] }}
Cost range (L/M/H): ${{ $cost['low'] }} / ${{ $cost['mid'] }} / ${{ $cost['high'] }}

Task breakdown:
@foreach($tasks as $t)
- {{ $t['name'] }} — {{ $t['hours_low'] }}/{{ $t['hours_mid'] }}/{{ $t['hours_high'] }} hrs
@endforeach

## 6) Assumptions
@foreach($assumptions as $a)
- {{ $a }}
@endforeach

## 7) Out of Scope
@foreach($outOfScope as $o)
- {{ $o }}
@endforeach

## 8) Risks
@foreach($risks as $r)
- {{ $r }}
@endforeach

## 9) Acceptance & Next Steps
- Client reviews and approves this SOW.
- We schedule kickoff and confirm milestones.
- Any scope changes will be handled via change order.

