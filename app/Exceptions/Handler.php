<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
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
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     * Provides custom JSON error responses for API
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws Throwable
     */
    public function render($request, Throwable $e)
    {
        // Handle API requests with JSON responses
        if ($request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle exceptions for API routes with consistent JSON format
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Throwable  $e
     * @return JsonResponse
     */
    private function handleApiException($request, Throwable $e): JsonResponse
    {
        // Handle validation errors
        if ($e instanceof ValidationException) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        // Handle Model Not Found (Product not found)
        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'message' => 'Resource not found',
            ], 404);
        }

        // Handle 404 Not Found
        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'message' => 'Endpoint not found',
            ], 404);
        }

        // Handle other exceptions
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

        return response()->json([
            'message' => $e->getMessage() ?: 'An error occurred',
            'error' => config('app.debug') ? [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ] : null,
        ], $statusCode);
    }
}
