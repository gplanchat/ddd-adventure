---
title: "Chapitre 58 : Gestion des Données et Validation"
description: "Gestion des données et validation dans le projet Hive avec des exemples concrets"
date: 2024-12-19
draft: true
type: "docs"
weight: 58
---

## 🎯 Objectif de ce Chapitre

Ce chapitre vous montre comment gérer les données et la validation dans le projet Hive. Vous apprendrez :
- Comment valider les données d'entrée
- Comment gérer les erreurs de validation
- Comment transformer les données entre les couches
- Comment tester la validation

### Références aux ADR du Projet Hive

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du projet Hive :
- **HIVE003** : Dates Management - Gestion des dates et timezones
- **HIVE004** : Opaque and Secret Data Objects - Gestion des données sensibles
- **HIVE036** : Input Validation Patterns - Patterns de validation des entrées
- **HIVE038** : Robust Error Handling Patterns - Patterns de gestion d'erreurs
- **HIVE027** : PHPUnit Testing Standards - Standards de test PHPUnit

## 🏗️ Architecture de la Validation

### Structure de Validation

```
api/src/
├── Platform/
│   ├── Domain/
│   │   ├── Validation/
│   │   │   ├── ValidatorInterface.php
│   │   │   ├── ValidationResult.php
│   │   │   └── ValidationException.php
│   │   └── ValueObjects/
│   │       ├── Email.php
│   │       ├── Money.php
│   │       └── DateRange.php
│   └── Infrastructure/
│       ├── Validation/
│       │   ├── SymfonyValidator.php
│       │   └── CustomValidator.php
│       └── ValueObjects/
│           ├── EmailValidator.php
│           └── MoneyValidator.php
```

### Interface de Validation

```php
// ✅ Interface de Validation (Projet Hive)
interface ValidatorInterface
{
    public function validate(mixed $data, array $constraints = []): ValidationResult;
    public function validateProperty(mixed $object, string $property, array $constraints = []): ValidationResult;
    public function validateValue(mixed $value, array $constraints = []): ValidationResult;
}

final class ValidationResult
{
    public function __construct(
        public readonly bool $isValid,
        public readonly array $violations = []
    ) {}

    public function hasViolations(): bool
    {
        return !empty($this->violations);
    }

    public function getViolationMessages(): array
    {
        return array_map(fn($violation) => $violation->getMessage(), $this->violations);
    }
}
```

## 📝 Validation des Données d'Entrée

### Validation des Value Objects

```php
// ✅ Value Object Email avec Validation (Projet Hive)
final readonly class Email
{
    public function __construct(private string $value)
    {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->value)) {
            throw new InvalidEmailException('Email cannot be empty');
        }

        if (!filter_var($this->value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException('Invalid email format');
        }

        if (strlen($this->value) > 255) {
            throw new InvalidEmailException('Email too long');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

### Validation des Agrégats

```php
// ✅ Agrégat Payment avec Validation (Projet Hive)
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
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if ($this->subtotal && $this->total && $this->subtotal->isGreaterThan($this->total)) {
            throw new InvalidPaymentException('Subtotal cannot be greater than total');
        }

        if ($this->discount && $this->subtotal && $this->discount->isGreaterThan($this->subtotal)) {
            throw new InvalidPaymentException('Discount cannot be greater than subtotal');
        }

        if ($this->captured && $this->total && $this->captured->isGreaterThan($this->total)) {
            throw new InvalidPaymentException('Captured amount cannot be greater than total');
        }
    }
}
```

### Validation des Commandes

```php
// ✅ Commande avec Validation (Projet Hive)
final class CreatePaymentCommand
{
    public function __construct(
        public readonly PaymentId $uuid,
        public readonly RealmId $realmId,
        public readonly OrganizationId $organizationId,
        public readonly SubscriptionId $subscriptionId,
        public readonly \DateTimeInterface $creationDate,
        public readonly \DateTimeInterface $expirationDate,
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly Statuses $status,
        public readonly Price $subtotal,
        public readonly Price $discount,
        public readonly Price $taxes,
        public readonly Price $total,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->customerName)) {
            throw new InvalidCommandException('Customer name cannot be empty');
        }

        if (strlen($this->customerName) > 255) {
            throw new InvalidCommandException('Customer name too long');
        }

        if (empty($this->customerEmail)) {
            throw new InvalidCommandException('Customer email cannot be empty');
        }

        if (!filter_var($this->customerEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidCommandException('Invalid customer email format');
        }

        if ($this->expirationDate <= $this->creationDate) {
            throw new InvalidCommandException('Expiration date must be after creation date');
        }

        if ($this->subtotal->isNegative()) {
            throw new InvalidCommandException('Subtotal cannot be negative');
        }

        if ($this->discount->isNegative()) {
            throw new InvalidCommandException('Discount cannot be negative');
        }

        if ($this->taxes->isNegative()) {
            throw new InvalidCommandException('Taxes cannot be negative');
        }

        if ($this->total->isNegative()) {
            throw new InvalidCommandException('Total cannot be negative');
        }
    }
}
```

## 🔍 Validation des Requêtes

### Validation des Paramètres de Requête

```php
// ✅ Validation des Paramètres de Requête (Projet Hive)
final class PaymentQueryValidator
{
    public function __construct(
        private ValidatorInterface $validator
    ) {}

    public function validateQueryParameters(array $parameters): ValidationResult
    {
        $constraints = [
            'page' => [
                new Type('integer'),
                new Range(['min' => 1, 'max' => 1000])
            ],
            'pageSize' => [
                new Type('integer'),
                new Range(['min' => 1, 'max' => 100])
            ],
            'status' => [
                new Type('string'),
                new Choice(['choices' => ['pending', 'completed', 'failed', 'cancelled']])
            ],
            'organizationId' => [
                new Type('string'),
                new Uuid()
            ],
            'dateFrom' => [
                new Type('string'),
                new DateTime(['format' => 'Y-m-d H:i:s'])
            ],
            'dateTo' => [
                new Type('string'),
                new DateTime(['format' => 'Y-m-d H:i:s'])
            ]
        ];

        return $this->validator->validate($parameters, $constraints);
    }
}
```

### Validation des Filtres

```php
// ✅ Validation des Filtres (Projet Hive)
final class PaymentFilterValidator
{
    public function __construct(
        private ValidatorInterface $validator
    ) {}

    public function validateFilters(array $filters): ValidationResult
    {
        $constraints = [
            'amountMin' => [
                new Type('numeric'),
                new Range(['min' => 0])
            ],
            'amountMax' => [
                new Type('numeric'),
                new Range(['min' => 0])
            ],
            'currency' => [
                new Type('string'),
                new Choice(['choices' => ['EUR', 'USD', 'GBP']])
            ],
            'gateway' => [
                new Type('string'),
                new Choice(['choices' => ['stripe', 'paypal', 'manual']])
            ]
        ];

        return $this->validator->validate($filters, $constraints);
    }
}
```

## 🚨 Gestion des Erreurs de Validation

### Exceptions de Validation

```php
// ✅ Exceptions de Validation (Projet Hive)
final class ValidationException extends \DomainException
{
    public function __construct(
        private array $violations,
        string $message = 'Validation failed',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getViolations(): array
    {
        return $this->violations;
    }

    public function getViolationMessages(): array
    {
        return array_map(fn($violation) => $violation->getMessage(), $this->violations);
    }
}

final class InvalidEmailException extends ValidationException
{
    public function __construct(string $message = 'Invalid email')
    {
        parent::__construct([], $message);
    }
}

final class InvalidPaymentException extends ValidationException
{
    public function __construct(string $message = 'Invalid payment')
    {
        parent::__construct([], $message);
    }
}
```

### Gestionnaire d'Erreurs de Validation

```php
// ✅ Gestionnaire d'Erreurs de Validation (Projet Hive)
final class ValidationErrorHandler
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function handleValidationException(ValidationException $exception): array
    {
        $this->logger->warning('Validation failed', [
            'violations' => $exception->getViolationMessages()
        ]);

        return [
            'error' => 'Validation failed',
            'violations' => $exception->getViolationMessages(),
            'code' => 'VALIDATION_ERROR'
        ];
    }

    public function handleInvalidEmailException(InvalidEmailException $exception): array
    {
        $this->logger->warning('Invalid email provided', [
            'message' => $exception->getMessage()
        ]);

        return [
            'error' => 'Invalid email',
            'message' => $exception->getMessage(),
            'code' => 'INVALID_EMAIL'
        ];
    }
}
```

## 🔄 Transformation des Données

### Mapper de Données

```php
// ✅ Mapper de Données (Projet Hive)
final class PaymentDataMapper
{
    public function __construct(
        private ValidatorInterface $validator
    ) {}

    public function mapFromArray(array $data): CreatePaymentCommand
    {
        $this->validateInputData($data);

        return new CreatePaymentCommand(
            uuid: PaymentId::fromString($data['uuid']),
            realmId: RealmId::fromString($data['realm_id']),
            organizationId: OrganizationId::fromString($data['organization_id']),
            subscriptionId: SubscriptionId::fromString($data['subscription_id']),
            creationDate: new \DateTimeImmutable($data['creation_date']),
            expirationDate: new \DateTimeImmutable($data['expiration_date']),
            customerName: $data['customer_name'],
            customerEmail: $data['customer_email'],
            status: Statuses::from($data['status']),
            subtotal: new Price(
                BigDecimal::of($data['subtotal']),
                Currencies::from($data['subtotal_currency'])
            ),
            discount: new Price(
                BigDecimal::of($data['discount']),
                Currencies::from($data['discount_currency'])
            ),
            taxes: new Price(
                BigDecimal::of($data['taxes']),
                Currencies::from($data['taxes_currency'])
            ),
            total: new Price(
                BigDecimal::of($data['total']),
                Currencies::from($data['total_currency'])
            )
        );
    }

    public function mapToArray(Payment $payment): array
    {
        return [
            'uuid' => $payment->uuid->toString(),
            'realm_id' => $payment->realmId->toString(),
            'organization_id' => $payment->organizationId->toString(),
            'subscription_id' => $payment->subscriptionId->toString(),
            'creation_date' => $payment->creationDate?->format('Y-m-d H:i:s'),
            'expiration_date' => $payment->expirationDate?->format('Y-m-d H:i:s'),
            'completion_date' => $payment->completionDate?->format('Y-m-d H:i:s'),
            'status' => $payment->getStatus()?->value,
            'gateway' => $payment->gateway?->value,
            'subtotal' => $payment->subtotal?->amount->toString(),
            'subtotal_currency' => $payment->subtotal?->currency->value,
            'discount' => $payment->discount?->amount->toString(),
            'discount_currency' => $payment->discount?->currency->value,
            'taxes' => $payment->taxes?->amount->toString(),
            'taxes_currency' => $payment->taxes?->currency->value,
            'total' => $payment->total?->amount->toString(),
            'total_currency' => $payment->total?->currency->value,
            'captured' => $payment->captured?->amount->toString(),
            'captured_currency' => $payment->captured?->currency->value,
            'version' => $payment->version
        ];
    }

    private function validateInputData(array $data): void
    {
        $constraints = [
            'uuid' => [new Type('string'), new Uuid()],
            'realm_id' => [new Type('string'), new Uuid()],
            'organization_id' => [new Type('string'), new Uuid()],
            'subscription_id' => [new Type('string'), new Uuid()],
            'creation_date' => [new Type('string'), new DateTime(['format' => 'Y-m-d H:i:s'])],
            'expiration_date' => [new Type('string'), new DateTime(['format' => 'Y-m-d H:i:s'])],
            'customer_name' => [new Type('string'), new NotBlank(), new Length(['max' => 255])],
            'customer_email' => [new Type('string'), new NotBlank(), new Email()],
            'status' => [new Type('string'), new Choice(['choices' => ['pending', 'completed', 'failed', 'cancelled']])],
            'subtotal' => [new Type('string'), new Regex(['pattern' => '/^\d+\.\d{2}$/'])],
            'subtotal_currency' => [new Type('string'), new Choice(['choices' => ['EUR', 'USD', 'GBP']])],
            'discount' => [new Type('string'), new Regex(['pattern' => '/^\d+\.\d{2}$/'])],
            'discount_currency' => [new Type('string'), new Choice(['choices' => ['EUR', 'USD', 'GBP']])],
            'taxes' => [new Type('string'), new Regex(['pattern' => '/^\d+\.\d{2}$/'])],
            'taxes_currency' => [new Type('string'), new Choice(['choices' => ['EUR', 'USD', 'GBP']])],
            'total' => [new Type('string'), new Regex(['pattern' => '/^\d+\.\d{2}$/'])],
            'total_currency' => [new Type('string'), new Choice(['choices' => ['EUR', 'USD', 'GBP']])]
        ];

        $result = $this->validator->validate($data, $constraints);
        
        if (!$result->isValid) {
            throw new ValidationException($result->violations);
        }
    }
}
```

## 🧪 Tests de Validation

### Test des Value Objects

```php
// ✅ Test des Value Objects (Projet Hive)
final class EmailTest extends TestCase
{
    /** @test */
    public function itShouldCreateValidEmail(): void
    {
        // Act
        $email = new Email('john.doe@example.com');
        
        // Assert
        $this->assertEquals('john.doe@example.com', $email->__toString());
    }

    /** @test */
    public function itShouldThrowExceptionForEmptyEmail(): void
    {
        // Act & Assert
        $this->expectException(InvalidEmailException::class);
        $this->expectExceptionMessage('Email cannot be empty');
        
        new Email('');
    }

    /** @test */
    public function itShouldThrowExceptionForInvalidEmailFormat(): void
    {
        // Act & Assert
        $this->expectException(InvalidEmailException::class);
        $this->expectExceptionMessage('Invalid email format');
        
        new Email('invalid-email');
    }

    /** @test */
    public function itShouldThrowExceptionForEmailTooLong(): void
    {
        // Act & Assert
        $this->expectException(InvalidEmailException::class);
        $this->expectExceptionMessage('Email too long');
        
        new Email(str_repeat('a', 256) . '@example.com');
    }
}
```

### Test des Agrégats

```php
// ✅ Test des Agrégats (Projet Hive)
final class PaymentTest extends TestCase
{
    /** @test */
    public function itShouldCreateValidPayment(): void
    {
        // Arrange
        $uuid = PaymentId::generate();
        $realmId = RealmId::generate();
        $organizationId = OrganizationId::generate();
        $subscriptionId = SubscriptionId::generate();
        $creationDate = new \DateTimeImmutable();
        $expirationDate = new \DateTimeImmutable('+30 days');
        $subtotal = new Price(BigDecimal::of('100.00'), Currencies::EUR);
        $discount = new Price(BigDecimal::of('0.00'), Currencies::EUR);
        $taxes = new Price(BigDecimal::of('20.00'), Currencies::EUR);
        $total = new Price(BigDecimal::of('120.00'), Currencies::EUR);
        
        // Act
        $payment = Payment::registerManualPayment(
            $uuid,
            $realmId,
            $organizationId,
            $subscriptionId,
            $creationDate,
            $expirationDate,
            null,
            'John Doe',
            'john.doe@example.com',
            Statuses::Pending,
            $subtotal,
            $discount,
            $taxes,
            $total
        );
        
        // Assert
        $this->assertEquals($uuid->toString(), $payment->uuid->toString());
        $this->assertEquals($realmId->toString(), $payment->realmId->toString());
        $this->assertEquals($organizationId->toString(), $payment->organizationId->toString());
        $this->assertEquals($subscriptionId->toString(), $payment->subscriptionId->toString());
    }

    /** @test */
    public function itShouldThrowExceptionForInvalidSubtotal(): void
    {
        // Arrange
        $uuid = PaymentId::generate();
        $realmId = RealmId::generate();
        $organizationId = OrganizationId::generate();
        $subscriptionId = SubscriptionId::generate();
        $creationDate = new \DateTimeImmutable();
        $expirationDate = new \DateTimeImmutable('+30 days');
        $subtotal = new Price(BigDecimal::of('150.00'), Currencies::EUR);
        $discount = new Price(BigDecimal::of('0.00'), Currencies::EUR);
        $taxes = new Price(BigDecimal::of('20.00'), Currencies::EUR);
        $total = new Price(BigDecimal::of('120.00'), Currencies::EUR);
        
        // Act & Assert
        $this->expectException(InvalidPaymentException::class);
        $this->expectExceptionMessage('Subtotal cannot be greater than total');
        
        Payment::registerManualPayment(
            $uuid,
            $realmId,
            $organizationId,
            $subscriptionId,
            $creationDate,
            $expirationDate,
            null,
            'John Doe',
            'john.doe@example.com',
            Statuses::Pending,
            $subtotal,
            $discount,
            $taxes,
            $total
        );
    }
}
```

### Test des Mappers

```php
// ✅ Test des Mappers (Projet Hive)
final class PaymentDataMapperTest extends TestCase
{
    private PaymentDataMapper $mapper;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->mapper = new PaymentDataMapper($this->validator);
    }

    /** @test */
    public function itShouldMapFromValidArray(): void
    {
        // Arrange
        $data = [
            'uuid' => '0197b105-0c38-75e3-8cd0-32c57bd7f35b',
            'realm_id' => '0197b105-0c38-75e3-8cd0-32c57bd7f35c',
            'organization_id' => '0197b105-0c38-75e3-8cd0-32c57bd7f35d',
            'subscription_id' => '0197b105-0c38-75e3-8cd0-32c57bd7f35e',
            'creation_date' => '2024-01-01 10:00:00',
            'expiration_date' => '2024-01-31 10:00:00',
            'customer_name' => 'John Doe',
            'customer_email' => 'john.doe@example.com',
            'status' => 'pending',
            'subtotal' => '100.00',
            'subtotal_currency' => 'EUR',
            'discount' => '0.00',
            'discount_currency' => 'EUR',
            'taxes' => '20.00',
            'taxes_currency' => 'EUR',
            'total' => '120.00',
            'total_currency' => 'EUR'
        ];

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ValidationResult(true));

        // Act
        $command = $this->mapper->mapFromArray($data);

        // Assert
        $this->assertInstanceOf(CreatePaymentCommand::class, $command);
        $this->assertEquals('0197b105-0c38-75e3-8cd0-32c57bd7f35b', $command->uuid->toString());
        $this->assertEquals('John Doe', $command->customerName);
        $this->assertEquals('john.doe@example.com', $command->customerEmail);
    }

    /** @test */
    public function itShouldThrowExceptionForInvalidData(): void
    {
        // Arrange
        $data = [
            'uuid' => 'invalid-uuid',
            'realm_id' => '0197b105-0c38-75e3-8cd0-32c57bd7f35c',
            'organization_id' => '0197b105-0c38-75e3-8cd0-32c57bd7f35d',
            'subscription_id' => '0197b105-0c38-75e3-8cd0-32c57bd7f35e',
            'creation_date' => '2024-01-01 10:00:00',
            'expiration_date' => '2024-01-31 10:00:00',
            'customer_name' => 'John Doe',
            'customer_email' => 'john.doe@example.com',
            'status' => 'pending',
            'subtotal' => '100.00',
            'subtotal_currency' => 'EUR',
            'discount' => '0.00',
            'discount_currency' => 'EUR',
            'taxes' => '20.00',
            'taxes_currency' => 'EUR',
            'total' => '120.00',
            'total_currency' => 'EUR'
        ];

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ValidationResult(false, [new ConstraintViolation('Invalid UUID')]));

        // Act & Assert
        $this->expectException(ValidationException::class);
        
        $this->mapper->mapFromArray($data);
    }
}
```

## 🏗️ Bonnes Pratiques du Projet Hive

### Architecture de Validation dans Hive

Le projet Hive suit une architecture de validation en couches, respectant les principes DDD et les ADR du projet :

```php
// ✅ Architecture de Validation Hive (Projet Hive)
final class HiveValidationService
{
    public function __construct(
        private ValidatorInterface $validator,
        private ErrorHandler $errorHandler,
        private LoggerInterface $logger
    ) {}

    public function validateCommand(mixed $command): ValidationResult
    {
        $this->logger->info('Validating command', [
            'command_class' => get_class($command),
            'command_data' => $this->sanitizeForLogging($command)
        ]);

        $constraints = $this->getConstraintsForCommand($command);
        $result = $this->validator->validate($command, $constraints);

        if (!$result->isValid) {
            $this->errorHandler->handleValidationErrors($result->violations);
        }

        return $result;
    }

    private function getConstraintsForCommand(mixed $command): array
    {
        return match (get_class($command)) {
            CreatePaymentCommand::class => $this->getPaymentConstraints(),
            CreateSubscriptionCommand::class => $this->getSubscriptionConstraints(),
            CreateOrganizationCommand::class => $this->getOrganizationConstraints(),
            default => []
        };
    }

    private function getPaymentConstraints(): array
    {
        return [
            'uuid' => [new Type('string'), new Uuid()],
            'realmId' => [new Type('string'), new Uuid()],
            'organizationId' => [new Type('string'), new Uuid()],
            'subscriptionId' => [new Type('string'), new Uuid()],
            'creationDate' => [new Type('string'), new DateTime(['format' => 'Y-m-d H:i:s'])],
            'expirationDate' => [new Type('string'), new DateTime(['format' => 'Y-m-d H:i:s'])],
            'customerName' => [new Type('string'), new NotBlank(), new Length(['max' => 255])],
            'customerEmail' => [new Type('string'), new NotBlank(), new Email()],
            'status' => [new Type('string'), new Choice(['choices' => ['pending', 'completed', 'failed', 'cancelled']])],
            'subtotal' => [new Type('string'), new Regex(['pattern' => '/^\d+\.\d{2}$/'])],
            'subtotalCurrency' => [new Type('string'), new Choice(['choices' => ['EUR', 'USD', 'GBP']])],
            'discount' => [new Type('string'), new Regex(['pattern' => '/^\d+\.\d{2}$/'])],
            'discountCurrency' => [new Type('string'), new Choice(['choices' => ['EUR', 'USD', 'GBP']])],
            'taxes' => [new Type('string'), new Regex(['pattern' => '/^\d+\.\d{2}$/'])],
            'taxesCurrency' => [new Type('string'), new Choice(['choices' => ['EUR', 'USD', 'GBP']])],
            'total' => [new Type('string'), new Regex(['pattern' => '/^\d+\.\d{2}$/'])],
            'totalCurrency' => [new Type('string'), new Choice(['choices' => ['EUR', 'USD', 'GBP']])]
        ];
    }
}
```

### Gestion des Erreurs selon HIVE038

Le projet Hive implémente une gestion d'erreurs robuste selon l'ADR HIVE038 :

```php
// ✅ Gestion d'Erreurs Hive (Projet Hive)
final class HiveErrorHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private ErrorReporter $reporter,
        private MetricsCollector $metrics
    ) {}

    public function handleValidationErrors(array $violations): void
    {
        $this->metrics->incrementCounter('validation_errors_total', [
            'type' => 'validation',
            'severity' => 'medium'
        ]);

        foreach ($violations as $violation) {
            $this->logger->warning('Validation error', [
                'field' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
                'invalid_value' => $violation->getInvalidValue(),
                'code' => $violation->getCode()
            ]);
        }

        $this->reporter->reportValidationErrors($violations);
    }

    public function handleDomainException(DomainException $exception): void
    {
        $this->metrics->incrementCounter('domain_errors_total', [
            'type' => 'domain',
            'severity' => $exception->getSeverity()->value
        ]);

        $this->logger->error('Domain error', [
            'error_code' => $exception->getErrorCode()->getCode(),
            'message' => $exception->getMessage(),
            'context' => $exception->getContext()->toArray()
        ]);

        if ($exception->getSeverity()->getPriority() >= ErrorSeverity::HIGH->getPriority()) {
            $this->reporter->reportDomainException($exception);
        }
    }
}
```

### Validation des Dates selon HIVE003

Le projet Hive gère les dates de manière cohérente selon l'ADR HIVE003 :

```php
// ✅ Validation des Dates Hive (Projet Hive)
final class HiveDateValidator
{
    public function __construct(
        private string $defaultTimezone = 'UTC'
    ) {}

    public function validateDateRange(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        string $fieldName = 'date_range'
    ): void
    {
        if ($endDate <= $startDate) {
            throw new ValidationException(
                new ErrorCode('INVALID_DATE_RANGE', ErrorSeverity::MEDIUM, 'validation'),
                new ErrorContext(['field' => $fieldName, 'start_date' => $startDate->format('Y-m-d H:i:s'), 'end_date' => $endDate->format('Y-m-d H:i:s')]),
                'End date must be after start date'
            );
        }

        $this->validateTimezone($startDate, $fieldName . '_start');
        $this->validateTimezone($endDate, $fieldName . '_end');
    }

    public function validateTimezone(\DateTimeInterface $date, string $fieldName): void
    {
        if ($date->getTimezone()->getName() !== $this->defaultTimezone) {
            throw new ValidationException(
                new ErrorCode('INVALID_TIMEZONE', ErrorSeverity::LOW, 'validation'),
                new ErrorContext(['field' => $fieldName, 'timezone' => $date->getTimezone()->getName(), 'expected' => $this->defaultTimezone]),
                'Date must be in UTC timezone'
            );
        }
    }

    public function normalizeDate(\DateTimeInterface $date): \DateTimeImmutable
    {
        return $date instanceof \DateTimeImmutable 
            ? $date->setTimezone(new \DateTimeZone($this->defaultTimezone))
            : \DateTimeImmutable::createFromInterface($date)->setTimezone(new \DateTimeZone($this->defaultTimezone));
    }
}
```

### Gestion des Données Sensibles selon HIVE004

Le projet Hive gère les données sensibles de manière sécurisée selon l'ADR HIVE004 :

```php
// ✅ Gestion des Données Sensibles Hive (Projet Hive)
final class HiveSecretDataHandler
{
    public function __construct(
        private EncryptionService $encryption,
        private LoggerInterface $logger
    ) {}

    public function sanitizeForLogging(mixed $data): mixed
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeForLogging'], $data);
        }

        if (is_object($data)) {
            $sanitized = [];
            $reflection = new \ReflectionClass($data);
            
            foreach ($reflection->getProperties() as $property) {
                $property->setAccessible(true);
                $value = $property->getValue($data);
                
                if ($this->isSensitiveField($property->getName())) {
                    $sanitized[$property->getName()] = '[REDACTED]';
                } else {
                    $sanitized[$property->getName()] = $this->sanitizeForLogging($value);
                }
            }
            
            return $sanitized;
        }

        return $data;
    }

    private function isSensitiveField(string $fieldName): bool
    {
        $sensitiveFields = [
            'password', 'token', 'secret', 'key', 'email', 'phone',
            'creditCard', 'bankAccount', 'ssn', 'socialSecurityNumber'
        ];

        foreach ($sensitiveFields as $sensitiveField) {
            if (stripos($fieldName, $sensitiveField) !== false) {
                return true;
            }
        }

        return false;
    }

    public function encryptSensitiveData(mixed $data): string
    {
        $serialized = serialize($data);
        return $this->encryption->encrypt($serialized);
    }

    public function decryptSensitiveData(string $encryptedData): mixed
    {
        $decrypted = $this->encryption->decrypt($encryptedData);
        return unserialize($decrypted);
    }
}
```

### Tests de Validation selon HIVE027

Le projet Hive suit les standards de test PHPUnit selon l'ADR HIVE027 :

```php
// ✅ Tests de Validation Hive (Projet Hive)
final class HiveValidationServiceTest extends TestCase
{
    private HiveValidationService $service;
    private ValidatorInterface $validator;
    private ErrorHandler $errorHandler;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->errorHandler = $this->createMock(ErrorHandler::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new HiveValidationService($this->validator, $this->errorHandler, $this->logger);
    }

    /** @test */
    public function itShouldValidateValidCommand(): void
    {
        // Arrange
        $command = new CreatePaymentCommand(
            PaymentId::generate(),
            RealmId::generate(),
            OrganizationId::generate(),
            SubscriptionId::generate(),
            new \DateTimeImmutable(),
            new \DateTimeImmutable('+30 days'),
            'John Doe',
            'john.doe@example.com',
            Statuses::Pending,
            new Price(BigDecimal::of('100.00'), Currencies::EUR),
            new Price(BigDecimal::of('0.00'), Currencies::EUR),
            new Price(BigDecimal::of('20.00'), Currencies::EUR),
            new Price(BigDecimal::of('120.00'), Currencies::EUR)
        );

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($command, $this->isType('array'))
            ->willReturn(new ValidationResult(true));

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Validating command', $this->isType('array'));

        // Act
        $result = $this->service->validateCommand($command);

        // Assert
        $this->assertTrue($result->isValid);
    }

    /** @test */
    public function itShouldHandleValidationErrors(): void
    {
        // Arrange
        $command = new CreatePaymentCommand(
            PaymentId::generate(),
            RealmId::generate(),
            OrganizationId::generate(),
            SubscriptionId::generate(),
            new \DateTimeImmutable(),
            new \DateTimeImmutable('+30 days'),
            '', // Invalid: empty name
            'invalid-email', // Invalid: malformed email
            Statuses::Pending,
            new Price(BigDecimal::of('100.00'), Currencies::EUR),
            new Price(BigDecimal::of('0.00'), Currencies::EUR),
            new Price(BigDecimal::of('20.00'), Currencies::EUR),
            new Price(BigDecimal::of('120.00'), Currencies::EUR)
        );

        $violations = [
            new ConstraintViolation('Customer name cannot be empty', 'customerName', ''),
            new ConstraintViolation('Invalid email format', 'customerEmail', 'invalid-email')
        ];

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ValidationResult(false, $violations));

        $this->errorHandler->expects($this->once())
            ->method('handleValidationErrors')
            ->with($violations);

        // Act
        $result = $this->service->validateCommand($command);

        // Assert
        $this->assertFalse($result->isValid);
        $this->assertCount(2, $result->violations);
    }
}
```

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux explorer la pagination et la performance" 
    subtitle="Vous voulez optimiser les performances de vos requêtes" 
    criteria="Besoin de performance sur les lectures,Grandes quantités de données,Requêtes complexes,Optimisation des requêtes" 
    time="25-35 minutes" 
    chapter="59" 
    chapter-title="Pagination et Performance" 
    chapter-url="/chapitres/chapitre-21/" 
  >}}
  
  {{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux explorer la gestion d'erreurs et l'observabilité" 
    subtitle="Vous voulez améliorer la robustesse de votre application" 
    criteria="Besoin de robustesse,Gestion d'erreurs complexe,Observabilité importante,Monitoring et logging" 
    time="30-40 minutes" 
    chapter="60" 
    chapter-title="Gestion d'Erreurs et Observabilité" 
    chapter-url="/chapitres/chapitre-22/" 
  >}}
  
  {{< chapter-option 
    letter="C" 
    color="red" 
    title="Je veux explorer les tests et la qualité" 
    subtitle="Vous voulez améliorer la qualité de votre code" 
    criteria="Besoin de qualité de code,Tests complets,Couverture de code,Standards de qualité" 
    time="35-45 minutes" 
    chapter="61" 
    chapter-title="Tests et Qualité" 
    chapter-url="/chapitres/chapitre-23/" 
  >}}
  
  {{< chapter-option 
    letter="D" 
    color="blue" 
    title="Je veux continuer avec la gestion des données" 
    subtitle="La gestion des données me convient parfaitement" 
    criteria="Application simple,Validation basique,Gestion d'erreurs simple,Pas de besoins complexes" 
    time="20-30 minutes" 
    chapter="62" 
    chapter-title="Sécurité et Autorisation" 
    chapter-url="/chapitres/chapitre-24/" 
  >}}
  
{{< /chapter-nav >}}