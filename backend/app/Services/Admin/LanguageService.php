<?php

namespace App\Services\Admin;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Models\Language;
use App\Services\AuditLogger;
use App\Services\LocaleService;
use Illuminate\Support\Facades\DB;

class LanguageService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly LocaleService $locales,
    ) {}

    public function create(array $data): Language
    {
        return $this->persist(new Language, $data, 'language.created');
    }

    public function update(Language $language, array $data): Language
    {
        return $this->persist($language, $data, 'language.updated');
    }

    private function persist(Language $language, array $data, string $action): Language
    {
        $language = DB::transaction(function () use ($language, $data, $action) {
            $language->fill($data);

            // The default language is the global fallback: it must stay active and unique.
            if ($language->is_default && ! $language->is_active) {
                throw ApiException::of(ErrorCode::CONFLICT);
            }
            if (! $language->is_default && $language->exists && $language->getOriginal('is_default')) {
                throw ApiException::of(ErrorCode::CONFLICT);
            }

            $this->audit->log($action, $language, $language->exists ? array_intersect_key($language->getOriginal(), $language->getDirty()) : null, $language->getDirty());
            $language->save();

            if ($language->is_default) {
                Language::query()->whereKeyNot($language->id)->update(['is_default' => false]);
            }

            return $language;
        });

        $this->locales->flush();

        return $language;
    }
}
