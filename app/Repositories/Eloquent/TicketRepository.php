<?php


namespace App\Repositories\Eloquent;

use App\Models\Ticket;
use App\Repositories\Interfaces\TicketRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TicketRepository extends BaseRepository implements TicketRepositoryInterface
{
    public function __construct(Ticket $model)
    {
        parent::__construct($model);
    }

    public function findActiveTickets(): Collection
    {
        return $this->model->where('estado', 'activo')->get();
    }

    public function findByUsuario(int $usuarioId): Collection
    {
        return $this->model->where('usuario_id', $usuarioId)->get();
    }

    public function findByVehiculo(int $vehiculoId): Collection
    {
        return $this->model->where('vehiculo_id', $vehiculoId)->get();
    }

    public function findByEstacionamiento(int $estacionamientoId): Collection
    {
        return $this->model->where('estacionamiento_id', $estacionamientoId)->get();
    }

    public function findByCodigo(string $codigo): ?Ticket
    {
        return $this->model->where('codigo_ticket', $codigo)->first();
    }

    public function finalizarTicket(int $ticketId, float $monto): bool
    {
        $ticket = $this->find($ticketId);
        if ($ticket) {
            $ticket->update([
                'fecha_salida' => now(),
                'precio_total' => $monto,  // Usar precio_total en lugar de monto
                'estado' => 'finalizado'
            ]);
            return true;
        }
        return false;
    }

    public function getTicketsByDateRange(string $fechaInicio, string $fechaFin): Collection
    {
        return $this->model->whereBetween('fecha_entrada', [$fechaInicio, $fechaFin])->get();
    }

    public function getTicketsByEstado(string $estado): Collection
    {
        return $this->model->where('estado', $estado)->get();
    }

    public function getTicketsActivosByUsuario(int $usuarioId): Collection
    {
        return $this->model->where('usuario_id', $usuarioId)
                          ->where('estado', 'activo')
                          ->get();
    }

    public function getIngresosPorEstacionamiento(int $estacionamientoId): float
    {
        return $this->model->where('estacionamiento_id', $estacionamientoId)
                          ->where('estado', 'pagado')
                          ->sum('monto');
    }
}