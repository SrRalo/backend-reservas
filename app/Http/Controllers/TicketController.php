<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\TicketRepositoryInterface;
use App\Services\TicketService;
use App\Http\Requests\TicketRequest;
use App\Events\ReservaStatusEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Exceptions\ValidationException;
use App\Exceptions\EntityNotFoundException;

class TicketController extends Controller
{
    private TicketRepositoryInterface $ticketRepository;
    private TicketService $ticketService;

    public function __construct(
        TicketRepositoryInterface $ticketRepository,
        TicketService $ticketService
    ) {
        $this->ticketRepository = $ticketRepository;
        $this->ticketService = $ticketService;
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

            // Emitir evento WebSocket para nueva reserva
            ReservaStatusEvent::dispatch([
                'ticket_id' => $ticket->id,
                'usuario_id' => $ticket->usuario_id,
                'estacionamiento_id' => $ticket->estacionamiento_id,
                'status' => $ticket->estado,
                'tipo_reserva' => $ticket->tipo_reserva,
                'action' => 'created',
                'timestamp' => now()->toISOString()
            ]);

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

            // Emitir evento WebSocket para actualización de reserva
            ReservaStatusEvent::dispatch([
                'ticket_id' => $updatedTicket->id,
                'usuario_id' => $updatedTicket->usuario_id,
                'estacionamiento_id' => $updatedTicket->estacionamiento_id,
                'status' => $updatedTicket->estado,
                'tipo_reserva' => $updatedTicket->tipo_reserva,
                'action' => 'updated',
                'timestamp' => now()->toISOString()
            ]);

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

            // Emitir evento WebSocket antes de eliminar
            ReservaStatusEvent::dispatch([
                'ticket_id' => $ticket->id,
                'usuario_id' => $ticket->usuario_id,
                'estacionamiento_id' => $ticket->estacionamiento_id,
                'status' => 'eliminado',
                'tipo_reserva' => $ticket->tipo_reserva,
                'action' => 'deleted',
                'timestamp' => now()->toISOString()
            ]);

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
     * Get all active tickets for admin management
     */
    public function getActiveTickets(): JsonResponse
    {
        try {
            $activeTickets = $this->ticketRepository->getByStatus(['activo']);
            
            return response()->json([
                'success' => true,
                'data' => $activeTickets,
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
     * Get tickets by user ID
     */
    public function getTicketsByUser(int $userId): JsonResponse
    {
        try {
            $tickets = $this->ticketRepository->getByUser($userId);
            
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
            $ticket = $this->ticketRepository->getByCode($codigo);
            
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
     * Finalize a ticket (admin action)
     */
    public function finalizeTicket(int $id): JsonResponse
    {
        try {
            $result = $this->ticketService->finalizeTicket($id);

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            // Emitir evento WebSocket para finalización de reserva
            if (isset($result['data'])) {
                $ticket = $result['data'];
                ReservaStatusEvent::dispatch([
                    'ticket_id' => $ticket->id ?? $id,
                    'usuario_id' => $ticket->usuario_id ?? null,
                    'estacionamiento_id' => $ticket->estacionamiento_id ?? null,
                    'status' => 'finalizado',
                    'tipo_reserva' => $ticket->tipo_reserva ?? null,
                    'action' => 'finalized',
                    'timestamp' => now()->toISOString()
                ]);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error finalizando ticket: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Report a ticket (admin action)
     */
    public function reportTicket(int $id): JsonResponse
    {
        try {
            $result = $this->ticketService->reportTicket($id);

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error reportando ticket: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }
}
