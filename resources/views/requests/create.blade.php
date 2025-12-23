<x-app-layout>
    <x-slot name="header">Create New Request</x-slot>

    <div class="row">
        <div class="col-lg-8">
            <livewire:requests.create-request />
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle mr-1"></i>
                        Tips
                    </h3>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            Be specific about your requirements
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            Include any relevant files or references
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            Set a realistic due date if needed
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            Choose the appropriate priority level
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
