<?php

namespace App\Services\Admin;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Models\ProductCategory;
use App\Services\AuditLogger;
use App\Services\TranslationService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly TranslationService $translations,
    ) {}

    public function create(int $organizationId, array $data): ProductCategory
    {
        return DB::transaction(function () use ($organizationId, $data) {
            $category = ProductCategory::create([
                ...Arr::except($data, ['translations']),
                'organization_id' => $organizationId,
            ]);
            $this->translations->sync($category, $data['translations']);
            $this->audit->log('category.created', $category, null, $data);

            return $category;
        });
    }

    public function update(ProductCategory $category, array $data): ProductCategory
    {
        return DB::transaction(function () use ($category, $data) {
            $before = ['translations' => $category->translationsByLocale()];
            $category->fill(Arr::except($data, ['translations']));
            $before += array_intersect_key($category->getOriginal(), $category->getDirty());
            $category->save();

            if (array_key_exists('translations', $data)) {
                $this->translations->sync($category, $data['translations']);
            }

            $this->audit->log('category.updated', $category, $before, $data);

            return $category;
        });
    }

    public function delete(ProductCategory $category): void
    {
        if ($category->products()->exists()) {
            throw ApiException::of(ErrorCode::CONFLICT);
        }

        DB::transaction(function () use ($category) {
            $this->audit->log('category.deleted', $category, $category->getAttributes());
            $category->delete();
        });
    }
}
