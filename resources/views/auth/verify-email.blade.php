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
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-blue-200">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4 text-center">
            <h4 class="text-xl font-semibold text-white mb-1 flex items-center justify-center">
                <i class="fas fa-envelope-open-text mr-2"></i>Verify Your Email
            </h4>
            <p class="text-white/90 text-sm">Please verify your email address to continue</p>
        </div>

        <div class="p-6">
            <div class="text-center mb-6">
                <div class="mb-4 inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full">
                    <i class="fas fa-envelope text-blue-600 text-2xl"></i>
                </div>
                <p class="text-slate-600 text-sm leading-relaxed">
                    Thanks for signing up! Before getting started, please verify your email address by clicking on the link we just emailed to you.
                </p>
            </div>

            @if(session('status') == 'verification-link-sent')
                <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg" role="alert">
                    <i class="fas fa-check mr-2"></i>
                    A new verification link has been sent to your email address.
                </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}" class="mb-3">
                @csrf
                <button type="submit" class="w-full btn-brand-primary text-white font-semibold py-3 rounded-lg transition flex items-center justify-center">
                    <i class="fas fa-redo mr-2"></i>Resend Verification Email
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold py-3 rounded-lg transition flex items-center justify-center">
                    <i class="fas fa-sign-out-alt mr-2"></i>Log Out
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
