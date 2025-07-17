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
    
    /**
     * Métodos adicionales para mejoras críticas
     */
    public function getPaginated(int $perPage = 15, int $page = 1): array;
    public function getWithFilters(array $filters, ?string $search = null, int $perPage = 15): array;
    public function getByType(string $type): Collection;
    public function search(string $query): Collection;
    public function getStatistics(): array;
    public function getPendingPenalties(): Collection;
    public function markAsPaid(int $id): bool;
    public function cancel(int $id): bool;
}