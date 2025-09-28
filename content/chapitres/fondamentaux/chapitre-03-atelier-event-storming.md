---
title: "Chapitre 3 : L'Atelier Event Storming - Guide Pratique"
description: "Maîtriser l'organisation et l'animation d'un atelier Event Storming efficace"
date: 2024-12-19
draft: true
type: "docs"
weight: 3
---

## 🎯 Objectif de ce Chapitre

### Mon Problème : Comment Organiser un Event Storming Efficace ?

**Voici ce qui s'est passé avec Gyroscops** : J'avais entendu parler de l'Event Storming, j'avais lu les articles, j'avais même regardé des vidéos. **Parfait !** Je savais théoriquement comment ça marchait.

**Mais attendez...** Quand j'ai voulu organiser mon premier atelier, j'étais perdu. Combien de personnes inviter ? Comment structurer l'atelier ? Que faire si les participants se disputent ? Comment gérer les divergences d'opinion ?

**Soudain, je réalisais que connaître la théorie ne suffisait pas !** Il me fallait un guide pratique, étape par étape.

### L'Event Storming : Mon Guide Pratique

L'Event Storming, créé par Alberto Brandolini, m'a permis de :
- **Comprendre** le domaine métier en profondeur
- **Aligner** l'équipe sur une vision commune
- **Identifier** les événements et processus métier
- **Découvrir** les règles métier cachées

## Qu'est-ce que l'Event Storming ?

### Le Concept Fondamental

L'Event Storming est une technique de conception collaborative qui utilise des post-its pour modéliser le domaine métier. **L'idée** : Au lieu de partir des données, on part des événements métier.

**Avec Gyroscops, voici comment j'ai appliqué l'Event Storming** :

### Les 7 Étapes de l'Event Storming

#### 1. **Préparation** - La Fondation de l'Atelier

**Voici comment j'ai préparé mon premier Event Storming avec Gyroscops** :

**Participants** :
- **Moi (CTO)** : Vision technique
- **CEO** : Vision business
- **Responsable commercial** : Besoins clients
- **2 clients existants** : Retour terrain
- **Expert métier** : Règles business

**Durée** : 4 heures (2 sessions de 2h)
**Matériel** : Post-its, marqueurs, tableau blanc de 3m
**Espace** : Salle avec murs libres pour coller les post-its

**Pourquoi c'est crucial ?** Une mauvaise préparation peut ruiner l'atelier. J'ai appris à mes dépens !

#### 2. **Identification des Acteurs** - Qui Fait Quoi ?

**Exemple concret avec Gyroscops** :

**Acteurs identifiés** :
- **Utilisateur** : Personne qui utilise Gyroscops
- **Organisation** : Entité qui paie les factures
- **Workflow** : Espace de travail déployé
- **Système externe** : Salesforce, HubSpot, etc.
- **Équipe support** : Aide les utilisateurs

**Pourquoi c'est important ?** Chaque acteur a des motivations et des contraintes différentes. Les comprendre, c'est comprendre le domaine.

#### 3. **Identification des Événements** - Qu'est-ce qui se Passe ?

**Exemple concret avec Gyroscops** :

**Événements identifiés** :
- `UserRegistered` : Un utilisateur s'inscrit
- `OrganizationCreated` : Une organisation est créée
- `WorkflowDeployed` : Un workflow est déployé
- `IntegrationStarted` : Une intégration commence
- `IntegrationCompleted` : Une intégration se termine
- `PaymentProcessed` : Un paiement est traité
- `UserSuspended` : Un utilisateur est suspendu

**Pourquoi c'est essentiel ?** Les événements racontent l'histoire du domaine. Ils révèlent les processus métier.

#### 4. **Identification des Commandes** - Qui Déclenche Quoi ?

**Exemple concret avec Gyroscops** :

**Commandes identifiées** :
- `RegisterUser` : Inscrire un utilisateur
- `CreateOrganization` : Créer une organisation
- `DeployWorkflow` : Déployer un workflow
- `StartIntegration` : Démarrer une intégration
- `ProcessPayment` : Traiter un paiement
- `SuspendUser` : Suspendre un utilisateur

**Pourquoi c'est crucial ?** Les commandes montrent qui peut faire quoi. Elles révèlent les responsabilités.

#### 5. **Identification des Agrégats** - Qu'est-ce qui Change Ensemble ?

**Exemple concret avec Gyroscops** :

**Agrégats identifiés** :
- **User** : Gère les utilisateurs
- **Organization** : Gère les organisations
- **Workflow** : Gère les workflows
- **Integration** : Gère les intégrations
- **Payment** : Gère les paiements

**Pourquoi c'est important ?** Les agrégats définissent les frontières de cohérence. Ils montrent ce qui change ensemble.

#### 6. **Identification des Règles Métier** - Qu'est-ce qui est Interdit ?

**Exemple concret avec Gyroscops** :

**Règles métier identifiées** :
- "Un utilisateur ne peut pas être suspendu s'il a des paiements en cours"
- "Une organisation suspendue ne peut pas créer de nouveaux workflows"
- "Un workflow ne peut pas être déployé dans une région cloud indisponible"
- "Un paiement ne peut pas être traité pour une organisation suspendue"

**Pourquoi c'est essentiel ?** Les règles métier sont le cœur du domaine. Elles définissent ce qui est possible et ce qui ne l'est pas.

#### 7. **Identification des Vues** - Qu'est-ce qu'on Veut Voir ?

**Exemple concret avec Gyroscops** :

**Vues identifiées** :
- **Tableau de bord utilisateur** : Voir ses intégrations
- **Tableau de bord organisation** : Voir les workflows
- **Tableau de bord monitoring** : Voir les performances
- **Tableau de bord facturation** : Voir les paiements

**Pourquoi c'est la clé ?** Les vues montrent ce que les utilisateurs veulent voir. Elles guident l'interface utilisateur.

## Mon Premier Atelier Event Storming avec Gyroscops

### La Préparation

**Voici comment j'ai organisé mon premier Event Storming** :

1. **Participants** : 6 personnes (CTO, CEO, commercial, 2 clients, expert métier)
2. **Durée** : 4 heures (2 sessions de 2h)
3. **Matériel** : Post-its de 4 couleurs, marqueurs, tableau blanc de 3m
4. **Espace** : Salle avec murs libres pour coller les post-its

### L'Atelier en Action

#### Étape 1 : Présentation de l'Event Storming

**Ce que j'ai expliqué** :
- "Nous allons modéliser le domaine métier avec des post-its"
- "Chaque post-it représente un événement métier"
- "Nous allons coller les post-its sur le mur dans l'ordre chronologique"
- "Il n'y a pas de mauvaise réponse, seulement des discussions"

**Résultat** : Tout le monde comprenait l'objectif et la méthode.

#### Étape 2 : Identification des Acteurs

**Discussion** : "Qui intervient dans notre système ?"

**Acteurs identifiés** :
- **Utilisateur** : Personne qui utilise Gyroscops
- **Organisation** : Entité qui paie les factures
- **Workflow** : Espace de travail déployé
- **Système externe** : Salesforce, HubSpot, etc.
- **Équipe support** : Aide les utilisateurs

**Résultat** : Vision claire des acteurs impliqués.

#### Étape 3 : Identification des Événements

**Discussion** : "Quels événements se produisent dans notre système ?"

**Événements identifiés** :
- `UserRegistered` : Un utilisateur s'inscrit
- `OrganizationCreated` : Une organisation est créée
- `WorkflowDeployed` : Un workflow est déployé
- `IntegrationStarted` : Une intégration commence
- `IntegrationCompleted` : Une intégration se termine
- `PaymentProcessed` : Un paiement est traité
- `UserSuspended` : Un utilisateur est suspendu

**Résultat** : Chronologie des événements métier.

#### Étape 4 : Identification des Commandes

**Discussion** : "Qui déclenche ces événements ?"

**Commandes identifiées** :
- `RegisterUser` : Inscrire un utilisateur
- `CreateOrganization` : Créer une organisation
- `DeployWorkflow` : Déployer un workflow
- `StartIntegration` : Démarrer une intégration
- `ProcessPayment` : Traiter un paiement
- `SuspendUser` : Suspendre un utilisateur

**Résultat** : Responsabilités claires pour chaque événement.

#### Étape 5 : Identification des Agrégats

**Discussion** : "Qu'est-ce qui change ensemble ?"

**Agrégats identifiés** :
- **User** : Gère les utilisateurs
- **Organization** : Gère les organisations
- **Workflow** : Gère les workflows
- **Integration** : Gère les intégrations
- **Payment** : Gère les paiements

**Résultat** : Frontières de cohérence définies.

#### Étape 6 : Identification des Règles Métier

**Discussion** : "Quelles sont les règles métier ?"

**Règles métier identifiées** :
- "Un utilisateur ne peut pas être suspendu s'il a des paiements en cours"
- "Une organisation suspendue ne peut pas créer de nouveaux workflows"
- "Un workflow ne peut pas être déployé dans une région cloud indisponible"
- "Un paiement ne peut pas être traité pour une organisation suspendue"

**Résultat** : Règles métier explicites et partagées.

#### Étape 7 : Identification des Vues

**Discussion** : "Qu'est-ce que les utilisateurs veulent voir ?"

**Vues identifiées** :
- **Tableau de bord utilisateur** : Voir ses intégrations
- **Tableau de bord organisation** : Voir les workflows
- **Tableau de bord monitoring** : Voir les performances
- **Tableau de bord facturation** : Voir les paiements

**Résultat** : Interface utilisateur guidée par les besoins métier.

### Les Découvertes Surprenantes

#### 1. **Les Règles Métier Cachées**

**Avant l'Event Storming** : Je pensais que suspendre un utilisateur était simple.

**Après l'Event Storming** : J'ai découvert que suspendre un utilisateur impliquait de gérer ses paiements, ses workflows, ses intégrations, et ses données.

**Résultat** : J'ai compris pourquoi cette fonctionnalité était si complexe à implémenter !

#### 2. **Les Inter-dépendances Complexes**

**Avant l'Event Storming** : Je voyais chaque entité indépendamment.

**Après l'Event Storming** : J'ai découvert que User, Organization, Workflow, et Integration étaient tous liés.

**Résultat** : J'ai compris pourquoi mes tests étaient si fragiles !

#### 3. **Les Besoins Utilisateurs Réels**

**Avant l'Event Storming** : Je pensais que les utilisateurs voulaient des fonctionnalités avancées.

**Après l'Event Storming** : J'ai découvert qu'ils voulaient juste savoir que leurs intégrations fonctionnaient.

**Résultat** : J'ai développé le monitoring au lieu de nouvelles fonctionnalités !

## Les 4 Types de Post-its

### 🟡 Post-its Jaunes : Événements

**Exemple avec Gyroscops** :
- `UserRegistered` : Un utilisateur s'inscrit
- `OrganizationCreated` : Une organisation est créée
- `WorkflowDeployed` : Un workflow est déployé
- `IntegrationStarted` : Une intégration commence

**Pourquoi c'est important ?** Les événements racontent l'histoire du domaine.

### 🔵 Post-its Bleus : Commandes

**Exemple avec Gyroscops** :
- `RegisterUser` : Inscrire un utilisateur
- `CreateOrganization` : Créer une organisation
- `DeployWorkflow` : Déployer un workflow
- `StartIntegration` : Démarrer une intégration

**Pourquoi c'est crucial ?** Les commandes montrent qui peut faire quoi.

### 🟢 Post-its Verts : Agrégats

**Exemple avec Gyroscops** :
- **User** : Gère les utilisateurs
- **Organization** : Gère les organisations
- **Workflow** : Gère les workflows
- **Integration** : Gère les intégrations

**Pourquoi c'est essentiel ?** Les agrégats définissent les frontières de cohérence.

### 🟠 Post-its Orange : Règles Métier

**Exemple avec Gyroscops** :
- "Un utilisateur ne peut pas être suspendu s'il a des paiements en cours"
- "Une organisation suspendue ne peut pas créer de nouveaux workflows"
- "Un workflow ne peut pas être déployé dans une région cloud indisponible"
- "Un paiement ne peut pas être traité pour une organisation suspendue"

**Pourquoi c'est la clé ?** Les règles métier sont le cœur du domaine.

## Comment Animer un Event Storming

### 1. **Avant l'Atelier**

**Avec Gyroscops** : 
- **Préparer** la salle avec le matériel
- **Inviter** les bonnes personnes
- **Expliquer** l'objectif et la méthode
- **Définir** les règles de l'atelier

**Résultat** : Atelier bien préparé et participants motivés.

### 2. **Pendant l'Atelier**

**Avec Gyroscops** :
- **Faciliter** les discussions sans imposer
- **Encourager** tous les participants à s'exprimer
- **Gérer** les conflits et divergences
- **Maintenir** le focus sur le domaine métier

**Résultat** : Discussions productives et découvertes partagées.

### 3. **Après l'Atelier**

**Avec Gyroscops** :
- **Documenter** les découvertes
- **Prioriser** les fonctionnalités
- **Planifier** le développement
- **Communiquer** les résultats

**Résultat** : Découvertes transformées en actions concrètes.

## Les Pièges à Éviter

### 1. **Trop de Participants**

**❌ Mauvais** : 15 personnes dans l'atelier
**✅ Bon** : 6-8 personnes maximum

**Pourquoi c'est important ?** Trop de participants créent du chaos et ralentissent l'atelier.

### 2. **Atelier Trop Long**

**❌ Mauvais** : 8 heures d'affilée
**✅ Bon** : 2 sessions de 2h avec pause

**Pourquoi c'est crucial ?** Un atelier trop long fatigue les participants et réduit la qualité.

### 3. **Discussion Technique**

**❌ Mauvais** : "Comment implémenter cette fonctionnalité ?"
**✅ Bon** : "Quel événement se produit quand l'utilisateur fait ça ?"

**Pourquoi c'est essentiel ?** L'Event Storming se concentre sur le domaine métier, pas sur l'implémentation.

### 4. **Post-its Trop Détaillés**

**❌ Mauvais** : "UserRegisteredWithEmailAndPasswordAndOrganizationAndWorkflow"
**✅ Bon** : "UserRegistered"

**Pourquoi c'est la clé ?** Des post-its trop détaillés compliquent la lecture et la discussion.

## L'Event Storming et l'Example Mapping

### La Synergie

**L'Event Storming** me dit **quels** événements se produisent.
**L'Example Mapping** me dit **quand** et **pourquoi** ils se produisent.

**Avec Gyroscops** : 
1. **Event Storming** : "Quand un utilisateur est suspendu, l'événement `UserSuspended` se produit"
2. **Example Mapping** : "Quelles sont les règles pour suspendre un utilisateur ?" → "Un utilisateur ne peut pas être suspendu s'il a des paiements en cours"
3. **Résultat** : Règles métier détaillées et testables

### La Progression Logique

1. **Event Storming** : Comprendre le domaine métier
2. **Example Mapping** : Détailer les règles métier
3. **Développement** : Implémenter les fonctionnalités

**Résultat** : Développement guidé par le domaine métier.

## 🏗️ Implémentation Concrète dans le projet Gyroscops Cloud

### Event Storming Appliqué à Gyroscops Cloud

Le projet Gyroscops Cloud applique concrètement les principes de l'Event Storming à travers son architecture et ses ADR (Architecture Decision Records). Voici comment :

#### Atelier Event Storming Gyroscops Cloud

**Participants** :
- **Product Owner** : Vision produit et roadmap
- **Développeurs** : Contraintes techniques et implémentation
- **Architectes** : Vision architecture et patterns
- **DevOps** : Contraintes infrastructure et déploiement
- **Clients** : Besoins utilisateurs et retours terrain

**Durée** : 6 heures (3 sessions de 2h)
**Matériel** : Miro, post-its virtuels, diagrammes Mermaid

#### Événements Identifiés

```php
// ✅ Événements Métier Gyroscops Cloud (projet Gyroscops Cloud)
final class HiveDomainEvents
{
    // Événements d'Authentification
    public const USER_REGISTERED = 'user.registered';
    public const USER_AUTHENTICATED = 'user.authenticated';
    public const USER_LOGGED_OUT = 'user.logged_out';
    
    // Événements d'Intégration
    public const INTEGRATION_CREATED = 'integration.created';
    public const INTEGRATION_DEPLOYED = 'integration.deployed';
    public const INTEGRATION_FAILED = 'integration.failed';
    
    // Événements de Paiement
    public const PAYMENT_INITIATED = 'payment.initiated';
    public const PAYMENT_COMPLETED = 'payment.completed';
    public const PAYMENT_FAILED = 'payment.failed';
    
    // Événements de Monitoring
    public const ALERT_TRIGGERED = 'alert.triggered';
    public const METRICS_COLLECTED = 'metrics.collected';
    public const HEALTH_CHECK_FAILED = 'health.check.failed';
}
```

#### Commandes Identifiées

```php
// ✅ Commandes Gyroscops Cloud (projet Gyroscops Cloud)
final class HiveCommands
{
    // Commandes d'Authentification
    public const REGISTER_USER = 'RegisterUser';
    public const AUTHENTICATE_USER = 'AuthenticateUser';
    public const LOGOUT_USER = 'LogoutUser';
    
    // Commandes d'Intégration
    public const CREATE_INTEGRATION = 'CreateIntegration';
    public const DEPLOY_INTEGRATION = 'DeployIntegration';
    public const STOP_INTEGRATION = 'StopIntegration';
    
    // Commandes de Paiement
    public const INITIATE_PAYMENT = 'InitiatePayment';
    public const PROCESS_PAYMENT = 'ProcessPayment';
    public const REFUND_PAYMENT = 'RefundPayment';
}
```

#### Règles Métier Découvertes

```php
// ✅ Règles Métier Gyroscops Cloud (projet Gyroscops Cloud)
final class HiveBusinessRules
{
    // Règles d'Authentification
    public const USER_MUST_HAVE_VALID_EMAIL = 'user.must.have.valid.email';
    public const PASSWORD_MUST_BE_STRONG = 'password.must.be.strong';
    public const USER_MUST_BE_ACTIVE = 'user.must.be.active';
    
    // Règles d'Intégration
    public const INTEGRATION_MUST_HAVE_VALID_CONFIG = 'integration.must.have.valid.config';
    public const INTEGRATION_MUST_PASS_TESTS = 'integration.must.pass.tests';
    public const INTEGRATION_MUST_HAVE_MONITORING = 'integration.must.have.monitoring';
    
    // Règles de Paiement
    public const PAYMENT_MUST_HAVE_VALID_AMOUNT = 'payment.must.have.valid.amount';
    public const PAYMENT_MUST_HAVE_VALID_CURRENCY = 'payment.must.have.valid.currency';
    public const PAYMENT_MUST_HAVE_VALID_CUSTOMER = 'payment.must.have.valid.customer';
}
```

### Références aux ADR du projet Gyroscops Cloud

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du projet Gyroscops Cloud :
- **HIVE008** : Event Collaboration - Collaboration basée sur les événements
- **HIVE009** : Message Buses - Bus de messages pour les événements
- **HIVE040** : Enhanced Models with Property Access Patterns - Modèles enrichis pour les événements
- **HIVE041** : Cross-Cutting Concerns Architecture - Architecture des préoccupations transversales

---

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux détailler les règles métier avec l'Example Mapping" 
    subtitle="Vous voulez explorer les règles complexes découvertes lors de l'Event Storming" 
    criteria="Équipe ayant fait un Event Storming,Besoin de détailler les règles métier,Tests d'acceptation à écrire,Communication avec les parties prenantes" 
    time="20-30 minutes" 
    chapter="4" 
    chapter-title="L'Example Mapping - Détailer les Règles Métier" 
    chapter-url="/chapitres/fondamentaux/chapitre-04-example-mapping/" 
  >}}

  {{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux comprendre la complexité architecturale" 
    subtitle="Vous voulez savoir quand utiliser quels patterns" 
    criteria="Équipe expérimentée,Besoin de choisir une architecture,Projet avec contraintes techniques,Décision architecturale à prendre" 
    time="20-30 minutes" 
    chapter="5" 
    chapter-title="Complexité Accidentelle vs Essentielle" 
    chapter-url="/chapitres/fondamentaux/chapitre-05-complexite-accidentelle-essentielle/" 
  >}}

  {{< chapter-option 
    letter="C" 
    color="red" 
    title="Je veux voir des exemples concrets de modèles" 
    subtitle="Vous voulez comprendre la différence entre modèles riches et anémiques" 
    criteria="Développeur avec expérience,Besoin d'exemples pratiques,Compréhension des patterns de code,Implémentation à faire" 
    time="25-35 minutes" 
    chapter="7" 
    chapter-title="Modèles Riches vs Modèles Anémiques" 
    chapter-url="/chapitres/fondamentaux/chapitre-07-modeles-riches-vs-anemiques/" 
  >}}

  {{< chapter-option 
    letter="D" 
    color="blue" 
    title="Je veux comprendre l'architecture événementielle" 
    subtitle="Vous voulez voir comment structurer votre code autour des événements" 
    criteria="Développeur avec expérience,Besoin de découpler les composants,Système complexe à maintenir,Évolutivité importante" 
    time="25-35 minutes" 
    chapter="8" 
    chapter-title="Architecture Événementielle" 
    chapter-url="/chapitres/fondamentaux/chapitre-08-architecture-evenementielle/" 
  >}}

  {{< chapter-option 
    letter="E" 
    color="purple" 
    title="Je veux comprendre les repositories et la persistance" 
    subtitle="Vous voulez voir comment gérer la persistance des données" 
    criteria="Développeur avec expérience,Besoin de comprendre la persistance,Architecture à définir,Patterns de stockage à choisir" 
    time="25-35 minutes" 
    chapter="9" 
    chapter-title="Repositories et Persistance" 
    chapter-url="/chapitres/fondamentaux/chapitre-09-repositories-persistance/" 
  >}}
{{< /chapter-nav >}}

---

**💡 Conseil** : Si vous n'êtes pas sûr, choisissez l'option A pour apprendre l'Example Mapping, puis continuez avec les autres chapitres dans l'ordre.

**🔄 Alternative** : Si vous voulez tout voir dans l'ordre, commencez par le [Chapitre 4](/chapitres/fondamentaux/chapitre-04-example-mapping/).
