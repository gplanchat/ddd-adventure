---
title: "Stockage Temporal - CQS"
description: "Implémentation Command Query Separation avec Temporal Workflows pour optimiser les performances"
date: 2024-12-19
draft: true
type: "docs"
weight: 47
---

# ⚡ Stockage Temporal - CQS

## 🎯 **Contexte et Objectifs**

### **Pourquoi CQS avec Temporal ?**

La combinaison CQS avec Temporal offre une architecture optimisée qui sépare clairement les responsabilités tout en conservant les avantages de l'orchestration et de la résilience des workflows.

#### **Avantages de CQS avec Temporal**
- **Performance optimisée** : Séparation claire entre écriture et lecture
- **Scalabilité** : Possibilité de scaler indépendamment les commandes et requêtes
- **Flexibilité** : Requêtes optimisées pour chaque usage
- **Maintenabilité** : Code plus clair et organisé
- **Résilience** : Workflows robustes avec reprise automatique

### **Contexte Gyroscops**

Dans notre écosystème **User → Organization → Workflow → Cloud Resources → Billing**, CQS avec Temporal est particulièrement pertinent pour :
- **Workflows de commande** : Orchestration des processus de modification
- **Workflows de requête** : Optimisation des lectures et analytics
- **Processus de facturation** : Séparation des écritures et lectures de facturation
- **Intégrations complexes** : Orchestration des intégrations avec séparation des responsabilités

## 🏗️ **Architecture CQS avec Temporal**

### **Séparation des Responsabilités**

#### **Côté Commande (Write)**
- **Command Workflows** : Orchestration des processus de modification
- **Command Activities** : Exécution des activités de modification
- **Event Handlers** : Gestion des événements de domaine
- **Bulk Operations** : Optimisation des écritures

#### **Côté Requête (Read)**
- **Query Workflows** : Orchestration des processus de lecture
- **Query Activities** : Exécution des activités de lecture
- **Search Services** : Services de recherche spécialisés
- **Caches** : Optimisation des performances

### **Flux de Données**

```mermaid
graph TD
    A[Command] --> B[Command Workflow]
    B --> C[Command Activities]
    C --> D[Write Operations]
    D --> E[Event Handlers]
    E --> F[State Updates]
    
    G[Query] --> H[Query Workflow]
    H --> I[Query Activities]
    I --> J[Read Operations]
    J --> K[Search Results]
    K --> L[Response]
    
    M[Event] --> N[Event Handler]
    N --> B
    
    O[Cache] --> P[Cache Manager]
    P --> J
    J --> Q[Cached Data]
    Q --> R[Fast Response]
```

## 💻 **Implémentation Pratique**

### **1. Command Side Implementation**

#### **Command Workflow pour les Paiements**

```php
<?php

namespace App\Workflow\Command\Payment;

use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;
use Temporal\Workflow\ActivityInterface;
use Temporal\Workflow\ActivityMethod;
use Temporal\Workflow\Workflow;

#[WorkflowInterface]
interface PaymentCommandWorkflowInterface
{
    #[WorkflowMethod]
    public function processPaymentCommand(PaymentCommandRequest $request): PaymentCommandResult;
    
    #[WorkflowMethod]
    public function updatePaymentCommand(PaymentUpdateCommandRequest $request): PaymentCommandResult;
    
    #[WorkflowMethod]
    public function deletePaymentCommand(PaymentDeleteCommandRequest $request): PaymentCommandResult;
}

#[ActivityInterface]
interface PaymentCommandActivityInterface
{
    #[ActivityMethod]
    public function validatePaymentCommand(PaymentCommandRequest $request): ValidationResult;
    
    #[ActivityMethod]
    public function executePaymentCommand(PaymentCommandRequest $request): CommandExecutionResult;
    
    #[ActivityMethod]
    public function publishPaymentEvent(PaymentEvent $event): void;
}

class PaymentCommandWorkflow implements PaymentCommandWorkflowInterface
{
    private PaymentCommandActivityInterface $commandActivity;

    public function __construct()
    {
        $this->commandActivity = Workflow::newActivityStub(PaymentCommandActivityInterface::class);
    }

    public function processPaymentCommand(PaymentCommandRequest $request): PaymentCommandResult
    {
        try {
            // Validation de la commande
            $validation = yield $this->commandActivity->validatePaymentCommand($request);
            
            if (!$validation->isValid()) {
                return new PaymentCommandResult(false, $validation->getError());
            }
            
            // Exécution de la commande
            $execution = yield $this->commandActivity->executePaymentCommand($request);
            
            if (!$execution->isSuccess()) {
                return new PaymentCommandResult(false, $execution->getError());
            }
            
            // Publication de l'événement
            $event = new PaymentProcessedEvent(
                $request->getPaymentId(),
                $request->getAmount(),
                $request->getCurrency(),
                $request->getOrganizationId()
            );
            
            yield $this->commandActivity->publishPaymentEvent($event);
            
            return new PaymentCommandResult(true, 'Payment command processed successfully');
            
        } catch (\Exception $e) {
            return new PaymentCommandResult(false, $e->getMessage());
        }
    }

    public function updatePaymentCommand(PaymentUpdateCommandRequest $request): PaymentCommandResult
    {
        try {
            // Validation de la commande de mise à jour
            $validation = yield $this->commandActivity->validatePaymentCommand($request);
            
            if (!$validation->isValid()) {
                return new PaymentCommandResult(false, $validation->getError());
            }
            
            // Exécution de la mise à jour
            $execution = yield $this->commandActivity->executePaymentCommand($request);
            
            if (!$execution->isSuccess()) {
                return new PaymentCommandResult(false, $execution->getError());
            }
            
            // Publication de l'événement de mise à jour
            $event = new PaymentUpdatedEvent(
                $request->getPaymentId(),
                $request->getUpdates(),
                $request->getOrganizationId()
            );
            
            yield $this->commandActivity->publishPaymentEvent($event);
            
            return new PaymentCommandResult(true, 'Payment update command processed successfully');
            
        } catch (\Exception $e) {
            return new PaymentCommandResult(false, $e->getMessage());
        }
    }

    public function deletePaymentCommand(PaymentDeleteCommandRequest $request): PaymentCommandResult
    {
        try {
            // Validation de la commande de suppression
            $validation = yield $this->commandActivity->validatePaymentCommand($request);
            
            if (!$validation->isValid()) {
                return new PaymentCommandResult(false, $validation->getError());
            }
            
            // Exécution de la suppression
            $execution = yield $this->commandActivity->executePaymentCommand($request);
            
            if (!$execution->isSuccess()) {
                return new PaymentCommandResult(false, $execution->getError());
            }
            
            // Publication de l'événement de suppression
            $event = new PaymentDeletedEvent(
                $request->getPaymentId(),
                $request->getOrganizationId()
            );
            
            yield $this->commandActivity->publishPaymentEvent($event);
            
            return new PaymentCommandResult(true, 'Payment delete command processed successfully');
            
        } catch (\Exception $e) {
            return new PaymentCommandResult(false, $e->getMessage());
        }
    }
}
```

#### **Command Activities**

```php
<?php

namespace App\Workflow\Command\Payment;

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;
use App\Domain\Payment\PaymentRepositoryInterface;
use App\Domain\Payment\Payment;
use Psr\Log\LoggerInterface;

class PaymentCommandActivity implements PaymentCommandActivityInterface
{
    private PaymentRepositoryInterface $paymentRepository;
    private LoggerInterface $logger;

    public function __construct(
        PaymentRepositoryInterface $paymentRepository,
        LoggerInterface $logger
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->logger = $logger;
    }

    #[ActivityMethod]
    public function validatePaymentCommand(PaymentCommandRequest $request): ValidationResult
    {
        try {
            $this->logger->info('Validating payment command', [
                'paymentId' => $request->getPaymentId(),
                'commandType' => $request->getCommandType()
            ]);
            
            // Validation métier spécifique au type de commande
            switch ($request->getCommandType()) {
                case 'CREATE':
                    return $this->validateCreateCommand($request);
                case 'UPDATE':
                    return $this->validateUpdateCommand($request);
                case 'DELETE':
                    return $this->validateDeleteCommand($request);
                default:
                    return new ValidationResult(false, 'Unknown command type');
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Payment command validation failed', [
                'paymentId' => $request->getPaymentId(),
                'error' => $e->getMessage()
            ]);
            
            return new ValidationResult(false, $e->getMessage());
        }
    }

    #[ActivityMethod]
    public function executePaymentCommand(PaymentCommandRequest $request): CommandExecutionResult
    {
        try {
            $this->logger->info('Executing payment command', [
                'paymentId' => $request->getPaymentId(),
                'commandType' => $request->getCommandType()
            ]);
            
            // Exécution spécifique au type de commande
            switch ($request->getCommandType()) {
                case 'CREATE':
                    return $this->executeCreateCommand($request);
                case 'UPDATE':
                    return $this->executeUpdateCommand($request);
                case 'DELETE':
                    return $this->executeDeleteCommand($request);
                default:
                    return new CommandExecutionResult(false, 'Unknown command type');
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Payment command execution failed', [
                'paymentId' => $request->getPaymentId(),
                'error' => $e->getMessage()
            ]);
            
            return new CommandExecutionResult(false, $e->getMessage());
        }
    }

    #[ActivityMethod]
    public function publishPaymentEvent(PaymentEvent $event): void
    {
        try {
            $this->logger->info('Publishing payment event', [
                'eventType' => $event->getEventType(),
                'paymentId' => $event->getPaymentId()
            ]);
            
            // Publication de l'événement (ex: via Event Bus)
            $this->eventBus->publish($event);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to publish payment event', [
                'eventType' => $event->getEventType(),
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    private function validateCreateCommand(PaymentCommandRequest $request): ValidationResult
    {
        if ($request->getAmount() <= 0) {
            return new ValidationResult(false, 'Invalid amount');
        }
        
        if (empty($request->getCurrency())) {
            return new ValidationResult(false, 'Currency required');
        }
        
        return new ValidationResult(true);
    }

    private function executeCreateCommand(PaymentCommandRequest $request): CommandExecutionResult
    {
        $payment = new Payment(
            $request->getPaymentId(),
            $request->getOrganizationId(),
            $request->getUserId(),
            $request->getAmount(),
            $request->getCurrency(),
            'processing',
            $request->getDescription(),
            new \DateTime()
        );
        
        $this->paymentRepository->save($payment);
        
        return new CommandExecutionResult(true, 'Payment created successfully');
    }

    private function validateUpdateCommand(PaymentCommandRequest $request): ValidationResult
    {
        $payment = $this->paymentRepository->findById($request->getPaymentId());
        
        if (!$payment) {
            return new ValidationResult(false, 'Payment not found');
        }
        
        return new ValidationResult(true);
    }

    private function executeUpdateCommand(PaymentCommandRequest $request): CommandExecutionResult
    {
        $payment = $this->paymentRepository->findById($request->getPaymentId());
        
        if ($request->getAmount()) {
            $payment->updateAmount($request->getAmount());
        }
        
        if ($request->getDescription()) {
            $payment->updateDescription($request->getDescription());
        }
        
        $this->paymentRepository->save($payment);
        
        return new CommandExecutionResult(true, 'Payment updated successfully');
    }

    private function validateDeleteCommand(PaymentCommandRequest $request): ValidationResult
    {
        $payment = $this->paymentRepository->findById($request->getPaymentId());
        
        if (!$payment) {
            return new ValidationResult(false, 'Payment not found');
        }
        
        if ($payment->getStatus() === 'completed') {
            return new ValidationResult(false, 'Cannot delete completed payment');
        }
        
        return new ValidationResult(true);
    }

    private function executeDeleteCommand(PaymentCommandRequest $request): CommandExecutionResult
    {
        $this->paymentRepository->delete($request->getPaymentId());
        
        return new CommandExecutionResult(true, 'Payment deleted successfully');
    }
}
```

### **2. Query Side Implementation**

#### **Query Workflow pour les Paiements**

```php
<?php

namespace App\Workflow\Query\Payment;

use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;
use Temporal\Workflow\ActivityInterface;
use Temporal\Workflow\ActivityMethod;
use Temporal\Workflow\Workflow;

#[WorkflowInterface]
interface PaymentQueryWorkflowInterface
{
    #[WorkflowMethod]
    public function searchPayments(PaymentSearchQuery $query): PaymentSearchResult;
    
    #[WorkflowMethod]
    public function getPaymentById(PaymentByIdQuery $query): ?Payment;
    
    #[WorkflowMethod]
    public function getPaymentStatistics(PaymentStatisticsQuery $query): array;
}

#[ActivityInterface]
interface PaymentQueryActivityInterface
{
    #[ActivityMethod]
    public function searchPaymentsInDatabase(PaymentSearchQuery $query): array;
    
    #[ActivityMethod]
    public function getPaymentFromDatabase(string $paymentId): ?Payment;
    
    #[ActivityMethod]
    public function calculatePaymentStatistics(PaymentStatisticsQuery $query): array;
}

class PaymentQueryWorkflow implements PaymentQueryWorkflowInterface
{
    private PaymentQueryActivityInterface $queryActivity;

    public function __construct()
    {
        $this->queryActivity = Workflow::newActivityStub(PaymentQueryActivityInterface::class);
    }

    public function searchPayments(PaymentSearchQuery $query): PaymentSearchResult
    {
        try {
            // Recherche dans la base de données
            $payments = yield $this->queryActivity->searchPaymentsInDatabase($query);
            
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

    public function getPaymentById(PaymentByIdQuery $query): ?Payment
    {
        try {
            return yield $this->queryActivity->getPaymentFromDatabase($query->getPaymentId());
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getPaymentStatistics(PaymentStatisticsQuery $query): array
    {
        try {
            return yield $this->queryActivity->calculatePaymentStatistics($query);
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

#### **Query Activities**

```php
<?php

namespace App\Workflow\Query\Payment;

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;
use App\Domain\Payment\PaymentRepositoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Cache\CacheItemPoolInterface;

class PaymentQueryActivity implements PaymentQueryActivityInterface
{
    private PaymentRepositoryInterface $paymentRepository;
    private LoggerInterface $logger;
    private CacheItemPoolInterface $cache;

    public function __construct(
        PaymentRepositoryInterface $paymentRepository,
        LoggerInterface $logger,
        CacheItemPoolInterface $cache
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->logger = $logger;
        $this->cache = $cache;
    }

    #[ActivityMethod]
    public function searchPaymentsInDatabase(PaymentSearchQuery $query): array
    {
        try {
            $cacheKey = 'payment_search_' . md5(serialize($query));
            $cachedItem = $this->cache->getItem($cacheKey);
            
            if ($cachedItem->isHit()) {
                $this->logger->debug('Payment search result served from cache', [
                    'query' => $query->toArray()
                ]);
                return $cachedItem->get();
            }
            
            $this->logger->info('Searching payments in database', [
                'query' => $query->toArray()
            ]);
            
            $payments = $this->paymentRepository->search($query);
            
            // Mettre en cache
            $cachedItem->set($payments);
            $cachedItem->expiresAfter(300); // 5 minutes
            $this->cache->save($cachedItem);
            
            return $payments;
            
        } catch (\Exception $e) {
            $this->logger->error('Payment search failed', [
                'query' => $query->toArray(),
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    #[ActivityMethod]
    public function getPaymentFromDatabase(string $paymentId): ?Payment
    {
        try {
            $cacheKey = 'payment_' . $paymentId;
            $cachedItem = $this->cache->getItem($cacheKey);
            
            if ($cachedItem->isHit()) {
                $this->logger->debug('Payment served from cache', [
                    'paymentId' => $paymentId
                ]);
                return $cachedItem->get();
            }
            
            $this->logger->info('Getting payment from database', [
                'paymentId' => $paymentId
            ]);
            
            $payment = $this->paymentRepository->findById($paymentId);
            
            // Mettre en cache
            if ($payment) {
                $cachedItem->set($payment);
                $cachedItem->expiresAfter(600); // 10 minutes
                $this->cache->save($cachedItem);
            }
            
            return $payment;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get payment from database', [
                'paymentId' => $paymentId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    #[ActivityMethod]
    public function calculatePaymentStatistics(PaymentStatisticsQuery $query): array
    {
        try {
            $cacheKey = 'payment_stats_' . md5(serialize($query));
            $cachedItem = $this->cache->getItem($cacheKey);
            
            if ($cachedItem->isHit()) {
                $this->logger->debug('Payment statistics served from cache', [
                    'query' => $query->toArray()
                ]);
                return $cachedItem->get();
            }
            
            $this->logger->info('Calculating payment statistics', [
                'query' => $query->toArray()
            ]);
            
            $statistics = $this->paymentRepository->getStatistics($query);
            
            // Mettre en cache
            $cachedItem->set($statistics);
            $cachedItem->expiresAfter(300); // 5 minutes
            $this->cache->save($cachedItem);
            
            return $statistics;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to calculate payment statistics', [
                'query' => $query->toArray(),
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
}
```

### **3. Service de Synchronisation**

#### **Service de Synchronisation CQS**

```php
<?php

namespace App\Application\Service\Temporal;

use App\Workflow\Command\Payment\PaymentCommandWorkflowInterface;
use App\Workflow\Query\Payment\PaymentQueryWorkflowInterface;
use App\Workflow\Command\Payment\PaymentCommandRequest;
use App\Workflow\Query\Payment\PaymentSearchQuery;
use Temporal\Client\WorkflowClientInterface;
use Psr\Log\LoggerInterface;

class PaymentCqsService
{
    private WorkflowClientInterface $workflowClient;
    private LoggerInterface $logger;

    public function __construct(
        WorkflowClientInterface $workflowClient,
        LoggerInterface $logger
    ) {
        $this->workflowClient = $workflowClient;
        $this->logger = $logger;
    }

    public function processPaymentCommand(PaymentCommandRequest $request): PaymentCommandResult
    {
        try {
            $workflowOptions = $this->createCommandWorkflowOptions($request->getPaymentId());
            
            $workflow = $this->workflowClient->newWorkflowStub(
                PaymentCommandWorkflowInterface::class,
                $workflowOptions
            );
            
            $result = $workflow->processPaymentCommand($request);
            
            $this->logger->info('Payment command processed', [
                'paymentId' => $request->getPaymentId(),
                'success' => $result->isSuccess()
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logger->error('Payment command processing failed', [
                'paymentId' => $request->getPaymentId(),
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    public function searchPayments(PaymentSearchQuery $query): PaymentSearchResult
    {
        try {
            $workflowOptions = $this->createQueryWorkflowOptions('payment-search-' . uniqid());
            
            $workflow = $this->workflowClient->newWorkflowStub(
                PaymentQueryWorkflowInterface::class,
                $workflowOptions
            );
            
            $result = $workflow->searchPayments($query);
            
            $this->logger->info('Payment search completed', [
                'query' => $query->toArray(),
                'results' => $result->getTotal()
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logger->error('Payment search failed', [
                'query' => $query->toArray(),
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    private function createCommandWorkflowOptions(string $paymentId): WorkflowOptions
    {
        return WorkflowOptions::new()
            ->withWorkflowId('payment-command-' . $paymentId)
            ->withWorkflowExecutionTimeout(300)
            ->withWorkflowRunTimeout(300)
            ->withWorkflowTaskTimeout(60);
    }

    private function createQueryWorkflowOptions(string $workflowId): WorkflowOptions
    {
        return WorkflowOptions::new()
            ->withWorkflowId($workflowId)
            ->withWorkflowExecutionTimeout(120)
            ->withWorkflowRunTimeout(120)
            ->withWorkflowTaskTimeout(30);
    }
}
```

## 🧪 **Tests et Validation**

### **Tests d'Intégration CQS**

```php
<?php

namespace App\Tests\Integration\Temporal;

use App\Workflow\Command\Payment\PaymentCommandRequest;
use App\Workflow\Query\Payment\PaymentSearchQuery;
use App\Application\Service\Temporal\PaymentCqsService;
use App\Infrastructure\Temporal\TemporalClientFactory;

class TemporalPaymentCqsTest extends TestCase
{
    private PaymentCqsService $cqsService;
    private TemporalClientFactory $temporalFactory;

    protected function setUp(): void
    {
        $this->temporalFactory = new TemporalClientFactory('localhost', 7233, 'test');
        $this->cqsService = new PaymentCqsService(
            $this->temporalFactory->createClient(),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testCommandQuerySeparation(): void
    {
        // Exécuter une commande
        $command = new PaymentCommandRequest(
            'CREATE',
            'payment-123',
            'org-456',
            'user-789',
            100.00,
            'EUR',
            'Test payment'
        );
        
        $result = $this->cqsService->processPaymentCommand($command);
        
        $this->assertTrue($result->isSuccess());
        
        // Vérifier avec une requête
        $query = new PaymentSearchQuery('org-456', 0, 10);
        $searchResult = $this->cqsService->searchPayments($query);
        
        $this->assertGreaterThan(0, $searchResult->getTotal());
    }

    public function testQueryCaching(): void
    {
        $query = new PaymentSearchQuery('org-456', 0, 10);
        
        // Première recherche
        $result1 = $this->cqsService->searchPayments($query);
        
        // Deuxième recherche (devrait utiliser le cache)
        $result2 = $this->cqsService->searchPayments($query);
        
        $this->assertEquals($result1->getTotal(), $result2->getTotal());
    }
}
```

## 📊 **Performance et Optimisation**

### **Stratégies d'Optimisation CQS**

#### **1. Cache Stratégique**
```php
public function searchPaymentsWithCache(PaymentSearchQuery $query): PaymentSearchResult
{
    $cacheKey = 'payment_search_' . md5(serialize($query));
    
    if ($cached = $this->cache->get($cacheKey)) {
        return $cached;
    }
    
    $result = $this->searchPayments($query);
    $this->cache->set($cacheKey, $result, 300);
    
    return $result;
}
```

#### **2. Workflows Asynchrones**
```php
public function processPaymentCommandAsync(PaymentCommandRequest $request): string
{
    $workflowOptions = $this->createCommandWorkflowOptions($request->getPaymentId());
    
    $workflow = $this->workflowClient->newWorkflowStub(
        PaymentCommandWorkflowInterface::class,
        $workflowOptions
    );
    
    // Démarrer le workflow de manière asynchrone
    $this->workflowClient->start($workflow, $request);
    
    return $workflowOptions->getWorkflowId();
}
```

#### **3. Monitoring des Workflows**
```php
public function getWorkflowMetrics(): array
{
    return [
        'commandWorkflows' => $this->getCommandWorkflowMetrics(),
        'queryWorkflows' => $this->getQueryWorkflowMetrics(),
        'cacheHitRate' => $this->getCacheHitRate(),
        'averageExecutionTime' => $this->getAverageExecutionTime()
    ];
}
```

## 🎯 **Critères d'Adoption**

### **Quand Utiliser CQS avec Temporal**

#### **✅ Avantages**
- **Performance optimisée** : Séparation claire entre écriture et lecture
- **Scalabilité** : Possibilité de scaler indépendamment
- **Flexibilité** : Requêtes optimisées pour chaque usage
- **Maintenabilité** : Code plus clair et organisé
- **Résilience** : Workflows robustes avec reprise automatique

#### **❌ Inconvénients**
- **Complexité** : Architecture plus complexe
- **Infrastructure** : Nécessite un serveur Temporal
- **Latence** : Overhead pour les opérations simples
- **Expertise** : Équipe expérimentée requise

#### **🎯 Critères d'Adoption**
- **Performance importante** : Besoins de performance élevée
- **Processus métier complexes** : Workflows qui nécessitent de l'orchestration
- **Séparation des responsabilités** : Besoin de séparer clairement les commandes et requêtes
- **Équipe expérimentée** : Maîtrise de Temporal et CQS
- **Infrastructure disponible** : Serveur Temporal opérationnel
- **Cache nécessaire** : Besoin de mise en cache des requêtes

## 🚀 **Votre Prochaine Étape**

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux voir l'approche CQRS avec Temporal" 
    subtitle="Vous voulez comprendre la séparation complète des responsabilités"
    criteria="Architecture complexe,Équipe très expérimentée,Performance critique,Scalabilité maximale"
    time="45-60 minutes"
    chapter="48"
    chapter-title="Stockage Temporal - CQRS"
    chapter-url="/chapitres/stockage/temporal/chapitre-48-stockage-temporal-cqrs/"
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

*CQS avec Temporal offre un équilibre optimal entre performance et orchestration, parfaitement adapté aux besoins de workflows complexes de Gyroscops.*
