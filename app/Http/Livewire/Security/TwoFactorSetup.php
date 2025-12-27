<?php

namespace App\Http\Livewire\Security;

use App\Services\Security\TwoFactorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Livewire\Component;

class TwoFactorSetup extends Component
{
    public string $secret = '';
    public string $code = '';
    public array $recoveryCodes = [];
    public bool $confirmed = false;

    public function mount(TwoFactorService $svc): void
    {
        $u = Auth::user();
        abort_unless($u, 403);

        if ($u->two_factor_confirmed_at) {
            $this->confirmed = true;
            return;
        }

        // If already generated but not confirmed, reuse.
        if ($u->two_factor_secret) {
            try {
                $this->secret = Crypt::decryptString($u->two_factor_secret);
                return;
            } catch (\Throwable) {
                // fall through and regenerate
            }
        }

        $this->secret = $svc->generateSecret(20);
        $u->update([
            'two_factor_secret' => Crypt::encryptString($this->secret),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_enabled' => false,
        ]);
    }

    public function confirm(TwoFactorService $svc): void
    {
        $u = Auth::user();
        abort_unless($u, 403);
        abort_unless(!$u->two_factor_confirmed_at, 422);

        $secret = $this->secret;
        if (!$svc->verifyCode($secret, $this->code, 1)) {
            $this->addError('code', 'Invalid code. Try again.');
            return;
        }

        $codes = $svc->generateRecoveryCodes(8);
        $u->update([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($codes)),
            'two_factor_confirmed_at' => now(),
            'two_factor_enabled' => true,
        ]);

        $this->recoveryCodes = $codes;
        $this->confirmed = true;
        session()->flash('success', 'Two-factor authentication enabled.');
    }

    public function disable(): void
    {
        $u = Auth::user();
        abort_unless($u, 403);

        $u->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_enabled' => false,
        ]);

        $this->reset(['secret', 'code', 'recoveryCodes', 'confirmed']);
        session()->flash('success', 'Two-factor authentication disabled.');
    }

    public function render(TwoFactorService $svc)
    {
        $u = Auth::user();
        abort_unless($u, 403);

        $issuer = (string) config('security.two_factor_issuer', config('app.name'));
        $label = (string) ($u->email ?: $u->name ?: 'user');
        $otpauth = $this->secret ? $svc->otpAuthUrl($issuer, $label, $this->secret) : '';
        $qrUrl = $otpauth ? ('https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . rawurlencode($otpauth)) : '';

        // If confirmed and codes not loaded, try decrypt.
        if ($u->two_factor_confirmed_at && empty($this->recoveryCodes) && $u->two_factor_recovery_codes) {
            try {
                $codes = json_decode(Crypt::decryptString($u->two_factor_recovery_codes), true);
                if (is_array($codes)) $this->recoveryCodes = $codes;
            } catch (\Throwable) {
                // ignore
            }
        }

        return view('livewire.security.two-factor-setup', [
            'otpauth' => $otpauth,
            'qrUrl' => $qrUrl,
        ]);
    }
}

