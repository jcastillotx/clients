<x-app-layout>
    <x-slot name="header">Profile Settings</x-slot>

    <div class="container-fluid">
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-6">
                <!-- Profile Information -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user mr-2"></i>Profile Information</h3>
                    </div>
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <div class="card-body">
                            <!-- Profile Photo -->
                            <div class="form-group">
                                <label>Profile Photo</label>
                                <div class="d-flex align-items-center">
                                    @php $photoUrl = $user->profilePhotoUrl(); @endphp
                                    @if($photoUrl)
                                        <div class="position-relative mr-3" style="cursor: pointer;" data-toggle="modal" data-target="#profilePhotoModal">
                                            <img src="{{ $photoUrl }}" alt="Profile photo" class="img-circle elevation-2" style="width: 64px; height: 64px; object-fit: cover;">
                                            <div class="position-absolute d-flex align-items-center justify-content-center" style="top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); border-radius: 50%; opacity: 0; transition: opacity 0.2s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0">
                                                <i class="fas fa-search-plus text-white"></i>
                                            </div>
                                        </div>
                                    @else
                                        <div class="mr-3 d-flex align-items-center justify-content-center bg-primary text-white rounded-circle" style="width: 64px; height: 64px; font-size: 1.5rem; font-weight: 600;">
                                            {{ $user->initials }}
                                        </div>
                                    @endif
                                    <div class="flex-grow-1">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input @error('profile_photo') is-invalid @enderror" id="profile_photo" name="profile_photo" accept="image/png,image/jpeg,image/webp">
                                            <label class="custom-file-label" for="profile_photo">Choose file...</label>
                                        </div>
                                        <small class="text-muted d-block mt-1">PNG, JPG, or WEBP up to 4MB. @if($photoUrl)Click photo to view full size.@endif</small>
                                        @error('profile_photo')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Name -->
                            <div class="form-group">
                                <label for="name">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div class="form-group">
                                <label for="email">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                    <div class="alert alert-warning mt-2 mb-0 py-2">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        Your email address is unverified.
                                        <form id="send-verification" method="post" action="{{ route('verification.send') }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-link p-0 text-warning">Click here to resend verification.</button>
                                        </form>
                                    </div>
                                @endif
                            </div>

                            <!-- Phone -->
                            <div class="form-group mb-0">
                                <label for="phone">Phone</label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Update Password -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-lock mr-2"></i>Update Password</h3>
                    </div>
                    <form method="POST" action="{{ route('profile.password') }}">
                        @csrf
                        @method('PUT')

                        <div class="card-body">
                            <p class="text-muted">Ensure your account is using a long, random password to stay secure.</p>
                            
                            <!-- Current Password -->
                            <div class="form-group">
                                <label for="current_password">Current Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" required>
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- New Password -->
                            <div class="form-group">
                                <label for="password">New Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Confirm Password -->
                            <div class="form-group mb-0">
                                <label for="password_confirmation">Confirm New Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key mr-1"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-6">
                <!-- Company Information (Editable) -->
                @if($user->client)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-building mr-2"></i>Company Information</h3>
                    </div>
                    <form method="POST" action="{{ route('profile.company.update') }}">
                        @csrf
                        @method('PATCH')
                        
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="company_name">Company Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('company_name') is-invalid @enderror" id="company_name" name="company_name" value="{{ old('company_name', $user->client->company_name) }}" required>
                                        @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="contact_name">Contact Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('contact_name') is-invalid @enderror" id="contact_name" name="contact_name" value="{{ old('contact_name', $user->client->contact_name) }}" required>
                                        @error('contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="company_phone">Company Phone</label>
                                        <input type="tel" class="form-control @error('company_phone') is-invalid @enderror" id="company_phone" name="company_phone" value="{{ old('company_phone', $user->client->phone) }}">
                                        @error('company_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="website">Website</label>
                                        <input type="url" class="form-control @error('website') is-invalid @enderror" id="website" name="website" value="{{ old('website', $user->client->website) }}" placeholder="https://example.com">
                                        @error('website') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="industry">Industry</label>
                                <input type="text" class="form-control @error('industry') is-invalid @enderror" id="industry" name="industry" value="{{ old('industry', $user->client->industry) }}">
                                @error('industry') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <hr>
                            <h5><i class="fas fa-map-marker-alt mr-2"></i>Address</h5>

                            <div class="form-group">
                                <label for="address">Street Address</label>
                                <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $user->client->address) }}">
                                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="city">City</label>
                                        <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city', $user->client->city) }}">
                                        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="state">State</label>
                                        <input type="text" class="form-control @error('state') is-invalid @enderror" id="state" name="state" value="{{ old('state', $user->client->state) }}">
                                        @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="zip_code">ZIP</label>
                                        <input type="text" class="form-control @error('zip_code') is-invalid @enderror" id="zip_code" name="zip_code" value="{{ old('zip_code', $user->client->zip_code) }}">
                                        @error('zip_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <label for="country">Country</label>
                                <input type="text" class="form-control @error('country') is-invalid @enderror" id="country" name="country" value="{{ old('country', $user->client->country) }}">
                                @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Update Company Info
                            </button>
                        </div>
                    </form>
                </div>
                @endif

                <!-- Account Information (Read-only) -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Account Information</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <tbody>
                                <tr>
                                    <td class="font-weight-bold" style="width: 40%;">Role</td>
                                    <td>
                                        @php
                                            $roles = $user->roles->pluck('name');
                                            $roleLabels = [
                                                'super_admin' => 'Super Admin',
                                                'admin' => 'Admin',
                                                'staff' => 'Staff',
                                                'client' => 'Client',
                                            ];
                                            $badgeColors = [
                                                'super_admin' => 'danger',
                                                'admin' => 'primary',
                                                'staff' => 'info',
                                                'client' => 'success',
                                            ];
                                        @endphp
                                        @forelse($roles as $role)
                                            <span class="badge badge-{{ $badgeColors[$role] ?? 'secondary' }}">
                                                {{ $roleLabels[$role] ?? ucwords(str_replace('_', ' ', $role)) }}
                                            </span>
                                        @empty
                                            <span class="text-muted">No role assigned</span>
                                        @endforelse
                                    </td>
                                </tr>
                                @if($user->client)
                                <tr>
                                    <td class="font-weight-bold">Account Tier</td>
                                    <td><span class="badge badge-info">{{ ucfirst($user->client->tier) }}</span></td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Status</td>
                                    <td>
                                        <span class="badge badge-{{ $user->client->status === 'active' ? 'success' : 'warning' }}">
                                            {{ ucfirst($user->client->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @endif
                                <tr>
                                    <td class="font-weight-bold">Account Created</td>
                                    <td>{{ $user->created_at->format('M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Last Login</td>
                                    <td>{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Email Verified</td>
                                    <td>
                                        @if($user->email_verified_at)
                                            <span class="text-success"><i class="fas fa-check-circle mr-1"></i>{{ $user->email_verified_at->format('M d, Y') }}</span>
                                        @else
                                            <span class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Not verified</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Danger Zone -->
                @if(!$user->hasRole('super_admin'))
                <div class="card card-outline card-danger">
                    <div class="card-header">
                        <h3 class="card-title text-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Danger Zone</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Once you delete your account, all of your data will be permanently removed. Please be certain before proceeding.</p>
                        <button type="button" class="btn btn-outline-danger" data-toggle="modal" data-target="#deleteAccountModal">
                            <i class="fas fa-trash-alt mr-1"></i> Delete Account
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Profile Photo Preview Modal -->
    @php $photoUrl = $user->profilePhotoUrl(); @endphp
    @if($photoUrl)
    <div class="modal fade" id="profilePhotoModal" tabindex="-1" role="dialog" aria-labelledby="profilePhotoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="profilePhotoModalLabel">Profile Photo</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center p-4 bg-light">
                    <img src="{{ $photoUrl }}" alt="Profile photo" class="img-fluid rounded shadow" style="max-height: 70vh;">
                </div>
                <div class="modal-footer">
                    <a href="{{ $photoUrl }}" download class="btn btn-outline-primary">
                        <i class="fas fa-download mr-1"></i> Download
                    </a>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Delete Account Modal -->
    @if(!$user->hasRole('super_admin'))
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" role="dialog" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('DELETE')
                    
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deleteAccountModalLabel"><i class="fas fa-exclamation-triangle mr-2"></i>Delete Account</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete your account? This action cannot be undone and all your data will be permanently removed.</p>
                        <div class="form-group mb-0">
                            <label for="delete_password">Enter your password to confirm</label>
                            <input type="password" class="form-control" id="delete_password" name="password" placeholder="Password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash-alt mr-1"></i> Delete Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @push('scripts')
    <script>
        // Update custom file input label
        document.querySelectorAll('.custom-file-input').forEach(function(input) {
            input.addEventListener('change', function(e) {
                var fileName = e.target.files[0] ? e.target.files[0].name : 'Choose file...';
                var label = e.target.nextElementSibling;
                if (label) {
                    label.textContent = fileName;
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
