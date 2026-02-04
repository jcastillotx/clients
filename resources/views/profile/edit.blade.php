<x-app-layout>
    <x-slot name="header">Profile Settings</x-slot>

    <div class="w-full px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-6">
                <!-- Profile Information -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h3 class="text-base font-semibold text-slate-900">
                            <i class="fas fa-user mr-2"></i>Profile Information
                        </h3>
                    </div>
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <div class="p-6 space-y-4">
                            <!-- Profile Photo -->
                            <div x-data="{
                                photoModalOpen: false,
                                fileName: 'Choose file...',
                                updateFileName(event) {
                                    this.fileName = event.target.files[0]?.name || 'Choose file...';
                                }
                            }">
                                <label class="block text-xs font-semibold text-slate-600 mb-2">Profile Photo</label>
                                <div class="flex items-center gap-4">
                                    @php $photoUrl = $user->profilePhotoUrl(); @endphp
                                    @if($photoUrl)
                                        <div class="relative cursor-pointer group" @click="photoModalOpen = true">
                                            <img src="{{ $photoUrl }}" alt="Profile photo" class="w-16 h-16 rounded-full object-cover shadow">
                                            <div class="absolute inset-0 bg-black/40 rounded-full opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                <i class="fas fa-search-plus text-white"></i>
                                            </div>
                                        </div>
                                    @else
                                        <div class="w-16 h-16 rounded-full bg-blue-600 text-white flex items-center justify-content font-semibold text-xl">
                                            {{ $user->initials }}
                                        </div>
                                    @endif
                                    <div class="flex-1">
                                        <input type="file"
                                               class="hidden @error('profile_photo') border-rose-500 @enderror"
                                               id="profile_photo"
                                               name="profile_photo"
                                               accept="image/png,image/jpeg,image/webp"
                                               x-ref="fileInput"
                                               @change="updateFileName($event)">
                                        <button type="button"
                                                @click="$refs.fileInput.click()"
                                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                                            <span x-text="fileName"></span>
                                        </button>
                                        <small class="block mt-1.5 text-xs text-slate-500">
                                            PNG, JPG, or WEBP up to 4MB. @if($photoUrl)Click photo to view full size.@endif
                                        </small>
                                        @error('profile_photo')
                                            <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Photo Preview Modal -->
                                @if($photoUrl)
                                <div x-show="photoModalOpen"
                                     x-cloak
                                     @click.away="photoModalOpen = false"
                                     @keydown.escape.window="photoModalOpen = false"
                                     class="fixed inset-0 z-50 overflow-y-auto"
                                     style="display: none;">
                                    <div class="flex items-center justify-center min-h-screen px-4">
                                        <div class="fixed inset-0 bg-black/50 transition-opacity"></div>
                                        <div class="relative bg-white rounded-2xl shadow-xl max-w-3xl w-full">
                                            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                                                <h5 class="text-lg font-semibold text-slate-900">Profile Photo</h5>
                                                <button @click="photoModalOpen = false" class="text-slate-400 hover:text-slate-600">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="p-8 bg-slate-50 text-center">
                                                <img src="{{ $photoUrl }}" alt="Profile photo" class="max-h-[70vh] mx-auto rounded-lg shadow-lg">
                                            </div>
                                            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-200">
                                                <a href="{{ $photoUrl }}" download class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                                                    <i class="fas fa-download mr-1"></i> Download
                                                </a>
                                                <button @click="photoModalOpen = false" class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-300 transition-colors">
                                                    Close
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>

                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                    Name <span class="text-rose-500">*</span>
                                </label>
                                <input type="text"
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('name') border-rose-500 @enderror"
                                       id="name"
                                       name="name"
                                       value="{{ old('name', $user->name) }}"
                                       required>
                                @error('name')
                                    <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                    Email <span class="text-rose-500">*</span>
                                </label>
                                <input type="email"
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('email') border-rose-500 @enderror"
                                       id="email"
                                       name="email"
                                       value="{{ old('email', $user->email) }}"
                                       required>
                                @error('email')
                                    <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                                @enderror

                                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                    <div class="mt-2 rounded-xl bg-amber-50 border border-amber-200 p-3">
                                        <i class="fas fa-exclamation-triangle mr-2 text-amber-600"></i>
                                        <span class="text-sm text-amber-800">Your email address is unverified.</span>
                                        <form id="send-verification" method="post" action="{{ route('verification.send') }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-sm font-semibold text-amber-700 underline hover:text-amber-800">
                                                Click here to resend verification.
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-xs font-semibold text-slate-600 mb-1.5">Phone</label>
                                <input type="tel"
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('phone') border-rose-500 @enderror"
                                       id="phone"
                                       name="phone"
                                       value="{{ old('phone', $user->phone) }}">
                                @error('phone')
                                    <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
                                <i class="fas fa-save mr-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Update Password -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h3 class="text-base font-semibold text-slate-900">
                            <i class="fas fa-lock mr-2"></i>Update Password
                        </h3>
                    </div>
                    <form method="POST" action="{{ route('profile.password') }}">
                        @csrf
                        @method('PUT')

                        <div class="p-6 space-y-4">
                            <p class="text-sm text-slate-600">Ensure your account is using a long, random password to stay secure.</p>

                            <!-- Current Password -->
                            <div>
                                <label for="current_password" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                    Current Password <span class="text-rose-500">*</span>
                                </label>
                                <input type="password"
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('current_password') border-rose-500 @enderror"
                                       id="current_password"
                                       name="current_password"
                                       required>
                                @error('current_password')
                                    <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- New Password -->
                            <div>
                                <label for="password" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                    New Password <span class="text-rose-500">*</span>
                                </label>
                                <input type="password"
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('password') border-rose-500 @enderror"
                                       id="password"
                                       name="password"
                                       required>
                                @error('password')
                                    <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label for="password_confirmation" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                    Confirm New Password <span class="text-rose-500">*</span>
                                </label>
                                <input type="password"
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors"
                                       id="password_confirmation"
                                       name="password_confirmation"
                                       required>
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
                                <i class="fas fa-key mr-1"></i> Update Password
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
                        <h3 class="text-base font-semibold text-slate-900">
                            <i class="fas fa-building mr-2"></i>Company Information
                        </h3>
                    </div>
                    <form method="POST" action="{{ route('profile.company.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="company_name" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                        Company Name <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text"
                                           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('company_name') border-rose-500 @enderror"
                                           id="company_name"
                                           name="company_name"
                                           value="{{ old('company_name', $user->client->company_name) }}"
                                           required>
                                    @error('company_name')
                                        <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label for="contact_name" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                        Contact Name <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text"
                                           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('contact_name') border-rose-500 @enderror"
                                           id="contact_name"
                                           name="contact_name"
                                           value="{{ old('contact_name', $user->client->contact_name) }}"
                                           required>
                                    @error('contact_name')
                                        <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="company_phone" class="block text-xs font-semibold text-slate-600 mb-1.5">Company Phone</label>
                                    <input type="tel"
                                           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('company_phone') border-rose-500 @enderror"
                                           id="company_phone"
                                           name="company_phone"
                                           value="{{ old('company_phone', $user->client->phone) }}">
                                    @error('company_phone')
                                        <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label for="website" class="block text-xs font-semibold text-slate-600 mb-1.5">Website</label>
                                    <input type="url"
                                           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('website') border-rose-500 @enderror"
                                           id="website"
                                           name="website"
                                           value="{{ old('website', $user->client->website) }}"
                                           placeholder="https://example.com">
                                    @error('website')
                                        <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="industry" class="block text-xs font-semibold text-slate-600 mb-1.5">Industry</label>
                                <input type="text"
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('industry') border-rose-500 @enderror"
                                       id="industry"
                                       name="industry"
                                       value="{{ old('industry', $user->client->industry) }}">
                                @error('industry')
                                    <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="border-t border-slate-200 pt-4">
                                <h5 class="text-sm font-semibold text-slate-900 mb-3">
                                    <i class="fas fa-map-marker-alt mr-2"></i>Address
                                </h5>
                            </div>

                            <div>
                                <label for="address" class="block text-xs font-semibold text-slate-600 mb-1.5">Street Address</label>
                                <input type="text"
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('address') border-rose-500 @enderror"
                                       id="address"
                                       name="address"
                                       value="{{ old('address', $user->client->address) }}">
                                @error('address')
                                    <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="col-span-2">
                                    <label for="city" class="block text-xs font-semibold text-slate-600 mb-1.5">City</label>
                                    <input type="text"
                                           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('city') border-rose-500 @enderror"
                                           id="city"
                                           name="city"
                                           value="{{ old('city', $user->client->city) }}">
                                    @error('city')
                                        <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label for="state" class="block text-xs font-semibold text-slate-600 mb-1.5">State</label>
                                    <input type="text"
                                           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('state') border-rose-500 @enderror"
                                           id="state"
                                           name="state"
                                           value="{{ old('state', $user->client->state) }}">
                                    @error('state')
                                        <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label for="zip_code" class="block text-xs font-semibold text-slate-600 mb-1.5">ZIP</label>
                                    <input type="text"
                                           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('zip_code') border-rose-500 @enderror"
                                           id="zip_code"
                                           name="zip_code"
                                           value="{{ old('zip_code', $user->client->zip_code) }}">
                                    @error('zip_code')
                                        <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="country" class="block text-xs font-semibold text-slate-600 mb-1.5">Country</label>
                                <input type="text"
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('country') border-rose-500 @enderror"
                                       id="country"
                                       name="country"
                                       value="{{ old('country', $user->client->country) }}">
                                @error('country')
                                    <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
                                <i class="fas fa-save mr-1"></i> Update Company Info
                            </button>
                        </div>
                    </form>
                </div>
                @endif

                <!-- Account Information (Read-only) -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h3 class="text-base font-semibold text-slate-900">
                            <i class="fas fa-info-circle mr-2"></i>Account Information
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <tbody class="divide-y divide-slate-200">
                                <tr>
                                    <td class="px-6 py-3 text-xs font-semibold text-slate-600 w-2/5">Role</td>
                                    <td class="px-6 py-3">
                                        @php
                                            $roles = $user->roles->pluck('name');
                                            $roleLabels = [
                                                'super_admin' => 'Super Admin',
                                                'admin' => 'Admin',
                                                'staff' => 'Staff',
                                                'client' => 'Client',
                                            ];
                                            $badgeColors = [
                                                'super_admin' => 'bg-rose-100 text-rose-700',
                                                'admin' => 'bg-blue-100 text-blue-700',
                                                'staff' => 'bg-cyan-100 text-cyan-700',
                                                'client' => 'bg-emerald-100 text-emerald-700',
                                            ];
                                        @endphp
                                        @forelse($roles as $role)
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeColors[$role] ?? 'bg-slate-100 text-slate-700' }}">
                                                {{ $roleLabels[$role] ?? ucwords(str_replace('_', ' ', $role)) }}
                                            </span>
                                        @empty
                                            <span class="text-sm text-slate-500">No role assigned</span>
                                        @endforelse
                                    </td>
                                </tr>
                                @if($user->client)
                                <tr>
                                    <td class="px-6 py-3 text-xs font-semibold text-slate-600">Account Tier</td>
                                    <td class="px-6 py-3">
                                        <span class="inline-flex items-center rounded-full bg-cyan-100 text-cyan-700 px-2.5 py-0.5 text-xs font-semibold">
                                            {{ ucfirst($user->client->tier) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-3 text-xs font-semibold text-slate-600">Status</td>
                                    <td class="px-6 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $user->client->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ ucfirst($user->client->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @endif
                                <tr>
                                    <td class="px-6 py-3 text-xs font-semibold text-slate-600">Account Created</td>
                                    <td class="px-6 py-3 text-sm text-slate-900">{{ $user->created_at->format('M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-3 text-xs font-semibold text-slate-600">Last Login</td>
                                    <td class="px-6 py-3 text-sm text-slate-900">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-3 text-xs font-semibold text-slate-600">Email Verified</td>
                                    <td class="px-6 py-3">
                                        @if($user->email_verified_at)
                                            <span class="text-sm text-emerald-600 flex items-center gap-1">
                                                <i class="fas fa-check-circle"></i>
                                                {{ $user->email_verified_at->format('M d, Y') }}
                                            </span>
                                        @else
                                            <span class="text-sm text-amber-600 flex items-center gap-1">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                Not verified
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Danger Zone -->
                @if(!$user->hasRole('super_admin'))
                <div class="rounded-2xl border border-rose-200 bg-white shadow-sm overflow-hidden" x-data="{ deleteModalOpen: false }">
                    <div class="px-6 py-4 border-b border-rose-200 bg-rose-50">
                        <h3 class="text-base font-semibold text-rose-700">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Danger Zone
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-slate-600 mb-4">
                            Once you delete your account, all of your data will be permanently removed. Please be certain before proceeding.
                        </p>
                        <button type="button"
                                @click="deleteModalOpen = true"
                                class="rounded-lg border-2 border-rose-600 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50 transition-colors">
                            <i class="fas fa-trash-alt mr-1"></i> Delete Account
                        </button>
                    </div>

                    <!-- Delete Account Modal -->
                    <div x-show="deleteModalOpen"
                         x-cloak
                         @click.away="deleteModalOpen = false"
                         @keydown.escape.window="deleteModalOpen = false"
                         class="fixed inset-0 z-50 overflow-y-auto"
                         style="display: none;">
                        <div class="flex items-center justify-center min-h-screen px-4">
                            <div class="fixed inset-0 bg-black/50 transition-opacity"></div>
                            <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full">
                                <form method="POST" action="{{ route('profile.destroy') }}">
                                    @csrf
                                    @method('DELETE')

                                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-rose-50">
                                        <h5 class="text-lg font-semibold text-rose-700">
                                            <i class="fas fa-exclamation-triangle mr-2"></i>Delete Account
                                        </h5>
                                        <button type="button" @click="deleteModalOpen = false" class="text-slate-400 hover:text-slate-600">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <p class="text-sm text-slate-900">
                                            Are you sure you want to delete your account? This action cannot be undone and all your data will be permanently removed.
                                        </p>
                                        <div>
                                            <label for="delete_password" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                                Enter your password to confirm
                                            </label>
                                            <input type="password"
                                                   class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors"
                                                   id="delete_password"
                                                   name="password"
                                                   placeholder="Password"
                                                   required>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-200">
                                        <button type="button"
                                                @click="deleteModalOpen = false"
                                                class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-300 transition-colors">
                                            Cancel
                                        </button>
                                        <button type="submit"
                                                class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 transition-colors">
                                            <i class="fas fa-trash-alt mr-1"></i> Delete Account
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
