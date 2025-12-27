<?php

namespace App\Http\Controllers\Storage;

use App\Http\Controllers\Controller;
use App\Services\Storage\GoogleDriveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class GoogleDriveOAuthController extends Controller
{
    public function authorize(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user, 403);

        $clientId = (int) ($request->integer('client_id') ?: ($user->client_id ?? 0));
        abort_if($clientId <= 0, 422, 'Missing client_id.');

        // Staff scoping.
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
            'folder_id' => (string) $request->string('folder_id', ''),
            'sync_mode' => (string) $request->string('sync_mode', 'bidirectional'),
            'redirect_to' => (string) $request->string('redirect_to', route('admin.storage.google-drive.connect')),
        ];

        Cache::put("gdrive_oauth_state:{$state}", $payload, now()->addMinutes(10));

        /** @var GoogleDriveService $svc */
        $svc = app(GoogleDriveService::class);
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

        $payload = Cache::pull("gdrive_oauth_state:{$state}");
        abort_if(! is_array($payload), 419, 'OAuth state expired. Please try again.');

        $clientId = (int) ($payload['client_id'] ?? 0);
        abort_if($clientId <= 0, 422, 'Invalid OAuth state payload.');

        // Repeat scoping.
        if ($user->hasRole('staff') && ! $user->hasAnyRole(['super_admin', 'admin'])) {
            $allowed = $user->assignedClientIds();
            abort_if(! in_array($clientId, $allowed, true), 403, 'You do not have access to this client.');
        }
        if ($user->isClient()) {
            abort_if((int) $user->client_id !== $clientId, 403, 'You do not have access to this client.');
        }

        /** @var GoogleDriveService $svc */
        $svc = app(GoogleDriveService::class);

        $svc->connect([
            'client_id' => $clientId,
            'authorization_code' => $code,
            'folder_id' => (string) ($payload['folder_id'] ?? ''),
            'sync_mode' => (string) ($payload['sync_mode'] ?? 'bidirectional'),
            'is_primary' => (bool) ($payload['is_primary'] ?? true),
        ]);

        $redirectTo = (string) ($payload['redirect_to'] ?? route('admin.storage.google-drive.connect'));

        return response()->view('storage.google-drive.oauth-close', [
            'redirectTo' => $redirectTo,
        ]);
    }
}
