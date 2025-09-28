---
title: "Stockage Temporal - Event Sourcing + CQRS"
description: "Implémentation ultime Event Sourcing + CQRS avec Temporal Workflows pour architecture maximale"
date: 2024-12-19
draft: true
type: "docs"
weight: 51
---

# 🚀📚⚡ Stockage Temporal - Event Sourcing + CQRS

## 🎯 **Contexte et Objectifs**

### **L'Architecture Ultime : Event Sourcing + CQRS avec Temporal**

Nous arrivons maintenant à l'approche la plus sophistiquée et puissante pour Temporal : **Event Sourcing + CQRS complet**. Cette combinaison représente l'état de l'art en matière d'architecture scalable, offrant une solution complète pour les systèmes les plus exigeants.

#### **Pourquoi cette Combinaison Ultime ?**
- **Séparation totale** : Commandes et requêtes complètement découplées
- **Audit trail complet** : Historique immuable de tous les événements
- **Scalabilité maximale** : Possibilité de scaler indépendamment chaque côté
- **Performance optimale** : Chaque côté optimisé pour son usage
- **Flexibilité maximale** : Projections multiples pour différents besoins
- **Résilience** : Workflows robustes avec reprise automatique

### **Contexte Gyroscops**

Dans notre écosystème **User → Organization → Workflow → Cloud Resources → Billing**, Event Sourcing + CQRS avec Temporal est la solution ultime pour :
- **Workflows de commande** : Orchestration des processus de modification avec projections
- **Workflows de requête** : Optimisation des lectures avec analytics avancées
- **Processus de facturation** : Séparation complète des écritures et lectures de facturation
- **Intégrations complexes** : Orchestration des intégrations avec projections multiples

## 🏗️ **Architecture Event Sourcing + CQRS avec Temporal**

### **Séparation Complète des Responsabilités**

#### **Command Side (Write) avec Event Sourcing**
- **Command Workflows** : Orchestration des processus de modification
- **Command Activities** : Exécution des activités de modification
- **Event Handlers** : Gestion des événements de domaine
- **Command Bus** : Orchestration des commandes
- **Event Store** : Persistance des événements
- **Projections** : Mise à jour des vues matérialisées
- **Command Models** : Modèles optimisés pour les écritures

#### **Query Side (Read) avec Event Sourcing**
- **Query Workflows** : Orchestration des processus de lecture
- **Query Activities** : Exécution des activités de lecture
- **Search Services** : Services de recherche spécialisés
- **Query Bus** : Orchestration des requêtes
- **Caches** : Optimisation des performances
- **Read Models** : Vues matérialisées des événements
- **Query Models** : Modèles optimisés pour les lectures

### **Flux de Données Complet**

```mermaid
graph TD
    A[Command] --> B[Command Bus]
    B --> C[Command Workflow]
    C --> D[Command Activities]
    D --> E[Aggregate]
    E --> F[Events]
    F --> G[Event Store Workflow]
    G --> H[Event Store Activity]
    H --> I[Event Store In-Memory]
    I --> J[Event Handlers]
    J --> K[Projections]
    K --> L[Read Models]
    
    M[Query] --> N[Query Bus]
    N --> O[Query Workflow]
    O --> P[Query Activities]
    P --> Q[Read Operations]
    Q --> R[Search Results]
    R --> S[Response]
    
    T[Event] --> U[Event Handler]
    U --> C
    
    V[Projection] --> W[Read Model Update]
    W --> L
    
    X[Command Model] --> Y[Write Optimization]
    Y --> E
    
    Z[Query Model] --> AA[Read Optimization]
    AA --> Q
```

## 💻 **Implémentation Complète**

### **1. Command Side avec Event Sourcing + CQRS**

#### **Command Bus avec Event Sourcing + CQRS**

```php
<?php

namespace App\Application\CommandBus\Temporal;

use App\Domain\Command\CommandInterface;
use App\Domain\Command\CommandHandlerInterface;
use App\Domain\Event\DomainEvent;
use Temporal\Client\WorkflowClientInterface;
use Psr\Log\LoggerInterface;

class TemporalEventSourcingCqrsCommandBus
{
    private array $handlers = [];
    private array $middleware = [];
    private WorkflowClientInterface $workflowClient;
    private LoggerInterface $logger;

    public function __construct(
        WorkflowClientInterface $workflowClient,
        LoggerInterface $logger
    ) {
        $this->workflowClient = $workflowClient;
        $this->logger = $logger;
    }

    public function registerHandler(string $commandClass, CommandHandlerInterface $handler): void
    {
        $this->handlers[$commandClass] = $handler;
    }

    public function addMiddleware(callable $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function handle(CommandInterface $command): void
    {
        $commandClass = get_class($command);
        
        if (!isset($this->handlers[$commandClass])) {
            throw new \InvalidArgumentException("No handler registered for command: $commandClass");
        }

        $handler = $this->handlers[$commandClass];
        
        // Exécuter les middleware
        $this->executeMiddleware($command, function() use ($handler, $command) {
            $this->logger->info('Executing command via Temporal with Event Sourcing + CQRS', [
                'command' => get_class($command),
                'data' => $command->toArray()
            ]);
            
            $handler->handle($command);
        });
    }

    private function executeMiddleware(CommandInterface $command, callable $next): void
    {
        $middleware = array_reverse($this->middleware);
        
        foreach ($middleware as $mw) {
            $next = function() use ($mw, $command, $next) {
                return $mw($command, $next);
            };
        }
        
        $next();
    }
}
```

#### **Command Workflow avec Event Sourcing + CQRS**

```php
<?php

namespace App\Workflow\Command\Payment;

use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;
use Temporal\Workflow\ActivityInterface;
use Temporal\Workflow\ActivityMethod;
use Temporal\Workflow\Workflow;
use App\Domain\Payment\PaymentAggregate;
use App\Infrastructure\EventStore\Temporal\EventStoreWorkflowInterface;

#[WorkflowInterface]
interface PaymentEventSourcingCqrsCommandWorkflowInterface
{
    #[WorkflowMethod]
    public function processPaymentCommandWithEventSourcingCqrs(PaymentCommandRequest $request): PaymentCommandResult;
    
    #[WorkflowMethod]
    public function updatePaymentCommandWithEventSourcingCqrs(PaymentUpdateCommandRequest $request): PaymentCommandResult;
    
    #[WorkflowMethod]
    public function deletePaymentCommandWithEventSourcingCqrs(PaymentDeleteCommandRequest $request): PaymentCommandResult;
}

#[ActivityInterface]
interface PaymentEventSourcingCqrsCommandActivityInterface
{
    #[ActivityMethod]
    public function validatePaymentCommand(PaymentCommandRequest $request): ValidationResult;
    
    #[ActivityMethod]
    public function loadPaymentAggregate(string $paymentId): ?PaymentAggregate;
    
    #[ActivityMethod]
    public function executePaymentCommand(PaymentAggregate $payment, PaymentCommandRequest $request): array;
    
    #[ActivityMethod]
    public function persistEvents(string $aggregateId, array $events, int $expectedVersion): void;
    
    #[ActivityMethod]
    public function updateProjections(array $events): void;
    
    #[ActivityMethod]
    public function updateCommandModels(array $events): void;
}

class PaymentEventSourcingCqrsCommandWorkflow implements PaymentEventSourcingCqrsCommandWorkflowInterface
{
    private PaymentEventSourcingCqrsCommandActivityInterface $commandActivity;
    private EventStoreWorkflowInterface $eventStoreWorkflow;

    public function __construct()
    {
        $this->commandActivity = Workflow::newActivityStub(PaymentEventSourcingCqrsCommandActivityInterface::class);
        $this->eventStoreWorkflow = Workflow::newWorkflowStub(EventStoreWorkflowInterface::class);
    }

    public function processPaymentCommandWithEventSourcingCqrs(PaymentCommandRequest $request): PaymentCommandResult
    {
        try {
            // Validation de la commande
            $validation = yield $this->commandActivity->validatePaymentCommand($request);
            
            if (!$validation->isValid()) {
                return new PaymentCommandResult(false, $validation->getError());
            }
            
            // Charger l'agrégat depuis les événements
            $payment = yield $this->commandActivity->loadPaymentAggregate($request->getPaymentId());
            
            if (!$payment) {
                $payment = new PaymentAggregate($request->getPaymentId());
            }
            
            // Exécuter la commande
            $events = yield $this->commandActivity->executePaymentCommand($payment, $request);
            
            if (empty($events)) {
                return new PaymentCommandResult(false, 'No events generated');
            }
            
            // Persister les événements
            yield $this->commandActivity->persistEvents(
                $request->getPaymentId(),
                $events,
                $payment->getVersion()
            );
            
            // Mettre à jour les projections
            yield $this->commandActivity->updateProjections($events);
            
            // Mettre à jour les modèles de commande
            yield $this->commandActivity->updateCommandModels($events);
            
            return new PaymentCommandResult(true, 'Payment command processed with Event Sourcing + CQRS');
            
        } catch (\Exception $e) {
            return new PaymentCommandResult(false, $e->getMessage());
        }
    }

    public function updatePaymentCommandWithEventSourcingCqrs(PaymentUpdateCommandRequest $request): PaymentCommandResult
    {
        try {
            // Validation de la commande de mise à jour
            $validation = yield $this->commandActivity->validatePaymentCommand($request);
            
            if (!$validation->isValid()) {
                return new PaymentCommandResult(false, $validation->getError());
            }
            
            // Charger l'agrégat depuis les événements
            $payment = yield $this->commandActivity->loadPaymentAggregate($request->getPaymentId());
            
            if (!$payment) {
                return new PaymentCommandResult(false, 'Payment not found');
            }
            
            // Exécuter la commande de mise à jour
            $events = yield $this->commandActivity->executePaymentCommand($payment, $request);
            
            if (empty($events)) {
                return new PaymentCommandResult(false, 'No events generated');
            }
            
            // Persister les événements
            yield $this->commandActivity->persistEvents(
                $request->getPaymentId(),
                $events,
                $payment->getVersion()
            );
            
            // Mettre à jour les projections
            yield $this->commandActivity->updateProjections($events);
            
            // Mettre à jour les modèles de commande
            yield $this->commandActivity->updateCommandModels($events);
            
            return new PaymentCommandResult(true, 'Payment update command processed with Event Sourcing + CQRS');
            
        } catch (\Exception $e) {
            return new PaymentCommandResult(false, $e->getMessage());
        }
    }

    public function deletePaymentCommandWithEventSourcingCqrs(PaymentDeleteCommandRequest $request): PaymentCommandResult
    {
        try {
            // Validation de la commande de suppression
            $validation = yield $this->commandActivity->validatePaymentCommand($request);
            
            if (!$validation->isValid()) {
                return new PaymentCommandResult(false, $validation->getError());
            }
            
            // Charger l'agrégat depuis les événements
            $payment = yield $this->commandActivity->loadPaymentAggregate($request->getPaymentId());
            
            if (!$payment) {
                return new PaymentCommandResult(false, 'Payment not found');
            }
            
            // Exécuter la commande de suppression
            $events = yield $this->commandActivity->executePaymentCommand($payment, $request);
            
            if (empty($events)) {
                return new PaymentCommandResult(false, 'No events generated');
            }
            
            // Persister les événements
            yield $this->commandActivity->persistEvents(
                $request->getPaymentId(),
                $events,
                $payment->getVersion()
            );
            
            // Mettre à jour les projections
            yield $this->commandActivity->updateProjections($events);
            
            // Mettre à jour les modèles de commande
            yield $this->commandActivity->updateCommandModels($events);
            
            return new PaymentCommandResult(true, 'Payment delete command processed with Event Sourcing + CQRS');
            
        } catch (\Exception $e) {
            return new PaymentCommandResult(false, $e->getMessage());
        }
    }
}
```

### **2. Query Side avec Event Sourcing + CQRS**

#### **Query Bus avec Cache et Event Sourcing + CQRS**

```php
<?php

namespace App\Application\QueryBus\Temporal;

use App\Domain\Query\QueryInterface;
use App\Domain\Query\QueryHandlerInterface;
use Temporal\Client\WorkflowClientInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class TemporalEventSourcingCqrsQueryBus
{
    private array $handlers = [];
    private WorkflowClientInterface $workflowClient;
    private CacheItemPoolInterface $cache;
    private LoggerInterface $logger;

    public function __construct(
        WorkflowClientInterface $workflowClient,
        CacheItemPoolInterface $cache,
        LoggerInterface $logger
    ) {
        $this->workflowClient = $workflowClient;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function registerHandler(string $queryClass, QueryHandlerInterface $handler): void
    {
        $this->handlers[$queryClass] = $handler;
    }

    public function handle(QueryInterface $query): mixed
    {
        $queryClass = get_class($query);
        
        if (!isset($this->handlers[$queryClass])) {
            throw new \InvalidArgumentException("No handler registered for query: $queryClass");
        }

        // Vérifier le cache
        $cacheKey = $this->generateCacheKey($query);
        $cachedItem = $this->cache->getItem($cacheKey);
        
        if ($cachedItem->isHit()) {
            $this->logger->debug('Query result served from cache', [
                'query' => $queryClass,
                'cacheKey' => $cacheKey
            ]);
            
            return $cachedItem->get();
        }

        // Exécuter la requête via Temporal
        $handler = $this->handlers[$queryClass];
        $result = $handler->handle($query);
        
        // Mettre en cache
        $cachedItem->set($result);
        $cachedItem->expiresAfter(300); // 5 minutes
        $this->cache->save($cachedItem);
        
        $this->logger->info('Query executed and cached via Temporal with Event Sourcing + CQRS', [
            'query' => $queryClass,
            'cacheKey' => $cacheKey
        ]);
        
        return $result;
    }

    private function generateCacheKey(QueryInterface $query): string
    {
        return 'temporal_event_sourcing_cqrs_query_' . md5(serialize($query));
    }
}
```

#### **Query Workflow avec Event Sourcing + CQRS**

```php
<?php

namespace App\Workflow\Query\Payment;

use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;
use Temporal\Workflow\ActivityInterface;
use Temporal\Workflow\ActivityMethod;
use Temporal\Workflow\Workflow;
use App\Infrastructure\EventStore\Temporal\EventStoreWorkflowInterface;

#[WorkflowInterface]
interface PaymentEventSourcingCqrsQueryWorkflowInterface
{
    #[WorkflowMethod]
    public function searchPaymentsWithEventSourcingCqrs(PaymentSearchQuery $query): PaymentSearchResult;
    
    #[WorkflowMethod]
    public function getPaymentByIdWithEventSourcingCqrs(PaymentByIdQuery $query): ?Payment;
    
    #[WorkflowMethod]
    public function getPaymentHistoryWithEventSourcingCqrs(PaymentHistoryQuery $query): array;
    
    #[WorkflowMethod]
    public function getPaymentStatisticsWithEventSourcingCqrs(PaymentStatisticsQuery $query): array;
    
    #[WorkflowMethod]
    public function getPaymentAnalyticsWithEventSourcingCqrs(PaymentAnalyticsQuery $query): array;
    
    #[WorkflowMethod]
    public function getPaymentTimelineWithEventSourcingCqrs(PaymentTimelineQuery $query): array;
}

#[ActivityInterface]
interface PaymentEventSourcingCqrsQueryActivityInterface
{
    #[ActivityMethod]
    public function searchPaymentsInReadModel(PaymentSearchQuery $query): array;
    
    #[ActivityMethod]
    public function getPaymentFromReadModel(string $paymentId): ?Payment;
    
    #[ActivityMethod]
    public function getEventsByAggregate(string $aggregateId): array;
    
    #[ActivityMethod]
    public function calculatePaymentStatisticsFromEvents(PaymentStatisticsQuery $query): array;
    
    #[ActivityMethod]
    public function generatePaymentAnalyticsFromEvents(PaymentAnalyticsQuery $query): array;
    
    #[ActivityMethod]
    public function getEventsByCorrelationId(string $correlationId): array;
    
    #[ActivityMethod]
    public function updateQueryModels(array $events): void;
}

class PaymentEventSourcingCqrsQueryWorkflow implements PaymentEventSourcingCqrsQueryWorkflowInterface
{
    private PaymentEventSourcingCqrsQueryActivityInterface $queryActivity;
    private EventStoreWorkflowInterface $eventStoreWorkflow;

    public function __construct()
    {
        $this->queryActivity = Workflow::newActivityStub(PaymentEventSourcingCqrsQueryActivityInterface::class);
        $this->eventStoreWorkflow = Workflow::newWorkflowStub(EventStoreWorkflowInterface::class);
    }

    public function searchPaymentsWithEventSourcingCqrs(PaymentSearchQuery $query): PaymentSearchResult
    {
        try {
            // Recherche dans le Read Model
            $payments = yield $this->queryActivity->searchPaymentsInReadModel($query);
            
            // Filtrage et tri
            $filteredPayments = $this->filterPayments($payments, $query);
            $sortedPayments = $this->sortPayments($filteredPayments, $query);
            
            // Pagination
            $paginatedPayments = $this->paginatePayments($sortedPayments, $query);
            
            return new PaymentSearchResult(
                $paginatedPayments,
                count($filteredPayments),
                $query->getPage(),
                $query->getSize()
            );
            
        } catch (\Exception $e) {
            return new PaymentSearchResult([], 0, $query->getPage(), $query->getSize());
        }
    }

    public function getPaymentByIdWithEventSourcingCqrs(PaymentByIdQuery $query): ?Payment
    {
        try {
            return yield $this->queryActivity->getPaymentFromReadModel($query->getPaymentId());
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getPaymentHistoryWithEventSourcingCqrs(PaymentHistoryQuery $query): array
    {
        try {
            $events = yield $this->queryActivity->getEventsByAggregate($query->getPaymentId());
            
            $history = [];
            foreach ($events as $event) {
                $history[] = [
                    'eventId' => $event->getId(),
                    'eventType' => $event->getEventType(),
                    'timestamp' => $event->getTimestamp(),
                    'data' => $event->toArray(),
                    'metadata' => $event->getMetadata()
                ];
            }
            
            return $history;
            
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getPaymentStatisticsWithEventSourcingCqrs(PaymentStatisticsQuery $query): array
    {
        try {
            return yield $this->queryActivity->calculatePaymentStatisticsFromEvents($query);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getPaymentAnalyticsWithEventSourcingCqrs(PaymentAnalyticsQuery $query): array
    {
        try {
            return yield $this->queryActivity->generatePaymentAnalyticsFromEvents($query);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getPaymentTimelineWithEventSourcingCqrs(PaymentTimelineQuery $query): array
    {
        try {
            $events = yield $this->queryActivity->getEventsByCorrelationId($query->getCorrelationId());
            
            $timeline = [];
            foreach ($events as $event) {
                $timeline[] = [
                    'eventId' => $event->getId(),
                    'aggregateId' => $event->getAggregateId(),
                    'eventType' => $event->getEventType(),
                    'timestamp' => $event->getTimestamp(),
                    'data' => $event->toArray(),
                    'metadata' => $event->getMetadata()
                ];
            }
            
            return $timeline;
            
        } catch (\Exception $e) {
            return [];
        }
    }

    private function filterPayments(array $payments, PaymentSearchQuery $query): array
    {
        return array_filter($payments, function($payment) use ($query) {
            if ($query->getOrganizationId() && $payment->getOrganizationId() !== $query->getOrganizationId()) {
                return false;
            }
            
            if ($query->getStatus() && $payment->getStatus() !== $query->getStatus()) {
                return false;
            }
            
            if ($query->getMinAmount() && $payment->getAmount() < $query->getMinAmount()) {
                return false;
            }
            
            if ($query->getMaxAmount() && $payment->getAmount() > $query->getMaxAmount()) {
                return false;
            }
            
            return true;
        });
    }

    private function sortPayments(array $payments, PaymentSearchQuery $query): array
    {
        $sortField = $query->getSortField() ?? 'createdAt';
        $sortDirection = $query->getSortDirection() ?? 'desc';
        
        usort($payments, function($a, $b) use ($sortField, $sortDirection) {
            $valueA = $this->getFieldValue($a, $sortField);
            $valueB = $this->getFieldValue($b, $sortField);
            
            if ($sortDirection === 'asc') {
                return $valueA <=> $valueB;
            } else {
                return $valueB <=> $valueA;
            }
        });
        
        return $payments;
    }

    private function paginatePayments(array $payments, PaymentSearchQuery $query): array
    {
        $page = $query->getPage() ?? 1;
        $size = $query->getSize() ?? 10;
        $offset = ($page - 1) * $size;
        
        return array_slice($payments, $offset, $size);
    }

    private function getFieldValue(Payment $payment, string $field): mixed
    {
        switch ($field) {
            case 'amount':
                return $payment->getAmount();
            case 'createdAt':
                return $payment->getCreatedAt();
            case 'status':
                return $payment->getStatus();
            default:
                return $payment->getCreatedAt();
        }
    }
}
```

### **3. Service de Projection Avancé avec CQRS**

#### **Service de Projection avec Event Sourcing + CQRS**

```php
<?php

namespace App\Application\Service\Temporal;

use App\Domain\Event\DomainEvent;
use App\Infrastructure\EventStore\Temporal\EventStoreWorkflowInterface;
use Temporal\Client\WorkflowClientInterface;
use Psr\Log\LoggerInterface;

class EventSourcingCqrsProjectionService
{
    private WorkflowClientInterface $workflowClient;
    private EventStoreWorkflowInterface $eventStoreWorkflow;
    private LoggerInterface $logger;

    public function __construct(
        WorkflowClientInterface $workflowClient,
        EventStoreWorkflowInterface $eventStoreWorkflow,
        LoggerInterface $logger
    ) {
        $this->workflowClient = $workflowClient;
        $this->eventStoreWorkflow = $eventStoreWorkflow;
        $this->logger = $logger;
    }

    public function handleEvent(DomainEvent $event): void
    {
        try {
            $workflowOptions = $this->createProjectionWorkflowOptions();
            
            $workflow = $this->workflowClient->newWorkflowStub(
                EventSourcingCqrsProjectionWorkflowInterface::class,
                $workflowOptions
            );
            
            $workflow->updateProjection($event);
            
            $this->logger->info('Projection updated via Temporal with Event Sourcing + CQRS', [
                'eventType' => $event->getEventType(),
                'eventId' => $event->getId()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update projection via Temporal with Event Sourcing + CQRS', [
                'eventType' => $event->getEventType(),
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    public function rebuildProjection(string $projectionType): void
    {
        try {
            $workflowOptions = $this->createProjectionWorkflowOptions();
            
            $workflow = $this->workflowClient->newWorkflowStub(
                EventSourcingCqrsProjectionWorkflowInterface::class,
                $workflowOptions
            );
            
            $workflow->rebuildProjection($projectionType);
            
            $this->logger->info('Projection rebuilt via Temporal with Event Sourcing + CQRS', [
                'projectionType' => $projectionType
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to rebuild projection via Temporal with Event Sourcing + CQRS', [
                'projectionType' => $projectionType,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    private function createProjectionWorkflowOptions(): WorkflowOptions
    {
        return WorkflowOptions::new()
            ->withWorkflowId('event-sourcing-cqrs-projection-' . uniqid())
            ->withWorkflowExecutionTimeout(600)
            ->withWorkflowRunTimeout(600)
            ->withWorkflowTaskTimeout(120);
    }
}
```

## 🧪 **Tests et Validation**

### **Tests d'Intégration Event Sourcing + CQRS**

```php
<?php

namespace App\Tests\Integration\Temporal;

use App\Workflow\Command\Payment\PaymentCommandRequest;
use App\Workflow\Query\Payment\PaymentSearchQuery;
use App\Application\CommandBus\Temporal\TemporalEventSourcingCqrsCommandBus;
use App\Application\QueryBus\Temporal\TemporalEventSourcingCqrsQueryBus;
use App\Infrastructure\Temporal\TemporalClientFactory;

class TemporalEventSourcingCqrsTest extends TestCase
{
    private TemporalEventSourcingCqrsCommandBus $commandBus;
    private TemporalEventSourcingCqrsQueryBus $queryBus;
    private TemporalClientFactory $temporalFactory;

    protected function setUp(): void
    {
        $this->temporalFactory = new TemporalClientFactory('localhost', 7233, 'test');
        $this->commandBus = new TemporalEventSourcingCqrsCommandBus(
            $this->temporalFactory->createClient(),
            $this->createMock(LoggerInterface::class)
        );
        $this->queryBus = new TemporalEventSourcingCqrsQueryBus(
            $this->temporalFactory->createClient(),
            $this->createMock(CacheItemPoolInterface::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testEventSourcingCqrsSeparation(): void
    {
        // Exécuter une commande avec Event Sourcing + CQRS
        $command = new PaymentCommandRequest(
            'CREATE',
            'payment-123',
            'org-456',
            'user-789',
            100.00,
            'EUR',
            'Test payment'
        );
        
        $this->commandBus->handle($command);
        
        // Vérifier avec une requête
        $query = new PaymentSearchQuery('org-456', 0, 10);
        $result = $this->queryBus->handle($query);
        
        $this->assertGreaterThan(0, $result->getTotal());
    }

    public function testEventSourcingCqrsQueryCaching(): void
    {
        $query = new PaymentSearchQuery('org-456', 0, 10);
        
        // Première recherche
        $result1 = $this->queryBus->handle($query);
        
        // Deuxième recherche (devrait utiliser le cache)
        $result2 = $this->queryBus->handle($query);
        
        $this->assertEquals($result1->getTotal(), $result2->getTotal());
    }
}
```

## 📊 **Performance et Optimisation**

### **Stratégies d'Optimisation Event Sourcing + CQRS**

#### **1. Cache Multi-Niveaux avec Event Sourcing + CQRS**
```php
public function searchPaymentsWithCache(PaymentSearchQuery $query): PaymentSearchResult
{
    // Cache L1: Mémoire
    if (isset($this->memoryCache[$query->getCacheKey()])) {
        return $this->memoryCache[$query->getCacheKey()];
    }
    
    // Cache L2: Redis
    if ($cached = $this->redis->get("payment_search_es_cqrs:{$query->getCacheKey()}")) {
        $result = PaymentSearchResult::fromArray(json_decode($cached, true));
        $this->memoryCache[$query->getCacheKey()] = $result;
        return $result;
    }
    
    // Temporal avec Event Sourcing + CQRS
    $result = $this->searchPaymentsWithEventSourcingCqrs($query);
    
    // Mettre en cache
    $this->memoryCache[$query->getCacheKey()] = $result;
    $this->redis->setex("payment_search_es_cqrs:{$query->getCacheKey()}", 300, json_encode($result->toArray()));
    
    return $result;
}
```

#### **2. Projections Asynchrones avec Event Sourcing + CQRS**
```php
public function handleEventAsync(DomainEvent $event): void
{
    // Mettre en queue pour traitement asynchrone
    $this->messageBus->dispatch(new ProcessEventSourcingCqrsProjectionCommand($event));
}
```

#### **3. Monitoring des Workflows Event Sourcing + CQRS**
```php
public function getWorkflowMetrics(): array
{
    return [
        'commandWorkflows' => $this->getCommandWorkflowMetrics(),
        'queryWorkflows' => $this->getQueryWorkflowMetrics(),
        'eventStoreWorkflows' => $this->getEventStoreWorkflowMetrics(),
        'projections' => $this->getProjectionMetrics(),
        'commandModels' => $this->getCommandModelMetrics(),
        'queryModels' => $this->getQueryModelMetrics(),
        'cacheHitRate' => $this->getCacheHitRate(),
        'averageExecutionTime' => $this->getAverageExecutionTime()
    ];
}
```

## 🎯 **Critères d'Adoption**

### **Quand Utiliser Event Sourcing + CQRS avec Temporal**

#### **✅ Avantages**
- **Séparation totale** : Commandes et requêtes complètement découplées
- **Audit trail complet** : Historique immuable de tous les événements
- **Scalabilité maximale** : Possibilité de scaler indépendamment
- **Performance optimale** : Chaque côté optimisé pour son usage
- **Flexibilité maximale** : Projections multiples pour différents besoins
- **Résilience** : Workflows robustes avec reprise automatique
- **Maintenabilité** : Code plus clair et organisé

#### **❌ Inconvénients**
- **Complexité maximale** : Architecture très complexe
- **Infrastructure** : Nécessite un serveur Temporal
- **Latence** : Overhead pour les opérations simples
- **Expertise** : Équipe très expérimentée requise
- **Coût** : Infrastructure très coûteuse
- **Courbe d'apprentissage** : Très élevée
- **Temps de développement** : Très long

#### **🎯 Critères d'Adoption**
- **Système très complexe** : Besoins de scalabilité maximale
- **Audit trail critique** : Besoin de traçabilité complète
- **Performance critique** : Besoins de performance maximale
- **Projections multiples** : Besoin de vues différentes des données
- **Équipe très expérimentée** : Maîtrise de Temporal, Event Sourcing et CQRS
- **Budget important** : Investissement en complexité justifié
- **Infrastructure disponible** : Serveur Temporal opérationnel
- **Temps de développement** : Suffisant pour implémenter cette complexité
- **Maintenance** : Équipe capable de maintenir cette complexité

## 🚀 **Votre Prochaine Étape**

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux explorer les autres types de stockage" 
    subtitle="Vous voulez voir les alternatives à Temporal"
    criteria="Comparaison nécessaire,Choix de stockage,Architecture à définir,Performance à optimiser"
    time="30-40 minutes"
    chapter="10"
    chapter-title="Choix du Type de Stockage"
    chapter-url="/chapitres/fondamentaux/chapitre-10-choix-type-stockage/"
  >}}
  
  {{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux voir des exemples concrets" 
    subtitle="Vous voulez comprendre les implémentations pratiques"
    criteria="Développeur expérimenté,Besoin d'exemples pratiques,Implémentation à faire,Code à écrire"
    time="Variable"
    chapter="0"
    chapter-title="Exemples et Implémentations"
    chapter-url="/examples/"
  >}}
  
  {{< chapter-option 
    letter="C" 
    color="blue" 
    title="Je veux revenir aux fondamentaux" 
    subtitle="Vous voulez comprendre les concepts de base"
    criteria="Développeur débutant,Besoin de comprendre les concepts,Projet à structurer,Équipe à former"
    time="45-60 minutes"
    chapter="1"
    chapter-title="Introduction au Domain-Driven Design et Event Storming"
    chapter-url="/chapitres/fondamentaux/chapitre-01-introduction-event-storming-ddd/"
  >}}
  
  {{< chapter-option 
    letter="D" 
    color="purple" 
    title="Je veux voir les chapitres Multi-sources" 
    subtitle="Vous voulez comprendre les approches multi-sources"
    criteria="Architecture complexe,Équipe expérimentée,Intégrations multiples,Stockage hybride"
    time="40-50 minutes"
    chapter="52"
    chapter-title="Stockage Multi-sources - Approche Classique"
    chapter-url="/chapitres/stockage/multi-sources/chapitre-52-stockage-multi-sources-classique/"
  >}}
{{< /chapter-nav >}}

---

*Event Sourcing + CQRS avec Temporal représente l'état de l'art ultime en matière d'architecture scalable, performante et traçable pour l'orchestration, parfaitement adapté aux besoins les plus exigeants de Gyroscops.*

