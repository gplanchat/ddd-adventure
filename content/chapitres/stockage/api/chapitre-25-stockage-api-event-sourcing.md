---
title: "Chapitre 63 : Stockage API - Event Sourcing Seul"
description: "Maîtriser le stockage via APIs externes avec Event Sourcing pour un audit trail complet"
date: 2024-12-19
draft: true
type: "docs"
weight: 63
---

## 🎯 Objectif de ce Chapitre

### Mon Problème : Comment Avoir un Audit Trail Complet avec les APIs Externes ?

**Voici ce qui s'est passé avec Gyroscops** : J'avais besoin d'un audit trail complet pour les intégrations API. Qui a créé cet utilisateur ? Quand ? Pourquoi l'API a-t-elle échoué ? J'avais besoin de pouvoir reconstruire l'état à n'importe quel moment et de déboguer facilement les problèmes d'intégration.

**Mais attendez...** Comment stocker tous les événements d'API ? Comment reconstruire l'état ? Comment gérer les projections ? Comment intégrer avec API Platform ?

**Soudain, je réalisais que l'Event Sourcing + API était parfait !** Il me fallait une méthode pour stocker les événements comme source de vérité tout en intégrant les APIs externes.

### Stockage API Event Sourcing : Mon Guide Pratique

Le stockage API Event Sourcing m'a permis de :
- **Auditer** complètement les intégrations
- **Déboguer** facilement les problèmes d'API
- **Reconstruire** l'état des intégrations
- **Évoluer** les vues métier des APIs

## Qu'est-ce que le Stockage API Event Sourcing ?

### Le Concept Fondamental

Le stockage API Event Sourcing combine l'utilisation d'APIs externes avec l'Event Sourcing pour stocker les événements comme source de vérité. **L'idée** : Au lieu de stocker l'état final des APIs, on stocke tous les événements qui ont mené à cet état.

**Avec Gyroscops, voici comment j'ai structuré le stockage API Event Sourcing** :

### Les 4 Piliers du Stockage API Event Sourcing

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
- `UserApiCreated`
- `UserApiUpdated`
- `UserApiEnabled`
- `UserApiDisabled`
- `UserApiDeleted`

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
- `UserApiProjection` (état actuel)
- `UserApiAuditProjection` (historique complet)
- `UserApiAnalyticsProjection` (statistiques)

#### 4. **Event Store** - Gestion des événements

**Voici comment j'ai implémenté l'Event Store avec Gyroscops** :

**Fonctionnalités** :
- Sauvegarde des événements
- Reconstruction des agrégats
- Gestion des versions
- Optimistic locking

## Comment Implémenter le Stockage API Event Sourcing

### 1. **Créer la Table des Événements**

**Avec Gyroscops** : J'ai créé la table des événements :

```sql
-- ✅ Table des événements API Hive (Projet Hive)
CREATE TABLE api_event_store (
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

**Résultat** : Table optimisée pour les événements d'API.

### 2. **Créer la Table des Snapshots**

**Avec Gyroscops** : J'ai créé la table des snapshots :

```sql
-- ✅ Table des snapshots API Hive (Projet Hive)
CREATE TABLE api_snapshots (
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
// ✅ Event Store API Hive (Projet Hive)
final class ApiEventStore implements EventStoreInterface
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
                FROM api_event_store 
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
        $sql = 'INSERT INTO api_event_store (event_id, aggregate_id, event_type, event_data, event_metadata, version) 
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

**Résultat** : Event Store robuste pour les APIs.

### 4. **Créer les Agrégats Event Sourcing**

**Avec Gyroscops** : J'ai créé les agrégats :

```php
// ✅ Agrégat User API Event Sourcing Hive (Projet Hive)
final class UserApiAggregate
{
    private string $id;
    private string $username;
    private string $email;
    private string $firstName;
    private string $lastName;
    private string $organizationId;
    private array $roles;
    private bool $enabled;
    private bool $emailVerified;
    private \DateTimeImmutable $createdAt;
    private string $createdBy;
    private int $version = 0;
    
    private array $uncommittedEvents = [];
    
    public static function create(
        string $id,
        string $username,
        string $email,
        string $firstName,
        string $lastName,
        string $organizationId,
        array $roles,
        string $createdBy
    ): self {
        $aggregate = new self();
        $aggregate->apply(new UserApiCreated(
            $id,
            $username,
            $email,
            $firstName,
            $lastName,
            $organizationId,
            $roles,
            $createdBy
        ));
        
        return $aggregate;
    }
    
    public function update(
        string $username,
        string $email,
        string $firstName,
        string $lastName,
        string $updatedBy
    ): void {
        $this->apply(new UserApiUpdated(
            $this->id,
            $username,
            $email,
            $firstName,
            $lastName,
            $updatedBy
        ));
    }
    
    public function enable(string $enabledBy): void
    {
        if ($this->enabled) {
            throw new InvalidOperationException('User is already enabled');
        }
        
        $this->apply(new UserApiEnabled($this->id, $enabledBy));
    }
    
    public function disable(string $disabledBy): void
    {
        if (!$this->enabled) {
            throw new InvalidOperationException('User is already disabled');
        }
        
        $this->apply(new UserApiDisabled($this->id, $disabledBy));
    }
    
    public function delete(string $deletedBy): void
    {
        $this->apply(new UserApiDeleted($this->id, $deletedBy));
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
            case UserApiCreated::class:
                $this->handleUserApiCreated($event);
                break;
            case UserApiUpdated::class:
                $this->handleUserApiUpdated($event);
                break;
            case UserApiEnabled::class:
                $this->handleUserApiEnabled($event);
                break;
            case UserApiDisabled::class:
                $this->handleUserApiDisabled($event);
                break;
            case UserApiDeleted::class:
                $this->handleUserApiDeleted($event);
                break;
        }
    }
    
    private function handleUserApiCreated(UserApiCreated $event): void
    {
        $this->id = $event->getUserId();
        $this->username = $event->getUsername();
        $this->email = $event->getEmail();
        $this->firstName = $event->getFirstName();
        $this->lastName = $event->getLastName();
        $this->organizationId = $event->getOrganizationId();
        $this->roles = $event->getRoles();
        $this->enabled = true;
        $this->emailVerified = false;
        $this->createdAt = $event->getOccurredAt();
        $this->createdBy = $event->getCreatedBy();
        $this->version++;
    }
    
    private function handleUserApiUpdated(UserApiUpdated $event): void
    {
        $this->username = $event->getUsername();
        $this->email = $event->getEmail();
        $this->firstName = $event->getFirstName();
        $this->lastName = $event->getLastName();
        $this->version++;
    }
    
    private function handleUserApiEnabled(UserApiEnabled $event): void
    {
        $this->enabled = true;
        $this->version++;
    }
    
    private function handleUserApiDisabled(UserApiDisabled $event): void
    {
        $this->enabled = false;
        $this->version++;
    }
    
    private function handleUserApiDeleted(UserApiDeleted $event): void
    {
        $this->enabled = false;
        $this->version++;
    }
    
    // Getters...
    public function getId(): string { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getEmail(): string { return $this->email; }
    public function getFirstName(): string { return $this->firstName; }
    public function getLastName(): string { return $this->lastName; }
    public function getOrganizationId(): string { return $this->organizationId; }
    public function getRoles(): array { return $this->roles; }
    public function isEnabled(): bool { return $this->enabled; }
    public function isEmailVerified(): bool { return $this->emailVerified; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getCreatedBy(): string { return $this->createdBy; }
}
```

**Résultat** : Agrégat Event Sourcing pour les APIs.

### 5. **Créer les Projections**

**Avec Gyroscops** : J'ai créé les projections :

```php
// ✅ Projection User API Hive (Projet Hive)
final class UserApiProjectionHandler
{
    public function __construct(
        private Connection $connection
    ) {}
    
    public function handle(DomainEvent $event): void
    {
        switch ($event::class) {
            case UserApiCreated::class:
                $this->handleUserApiCreated($event);
                break;
            case UserApiUpdated::class:
                $this->handleUserApiUpdated($event);
                break;
            case UserApiEnabled::class:
                $this->handleUserApiEnabled($event);
                break;
            case UserApiDisabled::class:
                $this->handleUserApiDisabled($event);
                break;
            case UserApiDeleted::class:
                $this->handleUserApiDeleted($event);
                break;
        }
    }
    
    private function handleUserApiCreated(UserApiCreated $event): void
    {
        $sql = 'INSERT INTO user_api_projections (id, username, email, first_name, last_name, organization_id, roles, enabled, email_verified, created_at, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getUserId(),
            $event->getUsername(),
            $event->getEmail(),
            $event->getFirstName(),
            $event->getLastName(),
            $event->getOrganizationId(),
            json_encode($event->getRoles()),
            true,
            false,
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getCreatedBy()
        ]);
    }
    
    private function handleUserApiUpdated(UserApiUpdated $event): void
    {
        $sql = 'UPDATE user_api_projections SET username = ?, email = ?, first_name = ?, last_name = ?, updated_at = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getUsername(),
            $event->getEmail(),
            $event->getFirstName(),
            $event->getLastName(),
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getUserId()
        ]);
    }
    
    private function handleUserApiEnabled(UserApiEnabled $event): void
    {
        $sql = 'UPDATE user_api_projections SET enabled = 1, updated_at = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getUserId()
        ]);
    }
    
    private function handleUserApiDisabled(UserApiDisabled $event): void
    {
        $sql = 'UPDATE user_api_projections SET enabled = 0, updated_at = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getUserId()
        ]);
    }
    
    private function handleUserApiDeleted(UserApiDeleted $event): void
    {
        $sql = 'UPDATE user_api_projections SET deleted = 1, deleted_at = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getUserId()
        ]);
    }
}
```

**Résultat** : Projection pour les vues de lecture.

## Les Avantages du Stockage API Event Sourcing

### 1. **Audit Trail Complet**

**Avec Gyroscops** : Le stockage API Event Sourcing m'a donné un audit trail complet :
- Historique complet des changements d'API
- Qui a fait quoi et quand
- Raison de chaque changement
- Traçabilité totale des intégrations

**Résultat** : Audit trail parfait pour les intégrations API.

### 2. **Debugging Facilité**

**Avec Gyroscops** : Le stockage API Event Sourcing m'a facilité le debugging :
- Reconstruction de l'état à n'importe quel moment
- Compréhension des causes d'erreur d'API
- Simulation de scénarios d'intégration
- Analyse des comportements d'API

**Résultat** : Debugging des intégrations API simplifié.

### 3. **Évolution des Vues Métier**

**Avec Gyroscops** : Le stockage API Event Sourcing m'a permis d'évoluer les vues :
- Nouvelles projections sans migration
- Vues personnalisées par contexte d'API
- Analytics avancées des intégrations
- Rapports complexes sur les APIs

**Résultat** : Flexibilité maximale pour les vues d'API.

### 4. **Intégrité des Données**

**Avec Gyroscops** : Le stockage API Event Sourcing m'a garanti l'intégrité :
- Événements immutables
- Versioning des agrégats
- Optimistic locking
- Cohérence garantie

**Résultat** : Intégrité des données d'API assurée.

## Les Inconvénients du Stockage API Event Sourcing

### 1. **Complexité Technique**

**Avec Gyroscops** : Le stockage API Event Sourcing a ajouté de la complexité :
- Courbe d'apprentissage importante
- Plus de code à maintenir
- Concepts avancés
- Debugging plus complexe

**Résultat** : Complexité technique élevée.

### 2. **Performance des Lectures**

**Avec Gyroscops** : Le stockage API Event Sourcing peut avoir des problèmes de performance :
- Reconstruction coûteuse
- Requêtes complexes
- Besoin de projections
- Cache complexe

**Résultat** : Performance des lectures dégradée.

### 3. **Stockage Important**

**Avec Gyroscops** : Le stockage API Event Sourcing consomme plus d'espace :
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

### Stockage API Event Sourcing Appliqué à Hive

Le projet Hive applique concrètement les principes du stockage API Event Sourcing à travers son architecture et ses ADR (Architecture Decision Records). Voici comment :

#### Configuration API Event Store Hive

```php
// ✅ Configuration API Event Store Hive (Projet Hive)
final class HiveApiEventStoreConfiguration
{
    public function configureApiEventStore(ContainerBuilder $container): void
    {
        // Configuration de l'Event Store
        $container->register(ApiEventStore::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration des projections
        $container->register(UserApiProjectionHandler::class)
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
- **HIVE015** : API Repositories - Repositories d'API
- **HIVE025** : Authorization System - Système d'autorisation
- **HIVE026** : Keycloak Resource and Scope Management - Gestion des ressources Keycloak

{{< chapter-nav >}}
  {{{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux comprendre le stockage API Event Sourcing + CQS" 
    subtitle="Vous voulez voir une approche Event Sourcing avec séparation des commandes et requêtes" 
    criteria="Équipe expérimentée,Besoin d'optimiser les performances,Event Sourcing déjà en place,Séparation des responsabilités importante" 
    time="30-40 minutes" 
    chapter="63" 
    chapter-title="Stockage API - Event Sourcing + CQS" 
    chapter-url="/chapitres/stockage/api/chapitre-51-stockage-api-event-sourcing-cqs/" 
  >}}}}
  
  {{{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux comprendre le stockage ElasticSearch" 
    subtitle="Vous voulez voir comment optimiser la recherche" 
    criteria="Équipe expérimentée,Besoin de recherche avancée,Analytics importantes,Performance de recherche critique" 
    time="30-40 minutes" 
    chapter="26" 
    chapter-title="Stockage ElasticSearch - Approche Classique" 
    chapter-url="/chapitres/stockage/chapitre-51-stockage-elasticsearch-classique/" 
  >}}}}
  
  {{{< chapter-option 
    letter="C" 
    color="red" 
    title="Je veux comprendre le stockage MongoDB" 
    subtitle="Vous voulez voir comment gérer des données semi-structurées" 
    criteria="Équipe expérimentée,Besoin de flexibilité du schéma,Données semi-structurées,Performance de lecture élevée" 
    time="30-40 minutes" 
    chapter="27" 
    chapter-title="Stockage MongoDB - Approche Classique" 
    chapter-url="/chapitres/stockage/chapitre-51-stockage-mongodb-classique/" 
  >}}}}
  
{{< /chapter-nav >}}