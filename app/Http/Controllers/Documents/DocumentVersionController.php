<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentVersionController extends Controller
{
    public function download(DocumentVersion $documentVersion): StreamedResponse
    {
        $documentVersion->loadMissing('document');
        $doc = $documentVersion->document;
        abort_unless($doc, 404);

        $user = auth()->user();
        abort_unless($user, 403);
        if ($user->isClient() && (int) $user->client_id !== (int) $doc->client_id) {
            abort(403);
        }

        abort_unless(Storage::disk($documentVersion->disk)->exists($documentVersion->file_path), 404);

        return Storage::disk($documentVersion->disk)->download(
            $documentVersion->file_path,
            $documentVersion->original_filename
        );
    }
}

