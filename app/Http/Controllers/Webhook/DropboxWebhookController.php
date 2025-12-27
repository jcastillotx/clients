<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\SyncDropboxChanges;
use App\Models\StorageConnection;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DropboxWebhookController extends Controller
{
    /**
     * Dropbox webhook verification: respond with the challenge.
     */
    public function verify(Request $request): Response
    {
        return response((string) $request->query('challenge', ''), 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Dropbox webhook notification.
     *
     * Body format: { "list_folder": { "accounts": ["dbid:..."] } }
     */
    public function handle(Request $request): Response
    {
        $secret = (string) env('DROPBOX_APP_SECRET', '');
        if ($secret === '') {
            return response('Dropbox webhook not configured.', 500);
        }

        $signature = (string) $request->header('X-Dropbox-Signature', '');
        $body = $request->getContent();

        $expected = hash_hmac('sha256', $body, $secret);
        if (! hash_equals($expected, $signature)) {
            return response('Invalid signature.', 403);
        }

        $accounts = (array) data_get($request->json()->all(), 'list_folder.accounts', []);
        if (empty($accounts)) {
            return response('OK', 200);
        }

        // Credentials are stored encrypted; for MVP, decrypt by iterating connections.
        $connections = StorageConnection::query()
            ->where('provider', 'dropbox')
            ->where('status', 'connected')
            ->get();

        foreach ($connections as $conn) {
            $accountId = (string) data_get($conn->credentials, 'account_id', '');
            if ($accountId !== '' && in_array($accountId, $accounts, true)) {
                SyncDropboxChanges::dispatch($conn->id)->onQueue('default');
            }
        }

        return response('OK', 200);
    }
}
