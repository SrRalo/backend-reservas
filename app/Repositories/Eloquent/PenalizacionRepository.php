<?php

namespace App\Repositories\Eloquent;

use App\Models\Penalizacion;
use App\Repositories\Interfaces\PenalizacionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PenalizacionRepository extends BaseRepository implements PenalizacionRepositoryInterface
{
    public function __construct(Penalizacion $model)
    {
        parent::__construct($model);
    }

    public function getPaginated(
        int $page = 1, 
        int $perPage = 10, 
        array $filters = [],
        ?string $sortBy = null,
        string $sortOrder = 'desc'
    ): LengthAwarePaginator {
        $query = $this->model->query();

        // Aplicar filtros
        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }
        if (!empty($filters['usuario_id'])) {
            $query->where('usuario_id', $filters['usuario_id']);
        }

        // Aplicar ordenamiento
        if ($sortBy) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function getWithFilters(array $filters): Collection
    {
        $query = $this->model->query();

        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }
        if (!empty($filters['usuario_id'])) {
            $query->where('usuario_id', $filters['usuario_id']);
        }

        return $query->get();
    }

    public function getStatistics(): array
    {
        $total = $this->model->count();
        $pagadas = $this->model->where('estado', 'pagada')->count();
        $pendientes = $this->model->where('estado', 'pendiente')->count();

        return [
            'total' => $total,
            'pagadas' => $pagadas,
            'pendientes' => $pendientes
        ];
    }

    public function markAsPaid(int $id): ?Penalizacion
    {
        $penalizacion = $this->find($id);
        if ($penalizacion) {
            $penalizacion->update([
                'estado' => 'pagada',
                'fecha_pago' => now()
            ]);
            return $penalizacion->fresh();
        }
        return null;
    }
}