<?php


namespace App\Repositories\Interfaces;

use App\Models\UsuarioReserva;
use Illuminate\Database\Eloquent\Collection;

interface UsuarioReservaRepositoryInterface extends BaseRepositoryInterface
{
    public function findByEmail(string $email): ?UsuarioReserva;
    public function findByDocumento(string $documento): ?UsuarioReserva;
    public function findActiveUsers(): Collection;
    public function findByEstado(string $estado): Collection;
    public function searchByName(string $name): Collection;
    public function getUsersWithReservations(): Collection;
    public function updateLastLogin(int $userId): bool;
}