<?php

namespace App\Repositories\Eloquent;

use App\Models\EstacionamientoAdmin;
use App\Repositories\Interfaces\EstacionamientoAdminRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EstacionamientoAdminRepository extends BaseRepository implements EstacionamientoAdminRepositoryInterface
{
    public function __construct(EstacionamientoAdmin $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?EstacionamientoAdmin
    {
        return $this->model->where('email', $email)->first();
    }

    public function findByEstacionamiento(int $estacionamientoId): Collection
    {
        return $this->model->where('id', $estacionamientoId)->get();
    }

    public function findActiveAdmins(): Collection
    {
        return $this->model->where('estado', 'activo')->get();
    }

    public function updateEspaciosDisponibles(int $id, int $espacios): bool
    {
        return $this->model->where('id', $id)
                          ->update(['espacios_disponibles' => $espacios]);
    }

    public function incrementarReservas(int $id): bool
    {
        // No hacemos nada por ahora - el campo total_reservas no existe en la migración
        return true;
    }

    public function decrementarReservas(int $id): bool
    {
        // No hacemos nada por ahora - el campo total_reservas no existe en la migración
        return true;
    }

    public function getEstacionamientosConEspacios(): Collection
    {
        return $this->model->where('espacios_disponibles', '>', 0)->get();
    }

    public function getByUsuario(int $usuarioId): Collection
    {
        return $this->model->where('usuario_id', $usuarioId)->get();
    }

    public function getEstacionamientosActivos(): Collection
    {
        return $this->model->where('estado', 'activo')->get();
    }
}