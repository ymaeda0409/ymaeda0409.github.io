<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LanguageRequest;
use App\Http\Resources\Admin\LanguageResource;
use App\Models\Language;
use App\Services\Admin\LanguageService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class LanguageController extends Controller
{
    public function __construct(private readonly LanguageService $languages) {}

    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Language::class);

        return ApiResponse::success(LanguageResource::collection(Language::query()->orderBy('sort_order')->orderBy('id')->get()));
    }

    public function store(LanguageRequest $request): JsonResponse
    {

        return ApiResponse::created(new LanguageResource($this->languages->create($request->validated())));
    }

    public function update(LanguageRequest $request, Language $language): JsonResponse
    {

        return ApiResponse::success(new LanguageResource($this->languages->update($language, $request->validated())));
    }
}
