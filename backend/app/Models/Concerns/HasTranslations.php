<?php

namespace App\Models\Concerns;

use App\Services\LocaleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

/**
 * Locale-based translation lookup for models backed by a `<entity>_translations` table.
 *
 * Resolution order: requested locale → default locale (en) → any existing translation.
 * The using model must define a `translations()` HasMany relation.
 */
trait HasTranslations
{
    /**
     * Eager load only the translations needed to render the requested locale
     * (requested + default), so payload size does not grow with the number of languages.
     */
    public function scopeWithTranslations(Builder $query, ?string $locale = null): Builder
    {
        return $query->with(['translations' => fn ($q) => $q->whereIn('locale', self::localesToLoad($locale))]);
    }

    public function translation(?string $locale = null): ?Model
    {
        $locale ??= App::getLocale();
        $translations = $this->translations;

        return $translations->firstWhere('locale', $locale)
            ?? $translations->firstWhere('locale', app(LocaleService::class)->defaultLocale())
            ?? $translations->sortBy('id')->first();
    }

    public function translate(string $attribute, ?string $locale = null): ?string
    {
        return $this->translation($locale)?->getAttribute($attribute);
    }

    /**
     * All translations keyed by locale, e.g. for admin editing screens.
     *
     * @return array<string, array<string, mixed>>
     */
    public function translationsByLocale(): array
    {
        return $this->translations
            ->mapWithKeys(fn (Model $t) => [$t->getAttribute('locale') => $t->only(static::translatedAttributes())])
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function localesToLoad(?string $locale = null): array
    {
        return array_values(array_unique([
            $locale ?? App::getLocale(),
            app(LocaleService::class)->defaultLocale(),
        ]));
    }

    /**
     * @return list<string>
     */
    abstract public static function translatedAttributes(): array;
}
