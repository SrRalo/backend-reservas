<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseService
{
    protected $repository;

    public function __construct($repository = null)
    {
        $this->repository = $repository;
    }

    /**
     * Get all records
     */
    public function getAll(): Collection
    {
        try {
            return $this->repository->getAll();
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener registros: " . $e->getMessage());
        }
    }

    /**
     * Get paginated records
     */
    public function getPaginated(int $page = 1, int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        try {
            return $this->repository->getPaginated($page, $perPage, $filters);
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener registros paginados: " . $e->getMessage());
        }
    }

    /**
     * Get record by ID
     */
    public function getById(int $id): ?Model
    {
        try {
            return $this->repository->find($id);
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener registro: " . $e->getMessage());
        }
    }

    /**
     * Create new record
     */
    public function create(array $data): Model
    {
        try {
            return $this->repository->create($data);
        } catch (\Exception $e) {
            throw new \Exception("Error al crear registro: " . $e->getMessage());
        }
    }

    /**
     * Update existing record
     */
    public function update(Model $model, array $data): Model
    {
        try {
            return $this->repository->update($model, $data);
        } catch (\Exception $e) {
            throw new \Exception("Error al actualizar registro: " . $e->getMessage());
        }
    }

    /**
     * Delete record
     */
    public function delete(Model $model): bool
    {
        try {
            return $this->repository->delete($model);
        } catch (\Exception $e) {
            throw new \Exception("Error al eliminar registro: " . $e->getMessage());
        }
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        try {
            return $this->repository->getStatistics();
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener estadísticas: " . $e->getMessage());
        }
    }
}
