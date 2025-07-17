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

    /**
     * Métodos adicionales para mejoras críticas
     */
    public function getPaginated(int $perPage = 15, int $page = 1): array
    {
        $query = $this->model->with(['ticket', 'ticket.usuario', 'ticket.vehiculo']);
        
        $paginated = $query->paginate($perPage, ['*'], 'page', $page);
        
        return [
            'data' => $paginated->items(),
            'total' => $paginated->total(),
            'per_page' => $paginated->perPage(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'from' => $paginated->firstItem(),
            'to' => $paginated->lastItem()
        ];
    }

    public function getWithFilters(array $filters, ?string $search = null, int $perPage = 15): array
    {
        $query = $this->model->with(['ticket', 'ticket.usuario', 'ticket.vehiculo']);

        // Aplicar filtros
        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        if (!empty($filters['tipo_penalizacion'])) {
            $query->where('tipo', $filters['tipo_penalizacion']);
        }

        if (!empty($filters['usuario_id'])) {
            $query->whereHas('ticket', function ($q) use ($filters) {
                $q->where('usuario_id', $filters['usuario_id']);
            });
        }

        if (!empty($filters['fecha_desde'])) {
            $query->whereDate('fecha', '>=', $filters['fecha_desde']);
        }

        if (!empty($filters['fecha_hasta'])) {
            $query->whereDate('fecha', '<=', $filters['fecha_hasta']);
        }

        // Aplicar búsqueda
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('motivo', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%")
                  ->orWhereHas('ticket.usuario', function ($userQuery) use ($search) {
                      $userQuery->where('nombre', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $paginated = $query->paginate($perPage);
        
        return [
            'data' => $paginated->items(),
            'total' => $paginated->total(),
            'per_page' => $paginated->perPage(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'from' => $paginated->firstItem(),
            'to' => $paginated->lastItem()
        ];
    }

    public function getByType(string $type): Collection
    {
        return $this->model->where('tipo', $type)->get();
    }

    public function search(string $query): Collection
    {
        return $this->model->where('motivo', 'like', "%{$query}%")
                          ->orWhere('descripcion', 'like', "%{$query}%")
                          ->get();
    }

    public function getStatistics(): array
    {
        return [
            'total_penalizaciones' => $this->model->count(),
            'penalizaciones_activas' => $this->model->where('estado', 'activa')->count(),
            'penalizaciones_pagadas' => $this->model->where('estado', 'pagada')->count(),
            'penalizaciones_canceladas' => $this->model->where('estado', 'cancelada')->count(),
            'monto_total_pendiente' => $this->model->where('estado', 'activa')->sum('monto'),
            'monto_total_pagado' => $this->model->where('estado', 'pagada')->sum('monto'),
            'por_tipo' => [
                'tiempo_excedido' => $this->model->where('tipo', 'tiempo_excedido')->count(),
                'dano_propiedad' => $this->model->where('tipo', 'dano_propiedad')->count(),
                'mal_estacionamiento' => $this->model->where('tipo', 'mal_estacionamiento')->count(),
            ]
        ];
    }

    public function getPendingPenalties(): Collection
    {
        return $this->model->where('estado', 'pendiente')
                          ->orWhere('estado', 'activa')
                          ->get();
    }

    public function markAsPaid(int $id): bool
    {
        return $this->model->where('id', $id)
                          ->update(['estado' => 'pagada', 'fecha_pago' => now()]);
    }

    public function cancel(int $id): bool
    {
        return $this->model->where('id', $id)
                          ->update(['estado' => 'cancelada', 'fecha_cancelacion' => now()]);
    }
}