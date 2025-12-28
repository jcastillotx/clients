<div>
    <div class="mb-4">
        <h2 class="text-2xl font-semibold text-slate-900">Two-factor authentication</h2>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-shield-alt mr-1"></i> 2FA</h3>
        </div>
        <div class="card-body">
            @if($confirmed)
                <div class="alert alert-success">2FA is enabled for your account.</div>

                @if(!empty($recoveryCodes))
                    <div class="alert alert-warning">
                        <div class="font-weight-bold">Recovery codes</div>
                        <div class="text-muted small">Store these somewhere safe. Each can be used once.</div>
                        <ul class="mb-0">
                            @foreach($recoveryCodes as $c)
                                <li><code>{{ $c }}</code></li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <button class="btn btn-outline-danger" wire:click="disable">
                    <i class="fas fa-times mr-1"></i> Disable 2FA
                </button>
            @else
                <div class="alert alert-info">
                    Scan the QR code with an authenticator app, then enter the 6-digit code to confirm.
                </div>

                @if($otpauth)
                    <div class="mb-3">
                        <div id="qrcode" style="display: inline-block;"></div>
                    </div>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var qrcodeContainer = document.getElementById("qrcode");
                            if (qrcodeContainer && !qrcodeContainer.hasChildNodes()) {
                                new QRCode(qrcodeContainer, {
                                    text: "{{ $otpauth }}",
                                    width: 200,
                                    height: 200,
                                    colorDark : "#000000",
                                    colorLight : "#ffffff",
                                    correctLevel : QRCode.CorrectLevel.H
                                });
                            }
                        });
                    </script>
                @endif

                <div class="form-group">
                    <label class="mb-1">Manual setup key</label>
                    <input class="form-control" value="{{ $secret }}" readonly>
                </div>

                <div class="form-group">
                    <label class="mb-1">Verification code</label>
                    <input class="form-control" wire:model.defer="code" placeholder="123456">
                    @error('code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                <button class="btn btn-primary" wire:click="confirm">
                    <i class="fas fa-check mr-1"></i> Confirm 2FA
                </button>
            @endif
        </div>
    </div>
</div>
