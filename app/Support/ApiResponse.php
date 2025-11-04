<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Throwable;

trait ApiResponse
{
    protected function ok($data = null, string $message = 'OK', int $code = 200): JsonResponse
    {
        return response()->json(['message' => $message, 'data' => $data], $code);
    }

    protected function created($data = null, string $message = 'Recurso creado'): JsonResponse
    {
        return $this->ok($data, $message, 201);
    }

    protected function fail(string $message = 'Error', int $code = 500, $errors = null): JsonResponse
    {
        return response()->json(['message' => $message, 'errors' => $errors], $code);
    }

    protected function exception(Throwable $e, string $fallback = 'Error interno'): JsonResponse
    {
        report($e);
        return $this->fail(app()->hasDebugModeEnabled() ? $e->getMessage() : $fallback, 500);
    }
    protected function successResponse($data = null, string $message = 'OK', int $code = 200): JsonResponse
    {
        return $this->ok($data, $message, $code);
    }

}
