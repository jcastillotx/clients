<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <a href="/" class="inline-block">
                    @php
                        $loginLogo = config('branding.auth.login_logo');
                        $mainLogo = config('branding.logo.main');
                    @endphp
                    @if(!empty($loginLogo))
                        <img src="{{ asset($loginLogo) }}" alt="{{ config('branding.company.name') }} Logo" class="h-12 mx-auto" onerror="this.style.display='none'">
                    @elseif(!empty($mainLogo))
                        <img src="{{ asset($mainLogo) }}" alt="{{ config('branding.company.name') }} Logo" class="h-12 mx-auto" onerror="this.style.display='none'">
                    @else
                        <span class="text-2xl font-bold text-white">{{ config('branding.company.name', 'Client') }} Portal</span>
                    @endif
                </a>
            </div>

            <!-- Card -->
            <div class="rounded-2xl bg-white p-8 shadow-xl">
                <div class="text-center mb-6">
                    <div class="mx-auto w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                        </svg>
                    </div>
                    <h1 class="text-xl font-semibold text-slate-900">Verify your email</h1>
                    <p class="text-sm text-slate-500 mt-1">Thanks for signing up! Before getting started, please verify your email address by clicking on the link we just emailed to you.</p>
                </div>

                @if(session('status') == 'verification-link-sent')
                    <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>A new verification link has been sent to your email address.</span>
                    </div>
                @endif

                <div class="space-y-3">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2 transition-colors">
                            Resend verification email
                        </button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition-colors">
                            Log out
                        </button>
                    </form>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-6">
                <p class="text-sm text-white/80">
                    &copy; {{ date('Y') }} {{ config('branding.company.name', 'Kre8iv Designs') }}. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</x-guest-layout>
