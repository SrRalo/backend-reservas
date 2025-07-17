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

    /**
     * Update penalization
     */
    public function update(int $id, array $data): ?Penalizacion;

    /**
     * Delete penalization
     */
    public function delete(int $id): bool;

    /**
     * Create penalization
     */
    public function create(array $data): Penalizacion;

    /**
     * Find penalization by ID
     */
    public function find(int $id): ?Penalizacion;