<?php

namespace App\Http\Controllers;

use App\Models\DataPrivacyRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PrivacyExportController extends Controller
{
    public function download(DataPrivacyRequest $privacyRequest)
    {
        $u = Auth::user();
        abort_unless($u && $u->isClient(), 403);
        abort_unless((int) $privacyRequest->user_id === (int) $u->id, 403);
        abort_unless($privacyRequest->type === 'export' && $privacyRequest->status === 'processed', 404);

        $meta = (array) ($privacyRequest->meta ?? []);
        $disk = (string) ($meta['disk'] ?? 'exports');
        $path = (string) ($meta['path'] ?? '');
        abort_unless($path !== '', 404);

        return Storage::disk($disk)->download($path, 'data_export_'.$privacyRequest->id.'.json');
    }
}
