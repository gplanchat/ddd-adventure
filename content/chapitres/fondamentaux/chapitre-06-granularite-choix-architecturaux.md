---
title: "Chapitre 6 : Granularité des Choix Architecturaux"
description: "Comprendre comment choisir l'architecture au bon niveau pour maintenir la cohérence et éviter la surcharge cognitive"
date: 2024-12-19
draft: true
type: "docs"
weight: 6
---

## 🎯 Objectif de ce Chapitre

### Mon Problème : Comment Choisir l'Architecture au Bon Niveau ?

**Voici ce qui s'est passé avec Gyroscops** : J'avais compris la complexité et choisi les patterns appropriés. **Parfait !** J'avais une vision claire de l'architecture.

**Mais attendez...** Quand j'ai voulu appliquer ces patterns, j'étais perdu. CQRS partout ? Event Sourcing pour tout ? Architecture classique pour certains composants ? Comment décider au niveau de chaque composant ?

**Soudain, je réalisais que j'appliquais les patterns de manière uniforme !** Il me fallait un cadre pour choisir l'architecture au bon niveau de granularité.

### La Granularité : Mon Principe de Cohérence

La granularité des choix architecturaux m'a permis de :
- **Choisir** l'architecture appropriée à chaque niveau
- **Maintenir** la cohérence dans le système
- **Éviter** la surcharge cognitive
- **Optimiser** l'effort de développement

## Qu'est-ce que la Granularité Architecturale ?

### Le Concept Fondamental

La granularité architecturale est le niveau auquel on applique les choix architecturaux. **L'idée** : On peut choisir l'architecture globalement, par Bounded Context, ou par Agrégat selon les besoins.

**Avec Gyroscops, voici comment j'ai appliqué cette granularité** :

### Les 3 Niveaux de Granularité

#### 1. **Choix Globaux** - L'Architecture Générale

**Exemple concret avec Gyroscops** :
- **Architecture générale** : Microservices avec API Gateway
- **Communication** : Événements asynchrones
- **Stockage** : Bases de données par service
- **Déploiement** : Containers avec Kubernetes

**Pourquoi c'est important ?** Les choix globaux définissent l'architecture générale du système.

#### 2. **Choix par Bounded Context** - L'Architecture par Domaine

**Exemple concret avec Gyroscops** :
- **Authentication** : Architecture classique (CRUD simple)
- **Accounting** : CQRS avec Event Sourcing (règles complexes)
- **Cloud Management** : Architecture classique (gestion d'infrastructure)
- **GenAI** : Architecture événementielle (traitement asynchrone)

**Pourquoi c'est crucial ?** Chaque Bounded Context a ses propres besoins et contraintes.

#### 3. **Choix par Agrégat** - L'Architecture Fine

**Exemple concret avec Gyroscops** :
- **User** : Architecture classique (CRUD simple)
- **Payment** : CQRS avec Event Sourcing (règles complexes)
- **Workflow** : Architecture événementielle (traitement asynchrone)
- **Integration** : Architecture classique (gestion d'état)

**Pourquoi c'est essentiel ?** Chaque Agrégat a ses propres caractéristiques et besoins.

## Mon Processus de Décision avec Gyroscops

### Étape 1 : Définir l'Architecture Globale

**Voici comment j'ai défini l'architecture globale de Gyroscops** :

**Contraintes globales** :
- **Équipe** : 8 développeurs
- **Performance** : 1000 requêtes/seconde
- **Disponibilité** : 99.9%
- **Évolutivité** : Croissance de 50% par an

**Architecture globale choisie** :
- **Microservices** : Équipe autonome, déploiement indépendant
- **API Gateway** : Point d'entrée unique, sécurité centralisée
- **Event Bus** : Communication asynchrone, découplage
- **Containers** : Déploiement standardisé, scalabilité

**Résultat** : Architecture globale cohérente et évolutive.

### Étape 2 : Choisir l'Architecture par Bounded Context

**Voici comment j'ai choisi l'architecture de chaque Bounded Context** :

#### Authentication Context
**Caractéristiques** :
- CRUD simple (inscription, connexion, profil)
- Règles métier basiques
- Peu d'intégrations
- Équipe : 1 développeur

**Architecture choisie** : Classique
- Modèle classique avec ORM
- Base de données relationnelle
- API REST simple

**Justification** : Complexité faible, pas besoin de patterns avancés.

#### Accounting Context
**Caractéristiques** :
- Règles métier très complexes
- Audit complet nécessaire
- Conformité réglementaire
- Équipe : 2 développeurs

**Architecture choisie** : CQRS avec Event Sourcing
- Séparation lecture/écriture
- Stockage des événements
- Projections multiples

**Justification** : Complexité élevée, audit critique.

#### Cloud Management Context
**Caractéristiques** :
- Gestion d'infrastructure
- Règles métier moyennes
- Intégrations multiples
- Équipe : 2 développeurs

**Architecture choisie** : Classique avec événements
- Modèle classique avec ORM
- Event Bus pour les notifications
- Base de données relationnelle

**Justification** : Complexité moyenne, besoin d'événements.

#### GenAI Context
**Caractéristiques** :
- Traitement asynchrone
- Règles métier moyennes
- Intégrations multiples
- Équipe : 3 développeurs

**Architecture choisie** : Événementielle
- Event Bus pour le traitement
- Queues pour l'asynchrone
- Base de données relationnelle

**Justification** : Traitement asynchrone, besoin d'événements.

### Étape 3 : Choisir l'Architecture par Agrégat

**Voici comment j'ai choisi l'architecture de chaque Agrégat** :

#### User Aggregate
**Caractéristiques** :
- CRUD simple
- Règles métier basiques
- Pas d'intégrations complexes

**Architecture choisie** : Classique
- Modèle classique avec ORM
- Base de données relationnelle
- API REST simple

**Justification** : Complexité faible, pas besoin de patterns avancés.

#### Payment Aggregate
**Caractéristiques** :
- Règles métier très complexes
- Audit complet nécessaire
- Conformité réglementaire

**Architecture choisie** : CQRS avec Event Sourcing
- Séparation lecture/écriture
- Stockage des événements
- Projections multiples

**Justification** : Complexité élevée, audit critique.

#### Workflow Aggregate
**Caractéristiques** :
- Traitement asynchrone
- Règles métier moyennes
- Intégrations multiples

**Architecture choisie** : Événementielle
- Event Bus pour le traitement
- Queues pour l'asynchrone
- Base de données relationnelle

**Justification** : Traitement asynchrone, besoin d'événements.

#### Integration Aggregate
**Caractéristiques** :
- Gestion d'état
- Règles métier moyennes
- Intégrations multiples

**Architecture choisie** : Classique avec événements
- Modèle classique avec ORM
- Event Bus pour les notifications
- Base de données relationnelle

**Justification** : Complexité moyenne, besoin d'événements.

## Les Principes de Cohérence

### 1. **Principe de Cohérence Globale**

**Avec Gyroscops** : Tous les Bounded Contexts partagent la même architecture globale :
- Microservices avec API Gateway
- Communication par événements
- Déploiement en containers

**Pourquoi c'est important ?** La cohérence globale facilite la maintenance et l'évolution.

### 2. **Principe de Cohérence par Bounded Context**

**Avec Gyroscops** : Chaque Bounded Context a une architecture cohérente :
- Authentication : Architecture classique partout
- Accounting : CQRS avec Event Sourcing partout
- Cloud Management : Architecture classique avec événements partout
- GenAI : Architecture événementielle partout

**Pourquoi c'est crucial ?** La cohérence par Bounded Context facilite la compréhension et la maintenance.

### 3. **Principe de Cohérence par Agrégat**

**Avec Gyroscops** : Chaque Agrégat a une architecture cohérente :
- User : Architecture classique partout
- Payment : CQRS avec Event Sourcing partout
- Workflow : Architecture événementielle partout
- Integration : Architecture classique avec événements partout

**Pourquoi c'est essentiel ?** La cohérence par Agrégat facilite la compréhension et la maintenance.

## Les Pièges à Éviter

### 1. **Uniformité Excessive**

**❌ Mauvais** : CQRS partout, même pour les CRUD simples
**✅ Bon** : CQRS seulement là où c'est justifié

**Pourquoi c'est important ?** L'uniformité excessive complique inutilement le système.

### 2. **Incohérence Excessive**

**❌ Mauvais** : Chaque composant avec une architecture différente
**✅ Bon** : Architecture cohérente par niveau de granularité

**Pourquoi c'est crucial ?** L'incohérence excessive complique la maintenance.

### 3. **Granularité Inappropriée**

**❌ Mauvais** : Choisir l'architecture au niveau de chaque méthode
**✅ Bon** : Choisir l'architecture au niveau approprié (global, Bounded Context, Agrégat)

**Pourquoi c'est essentiel ?** La granularité inappropriée complique la décision.

### 4. **Ignorer l'Évolution**

**❌ Mauvais** : Architecture figée, pas d'évolution possible
**✅ Bon** : Architecture évolutive, adaptation selon les besoins

**Pourquoi c'est la clé ?** L'architecture doit évoluer avec les besoins.

## L'Évolution de la Granularité

### Phase 1 : Architecture Uniforme

**Avec Gyroscops** : Au début, j'ai appliqué la même architecture partout :
- Architecture classique pour tout
- Cohérence maximale
- Simplicité de maintenance

**Résultat** : Développement rapide, maintenance facile.

### Phase 2 : Différenciation par Bounded Context

**Avec Gyroscops** : Quand la complexité a augmenté, j'ai différencié par Bounded Context :
- Authentication : Architecture classique
- Accounting : CQRS avec Event Sourcing
- Cloud Management : Architecture classique avec événements
- GenAI : Architecture événementielle

**Résultat** : Architecture adaptée aux besoins de chaque domaine.

### Phase 3 : Différenciation par Agrégat

**Avec Gyroscops** : Quand la complexité a encore augmenté, j'ai différencié par Agrégat :
- User : Architecture classique
- Payment : CQRS avec Event Sourcing
- Workflow : Architecture événementielle
- Integration : Architecture classique avec événements

**Résultat** : Architecture optimisée pour chaque composant.

### Phase 4 : Architecture Hybride

**Avec Gyroscops** : Maintenant, j'ai une architecture hybride :
- Choix globaux : Microservices, Event Bus, Containers
- Choix par Bounded Context : Architecture adaptée au domaine
- Choix par Agrégat : Architecture adaptée au composant

**Résultat** : Architecture optimale à tous les niveaux.

## 🏗️ Implémentation Concrète dans le Projet Hive

### Granularité Appliquée à Hive

Le projet Hive applique concrètement les principes de granularité à travers son architecture et ses ADR (Architecture Decision Records). Voici comment :

#### Granularité par Domaine

```php
// ✅ Granularité par Domaine Hive (Projet Hive)
final class HiveDomainGranularity
{
    // Domaine d'Authentification
    public const AUTHENTICATION_DOMAIN = 'authentication';
    public const USER_MANAGEMENT_DOMAIN = 'user_management';
    public const AUTHORIZATION_DOMAIN = 'authorization';
    
    // Domaine de Paiement
    public const PAYMENT_DOMAIN = 'payment';
    public const SUBSCRIPTION_DOMAIN = 'subscription';
    public const BILLING_DOMAIN = 'billing';
    
    // Domaine d'Intégration
    public const INTEGRATION_DOMAIN = 'integration';
    public const WORKFLOW_DOMAIN = 'workflow';
    public const CONNECTOR_DOMAIN = 'connector';
    
    // Domaine de Monitoring
    public const MONITORING_DOMAIN = 'monitoring';
    public const METRICS_DOMAIN = 'metrics';
    public const ALERTING_DOMAIN = 'alerting';
}
```

#### Granularité par Bounded Context

```php
// ✅ Granularité par Bounded Context Hive (Projet Hive)
final class HiveBoundedContextGranularity
{
    // Bounded Context d'Authentification
    public const AUTHENTICATION_CONTEXT = 'authentication_context';
    public const USER_CONTEXT = 'user_context';
    public const PERMISSION_CONTEXT = 'permission_context';
    
    // Bounded Context de Paiement
    public const PAYMENT_CONTEXT = 'payment_context';
    public const SUBSCRIPTION_CONTEXT = 'subscription_context';
    public const BILLING_CONTEXT = 'billing_context';
    
    // Bounded Context d'Intégration
    public const INTEGRATION_CONTEXT = 'integration_context';
    public const WORKFLOW_CONTEXT = 'workflow_context';
    public const CONNECTOR_CONTEXT = 'connector_context';
}
```

#### Granularité par Agrégat

```php
// ✅ Granularité par Agrégat Hive (Projet Hive)
final class HiveAggregateGranularity
{
    // Agrégat d'Utilisateur
    public const USER_AGGREGATE = 'user_aggregate';
    public const USER_PROFILE_AGGREGATE = 'user_profile_aggregate';
    public const USER_PREFERENCES_AGGREGATE = 'user_preferences_aggregate';
    
    // Agrégat de Paiement
    public const PAYMENT_AGGREGATE = 'payment_aggregate';
    public const SUBSCRIPTION_AGGREGATE = 'subscription_aggregate';
    public const BILLING_AGGREGATE = 'billing_aggregate';
    
    // Agrégat d'Intégration
    public const INTEGRATION_AGGREGATE = 'integration_aggregate';
    public const WORKFLOW_AGGREGATE = 'workflow_aggregate';
    public const CONNECTOR_AGGREGATE = 'connector_aggregate';
}
```

#### Exemple Concret : Granularité des Repositories

```php
// ✅ Granularité des Repositories Hive (Projet Hive)
final class HiveRepositoryGranularity
{
    // Repository par Domaine
    public function getRepositoryByDomain(string $domain): RepositoryInterface
    {
        return match ($domain) {
            HiveDomainGranularity::AUTHENTICATION_DOMAIN => $this->authenticationRepository,
            HiveDomainGranularity::PAYMENT_DOMAIN => $this->paymentRepository,
            HiveDomainGranularity::INTEGRATION_DOMAIN => $this->integrationRepository,
            HiveDomainGranularity::MONITORING_DOMAIN => $this->monitoringRepository,
            default => throw new InvalidDomainException("Unknown domain: {$domain}")
        };
    }
    
    // Repository par Bounded Context
    public function getRepositoryByContext(string $context): RepositoryInterface
    {
        return match ($context) {
            HiveBoundedContextGranularity::AUTHENTICATION_CONTEXT => $this->authenticationRepository,
            HiveBoundedContextGranularity::PAYMENT_CONTEXT => $this->paymentRepository,
            HiveBoundedContextGranularity::INTEGRATION_CONTEXT => $this->integrationRepository,
            default => throw new InvalidContextException("Unknown context: {$context}")
        };
    }
    
    // Repository par Agrégat
    public function getRepositoryByAggregate(string $aggregate): RepositoryInterface
    {
        return match ($aggregate) {
            HiveAggregateGranularity::USER_AGGREGATE => $this->userRepository,
            HiveAggregateGranularity::PAYMENT_AGGREGATE => $this->paymentRepository,
            HiveAggregateGranularity::INTEGRATION_AGGREGATE => $this->integrationRepository,
            default => throw new InvalidAggregateException("Unknown aggregate: {$aggregate}")
        };
    }
}
```

### Références aux ADR du Projet Hive

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du projet Hive :
- **HIVE040** : Enhanced Models with Property Access Patterns - Modèles enrichis avec granularité
- **HIVE041** : Cross-Cutting Concerns Architecture - Architecture des préoccupations transversales
- **HIVE010** : Repositories - Repositories avec granularité appropriée
- **HIVE023** : Repository Testing Strategies - Stratégies de tests avec granularité

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux voir des exemples concrets de modèles" 
    subtitle="Vous voulez comprendre la différence entre modèles riches et anémiques" 
    criteria="Développeur avec expérience,Besoin d'exemples pratiques,Compréhension des patterns de code,Implémentation à faire" 
    time="25-35 minutes" 
    chapter="7" 
    chapter-title="Modèles Riches vs Modèles Anémiques" 
    chapter-url="/chapitres/fondamentaux/chapitre-07-modeles-riches-vs-anemiques/" 
  >}}}
  
  {{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux comprendre l'architecture événementielle" 
    subtitle="Vous voulez voir comment structurer votre code autour des événements" 
    criteria="Développeur avec expérience,Besoin de découpler les composants,Système complexe à maintenir,Évolutivité importante" 
    time="25-35 minutes" 
    chapter="8" 
    chapter-title="Architecture Événementielle" 
    chapter-url="/chapitres/fondamentaux/chapitre-08-architecture-evenementielle/" 
  >}}}
  
  {{< chapter-option 
    letter="C" 
    color="red" 
    title="Je veux comprendre les repositories et la persistance" 
    subtitle="Vous voulez voir comment gérer la persistance des données" 
    criteria="Développeur avec expérience,Besoin de comprendre la persistance,Architecture à définir,Patterns de stockage à choisir" 
    time="25-35 minutes" 
    chapter="9" 
    chapter-title="Repositories et Persistance" 
    chapter-url="/chapitres/fondamentaux/chapitre-09-repositories-persistance/" 
  >}}}
  
  {{< chapter-option 
    letter="D" 
    color="blue" 
    title="Je veux comprendre les patterns optionnels" 
    subtitle="Vous voulez voir les patterns avancés comme CQRS et Event Sourcing" 
    criteria="Équipe très expérimentée,Besoin de patterns avancés,Complexité très élevée,Performance critique" 
    time="30-45 minutes" 
    chapter="15" 
    chapter-title="Event Sourcing - La Source de Vérité" 
    chapter-url="/chapitres/optionnels/chapitre-15-event-sourcing/" 
  >}}}
  
  {{< chapter-option 
    letter="E" 
    color="purple" 
    title="Je veux comprendre l'architecture CQS" 
    subtitle="Vous voulez voir une alternative plus simple au CQRS" 
    criteria="Équipe expérimentée,Besoin d'une alternative au CQRS,Complexité élevée mais pas critique,Évolutivité importante" 
    time="25-35 minutes" 
    chapter="15" 
    chapter-title="Architecture CQS - Command Query Separation" 
    chapter-url="/chapitres/optionnels/chapitre-15-architecture-cqs/" 
  >}}}
  
{{< /chapter-nav >}}