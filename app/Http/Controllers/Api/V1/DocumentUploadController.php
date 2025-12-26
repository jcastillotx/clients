<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentUploadController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user?->currentAccessToken()?->can('admin') ?? false;

        $data = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'request_id' => ['nullable', 'integer', 'exists:requests,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:50'],
            'is_public' => ['nullable', 'boolean'],
            'file' => ['required', 'file', 'max:51200'], // 50MB
        ]);

        if (!$isAdmin && $user?->client_id) {
            abort_unless((int) $data['client_id'] === (int) $user->client_id, 403);
        }

        $file = $request->file('file');
        $original = $file->getClientOriginalName();
        $filename = uniqid('doc_', true) . '_' . preg_replace('/[^A-Za-z0-9._-]+/', '_', $original);

        $path = Storage::disk('documents')->putFileAs(
            date('Y/m'),
            $file,
            $filename
        );

        $doc = Document::create([
            'client_id' => (int) $data['client_id'],
            'request_id' => $data['request_id'] ?? null,
            'uploaded_by' => $request->user()?->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'filename' => $filename,
            'original_filename' => $original,
            'file_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => (int) $file->getSize(),
            'category' => $data['category'] ?? 'other',
            'is_public' => (bool) ($data['is_public'] ?? false),
        ]);

        return response()->json(['data' => $doc], 201);
    }
}

