---
title: "Event Sourcing - Stocker les Événements comme Source de Vérité"
description: "Découvrez Event Sourcing, le pattern qui stocke les événements métier comme source de vérité pour une traçabilité complète"
date: 2024-12-19
draft: false
weight: 2
type: "docs"
---

# 🎯 Event Sourcing - Stocker les Événements comme Source de Vérité

## 🌟 **Qu'est-ce que l'Event Sourcing ?**

**Event Sourcing** est un pattern architectural qui stocke les **événements métier** comme source de vérité, plutôt que l'état actuel des entités.

### **Le Principe Fondamental**

> **"L'état d'une entité est la conséquence de tous les événements qui lui sont arrivés"**

Au lieu de stocker l'état actuel, on stocke :
- **Tous les événements** qui ont modifié l'entité
- **L'ordre chronologique** de ces événements
- **Les métadonnées** associées à chaque événement

## 🏗️ **Event Sourcing dans Gyroscops**

### **Contexte Métier : Gestion des Abonnements**

Dans Gyroscops, un abonnement passe par plusieurs états :

#### **Événements Métier**
```php
// Événement : Abonnement créé
class SubscriptionCreated implements DomainEvent
{
    public function __construct(
        public readonly SubscriptionId $subscriptionId,
        public readonly PlanId $planId,
        public readonly CustomerId $customerId,
        public readonly DateTime $createdAt,
        public readonly BillingCycle $billingCycle
    ) {}
}

// Événement : Abonnement activé
class SubscriptionActivated implements DomainEvent
{
    public function __construct(
        public readonly SubscriptionId $subscriptionId,
        public readonly DateTime $activatedAt,
        public readonly DateTime $nextBillingDate
    ) {}
}

// Événement : Paiement traité
class PaymentProcessed implements DomainEvent
{
    public function __construct(
        public readonly SubscriptionId $subscriptionId,
        public readonly PaymentId $paymentId,
        public readonly Amount $amount,
        public readonly DateTime $processedAt
    ) {}
}

// Événement : Abonnement suspendu
class SubscriptionSuspended implements DomainEvent
{
    public function __construct(
        public readonly SubscriptionId $subscriptionId,
        public readonly string $reason,
        public readonly DateTime $suspendedAt
    ) {}
}
```

#### **Reconstruction de l'État**
```php
class Subscription
{
    private SubscriptionId $id;
    private SubscriptionStatus $status;
    private ?DateTime $nextBillingDate;
    private array $payments = [];
    
    public static function fromEvents(array $events): self
    {
        $subscription = new self();
        
        foreach ($events as $event) {
            $subscription->apply($event);
        }
        
        return $subscription;
    }
    
    private function apply(DomainEvent $event): void
    {
        match ($event::class) {
            SubscriptionCreated::class => $this->applySubscriptionCreated($event),
            SubscriptionActivated::class => $this->applySubscriptionActivated($event),
            PaymentProcessed::class => $this->applyPaymentProcessed($event),
            SubscriptionSuspended::class => $this->applySubscriptionSuspended($event),
        };
    }
    
    private function applySubscriptionCreated(SubscriptionCreated $event): void
    {
        $this->id = $event->subscriptionId;
        $this->status = SubscriptionStatus::PENDING;
        $this->nextBillingDate = $event->createdAt->add($event->billingCycle->interval());
    }
    
    private function applySubscriptionActivated(SubscriptionActivated $event): void
    {
        $this->status = SubscriptionStatus::ACTIVE;
        $this->nextBillingDate = $event->nextBillingDate;
    }
    
    private function applyPaymentProcessed(PaymentProcessed $event): void
    {
        $this->payments[] = $event->paymentId;
        $this->nextBillingDate = $this->nextBillingDate->add($this->billingCycle->interval());
    }
    
    private function applySubscriptionSuspended(SubscriptionSuspended $event): void
    {
        $this->status = SubscriptionStatus::SUSPENDED;
    }
}
```

## 🎯 **Avantages de l'Event Sourcing**

### **1. Traçabilité Complète**
- **Historique complet** : Tous les changements sont enregistrés
- **Audit trail** : Qui a fait quoi et quand
- **Debugging** : Possibilité de rejouer l'historique

### **2. Flexibilité Temporelle**
- **Time travel** : Voir l'état à n'importe quel moment
- **Replay** : Rejouer les événements pour tester
- **Debugging** : Comprendre comment on est arrivé à un état

### **3. Évolutivité**
- **Nouvelles projections** : Créer de nouvelles vues sans modifier le code existant
- **Migration** : Faciliter les migrations de données
- **Analytics** : Analyser l'historique des événements

### **4. Cohérence Événementielle**
- **Source de vérité unique** : Les événements sont la seule source de vérité
- **Intégrité** : Impossible de corrompre l'historique
- **Réconciliation** : Possibilité de détecter les incohérences

## 🔧 **Implémentation dans Gyroscops**

### **Structure des Dossiers**
```
src/Accounting/
├── Domain/
│   ├── Events/
│   │   ├── SubscriptionCreated.php
│   │   ├── SubscriptionActivated.php
│   │   ├── PaymentProcessed.php
│   │   └── SubscriptionSuspended.php
│   ├── Subscription.php
│   └── EventStore.php
├── Infrastructure/
│   ├── EventStore/
│   │   ├── DoctrineEventStore.php
│   │   └── EventStoreRepository.php
│   └── Projections/
│       ├── SubscriptionProjection.php
│       └── PaymentProjection.php
└── Application/
    ├── Command/
    │   └── ActivateSubscription/
    └── Query/
        └── GetSubscriptionHistory/
```

### **Event Store**
```php
interface EventStore
{
    public function append(StreamId $streamId, array $events, int $expectedVersion): void;
    public function getEvents(StreamId $streamId): array;
    public function getEventsFromVersion(StreamId $streamId, int $fromVersion): array;
}

class DoctrineEventStore implements EventStore
{
    public function append(StreamId $streamId, array $events, int $expectedVersion): void
    {
        $this->entityManager->transactional(function () use ($streamId, $events, $expectedVersion) {
            // Vérifier la version attendue
            $currentVersion = $this->getCurrentVersion($streamId);
            if ($currentVersion !== $expectedVersion) {
                throw new ConcurrencyException('Version mismatch');
            }
            
            // Enregistrer les événements
            foreach ($events as $event) {
                $this->persistEvent($streamId, $event);
            }
        });
    }
    
    public function getEvents(StreamId $streamId): array
    {
        return $this->eventRepository->findByStreamId($streamId);
    }
}
```

### **Projections**
```php
class SubscriptionProjection
{
    public function __construct(
        private SubscriptionQueryRepository $queryRepository
    ) {}
    
    public function handle(DomainEvent $event): void
    {
        match ($event::class) {
            SubscriptionCreated::class => $this->handleSubscriptionCreated($event),
            SubscriptionActivated::class => $this->handleSubscriptionActivated($event),
            PaymentProcessed::class => $this->handlePaymentProcessed($event),
        };
    }
    
    private function handleSubscriptionCreated(SubscriptionCreated $event): void
    {
        $this->queryRepository->createSubscriptionView(
            $event->subscriptionId,
            $event->planId,
            $event->customerId,
            SubscriptionStatus::PENDING,
            $event->createdAt
        );
    }
    
    private function handleSubscriptionActivated(SubscriptionActivated $event): void
    {
        $this->queryRepository->updateSubscriptionStatus(
            $event->subscriptionId,
            SubscriptionStatus::ACTIVE,
            $event->nextBillingDate
        );
    }
}
```

## 🚀 **Patterns Avancés avec Event Sourcing**

### **1. Snapshots**
```php
class SubscriptionSnapshot
{
    public function __construct(
        public readonly SubscriptionId $id,
        public readonly SubscriptionStatus $status,
        public readonly DateTime $nextBillingDate,
        public readonly int $version
    ) {}
}

class SnapshotService
{
    public function createSnapshot(Subscription $subscription): SubscriptionSnapshot
    {
        return new SubscriptionSnapshot(
            $subscription->id(),
            $subscription->status(),
            $subscription->nextBillingDate(),
            $subscription->version()
        );
    }
    
    public function restoreFromSnapshot(SubscriptionSnapshot $snapshot, array $events): Subscription
    {
        $subscription = Subscription::fromSnapshot($snapshot);
        
        // Appliquer seulement les événements après le snapshot
        $eventsAfterSnapshot = array_filter(
            $events,
            fn($event) => $event->version() > $snapshot->version
        );
        
        foreach ($eventsAfterSnapshot as $event) {
            $subscription->apply($event);
        }
        
        return $subscription;
    }
}
```

### **2. Event Sourcing + CQRS**
- **Command Side** : Gère les événements et l'event store
- **Query Side** : Lit depuis les projections

### **3. Event Sourcing + API Platform**
- **Ressources** : Exposer les événements via l'API
- **Validation** : Valider les événements avant stockage

## ⚡ **Performance et Optimisation**

### **Optimisations de l'Event Store**
- **Indexation** : Index sur stream_id et version
- **Partitioning** : Partitionner par stream_id
- **Compression** : Compresser les anciens événements

### **Optimisations des Projections**
- **Snapshots** : Créer des snapshots réguliers
- **Projections asynchrones** : Traiter les projections en arrière-plan
- **Cache** : Mettre en cache les projections fréquentes

## 🎯 **Quand Utiliser l'Event Sourcing ?**

### **✅ Cas d'Usage Appropriés**
- **Audit critique** : Besoin de traçabilité complète
- **Compliance** : Réglementations strictes
- **Analytics** : Besoin d'analyser l'historique
- **Debugging complexe** : Systèmes complexes à déboguer

### **❌ Cas d'Usage Inappropriés**
- **Applications simples** : CRUD basique
- **Performance critique** : Besoins de performance extrême
- **Équipe inexpérimentée** : Complexité élevée
- **Prototypage** : Développement rapide

## 🔄 **Migration vers Event Sourcing**

### **Étape 1 : Identifier les Événements Métier**
- Lister tous les changements d'état
- Grouper par contexte métier

### **Étape 2 : Créer l'Event Store**
- Choisir la technologie de stockage
- Implémenter les interfaces

### **Étape 3 : Migrer les Agrégats**
- Convertir les entités en événements
- Implémenter la reconstruction

### **Étape 4 : Créer les Projections**
- Créer les vues de lecture
- Implémenter la synchronisation

## 📊 **Métriques et Monitoring**

### **Métriques Event Store**
- Nombre d'événements par seconde
- Taille des streams
- Temps de reconstruction

### **Métriques Projections**
- Délai de traitement des événements
- Taille des projections
- Performance des requêtes

## 🎯 **Prochaines Étapes**

Maintenant que vous comprenez l'Event Sourcing, explorez :

1. **[CQRS](/concept/cqrs/)** : Séparer les commandes des requêtes
2. **[Repositories](/concept/repositories/)** : Patterns de persistance
3. **[Implémentation Event Sourcing](/chapitres/optionnels/chapitre-11-event-sourcing/)** : Guide d'implémentation complet

---

*Event Sourcing transforme la façon dont nous pensons la persistance. Dans Gyroscops, il nous a permis de gérer la complexité métier tout en gardant une traçabilité complète de tous les changements.*
