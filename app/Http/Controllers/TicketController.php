<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\TicketRepositoryInterface;
use App\Http\Requests\TicketRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Exceptions\ValidationException;
use App\Exceptions\EntityNotFoundException;

class TicketController extends Controller
{
    private TicketRepositoryInterface $ticketRepository;

    public function __construct(TicketRepositoryInterface $ticketRepository)
    {
        $this->ticketRepository = $ticketRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $tickets = $this->ticketRepository->all();
            
            return response()->json([
                'success' => true,
                'data' => $tickets,
                'message' => 'Tickets obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo tickets: ' . $e->getMessage());
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
                'usuario_id' => 'required|integer',
                'vehiculo_id' => 'required|string|exists:vehiculos,placa',
                'estacionamiento_id' => 'required|integer|exists:estacionamientoadmin,id',
                'codigo_ticket' => 'required|string|max:50|unique:tickets,codigo_ticket',
                'fecha_entrada' => 'required|date',
                'fecha_salida' => 'nullable|date|after:fecha_entrada',
                'precio_total' => 'nullable|numeric|min:0',
                'estado' => 'sometimes|in:activo,finalizado,cancelado',
                'tipo_reserva' => 'required|in:por_horas,mensual'
            ]);

            $ticket = $this->ticketRepository->create($validatedData);

            return response()->json([
                'success' => true,
                'data' => $ticket,
                'message' => 'Ticket creado exitosamente'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creando ticket: ' . $e->getMessage());
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
            $ticket = $this->ticketRepository->find($id);
            
            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $ticket,
                'message' => 'Ticket obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo ticket: ' . $e->getMessage());
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
            $ticket = $this->ticketRepository->find($id);
            
            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ], 404);
            }

            $validatedData = $request->validate([
                'usuario_id' => 'sometimes|integer',
                'vehiculo_id' => 'sometimes|string|exists:vehiculos,placa',
                'estacionamiento_id' => 'sometimes|integer',
                'codigo_ticket' => 'sometimes|string|max:50|unique:tickets,codigo_ticket,' . $id,
                'fecha_entrada' => 'sometimes|date',
                'fecha_salida' => 'nullable|date|after:fecha_entrada',
                'precio_total' => 'nullable|numeric|min:0',
                'estado' => 'sometimes|in:activo,finalizado,cancelado,pagado',
                'tipo_reserva' => 'sometimes|in:por_horas,mensual'
            ]);

            $updatedTicket = $this->ticketRepository->update($id, $validatedData);

            return response()->json([
                'success' => true,
                'data' => $updatedTicket,
                'message' => 'Ticket actualizado exitosamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error actualizando ticket: ' . $e->getMessage());
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
            $ticket = $this->ticketRepository->find($id);
            
            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ], 404);
            }

            $this->ticketRepository->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Ticket eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error eliminando ticket: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Get active tickets
     */
    public function getActiveTickets(): JsonResponse
    {
        try {
            $tickets = $this->ticketRepository->findActiveTickets();
            
            return response()->json([
                'success' => true,
                'data' => $tickets,
                'message' => 'Tickets activos obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo tickets activos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Get tickets by user
     */
    public function getTicketsByUser(int $userId): JsonResponse
    {
        try {
            $tickets = $this->ticketRepository->findByUsuario($userId);
            
            return response()->json([
                'success' => true,
                'data' => $tickets,
                'message' => 'Tickets del usuario obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo tickets del usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Get ticket by code
     */
    public function getTicketByCode(string $codigo): JsonResponse
    {
        try {
            $ticket = $this->ticketRepository->findByCodigo($codigo);
            
            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $ticket,
                'message' => 'Ticket obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo ticket por código: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Finalize ticket (checkout)
     */
    public function finalizeTicket(Request $request, int $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'precio_total' => 'required|numeric|min:0'
            ]);

            $result = $this->ticketRepository->finalizarTicket($id, $validatedData['precio_total']);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo finalizar el ticket'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Ticket finalizado exitosamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error finalizando ticket: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }
}
