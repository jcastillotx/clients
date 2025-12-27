<?php

namespace App\Http\Controllers\Storage;

use App\Http\Controllers\Controller;
use App\Services\Storage\DropboxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class DropboxOAuthController extends Controller
{
    public function authorize(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user, 403);

        $clientId = (int) ($request->integer('client_id') ?: ($user->client_id ?? 0));
        abort_if($clientId <= 0, 422, 'Missing client_id.');

        // Staff scoping: staff can only connect for assigned clients.
        if ($user->hasRole('staff') && ! $user->hasAnyRole(['super_admin', 'admin'])) {
            $allowed = $user->assignedClientIds();
            abort_if(! in_array($clientId, $allowed, true), 403, 'You do not have access to this client.');
        }

        // Clients can only connect for their own client.
        if ($user->isClient()) {
            abort_if((int) $user->client_id !== $clientId, 403, 'You do not have access to this client.');
        }

        $state = Str::random(40);

        $payload = [
            'client_id' => $clientId,
            'is_primary' => (bool) $request->boolean('is_primary', true),
            'folder_path' => (string) $request->string('folder_path', ''),
            'redirect_to' => (string) $request->string('redirect_to', route('admin.storage.dropbox.connect')),
        ];

        Cache::put("dropbox_oauth_state:{$state}", $payload, now()->addMinutes(10));

        /** @var DropboxService $svc */
        $svc = app(DropboxService::class);
        $url = $svc->authorizationUrl($state);

        return redirect()->away($url);
    }

    public function callback(Request $request): Response
    {
        $user = $request->user();
        abort_if(! $user, 403);

        $code = (string) $request->string('code', '');
        $state = (string) $request->string('state', '');

        abort_if($code === '' || $state === '', 422, 'Missing OAuth code/state.');

        $payload = Cache::pull("dropbox_oauth_state:{$state}");
        abort_if(! is_array($payload), 419, 'OAuth state expired. Please try again.');

        $clientId = (int) ($payload['client_id'] ?? 0);
        abort_if($clientId <= 0, 422, 'Invalid OAuth state payload.');

        // Repeat scoping checks.
        if ($user->hasRole('staff') && ! $user->hasAnyRole(['super_admin', 'admin'])) {
            $allowed = $user->assignedClientIds();
            abort_if(! in_array($clientId, $allowed, true), 403, 'You do not have access to this client.');
        }
        if ($user->isClient()) {
            abort_if((int) $user->client_id !== $clientId, 403, 'You do not have access to this client.');
        }

        /** @var DropboxService $svc */
        $svc = app(DropboxService::class);

        $svc->connect([
            'client_id' => $clientId,
            'authorization_code' => $code,
            'folder_path' => (string) ($payload['folder_path'] ?? ''),
            'is_primary' => (bool) ($payload['is_primary'] ?? true),
        ]);

        // If this is a popup, notify opener + close. Otherwise redirect.
        $redirectTo = (string) ($payload['redirect_to'] ?? route('admin.storage.dropbox.connect'));

        return response()->view('storage.dropbox.oauth-close', [
            'redirectTo' => $redirectTo,
        ]);
    }
}
