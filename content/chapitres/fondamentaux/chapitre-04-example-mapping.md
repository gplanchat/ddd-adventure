---
title: "Chapitre 4 : L'Example Mapping - Détailer les Règles Métier"
description: "Maîtriser l'Example Mapping pour détailler les règles métier complexes découvertes lors de l'Event Storming"
date: 2024-12-19
draft: true
type: "docs"
weight: 4
---

## 🎯 Objectif de ce Chapitre

### Mon Problème : Comment Détailer les Règles Métier Complexes ?

**Voici ce qui s'est passé avec Gyroscops** : Après mon premier Event Storming, j'avais identifié les événements et les processus métier. **Parfait !** J'avais une vision globale du domaine.

**Mais attendez...** Quand j'ai voulu implémenter la suspension d'un utilisateur, j'ai découvert que c'était beaucoup plus complexe que prévu. "Un utilisateur ne peut pas être suspendu s'il a des paiements en cours" - OK, mais qu'est-ce qu'un "paiement en cours" ? Et que se passe-t-il si l'utilisateur a des workflows actifs ? Et ses données personnelles ?

**Soudain, je réalisais que l'Event Storming ne suffisait pas !** Il me fallait une méthode pour détailler les règles métier complexes.

### L'Example Mapping : Mon Détective du Métier

L'Example Mapping, créé par Matt Wynne, m'a permis de :
- **Détailler** les règles métier complexes
- **Clarifier** les cas limites et exceptions
- **Transformer** les exemples en tests d'acceptation
- **Communiquer** efficacement avec les parties prenantes

## Qu'est-ce que l'Example Mapping ?

### Le Concept Fondamental

L'Example Mapping est une technique complémentaire à l'Event Storming qui utilise des cartes colorées pour détailler les règles métier. **L'idée** : Au lieu de partir des événements, on part des règles métier et on les illustre avec des exemples concrets.

**Avec Gyroscops, voici comment j'ai appliqué l'Example Mapping** :

### Les 4 Types de Cartes

#### 1. **Cartes Jaunes : Règles Métier** - Le Cœur du Domaine

**Exemple concret avec Gyroscops** :
- "Un utilisateur ne peut pas être suspendu s'il a des paiements en cours"
- "Un utilisateur suspendu ne peut pas créer de nouveaux workflows"
- "Un workflow ne peut pas être déployé dans une région cloud indisponible"
- "Un paiement ne peut pas être traité pour une organisation suspendue"

**Pourquoi c'est important ?** Les règles métier définissent ce qui est possible et ce qui ne l'est pas. Elles sont le cœur du domaine.

#### 2. **Cartes Vertes : Exemples** - L'Illustration des Règles

**Exemple concret avec Gyroscops** :
- **Règle** : "Un utilisateur ne peut pas être suspendu s'il a des paiements en cours"
- **Exemple** : "Jean a un paiement de 50€ en attente → Il ne peut pas être suspendu"
- **Exemple** : "Marie n'a aucun paiement en attente → Elle peut être suspendue"

**Pourquoi c'est crucial ?** Les exemples rendent les règles concrètes et compréhensibles.

#### 3. **Cartes Bleues : Questions** - Les Cas Limites

**Exemple concret avec Gyroscops** :
- **Règle** : "Un utilisateur ne peut pas être suspendu s'il a des paiements en cours"
- **Question** : "Que se passe-t-il si le paiement est en échec ?"
- **Question** : "Que se passe-t-il si le paiement est en attente depuis 30 jours ?"
- **Question** : "Que se passe-t-il si l'utilisateur a des workflows actifs ?"

**Pourquoi c'est essentiel ?** Les questions révèlent les cas limites et les exceptions.

#### 4. **Cartes Blanches : Scénarios** - Les Histoires Complètes

**Exemple concret avec Gyroscops** :
- **Scénario** : "Suspendre un utilisateur avec paiement en cours"
- **Scénario** : "Suspendre un utilisateur avec workflows actifs"
- **Scénario** : "Suspendre un utilisateur avec données personnelles"

**Pourquoi c'est la clé ?** Les scénarios montrent comment les règles s'appliquent dans des situations réelles.

## Pourquoi l'Example Mapping ?

### 1. **Révéler les Règles Implicites** - Mon Détective du Métier

L'Event Storming révèle les événements, mais l'Example Mapping révèle les règles métier complexes qui les gouvernent. **C'est comme passer d'une photo floue à une photo nette.**

**Exemple concret avec Gyroscops** : Je pensais qu'un "paiement" était traité si le montant était positif. Puis l'Example Mapping a révélé :

**Règles de paiement simples** :
- Un paiement de 1€ est traité ✅
- Un paiement de 1000€ est traité ✅
- Un paiement de 10000€ nécessite une validation manuelle ❌
- Un paiement de 100000€ nécessite une approbation du directeur ❌

**Règles de paiement complexes** :
- Un paiement avec une carte expirée est rejeté ❌
- Un paiement avec une carte volée est rejeté ❌
- Un paiement depuis un pays interdit est rejeté ❌
- Un paiement d'un client en défaut est rejeté ❌

**Règles liées à l'organisation** :
- Un paiement pour une organisation suspendue est rejeté ❌
- Un paiement pour une organisation en défaut de paiement est rejeté ❌
- Un paiement pour une organisation sans workflow actif est rejeté ❌

**Règles liées au workflow** :
- Un paiement pour un workflow suspendu est rejeté ❌
- Un paiement pour un workflow dans une région cloud indisponible est rejeté ❌
- Un paiement pour un workflow avec des ressources insuffisantes est rejeté ❌

**Soudain, j'ai compris pourquoi ma logique de paiement était si complexe !** Ce n'était pas juste un paiement, c'était un écosystème complet : User → Organization → Workflow → Cloud Resources → Billing.

### 2. **Concrétiser l'Abstrait** - De l'Idée à la Réalité

Les exemples concrets rendent les règles métier tangibles et compréhensibles. **Fini les "On devrait peut-être..." et les "Il faudrait que...".**

**Exemple concret avec Gyroscops** : Au lieu de dire "Un paiement peut être traité", nous disions "Un paiement de 100€ avec une carte Visa valide depuis la France peut être traité". **C'est beaucoup plus clair !**

**Voici comment l'Example Mapping a transformé nos discussions** :

**Avant** (discussions abstraites) :
- "Il faut valider les paiements"
- "On doit gérer les cas d'erreur"
- "Il faut respecter les règles de sécurité"
- "Il faut gérer les organisations"
- "Il faut gérer les workflows"
- "Il faut gérer les ressources cloud"

**Après** (exemples concrets) :
- "Un paiement de 50€ avec une carte Visa valide depuis la France pour une organisation active avec un workflow déployé en région Europe est traité automatiquement"
- "Un paiement de 5000€ avec une carte Visa valide depuis la France pour une organisation active avec un workflow déployé en région Europe nécessite une validation manuelle"
- "Un paiement de 50€ avec une carte expirée est rejeté avec le message 'Carte expirée'"
- "Un paiement de 50€ pour une organisation suspendue est rejeté avec le message 'Organisation suspendue'"
- "Un paiement de 50€ pour un workflow dans une région cloud indisponible est rejeté avec le message 'Région cloud indisponible'"
- "Un paiement de 50€ pour un workflow avec des ressources insuffisantes est rejeté avec le message 'Ressources insuffisantes'"

**Résultat** : Plus de malentendus, plus de discussions interminables, plus de "Ah, je pensais que...". Tout le monde comprenait exactement ce qui devait être fait, et nous avons découvert que chaque paiement impliquait une chaîne complète : User → Organization → Workflow → Cloud Resources → Billing.

### 3. **Faciliter les Tests** - De l'Exemple au Code

Les exemples deviennent naturellement des tests d'acceptation. **C'est la magie de l'Example Mapping !**

**Avec Gyroscops, voici ce qui s'est passé** : Après notre session d'Example Mapping sur les paiements, j'ai directement transformé les exemples en tests. Voici un exemple réel du projet Gyroscops Cloud :

```php
/** @test */
public function itShouldHydrateInstanceWithValidData(): void
{
    // 🟢 EXEMPLE : Hydratation d'un paiement avec des données valides (projet Gyroscops Cloud)
    $paymentData = $this->createValidPaymentData();
    
    $result = $this->hydrator->hydrate($paymentData);
    
    $this->assertInstanceOf(Payment::class, $result);
    $this->assertEquals($paymentData['uuid'], $result->uuid->toString());
    $this->assertEquals($paymentData['status'], $result->status->value);
    $this->assertEquals($paymentData['gateway'], $result->gateway->value);
}

/** @test */
public function itShouldRejectInvalidPaymentData(): void
{
    // 🔴 EXEMPLE : Rejet de données de paiement invalides (projet Gyroscops Cloud)
    $this->expectException(MultipleValidationException::class);
    
    $invalidData = [
        'uuid' => 'invalid-uuid',
        'status' => 'invalid-status',
        'gateway' => 'invalid-gateway',
    ];
    
    $this->hydrator->hydrate($invalidData);
}

/** @test */
public function it_requires_manual_validation_for_large_payment(): void
{
    // 🟡 EXEMPLE : Paiement de 5000€ avec carte Visa valide depuis la France pour une organisation active avec un workflow déployé en région Europe
    $organization = Organization::create('Acme Corp', 'active');
    $workflow = Workflow::create($organization, CloudRegion::europe(), 'active');
    $payment = Payment::create(
        PaymentId::generate(),
        Money::euros(5000),
        PaymentMethod::visa('4111111111111111'),
        Country::france(),
        $organization,
        $workflow
    );
    
    $payment->process();
    
    $this->assertEquals(PaymentStatus::PENDING_MANUAL_VALIDATION, $payment->getStatus());
}

/** @test */
public function it_rejects_payment_for_suspended_organization(): void
{
    // 🔴 EXEMPLE : Paiement de 50€ pour une organisation suspendue
    $this->expectException(SuspendedOrganizationException::class);
    $this->expectExceptionMessage('Organisation suspendue');
    
    $organization = Organization::create('Acme Corp', 'suspended');
    $workflow = Workflow::create($organization, CloudRegion::europe(), 'active');
    
    Payment::create(
        PaymentId::generate(),
        Money::euros(50),
        PaymentMethod::visa('4111111111111111'),
        Country::france(),
        $organization,
        $workflow
    );
}

/** @test */
public function it_rejects_payment_for_workflow_in_unavailable_region(): void
{
    // 🔴 EXEMPLE : Paiement de 50€ pour un workflow dans une région cloud indisponible
    $this->expectException(UnavailableCloudRegionException::class);
    $this->expectExceptionMessage('Région cloud indisponible');
    
    $organization = Organization::create('Acme Corp', 'active');
    $workflow = Workflow::create($organization, CloudRegion::asia(), 'active'); // Région indisponible
    
    Payment::create(
        PaymentId::generate(),
        Money::euros(50),
        PaymentMethod::visa('4111111111111111'),
        Country::france(),
        $organization,
        $workflow
    );
}
```

**Résultat** : J'ai écrit mes tests en 30 minutes au lieu de 3 heures ! Et mes tests couvraient exactement les cas métier identifiés par l'équipe, incluant toute la chaîne : User → Organization → Workflow → Cloud Resources → Billing.

## Exemple d'Example Mapping : Traitement d'un Paiement

Voici un exemple concret d'Example Mapping avec des post-it colorés pour illustrer la méthode :

{{< figure src="/images/example-mapping/example-mapping-overview.svg" title="Vue d'ensemble de l'Example Mapping - Traitement d'un Paiement" >}}

### 🟡 **Règles Métier (Post-it Jaunes)**

{{< figure src="/images/example-mapping/rule-payment-pending.svg" title="Règle : Paiement en statut pending uniquement" >}}

{{< figure src="/images/example-mapping/rule-amount-positive.svg" title="Règle : Montant positif obligatoire" >}}

{{< figure src="/images/example-mapping/rule-amount-limit.svg" title="Règle : Ne pas dépasser la limite du compte" >}}

{{< figure src="/images/example-mapping/rule-payment-immutable.svg" title="Règle : Paiement traité non modifiable" >}}

### 🟢 **Exemples Concrets (Post-it Verts)**

{{< figure src="/images/example-mapping/example-valid-payment.svg" title="Exemple : Paiement de 100€ avec compte ayant une limite de 500€" >}}

{{< figure src="/images/example-mapping/example-negative-amount.svg" title="Exemple : Paiement de -50€" >}}

{{< figure src="/images/example-mapping/example-exceed-limit.svg" title="Exemple : Paiement de 600€ avec compte ayant une limite de 500€" >}}

{{< figure src="/images/example-mapping/example-already-processed.svg" title="Exemple : Tentative de traiter un paiement déjà traité" >}}

### 🔴 **Questions à Explorer (Post-it Rouges)**

{{< figure src="/images/example-mapping/question-suspended-account.svg" title="Question : Que se passe-t-il si le compte est suspendu ?" >}}

{{< figure src="/images/example-mapping/question-time-limit.svg" title="Question : Y a-t-il une limite de temps pour traiter un paiement ?" >}}

{{< figure src="/images/example-mapping/question-partial-payment.svg" title="Question : Peut-on traiter un paiement partiellement ?" >}}

### 🔵 **Scénario d'Usage (Post-it Bleu)**

{{< figure src="/images/example-mapping/scenario-payment-process.svg" title="Scénario : Processus complet de paiement" >}}

**Processus complet de paiement :**
1. Client initie un paiement
2. Système valide le montant
3. Système vérifie la limite du compte
4. Système traite le paiement
5. Système envoie une confirmation

## Comment Utiliser l'Example Mapping

### 1. **Après l'Event Storming**
Utilisez l'Example Mapping pour détailler les événements les plus complexes découverts lors de l'Event Storming.

### 2. **En Petite Équipe**
3-5 personnes maximum : un expert métier, un développeur, un testeur.

### 3. **Durée Limitée**
30-45 minutes par fonctionnalité pour éviter l'over-engineering.

### 4. **Focus sur les Cas Limites**
Concentrez-vous sur les règles métier complexes et les cas d'erreur.

## Exemple Concret : Règles de Paiement

```php
// Les exemples de l'Example Mapping deviennent des tests
class PaymentTest extends TestCase
{
    /** @test */
    public function it_processes_a_valid_payment(): void
    {
        // 🟢 EXEMPLE : Paiement de 100€ avec compte ayant une limite de 500€
        $payment = Payment::create(
            PaymentId::generate(),
            Money::euros(100)
        );
        
        $account = Account::withLimit(Money::euros(500));
        
        $payment->process($account);
        
        $this->assertEquals(PaymentStatus::PROCESSED, $payment->getStatus());
    }
    
    /** @test */
    public function it_rejects_negative_amount(): void
    {
        // 🟢 EXEMPLE : Paiement de -50€
        $this->expectException(InvalidAmountException::class);
        
        Payment::create(
            PaymentId::generate(),
            Money::euros(-50)
        );
    }
    
    /** @test */
    public function it_rejects_amount_exceeding_limit(): void
    {
        // 🟢 EXEMPLE : Paiement de 600€ avec compte ayant une limite de 500€
        $payment = Payment::create(
            PaymentId::generate(),
            Money::euros(600)
        );
        
        $account = Account::withLimit(Money::euros(500));
        
        $this->expectException(AmountExceedsLimitException::class);
        
        $payment->process($account);
    }
}
```

## Avantages de l'Example Mapping

1. **Clarté des Règles** : Les exemples concrets clarifient les règles métier
2. **Tests Automatiques** : Les exemples deviennent des tests d'acceptation
3. **Communication** : Toute l'équipe comprend les mêmes règles
4. **Évolution** : Facile d'ajouter de nouveaux exemples quand les règles changent

## Mon Premier Example Mapping avec Gyroscops

### La Préparation

**Voici comment j'ai organisé mon premier Example Mapping** :

1. **Participants** : Moi (CTO), le CEO, le responsable commercial, 2 clients existants
2. **Durée** : 2 heures
3. **Matériel** : Post-its de 4 couleurs, marqueurs, tableau blanc
4. **Focus** : "Suspendre un utilisateur" (règle complexe identifiée lors de l'Event Storming)

### L'Atelier en Action

#### Étape 1 : Identifier la Règle Métier

**Discussion** : "Quelle est la règle pour suspendre un utilisateur ?"

**Règle identifiée** : "Un utilisateur ne peut pas être suspendu s'il a des paiements en cours"

**Résultat** : Règle claire et partagée par tous.

#### Étape 2 : Illustrer avec des Exemples

**Discussion** : "Donnez-moi des exemples concrets"

**Exemples identifiés** :
- **Exemple 1** : "Jean a un paiement de 50€ en attente → Il ne peut pas être suspendu"
- **Exemple 2** : "Marie n'a aucun paiement en attente → Elle peut être suspendue"
- **Exemple 3** : "Pierre a un paiement de 100€ en attente → Il ne peut pas être suspendu"

**Résultat** : Règle illustrée avec des exemples concrets.

#### Étape 3 : Identifier les Questions

**Discussion** : "Quelles questions vous viennent à l'esprit ?"

**Questions identifiées** :
- "Que se passe-t-il si le paiement est en échec ?"
- "Que se passe-t-il si le paiement est en attente depuis 30 jours ?"
- "Que se passe-t-il si l'utilisateur a des workflows actifs ?"
- "Que se passe-t-il si l'utilisateur a des données personnelles ?"

**Résultat** : Cas limites et exceptions identifiés.

#### Étape 4 : Développer les Scénarios

**Discussion** : "Comment ces règles s'appliquent-elles dans des situations réelles ?"

**Scénarios identifiés** :
- **Scénario 1** : "Suspendre un utilisateur avec paiement en cours"
  - Règle : "Un utilisateur ne peut pas être suspendu s'il a des paiements en cours"
  - Exemple : "Jean a un paiement de 50€ en attente"
  - Résultat : "Jean ne peut pas être suspendu"
- **Scénario 2** : "Suspendre un utilisateur avec workflows actifs"
  - Règle : "Un utilisateur suspendu ne peut pas créer de nouveaux workflows"
  - Exemple : "Marie a 3 workflows actifs"
  - Résultat : "Marie peut être suspendue, mais ses workflows restent actifs"

**Résultat** : Scénarios complets et testables.

### Les Découvertes Surprenantes

#### 1. **Les Règles Métier Cachées**

**Avant l'Example Mapping** : Je pensais que suspendre un utilisateur était simple.

**Après l'Example Mapping** : J'ai découvert que suspendre un utilisateur impliquait de gérer ses paiements, ses workflows, ses intégrations, et ses données.

**Résultat** : J'ai compris pourquoi cette fonctionnalité était si complexe à implémenter !

#### 2. **Les Cas Limites Complexes**

**Avant l'Example Mapping** : Je pensais que "paiement en cours" était clair.

**Après l'Example Mapping** : J'ai découvert qu'il fallait gérer les paiements en échec, les paiements en attente, les paiements expirés, etc.

**Résultat** : J'ai compris pourquoi mes tests étaient si fragiles !

#### 3. **Les Exceptions Métier**

**Avant l'Example Mapping** : Je pensais que les règles étaient absolues.

**Après l'Example Mapping** : J'ai découvert qu'il y avait des exceptions pour les cas d'urgence, les cas de force majeure, etc.

**Résultat** : J'ai compris pourquoi mes règles métier étaient si rigides !

## Comment Utiliser l'Example Mapping

### 1. **Avant de Commencer un Projet**

**Avec Gyroscops** : Avant de développer une nouvelle fonctionnalité, je fais toujours un mini Example Mapping :
- **Règle** : Quelle est la règle métier principale ?
- **Exemples** : Quels sont les exemples concrets ?
- **Questions** : Quelles sont les questions qui se posent ?
- **Scénarios** : Quels sont les scénarios complets ?

**Résultat** : Fonctionnalité bien comprise avant le développement.

### 2. **Pendant le Développement**

**Avec Gyroscops** : Quand je développe une fonctionnalité, je me demande constamment :
- "Est-ce que cette règle métier est bien implémentée ?"
- "Est-ce que ces exemples sont bien couverts ?"
- "Est-ce que ces questions sont bien gérées ?"
- "Est-ce que ces scénarios sont bien testés ?"

**Résultat** : Développement guidé par les règles métier.

### 3. **Pour Écrire les Tests**

**Avec Gyroscops** : Quand j'écris les tests, je transforme les exemples en tests d'acceptation :
- **Exemple** : "Jean a un paiement de 50€ en attente → Il ne peut pas être suspendu"
- **Test** : `it_prevents_suspending_user_with_pending_payment()`
- **Résultat** : Tests basés sur les exemples métier

**Résultat** : Tests qui reflètent la réalité métier.

## Les Pièges à Éviter

### 1. **Règles Trop Génériques**

**❌ Mauvais** : "Un utilisateur ne peut pas être suspendu"
**✅ Bon** : "Un utilisateur ne peut pas être suspendu s'il a des paiements en cours"

**Pourquoi c'est important ?** Des règles trop génériques ne permettent pas de comprendre les cas spécifiques.

### 2. **Exemples Trop Abstraits**

**❌ Mauvais** : "Un utilisateur avec des paiements"
**✅ Bon** : "Jean a un paiement de 50€ en attente"

**Pourquoi c'est crucial ?** Des exemples trop abstraits ne permettent pas de comprendre la règle.

### 3. **Questions Non Résolues**

**❌ Mauvais** : "Que se passe-t-il si... ?" (sans réponse)
**✅ Bon** : "Que se passe-t-il si le paiement est en échec ?" → "Le paiement est annulé et l'utilisateur peut être suspendu"

**Pourquoi c'est essentiel ?** Des questions non résolues créent de l'incertitude.

### 4. **Scénarios Incomplets**

**❌ Mauvais** : "Suspendre un utilisateur" (sans détails)
**✅ Bon** : "Suspendre un utilisateur avec paiement en cours" (avec règle, exemple, et résultat)

**Pourquoi c'est la clé ?** Des scénarios incomplets ne permettent pas de tester la fonctionnalité.

## L'Example Mapping et l'Event Storming

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

### Example Mapping Appliqué à Gyroscops Cloud

Le projet Gyroscops Cloud applique concrètement les principes de l'Example Mapping à travers ses tests et ses ADR (Architecture Decision Records). Voici comment :

#### Exemples Concrets d'Example Mapping

**Règle Métier** : "Un utilisateur doit pouvoir se connecter avec son email et son mot de passe"

**Exemples** :
- ✅ **Email valide + mot de passe correct** → Connexion réussie
- ❌ **Email invalide + mot de passe correct** → Erreur de validation
- ❌ **Email valide + mot de passe incorrect** → Erreur d'authentification
- ❌ **Email vide + mot de passe vide** → Erreur de validation

**Questions** :
- Que se passe-t-il si l'utilisateur est désactivé ?
- Que se passe-t-il si l'utilisateur a trop de tentatives de connexion ?
- Que se passe-t-il si l'utilisateur n'a pas confirmé son email ?

#### Implémentation des Exemples

```php
// ✅ Tests d'Example Mapping Gyroscops Cloud (projet Gyroscops Cloud)
final class UserAuthenticationExampleMappingTest extends TestCase
{
    /** @test */
    public function itShouldAuthenticateUserWithValidCredentials(): void
    {
        // Given: Un utilisateur avec des identifiants valides
        $user = UserFixtures::createActiveUser();
        $email = $user->getEmail();
        $password = 'validPassword123!';
        
        // When: L'utilisateur tente de se connecter
        $result = $this->authenticationService->authenticate($email, $password);
        
        // Then: La connexion doit réussir
        $this->assertTrue($result->isSuccess());
        $this->assertEquals($user->getId(), $result->getUser()->getId());
    }
    
    /** @test */
    public function itShouldRejectInvalidEmail(): void
    {
        // Given: Un email invalide
        $email = 'invalid-email';
        $password = 'validPassword123!';
        
        // When: L'utilisateur tente de se connecter
        $result = $this->authenticationService->authenticate($email, $password);
        
        // Then: La connexion doit échouer
        $this->assertFalse($result->isSuccess());
        $this->assertStringContains('Invalid email format', $result->getError());
    }
    
    /** @test */
    public function itShouldRejectIncorrectPassword(): void
    {
        // Given: Un utilisateur avec un mot de passe incorrect
        $user = UserFixtures::createActiveUser();
        $email = $user->getEmail();
        $password = 'wrongPassword';
        
        // When: L'utilisateur tente de se connecter
        $result = $this->authenticationService->authenticate($email, $password);
        
        // Then: La connexion doit échouer
        $this->assertFalse($result->isSuccess());
        $this->assertStringContains('Invalid credentials', $result->getError());
    }
    
    /** @test */
    public function itShouldRejectEmptyCredentials(): void
    {
        // Given: Des identifiants vides
        $email = '';
        $password = '';
        
        // When: L'utilisateur tente de se connecter
        $result = $this->authenticationService->authenticate($email, $password);
        
        // Then: La connexion doit échouer
        $this->assertFalse($result->isSuccess());
        $this->assertStringContains('Email and password are required', $result->getError());
    }
}
```

#### Règles Métier Découvertes

```php
// ✅ Règles Métier Gyroscops Cloud (projet Gyroscops Cloud)
final class HiveBusinessRules
{
    // Règles d'Authentification
    public const USER_EMAIL_MUST_BE_VALID = 'user.email.must.be.valid';
    public const USER_PASSWORD_MUST_BE_STRONG = 'user.password.must.be.strong';
    public const USER_MUST_BE_ACTIVE = 'user.must.be.active';
    public const USER_MUST_HAVE_CONFIRMED_EMAIL = 'user.must.have.confirmed.email';
    
    // Règles de Paiement
    public const PAYMENT_AMOUNT_MUST_BE_POSITIVE = 'payment.amount.must.be.positive';
    public const PAYMENT_CURRENCY_MUST_BE_SUPPORTED = 'payment.currency.must.be.supported';
    public const PAYMENT_CUSTOMER_MUST_EXIST = 'payment.customer.must.exist';
    
    // Règles d'Intégration
    public const INTEGRATION_CONFIG_MUST_BE_VALID = 'integration.config.must.be.valid';
    public const INTEGRATION_MUST_PASS_TESTS = 'integration.must.pass.tests';
    public const INTEGRATION_MUST_HAVE_MONITORING = 'integration.must.have.monitoring';
}
```

### Références aux ADR du projet Gyroscops Cloud

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du projet Gyroscops Cloud :
- **HIVE027** : PHPUnit Testing Standards - Standards de tests PHPUnit
- **HIVE023** : Repository Testing Strategies - Stratégies de tests des repositories
- **HIVE028** : Faker for test data generation - Génération de données de test avec Faker
- **HIVE040** : Enhanced Models with Property Access Patterns - Modèles enrichis pour les tests

---

### 🟣 Option E : Je veux comprendre la granularité des choix architecturaux
*Vous voulez savoir comment choisir l'architecture au bon niveau*

**Critères** :
- Équipe expérimentée
- Besoin de comprendre la granularité
- Choix architecturaux à faire
- Cohérence à maintenir

**Temps estimé** : 20-30 minutes

→ **[Aller au Chapitre 6](/chapitres/fondamentaux/chapitre-06-granularite-choix-architecturaux/)** (Granularité des Choix Architecturaux)

---

**💡 Conseil** : Si vous n'êtes pas sûr, choisissez l'option A pour comprendre l'architecture événementielle, puis continuez avec les autres chapitres dans l'ordre.

**🔄 Alternative** : Si vous voulez tout voir dans l'ordre, commencez par le [Chapitre 5](/chapitres/fondamentaux/chapitre-05-complexite-accidentelle-essentielle/).

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
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
    letter="B" 
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
    letter="C" 
    color="green" 
    title="Je veux comprendre l'architecture événementielle" 
    subtitle="Vous voulez voir comment structurer votre code autour des événements" 
    criteria="Développeur avec expérience,Besoin de découpler les composants,Système complexe à maintenir,Évolutivité importante" 
    time="25-35 minutes" 
    chapter="8" 
    chapter-title="Architecture Événementielle" 
    chapter-url="/chapitres/fondamentaux/chapitre-08-architecture-evenementielle/" 
  >}}
  
  {{< chapter-option 
    letter="D" 
    color="blue" 
    title="Je veux comprendre les repositories et la persistance" 
    subtitle="Vous voulez voir comment gérer la persistance des données" 
    criteria="Développeur avec expérience,Besoin de comprendre la persistance,Architecture à définir,Patterns de stockage à choisir" 
    time="25-35 minutes" 
    chapter="9" 
    chapter-title="Repositories et Persistance" 
    chapter-url="/chapitres/fondamentaux/chapitre-09-repositories-persistance/" 
  >}}
  
{{< /chapter-nav >}}
