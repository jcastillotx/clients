<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Reports &amp; Analytics</h2>
            <div class="text-muted small">Financial, client, request, performance, and storage reporting.</div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="h3 mb-1">Financial Reports</div>
                    <div class="text-muted small mb-3">Revenue, receivables, invoice aging, payments.</div>
                    <a class="btn btn-primary" href="{{ route('admin.reports.financial') }}">Open</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="h3 mb-1">Client Reports</div>
                    <div class="text-muted small mb-3">Acquisition, tiers, activity, churn risk.</div>
                    <a class="btn btn-primary" href="{{ route('admin.reports.clients') }}">Open</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="h3 mb-1">Request Reports</div>
                    <div class="text-muted small mb-3">Volume, completion time, SLA, staff output.</div>
                    <a class="btn btn-primary" href="{{ route('admin.reports.requests') }}">Open</a>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="h3 mb-1">Custom report builder</div>
                    <div class="text-muted small mb-3">Select metrics + date range, export, save templates, schedule email delivery.</div>
                    <a class="btn btn-outline-primary" href="{{ route('admin.reports.builder') }}">Open builder</a>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="h3 mb-1">Performance Reports</div>
                    <div class="text-muted small mb-3">Response/resolution trends and staff workload.</div>
                    <a class="btn btn-outline-primary" href="{{ route('admin.reports.performance') }}">Open</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="h3 mb-1">Storage Reports</div>
                    <div class="text-muted small mb-3">Usage, file types, large file alerts, sync success rate.</div>
                    <a class="btn btn-outline-primary" href="{{ route('admin.reports.storage') }}">Open</a>
                </div>
            </div>
        </div>
    </div>
</div>

