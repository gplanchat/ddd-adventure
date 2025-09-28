---
title: "Chapitre 18 : Stockage SQL - Approche CQRS"
description: "Implémentation du stockage SQL avec Command Query Responsibility Segregation pour des modèles distincts"
date: 2024-12-19
draft: true
type: "docs"
weight: 18
---

## 🎯 Objectif de ce Chapitre

Ce chapitre vous montre comment implémenter le stockage SQL avec Command Query Responsibility Segregation (CQRS) dans le Gyroscops Cloud. Vous apprendrez :
- Comment séparer complètement les modèles de commande et de requête
- Comment optimiser les performances de lecture et d'écriture
- Comment gérer la cohérence éventuelle entre les modèles
- Comment tester les repositories CQRS

### Références aux ADR du Projet Gyroscops Cloud

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du Gyroscops Cloud :
- **HIVE006** : Query Models for API Platform - Modèles de requête pour API Platform
- **HIVE007** : Command Models for API Platform - Modèles de commande pour API Platform
- **HIVE012** : Database Repositories - Patterns de repository pour base de données
- **HIVE033** : Hydrator Implementation Patterns - Patterns d'hydratation des données
- **HIVE023** : Repository Testing Strategies - Stratégies de test pour les repositories
- **HIVE027** : PHPUnit Testing Standards - Standards de test PHPUnit

## 🏗️ Architecture CQRS avec SQL

### Principe de Command Query Responsibility Segregation

CQRS sépare complètement les responsabilités :
- **Command Side** : Gère les écritures avec des modèles optimisés pour la cohérence
- **Query Side** : Gère les lectures avec des modèles optimisés pour la performance

### Structure des Repositories CQRS

```
api/src/
├── Accounting/
│   ├── Domain/
│   │   └── Payment/
│   │       ├── Command/
│   │       │   └── Payment.php          # Agrégat Payment (Command)
│   │       └── Query/
│   │           └── Payment.php          # Modèle de requête Payment (Query)
│   └── Infrastructure/
│       └── Payment/
│           ├── Command/
│           │   ├── DatabasePaymentRepository.php
│           │   └── PaymentEntity.php    # Entité Doctrine pour Command
│           └── Query/
│               ├── DatabasePaymentRepository.php
│               ├── PaymentHydrator.php  # Hydrateur pour Query
│               └── PaymentView.php      # Vue optimisée pour Query
```

## 📝 Command Side (Écriture)

### Agrégat Payment (Command)

```php
// ✅ Agrégat Payment - Command (Projet Gyroscops Cloud)
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

    private function recordThat(object $event): void
    {
        $this->events[] = $event;
        ++$this->version;
        $this->apply($event);
    }

    private function apply(object $event): void
    {
        $methodName = 'apply'.substr($event::class, strrpos($event::class, '\\') + 1);
        if (method_exists($this, $methodName)) {
            $this->{$methodName}($event);
        }
    }

    public function releaseEvents(): array
    {
        $releasedEvents = $this->events;
        $this->events = [];
        return $releasedEvents;
    }

    // Méthodes d'application des événements...
}
```

### Repository Command

```php
// ✅ Repository Command - DatabasePaymentRepository (Projet Gyroscops Cloud)
final class DatabasePaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventBusInterface $eventBus,
        private LoggerInterface $logger
    ) {}

    public function save(Payment $payment): void
    {
        try {
            $this->entityManager->beginTransaction();
            
            // Convertir l'agrégat en entité Doctrine
            $entity = $this->toEntity($payment);
            $this->entityManager->persist($entity);
            
            // Publier les événements
            $events = $payment->releaseEvents();
            foreach ($events as $event) {
                $this->eventBus->publish($event);
            }
            
            $this->entityManager->flush();
            $this->entityManager->commit();
            
            $this->logger->info('Payment saved successfully', [
                'payment_id' => $payment->uuid->toString(),
                'events_count' => count($events)
            ]);
            
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error('Failed to save payment', [
                'payment_id' => $payment->uuid->toString(),
                'error' => $e->getMessage()
            ]);
            throw new StorageException('Failed to save payment', 0, $e);
        }
    }

    public function find(PaymentId $id): Payment
    {
        $entity = $this->entityManager->find(PaymentEntity::class, $id->toString());
        
        if (!$entity) {
            throw new NotFoundException(sprintf('Payment with id %s not found', $id->toString()));
        }
        
        return $this->toAggregate($entity);
    }

    private function toEntity(Payment $payment): PaymentEntity
    {
        $entity = new PaymentEntity();
        $entity->setUuid($payment->uuid->toString());
        $entity->setRealmId($payment->realmId->toString());
        $entity->setOrganizationId($payment->organizationId->toString());
        $entity->setSubscriptionId($payment->subscriptionId->toString());
        $entity->setStatus($payment->getStatus()?->value);
        $entity->setGateway($payment->gateway?->value);
        $entity->setSubtotal($payment->subtotal?->amount->toString());
        $entity->setSubtotalCurrency($payment->subtotal?->currency->value);
        $entity->setDiscount($payment->discount?->amount->toString());
        $entity->setDiscountCurrency($payment->discount?->currency->value);
        $entity->setTaxes($payment->taxes?->amount->toString());
        $entity->setTaxesCurrency($payment->taxes?->currency->value);
        $entity->setTotal($payment->total?->amount->toString());
        $entity->setTotalCurrency($payment->total?->currency->value);
        $entity->setCaptured($payment->captured?->amount->toString());
        $entity->setCapturedCurrency($payment->captured?->currency->value);
        $entity->setCreationDate($payment->creationDate);
        $entity->setExpirationDate($payment->expirationDate);
        $entity->setCompletionDate($payment->completionDate);
        $entity->setVersion($payment->version);
        
        return $entity;
    }

    private function toAggregate(PaymentEntity $entity): Payment
    {
        return new Payment(
            uuid: PaymentId::fromString($entity->getUuid()),
            realmId: RealmId::fromString($entity->getRealmId()),
            organizationId: OrganizationId::fromString($entity->getOrganizationId()),
            subscriptionId: SubscriptionId::fromString($entity->getSubscriptionId()),
            creationDate: $entity->getCreationDate(),
            expirationDate: $entity->getExpirationDate(),
            completionDate: $entity->getCompletionDate(),
            status: $entity->getStatus() ? Statuses::from($entity->getStatus()) : null,
            gateway: $entity->getGateway() ? Gateways::from($entity->getGateway()) : null,
            subtotal: $entity->getSubtotal() ? new Price(
                BigDecimal::of($entity->getSubtotal()),
                Currencies::from($entity->getSubtotalCurrency())
            ) : null,
            discount: $entity->getDiscount() ? new Price(
                BigDecimal::of($entity->getDiscount()),
                Currencies::from($entity->getDiscountCurrency())
            ) : null,
            taxes: $entity->getTaxes() ? new Price(
                BigDecimal::of($entity->getTaxes()),
                Currencies::from($entity->getTaxesCurrency())
            ) : null,
            total: $entity->getTotal() ? new Price(
                BigDecimal::of($entity->getTotal()),
                Currencies::from($entity->getTotalCurrency())
            ) : null,
            captured: $entity->getCaptured() ? new Price(
                BigDecimal::of($entity->getCaptured()),
                Currencies::from($entity->getCapturedCurrency())
            ) : null,
            version: $entity->getVersion()
        );
    }
}
```

## 📖 Query Side (Lecture)

### Modèle de Requête Payment

```php
// ✅ Modèle Query - Payment (Projet Gyroscops Cloud)
final readonly class Payment
{
    public function __construct(
        public PaymentId $uuid,
        public RealmId $realmId,
        public OrganizationId $organizationId,
        public SubscriptionId $subscriptionId,
        public ?\DateTimeInterface $creationDate = null,
        public ?\DateTimeInterface $expirationDate = null,
        public ?\DateTimeInterface $completionDate = null,
        public ?Statuses $status = null,
        public ?Gateways $gateway = null,
        public ?Price $subtotal = null,
        public ?Price $discount = null,
        public ?Price $taxes = null,
        public ?Price $total = null,
        public ?Price $captured = null,
        public int $version = 0,
        // Propriétés optimisées pour la lecture
        public ?string $customerName = null,
        public ?string $customerEmail = null,
        public ?string $organizationName = null,
        public ?string $subscriptionName = null,
        public ?string $realmName = null,
        public ?string $gatewayName = null,
        public ?string $statusLabel = null,
        public ?string $formattedAmount = null,
        public ?string $formattedDate = null,
    ) {}
}
```

### Vue Optimisée pour la Lecture

```php
// ✅ Vue Optimisée - PaymentView (Projet Gyroscops Cloud)
final readonly class PaymentView
{
    public function __construct(
        public string $uuid,
        public string $realmId,
        public string $organizationId,
        public string $subscriptionId,
        public ?string $status = null,
        public ?string $gateway = null,
        public ?string $subtotal = null,
        public ?string $subtotalCurrency = null,
        public ?string $discount = null,
        public ?string $discountCurrency = null,
        public ?string $taxes = null,
        public ?string $taxesCurrency = null,
        public ?string $total = null,
        public ?string $totalCurrency = null,
        public ?string $captured = null,
        public ?string $capturedCurrency = null,
        public ?\DateTimeImmutable $creationDate = null,
        public ?\DateTimeImmutable $expirationDate = null,
        public ?\DateTimeImmutable $completionDate = null,
        public int $version = 0,
        // Propriétés optimisées pour la lecture
        public ?string $customerName = null,
        public ?string $customerEmail = null,
        public ?string $organizationName = null,
        public ?string $subscriptionName = null,
        public ?string $realmName = null,
        public ?string $gatewayName = null,
        public ?string $statusLabel = null,
        public ?string $formattedAmount = null,
        public ?string $formattedDate = null,
    ) {}
}
```

### Repository Query

```php
// ✅ Repository Query - DatabasePaymentRepository (Projet Gyroscops Cloud)
final class DatabasePaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(
        private Connection $connection,
        private PaymentHydrator $hydrator,
        private LoggerInterface $logger
    ) {}

    public function find(PaymentId $id): Payment
    {
        $sql = 'SELECT 
                    p.uuid,
                    p.realm_id,
                    p.organization_id,
                    p.subscription_id,
                    p.status,
                    p.gateway,
                    p.subtotal,
                    p.subtotal_currency,
                    p.discount,
                    p.discount_currency,
                    p.taxes,
                    p.taxes_currency,
                    p.total,
                    p.total_currency,
                    p.captured,
                    p.captured_currency,
                    p.creation_date,
                    p.expiration_date,
                    p.completion_date,
                    p.version,
                    o.name as organization_name,
                    s.name as subscription_name,
                    r.name as realm_name,
                    g.name as gateway_name,
                    st.label as status_label,
                    CONCAT(p.total, " ", p.total_currency) as formatted_amount,
                    DATE_FORMAT(p.creation_date, "%d/%m/%Y %H:%i") as formatted_date
                FROM accounting_payments p
                LEFT JOIN authentication_organizations o ON p.organization_id = o.uuid
                LEFT JOIN accounting_subscriptions s ON p.subscription_id = s.uuid
                LEFT JOIN authentication_realms r ON p.realm_id = r.uuid
                LEFT JOIN accounting_gateways g ON p.gateway = g.code
                LEFT JOIN accounting_statuses st ON p.status = st.code
                WHERE p.uuid = :uuid';
        
        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery(['uuid' => $id->toString()]);
        $data = $result->fetchAssociative();
        
        if (!$data) {
            throw new NotFoundException(sprintf('Payment with id %s not found', $id->toString()));
        }
        
        return $this->hydrator->hydrate($data);
    }

    public function findByOrganization(OrganizationId $organizationId, int $page = 1, int $pageSize = 25): PaymentPage
    {
        $offset = ($page - 1) * $pageSize;
        
        $sql = 'SELECT 
                    p.uuid,
                    p.realm_id,
                    p.organization_id,
                    p.subscription_id,
                    p.status,
                    p.gateway,
                    p.subtotal,
                    p.subtotal_currency,
                    p.discount,
                    p.discount_currency,
                    p.taxes,
                    p.taxes_currency,
                    p.total,
                    p.total_currency,
                    p.captured,
                    p.captured_currency,
                    p.creation_date,
                    p.expiration_date,
                    p.completion_date,
                    p.version,
                    o.name as organization_name,
                    s.name as subscription_name,
                    r.name as realm_name,
                    g.name as gateway_name,
                    st.label as status_label,
                    CONCAT(p.total, " ", p.total_currency) as formatted_amount,
                    DATE_FORMAT(p.creation_date, "%d/%m/%Y %H:%i") as formatted_date
                FROM accounting_payments p
                LEFT JOIN authentication_organizations o ON p.organization_id = o.uuid
                LEFT JOIN accounting_subscriptions s ON p.subscription_id = s.uuid
                LEFT JOIN authentication_realms r ON p.realm_id = r.uuid
                LEFT JOIN accounting_gateways g ON p.gateway = g.code
                LEFT JOIN accounting_statuses st ON p.status = st.code
                WHERE p.organization_id = :organization_id 
                ORDER BY p.creation_date DESC 
                LIMIT :limit OFFSET :offset';
        
        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery([
            'organization_id' => $organizationId->toString(),
            'limit' => $pageSize,
            'offset' => $offset
        ]);
        
        $payments = [];
        while ($data = $result->fetchAssociative()) {
            $payments[] = $this->hydrator->hydrate($data);
        }
        
        $totalCount = $this->getTotalCountByOrganization($organizationId);
        
        return new PaymentPage($page, $pageSize, $totalCount, ...$payments);
    }

    public function findByStatus(Statuses $status, int $page = 1, int $pageSize = 25): PaymentPage
    {
        $offset = ($page - 1) * $pageSize;
        
        $sql = 'SELECT 
                    p.uuid,
                    p.realm_id,
                    p.organization_id,
                    p.subscription_id,
                    p.status,
                    p.gateway,
                    p.subtotal,
                    p.subtotal_currency,
                    p.discount,
                    p.discount_currency,
                    p.taxes,
                    p.taxes_currency,
                    p.total,
                    p.total_currency,
                    p.captured,
                    p.captured_currency,
                    p.creation_date,
                    p.expiration_date,
                    p.completion_date,
                    p.version,
                    o.name as organization_name,
                    s.name as subscription_name,
                    r.name as realm_name,
                    g.name as gateway_name,
                    st.label as status_label,
                    CONCAT(p.total, " ", p.total_currency) as formatted_amount,
                    DATE_FORMAT(p.creation_date, "%d/%m/%Y %H:%i") as formatted_date
                FROM accounting_payments p
                LEFT JOIN authentication_organizations o ON p.organization_id = o.uuid
                LEFT JOIN accounting_subscriptions s ON p.subscription_id = s.uuid
                LEFT JOIN authentication_realms r ON p.realm_id = r.uuid
                LEFT JOIN accounting_gateways g ON p.gateway = g.code
                LEFT JOIN accounting_statuses st ON p.status = st.code
                WHERE p.status = :status 
                ORDER BY p.creation_date DESC 
                LIMIT :limit OFFSET :offset';
        
        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery([
            'status' => $status->value,
            'limit' => $pageSize,
            'offset' => $offset
        ]);
        
        $payments = [];
        while ($data = $result->fetchAssociative()) {
            $payments[] = $this->hydrator->hydrate($data);
        }
        
        $totalCount = $this->getTotalCountByStatus($status);
        
        return new PaymentPage($page, $pageSize, $totalCount, ...$payments);
    }

    public function findByDateRange(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        int $page = 1,
        int $pageSize = 25
    ): PaymentPage {
        $offset = ($page - 1) * $pageSize;
        
        $sql = 'SELECT 
                    p.uuid,
                    p.realm_id,
                    p.organization_id,
                    p.subscription_id,
                    p.status,
                    p.gateway,
                    p.subtotal,
                    p.subtotal_currency,
                    p.discount,
                    p.discount_currency,
                    p.taxes,
                    p.taxes_currency,
                    p.total,
                    p.total_currency,
                    p.captured,
                    p.captured_currency,
                    p.creation_date,
                    p.expiration_date,
                    p.completion_date,
                    p.version,
                    o.name as organization_name,
                    s.name as subscription_name,
                    r.name as realm_name,
                    g.name as gateway_name,
                    st.label as status_label,
                    CONCAT(p.total, " ", p.total_currency) as formatted_amount,
                    DATE_FORMAT(p.creation_date, "%d/%m/%Y %H:%i") as formatted_date
                FROM accounting_payments p
                LEFT JOIN authentication_organizations o ON p.organization_id = o.uuid
                LEFT JOIN accounting_subscriptions s ON p.subscription_id = s.uuid
                LEFT JOIN authentication_realms r ON p.realm_id = r.uuid
                LEFT JOIN accounting_gateways g ON p.gateway = g.code
                LEFT JOIN accounting_statuses st ON p.status = st.code
                WHERE p.creation_date BETWEEN :start_date AND :end_date
                ORDER BY p.creation_date DESC 
                LIMIT :limit OFFSET :offset';
        
        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery([
            'start_date' => $startDate->format('Y-m-d H:i:s'),
            'end_date' => $endDate->format('Y-m-d H:i:s'),
            'limit' => $pageSize,
            'offset' => $offset
        ]);
        
        $payments = [];
        while ($data = $result->fetchAssociative()) {
            $payments[] = $this->hydrator->hydrate($data);
        }
        
        $totalCount = $this->getTotalCountByDateRange($startDate, $endDate);
        
        return new PaymentPage($page, $pageSize, $totalCount, ...$payments);
    }

    private function getTotalCountByOrganization(OrganizationId $organizationId): int
    {
        $sql = 'SELECT COUNT(*) FROM accounting_payments WHERE organization_id = :organization_id';
        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery(['organization_id' => $organizationId->toString()]);
        
        return (int) $result->fetchOne();
    }

    private function getTotalCountByStatus(Statuses $status): int
    {
        $sql = 'SELECT COUNT(*) FROM accounting_payments WHERE status = :status';
        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery(['status' => $status->value]);
        
        return (int) $result->fetchOne();
    }

    private function getTotalCountByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): int
    {
        $sql = 'SELECT COUNT(*) FROM accounting_payments WHERE creation_date BETWEEN :start_date AND :end_date';
        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery([
            'start_date' => $startDate->format('Y-m-d H:i:s'),
            'end_date' => $endDate->format('Y-m-d H:i:s')
        ]);
        
        return (int) $result->fetchOne();
    }
}
```

## 🧪 Tests des Repositories CQRS

### Test du Repository Command

```php
// ✅ Test Repository Command - DatabasePaymentRepositoryTest (Projet Gyroscops Cloud)
final class DatabasePaymentRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private DatabasePaymentRepository $repository;
    private TestEventBus $eventBus;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->eventBus = new TestEventBus();
        $this->repository = new DatabasePaymentRepository(
            $this->entityManager,
            $this->eventBus,
            $this->createMock(LoggerInterface::class)
        );
    }

    /** @test */
    public function itShouldSavePaymentSuccessfully(): void
    {
        // Arrange
        $payment = PaymentFixtures::createValidPayment();
        
        $this->entityManager->expects($this->once())
            ->method('beginTransaction');
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(PaymentEntity::class));
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $this->entityManager->expects($this->once())
            ->method('commit');
        
        // Act
        $this->repository->save($payment);
        
        // Assert
        $this->assertCount(1, $this->eventBus->getPublishedEvents());
    }

    /** @test */
    public function itShouldRollbackOnError(): void
    {
        // Arrange
        $payment = PaymentFixtures::createValidPayment();
        
        $this->entityManager->expects($this->once())
            ->method('beginTransaction');
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->willThrowException(new \Exception('Database error'));
        
        $this->entityManager->expects($this->once())
            ->method('rollback');
        
        // Act & Assert
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Failed to save payment');
        
        $this->repository->save($payment);
    }
}
```

### Test du Repository Query

```php
// ✅ Test Repository Query - DatabasePaymentRepositoryTest (Projet Gyroscops Cloud)
final class DatabasePaymentRepositoryTest extends TestCase
{
    private Connection $connection;
    private DatabasePaymentRepository $repository;
    private PaymentHydrator $hydrator;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->hydrator = new PaymentHydrator();
        $this->repository = new DatabasePaymentRepository(
            $this->connection,
            $this->hydrator,
            $this->createMock(LoggerInterface::class)
        );
    }

    /** @test */
    public function itShouldFindPaymentById(): void
    {
        // Arrange
        $paymentId = PaymentId::generate();
        $paymentData = PaymentFixtures::createValidPaymentData();
        
        $stmt = $this->createMock(Result::class);
        $stmt->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn($paymentData);
        
        $this->connection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT'))
            ->willReturn($stmt);
        
        // Act
        $payment = $this->repository->find($paymentId);
        
        // Assert
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals($paymentId->toString(), $payment->uuid->toString());
    }

    /** @test */
    public function itShouldFindPaymentsByOrganization(): void
    {
        // Arrange
        $organizationId = OrganizationId::generate();
        $paymentData = PaymentFixtures::createValidPaymentData();
        
        $stmt = $this->createMock(Result::class);
        $stmt->expects($this->exactly(2))
            ->method('fetchAssociative')
            ->willReturnOnConsecutiveCalls($paymentData, false);
        
        $this->connection->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($stmt);
        
        // Act
        $paymentPage = $this->repository->findByOrganization($organizationId, 1, 25);
        
        // Assert
        $this->assertInstanceOf(PaymentPage::class, $paymentPage);
        $this->assertCount(1, $paymentPage);
        $this->assertEquals(1, $paymentPage->page);
        $this->assertEquals(25, $paymentPage->pageSize);
    }

    /** @test */
    public function itShouldFindPaymentsByDateRange(): void
    {
        // Arrange
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-12-31');
        $paymentData = PaymentFixtures::createValidPaymentData();
        
        $stmt = $this->createMock(Result::class);
        $stmt->expects($this->exactly(2))
            ->method('fetchAssociative')
            ->willReturnOnConsecutiveCalls($paymentData, false);
        
        $this->connection->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($stmt);
        
        // Act
        $paymentPage = $this->repository->findByDateRange($startDate, $endDate, 1, 25);
        
        // Assert
        $this->assertInstanceOf(PaymentPage::class, $paymentPage);
        $this->assertCount(1, $paymentPage);
        $this->assertEquals(1, $paymentPage->page);
        $this->assertEquals(25, $paymentPage->pageSize);
    }
}
```

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux explorer Event Sourcing" 
    subtitle="Vous voulez stocker les événements comme source de vérité" 
    criteria="Audit trail critique,Debugging complexe nécessaire,Équipe expérimentée,Évolution fréquente des vues métier" 
    time="35-45 minutes" 
    chapter="18" 
    chapter-title="Stockage SQL - Event Sourcing seul" 
    chapter-url="/chapitres/chapitre-18/" 
  >}}
  
  {{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux explorer Event Sourcing + CQRS" 
    subtitle="Vous voulez combiner Event Sourcing avec CQRS" 
    criteria="Audit trail critique,Performance critique sur les lectures,Modèles de lecture/écriture très différents,Équipe très expérimentée" 
    time="40-50 minutes" 
    chapter="19" 
    chapter-title="Stockage SQL - Event Sourcing + CQRS" 
    chapter-url="/chapitres/chapitre-19/" 
  >}}
  
  {{< chapter-option 
    letter="C" 
    color="red" 
    title="Je veux continuer avec CQRS" 
    subtitle="CQRS me convient parfaitement" 
    criteria="Lectures/écritures très différentes,Performance critique,Équipe expérimentée,Modèles distincts nécessaires" 
    time="20-30 minutes" 
    chapter="58" 
    chapter-title="Gestion des Données et Validation" 
    chapter-url="/chapitres/chapitre-20/" 
  >}}
  
  {{< chapter-option 
    letter="D" 
    color="blue" 
    title="Je veux explorer d'autres types de stockage" 
    subtitle="Je veux voir d'autres approches de stockage" 
    criteria="Besoin de stockage distribué,Intégrations avec des APIs externes,Recherche full-text,Données semi-structurées" 
    time="25-35 minutes" 
    chapter="59" 
    chapter-title="Stockage API - Approche Classique" 
    chapter-url="/chapitres/chapitre-21/" 
  >}}
  
{{< /chapter-nav >}}