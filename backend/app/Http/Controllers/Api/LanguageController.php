<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LanguageResource;
use App\Services\LocaleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class LanguageController extends Controller
{
    public function index(LocaleService $locales): JsonResponse
    {
        return ApiResponse::success(LanguageResource::collection($locales->activeLanguages()));
    }
}
