<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ErrorCode;
use App\Enums\Permission;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Services\Admin\TranslationManagementService;
use App\Services\AuditLogger;
use App\Services\LocaleService;
use App\Services\TranslationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * GET /admin/translations                      coverage per type × language
 * GET /admin/translations/{type}?locale=ny&missing=1
 * PUT /admin/translations/{type}/{id}         { locale, values: { attribute: text } }
 */
class TranslationController extends Controller
{
    public function __construct(
        private readonly TranslationManagementService $manager,
        private readonly LocaleService $locales,
    ) {}

    public function summary(Request $request): JsonResponse
    {
        $this->requirePermission($request, Permission::TRANSLATIONS_MANAGE);

        return ApiResponse::success([
            'default_locale' => $this->locales->defaultLocale(),
            'locales' => $this->manager->activeCodes(),
            'languages' => Language::query()->orderBy('sort_order')->orderBy('id')
                ->get(['code', 'name', 'native_name', 'is_active', 'is_default']),
            'types' => collect(TranslationManagementService::TYPES)->map(fn ($t) => ['attributes' => array_keys($t['max'])])->all(),
            'coverage' => $this->manager->summary($request->user()),
        ]);
    }

    public function index(Request $request, string $type): JsonResponse
    {
        $this->requirePermission($request, Permission::TRANSLATIONS_MANAGE);
        $this->assertType($type);
        $data = $request->validate([
            'locale' => ['required', Rule::in($this->manager->activeCodes())],
            'missing' => ['sometimes', 'boolean'],
        ]);

        $page = $this->manager->items($type, $request->user(), $data['locale'], $request->boolean('missing'), $this->perPage($request, 50));

        return ApiResponse::success($page->items(), meta: ['pagination' => [
            'current_page' => $page->currentPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
            'last_page' => $page->lastPage(),
        ]]);
    }

    public function update(Request $request, string $type, int $id, TranslationService $translations, AuditLogger $audit): JsonResponse
    {
        $this->requirePermission($request, Permission::TRANSLATIONS_MANAGE);
        $this->assertType($type);
        $definition = TranslationManagementService::TYPES[$type];

        $locale = $request->validate(['locale' => ['required', Rule::in($this->manager->activeCodes())]])['locale'];
        $rules = ['values' => ['required', 'array:'.implode(',', array_keys($definition['max']))]];
        foreach ($definition['max'] as $attribute => $max) {
            $rules["values.{$attribute}"] = ['nullable', 'string', "max:{$max}"];
        }
        // The default language is the fallback for everyone, so its main text cannot be removed.
        if ($definition['required'] && $locale === $this->locales->defaultLocale()) {
            $rules["values.{$definition['primary']}"] = ['required', 'string', "max:{$definition['max'][$definition['primary']]}"];
        }
        $values = $request->validate($rules)['values'];

        $model = $this->manager->find($type, $request->user(), $id) ?? throw ApiException::of(ErrorCode::RESOURCE_NOT_FOUND);

        DB::transaction(function () use ($model, $locale, $values, $translations, $audit, $type) {
            $before = $model->translationsByLocale()[$locale] ?? null;
            // Keep attributes that were not sent (e.g. only the name is being translated).
            $translations->sync($model, [$locale => $values + ($before ?? [])]);
            $audit->log('translation.updated', $model, [$type => $locale, 'values' => $before], $values);
        });

        return ApiResponse::success([
            'id' => $model->getKey(),
            'translations' => $model->load('translations')->translationsByLocale(),
        ]);
    }

    private function assertType(string $type): void
    {
        if (! array_key_exists($type, TranslationManagementService::TYPES)) {
            throw ApiException::of(ErrorCode::RESOURCE_NOT_FOUND);
        }
    }
}
