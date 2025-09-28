<?php

declare(strict_types=1);

namespace Examples\Techniques\PaginationPerformance;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Log\LoggerInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Service de pagination selon les bonnes pratiques du projet Gyroscops Cloud
 * 
 * Ce service implémente une pagination robuste et performante
 * en respectant les ADR HIVE037, HIVE039 et HIVE035.
 */
final class PaginationService
{
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger,
        private CacheItemPoolInterface $cache,
        private MetricsCollector $metrics
    ) {}

    /**
     * Pagination par offset avec cache
     */
    public function paginateWithOffset(
        string $table,
        PaginationRequest $request,
        array $filters = [],
        array $sorting = []
    ): PaginationResponse {
        $startTime = microtime(true);
        
        $this->logger->info('Starting offset pagination', [
            'table' => $table,
            'page' => $request->page,
            'page_size' => $request->pageSize,
            'filters' => $this->sanitizeFilters($filters),
            'sorting' => $sorting
        ]);

        // Generate cache key
        $cacheKey = $this->generateCacheKey($table, 'offset', $request, $filters, $sorting);
        
        // Try to get from cache
        $cached = $this->cache->getItem($cacheKey);
        if ($cached->isHit()) {
            $this->metrics->incrementCounter('pagination_cache_hits_total', [
                'type' => 'offset',
                'table' => $table
            ]);
            
            $this->logger->info('Pagination data retrieved from cache', [
                'cache_key' => $cacheKey
            ]);
            
            return $cached->get();
        }

        $this->metrics->incrementCounter('pagination_cache_misses_total', [
            'type' => 'offset',
            'table' => $table
        ]);

        // Build query
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($table);

        // Apply filters
        $this->applyFilters($queryBuilder, $filters);

        // Apply sorting
        $this->applySorting($queryBuilder, $sorting);

        // Get total count
        $totalItems = $this->getTotalCount($table, $filters);

        // Apply pagination
        $offset = ($request->page - 1) * $request->pageSize;
        $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($request->pageSize);

        // Execute query
        $result = $queryBuilder->executeQuery();
        $rows = $result->fetchAllAssociative();

        // Calculate pagination metadata
        $totalPages = (int) ceil($totalItems / $request->pageSize);
        $hasNextPage = $request->page < $totalPages;
        $hasPreviousPage = $request->page > 1;

        $response = new PaginationResponse(
            data: $rows,
            page: $request->page,
            pageSize: $request->pageSize,
            totalItems: $totalItems,
            totalPages: $totalPages,
            hasNextPage: $hasNextPage,
            hasPreviousPage: $hasPreviousPage
        );

        // Cache the response
        $cacheItem = $this->cache->getItem($cacheKey);
        $cacheItem->set($response);
        $cacheItem->expiresAfter(300); // 5 minutes
        $this->cache->save($cacheItem);

        $duration = microtime(true) - $startTime;
        
        $this->metrics->observeHistogram('pagination_duration_seconds', $duration, [
            'type' => 'offset',
            'table' => $table,
            'page_size' => $request->pageSize
        ]);

        $this->logger->info('Offset pagination completed', [
            'duration' => $duration,
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
            'cache_key' => $cacheKey
        ]);

        return $response;
    }

    /**
     * Pagination par curseur avec cache
     */
    public function paginateWithCursor(
        string $table,
        CursorPaginationRequest $request,
        array $filters = [],
        array $sorting = []
    ): CursorPaginationResponse {
        $startTime = microtime(true);
        
        $this->logger->info('Starting cursor pagination', [
            'table' => $table,
            'cursor' => $request->cursor?->toString(),
            'page_size' => $request->pageSize,
            'filters' => $this->sanitizeFilters($filters),
            'sorting' => $sorting
        ]);

        // Generate cache key
        $cacheKey = $this->generateCacheKey($table, 'cursor', $request, $filters, $sorting);
        
        // Try to get from cache
        $cached = $this->cache->getItem($cacheKey);
        if ($cached->isHit()) {
            $this->metrics->incrementCounter('pagination_cache_hits_total', [
                'type' => 'cursor',
                'table' => $table
            ]);
            
            $this->logger->info('Cursor pagination data retrieved from cache', [
                'cache_key' => $cacheKey
            ]);
            
            return $cached->get();
        }

        $this->metrics->incrementCounter('pagination_cache_misses_total', [
            'type' => 'cursor',
            'table' => $table
        ]);

        // Build query
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($table);

        // Apply filters
        $this->applyFilters($queryBuilder, $filters);

        // Apply cursor-based pagination
        $this->applyCursorPagination($queryBuilder, $request, $sorting);

        // Apply sorting
        $this->applySorting($queryBuilder, $sorting);

        // Execute query
        $result = $queryBuilder->executeQuery();
        $rows = $result->fetchAllAssociative();

        // Calculate cursor metadata
        $hasNextPage = count($rows) === $request->pageSize;
        $hasPreviousPage = $request->cursor !== null;

        // Generate cursors
        $nextCursor = $hasNextPage && !empty($rows) 
            ? $this->generateCursor(end($rows), $sorting)
            : null;
        
        $previousCursor = $hasPreviousPage && !empty($rows)
            ? $this->generateCursor(reset($rows), $sorting)
            : null;

        $response = new CursorPaginationResponse(
            data: $rows,
            pageSize: $request->pageSize,
            hasNextPage: $hasNextPage,
            hasPreviousPage: $hasPreviousPage,
            nextCursor: $nextCursor,
            previousCursor: $previousCursor
        );

        // Cache the response
        $cacheItem = $this->cache->getItem($cacheKey);
        $cacheItem->set($response);
        $cacheItem->expiresAfter(300); // 5 minutes
        $this->cache->save($cacheItem);

        $duration = microtime(true) - $startTime;
        
        $this->metrics->observeHistogram('pagination_duration_seconds', $duration, [
            'type' => 'cursor',
            'table' => $table,
            'page_size' => $request->pageSize
        ]);

        $this->logger->info('Cursor pagination completed', [
            'duration' => $duration,
            'has_next_page' => $hasNextPage,
            'has_previous_page' => $hasPreviousPage,
            'cache_key' => $cacheKey
        ]);

        return $response;
    }

    private function applyFilters(QueryBuilder $queryBuilder, array $filters): void
    {
        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                $queryBuilder->andWhere("{$field} IN (:" . $field . ")")
                    ->setParameter($field, $value);
            } elseif (is_string($value) && strpos($value, '%') !== false) {
                $queryBuilder->andWhere("{$field} LIKE :" . $field)
                    ->setParameter($field, $value);
            } else {
                $queryBuilder->andWhere("{$field} = :" . $field)
                    ->setParameter($field, $value);
            }
        }
    }

    private function applySorting(QueryBuilder $queryBuilder, array $sorting): void
    {
        foreach ($sorting as $field => $direction) {
            $allowedFields = ['id', 'created_at', 'updated_at', 'name', 'status'];
            $allowedDirections = ['ASC', 'DESC'];

            if (in_array($field, $allowedFields) && in_array(strtoupper($direction), $allowedDirections)) {
                $queryBuilder->addOrderBy($field, strtoupper($direction));
            }
        }

        // Default sorting
        if (empty($sorting)) {
            $queryBuilder->addOrderBy('id', 'ASC');
        }
    }

    private function applyCursorPagination(QueryBuilder $queryBuilder, CursorPaginationRequest $request, array $sorting): void
    {
        if ($request->cursor !== null) {
            $cursorData = $this->decodeCursor($request->cursor);
            
            // Apply cursor conditions based on sorting
            if (isset($cursorData['id'])) {
                $queryBuilder->andWhere('id > :cursor_id')
                    ->setParameter('cursor_id', $cursorData['id']);
            }
            
            if (isset($cursorData['created_at'])) {
                $queryBuilder->andWhere('created_at > :cursor_created_at')
                    ->setParameter('cursor_created_at', $cursorData['created_at']);
            }
        }

        // Limit results
        $queryBuilder->setMaxResults($request->pageSize + 1);
    }

    private function getTotalCount(string $table, array $filters): int
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from($table);

        $this->applyFilters($queryBuilder, $filters);

        $result = $queryBuilder->executeQuery();
        return (int) $result->fetchOne();
    }

    private function generateCursor(array $row, array $sorting): Cursor
    {
        $cursorData = [
            'id' => $row['id'],
            'created_at' => $row['created_at'] ?? null
        ];

        return new Cursor(base64_encode(json_encode($cursorData)));
    }

    private function decodeCursor(Cursor $cursor): array
    {
        $decoded = base64_decode($cursor->toString());
        return json_decode($decoded, true);
    }

    private function generateCacheKey(
        string $table,
        string $type,
        PaginationRequest|CursorPaginationRequest $request,
        array $filters,
        array $sorting
    ): string {
        $keyData = [
            'table' => $table,
            'type' => $type,
            'filters' => $filters,
            'sorting' => $sorting
        ];

        if ($request instanceof PaginationRequest) {
            $keyData['page'] = $request->page;
            $keyData['page_size'] = $request->pageSize;
        } else {
            $keyData['cursor'] = $request->cursor?->toString();
            $keyData['page_size'] = $request->pageSize;
        }

        return 'pagination:' . md5(serialize($keyData));
    }

    private function sanitizeFilters(array $filters): array
    {
        $sensitiveFields = ['password', 'token', 'secret', 'key'];
        $sanitized = [];
        
        foreach ($filters as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $sanitized[$key] = '[REDACTED]';
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
}

/**
 * Requête de pagination par offset
 */
final class PaginationRequest
{
    public function __construct(
        public readonly int $page,
        public readonly int $pageSize
    ) {
        if ($page < 1) {
            throw new \InvalidArgumentException('Page must be greater than 0');
        }

        if ($pageSize < 1 || $pageSize > 100) {
            throw new \InvalidArgumentException('Page size must be between 1 and 100');
        }
    }
}

/**
 * Requête de pagination par curseur
 */
final class CursorPaginationRequest
{
    public function __construct(
        public readonly int $pageSize,
        public readonly ?Cursor $cursor = null
    ) {
        if ($pageSize < 1 || $pageSize > 100) {
            throw new \InvalidArgumentException('Page size must be between 1 and 100');
        }
    }
}

/**
 * Curseur de pagination
 */
final class Cursor
{
    public function __construct(
        private string $value
    ) {}

    public function toString(): string
    {
        return $this->value;
    }
}

/**
 * Réponse de pagination par offset
 */
final class PaginationResponse
{
    public function __construct(
        public readonly array $data,
        public readonly int $page,
        public readonly int $pageSize,
        public readonly int $totalItems,
        public readonly int $totalPages,
        public readonly bool $hasNextPage,
        public readonly bool $hasPreviousPage
    ) {}

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    public function hasNextPage(): bool
    {
        return $this->hasNextPage;
    }

    public function hasPreviousPage(): bool
    {
        return $this->hasPreviousPage;
    }
}

/**
 * Réponse de pagination par curseur
 */
final class CursorPaginationResponse
{
    public function __construct(
        public readonly array $data,
        public readonly int $pageSize,
        public readonly bool $hasNextPage,
        public readonly bool $hasPreviousPage,
        public readonly ?Cursor $nextCursor,
        public readonly ?Cursor $previousCursor
    ) {}

    public function hasNextPage(): bool
    {
        return $this->hasNextPage;
    }

    public function hasPreviousPage(): bool
    {
        return $this->hasPreviousPage;
    }
}

/**
 * Collecteur de métriques
 */
interface MetricsCollector
{
    public function incrementCounter(string $name, array $labels = []): void;
    public function observeHistogram(string $name, float $value, array $labels = []): void;
}
