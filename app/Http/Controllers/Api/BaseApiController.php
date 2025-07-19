<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Base controller for API endpoints with common CRUD operations and standardized responses
 */
abstract class BaseApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->applyRoleMiddleware();
    }

    /**
     * Apply role middleware to the controller
     * @return void
     */
    abstract protected function applyRoleMiddleware(): void;

    /**
     * Return a success response
     */
    protected function successResponse($data = null, string $message = 'Operación exitosa', int $status = 200): JsonResponse
    {
        $response = ['message' => $message];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return response()->json($response, $status);
    }

    /**
     * Return an error response
     */
    protected function errorResponse(string $message = 'Error en la operación', int $status = 400, $errors = null): JsonResponse
    {
        $response = ['message' => $message];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        return response()->json($response, $status);
    }

    /**
     * Handle exceptions and return appropriate response
     */
    protected function handleException(\Exception $e): JsonResponse
    {
        Log::error('API Exception: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('Registro no encontrado', 404);
        }

        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return $this->errorResponse('Datos de validación incorrectos', 422, $e->errors());
        }

        return $this->errorResponse('Error interno del servidor: ' . $e->getMessage(), 500);
    }

    /**
     * Return a paginated response
     */
    protected function paginatedResponse($paginatedData, string $message = 'Datos obtenidos exitosamente'): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $paginatedData->items(),
            'pagination' => [
                'current_page' => $paginatedData->currentPage(),
                'per_page' => $paginatedData->perPage(),
                'total' => $paginatedData->total(),
                'last_page' => $paginatedData->lastPage(),
                'has_more_pages' => $paginatedData->hasMorePages()
            ]
        ]);
    }

    /**
     * Handle index operation with pagination
     */
    protected function indexResponse(BaseService $service, array $filters = [], int $perPage = 10, string $message = 'Datos obtenidos exitosamente'): JsonResponse
    {
        try {
            $page = request('page', 1);
            $result = $service->getPaginated($page, $perPage, $filters);
            
            return $this->paginatedResponse($result, $message);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Handle show operation
     */
    protected function showResponse(BaseService $service, int $id, string $message = 'Registro obtenido exitosamente'): JsonResponse
    {
        try {
            $result = $service->getById($id);
            
            return $this->successResponse($result, $message);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Handle store operation
     */
    protected function storeResponse(BaseService $service, array $data, string $message = 'Registro creado exitosamente'): JsonResponse
    {
        try {
            $result = $service->create($data);
            
            return $this->successResponse($result, $message, 201);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Handle update operation
     */
    protected function updateResponse(BaseService $service, int $id, array $data, string $message = 'Registro actualizado exitosamente'): JsonResponse
    {
        try {
            $model = $service->getById($id);
            $result = $service->update($model, $data);
            
            return $this->successResponse($result, $message);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Handle destroy operation
     */
    protected function destroyResponse(BaseService $service, int $id, string $message = 'Registro eliminado exitosamente'): JsonResponse
    {
        try {
            $model = $service->getById($id);
            $service->delete($model);
            
            return $this->successResponse(null, $message);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
