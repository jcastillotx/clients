@extends('layouts.admin')

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">API Documentation</h2>
                    <div class="text-muted">Interactive OpenAPI docs generated from the codebase.</div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('scramble.docs.ui') }}" class="btn btn-primary" target="_blank" rel="noreferrer">
                        Open in new tab
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <iframe
                    src="{{ route('scramble.docs.ui') }}"
                    style="width: 100%; height: 75vh; border: 0;"
                    title="API Documentation"
                ></iframe>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Quick examples</h3>
            </div>
            <div class="card-body">
                <div class="mb-2 fw-bold">cURL</div>
                <pre class="bg-light p-3 rounded"><code>curl -X POST "{{ url('/api/v1/requests') }}" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"client_id": 1, "title": "API request", "description": "Created via integration"}'</code></pre>

                <div class="mb-2 fw-bold">Node.js (fetch)</div>
                <pre class="bg-light p-3 rounded"><code>await fetch("{{ url('/api/v1/requests') }}", {
  method: "POST",
  headers: {
    Authorization: "Bearer YOUR_TOKEN",
    "Content-Type": "application/json",
    Accept: "application/json",
  },
  body: JSON.stringify({ client_id: 1, title: "API request", description: "Created via integration" }),
});</code></pre>

                <div class="mb-2 fw-bold">Python (requests)</div>
                <pre class="bg-light p-3 rounded"><code>import requests

resp = requests.post(
  "{{ url('/api/v1/requests') }}",
  headers={"Authorization": "Bearer YOUR_TOKEN", "Accept": "application/json"},
  json={"client_id": 1, "title": "API request", "description": "Created via integration"},
)
print(resp.status_code, resp.json())</code></pre>

                <hr>
                <div class="text-muted">
                    <strong>Zapier / Make.com</strong>: use the Admin → Webhooks screen to register an endpoint URL, then choose an event like
                    <code>request.created</code> or <code>invoice.paid</code>. Zapier/Make can also receive payloads via “Catch Hook” modules.
                </div>
            </div>
        </div>
    </div>
@endsection

