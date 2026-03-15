<?php

namespace Daniardev\LaravelTsd\Exceptions;

use Daniardev\LaravelTsd\Helpers\AppResponse;
use Daniardev\LaravelTsd\Helpers\AppLog;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

/**
 * Global Exception Handler for Laravel 11/12
 *
 * Standardized exception handling for all Laravel projects using laravel_tsd package.
 * This handler provides:
 * - Automatic logging with proper levels (WARNING for 4xx, ERROR for 5xx)
 * - JSON error responses for API requests
 * - Structured log context with request ID and user info
 *
 * Setup in bootstrap/app.php:
 *   ->withExceptions(fn (\Illuminate\Foundation\Configuration\Exceptions $e) => AppHandler::configure($e))
 *
 * @package Daniardev\LaravelTsd\Exceptions
 */
class AppHandler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Configure Laravel 11/12 exception handling.
     *
     * This method ONLY registers exception reporting (logging).
     * Custom rendering is handled by the render() method in this class.
     *
     * Called from bootstrap/app.php:
     *   ->withExceptions(fn (\Illuminate\Foundation\Configuration\Exceptions $e) => AppHandler::configure($e))
     *
     * @param Exceptions $exceptions Laravel exception configuration
     */
    public static function configure(Exceptions $exceptions): void
    {
        // Register exception reporting (logging)
        $exceptions->report(function (Throwable $e) {
            $request = request();

            // Skip normal errors that don't need logging
            if (static::shouldNotLog($e)) {
                return false;
            }

            // Build context with request ID and user info
            $context = static::buildLogContext($request, $e);

            // Use json-daily channel explicitly to ensure proper formatting
            $logger = app('log')->channel('json-daily');

            // Determine log level based on exception type
            if (static::isSecurityIssue($e)) {
                // 4xx - Security monitoring (WARNING level)
                $logger->warning('Security issue detected', $context);
            } else {
                // 500+ - System error (ERROR level)
                $logger->error('System error detected', $context);
            }

            return false; // Stop propagation to prevent duplicate logging
        })->stop();

        // Register exception rendering
        $exceptions->render(function (Throwable $e, Request $request) {
            // For API requests, return JSON responses
            if ($request->expectsJson() || $request->is('api/*')) {
                return static::renderException($e);
            }

            // For web requests, return null to let Laravel's default renderer handle it
            return null;
        });
    }

    /**
     * Determine if exception should NOT be logged.
     *
     * NOT logged (normal/expected errors):
     * - 404: Wrong endpoint/route (user error)
     * - 422: User submitted invalid data (expected validation)
     * - 429: Rate limiting (working as intended)
     * - 419: CSRF mismatch (normal session expiry)
     * - 405: Wrong HTTP method (user error)
     * - 413: File too large (validation error)
     *
     * LOGGED (important for debugging):
     * - 401/403: Unauthorized access attempts (security monitoring)
     * - 500+: Server errors, database errors, runtime exceptions
     */
    private static function shouldNotLog(Throwable $e): bool
    {
        $normalErrors = [
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
            \Illuminate\Database\Eloquent\ModelNotFoundException::class,
            \Illuminate\Validation\ValidationException::class,
            \Illuminate\Http\Exceptions\ThrottleRequestsException::class,
            \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
            \Illuminate\Session\TokenMismatchException::class,
            \Illuminate\Http\Exceptions\PostTooLargeException::class,
        ];

        return in_array(get_class($e), $normalErrors);
    }

    /**
     * Check if exception is a security issue (4xx status codes).
     *
     * Uses a generic approach based on HTTP status codes instead of checking
     * specific exception types. This works for any exception type.
     *
     * @return bool True if 4xx (client error), false if 5xx (server error)
     */
    private static function isSecurityIssue(Throwable $e): bool
    {
        $statusCode = static::getStatusCode($e);

        // 4xx codes (400-499) are security/client issues
        // Logged as WARNING for security monitoring
        return $statusCode >= 400 && $statusCode < 500;
    }

    /**
     * Get HTTP status code from exception.
     *
     * Generic approach that handles all exception types:
     * 1. AppException (custom business logic exceptions)
     * 2. Laravel's AuthenticationException and AuthorizationException
     * 3. Any Symfony HttpException (covers 404, 405, 413, 419, 422, 429, etc.)
     * 4. Defaults to 500 (server error)
     *
     * @return int HTTP status code (100-599)
     */
    private static function getStatusCode(Throwable $e): int
    {
        // AppException has code() method for custom status codes
        if ($e instanceof AppException) {
            return $e->code();
        }

        // Laravel AuthenticationException = 401
        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return 401;
        }

        // Laravel AuthorizationException = 403
        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return 403;
        }

        // Any Symfony HttpException (covers 404, 405, 413, 419, 422, 429, etc.)
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
            return $e->getStatusCode();
        }

        // Default: treat as 500 (server error)
        return 500;
    }

    /**
     * Build structured log context with request ID, user info, and sanitized data.
     *
     * Uses AppLog::getRequestContext() for consistent format across the app.
     * Adds exception-specific context (type, message, file, line, trace).
     *
     * @return array Structured context for logging
     */
    private static function buildLogContext(?Request $request, Throwable $e): array
    {
        $requestType = $request && $request->expectsJson() ? 'API' : 'Web';

        $exceptionContext = [
            'request_type' => $requestType,
            'exception_type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        // Add sanitized trace in debug mode only
        if (config('app.debug')) {
            $exceptionContext['trace'] = AppLog::sanitizeTrace($e->getTraceAsString());
        }

        // Merge with AppLog::getRequestContext() for consistent format
        return AppLog::getRequestContext($request, $exceptionContext);
    }

    /**
     * Render exception as JSON response for API requests.
     *
     * Handles all exception types and returns appropriate JSON responses
     * with proper HTTP status codes and error messages.
     *
     * @return JsonResponse JSON error response
     */
    private static function renderException(Throwable $e): JsonResponse
    {
        // exception authentication - 401
        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return AppResponse::error(__('tsd_message.unauthenticated'), 401);
        }

        // exception authorization - 403
        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return AppResponse::error(__('tsd_message.forbidden'), 403);
        }

        // exception not found (route) - 404
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return AppResponse::error(__('tsd_message.notFound'));
        }

        // exception model not found - 404
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return AppResponse::error(
                __('tsd_message.emptyLoadedName', ['name' => class_basename($e->getModel())])
            );
        }

        // exception method not allowed - 405
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
            return AppResponse::error(__('tsd_message.methodNotAllowed'), 405);
        }

        // exception post too large - 413
        if ($e instanceof \Illuminate\Http\Exceptions\PostTooLargeException) {
            return AppResponse::error(__('tsd_message.fileTooLarge'), 413);
        }

        // exception validation - 422
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return AppResponse::error($e->getMessage(), 422);
        }

        // exception token mismatch (CSRF) - 419
        if ($e instanceof \Illuminate\Session\TokenMismatchException) {
            return AppResponse::error(__('tsd_message.sessionExpired'), 419);
        }

        // exception too many requests - 429
        if ($e instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException) {
            return AppResponse::error(__('tsd_message.tooManyRequests'), 429);
        }

        // exception internal (custom AppException)
        if ($e instanceof AppException) {
            return AppResponse::error($e->message(), $e->code());
        }

        // exception query (database error)
        if ($e instanceof \Illuminate\Database\QueryException) {
            return AppResponse::error(
                config('app.debug')
                    ? __('tsd_message.databaseError') . ': ' . $e->getMessage()
                    : __('tsd_message.databaseError'),
                500
            );
        }

        // exception other (generic Exception, etc)
        return AppResponse::error(
            config('app.debug') ? $e->getMessage() : __('tsd_message.serverError'),
            500
        );
    }

    /**
     * Render an exception into an HTTP response.
     *
     * - API requests (expectsJson or /api/* routes): Return JSON responses
     * - Web requests: Use Laravel's default HTML rendering
     *
     * @param Request $request
     * @param Throwable $e
     * @return Response|JsonResponse|RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws Throwable
     */
    public function render($request, Throwable $e): Response|JsonResponse|RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        // For API requests, return JSON responses
        if ($request->expectsJson() || $request->is('api/*')) {
            return static::renderException($e);
        }

        // For non-API (web) requests, use Laravel's default rendering (HTML pages)
        return parent::render($request, $e);
    }
}