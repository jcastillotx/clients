New request created

Request #{{ $request->id }}: {{ $request->title }}
Type: {{ $request->type }}
Priority: {{ $request->priority }}
Status: {{ $request->status }}

View request: {{ route('requests.show', $request) }}

