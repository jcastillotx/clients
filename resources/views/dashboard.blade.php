<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    <!-- Info boxes -->
    <div class="row">
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-info elevation-1">
                    <i class="fas fa-clipboard-list"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">Open Requests</span>
                    <span class="info-box-number">{{ $stats['open_requests'] }}</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-warning elevation-1">
                    <i class="fas fa-file-invoice-dollar"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">Pending Invoices</span>
                    <span class="info-box-number">{{ $stats['pending_invoices'] }}</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-success elevation-1">
                    <i class="fas fa-file-contract"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">Active Contracts</span>
                    <span class="info-box-number">{{ $stats['active_contracts'] }}</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-danger elevation-1">
                    <i class="fas fa-dollar-sign"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Due</span>
                    <span class="info-box-number">${{ number_format($stats['total_due'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Cards -->
    @if($pendingContracts->count() > 0 || $stats['pending_invoices'] > 0)
    <div class="row">
        @foreach($pendingContracts as $contract)
        <div class="col-md-6">
            <div class="alert alert-warning">
                <h5><i class="icon fas fa-exclamation-triangle"></i> Contract Pending Signature</h5>
                <p class="mb-2">{{ $contract->title }} requires your signature.</p>
                <a href="{{ route('contracts.show', $contract) }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-signature mr-1"></i> Review & Sign
                </a>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <div class="row">
        <!-- Recent Requests -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-clipboard-list mr-1"></i>
                        Recent Requests
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('requests.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus mr-1"></i> New Request
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentRequests as $request)
                            <tr>
                                <td>
                                    <a href="{{ route('requests.show', $request) }}">
                                        {{ Str::limit($request->title, 30) }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $request->status_color }}">
                                        {{ $request->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $request->priority_color }}">
                                        {{ $request->priority_label }}
                                    </span>
                                </td>
                                <td>{{ $request->created_at->format('M d, Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No requests yet. <a href="{{ route('requests.create') }}">Create your first request</a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($recentRequests->count() > 0)
                <div class="card-footer text-center">
                    <a href="{{ route('requests.index') }}">View All Requests</a>
                </div>
                @endif
            </div>
        </div>

        <!-- Recent Invoices -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-invoice-dollar mr-1"></i>
                        Recent Invoices
                    </h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Due Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentInvoices as $invoice)
                            <tr>
                                <td>
                                    <a href="{{ route('invoices.show', $invoice) }}">
                                        {{ $invoice->invoice_number }}
                                    </a>
                                </td>
                                <td>${{ number_format($invoice->amount, 2) }}</td>
                                <td>
                                    <span class="badge badge-{{ $invoice->status_color }}">
                                        {{ $invoice->status_label }}
                                    </span>
                                </td>
                                <td>{{ $invoice->due_date->format('M d, Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No invoices yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($recentInvoices->count() > 0)
                <div class="card-footer text-center">
                    <a href="{{ route('invoices.index') }}">View All Invoices</a>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Expiring Contracts Warning -->
    @if($expiringContracts->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-clock mr-1"></i>
                        Contracts Expiring Soon
                    </h3>
                </div>
                <div class="card-body p-0">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Contract</th>
                                <th>Expires</th>
                                <th>Days Remaining</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expiringContracts as $contract)
                            <tr>
                                <td>{{ $contract->title }}</td>
                                <td>{{ $contract->end_date->format('M d, Y') }}</td>
                                <td>
                                    <span class="badge badge-{{ $contract->days_until_expiration <= 7 ? 'danger' : 'warning' }}">
                                        {{ $contract->days_until_expiration }} days
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('contracts.show', $contract) }}" class="btn btn-sm btn-outline-primary">
                                        View Contract
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
</x-app-layout>
