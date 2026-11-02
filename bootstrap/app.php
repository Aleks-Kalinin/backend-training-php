<?php

use App\Exceptions\Auth\ChallengeException;
use App\Exceptions\Auth\ResendCooldownException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // One JSON error shape for the whole API:
        //
        //   { "error": { "code": "...", "message": "...", "details": { ... } } }
        //
        // `code` is stable and machine-readable; `message` is for humans and must
        // never leak account existence, secrets or stack traces.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            [$status, $code, $message, $details, $headers] = match (true) {
                $e instanceof ValidationException => [
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    'VALIDATION_FAILED',
                    'The given data was invalid.',
                    $e->errors(),
                    [],
                ],
                $e instanceof ChallengeException => [
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    $e->errorCode,
                    $e->getMessage(),
                    null,
                    [],
                ],
                $e instanceof ResendCooldownException => [
                    Response::HTTP_TOO_MANY_REQUESTS,
                    'RESEND_COOLDOWN',
                    $e->getMessage(),
                    null,
                    ['Retry-After' => (string) $e->retryAfterSeconds],
                ],
                $e instanceof TooManyRequestsHttpException => [
                    Response::HTTP_TOO_MANY_REQUESTS,
                    'TOO_MANY_REQUESTS',
                    'Too many requests. Try again later.',
                    null,
                    $e->getHeaders(),
                ],
                $e instanceof AuthenticationException => [
                    Response::HTTP_UNAUTHORIZED,
                    'UNAUTHENTICATED',
                    'Authentication is required.',
                    null,
                    [],
                ],
                $e instanceof AuthorizationException => [
                    Response::HTTP_FORBIDDEN,
                    'FORBIDDEN',
                    'This action is not allowed.',
                    null,
                    [],
                ],
                $e instanceof ModelNotFoundException,
                $e instanceof NotFoundHttpException => [
                    Response::HTTP_NOT_FOUND,
                    'NOT_FOUND',
                    'The requested resource was not found.',
                    null,
                    [],
                ],
                $e instanceof HttpExceptionInterface => [
                    $e->getStatusCode(),
                    'HTTP_ERROR',
                    $e->getMessage() ?: 'Request failed.',
                    null,
                    $e->getHeaders(),
                ],
                default => [
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                    'SERVER_ERROR',
                    config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.',
                    null,
                    [],
                ],
            };

            $payload = ['error' => array_filter([
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ], static fn ($value) => $value !== null)];

            return response()->json($payload, $status, $headers);
        });
    })->create();
