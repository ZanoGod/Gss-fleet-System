<?php

use App\Support\ApiErrorResponses;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $exception): bool => ApiErrorResponses::shouldReturnJson($request)
        );

        $exceptions->render(
            fn (Throwable $exception, Request $request) => ApiErrorResponses::render($request, $exception)
        );

        $exceptions->report(function (Throwable $exception): ?bool {
            if (! ApiErrorResponses::isDatabaseConnectionFailure($exception)) {
                return null;
            }

            Log::error('Database connection unavailable.', ApiErrorResponses::databaseLogContext($exception));

            // The sanitized database event above replaces Laravel's raw SQL exception log.
            return false;
        });
    })->create();
