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
existante, nous avons voulu fournir notre service sous la forme de SaaS, ce qui a ajouté un niveau de complexité
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
    public function __construct(
        public BigDecimal $amount,
        public Currencies $currency,
    ) {
        Assertion::true($this->amount->isGreaterThan(0));
    }

    public function substract(self $price): self
    {
        if ($price->currency !== $this->currency) {
            throw new CurrencyMismatchException('Currency conversion is not supported');
        }

        return new self($this->amount->minus($price->amount), $this->currency);
    }

    public function add(self $price): self
    {
        if ($price->currency !== $this->currency) {
            throw new CurrencyMismatchException('Currency conversion is not supported');
        }

        return new self($this->amount->plus($price->amount), $this->currency);
    }

    public function isGreaterThan(self $price): bool
    {
        if ($price->currency !== $this->currency) {
            throw new CurrencyMismatchException('Currency conversion is not supported');
        }

        return $this->amount->isGreaterThan($price->amount);
    }

    public function isGreaterThanOrEqualTo(self $price): bool
    {
        if ($price->currency !== $this->currency) {
            throw new CurrencyMismatchException('Currency conversion is not supported');
        }

        return $this->amount->isGreaterThanOrEqualTo($price->amount);
    }

    public function isLessThan(self $price): bool
    {
        if ($price->currency !== $this->currency) {
            throw new CurrencyMismatchException('Currency conversion is not supported');
        }

        return $this->amount->isLessThan($price->amount);
    }

    public function isLessThanOrEqualTo(self $price): bool
    {
        if ($price->currency !== $this->currency) {
            throw new CurrencyMismatchException('Currency conversion is not supported');
        }

        return $this->amount->isLessThanOrEqualTo($price->amount);
    }

    public function isEqualTo(self $price): bool
    {
        if ($price->currency !== $this->currency) {
            throw new CurrencyMismatchException('Currency conversion is not supported');
        }

        return $this->amount->isEqualTo($price->amount);
    }

    public function isZero(): bool
    {
        return $this->amount->isZero();
    }

    public function multipliedBy(BigNumber|string $number): self
    {
        return new self(
            $this->amount->multipliedBy($number),
            $this->currency,
        );
    }
}
```

**Ce que cet exemple montre** :
- **Immutabilité** : `readonly` et constructeur privé empêchent la modification
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

    public function authorize(Gateways $gateway, Price $amount, \DateTimeInterface $authorizationDate): void
    {
        Assertion::true($this->canTransitionTo(Statuses::Authorized));
        
        $this->recordThat(new CapturedEvent(
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

    public function capture(Gateways $gateway, Price $amount, \DateTimeInterface $completionDate): void
    {
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

    public function fail(Gateways $gateway, Price $amount, \DateTimeInterface $failureDate, string $reason): void
    {
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
- **Event Sourcing** : Chaque changement d'état est enregistré comme un événement
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
class ProcessPayment
{
    public function __construct(
        public readonly PaymentId $id,
        public readonly Money $amount,
        public readonly PaymentMethod $method
    ) {}
}

class Payment
{
    public function process(ProcessPayment $command): void
    {
        // L'intention "process" est claire et préservée
        if (!$this->canBeProcessed()) {
            throw new PaymentCannotBeProcessedException();
        }
        
        $this->status = PaymentStatuses::Processing;
        $this->processedAt = new DateTimeImmutable();
        $this->amount = $command->amount;
        
        // L'intention est conservée dans l'événement
        $this->recordEvent(new PaymentProcessed($this->id, $command->amount));
    }
}
```

**Avantage** : L'intention métier est **explicite**, **préservée** et **traçable** tout au long du cycle de vie de l'objet.

### La Solution : Des Commandes Métier

```php
// ✅ Approche DDD - Le métier guide l'évolution
class ProcessPayment
{
    public function __construct(
        public readonly PaymentId $id,
        public readonly Money $amount,
        public readonly PaymentMethod $method
    ) {}
}

#[AsMessageHandler('command.bus')]
class ProcessPaymentHandler
{
    public function __invoke(ProcessPayment $command): void
    {
        $payment = $this->paymentRepository->find($command->id);
        $payment->process($command->amount);
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

Les modèles anémiques semblaient respecter le principe de séparation des responsabilités : "Les entités stockent les données, les services contiennent la logique". C'est logique, non ? **Non, c'est trompeur !**

**Le problème** : Je séparais les données de leur logique. C'est comme séparer le l'autopilote de l'avion : techniquement possible, mais pas très optimal.

**Voici ce qui s'est passé avec Gyroscops** : j'avais un système de gestion d'utilisateurs. Au début, c'était simple : des entités avec des getters/setters, des services qui faisaient la logique. Puis est arrivée la demande : "On veut pouvoir suspendre un utilisateur". Facile, j'ai ajouté un champ `isSuspended` et une méthode dans le service. Puis : "Un utilisateur suspendu ne peut pas se connecter". OK, j'ai ajouté une vérification dans le service. Puis : "Un utilisateur suspendu ne peut pas changer son email". Encore une vérification dans le service. Puis : "Il faut notifier l'utilisateur quand il est suspendu". Une autre vérification dans le service...

Je n'ai pas encore évoqué les inter-dépendances entre les entités lors de l'inscription.

**Résultat** : Ma logique métier était éparpillée dans de multiples services différents. Chaque modification nécessitait de toucher à au moins 5 fichiers. Je ne savais plus où était quoi, mes collègues non plus.

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
- Modifier le service `AuthService` pour vérifier le statut
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

**Résultat** : 12 fichiers à modifier pour une seule fonctionnalité ! Et si j'oubliais un service ? Et si les règles étaient incohérentes entre les services ? Et comment gérer les dépendances entre User → Organization → Workflow → Cloud Resources ?

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
- **Couplage fort** : L'entité est couplée à la base de données ET aux règles métier

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
class User
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
- **Séparation claire** : Règles métier dans le modèle, contraintes techniques dans les Value Objects
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
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
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

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'pending';

    // Getters et setters pour Doctrine
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }
}

// ✅ Value Objects pour les contraintes techniques
final readonly class UserId
{
    public function __construct(private string $value)
    {
        if (empty($value)) {
            throw new InvalidUserIdException('ID utilisateur obligatoire');
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
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException('Email invalide');
        }
        
        if (strlen($value) > 255) {
            throw new InvalidEmailException('Email trop long');
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
        if (empty($firstName) || empty($lastName)) {
            throw new InvalidNameException('Nom complet obligatoire');
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
        $entity = new UserEntity();
        $entity->setEmail($user->getEmail()->__toString());
        $entity->setFirstName($user->getFirstName());
        $entity->setLastName($user->getLastName());
        $entity->setStatus($user->getStatus()->value);
        
        return $entity;
    }

    public function toAggregate(UserEntity $entity): User
    {
        $user = new User();
        $user->id = new UserId($entity->getId());
        $user->email = new Email($entity->getEmail());
        $user->firstName = $entity->getFirstName();
        $user->lastName = $entity->getLastName();
        $user->status = UserStatuses::from($entity->getStatus());
        
        return $user;
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

Toute l'équipe (développeurs, product owners, experts métier) partage la même compréhension du domaine. **Fini les "Ah, je pensais que..." et les "Non, mais moi je croyais que..."**

**Exemple concret avec Gyroscops** : Pendant un Event Storming sur la gestion des utilisateurs. Voici ce qui est ressorti :

- **Moi (développeur)** : "Un utilisateur suspendu ne peut pas se connecter"
- **Le product owner** : "Non, il peut se connecter mais ne peut pas faire d'achats"
- **L'expert métier** : "Il peut se connecter et faire des achats mais ne peut pas changer son email"
- **Le responsable sécurité** : "Il ne peut pas accéder aux données sensibles"
- **Le responsable comptabilité** : "Il ne peut pas télécharger de factures"
- **Le responsable cloud** : "Il ne peut pas accéder aux workflows déployés"
- **Le responsable facturation** : "L'organisation doit continuer à être facturée même si l'utilisateur est suspendu"

**Résultat de l'Event Storming** : Nous avons découvert que "suspendre un utilisateur" n'était pas un seul événement, mais plusieurs, avec des implications sur toute la chaîne :

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

**Qui avait raison ?** Tout le monde ! Chacun avait une vision partielle de la réalité métier, et nous avons découvert que suspendre un utilisateur avait des implications sur l'organisation, le workflow, et même les ressources cloud.

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

```
Événements (Post-its Orange) :
- PaymentRequested
- PaymentProcessed
- PaymentFailed
- PaymentRefunded

Acteurs (Post-its Jaunes) :
- Customer
- PaymentGateway
- Admin

Commandes (Post-its Bleus) :
- ProcessPayment
- RefundPayment
- CancelPayment

Agrégats (Post-its Jaunes avec bordure) :
- Payment
- Customer
- Order
```

## L'Example Mapping : Détailer les Règles Métier

### Mon Problème : Les Règles Métier Cachées

**Voici ce qui s'est passé** : Je venais de faire un Event Storming sur le système de paiement de Gyroscops. J'avais identifié l'événement "Paiement traité". Parfait ! Je commençais à coder.

**Mais attendez...** Qu'est-ce qui détermine qu'un paiement peut être traité ? Le montant ? La méthode de paiement ? L'historique du client ? La disponibilité des fonds ? Les restrictions géographiques ? Les limites de sécurité ? Les règles de conformité ?

**Soudain, je réalisais que je ne connaissais pas les règles !** J'avais identifié l'événement, mais pas les conditions qui le déclenchent.

### Qu'est-ce que l'Example Mapping ?

L'Example Mapping est une méthode complémentaire à l'Event Storming qui permet de détailler les règles métier découvertes. Créée par Matt Wynne, cette technique utilise des cartes colorées pour explorer les exemples concrets d'une fonctionnalité.

**L'idée** : Au lieu de discuter de règles abstraites, vous discutez d'exemples concrets. C'est beaucoup plus facile !

### Pourquoi l'Example Mapping ?

#### 1. **Révéler les Règles Implicites** - Mon Détective du Métier

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

#### 2. **Concrétiser l'Abstrait** - De l'Idée à la Réalité

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

#### 3. **Faciliter les Tests** - De l'Exemple au Code

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

### Les 4 Types de Cartes

#### 🟡 Cartes Jaunes : Règles Métier
Les règles qui gouvernent le comportement du système.

#### 🟢 Cartes Vertes : Exemples
Les exemples concrets qui illustrent les règles.

#### 🔴 Cartes Rouges : Questions
Les questions qui émergent lors de l'exploration.

#### 🔵 Cartes Bleues : Scénarios
Les scénarios d'utilisation complets.

### Exemple d'Example Mapping : Traitement d'un Paiement

```
🟡 RÈGLE : Un paiement ne peut être traité que s'il est en statut "pending"
🟡 RÈGLE : Le montant doit être positif
🟡 RÈGLE : Le montant ne peut pas dépasser la limite du compte
🟡 RÈGLE : Un paiement traité ne peut plus être modifié

🟢 EXEMPLE : Paiement de 100€ avec compte ayant une limite de 500€
   → Résultat : Paiement traité avec succès

🟢 EXEMPLE : Paiement de -50€
   → Résultat : Erreur "Montant invalide"

🟢 EXEMPLE : Paiement de 600€ avec compte ayant une limite de 500€
   → Résultat : Erreur "Montant dépasse la limite"

🟢 EXEMPLE : Tentative de traiter un paiement déjà traité
   → Résultat : Erreur "Paiement déjà traité"

🔴 QUESTION : Que se passe-t-il si le compte est suspendu ?
🔴 QUESTION : Y a-t-il une limite de temps pour traiter un paiement ?
🔴 QUESTION : Peut-on traiter un paiement partiellement ?

🔵 SCÉNARIO : Processus complet de paiement
   1. Client initie un paiement
   2. Système valide le montant
   3. Système vérifie la limite du compte
   4. Système traite le paiement
   5. Système envoie une confirmation
```

### Comment Utiliser l'Example Mapping

#### 1. **Après l'Event Storming**
Utilisez l'Example Mapping pour détailler les événements les plus complexes découverts lors de l'Event Storming.

#### 2. **En Petite Équipe**
3-5 personnes maximum : un expert métier, un développeur, un testeur.

#### 3. **Durée Limitée**
30-45 minutes par fonctionnalité pour éviter l'over-engineering.

#### 4. **Focus sur les Cas Limites**
Concentrez-vous sur les règles métier complexes et les cas d'erreur.

### Exemple Concret : Règles de Paiement

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

### Avantages de l'Example Mapping

1. **Clarté des Règles** : Les exemples concrets clarifient les règles métier
2. **Tests Automatiques** : Les exemples deviennent des tests d'acceptation
3. **Communication** : Toute l'équipe comprend les mêmes règles
4. **Évolution** : Facile d'ajouter de nouveaux exemples quand les règles changent

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

J'ai vu trop d'équipes exploser leur codebase en 15 services "indépendants" qui finissent par dépendre les uns des autres, se synchroniser à coups de webhooks bancals, et mettre 40 minutes à déboguer un simple flux métier.

**Avec les Bounded Contexts, j'ai créé un monolithe modulaire** :
- **Bien découpé en domaines fonctionnels clairs** : Chaque Bounded Context correspond à un domaine métier
- **Avec des interfaces internes bien définies** : Les UseCases exposent des interfaces claires
- **Testable, maintenable, lisible** : Chaque contexte peut être testé et maintenu indépendamment
- **Déployable en un clic** : Un seul déploiement pour toute l'application

**Le résultat** : J'ai évité le piège des microservices prématurés. J'ai un système cohérent, maintenable, et évolutif. Et quand un module devient vraiment trop gros ou trop critique, là je peux l'extraire en microservice. Mais je pars du besoin, pas du dogme.

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
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;

#[Get]
#[GetCollection]
#[Post]
#[Patch]
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
