# 🎯 Guide Complet - API Platform en DDD avec Event Storming

## 📋 Index Général et Schéma de Lecture

Bienvenue dans le guide complet pour implémenter le Domain-Driven Design avec API Platform ! Cette documentation suit une approche "livre dont vous êtes le héros" qui vous permet de choisir votre parcours selon votre contexte et vos besoins.

**Source** : Présentation "API Platform Con 2025 - Et si on utilisait l'Event Storming ?" de Grégory Planchat  
**Objectif** : Créer des articles de blog découpés en chapitres, reposant sur les ADR du projet Hive, avec un fil conducteur cohérent et des options de lecture adaptées aux différents contextes.

## 🎮 Navigation Interactive "Livre dont vous êtes le héros"

### 🟢 Parcours Débutant
**Pour les équipes junior avec des applications simples**

**Chapitres** : 1-4 → 6 → 12 → 42-45  
**Durée estimée** : 2-3 semaines  
**Équipe** : 1-3 développeurs

[**Commencer le Parcours Débutant**](/navigation-interactive/)

---

### 🟡 Parcours Standard
**Pour la plupart des applications métier**

**Chapitres** : 1-4 → 6 → 12/13/16 → 42-45  
**Durée estimée** : 1-2 mois  
**Équipe** : 3-8 développeurs

[**Commencer le Parcours Standard**](/navigation-interactive/)

---

### 🔴 Parcours Événementiel
**Pour les systèmes avec intégrations multiples**

**Chapitres** : 1-5 → 6 → 12/13/16 → 42-45  
**Durée estimée** : 2-3 mois  
**Équipe** : 3-8 développeurs

[**Commencer le Parcours Événementiel**](/navigation-interactive/)

---

### ⚡ Parcours CQRS
**Pour les applications avec CQRS**

**Chapitres** : 1-5 → 8 → 6 → 11/14/17 → 42-45  
**Durée estimée** : 2-4 mois  
**Équipe** : 4-8 développeurs

[**Commencer le Parcours CQRS**](/navigation-interactive/)

---

### 🚀 Parcours Event Sourcing
**Pour les applications avec Event Sourcing + CQRS**

**Chapitres** : 1-5 → 7 → 8 → 9 → 6 → 12/15/18 → 42-45  
**Durée estimée** : 4-6 mois  
**Équipe** : 8+ développeurs

[**Commencer le Parcours Event Sourcing**](/navigation-interactive/)

---

### 🌐 Parcours Distribué
**Pour les systèmes distribués complexes**

**Chapitres** : 1-5 → 7 → 8 → 9 → 6 → 12/15/18 → 19 → 42-45  
**Durée estimée** : 6+ mois  
**Équipe** : 10+ développeurs

[**Commencer le Parcours Distribué**](/navigation-interactive/)

## 📚 Structure des Chapitres

### [Chapitres Fondamentaux](/chapitres/fondamentaux/) (Parcours Principal)

1. **[Chapitre 1 : Introduction à l'Event Storming et DDD](/chapitres/fondamentaux/chapitre-01-introduction-event-storming-ddd/)**
   - Problématique des modèles anémiques et du CRUD
   - Introduction à l'Event Storming comme solution de conception collaborative
   - Justification de l'approche DDD

2. **[Chapitre 2 : L'Atelier Event Storming - Guide Pratique](/chapitres/fondamentaux/chapitre-02-atelier-event-storming/)**
   - Méthodologie complète de l'atelier Event Storming
   - Les 7 étapes de l'atelier
   - Identification des acteurs et systèmes externes

3. **[Chapitre 3 : Complexité Accidentelle vs Essentielle - Le Choix Architectural](/chapitres/fondamentaux/chapitre-03-complexite-accidentelle-essentielle/)**
   - Concepts de Frederick Brooks
   - Guide de décision pour les patterns architecturaux
   - Matrice de coûts/bénéfices
   - **Position** : Chapitre pivot pour la prise de décision

4. **[Chapitre 3.1 : Granularité des Choix Architecturaux](/chapitres/fondamentaux/chapitre-03-1-granularite-choix-architecturaux/)**
   - Choix globaux vs choix granulaires
   - Architecture par Bounded Context
   - Architecture par Agrégat
   - Gestion de la charge mentale

5. **[Chapitre 4 : Modèles Riches vs Modèles Anémiques](/chapitres/fondamentaux/chapitre-04-modeles-riches-vs-anemiques/)**
   - Comparaison détaillée avec exemples de code
   - Patterns de modèles riches
   - Conservation de l'intention métier

6. **[Chapitre 5 : Architecture Événementielle (Optionnel)](/chapitres/fondamentaux/chapitre-05-architecture-evenementielle/)**
   - Event-Driven Architecture
   - Domain Events et Event Bus
   - Patterns de collaboration

7. **[Chapitre 6 : Repositories et Persistance](/chapitres/fondamentaux/chapitre-06-repositories-persistance/)**
   - Patterns Repository
   - Gestion des événements
   - Transaction management

### [Chapitres Optionnels](/chapitres/optionnels/) (Choix Conscients)

8. **[Chapitre 7 : Event Sourcing - La Source de Vérité](/chapitres/optionnels/chapitre-07-event-sourcing/)**
9. **[Chapitre 8 : Architecture CQS - Command Query Separation](/chapitres/optionnels/chapitre-08-architecture-cqs/)**
10. **[Chapitre 9 : Architecture CQRS avec API Platform](/chapitres/optionnels/chapitre-09-architecture-cqrs-api-platform/)**
11. **[Chapitre 10 : CQRS + Event Sourcing Combinés](/chapitres/optionnels/chapitre-10-cqrs-event-sourcing-combines/)**
12. **[Chapitre 11 : Projections Event Sourcing](/chapitres/optionnels/chapitre-11-projections-event-sourcing/)**

### [Chapitres de Stockage](/chapitres/stockage/) (Contextualisés CQS/CQRS/Event Sourcing)

#### Stockage SQL
- **[Chapitre 12 : Stockage SQL - Approche Classique](/chapitres/stockage/sql/chapitre-12-stockage-sql-classique/)**
- **[Chapitre 13 : Stockage SQL - Approche CQS](/chapitres/stockage/sql/chapitre-13-stockage-sql-cqs/)**
- **[Chapitre 14 : Stockage SQL - Approche CQRS](/chapitres/stockage/sql/chapitre-14-stockage-sql-cqrs/)**
- **[Chapitre 15 : Stockage SQL - Event Sourcing seul](/chapitres/stockage/sql/chapitre-15-stockage-sql-event-sourcing/)**
- **[Chapitre 16 : Stockage SQL - Event Sourcing + CQS](/chapitres/stockage/sql/chapitre-16-stockage-sql-event-sourcing-cqs/)**
- **[Chapitre 17 : Stockage SQL - Event Sourcing + CQRS](/chapitres/stockage/sql/chapitre-17-stockage-sql-event-sourcing-cqrs/)**

#### Stockage API Externe
- **[Chapitre 18 : Stockage API - Approche Classique](/chapitres/stockage/api/chapitre-18-stockage-api-classique/)**
- **[Chapitre 19 : Stockage API - Approche CQS](/chapitres/stockage/api/chapitre-19-stockage-api-cqs/)**
- **[Chapitre 20 : Stockage API - Approche CQRS](/chapitres/stockage/api/chapitre-20-stockage-api-cqrs/)**
- **[Chapitre 21 : Stockage API - Event Sourcing seul](/chapitres/stockage/api/chapitre-21-stockage-api-event-sourcing/)**
- **[Chapitre 22 : Stockage API - Event Sourcing + CQS](/chapitres/stockage/api/chapitre-22-stockage-api-event-sourcing-cqs/)**
- **[Chapitre 23 : Stockage API - Event Sourcing + CQRS](/chapitres/stockage/api/chapitre-23-stockage-api-event-sourcing-cqrs/)**

#### Stockage ElasticSearch
- **[Chapitre 24 : Stockage ElasticSearch - Approche Classique](/chapitres/stockage/elasticsearch/chapitre-24-stockage-elasticsearch-classique/)**
- **[Chapitre 25 : Stockage ElasticSearch - Approche CQS](/chapitres/stockage/elasticsearch/chapitre-25-stockage-elasticsearch-cqs/)**
- **[Chapitre 26 : Stockage ElasticSearch - Approche CQRS](/chapitres/stockage/elasticsearch/chapitre-26-stockage-elasticsearch-cqrs/)**
- **[Chapitre 27 : Stockage ElasticSearch - Event Sourcing seul](/chapitres/stockage/elasticsearch/chapitre-27-stockage-elasticsearch-event-sourcing/)**
- **[Chapitre 28 : Stockage ElasticSearch - Event Sourcing + CQS](/chapitres/stockage/elasticsearch/chapitre-28-stockage-elasticsearch-event-sourcing-cqs/)**
- **[Chapitre 29 : Stockage ElasticSearch - Event Sourcing + CQRS](/chapitres/stockage/elasticsearch/chapitre-29-stockage-elasticsearch-event-sourcing-cqrs/)**

#### Stockage MongoDB
- **[Chapitre 30 : Stockage MongoDB - Approche Classique](/chapitres/stockage/mongodb/chapitre-30-stockage-mongodb-classique/)**
- **[Chapitre 31 : Stockage MongoDB - Approche CQS](/chapitres/stockage/mongodb/chapitre-31-stockage-mongodb-cqs/)**
- **[Chapitre 32 : Stockage MongoDB - Approche CQRS](/chapitres/stockage/mongodb/chapitre-32-stockage-mongodb-cqrs/)**
- **[Chapitre 33 : Stockage MongoDB - Event Sourcing seul](/chapitres/stockage/mongodb/chapitre-33-stockage-mongodb-event-sourcing/)**
- **[Chapitre 34 : Stockage MongoDB - Event Sourcing + CQS](/chapitres/stockage/mongodb/chapitre-34-stockage-mongodb-event-sourcing-cqs/)**
- **[Chapitre 35 : Stockage MongoDB - Event Sourcing + CQRS](/chapitres/stockage/mongodb/chapitre-35-stockage-mongodb-event-sourcing-cqrs/)**

#### Stockage In-Memory
- **[Chapitre 36 : Stockage In-Memory - Approche Classique](/chapitres/stockage/in-memory/chapitre-36-stockage-in-memory-classique/)**
- **[Chapitre 37 : Stockage In-Memory - Approche CQS](/chapitres/stockage/in-memory/chapitre-37-stockage-in-memory-cqs/)**
- **[Chapitre 38 : Stockage In-Memory - Approche CQRS](/chapitres/stockage/in-memory/chapitre-38-stockage-in-memory-cqrs/)**
- **[Chapitre 39 : Stockage In-Memory - Event Sourcing seul](/chapitres/stockage/in-memory/chapitre-39-stockage-in-memory-event-sourcing/)**
- **[Chapitre 40 : Stockage In-Memory - Event Sourcing + CQS](/chapitres/stockage/in-memory/chapitre-40-stockage-in-memory-event-sourcing-cqs/)**
- **[Chapitre 41 : Stockage In-Memory - Event Sourcing + CQRS](/chapitres/stockage/in-memory/chapitre-41-stockage-in-memory-event-sourcing-cqrs/)**

#### Stockage Complexe
- **[Chapitre 42 : Stockage Complexe avec Temporal Workflows](/chapitres/stockage/complexe/chapitre-42-stockage-complexe-temporal/)**

### [Chapitres Techniques](/chapitres/techniques/) (Affinements)
- **[Chapitre 43 : Gestion des Données et Validation](/chapitres/techniques/chapitre-43-gestion-donnees-validation/)**
- **[Chapitre 44 : Pagination et Performance](/chapitres/techniques/chapitre-44-pagination-performance/)**
- **[Chapitre 45 : Gestion d'Erreurs et Observabilité](/chapitres/techniques/chapitre-45-gestion-erreurs-observabilite/)**
- **[Chapitre 46 : Tests et Qualité](/chapitres/techniques/chapitre-46-tests-qualite/)**

### [Chapitres Avancés](/chapitres/avances/) (Spécialisations)
- **[Chapitre 47 : Sécurité et Autorisation](/chapitres/avances/chapitre-47-securite-autorisation/)**
- **[Chapitre 48 : Architecture Frontend (PWA)](/chapitres/avances/chapitre-48-architecture-frontend-pwa/)**

## 🎯 Principes Fondamentaux

### 1. Complexité Accidentelle vs Essentielle
- Suivre les principes de Frederick Brooks ("No Silver Bullet")
- Distinguer la complexité inhérente au problème métier de celle introduite par les solutions techniques
- Permettre des choix architecturaux conscients et justifiés

### 2. Granularité des Choix Architecturaux
- **Choix globaux** : Architecture générale de l'application
- **Choix par Bounded Context** : Architecture spécifique à un domaine métier
- **Choix par Agrégat** : Architecture fine pour des entités particulières
- **Principe de cohérence** : Limiter le nombre d'architectures cohabitant dans le même système

### 3. Approche Optionnelle
- CQRS et Event Sourcing sont des **options conscientes**, pas des prérequis
- Chaque pattern doit avoir des critères d'adoption clairs
- Éviter l'adoption aveugle de patterns complexes

### 4. Contextualisation des Stockages
- Les chapitres de stockage sont contextualisés selon l'utilisation ou non du CQRS/Event Sourcing
- Six approches distinctes : Classique, CQS, CQRS, Event Sourcing seul, Event Sourcing + CQS, Event Sourcing + CQRS

## 📊 Critères d'Adoption par Pattern

### Architecture Classique
- ✅ **Adoptez si** : Application simple, équipe junior, développement rapide requis
- ❌ **Évitez si** : Performance critique, intégrations multiples, audit trail nécessaire

### Architecture CQS
- ✅ **Adoptez si** : Lectures/écritures différentes, besoin de performance, équipe intermédiaire
- ❌ **Évitez si** : Modèles identiques, équipe très junior

### Architecture CQRS
- ✅ **Adoptez si** : Lectures/écritures très différentes, équipes séparées, performance critique
- ❌ **Évitez si** : Application simple, modèles similaires, équipe petite

### Event Sourcing
- ✅ **Adoptez si** : Audit trail critique, debugging complexe, équipe expérimentée
- ❌ **Évitez si** : Application simple, équipe peu expérimentée, performance critique

### Event Sourcing + CQRS
- ✅ **Adoptez si** : Audit trail critique, performance critique, équipe très expérimentée
- ❌ **Évitez si** : Application simple, équipe peu expérimentée, budget limité

## 🧠 Gestion de la Charge Mentale

### Principe de Cohérence Architecturale
- **Maximum 3 architectures** différentes dans le même système
- **Préférer la cohérence** à la performance optimale
- **Documenter clairement** les choix par Bounded Context

### Matrice de Charge Mentale par Architecture

| Architecture | Complexité | Charge Mentale | Équipe Min. | Temps d'Apprentissage |
|--------------|------------|----------------|-------------|----------------------|
| **Classique** | Faible | Faible | 2-3 devs | 1-2 semaines |
| **CQS** | Faible-Moyenne | Faible-Moyenne | 3-4 devs | 2-3 semaines |
| **CQRS** | Moyenne | Moyenne | 4-5 devs | 1-2 mois |
| **Event Sourcing** | Moyenne-Élevée | Moyenne-Élevée | 5-6 devs | 2-3 mois |
| **Event Sourcing + CQS** | Élevée | Élevée | 6-8 devs | 3-4 mois |
| **Event Sourcing + CQRS** | Très Élevée | Très Élevée | 8+ devs | 4-6 mois |

## 🎮 Comment Naviguer

Chaque chapitre se termine par des **choix de lecture** qui vous permettent de personnaliser votre parcours selon :
- Votre niveau d'expérience
- La complexité de votre projet
- Vos contraintes (temps, budget, équipe)
- Vos besoins techniques spécifiques

## 💡 Conseil

Si vous n'êtes pas sûr de votre parcours, commencez par les [Chapitres Fondamentaux](/chapitres/fondamentaux/) et laissez-vous guider par les choix proposés à la fin de chaque chapitre.

## 🚀 Commencer Maintenant

[**Découvrir la Navigation Interactive**](/navigation-interactive/) | [**Commencer par le Chapitre 1**](/chapitres/fondamentaux/chapitre-01-introduction-event-storming-ddd/)

---

*Ce guide est basé sur les Architecture Decision Records (ADR) du projet Hive et suit les principes établis dans "API Platform Con 2025 - Et si on utilisait l'Event Storming ?"*
