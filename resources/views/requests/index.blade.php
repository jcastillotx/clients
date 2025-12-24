<x-app-layout>
    <x-slot name="header">Service Requests</x-slot>

    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('requests.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> New Request
            </a>
        </div>
    </div>

    <livewire:requests.request-list />
</x-app-layout>
