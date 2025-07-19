<?php

namespace App\Services;

use App\Models\Vehiculo;
use App\Repositories\Interfaces\VehiculoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class VehiculoService extends BaseService
{
    public function __construct(VehiculoRepositoryInterface $vehiculoRepository)
    {
        $this->repository = $vehiculoRepository;
    }

    /**
     * Get vehicle by license plate
     */
    public function getVehiculoByPlaca(string $placa): ?Vehiculo
    {
        try {
            return $this->repository->findByPlaca($placa);
        } catch (\Exception $e) {
            throw new \Exception('Error al buscar vehículo por placa: ' . $e->getMessage());
        }
    }

    /**
     * Get vehicles by user
     */
    public function getVehiculosByUsuario(int $usuarioId): Collection
    {
        try {
            return $this->repository->getByUsuario($usuarioId);
        } catch (\Exception $e) {
            throw new \Exception('Error al obtener vehículos del usuario: ' . $e->getMessage());
        }
    }

    /**
     * Get active vehicles only
     */
    public function getActiveVehiculos(): Collection
    {
        try {
            return $this->repository->getByEstado('activo');
        } catch (\Exception $e) {
            throw new \Exception('Error al obtener vehículos activos: ' . $e->getMessage());
        }
    }

    /**
     * Check if vehicle exists by placa
     */
    public function existsByPlaca(string $placa): bool
    {
        try {
            return $this->repository->existsByPlaca($placa);
        } catch (\Exception $e) {
            throw new \Exception('Error al verificar existencia del vehículo: ' . $e->getMessage());
        }
    }

    /**
     * Activate vehicle
     */
    public function activateVehiculo(Vehiculo $vehiculo): Vehiculo
    {
        try {
            return $this->repository->update($vehiculo, ['estado' => 'activo']);
        } catch (\Exception $e) {
            throw new \Exception('Error al activar vehículo: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate vehicle
     */
    public function deactivateVehiculo(Vehiculo $vehiculo): Vehiculo
    {
        try {
            return $this->repository->update($vehiculo, ['estado' => 'inactivo']);
        } catch (\Exception $e) {
            throw new \Exception('Error al desactivar vehículo: ' . $e->getMessage());
        }
    }
}
