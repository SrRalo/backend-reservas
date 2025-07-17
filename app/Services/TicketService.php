<?php

namespace App\Services;

use App\Repositories\Interfaces\TicketRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TicketService
{
    private TicketRepositoryInterface $ticketRepository;

    public function __construct(TicketRepositoryInterface $ticketRepository)
    {
        $this->ticketRepository = $ticketRepository;
    }

    /**
     * Finalizar un ticket
     */
    public function finalizeTicket(int $ticketId): array
    {
        try {
            // Obtener ticket
            $ticket = $this->ticketRepository->find($ticketId);

            if (!$ticket) {
                return [
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ];
            }

            // Validar estado
            if (!$this->canFinalizeTicket($ticket)) {
                return [
                    'success' => false,
                    'message' => 'Solo se pueden finalizar tickets activos'
                ];
            }

            // Actualizar ticket
            $updatedTicket = $this->ticketRepository->update($ticketId, [
                'estado' => 'finalizado',
                'fecha_salida' => Carbon::now()
            ]);

            return [
                'success' => true,
                'data' => $updatedTicket,
                'message' => 'Ticket finalizado exitosamente'
            ];

        } catch (\Exception $e) {
            Log::error('Error en finalizeTicket: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Reportar un ticket
     */
    public function reportTicket(int $ticketId): array
    {
        try {
            // Obtener ticket
            $ticket = $this->ticketRepository->find($ticketId);

            if (!$ticket) {
                return [
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ];
            }

            // Validar estado
            if (!$this->canReportTicket($ticket)) {
                return [
                    'success' => false,
                    'message' => 'Solo se pueden reportar tickets activos'
                ];
            }

            // Actualizar ticket
            $updatedTicket = $this->ticketRepository->update($ticketId, [
                'estado' => 'cancelado',
                'fecha_salida' => Carbon::now()
            ]);

            return [
                'success' => true,
                'data' => $updatedTicket,
                'message' => 'Ticket reportado exitosamente'
            ];

        } catch (\Exception $e) {
            Log::error('Error en reportTicket: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Validar si un ticket puede ser finalizado
     */
    private function canFinalizeTicket($ticket): bool
    {
        return $ticket->estado === 'activo';
    }

    /**
     * Validar si un ticket puede ser reportado
     */
    private function canReportTicket($ticket): bool
    {
        return $ticket->estado === 'activo';
    }
}
