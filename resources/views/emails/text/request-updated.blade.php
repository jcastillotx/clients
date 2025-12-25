Request updated

Request #{{ $request->id }}: {{ $request->title }}
@if($oldStatus && $newStatus)
Status: {{ $oldStatus }} -> {{ $newStatus }}
@endif
Current status: {{ $request->status }}
Priority: {{ $request->priority }}

View request: {{ route('requests.show', $request) }}

