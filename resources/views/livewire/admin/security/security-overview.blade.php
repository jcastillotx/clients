<div>
    <div class="page-pretitle">Security</div>
    <h2 class="page-title">Security settings</h2>
    <div class="text-muted mb-3">These settings are driven by environment variables/config.</div>

    <div class="row row-cards">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Admin IP allowlist</h3></div>
                <div class="card-body">
                    @if(empty($allowlist))
                        <div class="text-muted">Allowlist is empty (admin routes allow all IPs).</div>
                    @else
                        <div class="text-muted mb-2">Allowed IPs / CIDRs:</div>
                        <ul class="mb-0">
                            @foreach($allowlist as $ip)
                                <li><code>{{ $ip }}</code></li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="text-muted mt-3">
                        Update via <code>ADMIN_IP_ALLOWLIST</code> in your environment.
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">2FA enforcement</h3></div>
                <div class="card-body">
                    <div class="text-muted mb-2">Admin routes enforce 2FA:</div>
                    <div class="h3">{{ $enforce2fa ? 'Enabled' : 'Disabled' }}</div>
                    <div class="text-muted mt-3">
                        Update via <code>ENFORCE_ADMIN_2FA</code>.
                    </div>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">Audit retention</h3></div>
                <div class="card-body">
                    <div class="text-muted">Audit/activity logs are purged after:</div>
                    <div class="h3">{{ $retentionDays }} days</div>
                    <div class="text-muted mt-3">
                        Update via <code>AUDIT_RETENTION_DAYS</code>.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

