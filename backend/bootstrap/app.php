<?php

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SetLocale;
use App\Services\LocaleService;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [SetLocale::class]);
        $middleware->alias(['role' => EnsureRole::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport([ApiException::class]);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Every API error uses the unified envelope with a machine-readable code.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            // Errors raised before routing (e.g. unknown URL) never reach the SetLocale middleware.
            App::setLocale(app(LocaleService::class)->resolveForRequest($request, $request->user('sanctum')));

            return match (true) {
                $e instanceof HttpResponseException => null,
                $e instanceof ApiException => ApiResponse::error($e->errorCode, $e->fields, $e->replace),
                $e instanceof ValidationException => ApiResponse::error(ErrorCode::VALIDATION_FAILED, $e->errors()),
                $e instanceof AuthenticationException => ApiResponse::error(ErrorCode::UNAUTHENTICATED),
                $e instanceof HttpExceptionInterface => ApiResponse::error(
                    match ($e->getStatusCode()) {
                        401 => ErrorCode::UNAUTHENTICATED,
                        403 => ErrorCode::FORBIDDEN,
                        404 => $e->getPrevious() instanceof ModelNotFoundException
                            || $e->getPrevious() instanceof AuthorizationException
                                ? ErrorCode::RESOURCE_NOT_FOUND
                                : ErrorCode::ROUTE_NOT_FOUND,
                        405 => ErrorCode::METHOD_NOT_ALLOWED,
                        429 => ErrorCode::TOO_MANY_REQUESTS,
                        default => $e->getStatusCode() >= 500 ? ErrorCode::SERVER_ERROR : ErrorCode::BAD_REQUEST,
                    },
                    headers: $e->getHeaders(),
                ),
                default => config('app.debug') ? null : ApiResponse::error(ErrorCode::SERVER_ERROR),
            };
        });
    })->create();
