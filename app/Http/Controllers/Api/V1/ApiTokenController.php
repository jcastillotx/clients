<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['string', 'max:50'],
        ]);

        $user = isset($data['user_id'])
            ? User::query()->findOrFail((int) $data['user_id'])
            : $request->user();

        $abilities = array_values(array_unique($data['abilities']));
        $token = $user->createToken($data['name'], $abilities);

        return response()->json([
            'token' => $token->plainTextToken,
            'abilities' => $abilities,
            'user_id' => $user->id,
        ], 201);
    }

    public function destroy(Request $request, int $tokenId)
    {
        $token = PersonalAccessToken::query()->findOrFail($tokenId);

        // Admins can revoke any token; otherwise only revoke own.
        if ((int) $token->tokenable_id !== (int) $request->user()->id) {
            // Require admin role for cross-user revocation.
            abort_unless($request->user()->hasAnyRole(['super_admin', 'admin']), 403);
        }

        $token->delete();

        return response()->json(['deleted' => true]);
    }
}

