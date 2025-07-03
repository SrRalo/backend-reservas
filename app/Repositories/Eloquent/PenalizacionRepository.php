<?php


namespace App\Repositories\Eloquent;

use App\Models\Penalizacion;
use App\Repositories\Interfaces\PenalizacionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PenalizacionRepository extends BaseRepository implements PenalizacionRepositoryInterface
{
    public function __construct(Penalizacion $model)
    {
        parent::__construct($model);
    }

    public function findByUsuario(int $usuarioId): Collection
    {
        return $this->model->whereHas('ticket', function ($query) use ($usuarioId) {
            $query->where('usuario_id', $usuarioId);
        })->get();
    }

    public function findByTicket(int $ticketId): Collection
    {
        return $this->model->where('ticket_id', $ticketId)->get();
    }

    public function findByEstado(string $estado): Collection
    {
        return $this->model->where('estado', $estado)->get();
    }

    public function getPenalizacionesActivas(): Collection
    {
        return $this->model->where('estado', 'activa')->get();
    }

    public function marcarComoResuelta(int $penalizacionId): bool
    {
        return $this->model->where('id', $penalizacionId)
                          ->update(['estado' => 'resuelta']);
    }

    public function getTotalPenalizacionesPorUsuario(int $usuarioId): float
    {
        return $this->model->whereHas('ticket', function ($query) use ($usuarioId) {
            $query->where('usuario_id', $usuarioId);
        })->where('estado', 'activa')->sum('monto');
    }
}