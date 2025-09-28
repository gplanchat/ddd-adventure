<?php

declare(strict_types=1);

namespace App\Examples\StockageSqlClassique;

use App\Accounting\Domain\Payment\Command\Payment;
use App\Accounting\Domain\Payment\PaymentId;
use App\Accounting\Domain\Payment\Statuses;
use App\Accounting\Domain\Price;
use App\Accounting\Domain\Currencies;
use App\Authentication\Domain\Organization\OrganizationId;
use App\Authentication\Domain\Realm\RealmId;
use App\Accounting\Domain\Subscription\SubscriptionId;
use Brick\Math\BigDecimal;
use Doctrine\ORM\EntityManagerInterface;
use App\Platform\Infrastructure\EventBus\EventBusInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Exemple de Test pour Repository SQL Classique
 * 
 * Ce fichier montre comment tester un repository SQL classique
 * en suivant les standards du projet Gyroscops Cloud.
 * 
 * Références ADR :
 * - HIVE023 : Repository Testing Strategies
 * - HIVE027 : PHPUnit Testing Standards
 */
final class PaymentRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private PaymentRepository $repository;
    private TestEventBus $eventBus;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->eventBus = new TestEventBus();
        $this->repository = new PaymentRepository(
            $this->entityManager,
            $this->eventBus,
            $this->createMock(LoggerInterface::class)
        );
    }

    /** @test */
    public function itShouldSavePaymentSuccessfully(): void
    {
        // Arrange
        $payment = $this->createValidPayment();
        
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
        $payment = $this->createValidPayment();
        
        $this->entityManager->expects($this->once())
            ->method('beginTransaction');
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->willThrowException(new \Exception('Database error'));
        
        $this->entityManager->expects($this->once())
            ->method('rollback');
        
        // Act & Assert
        $this->expectException(\StorageException::class);
        $this->expectExceptionMessage('Failed to save payment');
        
        $this->repository->save($payment);
    }

    /** @test */
    public function itShouldFindPaymentById(): void
    {
        // Arrange
        $paymentId = PaymentId::generate();
        $entity = $this->createValidPaymentEntity();
        
        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(PaymentEntity::class, $paymentId->toString())
            ->willReturn($entity);
        
        // Act
        $payment = $this->repository->find($paymentId);
        
        // Assert
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals($paymentId->toString(), $payment->uuid->toString());
    }

    /** @test */
    public function itShouldThrowNotFoundExceptionWhenPaymentNotFound(): void
    {
        // Arrange
        $paymentId = PaymentId::generate();
        
        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(PaymentEntity::class, $paymentId->toString())
            ->willReturn(null);
        
        // Act & Assert
        $this->expectException(\NotFoundException::class);
        $this->expectExceptionMessage(sprintf('Payment with id %s not found', $paymentId->toString()));
        
        $this->repository->find($paymentId);
    }

    private function createValidPayment(): Payment
    {
        return Payment::registerManualPayment(
            PaymentId::generate(),
            RealmId::generate(),
            OrganizationId::generate(),
            SubscriptionId::generate(),
            new \DateTimeImmutable(),
            new \DateTimeImmutable('+30 days'),
            null,
            'John Doe',
            'john.doe@example.com',
            Statuses::Pending,
            new Price(BigDecimal::of('100.00'), Currencies::EUR),
            new Price(BigDecimal::of('0.00'), Currencies::EUR),
            new Price(BigDecimal::of('20.00'), Currencies::EUR),
            new Price(BigDecimal::of('120.00'), Currencies::EUR)
        );
    }

    private function createValidPaymentEntity(): PaymentEntity
    {
        $entity = new PaymentEntity();
        $entity->setUuid('0197b105-0c38-75e3-8cd0-32c57bd7f35b');
        $entity->setRealmId('0197b105-0c38-75e3-8cd0-32c57bd7f35c');
        $entity->setOrganizationId('0197b105-0c38-75e3-8cd0-32c57bd7f35d');
        $entity->setSubscriptionId('0197b105-0c38-75e3-8cd0-32c57bd7f35e');
        $entity->setStatus('pending');
        $entity->setGateway('manual');
        $entity->setSubtotal('100.00');
        $entity->setSubtotalCurrency('EUR');
        $entity->setDiscount('0.00');
        $entity->setDiscountCurrency('EUR');
        $entity->setTaxes('20.00');
        $entity->setTaxesCurrency('EUR');
        $entity->setTotal('120.00');
        $entity->setTotalCurrency('EUR');
        $entity->setCaptured('0.00');
        $entity->setCapturedCurrency('EUR');
        $entity->setCreationDate(new \DateTimeImmutable());
        $entity->setExpirationDate(new \DateTimeImmutable('+30 days'));
        $entity->setCompletionDate(null);
        $entity->setVersion(1);
        
        return $entity;
    }
}

/**
 * Test Event Bus pour les tests
 */
class TestEventBus implements EventBusInterface
{
    private array $events = [];

    public function publish(object $event): void
    {
        $this->events[] = $event;
    }

    public function getPublishedEvents(): array
    {
        return $this->events;
    }

    public function clear(): void
    {
        $this->events = [];
    }
}
