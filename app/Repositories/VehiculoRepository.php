<?php

namespace App\Repositories;

use App\Models\Vehiculo;
use App\Repositories\VehiculoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class VehiculoRepository implements VehiculoRepositoryInterface
{
    protected Vehiculo $model;

    public function __construct(Vehiculo $model)
    {
        $this->model = $model;
    }

    /**
     * Get all vehicles
     */
    public function getAll(): Collection
    {
        return $this->model->with(['usuario', 'reservas' => function ($query) {
            $query->where('estado', 'activa');
        }])->get();
    }

    /**
     * Get paginated vehicles
     */
    public function getPaginated(int $page = 1, int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->with(['usuario', 'reservas' => function ($query) {
            $query->where('estado', 'activa');
        }]);

        // Apply filters
        if (!empty($filters['usuario_id'])) {
            $query->where('usuario_id', $filters['usuario_id']);
        }

        if (!empty($filters['marca'])) {
            $query->where('marca', 'like', '%' . $filters['marca'] . '%');
        }

        if (!empty($filters['modelo'])) {
            $query->where('modelo', 'like', '%' . $filters['modelo'] . '%');
        }

        if (!empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        if (!empty($filters['placa'])) {
            $query->where('placa', 'like', '%' . $filters['placa'] . '%');
        }

        if (!empty($filters['año_desde'])) {
            $query->where('año', '>=', $filters['año_desde']);
        }

        if (!empty($filters['año_hasta'])) {
            $query->where('año', '<=', $filters['año_hasta']);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Find vehicle by ID
     */
    public function find(int $id): ?Vehiculo
    {
        return $this->model->with(['usuario', 'reservas', 'tickets'])->find($id);
    }

    /**
     * Create vehicle
     */
    public function create(array $data): Vehiculo
    {
        return $this->model->create($data);
    }

    /**
     * Update vehicle
     */
    public function update(int $id, array $data): ?Vehiculo
    {
        $vehiculo = $this->find($id);
        if (!$vehiculo) {
            return null;
        }

        $vehiculo->update($data);
        return $vehiculo->fresh();
    }

    /**
     * Delete vehicle
     */
    public function delete(int $id): bool
    {
        $vehiculo = $this->find($id);
        if (!$vehiculo) {
            return false;
        }

        return $vehiculo->delete();
    }

    /**
     * Find vehicle by license plate
     */
    public function findByPlaca(string $placa): ?Vehiculo
    {
        return $this->model->where('placa', $placa)->first();
    }

    /**
     * Get vehicles by user
     */
    public function getByUsuario(int $usuarioId): Collection
    {
        return $this->model->where('usuario_id', $usuarioId)
            ->with(['reservas' => function ($query) {
                $query->where('estado', 'activa');
            }])
            ->get();
    }

    /**
     * Check if vehicle has active reservations
     */
    public function hasActiveReservations(int $vehiculoId): bool
    {
        return $this->model->where('id', $vehiculoId)
            ->whereHas('reservas', function ($query) {
                $query->where('estado', 'activa');
            })
            ->exists();
    }

    /**
     * Get vehicle statistics
     */
    public function getStatistics(): array
    {
        $total = $this->model->count();
        $byTipo = $this->model->groupBy('tipo')->selectRaw('tipo, COUNT(*) as count')->get();
        $byMarca = $this->model->groupBy('marca')->selectRaw('marca, COUNT(*) as count')->get();
        $withActiveReservations = $this->model->whereHas('reservas', function ($query) {
            $query->where('estado', 'activa');
        })->count();

        return [
            'total' => $total,
            'con_reservas_activas' => $withActiveReservations,
            'disponibles' => $total - $withActiveReservations,
            'por_tipo' => $byTipo->toArray(),
            'por_marca' => $byMarca->toArray()
        ];
    }

    /**
     * Get vehicles with filters
     */
    public function getWithFilters(array $filters): Collection
    {
        $query = $this->model->with(['usuario', 'reservas']);

        // Apply filters
        if (!empty($filters['usuario_id'])) {
            $query->where('usuario_id', $filters['usuario_id']);
        }

        if (!empty($filters['marca'])) {
            $query->where('marca', 'like', '%' . $filters['marca'] . '%');
        }

        if (!empty($filters['modelo'])) {
            $query->where('modelo', 'like', '%' . $filters['modelo'] . '%');
        }

        if (!empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        if (!empty($filters['disponible'])) {
            if ($filters['disponible'] === 'si') {
                $query->whereDoesntHave('reservas', function ($subQuery) {
                    $subQuery->where('estado', 'activa');
                });
            } else {
                $query->whereHas('reservas', function ($subQuery) {
                    $subQuery->where('estado', 'activa');
                });
            }
        }

        return $query->get();
    }
}
