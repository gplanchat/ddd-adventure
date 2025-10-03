---
title: "Chapitre 1 : Introduction au Domain-Driven Design et Event Storming"
description: "Découvrir le Domain-Driven Design d'Eric Evans, l'Event Storming et l'Example Mapping pour révéler la complexité métier"
date: 2024-12-19
draft: false
type: "docs"
weight: 1
---

## 🎯 Objectif de ce Chapitre

### Un Récit Personnel

Il est 17h30, je viens de passer 3 heures dans xdebug pour un bug qui semblait simple. Le problème ? Une modification 
dans une partie du code a cassé quelque chose dans une autre partie, à des kilomètres de là. Je me demande : "Comment en
sommes-nous arrivés là ?"

**Cette situation vous dit quelque chose ?**

Moi, je l'ai vécue des dizaines de fois :
- J'ajoute une fonctionnalité et 3 autres se cassent
- Chaque modification nécessite de toucher à 5 fichiers différents
- Je ne comprends plus pourquoi le code fait ce qu'il fait
- Les tests passent mais l'application ne fonctionne pas comme attendu
- J'ai peur de modifier du code "qui marche"

### La Complexité qui s'installe

Ce n'était pas de ma faute. Mon projet Gyroscops a commencé avec de bonnes intentions : une architecture simple, du code
propre, des tests. Mais quelque part sur le chemin, la complexité s'est installée insidieusement. Elle se cachait
derrière des noms de variables trompeurs ou simplistes, des méthodes qui faisaient trop de choses, des dépendances
cachées.

**Le problème fondamental ?** J'ai construit mon logiciel comme si le métier était simple. Lorsque l'on démarre un 
projet, on a une vision partielle de ce qui sera nécessaire. De fait, j'ai essayé de forcer la réalité métier dans des 
structures techniques rigides, et cela a fini par rendre la maintenance insoutenable. Pour ajouter à cette complexité
existante, j'ai voulu fournir le service sous la forme de SaaS, ce qui a ajouté un niveau de complexité
technique supplémentaire.

**Le résultat ?** J'ai dû bloquer toutes les évolutions du produit pendant 2 mois pour remettre l'application dans un
état qui soit plus facile à maintenir.

### Ce Chapitre Change la Donne

Ce chapitre pose les fondations de l'approche que l'on a développée pour éviter de retomber dans ces pièges :
- **Les principes fondamentaux** du Domain-Driven Design selon Eric Evans
- **Pourquoi le CRUD limite vos mouvements** et vous fait perdre du temps
- **Pourquoi les modèles anémiques** vous empêchent d'avancer plus vite
- **Comment l'Impact Mapping** aligne le produit sur les objectifs business
- **Comment l'Event Storming** révèle la complexité métier cachée
- **Comment l'Example Mapping** détaille les règles métier complexes

### Les Trois Piliers du Pilotage de Projet

**L'Impact Mapping, l'Event Storming et l'Example Mapping ne sont pas seulement des techniques de développement.** Ce sont les **piliers fondamentaux de l'aide à la décision stratégique** dans le monde de l'édition logicielle et des services de développement.

Ces trois méthodes vous permettent d'aller **au-delà du code, au-delà de la technique** pour vous concentrer sur ce qui compte vraiment :

#### 🎯 **L'Impact Mapping** : La Boussole Stratégique
- **Décide QUOI** développer en fonction des objectifs business
- **Priorise** les fonctionnalités selon leur impact réel
- **Aligne** l'équipe technique sur les enjeux métier
- **Évite** de construire des fonctionnalités inutiles

#### 🏗️ **L'Event Storming** : Le Plan Architectural
- **Décide COMMENT** structurer le système métier
- **Révèle** la complexité cachée du domaine
- **Conçoit** l'architecture en collaboration avec les experts métier
- **Évite** les architectures techniques déconnectées du métier

#### 📋 **L'Example Mapping** : Le Guide d'Implémentation
- **Décide QUAND** et **POURQUOI** appliquer les règles métier
- **Détaille** les cas limites et exceptions
- **Transforme** les règles abstraites en exemples concrets
- **Évite** les malentendus entre business et technique

**Ensemble, ces trois méthodes constituent un système complet d'aide à la décision** qui vous guide de la stratégie business jusqu'à l'implémentation technique, en passant par la conception architecturale.

### Pourquoi cette Démarche ?

**L'arrivée de la GenAI et de la programmation agentique transforme progressivement le métier de développeur.** Cette évolution change la façon dont nous concevons et développons des logiciels.

**La nouvelle réalité :** Ceux qui maîtrisent l'architecture métier et savent **orchestrer les outils IA** sont plus efficaces.

**→ [Découvrir pourquoi ces méthodes sont importantes dans l'ère de la GenAI](/concept/mutation-metier-developpeur/)**

### Savoir bien structurer son monolithe modulaire 

**Voici ce que développer Gyroscops a révélé** : Même quand on démarre un projet, il est important de construire un
monolithe modulaire bien structuré. Le sujet des Micro-services a toujours été exclu dans mon cas, mais je suis tombé
dans le piège de la complexité à cause de contraintes techniques que je n'ai pas tout de suite séparé des contraintes
métiers.

Depuis 2008, j'ai beaucoup travaillé dans le milieu de l'e-commerce, souvent pour récupérer des projets en souffrance.
Quand on fait de l'intégration d'une solution existante, on est guidé, on peut rester dans les rails de ce que l'éditeur
a prévu pour nous. Cependant, les projets qui échouent sont souvent ceux où l'équipe de développement n'a pas pris
suffisamment de temps pour réfléchir au besoin métier. J'ai souvent vu des équipes exploser leur codebase en 15 services
"indépendants" qui finissent par dépendre les uns des autres. Je l'ai probablement fait en début de carrière.

**Aujourd'hui, je préfère un monolithe modulaire** :
- Bien découpé en domaines fonctionnels clairs
- Avec des interfaces internes bien définies
- Testable, maintenable, lisible
- Déployable en un clic

Et le jour où un module deviendra vraiment trop gros ou trop critique, là je réfléchirais à la possibilité de l'extraire
en microservice. Mais je pars du besoin, pas du dogme.

**Le microservice doit être un outil. Pas une posture.**

## Le Domain-Driven Design : Une Approche Centrée sur le Métier

### Les Fondements selon Eric Evans

Le Domain-Driven Design (DDD) est une approche de développement logiciel qui place le domaine métier au cœur de la
conception. Eric Evans, dans son livre fondateur "Domain-Driven Design: Tackling Complexity in the Heart of Software",
nous enseigne que :

> "Le logiciel doit refléter le domaine métier, pas l'inverse."

### Les Concepts Clés du DDD

#### 1. Le Langage Ubiquitaire (Ubiquitous Language)

Le langage utilisé par l'équipe de développement doit être le même que celui du domaine métier. Pas de traduction, pas 
de jargon technique qui éloigne du métier. Pas de charge mentale pour se souvenir de chaque définition en fonction du
contexte.

#### 2. Les Bounded Contexts

Chaque contexte métier a ses propres modèles, sa propre logique. Un "Client" dans le contexte "Ventes" n'est pas le même
qu'un "Client" dans le contexte "Cloud".

#### 3. Les Agrégats

Des grappes d'objets métier qui sont traités comme une unité cohérente. L'agrégat protège ses invariants métier. 

#### 4. Les Value Objects

Des objets immuables qui représentent des concepts métier par leur valeur, pas par leur identité.

**Exemple concret du projet Gyroscops Cloud** :

```php
use Assert\Assertion;
use Brick\Math\BigDecimal;

// ✅ Value Object - Price
final readonly class Price
{
    private function __construct(
        public BigDecimal $amount,
        public Currencies $currency,
    ) {
        Assertion::true($this->amount->isGreaterThan(0));
    }

    public static function inEuros(BigDecimal $amount): self
    {
        return new self($amount, Currencies::Euro);
    }

    public function plus(BigNumber|string $number): self
    {
        return new self(
            $this->amount->plus($number),
            $this->currency,
        );
    }

    public function multipliedBy(BigNumber|string $number): self
    {
        return new self(
            $this->amount->multipliedBy($number),
            $this->currency,
        );
    }

    public function isGreaterThanOrEqualTo(self $price): bool
    {
        if ($price->currency !== $this->currency) {
            throw new CurrencyMismatchException('Currency conversion is not supported');
        }

        return $this->amount->isGreaterThanOrEqualTo($price->amount);
    }

    public function isEqualTo(self $price): bool
    {
        if ($price->currency !== $this->currency) {
            throw new CurrencyMismatchException('Currency conversion is not supported');
        }

        return $this->amount->isEqualTo($price->amount);
    }

    // etc.
}
```

**Ce que cet exemple montre** :
- **Immutabilité** : `readonly` et constructeur privé empêchent la modification, chaque opération de modification produit la création d'une nouvelle instance d'objet modifié
- **Validation** : Le constructeur valide que le montant n'est pas négatif
- **Logique métier** : Les opérations arithmétiques respectent les règles métier
- **Comparaison par valeur** : Deux `Price` avec le même montant et la même devise sont égaux
- **Encapsulation** : La logique de calcul des prix est centralisée dans le Value Object

### Exemple Concret : Un Système de Paiement

Voici un exemple réel tiré du projet Gyroscops Cloud, montrant comment l'approche DDD guide la conception :

```php
use Assert\Assertion;

// ✅ Approche DDD - Le domaine métier guide la conception
final class Payment
{
    public function __construct(
        public readonly PaymentId $uuid,
        public readonly RealmId $realmId,
        public readonly OrganizationId $organizationId,
        public readonly SubscriptionId $subscriptionId,
        private ?\DateTimeInterface $creationDate = null,
        private ?\DateTimeInterface $expirationDate = null,
        private ?\DateTimeInterface $completionDate = null,
        private ?Statuses $status = null,
        private ?Gateways $gateway = null,
        private ?Price $subtotal = null,
        private ?Price $discount = null,
        private ?Price $taxes = null,
        private ?Price $total = null,
        private ?Price $captured = null,
        private array $events = [],
        private int $version = 0,
    ) {}

    public static function registerOnlinePayment(
        PaymentId $uuid,
        RealmId $realmId,
        OrganizationId $organizationId,
        SubscriptionId $subscriptionId,
        \DateTimeInterface $creationDate,
        \DateTimeInterface $expirationDate,
        string $customerName,
        string $customerEmail,
        Price $subtotal,
        Price $discount,
        Price $vat,
        Price $total,
    ): self {
        $instance = new self($uuid, $realmId, $organizationId, $subscriptionId);
        
        // Logique métier
        Assertion::true($this->canTransitionTo(Statuses::Pending));

        $instance->recordThat(new RegisteredPaymentEvent(
            uuid: $uuid,
            version: 1,
            realmId: $realmId,
            organizationId: $organizationId,
            subscriptionId: $subscriptionId,
            creationDate: $creationDate,
            expirationDate: $expirationDate,
            customerName: $customerName,
            customerEmail: $customerEmail,
            status: Statuses::Pending,
            subtotal: $subtotal,
            discount: $discount,
            taxes: $vat,
            total: $total,
        ));

        return $instance;
    }

    private function applyRegisteredPaymentEvent(RegisteredPaymentEvent $event): void
    {
        // Changement de l'état
    }

    public function authorize(Gateways $gateway, Price $amount, \DateTimeInterface $authorizationDate): void
    {
        // Logique métier
        Assertion::true($this->canTransitionTo(Statuses::Authorized));
        
        $this->recordThat(new AuthorizedEvent(
            uuid: $this->uuid,
            version: $this->version + 1,
            realmId: $this->realmId,
            organizationId: $this->organizationId,
            subscriptionId: $this->subscriptionId,
            status: Statuses::Authorized,
            gateway: $gateway,
            amount: $amount,
            authorizationDate: $authorizationDate,
        ));
    }

    private function applyAuthorizedEvent(AuthorizedEvent $event): void
    {
        // Changement de l'état
    }

    public function capture(Gateways $gateway, Price $amount, \DateTimeInterface $completionDate): void
    {
        // Logique métier
        Assertion::true($this->canTransitionTo(Statuses::Completed));
        
        $this->recordThat(new CapturedEvent(
            uuid: $this->uuid,
            version: $this->version + 1,
            realmId: $this->realmId,
            organizationId: $this->organizationId,
            subscriptionId: $this->subscriptionId,
            status: Statuses::Completed,
            gateway: $gateway,
            amount: $amount,
            completionDate: $completionDate,
        ));
    }

    private function applyCapturedEvent(CapturedEvent $event): void
    {
        // Changement de l'état
    }

    public function fail(Gateways $gateway, Price $amount, \DateTimeInterface $failureDate, string $reason): void
    {
        // Logique métier
        Assertion::true($this->canTransitionTo(Statuses::Failed));

        $this->recordThat(new FailedEvent(
            uuid: $this->uuid,
            version: $this->version + 1,
            realmId: $this->realmId,
            organizationId: $this->organizationId,
            subscriptionId: $this->subscriptionId,
            status: Statuses::Failed,
            gateway: $gateway,
            amount: $amount,
            failureDate: $failureDate,
            reason: $reason,
        ));
    }

    private function applyFailedEvent(FailedEvent $event): void
    {
        // Changement de l'état
    }

    private function canTransitionTo(Statuses $status): bool
    {
        return match ($this->status) {
            Statuses::Pending => match ($status) {
                null, Statuses::Pending, Statuses::Authorized, Statuses::Completed, Statuses::Cancelled, Statuses::Failed => true,
            },
            Statuses::Authorized => match ($status) {
                Statuses::Authorized, Statuses::Completed, Statuses::Cancelled, Statuses::Failed => true,
                default => false,
            },
            Statuses::Completed => match ($status) {
                Statuses::Completed => true,
                default => false,
            },
            Statuses::Cancelled => match ($status) {
                Statuses::Cancelled => true,
                default => false,
            },
            Statuses::Failed => match ($status) {
                Statuses::Failed => true,
                default => false,
            },
        };
    }
}
```

**Ce que cet exemple montre** :
- **Intention métier claire** : `registerOnlinePayment()`, `capture()`, `authorize()`, `fail()` expriment clairement l'intention
- **Protection des invariants** : `canTransitionTo()` protège les transitions d'état valides
- **Event Sourcing** : Chaque changement d'état est enregistré comme un événement, le changement détat est organisé dans des méthodes privées `apply...()`.
- **Value Objects** : `PaymentId`, `Price`, `Statuses` encapsulent les concepts métier
- **Séparation des responsabilités** : L'agrégat se concentre sur la logique métier, pas sur la persistance

## Pourquoi le CRUD limite vos mouvements

### Mon Piège : La Simplicité Apparente du CRUD

Le CRUD (Create, Read, Update, Delete) semblait être la solution parfaite : simple, direct, facile à comprendre. "Pourquoi compliquer les choses ?" me demandais-je. Et c'est exactement là que le piège s'est refermé.

**Voici ce qui s'est passé avec Gyroscops** : j'avais un système de gestion d'utilisateurs. Au début, c'était simple : créer, lire, modifier, supprimer. Puis est arrivée la demande : "On veut pouvoir suspendre un utilisateur". Facile, j'ai ajouté un champ `status`. Puis : "Un utilisateur suspendu ne peut pas se connecter". OK, j'ai ajouté une vérification. Puis : "Un utilisateur suspendu ne peut pas changer son email". Encore une vérification. Puis : "Il faut notifier l'utilisateur quand il est suspendu". Une autre vérification...

**Résultat** : Mon code ressemblait à un champ de mines. Chaque modification pouvait faire exploser quelque chose d'inattendu.

### Le Piège du CRUD

Le CRUD est une approche technique qui réduit votre domaine métier à des opérations de base de données. Cette approche m'a empêché de :

1. **Comprendre vraiment mon métier** - Je ne voyais que la technique, pas la logique
2. **Évoluer facilement** - Chaque changement devenait un cauchemar
3. **Maintenir la cohérence** - Rien n'empêchait les états incohérents
4. **Conserver l'intention utilisateur** (le plus grave) - L'intention se perdait dès le contrôleur

#### 1. **Comprendre Vraiment Votre Métier**
```php
// ❌ Approche CRUD - Le métier disparaît
class PaymentController
{
    public function create(Request $request): Response
    {
        $payment = new Payment();
        $payment->setAmount($request->get('amount'));
        $payment->setStatus('pending');
        $this->paymentRepository->save($payment);
        return new Response(['id' => $payment->getId()]);
    }
    
    public function update(Request $request, string $id): Response
    {
        $payment = $this->paymentRepository->find($id);
        $payment->setAmount($request->get('amount'));
        $payment->setStatus($request->get('status'));
        $this->paymentRepository->save($payment);
        return new Response(['success' => true]);
    }
}
```

La logique métier est saupoudrée au milieu de concepts techniques. Il est difficile d'identifier qui valide les règles. Toute évolution est rendue complexe. 

#### 2. **Évoluer Facilement**
Avec le CRUD, ajouter une nouvelle règle métier devient un un parcours d'obstacles :
1. Modifier le contrôleur
2. Modifier les services
3. Modifier les validations
4. Modifier les tests
5. Risque de régression

#### 3. **Maintenir la Cohérence**
Le CRUD ne protège pas les invariants métier. Rien n'empêche de créer un paiement avec un montant négatif ou de modifier un paiement déjà traité.

#### 4. **Conserver l'Intention Utilisateur**
Le plus grave des pièges du CRUD est la **perte de l'intention utilisateur**. Les méthodes `get` et `set` ne sont que le reflet d'une fraction de l'intention, parfois seulement des contraintes techniques.

```php
// ❌ CRUD - L'intention est perdue dès le contrôleur
class PaymentController
{
    public function processPayment(Request $request): Response
    {
        $payment = $this->paymentRepository->find($request->get('id'));
        
        // L'intention "traiter un paiement" devient :
        $payment->setStatus('processing');  // Contrainte technique
        $payment->setProcessedAt(now());    // Contrainte technique
        $payment->setAmount($request->get('amount')); // Donnée brute
        
        $this->paymentRepository->save($payment);
        return new Response(['success' => true]);
    }
}
```

**Problème** : 
- L'intention "traiter un paiement" disparaît dès les premiers instants
- Impossible de savoir **pourquoi** le paiement a été traité
- L'intention ne peut être que reconstruite ou déduite a posteriori
- Aucune trace de l'intention d'origine dans le code

```php
// ✅ DDD - L'intention est préservée et explicite
final class Payment
{
    public function capture(Gateways $gateway, Price $amount, \DateTimeInterface $completionDate): void
    {
        // Logique métier
        Assertion::true($this->canTransitionTo(Statuses::Completed));
        
        $this->recordThat(new CapturedEvent(
            uuid: $this->uuid,
            version: $this->version + 1,
            realmId: $this->realmId,
            organizationId: $this->organizationId,
            subscriptionId: $this->subscriptionId,
            status: Statuses::Completed,
            gateway: $gateway,
            amount: $amount,
            completionDate: $completionDate,
        ));
    }

    private function applyCapturedEvent(CapturedEvent $event): void
    {
        // Changement de l'état
        $this->status = $event->status;
        $this->capured = $this->captured->plus($event->amount);
        $this->gateway = $event->gateway;
        $this->completionDate = $event->completionDate;
    }
}
```

**Avantage** : L'intention métier est **explicite**, **préservée** et **traçable** tout au long du cycle de vie de l'objet.

### La Solution : Des Commandes Métier

```php
// ✅ Approche DDD - Le métier guide l'évolution
final readonly class ProcessPayment
{
    public function __construct(
        public readonly PaymentId $id,
        public readonly Price $amount,
        public readonly Gateway $gateway,
    ) {}
}

#[AsMessageHandler('command.bus')]
final readonly class ProcessPaymentHandler
{
    public function __construct(
        private ClockInterface $clock,
    ) {}

    public function __invoke(ProcessPayment $command): void
    {
        $payment = $this->paymentRepository->find($command->id);

        $payment->capture($command->gateway, $command->amount, $this->clock->now());

        $this->paymentRepository->save($payment);
    }
}
```

**Avantages** :
- La logique métier est centralisée
- L'évolution est guidée par le métier
- Les invariants sont protégés
- Le code est plus expressif

## Pourquoi les modèles anémiques vous empêchent d'avancer plus vite

### Mon Illusion : La Séparation des Responsabilités

Les modèles anémiques semblaient respecter le principe de séparation des responsabilités : "Les entités stockent les données, les services contiennent la logique". C'est logique, non ? **Non, c'est trompeur !**. C'est même en contradiction avec l'intention initiale de la Programmation Orientée Objet.

**Le problème** : Je séparais les données de leur logique. C'est comme séparer le l'autopilote des commandes de l'avion. C'est techniquement possible, et c'est justifié en disant "au cas où un jour on veuille changer les commandes de l'avion". **Ça n'arrivera jamais**.

**Voici ce qui s'est passé avec Gyroscops** : j'avais un système de gestion d'utilisateurs. Au début, c'était simple : des entités avec des getters/setters, des services qui faisaient la logique. Puis est arrivée la demande : "On veut pouvoir suspendre un utilisateur". Facile, j'ai ajouté un champ `isSuspended` et une méthode dans le service. Puis : "Un utilisateur suspendu ne peut pas se connecter". OK, j'ai ajouté une vérification dans le service. Puis : "Un utilisateur suspendu ne peut pas changer son email". Encore une vérification dans le service. Puis : "Il faut notifier l'utilisateur quand il est suspendu". Une autre vérification dans le service...

Je n'ai pas encore évoqué les inter-dépendances entre les entités lors de l'inscription : une organisation et un Workspace doivent être créés automatiquement pour que le compte soit fonctionnel.

**Résultat** : Ma logique métier était éparpillée dans de multiples services différents. Chaque modification nécessitait de toucher à au moins 5 fichiers. si on prenait chaque service individuellement, le code était propre. Mais je ne savais plus où était quoi, quel service interagissait avec quel autre, et mes collègues non plus.

### Mon Piège : Les Modèles Anémiques

Un modèle anémique est un modèle qui ne contient que des propriétés (getters/setters) sans logique métier. Cette approche a limité ma capacité à :

1. **Exprimer l'intention métier** - Ma logique était éparpillée dans plusieurs services et la cohérence n'était pas systématiquement maintenue
2. **Évoluer sans casser** - Chaque changement impactait plusieurs services
3. **Tester efficacement** - Mes tests devenaient fragiles et complexes

#### 1. **Exprimer l'Intention Métier**
```php
// ❌ Modèle Anémique - L'intention disparaît
class User
{
    public function __construct(
        private string $id,
        private string $email,
        private string $firstName,
        private string $lastName,
        private bool $isActive = true
    ) {}

    // Seulement des getters/setters
    public function getId(): string { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }
    // ... autres getters/setters
}
```

**Problème** : Comment savoir si un utilisateur peut se connecter ? Comment valider un email ? Comment gérer l'activation ?

**Avec Gyroscops, j'ai vécu cette situation** : J'avais un modèle `User` avec des getters/setters, et la logique était éparpillée dans 12 services différents. Quand j'ai voulu ajouter la fonctionnalité "suspendre un utilisateur", j'ai dû :

**Services liés à l'utilisateur** :
- Modifier le service `UserService` pour la logique de suspension
- Modifier le service `AuthenticationService` pour vérifier le statut
- Modifier le service `EmailService` pour les notifications

**Services liés à l'organisation** :
- Modifier le service `OrganizationService` car l'utilisateur appartient à une organisation
- Modifier le service `BillingService` car l'organisation est l'entité facturée
- Modifier le service `AuditService` pour l'historique organisationnel

**Services liés au workflow** :
- Modifier le service `WorkflowService` car le workflow est l'espace de travail
- Modifier le service `CloudService` car le workflow est déployé dans une région cloud
- Modifier le service `ResourceService` car le workflow comprend des ressources

**Services transversaux** :
- Modifier le service `NotificationService` pour les alertes
- Modifier le service `ReportService` pour les statistiques
- Modifier le service `CacheService` pour l'invalidation

**Résultat** : 12 fichiers à modifier pour une seule fonctionnalité ! Et si j'oubliais un service ? Et si les règles étaient incohérentes entre les services ? Et comment gérer les dépendances entre User → Organization → Workflow → Resources Cloud ? Et si j'oublie de décommissionner les ressources Cloud, je serai toujours facturé par le fournisseur d'infra.

La logique métier se retrouve éparpillée dans les services, mélangeant souvent **règles métier** et **contraintes techniques** :

```php
// ❌ Entité Doctrine avec contraintes mélangées
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\Email(message: 'Email invalide')]
    #[Assert\Length(max: 255, maxMessage: 'Email trop long')]
    #[Assert\NotBlank(message: 'Email obligatoire')]
    private string $email;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire')]
    #[Assert\Length(min: 2, max: 50, minMessage: 'Prénom trop court')]
    private string $firstName;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(min: 2, max: 50, minMessage: 'Nom trop court')]
    private string $lastName;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    // Seulement des getters/setters
    public function getId(): ?int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function getFirstName(): string { return $this->firstName; }
    public function setFirstName(string $firstName): void { $this->firstName = $firstName; }
    public function getLastName(): string { return $this->lastName; }
    public function setLastName(string $lastName): void { $this->lastName = $lastName; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): void { $this->isActive = $isActive; }
}

// ❌ Service avec logique métier mélangée
class UserService
{
    public function __construct(
        private ValidatorInterface $validator,
        private UserRepository $userRepository
    ) {}

    public function validateUser(User $user): bool
    {
        // Règle métier : L'utilisateur doit être actif
        if (!$user->isActive()) {
            return false;
        }
        
        // Contrainte technique : Validation Symfony (déjà dans l'entité)
        $violations = $this->validator->validate($user);
        if (count($violations) > 0) {
            return false;
        }
        
        // Règle métier : L'utilisateur ne doit pas être banni
        if ($this->isUserBanned($user->getId())) {
            return false;
        }
        
        return true;
    }
}
```

**Problèmes** :
- **Mélange de responsabilités** : Contraintes techniques (Doctrine/Symfony) et règles métier dans la même entité
- **Difficile à tester** : Comment tester uniquement les règles métier sans les contraintes techniques ?
- **Difficile à maintenir** : Où modifier une règle métier spécifique sans impacter les contraintes ?
- **Difficile à comprendre** : Quelle est l'intention réelle de cette validation ?
- **Couplage fort** : L'entité est couplée à la base de données ET aux règles métier, que se passe-t-il si je souhaite panacher mon stockage d'entités entre PostgreSQL et ElasticSearch ?

#### 2. **Évoluer Sans Casser**
Avec des modèles anémiques, changer une règle métier nécessite de :
1. Modifier tous les services qui utilisent le modèle
2. Modifier tous les contrôleurs
3. Modifier tous les tests
4. Risque de régression élevé

#### 3. **Tester Efficacement**
La logique métier est éparpillée dans les services, rendant les tests complexes et fragiles.

### La Solution : Des Modèles Riches

```php
// ✅ Modèle Riche - L'intention métier est claire
final class User
{
    private function __construct(
        private UserId $id,
        private Email $email,
        private FullName $name,
        private UserStatuses $status
    ) {}

    public static function register(UserId $id, Email $email, FullName $name): self
    {
        return new self($id, $email, $name, UserStatuses::Pending);
    }

    public static function restoreFromState(UserId $id, Email $email, FullName $name, Statuses $status): self
    {
        return new self($id, $email, $name, $status);
    }

    public function activate(): void
    {
        if ($this->status !== UserStatuses::Pending) {
            throw new UserCannotBeActivatedException();
        }
        
        $this->status = UserStatuses::Active;
    }

    public function canLogin(): bool
    {
        return $this->status === UserStatuses::Active;
    }

    public function changeEmail(Email $newEmail): void
    {
        if ($this->status === UserStatuses::Suspended) {
            throw new UserCannotChangeEmailException();
        }
        
        $this->email = $newEmail;
    }
}
```

**Avantages** :
- **Séparation claire** : Règles métier dans le modèle et les Value Objects, contraintes techniques dans les services de stockage
- **Intention métier explicite** : `register`, `canLogin()`, `activate()`, `changeEmail()` expriment clairement l'intention
- **Évolution guidée par le métier** : Les changements suivent la logique métier
- **Tests plus simples** : Chaque règle métier peut être testée indépendamment
- **Cohérence garantie** : Les invariants métier sont protégés

```php
// ✅ Agrégat racine séparé de l'entité Doctrine
class User
{
    public private(set) UserId $id;
    public private(set) Email $email;
    public private(set) FullName $name;
    public string $firstName {
        get => $this->name->firstName;
    }
    public string $lastName {
        get => $this->name->lastName;
    }
    public private(set) UserStatuses $status;

    public static function register(Email $email, FullName $name): self
    {
        $user = new self();
        $user->id = UserId::generate();
        $user->email = $email;
        $user->name = $name;
        $user->status = UserStatuses::Pending;
        return $user;
    }

    public function activate(): void
    {
        if ($this->status !== UserStatuses::Pending) {
            throw new UserCannotBeActivatedException();
        }
        
        $this->status = UserStatuses::Active;
    }

    public function canLogin(): bool
    {
        return $this->status === UserStatuses::Active;
    }

    public function changeName(FullName $newName): void
    {
        if ($this->status === UserStatuses::Suspended) {
            throw new UserCannotChangeNameException();
        }
        
        $this->name = $newName;
    }

    public function changeEmail(Email $newEmail): void
    {
        if ($this->status === UserStatuses::Suspended) {
            throw new UserCannotChangeEmailException();
        }
        
        $this->email = $newEmail;
    }

    public function suspend(): void
    {
        if ($this->status === UserStatuses::Suspended) {
            throw new UserAlreadySuspendedException();
        }
        
        $this->status = UserStatuses::Suspended;
    }
}

// ✅ Entité Doctrine séparée pour la persistance
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class UserEntity
{
    public ?FullName $fullName {
        get => ($this-firstName !== null && $this->lastName !== null)
            ? new FullName(
                $this->firstName,
                $this->lastName,
            )
            : null;
    }

    public function __construct(
        #[Assert\Uuid]
        #[ORM\Id]
        #[ORM\Column(type: 'user_id', unique: true)]
        public UserId $id = UserId::generateRandom();

        #[ORM\Column(type: 'string', length: 255)]
        #[Assert\Email(message: 'Email invalide')]
        #[Assert\Length(max: 255, maxMessage: 'Email trop long')]
        #[Assert\NotBlank(message: 'Email obligatoire')]
        public ?string $email;

        #[ORM\Column(length: 50)]
        #[Assert\NotBlank(message: 'Le prénom est obligatoire')]
        #[Assert\Length(min: 2, max: 50, minMessage: 'Prénom trop court')]
        public ?string $firstName;

        #[ORM\Column(length: 50)]
        #[Assert\NotBlank(message: 'Le nom est obligatoire')]
        #[Assert\Length(min: 2, max: 50, minMessage: 'Nom trop court')]
        public ?string $lastName;

        #[ORM\Column()]
        public Statuses $status = Statuses::Pending;
    ) {}
}

// ✅ Value Objects pour les contraintes techniques
use Assert\Assertion;
use Assert\AssertionFailedException;
final readonly class UserId
{
    public function __construct(private string $value)
    {
        try {
            Assertion::uuid($value);
        } catch(AssertionFailedException $exception) {
            throw new InvalidUserIdException('ID utilisateur obligatoire', previous: $exception);
        }
    }

    public static function generate(): self
    {
        return new self(uniqid('user_', true));
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

final readonly class Email
{
    public function __construct(private string $value)
    {
        try {
            Assertion::email($value);
        } catch(AssertionFailedException $exception) {
            throw new InvalidEmailException('Email invalide', previous: $exception);
        }
    }
    
    public function __toString(): string
    {
        return $this->value;
    }
}

final readonly class FullName
{
    public function __construct(
        public string $firstName,
        public string $lastName
    ) {
        try {
            Assertion::notEmpty($firstName);
            Assertion::notEmpty($lastName);
        } catch(AssertionFailedException $exception) {
            throw new InvalidNameException('Nom complet obligatoire', previous: $exception);
        }
    }
}
```

### **Mapper entre Agrégat et Entité Doctrine**

```php
// ✅ Mapper pour convertir entre l'agrégat et l'entité Doctrine
class UserMapper
{
    public function toEntity(User $user): UserEntity
    {
        return new UserEntity(
            $user->email,
            $user->firstName,
            $user->lastName,
            $user->status,
        );
    }

    public function toAggregate(UserEntity $entity): User
    {
        return User::restoreFromState(
            $entity->id,
            new Email($entity->email),
            $entity->fullName,
            $entity->status,
        );
    }
}
```

**Résultat** : 
- **Séparation claire** : Agrégat métier séparé de l'entité de persistance
- **Agrégat pur** : Pas de dépendance à Doctrine dans le modèle métier
- **Entité Doctrine** : Gestion de la persistance avec validation Symfony
- **Mapper dédié** : Conversion explicite entre les deux représentations
- **Testabilité** : L'agrégat peut être testé sans base de données
- **Flexibilité** : Possibilité de changer de stratégie de persistance

## L'Event Storming : Révéler la Complexité Métier

> **📖 Note** : Ce chapitre présente une introduction à l'Event Storming. Pour un guide pratique complet avec des ateliers pas-à-pas, consultez le [Chapitre 3 : L'Atelier Event Storming - Guide Pratique](/chapitres/fondamentaux/chapitre-03-atelier-event-storming/).

### Mon Problème : Je Ne Voyais Que la Pointe de l'Iceberg

**Voici ce qui s'est passé** : J'étais en réunion avec l'équipe'. On décrivait une nouvelle fonctionnalité : "On veut pouvoir suspendre un utilisateur". Simple, non ? J'ajoute un champ `isSuspended` et c'est réglé.

**Mais attendez...** Que se passe-t-il si l'utilisateur a des paiements en cours ? Et ses abonnements ? Et ses données personnelles ? Et les notifications ? Et l'audit ? Et la conformité RGPD ? Et les intégrations avec les systèmes externes ?

**Soudain, ce qui semblait simple devenait un cauchemar.** Je réalisais que je ne comprenais pas vraiment le métier. Je ne voyais que la pointe de l'iceberg.

### Qu'est-ce que l'Event Storming ?

L'Event Storming est une méthode de conception collaborative qui permet de :
1. **Découvrir le domaine métier** - Voir l'iceberg entier, pas juste la pointe
2. **Identifier les événements métier** - Comprendre ce qui se passe vraiment
3. **Modéliser les processus** - Voir comment tout s'articule
4. **Concevoir l'architecture** - Construire sur des bases solides

### Pourquoi l'Event Storming ?

#### 1. **Révéler la Vraie Complexité** - Mon Réveil Brutal

L'Event Storming m'a montré que mon domaine était plus complexe que je ne le pensais. Cette complexité existait, que je la modélise ou non. **La question n'était pas de savoir si elle existait, mais si je voulais la gérer ou la subir.**

**Exemple concret avec Gyroscops** : Je pensais qu'un "paiement" était simple. Puis l'Event Storming a révélé :
- Paiement initié
- Paiement en attente de validation
- Paiement validé par la banque
- Paiement traité par le système
- Paiement notifié au client
- Paiement enregistré pour l'audit
- Paiement synchronisé avec la comptabilité
- Paiement affiché dans le tableau de bord
- Paiement exporté pour les rapports
- Paiement archivé pour la conformité

**Soudain, j'ai compris pourquoi mon code était si complexe !**

#### 2. **Alignement de l'Équipe** - La Fin des Malentendus

Toute l'équipe (développeurs, product owners, experts métier) partage la même compréhension du domaine. **Fini les "Ah, je pensais que..." et les "Non, mais je te dis que c'est ça qui..."**

**Exemple concret avec Gyroscops** : Pendant un Event Storming sur la gestion des utilisateurs. Voici ce qui est ressorti :

- **Moi (développeur)** : "Un utilisateur suspendu ne peut pas se connecter"
- **Le product owner** : "Non, il peut se connecter mais ne peut pas faire d'achats"
- **L'expert métier** : "Il peut se connecter et faire des achats mais ne peut pas changer son email"
- **Le responsable sécurité** : "Il ne peut pas accéder aux données sensibles"
- **Le responsable comptabilité** : "Il ne peut pas télécharger de factures"
- **Le responsable cloud** : "Il ne peut pas accéder aux workflows déployés"
- **Le responsable facturation** : "L'organisation doit continuer à être facturée même si l'utilisateur est suspendu"

**Résultat de l'Event Storming** : J'ai découvert que "suspendre un utilisateur" n'était pas un seul événement, mais plusieurs, avec des implications sur toute la chaîne :

**Événements liés à l'utilisateur** :
- `UserSuspended` (accès restreint)
- `UserBillingSuspended` (pas d'achats)
- `UserDataAccessSuspended` (pas de données sensibles)
- `UserEmailChangeSuspended` (pas de changement d'email)

**Événements liés à l'organisation** :
- `OrganizationBillingMaintained` (l'organisation continue d'être facturée)
- `OrganizationAccessRestricted` (accès restreint aux données organisationnelles)

**Événements liés au workflow** :
- `WorkflowAccessSuspended` (pas d'accès aux workflows)
- `WorkflowResourcesMaintained` (les ressources restent actives)
- `WorkflowBillingMaintained` (la facturation du workflow continue)

**Qui avait raison ?** Tout le monde ! Chacun avait une vision partielle de la réalité métier, et j'ai découvert que suspendre un utilisateur avait des implications sur l'organisation, le workflow, et même les ressources cloud.

#### 3. **Conception Collaborative** - Briser les Silos

L'Event Storming brise les silos et permet une conception vraiment collaborative. **Fini les "spécifications" écrites par les uns et interprétées par les autres.**

### Les 7 Étapes de l'Event Storming

1. **Identifier les Événements** : Qu'est-ce qui se passe dans le domaine ?
2. **Identifier les Acteurs** : Qui déclenche ces événements ?
3. **Identifier les Commandes** : Quelles actions déclenchent les événements ?
4. **Identifier les Agrégats** : Quelles entités sont concernées ?
5. **Identifier les Systèmes Externes** : Quelles intégrations sont nécessaires ?
6. **Identifier les Vues de Lecture** : Quelles données sont nécessaires pour l'affichage ?
7. **Concevoir l'Architecture** : Comment organiser le code ?

### Exemple d'Event Storming : Système de Paiement

Voici comment se présente un tableau d'Event Storming pour un système de paiement, avec les différents types de post-its organisés de gauche à droite selon le flux temporel :

{{< figure src="/images/event-storming/payment-system-flow.svg" title="Tableau Event Storming - Système de Paiement" >}}

**Ce que cette organisation révèle** :
- **Le flux temporel** : De gauche à droite, on voit l'ordre chronologique des événements
- **Les responsabilités** : Chaque acteur (Customer, Admin) déclenche des commandes spécifiques
- **Les dépendances** : On voit clairement que Payment dépend de PaymentGateway (système externe)
- **Les agrégats** : Payment est l'agrégat central, mais Order est aussi concerné
- **Les chemins alternatifs** : PaymentProcessed ou PaymentFailed selon la réponse de la gateway

> **💡 Pour aller plus loin** : Cette présentation simplifiée montre les concepts de base. Dans le [Chapitre 3](/chapitres/fondamentaux/chapitre-03-atelier-event-storming/), vous découvrirez comment animer un atelier complet avec votre équipe, avec des exemples détaillés et des templates à télécharger.


## Architecture Résultante

### Structure par Bounded Context

Voici la structure réelle du projet Gyroscops Cloud, organisée par Bounded Context :

```
api/src/
├── Accounting/           # Contexte Comptabilité
│   ├── Domain/
│   │   ├── Payment/      # Agrégat Payment
│   │   │   ├── Command/  # Modèles de commande
│   │   │   └── Query/    # Modèles de requête
│   │   ├── Subscription/ # Agrégat Subscription
│   │   └── Offer/        # Agrégat Offer
│   └── Infrastructure/   # Implémentations techniques
├── Authentication/       # Contexte Authentification
│   ├── Domain/
│   │   ├── User/         # Agrégat User
│   │   ├── Organization/ # Agrégat Organization
│   │   ├── Role/         # Agrégat Role
│   │   └── Realm/        # Agrégat Realm
│   └── Infrastructure/
├── ...
└── Platform/             # Contexte Plateforme
    ├── Domain/
    │   └── FeatureRollout/ # Agrégat FeatureRollout
    └── Infrastructure/
```

### Le Monolithe Modulaire : La Vraie Solution

**Voici ce que j'ai appris avec Gyroscops** : Dans 80% des cas, les microservices sont une réponse hors sujet. La vraie dette technique, ce n'est pas le monolithe. C'est un monolithe mal structuré.

J'ai vu trop d'équipes exploser leur codebase en 15 services "indépendants" qui finissent par dépendre les uns des autres, en justifiant qu'un jour peut être on supprimmera le module. Quand un problème basique survient il faut mettre 40 minutes à déboguer un simple flux métier. Quand le jour de la suppression d'un module arrive, on se rend compte que tous les modules ont des dépendances circulaires et qu'il n'y a, de fait, qu'un seul module.

**Avec les Bounded Contexts, j'ai créé un monolithe modulaire** :
- **Bien découpé en domaines fonctionnels clairs** : Chaque Bounded Context correspond à un domaine métier
- **Avec des interfaces internes bien définies** : Les UseCases exposent des interfaces claires
- **Testable, maintenable, lisible** : Chaque contexte peut être testé et maintenu indépendamment
- **Déployable en un clic** : Un seul déploiement pour toute l'application
- **Des dépendances marisées** : Seuls les objets d'ID d'un autre Bounded Context sont tolérés, sinon tout toit être isolé au Bounded context actuel

**Le résultat** : J'ai évité le piège des microservices prématurés. J'ai un système cohérent, maintenable, et évolutif. Et quand un module devient vraiment trop gros ou trop critique et que toutes les pistes d'optimisation ont été parcourues, là je peux réfléchir à l'extraire en microservice. Mais je pars du besoin, pas du dogme.

**Comme le dit [Jean-Vincent Quilichini](https://www.linkedin.com/posts/jeanvincentquilichini_je-ne-fais-presque-plus-de-microservices-activity-7375767071550423040-Kivq) : "Le microservice doit être un outil. Pas une posture."**

### Références aux ADR du projet Gyroscops Cloud

Cette architecture suit les principes définis dans les Architecture Decision Records (ADR) du projet Gyroscops Cloud :

- **HIVE040** : Enhanced Models with Property Access Patterns - Utilisation de propriétés publiques en lecture seule
- **HIVE041** : Cross-Cutting Concerns Architecture - Séparation claire des responsabilités
- **HIVE005** : Common Identifier Model Interfaces - Interfaces standardisées pour les identifiants
- **HIVE010** : Repositories - Patterns de repository avec intégration Event Bus
- **HIVE023** : Repository Testing Strategies - Stratégies de test pour les repositories
- **HIVE027** : PHPUnit Testing Standards - Standards de test PHPUnit

### Intégration avec API Platform

```php
// Exemple d'intégration API Platform
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;

#[Get]
#[GetCollection]
#[Post]
#[Put]
#[Patch]
#[Delete]
class Payment
{
    public function __construct(
        public readonly PaymentId $id,
        public readonly Money $amount,
        public readonly PaymentMethods $method,
        public readonly PaymentStatus $status,
        public readonly DateTime $createdAt
    ) {}
}
```

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux aligner le produit sur les objectifs business avec l'Impact Mapping" 
    subtitle="Vous voulez recentrer le périmètre du projet autour d'objectifs business précis"
    criteria="Objectifs business à clarifier,Besoin d'aligner business et technique,Équipe avec business owners disponibles,Temps pour la planification stratégique"
    time="2-3 heures"
    chapter="2"
    chapter-title="L'Impact Mapping - Aligner le Produit sur les Objectifs Business"
    chapter-url="/chapitres/fondamentaux/chapitre-02-impact-mapping/"
  >}}
  
  {{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux apprendre la méthode Event Storming" 
    subtitle="Vous voulez maîtriser la technique de conception collaborative"
    criteria="Équipe de 3-8 personnes,Besoin de conception collaborative,Projet complexe à modéliser,Temps disponible pour un atelier"
    time="30-45 minutes"
    chapter="3"
    chapter-title="L'Atelier Event Storming - Guide Pratique"
    chapter-url="/chapitres/fondamentaux/chapitre-03-atelier-event-storming/"
  >}}
  
  {{< chapter-option 
    letter="C" 
    color="red" 
    title="Je veux détailler les règles métier avec l'Example Mapping" 
    subtitle="Vous voulez explorer les règles complexes découvertes lors de l'Event Storming"
    criteria="Règles métier complexes identifiées,Besoin de clarifier les cas limites,Équipe avec expert métier disponible,Temps pour approfondir les détails"
    time="30-45 minutes"
    chapter="4"
    chapter-title="L'Example Mapping - Détailer les Règles Métier"
    chapter-url="/chapitres/fondamentaux/chapitre-04-example-mapping/"
  >}}
  
  {{< chapter-option 
    letter="D" 
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
    letter="E" 
    color="purple" 
    title="Je veux voir des exemples concrets de modèles" 
    subtitle="Vous voulez comprendre la différence entre modèles riches et anémiques"
    criteria="Développeur avec expérience,Besoin d'exemples pratiques,Compréhension des patterns de code,Implémentation à faire"
    time="25-35 minutes"
    chapter="7"
    chapter-title="Modèles Riches vs Modèles Anémiques"
    chapter-url="/chapitres/fondamentaux/chapitre-07-modeles-riches-vs-anemiques/"
  >}}
{{< /chapter-nav >}}

**💡 Conseil** : Si vous n'êtes pas sûr, choisissez l'option A pour commencer par l'Impact Mapping, puis continuez avec les autres chapitres dans l'ordre.

**🔄 Alternative** : Si vous voulez tout voir dans l'ordre, commencez par le [Chapitre 2](/chapitres/fondamentaux/chapitre-02-impact-mapping/).
