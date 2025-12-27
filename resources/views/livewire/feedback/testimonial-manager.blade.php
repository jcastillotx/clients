<x-app-layout>
    <x-slot name="header">Testimonials</x-slot>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-hourglass-half mr-1"></i> Pending</h3>
                </div>
                <div class="card-body">
                    @forelse($pending as $t)
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <div class="font-weight-bold">{{ $t->client?->company_name }}</div>
                                <div class="text-muted small">{{ $t->created_at?->toDateString() }}</div>
                            </div>
                            <div class="mt-1" style="white-space: pre-wrap;">{{ $t->testimonial_text }}</div>
                            <div class="text-muted small mt-1">Rating: {{ $t->rating ?? '—' }}</div>
                            <div class="mt-2 d-flex" style="gap:8px;">
                                <button class="btn btn-sm btn-success" wire:click="approve({{ $t->id }}, true)">Approve + Public</button>
                                <button class="btn btn-sm btn-outline-success" wire:click="approve({{ $t->id }}, false)">Approve (private)</button>
                                <button class="btn btn-sm btn-outline-danger" wire:click="reject({{ $t->id }})">Reject</button>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">No pending testimonials.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-check mr-1"></i> Approved</h3>
                </div>
                <div class="card-body">
                    @forelse($approved as $t)
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <div class="font-weight-bold">{{ $t->client?->company_name }}</div>
                                <div class="text-muted small">{{ $t->is_public ? 'public' : 'private' }}</div>
                            </div>
                            <div class="mt-1" style="white-space: pre-wrap;">{{ $t->testimonial_text }}</div>
                        </div>
                    @empty
                        <div class="text-muted">No approved testimonials.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

