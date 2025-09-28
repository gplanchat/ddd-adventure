---
title: "Chapitre 26 : Stockage API - Event Sourcing + CQS"
description: "Maîtriser le stockage via APIs externes avec Event Sourcing et Command Query Separation pour des performances optimisées"
date: 2024-12-19
draft: true
type: "docs"
weight: 26
---

## 🎯 Objectif de ce Chapitre

### Mon Problème : Comment Optimiser les Performances avec Event Sourcing et APIs Externes ?

**Voici ce qui s'est passé avec Gyroscops** : J'avais implémenté l'Event Sourcing pour l'audit trail des APIs, mais les performances de lecture étaient dégradées. J'avais besoin d'optimiser les lectures tout en gardant l'Event Sourcing pour l'écriture et l'intégration avec les APIs externes.

**Mais attendez...** Comment séparer les commandes et les requêtes ? Comment optimiser les projections ? Comment gérer la cohérence ? Comment intégrer avec API Platform ?

**Soudain, je réalisais que CQS + Event Sourcing + API était parfait !** Il me fallait une méthode pour optimiser les performances tout en gardant l'audit trail et l'intégration API.

### Stockage API Event Sourcing + CQS : Mon Guide Pratique

Le stockage API Event Sourcing + CQS m'a permis de :
- **Optimiser** les performances de lecture
- **Conserver** l'audit trail complet
- **Séparer** les responsabilités
- **Équilibrer** complexité et performance pour les APIs

## Qu'est-ce que le Stockage API Event Sourcing + CQS ?

### Le Concept Fondamental

Le stockage API Event Sourcing + CQS combine l'Event Sourcing pour l'écriture avec la Command Query Separation pour optimiser les lectures, le tout intégré avec des APIs externes. **L'idée** : Écriture via Event Sourcing + APIs, lecture via projections optimisées.

**Avec Gyroscops, voici comment j'ai structuré le stockage API Event Sourcing + CQS** :

### Les 4 Piliers du Stockage API Event Sourcing + CQS

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

#### 2. **Command Side** - Gestion des écritures via APIs

**Voici comment j'ai implémenté le Command Side avec Gyroscops** :

**Composants** :
- Agrégats Event Sourcing
- Command Handlers
- Clients API externes
- Event Store
- Event Bus

**Exemples** :
- `CreateUserApiCommand`
- `UpdateUserApiCommand`
- `EnableUserApiCommand`

#### 3. **Query Side** - Optimisation des lectures

**Voici comment j'ai implémenté le Query Side avec Gyroscops** :

**Composants** :
- Projections optimisées
- Query Models
- Query Handlers
- Cache intelligent

**Exemples** :
- `UserApiQueryModel`
- `UserApiListQueryModel`
- `UserApiAnalyticsQueryModel`

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

## Comment Implémenter le Stockage API Event Sourcing + CQS

### 1. **Créer l'Event Store (Command Side)**

**Avec Gyroscops** : J'ai créé l'Event Store :

```php
// ✅ Event Store API Gyroscops Cloud (Projet Gyroscops Cloud)
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

**Résultat** : Event Store robuste pour l'écriture via APIs.

### 2. **Créer les Command Handlers**

**Avec Gyroscops** : J'ai créé les Command Handlers :

```php
// ✅ Command Handler User API Gyroscops Cloud (Projet Gyroscops Cloud)
final class UserApiCommandHandler
{
    public function __construct(
        private ApiEventStore $eventStore,
        private EventBusInterface $eventBus,
        private KeycloakClient $keycloakClient
    ) {}
    
    public function handleCreateUserApi(CreateUserApiCommand $command): void
    {
        // Reconstruire l'agrégat depuis les événements
        $events = $this->eventStore->getEvents($command->getUserId());
        $aggregate = UserApiAggregate::fromEvents($events);
        
        // Exécuter la commande
        $aggregate->create(
            $command->getUserId(),
            $command->getUsername(),
            $command->getEmail(),
            $command->getFirstName(),
            $command->getLastName(),
            $command->getOrganizationId(),
            $command->getRoles(),
            $command->getCreatedBy()
        );
        
        // Créer l'utilisateur dans Keycloak
        $userData = $this->prepareKeycloakData($aggregate);
        $result = $this->keycloakClient->createUser($userData);
        
        // Sauvegarder les événements
        $this->eventStore->append(
            $command->getUserId(),
            $aggregate->getUncommittedEvents(),
            $aggregate->getVersion() - count($aggregate->getUncommittedEvents())
        );
        
        // Publier les événements
        foreach ($aggregate->getUncommittedEvents() as $event) {
            $this->eventBus->publish($event);
        }
        
        $aggregate->markEventsAsCommitted();
    }
    
    public function handleUpdateUserApi(UpdateUserApiCommand $command): void
    {
        // Reconstruire l'agrégat depuis les événements
        $events = $this->eventStore->getEvents($command->getUserId());
        $aggregate = UserApiAggregate::fromEvents($events);
        
        // Exécuter la commande
        $aggregate->update(
            $command->getUsername(),
            $command->getEmail(),
            $command->getFirstName(),
            $command->getLastName(),
            $command->getUpdatedBy()
        );
        
        // Mettre à jour l'utilisateur dans Keycloak
        $userData = $this->prepareKeycloakData($aggregate);
        $this->keycloakClient->updateUser($command->getUserId(), $userData);
        
        // Sauvegarder les événements
        $this->eventStore->append(
            $command->getUserId(),
            $aggregate->getUncommittedEvents(),
            $aggregate->getVersion() - count($aggregate->getUncommittedEvents())
        );
        
        // Publier les événements
        foreach ($aggregate->getUncommittedEvents() as $event) {
            $this->eventBus->publish($event);
        }
        
        $aggregate->markEventsAsCommitted();
    }
    
    public function handleEnableUserApi(EnableUserApiCommand $command): void
    {
        // Reconstruire l'agrégat depuis les événements
        $events = $this->eventStore->getEvents($command->getUserId());
        $aggregate = UserApiAggregate::fromEvents($events);
        
        // Exécuter la commande
        $aggregate->enable($command->getEnabledBy());
        
        // Activer l'utilisateur dans Keycloak
        $this->keycloakClient->enableUser($command->getUserId());
        
        // Sauvegarder les événements
        $this->eventStore->append(
            $command->getUserId(),
            $aggregate->getUncommittedEvents(),
            $aggregate->getVersion() - count($aggregate->getUncommittedEvents())
        );
        
        // Publier les événements
        foreach ($aggregate->getUncommittedEvents() as $event) {
            $this->eventBus->publish($event);
        }
        
        $aggregate->markEventsAsCommitted();
    }
    
    private function prepareKeycloakData(UserApiAggregate $aggregate): array
    {
        return [
            'id' => $aggregate->getId(),
            'username' => $aggregate->getUsername(),
            'email' => $aggregate->getEmail(),
            'firstName' => $aggregate->getFirstName(),
            'lastName' => $aggregate->getLastName(),
            'enabled' => $aggregate->isEnabled(),
            'emailVerified' => $aggregate->isEmailVerified(),
            'attributes' => [
                'organizationId' => [$aggregate->getOrganizationId()],
                'roles' => $aggregate->getRoles()
            ]
        ];
    }
}
```

**Résultat** : Command Handlers pour l'écriture via APIs.

### 3. **Créer les Query Models**

**Avec Gyroscops** : J'ai créé les Query Models :

```php
// ✅ Query Model User API Gyroscops Cloud (Projet Gyroscops Cloud)
final class UserApiQueryModel
{
    public function __construct(
        public readonly string $id,
        public readonly string $username,
        public readonly string $email,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly bool $enabled,
        public readonly bool $emailVerified,
        public readonly string $organizationId,
        public readonly array $roles,
        public readonly \DateTimeImmutable $createdAt,
        public readonly string $createdBy,
        public readonly ?\DateTimeImmutable $updatedAt = null,
        public readonly ?string $updatedBy = null,
        public readonly ?\DateTimeImmutable $lastLoginAt = null,
        public readonly array $metadata = []
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            username: $data['username'],
            email: $data['email'],
            firstName: $data['firstName'] ?? '',
            lastName: $data['lastName'] ?? '',
            enabled: $data['enabled'] ?? true,
            emailVerified: $data['emailVerified'] ?? false,
            organizationId: $data['organizationId'],
            roles: $data['roles'] ?? [],
            createdAt: new \DateTimeImmutable($data['createdAt']),
            createdBy: $data['createdBy'],
            updatedAt: $data['updatedAt'] ? new \DateTimeImmutable($data['updatedAt']) : null,
            updatedBy: $data['updatedBy'] ?? null,
            lastLoginAt: $data['lastLoginAt'] ? new \DateTimeImmutable($data['lastLoginAt']) : null,
            metadata: $data['metadata'] ?? []
        );
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'enabled' => $this->enabled,
            'emailVerified' => $this->emailVerified,
            'organizationId' => $this->organizationId,
            'roles' => $this->roles,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'createdBy' => $this->createdBy,
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'updatedBy' => $this->updatedBy,
            'lastLoginAt' => $this->lastLoginAt?->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata
        ];
    }
}

// ✅ Query Model User API List Gyroscops Cloud (Projet Gyroscops Cloud)
final class UserApiListQueryModel
{
    public function __construct(
        public readonly string $id,
        public readonly string $username,
        public readonly string $email,
        public readonly string $fullName,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly string $statusColor,
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?\DateTimeImmutable $lastLoginAt = null,
        public readonly array $roles = [],
        public readonly bool $canEdit = false,
        public readonly bool $canDelete = false,
        public readonly bool $canEnable = false,
        public readonly bool $canDisable = false
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            username: $data['username'],
            email: $data['email'],
            fullName: trim(($data['firstName'] ?? '') . ' ' . ($data['lastName'] ?? '')),
            status: self::determineStatus($data),
            statusLabel: self::getStatusLabel($data),
            statusColor: self::getStatusColor($data),
            createdAt: new \DateTimeImmutable($data['createdAt']),
            lastLoginAt: $data['lastLoginAt'] ? new \DateTimeImmutable($data['lastLoginAt']) : null,
            roles: $data['roles'] ?? [],
            canEdit: self::canEdit($data),
            canDelete: self::canDelete($data),
            canEnable: self::canEnable($data),
            canDisable: self::canDisable($data)
        );
    }
    
    private static function determineStatus(array $data): string
    {
        if (!$data['enabled']) return 'disabled';
        if (!$data['emailVerified']) return 'unverified';
        return 'active';
    }
    
    private static function getStatusLabel(array $data): string
    {
        return match(self::determineStatus($data)) {
            'active' => 'Actif',
            'unverified' => 'Non vérifié',
            'disabled' => 'Désactivé',
            default => 'Inconnu'
        };
    }
    
    private static function getStatusColor(array $data): string
    {
        return match(self::determineStatus($data)) {
            'active' => 'green',
            'unverified' => 'orange',
            'disabled' => 'red',
            default => 'gray'
        };
    }
    
    private static function canEdit(array $data): bool
    {
        return $data['enabled'] && $data['emailVerified'];
    }
    
    private static function canDelete(array $data): bool
    {
        return !$data['enabled'] || $data['lastLoginAt'] === null;
    }
    
    private static function canEnable(array $data): bool
    {
        return !$data['enabled'];
    }
    
    private static function canDisable(array $data): bool
    {
        return $data['enabled'];
    }
}
```

**Résultat** : Query Models optimisés pour la lecture.

### 4. **Créer les Query Handlers**

**Avec Gyroscops** : J'ai créé les Query Handlers :

```php
// ✅ Query Handler User API Gyroscops Cloud (Projet Gyroscops Cloud)
final class UserApiQueryHandler
{
    public function __construct(
        private Connection $connection,
        private CacheInterface $cache
    ) {}
    
    public function handleGetUserApi(GetUserApiQuery $query): ?UserApiQueryModel
    {
        $cacheKey = "user_api_{$query->getUserId()}";
        
        // Vérifier le cache
        if ($cached = $this->cache->get($cacheKey)) {
            return UserApiQueryModel::fromArray($cached);
        }
        
        // Requête à la projection
        $sql = 'SELECT id, username, email, first_name, last_name, enabled, email_verified, 
                       organization_id, roles, created_at, created_by, updated_at, updated_by, 
                       last_login_at, metadata
                FROM user_api_projections 
                WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$query->getUserId()]);
        
        $data = $stmt->fetch();
        if (!$data) {
            return null;
        }
        
        $user = UserApiQueryModel::fromArray($data);
        
        // Mettre en cache
        $this->cache->set($cacheKey, $user->toArray(), 3600);
        
        return $user;
    }
    
    public function handleGetUsersApiByOrganization(GetUsersApiByOrganizationQuery $query): array
    {
        $cacheKey = "users_api_org_{$query->getOrganizationId()}_{$query->getPage()}_{$query->getLimit()}";
        
        // Vérifier le cache
        if ($cached = $this->cache->get($cacheKey)) {
            return array_map([UserApiListQueryModel::class, 'fromArray'], $cached);
        }
        
        // Requête optimisée pour la liste
        $sql = 'SELECT id, username, email, first_name, last_name, enabled, email_verified, 
                       created_at, last_login_at, roles
                FROM user_api_list_projections 
                WHERE organization_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $query->getOrganizationId(),
            $query->getLimit(),
            ($query->getPage() - 1) * $query->getLimit()
        ]);
        
        $users = [];
        while ($data = $stmt->fetch()) {
            $users[] = UserApiListQueryModel::fromArray($data);
        }
        
        // Mettre en cache
        $this->cache->set($cacheKey, array_map(fn($u) => $u->toArray(), $users), 1800);
        
        return $users;
    }
    
    public function handleGetUserApiAnalytics(GetUserApiAnalyticsQuery $query): UserApiAnalyticsQueryModel
    {
        $cacheKey = "user_api_analytics_{$query->getOrganizationId()}_{$query->getStartDate()}_{$query->getEndDate()}";
        
        // Vérifier le cache
        if ($cached = $this->cache->get($cacheKey)) {
            return UserApiAnalyticsQueryModel::fromArray($cached);
        }
        
        // Requête analytique
        $sql = 'SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN enabled = 1 THEN 1 ELSE 0 END) as active_users,
                    SUM(CASE WHEN enabled = 0 THEN 1 ELSE 0 END) as inactive_users,
                    SUM(CASE WHEN email_verified = 1 THEN 1 ELSE 0 END) as verified_users,
                    SUM(CASE WHEN last_login_at IS NOT NULL THEN 1 ELSE 0 END) as users_with_login,
                    AVG(CASE WHEN last_login_at IS NOT NULL 
                        THEN TIMESTAMPDIFF(DAY, created_at, last_login_at) 
                        ELSE NULL END) as average_days_to_first_login
                FROM user_api_analytics_projections 
                WHERE organization_id = ? 
                AND created_at BETWEEN ? AND ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $query->getOrganizationId(),
            $query->getStartDate()->format('Y-m-d H:i:s'),
            $query->getEndDate()->format('Y-m-d H:i:s')
        ]);
        
        $data = $stmt->fetch();
        $analytics = UserApiAnalyticsQueryModel::fromArray($data);
        
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
// ✅ Projection User API Asynchrone Gyroscops Cloud (Projet Gyroscops Cloud)
final class UserApiProjectionHandler
{
    public function __construct(
        private Connection $connection,
        private CacheInterface $cache
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
        
        // Invalider le cache
        $this->invalidateCache($event);
    }
    
    private function handleUserApiCreated(UserApiCreated $event): void
    {
        // Projection principale
        $sql = 'INSERT INTO user_api_projections (id, username, email, first_name, last_name, enabled, email_verified, organization_id, roles, created_at, created_by, metadata) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getUserId(),
            $event->getUsername(),
            $event->getEmail(),
            $event->getFirstName(),
            $event->getLastName(),
            true,
            false,
            $event->getOrganizationId(),
            json_encode($event->getRoles()),
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getCreatedBy(),
            json_encode($event->getMetadata() ?? [])
        ]);
        
        // Projection de liste
        $sql = 'INSERT INTO user_api_list_projections (id, username, email, first_name, last_name, enabled, email_verified, organization_id, roles, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getUserId(),
            $event->getUsername(),
            $event->getEmail(),
            $event->getFirstName(),
            $event->getLastName(),
            true,
            false,
            $event->getOrganizationId(),
            json_encode($event->getRoles()),
            $event->getOccurredAt()->format('Y-m-d H:i:s')
        ]);
        
        // Projection analytique
        $sql = 'INSERT INTO user_api_analytics_projections (id, username, email, enabled, email_verified, organization_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getUserId(),
            $event->getUsername(),
            $event->getEmail(),
            true,
            false,
            $event->getOrganizationId(),
            $event->getOccurredAt()->format('Y-m-d H:i:s')
        ]);
    }
    
    private function handleUserApiUpdated(UserApiUpdated $event): void
    {
        // Projection principale
        $sql = 'UPDATE user_api_projections SET username = ?, email = ?, first_name = ?, last_name = ?, updated_at = ?, updated_by = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getUsername(),
            $event->getEmail(),
            $event->getFirstName(),
            $event->getLastName(),
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getUpdatedBy(),
            $event->getUserId()
        ]);
        
        // Projection de liste
        $sql = 'UPDATE user_api_list_projections SET username = ?, email = ?, first_name = ?, last_name = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getUsername(),
            $event->getEmail(),
            $event->getFirstName(),
            $event->getLastName(),
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
        
        $sql = 'UPDATE user_api_list_projections SET enabled = 1 WHERE id = ?';
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$event->getUserId()]);
        
        $sql = 'UPDATE user_api_analytics_projections SET enabled = 1 WHERE id = ?';
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$event->getUserId()]);
    }
    
    private function handleUserApiDisabled(UserApiDisabled $event): void
    {
        $sql = 'UPDATE user_api_projections SET enabled = 0, updated_at = ? WHERE id = ?';
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getUserId()
        ]);
        
        $sql = 'UPDATE user_api_list_projections SET enabled = 0 WHERE id = ?';
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$event->getUserId()]);
        
        $sql = 'UPDATE user_api_analytics_projections SET enabled = 0 WHERE id = ?';
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$event->getUserId()]);
    }
    
    private function handleUserApiDeleted(UserApiDeleted $event): void
    {
        $sql = 'UPDATE user_api_projections SET deleted = 1, deleted_at = ? WHERE id = ?';
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getUserId()
        ]);
        
        $this->connection->executeStatement('DELETE FROM user_api_list_projections WHERE id = ?', [$event->getUserId()]);
        $this->connection->executeStatement('DELETE FROM user_api_analytics_projections WHERE id = ?', [$event->getUserId()]);
    }
    
    private function invalidateCache(DomainEvent $event): void
    {
        // Invalider les caches liés à cet événement
        $this->cache->delete("user_api_{$event->getUserId()}");
        $this->cache->delete("users_api_org_{$event->getOrganizationId()}_*");
        $this->cache->delete("user_api_analytics_{$event->getOrganizationId()}_*");
    }
}
```

**Résultat** : Projections asynchrones avec invalidation de cache.

## Les Avantages du Stockage API Event Sourcing + CQS

### 1. **Performance Optimisée**

**Avec Gyroscops** : Le stockage API Event Sourcing + CQS m'a donné des performances optimisées :
- Lectures via projections optimisées
- Cache intelligent
- Requêtes spécialisées
- Performance prévisible

**Résultat** : Performances de lecture excellentes.

### 2. **Audit Trail Complet**

**Avec Gyroscops** : Le stockage API Event Sourcing + CQS m'a conservé l'audit trail :
- Historique complet des changements
- Reconstruction possible
- Debugging facilité
- Traçabilité totale

**Résultat** : Audit trail parfait conservé.

### 3. **Séparation des Responsabilités**

**Avec Gyroscops** : Le stockage API Event Sourcing + CQS m'a séparé les responsabilités :
- Command Side pour l'écriture
- Query Side pour la lecture
- Optimisations indépendantes
- Maintenance facilitée

**Résultat** : Architecture claire et maintenable.

### 4. **Évolutivité**

**Avec Gyroscops** : Le stockage API Event Sourcing + CQS m'a permis d'évoluer :
- Nouvelles projections sans impact
- Optimisations ciblées
- Évolution indépendante
- Flexibilité maximale

**Résultat** : Évolutivité excellente.

## Les Inconvénients du Stockage API Event Sourcing + CQS

### 1. **Complexité Technique**

**Avec Gyroscops** : Le stockage API Event Sourcing + CQS a ajouté de la complexité :
- Courbe d'apprentissage importante
- Plus de composants à maintenir
- Concepts avancés
- Debugging plus complexe

**Résultat** : Complexité technique élevée.

### 2. **Cohérence Éventuelle**

**Avec Gyroscops** : Le stockage API Event Sourcing + CQS peut avoir des problèmes de cohérence :
- Projections asynchrones
- Délai de synchronisation
- Incohérence temporaire
- Gestion des erreurs complexe

**Résultat** : Cohérence éventuelle à gérer.

### 3. **Gestion du Cache**

**Avec Gyroscops** : Le stockage API Event Sourcing + CQS nécessite une gestion du cache :
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

### 3. **Pas de Gestion d'Erreurs API**

**❌ Mauvais** : Pas de gestion des erreurs des APIs externes
**✅ Bon** : Gestion complète des erreurs avec retry et fallback

**Pourquoi c'est essentiel ?** Les APIs externes peuvent échouer.

## 🏗️ Implémentation Concrète dans le Projet Gyroscops Cloud

### Stockage API Event Sourcing + CQS Appliqué à Gyroscops Cloud

Le Gyroscops Cloud applique concrètement les principes du stockage API Event Sourcing + CQS à travers son architecture et ses ADR (Architecture Decision Records). Voici comment :

#### Configuration API Event Sourcing + CQS Gyroscops Cloud

```php
// ✅ Configuration API Event Sourcing + CQS Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveApiEventSourcingCQSConfiguration
{
    public function configureApiEventSourcingCQS(ContainerBuilder $container): void
    {
        // Configuration de l'Event Store
        $container->register(ApiEventStore::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration des Command Handlers
        $container->register(UserApiCommandHandler::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration des Query Handlers
        $container->register(UserApiQueryHandler::class)
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
- **HIVE011** : Command Query Separation - Séparation des commandes et requêtes
- **HIVE015** : API Repositories - Repositories d'API
- **HIVE025** : Authorization System - Système d'autorisation
- **HIVE026** : Keycloak Resource and Scope Management - Gestion des ressources Keycloak
- **HIVE038** : Robust Error Handling Patterns - Patterns de gestion d'erreurs

{{< chapter-nav >}}
  {{{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux comprendre le stockage API Event Sourcing + CQRS" 
    subtitle="Vous voulez voir une approche Event Sourcing avec séparation complète des modèles" 
    criteria="Équipe très expérimentée,Besoin de performance maximale,Event Sourcing déjà en place,Complexité élevée acceptable" 
    time="35-50 minutes" 
    chapter="26" 
    chapter-title="Stockage API - Event Sourcing + CQRS" 
    chapter-url="/chapitres/stockage/api/chapitre-51-stockage-api-event-sourcing-cqrs/" 
  >}}}}
  
  {{{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux comprendre le stockage ElasticSearch" 
    subtitle="Vous voulez voir comment optimiser la recherche" 
    criteria="Équipe expérimentée,Besoin de recherche avancée,Analytics importantes,Performance de recherche critique" 
    time="30-40 minutes" 
    chapter="27" 
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
    chapter="28" 
    chapter-title="Stockage MongoDB - Approche Classique" 
    chapter-url="/chapitres/stockage/chapitre-51-stockage-mongodb-classique/" 
  >}}}}
  
{{< /chapter-nav >}}