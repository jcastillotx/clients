<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\StorageFile;
use App\Services\Documents\DocumentAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DocumentViewerController extends Controller
{
    public function __construct(private readonly DocumentAccessService $access)
    {
    }

    public function openDocument(Document $document, string $viewer = 'office'): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);
        abort_unless($this->access->canDownload($user, $document), 403);

        $shareUrl = $this->createShortShare(Document::class, $document->id, $user->id);
        return redirect()->away($this->viewerUrl($shareUrl, $viewer));
    }

    public function openStorageFile(StorageFile $storageFile, string $viewer = 'office'): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $storageFile->loadMissing('connection');
        if ($user->isClient() && (int) $user->client_id !== (int) $storageFile->connection->client_id) {
            abort(403);
        }

        $shareUrl = $this->createShortShare(StorageFile::class, $storageFile->id, $user->id);
        return redirect()->away($this->viewerUrl($shareUrl, $viewer));
    }

    protected function createShortShare(string $sourceType, int $sourceId, int $userId): string
    {
        $share = DocumentShare::create([
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'token' => Str::random(40),
            'expires_at' => now()->addMinutes(15),
            'max_downloads' => 10,
            'permissions' => ['download' => true],
            'created_by' => $userId,
        ]);

        return route('documents.share.download', $share->token);
    }

    protected function viewerUrl(string $publicUrl, string $viewer): string
    {
        $encoded = urlencode($publicUrl);

        return match ($viewer) {
            'google' => "https://docs.google.com/gview?embedded=1&url={$encoded}",
            default => "https://view.officeapps.live.com/op/view.aspx?src={$encoded}",
        };
    }
}

