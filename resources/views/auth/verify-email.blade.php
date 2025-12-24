<x-guest-layout>
    <div class="login-box">
        <div class="login-logo">
            <a href="/">
                <b>Kre8iv</b> Client Portal
            </a>
        </div>

        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">
                    Thanks for signing up! Before getting started, please verify your email address by clicking on the link we just emailed to you.
                </p>

                @if(session('status') == 'verification-link-sent')
                <div class="alert alert-success mb-3">
                    A new verification link has been sent to your email address.
                </div>
                @endif

                <div class="row">
                    <div class="col-12 mb-3">
                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-block">
                                Resend Verification Email
                            </button>
                        </form>
                    </div>

                    <div class="col-12">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-block">
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
