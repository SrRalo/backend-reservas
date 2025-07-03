<?php


namespace App\Repositories\Eloquent;

use App\Models\Pago;
use App\Repositories\Interfaces\PagoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class PagoRepository extends BaseRepository implements PagoRepositoryInterface
{
    public function __construct(Pago $model)
    {
        parent::__construct($model);
    }

    public function findByTicket(int $ticketId): Collection
    {
        return $this->model->where('ticket_id', $ticketId)->get();
    }

    public function findByEstado(string $estado): Collection
    {
        return $this->model->where('estado', $estado)->get();
    }

    public function findByUsuario(int $usuarioId): Collection
    {
        return $this->model->whereHas('ticket', function ($query) use ($usuarioId) {
            $query->where('usuario_id', $usuarioId);
        })->get();
    }

    public function findByFechaRango(Carbon $fechaInicio, Carbon $fechaFin): Collection
    {
        return $this->model->whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
                          ->get();
    }

    public function marcarComoPagado(int $pagoId): bool
    {
        return $this->model->where('id', $pagoId)
                          ->update([
                              'estado' => 'pagado',
                              'fecha_pago' => now()
                          ]);
    }

    public function getPagosPendientes(): Collection
    {
        return $this->model->where('estado', 'pendiente')->get();
    }

    public function getTotalPagadoPorUsuario(int $usuarioId): float
    {
        return $this->model->whereHas('ticket', function ($query) use ($usuarioId) {
            $query->where('usuario_id', $usuarioId);
        })->where('estado', 'pagado')->sum('monto');
    }
}