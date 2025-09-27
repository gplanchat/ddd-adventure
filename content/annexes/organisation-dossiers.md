---
title: "Organisation des dossiers en DDD"
linkTitle: "Organisation des dossiers"
weight: 1
description: "Guide complet pour structurer votre projet DDD selon sa taille et sa complexité"
---

# 📁 Organisation des dossiers

Découvrez différentes approches pour structurer votre projet DDD selon sa taille et sa complexité.
      
## 🎯 Principes fondamentaux

L'organisation des dossiers en DDD doit respecter plusieurs principes clés :

### 🏗️ Séparation par Bounded Context
Chaque contexte métier doit avoir sa propre structure indépendante.

### 📦 Couches architecturales
Domain, Application, Infrastructure et Interface clairement séparées.

### 🔄 CQRS et Event Sourcing
Séparation claire entre Command et Query, avec gestion des événements.

### 🧪 Tests intégrés
Structure de tests qui reflète l'organisation du code de production.

## 🏢 Approche 1 : Structure par Bounded Context (Recommandée)

Cette approche est idéale pour les projets de taille moyenne à grande avec plusieurs contextes métier distincts.

### Structure recommandée :

```
src/
├── Accounting/                    # Bounded Context
│   ├── Domain/
│   │   ├── Entities/
│   │   ├── ValueObjects/
│   │   ├── Events/
│   │   └── Services/
│   ├── Application/
│   │   ├── Commands/
│   │   ├── Queries/
│   │   └── Handlers/
│   ├── Infrastructure/
│   │   ├── Repositories/
│   │   ├── Persistence/
│   │   └── External/
│   └── UserInterface/
│       ├── Controllers/
│       ├── DTOs/
│       └── Validators/
├── Authentication/               # Autre Bounded Context
│   ├── Domain/
│   ├── Application/
│   ├── Infrastructure/
│   └── UserInterface/
└── Shared/                       # Code partagé
    ├── Domain/
    ├── Infrastructure/
    └── UserInterface/
```

### ✅ Avantages :
- **Isolation claire** entre les contextes métier
- **Évolutivité** : facile d'ajouter de nouveaux contextes
- **Maintenance** : chaque équipe peut travailler sur son contexte
- **Tests** : isolation des tests par contexte

## 🏠 Approche 2 : Structure par couches (Simple)

Cette approche convient aux petits projets ou aux équipes débutantes en DDD.

### Structure simple :

```
src/
├── Domain/
│   ├── Entities/
│   ├── ValueObjects/
│   ├── Events/
│   └── Services/
├── Application/
│   ├── Commands/
│   ├── Queries/
│   └── Handlers/
├── Infrastructure/
│   ├── Repositories/
│   ├── Persistence/
│   └── External/
└── UserInterface/
    ├── Controllers/
    ├── DTOs/
    └── Validators/
```

### ✅ Avantages :
- **Simplicité** : structure claire et compréhensible
- **Démarrage rapide** : pas de complexité organisationnelle
- **Apprentissage** : idéal pour découvrir le DDD

## 🏗️ Approche 3 : Structure modulaire (Avancée)

Cette approche est recommandée pour les très gros projets avec de nombreux modules et équipes.

### Structure modulaire :

```
modules/
├── accounting/
│   ├── src/
│   │   ├── Domain/
│   │   ├── Application/
│   │   ├── Infrastructure/
│   │   └── UserInterface/
│   ├── tests/
│   └── composer.json
├── authentication/
│   ├── src/
│   ├── tests/
│   └── composer.json
└── shared/
    ├── src/
    ├── tests/
    └── composer.json
```

## 🧪 Organisation des tests

La structure des tests doit refléter l'organisation du code de production :

### Structure des tests :

```
tests/
├── Unit/                         # Tests unitaires
│   ├── Domain/
│   │   ├── Entities/
│   │   └── ValueObjects/
│   └── Application/
│       └── Handlers/
├── Integration/                  # Tests d'intégration
│   ├── Infrastructure/
│   └── UserInterface/
├── Functional/                   # Tests fonctionnels
│   └── Api/
└── Fixtures/                     # Données de test
    ├── Accounting/
    └── Authentication/
```

## 📋 Checklist de validation

### ✅ Structure claire :
- Chaque Bounded Context est isolé
- Les couches architecturales sont séparées
- Le code partagé est identifié
- Les tests reflètent la structure

### ✅ Évolutivité :
- Facile d'ajouter de nouveaux contextes
- Possibilité de refactorer sans casser
- Tests indépendants par contexte
- Documentation à jour

> **💡 Conseil pratique**  
> Commencez simple avec l'approche par couches, puis évoluez vers l'approche par Bounded Context quand votre projet grandit. L'important est de rester cohérent et de documenter vos choix architecturaux.
