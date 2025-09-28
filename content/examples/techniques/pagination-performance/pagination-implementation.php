<?php

namespace App\Technical\Pagination;

use App\Technical\Pagination\Cursor\Cursor;
use App\Technical\Pagination\Cursor\CursorBuilder;
use App\Technical\Pagination\Request\CursorPaginationRequest;
use App\Technical\Pagination\Request\PaginationRequest;
use App\Technical\Pagination\Response\CursorPaginationResponse;
use App\Technical\Pagination\Response\PaginationResponse;
use App\Technical\Repository\CursorPaginatedRepositoryInterface;
use App\Technical\Repository\PaginatedRepositoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Log\LoggerInterface;

// ✅ Implémentation de Pagination Gyroscops Cloud (Projet Gyroscops Cloud)
final class HivePaginationImplementation
{
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger,
        private CursorBuilder $cursorBuilder
    ) {}

    public function paginateWithOffset(
        PaginatedRepositoryInterface $repository,
        PaginationRequest $request,
        array $filters = [],
        array $sorting = []
    ): PaginationResponse {
        $startTime = microtime(true);
        
        $this->logger->info('Starting offset pagination', [
            'page' => $request->page,
            'page_size' => $request->pageSize,
            'filters' => $this->sanitizeFilters($filters),
            'sorting' => $sorting
        ]);

        try {
            // Calculer l'offset
            $offset = ($request->page - 1) * $request->pageSize;
            
            // Récupérer les données
            $data = $repository->findPaginated($request, $filters, $sorting);
            
            // Compter le total
            $total = $repository->count($filters);
            
            // Calculer les métadonnées
            $totalPages = (int) ceil($total / $request->pageSize);
            $hasNextPage = $request->page < $totalPages;
            $hasPreviousPage = $request->page > 1;
            
            $response = new PaginationResponse(
                $data,
                $request->page,
                $request->pageSize,
                $total,
                $totalPages,
                $hasNextPage,
                $hasPreviousPage
            );
            
            $duration = microtime(true) - $startTime;
            
            $this->logger->info('Offset pagination completed', [
                'duration' => $duration,
                'total_items' => $total,
                'total_pages' => $totalPages,
                'page' => $request->page
            ]);
            
            return $response;
            
        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;
            
            $this->logger->error('Offset pagination failed', [
                'duration' => $duration,
                'error' => $e->getMessage(),
                'page' => $request->page,
                'page_size' => $request->pageSize
            ]);
            
            throw $e;
        }
    }

    public function paginateWithCursor(
        CursorPaginatedRepositoryInterface $repository,
        CursorPaginationRequest $request,
        array $filters = [],
        array $sorting = []
    ): CursorPaginationResponse {
        $startTime = microtime(true);
        
        $this->logger->info('Starting cursor pagination', [
            'cursor' => $request->cursor?->toString(),
            'page_size' => $request->pageSize,
            'filters' => $this->sanitizeFilters($filters),
            'sorting' => $sorting
        ]);

        try {
            // Récupérer les données avec le curseur
            $data = $repository->findCursorPaginated($request, $filters, $sorting);
            
            // Déterminer s'il y a une page suivante
            $hasNextPage = count($data) > $request->pageSize;
            
            // Si on a plus d'éléments que demandé, enlever le dernier
            if ($hasNextPage) {
                array_pop($data);
            }
            
            // Créer le curseur pour la page suivante
            $nextCursor = null;
            if ($hasNextPage && !empty($data)) {
                $lastItem = end($data);
                $nextCursor = $this->cursorBuilder->buildFromItem($lastItem, $sorting);
            }
            
            // Créer le curseur pour la page précédente
            $previousCursor = null;
            if ($request->cursor && !empty($data)) {
                $firstItem = reset($data);
                $previousCursor = $this->cursorBuilder->buildFromItem($firstItem, $sorting, true);
            }
            
            $response = new CursorPaginationResponse(
                $data,
                $request->pageSize,
                $nextCursor,
                $previousCursor,
                $hasNextPage,
                $request->cursor !== null
            );
            
            $duration = microtime(true) - $startTime;
            
            $this->logger->info('Cursor pagination completed', [
                'duration' => $duration,
                'items_count' => count($data),
                'has_next_page' => $hasNextPage,
                'has_previous_page' => $request->cursor !== null
            ]);
            
            return $response;
            
        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;
            
            $this->logger->error('Cursor pagination failed', [
                'duration' => $duration,
                'error' => $e->getMessage(),
                'cursor' => $request->cursor?->toString(),
                'page_size' => $request->pageSize
            ]);
            
            throw $e;
        }
    }

    public function buildQueryWithPagination(
        QueryBuilder $queryBuilder,
        PaginationRequest $request,
        array $filters = [],
        array $sorting = []
    ): QueryBuilder {
        // Appliquer les filtres
        $this->applyFilters($queryBuilder, $filters);
        
        // Appliquer le tri
        $this->applySorting($queryBuilder, $sorting);
        
        // Appliquer la pagination
        $offset = ($request->page - 1) * $request->pageSize;
        $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($request->pageSize);
        
        return $queryBuilder;
    }

    public function buildQueryWithCursor(
        QueryBuilder $queryBuilder,
        CursorPaginationRequest $request,
        array $filters = [],
        array $sorting = []
    ): QueryBuilder {
        // Appliquer les filtres
        $this->applyFilters($queryBuilder, $filters);
        
        // Appliquer le tri
        $this->applySorting($queryBuilder, $sorting);
        
        // Appliquer le curseur
        if ($request->cursor) {
            $this->applyCursor($queryBuilder, $request->cursor, $sorting);
        }
        
        // Appliquer la limite
        $queryBuilder->setMaxResults($request->pageSize + 1); // +1 pour détecter s'il y a une page suivante
        
        return $queryBuilder;
    }

    private function applyFilters(QueryBuilder $queryBuilder, array $filters): void
    {
        foreach ($filters as $field => $value) {
            if ($value === null) {
                continue;
            }
            
            if (is_array($value)) {
                $queryBuilder->andWhere($queryBuilder->expr()->in($field, $value));
            } elseif (is_string($value) && str_contains($value, '%')) {
                $queryBuilder->andWhere($queryBuilder->expr()->like($field, ':value'))
                    ->setParameter('value', $value);
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->eq($field, ':value'))
                    ->setParameter('value', $value);
            }
        }
    }

    private function applySorting(QueryBuilder $queryBuilder, array $sorting): void
    {
        foreach ($sorting as $field => $direction) {
            $queryBuilder->addOrderBy($field, $direction);
        }
    }

    private function applyCursor(QueryBuilder $queryBuilder, Cursor $cursor, array $sorting): void
    {
        $cursorData = $cursor->getData();
        
        // Construire la condition WHERE pour le curseur
        $conditions = [];
        $parameters = [];
        
        foreach ($sorting as $field => $direction) {
            if (!isset($cursorData[$field])) {
                continue;
            }
            
            $value = $cursorData[$field];
            
            if ($direction === 'ASC') {
                $conditions[] = "({$field} > :cursor_{$field})";
            } else {
                $conditions[] = "({$field} < :cursor_{$field})";
            }
            
            $parameters["cursor_{$field}"] = $value;
        }
        
        if (!empty($conditions)) {
            $queryBuilder->andWhere(implode(' AND ', $conditions));
            
            foreach ($parameters as $key => $value) {
                $queryBuilder->setParameter($key, $value);
            }
        }
    }

    private function sanitizeFilters(array $filters): array
    {
        $sensitiveFields = ['password', 'token', 'secret', 'key', 'api_key'];
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
