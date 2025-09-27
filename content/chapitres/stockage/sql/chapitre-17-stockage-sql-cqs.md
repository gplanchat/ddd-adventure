---
title: "Chapitre 17 : Stockage SQL - Approche CQS"
description: "Optimiser le stockage SQL avec la séparation des commandes et requêtes"
date: 2024-12-19
draft: true
type: "docs"
weight: 17
---

## 🎯 Objectif de ce Chapitre

### Mon Problème : Comment Optimiser les Performances de Lecture et d'Écriture ?

**Voici ce qui s'est passé avec Gyroscops** : J'avais un stockage SQL classique qui fonctionnait, mais les performances n'étaient pas optimales. Les requêtes de lecture étaient lentes à cause des jointures complexes, et les écritures étaient bloquées par les verrous de lecture.

**Mais attendez...** Comment séparer les opérations de lecture et d'écriture ? Comment optimiser chaque côté ? Comment maintenir la cohérence ?

**Soudain, je réalisais que CQS était la solution !** Il me fallait une approche structurée pour optimiser les performances.

### Stockage SQL CQS : Mon Guide Optimisé

Le stockage SQL CQS m'a permis de :
- **Optimiser** les performances de lecture
- **Optimiser** les performances d'écriture
- **Séparer** clairement les responsabilités
- **Améliorer** la maintenabilité

## Qu'est-ce que le Stockage SQL CQS ?

### Le Concept Fondamental

Le stockage SQL CQS consiste à séparer les opérations de lecture et d'écriture dans des repositories différents. **L'idée** : Un repository pour les commandes (écriture) et un autre pour les requêtes (lecture), chacun optimisé pour son usage.

**Avec Gyroscops, voici comment j'ai structuré le stockage SQL CQS** :

### Les 4 Piliers du Stockage SQL CQS

#### 1. **Repository de Commande** - Optimisé pour l'écriture

**Voici comment j'ai implémenté le repository de commande avec Gyroscops** :

**Commandes Optimisées** :
- Opérations d'écriture uniquement
- Transactions courtes
- Indexation optimisée pour l'écriture
- Pas de jointures complexes

**Exemples** :
- `save()`, `update()`, `delete()`
- `processPayment()`, `approveOrder()`
- `createUser()`, `updateProfile()`

#### 2. **Repository de Requête** - Optimisé pour la lecture

**Voici comment j'ai implémenté le repository de requête avec Gyroscops** :

**Requêtes Optimisées** :
- Opérations de lecture uniquement
- Jointures optimisées
- Indexation optimisée pour la lecture
- Cache des requêtes fréquentes

**Exemples** :
- `findById()`, `findAll()`, `findBy()`
- `getStatistics()`, `getReport()`
- `search()`, `filter()`

## Comment Implémenter le Stockage SQL CQS

### 1. **Créer les Repositories de Commande**

**Avec Gyroscops** : J'ai créé les repositories de commande :

```php
// ✅ Repository de Commande Payment Hive (Projet Hive)
final class PaymentCommandRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}
    
    public function save(Payment $payment): void
    {
        $this->entityManager->persist($payment);
        $this->entityManager->flush();
        
        $this->logger->info('Payment saved', [
            'payment_id' => $payment->getId()
        ]);
    }
    
    public function update(Payment $payment): void
    {
        $this->entityManager->flush();
        
        $this->logger->info('Payment updated', [
            'payment_id' => $payment->getId()
        ]);
    }
    
    public function processPayment(Payment $payment): void
    {
        $payment->setStatus('processing');
        $this->entityManager->flush();
        
        $this->logger->info('Payment processing started', [
            'payment_id' => $payment->getId()
        ]);
    }
}
```

**Résultat** : Repository de commande optimisé pour l'écriture.

### 2. **Créer les Repositories de Requête**

**Avec Gyroscops** : J'ai créé les repositories de requête :

```php
// ✅ Repository de Requête Payment Hive (Projet Hive)
final class PaymentQueryRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}
    
    public function findById(string $id): ?PaymentView
    {
        $sql = 'SELECT p.*, o.name as organization_name, u.first_name, u.last_name 
                FROM payment_views p
                LEFT JOIN organizations o ON p.organization_id = o.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.id = ?';
        
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute([$id]);
        
        $row = $stmt->fetchAssociative();
        if (!$row) {
            return null;
        }
        
        return new PaymentView(
            $row['id'],
            $row['organization_id'],
            $row['organization_name'],
            $row['customer_name'],
            $row['customer_email'],
            $row['amount'],
            $row['currency'],
            $row['status'],
            $row['created_at'],
            $row['created_by'],
            $row['first_name'] . ' ' . $row['last_name']
        );
    }
    
    public function getStatistics(string $organizationId): PaymentStatistics
    {
        $sql = 'SELECT 
                    COUNT(*) as total_payments,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_payments,
                    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_payments,
                    SUM(amount) as total_amount,
                    AVG(amount) as average_amount
                FROM payment_views 
                WHERE organization_id = ?';
        
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute([$organizationId]);
        
        $row = $stmt->fetchAssociative();
        
        return new PaymentStatistics(
            (int) $row['total_payments'],
            (int) $row['pending_payments'],
            (int) $row['completed_payments'],
            $row['total_amount'],
            $row['average_amount']
        );
    }
}
```

**Résultat** : Repository de requête optimisé pour la lecture.

## Les Avantages du Stockage SQL CQS

### 1. **Performance Optimisée**

**Avec Gyroscops** : Le stockage SQL CQS m'a donné des performances optimisées :
- Requêtes de lecture optimisées
- Écritures optimisées
- Indexation spécialisée
- Cache adapté

**Résultat** : Performances améliorées.

### 2. **Séparation Claire**

**Avec Gyroscops** : Le stockage SQL CQS m'a donné une séparation claire :
- Responsabilités distinctes
- Code plus lisible
- Maintenance facilitée
- Tests simplifiés

**Résultat** : Code plus maintenable.

### 3. **Évolutivité**

**Avec Gyroscops** : Le stockage SQL CQS m'a permis d'évoluer :
- Évolution indépendante des côtés
- Optimisations spécialisées
- Ajout de fonctionnalités
- Refactoring facilité

**Résultat** : Évolution facilitée.

## Les Inconvénients du Stockage SQL CQS

### 1. **Complexité Accrue**

**Avec Gyroscops** : Le stockage SQL CQS a ajouté de la complexité :
- Plus de classes et interfaces
- Séparation à maintenir
- Coordination nécessaire
- Courbe d'apprentissage

**Résultat** : Architecture plus complexe.

### 2. **Duplication de Code**

**Avec Gyroscops** : Le stockage SQL CQS peut créer de la duplication :
- Logique similaire dans les deux côtés
- Validation dupliquée
- Mapping dupliqué
- Maintenance double

**Résultat** : Code dupliqué à maintenir.

## Les Pièges à Éviter

### 1. **Mélanger Commandes et Requêtes**

**❌ Mauvais** : Une méthode qui fait les deux
**✅ Bon** : Séparation claire des responsabilités

**Pourquoi c'est important ?** CQS perd son sens si on mélange.

### 2. **Duplication Excessive**

**❌ Mauvais** : Code dupliqué partout
**✅ Bon** : Extraction des parties communes

**Pourquoi c'est crucial ?** La duplication complique la maintenance.

## 🏗️ Implémentation Concrète dans le Projet Hive

### Stockage SQL CQS Appliqué à Hive

Le projet Hive applique concrètement les principes du stockage SQL CQS à travers son architecture et ses ADR (Architecture Decision Records). Voici comment :

#### Configuration CQS Hive

```php
// ✅ Configuration CQS Hive (Projet Hive)
final class HiveCQSConfiguration
{
    public function configureServices(ContainerBuilder $container): void
    {
        // Repositories de Commande
        $container->register(PaymentCommandRepository::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Repositories de Requête
        $container->register(PaymentQueryRepository::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Services CQS
        $container->register(PaymentCQSService::class)
            ->setAutowired(true)
            ->setPublic(true);
    }
}
```

### Références aux ADR du Projet Hive

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du projet Hive :
- **HIVE006** : Query Models for API Platform - Modèles de requête
- **HIVE007** : Command Models for API Platform - Modèles de commande
- **HIVE012** : Database Repositories - Repositories de base de données
- **HIVE040** : Enhanced Models with Property Access Patterns - Modèles enrichis

{{< chapter-nav >}}
  {{{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux comprendre le stockage SQL CQRS" 
    subtitle="Vous voulez voir une approche avec séparation complète des modèles" 
    criteria="Équipe très expérimentée,Besoin de performance maximale,Complexité élevée acceptable,Scalabilité critique" 
    time="30-45 minutes" 
    chapter="17" 
    chapter-title="Stockage SQL - Approche CQRS" 
    chapter-url="/chapitres/stockage/chapitre-51-stockage-sql-cqrs/" 
  >}}}}
  
  {{{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux comprendre le stockage API" 
    subtitle="Vous voulez voir comment intégrer des APIs externes" 
    criteria="Équipe expérimentée,Besoin d'intégrer des services externes,Données distribuées,Intégrations multiples" 
    time="25-35 minutes" 
    chapter="18" 
    chapter-title="Stockage API - Approche Classique" 
    chapter-url="/chapitres/stockage/chapitre-51-stockage-api-classique/" 
  >}}}}
  
{{< /chapter-nav >}}