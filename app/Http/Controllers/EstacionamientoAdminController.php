<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\EstacionamientoAdminRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class EstacionamientoAdminController extends Controller
{
    private EstacionamientoAdminRepositoryInterface $estacionamientoRepository;

    public function __construct(EstacionamientoAdminRepositoryInterface $estacionamientoRepository)
    {
        $this->estacionamientoRepository = $estacionamientoRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $estacionamientos = $this->estacionamientoRepository->all();
            
            return response()->json([
                'success' => true,
                'data' => $estacionamientos,
                'message' => 'Estacionamientos obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo estacionamientos: ' . $e->getMessage());
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
                'nombre' => 'required|string|max:100',
                'email' => 'required|email|max:100|unique:estacionamientoadmin,email',
                'direccion' => 'required|string|max:255',
                'espacios_totales' => 'required|integer|min:1',
                'espacios_disponibles' => 'required|integer|min:0',
                'precio_por_hora' => 'required|numeric|min:0',
                'precio_mensual' => 'required|numeric|min:0',
                'estado' => 'sometimes|in:activo,inactivo'
            ]);

            $estacionamiento = $this->estacionamientoRepository->create($validatedData);

            return response()->json([
                'success' => true,
                'data' => $estacionamiento,
                'message' => 'Estacionamiento creado exitosamente'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creando estacionamiento: ' . $e->getMessage());
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
            $estacionamiento = $this->estacionamientoRepository->find($id);
            
            if (!$estacionamiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estacionamiento no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $estacionamiento,
                'message' => 'Estacionamiento obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo estacionamiento: ' . $e->getMessage());
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
            $estacionamiento = $this->estacionamientoRepository->find($id);
            
            if (!$estacionamiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estacionamiento no encontrado'
                ], 404);
            }

            $validatedData = $request->validate([
                'nombre' => 'sometimes|string|max:100',
                'email' => 'sometimes|email|max:100|unique:estacionamientoadmin,email,' . $id,
                'direccion' => 'sometimes|string|max:255',
                'espacios_totales' => 'sometimes|integer|min:1',
                'espacios_disponibles' => 'sometimes|integer|min:0',
                'precio_por_hora' => 'sometimes|numeric|min:0',
                'precio_mensual' => 'sometimes|numeric|min:0',
                'estado' => 'sometimes|in:activo,inactivo'
            ]);

            $updatedEstacionamiento = $this->estacionamientoRepository->update($id, $validatedData);

            return response()->json([
                'success' => true,
                'data' => $updatedEstacionamiento,
                'message' => 'Estacionamiento actualizado exitosamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error actualizando estacionamiento: ' . $e->getMessage());
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
            $estacionamiento = $this->estacionamientoRepository->find($id);
            
            if (!$estacionamiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estacionamiento no encontrado'
                ], 404);
            }

            $this->estacionamientoRepository->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Estacionamiento eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error eliminando estacionamiento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Get estacionamiento by email
     */
    public function getEstacionamientoByEmail(string $email): JsonResponse
    {
        try {
            $estacionamiento = $this->estacionamientoRepository->findByEmail($email);
            
            if (!$estacionamiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estacionamiento no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $estacionamiento,
                'message' => 'Estacionamiento obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo estacionamiento por email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Get estacionamientos with available spaces
     */
    public function getEstacionamientosConEspacios(): JsonResponse
    {
        try {
            $estacionamientos = $this->estacionamientoRepository->getEstacionamientosConEspacios();
            
            return response()->json([
                'success' => true,
                'data' => $estacionamientos,
                'message' => 'Estacionamientos con espacios obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo estacionamientos con espacios: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Update available spaces
     */
    public function updateEspaciosDisponibles(Request $request, int $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'espacios_disponibles' => 'required|integer|min:0'
            ]);

            $result = $this->estacionamientoRepository->updateEspaciosDisponibles(
                $id, 
                $validatedData['espacios_disponibles']
            );

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo actualizar los espacios disponibles'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Espacios disponibles actualizados exitosamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error actualizando espacios disponibles: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Increment reservations count
     */
    public function incrementarReservas(int $id): JsonResponse
    {
        try {
            $result = $this->estacionamientoRepository->incrementarReservas($id);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo incrementar las reservas'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reservas incrementadas exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error incrementando reservas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }
}
