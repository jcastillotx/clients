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
        <div class="card card-outline card-warning">
            <div class="card-header text-center">
                <h4 class="mb-0"><i class="fas fa-shield-alt mr-2"></i>Confirm Password</h4>
                <p class="text-muted mb-0 mt-1">This is a secure area. Please confirm your password.</p>
            </div>
            <div class="card-body login-card-body">
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        @foreach ($errors->all() as $error)
                            <p class="mb-0">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('password.confirm') }}">
                    @csrf

                    <!-- Password Field -->
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <input type="password" 
                                   name="password" 
                                   id="password"
                                   class="form-control @error('password') is-invalid @enderror" 
                                   placeholder="Enter your password"
                                   required
                                   autofocus>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-lock"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-check mr-2"></i>Confirm Password
                        </button>
                    </div>
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
