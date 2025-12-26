<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiTokenController extends Controller
{
    /**
     * Create a new personal access token for the authenticated user.
     *
     * Abilities:
     * - read
     * - write
     * - admin (staff/admin-only)
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string', 'in:read,write,admin'],
        ])->validate();

        $abilities = $data['abilities'] ?? ['read'];

        // Only staff can mint admin tokens
        if (in_array('admin', $abilities, true)) {
            abort_unless(!$user->isClient() && $user->can('access admin panel'), 403);
        }

        $token = $user->createToken($data['name'], $abilities);

        return response()->json([
            'token' => $token->plainTextToken,
            'abilities' => $abilities,
        ]);
    }
}

