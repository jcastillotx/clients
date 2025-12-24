<div>
    <div class="alert alert-info">
        <h5><i class="icon fas fa-info"></i> Important</h5>
        By signing this contract electronically, you agree to be bound by its terms and conditions. 
        Please review the contract carefully before signing.
    </div>

    @if($contract->file_path)
    <div class="mb-4">
        <a href="{{ route('contracts.download', $contract) }}" class="btn btn-outline-primary" target="_blank">
            <i class="fas fa-file-pdf mr-1"></i> Review Contract Document
        </a>
    </div>
    @endif

    <form wire:submit="sign">
        <div class="form-group">
            <label for="signature">Your Signature <span class="text-danger">*</span></label>
            <p class="text-muted small">
                Type your full legal name as it appears on the contract.
            </p>
            <input type="text" 
                   wire:model="signature" 
                   id="signature" 
                   class="form-control form-control-lg @error('signature') is-invalid @enderror"
                   placeholder="Type your full name"
                   style="font-family: 'Brush Script MT', cursive; font-size: 24px;">
            @error('signature')
            <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        @if($signature)
        <div class="form-group">
            <label>Signature Preview:</label>
            <div class="border rounded p-4 bg-white text-center">
                <span style="font-family: 'Brush Script MT', cursive; font-size: 36px;">
                    {{ $signature }}
                </span>
            </div>
        </div>
        @endif

        <div class="form-group">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" 
                       wire:model="agreeTerms" 
                       id="agreeTerms" 
                       class="custom-control-input @error('agreeTerms') is-invalid @enderror">
                <label class="custom-control-label" for="agreeTerms">
                    I have read and agree to the terms and conditions of this contract. 
                    I understand that this electronic signature has the same legal effect as a handwritten signature.
                </label>
                @error('agreeTerms')
                <span class="invalid-feedback d-block">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="form-group mb-0">
            <button type="submit" 
                    class="btn btn-success btn-lg" 
                    wire:loading.attr="disabled"
                    {{ !$signature || !$agreeTerms ? 'disabled' : '' }}>
                <span wire:loading.remove wire:target="sign">
                    <i class="fas fa-signature mr-1"></i> Sign Contract
                </span>
                <span wire:loading wire:target="sign">
                    <i class="fas fa-spinner fa-spin mr-1"></i> Signing...
                </span>
            </button>
        </div>
    </form>

    <div class="mt-4 text-muted small">
        <p class="mb-1">
            <i class="fas fa-lock mr-1"></i>
            Your signature will be recorded with a timestamp and your IP address for verification purposes.
        </p>
        <p class="mb-0">
            <i class="fas fa-globe mr-1"></i>
            IP Address: {{ request()->ip() }}
        </p>
    </div>
</div>
