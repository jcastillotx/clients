<x-app-layout>
    <x-slot name="header">Contract: {{ $contract->title }}</x-slot>

    <div class="row">
        <div class="col-lg-8">
            <!-- Contract Details -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Contract Details</h3>
                    <div class="card-tools">
                        <span class="badge badge-{{ $contract->status_color }}">
                            {{ $contract->status_label }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Contract Number</dt>
                        <dd class="col-sm-8">{{ $contract->contract_number }}</dd>

                        <dt class="col-sm-4">Value</dt>
                        <dd class="col-sm-8">${{ number_format($contract->value, 2) }}</dd>

                        @if($contract->start_date)
                        <dt class="col-sm-4">Start Date</dt>
                        <dd class="col-sm-8">{{ $contract->start_date->format('M d, Y') }}</dd>
                        @endif

                        @if($contract->end_date)
                        <dt class="col-sm-4">End Date</dt>
                        <dd class="col-sm-8">
                            {{ $contract->end_date->format('M d, Y') }}
                            @if($contract->isActive() && $contract->days_until_expiration !== null)
                                @if($contract->days_until_expiration <= 30)
                                <span class="badge badge-warning ml-2">
                                    Expires in {{ $contract->days_until_expiration }} days
                                </span>
                                @endif
                            @endif
                        </dd>
                        @endif

                        @if($contract->signed_at)
                        <dt class="col-sm-4">Signed</dt>
                        <dd class="col-sm-8">
                            {{ $contract->signed_at->format('M d, Y h:i A') }} by {{ $contract->signed_by }}
                        </dd>
                        @endif
                    </dl>

                    @if($contract->description)
                    <hr>
                    <h5>Description</h5>
                    <p>{{ $contract->description }}</p>
                    @endif
                </div>
                <div class="card-footer">
                    @if($contract->file_path)
                    <a href="{{ route('contracts.download', $contract) }}" class="btn btn-primary">
                        <i class="fas fa-download mr-1"></i> Download Contract
                    </a>
                    @endif
                </div>
            </div>

            <!-- Signature Section -->
            @if($contract->isPendingSignature())
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-signature mr-1"></i>
                        E-Signature Required
                    </h3>
                </div>
                <div class="card-body">
                    <livewire:contracts.sign-contract :contract="$contract" />
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- Actions -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Actions</h3>
                </div>
                <div class="card-body">
                    <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Contracts
                    </a>
                </div>
            </div>

            <!-- Status Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Status</h3>
                </div>
                <div class="card-body text-center">
                    @if($contract->isSigned())
                    <div class="text-success">
                        <i class="fas fa-check-circle fa-4x mb-3"></i>
                        <h4>Contract Signed</h4>
                        <p class="text-muted mb-0">
                            Signed on {{ $contract->signed_at->format('M d, Y') }}
                        </p>
                    </div>
                    @elseif($contract->isPendingSignature())
                    <div class="text-warning">
                        <i class="fas fa-clock fa-4x mb-3"></i>
                        <h4>Awaiting Signature</h4>
                        <p class="text-muted mb-0">
                            Please review and sign this contract.
                        </p>
                    </div>
                    @elseif($contract->isExpired())
                    <div class="text-danger">
                        <i class="fas fa-times-circle fa-4x mb-3"></i>
                        <h4>Contract Expired</h4>
                        <p class="text-muted mb-0">
                            Expired on {{ $contract->end_date->format('M d, Y') }}
                        </p>
                    </div>
                    @else
                    <div class="text-secondary">
                        <i class="fas fa-file-contract fa-4x mb-3"></i>
                        <h4>{{ $contract->status_label }}</h4>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
