<span class="ms-auto">
    @if(isset($testResults[$provider]))
        @if(isset($testResults[$provider]['testing']))
            <span class="badge bg-secondary">Testing...</span>
        @elseif($testResults[$provider]['success'] ?? false)
            <span class="badge bg-success">
                <i class="fas fa-check me-1"></i>{{ $testResults[$provider]['message'] ?? 'Connected' }}
            </span>
        @else
            <span class="badge bg-danger">
                <i class="fas fa-times me-1"></i>{{ $testResults[$provider]['message'] ?? 'Failed' }}
            </span>
        @endif
    @endif
    <button type="button" class="btn btn-sm btn-outline-secondary ms-2" 
            wire:click="testConnection('{{ $provider }}')" 
            wire:loading.attr="disabled"
            wire:target="testConnection('{{ $provider }}')">
        <span wire:loading.remove wire:target="testConnection('{{ $provider }}')">
            <i class="fas fa-plug"></i> Test
        </span>
        <span wire:loading wire:target="testConnection('{{ $provider }}')">
            <i class="fas fa-spinner fa-spin"></i>
        </span>
    </button>
</span>
