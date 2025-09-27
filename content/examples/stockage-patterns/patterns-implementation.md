# Patterns d'Implémentation pour le Stockage

Ce document explique les patterns d'implémentation spécifiques pour chaque type de stockage dans le projet Hive.

## 🏗️ Architecture des Patterns

### 1. Pattern Repository

Le pattern Repository encapsule la logique d'accès aux données et fournit une interface plus orientée objet pour accéder à la couche de persistance.

#### Structure de Base

```php
interface RepositoryInterface
{
    public function save(Aggregate $aggregate): void;
    public function find(Id $id): Aggregate;
    public function remove(Id $id): void;
}
```

#### Implémentation SQL Classique

```php
final class DatabaseRepository implements RepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventBusInterface $eventBus,
        private LoggerInterface $logger
    ) {}

    public function save(Aggregate $aggregate): void
    {
        try {
            $this->entityManager->beginTransaction();
            
            $entity = $this->toEntity($aggregate);
            $this->entityManager->persist($entity);
            
            $events = $aggregate->releaseEvents();
            foreach ($events as $event) {
                $this->eventBus->publish($event);
            }
            
            $this->entityManager->flush();
            $this->entityManager->commit();
            
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw new StorageException('Failed to save', 0, $e);
        }
    }
}
```

### 2. Pattern Hydrator

Le pattern Hydrator convertit les données brutes de la base de données en objets du domaine.

#### Structure de Base

```php
interface HydratorInterface
{
    public function hydrate(array $data): DomainObject;
    public function extract(DomainObject $object): array;
}
```

#### Implémentation

```php
final class PaymentHydrator implements HydratorInterface
{
    public function hydrate(array $data): Payment
    {
        $this->validateData($data);
        
        return new Payment(
            uuid: PaymentId::fromString($data['uuid']),
            realmId: RealmId::fromString($data['realm_id']),
            organizationId: OrganizationId::fromString($data['organization_id']),
            subscriptionId: SubscriptionId::fromString($data['subscription_id']),
            // ... autres propriétés
        );
    }

    private function validateData(array $data): void
    {
        $violations = [];
        
        if (!isset($data['uuid']) || !is_string($data['uuid'])) {
            $violations[] = 'uuid is required and must be a string';
        }
        
        if (!empty($violations)) {
            throw new MultipleValidationException($violations);
        }
    }
}
```

### 3. Pattern Mapper

Le pattern Mapper convertit entre les objets du domaine et les entités de persistance.

#### Structure de Base

```php
interface MapperInterface
{
    public function toEntity(DomainObject $object): Entity;
    public function toDomain(Entity $entity): DomainObject;
}
```

#### Implémentation

```php
final class PaymentMapper implements MapperInterface
{
    public function toEntity(Payment $payment): PaymentEntity
    {
        $entity = new PaymentEntity();
        $entity->setUuid($payment->uuid->toString());
        $entity->setRealmId($payment->realmId->toString());
        $entity->setOrganizationId($payment->organizationId->toString());
        $entity->setSubscriptionId($payment->subscriptionId->toString());
        // ... autres propriétés
        
        return $entity;
    }

    public function toDomain(PaymentEntity $entity): Payment
    {
        return new Payment(
            uuid: PaymentId::fromString($entity->getUuid()),
            realmId: RealmId::fromString($entity->getRealmId()),
            organizationId: OrganizationId::fromString($entity->getOrganizationId()),
            subscriptionId: SubscriptionId::fromString($entity->getSubscriptionId()),
            // ... autres propriétés
        );
    }
}
```

## 📊 Patterns par Type de Stockage

### 1. Stockage SQL

#### Pattern Repository SQL

```php
final class DatabaseRepository implements RepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventBusInterface $eventBus,
        private LoggerInterface $logger
    ) {}

    public function save(Aggregate $aggregate): void
    {
        try {
            $this->entityManager->beginTransaction();
            
            $entity = $this->toEntity($aggregate);
            $this->entityManager->persist($entity);
            
            $events = $aggregate->releaseEvents();
            foreach ($events as $event) {
                $this->eventBus->publish($event);
            }
            
            $this->entityManager->flush();
            $this->entityManager->commit();
            
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw new StorageException('Failed to save', 0, $e);
        }
    }
}
```

#### Pattern Query Builder

```php
final class PaymentQueryBuilder
{
    public function __construct(
        private Connection $connection
    ) {}

    public function findByOrganization(OrganizationId $organizationId): array
    {
        $sql = 'SELECT 
                    p.uuid,
                    p.realm_id,
                    p.organization_id,
                    p.subscription_id,
                    p.status,
                    p.gateway,
                    p.subtotal,
                    p.subtotal_currency,
                    p.discount,
                    p.discount_currency,
                    p.taxes,
                    p.taxes_currency,
                    p.total,
                    p.total_currency,
                    p.captured,
                    p.captured_currency,
                    p.creation_date,
                    p.expiration_date,
                    p.completion_date,
                    p.version,
                    o.name as organization_name,
                    s.name as subscription_name
                FROM accounting_payments p
                LEFT JOIN authentication_organizations o ON p.organization_id = o.uuid
                LEFT JOIN accounting_subscriptions s ON p.subscription_id = s.uuid
                WHERE p.organization_id = :organization_id 
                ORDER BY p.creation_date DESC';
        
        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery(['organization_id' => $organizationId->toString()]);
        
        return $result->fetchAllAssociative();
    }
}
```

### 2. Stockage API

#### Pattern API Repository

```php
final class ApiRepository implements RepositoryInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $baseUrl,
        private string $apiKey,
        private LoggerInterface $logger
    ) {}

    public function save(Aggregate $aggregate): void
    {
        try {
            $data = $this->toApiData($aggregate);
            
            $response = $this->httpClient->request('POST', $this->baseUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => $data
            ]);
            
            if ($response->getStatusCode() !== 201) {
                throw new ApiException('Failed to save via API');
            }
            
        } catch (\Exception $e) {
            $this->logger->error('API save failed', [
                'error' => $e->getMessage()
            ]);
            throw new StorageException('Failed to save via API', 0, $e);
        }
    }

    private function toApiData(Aggregate $aggregate): array
    {
        return [
            'uuid' => $aggregate->uuid->toString(),
            'realm_id' => $aggregate->realmId->toString(),
            'organization_id' => $aggregate->organizationId->toString(),
            'subscription_id' => $aggregate->subscriptionId->toString(),
            // ... autres propriétés
        ];
    }
}
```

#### Pattern Circuit Breaker

```php
final class CircuitBreakerRepositoryDecorator implements RepositoryInterface
{
    public function __construct(
        private RepositoryInterface $innerRepository,
        private CircuitBreakerInterface $circuitBreaker
    ) {}

    public function save(Aggregate $aggregate): void
    {
        $this->circuitBreaker->call(function() use ($aggregate) {
            $this->innerRepository->save($aggregate);
        });
    }
}
```

### 3. Stockage ElasticSearch

#### Pattern ElasticSearch Repository

```php
final class ElasticSearchRepository implements RepositoryInterface
{
    public function __construct(
        private Client $elasticsearch,
        private string $index,
        private LoggerInterface $logger
    ) {}

    public function save(Aggregate $aggregate): void
    {
        try {
            $data = $this->toElasticSearchData($aggregate);
            
            $this->elasticsearch->index([
                'index' => $this->index,
                'id' => $aggregate->uuid->toString(),
                'body' => $data
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('ElasticSearch save failed', [
                'error' => $e->getMessage()
            ]);
            throw new StorageException('Failed to save to ElasticSearch', 0, $e);
        }
    }

    public function search(array $query): array
    {
        try {
            $response = $this->elasticsearch->search([
                'index' => $this->index,
                'body' => $query
            ]);
            
            return $response['hits']['hits'];
            
        } catch (\Exception $e) {
            $this->logger->error('ElasticSearch search failed', [
                'error' => $e->getMessage()
            ]);
            throw new StorageException('Failed to search ElasticSearch', 0, $e);
        }
    }
}
```

### 4. Stockage MongoDB

#### Pattern MongoDB Repository

```php
final class MongoRepository implements RepositoryInterface
{
    public function __construct(
        private Manager $manager,
        private string $database,
        private string $collection,
        private LoggerInterface $logger
    ) {}

    public function save(Aggregate $aggregate): void
    {
        try {
            $data = $this->toMongoData($aggregate);
            
            $this->manager->executeBulkWrite(
                $this->database . '.' . $this->collection,
                [new UpdateOne(
                    ['uuid' => $aggregate->uuid->toString()],
                    ['$set' => $data],
                    ['upsert' => true]
                )]
            );
            
        } catch (\Exception $e) {
            $this->logger->error('MongoDB save failed', [
                'error' => $e->getMessage()
            ]);
            throw new StorageException('Failed to save to MongoDB', 0, $e);
        }
    }

    private function toMongoData(Aggregate $aggregate): array
    {
        return [
            'uuid' => $aggregate->uuid->toString(),
            'realm_id' => $aggregate->realmId->toString(),
            'organization_id' => $aggregate->organizationId->toString(),
            'subscription_id' => $aggregate->subscriptionId->toString(),
            // ... autres propriétés
        ];
    }
}
```

### 5. Stockage In-Memory

#### Pattern In-Memory Repository

```php
final class InMemoryRepository implements RepositoryInterface
{
    public function __construct(
        private StorageMock $storage,
        private EventBusInterface $eventBus
    ) {}

    public function save(Aggregate $aggregate): void
    {
        $key = $this->generateKey($aggregate->uuid);
        $this->storage->set($key, $aggregate);
        
        $events = $aggregate->releaseEvents();
        foreach ($events as $event) {
            $this->eventBus->publish($event);
        }
    }

    public function find(Id $id): Aggregate
    {
        $key = $this->generateKey($id);
        $aggregate = $this->storage->get($key);
        
        if (!$aggregate) {
            throw new NotFoundException(sprintf('Aggregate with id %s not found', $id->toString()));
        }
        
        return $aggregate;
    }

    private function generateKey(Id $id): string
    {
        return 'aggregate_' . $id->toString();
    }
}
```

### 6. Stockage Temporal Workflows

#### Pattern Temporal Repository

```php
final class TemporalRepository implements RepositoryInterface
{
    public function __construct(
        private WorkflowClientInterface $workflowClient,
        private LoggerInterface $logger
    ) {}

    public function save(Aggregate $aggregate): void
    {
        try {
            $workflowId = $aggregate->uuid->toString();
            $workflow = $this->workflowClient->newWorkflowStub(
                AggregateWorkflow::class,
                WorkflowOptions::new()
                    ->withWorkflowId($workflowId)
                    ->withTaskQueue('aggregate-task-queue')
            );
            
            $this->workflowClient->start($workflow, $aggregate);
            
        } catch (\Exception $e) {
            $this->logger->error('Temporal save failed', [
                'error' => $e->getMessage()
            ]);
            throw new StorageException('Failed to save to Temporal', 0, $e);
        }
    }
}
```

## 🧪 Patterns de Test

### 1. Pattern Test Repository

```php
final class RepositoryTest extends TestCase
{
    private RepositoryInterface $repository;
    private TestEventBus $eventBus;

    protected function setUp(): void
    {
        $this->eventBus = new TestEventBus();
        $this->repository = new DatabaseRepository(
            $this->createMock(EntityManagerInterface::class),
            $this->eventBus,
            $this->createMock(LoggerInterface::class)
        );
    }

    /** @test */
    public function itShouldSaveAggregateSuccessfully(): void
    {
        // Arrange
        $aggregate = $this->createValidAggregate();
        
        // Act
        $this->repository->save($aggregate);
        
        // Assert
        $this->assertCount(1, $this->eventBus->getPublishedEvents());
    }
}
```

### 2. Pattern Test Hydrator

```php
final class HydratorTest extends TestCase
{
    private HydratorInterface $hydrator;

    protected function setUp(): void
    {
        $this->hydrator = new PaymentHydrator();
    }

    /** @test */
    public function itShouldHydrateValidData(): void
    {
        // Arrange
        $data = $this->createValidData();
        
        // Act
        $result = $this->hydrator->hydrate($data);
        
        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals($data['uuid'], $result->uuid->toString());
    }
}
```

## 📚 Références aux ADR

Ces patterns s'appuient sur les Architecture Decision Records (ADR) suivants du projet Hive :

- **HIVE010** : Repositories - Patterns de repository fondamentaux
- **HIVE011** : In-Memory Repositories - Patterns de repository en mémoire
- **HIVE012** : Database Repositories - Patterns de repository pour base de données
- **HIVE014** : ElasticSearch Repositories - Patterns de repository ElasticSearch
- **HIVE015** : API Repositories - Patterns de repository pour APIs externes
- **HIVE023** : Repository Testing Strategies - Stratégies de test pour les repositories
- **HIVE027** : PHPUnit Testing Standards - Standards de test PHPUnit
- **HIVE033** : Hydrator Implementation Patterns - Patterns d'hydratation des données
- **HIVE035** : Database Operation Logging - Logging des opérations de base de données
- **HIVE042** : Temporal Workflows Implementation - Implémentation des workflows Temporal
