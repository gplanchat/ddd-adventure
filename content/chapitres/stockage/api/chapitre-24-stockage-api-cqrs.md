---
title: "Chapitre 62 : Stockage API - Approche CQRS"
description: "Maîtriser le stockage via APIs externes avec CQRS pour des performances et une flexibilité maximales"
date: 2024-12-19
draft: true
type: "docs"
weight: 62
---

## 🎯 Objectif de ce Chapitre

### Mon Problème : Comment Maximiser les Performances et la Flexibilité des APIs Externes ?

**Voici ce qui s'est passé avec Gyroscops** : J'avais implémenté le stockage API CQS, mais j'avais besoin de modèles de lecture complètement différents des modèles d'écriture. Les vues métier évoluaient constamment et j'avais besoin de flexibilité maximale pour les APIs externes.

**Mais attendez...** Comment séparer complètement les modèles ? Comment optimiser chaque côté indépendamment ? Comment gérer la cohérence ? Comment intégrer avec API Platform ?

**Soudain, je réalisais que CQRS + API était parfait !** Il me fallait une méthode pour maximiser les performances et la flexibilité des intégrations API.

### Stockage API CQRS : Mon Guide Pratique

Le stockage API CQRS m'a permis de :
- **Maximiser** les performances de lecture
- **Optimiser** les modèles par usage
- **Évoluer** indépendamment chaque côté
- **Flexibiliser** au maximum l'architecture API

## Qu'est-ce que le Stockage API CQRS ?

### Le Concept Fondamental

Le stockage API CQRS combine l'utilisation d'APIs externes avec la séparation complète des modèles Command et Query. **L'idée** : Modèles d'écriture via APIs externes, modèles de lecture complètement séparés et optimisés.

**Avec Gyroscops, voici comment j'ai structuré le stockage API CQRS** :

### Les 4 Piliers du Stockage API CQRS

#### 1. **Command Side** - Modèles d'écriture API

**Voici comment j'ai implémenté le Command Side avec Gyroscops** :

**Composants** :
- Command Models spécialisés
- Command Handlers
- Clients API externes
- Event Bus

**Caractéristiques** :
- Modèles optimisés pour l'écriture
- Logique métier complexe
- Validation des règles
- Gestion des transactions

#### 2. **Query Side** - Modèles de lecture optimisés

**Voici comment j'ai implémenté le Query Side avec Gyroscops** :

**Composants** :
- Query Models spécialisés
- Query Handlers
- Projections optimisées
- Cache intelligent
- Requêtes spécialisées

**Caractéristiques** :
- Modèles optimisés pour la lecture
- Vues métier spécialisées
- Performance maximale
- Flexibilité totale

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
- Projections spécialisées (par contexte métier)

**Synchronisation** :
- Asynchrone via Event Bus
- Cohérence éventuelle
- Gestion des erreurs
- Reprocessing possible

## Comment Implémenter le Stockage API CQRS

### 1. **Créer les Command Models**

**Avec Gyroscops** : J'ai créé les Command Models :

```php
// ✅ Command Model User API Gyroscops Cloud (Projet Gyroscops Cloud)
final class UserCommandModel
{
    public function __construct(
        private string $id,
        private string $username,
        private string $email,
        private string $firstName,
        private string $lastName,
        private string $organizationId,
        private array $roles,
        private bool $enabled = true,
        private bool $emailVerified = false,
        private int $version = 0
    ) {}
    
    public function create(): void
    {
        $this->validateUserData();
        $this->checkOrganizationExists();
        $this->validateEmailUniqueness();
        $this->prepareUserCreation();
    }
    
    public function update(array $data): void
    {
        $this->validateUpdateData($data);
        $this->checkUpdatePermissions();
        $this->validateEmailUniqueness($data['email'] ?? $this->email);
        $this->prepareUserUpdate($data);
    }
    
    public function enable(): void
    {
        if ($this->enabled) {
            throw new InvalidOperationException('User is already enabled');
        }
        
        $this->validateEnablePermissions();
        $this->prepareUserEnable();
    }
    
    public function disable(): void
    {
        if (!$this->enabled) {
            throw new InvalidOperationException('User is already disabled');
        }
        
        $this->validateDisablePermissions();
        $this->prepareUserDisable();
    }
    
    public function verifyEmail(): void
    {
        if ($this->emailVerified) {
            throw new InvalidOperationException('Email is already verified');
        }
        
        $this->validateEmailVerification();
        $this->prepareEmailVerification();
    }
    
    private function validateUserData(): void
    {
        if (empty($this->username)) {
            throw new ValidationException('Username is required');
        }
        
        if (empty($this->email) || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Valid email is required');
        }
        
        if (empty($this->organizationId)) {
            throw new ValidationException('Organization ID is required');
        }
    }
    
    private function checkOrganizationExists(): void
    {
        // Vérifier que l'organisation existe
        // Logique métier complexe
    }
    
    private function validateEmailUniqueness(?string $email = null): void
    {
        $emailToCheck = $email ?? $this->email;
        // Vérifier l'unicité de l'email
        // Logique métier complexe
    }
    
    private function prepareUserCreation(): void
    {
        // Préparer la création de l'utilisateur
        // Logique métier complexe
    }
    
    // Autres méthodes privées...
    
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
    public function getVersion(): int { return $this->version; }
}
```

**Résultat** : Command Model optimisé pour l'écriture via APIs.

### 2. **Créer les Query Models**

**Avec Gyroscops** : J'ai créé les Query Models :

```php
// ✅ Query Model User List Gyroscops Cloud (Projet Gyroscops Cloud)
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
        public readonly string $organizationName,
        public readonly array $roles,
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?\DateTimeImmutable $lastLoginAt = null,
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
            organizationName: $data['organizationName'] ?? '',
            roles: $data['roles'] ?? [],
            createdAt: new \DateTimeImmutable($data['createdAt']),
            lastLoginAt: $data['lastLoginAt'] ? new \DateTimeImmutable($data['lastLoginAt']) : null,
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

// ✅ Query Model User Details Gyroscops Cloud (Projet Gyroscops Cloud)
final class UserDetailsQueryModel
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
        public readonly string $organizationName,
        public readonly array $roles,
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?\DateTimeImmutable $updatedAt = null,
        public readonly ?\DateTimeImmutable $lastLoginAt = null,
        public readonly array $permissions = [],
        public readonly array $auditTrail = [],
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
            organizationName: $data['organizationName'] ?? '',
            roles: $data['roles'] ?? [],
            createdAt: new \DateTimeImmutable($data['createdAt']),
            updatedAt: $data['updatedAt'] ? new \DateTimeImmutable($data['updatedAt']) : null,
            lastLoginAt: $data['lastLoginAt'] ? new \DateTimeImmutable($data['lastLoginAt']) : null,
            permissions: $data['permissions'] ?? [],
            auditTrail: $data['auditTrail'] ?? [],
            metadata: $data['metadata'] ?? []
        );
    }
}

// ✅ Query Model User Analytics Gyroscops Cloud (Projet Gyroscops Cloud)
final class UserAnalyticsQueryModel
{
    public function __construct(
        public readonly int $totalUsers,
        public readonly int $activeUsers,
        public readonly int $inactiveUsers,
        public readonly int $verifiedUsers,
        public readonly int $unverifiedUsers,
        public readonly int $usersWithLogin,
        public readonly string $activationRate,
        public readonly string $verificationRate,
        public readonly string $loginRate,
        public readonly array $dailyStats = [],
        public readonly array $roleDistribution = [],
        public readonly array $organizationStats = []
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            totalUsers: $data['total_users'],
            activeUsers: $data['active_users'],
            inactiveUsers: $data['inactive_users'],
            verifiedUsers: $data['verified_users'],
            unverifiedUsers: $data['unverified_users'],
            usersWithLogin: $data['users_with_login'],
            activationRate: $data['activation_rate'],
            verificationRate: $data['verification_rate'],
            loginRate: $data['login_rate'],
            dailyStats: json_decode($data['daily_stats'] ?? '[]', true),
            roleDistribution: json_decode($data['role_distribution'] ?? '[]', true),
            organizationStats: json_decode($data['organization_stats'] ?? '[]', true)
        );
    }
}
```

**Résultat** : Query Models spécialisés pour chaque usage.

### 3. **Créer les Command Handlers**

**Avec Gyroscops** : J'ai créé les Command Handlers :

```php
// ✅ Command Handler User API Gyroscops Cloud (Projet Gyroscops Cloud)
final class UserCommandHandler
{
    public function __construct(
        private KeycloakClient $keycloakClient,
        private EventBusInterface $eventBus,
        private UserCommandModelFactory $commandModelFactory
    ) {}
    
    public function handleCreateUser(CreateUserCommand $command): void
    {
        // Créer le Command Model
        $commandModel = $this->commandModelFactory->create(
            $command->getUserId(),
            $command->getUsername(),
            $command->getEmail(),
            $command->getFirstName(),
            $command->getLastName(),
            $command->getOrganizationId(),
            $command->getRoles()
        );
        
        // Exécuter la logique métier
        $commandModel->create();
        
        // Préparer les données pour Keycloak
        $userData = $this->prepareKeycloakData($commandModel);
        
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
        // Créer le Command Model
        $commandModel = $this->commandModelFactory->fromExisting(
            $command->getUserId(),
            $command->getVersion()
        );
        
        // Exécuter la logique métier
        $commandModel->update($command->getData());
        
        // Préparer les données pour Keycloak
        $userData = $this->prepareKeycloakData($commandModel);
        
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
    
    public function handleEnableUser(EnableUserCommand $command): void
    {
        // Créer le Command Model
        $commandModel = $this->commandModelFactory->fromExisting(
            $command->getUserId(),
            $command->getVersion()
        );
        
        // Exécuter la logique métier
        $commandModel->enable();
        
        // Activer l'utilisateur dans Keycloak
        $this->keycloakClient->enableUser($command->getUserId());
        
        // Créer l'événement
        $event = new UserEnabled(
            $command->getUserId(),
            $command->getEnabledBy()
        );
        
        // Publier l'événement
        $this->eventBus->publish($event);
    }
    
    public function handleDisableUser(DisableUserCommand $command): void
    {
        // Créer le Command Model
        $commandModel = $this->commandModelFactory->fromExisting(
            $command->getUserId(),
            $command->getVersion()
        );
        
        // Exécuter la logique métier
        $commandModel->disable();
        
        // Désactiver l'utilisateur dans Keycloak
        $this->keycloakClient->disableUser($command->getUserId());
        
        // Créer l'événement
        $event = new UserDisabled(
            $command->getUserId(),
            $command->getDisabledBy()
        );
        
        // Publier l'événement
        $this->eventBus->publish($event);
    }
    
    private function prepareKeycloakData(UserCommandModel $commandModel): array
    {
        return [
            'id' => $commandModel->getId(),
            'username' => $commandModel->getUsername(),
            'email' => $commandModel->getEmail(),
            'firstName' => $commandModel->getFirstName(),
            'lastName' => $commandModel->getLastName(),
            'enabled' => $commandModel->isEnabled(),
            'emailVerified' => $commandModel->isEmailVerified(),
            'attributes' => [
                'organizationId' => [$commandModel->getOrganizationId()],
                'roles' => $commandModel->getRoles()
            ]
        ];
    }
}
```

**Résultat** : Command Handlers pour l'écriture via APIs.

### 4. **Créer les Query Handlers**

**Avec Gyroscops** : J'ai créé les Query Handlers :

```php
// ✅ Query Handler User List Gyroscops Cloud (Projet Gyroscops Cloud)
final class UserListQueryHandler
{
    public function __construct(
        private Connection $connection,
        private CacheInterface $cache
    ) {}
    
    public function handleGetUserList(GetUserListQuery $query): array
    {
        $cacheKey = "user_list_{$query->getOrganizationId()}_{$query->getPage()}_{$query->getLimit()}_{$query->getStatus()}_{$query->getSortBy()}";
        
        // Vérifier le cache
        if ($cached = $this->cache->get($cacheKey)) {
            return array_map([UserListQueryModel::class, 'fromArray'], $cached);
        }
        
        // Requête optimisée pour la liste
        $sql = 'SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.enabled, u.email_verified, 
                       u.created_at, u.last_login_at, u.roles,
                       o.name as organization_name,
                       CASE 
                           WHEN u.enabled = 0 THEN "disabled"
                           WHEN u.email_verified = 0 THEN "unverified"
                           ELSE "active"
                       END as status,
                       CASE 
                           WHEN u.enabled = 0 THEN "Désactivé"
                           WHEN u.email_verified = 0 THEN "Non vérifié"
                           ELSE "Actif"
                       END as status_label,
                       CASE 
                           WHEN u.enabled = 0 THEN "red"
                           WHEN u.email_verified = 0 THEN "orange"
                           ELSE "green"
                       END as status_color,
                       CASE WHEN u.enabled = 1 AND u.email_verified = 1 THEN 1 ELSE 0 END as can_edit,
                       CASE WHEN u.enabled = 0 OR u.last_login_at IS NULL THEN 1 ELSE 0 END as can_delete,
                       CASE WHEN u.enabled = 0 THEN 1 ELSE 0 END as can_enable,
                       CASE WHEN u.enabled = 1 THEN 1 ELSE 0 END as can_disable
                FROM user_list_projections u
                LEFT JOIN organization_projections o ON o.id = u.organization_id
                WHERE u.organization_id = ?';
        
        $params = [$query->getOrganizationId()];
        
        if ($query->getStatus()) {
            $sql .= ' AND u.status = ?';
            $params[] = $query->getStatus();
        }
        
        $sql .= ' ORDER BY u.' . $query->getSortBy() . ' ' . $query->getSortDirection();
        $sql .= ' LIMIT ? OFFSET ?';
        $params[] = $query->getLimit();
        $params[] = ($query->getPage() - 1) * $query->getLimit();
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        
        $users = [];
        while ($data = $stmt->fetch()) {
            $users[] = UserListQueryModel::fromArray($data);
        }
        
        // Mettre en cache
        $this->cache->set($cacheKey, array_map(fn($u) => $u->toArray(), $users), 1800);
        
        return $users;
    }
}

// ✅ Query Handler User Details Gyroscops Cloud (Projet Gyroscops Cloud)
final class UserDetailsQueryHandler
{
    public function __construct(
        private Connection $connection,
        private CacheInterface $cache
    ) {}
    
    public function handleGetUserDetails(GetUserDetailsQuery $query): ?UserDetailsQueryModel
    {
        $cacheKey = "user_details_{$query->getUserId()}";
        
        // Vérifier le cache
        if ($cached = $this->cache->get($cacheKey)) {
            return UserDetailsQueryModel::fromArray($cached);
        }
        
        // Requête optimisée pour les détails
        $sql = 'SELECT u.*, 
                       o.name as organization_name,
                       GROUP_CONCAT(
                           JSON_OBJECT(
                               "event_type", e.event_type,
                               "occurred_at", e.created_at,
                               "user_id", JSON_EXTRACT(e.event_metadata, "$.user_id"),
                               "details", e.event_data
                           )
                           ORDER BY e.created_at
                       ) as audit_trail
                FROM user_details_projections u
                LEFT JOIN organization_projections o ON o.id = u.organization_id
                LEFT JOIN event_store e ON e.aggregate_id = u.id
                WHERE u.id = ?
                GROUP BY u.id';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$query->getUserId()]);
        
        $data = $stmt->fetch();
        if (!$data) {
            return null;
        }
        
        $user = UserDetailsQueryModel::fromArray($data);
        
        // Mettre en cache
        $this->cache->set($cacheKey, $user->toArray(), 3600);
        
        return $user;
    }
}

// ✅ Query Handler User Analytics Gyroscops Cloud (Projet Gyroscops Cloud)
final class UserAnalyticsQueryHandler
{
    public function __construct(
        private Connection $connection,
        private CacheInterface $cache
    ) {}
    
    public function handleGetUserAnalytics(GetUserAnalyticsQuery $query): UserAnalyticsQueryModel
    {
        $cacheKey = "user_analytics_{$query->getOrganizationId()}_{$query->getStartDate()}_{$query->getEndDate()}";
        
        // Vérifier le cache
        if ($cached = $this->cache->get($cacheKey)) {
            return UserAnalyticsQueryModel::fromArray($cached);
        }
        
        // Requête analytique complexe
        $sql = 'SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN enabled = 1 THEN 1 ELSE 0 END) as active_users,
                    SUM(CASE WHEN enabled = 0 THEN 1 ELSE 0 END) as inactive_users,
                    SUM(CASE WHEN email_verified = 1 THEN 1 ELSE 0 END) as verified_users,
                    SUM(CASE WHEN email_verified = 0 THEN 1 ELSE 0 END) as unverified_users,
                    SUM(CASE WHEN last_login_at IS NOT NULL THEN 1 ELSE 0 END) as users_with_login,
                    ROUND(
                        (SUM(CASE WHEN enabled = 1 THEN 1 ELSE 0 END) * 100.0) / COUNT(*), 2
                    ) as activation_rate,
                    ROUND(
                        (SUM(CASE WHEN email_verified = 1 THEN 1 ELSE 0 END) * 100.0) / COUNT(*), 2
                    ) as verification_rate,
                    ROUND(
                        (SUM(CASE WHEN last_login_at IS NOT NULL THEN 1 ELSE 0 END) * 100.0) / COUNT(*), 2
                    ) as login_rate,
                    JSON_ARRAYAGG(
                        JSON_OBJECT(
                            "date", DATE(created_at),
                            "count", daily_count,
                            "active", daily_active
                        )
                    ) as daily_stats,
                    JSON_ARRAYAGG(
                        JSON_OBJECT(
                            "role", role_name,
                            "count", role_count,
                            "percentage", ROUND((role_count * 100.0) / total_count, 2)
                        )
                    ) as role_distribution,
                    JSON_ARRAYAGG(
                        JSON_OBJECT(
                            "organization", organization_name,
                            "count", org_count,
                            "active", org_active
                        )
                    ) as organization_stats
                FROM (
                    SELECT u.*,
                           o.name as organization_name,
                           COUNT(*) OVER (PARTITION BY DATE(u.created_at)) as daily_count,
                           SUM(CASE WHEN u.enabled = 1 THEN 1 ELSE 0 END) OVER (PARTITION BY DATE(u.created_at)) as daily_active,
                           COUNT(*) OVER (PARTITION BY u.role_name) as role_count,
                           COUNT(*) OVER () as total_count,
                           COUNT(*) OVER (PARTITION BY u.organization_id) as org_count,
                           SUM(CASE WHEN u.enabled = 1 THEN 1 ELSE 0 END) OVER (PARTITION BY u.organization_id) as org_active
                    FROM user_analytics_projections u
                    LEFT JOIN organization_projections o ON o.id = u.organization_id
                    WHERE u.organization_id = ? 
                    AND u.created_at BETWEEN ? AND ?
                ) as analytics';
        
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

**Résultat** : Query Handlers spécialisés et optimisés.

### 5. **Créer les Projections Spécialisées**

**Avec Gyroscops** : J'ai créé les projections spécialisées :

```php
// ✅ Projection User List Gyroscops Cloud (Projet Gyroscops Cloud)
final class UserListProjectionHandler
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
            case UserEnabled::class:
                $this->handleUserEnabled($event);
                break;
            case UserDisabled::class:
                $this->handleUserDisabled($event);
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
        $sql = 'INSERT INTO user_list_projections (id, username, email, first_name, last_name, enabled, email_verified, organization_id, roles, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        
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
            $event->getOccurredAt()->format('Y-m-d H:i:s')
        ]);
    }
    
    private function handleUserUpdated(UserUpdated $event): void
    {
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
    
    private function handleUserEnabled(UserEnabled $event): void
    {
        $sql = 'UPDATE user_list_projections SET enabled = 1, updated_at = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getUserId()
        ]);
    }
    
    private function handleUserDisabled(UserDisabled $event): void
    {
        $sql = 'UPDATE user_list_projections SET enabled = 0, updated_at = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getUserId()
        ]);
    }
    
    private function handleUserDeleted(UserDeleted $event): void
    {
        $this->connection->executeStatement('DELETE FROM user_list_projections WHERE id = ?', [$event->getUserId()]);
    }
    
    private function invalidateCache(DomainEvent $event): void
    {
        // Invalider les caches liés à cet événement
        $this->cache->delete("user_list_{$event->getOrganizationId()}_*");
    }
}

// ✅ Projection User Details Gyroscops Cloud (Projet Gyroscops Cloud)
final class UserDetailsProjectionHandler
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
            case UserEnabled::class:
                $this->handleUserEnabled($event);
                break;
            case UserDisabled::class:
                $this->handleUserDisabled($event);
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
        $sql = 'INSERT INTO user_details_projections (id, username, email, first_name, last_name, enabled, email_verified, organization_id, roles, created_at, metadata) 
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
    }
    
    private function handleUserUpdated(UserUpdated $event): void
    {
        $sql = 'UPDATE user_details_projections SET username = ?, email = ?, first_name = ?, last_name = ?, updated_at = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getUsername(),
            $event->getEmail(),
            $event->getFirstName() ?? '',
            $event->getLastName() ?? '',
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getUserId()
        ]);
    }
    
    private function handleUserEnabled(UserEnabled $event): void
    {
        $sql = 'UPDATE user_details_projections SET enabled = 1, updated_at = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getUserId()
        ]);
    }
    
    private function handleUserDisabled(UserDisabled $event): void
    {
        $sql = 'UPDATE user_details_projections SET enabled = 0, updated_at = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getUserId()
        ]);
    }
    
    private function handleUserDeleted(UserDeleted $event): void
    {
        $this->connection->executeStatement('DELETE FROM user_details_projections WHERE id = ?', [$event->getUserId()]);
    }
    
    private function invalidateCache(DomainEvent $event): void
    {
        // Invalider les caches liés à cet événement
        $this->cache->delete("user_details_{$event->getUserId()}");
    }
}
```

**Résultat** : Projections spécialisées pour chaque usage.

## Les Avantages du Stockage API CQRS

### 1. **Performance Maximale**

**Avec Gyroscops** : Le stockage API CQRS m'a donné des performances maximales :
- Modèles optimisés par usage
- Requêtes spécialisées
- Cache intelligent
- Performance prévisible

**Résultat** : Performances de lecture et d'écriture excellentes.

### 2. **Flexibilité Totale**

**Avec Gyroscops** : Le stockage API CQRS m'a donné une flexibilité totale :
- Modèles indépendants
- Évolution séparée
- Vues personnalisées
- Optimisations ciblées

**Résultat** : Flexibilité maximale pour l'évolution.

### 3. **Intégration API Optimisée**

**Avec Gyroscops** : Le stockage API CQRS m'a optimisé l'intégration API :
- APIs externes pour l'écriture
- Projections optimisées pour la lecture
- Gestion des erreurs spécialisée
- Performance maximale

**Résultat** : Intégration API parfaite.

### 4. **Évolutivité Maximale**

**Avec Gyroscops** : Le stockage API CQRS m'a permis une évolutivité maximale :
- Nouvelles projections sans impact
- Optimisations ciblées
- Évolution indépendante
- Flexibilité totale

**Résultat** : Évolutivité maximale.

## Les Inconvénients du Stockage API CQRS

### 1. **Complexité Technique Très Élevée**

**Avec Gyroscops** : Le stockage API CQRS a ajouté une complexité très élevée :
- Courbe d'apprentissage importante
- Beaucoup de composants à maintenir
- Concepts très avancés
- Debugging très complexe

**Résultat** : Complexité technique très élevée.

### 2. **Cohérence Éventuelle**

**Avec Gyroscops** : Le stockage API CQRS peut avoir des problèmes de cohérence :
- Projections asynchrones
- Délai de synchronisation
- Incohérence temporaire
- Gestion des erreurs complexe

**Résultat** : Cohérence éventuelle à gérer.

### 3. **Gestion du Cache Complexe**

**Avec Gyroscops** : Le stockage API CQRS nécessite une gestion du cache complexe :
- Invalidation complexe
- Synchronisation des caches
- Gestion des erreurs
- Performance du cache

**Résultat** : Gestion du cache très complexe.

### 4. **Charge Mentale Élevée**

**Avec Gyroscops** : Le stockage API CQRS a une charge mentale élevée :
- Concepts multiples
- Interactions complexes
- Debugging difficile
- Formation nécessaire

**Résultat** : Charge mentale très élevée.

## Les Pièges à Éviter

### 1. **Modèles Trop Similaires**

**❌ Mauvais** : Command et Query Models trop similaires
**✅ Bon** : Modèles complètement séparés et optimisés

**Pourquoi c'est important ?** Si les modèles sont similaires, CQRS n'apporte rien.

### 2. **Projections Synchrones**

**❌ Mauvais** : Projections mises à jour de façon synchrone
**✅ Bon** : Projections asynchrones avec Event Bus

**Pourquoi c'est crucial ?** Les projections synchrones tuent les performances.

### 3. **Cache Non Invalidé**

**❌ Mauvais** : Cache qui n'est jamais invalidé
**✅ Bon** : Invalidation intelligente du cache

**Pourquoi c'est essentiel ?** Le cache obsolète donne de mauvaises données.

### 4. **Pas de Gestion d'Erreurs API**

**❌ Mauvais** : Pas de gestion des erreurs des APIs externes
**✅ Bon** : Gestion complète des erreurs avec retry et fallback

**Pourquoi c'est critique ?** Les APIs externes peuvent échouer.

## 🏗️ Implémentation Concrète dans le Projet Gyroscops Cloud

### Stockage API CQRS Appliqué à Gyroscops Cloud

Le Gyroscops Cloud applique concrètement les principes du stockage API CQRS à travers son architecture et ses ADR (Architecture Decision Records). Voici comment :

#### Configuration API CQRS Gyroscops Cloud

```php
// ✅ Configuration API CQRS Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveApiCQRSConfiguration
{
    public function configureApiCQRS(ContainerBuilder $container): void
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
        $container->register(UserListQueryHandler::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        $container->register(UserDetailsQueryHandler::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        $container->register(UserAnalyticsQueryHandler::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration des projections
        $container->register(UserListProjectionHandler::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        $container->register(UserDetailsProjectionHandler::class)
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
- **HIVE006** : Query Models for API Platform - Modèles de requête
- **HIVE007** : Command Models for API Platform - Modèles de commande
- **HIVE015** : API Repositories - Repositories d'API
- **HIVE025** : Authorization System - Système d'autorisation
- **HIVE026** : Keycloak Resource and Scope Management - Gestion des ressources Keycloak
- **HIVE038** : Robust Error Handling Patterns - Patterns de gestion d'erreurs

{{< chapter-nav >}}
  {{{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux comprendre le stockage API Event Sourcing" 
    subtitle="Vous voulez voir une approche Event Sourcing avec APIs externes" 
    criteria="Équipe expérimentée,Besoin d'audit trail complet,APIs externes comme source de vérité,Debugging complexe nécessaire" 
    time="30-40 minutes" 
    chapter="62" 
    chapter-title="Stockage API - Event Sourcing seul" 
    chapter-url="/chapitres/stockage/api/chapitre-51-stockage-api-event-sourcing/" 
  >}}}}
  
  {{{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux comprendre le stockage ElasticSearch" 
    subtitle="Vous voulez voir comment optimiser la recherche" 
    criteria="Équipe expérimentée,Besoin de recherche avancée,Analytics importantes,Performance de recherche critique" 
    time="30-40 minutes" 
    chapter="63" 
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
    chapter="26" 
    chapter-title="Stockage MongoDB - Approche Classique" 
    chapter-url="/chapitres/stockage/chapitre-51-stockage-mongodb-classique/" 
  >}}}}
  
{{< /chapter-nav >}}