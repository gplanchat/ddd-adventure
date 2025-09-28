---
title: "Chapitre 9 : Repositories et Persistance"
description: "Comprendre les patterns Repository pour gérer la persistance des données de manière découplée"
date: 2024-12-19
draft: true
type: "docs"
weight: 9
---

## 🎯 Objectif de ce Chapitre

### Mon Problème : Comment Gérer la Persistance des Données ?

**Voici ce qui s'est passé avec Gyroscops** : J'avais créé des modèles riches et implémenté l'architecture événementielle. **Parfait !** J'avais une vision claire des modèles métier et de la communication.

**Mais attendez...** Quand j'ai voulu persister les données, j'étais perdu. Comment sauvegarder les modèles riches ? Comment gérer les transactions ? Comment découpler la persistance du domaine métier ?

**Soudain, je réalisais que mes modèles étaient couplés à la base de données !** Il me fallait comprendre les patterns Repository.

### Les Repositories : Mon Découplage de la Persistance

Les patterns Repository m'ont permis de :
- **Découpler** le domaine métier de la persistance
- **Faciliter** les tests et la maintenance
- **Abstraire** les détails de stockage
- **Gérer** les transactions de manière cohérente

## Qu'est-ce qu'un Repository ?

### Le Concept Fondamental

Un Repository est un pattern qui encapsule la logique d'accès aux données. **L'idée** : Au lieu d'accéder directement à la base de données, on passe par une interface qui abstrait les détails de persistance.

**Avec Gyroscops, voici comment j'ai appliqué les patterns Repository** :

### Les 2 Types de Repositories

#### 1. **Command Repository** - Les Opérations d'Écriture

**Exemple concret avec Gyroscops** :
- `UserCommandRepository` : Sauvegarder, modifier, supprimer des utilisateurs
- `OrganizationCommandRepository` : Sauvegarder, modifier, supprimer des organisations
- `WorkflowCommandRepository` : Sauvegarder, modifier, supprimer des workflows
- `PaymentCommandRepository` : Sauvegarder, modifier, supprimer des paiements

**Pourquoi c'est important ?** Les Command Repositories gèrent les opérations qui modifient l'état.

#### 2. **Query Repository** - Les Opérations de Lecture

**Exemple concret avec Gyroscops** :
- `UserQueryRepository` : Rechercher des utilisateurs
- `OrganizationQueryRepository` : Rechercher des organisations
- `WorkflowQueryRepository` : Rechercher des workflows
- `PaymentQueryRepository` : Rechercher des paiements

**Pourquoi c'est crucial ?** Les Query Repositories gèrent les opérations qui lisent les données.

## Mon Implémentation avec Gyroscops

### L'Interface Repository

**Voici comment j'ai défini l'interface Repository de Gyroscops** :

```php
interface UserCommandRepository
{
    public function save(User $user): void;
    public function update(User $user): void;
    public function delete(string $userId): void;
    public function findById(string $userId): ?User;
}

interface UserQueryRepository
{
    public function findByEmail(string $email): ?User;
    public function findByStatus(UserStatus $status): array;
    public function findByOrganizationId(string $organizationId): array;
    public function findAll(int $limit = 10, int $offset = 0): array;
}
```

**Résultat** : Interfaces claires et découplées.

### L'Implémentation Doctrine

**Voici comment j'ai implémenté les Repositories avec Doctrine** :

```php
class DoctrineUserCommandRepository implements UserCommandRepository
{
    private EntityManager $entityManager;
    private UserMapper $userMapper;

    public function __construct(
        EntityManager $entityManager,
        UserMapper $userMapper
    ) {
        $this->entityManager = $entityManager;
        $this->userMapper = $userMapper;
    }

    public function save(User $user): void
    {
        $userEntity = $this->userMapper->toEntity($user);
        $this->entityManager->persist($userEntity);
        $this->entityManager->flush();
    }

    public function update(User $user): void
    {
        $userEntity = $this->userMapper->toEntity($user);
        $this->entityManager->merge($userEntity);
        $this->entityManager->flush();
    }

    public function delete(string $userId): void
    {
        $userEntity = $this->entityManager->getRepository(UserEntity::class)
            ->find($userId);
        
        if ($userEntity) {
            $this->entityManager->remove($userEntity);
            $this->entityManager->flush();
        }
    }

    public function findById(string $userId): ?User
    {
        $userEntity = $this->entityManager->getRepository(UserEntity::class)
            ->find($userId);
        
        return $userEntity ? $this->userMapper->toDomain($userEntity) : null;
    }
}
```

**Résultat** : Implémentation Doctrine découplée du domaine.

### L'Implémentation In-Memory

**Voici comment j'ai implémenté les Repositories en mémoire pour les tests** :

```php
class InMemoryUserCommandRepository implements UserCommandRepository
{
    private array $users = [];

    public function save(User $user): void
    {
        $this->users[$user->getId()] = $user;
    }

    public function update(User $user): void
    {
        $this->users[$user->getId()] = $user;
    }

    public function delete(string $userId): void
    {
        unset($this->users[$userId]);
    }

    public function findById(string $userId): ?User
    {
        return $this->users[$userId] ?? null;
    }
}
```

**Résultat** : Implémentation simple pour les tests.

### Le UserMapper

**Voici comment j'ai créé le UserMapper pour convertir entre les entités** :

```php
class UserMapper
{
    public function toEntity(User $user): UserEntity
    {
        return new UserEntity(
            $user->getId(),
            $user->getEmail()->getValue(),
            $user->getStatus()->getValue(),
            $user->getCreatedAt(),
            $user->getUpdatedAt()
        );
    }

    public function toDomain(UserEntity $userEntity): User
    {
        return User::fromPersistence(
            $userEntity->getId(),
            new Email($userEntity->getEmail()),
            UserStatus::from($userEntity->getStatus()),
            $userEntity->getCreatedAt(),
            $userEntity->getUpdatedAt()
        );
    }
}
```

**Résultat** : Conversion claire entre domaine et persistance.

## Les Avantages des Repositories

### 1. **Découplage du Domaine**

**Avec Gyroscops** : Au lieu d'avoir le domaine couplé à la base de données :
```php
class User {
    public function save(): void {
        // Logique de sauvegarde directement dans le modèle
        $this->entityManager->persist($this);
        $this->entityManager->flush();
    }
}
```

J'ai des Repositories :
```php
class User {
    // Pas de logique de persistance dans le modèle
}

class UserService {
    public function registerUser(User $user): void {
        // Logique métier
        $this->userRepository->save($user);
    }
}
```

**Résultat** : Domaine découplé de la persistance.

### 2. **Facilitation des Tests**

**Avec Gyroscops** : Je peux tester le domaine sans base de données :
```php
public function testUserRegistration(): void
{
    $userRepository = new InMemoryUserCommandRepository();
    $userService = new UserService($userRepository);
    
    $user = User::register('user-id', 'email@example.com');
    $userService->registerUser($user);
    
    $savedUser = $userRepository->findById('user-id');
    $this->assertEquals($user, $savedUser);
}
```

**Résultat** : Tests plus rapides et plus fiables.

### 3. **Abstraction des Détails de Stockage**

**Avec Gyroscops** : Je peux changer de base de données sans modifier le domaine :
```php
// Implémentation Doctrine
$userRepository = new DoctrineUserCommandRepository($entityManager, $userMapper);

// Implémentation MongoDB
$userRepository = new MongoUserCommandRepository($mongoClient, $userMapper);

// Implémentation In-Memory
$userRepository = new InMemoryUserCommandRepository();
```

**Résultat** : Flexibilité dans le choix de la persistance.

### 4. **Gestion des Transactions**

**Avec Gyroscops** : Je peux gérer les transactions de manière cohérente :
```php
class UserService
{
    public function registerUserWithOrganization(User $user, Organization $organization): void
    {
        $this->entityManager->beginTransaction();
        
        try {
            $this->userRepository->save($user);
            $this->organizationRepository->save($organization);
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }
}
```

**Résultat** : Gestion des transactions centralisée.

## Les Inconvénients des Repositories

### 1. **Complexité Accrue**

**Avec Gyroscops** : Les Repositories ajoutent de la complexité :
- Interfaces à maintenir
- Mappers à créer
- Implémentations multiples

**Résultat** : Courbe d'apprentissage plus importante.

### 2. **Overhead de Performance**

**Avec Gyroscops** : Les Repositories peuvent impacter les performances :
- Couche d'abstraction supplémentaire
- Mappers à exécuter
- Appels de méthodes multiples

**Résultat** : Performance potentiellement dégradée.

### 3. **Duplication de Code**

**Avec Gyroscops** : Les Repositories peuvent créer de la duplication :
- Interfaces similaires
- Implémentations répétitives
- Mappers redondants

**Résultat** : Maintenance plus complexe.

### 4. **Gestion des Relations**

**Avec Gyroscops** : Les Repositories compliquent la gestion des relations :
- Relations complexes à mapper
- Lazy loading difficile
- N+1 queries

**Résultat** : Gestion des relations plus complexe.

## Comment Implémenter les Repositories

### 1. **Définir les Interfaces**

**Avec Gyroscops** : J'ai défini des interfaces claires :
- Command Repositories pour les opérations d'écriture
- Query Repositories pour les opérations de lecture
- Interfaces spécifiques par agrégat

**Résultat** : Contrats clairs et découplés.

### 2. **Créer les Mappers**

**Avec Gyroscops** : J'ai créé des Mappers pour convertir entre les entités :
- Mappers bidirectionnels
- Validation des données
- Gestion des erreurs

**Résultat** : Conversion fiable entre domaine et persistance.

### 3. **Implémenter les Repositories**

**Avec Gyroscops** : J'ai implémenté les Repositories :
- Implémentation Doctrine pour la production
- Implémentation In-Memory pour les tests
- Gestion des transactions

**Résultat** : Implémentations robustes et testables.

### 4. **Intégrer avec les Services**

**Avec Gyroscops** : J'ai intégré les Repositories avec les services :
- Injection de dépendances
- Gestion des transactions
- Gestion des erreurs

**Résultat** : Intégration cohérente et robuste.

## Les Pièges à Éviter

### 1. **Repositories Trop Génériques**

**❌ Mauvais** : `Repository<T>` avec des méthodes génériques
**✅ Bon** : Repositories spécifiques par agrégat

**Pourquoi c'est important ?** Des Repositories trop génériques perdent l'expressivité.

### 2. **Logique Métier dans les Repositories**

**❌ Mauvais** : Logique métier dans les Repositories
**✅ Bon** : Repositories uniquement pour la persistance

**Pourquoi c'est crucial ?** La logique métier doit être dans le domaine.

### 3. **Dépendances Circulaires**

**❌ Mauvais** : Repository A dépend de Repository B qui dépend de Repository A
**✅ Bon** : Dépendances unidirectionnelles

**Pourquoi c'est essentiel ?** Les dépendances circulaires créent des problèmes.

### 4. **Ignorer les Transactions**

**❌ Mauvais** : Pas de gestion des transactions
**✅ Bon** : Gestion appropriée des transactions

**Pourquoi c'est la clé ?** Les transactions sont essentielles pour la cohérence.

## L'Évolution vers les Repositories

### Phase 1 : Accès Direct à la Base de Données

**Avec Gyroscops** : Au début, j'accédais directement à la base de données :
- Modèles couplés à Doctrine
- Logique de persistance dans les modèles
- Tests difficiles

**Résultat** : Développement rapide, maintenance difficile.

### Phase 2 : Introduction des Repositories

**Avec Gyroscops** : J'ai introduit les Repositories :
- Interfaces claires
- Mappers pour la conversion
- Découplage du domaine

**Résultat** : Découplage amélioré, tests plus faciles.

### Phase 3 : Repositories Complets

**Avec Gyroscops** : Maintenant, j'ai des Repositories complets :
- Command et Query Repositories
- Implémentations multiples
- Gestion des transactions

**Résultat** : Architecture robuste et maintenable.

## 🏗️ Implémentation Concrète dans le Projet Gyroscops Cloud

### Repositories Appliqués à Gyroscops Cloud

Le Gyroscops Cloud applique concrètement les principes des Repositories à travers son architecture et ses ADR (Architecture Decision Records). Voici comment :

#### Interfaces de Repositories Gyroscops Cloud

```php
// ✅ Interfaces de Repositories Gyroscops Cloud (Projet Gyroscops Cloud)
interface PaymentCommandRepositoryInterface
{
    public function save(Payment $payment): void;
    public function remove(PaymentId $paymentId): void;
    public function update(Payment $payment): void;
}

interface PaymentQueryRepositoryInterface
{
    public function findById(PaymentId $paymentId): ?Payment;
    public function findByOrganizationId(OrganizationId $organizationId): array;
    public function findByStatus(Statuses $status): array;
    public function findPaginated(PaginationRequest $request): PaginationResponse;
}

interface UserCommandRepositoryInterface
{
    public function save(User $user): void;
    public function remove(UserId $userId): void;
    public function update(User $user): void;
}

interface UserQueryRepositoryInterface
{
    public function findById(UserId $userId): ?User;
    public function findByEmail(Email $email): ?User;
    public function findByOrganizationId(OrganizationId $organizationId): array;
    public function findPaginated(PaginationRequest $request): PaginationResponse;
}
```

#### Implémentations de Repositories Gyroscops Cloud

```php
// ✅ Implémentations de Repositories Gyroscops Cloud (Projet Gyroscops Cloud)
final class SqlPaymentCommandRepository implements PaymentCommandRepositoryInterface
{
    public function __construct(
        private Connection $connection,
        private PaymentMapper $mapper,
        private LoggerInterface $logger
    ) {}
    
    public function save(Payment $payment): void
    {
        $this->logger->info('Saving payment', [
            'payment_id' => $payment->getId()->toString(),
            'organization_id' => $payment->getOrganizationId()->toString()
        ]);
        
        $data = $this->mapper->toArray($payment);
        
        $this->connection->insert('payments', $data);
        
        $this->logger->info('Payment saved successfully', [
            'payment_id' => $payment->getId()->toString()
        ]);
    }
    
    public function remove(PaymentId $paymentId): void
    {
        $this->logger->info('Removing payment', [
            'payment_id' => $paymentId->toString()
        ]);
        
        $this->connection->delete('payments', [
            'id' => $paymentId->toString()
        ]);
        
        $this->logger->info('Payment removed successfully', [
            'payment_id' => $paymentId->toString()
        ]);
    }
    
    public function update(Payment $payment): void
    {
        $this->logger->info('Updating payment', [
            'payment_id' => $payment->getId()->toString()
        ]);
        
        $data = $this->mapper->toArray($payment);
        
        $this->connection->update('payments', $data, [
            'id' => $payment->getId()->toString()
        ]);
        
        $this->logger->info('Payment updated successfully', [
            'payment_id' => $payment->getId()->toString()
        ]);
    }
}
```

#### Mappers Gyroscops Cloud

```php
// ✅ Mappers Gyroscops Cloud (Projet Gyroscops Cloud)
final class PaymentMapper
{
    public function toArray(Payment $payment): array
    {
        return [
            'id' => $payment->getId()->toString(),
            'realm_id' => $payment->getRealmId()->toString(),
            'organization_id' => $payment->getOrganizationId()->toString(),
            'subscription_id' => $payment->getSubscriptionId()->toString(),
            'creation_date' => $payment->getCreationDate()->format('Y-m-d H:i:s'),
            'expiration_date' => $payment->getExpirationDate()->format('Y-m-d H:i:s'),
            'customer_name' => $payment->getCustomerName(),
            'customer_email' => $payment->getCustomerEmail(),
            'status' => $payment->getStatus()->value,
            'subtotal' => $payment->getSubtotal()->getAmount()->toString(),
            'subtotal_currency' => $payment->getSubtotal()->getCurrency()->value,
            'discount' => $payment->getDiscount()->getAmount()->toString(),
            'discount_currency' => $payment->getDiscount()->getCurrency()->value,
            'taxes' => $payment->getTaxes()->getAmount()->toString(),
            'taxes_currency' => $payment->getTaxes()->getCurrency()->value,
            'total' => $payment->getTotal()->getAmount()->toString(),
            'total_currency' => $payment->getTotal()->getCurrency()->value
        ];
    }
    
    public function toDomain(array $data): Payment
    {
        return new Payment(
            PaymentId::fromString($data['id']),
            RealmId::fromString($data['realm_id']),
            OrganizationId::fromString($data['organization_id']),
            SubscriptionId::fromString($data['subscription_id']),
            new \DateTimeImmutable($data['creation_date']),
            new \DateTimeImmutable($data['expiration_date']),
            $data['customer_name'],
            $data['customer_email'],
            Statuses::from($data['status']),
            new Price(BigDecimal::of($data['subtotal']), Currencies::from($data['subtotal_currency'])),
            new Price(BigDecimal::of($data['discount']), Currencies::from($data['discount_currency'])),
            new Price(BigDecimal::of($data['taxes']), Currencies::from($data['taxes_currency'])),
            new Price(BigDecimal::of($data['total']), Currencies::from($data['total_currency']))
        );
    }
}
```

#### Configuration des Repositories

```php
// ✅ Configuration des Repositories Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveRepositoryConfiguration
{
    public function configureRepositories(ContainerInterface $container): void
    {
        // Command Repositories
        $container->set(PaymentCommandRepositoryInterface::class, SqlPaymentCommandRepository::class);
        $container->set(UserCommandRepositoryInterface::class, SqlUserCommandRepository::class);
        $container->set(OrganizationCommandRepositoryInterface::class, SqlOrganizationCommandRepository::class);
        
        // Query Repositories
        $container->set(PaymentQueryRepositoryInterface::class, SqlPaymentQueryRepository::class);
        $container->set(UserQueryRepositoryInterface::class, SqlUserQueryRepository::class);
        $container->set(OrganizationQueryRepositoryInterface::class, SqlOrganizationQueryRepository::class);
        
        // Mappers
        $container->set(PaymentMapper::class, PaymentMapper::class);
        $container->set(UserMapper::class, UserMapper::class);
        $container->set(OrganizationMapper::class, OrganizationMapper::class);
    }
}
```

### Références aux ADR du Projet Gyroscops Cloud

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du Gyroscops Cloud :
- **HIVE010** : Repositories - Repositories fondamentaux
- **HIVE011** : In-Memory Repositories - Repositories en mémoire pour les tests
- **HIVE012** : Database Repositories - Repositories de base de données
- **HIVE023** : Repository Testing Strategies - Stratégies de tests des repositories
- **HIVE033** : Hydrator Implementation Patterns - Patterns d'implémentation des hydrators

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux comprendre les patterns optionnels" 
    subtitle="Vous voulez voir les patterns avancés comme CQRS et Event Sourcing" 
    criteria="Équipe très expérimentée,Besoin de patterns avancés,Complexité très élevée,Performance critique" 
    time="30-45 minutes" 
    chapter="15" 
    chapter-title="Event Sourcing - La Source de Vérité" 
    chapter-url="/chapitres/optionnels/chapitre-15-event-sourcing/" 
  >}}}
  
  {{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux comprendre l'architecture CQS" 
    subtitle="Vous voulez voir une alternative plus simple au CQRS" 
    criteria="Équipe expérimentée,Besoin d'une alternative au CQRS,Complexité élevée mais pas critique,Évolutivité importante" 
    time="25-35 minutes" 
    chapter="15" 
    chapter-title="Architecture CQS - Command Query Separation" 
    chapter-url="/chapitres/optionnels/chapitre-15-architecture-cqs/" 
  >}}}
  
  {{< chapter-option 
    letter="C" 
    color="red" 
    title="Je veux comprendre l'architecture CQRS" 
    subtitle="Vous voulez voir la séparation complète entre commandes et requêtes" 
    criteria="Équipe très expérimentée,Besoin de CQRS complet,Complexité très élevée,Performance critique" 
    time="30-45 minutes" 
    chapter="15" 
    chapter-title="Architecture CQRS avec API Platform" 
    chapter-url="/chapitres/optionnels/chapitre-15-architecture-cqrs/" 
  >}}}
  
  {{< chapter-option 
    letter="D" 
    color="blue" 
    title="Je veux comprendre les chapitres de stockage" 
    subtitle="Vous voulez voir comment implémenter la persistance selon différents patterns" 
    criteria="Équipe expérimentée,Besoin de comprendre la persistance,Patterns de stockage à choisir,Implémentation à faire" 
    time="30-45 minutes" 
    chapter="15" 
    chapter-title="Stockage SQL - Approche Classique" 
    chapter-url="/chapitres/stockage/chapitre-15-stockage-sql-classique/" 
  >}}}
  
  {{< chapter-option 
    letter="E" 
    color="purple" 
    title="Je veux comprendre les chapitres techniques" 
    subtitle="Vous voulez voir les aspects techniques d'affinement" 
    criteria="Équipe expérimentée,Besoin de comprendre les aspects techniques,Qualité et performance importantes,Bonnes pratiques à appliquer" 
    time="25-35 minutes" 
    chapter="51" 
    chapter-title="Gestion des Données et Validation" 
    chapter-url="/chapitres/techniques/chapitre-51-gestion-donnees-validation/" 
  >}}}
  
{{< /chapter-nav >}}