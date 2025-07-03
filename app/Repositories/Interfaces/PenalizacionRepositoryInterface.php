<?php


namespace App\Repositories\Interfaces;

use App\Models\Penalizacion;
use Illuminate\Database\Eloquent\Collection;

interface PenalizacionRepositoryInterface extends BaseRepositoryInterface
{
    public function findByUsuario(int $usuarioId): Collection;
    public function findByTicket(int $ticketId): Collection;
    public function findByEstado(string $estado): Collection;
    public function getPenalizacionesActivas(): Collection;
    public function marcarComoResuelta(int $penalizacionId): bool;
    public function getTotalPenalizacionesPorUsuario(int $usuarioId): float;
}