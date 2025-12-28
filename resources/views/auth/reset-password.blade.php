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
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <h4 class="mb-0"><i class="fas fa-key mr-2"></i>Create New Password</h4>
                <p class="text-muted mb-0 mt-1">Enter your new password below</p>
            </div>
            <div class="card-body login-card-body">
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas fa-ban"></i> Error</h5>
                        @foreach ($errors->all() as $error)
                            <p class="mb-0">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('password.store') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <!-- Email Field -->
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-group">
                            <input type="email" 
                                   name="email" 
                                   id="email"
                                   class="form-control @error('email') is-invalid @enderror" 
                                   value="{{ old('email', $request->email) }}"
                                   required
                                   readonly>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-envelope"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <div class="input-group">
                            <input type="password" 
                                   name="password" 
                                   id="password"
                                   class="form-control @error('password') is-invalid @enderror" 
                                   placeholder="Enter new password"
                                   required
                                   autofocus>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-lock"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="form-group">
                        <label for="password_confirmation">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" 
                                   name="password_confirmation" 
                                   id="password_confirmation"
                                   class="form-control" 
                                   placeholder="Confirm new password"
                                   required>
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
                            <i class="fas fa-save mr-2"></i>Reset Password
                        </button>
                    </div>

                    <!-- Back to Login -->
                    <div class="text-center">
                        <a href="{{ route('login') }}" class="text-muted">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Login
                        </a>
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
