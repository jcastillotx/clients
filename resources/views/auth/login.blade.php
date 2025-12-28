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

        <!-- Login Card -->
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <h4 class="mb-0"><i class="fas fa-sign-in-alt mr-2"></i>Welcome Back</h4>
                <p class="text-muted mb-0 mt-1">Sign in to access your account</p>
            </div>
            <div class="card-body login-card-body">
                @if(session('status'))
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <i class="icon fas fa-check"></i> {{ session('status') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas fa-ban"></i> Login Error</h5>
                        @foreach ($errors->all() as $error)
                            <p class="mb-0">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Email Field -->
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-group">
                            <input type="email" 
                                   name="email" 
                                   id="email"
                                   class="form-control @error('email') is-invalid @enderror" 
                                   placeholder="Enter your email"
                                   value="{{ old('email') }}"
                                   required 
                                   autofocus>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-envelope"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <input type="password" 
                                   name="password" 
                                   id="password"
                                   class="form-control @error('password') is-invalid @enderror" 
                                   placeholder="Enter your password"
                                   required>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-lock"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="row mb-3">
                        <div class="col-7">
                            <div class="icheck-primary">
                                <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label for="remember">Remember Me</label>
                            </div>
                        </div>
                        <div class="col-5 text-right">
                            <a href="{{ route('password.request') }}" class="text-muted">
                                <small>Forgot password?</small>
                            </a>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-sign-in-alt mr-2"></i>Sign In
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
