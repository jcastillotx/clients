@extends('emails._layout')

@section('content')
    <p>Here is your scheduled report:</p>
    <p><strong>{{ $title }}</strong></p>
    <p class="muted">Range: {{ $from }} → {{ $to }}</p>
    <p>The report is attached as a CSV file.</p>
@endsection

