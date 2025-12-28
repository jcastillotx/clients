<div class="contract-show">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <h2 class="text-xl font-semibold mb-4">Contract Details</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="font-medium">Client</h3>
                    <p>{{ $contract->client->name ?? 'N/A' }}</p>
                </div>
                
                <div>
                    <h3 class="font-medium">Status</h3>
                    <span class="px-2 py-1 text-xs rounded-full {{ $contract->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $contract->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                
                @if($contract->start_date)
                <div>
                    <h3 class="font-medium">Start Date</h3>
                    <p>{{ $contract->start_date->format('M d, Y') }}</p>
                </div>
                @endif
                
                @if($contract->end_date)
                <div>
                    <h3 class="font-medium">End Date</h3>
                    <p>{{ $contract->end_date->format('M d, Y') }}</p>
                </div>
                @endif
                
                @if($contract->terms)
                <div class="md:col-span-2">
                    <h3 class="font-medium">Terms & Conditions</h3>
                    <div class="prose max-w-none">
                        {!! $contract->terms !!}
                    </div>
                </div>
                @endif
            </div>
            
            <div class="mt-6 flex space-x-4">
                @can('access admin panel')
                    @if(Route::has('admin.contracts.edit'))
                    <a href="{{ route('admin.contracts.edit', $contract) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Edit Contract
                    </a>
                    @endif
                @endcan
                
                @if($contract->file_path)
                <a href="{{ route('contracts.download', $contract) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Download PDF
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
