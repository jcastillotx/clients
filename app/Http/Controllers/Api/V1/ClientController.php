<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);
        abort_unless(! $user->isClient() && $user->can('access admin panel'), 403);

        $data = Validator::make($request->all(), [
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:clients,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'zip_code' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:5'],
            'website' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive,pending,suspended'],
            'tier' => ['nullable', 'in:basic,standard,premium,enterprise'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $client = Client::create($data);

        return response()->json([
            'data' => new ClientResource($client),
        ], 201);
    }

    public function show(Request $request, Client $client): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($user->isClient()) {
            abort_unless((int) $user->client_id === (int) $client->id, 403);
        }

        return response()->json([
            'data' => new ClientResource($client),
        ]);
    }
}
