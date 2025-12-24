<x-app-layout>
    <x-slot name="header">Invoice: {{ $invoice->invoice_number }}</x-slot>

    <div class="row">
        <div class="col-lg-8">
            <!-- Invoice Details -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Invoice Details</h3>
                    <div class="card-tools">
                        <span class="badge badge-{{ $invoice->status_color }} badge-lg">
                            {{ $invoice->status_label }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <strong>Invoice Number:</strong> {{ $invoice->invoice_number }}<br>
                            <strong>Issue Date:</strong> {{ $invoice->issue_date->format('M d, Y') }}<br>
                            <strong>Due Date:</strong> 
                            <span class="{{ $invoice->isOverdue() ? 'text-danger font-weight-bold' : '' }}">
                                {{ $invoice->due_date->format('M d, Y') }}
                                @if($invoice->isOverdue())
                                <small>(Overdue)</small>
                                @endif
                            </span>
                        </div>
                        <div class="col-md-6 text-md-right">
                            <h2 class="mb-0">${{ number_format($invoice->amount, 2) }}</h2>
                            @if($invoice->balance_due > 0 && $invoice->balance_due < $invoice->amount)
                            <small class="text-muted">Balance Due: ${{ number_format($invoice->balance_due, 2) }}</small>
                            @endif
                        </div>
                    </div>

                    <!-- Invoice Items -->
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>Description</th>
                                <th class="text-center" style="width: 100px;">Qty</th>
                                <th class="text-right" style="width: 120px;">Unit Price</th>
                                <th class="text-right" style="width: 120px;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                                <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-right">${{ number_format($item->total, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right"><strong>Subtotal</strong></td>
                                <td class="text-right">${{ number_format($invoice->subtotal, 2) }}</td>
                            </tr>
                            @if($invoice->tax_amount > 0)
                            <tr>
                                <td colspan="3" class="text-right">Tax ({{ $invoice->tax_rate }}%)</td>
                                <td class="text-right">${{ number_format($invoice->tax_amount, 2) }}</td>
                            </tr>
                            @endif
                            @if($invoice->discount > 0)
                            <tr>
                                <td colspan="3" class="text-right">Discount</td>
                                <td class="text-right text-danger">-${{ number_format($invoice->discount, 2) }}</td>
                            </tr>
                            @endif
                            <tr class="table-primary">
                                <td colspan="3" class="text-right"><strong>Total</strong></td>
                                <td class="text-right"><strong>${{ number_format($invoice->amount, 2) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>

                    @if($invoice->notes)
                    <div class="mt-4">
                        <strong>Notes:</strong>
                        <p class="text-muted">{{ $invoice->notes }}</p>
                    </div>
                    @endif
                </div>
                <div class="card-footer">
                    <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-outline-primary" target="_blank">
                        <i class="fas fa-eye mr-1"></i> View PDF
                    </a>
                    <a href="{{ route('invoices.download', $invoice) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-download mr-1"></i> Download PDF
                    </a>
                    @if($invoice->canBePaid())
                    <a href="{{ route('payments.show', $invoice) }}" class="btn btn-success float-right">
                        <i class="fas fa-credit-card mr-1"></i> Pay Now
                    </a>
                    @endif
                </div>
            </div>

            <!-- Payment History -->
            @if($invoice->payments->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Payment History</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Transaction ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->payments as $payment)
                            <tr>
                                <td>{{ $payment->created_at->format('M d, Y h:i A') }}</td>
                                <td>${{ number_format($payment->amount, 2) }}</td>
                                <td>{{ ucfirst($payment->payment_method) }}</td>
                                <td>
                                    <span class="badge badge-{{ $payment->status_color }}">
                                        {{ $payment->status_label }}
                                    </span>
                                </td>
                                <td><code>{{ $payment->transaction_id ?? '-' }}</code></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- Actions -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Actions</h3>
                </div>
                <div class="card-body">
                    <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Invoices
                    </a>
                    @if($invoice->canBePaid())
                    <a href="{{ route('payments.show', $invoice) }}" class="btn btn-success btn-block btn-lg mt-3">
                        <i class="fas fa-credit-card mr-1"></i> Pay ${{ number_format($invoice->balance_due, 2) }}
                    </a>
                    @endif
                </div>
            </div>

            <!-- Status Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Status</h3>
                </div>
                <div class="card-body text-center">
                    @if($invoice->isPaid())
                    <div class="text-success">
                        <i class="fas fa-check-circle fa-4x mb-3"></i>
                        <h4>Paid in Full</h4>
                        <p class="text-muted mb-0">
                            Paid on {{ $invoice->paid_at->format('M d, Y') }}
                        </p>
                    </div>
                    @elseif($invoice->isOverdue())
                    <div class="text-danger">
                        <i class="fas fa-exclamation-circle fa-4x mb-3"></i>
                        <h4>Payment Overdue</h4>
                        <p class="text-muted mb-0">
                            Was due {{ $invoice->due_date->diffForHumans() }}
                        </p>
                    </div>
                    @else
                    <div class="text-info">
                        <i class="fas fa-clock fa-4x mb-3"></i>
                        <h4>Payment Due</h4>
                        <p class="text-muted mb-0">
                            Due {{ $invoice->due_date->diffForHumans() }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
