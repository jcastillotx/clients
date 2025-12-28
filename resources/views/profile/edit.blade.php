<x-app-layout>
    <x-slot name="header">Profile Settings</x-slot>

    <div class="max-w-5xl mx-auto space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-6">
                <!-- Profile Information -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-base font-semibold text-slate-900">Profile Information</h2>
                        <p class="text-sm text-slate-500 mt-0.5">Update your account's profile information and email address.</p>
                    </div>
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <div class="p-6 space-y-5">
                            <!-- Profile Photo -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-2">Profile photo</label>
                                <div class="flex items-center gap-4">
                                    @php $photoUrl = $user->profilePhotoUrl(); @endphp
                                    @if($photoUrl)
                                        <img src="{{ $photoUrl }}" alt="Profile photo" class="w-14 h-14 rounded-full object-cover ring-2 ring-slate-200">
                                    @else
                                        <div class="w-14 h-14 rounded-full bg-slate-900 flex items-center justify-center ring-2 ring-slate-200">
                                            <span class="text-white font-semibold text-lg">{{ $user->initials }}</span>
                                        </div>
                                    @endif
                                    <div class="flex-1">
                                        <input type="file"
                                               name="profile_photo"
                                               class="block w-full text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800 file:cursor-pointer file:transition-colors @error('profile_photo') border-rose-300 @enderror"
                                               accept="image/png,image/jpeg,image/webp">
                                        <p class="mt-1.5 text-xs text-slate-500">PNG, JPG, or WEBP up to 4MB</p>
                                        @error('profile_photo')
                                            <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                                </svg>
                                                <span>{{ $message }}</span>
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-xs font-semibold text-slate-600 mb-1.5">Name <span class="text-rose-500">*</span></label>
                                <input type="text" 
                                       name="name" 
                                       id="name" 
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('name') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                                       value="{{ old('name', $user->name) }}"
                                       required>
                                @error('name')
                                    <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ $message }}</span>
                                    </div>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-xs font-semibold text-slate-600 mb-1.5">Email <span class="text-rose-500">*</span></label>
                                <input type="email" 
                                       name="email" 
                                       id="email" 
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('email') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                                       value="{{ old('email', $user->email) }}"
                                       required>
                                @error('email')
                                    <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ $message }}</span>
                                    </div>
                                @enderror

                                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                    <div class="mt-2 rounded-xl bg-amber-50 border border-amber-200 p-3">
                                        <p class="text-sm text-amber-800 flex items-start gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 text-amber-600" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            <span>
                                                Your email address is unverified.
                                                <form id="send-verification" method="post" action="{{ route('verification.send') }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="font-semibold text-amber-900 underline hover:no-underline">
                                                        Click here to resend verification.
                                                    </button>
                                                </form>
                                            </span>
                                        </p>
                                    </div>
                                @endif
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-xs font-semibold text-slate-600 mb-1.5">Phone</label>
                                <input type="tel" 
                                       name="phone" 
                                       id="phone" 
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('phone') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                                       value="{{ old('phone', $user->phone) }}">
                                @error('phone')
                                    <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ $message }}</span>
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end">
                            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2 transition-colors">
                                Save changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Update Password -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-base font-semibold text-slate-900">Update Password</h2>
                        <p class="text-sm text-slate-500 mt-0.5">Ensure your account is using a long, random password to stay secure.</p>
                    </div>
                    <form method="POST" action="{{ route('profile.password') }}">
                        @csrf
                        @method('PUT')

                        <div class="p-6 space-y-5">
                            <!-- Current Password -->
                            <div>
                                <label for="current_password" class="block text-xs font-semibold text-slate-600 mb-1.5">Current password <span class="text-rose-500">*</span></label>
                                <input type="password" 
                                       name="current_password" 
                                       id="current_password" 
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('current_password') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                                       placeholder="••••••••"
                                       required>
                                @error('current_password')
                                    <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ $message }}</span>
                                    </div>
                                @enderror
                            </div>

                            <!-- New Password -->
                            <div>
                                <label for="password" class="block text-xs font-semibold text-slate-600 mb-1.5">New password <span class="text-rose-500">*</span></label>
                                <input type="password" 
                                       name="password" 
                                       id="password" 
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('password') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                                       placeholder="••••••••"
                                       required>
                                @error('password')
                                    <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ $message }}</span>
                                    </div>
                                @enderror
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label for="password_confirmation" class="block text-xs font-semibold text-slate-600 mb-1.5">Confirm new password <span class="text-rose-500">*</span></label>
                                <input type="password" 
                                       name="password_confirmation" 
                                       id="password_confirmation" 
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors"
                                       placeholder="••••••••"
                                       required>
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end">
                            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2 transition-colors">
                                Update password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- Company Information (Editable) -->
                @if($user->client)
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-base font-semibold text-slate-900">Company Information</h2>
                        <p class="text-sm text-slate-500 mt-0.5">Update your company details and address.</p>
                    </div>
                    <form method="POST" action="{{ route('profile.company.update') }}">
                        @csrf
                        @method('PATCH')
                        
                        <div class="p-6 space-y-5">
                            <!-- Company Name -->
                            <div>
                                <label for="company_name" class="block text-xs font-semibold text-slate-600 mb-1.5">Company name <span class="text-rose-500">*</span></label>
                                <input type="text" 
                                       name="company_name" 
                                       id="company_name" 
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('company_name') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                                       value="{{ old('company_name', $user->client->company_name) }}"
                                       required>
                                @error('company_name')
                                    <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ $message }}</span>
                                    </div>
                                @enderror
                            </div>

                            <!-- Contact Name -->
                            <div>
                                <label for="contact_name" class="block text-xs font-semibold text-slate-600 mb-1.5">Contact name <span class="text-rose-500">*</span></label>
                                <input type="text" 
                                       name="contact_name" 
                                       id="contact_name" 
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('contact_name') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                                       value="{{ old('contact_name', $user->client->contact_name) }}"
                                       required>
                                @error('contact_name')
                                    <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ $message }}</span>
                                    </div>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Company Phone -->
                                <div>
                                    <label for="company_phone" class="block text-xs font-semibold text-slate-600 mb-1.5">Company phone</label>
                                    <input type="tel" 
                                           name="company_phone" 
                                           id="company_phone" 
                                           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('company_phone') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                                           value="{{ old('company_phone', $user->client->phone) }}">
                                    @error('company_phone')
                                        <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                            </svg>
                                            <span>{{ $message }}</span>
                                        </div>
                                    @enderror
                                </div>

                                <!-- Website -->
                                <div>
                                    <label for="website" class="block text-xs font-semibold text-slate-600 mb-1.5">Website</label>
                                    <input type="url" 
                                           name="website" 
                                           id="website" 
                                           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('website') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                                           value="{{ old('website', $user->client->website) }}"
                                           placeholder="https://example.com">
                                    @error('website')
                                        <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                            </svg>
                                            <span>{{ $message }}</span>
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Industry -->
                            <div>
                                <label for="industry" class="block text-xs font-semibold text-slate-600 mb-1.5">Industry</label>
                                <input type="text" 
                                       name="industry" 
                                       id="industry" 
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('industry') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                                       value="{{ old('industry', $user->client->industry) }}">
                                @error('industry')
                                    <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ $message }}</span>
                                    </div>
                                @enderror
                            </div>

                            <!-- Address Section -->
                            <div class="border-t border-slate-200 pt-5 mt-5">
                                <h3 class="text-sm font-semibold text-slate-900 mb-4">Address</h3>
                                
                                <!-- Street Address -->
                                <div class="mb-4">
                                    <label for="address" class="block text-xs font-semibold text-slate-600 mb-1.5">Street address</label>
                                    <input type="text" 
                                           name="address" 
                                           id="address" 
                                           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('address') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                                           value="{{ old('address', $user->client->address) }}">
                                    @error('address')
                                        <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                            </svg>
                                            <span>{{ $message }}</span>
                                        </div>
                                    @enderror
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                    <!-- City -->
                                    <div class="col-span-2">
                                        <label for="city" class="block text-xs font-semibold text-slate-600 mb-1.5">City</label>
                                        <input type="text" 
                                               name="city" 
                                               id="city" 
                                               class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('city') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                                               value="{{ old('city', $user->client->city) }}">
                                        @error('city')
                                            <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                                </svg>
                                                <span>{{ $message }}</span>
                                            </div>
                                        @enderror
                                    </div>

                                    <!-- State -->
                                    <div>
                                        <label for="state" class="block text-xs font-semibold text-slate-600 mb-1.5">State</label>
                                        <input type="text" 
                                               name="state" 
                                               id="state" 
                                               class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('state') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                                               value="{{ old('state', $user->client->state) }}">
                                        @error('state')
                                            <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                                </svg>
                                                <span>{{ $message }}</span>
                                            </div>
                                        @enderror
                                    </div>

                                    <!-- ZIP -->
                                    <div>
                                        <label for="zip_code" class="block text-xs font-semibold text-slate-600 mb-1.5">ZIP</label>
                                        <input type="text" 
                                               name="zip_code" 
                                               id="zip_code" 
                                               class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('zip_code') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                                               value="{{ old('zip_code', $user->client->zip_code) }}">
                                        @error('zip_code')
                                            <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                                </svg>
                                                <span>{{ $message }}</span>
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Country -->
                                <div class="mt-4">
                                    <label for="country" class="block text-xs font-semibold text-slate-600 mb-1.5">Country</label>
                                    <input type="text" 
                                           name="country" 
                                           id="country" 
                                           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('country') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                                           value="{{ old('country', $user->client->country) }}">
                                    @error('country')
                                        <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                            </svg>
                                            <span>{{ $message }}</span>
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end">
                            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2 transition-colors">
                                Update company info
                            </button>
                        </div>
                    </form>
                </div>
                @endif

                <!-- Account Information (Read-only) -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-base font-semibold text-slate-900">Account Information</h2>
                    </div>
                    <div class="p-6">
                        <dl class="space-y-4">
                            <div class="flex justify-between">
                                <dt class="text-sm text-slate-500">Role</dt>
                                <dd class="text-sm font-medium text-slate-900">
                                    @php
                                        $roles = $user->roles->pluck('name');
                                        $roleLabels = [
                                            'super_admin' => 'Super Admin',
                                            'admin' => 'Admin',
                                            'staff' => 'Staff',
                                            'client' => 'Client',
                                            'project_manager' => 'Project Manager',
                                            'social_media_manager' => 'Social Media Manager',
                                        ];
                                        $badgeColors = [
                                            'super_admin' => 'bg-rose-100 text-rose-700',
                                            'admin' => 'bg-blue-100 text-blue-700',
                                            'staff' => 'bg-cyan-100 text-cyan-700',
                                            'client' => 'bg-emerald-100 text-emerald-700',
                                            'project_manager' => 'bg-amber-100 text-amber-700',
                                            'social_media_manager' => 'bg-purple-100 text-purple-700',
                                        ];
                                    @endphp
                                    @if($roles->count() > 0)
                                        <div class="flex flex-wrap gap-1 justify-end">
                                            @foreach($roles as $role)
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeColors[$role] ?? 'bg-slate-100 text-slate-700' }}">
                                                    {{ $roleLabels[$role] ?? ucwords(str_replace('_', ' ', $role)) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-slate-400">No role assigned</span>
                                    @endif
                                </dd>
                            </div>

                            @if($user->client)
                            <div class="flex justify-between">
                                <dt class="text-sm text-slate-500">Account tier</dt>
                                <dd>
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                                        {{ ucfirst($user->client->tier) }}
                                    </span>
                                </dd>
                            </div>

                            <div class="flex justify-between">
                                <dt class="text-sm text-slate-500">Status</dt>
                                <dd>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $user->client->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ ucfirst($user->client->status) }}
                                    </span>
                                </dd>
                            </div>
                            @endif

                            <div class="flex justify-between">
                                <dt class="text-sm text-slate-500">Account created</dt>
                                <dd class="text-sm font-medium text-slate-900">{{ $user->created_at->format('M d, Y') }}</dd>
                            </div>

                            <div class="flex justify-between">
                                <dt class="text-sm text-slate-500">Last login</dt>
                                <dd class="text-sm font-medium text-slate-900">
                                    {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
                                </dd>
                            </div>

                            <div class="flex justify-between">
                                <dt class="text-sm text-slate-500">Email verified</dt>
                                <dd>
                                    @if($user->email_verified_at)
                                        <span class="inline-flex items-center gap-1 text-sm font-medium text-emerald-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            {{ $user->email_verified_at->format('M d, Y') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-sm font-medium text-amber-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            Not verified
                                        </span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Danger Zone -->
                <div class="rounded-2xl border border-rose-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-rose-200 bg-rose-50">
                        <h2 class="text-base font-semibold text-rose-900">Danger Zone</h2>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-slate-600 mb-4">
                            Once you delete your account, all of your data will be permanently removed. Please be certain before proceeding.
                        </p>
                        <button type="button" 
                                class="rounded-lg border border-rose-300 bg-white px-4 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 transition-colors"
                                onclick="document.getElementById('deleteAccountModal').classList.remove('hidden')">
                            Delete account
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div id="deleteAccountModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-slate-900/50 transition-opacity" onclick="document.getElementById('deleteAccountModal').classList.add('hidden')"></div>

            <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>

            <!-- Modal panel -->
            <div class="relative inline-block transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                <form method="POST" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('DELETE')
                    
                    <div class="bg-rose-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-white" id="modal-title">Delete Account</h3>
                            <button type="button" class="text-white/80 hover:text-white" onclick="document.getElementById('deleteAccountModal').classList.add('hidden')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="px-6 py-5">
                        <p class="text-sm text-slate-600 mb-4">
                            Are you sure you want to delete your account? This action cannot be undone and all your data will be permanently removed.
                        </p>
                        <div>
                            <label for="delete_password" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                Enter your password to confirm
                            </label>
                            <input type="password" 
                                   name="password" 
                                   id="delete_password" 
                                   class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors"
                                   placeholder="••••••••"
                                   required>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-4 flex justify-end gap-3">
                        <button type="button" 
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors"
                                onclick="document.getElementById('deleteAccountModal').classList.add('hidden')">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 transition-colors">
                            Delete account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
