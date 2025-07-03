<?php


namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface TicketRepositoryInterface extends BaseRepositoryInterface
{
    public function findActiveTickets(): Collection;
    
    public function findByUsuario(int $usuarioId): Collection;
    
    public function findByVehiculo(int $vehiculoId): Collection;
    
    public function findByEstacionamiento(int $estacionamientoId): Collection;
    
    public function findByCodigo(string $codigo): ?\App\Models\Ticket;
    
    public function finalizarTicket(int $ticketId, float $monto): bool;
    
    public function getTicketsByDateRange(string $fechaInicio, string $fechaFin): Collection;
    
    public function getTicketsByEstado(string $estado): Collection;
}