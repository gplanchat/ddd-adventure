---
title: "Navigation Interactive - Livre dont vous êtes le héros"
description: "Schéma de navigation interactif pour choisir votre parcours de lecture"
date: 2024-12-19
draft: true
type: "docs"
weight: 0
---

# 🎮 Navigation Interactive - Livre dont vous êtes le héros

Cette page vous permet de naviguer de manière interactive dans la documentation en fonction de votre contexte et de vos besoins.

## 🎯 Votre Contexte

### 🟢 Parcours Débutant
**Pour les équipes junior avec des applications simples**

**Chapitres** : 1-4 → 6 → 12 → 42-45

**Durée estimée** : 2-3 semaines

**Critères** :
- Équipe de 1-3 développeurs
- Application monolithique
- Peu d'intégrations externes
- Développement rapide requis

[**Commencer le Parcours Débutant**](/chapitres/fondamentaux/chapitre-01-introduction-event-storming-ddd/)

---

### 🟡 Parcours Standard
**Pour la plupart des applications métier**

**Chapitres** : 1-4 → 6 → 12/13/16 → 42-45

**Durée estimée** : 1-2 mois

**Critères** :
- Équipe de 3-8 développeurs
- Quelques intégrations externes
- Besoin de performance modérée
- Évolutivité importante

[**Commencer le Parcours Standard**](/chapitres/fondamentaux/chapitre-01-introduction-event-storming-ddd/)

---

### 🔴 Parcours Événementiel
**Pour les systèmes avec intégrations multiples**

**Chapitres** : 1-5 → 6 → 12/13/16 → 42-45

**Durée estimée** : 2-3 mois

**Critères** :
- Équipe de 3-8 développeurs
- Intégrations multiples
- Besoin de découplage
- Architecture distribuée

[**Commencer le Parcours Événementiel**](/chapitres/fondamentaux/chapitre-01-introduction-event-storming-ddd/)

---

### ⚡ Parcours CQRS
**Pour les applications avec CQRS**

**Chapitres** : 1-5 → 8 → 6 → 11/14/17 → 42-45

**Durée estimée** : 2-4 mois

**Critères** :
- Équipe de 4-8 développeurs
- Lectures/écritures très différentes
- Performance critique
- Équipe expérimentée

[**Commencer le Parcours CQRS**](/chapitres/fondamentaux/chapitre-01-introduction-event-storming-ddd/)

---

### 🚀 Parcours Event Sourcing
**Pour les applications avec Event Sourcing + CQRS**

**Chapitres** : 1-5 → 7 → 8 → 9 → 6 → 12/15/18 → 42-45

**Durée estimée** : 4-6 mois

**Critères** :
- Équipe de 8+ développeurs
- Audit trail critique
- Performance critique
- Équipe très expérimentée
- Budget et temps importants

[**Commencer le Parcours Event Sourcing**](/chapitres/fondamentaux/chapitre-01-introduction-event-storming-ddd/)

---

### 🌐 Parcours Distribué
**Pour les systèmes distribués complexes**

**Chapitres** : 1-5 → 7 → 8 → 9 → 6 → 12/15/18 → 19 → 42-45

**Durée estimée** : 6+ mois

**Critères** :
- Équipe de 10+ développeurs
- Systèmes distribués
- Transactions complexes
- Équipe très expérimentée
- Budget important

[**Commencer le Parcours Distribué**](/chapitres/fondamentaux/chapitre-01-introduction-event-storming-ddd/)

## 🗺️ Schéma de Navigation

```mermaid
graph TD
    A[Chapitre 1: Introduction Event Storming] --> B[Chapitre 2: Atelier Event Storming]
    B --> C[Chapitre 3: Complexité Accidentelle vs Essentielle]
    
    C -->|Décision 1A| D[Chapitre 4: Modèles Riches vs Anémiques]
    C -->|Décision 1B| E[Chapitre 5: Architecture Événementielle]
    C -->|Décision 1C| D
    
    D -->|Décision 2A| F[Chapitre 6: Repositories et Persistance]
    D -->|Décision 2B| E
    D -->|Décision 2C| F
    
    E -->|Décision 3A| G[Chapitre 7: Event Sourcing]
    E -->|Décision 3B| H[Chapitre 8: CQS]
    E -->|Décision 3C| I[Chapitre 9: CQRS]
    E -->|Décision 3D| J[Chapitre 10: CQRS + Event Sourcing]
    E -->|Décision 3E| K[Chapitre 3.1: Granularité des Choix]
    E -->|Décision 3F| F
    
    F -->|Décision 4A| L[Chapitre 12: Stockage SQL Classique]
    F -->|Décision 4B| M[Chapitre 18: Stockage API Classique]
    F -->|Décision 4C| N[Chapitre 24: Stockage ElasticSearch Classique]
    F -->|Décision 4D| O[Chapitre 30: Stockage MongoDB Classique]
    F -->|Décision 4E| P[Chapitre 36: Stockage In-Memory Classique]
    F -->|Décision 4F| Q[Chapitre 42: Stockage Complexe Temporal]
    
    L -->|Décision 5A| R[Chapitre 42: Gestion des Données]
    L -->|Décision 5B| S[Chapitre 13: Stockage SQL CQS]
    L -->|Décision 5C| T[Chapitre 14: Stockage SQL CQRS]
    L -->|Décision 5D| U[Chapitre 15: Stockage SQL Event Sourcing]
    L -->|Décision 5E| V[Chapitre 16: Stockage SQL Event Sourcing + CQS]
    L -->|Décision 5F| W[Chapitre 17: Stockage SQL Event Sourcing + CQRS]
    
    R -->|Décision 6A| X[Chapitre 43: Pagination et Performance]
    R -->|Décision 6B| Y[Chapitre 44: Gestion d'Erreurs]
    R -->|Décision 6C| Z[Chapitre 45: Tests et Qualité]
    R -->|Décision 6D| X
    
    X --> Y
    Y --> Z
    
    Z -->|Décision 7A| AA[Chapitre 47: Sécurité et Autorisation]
    Z -->|Décision 7B| BB[Chapitre 48: Architecture Frontend]
    Z -->|Décision 7C| AA
    Z -->|Décision 7D| CC[Fin du parcours]
    
    AA --> BB
    BB --> CC
    
    %% Styles
    style A fill:#e1f5fe
    style B fill:#e1f5fe
    style C fill:#ffeb3b
    style D fill:#e1f5fe
    style E fill:#ff9800
    style F fill:#e1f5fe
    style G fill:#ff9800
    style H fill:#ff9800
    style I fill:#ff9800
    style J fill:#e91e63
    style K fill:#ffeb3b
    style L fill:#4caf50
    style M fill:#4caf50
    style N fill:#4caf50
    style O fill:#4caf50
    style P fill:#4caf50
    style Q fill:#ff5722
    style R fill:#fff3e0
    style X fill:#fff3e0
    style Y fill:#fff3e0
    style Z fill:#fff3e0
    style AA fill:#f3e5f5
    style BB fill:#f3e5f5
    style CC fill:#4caf50
```

## 🎯 Points de Décision Principaux

### Décision 1 : Après le Chapitre 3 (Complexité)
*"Maintenant que vous comprenez la différence entre complexité accidentelle et essentielle, quel est votre contexte ?"*

- **Option A** : Équipe junior, application simple → Chapitre 4
- **Option B** : Équipe expérimentée, intégrations multiples → Chapitre 5
- **Option C** : Voir des exemples concrets → Chapitre 4

### Décision 2 : Après le Chapitre 4 (Modèles)
*"Vous maîtrisez maintenant les modèles riches. Quel est votre niveau de complexité ?"*

- **Option A** : Application simple, équipe junior → Chapitre 6 (Parcours Classique)
- **Option B** : Système avec intégrations, besoin de découplage → Chapitre 5
- **Option C** : Voir les options de stockage → Chapitre 6

### Décision 3 : Après le Chapitre 5 (Architecture Événementielle)
*"Vous avez choisi l'architecture événementielle. Quel est votre niveau de complexité et vos besoins ?"*

- **Option A** : Audit trail critique, modèles simples → Chapitre 7 (Event Sourcing)
- **Option B** : Lectures/écritures différentes, un seul modèle → Chapitre 8 (CQS)
- **Option C** : Lectures/écritures très différentes, modèles distincts → Chapitre 9 (CQRS)
- **Option D** : Système très complexe, audit trail + modèles distincts → Chapitre 10 (CQRS + Event Sourcing)

### Décision 4 : Après le Chapitre 6 (Repositories)
*"Vous comprenez les patterns de repository. Quel type de stockage utilisez-vous principalement ?"*

- **Option A** : Base de données SQL → Chapitre 12 (Stockage SQL Classique)
- **Option B** : APIs externes → Chapitre 18 (Stockage API Classique)
- **Option C** : ElasticSearch → Chapitre 24 (Stockage ElasticSearch Classique)
- **Option D** : MongoDB → Chapitre 30 (Stockage MongoDB Classique)
- **Option E** : Stockage In-Memory → Chapitre 36 (Stockage In-Memory Classique)
- **Option F** : Systèmes multiples → Chapitre 42 (Stockage Complexe Temporal)

## 💡 Conseils de Navigation

1. **Commencez toujours** par le Chapitre 1 pour comprendre le contexte
2. **Suivez les choix** à la fin de chaque chapitre
3. **Vous pouvez toujours** revenir en arrière ou explorer d'autres options
4. **Les exemples** sont basés sur le projet Hive et ses ADR
5. **Prenez votre temps** pour comprendre chaque concept avant de passer au suivant

## 🔄 Alternative : Parcours Linéaire

Si vous préférez suivre un parcours linéaire sans choix interactifs, vous pouvez consulter les chapitres dans l'ordre :

1. [Chapitre 1 : Introduction à l'Event Storming et DDD](/chapitres/fondamentaux/chapitre-01-introduction-event-storming-ddd/)
2. [Chapitre 2 : L'Atelier Event Storming - Guide Pratique](/chapitres/fondamentaux/chapitre-02-atelier-event-storming/)
3. [Chapitre 3 : Complexité Accidentelle vs Essentielle](/chapitres/fondamentaux/chapitre-03-complexite-accidentelle-essentielle/)
4. [Chapitre 4 : Modèles Riches vs Modèles Anémiques](/chapitres/fondamentaux/chapitre-04-modeles-riches-vs-anemiques/)
5. [Chapitre 5 : Architecture Événementielle](/chapitres/fondamentaux/chapitre-05-architecture-evenementielle/)
6. [Chapitre 6 : Repositories et Persistance](/chapitres/fondamentaux/chapitre-06-repositories-persistance/)

Et ainsi de suite...
