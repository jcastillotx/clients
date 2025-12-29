<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Services\Documents\DocumentAccessService;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentAccessService $access) {}

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
        $this->authorizeView($document);

        $document->load(['client', 'request', 'uploader']);

        return view('documents.show', compact('document'));
    }

    /**
     * Download the document.
     */
    public function download(Document $document): StreamedResponse
    {
        $this->authorizeDownload($document);

        if (! Storage::disk('documents')->exists($document->file_path)) {
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
        $this->authorizeView($document);

        if (! Storage::disk('documents')->exists($document->file_path)) {
            abort(404, 'Document file not found.');
        }

        $file = Storage::disk('documents')->get($document->file_path);
        $mimeType = $document->mime_type ?? 'application/octet-stream';

        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="'.$document->original_filename.'"');
    }

    /**
     * Delete the document.
     */
    public function destroy(Document $document)
    {
        $this->authorizeDelete($document);

        // Delete the physical file
        if (Storage::disk('documents')->exists($document->file_path)) {
            Storage::disk('documents')->delete($document->file_path);
        }

        // Delete thumbnail if exists
        if ($document->thumbnail_path && Storage::disk('documents')->exists($document->thumbnail_path)) {
            Storage::disk('documents')->delete($document->thumbnail_path);
        }

        ActivityLog::log(
            "Deleted document: {$document->title}",
            $document,
            null,
            'deleted',
            'documents'
        );

        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document deleted successfully.');
    }

    /**
     * Authorize that the current user can access this document.
     */
    protected function authorizeClientAccess(Document $document): void
    {
        // kept for BC; superseded by granular permission checks below
        $this->authorizeView($document);
    }

    protected function authorizeView(Document $document): void
    {
        $user = auth()->user();
        abort_unless($user, 403);
        abort_unless($this->access->canView($user, $document), 403);
    }

    protected function authorizeDownload(Document $document): void
    {
        $user = auth()->user();
        abort_unless($user, 403);
        abort_unless($this->access->canDownload($user, $document), 403);
    }

    protected function authorizeDelete(Document $document): void
    {
        $user = auth()->user();
        abort_unless($user, 403);
        abort_unless($this->access->canDelete($user, $document), 403);
    }
}
