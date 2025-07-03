<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PenalizacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Penalizaciones obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo penalizaciones: ' . $e->getMessage());
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
                'ticket_id' => 'required|integer',
                'usuario_id' => 'required|integer',
                'tipo_penalizacion' => 'required|in:tiempo_excedido,dano_propiedad,mal_estacionamiento',
                'descripcion' => 'required|string|max:500',
                'monto' => 'required|numeric|min:0',
                'estado' => 'sometimes|in:pendiente,pagada,cancelada'
            ]);

            return response()->json([
                'success' => true,
                'data' => array_merge($validatedData, ['id' => rand(1, 1000)]),
                'message' => 'Penalización creada exitosamente'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creando penalización: ' . $e->getMessage());
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
            return response()->json([
                'success' => true,
                'data' => ['id' => $id],
                'message' => 'Penalización obtenida exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo penalización: ' . $e->getMessage());
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
            $validatedData = $request->validate([
                'descripcion' => 'sometimes|string|max:500',
                'monto' => 'sometimes|numeric|min:0',
                'estado' => 'sometimes|in:pendiente,pagada,cancelada'
            ]);

            return response()->json([
                'success' => true,
                'data' => array_merge(['id' => $id], $validatedData),
                'message' => 'Penalización actualizada exitosamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error actualizando penalización: ' . $e->getMessage());
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
            return response()->json([
                'success' => true,
                'message' => 'Penalización eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error eliminando penalización: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Get penalizations by user
     */
    public function getPenalizationsByUser(int $userId): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Penalizaciones del usuario obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo penalizaciones del usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Apply penalty to a ticket
     */
    public function applyPenalty(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'ticket_id' => 'required|integer',
                'tipo_penalizacion' => 'required|in:tiempo_excedido,dano_propiedad,mal_estacionamiento',
                'descripcion' => 'required|string|max:500'
            ]);

            // Lógica para calcular el monto de la penalización basado en el tipo
            $montos = [
                'tiempo_excedido' => 15.00,
                'dano_propiedad' => 100.00,
                'mal_estacionamiento' => 25.00
            ];

            $monto = $montos[$validatedData['tipo_penalizacion']] ?? 20.00;

            return response()->json([
                'success' => true,
                'data' => [
                    'penalty_id' => uniqid('pen_'),
                    'ticket_id' => $validatedData['ticket_id'],
                    'tipo' => $validatedData['tipo_penalizacion'],
                    'monto' => $monto,
                    'descripcion' => $validatedData['descripcion'],
                    'estado' => 'pendiente'
                ],
                'message' => 'Penalización aplicada exitosamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error aplicando penalización: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error aplicando penalización'
            ], 500);
        }
    }

    /**
     * Pay penalty
     */
    public function payPenalty(Request $request, int $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'metodo_pago' => 'required|in:efectivo,tarjeta,transferencia'
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'penalty_id' => $id,
                    'payment_id' => uniqid('pay_pen_'),
                    'estado' => 'pagada',
                    'metodo_pago' => $validatedData['metodo_pago']
                ],
                'message' => 'Penalización pagada exitosamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error pagando penalización: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error procesando pago de penalización'
            ], 500);
        }
    }
}
