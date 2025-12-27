<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Team Workload & Capacity</h2>
        </div>
        <div class="d-flex gap-2">
            <button wire:click="$refresh" class="btn btn-outline-secondary">
                <i class="fas fa-sync-alt me-1"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row row-deck row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Team Utilization</div>
                    </div>
                    <div class="h1 mb-3">{{ $workload['utilization_pct'] }}%</div>
                    <div class="progress progress-sm">
                        <div class="progress-bar {{ $workload['utilization_pct'] >= 90 ? 'bg-danger' : ($workload['utilization_pct'] >= 75 ? 'bg-warning' : 'bg-success') }}"
                             style="width: {{ min(100, $workload['utilization_pct']) }}%"></div>
                    </div>
                    <div class="mt-2 text-muted small">
                        @if($workload['utilization_pct'] >= 90)
                            <span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> At capacity</span>
                        @elseif($workload['utilization_pct'] >= 75)
                            <span class="text-warning"><i class="fas fa-clock me-1"></i> High utilization</span>
                        @else
                            <span class="text-success"><i class="fas fa-check-circle me-1"></i> Good availability</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Available Hours/Week</div>
                    </div>
                    <div class="h1 mb-3">{{ number_format($workload['available_hours_week'], 1) }}</div>
                    <div class="text-muted small">
                        of {{ number_format($workload['total_capacity_hours_week'], 0) }} total capacity
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Open Requests</div>
                    </div>
                    <div class="h1 mb-3">{{ $workload['open_requests'] }}</div>
                    <div class="text-muted small">
                        {{ $workload['in_progress_requests'] }} in progress
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Backlog Hours</div>
                    </div>
                    <div class="h1 mb-3">{{ number_format($workload['estimated_backlog_hours'], 0) }}</div>
                    <div class="text-muted small">
                        ~{{ ceil($workload['estimated_backlog_hours'] / max(1, $workload['available_hours_week'])) }} weeks of work
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Staff Breakdown -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Staff Workload Breakdown</h3>
            <div class="card-actions">
                <span class="badge bg-success me-1">{{ $workload['available_staff'] }} Available</span>
                <span class="badge bg-secondary">{{ $workload['total_staff'] }} Total</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Staff Member</th>
                        <th>Status</th>
                        <th>Utilization</th>
                        <th class="text-center">Logged (Week)</th>
                        <th class="text-center">Assigned Requests</th>
                        <th class="text-center">Tasks</th>
                        <th class="text-end">Available Hours</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($workload['staff_breakdown'] as $staff)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm me-2 bg-primary-lt">
                                        {{ strtoupper(substr($staff['name'], 0, 2)) }}
                                    </span>
                                    <div>
                                        <div class="fw-semibold">{{ $staff['name'] }}</div>
                                        <div class="text-muted small">{{ $staff['email'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'available' => 'success',
                                        'moderate' => 'info',
                                        'busy' => 'warning',
                                        'at_capacity' => 'danger',
                                        'overloaded' => 'danger',
                                    ];
                                    $statusLabels = [
                                        'available' => 'Available',
                                        'moderate' => 'Moderate',
                                        'busy' => 'Busy',
                                        'at_capacity' => 'At Capacity',
                                        'overloaded' => 'Overloaded',
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$staff['status']] ?? 'secondary' }}">
                                    {{ $statusLabels[$staff['status']] ?? ucfirst($staff['status']) }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress progress-sm flex-grow-1" style="width: 100px;">
                                        <div class="progress-bar {{ $staff['utilization_pct'] >= 90 ? 'bg-danger' : ($staff['utilization_pct'] >= 75 ? 'bg-warning' : 'bg-success') }}"
                                             style="width: {{ min(100, $staff['utilization_pct']) }}%"></div>
                                    </div>
                                    <span class="text-muted small">{{ $staff['utilization_pct'] }}%</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="text-muted">{{ $staff['logged_hours'] }} hrs</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary-lt">{{ $staff['assigned_requests'] }}</span>
                            </td>
                            <td class="text-center">
                                <span class="text-success small me-1" title="To Do">{{ $staff['tasks_todo'] }}</span>
                                <span class="text-info small me-1" title="In Progress">{{ $staff['tasks_in_progress'] }}</span>
                                <span class="text-danger small" title="Blocked">{{ $staff['tasks_blocked'] }}</span>
                            </td>
                            <td class="text-end">
                                <span class="fw-semibold {{ $staff['available_hours'] > 10 ? 'text-success' : ($staff['available_hours'] > 0 ? 'text-warning' : 'text-danger') }}">
                                    {{ number_format($staff['available_hours'], 1) }} hrs
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Best Available for Assignment -->
    <div class="card mt-4">
        <div class="card-header">
            <h3 class="card-title">Best Available for New Assignments</h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @foreach($availableStaff as $staff)
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-sm {{ $staff->workload['utilization_pct'] < 50 ? 'border-success' : '' }}">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <span class="avatar me-3 bg-primary-lt">
                                        {{ strtoupper(substr($staff->name, 0, 2)) }}
                                    </span>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $staff->name }}</div>
                                        <div class="text-muted small">
                                            {{ $staff->workload['available_hours'] }} hrs available
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-{{ $staff->workload['utilization_pct'] < 50 ? 'success' : ($staff->workload['utilization_pct'] < 75 ? 'info' : 'warning') }}">
                                            {{ $staff->workload['utilization_pct'] }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Capacity Planning Info -->
    <div class="card mt-4">
        <div class="card-header">
            <h3 class="card-title">Capacity Planning</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h4 class="mb-3">Current Week Summary</h4>
                    <dl class="row">
                        <dt class="col-5">Total Staff:</dt>
                        <dd class="col-7">{{ $workload['total_staff'] }} members</dd>

                        <dt class="col-5">Weekly Capacity:</dt>
                        <dd class="col-7">{{ number_format($workload['total_capacity_hours_week'], 0) }} hours</dd>

                        <dt class="col-5">Committed:</dt>
                        <dd class="col-7">{{ number_format($workload['committed_hours_week'], 1) }} hours</dd>

                        <dt class="col-5">Available:</dt>
                        <dd class="col-7 fw-semibold text-success">{{ number_format($workload['available_hours_week'], 1) }} hours</dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <h4 class="mb-3">Backlog Analysis</h4>
                    <dl class="row">
                        <dt class="col-5">Open Requests:</dt>
                        <dd class="col-7">{{ $workload['open_requests'] }}</dd>

                        <dt class="col-5">In Progress:</dt>
                        <dd class="col-7">{{ $workload['in_progress_requests'] }}</dd>

                        <dt class="col-5">Estimated Backlog:</dt>
                        <dd class="col-7">{{ number_format($workload['estimated_backlog_hours'], 0) }} hours</dd>

                        <dt class="col-5">Weeks to Clear:</dt>
                        <dd class="col-7">
                            @php
                                $weeksToClear = $workload['available_hours_week'] > 0
                                    ? ceil($workload['estimated_backlog_hours'] / $workload['available_hours_week'])
                                    : '∞';
                            @endphp
                            {{ $weeksToClear }} weeks
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
