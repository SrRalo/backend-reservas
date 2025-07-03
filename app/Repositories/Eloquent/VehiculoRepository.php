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
}