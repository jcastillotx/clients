<div class="flex items-center gap-2 ml-auto">
    @if(isset($testResults[$provider]))
        @if(isset($testResults[$provider]['testing']))
            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-800">Testing...</span>
        @elseif($testResults[$provider]['success'] ?? false)
            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                <i class="fas fa-check mr-1"></i>{{ $testResults[$provider]['message'] ?? 'Connected' }}
            </span>
        @else
            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                <i class="fas fa-times mr-1"></i>{{ $testResults[$provider]['message'] ?? 'Failed' }}
            </span>
        @endif
    @endif
    <button type="button"
            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            wire:click="testConnection('{{ $provider }}')"
            wire:loading.attr="disabled"
            wire:target="testConnection('{{ $provider }}')">
        <span wire:loading.remove wire:target="testConnection('{{ $provider }}')">
            <i class="fas fa-plug mr-1"></i> Test
        </span>
        <span wire:loading wire:target="testConnection('{{ $provider }}')">
            <i class="fas fa-spinner fa-spin mr-1"></i> Testing...
        </span>
    </button>
</div>
