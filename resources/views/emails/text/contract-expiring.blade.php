Contract expiring soon

Contract: {{ $contract->title }}
Contract #: {{ $contract->contract_number }}
End date: {{ $contract->end_date?->format('M d, Y') ?? '—' }}
Days remaining: {{ $daysRemaining }}

View contract: {{ route('contracts.show', $contract) }}

