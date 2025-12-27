<x-app-layout>
    <x-slot name="header">Project budgets</x-slot>

    <div class="card mb-3">
        <div class="card-body">
            <div class="form-row">
                <div class="col-md-8">
                    <label class="mb-1">Request</label>
                    <select class="form-control" wire:model="requestId">
                        <option value="">All</option>
                        @foreach($requests as $r)
                            <option value="{{ $r->id }}">#{{ $r->id }} — {{ $r->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" wire:click="recalc" @if(!$requestId) disabled @endif>
                        <i class="fas fa-sync mr-1"></i> Recalc selected
                    </button>
                </div>
            </div>
            <div class="text-muted small mt-2">Profitability is calculated as invoiced − spent (best-effort, based on tracked hourly rates).</div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Budget hrs</th>
                        <th>Spent hrs</th>
                        <th>Budget $</th>
                        <th>Spent $</th>
                        <th>Invoiced $</th>
                        <th>Margin $</th>
                        <th>Exceeded</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($budgets as $b)
                        @php
                            $inv = (float) ($invoiceTotals[$b->request_id] ?? 0);
                            $spent = (float) ($b->spent_amount ?? 0);
                            $margin = $inv - $spent;
                        @endphp
                        <tr>
                            <td>#{{ $b->request_id }} — {{ $b->request?->title }}</td>
                            <td>{{ $b->budget_hours ?? '—' }}</td>
                            <td>{{ $b->spent_hours ?? '—' }}</td>
                            <td>{{ $b->budget_amount ?? '—' }}</td>
                            <td>{{ $b->spent_amount ?? '—' }}</td>
                            <td>{{ number_format($inv, 2) }}</td>
                            <td>{{ number_format($margin, 2) }}</td>
                            <td>{{ $b->is_exceeded ? 'yes' : 'no' }}</td>
                        </tr>
                    @endforeach
                    @if($budgets->isEmpty())
                        <tr><td colspan="8" class="text-muted p-3">No budgets yet.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>

