<?php

declare(strict_types=1);

namespace App\Service\MdViewer;

/**
 * Abstraction for data sources - enables OCP (Open/Closed Principle).
 * New sources (DB, API, File) can be added without changing Controller.
 */
interface DataProviderInterface
{
    /**
     * Returns paginated, filtered, sorted data with metadata.
     *
     * @return array<string, mixed> Keys: data, total, filtered, page, perPage, totalPages, sortCol, sortDir
     */
    public function getData(
        ?string $search = null,
        ?string $status = null,
        ?string $category = null,
        int $page = 1,
        int $perPage = 25,
        string $sortCol = 'id',
        string $sortDir = 'asc'
    ): array;
}
