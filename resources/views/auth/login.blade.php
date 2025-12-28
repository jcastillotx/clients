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

            <!-- Login Card -->
            <div class="rounded-2xl bg-white p-8 shadow-xl">
                <div class="text-center mb-6">
                    <h1 class="text-xl font-semibold text-slate-900">Welcome back</h1>
                    <p class="text-sm text-slate-500 mt-1">Sign in to access your account</p>
                </div>

                @if(session('status'))
                    <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-600 mb-1.5">
                            Email address
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.162V6a2 2 0 00-2-2H3z" />
                                    <path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z" />
                                </svg>
                            </div>
                            <input type="email" 
                                   name="email" 
                                   id="email"
                                   class="w-full rounded-xl border border-slate-300 pl-10 pr-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('email') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror" 
                                   placeholder="you@example.com"
                                   value="{{ old('email') }}"
                                   required 
                                   autofocus>
                        </div>
                        @error('email')
                            <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

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
                                   required>
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

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" 
                                   name="remember" 
                                   id="remember" 
                                   class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0"
                                   {{ old('remember') ? 'checked' : '' }}>
                            <span class="text-sm text-slate-600">Remember me</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="text-sm font-medium text-slate-900 hover:text-slate-700">
                            Forgot password?
                        </a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2 transition-colors">
                        Sign in
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
