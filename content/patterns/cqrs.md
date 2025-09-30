---
title: "CQRS - Command Query Responsibility Segregation"
description: "Découvrez CQRS, le pattern qui sépare les commandes des requêtes pour une architecture plus claire et performante"
date: 2024-12-19
draft: false
weight: 1
type: "docs"
---

# 🎯 CQRS - Command Query Responsibility Segregation

## 🌟 **Qu'est-ce que CQRS ?**

**CQRS** (Command Query Responsibility Segregation) est un pattern architectural qui sépare clairement les opérations de **lecture** (Query) des opérations d'**écriture** (Command) dans une application.

### **Le Principe Fondamental**

> **"Une méthode ne devrait jamais retourner de valeur et modifier l'état de l'objet"** - Bertrand Meyer

CQRS pousse ce principe à l'extrême en créant deux modèles distincts :
- **Modèle de Commande** : Pour les opérations qui modifient l'état
- **Modèle de Requête** : Pour les opérations qui lisent les données

## 🏗️ **Architecture CQRS dans Gyroscops**

### **Contexte Métier : Gestion des Paiements**

Dans Gyroscops, nous gérons des paiements avec des besoins très différents :

#### **Côté Commande (Write)**
```php
// Commande : Créer un paiement
class CreatePaymentCommand
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly Amount $amount,
        public readonly PaymentMethod $method,
        public readonly CustomerId $customerId
    ) {}
}

// Handler : Traiter la commande
class CreatePaymentHandler
{
    public function __invoke(CreatePaymentCommand $command): void
    {
        $payment = Payment::create(
            $command->paymentId,
            $command->amount,
            $command->method,
            $command->customerId
        );
        
        $this->paymentRepository->save($payment);
        $this->eventBus->dispatch(new PaymentCreated($payment));
    }
}
```

#### **Côté Requête (Read)**
```php
// Requête : Obtenir les paiements d'un client
class GetCustomerPaymentsQuery
{
    public function __construct(
        public readonly CustomerId $customerId,
        public readonly ?DateRange $dateRange = null
    ) {}
}

// Handler : Exécuter la requête
class GetCustomerPaymentsHandler
{
    public function __invoke(GetCustomerPaymentsQuery $query): array
    {
        return $this->paymentQueryRepository->findByCustomer(
            $query->customerId,
            $query->dateRange
        );
    }
}
```

## 🎯 **Avantages de CQRS**

### **1. Séparation des Responsabilités**
- **Commandes** : Focus sur la logique métier et la validation
- **Requêtes** : Focus sur l'optimisation des performances de lecture

### **2. Optimisation Indépendante**
- **Write Model** : Optimisé pour la cohérence et les invariants métier
- **Read Model** : Optimisé pour les performances et la flexibilité

### **3. Scalabilité**
- Possibilité de scaler séparément les lectures et les écritures
- Utilisation de technologies différentes pour chaque côté

### **4. Évolutivité**
- Modification des requêtes sans impact sur les commandes
- Ajout de nouvelles vues sans affecter la logique métier

## 🔧 **Implémentation dans Gyroscops**

### **Structure des Dossiers**
```
src/Accounting/
├── Application/
│   ├── Command/
│   │   ├── CreatePayment/
│   │   │   ├── CreatePaymentCommand.php
│   │   │   ├── CreatePaymentHandler.php
│   │   │   └── CreatePaymentValidator.php
│   │   └── ProcessRefund/
│   └── Query/
│       ├── GetPaymentDetails/
│       │   ├── GetPaymentDetailsQuery.php
│       │   ├── GetPaymentDetailsHandler.php
│       │   └── PaymentDetailsView.php
│       └── ListCustomerPayments/
├── Domain/
│   ├── Payment.php (Write Model)
│   └── PaymentDetails.php (Read Model)
└── Infrastructure/
    ├── PaymentRepository.php (Write)
    └── PaymentQueryRepository.php (Read)
```

### **Exemple Concret : Gestion des Abonnements**

#### **Commande : Créer un Abonnement**
```php
class CreateSubscriptionCommand
{
    public function __construct(
        public readonly SubscriptionId $subscriptionId,
        public readonly PlanId $planId,
        public readonly CustomerId $customerId,
        public readonly BillingCycle $billingCycle
    ) {}
}

class CreateSubscriptionHandler
{
    public function __invoke(CreateSubscriptionCommand $command): void
    {
        // Validation métier
        $this->validateSubscriptionCreation($command);
        
        // Création de l'agrégat
        $subscription = Subscription::create(
            $command->subscriptionId,
            $command->planId,
            $command->customerId,
            $command->billingCycle
        );
        
        // Persistance
        $this->subscriptionRepository->save($subscription);
        
        // Événement
        $this->eventBus->dispatch(new SubscriptionCreated($subscription));
    }
}
```

#### **Requête : Obtenir les Détails d'un Abonnement**
```php
class GetSubscriptionDetailsQuery
{
    public function __construct(
        public readonly SubscriptionId $subscriptionId
    ) {}
}

class GetSubscriptionDetailsHandler
{
    public function __invoke(GetSubscriptionDetailsQuery $query): SubscriptionDetailsView
    {
        return $this->subscriptionQueryRepository->findDetails($query->subscriptionId);
    }
}

class SubscriptionDetailsView
{
    public function __construct(
        public readonly SubscriptionId $id,
        public readonly string $planName,
        public readonly string $customerName,
        public readonly string $status,
        public readonly DateTime $startDate,
        public readonly ?DateTime $endDate,
        public readonly Amount $monthlyPrice,
        public readonly array $features
    ) {}
}
```

## 🚀 **Patterns Avancés avec CQRS**

### **1. Event Sourcing + CQRS**
- Les commandes génèrent des événements
- Les requêtes lisent depuis les projections

### **2. CQRS avec Projections**
- Projections dénormalisées pour les requêtes
- Mise à jour asynchrone des vues

### **3. CQRS avec API Platform**
- Ressources séparées pour Command et Query
- Validation automatique des DTOs

## ⚡ **Performance et Optimisation**

### **Optimisations Côté Lecture**
- **Indexation** : Index optimisés pour les requêtes fréquentes
- **Cache** : Mise en cache des vues fréquemment consultées
- **Dénormalisation** : Données pré-calculées pour éviter les jointures

### **Optimisations Côté Écriture**
- **Validation** : Validation métier centralisée
- **Transactions** : Gestion des transactions optimisée
- **Événements** : Publication asynchrone des événements

## 🎯 **Quand Utiliser CQRS ?**

### **✅ Cas d'Usage Appropriés**
- **Complexité métier élevée** : Beaucoup de règles métier
- **Besoins de lecture/écriture différents** : Requêtes complexes vs écritures simples
- **Performance critique** : Besoins de performance différents
- **Équipes séparées** : Équipes différentes pour read/write

### **❌ Cas d'Usage Inappropriés**
- **Applications simples** : CRUD basique
- **Cohérence forte requise** : Besoin de cohérence immédiate
- **Équipe unique** : Une seule équipe pour tout
- **Prototypage** : Développement rapide

## 🔄 **Migration vers CQRS**

### **Étape 1 : Identifier les Commandes**
- Lister toutes les opérations qui modifient l'état
- Grouper par contexte métier

### **Étape 2 : Identifier les Requêtes**
- Lister toutes les opérations de lecture
- Analyser les patterns d'accès

### **Étape 3 : Séparer les Modèles**
- Créer des modèles distincts
- Migrer progressivement

### **Étape 4 : Optimiser**
- Optimiser chaque côté indépendamment
- Mesurer les performances

## 📊 **Métriques et Monitoring**

### **Métriques Côté Commande**
- Nombre de commandes par seconde
- Temps de traitement moyen
- Taux d'erreur

### **Métriques Côté Requête**
- Temps de réponse des requêtes
- Utilisation du cache
- Charge des projections

## 🎯 **Prochaines Étapes**

Maintenant que vous comprenez CQRS, explorez :

1. **[Event Sourcing](/concept/event-sourcing/)** : Stocker les événements comme source de vérité
2. **[Repositories](/concept/repositories/)** : Patterns de persistance
3. **[Implémentation CQRS](/chapitres/optionnels/chapitre-13-architecture-cqrs/)** : Guide d'implémentation complet

---

*CQRS n'est pas une solution universelle, mais un outil puissant pour les applications complexes. Dans Gyroscops, il nous a permis de gérer efficacement la complexité métier tout en optimisant les performances.*
