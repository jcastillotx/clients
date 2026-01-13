<div>
    <div class="alert alert-warning">
        <strong>Heads up:</strong> This does <em>not</em> update code from the web server. It dispatches a GitHub Actions workflow
        which should run your real deployment pipeline (build, migrate, cache, restart workers, etc).
    </div>

    @php
        $configured = (bool) ($updateStatus['configured'] ?? false);
        $owner = $updateStatus['owner'] ?? null;
        $repo = $updateStatus['repo'] ?? null;
        $branch = $updateStatus['branch'] ?? null;
        $workflow = $updateStatus['workflow'] ?? null;
        $currentSha = $updateStatus['current_sha'] ?? '';
        $latestSha = $updateStatus['latest_sha'] ?? null;
        $behindBy = $updateStatus['behind_by'] ?? null;
        $compareUrl = $updateStatus['compare_url'] ?? null;
        $actionsUrl = $updateStatus['actions_url'] ?? null;
        $checkedAt = $updateStatus['checked_at'] ?? null;
    @endphp

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">GitHub Updates</h5>
        </div>
        <div class="card-body">
            @if(! $configured)
                <div class="alert alert-danger mb-3">
                    <strong>Not configured.</strong> Set <code>GITHUB_UPDATES_OWNER</code>, <code>GITHUB_UPDATES_REPO</code>,
                    <code>GITHUB_UPDATES_WORKFLOW</code>, and <code>GITHUB_UPDATES_TOKEN</code> in your environment.
                </div>
            @endif

            <dl class="row mb-0">
                <dt class="col-sm-3">Repo</dt>
                <dd class="col-sm-9">
                    <code>{{ $owner ?: '—' }}/{{ $repo ?: '—' }}</code>
                </dd>

                <dt class="col-sm-3">Branch</dt>
                <dd class="col-sm-9"><code>{{ $branch ?: '—' }}</code></dd>

                <dt class="col-sm-3">Workflow</dt>
                <dd class="col-sm-9"><code>{{ $workflow ?: '—' }}</code></dd>

                <dt class="col-sm-3">Current build SHA</dt>
                <dd class="col-sm-9">
                    @if(!empty($currentSha))
                        <code>{{ \Illuminate\Support\Str::limit($currentSha, 12, '') }}</code>
                    @else
                        <span class="text-muted">Not set (recommended: set <code>APP_BUILD_SHA</code> at deploy time)</span>
                    @endif
                </dd>

                <dt class="col-sm-3">Latest SHA</dt>
                <dd class="col-sm-9">
                    @if(!empty($latestSha))
                        <code>{{ \Illuminate\Support\Str::limit($latestSha, 12, '') }}</code>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </dd>

                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">
                    @if(is_numeric($behindBy))
                        @if((int) $behindBy > 0)
                            <span class="badge badge-warning">Update available</span>
                            <span class="ms-2 text-muted">Behind by {{ (int) $behindBy }} commit(s)</span>
                        @else
                            <span class="badge badge-success">Up to date</span>
                        @endif
                    @else
                        <span class="text-muted">—</span>
                    @endif

                    @if($compareUrl)
                        <a href="{{ $compareUrl }}" target="_blank" rel="noopener" class="ms-2">View changes</a>
                    @endif
                </dd>

                <dt class="col-sm-3">Last checked</dt>
                <dd class="col-sm-9">
                    <span class="text-muted">{{ $checkedAt ?: '—' }}</span>
                </dd>
            </dl>

            <div class="mt-3 d-flex flex-wrap gap-2">
                <button type="button"
                        class="btn btn-primary"
                        wire:click="checkForGithubUpdates"
                        wire:loading.attr="disabled"
                        wire:target="checkForGithubUpdates">
                    <span wire:loading.remove wire:target="checkForGithubUpdates">Check for updates</span>
                    <span wire:loading wire:target="checkForGithubUpdates">Checking…</span>
                </button>

                <button type="button"
                        class="btn btn-danger"
                        wire:click="triggerGithubUpdate"
                        wire:loading.attr="disabled"
                        wire:target="triggerGithubUpdate">
                    <span wire:loading.remove wire:target="triggerGithubUpdate">Update now (deploy)</span>
                    <span wire:loading wire:target="triggerGithubUpdate">Dispatching…</span>
                </button>

                @if($actionsUrl)
                    <a class="btn btn-outline-secondary" href="{{ $actionsUrl }}" target="_blank" rel="noopener">
                        View Actions
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

