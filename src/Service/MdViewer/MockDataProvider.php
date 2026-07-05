<?php

declare(strict_types=1);

namespace App\Service\MdViewer;

/**
 * In-memory mock data provider for development/testing.
 * Can be swapped with DatabaseDataProvider or ApiDataProvider in production.
 */
final class MockDataProvider implements DataProviderInterface
{
    private const TOTAL_RECORDS = 150;

    public function getData(
        ?string $search = null,
        ?string $status = null,
        ?string $category = null,
        int $page = 1,
        int $perPage = 25,
        string $sortCol = 'id',
        string $sortDir = 'asc'
    ): array {
        $allData = $this->generateData();
        $filtered = $this->filter($allData, $search, $status, $category);
        $sorted = $this->sort($filtered, $sortCol, $sortDir);
        $paginated = $this->paginate($sorted, $page, $perPage);

        return [
            'data' => $paginated['data'],
            'total' => self::TOTAL_RECORDS,
            'filtered' => count($filtered),
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $paginated['totalPages'],
            'sortCol' => $sortCol,
            'sortDir' => $sortDir,
        ];
    }

    /** 
     * @return list<object> 
     * @throws \Exception
     */
    private function generateData(): array
    {
        $statuses = ['active', 'draft', 'archived'];
        $categories = ['docs', 'api', 'config'];
        $data = [];

        for ($i = 1; $i <= self::TOTAL_RECORDS; $i++) {
            $data[] = (object) [
                'id' => str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'title' => sprintf('document_%s.md', str_pad((string) $i, 3, '0', STR_PAD_LEFT)),
                'status' => $statuses[array_rand($statuses)],
                'category' => $categories[array_rand($categories)],
                'modified' => date('Y-m-d', time() - random_int(0, 86400 * 30)),
                'size' => sprintf('%d KB', random_int(1, 50)),
            ];
        }

        return $data;
    }

    /**
     * @param list<object> $data
     *
     * @return list<object>
     */
    private function filter(array $data, ?string $search, ?string $status, ?string $category): array
    {
        return array_values(array_filter($data, function ($item) use ($search, $status, $category) {
            $matchesSearch = $search === null || $search === '' ||
                str_contains(strtolower($item->title), strtolower($search)) ||
                str_contains($item->id, $search);

            $matchesStatus = $status === null || $status === '' || $item->status === $status;
            $matchesCategory = $category === null || $category === '' || $item->category === $category;

            return $matchesSearch && $matchesStatus && $matchesCategory;
        }));
    }

    /**
     * @param list<object> $data
     *
     * @return list<object>
     */
    private function sort(array $data, string $sortCol, string $sortDir): array
    {
        usort($data, function ($a, $b) use ($sortCol, $sortDir) {
            $valA = $a->$sortCol ?? '';
            $valB = $b->$sortCol ?? '';

            if ($sortCol === 'size') {
                $valA = (int) $valA;
                $valB = (int) $valB;
            }

            if ($valA === $valB) {
                return 0;
            }

            $result = $valA < $valB ? -1 : 1;

            return $sortDir === 'asc' ? $result : -$result;
        });

        return $data;
    }

    /**
     * @param list<object> $data
     *
     * @return array{data: list<object>, totalPages: int}
     */
    private function paginate(array $data, int $page, int $perPage): array
    {
        $total = count($data);
        $totalPages = (int) ceil($total / $perPage) ?: 1;
        $page = max(1, min($page, $totalPages));

        $start = ($page - 1) * $perPage;

        return [
            'data' => array_slice($data, $start, $perPage),
            'totalPages' => $totalPages,
        ];
    }
}
