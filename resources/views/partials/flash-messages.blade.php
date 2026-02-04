{{-- Flash Messages Partial - Include in Livewire components --}}

{{-- Success Message --}}
@if(session()->has('success'))
    <div x-data="{ show: true }" x-show="show" x-transition class="relative rounded-lg border border-green-200 bg-green-50 p-4 mb-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-600"></i>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium text-green-800">
                    <strong>Success!</strong> {{ session('success') }}
                </p>
            </div>
            <div class="ml-auto pl-3">
                <button @click="show = false" class="inline-flex rounded-md bg-green-50 p-1.5 text-green-500 hover:bg-green-100 focus:outline-none">
                    <span class="sr-only">Dismiss</span>
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
@endif

{{-- Error Message --}}
@if(session()->has('error'))
    <div x-data="{ show: true }" x-show="show" x-transition class="relative rounded-lg border border-red-200 bg-red-50 p-4 mb-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-600"></i>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium text-red-800">
                    <strong>Error!</strong> {{ session('error') }}
                </p>
            </div>
            <div class="ml-auto pl-3">
                <button @click="show = false" class="inline-flex rounded-md bg-red-50 p-1.5 text-red-500 hover:bg-red-100 focus:outline-none">
                    <span class="sr-only">Dismiss</span>
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
@endif

{{-- Warning Message --}}
@if(session()->has('warning'))
    <div x-data="{ show: true }" x-show="show" x-transition class="relative rounded-lg border border-amber-200 bg-amber-50 p-4 mb-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-amber-600"></i>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium text-amber-800">
                    <strong>Warning!</strong> {{ session('warning') }}
                </p>
            </div>
            <div class="ml-auto pl-3">
                <button @click="show = false" class="inline-flex rounded-md bg-amber-50 p-1.5 text-amber-500 hover:bg-amber-100 focus:outline-none">
                    <span class="sr-only">Dismiss</span>
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
@endif

{{-- Info Message --}}
@if(session()->has('info'))
    <div x-data="{ show: true }" x-show="show" x-transition class="relative rounded-lg border border-blue-200 bg-blue-50 p-4 mb-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-600"></i>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm text-blue-800">
                    {{ session('info') }}
                </p>
            </div>
            <div class="ml-auto pl-3">
                <button @click="show = false" class="inline-flex rounded-md bg-blue-50 p-1.5 text-blue-500 hover:bg-blue-100 focus:outline-none">
                    <span class="sr-only">Dismiss</span>
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
@endif

{{-- Validation Errors Summary --}}
@if($errors->any())
    <div x-data="{ show: true }" x-show="show" x-transition class="relative rounded-lg border border-red-200 bg-red-50 p-4 mb-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-red-800 mb-2">Please fix the following errors:</h3>
                <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="ml-auto pl-3">
                <button @click="show = false" class="inline-flex rounded-md bg-red-50 p-1.5 text-red-500 hover:bg-red-100 focus:outline-none">
                    <span class="sr-only">Dismiss</span>
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
@endif
