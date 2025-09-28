<?php

declare(strict_types=1);

namespace App\Examples\StockageSqlCqrs;

use App\Accounting\Domain\Payment\Query\Payment;
use App\Accounting\Domain\Payment\PaymentId;
use App\Accounting\Domain\Payment\Statuses;
use App\Accounting\Domain\Price;
use App\Accounting\Domain\Currencies;
use App\Authentication\Domain\Organization\OrganizationId;
use App\Authentication\Domain\Realm\RealmId;
use App\Accounting\Domain\Subscription\SubscriptionId;
use Brick\Math\BigDecimal;
use Doctrine\DBAL\Connection;
use App\Platform\Infrastructure\Hydrator\PaymentHydrator;
use Psr\Log\LoggerInterface;

/**
 * Exemple de Repository Query SQL CQRS pour Payment
 * 
 * Ce fichier montre comment implémenter un repository Query
 * en suivant les patterns CQRS du projet Gyroscops Cloud.
 * 
 * Références ADR :
 * - HIVE006 : Query Models for API Platform
 * - HIVE007 : Command Models for API Platform
 * - HIVE012 : Database Repositories
 * - HIVE033 : Hydrator Implementation Patterns
 * - HIVE035 : Database Operation Logging
 */
final class PaymentQueryRepository
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
            throw new \NotFoundException(sprintf('Payment with id %s not found', $id->toString()));
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
