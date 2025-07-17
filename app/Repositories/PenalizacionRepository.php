<?php

namespace App\Repositories;

use App\Models\Penalizacion;
use Illuminate\Pagination\LengthAwarePaginator;

class PenalizacionRepository
{
    /**
     * Get paginated penalizaciones with advanced filtering and sorting
     */
    public function getPaginated(
        int $page = 1,
        int $perPage = 10,
        array $filters = [],
        ?string $sortBy = null,
        string $sortOrder = 'desc'
    ): LengthAwarePaginator {
        try {
            $query = Penalizacion::with(['ticket', 'usuario', 'estacionamiento']);

            // Apply filters
            if (!empty($filters['estado'])) {
                $query->where('estado', $filters['estado']);
            }

            if (!empty($filters['tipo_penalizacion'])) {
                $query->where('tipo_penalizacion', $filters['tipo_penalizacion']);
            }

            if (!empty($filters['usuario_id'])) {
                $query->where('usuario_id', $filters['usuario_id']);
            }

            if (!empty($filters['fecha_desde'])) {
                $query->whereDate('fecha_penalizacion', '>=', $filters['fecha_desde']);
            }

            if (!empty($filters['fecha_hasta'])) {
                $query->whereDate('fecha_penalizacion', '<=', $filters['fecha_hasta']);
            }

            if (!empty($filters['monto_min'])) {
                $query->where('monto', '>=', $filters['monto_min']);
            }

            if (!empty($filters['monto_max'])) {
                $query->where('monto', '<=', $filters['monto_max']);
            }

            if (!empty($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('descripcion', 'like', '%' . $filters['search'] . '%')
                      ->orWhereHas('usuario', function ($userQuery) use ($filters) {
                          $userQuery->where('nombre', 'like', '%' . $filters['search'] . '%')
                                   ->orWhere('email', 'like', '%' . $filters['search'] . '%');
                      });
                });
            }

            // Apply sorting
            $sortableFields = ['id', 'fecha_penalizacion', 'monto', 'estado', 'tipo_penalizacion', 'created_at'];
            $sortBy = in_array($sortBy, $sortableFields) ? $sortBy : 'created_at';
            $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'desc';

            $query->orderBy($sortBy, $sortOrder);

            return $query->paginate($perPage, ['*'], 'page', $page);
        } catch (\Exception $e) {
            throw new \Exception('Error al obtener penalizaciones paginadas: ' . $e->getMessage());
        }
    }

    /**
     * Create penalization
     */
    public function create(array $data): Penalizacion
    {
        try {
            return Penalizacion::create($data);
        } catch (\Exception $e) {
            throw new \Exception('Error al crear penalización: ' . $e->getMessage());
        }
    }

    /**
     * Mark penalty as paid
     */
    public function markAsPaid(int $id): ?Penalizacion
    {
        try {
            $penalizacion = $this->find($id);
            if (!$penalizacion) {
                return null;
            }

            $penalizacion->update([
                'estado' => 'pagada',
                'fecha_pago' => now()
            ]);

            return $penalizacion->fresh();
        } catch (\Exception $e) {
            throw new \Exception('Error al marcar penalización como pagada: ' . $e->getMessage());
        }
    }

    /**
     * Update penalization
     */
    public function update(int $id, array $data): ?Penalizacion
    {
        try {
            $penalizacion = $this->find($id);
            if (!$penalizacion) {
                return null;
            }

            $penalizacion->update($data);
            return $penalizacion->fresh();
        } catch (\Exception $e) {
            throw new \Exception('Error al actualizar penalización: ' . $e->getMessage());
        }
    }

    /**
     * Delete penalization
     */
    public function delete(int $id): bool
    {
        try {
            $penalizacion = $this->find($id);
            if (!$penalizacion) {
                return false;
            }

            return $penalizacion->delete();
        } catch (\Exception $e) {
            throw new \Exception('Error al eliminar penalización: ' . $e->getMessage());
        }
    }

    /**
     * Find penalization by ID
     */
    public function find(int $id): ?Penalizacion
    {
        try {
            return Penalizacion::with(['ticket', 'usuario', 'estacionamiento'])
                ->find($id);
        } catch (\Exception $e) {
            throw new \Exception('Error al buscar penalización: ' . $e->getMessage());
        }
    }
}