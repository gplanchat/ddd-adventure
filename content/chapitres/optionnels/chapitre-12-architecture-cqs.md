---
title: "Chapitre 15 : Architecture CQS - Command Query Separation"
description: "Séparer les commandes des requêtes pour une architecture plus claire et performante"
date: 2024-12-19
draft: true
type: "docs"
weight: 15
---

## 🎯 Objectif de ce Chapitre

### Mon Problème : Comment Séparer les Opérations de Lecture et d'Écriture ?

**Voici ce qui s'est passé avec Gyroscops** : J'avais des méthodes qui faisaient tout : elles lisaient des données, les modifiaient, et retournaient des résultats. C'était pratique au début, mais ça devenait un cauchemar pour les tests, la performance et la maintenance.

**Mais attendez...** Comment séparer proprement les commandes (qui modifient l'état) des requêtes (qui lisent l'état) ? Comment éviter les effets de bord ? Comment optimiser les performances ?

**Soudain, je réalisais que CQS était la solution !** Il me fallait une approche structurée pour séparer les responsabilités.

### CQS : Mon Guide Pratique

CQS m'a permis de :
- **Séparer** clairement les responsabilités
- **Optimiser** les performances de lecture
- **Simplifier** les tests
- **Améliorer** la maintenabilité

## Qu'est-ce que CQS ?

### Le Concept Fondamental

CQS (Command Query Separation) consiste à séparer les opérations en deux catégories : les **Commandes** (qui modifient l'état) et les **Requêtes** (qui lisent l'état). **L'idée** : Une méthode ne peut pas faire les deux à la fois.

**Avec Gyroscops, voici comment j'ai structuré CQS** :

### Les 2 Piliers de CQS

#### 1. **Commandes** - Modifier l'état sans retourner de valeur

**Voici comment j'ai implémenté les commandes avec Gyroscops** :

**Commandes Pures** :
- Modifient l'état de l'application
- Ne retournent pas de valeur (void)
- Peuvent avoir des effets de bord
- Sont idempotentes quand possible

**Exemples** :
- `createPayment()`
- `updateUser()`
- `deleteOrder()`
- `processPayment()`

#### 2. **Requêtes** - Lire l'état sans le modifier

**Voici comment j'ai implémenté les requêtes avec Gyroscops** :

**Requêtes Pures** :
- Lisent l'état de l'application
- Retournent une valeur
- N'ont pas d'effets de bord
- Sont idempotentes

**Exemples** :
- `getPayment(id)`
- `findUsersByOrganization()`
- `getOrderHistory()`
- `calculateTotal()`

## Comment Implémenter CQS

### 1. **Séparer les Interfaces**

**Avec Gyroscops** : J'ai séparé les interfaces :

```php
// ✅ Interfaces CQS Gyroscops Cloud (Projet Gyroscops Cloud)
interface PaymentCommandRepositoryInterface
{
    public function save(Payment $payment): void;
    public function update(Payment $payment): void;
    public function delete(PaymentId $id): void;
    public function processPayment(PaymentId $id): void;
}

interface PaymentQueryRepositoryInterface
{
    public function findById(PaymentId $id): ?Payment;
    public function findByOrganization(OrganizationId $organizationId): array;
    public function findByStatus(PaymentStatus $status): array;
    public function findByDateRange(\DateTimeImmutable $start, \DateTimeImmutable $end): array;
    public function countByOrganization(OrganizationId $organizationId): int;
}
```

**Résultat** : Interfaces claires et séparées.

### 2. **Implémenter les Commandes**

**Avec Gyroscops** : J'ai implémenté les commandes :

```php
// ✅ Commandes Gyroscops Cloud (Projet Gyroscops Cloud)
final class PaymentCommandService
{
    public function __construct(
        private PaymentCommandRepositoryInterface $commandRepository,
        private EventBus $eventBus,
        private LoggerInterface $logger
    ) {}
    
    public function createPayment(CreatePaymentCommand $command): void
    {
        $this->logger->info('Creating payment', [
            'organization_id' => $command->organizationId->toString(),
            'amount' => $command->amount->getAmount()->toString()
        ]);
        
        $payment = new Payment(
            $command->paymentId,
            $command->organizationId,
            $command->customerName,
            $command->customerEmail,
            $command->amount,
            PaymentStatus::PENDING,
            new \DateTimeImmutable(),
            $command->createdBy
        );
        
        $this->commandRepository->save($payment);
        
        $this->eventBus->publish(new PaymentCreated(
            $payment->getId(),
            $payment->getOrganizationId(),
            $payment->getCustomerName(),
            $payment->getCustomerEmail(),
            $payment->getAmount(),
            $payment->getCreatedAt(),
            $command->createdBy
        ));
        
        $this->logger->info('Payment created successfully', [
            'payment_id' => $payment->getId()->toString()
        ]);
    }
    
    public function updatePayment(UpdatePaymentCommand $command): void
    {
        $this->logger->info('Updating payment', [
            'payment_id' => $command->paymentId->toString()
        ]);
        
        $payment = $this->commandRepository->findById($command->paymentId);
        if (!$payment) {
            throw new PaymentNotFoundException($command->paymentId);
        }
        
        $payment->updateCustomerInfo($command->customerName, $command->customerEmail);
        $payment->updateAmount($command->amount);
        
        $this->commandRepository->update($payment);
        
        $this->eventBus->publish(new PaymentUpdated(
            $payment->getId(),
            $payment->getUpdatedAt(),
            $command->updatedBy
        ));
        
        $this->logger->info('Payment updated successfully', [
            'payment_id' => $payment->getId()->toString()
        ]);
    }
    
    public function processPayment(ProcessPaymentCommand $command): void
    {
        $this->logger->info('Processing payment', [
            'payment_id' => $command->paymentId->toString()
        ]);
        
        $payment = $this->commandRepository->findById($command->paymentId);
        if (!$payment) {
            throw new PaymentNotFoundException($command->paymentId);
        }
        
        if ($payment->getStatus() !== PaymentStatus::PENDING) {
            throw new InvalidPaymentStatusException($payment->getStatus());
        }
        
        $this->commandRepository->processPayment($command->paymentId);
        
        $this->eventBus->publish(new PaymentProcessed(
            $payment->getId(),
            new \DateTimeImmutable(),
            $command->processedBy
        ));
        
        $this->logger->info('Payment processed successfully', [
            'payment_id' => $payment->getId()->toString()
        ]);
    }
}
```

**Résultat** : Commandes claires et sans retour de valeur.

### 3. **Implémenter les Requêtes**

**Avec Gyroscops** : J'ai implémenté les requêtes :

```php
// ✅ Requêtes Gyroscops Cloud (Projet Gyroscops Cloud)
final class PaymentQueryService
{
    public function __construct(
        private PaymentQueryRepositoryInterface $queryRepository,
        private LoggerInterface $logger
    ) {}
    
    public function getPayment(PaymentId $id): ?Payment
    {
        $this->logger->debug('Getting payment', [
            'payment_id' => $id->toString()
        ]);
        
        return $this->queryRepository->findById($id);
    }
    
    public function getPaymentsByOrganization(
        OrganizationId $organizationId,
        int $page = 1,
        int $limit = 20
    ): PaginatedResult {
        $this->logger->debug('Getting payments by organization', [
            'organization_id' => $organizationId->toString(),
            'page' => $page,
            'limit' => $limit
        ]);
        
        $payments = $this->queryRepository->findByOrganization($organizationId);
        $total = $this->queryRepository->countByOrganization($organizationId);
        
        return new PaginatedResult(
            $payments,
            $page,
            $limit,
            $total
        );
    }
    
    public function getPaymentsByStatus(
        PaymentStatus $status,
        int $page = 1,
        int $limit = 20
    ): PaginatedResult {
        $this->logger->debug('Getting payments by status', [
            'status' => $status->value,
            'page' => $page,
            'limit' => $limit
        ]);
        
        $payments = $this->queryRepository->findByStatus($status);
        $total = count($payments);
        
        return new PaginatedResult(
            $payments,
            $page,
            $limit,
            $total
        );
    }
    
    public function getPaymentStatistics(OrganizationId $organizationId): PaymentStatistics
    {
        $this->logger->debug('Getting payment statistics', [
            'organization_id' => $organizationId->toString()
        ]);
        
        $totalPayments = $this->queryRepository->countByOrganization($organizationId);
        $pendingPayments = count($this->queryRepository->findByStatus(PaymentStatus::PENDING));
        $completedPayments = count($this->queryRepository->findByStatus(PaymentStatus::COMPLETED));
        $failedPayments = count($this->queryRepository->findByStatus(PaymentStatus::FAILED));
        
        return new PaymentStatistics(
            $totalPayments,
            $pendingPayments,
            $completedPayments,
            $failedPayments
        );
    }
}
```

**Résultat** : Requêtes optimisées et sans effets de bord.

### 4. **Créer les Contrôleurs API**

**Avec Gyroscops** : J'ai créé les contrôleurs :

```php
// ✅ Contrôleurs CQS Gyroscops Cloud (Projet Gyroscops Cloud)
final class PaymentCommandController
{
    public function __construct(
        private PaymentCommandService $commandService,
        private LoggerInterface $logger
    ) {}
    
    #[Route('/api/payments', methods: ['POST'])]
    public function createPayment(CreatePaymentRequest $request): JsonResponse
    {
        try {
            $command = new CreatePaymentCommand(
                PaymentId::generate(),
                new OrganizationId($request->organizationId),
                $request->customerName,
                $request->customerEmail,
                new Price(BigDecimal::of($request->amount), Currencies::from($request->currency)),
                new UserId($request->createdBy)
            );
            
            $this->commandService->createPayment($command);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Payment created successfully',
                'payment_id' => $command->paymentId->toString()
            ], 201);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create payment', [
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to create payment',
                'error' => $e->getMessage()
            ], 400);
        }
    }
    
    #[Route('/api/payments/{id}', methods: ['PUT'])]
    public function updatePayment(string $id, UpdatePaymentRequest $request): JsonResponse
    {
        try {
            $command = new UpdatePaymentCommand(
                new PaymentId($id),
                $request->customerName,
                $request->customerEmail,
                new Price(BigDecimal::of($request->amount), Currencies::from($request->currency)),
                new UserId($request->updatedBy)
            );
            
            $this->commandService->updatePayment($command);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Payment updated successfully'
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update payment', [
                'payment_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to update payment',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}

final class PaymentQueryController
{
    public function __construct(
        private PaymentQueryService $queryService,
        private LoggerInterface $logger
    ) {}
    
    #[Route('/api/payments/{id}', methods: ['GET'])]
    public function getPayment(string $id): JsonResponse
    {
        try {
            $payment = $this->queryService->getPayment(new PaymentId($id));
            
            if (!$payment) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }
            
            return new JsonResponse([
                'success' => true,
                'data' => $payment->toArray()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get payment', [
                'payment_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to get payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    #[Route('/api/payments', methods: ['GET'])]
    public function getPayments(Request $request): JsonResponse
    {
        try {
            $organizationId = new OrganizationId($request->query->get('organization_id'));
            $page = (int) $request->query->get('page', 1);
            $limit = (int) $request->query->get('limit', 20);
            
            $result = $this->queryService->getPaymentsByOrganization($organizationId, $page, $limit);
            
            return new JsonResponse([
                'success' => true,
                'data' => $result->getData(),
                'pagination' => $result->getPagination()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get payments', [
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to get payments',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

**Résultat** : Contrôleurs séparés et spécialisés.

## Les Avantages de CQS

### 1. **Clarté du Code**

**Avec Gyroscops** : CQS m'a donné une clarté du code :
- Séparation claire des responsabilités
- Code plus lisible
- Intention explicite
- Maintenance facilitée

**Résultat** : Code plus maintenable et compréhensible.

### 2. **Optimisation des Performances**

**Avec Gyroscops** : CQS m'a permis d'optimiser les performances :
- Requêtes optimisées pour la lecture
- Commandes optimisées pour l'écriture
- Cache spécialisé
- Indexation adaptée

**Résultat** : Performances améliorées.

### 3. **Tests Simplifiés**

**Avec Gyroscops** : CQS a simplifié les tests :
- Tests de commandes isolés
- Tests de requêtes isolés
- Mocks plus simples
- Couverture de test améliorée

**Résultat** : Tests plus fiables et maintenables.

### 4. **Évolutivité**

**Avec Gyroscops** : CQS m'a donné de l'évolutivité :
- Évolution indépendante des commandes et requêtes
- Ajout de nouvelles fonctionnalités
- Refactoring facilité
- Architecture modulaire

**Résultat** : Évolution facilitée.

## Les Inconvénients de CQS

### 1. **Complexité Accrue**

**Avec Gyroscops** : CQS a ajouté de la complexité :
- Plus de classes et interfaces
- Séparation à maintenir
- Coordination nécessaire
- Courbe d'apprentissage

**Résultat** : Architecture plus complexe.

### 2. **Duplication de Code**

**Avec Gyroscops** : CQS peut créer de la duplication :
- Logique similaire dans commandes et requêtes
- Validation dupliquée
- Mapping dupliqué
- Maintenance de deux côtés

**Résultat** : Code dupliqué à maintenir.

### 3. **Performance d'Écriture**

**Avec Gyroscops** : CQS peut impacter les écritures :
- Plus d'appels de méthodes
- Validation multiple
- Coordination des services
- Latence accrue

**Résultat** : Performance d'écriture potentiellement dégradée.

### 4. **Gestion des Transactions**

**Avec Gyroscops** : CQS complique les transactions :
- Transactions distribuées
- Coordination des commandes
- Rollback complexe
- Gestion des erreurs

**Résultat** : Gestion des transactions plus complexe.

## Les Pièges à Éviter

### 1. **Mélanger Commandes et Requêtes**

**❌ Mauvais** : Une méthode qui modifie et retourne
**✅ Bon** : Séparation claire des responsabilités

**Pourquoi c'est important ?** CQS perd son sens si on mélange.

### 2. **Ignorer les Effets de Bord**

**❌ Mauvais** : Requêtes avec effets de bord
**✅ Bon** : Requêtes pures

**Pourquoi c'est crucial ?** Les effets de bord cassent CQS.

### 3. **Duplication Excessive**

**❌ Mauvais** : Code dupliqué partout
**✅ Bon** : Extraction des parties communes

**Pourquoi c'est essentiel ?** La duplication complique la maintenance.

### 4. **Transactions Mal Gérées**

**❌ Mauvais** : Pas de gestion des transactions
**✅ Bon** : Transactions bien définies

**Pourquoi c'est la clé ?** Les transactions sont critiques pour la cohérence.

## L'Évolution vers CQS

### Phase 1 : Architecture Monolithique

**Avec Gyroscops** : Au début, j'avais une architecture monolithique :
- Méthodes qui font tout
- Pas de séparation
- Tests difficiles
- Performance non optimale

**Résultat** : Développement rapide, maintenance difficile.

### Phase 2 : Introduction de CQS

**Avec Gyroscops** : J'ai introduit CQS :
- Séparation des commandes et requêtes
- Interfaces spécialisées
- Tests simplifiés
- Performance améliorée

**Résultat** : Architecture plus claire, maintenance facilitée.

### Phase 3 : CQS Complet

**Avec Gyroscops** : Maintenant, j'ai un CQS complet :
- Séparation claire des responsabilités
- Optimisation des performances
- Tests robustes
- Architecture modulaire

**Résultat** : Architecture claire et performante.

## 🏗️ Implémentation Concrète dans le Projet Gyroscops Cloud

### CQS Appliqué à Gyroscops Cloud

Le Gyroscops Cloud applique concrètement les principes de CQS à travers son architecture et ses ADR (Architecture Decision Records). Voici comment :

#### Services CQS Gyroscops Cloud

```php
// ✅ Services CQS Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveCQSService
{
    public function __construct(
        private PaymentCommandService $commandService,
        private PaymentQueryService $queryService,
        private LoggerInterface $logger
    ) {}
    
    public function executeCommand(CommandInterface $command): void
    {
        $this->logger->info('Executing command', [
            'command_type' => get_class($command)
        ]);
        
        try {
            match (get_class($command)) {
                CreatePaymentCommand::class => $this->commandService->createPayment($command),
                UpdatePaymentCommand::class => $this->commandService->updatePayment($command),
                ProcessPaymentCommand::class => $this->commandService->processPayment($command),
                default => throw new UnknownCommandException(get_class($command))
            };
            
            $this->logger->info('Command executed successfully', [
                'command_type' => get_class($command)
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Command execution failed', [
                'command_type' => get_class($command),
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    public function executeQuery(QueryInterface $query): mixed
    {
        $this->logger->info('Executing query', [
            'query_type' => get_class($query)
        ]);
        
        try {
            $result = match (get_class($query)) {
                GetPaymentQuery::class => $this->queryService->getPayment($query->paymentId),
                GetPaymentsByOrganizationQuery::class => $this->queryService->getPaymentsByOrganization(
                    $query->organizationId,
                    $query->page,
                    $query->limit
                ),
                GetPaymentStatisticsQuery::class => $this->queryService->getPaymentStatistics($query->organizationId),
                default => throw new UnknownQueryException(get_class($query))
            };
            
            $this->logger->info('Query executed successfully', [
                'query_type' => get_class($query)
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logger->error('Query execution failed', [
                'query_type' => get_class($query),
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
```

#### Configuration CQS Gyroscops Cloud

```php
// ✅ Configuration CQS Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveCQSConfiguration
{
    public function configureServices(ContainerBuilder $container): void
    {
        // Command Services
        $container->register(PaymentCommandService::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Query Services
        $container->register(PaymentQueryService::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // CQS Service
        $container->register(HiveCQSService::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Command Handlers
        $container->register(CreatePaymentCommandHandler::class)
            ->addTag('command.handler', ['command' => CreatePaymentCommand::class]);
        
        // Query Handlers
        $container->register(GetPaymentQueryHandler::class)
            ->addTag('query.handler', ['query' => GetPaymentQuery::class]);
    }
}
```

### Références aux ADR du Projet Gyroscops Cloud

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du Gyroscops Cloud :
- **HIVE006** : Query Models for API Platform - Modèles de requête
- **HIVE007** : Command Models for API Platform - Modèles de commande
- **HIVE040** : Enhanced Models with Property Access Patterns - Modèles enrichis pour CQS
- **HIVE041** : Cross-Cutting Concerns Architecture - Architecture des préoccupations transversales

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux comprendre CQRS complet" 
    subtitle="Vous voulez voir la séparation complète entre commandes et requêtes" 
    criteria="Équipe très expérimentée,Besoin de CQRS complet,Complexité très élevée,Performance critique" 
    time="30-45 minutes" 
    chapter="15" 
    chapter-title="Architecture CQRS avec API Platform" 
    chapter-url="/chapitres/optionnels/chapitre-15-architecture-cqrs/" 
  >}}}
  
  {{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux comprendre les chapitres de stockage" 
    subtitle="Vous voulez voir comment implémenter la persistance selon différents patterns" 
    criteria="Équipe expérimentée,Besoin de comprendre la persistance,Patterns de stockage à choisir,Implémentation à faire" 
    time="30-45 minutes" 
    chapter="15" 
    chapter-title="Stockage SQL - Approche Classique" 
    chapter-url="/chapitres/stockage/chapitre-15-stockage-sql-classique/" 
  >}}}
  
  {{< chapter-option 
    letter="C" 
    color="red" 
    title="Je veux comprendre les chapitres techniques" 
    subtitle="Vous voulez voir les aspects techniques d'affinement" 
    criteria="Équipe expérimentée,Besoin de comprendre les aspects techniques,Qualité et performance importantes,Bonnes pratiques à appliquer" 
    time="25-35 minutes" 
    chapter="58" 
    chapter-title="Gestion des Données et Validation" 
    chapter-url="/chapitres/techniques/chapitre-58-gestion-donnees-validation/" 
  >}}}
  
  {{< chapter-option 
    letter="D" 
    color="blue" 
    title="Je veux comprendre les chapitres avancés" 
    subtitle="Vous voulez voir la sécurité et le frontend" 
    criteria="Équipe expérimentée,Besoin de comprendre la sécurité et le frontend,Intégration importante,Bonnes pratiques à appliquer" 
    time="25-35 minutes" 
    chapter="62" 
    chapter-title="Sécurité et Autorisation" 
    chapter-url="/chapitres/avances/chapitre-62-securite-autorisation/" 
  >}}}
  
{{< /chapter-nav >}}