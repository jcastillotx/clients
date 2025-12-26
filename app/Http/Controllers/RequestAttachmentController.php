<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Request as ServiceRequest;
use App\Models\RequestAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequestAttachmentController extends Controller
{
    public function download(ServiceRequest $request, RequestAttachment $attachment): StreamedResponse
    {
        // Ensure attachment belongs to request
        if ($attachment->request_id !== $request->id) {
            abort(404);
        }

        $user = auth()->user();
        if ($user->isClient() && $request->client_id !== $user->client_id) {
            abort(403, 'You do not have access to this attachment.');
        }

        if (!Storage::disk('attachments')->exists($attachment->file_path)) {
            abort(404, 'Attachment file not found.');
        }

        ActivityLog::log(
            "Downloaded attachment: {$attachment->original_filename}",
            $request,
            ['attachment_id' => $attachment->id],
            'downloaded',
            'requests'
        );

        return Storage::disk('attachments')->download(
            $attachment->file_path,
            $attachment->original_filename
        );
    }
}

