---
title: "Chapitres Optionnels"
description: "Les patterns avancés (CQRS, Event Sourcing, etc.) pour les équipes expérimentées"
date: 2024-12-19
draft: true
type: "docs"
weight: 2
---

Ces chapitres présentent les patterns avancés pour les équipes expérimentées. Ils ne sont pas nécessaires pour tous les projets, mais offrent des solutions puissantes pour les systèmes complexes.

## Prérequis

Avant d'explorer ces chapitres, assurez-vous d'avoir :
- Maîtrisé les [Chapitres Fondamentaux](/chapitres/fondamentaux/)
- Une équipe expérimentée (3+ développeurs)
- Un projet avec des besoins complexes
- Du temps et un budget suffisants

## 📚 Liste des Chapitres

### [Chapitre 10 : Event Sourcing - La Source de Vérité](/chapitres/optionnels/chapitre-15-event-sourcing/)
- Concepts d'Event Sourcing
- Stockage des événements comme source de vérité
- Reconstruction d'état
- Event Sourcing sans CQRS

### [Chapitre 11 : Architecture CQS - Command Query Separation](/chapitres/optionnels/chapitre-15-architecture-cqs/)
- Séparation Command/Query dans un seul modèle
- Propriétés en lecture publique, modification par méthodes
- Alternative plus simple au CQRS
- Intégration avec Event Sourcing

### [Chapitre 12 : Architecture CQRS avec API Platform](/chapitres/optionnels/chapitre-15-architecture-cqrs/)
- Séparation Command/Query avec modèles distincts
- Query Models et Command Models
- Intégration API Platform
- CQRS sans Event Sourcing

### [Chapitre 13 : CQRS + Event Sourcing Combinés](/chapitres/optionnels/chapitre-15-cqrs-event-sourcing-combines/)
- Architecture combinée complète
- Avantages et inconvénients
- Complexité technique élevée
- Maximum de flexibilité

### [Chapitre 14 : Projections Event Sourcing](/chapitres/optionnels/chapitre-15-projections-event-sourcing/)
- Concepts de projection dans l'Event Sourcing
- Reconstruction des vues de lecture
- Gestion des projections en temps réel
- Projections vs Stockage complexe

## Critères d'Adoption

### Event Sourcing (Seul)
- ✅ **Adoptez si** : Audit trail critique, debugging complexe, évolution fréquente des vues
- ❌ **Évitez si** : Application simple, équipe peu expérimentée, performance critique

### CQS (Command Query Separation)
- ✅ **Adoptez si** : Lectures/écritures différentes, besoin de performance, équipe intermédiaire
- ❌ **Évitez si** : Modèles identiques, équipe très junior

### CQRS (Command Query Responsibility Segregation)
- ✅ **Adoptez si** : Lectures/écritures très différentes, équipes séparées, performance critique
- ❌ **Évitez si** : Application simple, modèles similaires, équipe petite

### Event Sourcing + CQRS Combinés
- ✅ **Adoptez si** : Audit trail critique, performance critique, équipe très expérimentée
- ❌ **Évitez si** : Application simple, équipe peu expérimentée, budget limité

## Conseil

Ces patterns sont puissants mais complexes. Commencez par les [Chapitres Fondamentaux](/chapitres/fondamentaux/) et n'ajoutez ces patterns que si vous en avez vraiment besoin.
