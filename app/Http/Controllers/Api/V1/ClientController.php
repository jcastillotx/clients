<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function store(Request $request)
    {
        abort_unless($request->user()?->currentAccessToken()?->can('admin'), 403);

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:clients,email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:2'],
            'website' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'pending', 'suspended'])],
            'tier' => ['nullable', Rule::in(['basic', 'standard', 'premium', 'enterprise'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $client = Client::create($data);

        return response()->json([
            'data' => $client,
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();
        $isAdmin = $user?->currentAccessToken()?->can('admin') ?? false;
        if (!$isAdmin && $user?->client_id) {
            abort_unless((int) $id === (int) $user->client_id, 403);
        }

        $client = Client::query()->findOrFail($id);

        return response()->json([
            'data' => $client,
        ]);
    }
}

