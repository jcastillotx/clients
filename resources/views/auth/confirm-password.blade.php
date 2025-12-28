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
                    <div class="mx-auto w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <h1 class="text-xl font-semibold text-slate-900">Confirm your password</h1>
                    <p class="text-sm text-slate-500 mt-1">This is a secure area. Please confirm your password before continuing.</p>
                </div>

                <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
                    @csrf

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-600 mb-1.5">
                            Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input type="password" 
                                   name="password" 
                                   id="password"
                                   class="w-full rounded-xl border border-slate-300 pl-10 pr-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('password') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror" 
                                   placeholder="••••••••"
                                   required
                                   autofocus>
                        </div>
                        @error('password')
                            <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2 transition-colors">
                        Confirm password
                    </button>
                </form>
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
