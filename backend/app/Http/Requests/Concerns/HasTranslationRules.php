<?php

namespace App\Http\Requests\Concerns;

use App\Rules\SupportedLocaleKeys;
use App\Services\LocaleService;

trait HasTranslationRules
{
    /**
     * Rules for a `{ "<locale>": { name, description? } }` payload at $prefix.
     * The default locale's name is mandatory on create; other locales are optional and fall back.
     *
     * @param  array<string, int>  $attributes  translated attribute => max length
     * @param  string|null  $requiredAttribute  NOT NULL column of the translation table, if any
     */
    protected function translationRules(string $prefix, array $attributes, bool $creating, ?string $requiredAttribute = 'name'): array
    {
        $default = app(LocaleService::class)->defaultLocale();
        $rules = [
            $prefix => [$creating ? 'required' : 'sometimes', 'array', new SupportedLocaleKeys],
            "{$prefix}.*" => ['array'],
        ];

        foreach ($attributes as $attribute => $max) {
            $rules["{$prefix}.*.{$attribute}"] = ['nullable', 'string', "max:{$max}"];
        }

        if ($requiredAttribute === null) {
            return $rules;
        }

        $others = array_diff(array_keys($attributes), [$requiredAttribute]);
        if ($others !== []) {
            $rules["{$prefix}.*.{$requiredAttribute}"][] = 'required_with:'.implode(',', array_map(fn ($a) => "{$prefix}.*.{$a}", $others));
        }
        $rules["{$prefix}.{$default}.{$requiredAttribute}"] = [
            $creating ? 'required' : 'required_with:'."{$prefix}.{$default}",
            'string',
            'max:'.$attributes[$requiredAttribute],
        ];

        return $rules;
    }
}
