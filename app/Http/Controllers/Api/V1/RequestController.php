<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Request as ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RequestController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user?->currentAccessToken()?->can('admin') ?? false;

        $data = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'type' => ['nullable', 'string', 'max:50'],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'status' => ['nullable', Rule::in(['pending', 'in_review', 'approved', 'in_progress', 'on_hold', 'completed', 'cancelled'])],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $data['created_by'] = $request->user()?->id;
        $data['type'] = $data['type'] ?? 'support';
        $data['priority'] = $data['priority'] ?? 'medium';
        $data['status'] = $data['status'] ?? 'pending';

        if (!$isAdmin && $user?->client_id) {
            abort_unless((int) $data['client_id'] === (int) $user->client_id, 403);
        }

        $req = ServiceRequest::create($data);

        return response()->json(['data' => $req], 201);
    }

    public function show(string $id)
    {
        $req = ServiceRequest::query()->with(['client', 'creator', 'assignedTo'])->findOrFail($id);
        $user = request()->user();
        $isAdmin = $user?->currentAccessToken()?->can('admin') ?? false;
        if (!$isAdmin && $user?->client_id) {
            abort_unless((int) $req->client_id === (int) $user->client_id, 403);
        }
        return response()->json(['data' => $req]);
    }

    public function updateStatus(string $id, Request $request)
    {
        $req = ServiceRequest::query()->findOrFail($id);
        $user = $request->user();
        $isAdmin = $user?->currentAccessToken()?->can('admin') ?? false;
        if (!$isAdmin && $user?->client_id) {
            abort_unless((int) $req->client_id === (int) $user->client_id, 403);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'in_review', 'approved', 'in_progress', 'on_hold', 'completed', 'cancelled'])],
        ]);

        $req->update(['status' => $data['status']]);

        return response()->json(['data' => $req]);
    }
}

