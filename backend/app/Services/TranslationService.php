<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * Writes `<entity>_translations` rows from a `{ "<locale>": { attr: value } }` payload.
 */
class TranslationService
{
    /**
     * @param  array<string, array<string, mixed>>  $translations
     */
    public function sync(Model $model, array $translations): void
    {
        $attributes = $model::translatedAttributes();

        foreach ($translations as $locale => $values) {
            $values = array_intersect_key((array) $values, array_flip($attributes));
            $isEmpty = collect($values)->every(fn ($v) => $v === null || $v === '');

            if ($isEmpty) {
                $model->translations()->where('locale', $locale)->delete();

                continue;
            }

            $model->translations()->updateOrCreate(['locale' => $locale], $values);
        }

        $model->unsetRelation('translations');
    }
}
