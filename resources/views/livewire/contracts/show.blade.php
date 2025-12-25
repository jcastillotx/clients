@php
    $contract = $contract;
    $badge = match ($contract->status) {
        'active' => 'bg-emerald-100 text-emerald-800',
        'expired' => 'bg-slate-100 text-slate-700',
        'draft' => 'bg-amber-100 text-amber-800',
        'pending_signature' => 'bg-amber-100 text-amber-800',
        'terminated' => 'bg-slate-100 text-slate-700',
        default => 'bg-slate-100 text-slate-700',
    };
    $canSign = $contract->isPendingSignature() && !$contract->isSigned();
@endphp

<div wire:loading.flex class="fixed inset-0 z-50 items-center justify-center bg-slate-900/20 backdrop-blur-sm" aria-label="Loading" style="display:none;">
    <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-lg ring-1 ring-black/5">
        <svg class="h-5 w-5 animate-spin text-slate-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        <span class="text-sm font-medium text-slate-700">Loading contract…</span>
    </div>
</div>

<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm text-slate-500">Contract</div>
            <div class="text-xl font-semibold text-slate-900">{{ $contract->title }}</div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('contracts.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
                Back
            </a>
            @if($contract->file_path)
                <a href="{{ route('contracts.download', $contract) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
                    Download Contract
                </a>
            @endif
            @if($canSign)
                <a href="#sign" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                    Sign Contract
                </a>
            @endif
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-2 text-sm text-slate-700">
                <div><span class="text-slate-500">Contract #:</span> <span class="font-semibold text-slate-900">{{ $contract->contract_number }}</span></div>
                <div><span class="text-slate-500">Value:</span> <span class="font-semibold text-slate-900">@money($contract->value)</span></div>
                <div><span class="text-slate-500">Start:</span> <span class="font-semibold text-slate-900">{{ $contract->start_date?->format('M d, Y') ?? '—' }}</span></div>
                <div>
                    <span class="text-slate-500">End:</span>
                    <span class="font-semibold text-slate-900">{{ $contract->end_date?->format('M d, Y') ?? '—' }}</span>
                    @if($contract->isActive() && $contract->days_until_expiration !== null && $contract->days_until_expiration <= 30)
                        <span class="ml-2 inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                            Expires in {{ $contract->days_until_expiration }} days
                        </span>
                    @endif
                </div>
                @if($contract->signed_at)
                    <div><span class="text-slate-500">Signed:</span> <span class="font-semibold text-slate-900">{{ $contract->signed_at->format('M d, Y h:i A') }}</span> <span class="text-slate-500">by</span> <span class="font-semibold text-slate-900">{{ $contract->signed_by }}</span></div>
                @endif
            </div>

            <div class="text-right">
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge }}">
                    {{ $contract->status_label }}
                </span>
            </div>
        </div>

        @if($contract->description)
            <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-700">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Description</div>
                <div class="mt-1 whitespace-pre-wrap">{{ $contract->description }}</div>
            </div>
        @endif
    </div>

    <!-- PDF preview -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4">
            <div class="text-sm font-semibold text-slate-900">Contract document</div>
            <div class="mt-1 text-xs text-slate-500">Preview (if available)</div>
        </div>

        @if($contract->file_url)
            <div class="bg-slate-50">
                <iframe
                    src="{{ $contract->file_url }}"
                    class="h-[70vh] w-full"
                    title="Contract PDF"
                ></iframe>
            </div>
        @else
            <div class="px-5 py-12 text-center text-sm text-slate-500">
                No contract file available.
            </div>
        @endif
    </div>

    <!-- Signature -->
    @if($canSign)
        <div id="sign" class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <div class="text-sm font-semibold text-amber-900">Signature required</div>
            <div class="mt-1 text-sm text-amber-800">
                Please review the contract above and sign to proceed.
            </div>
            <div class="mt-4 rounded-2xl border border-amber-200 bg-white p-5">
                <livewire:contracts.sign-contract :contract="$contract" />
            </div>
        </div>
    @endif
</div>

