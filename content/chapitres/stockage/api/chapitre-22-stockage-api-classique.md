---
title: "Chapitre 60 : Stockage API - Approche Classique"
description: "Maîtriser le stockage via APIs externes avec une approche classique simple et efficace"
date: 2024-12-19
draft: true
type: "docs"
weight: 60
---

## 🎯 Objectif de ce Chapitre

### Mon Problème : Comment Intégrer des APIs Externes de Façon Simple et Efficace ?

**Voici ce qui s'est passé avec Gyroscops** : J'avais besoin d'intégrer des APIs externes comme Keycloak pour l'authentification, des services de paiement pour les transactions, et des APIs métier pour les données. Pas de complexité, pas de CQRS, pas d'Event Sourcing. Juste une approche classique qui fonctionne bien.

**Mais attendez...** Comment structurer les clients API ? Comment gérer les erreurs ? Comment optimiser les performances ? Comment intégrer avec API Platform ?

**Soudain, je réalisais que l'approche classique était parfaite !** Il me fallait une méthode simple et efficace pour intégrer les APIs externes.

### Stockage API Classique : Mon Guide Pratique

Le stockage API classique m'a permis de :
- **Intégrer** facilement des services externes
- **Maintenir** simplement les clients API
- **Comprendre** clairement les intégrations
- **Évoluer** progressivement les connexions

## Qu'est-ce que le Stockage API Classique ?

### Le Concept Fondamental

Le stockage API classique consiste à utiliser des clients HTTP pour interagir avec des APIs externes via des repositories classiques. **L'idée** : Un repository = une API, avec des méthodes simples et des requêtes directes.

**Avec Gyroscops, voici comment j'ai structuré le stockage API classique** :

### Les 4 Piliers du Stockage API Classique

#### 1. **Clients HTTP** - Communication avec les APIs

**Voici comment j'ai implémenté les clients HTTP avec Gyroscops** :

**Clients Spécialisés** :
- Client Keycloak pour l'authentification
- Client Stripe pour les paiements
- Client SendGrid pour les emails
- Client Slack pour les notifications

**Exemples** :
- `KeycloakClient` (authentification)
- `StripeClient` (paiements)
- `SendGridClient` (emails)

#### 2. **Repositories API** - Accès aux données externes

**Voici comment j'ai implémenté les repositories API avec Gyroscops** :

**Repositories Simples** :
- Méthodes de base (find, findAll, save, delete)
- Requêtes HTTP directes
- Gestion des erreurs
- Pas de complexité CQRS

#### 3. **API Platform** - Exposition des données

**Voici comment j'ai intégré API Platform avec Gyroscops** :

**Ressources API** :
- Entités exposées directement
- Opérations CRUD automatiques
- Filtres et pagination
- Documentation automatique

#### 4. **Gestion des Erreurs** - Robustesse des intégrations

**Voici comment j'ai géré les erreurs avec Gyroscops** :

**Stratégies d'Erreur** :
- Retry automatique
- Circuit breaker
- Fallback gracieux
- Logging détaillé

## Comment Implémenter le Stockage API Classique

### 1. **Créer les Clients HTTP**

**Avec Gyroscops** : J'ai créé les clients HTTP :

```php
// ✅ Client Keycloak Hive (Projet Hive)
final class KeycloakClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $baseUrl,
        private string $realm,
        private string $clientId,
        private string $clientSecret
    ) {}
    
    public function createUser(array $userData): array
    {
        $token = $this->getAccessToken();
        
        $response = $this->httpClient->request('POST', $this->baseUrl . "/admin/realms/{$this->realm}/users", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ],
            'json' => $userData
        ]);
        
        if ($response->getStatusCode() !== 201) {
            throw new KeycloakException('Failed to create user: ' . $response->getContent());
        }
        
        return json_decode($response->getContent(), true);
    }
    
    public function getUser(string $userId): ?array
    {
        $token = $this->getAccessToken();
        
        $response = $this->httpClient->request('GET', $this->baseUrl . "/admin/realms/{$this->realm}/users/{$userId}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        
        if ($response->getStatusCode() === 404) {
            return null;
        }
        
        if ($response->getStatusCode() !== 200) {
            throw new KeycloakException('Failed to get user: ' . $response->getContent());
        }
        
        return json_decode($response->getContent(), true);
    }
    
    public function updateUser(string $userId, array $userData): void
    {
        $token = $this->getAccessToken();
        
        $response = $this->httpClient->request('PUT', $this->baseUrl . "/admin/realms/{$this->realm}/users/{$userId}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ],
            'json' => $userData
        ]);
        
        if ($response->getStatusCode() !== 204) {
            throw new KeycloakException('Failed to update user: ' . $response->getContent());
        }
    }
    
    public function deleteUser(string $userId): void
    {
        $token = $this->getAccessToken();
        
        $response = $this->httpClient->request('DELETE', $this->baseUrl . "/admin/realms/{$this->realm}/users/{$userId}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        
        if ($response->getStatusCode() !== 204) {
            throw new KeycloakException('Failed to delete user: ' . $response->getContent());
        }
    }
    
    public function getUsers(array $filters = []): array
    {
        $token = $this->getAccessToken();
        
        $queryParams = http_build_query($filters);
        $url = $this->baseUrl . "/admin/realms/{$this->realm}/users";
        if ($queryParams) {
            $url .= '?' . $queryParams;
        }
        
        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        
        if ($response->getStatusCode() !== 200) {
            throw new KeycloakException('Failed to get users: ' . $response->getContent());
        }
        
        return json_decode($response->getContent(), true);
    }
    
    private function getAccessToken(): string
    {
        $response = $this->httpClient->request('POST', $this->baseUrl . "/realms/{$this->realm}/protocol/openid-connect/token", [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body' => http_build_query([
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret
            ])
        ]);
        
        if ($response->getStatusCode() !== 200) {
            throw new KeycloakException('Failed to get access token: ' . $response->getContent());
        }
        
        $data = json_decode($response->getContent(), true);
        return $data['access_token'];
    }
}
```

**Résultat** : Client HTTP robuste et réutilisable.

### 2. **Créer les Repositories API**

**Avec Gyroscops** : J'ai créé les repositories API :

```php
// ✅ Repository User API Hive (Projet Hive)
final class UserApiRepository implements UserRepositoryInterface
{
    public function __construct(
        private KeycloakClient $keycloakClient,
        private UserMapper $userMapper
    ) {}
    
    public function save(User $user): void
    {
        $userData = $this->userMapper->toArray($user);
        
        if ($user->getId()) {
            $this->keycloakClient->updateUser($user->getId(), $userData);
        } else {
            $result = $this->keycloakClient->createUser($userData);
            $user->setId($result['id']);
        }
    }
    
    public function find(string $id): ?User
    {
        $userData = $this->keycloakClient->getUser($id);
        
        if (!$userData) {
            return null;
        }
        
        return $this->userMapper->fromArray($userData);
    }
    
    public function findAll(array $criteria = []): array
    {
        $usersData = $this->keycloakClient->getUsers($criteria);
        
        return array_map([$this->userMapper, 'fromArray'], $usersData);
    }
    
    public function delete(string $id): void
    {
        $this->keycloakClient->deleteUser($id);
    }
    
    public function findByEmail(string $email): ?User
    {
        $usersData = $this->keycloakClient->getUsers(['email' => $email]);
        
        if (empty($usersData)) {
            return null;
        }
        
        return $this->userMapper->fromArray($usersData[0]);
    }
    
    public function findByOrganization(string $organizationId): array
    {
        $usersData = $this->keycloakClient->getUsers(['organizationId' => $organizationId]);
        
        return array_map([$this->userMapper, 'fromArray'], $usersData);
    }
}
```

**Résultat** : Repository API simple et efficace.

### 3. **Créer les Mappers**

**Avec Gyroscops** : J'ai créé les mappers :

```php
// ✅ Mapper User Hive (Projet Hive)
final class UserMapper
{
    public function toArray(User $user): array
    {
        return [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'enabled' => $user->isEnabled(),
            'emailVerified' => $user->isEmailVerified(),
            'attributes' => [
                'organizationId' => [$user->getOrganizationId()],
                'roles' => $user->getRoles()
            ]
        ];
    }
    
    public function fromArray(array $data): User
    {
        $user = new User();
        $user->setId($data['id'] ?? null);
        $user->setUsername($data['username'] ?? '');
        $user->setEmail($data['email'] ?? '');
        $user->setFirstName($data['firstName'] ?? '');
        $user->setLastName($data['lastName'] ?? '');
        $user->setEnabled($data['enabled'] ?? true);
        $user->setEmailVerified($data['emailVerified'] ?? false);
        
        if (isset($data['attributes']['organizationId'][0])) {
            $user->setOrganizationId($data['attributes']['organizationId'][0]);
        }
        
        if (isset($data['attributes']['roles'])) {
            $user->setRoles($data['attributes']['roles']);
        }
        
        return $user;
    }
}
```

**Résultat** : Mapper pour la conversion des données.

### 4. **Créer les Entités API Platform**

**Avec Gyroscops** : J'ai créé les entités API Platform :

```php
// ✅ Entité User API Platform Hive (Projet Hive)
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete()
    ],
    filters: [
        'user.email_filter',
        'user.organization_filter'
    ]
)]
final class User
{
    #[Id]
    public ?string $id = null;
    
    #[Column(type: 'string', length: 255)]
    public string $username;
    
    #[Column(type: 'string', length: 255)]
    public string $email;
    
    #[Column(type: 'string', length: 255)]
    public string $firstName;
    
    #[Column(type: 'string', length: 255)]
    public string $lastName;
    
    #[Column(type: 'boolean')]
    public bool $enabled = true;
    
    #[Column(type: 'boolean')]
    public bool $emailVerified = false;
    
    #[Column(type: 'uuid')]
    public string $organizationId;
    
    #[Column(type: 'json')]
    public array $roles = [];
    
    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }
    
    // Getters et setters...
    public function getId(): ?string { return $this->id; }
    public function setId(?string $id): void { $this->id = $id; }
    
    public function getUsername(): string { return $this->username; }
    public function setUsername(string $username): void { $this->username = $username; }
    
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }
    
    public function getFirstName(): string { return $this->firstName; }
    public function setFirstName(string $firstName): void { $this->firstName = $firstName; }
    
    public function getLastName(): string { return $this->lastName; }
    public function setLastName(string $lastName): void { $this->lastName = $lastName; }
    
    public function isEnabled(): bool { return $this->enabled; }
    public function setEnabled(bool $enabled): void { $this->enabled = $enabled; }
    
    public function isEmailVerified(): bool { return $this->emailVerified; }
    public function setEmailVerified(bool $emailVerified): void { $this->emailVerified = $emailVerified; }
    
    public function getOrganizationId(): string { return $this->organizationId; }
    public function setOrganizationId(string $organizationId): void { $this->organizationId = $organizationId; }
    
    public function getRoles(): array { return $this->roles; }
    public function setRoles(array $roles): void { $this->roles = $roles; }
}
```

**Résultat** : Entité API Platform pour l'exposition.

### 5. **Créer les Filtres API Platform**

**Avec Gyroscops** : J'ai créé les filtres :

```php
// ✅ Filtre Email User Hive (Projet Hive)
final class EmailFilter extends AbstractFilter
{
    protected function getPropertyName(string $property): string
    {
        return match($property) {
            'email' => 'email',
            default => $property
        };
    }
    
    public function getDescription(string $resourceClass): array
    {
        return [
            'email' => [
                'property' => 'email',
                'type' => 'string',
                'required' => false,
                'description' => 'Filter by email address'
            ]
        ];
    }
}

// ✅ Filtre Organization User Hive (Projet Hive)
final class OrganizationFilter extends AbstractFilter
{
    protected function getPropertyName(string $property): string
    {
        return match($property) {
            'organizationId' => 'organizationId',
            default => $property
        };
    }
    
    public function getDescription(string $resourceClass): array
    {
        return [
            'organizationId' => [
                'property' => 'organizationId',
                'type' => 'string',
                'required' => false,
                'description' => 'Filter by organization ID'
            ]
        ];
    }
}
```

**Résultat** : Filtres pour la recherche et la pagination.

## Les Avantages du Stockage API Classique

### 1. **Simplicité**

**Avec Gyroscops** : Le stockage API classique m'a donné de la simplicité :
- Code simple et compréhensible
- Pas de complexité inutile
- Développement rapide
- Maintenance facile

**Résultat** : Développement et maintenance simplifiés.

### 2. **Intégration Facile**

**Avec Gyroscops** : Le stockage API classique m'a facilité l'intégration :
- Clients HTTP standard
- APIs externes directement accessibles
- Pas de couche d'abstraction complexe
- Intégration rapide

**Résultat** : Intégration des services externes facilitée.

### 3. **Flexibilité**

**Avec Gyroscops** : Le stockage API classique m'a donné de la flexibilité :
- Support de toutes les APIs REST
- Adaptation facile aux changements d'API
- Gestion des versions d'API
- Évolution progressive

**Résultat** : Flexibilité maximale pour les intégrations.

### 4. **Performance**

**Avec Gyroscops** : Le stockage API classique m'a donné de bonnes performances :
- Requêtes HTTP optimisées
- Cache HTTP standard
- Gestion des timeouts
- Performance prévisible

**Résultat** : Performances satisfaisantes.

## Les Inconvénients du Stockage API Classique

### 1. **Dépendance Externe**

**Avec Gyroscops** : Le stockage API classique a créé des dépendances :
- Dépendance aux APIs externes
- Disponibilité non garantie
- Latence réseau
- Gestion des pannes

**Résultat** : Dépendance aux services externes.

### 2. **Gestion des Erreurs**

**Avec Gyroscops** : Le stockage API classique nécessite une gestion d'erreurs :
- Erreurs réseau
- Timeouts
- Codes d'erreur variés
- Retry logic complexe

**Résultat** : Gestion des erreurs complexe.

### 3. **Cohérence des Données**

**Avec Gyroscops** : Le stockage API classique peut avoir des problèmes de cohérence :
- Pas de transactions distribuées
- Cohérence éventuelle
- Synchronisation complexe
- Gestion des conflits

**Résultat** : Cohérence des données limitée.

## Les Pièges à Éviter

### 1. **Pas de Gestion d'Erreurs**

**❌ Mauvais** : Pas de gestion des erreurs API
**✅ Bon** : Gestion complète des erreurs avec retry et fallback

**Pourquoi c'est important ?** Les APIs externes peuvent échouer.

### 2. **Pas de Cache**

**❌ Mauvais** : Requêtes répétées aux APIs
**✅ Bon** : Cache HTTP intelligent

**Pourquoi c'est crucial ?** Le cache améliore les performances.

### 3. **Pas de Timeout**

**❌ Mauvais** : Requêtes qui traînent indéfiniment
**✅ Bon** : Timeouts appropriés

**Pourquoi c'est essentiel ?** Les timeouts évitent les blocages.

## 🏗️ Implémentation Concrète dans le Projet Hive

### Stockage API Classique Appliqué à Hive

Le projet Hive applique concrètement les principes du stockage API classique à travers son architecture et ses ADR (Architecture Decision Records). Voici comment :

#### Configuration API Clients Hive

```php
// ✅ Configuration API Clients Hive (Projet Hive)
final class HiveApiClientsConfiguration
{
    public function configureApiClients(ContainerBuilder $container): void
    {
        // Configuration Keycloak
        $container->register(KeycloakClient::class)
            ->setArguments([
                '$baseUrl' => $_ENV['KEYCLOAK_BASE_URL'],
                '$realm' => $_ENV['KEYCLOAK_REALM'],
                '$clientId' => $_ENV['KEYCLOAK_CLIENT_ID'],
                '$clientSecret' => $_ENV['KEYCLOAK_CLIENT_SECRET']
            ])
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration Stripe
        $container->register(StripeClient::class)
            ->setArguments([
                '$apiKey' => $_ENV['STRIPE_API_KEY'],
                '$webhookSecret' => $_ENV['STRIPE_WEBHOOK_SECRET']
            ])
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration des repositories
        $container->register(UserApiRepository::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        $container->register(PaymentApiRepository::class)
            ->setAutowired(true)
            ->setPublic(true);
    }
}
```

### Références aux ADR du Projet Hive

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du projet Hive :
- **HIVE015** : API Repositories - Repositories d'API
- **HIVE025** : Authorization System - Système d'autorisation
- **HIVE026** : Keycloak Resource and Scope Management - Gestion des ressources Keycloak
- **HIVE038** : Robust Error Handling Patterns - Patterns de gestion d'erreurs

{{< chapter-nav >}}
  {{{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux comprendre le stockage API CQS" 
    subtitle="Vous voulez voir une approche avec séparation des commandes et requêtes" 
    criteria="Équipe expérimentée,Besoin d'optimiser les performances,Séparation des responsabilités importante,Évolutivité importante" 
    time="25-35 minutes" 
    chapter="60" 
    chapter-title="Stockage API - Approche CQS" 
    chapter-url="/chapitres/stockage/api/chapitre-51-stockage-api-cqs/" 
  >}}}}
  
  {{{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux comprendre le stockage API CQRS" 
    subtitle="Vous voulez voir une approche avec séparation complète des modèles" 
    criteria="Équipe très expérimentée,Besoin de performance maximale,Complexité élevée acceptable,Scalabilité critique" 
    time="30-45 minutes" 
    chapter="61" 
    chapter-title="Stockage API - Approche CQRS" 
    chapter-url="/chapitres/stockage/api/chapitre-51-stockage-api-cqrs/" 
  >}}}}
  
  {{{< chapter-option 
    letter="C" 
    color="red" 
    title="Je veux comprendre le stockage ElasticSearch" 
    subtitle="Vous voulez voir comment optimiser la recherche" 
    criteria="Équipe expérimentée,Besoin de recherche avancée,Analytics importantes,Performance de recherche critique" 
    time="30-40 minutes" 
    chapter="62" 
    chapter-title="Stockage ElasticSearch - Approche Classique" 
    chapter-url="/chapitres/stockage/chapitre-51-stockage-elasticsearch-classique/" 
  >}}}}
  
{{< /chapter-nav >}}