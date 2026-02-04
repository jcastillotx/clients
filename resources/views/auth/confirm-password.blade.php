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
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-amber-200">
        <div class="bg-gradient-to-r from-amber-500 to-amber-600 px-6 py-4 text-center">
            <h4 class="text-xl font-semibold text-white mb-1 flex items-center justify-center">
                <i class="fas fa-shield-alt mr-2"></i>Confirm Password
            </h4>
            <p class="text-white/90 text-sm">This is a secure area. Please confirm your password.</p>
        </div>

        <div class="p-6">
            @if($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg relative" role="alert">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.confirm') }}">
                @csrf

                <!-- Password Field -->
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-2">Password</label>
                    <div class="relative">
                        <input type="password"
                               name="password"
                               id="password"
                               class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition @error('password') border-red-500 @enderror"
                               placeholder="Enter your password"
                               required
                               autofocus>
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-slate-400"></i>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3 rounded-lg transition flex items-center justify-center">
                    <i class="fas fa-check mr-2"></i>Confirm Password
                </button>
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
