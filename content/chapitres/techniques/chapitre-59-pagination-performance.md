---
title: "Chapitre 59 : Pagination et Performance"
description: "Pagination et performance dans le projet Hive avec des exemples concrets"
date: 2024-12-19
draft: true
type: "docs"
weight: 59
---

## 🎯 Objectif de ce Chapitre

Ce chapitre vous montre comment implémenter la pagination et optimiser les performances dans le projet Hive. Vous apprendrez :
- Comment implémenter la pagination efficace
- Comment optimiser les performances des requêtes
- Comment gérer les grandes quantités de données
- Comment tester la pagination

### Références aux ADR du Projet Hive

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du projet Hive :
- **HIVE037** : Pagination Implementation Guidelines - Guidelines d'implémentation de la pagination
- **HIVE039** : Cursor-Based Pagination - Pagination basée sur les curseurs
- **HIVE013** : Collection Management - Gestion des collections
- **HIVE014** : ElasticSearch Repositories - Repositories ElasticSearch
- **HIVE027** : PHPUnit Testing Standards - Standards de test PHPUnit

## 🏗️ Architecture de la Pagination

### Structure de Pagination

```
api/src/
├── Platform/
│   ├── Domain/
│   │   ├── Pagination/
│   │   │   ├── PaginationRequest.php
│   │   │   ├── PaginationResponse.php
│   │   │   ├── CursorPaginationRequest.php
│   │   │   └── CursorPaginationResponse.php
│   │   └── ValueObjects/
│   │       ├── PageNumber.php
│   │       ├── PageSize.php
│   │       └── Cursor.php
│   └── Infrastructure/
│       ├── Pagination/
│       │   ├── OffsetPaginationService.php
│       │   ├── CursorPaginationService.php
│       │   └── PaginationValidator.php
│       └── Repositories/
│           ├── PaginatedRepositoryInterface.php
│           └── CursorPaginatedRepositoryInterface.php
```

### Interfaces de Pagination

```php
// ✅ Interface de Pagination (Projet Hive)
interface PaginatedRepositoryInterface
{
    public function findPaginated(
        PaginationRequest $pagination,
        array $filters = [],
        array $sorting = []
    ): PaginationResponse;
}

interface CursorPaginatedRepositoryInterface
{
    public function findCursorPaginated(
        CursorPaginationRequest $pagination,
        array $filters = [],
        array $sorting = []
    ): CursorPaginationResponse;
}

final class PaginationRequest
{
    public function __construct(
        public readonly int $page,
        public readonly int $pageSize,
        public readonly array $filters = [],
        public readonly array $sorting = []
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if ($this->page < 1) {
            throw new InvalidPaginationException('Page must be greater than 0');
        }

        if ($this->pageSize < 1 || $this->pageSize > 100) {
            throw new InvalidPaginationException('Page size must be between 1 and 100');
        }
    }
}

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
```

## 📄 Pagination par Offset

### Service de Pagination par Offset

```php
// ✅ Service de Pagination par Offset (Projet Hive)
final class OffsetPaginationService
{
    public function __construct(
        private PaginatedRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {}

    public function paginate(
        PaginationRequest $request,
        array $filters = [],
        array $sorting = []
    ): PaginationResponse {
        $this->logger->info('Paginating data', [
            'page' => $request->page,
            'pageSize' => $request->pageSize,
            'filters' => $filters,
            'sorting' => $sorting
        ]);

        $response = $this->repository->findPaginated($request, $filters, $sorting);

        $this->logger->info('Pagination completed', [
            'totalItems' => $response->getTotalItems(),
            'totalPages' => $response->getTotalPages(),
            'hasNextPage' => $response->hasNextPage(),
            'hasPreviousPage' => $response->hasPreviousPage()
        ]);

        return $response;
    }
}
```

### Repository avec Pagination par Offset

```php
// ✅ Repository avec Pagination par Offset (Projet Hive)
final class SqlPaymentRepository implements PaginatedRepositoryInterface
{
    public function __construct(
        private Connection $connection,
        private PaymentHydrator $hydrator
    ) {}

    public function findPaginated(
        PaginationRequest $pagination,
        array $filters = [],
        array $sorting = []
    ): PaginationResponse {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('payments');

        // Apply filters
        $this->applyFilters($queryBuilder, $filters);

        // Apply sorting
        $this->applySorting($queryBuilder, $sorting);

        // Get total count
        $totalItems = $this->getTotalCount($filters);

        // Apply pagination
        $offset = ($pagination->page - 1) * $pagination->pageSize;
        $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($pagination->pageSize);

        // Execute query
        $result = $queryBuilder->executeQuery();
        $rows = $result->fetchAllAssociative();

        // Hydrate entities
        $data = array_map(fn($row) => $this->hydrator->hydrate($row), $rows);

        // Calculate pagination metadata
        $totalPages = (int) ceil($totalItems / $pagination->pageSize);
        $hasNextPage = $pagination->page < $totalPages;
        $hasPreviousPage = $pagination->page > 1;

        return new PaginationResponse(
            data: $data,
            page: $pagination->page,
            pageSize: $pagination->pageSize,
            totalItems: $totalItems,
            totalPages: $totalPages,
            hasNextPage: $hasNextPage,
            hasPreviousPage: $hasPreviousPage
        );
    }

    private function applyFilters(QueryBuilder $queryBuilder, array $filters): void
    {
        if (isset($filters['status'])) {
            $queryBuilder->andWhere('status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (isset($filters['organization_id'])) {
            $queryBuilder->andWhere('organization_id = :organization_id')
                ->setParameter('organization_id', $filters['organization_id']);
        }

        if (isset($filters['date_from'])) {
            $queryBuilder->andWhere('creation_date >= :date_from')
                ->setParameter('date_from', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $queryBuilder->andWhere('creation_date <= :date_to')
                ->setParameter('date_to', $filters['date_to']);
        }

        if (isset($filters['amount_min'])) {
            $queryBuilder->andWhere('total_amount >= :amount_min')
                ->setParameter('amount_min', $filters['amount_min']);
        }

        if (isset($filters['amount_max'])) {
            $queryBuilder->andWhere('total_amount <= :amount_max')
                ->setParameter('amount_max', $filters['amount_max']);
        }
    }

    private function applySorting(QueryBuilder $queryBuilder, array $sorting): void
    {
        foreach ($sorting as $field => $direction) {
            $allowedFields = ['creation_date', 'total_amount', 'status', 'customer_name'];
            $allowedDirections = ['ASC', 'DESC'];

            if (in_array($field, $allowedFields) && in_array(strtoupper($direction), $allowedDirections)) {
                $queryBuilder->addOrderBy($field, strtoupper($direction));
            }
        }

        // Default sorting
        if (empty($sorting)) {
            $queryBuilder->addOrderBy('creation_date', 'DESC');
        }
    }

    private function getTotalCount(array $filters): int
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('payments');

        $this->applyFilters($queryBuilder, $filters);

        $result = $queryBuilder->executeQuery();
        return (int) $result->fetchOne();
    }
}
```

## 🎯 Pagination par Curseur

### Service de Pagination par Curseur

```php
// ✅ Service de Pagination par Curseur (Projet Hive)
final class CursorPaginationService
{
    public function __construct(
        private CursorPaginatedRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {}

    public function paginate(
        CursorPaginationRequest $request,
        array $filters = [],
        array $sorting = []
    ): CursorPaginationResponse {
        $this->logger->info('Cursor paginating data', [
            'cursor' => $request->cursor?->toString(),
            'pageSize' => $request->pageSize,
            'filters' => $filters,
            'sorting' => $sorting
        ]);

        $response = $this->repository->findCursorPaginated($request, $filters, $sorting);

        $this->logger->info('Cursor pagination completed', [
            'hasNextPage' => $response->hasNextPage(),
            'hasPreviousPage' => $response->hasPreviousPage(),
            'nextCursor' => $response->nextCursor?->toString(),
            'previousCursor' => $response->previousCursor?->toString()
        ]);

        return $response;
    }
}
```

### Repository avec Pagination par Curseur

```php
// ✅ Repository avec Pagination par Curseur (Projet Hive)
final class SqlPaymentRepository implements CursorPaginatedRepositoryInterface
{
    public function __construct(
        private Connection $connection,
        private PaymentHydrator $hydrator
    ) {}

    public function findCursorPaginated(
        CursorPaginationRequest $pagination,
        array $filters = [],
        array $sorting = []
    ): CursorPaginationResponse {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('payments');

        // Apply filters
        $this->applyFilters($queryBuilder, $filters);

        // Apply cursor-based pagination
        $this->applyCursorPagination($queryBuilder, $pagination);

        // Apply sorting
        $this->applySorting($queryBuilder, $sorting);

        // Execute query
        $result = $queryBuilder->executeQuery();
        $rows = $result->fetchAllAssociative();

        // Hydrate entities
        $data = array_map(fn($row) => $this->hydrator->hydrate($row), $rows);

        // Calculate cursor metadata
        $hasNextPage = count($data) === $pagination->pageSize;
        $hasPreviousPage = $pagination->cursor !== null;

        // Generate cursors
        $nextCursor = $hasNextPage && !empty($data) 
            ? $this->generateCursor(end($data), $sorting)
            : null;
        
        $previousCursor = $hasPreviousPage && !empty($data)
            ? $this->generateCursor(reset($data), $sorting)
            : null;

        return new CursorPaginationResponse(
            data: $data,
            pageSize: $pagination->pageSize,
            hasNextPage: $hasNextPage,
            hasPreviousPage: $hasPreviousPage,
            nextCursor: $nextCursor,
            previousCursor: $previousCursor
        );
    }

    private function applyCursorPagination(QueryBuilder $queryBuilder, CursorPaginationRequest $pagination): void
    {
        if ($pagination->cursor !== null) {
            $cursorData = $this->decodeCursor($pagination->cursor);
            
            // Apply cursor conditions based on sorting
            if (isset($cursorData['creation_date'])) {
                $queryBuilder->andWhere('creation_date > :cursor_date')
                    ->setParameter('cursor_date', $cursorData['creation_date']);
            }
            
            if (isset($cursorData['id'])) {
                $queryBuilder->andWhere('id > :cursor_id')
                    ->setParameter('cursor_id', $cursorData['id']);
            }
        }

        // Limit results
        $queryBuilder->setMaxResults($pagination->pageSize + 1);
    }

    private function generateCursor(array $entity, array $sorting): ?Cursor
    {
        $cursorData = [
            'id' => $entity['id'],
            'creation_date' => $entity['creation_date']
        ];

        return new Cursor(base64_encode(json_encode($cursorData)));
    }

    private function decodeCursor(Cursor $cursor): array
    {
        $decoded = base64_decode($cursor->toString());
        return json_decode($decoded, true);
    }
}
```

## ⚡ Optimisation des Performances

### Optimisation des Requêtes

```php
// ✅ Optimisation des Requêtes (Projet Hive)
final class OptimizedPaymentRepository implements PaginatedRepositoryInterface
{
    public function __construct(
        private Connection $connection,
        private PaymentHydrator $hydrator
    ) {}

    public function findPaginated(
        PaginationRequest $pagination,
        array $filters = [],
        array $sorting = []
    ): PaginationResponse {
        // Use prepared statements for better performance
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('p.*')
            ->from('payments', 'p')
            ->leftJoin('p', 'organizations', 'o', 'p.organization_id = o.id')
            ->leftJoin('p', 'subscriptions', 's', 'p.subscription_id = s.id');

        // Apply filters with indexes
        $this->applyOptimizedFilters($queryBuilder, $filters);

        // Apply sorting with proper indexes
        $this->applyOptimizedSorting($queryBuilder, $sorting);

        // Get total count efficiently
        $totalItems = $this->getOptimizedTotalCount($filters);

        // Apply pagination
        $offset = ($pagination->page - 1) * $pagination->pageSize;
        $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($pagination->pageSize);

        // Execute query with profiling
        $startTime = microtime(true);
        $result = $queryBuilder->executeQuery();
        $executionTime = microtime(true) - $startTime;

        // Log performance metrics
        if ($executionTime > 1.0) {
            $this->logger->warning('Slow query detected', [
                'execution_time' => $executionTime,
                'query' => $queryBuilder->getSQL(),
                'parameters' => $queryBuilder->getParameters()
            ]);
        }

        $rows = $result->fetchAllAssociative();

        // Hydrate entities efficiently
        $data = $this->hydrateEntities($rows);

        // Calculate pagination metadata
        $totalPages = (int) ceil($totalItems / $pagination->pageSize);
        $hasNextPage = $pagination->page < $totalPages;
        $hasPreviousPage = $pagination->page > 1;

        return new PaginationResponse(
            data: $data,
            page: $pagination->page,
            pageSize: $pagination->pageSize,
            totalItems: $totalItems,
            totalPages: $totalPages,
            hasNextPage: $hasNextPage,
            hasPreviousPage: $hasPreviousPage
        );
    }

    private function applyOptimizedFilters(QueryBuilder $queryBuilder, array $filters): void
    {
        // Use indexed columns for filtering
        if (isset($filters['status'])) {
            $queryBuilder->andWhere('p.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (isset($filters['organization_id'])) {
            $queryBuilder->andWhere('p.organization_id = :organization_id')
                ->setParameter('organization_id', $filters['organization_id']);
        }

        if (isset($filters['date_from'])) {
            $queryBuilder->andWhere('p.creation_date >= :date_from')
                ->setParameter('date_from', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $queryBuilder->andWhere('p.creation_date <= :date_to')
                ->setParameter('date_to', $filters['date_to']);
        }

        // Use range queries for better performance
        if (isset($filters['amount_min']) || isset($filters['amount_max'])) {
            if (isset($filters['amount_min'])) {
                $queryBuilder->andWhere('p.total_amount >= :amount_min')
                    ->setParameter('amount_min', $filters['amount_min']);
            }
            if (isset($filters['amount_max'])) {
                $queryBuilder->andWhere('p.total_amount <= :amount_max')
                    ->setParameter('amount_max', $filters['amount_max']);
            }
        }
    }

    private function applyOptimizedSorting(QueryBuilder $queryBuilder, array $sorting): void
    {
        // Use composite indexes for sorting
        $defaultSorting = ['p.creation_date' => 'DESC', 'p.id' => 'ASC'];
        $appliedSorting = $sorting ?: $defaultSorting;

        foreach ($appliedSorting as $field => $direction) {
            $allowedFields = [
                'p.creation_date' => 'creation_date',
                'p.total_amount' => 'total_amount',
                'p.status' => 'status',
                'p.customer_name' => 'customer_name'
            ];

            if (isset($allowedFields[$field])) {
                $queryBuilder->addOrderBy($field, strtoupper($direction));
            }
        }
    }

    private function getOptimizedTotalCount(array $filters): int
    {
        // Use COUNT(*) with proper indexes
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('payments', 'p');

        $this->applyOptimizedFilters($queryBuilder, $filters);

        $result = $queryBuilder->executeQuery();
        return (int) $result->fetchOne();
    }

    private function hydrateEntities(array $rows): array
    {
        // Batch hydrate for better performance
        return array_map(fn($row) => $this->hydrator->hydrate($row), $rows);
    }
}
```

### Cache de Pagination

```php
// ✅ Cache de Pagination (Projet Hive)
final class CachedPaginationService
{
    public function __construct(
        private PaginatedRepositoryInterface $repository,
        private CacheInterface $cache,
        private LoggerInterface $logger
    ) {}

    public function paginate(
        PaginationRequest $request,
        array $filters = [],
        array $sorting = []
    ): PaginationResponse {
        $cacheKey = $this->generateCacheKey($request, $filters, $sorting);

        // Try to get from cache
        $cachedResponse = $this->cache->get($cacheKey);
        if ($cachedResponse !== null) {
            $this->logger->info('Pagination data retrieved from cache', [
                'cache_key' => $cacheKey
            ]);
            return $cachedResponse;
        }

        // Get from repository
        $response = $this->repository->findPaginated($request, $filters, $sorting);

        // Cache the response
        $this->cache->set($cacheKey, $response, 300); // 5 minutes

        $this->logger->info('Pagination data cached', [
            'cache_key' => $cacheKey,
            'ttl' => 300
        ]);

        return $response;
    }

    private function generateCacheKey(
        PaginationRequest $request,
        array $filters,
        array $sorting
    ): string {
        $keyData = [
            'page' => $request->page,
            'page_size' => $request->pageSize,
            'filters' => $filters,
            'sorting' => $sorting
        ];

        return 'pagination:' . md5(serialize($keyData));
    }
}
```

## 🧪 Tests de Pagination

### Test de Pagination par Offset

```php
// ✅ Test de Pagination par Offset (Projet Hive)
final class OffsetPaginationServiceTest extends TestCase
{
    private OffsetPaginationService $service;
    private PaginatedRepositoryInterface $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PaginatedRepositoryInterface::class);
        $this->service = new OffsetPaginationService(
            $this->repository,
            $this->createMock(LoggerInterface::class)
        );
    }

    /** @test */
    public function itShouldPaginateFirstPage(): void
    {
        // Arrange
        $request = new PaginationRequest(1, 10);
        $expectedResponse = new PaginationResponse(
            data: [['id' => 1, 'name' => 'Payment 1']],
            page: 1,
            pageSize: 10,
            totalItems: 25,
            totalPages: 3,
            hasNextPage: true,
            hasPreviousPage: false
        );

        $this->repository->expects($this->once())
            ->method('findPaginated')
            ->with($request, [], [])
            ->willReturn($expectedResponse);

        // Act
        $response = $this->service->paginate($request);

        // Assert
        $this->assertEquals(1, $response->page);
        $this->assertEquals(10, $response->pageSize);
        $this->assertEquals(25, $response->getTotalItems());
        $this->assertEquals(3, $response->getTotalPages());
        $this->assertTrue($response->hasNextPage());
        $this->assertFalse($response->hasPreviousPage());
    }

    /** @test */
    public function itShouldPaginateWithFilters(): void
    {
        // Arrange
        $request = new PaginationRequest(1, 10);
        $filters = ['status' => 'completed'];
        $sorting = ['creation_date' => 'DESC'];

        $expectedResponse = new PaginationResponse(
            data: [['id' => 1, 'name' => 'Payment 1']],
            page: 1,
            pageSize: 10,
            totalItems: 15,
            totalPages: 2,
            hasNextPage: true,
            hasPreviousPage: false
        );

        $this->repository->expects($this->once())
            ->method('findPaginated')
            ->with($request, $filters, $sorting)
            ->willReturn($expectedResponse);

        // Act
        $response = $this->service->paginate($request, $filters, $sorting);

        // Assert
        $this->assertEquals(15, $response->getTotalItems());
        $this->assertEquals(2, $response->getTotalPages());
    }
}
```

### Test de Pagination par Curseur

```php
// ✅ Test de Pagination par Curseur (Projet Hive)
final class CursorPaginationServiceTest extends TestCase
{
    private CursorPaginationService $service;
    private CursorPaginatedRepositoryInterface $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CursorPaginatedRepositoryInterface::class);
        $this->service = new CursorPaginationService(
            $this->repository,
            $this->createMock(LoggerInterface::class)
        );
    }

    /** @test */
    public function itShouldPaginateWithCursor(): void
    {
        // Arrange
        $cursor = new Cursor('eyJpZCI6MSwiY3JlYXRpb25fZGF0ZSI6IjIwMjQtMDEtMDEgMTA6MDA6MDAifQ==');
        $request = new CursorPaginationRequest(10, $cursor);

        $expectedResponse = new CursorPaginationResponse(
            data: [['id' => 2, 'name' => 'Payment 2']],
            pageSize: 10,
            hasNextPage: true,
            hasPreviousPage: true,
            nextCursor: new Cursor('eyJpZCI6MiwiY3JlYXRpb25fZGF0ZSI6IjIwMjQtMDEtMDIgMTA6MDA6MDAifQ=='),
            previousCursor: new Cursor('eyJpZCI6MSwiY3JlYXRpb25fZGF0ZSI6IjIwMjQtMDEtMDEgMTA6MDA6MDAifQ==')
        );

        $this->repository->expects($this->once())
            ->method('findCursorPaginated')
            ->with($request, [], [])
            ->willReturn($expectedResponse);

        // Act
        $response = $this->service->paginate($request);

        // Assert
        $this->assertEquals(10, $response->pageSize);
        $this->assertTrue($response->hasNextPage());
        $this->assertTrue($response->hasPreviousPage());
        $this->assertNotNull($response->nextCursor);
        $this->assertNotNull($response->previousCursor);
    }

    /** @test */
    public function itShouldPaginateFirstPageWithoutCursor(): void
    {
        // Arrange
        $request = new CursorPaginationRequest(10, null);

        $expectedResponse = new CursorPaginationResponse(
            data: [['id' => 1, 'name' => 'Payment 1']],
            pageSize: 10,
            hasNextPage: true,
            hasPreviousPage: false,
            nextCursor: new Cursor('eyJpZCI6MSwiY3JlYXRpb25fZGF0ZSI6IjIwMjQtMDEtMDEgMTA6MDA6MDAifQ=='),
            previousCursor: null
        );

        $this->repository->expects($this->once())
            ->method('findCursorPaginated')
            ->with($request, [], [])
            ->willReturn($expectedResponse);

        // Act
        $response = $this->service->paginate($request);

        // Assert
        $this->assertTrue($response->hasNextPage());
        $this->assertFalse($response->hasPreviousPage());
        $this->assertNotNull($response->nextCursor);
        $this->assertNull($response->previousCursor);
    }
}
```

## 🏗️ Bonnes Pratiques du Projet Hive

### Pagination selon HIVE037 et HIVE039

Le projet Hive implémente une pagination robuste selon les ADR HIVE037 et HIVE039 :

```php
// ✅ Pagination Hive (Projet Hive)
final class HivePaginationService
{
    public function __construct(
        private PaginatedRepositoryInterface $repository,
        private CursorPaginatedRepositoryInterface $cursorRepository,
        private LoggerInterface $logger,
        private MetricsCollector $metrics
    ) {}

    public function paginateWithOffset(
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

        $response = $this->repository->findPaginated($request, $filters, $sorting);
        
        $duration = microtime(true) - $startTime;
        
        $this->metrics->observeHistogram('pagination_duration_seconds', $duration, [
            'type' => 'offset',
            'page_size' => $request->pageSize
        ]);

        $this->logger->info('Offset pagination completed', [
            'duration' => $duration,
            'total_items' => $response->getTotalItems(),
            'total_pages' => $response->getTotalPages()
        ]);

        return $response;
    }

    public function paginateWithCursor(
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

        $response = $this->cursorRepository->findCursorPaginated($request, $filters, $sorting);
        
        $duration = microtime(true) - $startTime;
        
        $this->metrics->observeHistogram('pagination_duration_seconds', $duration, [
            'type' => 'cursor',
            'page_size' => $request->pageSize
        ]);

        $this->logger->info('Cursor pagination completed', [
            'duration' => $duration,
            'has_next_page' => $response->hasNextPage(),
            'has_previous_page' => $response->hasPreviousPage()
        ]);

        return $response;
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
```

### Optimisation des Requêtes selon HIVE035

Le projet Hive optimise les requêtes avec un logging détaillé selon l'ADR HIVE035 :

```php
// ✅ Optimisation des Requêtes Hive (Projet Hive)
final class HiveQueryOptimizer
{
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger,
        private MetricsCollector $metrics
    ) {}

    public function executeOptimizedQuery(
        QueryBuilder $queryBuilder,
        string $operationName,
        array $context = []
    ): array {
        $startTime = microtime(true);
        $query = $queryBuilder->getSQL();
        $parameters = $queryBuilder->getParameters();
        
        $this->logger->info('Executing optimized query', [
            'operation' => $operationName,
            'query' => $query,
            'parameters' => $this->sanitizeParameters($parameters),
            'context' => $context
        ]);

        try {
            $result = $queryBuilder->executeQuery();
            $rows = $result->fetchAllAssociative();
            
            $duration = microtime(true) - $startTime;
            
            $this->metrics->observeHistogram('query_duration_seconds', $duration, [
                'operation' => $operationName,
                'row_count' => count($rows)
            ]);

            $this->logger->info('Query executed successfully', [
                'operation' => $operationName,
                'duration' => $duration,
                'row_count' => count($rows)
            ]);

            return $rows;
        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;
            
            $this->metrics->incrementCounter('query_errors_total', [
                'operation' => $operationName,
                'error_type' => get_class($e)
            ]);

            $this->logger->error('Query execution failed', [
                'operation' => $operationName,
                'query' => $query,
                'parameters' => $this->sanitizeParameters($parameters),
                'error' => $e->getMessage(),
                'duration' => $duration
            ]);

            throw $e;
        }
    }

    private function sanitizeParameters(array $parameters): array
    {
        $sensitiveFields = ['password', 'token', 'secret', 'key'];
        $sanitized = [];
        
        foreach ($parameters as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $sanitized[$key] = '[REDACTED]';
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
}
```

### Cache de Pagination selon HIVE013

Le projet Hive implémente un cache intelligent pour la pagination selon l'ADR HIVE013 :

```php
// ✅ Cache de Pagination Hive (Projet Hive)
final class HivePaginationCache
{
    public function __construct(
        private CacheInterface $cache,
        private LoggerInterface $logger,
        private MetricsCollector $metrics
    ) {}

    public function getCachedPagination(
        string $cacheKey,
        callable $paginationCallback,
        int $ttl = 300
    ): PaginationResponse {
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            $this->metrics->incrementCounter('pagination_cache_hits_total', [
                'type' => 'offset'
            ]);
            
            $this->logger->info('Pagination data retrieved from cache', [
                'cache_key' => $cacheKey
            ]);
            
            return $cached;
        }

        $this->metrics->incrementCounter('pagination_cache_misses_total', [
            'type' => 'offset'
        ]);

        $response = $paginationCallback();
        
        $this->cache->set($cacheKey, $response, $ttl);
        
        $this->logger->info('Pagination data cached', [
            'cache_key' => $cacheKey,
            'ttl' => $ttl
        ]);

        return $response;
    }

    public function getCachedCursorPagination(
        string $cacheKey,
        callable $paginationCallback,
        int $ttl = 300
    ): CursorPaginationResponse {
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            $this->metrics->incrementCounter('pagination_cache_hits_total', [
                'type' => 'cursor'
            ]);
            
            $this->logger->info('Cursor pagination data retrieved from cache', [
                'cache_key' => $cacheKey
            ]);
            
            return $cached;
        }

        $this->metrics->incrementCounter('pagination_cache_misses_total', [
            'type' => 'cursor'
        ]);

        $response = $paginationCallback();
        
        $this->cache->set($cacheKey, $response, $ttl);
        
        $this->logger->info('Cursor pagination data cached', [
            'cache_key' => $cacheKey,
            'ttl' => $ttl
        ]);

        return $response;
    }

    public function generateCacheKey(
        string $operation,
        array $filters,
        array $sorting,
        ?int $page = null,
        ?int $pageSize = null,
        ?string $cursor = null
    ): string {
        $keyData = [
            'operation' => $operation,
            'filters' => $filters,
            'sorting' => $sorting,
            'page' => $page,
            'page_size' => $pageSize,
            'cursor' => $cursor
        ];

        return 'pagination:' . md5(serialize($keyData));
    }
}
```

### Tests de Pagination selon HIVE027

Le projet Hive teste la pagination selon les standards HIVE027 :

```php
// ✅ Tests de Pagination Hive (Projet Hive)
final class HivePaginationServiceTest extends TestCase
{
    private HivePaginationService $service;
    private PaginatedRepositoryInterface $repository;
    private CursorPaginatedRepositoryInterface $cursorRepository;
    private LoggerInterface $logger;
    private MetricsCollector $metrics;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PaginatedRepositoryInterface::class);
        $this->cursorRepository = $this->createMock(CursorPaginatedRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->metrics = $this->createMock(MetricsCollector::class);
        $this->service = new HivePaginationService(
            $this->repository,
            $this->cursorRepository,
            $this->logger,
            $this->metrics
        );
    }

    /** @test */
    public function itShouldPaginateWithOffset(): void
    {
        // Arrange
        $request = new PaginationRequest(1, 10);
        $filters = ['status' => 'completed'];
        $sorting = ['creation_date' => 'DESC'];
        
        $expectedResponse = new PaginationResponse(
            data: [['id' => 1, 'name' => 'Payment 1']],
            page: 1,
            pageSize: 10,
            totalItems: 25,
            totalPages: 3,
            hasNextPage: true,
            hasPreviousPage: false
        );

        $this->repository->expects($this->once())
            ->method('findPaginated')
            ->with($request, $filters, $sorting)
            ->willReturn($expectedResponse);

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Starting offset pagination', $this->isType('array')],
                ['Offset pagination completed', $this->isType('array')]
            );

        $this->metrics->expects($this->once())
            ->method('observeHistogram')
            ->with('pagination_duration_seconds', $this->isType('float'), [
                'type' => 'offset',
                'page_size' => 10
            ]);

        // Act
        $response = $this->service->paginateWithOffset($request, $filters, $sorting);

        // Assert
        $this->assertEquals(1, $response->page);
        $this->assertEquals(10, $response->pageSize);
        $this->assertEquals(25, $response->getTotalItems());
        $this->assertEquals(3, $response->getTotalPages());
    }

    /** @test */
    public function itShouldPaginateWithCursor(): void
    {
        // Arrange
        $cursor = new Cursor('eyJpZCI6MSwiY3JlYXRpb25fZGF0ZSI6IjIwMjQtMDEtMDEgMTA6MDA6MDAifQ==');
        $request = new CursorPaginationRequest(10, $cursor);
        $filters = ['status' => 'completed'];
        $sorting = ['creation_date' => 'DESC'];
        
        $expectedResponse = new CursorPaginationResponse(
            data: [['id' => 2, 'name' => 'Payment 2']],
            pageSize: 10,
            hasNextPage: true,
            hasPreviousPage: true,
            nextCursor: new Cursor('eyJpZCI6MiwiY3JlYXRpb25fZGF0ZSI6IjIwMjQtMDEtMDIgMTA6MDA6MDAifQ=='),
            previousCursor: new Cursor('eyJpZCI6MSwiY3JlYXRpb25fZGF0ZSI6IjIwMjQtMDEtMDEgMTA6MDA6MDAifQ==')
        );

        $this->cursorRepository->expects($this->once())
            ->method('findCursorPaginated')
            ->with($request, $filters, $sorting)
            ->willReturn($expectedResponse);

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Starting cursor pagination', $this->isType('array')],
                ['Cursor pagination completed', $this->isType('array')]
            );

        $this->metrics->expects($this->once())
            ->method('observeHistogram')
            ->with('pagination_duration_seconds', $this->isType('float'), [
                'type' => 'cursor',
                'page_size' => 10
            ]);

        // Act
        $response = $this->service->paginateWithCursor($request, $filters, $sorting);

        // Assert
        $this->assertEquals(10, $response->pageSize);
        $this->assertTrue($response->hasNextPage());
        $this->assertTrue($response->hasPreviousPage());
        $this->assertNotNull($response->nextCursor);
        $this->assertNotNull($response->previousCursor);
    }
}
```

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux explorer la gestion d'erreurs et l'observabilité" 
    subtitle="Vous voulez améliorer la robustesse de votre application" 
    criteria="Besoin de robustesse,Gestion d'erreurs complexe,Observabilité importante,Monitoring et logging" 
    time="30-40 minutes" 
    chapter="60" 
    chapter-title="Gestion d'Erreurs et Observabilité" 
    chapter-url="/chapitres/chapitre-22/" 
  >}}
  
  {{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux explorer les tests et la qualité" 
    subtitle="Vous voulez améliorer la qualité de votre code" 
    criteria="Besoin de qualité de code,Tests complets,Couverture de code,Standards de qualité" 
    time="35-45 minutes" 
    chapter="61" 
    chapter-title="Tests et Qualité" 
    chapter-url="/chapitres/chapitre-23/" 
  >}}
  
  {{< chapter-option 
    letter="C" 
    color="red" 
    title="Je veux explorer la sécurité et l'autorisation" 
    subtitle="Vous voulez sécuriser votre application" 
    criteria="Besoin de sécurité,Gestion des permissions,Authentification,Autorisation fine" 
    time="40-50 minutes" 
    chapter="62" 
    chapter-title="Sécurité et Autorisation" 
    chapter-url="/chapitres/chapitre-24/" 
  >}}
  
  {{< chapter-option 
    letter="D" 
    color="blue" 
    title="Je veux continuer avec la pagination" 
    subtitle="La pagination me convient parfaitement" 
    criteria="Application simple,Pagination basique,Performance acceptable,Pas de besoins complexes" 
    time="25-35 minutes" 
    chapter="63" 
    chapter-title="Frontend et Intégration" 
    chapter-url="/chapitres/chapitre-25/" 
  >}}
  
{{< /chapter-nav >}}