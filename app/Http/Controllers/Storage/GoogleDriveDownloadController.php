<?php

namespace App\Http\Controllers\Storage;

use App\Http\Controllers\Controller;
use App\Models\StorageConnection;
use App\Services\Storage\GoogleDriveService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoogleDriveDownloadController extends Controller
{
    public function download(Request $request, int $connection): StreamedResponse
    {
        $user = $request->user();
        abort_if(!$user, 403);

        $fileId = (string) $request->query('file', '');
        abort_if($fileId === '', 422, 'Missing file id.');

        $conn = StorageConnection::query()->where('provider', 'google_drive')->findOrFail($connection);

        // Staff scoping.
        if ($user->hasRole('staff') && !$user->hasAnyRole(['super_admin', 'admin'])) {
            $allowed = $user->assignedClientIds();
            abort_if(!in_array((int) $conn->client_id, $allowed, true), 403);
        }
        // Client scoping.
        if ($user->isClient()) {
            abort_if((int) $conn->client_id !== (int) $user->client_id, 403);
        }

        /** @var GoogleDriveService $svc */
        $svc = app(GoogleDriveService::class)->useConnection($conn);

        $export = (string) $request->query('export', '');
        $result = $export !== ''
            ? $svc->downloadExport($fileId, $export)
            : $svc->downloadStream($fileId);

        $filename = (string) ($result['file_name'] ?? ($fileId . '.bin'));
        $mime = (string) ($result['mime_type'] ?? 'application/octet-stream');
        $stream = $result['stream'];

        return response()->streamDownload(function () use ($stream) {
            while (!feof($stream)) {
                echo fread($stream, 1024 * 1024);
                flush();
            }
            try { fclose($stream); } catch (\Throwable $e) {}
        }, $filename, [
            'Content-Type' => $mime,
        ]);
    }
}

