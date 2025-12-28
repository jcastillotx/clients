<x-guest-layout>
    <div class="login-box">
        <!-- Logo -->
        <div class="login-logo">
            <a href="/">
                @php
                    $brandingService = app(\App\Services\BrandingService::class);
                    $brand = $brandingService->all();
                    $loginLogo = $brand['login_logo_path'] ?? '';
                    $mainLogo = $brand['logo_path'] ?? '';
                    $companyName = $brand['company_name'] ?? config('app.name', 'Client Portal');
                @endphp
                @if(!empty($loginLogo))
                    <img src="{{ asset('storage/' . $loginLogo) }}" alt="{{ $companyName }}" style="max-height: 60px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                    <span style="display: none;"><b>{{ $companyName }}</b></span>
                @elseif(!empty($mainLogo))
                    <img src="{{ asset('storage/' . $mainLogo) }}" alt="{{ $companyName }}" style="max-height: 60px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                    <span style="display: none;"><b>{{ $companyName }}</b></span>
                @else
                    <b>{{ $companyName }}</b>
                @endif
            </a>
        </div>

        <!-- Card -->
        <div class="card card-outline card-info">
            <div class="card-header text-center">
                <h4 class="mb-0"><i class="fas fa-envelope-open-text mr-2"></i>Verify Your Email</h4>
                <p class="text-muted mb-0 mt-1">Please verify your email address to continue</p>
            </div>
            <div class="card-body login-card-body">
                <div class="text-center mb-4">
                    <div class="mb-3">
                        <span class="fa-stack fa-2x text-info">
                            <i class="fas fa-circle fa-stack-2x"></i>
                            <i class="fas fa-envelope fa-stack-1x fa-inverse"></i>
                        </span>
                    </div>
                    <p class="text-muted">
                        Thanks for signing up! Before getting started, please verify your email address by clicking on the link we just emailed to you.
                    </p>
                </div>

                @if(session('status') == 'verification-link-sent')
                    <div class="alert alert-success">
                        <i class="icon fas fa-check"></i>
                        A new verification link has been sent to your email address.
                    </div>
                @endif

                <form method="POST" action="{{ route('verification.send') }}" class="mb-3">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-redo mr-2"></i>Resend Verification Email
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-sign-out-alt mr-2"></i>Log Out
                    </button>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-3">
            <p class="text-white-50 mb-0">
                <small>&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</small>
            </p>
        </div>
    </div>
</x-guest-layout>
