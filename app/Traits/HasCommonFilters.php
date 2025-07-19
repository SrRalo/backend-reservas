<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasCommonFilters
{
    /**
     * Apply common filters to query
     */
    protected function applyCommonFilters(Builder $query, array $filters): Builder
    {
        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        if (!empty($filters['usuario_id'])) {
            $query->where('usuario_id', $filters['usuario_id']);
        }

        if (!empty($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }

        if (!empty($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }

        if (!empty($filters['search'])) {
            $this->applySearchFilter($query, $filters['search']);
        }

        return $query;
    }

    /**
     * Apply sorting to query
     */
    protected function applySorting(Builder $query, array $filters): Builder
    {
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        // Validate sort fields to prevent SQL injection
        $allowedSortFields = $this->getAllowedSortFields();
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    /**
     * Apply search filter - to be implemented by classes using this trait
     */
    abstract protected function applySearchFilter(Builder $query, string $search): Builder;

    /**
     * Get allowed sort fields - to be implemented by classes using this trait
     */
    abstract protected function getAllowedSortFields(): array;

    /**
     * Apply pagination with filters
     */
    protected function applyFiltersAndPagination(Builder $query, array $filters, int $perPage = 10)
    {
        $query = $this->applyCommonFilters($query, $filters);
        $query = $this->applySorting($query, $filters);

        return $query->paginate($perPage);
    }
}
