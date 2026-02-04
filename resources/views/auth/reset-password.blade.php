<x-guest-layout>
    <!-- Logo -->
    <div class="text-center mb-8">
        <a href="/" class="inline-block">
            @php
                $brandingService = app(\App\Services\BrandingService::class);
                $brand = $brandingService->all();
                $loginLogo = $brand['login_logo_path'] ?? '';
                $mainLogo = $brand['logo_path'] ?? '';
                $companyName = $brand['company_name'] ?? config('app.name', 'Client Portal');
            @endphp
            @if(!empty($loginLogo))
                <img src="{{ asset('storage/' . $loginLogo) }}" alt="{{ $companyName }}" class="h-14 max-h-14" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                <span class="hidden"><b>{{ $companyName }}</b></span>
            @elseif(!empty($mainLogo))
                <img src="{{ asset('storage/' . $mainLogo) }}" alt="{{ $companyName }}" class="h-14 max-h-14" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                <span class="hidden"><b>{{ $companyName }}</b></span>
            @else
                <span class="text-2xl font-bold text-white">{{ $companyName }}</span>
            @endif
        </a>
    </div>

    <!-- Card -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-slate-200">
        <div class="bg-gradient-to-r from-brand-primary to-brand-secondary px-6 py-4 text-center">
            <h4 class="text-xl font-semibold text-white mb-1 flex items-center justify-center">
                <i class="fas fa-key mr-2"></i>Create New Password
            </h4>
            <p class="text-white/80 text-sm">Enter your new password below</p>
        </div>

        <div class="p-6">
            @if($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg relative" role="alert">
                    <h5 class="font-semibold mb-2"><i class="fas fa-ban mr-2"></i>Error</h5>
                    @foreach ($errors->all() as $error)
                        <p class="text-sm">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.store') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <!-- Email Field -->
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-2">Email Address</label>
                    <div class="relative">
                        <input type="email"
                               name="email"
                               id="email"
                               class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-lg bg-slate-50 @error('email') border-red-500 @enderror"
                               value="{{ old('email', $request->email) }}"
                               required
                               readonly>
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-slate-400"></i>
                        </div>
                    </div>
                </div>

                <!-- Password Field -->
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-2">New Password</label>
                    <div class="relative">
                        <input type="password"
                               name="password"
                               id="password"
                               class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent transition @error('password') border-red-500 @enderror"
                               placeholder="Enter new password"
                               required
                               autofocus>
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-slate-400"></i>
                        </div>
                    </div>
                </div>

                <!-- Confirm Password Field -->
                <div class="mb-6">
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-2">Confirm New Password</label>
                    <div class="relative">
                        <input type="password"
                               name="password_confirmation"
                               id="password_confirmation"
                               class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent transition"
                               placeholder="Confirm new password"
                               required>
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-slate-400"></i>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full btn-brand-primary text-white font-semibold py-3 rounded-lg transition flex items-center justify-center mb-4">
                    <i class="fas fa-save mr-2"></i>Reset Password
                </button>

                <!-- Back to Login -->
                <div class="text-center">
                    <a href="{{ route('login') }}" class="text-slate-600 hover:text-brand-primary transition text-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Login
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center mt-6">
        <p class="text-white/70 text-sm">
            &copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.
        </p>
    </div>
</x-guest-layout>
