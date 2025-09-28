---
title: "Chapitre 58 : Stockage SQL - Event Sourcing + CQS"
description: "Maîtriser le stockage SQL avec Event Sourcing et Command Query Separation pour des performances optimisées"
date: 2024-12-19
draft: true
type: "docs"
weight: 58
---

## 🎯 Objectif de ce Chapitre

### Mon Problème : Comment Optimiser les Performances avec Event Sourcing ?

**Voici ce qui s'est passé avec Gyroscops** : J'avais implémenté l'Event Sourcing pour l'audit trail, mais les performances de lecture étaient dégradées. J'avais besoin d'optimiser les lectures tout en gardant l'Event Sourcing pour l'écriture.

**Mais attendez...** Comment séparer les commandes et les requêtes ? Comment optimiser les projections ? Comment gérer la cohérence ? Comment intégrer avec API Platform ?

**Soudain, je réalisais que CQS + Event Sourcing était parfait !** Il me fallait une méthode pour optimiser les performances tout en gardant l'audit trail.

### Stockage SQL Event Sourcing + CQS : Mon Guide Pratique

Le stockage SQL Event Sourcing + CQS m'a permis de :
- **Optimiser** les performances de lecture
- **Conserver** l'audit trail complet
- **Séparer** les responsabilités
- **Équilibrer** complexité et performance

## Qu'est-ce que le Stockage SQL Event Sourcing + CQS ?

### Le Concept Fondamental

Le stockage SQL Event Sourcing + CQS combine l'Event Sourcing pour l'écriture avec la Command Query Separation pour optimiser les lectures. **L'idée** : Écriture via Event Sourcing, lecture via projections optimisées.

**Avec Gyroscops, voici comment j'ai structuré le stockage SQL Event Sourcing + CQS** :

### Les 4 Piliers du Stockage SQL Event Sourcing + CQS

#### 1. **Event Store** - Source de vérité pour l'écriture

**Voici comment j'ai implémenté l'Event Store avec Gyroscops** :

**Fonctionnalités** :
- Stockage des événements
- Reconstruction des agrégats
- Gestion des versions
- Optimistic locking

**Avantages** :
- Audit trail complet
- Intégrité des données
- Évolutivité des vues
- Debugging facilité

#### 2. **Command Side** - Gestion des écritures

**Voici comment j'ai implémenté le Command Side avec Gyroscops** :

**Composants** :
- Agrégats Event Sourcing
- Command Handlers
- Event Store
- Event Bus

**Exemples** :
- `CreatePaymentCommand`
- `ProcessPaymentCommand`
- `RefundPaymentCommand`

#### 3. **Query Side** - Optimisation des lectures

**Voici comment j'ai implémenté le Query Side avec Gyroscops** :

**Composants** :
- Projections optimisées
- Query Models
- Query Handlers
- Cache intelligent

**Exemples** :
- `PaymentQueryModel`
- `PaymentListQueryModel`
- `PaymentAnalyticsQueryModel`

#### 4. **Projections** - Synchronisation des vues

**Voici comment j'ai implémenté les projections avec Gyroscops** :

**Types de Projections** :
- Projections de lecture (pour l'API)
- Projections d'audit (pour le debugging)
- Projections d'analytics (pour les rapports)

**Synchronisation** :
- Asynchrone via Event Bus
- Cohérence éventuelle
- Gestion des erreurs
- Reprocessing possible

## Comment Implémenter le Stockage SQL Event Sourcing + CQS

### 1. **Créer l'Event Store (Command Side)**

**Avec Gyroscops** : J'ai créé l'Event Store :

```php
// ✅ Event Store Gyroscops Cloud (Projet Gyroscops Cloud)
final class SqlEventStore implements EventStoreInterface
{
    public function __construct(
        private Connection $connection,
        private EventSerializer $serializer
    ) {}
    
    public function append(string $aggregateId, array $events, int $expectedVersion): void
    {
        $this->connection->beginTransaction();
        
        try {
            // Vérifier la version attendue
            $currentVersion = $this->getCurrentVersion($aggregateId);
            if ($currentVersion !== $expectedVersion) {
                throw new ConcurrencyException('Version mismatch');
            }
            
            // Insérer les événements
            foreach ($events as $event) {
                $this->insertEvent($aggregateId, $event, $currentVersion + 1);
                $currentVersion++;
            }
            
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollback();
            throw $e;
        }
    }
    
    public function getEvents(string $aggregateId, int $fromVersion = 0): array
    {
        $sql = 'SELECT event_type, event_data, event_metadata, version, created_at 
                FROM event_store 
                WHERE aggregate_id = ? AND version > ? 
                ORDER BY version ASC';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$aggregateId, $fromVersion]);
        
        $events = [];
        while ($row = $stmt->fetch()) {
            $events[] = $this->serializer->deserialize(
                $row['event_type'],
                $row['event_data'],
                $row['event_metadata']
            );
        }
        
        return $events;
    }
    
    private function insertEvent(string $aggregateId, DomainEvent $event, int $version): void
    {
        $sql = 'INSERT INTO event_store (event_id, aggregate_id, event_type, event_data, event_metadata, version) 
                VALUES (?, ?, ?, ?, ?, ?)';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            Uuid::uuid4()->toString(),
            $aggregateId,
            $event::class,
            json_encode($event->toArray()),
            json_encode($event->getMetadata()),
            $version
        ]);
    }
}
```

**Résultat** : Event Store robuste pour l'écriture.

### 2. **Créer les Command Handlers**

**Avec Gyroscops** : J'ai créé les Command Handlers :

```php
// ✅ Command Handler Payment Gyroscops Cloud (Projet Gyroscops Cloud)
final class PaymentCommandHandler
{
    public function __construct(
        private EventStoreInterface $eventStore,
        private EventBusInterface $eventBus
    ) {}
    
    public function handleCreatePayment(CreatePaymentCommand $command): void
    {
        // Reconstruire l'agrégat depuis les événements
        $events = $this->eventStore->getEvents($command->getPaymentId());
        $aggregate = PaymentAggregate::fromEvents($events);
        
        // Exécuter la commande
        $aggregate->initiate(
            $command->getPaymentId(),
            $command->getOrganizationId(),
            $command->getCustomerName(),
            $command->getCustomerEmail(),
            $command->getAmount(),
            $command->getCurrency(),
            $command->getCreatedBy()
        );
        
        // Sauvegarder les événements
        $this->eventStore->append(
            $command->getPaymentId(),
            $aggregate->getUncommittedEvents(),
            $aggregate->getVersion() - count($aggregate->getUncommittedEvents())
        );
        
        // Publier les événements
        foreach ($aggregate->getUncommittedEvents() as $event) {
            $this->eventBus->publish($event);
        }
        
        $aggregate->markEventsAsCommitted();
    }
    
    public function handleProcessPayment(ProcessPaymentCommand $command): void
    {
        // Reconstruire l'agrégat depuis les événements
        $events = $this->eventStore->getEvents($command->getPaymentId());
        $aggregate = PaymentAggregate::fromEvents($events);
        
        // Exécuter la commande
        $aggregate->process($command->getProcessedBy());
        
        // Sauvegarder les événements
        $this->eventStore->append(
            $command->getPaymentId(),
            $aggregate->getUncommittedEvents(),
            $aggregate->getVersion() - count($aggregate->getUncommittedEvents())
        );
        
        // Publier les événements
        foreach ($aggregate->getUncommittedEvents() as $event) {
            $this->eventBus->publish($event);
        }
        
        $aggregate->markEventsAsCommitted();
    }
}
```

**Résultat** : Command Handlers pour l'écriture.

### 3. **Créer les Query Models**

**Avec Gyroscops** : J'ai créé les Query Models :

```php
// ✅ Query Model Payment Gyroscops Cloud (Projet Gyroscops Cloud)
final class PaymentQueryModel
{
    public function __construct(
        public readonly string $id,
        public readonly string $organizationId,
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly string $amount,
        public readonly string $currency,
        public readonly string $status,
        public readonly \DateTimeImmutable $createdAt,
        public readonly string $createdBy,
        public readonly ?\DateTimeImmutable $updatedAt = null,
        public readonly ?string $updatedBy = null,
        public readonly ?string $failureReason = null,
        public readonly ?string $refundReason = null
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            organizationId: $data['organization_id'],
            customerName: $data['customer_name'],
            customerEmail: $data['customer_email'],
            amount: $data['amount'],
            currency: $data['currency'],
            status: $data['status'],
            createdAt: new \DateTimeImmutable($data['created_at']),
            createdBy: $data['created_by'],
            updatedAt: $data['updated_at'] ? new \DateTimeImmutable($data['updated_at']) : null,
            updatedBy: $data['updated_by'] ?? null,
            failureReason: $data['failure_reason'] ?? null,
            refundReason: $data['refund_reason'] ?? null
        );
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organizationId' => $this->organizationId,
            'customerName' => $this->customerName,
            'customerEmail' => $this->customerEmail,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'createdBy' => $this->createdBy,
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'updatedBy' => $this->updatedBy,
            'failureReason' => $this->failureReason,
            'refundReason' => $this->refundReason
        ];
    }
}
```

**Résultat** : Query Models optimisés pour la lecture.

### 4. **Créer les Query Handlers**

**Avec Gyroscops** : J'ai créé les Query Handlers :

```php
// ✅ Query Handler Payment Gyroscops Cloud (Projet Gyroscops Cloud)
final class PaymentQueryHandler
{
    public function __construct(
        private Connection $connection,
        private CacheInterface $cache
    ) {}
    
    public function handleGetPayment(GetPaymentQuery $query): ?PaymentQueryModel
    {
        $cacheKey = "payment_{$query->getPaymentId()}";
        
        // Vérifier le cache
        if ($cached = $this->cache->get($cacheKey)) {
            return PaymentQueryModel::fromArray($cached);
        }
        
        // Requête à la base de données
        $sql = 'SELECT id, organization_id, customer_name, customer_email, amount, currency, status, 
                       created_at, created_by, updated_at, updated_by, failure_reason, refund_reason
                FROM payment_projections 
                WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$query->getPaymentId()]);
        
        $data = $stmt->fetch();
        if (!$data) {
            return null;
        }
        
        $payment = PaymentQueryModel::fromArray($data);
        
        // Mettre en cache
        $this->cache->set($cacheKey, $payment->toArray(), 3600);
        
        return $payment;
    }
    
    public function handleGetPaymentsByOrganization(GetPaymentsByOrganizationQuery $query): array
    {
        $cacheKey = "payments_org_{$query->getOrganizationId()}_{$query->getPage()}_{$query->getLimit()}";
        
        // Vérifier le cache
        if ($cached = $this->cache->get($cacheKey)) {
            return array_map([PaymentQueryModel::class, 'fromArray'], $cached);
        }
        
        // Requête à la base de données
        $sql = 'SELECT id, organization_id, customer_name, customer_email, amount, currency, status, 
                       created_at, created_by, updated_at, updated_by, failure_reason, refund_reason
                FROM payment_projections 
                WHERE organization_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $query->getOrganizationId(),
            $query->getLimit(),
            ($query->getPage() - 1) * $query->getLimit()
        ]);
        
        $payments = [];
        while ($data = $stmt->fetch()) {
            $payments[] = PaymentQueryModel::fromArray($data);
        }
        
        // Mettre en cache
        $this->cache->set($cacheKey, array_map(fn($p) => $p->toArray(), $payments), 1800);
        
        return $payments;
    }
    
    public function handleGetPaymentAnalytics(GetPaymentAnalyticsQuery $query): PaymentAnalyticsQueryModel
    {
        $cacheKey = "payment_analytics_{$query->getOrganizationId()}_{$query->getStartDate()}_{$query->getEndDate()}";
        
        // Vérifier le cache
        if ($cached = $this->cache->get($cacheKey)) {
            return PaymentAnalyticsQueryModel::fromArray($cached);
        }
        
        // Requête analytique
        $sql = 'SELECT 
                    COUNT(*) as total_payments,
                    SUM(CASE WHEN status = "processed" THEN 1 ELSE 0 END) as successful_payments,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_payments,
                    SUM(CASE WHEN status = "refunded" THEN 1 ELSE 0 END) as refunded_payments,
                    SUM(CASE WHEN status = "processed" THEN CAST(amount AS DECIMAL(10,2)) ELSE 0 END) as total_amount,
                    AVG(CASE WHEN status = "processed" THEN CAST(amount AS DECIMAL(10,2)) ELSE NULL END) as average_amount
                FROM payment_projections 
                WHERE organization_id = ? 
                AND created_at BETWEEN ? AND ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $query->getOrganizationId(),
            $query->getStartDate()->format('Y-m-d H:i:s'),
            $query->getEndDate()->format('Y-m-d H:i:s')
        ]);
        
        $data = $stmt->fetch();
        $analytics = PaymentAnalyticsQueryModel::fromArray($data);
        
        // Mettre en cache
        $this->cache->set($cacheKey, $analytics->toArray(), 3600);
        
        return $analytics;
    }
}
```

**Résultat** : Query Handlers optimisés avec cache.

### 5. **Créer les Projections Asynchrones**

**Avec Gyroscops** : J'ai créé les projections asynchrones :

```php
// ✅ Projection Payment Asynchrone Gyroscops Cloud (Projet Gyroscops Cloud)
final class PaymentProjectionHandler
{
    public function __construct(
        private Connection $connection,
        private CacheInterface $cache
    ) {}
    
    public function handle(DomainEvent $event): void
    {
        switch ($event::class) {
            case PaymentInitiated::class:
                $this->handlePaymentInitiated($event);
                break;
            case PaymentProcessed::class:
                $this->handlePaymentProcessed($event);
                break;
            case PaymentFailed::class:
                $this->handlePaymentFailed($event);
                break;
            case PaymentRefunded::class:
                $this->handlePaymentRefunded($event);
                break;
        }
        
        // Invalider le cache
        $this->invalidateCache($event);
    }
    
    private function handlePaymentInitiated(PaymentInitiated $event): void
    {
        $sql = 'INSERT INTO payment_projections (id, organization_id, customer_name, customer_email, amount, currency, status, created_at, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getPaymentId(),
            $event->getOrganizationId(),
            $event->getCustomerName(),
            $event->getCustomerEmail(),
            $event->getAmount(),
            $event->getCurrency(),
            'initiated',
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getCreatedBy()
        ]);
    }
    
    private function handlePaymentProcessed(PaymentProcessed $event): void
    {
        $sql = 'UPDATE payment_projections SET status = ?, updated_at = ?, updated_by = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'processed',
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getProcessedBy(),
            $event->getPaymentId()
        ]);
    }
    
    private function handlePaymentFailed(PaymentFailed $event): void
    {
        $sql = 'UPDATE payment_projections SET status = ?, failure_reason = ?, updated_at = ?, updated_by = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'failed',
            $event->getReason(),
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getFailedBy(),
            $event->getPaymentId()
        ]);
    }
    
    private function handlePaymentRefunded(PaymentRefunded $event): void
    {
        $sql = 'UPDATE payment_projections SET status = ?, refund_reason = ?, updated_at = ?, updated_by = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'refunded',
            $event->getReason(),
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getRefundedBy(),
            $event->getPaymentId()
        ]);
    }
    
    private function invalidateCache(DomainEvent $event): void
    {
        // Invalider les caches liés à cet événement
        $this->cache->delete("payment_{$event->getAggregateId()}");
        $this->cache->delete("payments_org_{$event->getOrganizationId()}_*");
        $this->cache->delete("payment_analytics_{$event->getOrganizationId()}_*");
    }
}
```

**Résultat** : Projections asynchrones avec invalidation de cache.

## Les Avantages du Stockage SQL Event Sourcing + CQS

### 1. **Performance Optimisée**

**Avec Gyroscops** : Le stockage SQL Event Sourcing + CQS m'a donné des performances optimisées :
- Lectures via projections optimisées
- Cache intelligent
- Requêtes spécialisées
- Performance prévisible

**Résultat** : Performances de lecture excellentes.

### 2. **Audit Trail Complet**

**Avec Gyroscops** : Le stockage SQL Event Sourcing + CQS m'a conservé l'audit trail :
- Historique complet des changements
- Reconstruction possible
- Debugging facilité
- Traçabilité totale

**Résultat** : Audit trail parfait conservé.

### 3. **Séparation des Responsabilités**

**Avec Gyroscops** : Le stockage SQL Event Sourcing + CQS m'a séparé les responsabilités :
- Command Side pour l'écriture
- Query Side pour la lecture
- Optimisations indépendantes
- Maintenance facilitée

**Résultat** : Architecture claire et maintenable.

### 4. **Évolutivité**

**Avec Gyroscops** : Le stockage SQL Event Sourcing + CQS m'a permis d'évoluer :
- Nouvelles projections sans impact
- Optimisations ciblées
- Évolution indépendante
- Flexibilité maximale

**Résultat** : Évolutivité excellente.

## Les Inconvénients du Stockage SQL Event Sourcing + CQS

### 1. **Complexité Technique**

**Avec Gyroscops** : Le stockage SQL Event Sourcing + CQS a ajouté de la complexité :
- Courbe d'apprentissage importante
- Plus de composants à maintenir
- Concepts avancés
- Debugging plus complexe

**Résultat** : Complexité technique élevée.

### 2. **Cohérence Éventuelle**

**Avec Gyroscops** : Le stockage SQL Event Sourcing + CQS peut avoir des problèmes de cohérence :
- Projections asynchrones
- Délai de synchronisation
- Incohérence temporaire
- Gestion des erreurs complexe

**Résultat** : Cohérence éventuelle à gérer.

### 3. **Gestion du Cache**

**Avec Gyroscops** : Le stockage SQL Event Sourcing + CQS nécessite une gestion du cache :
- Invalidation complexe
- Synchronisation des caches
- Gestion des erreurs
- Performance du cache

**Résultat** : Gestion du cache complexe.

## Les Pièges à Éviter

### 1. **Projections Synchrones**

**❌ Mauvais** : Projections mises à jour de façon synchrone
**✅ Bon** : Projections asynchrones avec Event Bus

**Pourquoi c'est important ?** Les projections synchrones tuent les performances.

### 2. **Cache Non Invalidé**

**❌ Mauvais** : Cache qui n'est jamais invalidé
**✅ Bon** : Invalidation intelligente du cache

**Pourquoi c'est crucial ?** Le cache obsolète donne de mauvaises données.

### 3. **Requêtes Complexes dans les Projections**

**❌ Mauvais** : Requêtes complexes dans les projections
**✅ Bon** : Projections simples et optimisées

**Pourquoi c'est essentiel ?** Les projections complexes ralentissent le système.

## 🏗️ Implémentation Concrète dans le Projet Gyroscops Cloud

### Stockage SQL Event Sourcing + CQS Appliqué à Gyroscops Cloud

Le Gyroscops Cloud applique concrètement les principes du stockage SQL Event Sourcing + CQS à travers son architecture et ses ADR (Architecture Decision Records). Voici comment :

#### Configuration Event Sourcing + CQS Gyroscops Cloud

```php
// ✅ Configuration Event Sourcing + CQS Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveEventSourcingCQSConfiguration
{
    public function configureEventSourcingCQS(ContainerBuilder $container): void
    {
        // Configuration de l'Event Store
        $container->register(SqlEventStore::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration des Command Handlers
        $container->register(PaymentCommandHandler::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration des Query Handlers
        $container->register(PaymentQueryHandler::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration des projections
        $container->register(PaymentProjectionHandler::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration de l'Event Bus
        $container->register(EventBus::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration du cache
        $container->register(CacheInterface::class)
            ->setFactory([RedisAdapter::class, 'createConnection'])
            ->setAutowired(true)
            ->setPublic(true);
    }
}
```

### Références aux ADR du Projet Gyroscops Cloud

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du Gyroscops Cloud :
- **HIVE008** : Event Collaboration - Collaboration par événements
- **HIVE009** : Message Buses - Bus de messages
- **HIVE010** : Repositories - Repositories de base
- **HIVE011** : Command Query Separation - Séparation des commandes et requêtes
- **HIVE012** : Database Repositories - Repositories de base de données
- **HIVE014** : Projections Event Sourcing - Projections Event Sourcing

{{< chapter-nav >}}
  {{{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux comprendre le stockage SQL Event Sourcing + CQRS" 
    subtitle="Vous voulez voir une approche Event Sourcing avec séparation complète des modèles" 
    criteria="Équipe très expérimentée,Besoin de performance maximale,Event Sourcing déjà en place,Complexité élevée acceptable" 
    time="35-50 minutes" 
    chapter="58" 
    chapter-title="Stockage SQL - Event Sourcing + CQRS" 
    chapter-url="/chapitres/stockage/chapitre-51-stockage-sql-event-sourcing-cqrs/" 
  >}}}}
  
  {{{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux comprendre le stockage API" 
    subtitle="Vous voulez voir comment intégrer des APIs externes" 
    criteria="Équipe expérimentée,Besoin d'intégrer des services externes,Données distribuées,Intégrations multiples" 
    time="25-35 minutes" 
    chapter="59" 
    chapter-title="Stockage API - Approche Classique" 
    chapter-url="/chapitres/stockage/chapitre-51-stockage-api-classique/" 
  >}}}}
  
  {{{< chapter-option 
    letter="C" 
    color="red" 
    title="Je veux comprendre le stockage ElasticSearch" 
    subtitle="Vous voulez voir comment optimiser la recherche" 
    criteria="Équipe expérimentée,Besoin de recherche avancée,Analytics importantes,Performance de recherche critique" 
    time="30-40 minutes" 
    chapter="60" 
    chapter-title="Stockage ElasticSearch - Approche Classique" 
    chapter-url="/chapitres/stockage/chapitre-51-stockage-elasticsearch-classique/" 
  >}}}}
  
{{< /chapter-nav >}}