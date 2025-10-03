---
title: "Chapitre 2 : L'Impact Mapping - Aligner le Produit sur les Objectifs Business"
description: "Découvrir l'Impact Mapping de Gojko Adzic pour aligner le développement produit sur les objectifs business"
date: 2024-12-19
draft: false
type: "docs"
weight: 2
---

## 🎯 Objectif de ce Chapitre

### Mon Problème : L'Écart entre Business et Technique

N'avez-vous pas déjà eu le sentiment que vous construisez un produit pour vous, pas pour vos utilisateurs ? Vous avez une vision technique claire de ce que vous voulez construire. Un système robuste, avec des APIs bien conçues, une architecture évolutive. **Parfait !**

**Mais attendez...** Vos utilisateurs ne comprennent pas pourquoi vous passez 3 mois à refactoriser l'architecture alors qu'ils ont besoin d'une fonctionnalité simple. Le business vous demande des fonctionnalités que vous ne comprenez pas. Les priorités changent constamment.

**Soudain, vous réalisez que vous avez perdu de vue l'objectif business !** Vous développez des fonctionnalités "cool" techniquement, mais qui ne génèrent pas de valeur pour vos clients.

### L'Impact Mapping : l'aide à la décision

L'Impact Mapping, créé par Gojko Adzic, permet de :
- **Aligner** le développement sur les objectifs business
- **Prioriser** les fonctionnalités selon leur impact réel
- **Communiquer** efficacement avec les parties prenantes
- **Éviter** de construire des fonctionnalités inutiles

## Qu'est-ce que l'Impact Mapping ?

### Le Concept Fondamental

L'Impact Mapping est une méthode de planification stratégique qui utilise des cartes pour visualiser les objectifs business et leurs impacts. **L'idée** : Au lieu de commencer par les fonctionnalités, on commence par les objectifs.

**Mais l'Impact Mapping va bien au-delà de la planification.** C'est un **outil d'aide à la décision stratégique** qui transforme la façon dont vous pilotez vos projets, que vous soyez dans l'édition logicielle ou les services de développement.

### L'Impact Mapping : Un Pilier du Pilotage de Projet

#### 🎯 **Au-Delà du Code et de la Technique**

L'Impact Mapping vous permet d'aller **au-delà du code, au-delà de la technique** pour vous concentrer sur ce qui compte vraiment : **l'impact business de vos décisions**.

**Dans l'édition logicielle** : Au lieu de vous demander "Comment implémenter cette fonctionnalité ?", vous vous demandez "Cette fonctionnalité contribue-t-elle à nos objectifs business ?"

**Dans les services de développement** : Au lieu de vous demander "Quelle technologie utiliser ?", vous vous demandez "Cette solution répond-elle aux vrais besoins business du client ?"

#### 🧭 **La Boussole Stratégique**

L'Impact Mapping agit comme une **boussole stratégique** qui vous guide dans vos décisions :

- **Décision de priorisation** : Quelle fonctionnalité développer en premier ?
- **Décision d'architecture** : Quelle approche technique choisir ?
- **Décision de ressource** : Où allouer votre budget et votre temps ?
- **Décision de partenariat** : Avec qui collaborer pour maximiser l'impact ?

#### 📊 **Mesure de l'Impact Réel**

Contrairement aux méthodes traditionnelles qui se concentrent sur les livrables, l'Impact Mapping vous aide à **mesurer l'impact réel** de vos décisions :

- **Impact sur les utilisateurs** : Comment cette décision change-t-elle leur comportement ?
- **Impact sur le business** : Comment cette décision contribue-t-elle aux objectifs ?
- **Impact sur l'équipe** : Comment cette décision améliore-t-elle la productivité ?
- **Impact sur l'écosystème** : Comment cette décision influence-t-elle les partenaires ?

**Avec Gyroscops, voici comment j'ai appliqué l'Impact Mapping** :

### Les 4 Niveaux de l'Impact Mapping

#### 1. **Objectif (Pourquoi ?)** - Le Nord de la Boussole

**Exemple concret avec Gyroscops** : 
- **Objectif** : "Augmenter le chiffre d'affaires de X% en Y mois"
- **Mesurable** : Pourcentage d'augmentation défini
- **Temporel** : Période définie
- **Business** : Chiffre d'affaires

**Pourquoi c'est important ?** Sans objectif clair, on développe des fonctionnalités "parce que c'est cool" ou "parce que c'est technique". Avec un objectif, chaque décision a un sens.

#### 2. **Acteurs (Qui ?)** - Les Personnes qui Comptent

**Exemple concret avec Gyroscops** :
- **Clients existants** : Ceux qui utilisent déjà Gyroscops
- **Prospects** : Ceux qui pourraient utiliser Gyroscops
- **Partenaires** : Ceux qui intègrent Gyroscops dans leurs solutions
- **Équipe interne** : Les développeurs et support

**Pourquoi c'est crucial ?** Chaque acteur a des besoins différents. Un client existant veut de la stabilité, un prospect veut de la simplicité, un partenaire veut de la flexibilité.

#### 3. **Impacts (Comment ?)** - Les Comportements à Influencer

**Exemple concret avec Gyroscops** :
- **Clients existants** : "Renouveler leur abonnement" → Impact sur la rétention
- **Prospects** : "Essayer Gyroscops" → Impact sur l'acquisition
- **Partenaires** : "Intégrer Gyroscops plus facilement" → Impact sur l'écosystème
- **Équipe interne** : "Développer plus rapidement" → Impact sur la productivité

**Pourquoi c'est essentiel ?** On ne peut pas contrôler directement les résultats business, mais on peut influencer les comportements qui y mènent.

#### 4. **Livrables (Quoi ?)** - Les Fonctionnalités à Développer

**Exemple concret avec Gyroscops** :
- **Pour les clients existants** : "Supervision détaillée" → Les aide à voir la valeur
- **Pour les prospects** : "Essai gratuit sans carte bancaire" → Réduit la friction
- **Pour les partenaires** : "Contexte d'architecture pour agents GenAI" → Facilite l'intégration
- **Pour l'équipe interne** : "Tests automatisés" → Accélère le développement

**Pourquoi c'est la clé ?** Chaque livrable doit avoir un impact mesurable sur un acteur spécifique pour contribuer à l'objectif.

### Vue d'Ensemble : L'Impact Map Complet de Gyroscops

Voici une représentation visuelle complète de l'Impact Mapping que nous avons créé pour Gyroscops :

{{< figure src="/images/impact-mapping/gyroscops-impact-map.svg" title="Impact Map Complet - Gyroscops" >}}

**Comment lire cette carte ?**
- 🎯 **Jaune** : L'objectif business (le "Pourquoi")
- 👥 **Vert** : Les segments (acteurs) qui peuvent nous aider (le "Qui")
- 💎 **Cyan** : Les comportements à influencer (le "Comment")
- 📦 **Rouge** : Les fonctionnalités à développer (le "Quoi")

Chaque niveau découle logiquement du précédent, créant une chaîne de valeur claire de l'objectif aux livrables concrets.

## Mon Atelier Impact Mapping avec Gyroscops

### La Préparation

**Voici comment j'ai organisé mon premier Impact Mapping** :

1. **Participants** : Moi (CEO/CTO), le responsable commercial, 2 clients existants
2. **Durée** : 2 heures
3. **Matériel** : Post-its, tableau blanc, marqueurs
4. **Objectif** : "Augmenter le chiffre d'affaires de X% en Y mois"

### L'Atelier en Action

#### Étape 1 : Définir l'Objectif

**Discussion** : "Pourquoi voulons-nous augmenter le CA ?"
- **CEO** : "Pour financer le développement de nouvelles fonctionnalités"
- **Commercial** : "Pour justifier l'investissement auprès des investisseurs"
- **Moi** : "Pour avoir plus de ressources pour l'équipe technique"

**Résultat** : Objectif clarifié et partagé par tous.

#### Étape 2 : Identifier les Acteurs

**Discussion** : "Qui peut nous aider à atteindre cet objectif ?"

**Acteurs identifiés** :
- **Clients existants** (80% du CA actuel)
- **Prospects qualifiés** (pourcentage du CA cible défini)
- **Partenaires technologiques** (nouveaux canaux de vente)
- **Équipe de vente** (conversion des prospects)

**Résultat** : Focus sur les acteurs les plus impactants.

#### Étape 3 : Définir les Impacts

**Discussion** : "Quel comportement de chaque acteur nous aiderait ?"

**Impacts identifiés** :
- **Clients existants** : "Renouveler leur abonnement" + "Upgrader vers un plan plus élevé"
- **Prospects** : "Essayer Gyroscops" + "Convertir en client payant"
- **Partenaires** : "Intégrer Gyroscops" + "Recommander à leurs clients"
- **Équipe de vente** : "Convertir plus de prospects" + "Vendre des plans plus chers"

**Résultat** : Comportements concrets et mesurables.

#### Étape 4 : Proposer les Livrables

**Discussion** : "Quelles fonctionnalités influenceraient ces comportements ?"

**Livrables identifiés** :
- **Pour les clients existants** :
  - Supervision détaillée (voir la valeur)
  - Alertes proactives (éviter les problèmes)
  - Support prioritaire (sentiment de valeur)
- **Pour les prospects** :
  - Essai gratuit sans carte bancaire (réduire la friction)
  - Documentation interactive (faciliter l'adoption)
  - Chat en direct (répondre aux questions)
- **Pour les partenaires** :
  - Contexte d'architecture pour agents GenAI (faciliter l'intégration)
  - Documentation API complète (réduire le temps d'intégration)
  - Programme de partenariat (inciter à recommander)

**Résultat** : Fonctionnalités alignées sur les impacts.

### Les Découvertes Surprenantes

#### 1. **Mes Priorités Techniques n'Étaient pas les Bonnes**

**Avant l'Impact Mapping** : Je voulais refactoriser l'architecture pour la rendre plus évolutive.

**Après l'Impact Mapping** : Les clients voulaient une supervision détaillée simple pour voir leurs intégrations. L'architecture était secondaire.

**Résultat** : J'ai reporté la refactorisation et développé la supervision détaillée. Les clients ont été plus satisfaits et le business s'est amélioré.

#### 2. **Nous ne Savions pas dans quelle Direction Aller**

**Avant l'Impact Mapping** : Nous avions plusieurs idées de fonctionnalités mais aucune vision claire de ce qui était vraiment important pour nos clients.

**Après l'Impact Mapping** : Nous avons identifié que nos clients avaient besoin d'une meilleure visibilité sur leurs intégrations et de processus plus simples.

**Résultat** : Nous avons concentré nos efforts sur les fonctionnalités qui généraient vraiment de la valeur business.

#### 3. **Les Clients Existants Avaient des Besoins Cachés**

**Avant l'Impact Mapping** : Je pensais qu'ils voulaient plus de fonctionnalités.

**Après l'Impact Mapping** : Ils voulaient juste savoir que leurs intégrations fonctionnaient bien.

**Résultat** : J'ai développé le monitoring. Les clients ont été plus satisfaits et la rétention s'est améliorée.

## Les 4 Types de Cartes

### 🎯 Cartes Jaunes : Objectifs

**Exemple avec Gyroscops** :
- "Augmenter le chiffre d'affaires de X% en Y mois"
- "Réduire le taux de churn de X%"
- "Améliorer la satisfaction client"

**Pourquoi c'est important ?** Chaque objectif doit être mesurable et temporel.

### 👥 Cartes Bleues : Acteurs

**Exemple avec Gyroscops** :
- "Clients existants" (80% du CA)
- "Prospects qualifiés" (pourcentage du CA cible défini)
- "Partenaires technologiques" (nouveaux canaux)
- "Équipe de vente" (conversion)

**Pourquoi c'est crucial ?** Chaque acteur a des motivations et des contraintes différentes.

### 🎯 Cartes Vertes : Impacts

**Exemple avec Gyroscops** :
- "Renouveler leur abonnement" (clients existants)
- "Essayer Gyroscops" (prospects)
- "Intégrer Gyroscops" (partenaires)
- "Convertir plus de prospects" (équipe de vente)

**Pourquoi c'est essentiel ?** On ne peut contrôler que les comportements, pas les résultats.

### 📦 Cartes Orange : Livrables

**Exemple avec Gyroscops** :
- "Supervision détaillée" (pour clients existants)
- "Essai gratuit sans carte bancaire" (pour prospects)
- "SDK JavaScript" (pour partenaires)
- "Formation équipe de vente" (pour équipe de vente)

**Pourquoi c'est la clé ?** Chaque livrable doit avoir un impact mesurable.

## Comment Utiliser l'Impact Mapping

### 1. **Avant de Commencer un Projet**

**Avec Gyroscops** : Avant de développer une nouvelle fonctionnalité, je fais toujours un mini Impact Mapping :
- **Objectif** : Quel problème business résout cette fonctionnalité ?
- **Acteurs** : Qui va utiliser cette fonctionnalité ?
- **Impacts** : Quel comportement va-t-elle influencer ?
- **Livrables** : Quelles sont les fonctionnalités concrètes ?

**Résultat** : Plus de fonctionnalités inutiles, plus de focus sur l'impact business.

### 2. **Pendant le Développement**

**Avec Gyroscops** : Quand je développe une fonctionnalité, je me demande constamment :
- "Est-ce que cette fonctionnalité influence le comportement ciblé ?"
- "Est-ce que cet acteur va vraiment utiliser cette fonctionnalité ?"
- "Est-ce que cette fonctionnalité contribue à l'objectif business ?"

**Résultat** : Développement plus ciblé, moins de gaspillage.

### 3. **Pour Prioriser les Fonctionnalités**

**Avec Gyroscops** : Quand j'ai plusieurs fonctionnalités à développer, je les évalue selon :
- **Impact sur l'objectif** : Quelle fonctionnalité contribue le plus à l'objectif ?
- **Impact sur l'acteur** : Quelle fonctionnalité influence le plus le comportement ciblé ?
- **Effort de développement** : Quelle fonctionnalité donne le meilleur ROI ?

**Résultat** : Priorisation basée sur l'impact business, pas sur la complexité technique.

## Les Pièges à Éviter

### 1. **Objectifs Vagues**

**❌ Mauvais** : "Améliorer le produit"
**✅ Bon** : "Augmenter le chiffre d'affaires de X% en Y mois"

**Pourquoi c'est important ?** Un objectif vague ne permet pas de mesurer l'impact des fonctionnalités.

### 2. **Acteurs Trop Génériques**

**❌ Mauvais** : "Les utilisateurs"
**✅ Bon** : "Les développeurs qui intègrent des APIs", "Les responsables IT qui gèrent les intégrations"

**Pourquoi c'est crucial ?** Des acteurs génériques ont des besoins trop différents pour être ciblés efficacement.

### 3. **Impacts Non Mesurables**

**❌ Mauvais** : "Être plus satisfait"
**✅ Bon** : "Renouveler leur abonnement", "Recommander à un collègue"

**Pourquoi c'est essentiel ?** Un impact non mesurable ne permet pas de savoir si la fonctionnalité fonctionne.

### 4. **Livrables Trop Techniques**

**❌ Mauvais** : "API REST"
**✅ Bon** : "API REST qui permet d'intégrer Salesforce en 5 minutes"

**Pourquoi c'est la clé ?** Un livrable technique ne dit pas quel problème business il résout.

## L'Impact Mapping et l'Event Storming

### La Synergie

**L'Impact Mapping** me dit **quoi** développer et **pourquoi**.
**L'Event Storming** me dit **comment** le développer.

**Avec Gyroscops** : 
1. **Impact Mapping** : "Les clients existants veulent une supervision détaillée"
2. **Event Storming** : "Quels événements se produisent dans le système ?" → `IntegrationStarted`, `IntegrationCompleted`, `IntegrationFailed`
3. **Résultat** : Supervision détaillée qui affiche ces événements en temps réel

### La Progression Logique

1. **Impact Mapping** : Définir l'objectif et les acteurs
2. **Event Storming** : Comprendre le domaine métier
3. **Example Mapping** : Détailer les règles métier
4. **Développement** : Implémenter les fonctionnalités

**Résultat** : Développement aligné sur le business et techniquement solide.

### Les Trois Méthodes : Un Système Complet d'Aide à la Décision

#### 🎯 **Impact Mapping** : La Décision Stratégique
- **Décide** quoi développer en fonction des objectifs business
- **Priorise** les fonctionnalités selon leur impact réel
- **Aligne** l'équipe sur les enjeux métier
- **Évite** de construire des fonctionnalités inutiles

#### 🏗️ **Event Storming** : La Décision Architecturale
- **Décide** comment structurer le système métier
- **Révèle** la complexité cachée du domaine
- **Conçoit** l'architecture en collaboration avec les experts métier
- **Évite** les architectures techniques déconnectées du métier

#### 📋 **Example Mapping** : La Décision d'Implémentation
- **Décide** quand et pourquoi appliquer les règles métier
- **Détaille** les cas limites et exceptions
- **Transforme** les règles abstraites en exemples concrets
- **Évite** les malentendus entre business et technique

#### 🔄 **Le Cycle de Décision Complet**

Ces trois méthodes forment un **cycle de décision complet** qui vous guide de la stratégie business jusqu'à l'implémentation technique :

1. **Impact Mapping** → **Décision stratégique** : "Quel est notre objectif business ?"
2. **Event Storming** → **Décision architecturale** : "Comment structurer le système ?"
3. **Example Mapping** → **Décision d'implémentation** : "Comment implémenter les règles ?"
4. **Retour à l'Impact Mapping** → **Mesure de l'impact** : "Avons-nous atteint notre objectif ?"

**Résultat** : Un système de pilotage de projet qui vous guide de la stratégie business jusqu'à la livraison technique, en passant par la conception architecturale.

## 🏗️ Implémentation Concrète dans le Projet Gyroscops Cloud

### Impact Mapping Appliqué à Gyroscops Cloud

Le Gyroscops Cloud applique concrètement les principes de l'Impact Mapping à travers son architecture et ses ADR (Architecture Decision Records). Voici comment :

#### Objectif Business : Plateforme d'Intégration Robuste

**Objectif** : "Créer une plateforme d'intégration qui permet aux entreprises de connecter leurs systèmes sans complexité technique"

**Métriques** :
- Temps de déploiement d'intégration < 5 minutes
- Uptime > 99.9%
- Support de 50+ connecteurs

#### Acteurs Identifiés

**Acteurs principaux :**
- **Développeurs** : Ceux qui intègrent les systèmes
- **Utilisateurs métier** : Ceux qui utilisent les intégrations
- **Administrateurs système** : Ceux qui gèrent l'infrastructure
- **Clients** : Ceux qui paient pour le service

**Acteurs secondaires :**
- **Équipe support** : Aide les utilisateurs
- **Équipe commerciale** : Convertit les prospects
- **Product Manager** : Définit la roadmap

#### Impacts Mesurés

**Productivité développeur :**
- Temps de déploiement d'intégration
- Croissance de l'utilisation des APIs
- Score de satisfaction développeur

**Valeur business :**
- Coût d'acquisition client
- Valeur vie client
- Croissance du chiffre d'affaires

**Fiabilité système :**
- Pourcentage de disponibilité
- Temps moyen de récupération
- Taux d'erreur

#### Livrables Prioritaires

**Priorité 1 : Fonctionnalités Core**
- Intégrations de base
- Plateforme API
- Gestion des utilisateurs

**Priorité 2 : Fonctionnalités Avancées**
- Moteur de workflow
- Tableau de bord de monitoring
- Analytics

**Priorité 3 : Fonctionnalités Nice-to-Have**
- Rapports avancés
- Thèmes personnalisés
- Application mobile

### Exemple Concret : Impact Mapping pour l'Authentification

**Objectif** : "Sécuriser l'accès à la plateforme sans complexifier l'expérience utilisateur"

**Acteurs** :
- **Développeurs** : Veulent une API simple et sécurisée
- **Utilisateurs finaux** : Veulent se connecter facilement
- **Administrateurs** : Veulent gérer les permissions

**Impacts** :
- **Développeurs** : Intégration en < 10 minutes
- **Utilisateurs** : Connexion en < 3 clics
- **Administrateurs** : Gestion centralisée des accès

**Livrables** :
```php
// ✅ Système d'Authentification Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveAuthenticationSystem
{
    public function __construct(
        private KeycloakIntegration $keycloak,
        private JwtTokenService $jwtService,
        private PermissionManager $permissionManager
    ) {}
    
    public function authenticateUser(string $email, string $password): AuthResult
    {
        // Impact : Connexion simple pour l'utilisateur
        $user = $this->keycloak->authenticate($email, $password);
        
        if ($user) {
            $token = $this->jwtService->generateToken($user);
            $permissions = $this->permissionManager->getUserPermissions($user);
            
            return new AuthResult($user, $token, $permissions);
        }
        
        throw new AuthenticationException('Invalid credentials');
    }
    
    public function validateApiToken(string $token): bool
    {
        // Impact : API sécurisée pour les développeurs
        return $this->jwtService->validateToken($token);
    }
    
    public function checkPermission(string $userId, string $resource, string $action): bool
    {
        // Impact : Gestion fine des permissions pour les administrateurs
        return $this->permissionManager->hasPermission($userId, $resource, $action);
    }
}
```

### Exemple Concret : Impact Mapping pour les Intégrations

**Objectif** : "Permettre aux développeurs de créer des intégrations en moins de 5 minutes"

**Acteurs** :
- **Développeurs** : Veulent des connecteurs prêts à l'emploi
- **Business Users** : Veulent des intégrations qui marchent
- **Support** : Veut moins de tickets liés aux intégrations

**Impacts** :
- **Développeurs** : Déploiement d'intégration en < 5 minutes
- **Business Users** : Intégrations fiables et rapides
- **Support** : Réduction de 80% des tickets d'intégration

**Livrables** :
```php
// ✅ Système d'Intégration Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveIntegrationSystem
{
    public function __construct(
        private ConnectorRegistry $connectorRegistry,
        private WorkflowEngine $workflowEngine,
        private MonitoringService $monitoringService
    ) {}
    
    public function createIntegration(IntegrationRequest $request): Integration
    {
        // Impact : Création rapide d'intégration
        $connector = $this->connectorRegistry->getConnector($request->getConnectorType());
        $workflow = $this->workflowEngine->createWorkflow($request->getWorkflowDefinition());
        
        $integration = new Integration(
            $request->getName(),
            $connector,
            $workflow,
            $request->getConfiguration()
        );
        
        // Impact : Monitoring automatique
        $this->monitoringService->setupMonitoring($integration);
        
        return $integration;
    }
    
    public function deployIntegration(Integration $integration): DeploymentResult
    {
        // Impact : Déploiement en < 5 minutes
        $startTime = microtime(true);
        
        try {
            $deployment = $this->workflowEngine->deploy($integration);
            $duration = microtime(true) - $startTime;
            
            return new DeploymentResult($deployment, $duration, true);
        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;
            return new DeploymentResult(null, $duration, false, $e->getMessage());
        }
    }
}
```

### Références aux ADR du Projet Gyroscops Cloud

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du Gyroscops Cloud :
- **HIVE025** : Authorization System - Système d'autorisation basé sur les acteurs
- **HIVE026** : Keycloak Resource and Scope Management - Gestion des ressources et scopes
- **HIVE040** : Enhanced Models with Property Access Patterns - Modèles enrichis pour les acteurs
- **HIVE041** : Cross-Cutting Concerns Architecture - Architecture des préoccupations transversales

**💡 Conseil** : Si vous n'êtes pas sûr, choisissez l'option A pour apprendre la méthode Event Storming, puis continuez avec les autres chapitres dans l'ordre.

**🔄 Alternative** : Si vous voulez tout voir dans l'ordre, commencez par le [Chapitre 3](/chapitres/fondamentaux/chapitre-03-atelier-event-storming/).

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux organiser un atelier Event Storming" 
    subtitle="Vous voulez maîtriser la technique de conception collaborative" 
    criteria="Équipe de développement,Besoin de comprendre le domaine métier,Projet complexe à modéliser,Collaboration nécessaire" 
    time="30-45 minutes" 
    chapter="3" 
    chapter-title="L'Atelier Event Storming - Guide Pratique" 
    chapter-url="/chapitres/fondamentaux/chapitre-03-atelier-event-storming/" 
  >}}
  
  {{< chapter-option 
    letter="B" 
    color="blue" 
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
    color="purple" 
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
    color="yellow" 
    title="Je veux comprendre l'architecture événementielle" 
    subtitle="Vous voulez voir comment structurer votre code autour des événements" 
    criteria="Développeur avec expérience,Besoin de découpler les composants,Système complexe à maintenir,Évolutivité importante" 
    time="25-35 minutes" 
    chapter="8" 
    chapter-title="Architecture Événementielle" 
    chapter-url="/chapitres/fondamentaux/chapitre-08-architecture-evenementielle/" 
  >}}
  
{{< /chapter-nav >}}