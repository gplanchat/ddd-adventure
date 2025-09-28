---
title: "Chapitre 7 : Modèles Riches vs Modèles Anémiques"
description: "Comparaison détaillée avec exemples de code pour comprendre la différence entre modèles riches et anémiques"
date: 2024-12-19
draft: true
type: "docs"
weight: 7
---

## 🎯 Objectif de ce Chapitre

Ce chapitre vous montre concrètement la différence entre modèles riches et anémiques avec des exemples de code du projet Gyroscops Cloud. Vous apprendrez :
- Comment identifier un modèle anémique
- Comment transformer un modèle anémique en modèle riche
- Les patterns de modèles riches
- La conservation de l'intention métier

### Références aux ADR du projet Gyroscops Cloud

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du projet Gyroscops Cloud :
- **HIVE040** : Enhanced Models with Property Access Patterns - Patterns d'accès aux propriétés des modèles
- **HIVE041** : Cross-Cutting Concerns Architecture - Architecture des préoccupations transversales
- **HIVE005** : Common Identifier Model Interfaces - Interfaces standardisées pour les identifiants
- **HIVE027** : PHPUnit Testing Standards - Standards de test pour valider les modèles riches

## 🚨 Le Problème des Modèles Anémiques

### Qu'est-ce qu'un Modèle Anémique ?

Un modèle anémique est un modèle de données qui ne contient que des propriétés (getters/setters) sans logique métier. Toute la logique est déplacée dans des services externes.

```php
// ❌ Modèle Anémique - Exemple typique
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
    public function getFirstName(): string { return $this->firstName; }
    public function setFirstName(string $firstName): void { $this->firstName = $firstName; }
    public function getLastName(): string { return $this->lastName; }
    public function setLastName(string $lastName): void { $this->lastName = $lastName; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): void { $this->isActive = $isActive; }
}
```

### Pourquoi c'est Problématique ?

1. **Perte de l'Intention Métier** : Le code ne reflète plus les règles métier
2. **Violation du Principe DRY** : La logique est dupliquée dans les services
3. **Difficile à Tester** : Les règles métier sont éparpillées
4. **Évolution Complexe** : Changer une règle métier nécessite de modifier plusieurs endroits

### Exemple Concret : Gestion des Paiements

```php
// ❌ Approche Anémique
class PaymentService
{
    public function processPayment(Payment $payment, float $amount): void
    {
        // Logique métier dans le service
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be positive');
        }
        
        if ($payment->getStatus() !== 'pending') {
            throw new InvalidStateException('Payment already processed');
        }
        
        if ($amount > $payment->getMaxAmount()) {
            throw new BusinessRuleException('Amount exceeds maximum');
        }
        
        // Plus de logique métier...
        $payment->setStatus('processed');
        $payment->setProcessedAt(new DateTime());
        $payment->setAmount($amount);
    }
}
```

### Exemple du projet Gyroscops Cloud : Modèle Anémique

Voici un exemple réel d'un modèle anémique que l'on pourrait trouver dans le projet Gyroscops Cloud :

```php
// ❌ Modèle Anémique - Exemple typique (projet Gyroscops Cloud)
class Payment
{
    public function __construct(
        private string $id,
        private string $amount,
        private string $currency,
        private string $status,
        private ?string $processedAt = null
    ) {}

    // Seulement des getters/setters
    public function getId(): string { return $this->id; }
    public function getAmount(): string { return $this->amount; }
    public function setAmount(string $amount): void { $this->amount = $amount; }
    public function getCurrency(): string { return $this->currency; }
    public function setCurrency(string $currency): void { $this->currency = $currency; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function getProcessedAt(): ?string { return $this->processedAt; }
    public function setProcessedAt(?string $processedAt): void { $this->processedAt = $processedAt; }
}
```

**Problèmes identifiés** :
- **Violation de HIVE040** : Pas de patterns d'accès aux propriétés appropriés
- **Violation de HIVE041** : Mélange de responsabilités (données + logique métier)
- **Violation de HIVE005** : Pas d'interface standardisée pour les identifiants
- **Difficile à tester** : Logique métier éparpillée dans les services

## ✅ La Solution : Modèles Riches

### Principe Fondamental

Le DDD place la logique métier au cœur du modèle, dans les entités et objets de valeur.

### Exemple du projet Gyroscops Cloud : Modèle Riche

Voici un exemple réel de modèle riche du projet Gyroscops Cloud, respectant les ADR :

```php
// ✅ Modèle Riche - Approche DDD (projet Gyroscops Cloud)
final class Payment
{
    public function __construct(
        public readonly PaymentId $uuid,                    // HIVE005 : Interface standardisée
        public readonly RealmId $realmId,                   // HIVE005 : Interface standardisée
        public readonly OrganizationId $organizationId,     // HIVE005 : Interface standardisée
        public readonly SubscriptionId $subscriptionId,     // HIVE005 : Interface standardisée
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

    public static function registerManualPayment(
        PaymentId $uuid,
        RealmId $realmId,
        OrganizationId $organizationId,
        SubscriptionId $subscriptionId,
        \DateTimeInterface $creationDate,
        \DateTimeInterface $expirationDate,
        ?\DateTimeInterface $completionDate,
        string $customerName,
        string $customerEmail,
        Statuses $status,
        Price $subtotal,
        Price $discount,
        Price $vat,
        Price $total,
    ): self {
        $instance = new self($uuid, $realmId, $organizationId, $subscriptionId);

        $instance->recordThat(new RegisteredPaymentEvent(
            uuid: $uuid,
            version: 1,
            realmId: $realmId,
            organizationId: $organizationId,
            subscriptionId: $subscriptionId,
            creationDate: $creationDate,
            expirationDate: $expirationDate,
            completionDate: $completionDate,
            customerName: $customerName,
            customerEmail: $customerEmail,
            status: $status,
            gateway: Gateways::Manual,
            subtotal: $subtotal,
            discount: $discount,
            taxes: $vat,
            total: $total,
        ));

        return $instance;
    }

    public function capture(Price $amount): void
    {
        $this->recordThat(new CapturedEvent(
            uuid: $this->uuid,
            version: $this->version + 1,
            realmId: $this->realmId,
            organizationId: $this->organizationId,
            subscriptionId: $this->subscriptionId,
            status: Statuses::Completed,
            gateway: $this->gateway,
            subtotal: $this->subtotal,
            discount: $this->discount,
            taxes: $this->taxes,
            total: $this->total,
            completionDate: new \DateTimeImmutable(),
        ));
    }

    public function fail(string $reason): void
    {
        $this->recordThat(new FailedEvent(
            uuid: $this->uuid,
            version: $this->version + 1,
            realmId: $this->realmId,
            organizationId: $this->organizationId,
            subscriptionId: $this->subscriptionId,
            status: Statuses::Failed,
            reason: $reason,
        ));
    }

    private function canTransitionTo(Statuses $status): bool
    {
        return match ($this->status) {
            Statuses::Pending => match ($status) {
                Statuses::Pending, Statuses::Authorized, Statuses::Completed, Statuses::Cancelled, Statuses::Failed => true,
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

**Conformité aux ADR** :
- **HIVE040** : Utilisation de propriétés publiques en lecture seule (`public readonly`)
- **HIVE041** : Séparation claire des responsabilités (logique métier dans l'agrégat)
- **HIVE005** : Utilisation d'interfaces standardisées pour les identifiants
- **Event Sourcing** : Chaque changement d'état est enregistré comme un événement
- **Protection des invariants** : `canTransitionTo()` protège les transitions d'état valides

### Avantages du Modèle Riche

1. **Intention Métier Préservée** : Le code reflète les règles métier
2. **Cohérence** : Les règles sont centralisées dans le modèle
3. **Testabilité** : Facile de tester les règles métier
4. **Évolutivité** : Changer une règle ne nécessite qu'une modification

## Patterns de Modèles Riches

### 1. Constructeurs Privés avec Méthodes Statiques

```php
class User
{
    private function __construct(
        private UserId $id,
        private Email $email,
        private UserName $name,
        private UserStatus $status
    ) {}

    public static function create(UserId $id, Email $email, UserName $name): self
    {
        return new self($id, $email, $name, UserStatus::ACTIVE);
    }

    public static function fromExisting(
        UserId $id,
        Email $email,
        UserName $name,
        UserStatus $status
    ): self {
        return new self($id, $email, $name, $status);
    }
}
```

### 2. Objets de Valeur pour les Propriétés Complexes

```php
class Money
{
    public function __construct(
        private float $amount,
        private Currency $currency
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }
    }

    public function add(Money $other): Money
    {
        if (!$this->currency->equals($other->currency)) {
            throw new CurrencyMismatchException();
        }
        
        return new Money($this->amount + $other->amount, $this->currency);
    }

    public function multiply(float $factor): Money
    {
        return new Money($this->amount * $factor, $this->currency);
    }

    public function isGreaterThan(Money $other): bool
    {
        if (!$this->currency->equals($other->currency)) {
            throw new CurrencyMismatchException();
        }
        
        return $this->amount > $other->amount;
    }
}
```

### Exemple du projet Gyroscops Cloud : Value Object Price

Voici un exemple réel de Value Object du projet Gyroscops Cloud :

```php
// ✅ Value Object Price - projet Gyroscops Cloud
final readonly class Price
{
    public function __construct(
        public BigDecimal $amount,
        public Currencies $currency,
    ) {
        Assertion::true($this->amount->isGreaterThanOrEqualTo(0));
    }

    public function substract(self $price): self
    {
        if ($price->currency !== $this->currency) {
            throw new \DomainException('Currency conversion is not supported');
        }

        return new self($this->amount->minus($price->amount), $this->currency);
    }

    public function add(self $price): self
    {
        if ($price->currency !== $this->currency) {
            throw new \DomainException('Currency conversion is not supported');
        }

        return new self($this->amount->plus($price->amount), $this->currency);
    }

    public function isGreaterThan(self $price): bool
    {
        if ($price->currency !== $this->currency) {
            throw new \DomainException('Currency conversion is not supported');
        }

        return $this->amount->isGreaterThan($price->amount);
    }

    public function isGreaterThanOrEqualTo(self $price): bool
    {
        if ($price->currency !== $this->currency) {
            throw new \DomainException('Currency conversion is not supported');
        }

        return $this->amount->isGreaterThanOrEqualTo($price->amount);
    }
}
```

**Caractéristiques de ce Value Object :**
- **Immuable** : `readonly` class
- **Validation** : Vérification que le montant est positif
- **Logique métier** : Opérations arithmétiques avec validation des devises
- **Type safety** : Utilisation de `BigDecimal` pour la précision
- **Exceptions métier** : Messages d'erreur explicites

### 3. Énumérations pour les États

```php
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PROCESSED = 'processed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public function canTransitionTo(PaymentStatus $newStatus): bool
    {
        return match ($this) {
            self::PENDING => in_array($newStatus, [self::PROCESSED, self::FAILED]),
            self::PROCESSED => $newStatus === self::REFUNDED,
            self::FAILED => $newStatus === self::PENDING,
            self::REFUNDED => false,
        };
    }
}
```

### Exemple du projet Gyroscops Cloud : Énumération Statuses

Voici un exemple réel d'énumération du projet Gyroscops Cloud :

```php
// ✅ Énumération Statuses - projet Gyroscops Cloud
enum Statuses: string
{
    case Pending = 'Pending';
    case Active = 'Active';
    case Cancelled = 'Cancelled';
    case Suspended = 'Suspended';
    case Expired = 'Expired';
    case Declined = 'Declined';

    public function isEnabled(): bool
    {
        return self::Active === $this || self::Cancelled === $this;
    }

    public function isPending(): bool
    {
        return self::Pending === $this;
    }
}
```

**Caractéristiques de cette énumération :**
- **Logique métier** : Méthodes `isEnabled()` et `isPending()`
- **Type safety** : Utilisation d'énumérations PHP 8.1+
- **Lisibilité** : Noms explicites pour chaque état
- **Encapsulation** : Logique métier dans l'énumération

### Exemple du projet Gyroscops Cloud : Énumération Recurrences

```php
// ✅ Énumération Recurrences - projet Gyroscops Cloud
enum Recurrences: string
{
    case Once = 'Once';
    case Monthly = 'Monthly';
    case Quarterly = 'Quarterly';
    case Biannually = 'Biannually';
    case Yearly = 'Yearly';

    public function nextPaymentTerm(\DateTimeInterface $lastPaymentDate): ?\DateTimeInterface
    {
        $lastPaymentDate = \DateTimeImmutable::createFromInterface($lastPaymentDate);

        return match ($this) {
            Recurrences::Once => null,
            Recurrences::Monthly => $lastPaymentDate->add(new \DateInterval('P1M')),
            Recurrences::Quarterly => $lastPaymentDate->add(new \DateInterval('P3M')),
            Recurrences::Biannually => $lastPaymentDate->add(new \DateInterval('P6M')),
            Recurrences::Yearly => $lastPaymentDate->add(new \DateInterval('P1Y')),
        };
    }
}
```

**Caractéristiques de cette énumération :**
- **Logique métier complexe** : Calcul de la prochaine échéance
- **Pattern matching** : Utilisation de `match` pour la logique
- **Immutabilité** : Retour d'un nouvel objet `DateTimeImmutable`
- **Null safety** : Retour de `null` pour les paiements uniques

### 4. Méthodes Métier Expressives

```php
class Subscription
{
    public function renew(): void
    {
        if (!$this->canBeRenewed()) {
            throw new SubscriptionCannotBeRenewedException();
        }
        
        $this->status = SubscriptionStatus::ACTIVE;
        $this->renewedAt = new DateTime();
        $this->expiresAt = $this->calculateNewExpirationDate();
    }

    public function cancel(): void
    {
        if (!$this->canBeCancelled()) {
            throw new SubscriptionCannotBeCancelledException();
        }
        
        $this->status = SubscriptionStatus::CANCELLED;
        $this->cancelledAt = new DateTime();
    }

    private function canBeRenewed(): bool
    {
        return $this->status === SubscriptionStatus::EXPIRED
            && $this->isNotInGracePeriod();
    }

    private function canBeCancelled(): bool
    {
        return $this->status === SubscriptionStatus::ACTIVE
            && !$this->isInGracePeriod();
    }
}
```

### Exemple du projet Gyroscops Cloud : Identifiants Value Objects

Voici un exemple réel d'identifiant du projet Gyroscops Cloud :

```php
// ✅ Identifiant UserId - projet Gyroscops Cloud
final readonly class UserId implements UuidInterface, LocatableInterface
{
    public const string REQUIREMENT = Requirement::UUID_V7;
    public const string URI_REQUIREMENT = '\/authentication\/users\/('.Requirement::UUID_V7.')';
    public const string URI_PARSE = '/^\/authentication\/users\/(?<reference>'.Requirement::UUID.')$/';

    private function __construct(
        private string $reference,
    ) {
        if (!uuid_is_valid($this->reference)) {
            throw new InvalidUuidFormatException(\sprintf('<%s> is not a valid UUID.', $reference));
        }
    }

    public static function generateRandom(): self
    {
        return new self(UuidV7::generate());
    }

    public static function nil(): self
    {
        return new self(uuid_create(UUID_TYPE_NULL));
    }

    public static function fromUri(string $uri): self
    {
        if (!preg_match(self::URI_PARSE, $uri, $matches)) {
            throw new InvalidUriFormatException(\sprintf('<%s> is not a valid URI.', $uri));
        }

        return new self($matches['reference']);
    }

    public static function fromString(string $reference): self
    {
        return new self($reference);
    }

    public function equals(IdInterface|string $other): bool
    {
        if (\is_string($other)) {
            return 0 === uuid_compare($this->reference, $other);
        }

        if (!$other instanceof self) {
            return false;
        }

        return 0 === uuid_compare($this->reference, $other->reference);
    }

    public function isNil(): bool
    {
        return uuid_is_null($this->reference);
    }

    public function __toString(): string
    {
        return $this->reference;
    }
}
```

**Caractéristiques de cet identifiant :**
- **Type safety** : Implémentation d'interfaces spécifiques
- **Validation** : Vérification du format UUID
- **Factory methods** : Méthodes statiques pour créer des instances
- **Comparaison** : Méthode `equals()` pour la comparaison
- **URI support** : Parsing et génération d'URIs
- **Immutabilité** : `readonly` class

### 📚 Références aux ADR du projet Gyroscops Cloud

Ces exemples suivent les patterns établis dans les Architecture Decision Records du projet Gyroscops Cloud :

- **[HIVE040](https://github.com/yourusername/hive/blob/main/architecture/HIVE040-enhanced-models-with-property-access-patterns.md)** : Enhanced Models with Property Access Patterns - Patterns de modèles riches avec accès aux propriétés
- **[HIVE005](https://github.com/yourusername/hive/blob/main/architecture/HIVE005-common-identifier-model-interfaces.md)** : Common Identifier Model Interfaces - Interfaces communes pour les identifiants
- **[HIVE004](https://github.com/yourusername/hive/blob/main/architecture/HIVE004-opaque-and-secret-data-objects.md)** : Opaque and Secret Data Objects - Objets de données sécurisés
- **[HIVE003](https://github.com/yourusername/hive/blob/main/architecture/HIVE003-dates-management.md)** : Dates Management - Gestion des dates et timezones

## Transformation d'un Modèle Anémique

### Avant : Modèle Anémique

```php
// ❌ Modèle Anémique
class Order
{
    public function __construct(
        private string $id,
        private string $customerId,
        private array $items,
        private float $total,
        private string $status
    ) {}

    // Getters et setters uniquement
    public function getId(): string { return $this->id; }
    public function getCustomerId(): string { return $this->customerId; }
    public function getItems(): array { return $this->items; }
    public function setItems(array $items): void { $this->items = $items; }
    public function getTotal(): float { return $this->total; }
    public function setTotal(float $total): void { $this->total = $total; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }
}

// Logique métier dans le service
class OrderService
{
    public function addItem(Order $order, string $itemId, int $quantity, float $price): void
    {
        $order->getItems()[] = ['id' => $itemId, 'quantity' => $quantity, 'price' => $price];
        $order->setTotal($this->calculateTotal($order->getItems()));
    }

    public function confirm(Order $order): void
    {
        if ($order->getStatus() !== 'draft') {
            throw new InvalidStateException('Order cannot be confirmed');
        }
        $order->setStatus('confirmed');
    }
}
```

### Après : Modèle Riche

```php
// ✅ Modèle Riche
class Order
{
    private function __construct(
        private OrderId $id,
        private CustomerId $customerId,
        private OrderItems $items,
        private Money $total,
        private OrderStatus $status
    ) {}

    public static function create(OrderId $id, CustomerId $customerId): self
    {
        return new self(
            $id,
            $customerId,
            OrderItems::empty(),
            Money::zero(),
            OrderStatus::DRAFT
        );
    }

    public function addItem(ItemId $itemId, Quantity $quantity, Money $price): void
    {
        if (!$this->canAddItems()) {
            throw new OrderCannotAddItemsException();
        }
        
        $this->items = $this->items->add(new OrderItem($itemId, $quantity, $price));
        $this->total = $this->items->calculateTotal();
    }

    public function confirm(): void
    {
        if (!$this->canBeConfirmed()) {
            throw new OrderCannotBeConfirmedException();
        }
        
        $this->status = OrderStatus::CONFIRMED;
    }

    private function canAddItems(): bool
    {
        return $this->status === OrderStatus::DRAFT;
    }

    private function canBeConfirmed(): bool
    {
        return $this->status === OrderStatus::DRAFT
            && !$this->items->isEmpty()
            && $this->total->isPositive();
    }
}
```

## 🧪 Tests des Modèles Riches

### Tests Unitaires Simplifiés

```php
class PaymentTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_valid_amount(): void
    {
        $payment = Payment::create(
            PaymentId::generate(),
            new Money(100.0, Currency::EUR)
        );

        $this->assertEquals(PaymentStatus::PENDING, $payment->getStatus());
    }

    #[Test]
    public function it_cannot_be_created_with_negative_amount(): void
    {
        $this->expectException(InvalidAmountException::class);
        
        Payment::create(
            PaymentId::generate(),
            new Money(-100.0, Currency::EUR)
        );
    }

    #[Test]
    public function it_can_be_processed_when_pending(): void
    {
        $payment = Payment::create(
            PaymentId::generate(),
            new Money(100.0, Currency::EUR)
        );

        $payment->process(new Money(100.0, Currency::EUR));

        $this->assertEquals(PaymentStatus::PROCESSED, $payment->getStatus());
    }
}
```

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Application simple, équipe junior, développement rapide" 
    subtitle="Vous voulez une approche simple et efficace" 
    criteria="Équipe de 1-3 développeurs,Application monolithique,Peu d'intégrations externes,Développement rapide requis,Cohérence forte requise" 
    time="30-45 minutes" 
    chapter="9" 
    chapter-title="Repositories et Persistance" 
    chapter-url="/chapitres/fondamentaux/chapitre-09-repositories-persistance/" 
  >}}

  {{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Système avec intégrations, besoin de découplage" 
    subtitle="Vous voulez explorer l'architecture événementielle" 
    criteria="Équipe de 3-8 développeurs,Intégrations multiples,Besoin de découplage,Architecture distribuée,Équipe expérimentée" 
    time="25-35 minutes" 
    chapter="8" 
    chapter-title="Architecture Événementielle" 
    chapter-url="/chapitres/fondamentaux/chapitre-08-architecture-evenementielle/" 
  >}}

  {{< chapter-option 
    letter="C" 
    color="red" 
    title="Je veux d'abord voir les options de stockage" 
    subtitle="Vous voulez comprendre les patterns de repository" 
    criteria="Besoin de comprendre la persistance,Implémentation technique importante,Choix de stockage à faire,Patterns de repository à maîtriser" 
    time="30-45 minutes" 
    chapter="15" 
    chapter-title="Choix du Type de Stockage" 
    chapter-url="/chapitres/stockage/chapitre-15-choix-type-stockage/" 
  >}}
{{< /chapter-nav >}}