<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\ActivityLog;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /**
     * Display a listing of the documents.
     */
    public function index(): View
    {
        return view('documents.index');
    }

    /**
     * Display the specified document.
     */
    public function show(Document $document): View
    {
        $this->authorizeClientAccess($document);

        $document->load(['client', 'request', 'uploader']);

        return view('documents.show', compact('document'));
    }

    /**
     * Download the document.
     */
    public function download(Document $document): StreamedResponse
    {
        $this->authorizeClientAccess($document);

        if (!Storage::disk('documents')->exists($document->file_path)) {
            abort(404, 'Document file not found.');
        }

        ActivityLog::log(
            "Downloaded document: {$document->title}",
            $document,
            null,
            'downloaded',
            'documents'
        );

        return Storage::disk('documents')->download(
            $document->file_path,
            $document->original_filename
        );
    }

    /**
     * View the document in browser (for PDFs and images).
     */
    public function view(Document $document)
    {
        $this->authorizeClientAccess($document);

        if (!Storage::disk('documents')->exists($document->file_path)) {
            abort(404, 'Document file not found.');
        }

        $file = Storage::disk('documents')->get($document->file_path);
        $mimeType = $document->mime_type ?? 'application/octet-stream';

        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="' . $document->original_filename . '"');
    }

    /**
     * Authorize that the current user can access this document.
     */
    protected function authorizeClientAccess(Document $document): void
    {
        $user = auth()->user();

        if ($user->isClient() && $document->client_id !== $user->client_id) {
            abort(403, 'You do not have access to this document.');
        }
    }
}
