---
title: "Chapitre 8 : Architecture Événementielle"
description: "Comprendre l'architecture événementielle pour découpler les composants et améliorer l'évolutivité"
date: 2024-12-19
draft: true
type: "docs"
weight: 8
---

## 🎯 Objectif de ce Chapitre

### Mon Problème : Comment Découpler les Composants ?

**Voici ce qui s'est passé avec Gyroscops** : J'avais créé des modèles riches et défini l'architecture appropriée. **Parfait !** J'avais une vision claire des modèles métier.

**Mais attendez...** Quand j'ai voulu faire communiquer les composants, j'étais perdu. Appels directs entre services ? Couplage fort ? Comment découpler les composants pour améliorer l'évolutivité ?

**Soudain, je réalisais que mes composants étaient trop couplés !** Il me fallait comprendre l'architecture événementielle.

### L'Architecture Événementielle : Mon Découplage

L'architecture événementielle m'a permis de :
- **Découpler** les composants du système
- **Améliorer** l'évolutivité et la maintenabilité
- **Faciliter** l'ajout de nouvelles fonctionnalités
- **Réduire** les dépendances entre composants

## Qu'est-ce que l'Architecture Événementielle ?

### Le Concept Fondamental

L'architecture événementielle est un paradigme où les composants communiquent via des événements. **L'idée** : Au lieu d'appeler directement les composants, on publie des événements que d'autres composants peuvent écouter.

**Avec Gyroscops, voici comment j'ai appliqué l'architecture événementielle** :

### Les 3 Composants Principaux

#### 1. **Domain Events** - Les Événements Métier

**Exemple concret avec Gyroscops** :
- `UserRegistered` : Un utilisateur s'est inscrit
- `OrganizationCreated` : Une organisation a été créée
- `WorkflowDeployed` : Un workflow a été déployé
- `IntegrationStarted` : Une intégration a commencé
- `PaymentProcessed` : Un paiement a été traité

**Pourquoi c'est important ?** Les événements racontent l'histoire du domaine métier.

#### 2. **Event Bus** - Le Système de Communication

**Exemple concret avec Gyroscops** :
- **Publication** : Les composants publient des événements
- **Souscription** : Les composants s'abonnent aux événements
- **Distribution** : L'Event Bus distribue les événements

**Pourquoi c'est crucial ?** L'Event Bus découple les composants et facilite la communication.

#### 3. **Event Handlers** - Les Gestionnaires d'Événements

**Exemple concret avec Gyroscops** :
- `UserRegisteredHandler` : Envoie un email de bienvenue
- `OrganizationCreatedHandler` : Crée un workflow par défaut
- `WorkflowDeployedHandler` : Configure les ressources cloud
- `IntegrationStartedHandler` : Démarre le monitoring

**Pourquoi c'est essentiel ?** Les Event Handlers réagissent aux événements et exécutent des actions.

## Mon Implémentation avec Gyroscops

### La Structure de Base

**Voici comment j'ai structuré l'architecture événementielle de Gyroscops** :

```php
// Domain Event
interface DomainEvent
{
    public function getEventId(): string;
    public function getOccurredOn(): DateTime;
    public function getEventType(): string;
}

// Event Bus
interface EventBus
{
    public function publish(DomainEvent $event): void;
    public function subscribe(string $eventType, EventHandler $handler): void;
}

// Event Handler
interface EventHandler
{
    public function handle(DomainEvent $event): void;
}
```

**Résultat** : Structure claire et découplée.

### Les Événements Métier

**Voici comment j'ai créé les événements métier de Gyroscops** :

```php
class UserRegistered implements DomainEvent
{
    private string $eventId;
    private DateTime $occurredOn;
    private string $userId;
    private string $email;
    private string $organizationId;

    public function __construct(
        string $userId,
        string $email,
        string $organizationId
    ) {
        $this->eventId = Uuid::uuid4()->toString();
        $this->occurredOn = new DateTime();
        $this->userId = $userId;
        $this->email = $email;
        $this->organizationId = $organizationId;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getOccurredOn(): DateTime
    {
        return $this->occurredOn;
    }

    public function getEventType(): string
    {
        return 'UserRegistered';
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getOrganizationId(): string
    {
        return $this->organizationId;
    }
}
```

**Résultat** : Événements métier clairs et typés.

### L'Event Bus

**Voici comment j'ai implémenté l'Event Bus de Gyroscops** :

```php
class InMemoryEventBus implements EventBus
{
    private array $handlers = [];

    public function publish(DomainEvent $event): void
    {
        $eventType = $event->getEventType();
        
        if (!isset($this->handlers[$eventType])) {
            return;
        }

        foreach ($this->handlers[$eventType] as $handler) {
            try {
                $handler->handle($event);
            } catch (Exception $e) {
                // Log l'erreur mais continue avec les autres handlers
                error_log("Erreur dans le handler {$eventType}: " . $e->getMessage());
            }
        }
    }

    public function subscribe(string $eventType, EventHandler $handler): void
    {
        if (!isset($this->handlers[$eventType])) {
            $this->handlers[$eventType] = [];
        }
        
        $this->handlers[$eventType][] = $handler;
    }
}
```

**Résultat** : Event Bus simple et robuste.

### Les Event Handlers

**Voici comment j'ai créé les Event Handlers de Gyroscops** :

```php
class UserRegisteredHandler implements EventHandler
{
    private EmailService $emailService;
    private OrganizationService $organizationService;

    public function __construct(
        EmailService $emailService,
        OrganizationService $organizationService
    ) {
        $this->emailService = $emailService;
        $this->organizationService = $organizationService;
    }

    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof UserRegistered) {
            return;
        }

        // Envoyer un email de bienvenue
        $this->emailService->sendWelcomeEmail(
            $event->getEmail(),
            $event->getUserId()
        );

        // Créer un workflow par défaut
        $this->organizationService->createDefaultWorkflow(
            $event->getOrganizationId(),
            $event->getUserId()
        );
    }
}
```

**Résultat** : Event Handlers découplés et réutilisables.

## Les Avantages de l'Architecture Événementielle

### 1. **Découplage des Composants**

**Avec Gyroscops** : Au lieu d'avoir des appels directs :
```php
class UserService {
    public function registerUser(User $user): void {
        // Logique d'inscription
        $this->emailService->sendWelcomeEmail($user->getEmail());
        $this->organizationService->createDefaultWorkflow($user->getOrganizationId());
    }
}
```

J'ai des événements :
```php
class UserService {
    public function registerUser(User $user): void {
        // Logique d'inscription
        $this->eventBus->publish(new UserRegistered(
            $user->getId(),
            $user->getEmail(),
            $user->getOrganizationId()
        ));
    }
}
```

**Résultat** : Composants découplés, maintenance plus facile.

### 2. **Évolutivité Améliorée**

**Avec Gyroscops** : Pour ajouter une nouvelle fonctionnalité, je n'ai qu'à créer un nouvel Event Handler :
```php
class UserRegisteredHandler implements EventHandler
{
    public function handle(DomainEvent $event): void
    {
        // Nouvelle fonctionnalité : envoyer une notification Slack
        $this->slackService->notifyUserRegistration($event->getUserId());
    }
}
```

**Résultat** : Ajout de fonctionnalités sans modifier le code existant.

### 3. **Testabilité Améliorée**

**Avec Gyroscops** : Je peux tester chaque composant indépendamment :
```php
public function testUserRegistration(): void
{
    $eventBus = new InMemoryEventBus();
    $userService = new UserService($eventBus);
    
    $user = User::register('user-id', 'email@example.com');
    $userService->registerUser($user);
    
    // Vérifier que l'événement a été publié
    $this->assertEventPublished($eventBus, UserRegistered::class);
}
```

**Résultat** : Tests plus simples et plus fiables.

### 4. **Résilience Améliorée**

**Avec Gyroscops** : Si un Event Handler échoue, les autres continuent :
```php
public function publish(DomainEvent $event): void
{
    foreach ($this->handlers[$eventType] as $handler) {
        try {
            $handler->handle($event);
        } catch (Exception $e) {
            // Log l'erreur mais continue avec les autres handlers
            error_log("Erreur dans le handler: " . $e->getMessage());
        }
    }
}
```

**Résultat** : Système plus résilient aux erreurs.

## Les Inconvénients de l'Architecture Événementielle

### 1. **Complexité Accrue**

**Avec Gyroscops** : L'architecture événementielle ajoute de la complexité :
- Event Bus à gérer
- Event Handlers à maintenir
- Debugging plus difficile

**Résultat** : Courbe d'apprentissage plus importante.

### 2. **Debugging Plus Difficile**

**Avec Gyroscops** : Le flux d'exécution est moins évident :
- Événements asynchrones
- Handlers multiples
- Ordre d'exécution non garanti

**Résultat** : Debugging plus complexe.

### 3. **Gestion des Erreurs**

**Avec Gyroscops** : Les erreurs dans les Event Handlers peuvent être silencieuses :
- Handlers qui échouent
- Événements perdus
- Retry logic nécessaire

**Résultat** : Gestion d'erreurs plus complexe.

### 4. **Performance**

**Avec Gyroscops** : L'architecture événementielle peut impacter les performances :
- Overhead de l'Event Bus
- Handlers multiples
- Sérialisation/désérialisation

**Résultat** : Performance potentiellement dégradée.

## Comment Implémenter l'Architecture Événementielle

### 1. **Identifier les Événements Métier**

**Avec Gyroscops** : J'ai identifié les événements métier lors de l'Event Storming :
- `UserRegistered` : Inscription d'un utilisateur
- `OrganizationCreated` : Création d'une organisation
- `WorkflowDeployed` : Déploiement d'un workflow
- `IntegrationStarted` : Démarrage d'une intégration
- `PaymentProcessed` : Traitement d'un paiement

**Résultat** : Événements métier clairs et partagés.

### 2. **Créer l'Event Bus**

**Avec Gyroscops** : J'ai créé un Event Bus simple :
- Interface claire
- Implémentation in-memory pour commencer
- Gestion d'erreurs robuste

**Résultat** : Event Bus simple et fiable.

### 3. **Implémenter les Event Handlers**

**Avec Gyroscops** : J'ai créé des Event Handlers découplés :
- Un handler par responsabilité
- Gestion d'erreurs appropriée
- Tests unitaires

**Résultat** : Event Handlers maintenables et testables.

### 4. **Intégrer avec les Modèles Riches**

**Avec Gyroscops** : J'ai intégré les événements avec les modèles riches :
- Publication d'événements dans les méthodes métier
- Événements typés et immutables
- Validation des événements

**Résultat** : Intégration cohérente et robuste.

## Les Pièges à Éviter

### 1. **Événements Trop Granulaires**

**❌ Mauvais** : Un événement pour chaque setter
**✅ Bon** : Un événement pour chaque action métier significative

**Pourquoi c'est important ?** Des événements trop granulaires créent du bruit.

### 2. **Handlers Trop Gros**

**❌ Mauvais** : Un handler qui fait tout
**✅ Bon** : Un handler par responsabilité

**Pourquoi c'est crucial ?** Des handlers trop gros sont difficiles à maintenir.

### 3. **Dépendances Circulaires**

**❌ Mauvais** : Handler A publie un événement que Handler B écoute, qui publie un événement que Handler A écoute
**✅ Bon** : Flux d'événements unidirectionnel

**Pourquoi c'est essentiel ?** Les dépendances circulaires créent des boucles infinies.

### 4. **Ignorer les Erreurs**

**❌ Mauvais** : Ignorer les erreurs dans les handlers
**✅ Bon** : Gérer les erreurs appropriément

**Pourquoi c'est la clé ?** Les erreurs non gérées peuvent casser le système.

## L'Évolution vers l'Architecture Événementielle

### Phase 1 : Appels Directs

**Avec Gyroscops** : Au début, j'avais des appels directs :
- Services qui appellent d'autres services
- Couplage fort
- Difficile à tester

**Résultat** : Développement rapide, maintenance difficile.

### Phase 2 : Introduction des Événements

**Avec Gyroscops** : J'ai introduit les événements :
- Événements métier clairs
- Event Bus simple
- Handlers découplés

**Résultat** : Découplage amélioré, évolutivité accrue.

### Phase 3 : Architecture Événementielle Complète

**Avec Gyroscops** : Maintenant, j'ai une architecture événementielle complète :
- Événements métier riches
- Event Bus robuste
- Handlers spécialisés

**Résultat** : Architecture évolutive et maintenable.

## 🏗️ Implémentation Concrète dans le Projet Gyroscops Cloud

### Architecture Événementielle Appliquée à Gyroscops Cloud

Le Gyroscops Cloud applique concrètement les principes de l'architecture événementielle à travers son architecture et ses ADR (Architecture Decision Records). Voici comment :

#### Event Bus Gyroscops Cloud

```php
// ✅ Event Bus Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveEventBus
{
    public function __construct(
        private array $handlers = [],
        private LoggerInterface $logger
    ) {}
    
    public function publish(DomainEvent $event): void
    {
        $eventType = get_class($event);
        
        $this->logger->info('Publishing event', [
            'event_type' => $eventType,
            'event_data' => $event->toArray()
        ]);
        
        if (!isset($this->handlers[$eventType])) {
            $this->logger->warning('No handlers found for event', [
                'event_type' => $eventType
            ]);
            return;
        }
        
        foreach ($this->handlers[$eventType] as $handler) {
            try {
                $handler->handle($event);
            } catch (\Exception $e) {
                $this->logger->error('Error in event handler', [
                    'event_type' => $eventType,
                    'handler_class' => get_class($handler),
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    public function subscribe(string $eventType, EventHandlerInterface $handler): void
    {
        $this->handlers[$eventType][] = $handler;
        
        $this->logger->info('Subscribed handler to event', [
            'event_type' => $eventType,
            'handler_class' => get_class($handler)
        ]);
    }
}
```

#### Événements Métier Gyroscops Cloud

```php
// ✅ Événements Métier Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveDomainEvents
{
    // Événements d'Authentification
    public const USER_REGISTERED = 'user.registered';
    public const USER_AUTHENTICATED = 'user.authenticated';
    public const USER_LOGGED_OUT = 'user.logged_out';
    
    // Événements de Paiement
    public const PAYMENT_INITIATED = 'payment.initiated';
    public const PAYMENT_COMPLETED = 'payment.completed';
    public const PAYMENT_FAILED = 'payment.failed';
    
    // Événements d'Intégration
    public const INTEGRATION_CREATED = 'integration.created';
    public const INTEGRATION_DEPLOYED = 'integration.deployed';
    public const INTEGRATION_FAILED = 'integration.failed';
    
    // Événements de Monitoring
    public const ALERT_TRIGGERED = 'alert.triggered';
    public const METRICS_COLLECTED = 'metrics.collected';
    public const HEALTH_CHECK_FAILED = 'health.check.failed';
}
```

#### Event Handlers Gyroscops Cloud

```php
// ✅ Event Handlers Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveEventHandlers
{
    // Handler d'Authentification
    public function handleUserRegistered(UserRegisteredEvent $event): void
    {
        // Envoyer un email de bienvenue
        $this->emailService->sendWelcomeEmail($event->getUser());
        
        // Créer un profil utilisateur
        $this->profileService->createProfile($event->getUser());
        
        // Logger l'événement
        $this->logger->info('User registered', [
            'user_id' => $event->getUser()->getId(),
            'email' => $event->getUser()->getEmail()
        ]);
    }
    
    // Handler de Paiement
    public function handlePaymentCompleted(PaymentCompletedEvent $event): void
    {
        // Mettre à jour le statut de la commande
        $this->orderService->updateOrderStatus($event->getOrderId(), 'paid');
        
        // Envoyer une confirmation de paiement
        $this->emailService->sendPaymentConfirmation($event->getCustomer());
        
        // Logger l'événement
        $this->logger->info('Payment completed', [
            'payment_id' => $event->getPaymentId(),
            'amount' => $event->getAmount()
        ]);
    }
    
    // Handler d'Intégration
    public function handleIntegrationDeployed(IntegrationDeployedEvent $event): void
    {
        // Démarrer le monitoring
        $this->monitoringService->startMonitoring($event->getIntegration());
        
        // Notifier les administrateurs
        $this->notificationService->notifyAdmins($event->getIntegration());
        
        // Logger l'événement
        $this->logger->info('Integration deployed', [
            'integration_id' => $event->getIntegration()->getId(),
            'environment' => $event->getEnvironment()
        ]);
    }
}
```

#### Configuration des Événements

```php
// ✅ Configuration des Événements Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveEventConfiguration
{
    public function configureEventBus(EventBusInterface $eventBus): void
    {
        // Authentification
        $eventBus->subscribe(UserRegisteredEvent::class, new UserRegisteredHandler());
        $eventBus->subscribe(UserAuthenticatedEvent::class, new UserAuthenticatedHandler());
        $eventBus->subscribe(UserLoggedOutEvent::class, new UserLoggedOutHandler());
        
        // Paiement
        $eventBus->subscribe(PaymentInitiatedEvent::class, new PaymentInitiatedHandler());
        $eventBus->subscribe(PaymentCompletedEvent::class, new PaymentCompletedHandler());
        $eventBus->subscribe(PaymentFailedEvent::class, new PaymentFailedHandler());
        
        // Intégration
        $eventBus->subscribe(IntegrationCreatedEvent::class, new IntegrationCreatedHandler());
        $eventBus->subscribe(IntegrationDeployedEvent::class, new IntegrationDeployedHandler());
        $eventBus->subscribe(IntegrationFailedEvent::class, new IntegrationFailedHandler());
        
        // Monitoring
        $eventBus->subscribe(AlertTriggeredEvent::class, new AlertTriggeredHandler());
        $eventBus->subscribe(MetricsCollectedEvent::class, new MetricsCollectedHandler());
        $eventBus->subscribe(HealthCheckFailedEvent::class, new HealthCheckFailedHandler());
    }
}
```

### Références aux ADR du Projet Gyroscops Cloud

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du Gyroscops Cloud :
- **HIVE008** : Event Collaboration - Collaboration basée sur les événements
- **HIVE009** : Message Buses - Bus de messages pour les événements
- **HIVE040** : Enhanced Models with Property Access Patterns - Modèles enrichis pour les événements
- **HIVE041** : Cross-Cutting Concerns Architecture - Architecture des préoccupations transversales

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux comprendre les repositories et la persistance" 
    subtitle="Vous voulez voir comment gérer la persistance des données" 
    criteria="Développeur avec expérience,Besoin de comprendre la persistance,Architecture à définir,Patterns de stockage à choisir" 
    time="25-35 minutes" 
    chapter="9" 
    chapter-title="Repositories et Persistance" 
    chapter-url="/chapitres/fondamentaux/chapitre-09-repositories-persistance/" 
  >}}}
  
  {{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux comprendre les patterns optionnels" 
    subtitle="Vous voulez voir les patterns avancés comme CQRS et Event Sourcing" 
    criteria="Équipe très expérimentée,Besoin de patterns avancés,Complexité très élevée,Performance critique" 
    time="30-45 minutes" 
    chapter="15" 
    chapter-title="Event Sourcing - La Source de Vérité" 
    chapter-url="/chapitres/optionnels/chapitre-15-event-sourcing/" 
  >}}}
  
  {{< chapter-option 
    letter="C" 
    color="red" 
    title="Je veux comprendre l'architecture CQS" 
    subtitle="Vous voulez voir une alternative plus simple au CQRS" 
    criteria="Équipe expérimentée,Besoin d'une alternative au CQRS,Complexité élevée mais pas critique,Évolutivité importante" 
    time="25-35 minutes" 
    chapter="15" 
    chapter-title="Architecture CQS - Command Query Separation" 
    chapter-url="/chapitres/optionnels/chapitre-15-architecture-cqs/" 
  >}}}
  
  {{< chapter-option 
    letter="D" 
    color="purple" 
    title="Je veux comprendre les chapitres de stockage" 
    subtitle="Vous voulez voir comment implémenter la persistance selon différents patterns" 
    criteria="Équipe expérimentée,Besoin de comprendre la persistance,Patterns de stockage à choisir,Implémentation à faire" 
    time="30-45 minutes" 
    chapter="15" 
    chapter-title="Stockage SQL - Approche Classique" 
    chapter-url="/chapitres/stockage/chapitre-15-stockage-sql-classique/" 
  >}}}
  
{{< /chapter-nav >}}