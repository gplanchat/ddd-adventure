---
title: "Chapitre 15 : Architecture CQRS avec API Platform"
description: "Maîtriser CQRS complet pour une séparation optimale entre commandes et requêtes"
date: 2024-12-19
draft: true
type: "docs"
weight: 15
---

## 🎯 Objectif de ce Chapitre

### Mon Problème : Comment Optimiser Complètement les Lectures et les Écritures ?

**Voici ce qui s'est passé avec Gyroscops** : J'avais CQS qui fonctionnait bien, mais j'avais encore des problèmes. Les requêtes étaient lentes à cause des jointures complexes, et les commandes étaient bloquées par les verrous de lecture. J'avais besoin d'une séparation complète.

**Mais attendez...** Comment avoir des modèles de lecture et d'écriture complètement différents ? Comment synchroniser les données ? Comment gérer la cohérence ?

**Soudain, je réalisais que CQRS était la solution !** Il me fallait une architecture complète avec des modèles séparés.

### CQRS : Mon Guide Complet

CQRS m'a permis de :
- **Optimiser** complètement les lectures et écritures
- **Séparer** les modèles de données
- **Améliorer** les performances
- **Scaler** indépendamment

## Qu'est-ce que CQRS ?

### Le Concept Fondamental

CQRS (Command Query Responsibility Segregation) consiste à séparer complètement les modèles de lecture et d'écriture. **L'idée** : Avoir des modèles de données optimisés pour chaque usage, avec des bases de données et des structures différentes.

**Avec Gyroscops, voici comment j'ai structuré CQRS** :

### Les 4 Piliers de CQRS

#### 1. **Modèles de Commande** - Optimisés pour l'écriture

**Voici comment j'ai implémenté les modèles de commande avec Gyroscops** :

**Modèles Normaux** :
- Structure optimisée pour les écritures
- Validation métier
- Invariants d'agrégat
- Événements de domaine

**Exemples** :
- `Payment` (agrégat)
- `User` (agrégat)
- `Organization` (agrégat)

#### 2. **Modèles de Requête** - Optimisés pour la lecture

**Voici comment j'ai implémenté les modèles de requête avec Gyroscops** :

**Modèles Dénormalisés** :
- Structure optimisée pour les lectures
- Données pré-calculées
- Vues spécialisées
- Pas de logique métier

**Exemples** :
- `PaymentView`
- `UserProfile`
- `OrganizationSummary`

#### 3. **Synchronisation** - Maintenir la cohérence

**Voici comment j'ai implémenté la synchronisation avec Gyroscops** :

**Event Sourcing** :
- Événements comme source de vérité
- Projections pour les vues
- Synchronisation asynchrone
- Cohérence éventuelle

#### 4. **API Platform** - Exposer les deux côtés

**Voici comment j'ai intégré API Platform avec Gyroscops** :

**Ressources Séparées** :
- Ressources de commande
- Ressources de requête
- Endpoints spécialisés
- Documentation séparée

## Comment Implémenter CQRS

### 1. **Créer les Modèles de Commande**

**Avec Gyroscops** : J'ai créé les modèles de commande :

```php
// ✅ Modèles de Commande Hive (Projet Hive)
final class Payment
{
    private PaymentId $id;
    private OrganizationId $organizationId;
    private string $customerName;
    private string $customerEmail;
    private Price $amount;
    private PaymentStatus $status;
    private \DateTimeImmutable $createdAt;
    private UserId $createdBy;
    private array $uncommittedEvents = [];
    
    public function __construct(
        PaymentId $id,
        OrganizationId $organizationId,
        string $customerName,
        string $customerEmail,
        Price $amount,
        PaymentStatus $status,
        \DateTimeImmutable $createdAt,
        UserId $createdBy
    ) {
        $this->id = $id;
        $this->organizationId = $organizationId;
        $this->customerName = $customerName;
        $this->customerEmail = $customerEmail;
        $this->amount = $amount;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->createdBy = $createdBy;
    }
    
    public function processPayment(): void
    {
        if ($this->status !== PaymentStatus::PENDING) {
            throw new InvalidPaymentStatusException($this->status);
        }
        
        $this->status = PaymentStatus::PROCESSING;
        
        $this->raiseEvent(new PaymentProcessingStarted(
            $this->id,
            new \DateTimeImmutable()
        ));
    }
    
    public function completePayment(): void
    {
        if ($this->status !== PaymentStatus::PROCESSING) {
            throw new InvalidPaymentStatusException($this->status);
        }
        
        $this->status = PaymentStatus::COMPLETED;
        
        $this->raiseEvent(new PaymentCompleted(
            $this->id,
            new \DateTimeImmutable()
        ));
    }
    
    public function failPayment(string $reason): void
    {
        if ($this->status !== PaymentStatus::PROCESSING) {
            throw new InvalidPaymentStatusException($this->status);
        }
        
        $this->status = PaymentStatus::FAILED;
        
        $this->raiseEvent(new PaymentFailed(
            $this->id,
            $reason,
            new \DateTimeImmutable()
        ));
    }
    
    private function raiseEvent(DomainEvent $event): void
    {
        $this->uncommittedEvents[] = $event;
    }
    
    public function getUncommittedEvents(): array
    {
        return $this->uncommittedEvents;
    }
    
    public function markEventsAsCommitted(): void
    {
        $this->uncommittedEvents = [];
    }
    
    // Getters
    public function getId(): PaymentId { return $this->id; }
    public function getOrganizationId(): OrganizationId { return $this->organizationId; }
    public function getCustomerName(): string { return $this->customerName; }
    public function getCustomerEmail(): string { return $this->customerEmail; }
    public function getAmount(): Price { return $this->amount; }
    public function getStatus(): PaymentStatus { return $this->status; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getCreatedBy(): UserId { return $this->createdBy; }
}
```

**Résultat** : Modèles de commande optimisés pour l'écriture.

### 2. **Créer les Modèles de Requête**

**Avec Gyroscops** : J'ai créé les modèles de requête :

```php
// ✅ Modèles de Requête Hive (Projet Hive)
final class PaymentView
{
    public function __construct(
        public readonly string $id,
        public readonly string $organizationId,
        public readonly string $organizationName,
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly string $amount,
        public readonly string $currency,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly string $createdBy,
        public readonly string $createdByName,
        public readonly ?string $processedAt,
        public readonly ?string $failedAt,
        public readonly ?string $failureReason
    ) {}
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organizationId,
            'organization_name' => $this->organizationName,
            'customer_name' => $this->customerName,
            'customer_email' => $this->customerEmail,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'created_by' => $this->createdBy,
            'created_by_name' => $this->createdByName,
            'processed_at' => $this->processedAt,
            'failed_at' => $this->failedAt,
            'failure_reason' => $this->failureReason
        ];
    }
}

final class PaymentSummary
{
    public function __construct(
        public readonly string $id,
        public readonly string $customerName,
        public readonly string $amount,
        public readonly string $status,
        public readonly string $createdAt
    ) {}
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'customer_name' => $this->customerName,
            'amount' => $this->amount,
            'status' => $this->status,
            'created_at' => $this->createdAt
        ];
    }
}

final class PaymentStatistics
{
    public function __construct(
        public readonly int $totalPayments,
        public readonly int $pendingPayments,
        public readonly int $completedPayments,
        public readonly int $failedPayments,
        public readonly string $totalAmount,
        public readonly string $averageAmount,
        public readonly array $statusDistribution,
        public readonly array $monthlyTrends
    ) {}
    
    public function toArray(): array
    {
        return [
            'total_payments' => $this->totalPayments,
            'pending_payments' => $this->pendingPayments,
            'completed_payments' => $this->completedPayments,
            'failed_payments' => $this->failedPayments,
            'total_amount' => $this->totalAmount,
            'average_amount' => $this->averageAmount,
            'status_distribution' => $this->statusDistribution,
            'monthly_trends' => $this->monthlyTrends
        ];
    }
}
```

**Résultat** : Modèles de requête optimisés pour la lecture.

### 3. **Implémenter les Repositories**

**Avec Gyroscops** : J'ai implémenté les repositories :

```php
// ✅ Repositories CQRS Hive (Projet Hive)
final class PaymentCommandRepository
{
    public function __construct(
        private Connection $connection,
        private EventStore $eventStore,
        private LoggerInterface $logger
    ) {}
    
    public function save(Payment $payment): void
    {
        $this->connection->beginTransaction();
        
        try {
            // Sauvegarder l'agrégat
            $this->saveAggregate($payment);
            
            // Sauvegarder les événements
            $this->eventStore->append(
                $payment->getId()->toString(),
                $payment->getUncommittedEvents(),
                $this->getCurrentVersion($payment->getId())
            );
            
            $payment->markEventsAsCommitted();
            
            $this->connection->commit();
            
            $this->logger->info('Payment saved successfully', [
                'payment_id' => $payment->getId()->toString()
            ]);
            
        } catch (\Exception $e) {
            $this->connection->rollBack();
            
            $this->logger->error('Failed to save payment', [
                'payment_id' => $payment->getId()->toString(),
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    public function findById(PaymentId $id): ?Payment
    {
        $events = $this->eventStore->getEvents($id->toString());
        
        if (empty($events)) {
            return null;
        }
        
        return Payment::fromEvents($events);
    }
    
    private function saveAggregate(Payment $payment): void
    {
        $sql = 'INSERT INTO payment_aggregates 
                (id, organization_id, customer_name, customer_email, amount, currency, status, created_at, created_by, version) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                organization_id = VALUES(organization_id),
                customer_name = VALUES(customer_name),
                customer_email = VALUES(customer_email),
                amount = VALUES(amount),
                currency = VALUES(currency),
                status = VALUES(status),
                version = VALUES(version)';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $payment->getId()->toString(),
            $payment->getOrganizationId()->toString(),
            $payment->getCustomerName(),
            $payment->getCustomerEmail(),
            $payment->getAmount()->getAmount()->toString(),
            $payment->getAmount()->getCurrency()->value,
            $payment->getStatus()->value,
            $payment->getCreatedAt()->format('Y-m-d H:i:s'),
            $payment->getCreatedBy()->toString(),
            $this->getNextVersion($payment->getId())
        ]);
    }
}

final class PaymentQueryRepository
{
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger
    ) {}
    
    public function findById(string $id): ?PaymentView
    {
        $sql = 'SELECT p.*, o.name as organization_name, u.first_name, u.last_name 
                FROM payment_views p
                LEFT JOIN organizations o ON p.organization_id = o.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id]);
        
        $row = $stmt->fetchAssociative();
        if (!$row) {
            return null;
        }
        
        return new PaymentView(
            $row['id'],
            $row['organization_id'],
            $row['organization_name'],
            $row['customer_name'],
            $row['customer_email'],
            $row['amount'],
            $row['currency'],
            $row['status'],
            $row['created_at'],
            $row['created_by'],
            $row['first_name'] . ' ' . $row['last_name'],
            $row['processed_at'],
            $row['failed_at'],
            $row['failure_reason']
        );
    }
    
    public function findByOrganization(string $organizationId, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        
        $sql = 'SELECT p.*, o.name as organization_name, u.first_name, u.last_name 
                FROM payment_views p
                LEFT JOIN organizations o ON p.organization_id = o.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.organization_id = ?
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$organizationId, $limit, $offset]);
        
        $payments = [];
        while ($row = $stmt->fetchAssociative()) {
            $payments[] = new PaymentView(
                $row['id'],
                $row['organization_id'],
                $row['organization_name'],
                $row['customer_name'],
                $row['customer_email'],
                $row['amount'],
                $row['currency'],
                $row['status'],
                $row['created_at'],
                $row['created_by'],
                $row['first_name'] . ' ' . $row['last_name'],
                $row['processed_at'],
                $row['failed_at'],
                $row['failure_reason']
            );
        }
        
        return $payments;
    }
    
    public function getStatistics(string $organizationId): PaymentStatistics
    {
        $sql = 'SELECT 
                    COUNT(*) as total_payments,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_payments,
                    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_payments,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_payments,
                    SUM(amount) as total_amount,
                    AVG(amount) as average_amount
                FROM payment_views 
                WHERE organization_id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$organizationId]);
        
        $row = $stmt->fetchAssociative();
        
        return new PaymentStatistics(
            (int) $row['total_payments'],
            (int) $row['pending_payments'],
            (int) $row['completed_payments'],
            (int) $row['failed_payments'],
            $row['total_amount'],
            $row['average_amount'],
            $this->getStatusDistribution($organizationId),
            $this->getMonthlyTrends($organizationId)
        );
    }
}
```

**Résultat** : Repositories spécialisés pour chaque côté.

### 4. **Créer les Projections**

**Avec Gyroscops** : J'ai créé les projections :

```php
// ✅ Projections CQRS Hive (Projet Hive)
final class PaymentProjection
{
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger
    ) {}
    
    public function handle(DomainEvent $event): void
    {
        match ($event::class) {
            PaymentCreated::class => $this->handlePaymentCreated($event),
            PaymentProcessingStarted::class => $this->handlePaymentProcessingStarted($event),
            PaymentCompleted::class => $this->handlePaymentCompleted($event),
            PaymentFailed::class => $this->handlePaymentFailed($event),
            default => $this->logger->warning('Unknown event type', [
                'event_type' => get_class($event)
            ])
        };
    }
    
    private function handlePaymentCreated(PaymentCreated $event): void
    {
        $sql = 'INSERT INTO payment_views 
                (id, organization_id, customer_name, customer_email, amount, currency, status, created_at, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->paymentId->toString(),
            $event->organizationId->toString(),
            $event->customerName,
            $event->customerEmail,
            $event->amount->getAmount()->toString(),
            $event->amount->getCurrency()->value,
            PaymentStatus::PENDING->value,
            $event->createdAt->format('Y-m-d H:i:s'),
            $event->createdBy->toString()
        ]);
        
        $this->logger->info('Payment view created', [
            'payment_id' => $event->paymentId->toString()
        ]);
    }
    
    private function handlePaymentProcessingStarted(PaymentProcessingStarted $event): void
    {
        $sql = 'UPDATE payment_views 
                SET status = ?, processed_at = ? 
                WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            PaymentStatus::PROCESSING->value,
            $event->startedAt->format('Y-m-d H:i:s'),
            $event->paymentId->toString()
        ]);
        
        $this->logger->info('Payment view updated - processing started', [
            'payment_id' => $event->paymentId->toString()
        ]);
    }
    
    private function handlePaymentCompleted(PaymentCompleted $event): void
    {
        $sql = 'UPDATE payment_views 
                SET status = ?, processed_at = ? 
                WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            PaymentStatus::COMPLETED->value,
            $event->completedAt->format('Y-m-d H:i:s'),
            $event->paymentId->toString()
        ]);
        
        $this->logger->info('Payment view updated - completed', [
            'payment_id' => $event->paymentId->toString()
        ]);
    }
    
    private function handlePaymentFailed(PaymentFailed $event): void
    {
        $sql = 'UPDATE payment_views 
                SET status = ?, failed_at = ?, failure_reason = ? 
                WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            PaymentStatus::FAILED->value,
            $event->failedAt->format('Y-m-d H:i:s'),
            $event->reason,
            $event->paymentId->toString()
        ]);
        
        $this->logger->info('Payment view updated - failed', [
            'payment_id' => $event->paymentId->toString(),
            'reason' => $event->reason
        ]);
    }
}
```

**Résultat** : Projections qui maintiennent les vues de lecture.

## Les Avantages de CQRS

### 1. **Performance Optimale**

**Avec Gyroscops** : CQRS m'a donné une performance optimale :
- Modèles optimisés pour chaque usage
- Requêtes spécialisées
- Indexation adaptée
- Cache spécialisé

**Résultat** : Performances maximales.

### 2. **Scalabilité**

**Avec Gyroscops** : CQRS m'a permis de scaler :
- Scaling indépendant des lectures et écritures
- Bases de données séparées
- Load balancing spécialisé
- Réplication adaptée

**Résultat** : Scalabilité maximale.

### 3. **Flexibilité**

**Avec Gyroscops** : CQRS m'a donné de la flexibilité :
- Évolution indépendante des modèles
- Technologies différentes
- Optimisations spécialisées
- Maintenance facilitée

**Résultat** : Flexibilité maximale.

### 4. **Sécurité**

**Avec Gyroscops** : CQRS m'a amélioré la sécurité :
- Permissions différentes
- Accès séparés
- Audit spécialisé
- Isolation des données

**Résultat** : Sécurité renforcée.

## Les Inconvénients de CQRS

### 1. **Complexité Très Élevée**

**Avec Gyroscops** : CQRS a ajouté une complexité très élevée :
- Deux modèles à maintenir
- Synchronisation complexe
- Debugging difficile
- Courbe d'apprentissage importante

**Résultat** : Architecture très complexe.

### 2. **Cohérence Éventuelle**

**Avec Gyroscops** : CQRS introduit la cohérence éventuelle :
- Données pas toujours synchronisées
- Latence de synchronisation
- Gestion des conflits
- Tests complexes

**Résultat** : Cohérence éventuelle à gérer.

### 3. **Duplication de Code**

**Avec Gyroscops** : CQRS crée beaucoup de duplication :
- Logique dupliquée
- Validation dupliquée
- Mapping dupliqué
- Maintenance double

**Résultat** : Code dupliqué à maintenir.

### 4. **Coût de Développement**

**Avec Gyroscops** : CQRS augmente le coût de développement :
- Plus de code à écrire
- Plus de tests
- Plus de maintenance
- Plus de formation

**Résultat** : Coût de développement plus élevé.

## Les Pièges à Éviter

### 1. **CQRS Prématuré**

**❌ Mauvais** : CQRS pour des besoins simples
**✅ Bon** : CQRS seulement quand nécessaire

**Pourquoi c'est important ?** CQRS ajoute de la complexité inutile.

### 2. **Synchronisation Mal Gérée**

**❌ Mauvais** : Pas de plan de synchronisation
**✅ Bon** : Synchronisation bien planifiée

**Pourquoi c'est crucial ?** La synchronisation est critique pour CQRS.

### 3. **Modèles Trop Similaires**

**❌ Mauvais** : Modèles de commande et requête identiques
**✅ Bon** : Modèles optimisés pour chaque usage

**Pourquoi c'est essentiel ?** CQRS perd son sens si les modèles sont identiques.

### 4. **Tests Insuffisants**

**❌ Mauvais** : Pas de tests de synchronisation
**✅ Bon** : Tests complets de synchronisation

**Pourquoi c'est la clé ?** Les tests sont critiques pour CQRS.

## L'Évolution vers CQRS

### Phase 1 : Architecture Monolithique

**Avec Gyroscops** : Au début, j'avais une architecture monolithique :
- Un modèle pour tout
- Performance non optimale
- Scaling limité
- Maintenance difficile

**Résultat** : Développement rapide, performance limitée.

### Phase 2 : Introduction de CQS

**Avec Gyroscops** : J'ai introduit CQS :
- Séparation des commandes et requêtes
- Performance améliorée
- Tests simplifiés
- Maintenance facilitée

**Résultat** : Architecture plus claire, performance améliorée.

### Phase 3 : CQRS Complet

**Avec Gyroscops** : Maintenant, j'ai un CQRS complet :
- Modèles séparés et optimisés
- Performance maximale
- Scalabilité maximale
- Flexibilité maximale

**Résultat** : Architecture optimale mais complexe.

## 🏗️ Implémentation Concrète dans le Projet Hive

### CQRS Appliqué à Hive

Le projet Hive applique concrètement les principes de CQRS à travers son architecture et ses ADR (Architecture Decision Records). Voici comment :

#### Configuration CQRS Hive

```php
// ✅ Configuration CQRS Hive (Projet Hive)
final class HiveCQRSConfiguration
{
    public function configureServices(ContainerBuilder $container): void
    {
        // Command Side
        $container->register(PaymentCommandRepository::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        $container->register(PaymentCommandService::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Query Side
        $container->register(PaymentQueryRepository::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        $container->register(PaymentQueryService::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Projections
        $container->register(PaymentProjection::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Event Handlers
        $container->register(PaymentEventHandler::class)
            ->addTag('event.handler', ['event' => PaymentCreated::class]);
    }
}
```

#### API Platform CQRS Hive

```php
// ✅ API Platform CQRS Hive (Projet Hive)
#[ApiResource(
    operations: [
        new Post(uriTemplate: '/payments'),
        new Put(uriTemplate: '/payments/{id}'),
        new Delete(uriTemplate: '/payments/{id}')
    ],
    processor: PaymentCommandProcessor::class
)]
final class PaymentCommand
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $customerName,
        public string $customerEmail,
        public string $amount,
        public string $currency,
        public string $createdBy
    ) {}
}

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/payment-views/{id}'),
        new GetCollection(uriTemplate: '/payment-views')
    ],
    provider: PaymentViewProvider::class
)]
final class PaymentView
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $organizationName,
        public string $customerName,
        public string $customerEmail,
        public string $amount,
        public string $currency,
        public string $status,
        public string $createdAt,
        public string $createdBy,
        public string $createdByName
    ) {}
}
```

### Références aux ADR du Projet Hive

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du projet Hive :
- **HIVE006** : Query Models for API Platform - Modèles de requête
- **HIVE007** : Command Models for API Platform - Modèles de commande
- **HIVE008** : Event Collaboration - Collaboration basée sur les événements
- **HIVE009** : Message Buses - Bus de messages pour CQRS
- **HIVE040** : Enhanced Models with Property Access Patterns - Modèles enrichis pour CQRS
- **HIVE041** : Cross-Cutting Concerns Architecture - Architecture des préoccupations transversales

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="blue" 
    title="Je veux comprendre les chapitres fondamentaux" 
    subtitle="Vous voulez revoir les bases de DDD et Event Storming" 
    criteria="Équipe junior ou intermédiaire,Besoin de revoir les bases,Compréhension des concepts,Fondations solides" 
    time="30-45 minutes" 
    chapter="1" 
    chapter-title="Introduction Event Storming DDD" 
    chapter-url="/chapitres/fondamentaux/chapitre-01-introduction-event-storming-ddd/" 
  >}}}
  
  {{< chapter-option 
    letter="B" 
    color="green" 
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
    color="yellow" 
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
    color="red" 
    title="Je veux comprendre les chapitres avancés" 
    subtitle="Vous voulez voir la sécurité et le frontend" 
    criteria="Équipe expérimentée,Besoin de comprendre la sécurité et le frontend,Intégration importante,Bonnes pratiques à appliquer" 
    time="25-35 minutes" 
    chapter="62" 
    chapter-title="Sécurité et Autorisation" 
    chapter-url="/chapitres/avances/chapitre-62-securite-autorisation/" 
  >}}}
  
{{< /chapter-nav >}}