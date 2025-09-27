---
title: "Chapitre 16 : Stockage SQL - Approche Classique"
description: "Maîtriser le stockage SQL classique avec Doctrine et API Platform"
date: 2024-12-19
draft: true
type: "docs"
weight: 16
---

## 🎯 Objectif de ce Chapitre

### Mon Problème : Comment Stocker des Données de Façon Simple et Efficace ?

**Voici ce qui s'est passé avec Gyroscops** : J'avais besoin de stocker des données de base de données de façon simple. Pas de complexité, pas de CQRS, pas d'Event Sourcing. Juste une approche classique qui fonctionne bien.

**Mais attendez...** Comment structurer les entités ? Comment gérer les relations ? Comment optimiser les requêtes ? Comment intégrer avec API Platform ?

**Soudain, je réalisais que l'approche classique était parfaite !** Il me fallait une méthode simple et efficace.

### Stockage SQL Classique : Mon Guide Pratique

Le stockage SQL classique m'a permis de :
- **Développer** rapidement
- **Maintenir** facilement
- **Comprendre** simplement
- **Évoluer** progressivement

## Qu'est-ce que le Stockage SQL Classique ?

### Le Concept Fondamental

Le stockage SQL classique consiste à utiliser une base de données relationnelle avec des entités Doctrine classiques. **L'idée** : Une entité = une table, avec des relations simples et des requêtes directes.

**Avec Gyroscops, voici comment j'ai structuré le stockage SQL classique** :

### Les 4 Piliers du Stockage SQL Classique

#### 1. **Entités Doctrine** - Modèles de données simples

**Voici comment j'ai implémenté les entités avec Gyroscops** :

**Entités Basiques** :
- Propriétés publiques ou privées avec getters/setters
- Annotations Doctrine
- Relations simples
- Pas de logique métier complexe

**Exemples** :
- `Payment` (entité principale)
- `User` (entité utilisateur)
- `Organization` (entité organisation)

#### 2. **Repositories Doctrine** - Accès aux données

**Voici comment j'ai implémenté les repositories avec Gyroscops** :

**Repositories Simples** :
- Méthodes de base (find, findAll, save, delete)
- Requêtes personnalisées
- Pagination simple
- Pas de complexité CQRS

#### 3. **API Platform** - Exposition des données

**Voici comment j'ai intégré API Platform avec Gyroscops** :

**Ressources API** :
- Entités exposées directement
- Opérations CRUD automatiques
- Filtres et pagination
- Documentation automatique

#### 4. **Migrations** - Évolution du schéma

**Voici comment j'ai géré les migrations avec Gyroscops** :

**Migrations Doctrine** :
- Évolution du schéma
- Données de test
- Rollback possible
- Versioning des changements

## Comment Implémenter le Stockage SQL Classique

### 1. **Créer les Entités**

**Avec Gyroscops** : J'ai créé les entités :

```php
// ✅ Entité Payment Hive (Projet Hive)
#[Entity]
#[Table(name: 'payments')]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete()
    ]
)]
final class Payment
{
    #[Id]
    #[Column(type: 'uuid')]
    #[GeneratedValue(strategy: 'NONE')]
    public string $id;
    
    #[Column(type: 'uuid')]
    public string $organizationId;
    
    #[Column(type: 'string', length: 255)]
    public string $customerName;
    
    #[Column(type: 'string', length: 255)]
    public string $customerEmail;
    
    #[Column(type: 'decimal', precision: 10, scale: 2)]
    public string $amount;
    
    #[Column(type: 'string', length: 3)]
    public string $currency;
    
    #[Column(type: 'string', length: 50)]
    public string $status;
    
    #[Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt;
    
    #[Column(type: 'uuid')]
    public string $createdBy;
    
    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->createdAt = new \DateTimeImmutable();
    }
    
    // Getters et setters...
}
```

**Résultat** : Entité simple et claire.

### 2. **Créer les Repositories**

**Avec Gyroscops** : J'ai créé les repositories :

```php
// ✅ Repository Payment Hive (Projet Hive)
final class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }
    
    public function save(Payment $payment): void
    {
        $this->getEntityManager()->persist($payment);
        $this->getEntityManager()->flush();
    }
    
    public function findByOrganization(string $organizationId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.organizationId = :organizationId')
            ->setParameter('organizationId', $organizationId)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    public function countByOrganization(string $organizationId): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.organizationId = :organizationId')
            ->setParameter('organizationId', $organizationId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
```

**Résultat** : Repository simple et efficace.

## Les Avantages du Stockage SQL Classique

### 1. **Simplicité**

**Avec Gyroscops** : Le stockage SQL classique m'a donné de la simplicité :
- Code simple et compréhensible
- Pas de complexité inutile
- Développement rapide
- Maintenance facile

**Résultat** : Développement et maintenance simplifiés.

### 2. **Performance**

**Avec Gyroscops** : Le stockage SQL classique m'a donné de bonnes performances :
- Requêtes optimisées
- Indexation efficace
- Cache Doctrine
- Performance prévisible

**Résultat** : Performances satisfaisantes.

### 3. **Évolutivité**

**Avec Gyroscops** : Le stockage SQL classique m'a permis d'évoluer :
- Ajout de nouvelles entités
- Modification du schéma
- Migration des données
- Évolution progressive

**Résultat** : Évolution facilitée.

### 4. **Intégration**

**Avec Gyroscops** : Le stockage SQL classique s'intègre bien :
- API Platform automatique
- Documentation générée
- Tests simplifiés
- Outils standard

**Résultat** : Intégration facilitée.

## Les Inconvénients du Stockage SQL Classique

### 1. **Limitations de Performance**

**Avec Gyroscops** : Le stockage SQL classique a des limitations :
- Requêtes complexes lentes
- Jointures coûteuses
- Scaling vertical limité
- Performance non optimale

**Résultat** : Performance limitée pour des cas complexes.

### 2. **Cohérence des Données**

**Avec Gyroscops** : Le stockage SQL classique peut avoir des problèmes de cohérence :
- Pas d'audit trail
- Pas d'historique des changements
- Debugging difficile
- Traçabilité limitée

**Résultat** : Cohérence des données limitée.

## Les Pièges à Éviter

### 1. **Entités Trop Complexes**

**❌ Mauvais** : Entités avec trop de logique métier
**✅ Bon** : Entités simples avec getters/setters

**Pourquoi c'est important ?** Les entités complexes sont difficiles à maintenir.

### 2. **Requêtes N+1**

**❌ Mauvais** : Requêtes dans des boucles
**✅ Bon** : Eager loading ou requêtes optimisées

**Pourquoi c'est crucial ?** Les requêtes N+1 tuent les performances.

## 🏗️ Implémentation Concrète dans le Projet Hive

### Stockage SQL Classique Appliqué à Hive

Le projet Hive applique concrètement les principes du stockage SQL classique à travers son architecture et ses ADR (Architecture Decision Records). Voici comment :

#### Configuration Doctrine Hive

```php
// ✅ Configuration Doctrine Hive (Projet Hive)
final class HiveDoctrineConfiguration
{
    public function configureDoctrine(ContainerBuilder $container): void
    {
        // Configuration des entités
        $container->setParameter('doctrine.dbal.connections.default.url', $_ENV['DATABASE_URL']);
        
        // Configuration des repositories
        $container->register(PaymentRepository::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration des services
        $container->register(PaymentService::class)
            ->setAutowired(true)
            ->setPublic(true);
    }
}
```

### Références aux ADR du Projet Hive

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du projet Hive :
- **HIVE012** : Database Repositories - Repositories de base de données
- **HIVE016** : Database Migrations - Migrations de base de données
- **HIVE033** : Hydrator Implementation Patterns - Patterns d'hydratation
- **HIVE040** : Enhanced Models with Property Access Patterns - Modèles enrichis

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux comprendre le stockage SQL CQS" 
    subtitle="Vous voulez voir une approche avec séparation des commandes et requêtes" 
    criteria="Équipe expérimentée,Besoin d'optimiser les performances,Séparation des responsabilités importante,Évolutivité importante" 
    time="25-35 minutes" 
    chapter="17" 
    chapter-title="Stockage SQL - Approche CQS" 
    chapter-url="/chapitres/stockage/chapitre-51-stockage-sql-cqs/" 
  >}}
  
  {{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux comprendre le stockage SQL CQRS" 
    subtitle="Vous voulez voir une approche avec séparation complète des modèles" 
    criteria="Équipe très expérimentée,Besoin de performance maximale,Complexité élevée acceptable,Scalabilité critique" 
    time="30-45 minutes" 
    chapter="17" 
    chapter-title="Stockage SQL - Approche CQRS" 
    chapter-url="/chapitres/stockage/chapitre-51-stockage-sql-cqrs/" 
  >}}
  
  {{< chapter-option 
    letter="C" 
    color="red" 
    title="Je veux comprendre le stockage API" 
    subtitle="Vous voulez voir comment intégrer des APIs externes" 
    criteria="Équipe expérimentée,Besoin d'intégrer des services externes,Données distribuées,Intégrations multiples" 
    time="25-35 minutes" 
    chapter="18" 
    chapter-title="Stockage API - Approche Classique" 
    chapter-url="/chapitres/stockage/chapitre-51-stockage-api-classique/" 
  >}}
  
{{< /chapter-nav >}}