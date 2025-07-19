<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

abstract class BaseController extends Controller
{
    /**
     * Return success response
     */
    protected function successResponse($data = null, string $message = 'Operación exitosa', int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    /**
     * Return error response
     */
    protected function errorResponse(string $message = 'Error interno del servidor', int $status = 500, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Handle exceptions with standardized responses
     */
    protected function handleException(\Exception $e, string $context = 'Operación'): JsonResponse
    {
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return $this->errorResponse(
                'Datos de validación incorrectos',
                422,
                $e->errors()
            );
        }

        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('Recurso no encontrado', 404);
        }

        Log::error("{$context}: " . $e->getMessage(), [
            'exception' => $e,
            'trace' => $e->getTraceAsString()
        ]);

        return $this->errorResponse('Error interno del servidor', 500);
    }

    /**
     * Return paginated response
     */
    protected function paginatedResponse($paginatedData, string $message = 'Datos obtenidos exitosamente'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginatedData->items(),
            'pagination' => [
                'current_page' => $paginatedData->currentPage(),
                'per_page' => $paginatedData->perPage(),
                'total' => $paginatedData->total(),
                'last_page' => $paginatedData->lastPage(),
                'has_more_pages' => $paginatedData->hasMorePages(),
                'from' => $paginatedData->firstItem(),
                'to' => $paginatedData->lastItem()
            ]
        ]);
    }
}
