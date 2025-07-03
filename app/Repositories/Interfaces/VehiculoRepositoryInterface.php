<?php


namespace App\Repositories\Interfaces;

use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Collection;

interface VehiculoRepositoryInterface extends BaseRepositoryInterface
{
    public function findByUsuario(int $usuarioId): Collection;
    public function findByPlaca(string $placa): ?Vehiculo;
    public function findByTipo(string $tipo): Collection;
    public function getVehiculosActivos(): Collection;
}