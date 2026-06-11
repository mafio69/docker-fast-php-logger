<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\MdViewer\DataProviderInterface;

/**
 * Facade service - delegates data operations to injected provider.
 * Provider can be swapped (mock → database → api) without changing this service.
 */
readonly class MdViewerService
{
    public function __construct(
        private DataProviderInterface $provider,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(
        ?string $search = null,
        ?string $status = null,
        ?string $category = null,
        int $page = 1,
        int $perPage = 25,
        string $sortCol = 'id',
        string $sortDir = 'asc'
    ): array {
        return $this->provider->getData($search, $status, $category, $page, $perPage, $sortCol, $sortDir);
    }
}
