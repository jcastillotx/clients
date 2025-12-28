<?php

namespace App\Services\Entitlements;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Arr;

class PortalEntitlementService
{
    /**
     * Compute permission names granted by enabled client features.
     *
     * @return array<int, string>
     */
    public function permissionsForClient(Client $client): array
    {
        $featurePermissions = (array) config('entitlements.feature_permissions', []);

        $features = $client->getAvailableFeatures()->values()->all();
        $perms = [];

        foreach ($features as $feature) {
            $perms = array_merge($perms, Arr::wrap($featurePermissions[$feature] ?? []));
        }

        $perms = array_values(array_unique(array_filter($perms, fn ($p) => is_string($p) && $p !== '')));

        sort($perms);

        return $perms;
    }

    /**
     * Sync a single portal user: manual perms + entitlements.
     */
    public function syncUser(User $user): void
    {
        if (! $user->client_id) {
            return;
        }

        $client = $user->client;
        if (! $client) {
            return;
        }

        $manual = array_values(array_unique((array) ($user->manual_permissions ?? [])));
        $entitled = $this->permissionsForClient($client);

        $effective = array_values(array_unique(array_merge($manual, $entitled)));

        $user->syncPermissions($effective);
    }

    /**
     * Sync all portal users for a client.
     */
    public function syncClientUsers(Client $client): void
    {
        $entitled = $this->permissionsForClient($client);

        User::query()
            ->where('client_id', $client->id)
            ->get()
            ->each(function (User $u) use ($entitled) {
                $manual = array_values(array_unique((array) ($u->manual_permissions ?? [])));
                $effective = array_values(array_unique(array_merge($manual, $entitled)));
                $u->syncPermissions($effective);
            });
    }
}

