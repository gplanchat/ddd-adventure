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

/**
 * Exemple de Repository SQL Classique pour Payment
 * 
 * Ce fichier montre comment implémenter un repository SQL classique
 * en suivant les patterns du projet Gyroscops Cloud.
 * 
 * Références ADR :
 * - HIVE012 : Database Repositories
 * - HIVE033 : Hydrator Implementation Patterns
 * - HIVE035 : Database Operation Logging
 */
final class PaymentRepository
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
            throw new \StorageException('Failed to save payment', 0, $e);
        }
    }

    public function find(PaymentId $id): Payment
    {
        $entity = $this->entityManager->find(PaymentEntity::class, $id->toString());
        
        if (!$entity) {
            throw new \NotFoundException(sprintf('Payment with id %s not found', $id->toString()));
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
