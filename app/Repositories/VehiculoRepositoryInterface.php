<?php

namespace App\Repositories;

use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface VehiculoRepositoryInterface
{
    /**
     * Get all vehicles
     */
    public function getAll(): Collection;

    /**
     * Get paginated vehicles
     */
    public function getPaginated(int $page = 1, int $perPage = 10, array $filters = []): LengthAwarePaginator;

    /**
     * Find vehicle by ID
     */
    public function find(int $id): ?Vehiculo;

    /**
     * Create vehicle
     */
    public function create(array $data): Vehiculo;

    /**
     * Update vehicle
     */
    public function update(int $id, array $data): ?Vehiculo;

    /**
     * Delete vehicle
     */
    public function delete(int $id): bool;

    /**
     * Find vehicle by license plate
     */
    public function findByPlaca(string $placa): ?Vehiculo;

    /**
     * Get vehicles by user
     */
    public function getByUsuario(int $usuarioId): Collection;

    /**
     * Check if vehicle has active reservations
     */
    public function hasActiveReservations(int $vehiculoId): bool;

    /**
     * Get vehicle statistics
     */
    public function getStatistics(): array;

    /**
     * Get vehicles with filters
     */
    public function getWithFilters(array $filters): Collection;
}
