<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\VehiculoRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class VehiculoController extends Controller
{
    private VehiculoRepositoryInterface $vehiculoRepository;

    public function __construct(VehiculoRepositoryInterface $vehiculoRepository)
    {
        $this->vehiculoRepository = $vehiculoRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $vehiculos = $this->vehiculoRepository->all();
            
            return response()->json([
                'success' => true,
                'data' => $vehiculos,
                'message' => 'Vehículos obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo vehículos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
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
    public function show(int $id): JsonResponse
    {
        try {
            $vehiculo = $this->vehiculoRepository->find($id);
            
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
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $vehiculo = $this->vehiculoRepository->find($id);
            
            if (!$vehiculo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehículo no encontrado'
                ], 404);
            }

            $validatedData = $request->validate([
                'placa' => 'sometimes|string|max:20|unique:vehiculos,placa,' . $id,
                'modelo' => 'sometimes|string|max:100',
                'color' => 'sometimes|string|max:50',
                'usuario_id' => 'sometimes|integer',
                'estado' => 'sometimes|in:activo,inactivo'
            ]);

            $updatedVehiculo = $this->vehiculoRepository->update($id, $validatedData);

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
    public function destroy(int $id): JsonResponse
    {
        try {
            $vehiculo = $this->vehiculoRepository->find($id);
            
            if (!$vehiculo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehículo no encontrado'
                ], 404);
            }

            $this->vehiculoRepository->delete($id);

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
