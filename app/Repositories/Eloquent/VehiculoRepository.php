<?php


namespace App\Repositories\Eloquent;

use App\Models\Vehiculo;
use App\Repositories\Interfaces\VehiculoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class VehiculoRepository extends BaseRepository implements VehiculoRepositoryInterface
{
    public function __construct(Vehiculo $model)
    {
        parent::__construct($model);
    }

    public function findByUsuario(int $usuarioId): Collection
    {
        return $this->model->where('usuario_id', $usuarioId)->get();
    }

    public function findByPlaca(string $placa): ?Vehiculo
    {
        return $this->model->where('placa', $placa)->first();
    }

    public function findByTipo(string $tipo): Collection
    {
        return $this->model->where('tipo', $tipo)->get();
    }

    public function getVehiculosActivos(): Collection
    {
        return $this->model->where('estado', 'activo')->get();
    }

    public function getAll(): Collection
    {
        return $this->model->with(['usuario'])->get();
    }

    public function getPaginated(int $page = 1, int $perPage = 10, array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->model->with(['usuario']);

        // Aplicar filtros si existen
        if (!empty($filters['placa'])) {
            $query->where('placa', 'like', '%' . $filters['placa'] . '%');
        }
        if (!empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }
        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function hasActiveReservations(int $vehiculoId): bool
    {
        // Implementación simple - puedes ajustar según tu lógica de negocio
        return $this->model->where('id', $vehiculoId)
                          ->where('estado', 'activo')
                          ->exists();
    }

    public function getStatistics(): array
    {
        $total = $this->model->count();
        $activos = $this->model->where('estado', 'activo')->count();
        $inactivos = $this->model->where('estado', 'inactivo')->count();

        return [
            'total' => $total,
            'activos' => $activos,
            'inactivos' => $inactivos
        ];
    }

    public function getWithFilters(array $filters): Collection
    {
        $query = $this->model->with(['usuario']);

        if (!empty($filters['placa'])) {
            $query->where('placa', 'like', '%' . $filters['placa'] . '%');
        }
        if (!empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }
        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }
        if (!empty($filters['usuario_id'])) {
            $query->where('usuario_id', $filters['usuario_id']);
        }

        return $query->get();
    }
}