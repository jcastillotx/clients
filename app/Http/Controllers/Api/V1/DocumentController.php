<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DocumentResource;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = Validator::make($request->all(), [
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'request_id' => ['nullable', 'integer', 'exists:requests,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:80'],
            'file' => ['required', 'file', 'max:51200'],
        ])->validate();

        if ($user->isClient()) {
            $data['client_id'] = $user->client_id;
        } else {
            $data['client_id'] = $data['client_id'] ?? null;
            abort_unless($data['client_id'], 422, 'client_id is required for staff uploads.');
        }

        $file = $request->file('file');
        abort_unless($file, 422);

        $storedPath = $file->store('uploads/' . now()->format('Y/m'), 'documents');
        $original = $file->getClientOriginalName();
        $mime = $file->getClientMimeType();
        $size = $file->getSize();

        $title = $data['title'] ?? pathinfo($original, PATHINFO_FILENAME);
        $filename = Str::slug((string) $title) . '.' . strtolower(pathinfo($original, PATHINFO_EXTENSION) ?: 'bin');

        // Keep the original filename but store the "filename" field for internal use (UI)
        $doc = Document::create([
            'client_id' => $data['client_id'],
            'request_id' => $data['request_id'] ?? null,
            'uploaded_by' => $user->id,
            'title' => $title,
            'description' => $data['description'] ?? null,
            'filename' => $filename,
            'original_filename' => $original,
            'file_path' => $storedPath,
            'mime_type' => $mime,
            'file_size' => (int) $size,
            'category' => $data['category'] ?? 'other',
            'is_public' => false,
            'status' => 'draft',
            'current_version' => 1,
        ]);

        return response()->json([
            'data' => new DocumentResource($doc),
        ], 201);
    }
}

