---
title: "Chapitre 61 : Stockage API - Approche CQS"
description: "Maîtriser le stockage via APIs externes avec Command Query Separation pour des performances optimisées"
date: 2024-12-19
draft: true
type: "docs"
weight: 61
---

## 🎯 Objectif de ce Chapitre

### Mon Problème : Comment Optimiser les Performances des APIs Externes ?

**Voici ce qui s'est passé avec Gyroscops** : J'avais implémenté le stockage API classique, mais les performances de lecture étaient dégradées. Les APIs externes étaient lentes et j'avais besoin d'optimiser les lectures tout en gardant la simplicité pour l'écriture.

**Mais attendez...** Comment séparer les commandes et les requêtes ? Comment optimiser les projections ? Comment gérer la cohérence ? Comment intégrer avec API Platform ?

**Soudain, je réalisais que CQS + API était parfait !** Il me fallait une méthode pour optimiser les performances tout en gardant la simplicité.

### Stockage API CQS : Mon Guide Pratique

Le stockage API CQS m'a permis de :
- **Optimiser** les performances de lecture
- **Conserver** la simplicité d'écriture
- **Séparer** les responsabilités
- **Équilibrer** complexité et performance

## Qu'est-ce que le Stockage API CQS ?

### Le Concept Fondamental

Le stockage API CQS combine l'utilisation d'APIs externes avec la Command Query Separation pour optimiser les lectures. **L'idée** : Écriture via APIs externes, lecture via projections optimisées.

**Avec Gyroscops, voici comment j'ai structuré le stockage API CQS** :

### Les 4 Piliers du Stockage API CQS

#### 1. **Command Side** - Gestion des écritures

**Voici comment j'ai implémenté le Command Side avec Gyroscops** :

**Composants** :
- Clients API externes
- Command Handlers
- Validation des données
- Gestion des erreurs

**Exemples** :
- `CreateUserCommand`
- `UpdateUserCommand`
- `DeleteUserCommand`

#### 2. **Query Side** - Optimisation des lectures

**Voici comment j'ai implémenté le Query Side avec Gyroscops** :

**Composants** :
- Projections optimisées
- Query Models
- Query Handlers
- Cache intelligent

**Exemples** :
- `UserQueryModel`
- `UserListQueryModel`
- `UserAnalyticsQueryModel`

#### 3. **APIs Externes** - Source de vérité

**Voici comment j'ai implémenté les APIs externes avec Gyroscops** :

**Fonctionnalités** :
- Clients HTTP spécialisés
- Gestion des authentifications
- Retry et circuit breaker
- Gestion des erreurs

**Avantages** :
- Données à jour
- Intégrité des données
- Services spécialisés
- Pas de duplication

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

## Comment Implémenter le Stockage API CQS

### 1. **Créer les Command Handlers**

**Avec Gyroscops** : J'ai créé les Command Handlers :

```php
// ✅ Command Handler User API Hive (Projet Hive)
final class UserCommandHandler
{
    public function __construct(
        private KeycloakClient $keycloakClient,
        private EventBusInterface $eventBus,
        private UserMapper $userMapper
    ) {}
    
    public function handleCreateUser(CreateUserCommand $command): void
    {
        // Valider les données
        $this->validateUserData($command);
        
        // Préparer les données pour Keycloak
        $userData = $this->userMapper->toKeycloakArray($command);
        
        // Créer l'utilisateur dans Keycloak
        $result = $this->keycloakClient->createUser($userData);
        
        // Créer l'événement
        $event = new UserCreated(
            $result['id'],
            $command->getUsername(),
            $command->getEmail(),
            $command->getOrganizationId(),
            $command->getCreatedBy()
        );
        
        // Publier l'événement
        $this->eventBus->publish($event);
    }
    
    public function handleUpdateUser(UpdateUserCommand $command): void
    {
        // Valider les données
        $this->validateUserData($command);
        
        // Préparer les données pour Keycloak
        $userData = $this->userMapper->toKeycloakArray($command);
        
        // Mettre à jour l'utilisateur dans Keycloak
        $this->keycloakClient->updateUser($command->getUserId(), $userData);
        
        // Créer l'événement
        $event = new UserUpdated(
            $command->getUserId(),
            $command->getUsername(),
            $command->getEmail(),
            $command->getUpdatedBy()
        );
        
        // Publier l'événement
        $this->eventBus->publish($event);
    }
    
    public function handleDeleteUser(DeleteUserCommand $command): void
    {
        // Supprimer l'utilisateur dans Keycloak
        $this->keycloakClient->deleteUser($command->getUserId());
        
        // Créer l'événement
        $event = new UserDeleted(
            $command->getUserId(),
            $command->getDeletedBy()
        );
        
        // Publier l'événement
        $this->eventBus->publish($event);
    }
    
    private function validateUserData(UserCommandInterface $command): void
    {
        if (empty($command->getUsername())) {
            throw new ValidationException('Username is required');
        }
        
        if (empty($command->getEmail()) || !filter_var($command->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Valid email is required');
        }
        
        if (empty($command->getOrganizationId())) {
            throw new ValidationException('Organization ID is required');
        }
    }
}
```

**Résultat** : Command Handlers pour l'écriture via APIs.

### 2. **Créer les Query Models**

**Avec Gyroscops** : J'ai créé les Query Models :

```php
// ✅ Query Model User Hive (Projet Hive)
final class UserQueryModel
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
        public readonly ?\DateTimeImmutable $updatedAt = null,
        public readonly ?string $lastLoginAt = null,
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
            updatedAt: $data['updatedAt'] ? new \DateTimeImmutable($data['updatedAt']) : null,
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
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'lastLoginAt' => $this->lastLoginAt?->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata
        ];
    }
}

// ✅ Query Model User List Hive (Projet Hive)
final class UserListQueryModel
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
        public readonly array $roles = []
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            username: $data['username'],
            email: $data['email'],
            fullName: trim(($data['firstName'] ?? '') . ' ' . ($data['lastName'] ?? '')),
            status: $data['enabled'] ? 'active' : 'inactive',
            statusLabel: $data['enabled'] ? 'Actif' : 'Inactif',
            statusColor: $data['enabled'] ? 'green' : 'red',
            createdAt: new \DateTimeImmutable($data['createdAt']),
            lastLoginAt: $data['lastLoginAt'] ? new \DateTimeImmutable($data['lastLoginAt']) : null,
            roles: $data['roles'] ?? []
        );
    }
}
```

**Résultat** : Query Models optimisés pour la lecture.

### 3. **Créer les Query Handlers**

**Avec Gyroscops** : J'ai créé les Query Handlers :

```php
// ✅ Query Handler User Hive (Projet Hive)
final class UserQueryHandler
{
    public function __construct(
        private Connection $connection,
        private CacheInterface $cache
    ) {}
    
    public function handleGetUser(GetUserQuery $query): ?UserQueryModel
    {
        $cacheKey = "user_{$query->getUserId()}";
        
        // Vérifier le cache
        if ($cached = $this->cache->get($cacheKey)) {
            return UserQueryModel::fromArray($cached);
        }
        
        // Requête à la projection
        $sql = 'SELECT id, username, email, first_name, last_name, enabled, email_verified, 
                       organization_id, roles, created_at, updated_at, last_login_at, metadata
                FROM user_projections 
                WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$query->getUserId()]);
        
        $data = $stmt->fetch();
        if (!$data) {
            return null;
        }
        
        $user = UserQueryModel::fromArray($data);
        
        // Mettre en cache
        $this->cache->set($cacheKey, $user->toArray(), 3600);
        
        return $user;
    }
    
    public function handleGetUsersByOrganization(GetUsersByOrganizationQuery $query): array
    {
        $cacheKey = "users_org_{$query->getOrganizationId()}_{$query->getPage()}_{$query->getLimit()}";
        
        // Vérifier le cache
        if ($cached = $this->cache->get($cacheKey)) {
            return array_map([UserListQueryModel::class, 'fromArray'], $cached);
        }
        
        // Requête optimisée pour la liste
        $sql = 'SELECT id, username, email, first_name, last_name, enabled, created_at, last_login_at, roles
                FROM user_list_projections 
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
            $users[] = UserListQueryModel::fromArray($data);
        }
        
        // Mettre en cache
        $this->cache->set($cacheKey, array_map(fn($u) => $u->toArray(), $users), 1800);
        
        return $users;
    }
    
    public function handleGetUserAnalytics(GetUserAnalyticsQuery $query): UserAnalyticsQueryModel
    {
        $cacheKey = "user_analytics_{$query->getOrganizationId()}_{$query->getStartDate()}_{$query->getEndDate()}";
        
        // Vérifier le cache
        if ($cached = $this->cache->get($cacheKey)) {
            return UserAnalyticsQueryModel::fromArray($cached);
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
                FROM user_analytics_projections 
                WHERE organization_id = ? 
                AND created_at BETWEEN ? AND ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $query->getOrganizationId(),
            $query->getStartDate()->format('Y-m-d H:i:s'),
            $query->getEndDate()->format('Y-m-d H:i:s')
        ]);
        
        $data = $stmt->fetch();
        $analytics = UserAnalyticsQueryModel::fromArray($data);
        
        // Mettre en cache
        $this->cache->set($cacheKey, $analytics->toArray(), 3600);
        
        return $analytics;
    }
}
```

**Résultat** : Query Handlers optimisés avec cache.

### 4. **Créer les Projections Asynchrones**

**Avec Gyroscops** : J'ai créé les projections asynchrones :

```php
// ✅ Projection User Asynchrone Hive (Projet Hive)
final class UserProjectionHandler
{
    public function __construct(
        private Connection $connection,
        private CacheInterface $cache
    ) {}
    
    public function handle(DomainEvent $event): void
    {
        switch ($event::class) {
            case UserCreated::class:
                $this->handleUserCreated($event);
                break;
            case UserUpdated::class:
                $this->handleUserUpdated($event);
                break;
            case UserDeleted::class:
                $this->handleUserDeleted($event);
                break;
        }
        
        // Invalider le cache
        $this->invalidateCache($event);
    }
    
    private function handleUserCreated(UserCreated $event): void
    {
        // Projection principale
        $sql = 'INSERT INTO user_projections (id, username, email, first_name, last_name, enabled, email_verified, organization_id, roles, created_at, metadata) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getUserId(),
            $event->getUsername(),
            $event->getEmail(),
            $event->getFirstName() ?? '',
            $event->getLastName() ?? '',
            true,
            false,
            $event->getOrganizationId(),
            json_encode($event->getRoles() ?? []),
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            json_encode($event->getMetadata() ?? [])
        ]);
        
        // Projection de liste
        $sql = 'INSERT INTO user_list_projections (id, username, email, first_name, last_name, enabled, created_at, roles) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getUserId(),
            $event->getUsername(),
            $event->getEmail(),
            $event->getFirstName() ?? '',
            $event->getLastName() ?? '',
            true,
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            json_encode($event->getRoles() ?? [])
        ]);
        
        // Projection analytique
        $sql = 'INSERT INTO user_analytics_projections (id, username, email, enabled, email_verified, organization_id, created_at) 
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
    
    private function handleUserUpdated(UserUpdated $event): void
    {
        // Projection principale
        $sql = 'UPDATE user_projections SET username = ?, email = ?, first_name = ?, last_name = ?, updated_at = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getUsername(),
            $event->getEmail(),
            $event->getFirstName() ?? '',
            $event->getLastName() ?? '',
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getUserId()
        ]);
        
        // Projection de liste
        $sql = 'UPDATE user_list_projections SET username = ?, email = ?, first_name = ?, last_name = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getUsername(),
            $event->getEmail(),
            $event->getFirstName() ?? '',
            $event->getLastName() ?? '',
            $event->getUserId()
        ]);
    }
    
    private function handleUserDeleted(UserDeleted $event): void
    {
        // Supprimer de toutes les projections
        $this->connection->executeStatement('DELETE FROM user_projections WHERE id = ?', [$event->getUserId()]);
        $this->connection->executeStatement('DELETE FROM user_list_projections WHERE id = ?', [$event->getUserId()]);
        $this->connection->executeStatement('DELETE FROM user_analytics_projections WHERE id = ?', [$event->getUserId()]);
    }
    
    private function invalidateCache(DomainEvent $event): void
    {
        // Invalider les caches liés à cet événement
        $this->cache->delete("user_{$event->getUserId()}");
        $this->cache->delete("users_org_{$event->getOrganizationId()}_*");
        $this->cache->delete("user_analytics_{$event->getOrganizationId()}_*");
    }
}
```

**Résultat** : Projections asynchrones avec invalidation de cache.

## Les Avantages du Stockage API CQS

### 1. **Performance Optimisée**

**Avec Gyroscops** : Le stockage API CQS m'a donné des performances optimisées :
- Lectures via projections optimisées
- Cache intelligent
- Requêtes spécialisées
- Performance prévisible

**Résultat** : Performances de lecture excellentes.

### 2. **Simplicité d'Écriture**

**Avec Gyroscops** : Le stockage API CQS m'a conservé la simplicité d'écriture :
- APIs externes directement utilisées
- Pas de complexité inutile
- Développement rapide
- Maintenance facile

**Résultat** : Écriture simple et efficace.

### 3. **Séparation des Responsabilités**

**Avec Gyroscops** : Le stockage API CQS m'a séparé les responsabilités :
- Command Side pour l'écriture
- Query Side pour la lecture
- Optimisations indépendantes
- Maintenance facilitée

**Résultat** : Architecture claire et maintenable.

### 4. **Évolutivité**

**Avec Gyroscops** : Le stockage API CQS m'a permis d'évoluer :
- Nouvelles projections sans impact
- Optimisations ciblées
- Évolution indépendante
- Flexibilité maximale

**Résultat** : Évolutivité excellente.

## Les Inconvénients du Stockage API CQS

### 1. **Complexité Technique**

**Avec Gyroscops** : Le stockage API CQS a ajouté de la complexité :
- Courbe d'apprentissage importante
- Plus de composants à maintenir
- Concepts avancés
- Debugging plus complexe

**Résultat** : Complexité technique élevée.

### 2. **Cohérence Éventuelle**

**Avec Gyroscops** : Le stockage API CQS peut avoir des problèmes de cohérence :
- Projections asynchrones
- Délai de synchronisation
- Incohérence temporaire
- Gestion des erreurs complexe

**Résultat** : Cohérence éventuelle à gérer.

### 3. **Gestion du Cache**

**Avec Gyroscops** : Le stockage API CQS nécessite une gestion du cache :
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

## 🏗️ Implémentation Concrète dans le Projet Hive

### Stockage API CQS Appliqué à Hive

Le projet Hive applique concrètement les principes du stockage API CQS à travers son architecture et ses ADR (Architecture Decision Records). Voici comment :

#### Configuration API CQS Hive

```php
// ✅ Configuration API CQS Hive (Projet Hive)
final class HiveApiCQSConfiguration
{
    public function configureApiCQS(ContainerBuilder $container): void
    {
        // Configuration des clients API
        $container->register(KeycloakClient::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration des Command Handlers
        $container->register(UserCommandHandler::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration des Query Handlers
        $container->register(UserQueryHandler::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration des projections
        $container->register(UserProjectionHandler::class)
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

### Références aux ADR du Projet Hive

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du projet Hive :
- **HIVE011** : Command Query Separation - Séparation des commandes et requêtes
- **HIVE015** : API Repositories - Repositories d'API
- **HIVE025** : Authorization System - Système d'autorisation
- **HIVE026** : Keycloak Resource and Scope Management - Gestion des ressources Keycloak
- **HIVE038** : Robust Error Handling Patterns - Patterns de gestion d'erreurs

{{< chapter-nav >}}
  {{{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux comprendre le stockage API CQRS" 
    subtitle="Vous voulez voir une approche avec séparation complète des modèles" 
    criteria="Équipe très expérimentée,Besoin de performance maximale,Complexité élevée acceptable,Scalabilité critique" 
    time="30-45 minutes" 
    chapter="61" 
    chapter-title="Stockage API - Approche CQRS" 
    chapter-url="/chapitres/stockage/api/chapitre-51-stockage-api-cqrs/" 
  >}}}}
  
  {{{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux comprendre le stockage ElasticSearch" 
    subtitle="Vous voulez voir comment optimiser la recherche" 
    criteria="Équipe expérimentée,Besoin de recherche avancée,Analytics importantes,Performance de recherche critique" 
    time="30-40 minutes" 
    chapter="62" 
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
    chapter="63" 
    chapter-title="Stockage MongoDB - Approche Classique" 
    chapter-url="/chapitres/stockage/chapitre-51-stockage-mongodb-classique/" 
  >}}}}
  
{{< /chapter-nav >}}