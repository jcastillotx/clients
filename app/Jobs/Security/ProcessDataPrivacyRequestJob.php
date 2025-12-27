<?php

namespace App\Jobs\Security;

use App\Models\Contract;
use App\Models\DataPrivacyRequest;
use App\Models\Invoice;
use App\Models\Request as ServiceRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessDataPrivacyRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $privacyRequestId) {}

    public function handle(): void
    {
        $req = DataPrivacyRequest::query()->with('user')->find($this->privacyRequestId);
        if (! $req || $req->status !== 'pending') {
            return;
        }

        $user = $req->user;
        if (! $user) {
            $req->update(['status' => 'rejected', 'notes' => 'User not found', 'processed_at' => now()]);

            return;
        }

        if ($req->type === 'export') {
            $payload = $this->buildExportPayload($user);
            $path = 'privacy/'.$req->id.'/export_'.now()->format('Ymd_His').'.json';
            $bytes = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            Storage::disk('exports')->put($path, $bytes ?: '{}');

            $req->update([
                'status' => 'processed',
                'processed_at' => now(),
                'meta' => [
                    'disk' => 'exports',
                    'path' => $path,
                    'bytes' => $bytes ? strlen($bytes) : 2,
                ],
            ]);

            return;
        }

        if ($req->type === 'delete') {
            // MVP: soft-delete the user and anonymize basic fields.
            $anonEmail = 'deleted+'.$user->id.'+'.Str::random(8).'@example.invalid';

            $user->tokens()->delete();
            $user->forceFill([
                'name' => 'Deleted User',
                'email' => $anonEmail,
                'password' => Hash::make(Str::random(40)),
                'phone' => null,
                'avatar' => null,
                'two_factor_enabled' => false,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'is_active' => false,
                'status' => 'deleted',
            ])->save();

            $user->delete();

            $req->update([
                'status' => 'processed',
                'processed_at' => now(),
                'meta' => [
                    'action' => 'user_soft_deleted',
                ],
            ]);

            return;
        }

        $req->update(['status' => 'rejected', 'notes' => 'Unsupported type', 'processed_at' => now()]);
    }

    /**
     * @return array<string,mixed>
     */
    protected function buildExportPayload(User $user): array
    {
        $user->loadMissing('client');

        $clientId = (int) ($user->client_id ?? 0);
        $requests = $clientId
            ? ServiceRequest::query()->where('client_id', $clientId)->latest('id')->limit(500)->get()->toArray()
            : [];

        $invoices = $clientId
            ? Invoice::query()->where('client_id', $clientId)->latest('id')->limit(500)->get()->toArray()
            : [];

        $contracts = $clientId
            ? Contract::query()->where('client_id', $clientId)->latest('id')->limit(500)->get()->toArray()
            : [];

        return [
            'exported_at' => now()->toISOString(),
            'user' => $user->toArray(),
            'client' => $user->client?->toArray(),
            'requests' => $requests,
            'invoices' => $invoices,
            'contracts' => $contracts,
        ];
    }
}
