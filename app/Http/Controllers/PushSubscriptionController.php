<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = $request->validate([
            'endpoint' => ['required', 'string'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        $sub = PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id' => $user->id,
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => 'aesgcm',
                'expires_at' => null,
            ]
        );

        return response()->json(['ok' => true, 'id' => $sub->id]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = $request->validate([
            'endpoint' => ['required', 'string'],
        ]);

        PushSubscription::query()
            ->where('endpoint', $data['endpoint'])
            ->where('user_id', $user->id)
            ->delete();

        return response()->json(['ok' => true]);
    }
}
