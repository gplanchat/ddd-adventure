---
title: "Chapitre 19 : Stockage SQL - Event Sourcing Seul"
description: "Maîtriser le stockage SQL avec Event Sourcing pour un audit trail complet"
date: 2024-12-19
draft: true
type: "docs"
weight: 19
---

## 🎯 Objectif de ce Chapitre

### Mon Problème : Comment Avoir un Audit Trail Complet et Déboguer Facilement ?

**Voici ce qui s'est passé avec Gyroscops** : J'avais besoin d'un audit trail complet pour les paiements. Qui a payé ? Quand ? Combien ? Pourquoi le paiement a-t-il échoué ? J'avais besoin de pouvoir reconstruire l'état à n'importe quel moment.

**Mais attendez...** Comment stocker tous les événements ? Comment reconstruire l'état ? Comment gérer les projections ? Comment intégrer avec API Platform ?

**Soudain, je réalisais que l'Event Sourcing était parfait !** Il me fallait une méthode pour stocker les événements comme source de vérité.

### Stockage SQL Event Sourcing : Mon Guide Pratique

Le stockage SQL Event Sourcing m'a permis de :
- **Auditer** complètement
- **Déboguer** facilement
- **Reconstruire** l'état
- **Évoluer** les vues métier

## Qu'est-ce que le Stockage SQL Event Sourcing ?

### Le Concept Fondamental

Le stockage SQL Event Sourcing consiste à stocker les événements comme source de vérité dans une base de données SQL. **L'idée** : Au lieu de stocker l'état final, on stocke tous les événements qui ont mené à cet état.

**Avec Gyroscops, voici comment j'ai structuré le stockage SQL Event Sourcing** :

### Les 4 Piliers du Stockage SQL Event Sourcing

#### 1. **Table des Événements** - Source de vérité

**Voici comment j'ai implémenté la table des événements avec Gyroscops** :

**Structure de Base** :
- `event_id` : Identifiant unique de l'événement
- `aggregate_id` : Identifiant de l'agrégat
- `event_type` : Type d'événement
- `event_data` : Données de l'événement (JSON)
- `event_metadata` : Métadonnées (utilisateur, timestamp, etc.)
- `version` : Version de l'agrégat
- `created_at` : Date de création

**Exemples d'événements** :
- `PaymentInitiated`
- `PaymentProcessed`
- `PaymentFailed`
- `PaymentRefunded`

#### 2. **Table des Snapshots** - Optimisation des performances

**Voici comment j'ai implémenté les snapshots avec Gyroscops** :

**Structure de Base** :
- `aggregate_id` : Identifiant de l'agrégat
- `version` : Version du snapshot
- `snapshot_data` : État de l'agrégat (JSON)
- `created_at` : Date de création

**Avantages** :
- Reconstruction plus rapide
- Moins d'événements à charger
- Performance optimisée

#### 3. **Projections** - Vues de lecture optimisées

**Voici comment j'ai implémenté les projections avec Gyroscops** :

**Types de Projections** :
- Projections de lecture (pour l'API)
- Projections d'audit (pour le debugging)
- Projections d'analytics (pour les rapports)

**Exemples** :
- `PaymentProjection` (état actuel)
- `PaymentAuditProjection` (historique complet)
- `PaymentAnalyticsProjection` (statistiques)

#### 4. **Event Store** - Gestion des événements

**Voici comment j'ai implémenté l'Event Store avec Gyroscops** :

**Fonctionnalités** :
- Sauvegarde des événements
- Reconstruction des agrégats
- Gestion des versions
- Optimistic locking

## Comment Implémenter le Stockage SQL Event Sourcing

### 1. **Créer la Table des Événements**

**Avec Gyroscops** : J'ai créé la table des événements :

```sql
-- ✅ Table des événements Hive (Projet Hive)
CREATE TABLE event_store (
    event_id UUID PRIMARY KEY,
    aggregate_id UUID NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    event_data JSONB NOT NULL,
    event_metadata JSONB NOT NULL,
    version INTEGER NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    
    INDEX idx_aggregate_id (aggregate_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
);
```

**Résultat** : Table optimisée pour les événements.

### 2. **Créer la Table des Snapshots**

**Avec Gyroscops** : J'ai créé la table des snapshots :

```sql
-- ✅ Table des snapshots Hive (Projet Hive)
CREATE TABLE snapshots (
    aggregate_id UUID PRIMARY KEY,
    version INTEGER NOT NULL,
    snapshot_data JSONB NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);
```

**Résultat** : Table pour optimiser les performances.

### 3. **Créer l'Event Store**

**Avec Gyroscops** : J'ai créé l'Event Store :

```php
// ✅ Event Store Hive (Projet Hive)
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

**Résultat** : Event Store robuste et performant.

### 4. **Créer les Agrégats Event Sourcing**

**Avec Gyroscops** : J'ai créé les agrégats :

```php
// ✅ Agrégat Payment Event Sourcing Hive (Projet Hive)
final class PaymentAggregate
{
    private string $id;
    private string $organizationId;
    private string $customerName;
    private string $customerEmail;
    private string $amount;
    private string $currency;
    private string $status;
    private \DateTimeImmutable $createdAt;
    private string $createdBy;
    private int $version = 0;
    
    private array $uncommittedEvents = [];
    
    public static function initiate(
        string $id,
        string $organizationId,
        string $customerName,
        string $customerEmail,
        string $amount,
        string $currency,
        string $createdBy
    ): self {
        $aggregate = new self();
        $aggregate->apply(new PaymentInitiated(
            $id,
            $organizationId,
            $customerName,
            $customerEmail,
            $amount,
            $currency,
            $createdBy
        ));
        
        return $aggregate;
    }
    
    public function process(string $processedBy): void
    {
        if ($this->status !== 'initiated') {
            throw new InvalidOperationException('Payment can only be processed from initiated status');
        }
        
        $this->apply(new PaymentProcessed($this->id, $processedBy));
    }
    
    public function fail(string $reason, string $failedBy): void
    {
        if ($this->status === 'failed') {
            throw new InvalidOperationException('Payment is already failed');
        }
        
        $this->apply(new PaymentFailed($this->id, $reason, $failedBy));
    }
    
    public function refund(string $reason, string $refundedBy): void
    {
        if ($this->status !== 'processed') {
            throw new InvalidOperationException('Payment can only be refunded from processed status');
        }
        
        $this->apply(new PaymentRefunded($this->id, $reason, $refundedBy));
    }
    
    public function getUncommittedEvents(): array
    {
        return $this->uncommittedEvents;
    }
    
    public function markEventsAsCommitted(): void
    {
        $this->uncommittedEvents = [];
    }
    
    public function getVersion(): int
    {
        return $this->version;
    }
    
    private function apply(DomainEvent $event): void
    {
        $this->uncommittedEvents[] = $event;
        $this->handle($event);
    }
    
    private function handle(DomainEvent $event): void
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
    }
    
    private function handlePaymentInitiated(PaymentInitiated $event): void
    {
        $this->id = $event->getPaymentId();
        $this->organizationId = $event->getOrganizationId();
        $this->customerName = $event->getCustomerName();
        $this->customerEmail = $event->getCustomerEmail();
        $this->amount = $event->getAmount();
        $this->currency = $event->getCurrency();
        $this->status = 'initiated';
        $this->createdAt = $event->getOccurredAt();
        $this->createdBy = $event->getCreatedBy();
        $this->version++;
    }
    
    private function handlePaymentProcessed(PaymentProcessed $event): void
    {
        $this->status = 'processed';
        $this->version++;
    }
    
    private function handlePaymentFailed(PaymentFailed $event): void
    {
        $this->status = 'failed';
        $this->version++;
    }
    
    private function handlePaymentRefunded(PaymentRefunded $event): void
    {
        $this->status = 'refunded';
        $this->version++;
    }
    
    // Getters...
    public function getId(): string { return $this->id; }
    public function getOrganizationId(): string { return $this->organizationId; }
    public function getCustomerName(): string { return $this->customerName; }
    public function getCustomerEmail(): string { return $this->customerEmail; }
    public function getAmount(): string { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getCreatedBy(): string { return $this->createdBy; }
}
```

**Résultat** : Agrégat Event Sourcing complet.

### 5. **Créer les Projections**

**Avec Gyroscops** : J'ai créé les projections :

```php
// ✅ Projection Payment Hive (Projet Hive)
final class PaymentProjection
{
    public function __construct(
        private Connection $connection
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
        $sql = 'UPDATE payment_projections SET status = ?, updated_at = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'processed',
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getPaymentId()
        ]);
    }
    
    private function handlePaymentFailed(PaymentFailed $event): void
    {
        $sql = 'UPDATE payment_projections SET status = ?, failure_reason = ?, updated_at = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'failed',
            $event->getReason(),
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getPaymentId()
        ]);
    }
    
    private function handlePaymentRefunded(PaymentRefunded $event): void
    {
        $sql = 'UPDATE payment_projections SET status = ?, refund_reason = ?, updated_at = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'refunded',
            $event->getReason(),
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getPaymentId()
        ]);
    }
}
```

**Résultat** : Projection pour les vues de lecture.

## Les Avantages du Stockage SQL Event Sourcing

### 1. **Audit Trail Complet**

**Avec Gyroscops** : Le stockage SQL Event Sourcing m'a donné un audit trail complet :
- Historique complet des changements
- Qui a fait quoi et quand
- Raison de chaque changement
- Traçabilité totale

**Résultat** : Audit trail parfait pour la conformité.

### 2. **Debugging Facilité**

**Avec Gyroscops** : Le stockage SQL Event Sourcing m'a facilité le debugging :
- Reconstruction de l'état à n'importe quel moment
- Compréhension des causes d'erreur
- Simulation de scénarios
- Analyse des comportements

**Résultat** : Debugging et maintenance simplifiés.

### 3. **Évolution des Vues Métier**

**Avec Gyroscops** : Le stockage SQL Event Sourcing m'a permis d'évoluer les vues :
- Nouvelles projections sans migration
- Vues personnalisées par contexte
- Analytics avancées
- Rapports complexes

**Résultat** : Flexibilité maximale pour les vues.

### 4. **Intégrité des Données**

**Avec Gyroscops** : Le stockage SQL Event Sourcing m'a garanti l'intégrité :
- Événements immutables
- Versioning des agrégats
- Optimistic locking
- Cohérence garantie

**Résultat** : Intégrité des données assurée.

## Les Inconvénients du Stockage SQL Event Sourcing

### 1. **Complexité Technique**

**Avec Gyroscops** : Le stockage SQL Event Sourcing a ajouté de la complexité :
- Courbe d'apprentissage importante
- Plus de code à maintenir
- Concepts avancés
- Debugging plus complexe

**Résultat** : Complexité technique élevée.

### 2. **Performance des Lectures**

**Avec Gyroscops** : Le stockage SQL Event Sourcing peut avoir des problèmes de performance :
- Reconstruction coûteuse
- Requêtes complexes
- Besoin de projections
- Cache complexe

**Résultat** : Performance des lectures dégradée.

### 3. **Stockage Important**

**Avec Gyroscops** : Le stockage SQL Event Sourcing consomme plus d'espace :
- Tous les événements stockés
- Métadonnées importantes
- Snapshots réguliers
- Croissance continue

**Résultat** : Consommation d'espace importante.

## Les Pièges à Éviter

### 1. **Événements Trop Granulaires**

**❌ Mauvais** : Un événement par propriété modifiée
**✅ Bon** : Un événement par action métier significative

**Pourquoi c'est important ?** Trop d'événements rendent le système complexe.

### 2. **Projections Synchrones**

**❌ Mauvais** : Projections mises à jour de façon synchrone
**✅ Bon** : Projections asynchrones avec Event Bus

**Pourquoi c'est crucial ?** Les projections synchrones tuent les performances.

### 3. **Pas de Snapshots**

**❌ Mauvais** : Reconstruction complète à chaque fois
**✅ Bon** : Snapshots réguliers pour optimiser

**Pourquoi c'est essentiel ?** Sans snapshots, les performances se dégradent.

## 🏗️ Implémentation Concrète dans le Projet Hive

### Stockage SQL Event Sourcing Appliqué à Hive

Le projet Hive applique concrètement les principes du stockage SQL Event Sourcing à travers son architecture et ses ADR (Architecture Decision Records). Voici comment :

#### Configuration Event Store Hive

```php
// ✅ Configuration Event Store Hive (Projet Hive)
final class HiveEventStoreConfiguration
{
    public function configureEventStore(ContainerBuilder $container): void
    {
        // Configuration de l'Event Store
        $container->register(SqlEventStore::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration des projections
        $container->register(PaymentProjection::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration de l'Event Bus
        $container->register(EventBus::class)
            ->setAutowired(true)
            ->setPublic(true);
    }
}
```

### Références aux ADR du Projet Hive

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du projet Hive :
- **HIVE008** : Event Collaboration - Collaboration par événements
- **HIVE009** : Message Buses - Bus de messages
- **HIVE010** : Repositories - Repositories de base
- **HIVE012** : Database Repositories - Repositories de base de données
- **HIVE014** : Projections Event Sourcing - Projections Event Sourcing

{{< chapter-nav >}}
  {{{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux comprendre le stockage SQL Event Sourcing + CQS" 
    subtitle="Vous voulez voir une approche Event Sourcing avec séparation des commandes et requêtes" 
    criteria="Équipe expérimentée,Besoin d'optimiser les performances,Event Sourcing déjà en place,Séparation des responsabilités importante" 
    time="30-40 minutes" 
    chapter="19" 
    chapter-title="Stockage SQL - Event Sourcing + CQS" 
    chapter-url="/chapitres/stockage/chapitre-51-stockage-sql-event-sourcing-cqs/" 
  >}}}}
  
  {{{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux comprendre le stockage SQL Event Sourcing + CQRS" 
    subtitle="Vous voulez voir une approche Event Sourcing avec séparation complète des modèles" 
    criteria="Équipe très expérimentée,Besoin de performance maximale,Event Sourcing déjà en place,Complexité élevée acceptable" 
    time="35-50 minutes" 
    chapter="58" 
    chapter-title="Stockage SQL - Event Sourcing + CQRS" 
    chapter-url="/chapitres/stockage/chapitre-51-stockage-sql-event-sourcing-cqrs/" 
  >}}}}
  
  {{{< chapter-option 
    letter="C" 
    color="red" 
    title="Je veux comprendre le stockage API" 
    subtitle="Vous voulez voir comment intégrer des APIs externes" 
    criteria="Équipe expérimentée,Besoin d'intégrer des services externes,Données distribuées,Intégrations multiples" 
    time="25-35 minutes" 
    chapter="59" 
    chapter-title="Stockage API - Approche Classique" 
    chapter-url="/chapitres/stockage/chapitre-51-stockage-api-classique/" 
  >}}}}
  
{{< /chapter-nav >}}