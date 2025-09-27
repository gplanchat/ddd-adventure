---
title: "Stockage Temporal - Event Sourcing + CQS"
description: "Implémentation Event Sourcing + CQS avec Temporal Workflows pour performance et audit trail"
date: 2024-12-19
draft: true
type: "docs"
weight: 50
---

# ⚡📚 Stockage Temporal - Event Sourcing + CQS

## 🎯 **Contexte et Objectifs**

### **La Combinaison Ultime : Event Sourcing + CQS avec Temporal**

Nous arrivons maintenant à l'approche la plus sophistiquée pour Temporal : **Event Sourcing + CQS**. Cette combinaison offre une architecture hautement performante et traçable, parfaite pour les systèmes nécessitant audit trail complet et séparation claire des responsabilités.

#### **Pourquoi cette Combinaison ?**
- **Performance optimisée** : Séparation claire entre écriture et lecture
- **Audit trail complet** : Historique immuable de tous les événements
- **Scalabilité** : Possibilité de scaler indépendamment chaque côté
- **Flexibilité** : Projections multiples pour différents besoins
- **Résilience** : Workflows robustes avec reprise automatique

### **Contexte Gyroscops**

Dans notre écosystème **User → Organization → Workflow → Cloud Resources → Billing**, Event Sourcing + CQS avec Temporal est la solution ultime pour :
- **Workflows de commande** : Orchestration des processus de modification avec projections
- **Workflows de requête** : Optimisation des lectures avec analytics avancées
- **Processus de facturation** : Séparation complète des écritures et lectures de facturation
- **Intégrations complexes** : Orchestration des intégrations avec projections multiples

## 🏗️ **Architecture Event Sourcing + CQS avec Temporal**

### **Séparation Complète des Responsabilités**

#### **Command Side (Write) avec Event Sourcing**
- **Command Workflows** : Orchestration des processus de modification
- **Command Activities** : Exécution des activités de modification
- **Event Handlers** : Gestion des événements de domaine
- **Command Bus** : Orchestration des commandes
- **Event Store** : Persistance des événements
- **Projections** : Mise à jour des vues matérialisées

#### **Query Side (Read) avec Event Sourcing**
- **Query Workflows** : Orchestration des processus de lecture
- **Query Activities** : Exécution des activités de lecture
- **Search Services** : Services de recherche spécialisés
- **Query Bus** : Orchestration des requêtes
- **Caches** : Optimisation des performances
- **Read Models** : Vues matérialisées des événements

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
```

## 💻 **Implémentation Complète**

### **1. Command Side avec Event Sourcing**

#### **Command Bus avec Event Sourcing**

```php
<?php

namespace App\Application\CommandBus\Temporal;

use App\Domain\Command\CommandInterface;
use App\Domain\Command\CommandHandlerInterface;
use App\Domain\Event\DomainEvent;
use Temporal\Client\WorkflowClientInterface;
use Psr\Log\LoggerInterface;

class TemporalEventSourcingCommandBus
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
            $this->logger->info('Executing command via Temporal with Event Sourcing', [
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

#### **Command Workflow avec Event Sourcing**

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
interface PaymentEventSourcingCommandWorkflowInterface
{
    #[WorkflowMethod]
    public function processPaymentCommandWithEventSourcing(PaymentCommandRequest $request): PaymentCommandResult;
    
    #[WorkflowMethod]
    public function updatePaymentCommandWithEventSourcing(PaymentUpdateCommandRequest $request): PaymentCommandResult;
    
    #[WorkflowMethod]
    public function deletePaymentCommandWithEventSourcing(PaymentDeleteCommandRequest $request): PaymentCommandResult;
}

#[ActivityInterface]
interface PaymentEventSourcingCommandActivityInterface
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
}

class PaymentEventSourcingCommandWorkflow implements PaymentEventSourcingCommandWorkflowInterface
{
    private PaymentEventSourcingCommandActivityInterface $commandActivity;
    private EventStoreWorkflowInterface $eventStoreWorkflow;

    public function __construct()
    {
        $this->commandActivity = Workflow::newActivityStub(PaymentEventSourcingCommandActivityInterface::class);
        $this->eventStoreWorkflow = Workflow::newWorkflowStub(EventStoreWorkflowInterface::class);
    }

    public function processPaymentCommandWithEventSourcing(PaymentCommandRequest $request): PaymentCommandResult
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
            
            return new PaymentCommandResult(true, 'Payment command processed with event sourcing');
            
        } catch (\Exception $e) {
            return new PaymentCommandResult(false, $e->getMessage());
        }
    }

    public function updatePaymentCommandWithEventSourcing(PaymentUpdateCommandRequest $request): PaymentCommandResult
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
            
            return new PaymentCommandResult(true, 'Payment update command processed with event sourcing');
            
        } catch (\Exception $e) {
            return new PaymentCommandResult(false, $e->getMessage());
        }
    }

    public function deletePaymentCommandWithEventSourcing(PaymentDeleteCommandRequest $request): PaymentCommandResult
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
            
            return new PaymentCommandResult(true, 'Payment delete command processed with event sourcing');
            
        } catch (\Exception $e) {
            return new PaymentCommandResult(false, $e->getMessage());
        }
    }
}
```

### **2. Query Side avec Event Sourcing**

#### **Query Bus avec Cache et Event Sourcing**

```php
<?php

namespace App\Application\QueryBus\Temporal;

use App\Domain\Query\QueryInterface;
use App\Domain\Query\QueryHandlerInterface;
use Temporal\Client\WorkflowClientInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class TemporalEventSourcingQueryBus
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
        
        $this->logger->info('Query executed and cached via Temporal with Event Sourcing', [
            'query' => $queryClass,
            'cacheKey' => $cacheKey
        ]);
        
        return $result;
    }

    private function generateCacheKey(QueryInterface $query): string
    {
        return 'temporal_event_sourcing_query_' . md5(serialize($query));
    }
}
```

#### **Query Workflow avec Event Sourcing**

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
interface PaymentEventSourcingQueryWorkflowInterface
{
    #[WorkflowMethod]
    public function searchPaymentsWithEventSourcing(PaymentSearchQuery $query): PaymentSearchResult;
    
    #[WorkflowMethod]
    public function getPaymentByIdWithEventSourcing(PaymentByIdQuery $query): ?Payment;
    
    #[WorkflowMethod]
    public function getPaymentHistory(PaymentHistoryQuery $query): array;
    
    #[WorkflowMethod]
    public function getPaymentStatisticsWithEventSourcing(PaymentStatisticsQuery $query): array;
    
    #[WorkflowMethod]
    public function getPaymentAnalyticsWithEventSourcing(PaymentAnalyticsQuery $query): array;
}

#[ActivityInterface]
interface PaymentEventSourcingQueryActivityInterface
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
}

class PaymentEventSourcingQueryWorkflow implements PaymentEventSourcingQueryWorkflowInterface
{
    private PaymentEventSourcingQueryActivityInterface $queryActivity;
    private EventStoreWorkflowInterface $eventStoreWorkflow;

    public function __construct()
    {
        $this->queryActivity = Workflow::newActivityStub(PaymentEventSourcingQueryActivityInterface::class);
        $this->eventStoreWorkflow = Workflow::newWorkflowStub(EventStoreWorkflowInterface::class);
    }

    public function searchPaymentsWithEventSourcing(PaymentSearchQuery $query): PaymentSearchResult
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

    public function getPaymentByIdWithEventSourcing(PaymentByIdQuery $query): ?Payment
    {
        try {
            return yield $this->queryActivity->getPaymentFromReadModel($query->getPaymentId());
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getPaymentHistory(PaymentHistoryQuery $query): array
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

    public function getPaymentStatisticsWithEventSourcing(PaymentStatisticsQuery $query): array
    {
        try {
            return yield $this->queryActivity->calculatePaymentStatisticsFromEvents($query);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getPaymentAnalyticsWithEventSourcing(PaymentAnalyticsQuery $query): array
    {
        try {
            return yield $this->queryActivity->generatePaymentAnalyticsFromEvents($query);
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

### **3. Service de Projection Avancé**

#### **Service de Projection avec Event Sourcing**

```php
<?php

namespace App\Application\Service\Temporal;

use App\Domain\Event\DomainEvent;
use App\Infrastructure\EventStore\Temporal\EventStoreWorkflowInterface;
use Temporal\Client\WorkflowClientInterface;
use Psr\Log\LoggerInterface;

class EventSourcingProjectionService
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
                EventSourcingProjectionWorkflowInterface::class,
                $workflowOptions
            );
            
            $workflow->updateProjection($event);
            
            $this->logger->info('Projection updated via Temporal with Event Sourcing', [
                'eventType' => $event->getEventType(),
                'eventId' => $event->getId()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update projection via Temporal with Event Sourcing', [
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
                EventSourcingProjectionWorkflowInterface::class,
                $workflowOptions
            );
            
            $workflow->rebuildProjection($projectionType);
            
            $this->logger->info('Projection rebuilt via Temporal with Event Sourcing', [
                'projectionType' => $projectionType
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to rebuild projection via Temporal with Event Sourcing', [
                'projectionType' => $projectionType,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    private function createProjectionWorkflowOptions(): WorkflowOptions
    {
        return WorkflowOptions::new()
            ->withWorkflowId('event-sourcing-projection-' . uniqid())
            ->withWorkflowExecutionTimeout(600)
            ->withWorkflowRunTimeout(600)
            ->withWorkflowTaskTimeout(120);
    }
}
```

## 🧪 **Tests et Validation**

### **Tests d'Intégration Event Sourcing + CQS**

```php
<?php

namespace App\Tests\Integration\Temporal;

use App\Workflow\Command\Payment\PaymentCommandRequest;
use App\Workflow\Query\Payment\PaymentSearchQuery;
use App\Application\CommandBus\Temporal\TemporalEventSourcingCommandBus;
use App\Application\QueryBus\Temporal\TemporalEventSourcingQueryBus;
use App\Infrastructure\Temporal\TemporalClientFactory;

class TemporalEventSourcingCqsTest extends TestCase
{
    private TemporalEventSourcingCommandBus $commandBus;
    private TemporalEventSourcingQueryBus $queryBus;
    private TemporalClientFactory $temporalFactory;

    protected function setUp(): void
    {
        $this->temporalFactory = new TemporalClientFactory('localhost', 7233, 'test');
        $this->commandBus = new TemporalEventSourcingCommandBus(
            $this->temporalFactory->createClient(),
            $this->createMock(LoggerInterface::class)
        );
        $this->queryBus = new TemporalEventSourcingQueryBus(
            $this->temporalFactory->createClient(),
            $this->createMock(CacheItemPoolInterface::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testEventSourcingCqsSeparation(): void
    {
        // Exécuter une commande avec Event Sourcing
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

    public function testEventSourcingQueryCaching(): void
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

### **Stratégies d'Optimisation Event Sourcing + CQS**

#### **1. Cache Multi-Niveaux avec Event Sourcing**
```php
public function searchPaymentsWithCache(PaymentSearchQuery $query): PaymentSearchResult
{
    // Cache L1: Mémoire
    if (isset($this->memoryCache[$query->getCacheKey()])) {
        return $this->memoryCache[$query->getCacheKey()];
    }
    
    // Cache L2: Redis
    if ($cached = $this->redis->get("payment_search_es:{$query->getCacheKey()}")) {
        $result = PaymentSearchResult::fromArray(json_decode($cached, true));
        $this->memoryCache[$query->getCacheKey()] = $result;
        return $result;
    }
    
    // Temporal avec Event Sourcing
    $result = $this->searchPaymentsWithEventSourcing($query);
    
    // Mettre en cache
    $this->memoryCache[$query->getCacheKey()] = $result;
    $this->redis->setex("payment_search_es:{$query->getCacheKey()}", 300, json_encode($result->toArray()));
    
    return $result;
}
```

#### **2. Projections Asynchrones avec Event Sourcing**
```php
public function handleEventAsync(DomainEvent $event): void
{
    // Mettre en queue pour traitement asynchrone
    $this->messageBus->dispatch(new ProcessEventSourcingProjectionCommand($event));
}
```

#### **3. Monitoring des Workflows Event Sourcing**
```php
public function getWorkflowMetrics(): array
{
    return [
        'commandWorkflows' => $this->getCommandWorkflowMetrics(),
        'queryWorkflows' => $this->getQueryWorkflowMetrics(),
        'eventStoreWorkflows' => $this->getEventStoreWorkflowMetrics(),
        'projections' => $this->getProjectionMetrics(),
        'cacheHitRate' => $this->getCacheHitRate(),
        'averageExecutionTime' => $this->getAverageExecutionTime()
    ];
}
```

## 🎯 **Critères d'Adoption**

### **Quand Utiliser Event Sourcing + CQS avec Temporal**

#### **✅ Avantages**
- **Performance optimisée** : Séparation claire entre écriture et lecture
- **Audit trail complet** : Historique immuable de tous les événements
- **Scalabilité maximale** : Possibilité de scaler indépendamment
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

#### **🎯 Critères d'Adoption**
- **Système très complexe** : Besoins de scalabilité maximale
- **Audit trail critique** : Besoin de traçabilité complète
- **Performance critique** : Besoins de performance maximale
- **Projections multiples** : Besoin de vues différentes des données
- **Équipe très expérimentée** : Maîtrise de Temporal, Event Sourcing et CQS
- **Budget important** : Investissement en complexité justifié
- **Infrastructure disponible** : Serveur Temporal opérationnel
- **Temps de développement** : Suffisant pour implémenter cette complexité

## 🚀 **Votre Prochaine Étape**

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux voir l'approche Event Sourcing + CQRS avec Temporal" 
    subtitle="Vous voulez comprendre la combinaison ultime Event Sourcing + CQRS"
    criteria="Architecture maximale,Équipe très expérimentée,Performance critique,Audit trail complet"
    time="50-70 minutes"
    chapter="51"
    chapter-title="Stockage Temporal - Event Sourcing + CQRS"
    chapter-url="/chapitres/stockage/temporal/chapitre-51-stockage-temporal-event-sourcing-cqrs/"
  >}}
  
  {{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux explorer les autres types de stockage" 
    subtitle="Vous voulez voir les alternatives à Temporal"
    criteria="Comparaison nécessaire,Choix de stockage,Architecture à définir,Performance à optimiser"
    time="30-40 minutes"
    chapter="10"
    chapter-title="Choix du Type de Stockage"
    chapter-url="/chapitres/fondamentaux/chapitre-10-choix-type-stockage/"
  >}}
  
  {{< chapter-option 
    letter="C" 
    color="blue" 
    title="Je veux voir des exemples concrets" 
    subtitle="Vous voulez comprendre les implémentations pratiques"
    criteria="Développeur expérimenté,Besoin d'exemples pratiques,Implémentation à faire,Code à écrire"
    time="Variable"
    chapter="0"
    chapter-title="Exemples et Implémentations"
    chapter-url="/examples/"
  >}}
  
  {{< chapter-option 
    letter="D" 
    color="purple" 
    title="Je veux revenir aux fondamentaux" 
    subtitle="Vous voulez comprendre les concepts de base"
    criteria="Développeur débutant,Besoin de comprendre les concepts,Projet à structurer,Équipe à former"
    time="45-60 minutes"
    chapter="1"
    chapter-title="Introduction au Domain-Driven Design et Event Storming"
    chapter-url="/chapitres/fondamentaux/chapitre-01-introduction-event-storming-ddd/"
  >}}
{{< /chapter-nav >}}

---

*Event Sourcing + CQS avec Temporal représente l'état de l'art en matière d'architecture performante et traçable pour l'orchestration, parfaitement adapté aux besoins les plus exigeants de Gyroscops.*
