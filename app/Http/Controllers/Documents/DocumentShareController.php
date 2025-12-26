<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\DocumentShare;
use App\Models\StorageFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentShareController extends Controller
{
    public function download(string $token): StreamedResponse
    {
        $share = DocumentShare::query()->where('token', $token)->firstOrFail();
        abort_if($share->isExpired(), 410, 'This link has expired.');

        $source = $share->source;
        abort_unless($source, 404);

        // increment download count
        $share->increment('downloads');

        if ($source instanceof \App\Models\Document) {
            return Storage::disk('documents')->download($source->file_path, $source->original_filename);
        }

        if ($source instanceof StorageFile) {
            $source->loadMissing('connection');
            $disk = $source->connection?->disk;
            abort_unless($disk, 404);
            return Storage::disk($disk)->download($source->path, $source->filename);
        }

        abort(404);
    }
}

