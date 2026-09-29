<?php

namespace App\Http\Middleware;

use App\Services\LocaleService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the request locale: explicit Accept-Language → user.preferred_language → default.
 */
class SetLocale
{
    public function __construct(private readonly LocaleService $locales) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->locales->resolveForRequest($request, $request->user('sanctum'));
        App::setLocale($locale);

        $response = $next($request);
        $response->headers->set('Content-Language', $locale);

        return $response;
    }
}
