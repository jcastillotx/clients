<x-app-layout>
    <x-slot name="header">Profile Settings</x-slot>

    <div class="row">
        <div class="col-lg-6">
            <!-- Profile Information -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Profile Information</h3>
                </div>
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PATCH')

                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $user->name) }}"
                                   required>
                            @error('name')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" 
                                   name="email" 
                                   id="email" 
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}"
                                   required>
                            @error('email')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror

                            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                            <div class="mt-2">
                                <p class="text-warning small">
                                    Your email address is unverified.
                                    <form id="send-verification" method="post" action="{{ route('verification.send') }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-link p-0 text-warning">
                                            Click here to re-send the verification email.
                                        </button>
                                    </form>
                                </p>
                            </div>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" 
                                   name="phone" 
                                   id="phone" 
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $user->phone) }}">
                            @error('phone')
                            <span class="invalid-feedback">{{ $message }}</span>
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
                    <h3 class="card-title">Update Password</h3>
                </div>
                <form method="POST" action="{{ route('profile.password') }}">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" 
                                   name="current_password" 
                                   id="current_password" 
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   required>
                            @error('current_password')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   class="form-control @error('password') is-invalid @enderror"
                                   required>
                            @error('password')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">Confirm New Password</label>
                            <input type="password" 
                                   name="password_confirmation" 
                                   id="password_confirmation" 
                                   class="form-control"
                                   required>
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

        <div class="col-lg-6">
            <!-- Account Info -->
            @if($user->client)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Company Information</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Company</dt>
                        <dd class="col-sm-8">{{ $user->client->company_name }}</dd>

                        <dt class="col-sm-4">Contact</dt>
                        <dd class="col-sm-8">{{ $user->client->contact_name }}</dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">{{ $user->client->email }}</dd>

                        @if($user->client->phone)
                        <dt class="col-sm-4">Phone</dt>
                        <dd class="col-sm-8">{{ $user->client->phone }}</dd>
                        @endif

                        @if($user->client->address)
                        <dt class="col-sm-4">Address</dt>
                        <dd class="col-sm-8">{{ $user->client->full_address }}</dd>
                        @endif

                        <dt class="col-sm-4">Account Tier</dt>
                        <dd class="col-sm-8">
                            <span class="badge badge-info">{{ ucfirst($user->client->tier) }}</span>
                        </dd>

                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            <span class="badge badge-{{ $user->client->status === 'active' ? 'success' : 'warning' }}">
                                {{ ucfirst($user->client->status) }}
                            </span>
                        </dd>
                    </dl>
                </div>
                <div class="card-footer text-muted">
                    <small>Contact support to update company information.</small>
                </div>
            </div>
            @endif

            <!-- Account Activity -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Account Activity</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Account Created</dt>
                        <dd class="col-sm-8">{{ $user->created_at->format('M d, Y') }}</dd>

                        <dt class="col-sm-4">Last Login</dt>
                        <dd class="col-sm-8">
                            {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
                        </dd>

                        <dt class="col-sm-4">Email Verified</dt>
                        <dd class="col-sm-8">
                            @if($user->email_verified_at)
                            <span class="text-success">
                                <i class="fas fa-check-circle mr-1"></i>
                                {{ $user->email_verified_at->format('M d, Y') }}
                            </span>
                            @else
                            <span class="text-warning">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                Not verified
                            </span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="card card-danger">
                <div class="card-header">
                    <h3 class="card-title">Danger Zone</h3>
                </div>
                <div class="card-body">
                    <p>
                        Once you delete your account, all of your data will be permanently removed. 
                        Please be certain before proceeding.
                    </p>
                    <button type="button" 
                            class="btn btn-outline-danger" 
                            data-toggle="modal" 
                            data-target="#deleteAccountModal">
                        <i class="fas fa-trash mr-1"></i> Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white">Delete Account</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body">
                        <p>Are you sure you want to delete your account? This action cannot be undone.</p>
                        <div class="form-group">
                            <label for="delete_password">Enter your password to confirm:</label>
                            <input type="password" 
                                   name="password" 
                                   id="delete_password" 
                                   class="form-control"
                                   required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
