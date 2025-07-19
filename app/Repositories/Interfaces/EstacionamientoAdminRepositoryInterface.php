<?php


namespace App\Repositories\Interfaces;

use App\Models\EstacionamientoAdmin;
use Illuminate\Database\Eloquent\Collection;

interface EstacionamientoAdminRepositoryInterface extends BaseRepositoryInterface
{
    public function findByEmail(string $email): ?EstacionamientoAdmin;
    public function findByEstacionamiento(int $estacionamientoId): Collection;
    public function findActiveAdmins(): Collection;
    public function updateEspaciosDisponibles(int $id, int $espacios): bool;
    public function incrementarReservas(int $id): bool;
    public function decrementarReservas(int $id): bool;
    public function getEstacionamientosConEspacios(): Collection;
    public function getByUsuario(int $usuarioId): Collection;
    public function getEstacionamientosActivos(): Collection;
}