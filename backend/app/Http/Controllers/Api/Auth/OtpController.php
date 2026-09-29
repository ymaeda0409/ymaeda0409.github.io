<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use App\Services\Auth\OtpService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class OtpController extends Controller
{
    public function send(SendOtpRequest $request, OtpService $otp): JsonResponse
    {
        $phone = $request->validated('phone');
        $otp->send($phone, $request->ip());

        return ApiResponse::success([
            'phone' => $phone,
            'expires_in' => (int) config('bento.otp.ttl_seconds'),
            'resend_in' => (int) config('bento.otp.resend_seconds'),
        ]);
    }

    public function verify(VerifyOtpRequest $request, AuthService $auth): JsonResponse
    {
        $result = $auth->loginWithOtp(
            $request->validated('phone'),
            $request->validated('code'),
            $request->validated('device_name') ?? 'mobile',
            $request->validated('preferred_language'),
        );

        return ApiResponse::success([
            'token' => $result['token'],
            'is_new_user' => $result['is_new_user'],
            'user' => new UserResource($result['user']),
        ]);
    }
}
