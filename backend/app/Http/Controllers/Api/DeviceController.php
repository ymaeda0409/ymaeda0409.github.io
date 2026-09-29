<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * FCM registration tokens of the signed-in user's devices (customer or rider app).
 */
class DeviceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['required', Rule::in(['android', 'ios', 'web'])],
            'app' => ['required', Rule::in(['customer', 'driver'])],
        ]);

        // A phone that changes account moves its token to the new user.
        DeviceToken::updateOrCreate(['token' => $data['token']], [
            'user_id' => $request->user()->id,
            'platform' => $data['platform'],
            'app' => $data['app'],
            'last_seen_at' => now(),
        ]);

        return ApiResponse::success();
    }

    public function destroy(Request $request): JsonResponse
    {
        $token = $request->validate(['token' => ['required', 'string', 'max:512']])['token'];
        $request->user()->deviceTokens()->where('token', $token)->delete();

        return ApiResponse::success();
    }
}
