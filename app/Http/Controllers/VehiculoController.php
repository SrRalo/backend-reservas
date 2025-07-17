<?php

namespace App\Http\Controllers;

use App\Services\VehiculoService;
use App\Http\Requests\CreateVehiculoRequest;
use App\Http\Requests\UpdateVehiculoRequest;
use App\Http\Requests\PaginationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class VehiculoController extends Controller
{
    private VehiculoService $vehiculoService;

    public function __construct(VehiculoService $vehiculoService)
    {
        $this->vehiculoService = $vehiculoService;
        
        // Middleware de autenticación para todas las rutas
        $this->middleware('auth:sanctum');
        
        // Middleware de autorización específico por método
        $this->middleware('permission:view-all-vehiculos')->only(['index', 'getStatistics']);
        $this->middleware('permission:create-vehiculos')->only(['store']);
        $this->middleware('permission:update-vehiculos')->only(['update']);
        $this->middleware('permission:delete-vehiculos')->only(['destroy']);
        
        // Middleware de roles para métodos específicos
        $this->middleware('role:admin')->only(['destroy']);
    }

    /**
     * Display a listing of vehicles with advanced pagination and filters
     */
    public function index(PaginationRequest $request): JsonResponse
    {
        try {
            $paginationParams = $request->getPaginationParams();
            $filters = $request->getFilterParams();
            
            $vehiculos = $this->vehiculoService->getPaginatedVehiculos(
                $paginationParams['page'],
                $paginationParams['per_page'],
                $filters
            );
            
            return response()->json([
                'success' => true,
                'data' => $vehiculos->items(),
                'pagination' => [
                    'current_page' => $vehiculos->currentPage(),
                    'last_page' => $vehiculos->lastPage(),
                    'per_page' => $vehiculos->perPage(),
                    'total' => $vehiculos->total(),
                    'from' => $vehiculos->firstItem(),
                    'to' => $vehiculos->lastItem(),
                    'has_more_pages' => $vehiculos->hasMorePages()
                ],
                'filters_applied' => $filters,
                'message' => 'Vehículos obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo vehículos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los vehículos',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'placa' => 'required|string|max:20|unique:vehiculos,placa',
                'modelo' => 'required|string|max:100',
                'color' => 'required|string|max:50',
                'usuario_id' => 'required|integer',
                'estado' => 'sometimes|in:activo,inactivo'
            ]);

            $vehiculo = $this->vehiculoRepository->create($validatedData);

            return response()->json([
                'success' => true,
                'data' => $vehiculo,
                'message' => 'Vehículo creado exitosamente'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creando vehículo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $placa): JsonResponse
    {
        try {
            $vehiculo = $this->vehiculoRepository->findByPlaca($placa);
            
            if (!$vehiculo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehículo no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $vehiculo,
                'message' => 'Vehículo obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo vehículo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $placa): JsonResponse
    {
        try {
            $vehiculo = $this->vehiculoRepository->findByPlaca($placa);
            
            if (!$vehiculo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehículo no encontrado'
                ], 404);
            }

            $validatedData = $request->validate([
                'placa' => 'sometimes|string|max:20|unique:vehiculos,placa,' . $placa . ',placa',
                'modelo' => 'sometimes|string|max:100',
                'color' => 'sometimes|string|max:50',
                'usuario_id' => 'sometimes|integer',
                'estado' => 'sometimes|in:activo,inactivo'
            ]);

            // Actualizar usando el modelo directamente ya que BaseRepository espera ID
            $vehiculo->update($validatedData);
            $updatedVehiculo = $vehiculo->fresh();

            return response()->json([
                'success' => true,
                'data' => $updatedVehiculo,
                'message' => 'Vehículo actualizado exitosamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error actualizando vehículo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $placa): JsonResponse
    {
        try {
            $vehiculo = $this->vehiculoRepository->findByPlaca($placa);
            
            if (!$vehiculo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehículo no encontrado'
                ], 404);
            }

            // Eliminar usando el modelo directamente
            $vehiculo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Vehículo eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error eliminando vehículo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Get vehicle by license plate
     */
    public function getVehicleByPlaca(string $placa): JsonResponse
    {
        try {
            $vehiculo = $this->vehiculoRepository->findByPlaca($placa);
            
            if (!$vehiculo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehículo no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $vehiculo,
                'message' => 'Vehículo obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo vehículo por placa: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Get vehicles by user
     */
    public function getVehiclesByUser(int $userId): JsonResponse
    {
        try {
            $vehiculos = $this->vehiculoRepository->findByUsuario($userId);
            
            return response()->json([
                'success' => true,
                'data' => $vehiculos,
                'message' => 'Vehículos del usuario obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo vehículos del usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }
}
