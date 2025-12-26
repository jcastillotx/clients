<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\RequestResource;
use App\Models\Request as ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = Validator::make($request->all(), [
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'type' => ['nullable', 'string', 'max:50'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'due_date' => ['nullable', 'date'],
        ])->validate();

        if ($user->isClient()) {
            $data['client_id'] = $user->client_id;
        } else {
            $data['client_id'] = $data['client_id'] ?? null;
            abort_unless($data['client_id'], 422, 'client_id is required for staff requests.');
        }

        $sr = ServiceRequest::create([
            'client_id' => $data['client_id'],
            'created_by' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'type' => $data['type'] ?? 'support',
            'priority' => $data['priority'] ?? 'medium',
            'due_date' => $data['due_date'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'data' => new RequestResource($sr->load(['client'])),
        ], 201);
    }

    public function show(Request $request, ServiceRequest $requestModel): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($user->isClient()) {
            abort_unless((int) $requestModel->client_id === (int) $user->client_id, 403);
        }

        return response()->json([
            'data' => new RequestResource($requestModel->load(['client'])),
        ]);
    }

    public function updateStatus(Request $request, ServiceRequest $requestModel): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($user->isClient()) {
            abort_unless((int) $requestModel->client_id === (int) $user->client_id, 403);
        }

        $data = Validator::make($request->all(), [
            'status' => ['required', 'in:pending,in_review,approved,in_progress,on_hold,completed,cancelled'],
        ])->validate();

        $status = $data['status'];

        $updates = ['status' => $status];
        if ($status === 'completed' && !$requestModel->completed_at) {
            $updates['completed_at'] = now();
        }

        if ($status === 'in_progress' && !$requestModel->started_at) {
            $updates['started_at'] = now();
        }

        $requestModel->update($updates);

        return response()->json([
            'data' => new RequestResource($requestModel->fresh()->load(['client'])),
        ]);
    }
}

