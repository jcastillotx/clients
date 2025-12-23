<div>
    <!-- Summary Cards -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="info-box bg-warning">
                <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Unpaid</span>
                    <span class="info-box-number">${{ number_format($totals['unpaid'], 2) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="info-box bg-danger">
                <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Overdue</span>
                    <span class="info-box-number">${{ number_format($totals['overdue'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-md-0">
                        <input type="text" 
                               wire:model.live.debounce.300ms="search" 
                               class="form-control" 
                               placeholder="Search by invoice number...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select wire:model.live="status" class="form-control">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    @if($search || $status)
                    <button wire:click="clearFilters" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-times mr-1"></i> Clear
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice List -->
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th wire:click="sortBy('invoice_number')" style="cursor: pointer;">
                            Invoice #
                            @if($sortField === 'invoice_number')
                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </th>
                        <th wire:click="sortBy('amount')" style="cursor: pointer;">
                            Amount
                            @if($sortField === 'amount')
                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </th>
                        <th>Status</th>
                        <th wire:click="sortBy('issue_date')" style="cursor: pointer;">
                            Issue Date
                            @if($sortField === 'issue_date')
                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </th>
                        <th wire:click="sortBy('due_date')" style="cursor: pointer;">
                            Due Date
                            @if($sortField === 'due_date')
                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr class="{{ $invoice->isOverdue() ? 'table-danger' : '' }}">
                        <td>
                            <a href="{{ route('invoices.show', $invoice) }}" class="font-weight-bold">
                                {{ $invoice->invoice_number }}
                            </a>
                        </td>
                        <td>${{ number_format($invoice->amount, 2) }}</td>
                        <td>
                            <span class="badge badge-{{ $invoice->status_color }}">
                                {{ $invoice->status_label }}
                            </span>
                        </td>
                        <td>{{ $invoice->issue_date->format('M d, Y') }}</td>
                        <td>
                            <span class="{{ $invoice->isOverdue() ? 'text-danger font-weight-bold' : '' }}">
                                {{ $invoice->due_date->format('M d, Y') }}
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($invoice->canBePaid())
                            <a href="{{ route('payments.show', $invoice) }}" class="btn btn-sm btn-success">
                                <i class="fas fa-credit-card"></i> Pay
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-file-invoice-dollar fa-3x mb-3"></i>
                                <p>No invoices found.</p>
                                @if($search || $status)
                                <button wire:click="clearFilters" class="btn btn-outline-primary btn-sm">
                                    Clear Filters
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
        <div class="card-footer">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>
</div>
