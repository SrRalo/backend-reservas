<?php

namespace App\Services;

use App\Models\Vehiculo;
use App\Repositories\VehiculoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class VehiculoService
{
    protected VehiculoRepositoryInterface $vehiculoRepository;

    public function __construct(VehiculoRepositoryInterface $vehiculoRepository)
    {
        $this->vehiculoRepository = $vehiculoRepository;
    }

    /**
     * Get all vehicles
     */
    public function getAllVehiculos(): Collection
    {
        try {
            return $this->vehiculoRepository->getAll();
        } catch (\Exception $e) {
            throw new \Exception('Error al obtener vehículos: ' . $e->getMessage());
        }
    }

    /**
     * Get paginated vehicles
     */
    public function getPaginatedVehiculos(int $page = 1, int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        try {
            return $this->vehiculoRepository->getPaginated($page, $perPage, $filters);
        } catch (\Exception $e) {
            throw new \Exception('Error al obtener vehículos paginados: ' . $e->getMessage());
        }
    }

    /**
     * Get vehicle by ID
     */
    public function getVehiculoById(int $id): ?Vehiculo
    {
        try {
            return $this->vehiculoRepository->find($id);
        } catch (\Exception $e) {
            throw new \Exception('Error al obtener vehículo: ' . $e->getMessage());
        }
    }

    /**
     * Crear un nuevo vehículo
     */
    public function crearVehiculo(array $data): Vehiculo
    {
        try {
            return $this->vehiculoRepository->create($data);
        } catch (\Exception $e) {
            throw new \Exception('Error al crear vehículo: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar un vehículo existente
     */
    public function actualizarVehiculo(Vehiculo $vehiculo, array $data): Vehiculo
    {
        try {
            return $this->vehiculoRepository->update($vehiculo, $data);
        } catch (\Exception $e) {
            throw new \Exception('Error al actualizar vehículo: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar un vehículo
     */
    public function eliminarVehiculo(Vehiculo $vehiculo): bool
    {
        try {
            return $this->vehiculoRepository->delete($vehiculo);
        } catch (\Exception $e) {
            throw new \Exception('Error al eliminar vehículo: ' . $e->getMessage());
        }
    }

    /**
     * Get vehicle by license plate
     */
    public function getVehiculoByPlaca(string $placa): ?Vehiculo
    {
        try {
            return $this->vehiculoRepository->findByPlaca($placa);
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
            return $this->vehiculoRepository->getByUsuario($usuarioId);
        } catch (\Exception $e) {
            throw new \Exception('Error al obtener vehículos del usuario: ' . $e->getMessage());
        }
    }

    /**
     * Get vehicle statistics
     */
    public function getStatistics(): array
    {
        try {
            return $this->vehiculoRepository->getStatistics();
        } catch (\Exception $e) {
            throw new \Exception('Error al obtener estadísticas de vehículos: ' . $e->getMessage());
        }
    }

    /**
     * Validate vehicle data
     */
    public function validateVehicleData(array $data): array
    {
        $errors = [];

        // Validar placa
        if (!isset($data['placa']) || empty($data['placa'])) {
            $errors[] = 'La placa es requerida';
        } elseif (!preg_match('/^[A-Z]{3}-\d{3}$/', $data['placa'])) {
            $errors[] = 'La placa debe tener el formato ABC-123';
        }

        // Validar marca
        if (!isset($data['marca']) || empty($data['marca'])) {
            $errors[] = 'La marca es requerida';
        }

        // Validar modelo
        if (!isset($data['modelo']) || empty($data['modelo'])) {
            $errors[] = 'El modelo es requerido';
        }

        // Validar año
        if (!isset($data['año']) || !is_numeric($data['año'])) {
            $errors[] = 'El año debe ser un número';
        } elseif ($data['año'] < 1900 || $data['año'] > date('Y') + 1) {
            $errors[] = 'El año debe estar entre 1900 y ' . (date('Y') + 1);
        }

        // Validar color
        if (!isset($data['color']) || empty($data['color'])) {
            $errors[] = 'El color es requerido';
        }

        // Validar tipo
        if (!isset($data['tipo']) || empty($data['tipo'])) {
            $errors[] = 'El tipo es requerido';
        }

        return $errors;
    }
}
