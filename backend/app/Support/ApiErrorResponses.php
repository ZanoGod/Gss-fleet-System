<?php

namespace App\Support;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use PDOException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ApiErrorResponses
{
    private const DATABASE_UNAVAILABLE_CODES = [1044, 1045, 1049, 2002, 2003, 2005, 2006, 2013];

    public static function shouldReturnJson(Request $request): bool
    {
        return $request->is('api/*')
            || in_array($request->path(), ['login', 'logout'], true)
            || $request->expectsJson();
    }

    public static function isDatabaseConnectionFailure(Throwable $exception): bool
    {
        for ($current = $exception; $current !== null; $current = $current->getPrevious()) {
            if (! $current instanceof PDOException) {
                continue;
            }

            $errorInfo = $current->errorInfo ?? [];
            $sqlState = (string) ($errorInfo[0] ?? $current->getCode());
            $driverCode = (int) ($errorInfo[1] ?? 0);

            if (str_starts_with($sqlState, '08') || in_array($driverCode, self::DATABASE_UNAVAILABLE_CODES, true)) {
                return true;
            }
        }

        return false;
    }

    public static function databaseLogContext(Throwable $exception): array
    {
        for ($current = $exception; $current !== null; $current = $current->getPrevious()) {
            if (! $current instanceof PDOException) {
                continue;
            }

            $errorInfo = $current->errorInfo ?? [];

            return [
                'exception_type' => $exception::class,
                'sql_state' => (string) ($errorInfo[0] ?? $current->getCode()),
                'driver_code' => (int) ($errorInfo[1] ?? 0),
            ];
        }

        return ['exception_type' => $exception::class];
    }

    public static function render(Request $request, Throwable $exception): ?JsonResponse
    {
        if (! self::shouldReturnJson($request)) {
            return null;
        }

        if (self::isDatabaseConnectionFailure($exception)) {
            return self::failure('DATABASE_UNAVAILABLE', 'Database connection unavailable. Please contact the administrator.', 503);
        }

        if ($exception instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'code' => 'VALIDATION_ERROR',
                'message' => 'The submitted data is invalid.',
                'errors' => $exception->errors(),
            ], $exception->status);
        }

        if ($exception instanceof AuthenticationException) {
            return self::failure('UNAUTHENTICATED', 'Authentication is required to continue.', 401);
        }

        if ($exception instanceof TokenMismatchException) {
            return self::failure('CSRF_INVALID', 'Your session is invalid or expired. Please refresh and try again.', 419);
        }

        if ($exception instanceof ThrottleRequestsException) {
            return self::failure('RATE_LIMITED', 'Too many requests. Please wait and try again.', 429);
        }

        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
        $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];

        return match ($status) {
            401 => self::failure('UNAUTHENTICATED', 'Authentication is required to continue.', 401, [], $headers),
            403 => self::failure('FORBIDDEN', 'You are not authorized to perform this action.', 403, [], $headers),
            404 => self::failure('NOT_FOUND', 'The requested resource could not be found.', 404, [], $headers),
            419 => self::failure('CSRF_INVALID', 'Your session is invalid or expired. Please refresh and try again.', 419, [], $headers),
            429 => self::failure('RATE_LIMITED', 'Too many requests. Please wait and try again.', 429, [], $headers),
            default => self::failure('SERVER_ERROR', 'An unexpected server error occurred. Please try again later.', 500),
        };
    }

    private static function failure(string $code, string $message, int $status, array $errors = [], array $headers = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
            'errors' => (object) $errors,
        ], $status, $headers);
    }
}
