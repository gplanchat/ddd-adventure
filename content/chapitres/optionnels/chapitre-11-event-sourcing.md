---
title: "Chapitre 15 : Event Sourcing - La Source de Vérité"
description: "Maîtriser l'Event Sourcing pour une traçabilité complète et une reconstruction d'état"
date: 2024-12-19
draft: true
type: "docs"
weight: 15
---

## 🎯 Objectif de ce Chapitre

### Mon Problème : Comment Garder l'Historique Complet des Changements ?

**Voici ce qui s'est passé avec Gyroscops** : J'avais une application qui fonctionnait bien, mais quand un client me demandait "Pourquoi ce paiement a-t-il échoué ?" ou "Qui a modifié cette commande ?", je ne savais pas répondre. Les données étaient là, mais l'historique des changements était perdu.

**Mais attendez...** Quand j'ai voulu implémenter un audit trail complet, j'étais perdu. Comment stocker tous les événements ? Comment reconstruire l'état ? Comment gérer les performances ?

**Soudain, je réalisais que l'Event Sourcing était la solution !** Il me fallait une approche structurée pour capturer et stocker tous les événements métier.

### L'Event Sourcing : Mon Guide Complet

L'Event Sourcing m'a permis de :
- **Tracer** tous les changements d'état
- **Reconstruire** l'état à n'importe quel moment
- **Auditer** toutes les actions utilisateurs
- **Déboguer** les problèmes complexes

## Qu'est-ce que l'Event Sourcing ?

### Le Concept Fondamental

L'Event Sourcing consiste à stocker les événements comme source de vérité au lieu de l'état final. **L'idée** : Chaque changement d'état est capturé comme un événement immuable, et l'état actuel est reconstruit en appliquant tous les événements.

**Avec Gyroscops, voici comment j'ai structuré l'Event Sourcing** :

### Les 4 Piliers de l'Event Sourcing

#### 1. **Événements Immutables** - L'historique ne change jamais

**Voici comment j'ai implémenté les événements immutables avec Gyroscops** :

**Événements Typés** :
- Chaque événement a un type unique
- Les données sont immutables
- L'horodatage est inclus
- L'utilisateur est tracé

**Sérialisation** :
- Événements sérialisés en JSON
- Versioning des événements
- Migration des anciens formats

#### 2. **Event Store** - La base de données des événements

**Voici comment j'ai implémenté l'Event Store avec Gyroscops** :

**Stockage** :
- Base de données dédiée aux événements
- Indexation par agrégat et date
- Compression des anciens événements
- Sauvegarde régulière

**Performance** :
- Requêtes optimisées
- Cache des événements récents
- Pagination efficace
- Archivage automatique

#### 3. **Reconstruction d'État** - Reconstruire l'état à partir des événements

**Voici comment j'ai implémenté la reconstruction avec Gyroscops** :

**Projections** :
- Projections de lecture optimisées
- Cache des projections
- Mise à jour en temps réel
- Gestion des erreurs

**Performance** :
- Reconstruction incrémentale
- Snapshot périodique
- Parallélisation
- Optimisation des requêtes

#### 4. **Gestion des Versions** - Évoluer sans casser l'existant

**Voici comment j'ai géré les versions avec Gyroscops** :

**Migration** :
- Upgraders pour les anciens événements
- Tests de migration
- Rollback possible
- Documentation des changements

**Compatibilité** :
- Support des anciennes versions
- Détection des versions
- Conversion automatique
- Validation des données

## Comment Implémenter l'Event Sourcing

### 1. **Définir les Événements**

**Avec Gyroscops** : J'ai défini les événements métier :

```php
// ✅ Événements Métier Gyroscops Cloud (Projet Gyroscops Cloud)
final class PaymentCreated implements DomainEvent
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly OrganizationId $organizationId,
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly Price $amount,
        public readonly \DateTimeImmutable $createdAt,
        public readonly UserId $createdBy
    ) {}
    
    public function getEventType(): string
    {
        return 'payment.created';
    }
    
    public function getAggregateId(): string
    {
        return $this->paymentId->toString();
    }
    
    public function getVersion(): int
    {
        return 1;
    }
    
    public function toArray(): array
    {
        return [
            'payment_id' => $this->paymentId->toString(),
            'organization_id' => $this->organizationId->toString(),
            'customer_name' => $this->customerName,
            'customer_email' => $this->customerEmail,
            'amount' => $this->amount->toArray(),
            'created_at' => $this->createdAt->format(\DateTimeImmutable::ATOM),
            'created_by' => $this->createdBy->toString()
        ];
    }
}
```

**Résultat** : Événements typés et immutables.

### 2. **Créer l'Event Store**

**Avec Gyroscops** : J'ai créé l'Event Store :

```php
// ✅ Event Store Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveEventStore implements EventStoreInterface
{
    public function __construct(
        private Connection $connection,
        private EventSerializer $serializer,
        private LoggerInterface $logger
    ) {}
    
    public function append(string $aggregateId, array $events, int $expectedVersion): void
    {
        $this->connection->beginTransaction();
        
        try {
            // Vérifier la version attendue
            $currentVersion = $this->getCurrentVersion($aggregateId);
            if ($currentVersion !== $expectedVersion) {
                throw new ConcurrencyException("Expected version {$expectedVersion}, got {$currentVersion}");
            }
            
            // Insérer les événements
            foreach ($events as $event) {
                $this->insertEvent($aggregateId, $event);
            }
            
            $this->connection->commit();
            
            $this->logger->info('Events appended', [
                'aggregate_id' => $aggregateId,
                'event_count' => count($events),
                'version' => $expectedVersion + count($events)
            ]);
            
        } catch (\Exception $e) {
            $this->connection->rollBack();
            
            $this->logger->error('Failed to append events', [
                'aggregate_id' => $aggregateId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    public function getEvents(string $aggregateId, int $fromVersion = 0): array
    {
        $sql = 'SELECT * FROM events 
                WHERE aggregate_id = ? AND version > ? 
                ORDER BY version ASC';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$aggregateId, $fromVersion]);
        
        $events = [];
        while ($row = $stmt->fetchAssociative()) {
            $events[] = $this->serializer->deserialize($row['event_data']);
        }
        
        return $events;
    }
    
    private function insertEvent(string $aggregateId, DomainEvent $event): void
    {
        $sql = 'INSERT INTO events (aggregate_id, event_type, event_data, version, occurred_at) 
                VALUES (?, ?, ?, ?, ?)';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $aggregateId,
            $event->getEventType(),
            json_encode($event->toArray()),
            $this->getNextVersion($aggregateId),
            (new \DateTimeImmutable())->format('Y-m-d H:i:s')
        ]);
    }
}
```

**Résultat** : Event Store robuste et performant.

### 3. **Implémenter la Reconstruction d'État**

**Avec Gyroscops** : J'ai implémenté la reconstruction :

```php
// ✅ Reconstruction d'État Gyroscops Cloud (Projet Gyroscops Cloud)
final class PaymentAggregate
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
    
    public static function fromEvents(array $events): self
    {
        $aggregate = new self();
        
        foreach ($events as $event) {
            $aggregate->apply($event);
        }
        
        return $aggregate;
    }
    
    public function createPayment(
        PaymentId $id,
        OrganizationId $organizationId,
        string $customerName,
        string $customerEmail,
        Price $amount,
        UserId $createdBy
    ): void {
        $this->raiseEvent(new PaymentCreated(
            $id,
            $organizationId,
            $customerName,
            $customerEmail,
            $amount,
            new \DateTimeImmutable(),
            $createdBy
        ));
    }
    
    public function processPayment(): void
    {
        if ($this->status !== PaymentStatus::PENDING) {
            throw new InvalidOperationException('Payment is not pending');
        }
        
        $this->raiseEvent(new PaymentProcessed(
            $this->id,
            new \DateTimeImmutable()
        ));
    }
    
    private function raiseEvent(DomainEvent $event): void
    {
        $this->uncommittedEvents[] = $event;
        $this->apply($event);
    }
    
    private function apply(DomainEvent $event): void
    {
        match ($event::class) {
            PaymentCreated::class => $this->applyPaymentCreated($event),
            PaymentProcessed::class => $this->applyPaymentProcessed($event),
            PaymentFailed::class => $this->applyPaymentFailed($event),
            default => throw new UnknownEventException(get_class($event))
        };
    }
    
    private function applyPaymentCreated(PaymentCreated $event): void
    {
        $this->id = $event->paymentId;
        $this->organizationId = $event->organizationId;
        $this->customerName = $event->customerName;
        $this->customerEmail = $event->customerEmail;
        $this->amount = $event->amount;
        $this->status = PaymentStatus::PENDING;
        $this->createdAt = $event->createdAt;
        $this->createdBy = $event->createdBy;
    }
    
    private function applyPaymentProcessed(PaymentProcessed $event): void
    {
        $this->status = PaymentStatus::COMPLETED;
    }
    
    public function getUncommittedEvents(): array
    {
        return $this->uncommittedEvents;
    }
    
    public function markEventsAsCommitted(): void
    {
        $this->uncommittedEvents = [];
    }
}
```

**Résultat** : Reconstruction d'état fiable et performante.

### 4. **Créer les Projections**

**Avec Gyroscops** : J'ai créé les projections :

```php
// ✅ Projections Gyroscops Cloud (Projet Gyroscops Cloud)
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
            PaymentProcessed::class => $this->handlePaymentProcessed($event),
            PaymentFailed::class => $this->handlePaymentFailed($event),
            default => $this->logger->warning('Unknown event type', [
                'event_type' => get_class($event)
            ])
        };
    }
    
    private function handlePaymentCreated(PaymentCreated $event): void
    {
        $sql = 'INSERT INTO payment_read_model 
                (id, organization_id, customer_name, customer_email, amount, status, created_at, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->paymentId->toString(),
            $event->organizationId->toString(),
            $event->customerName,
            $event->customerEmail,
            $event->amount->getAmount()->toString(),
            PaymentStatus::PENDING->value,
            $event->createdAt->format('Y-m-d H:i:s'),
            $event->createdBy->toString()
        ]);
        
        $this->logger->info('Payment projection updated', [
            'payment_id' => $event->paymentId->toString(),
            'event_type' => 'payment.created'
        ]);
    }
    
    private function handlePaymentProcessed(PaymentProcessed $event): void
    {
        $sql = 'UPDATE payment_read_model 
                SET status = ?, processed_at = ? 
                WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            PaymentStatus::COMPLETED->value,
            $event->processedAt->format('Y-m-d H:i:s'),
            $event->paymentId->toString()
        ]);
        
        $this->logger->info('Payment projection updated', [
            'payment_id' => $event->paymentId->toString(),
            'event_type' => 'payment.processed'
        ]);
    }
}
```

**Résultat** : Projections de lecture optimisées.

## Les Avantages de l'Event Sourcing

### 1. **Audit Trail Complet**

**Avec Gyroscops** : L'Event Sourcing m'a donné un audit trail complet :
- Historique de tous les changements
- Qui a fait quoi et quand
- Raison des changements
- Traçabilité complète

**Résultat** : Conformité réglementaire et debugging facilité.

### 2. **Reconstruction d'État**

**Avec Gyroscops** : L'Event Sourcing m'a permis de reconstruire l'état :
- État à n'importe quel moment
- Debugging des problèmes
- Tests de régression
- Analyse des comportements

**Résultat** : Debugging et maintenance facilités.

### 3. **Évolutivité**

**Avec Gyroscops** : L'Event Sourcing m'a donné de l'évolutivité :
- Ajout de nouvelles projections
- Évolution des événements
- Migration des données
- Rétrocompatibilité

**Résultat** : Évolution facilitée et rétrocompatibilité.

### 4. **Performance de Lecture**

**Avec Gyroscops** : L'Event Sourcing m'a optimisé les lectures :
- Projections optimisées
- Cache des projections
- Requêtes spécialisées
- Performance adaptée

**Résultat** : Lectures rapides et optimisées.

## Les Inconvénients de l'Event Sourcing

### 1. **Complexité Accrue**

**Avec Gyroscops** : L'Event Sourcing a ajouté de la complexité :
- Event Store à gérer
- Projections à maintenir
- Migration des événements
- Debugging plus difficile

**Résultat** : Courbe d'apprentissage plus importante.

### 2. **Performance d'Écriture**

**Avec Gyroscops** : L'Event Sourcing peut impacter les écritures :
- Stockage de tous les événements
- Reconstruction d'état
- Projections à mettre à jour
- Latence accrue

**Résultat** : Performance d'écriture potentiellement dégradée.

### 3. **Gestion des Versions**

**Avec Gyroscops** : L'Event Sourcing complique la gestion des versions :
- Migration des anciens événements
- Compatibilité des versions
- Tests de migration
- Documentation des changements

**Résultat** : Gestion des versions plus complexe.

### 4. **Stockage**

**Avec Gyroscops** : L'Event Sourcing augmente les besoins de stockage :
- Tous les événements stockés
- Croissance continue
- Archivage nécessaire
- Coût de stockage

**Résultat** : Besoins de stockage plus importants.

## Les Pièges à Éviter

### 1. **Événements Trop Granulaires**

**❌ Mauvais** : Un événement pour chaque setter
**✅ Bon** : Un événement pour chaque action métier significative

**Pourquoi c'est important ?** Des événements trop granulaires créent du bruit.

### 2. **Projections Trop Complexes**

**❌ Mauvais** : Une projection qui fait tout
**✅ Bon** : Une projection par usage

**Pourquoi c'est crucial ?** Des projections complexes sont difficiles à maintenir.

### 3. **Ignorer la Migration**

**❌ Mauvais** : Pas de plan de migration des événements
**✅ Bon** : Migration planifiée et testée

**Pourquoi c'est essentiel ?** La migration est nécessaire pour l'évolution.

### 4. **Snapshot Ignoré**

**❌ Mauvais** : Pas de snapshot pour les gros agrégats
**✅ Bon** : Snapshot périodique pour les performances

**Pourquoi c'est la clé ?** Les snapshots améliorent les performances.

## L'Évolution vers l'Event Sourcing

### Phase 1 : Architecture Classique

**Avec Gyroscops** : Au début, j'avais une architecture classique :
- État stocké directement
- Pas d'historique
- Audit limité
- Debugging difficile

**Résultat** : Développement rapide, maintenance difficile.

### Phase 2 : Introduction des Événements

**Avec Gyroscops** : J'ai introduit les événements :
- Événements métier capturés
- Logs d'audit
- Historique partiel
- Debugging amélioré

**Résultat** : Audit amélioré, complexité modérée.

### Phase 3 : Event Sourcing Complet

**Avec Gyroscops** : Maintenant, j'ai un Event Sourcing complet :
- Événements comme source de vérité
- Reconstruction d'état
- Audit trail complet
- Projections optimisées

**Résultat** : Traçabilité complète et évolutivité maximale.

## 🏗️ Implémentation Concrète dans le Projet Gyroscops Cloud

### Event Sourcing Appliqué à Gyroscops Cloud

Le Gyroscops Cloud applique concrètement les principes de l'Event Sourcing à travers son architecture et ses ADR (Architecture Decision Records). Voici comment :

#### Event Store Gyroscops Cloud

```php
// ✅ Event Store Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveEventStore
{
    public function __construct(
        private Connection $connection,
        private EventSerializer $serializer,
        private EventBus $eventBus,
        private LoggerInterface $logger
    ) {}
    
    public function append(string $aggregateId, array $events, int $expectedVersion): void
    {
        $this->connection->beginTransaction();
        
        try {
            // Vérifier la version attendue
            $currentVersion = $this->getCurrentVersion($aggregateId);
            if ($currentVersion !== $expectedVersion) {
                throw new ConcurrencyException("Expected version {$expectedVersion}, got {$currentVersion}");
            }
            
            // Insérer les événements
            foreach ($events as $event) {
                $this->insertEvent($aggregateId, $event);
                
                // Publier l'événement pour les projections
                $this->eventBus->publish($event);
            }
            
            $this->connection->commit();
            
            $this->logger->info('Events appended successfully', [
                'aggregate_id' => $aggregateId,
                'event_count' => count($events),
                'version' => $expectedVersion + count($events)
            ]);
            
        } catch (\Exception $e) {
            $this->connection->rollBack();
            
            $this->logger->error('Failed to append events', [
                'aggregate_id' => $aggregateId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    public function getEvents(string $aggregateId, int $fromVersion = 0): array
    {
        $sql = 'SELECT * FROM events 
                WHERE aggregate_id = ? AND version > ? 
                ORDER BY version ASC';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$aggregateId, $fromVersion]);
        
        $events = [];
        while ($row = $stmt->fetchAssociative()) {
            $events[] = $this->serializer->deserialize($row['event_data']);
        }
        
        return $events;
    }
    
    public function getEventsByType(string $eventType, int $limit = 100): array
    {
        $sql = 'SELECT * FROM events 
                WHERE event_type = ? 
                ORDER BY occurred_at DESC 
                LIMIT ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$eventType, $limit]);
        
        $events = [];
        while ($row = $stmt->fetchAssociative()) {
            $events[] = $this->serializer->deserialize($row['event_data']);
        }
        
        return $events;
    }
}
```

#### Projections Gyroscops Cloud

```php
// ✅ Projections Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveProjectionManager
{
    public function __construct(
        private array $projections,
        private LoggerInterface $logger
    ) {}
    
    public function handleEvent(DomainEvent $event): void
    {
        $this->logger->info('Handling event for projections', [
            'event_type' => get_class($event),
            'aggregate_id' => $event->getAggregateId()
        ]);
        
        foreach ($this->projections as $projection) {
            try {
                $projection->handle($event);
            } catch (\Exception $e) {
                $this->logger->error('Projection failed', [
                    'projection' => get_class($projection),
                    'event_type' => get_class($event),
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    public function rebuildProjection(string $projectionName): void
    {
        $projection = $this->projections[$projectionName];
        
        $this->logger->info('Rebuilding projection', [
            'projection' => $projectionName
        ]);
        
        // Supprimer les données existantes
        $projection->clear();
        
        // Reconstruire depuis le début
        $projection->rebuild();
        
        $this->logger->info('Projection rebuilt successfully', [
            'projection' => $projectionName
        ]);
    }
}
```

### Références aux ADR du Projet Gyroscops Cloud

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du Gyroscops Cloud :
- **HIVE008** : Event Collaboration - Collaboration basée sur les événements
- **HIVE009** : Message Buses - Bus de messages pour les événements
- **HIVE040** : Enhanced Models with Property Access Patterns - Modèles enrichis pour l'Event Sourcing
- **HIVE041** : Cross-Cutting Concerns Architecture - Architecture des préoccupations transversales

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux comprendre CQS" 
    subtitle="Vous voulez voir une alternative plus simple au CQRS" 
    criteria="Équipe expérimentée,Besoin d'une alternative au CQRS,Complexité élevée mais pas critique,Évolutivité importante" 
    time="25-35 minutes" 
    chapter="15" 
    chapter-title="Architecture CQS - Command Query Separation" 
    chapter-url="/chapitres/optionnels/chapitre-15-architecture-cqs/" 
  >}}}
  
  {{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux comprendre CQRS complet" 
    subtitle="Vous voulez voir la séparation complète entre commandes et requêtes" 
    criteria="Équipe très expérimentée,Besoin de CQRS complet,Complexité très élevée,Performance critique" 
    time="30-45 minutes" 
    chapter="15" 
    chapter-title="Architecture CQRS avec API Platform" 
    chapter-url="/chapitres/optionnels/chapitre-15-architecture-cqrs/" 
  >}}}
  
  {{< chapter-option 
    letter="C" 
    color="red" 
    title="Je veux comprendre les chapitres de stockage" 
    subtitle="Vous voulez voir comment implémenter la persistance selon différents patterns" 
    criteria="Équipe expérimentée,Besoin de comprendre la persistance,Patterns de stockage à choisir,Implémentation à faire" 
    time="30-45 minutes" 
    chapter="15" 
    chapter-title="Stockage SQL - Approche Classique" 
    chapter-url="/chapitres/stockage/chapitre-15-stockage-sql-classique/" 
  >}}}
  
  {{< chapter-option 
    letter="D" 
    color="blue" 
    title="Je veux comprendre les chapitres techniques" 
    subtitle="Vous voulez voir les aspects techniques d'affinement" 
    criteria="Équipe expérimentée,Besoin de comprendre les aspects techniques,Qualité et performance importantes,Bonnes pratiques à appliquer" 
    time="25-35 minutes" 
    chapter="58" 
    chapter-title="Gestion des Données et Validation" 
    chapter-url="/chapitres/techniques/chapitre-58-gestion-donnees-validation/" 
  >}}}
  
{{< /chapter-nav >}}