<?php

namespace App\Repositories\Interfaces;

use App\Models\Penalizacion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PenalizacionRepositoryInterface extends BaseRepositoryInterface
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
    ): LengthAwarePaginator;

    /**
     * Get penalizaciones with filters
     */
    public function getWithFilters(array $filters): Collection;

    /**
     * Get penalizaciones statistics
     */
    public function getStatistics(): array;

    /**
     * Mark penalty as paid
     */
    public function markAsPaid(int $id): ?Penalizacion;
}