<?php

namespace App\Services;

use App\Models\Language;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Single source of truth for supported locales and locale resolution.
 * Nothing else in the codebase should compare language codes directly.
 */
class LocaleService
{
    private const CACHE_KEY = 'languages.active';

    /** @var Collection<int, Language>|null */
    private ?Collection $memo = null;

    /**
     * @return Collection<int, Language>
     */
    public function activeLanguages(): Collection
    {
        // Cache raw attributes, not models: the cache store does not unserialize arbitrary classes.
        return $this->memo ??= Language::hydrate(Cache::remember(
            self::CACHE_KEY,
            config('bento.languages_cache_ttl'),
            fn () => Language::query()->active()->get()->map->getAttributes()->all(),
        ))->toBase();
    }

    /**
     * @return list<string>
     */
    public function supportedLocales(): array
    {
        $codes = $this->activeLanguages()->pluck('code')->all();

        return $codes === [] ? [config('bento.default_locale')] : $codes;
    }

    public function isSupported(?string $locale): bool
    {
        return $locale !== null && in_array($locale, $this->supportedLocales(), true);
    }

    public function defaultLocale(): string
    {
        return $this->activeLanguages()->firstWhere('is_default', true)?->code
            ?? config('bento.default_locale');
    }

    /**
     * Priority: explicit Accept-Language → user.preferred_language → default locale.
     */
    public function resolveForRequest(Request $request, ?User $user = null): string
    {
        return $this->fromAcceptLanguage($request->header('Accept-Language'))
            ?? ($this->isSupported($user?->preferred_language) ? $user->preferred_language : null)
            ?? $this->defaultLocale();
    }

    /**
     * Picks the best supported locale from an Accept-Language header.
     * "ja-JP,ja;q=0.9,en;q=0.8" → "ja". Wildcards and unsupported tags are ignored.
     */
    public function fromAcceptLanguage(?string $header): ?string
    {
        if ($header === null || trim($header) === '') {
            return null;
        }

        $candidates = [];
        foreach (explode(',', $header) as $index => $part) {
            $segments = array_map('trim', explode(';', $part));
            $tag = $segments[0];
            $quality = 1.0;
            foreach (array_slice($segments, 1) as $param) {
                if (str_starts_with($param, 'q=')) {
                    $quality = (float) substr($param, 2);
                }
            }
            if ($tag === '' || $tag === '*' || $quality <= 0) {
                continue;
            }
            $candidates[] = ['tag' => str_replace('_', '-', $tag), 'q' => $quality, 'i' => $index];
        }

        usort($candidates, fn ($a, $b) => [$b['q'], $a['i']] <=> [$a['q'], $b['i']]);

        $supported = [];
        foreach ($this->supportedLocales() as $code) {
            $supported[strtolower($code)] = $code;
        }

        foreach ($candidates as $candidate) {
            // Try the full tag, then progressively drop subtags: zh-Hans-CN → zh-Hans → zh.
            $subtags = explode('-', strtolower($candidate['tag']));
            while ($subtags !== []) {
                $key = implode('-', $subtags);
                if (isset($supported[$key])) {
                    return $supported[$key];
                }
                array_pop($subtags);
            }
        }

        return null;
    }

    public function flush(): void
    {
        $this->memo = null;
        Cache::forget(self::CACHE_KEY);
    }
}
