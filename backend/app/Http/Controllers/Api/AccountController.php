<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateAccountRequest;
use App\Http\Requests\Account\UpdateLanguageRequest;
use App\Http\Resources\UserResource;
use App\Services\AccountService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(private readonly AccountService $accounts) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()));
    }

    public function update(UpdateAccountRequest $request): JsonResponse
    {
        $user = $this->accounts->update($request->user(), $request->validated());

        return ApiResponse::success(new UserResource($user));
    }

    public function updateLanguage(UpdateLanguageRequest $request): JsonResponse
    {
        $user = $this->accounts->changeLanguage($request->user(), $request->validated('language'));

        return ApiResponse::success(new UserResource($user));
    }
}
