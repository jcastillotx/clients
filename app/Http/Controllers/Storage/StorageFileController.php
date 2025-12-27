<?php

namespace App\Http\Controllers\Storage;

use App\Http\Controllers\Controller;
use App\Models\StorageFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageFileController extends Controller
{
    public function download(StorageFile $storageFile): StreamedResponse
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $storageFile->loadMissing('connection');
        $connection = $storageFile->connection;
        abort_unless($connection, 404);

        // Client-scoped authorization (admins/staff may not have client_id)
        if ($user->isClient() && (int) $user->client_id !== (int) $connection->client_id) {
            abort(403);
        }

        $diskName = $connection->disk;
        if (! $diskName) {
            abort(404, 'Storage disk is not configured for this connection.');
        }

        return Storage::disk($diskName)->download($storageFile->path, $storageFile->filename);
    }
}
