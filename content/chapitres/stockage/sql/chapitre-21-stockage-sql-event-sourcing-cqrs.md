---
title: "Chapitre 59 : Stockage SQL - Event Sourcing + CQRS"
description: "Maîtriser le stockage SQL avec Event Sourcing et CQRS pour des performances et une flexibilité maximales"
date: 2024-12-19
draft: true
type: "docs"
weight: 59
---

## 🎯 Objectif de ce Chapitre

### Mon Problème : Comment Maximiser les Performances et la Flexibilité ?

**Voici ce qui s'est passé avec Gyroscops** : J'avais implémenté l'Event Sourcing + CQS, mais j'avais besoin de modèles de lecture complètement différents des modèles d'écriture. Les vues métier évoluaient constamment et j'avais besoin de flexibilité maximale.

**Mais attendez...** Comment séparer complètement les modèles ? Comment optimiser chaque côté indépendamment ? Comment gérer la cohérence ? Comment intégrer avec API Platform ?

**Soudain, je réalisais que CQRS + Event Sourcing était parfait !** Il me fallait une méthode pour maximiser les performances et la flexibilité.

### Stockage SQL Event Sourcing + CQRS : Mon Guide Pratique

Le stockage SQL Event Sourcing + CQRS m'a permis de :
- **Maximiser** les performances de lecture
- **Optimiser** les modèles par usage
- **Évoluer** indépendamment chaque côté
- **Flexibiliser** au maximum l'architecture

## Qu'est-ce que le Stockage SQL Event Sourcing + CQRS ?

### Le Concept Fondamental

Le stockage SQL Event Sourcing + CQRS combine l'Event Sourcing pour l'écriture avec la séparation complète des modèles Command et Query. **L'idée** : Modèles d'écriture via Event Sourcing, modèles de lecture complètement séparés et optimisés.

**Avec Gyroscops, voici comment j'ai structuré le stockage SQL Event Sourcing + CQRS** :

### Les 4 Piliers du Stockage SQL Event Sourcing + CQRS

#### 1. **Command Side** - Modèles d'écriture Event Sourcing

**Voici comment j'ai implémenté le Command Side avec Gyroscops** :

**Composants** :
- Agrégats Event Sourcing
- Command Models
- Command Handlers
- Event Store
- Event Bus

**Caractéristiques** :
- Modèles optimisés pour l'écriture
- Logique métier complexe
- Validation des règles
- Gestion des transactions

#### 2. **Query Side** - Modèles de lecture optimisés

**Voici comment j'ai implémenté le Query Side avec Gyroscops** :

**Composants** :
- Query Models spécialisés
- Query Handlers
- Projections optimisées
- Cache intelligent
- Requêtes spécialisées

**Caractéristiques** :
- Modèles optimisés pour la lecture
- Vues métier spécialisées
- Performance maximale
- Flexibilité totale

#### 3. **Event Store** - Source de vérité

**Voici comment j'ai implémenté l'Event Store avec Gyroscops** :

**Fonctionnalités** :
- Stockage des événements
- Reconstruction des agrégats
- Gestion des versions
- Optimistic locking

**Avantages** :
- Audit trail complet
- Intégrité des données
- Évolutivité des vues
- Debugging facilité

#### 4. **Projections** - Synchronisation des vues

**Voici comment j'ai implémenté les projections avec Gyroscops** :

**Types de Projections** :
- Projections de lecture (pour l'API)
- Projections d'audit (pour le debugging)
- Projections d'analytics (pour les rapports)
- Projections spécialisées (par contexte métier)

**Synchronisation** :
- Asynchrone via Event Bus
- Cohérence éventuelle
- Gestion des erreurs
- Reprocessing possible

## Comment Implémenter le Stockage SQL Event Sourcing + CQRS

### 1. **Créer les Command Models**

**Avec Gyroscops** : J'ai créé les Command Models :

```php
// ✅ Command Model Payment Hive (Projet Hive)
final class PaymentCommandModel
{
    public function __construct(
        private string $id,
        private string $organizationId,
        private string $customerName,
        private string $customerEmail,
        private string $amount,
        private string $currency,
        private string $status,
        private \DateTimeImmutable $createdAt,
        private string $createdBy,
        private int $version = 0
    ) {}
    
    public function initiate(): void
    {
        if ($this->status !== 'initiated') {
            throw new InvalidOperationException('Payment can only be initiated once');
        }
        
        // Logique métier pour l'initiation
        $this->validatePaymentData();
        $this->checkOrganizationLimits();
        $this->preparePaymentProcessing();
    }
    
    public function process(string $processedBy): void
    {
        if ($this->status !== 'initiated') {
            throw new InvalidOperationException('Payment can only be processed from initiated status');
        }
        
        // Logique métier pour le traitement
        $this->validateProcessingRights($processedBy);
        $this->executePaymentProcessing();
        $this->updatePaymentStatus('processed');
    }
    
    public function fail(string $reason, string $failedBy): void
    {
        if ($this->status === 'failed') {
            throw new InvalidOperationException('Payment is already failed');
        }
        
        // Logique métier pour l'échec
        $this->validateFailureReason($reason);
        $this->logFailureDetails($reason, $failedBy);
        $this->updatePaymentStatus('failed');
    }
    
    public function refund(string $reason, string $refundedBy): void
    {
        if ($this->status !== 'processed') {
            throw new InvalidOperationException('Payment can only be refunded from processed status');
        }
        
        // Logique métier pour le remboursement
        $this->validateRefundEligibility();
        $this->executeRefundProcessing($reason, $refundedBy);
        $this->updatePaymentStatus('refunded');
    }
    
    private function validatePaymentData(): void
    {
        if (empty($this->customerName) || empty($this->customerEmail)) {
            throw new ValidationException('Customer data is required');
        }
        
        if ($this->amount <= 0) {
            throw new ValidationException('Amount must be positive');
        }
        
        if (!in_array($this->currency, ['EUR', 'USD', 'GBP'])) {
            throw new ValidationException('Unsupported currency');
        }
    }
    
    private function checkOrganizationLimits(): void
    {
        // Vérifier les limites de l'organisation
        // Logique métier complexe
    }
    
    private function preparePaymentProcessing(): void
    {
        // Préparer le traitement du paiement
        // Logique métier complexe
    }
    
    // Autres méthodes privées...
    
    // Getters...
    public function getId(): string { return $this->id; }
    public function getOrganizationId(): string { return $this->organizationId; }
    public function getCustomerName(): string { return $this->customerName; }
    public function getCustomerEmail(): string { return $this->customerEmail; }
    public function getAmount(): string { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getCreatedBy(): string { return $this->createdBy; }
    public function getVersion(): int { return $this->version; }
}
```

**Résultat** : Command Model optimisé pour l'écriture.

### 2. **Créer les Query Models**

**Avec Gyroscops** : J'ai créé les Query Models :

```php
// ✅ Query Model Payment List Hive (Projet Hive)
final class PaymentListQueryModel
{
    public function __construct(
        public readonly string $id,
        public readonly string $customerName,
        public readonly string $amount,
        public readonly string $currency,
        public readonly string $status,
        public readonly \DateTimeImmutable $createdAt,
        public readonly string $statusLabel,
        public readonly string $amountFormatted,
        public readonly string $statusColor,
        public readonly bool $canRefund,
        public readonly bool $canRetry
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            customerName: $data['customer_name'],
            amount: $data['amount'],
            currency: $data['currency'],
            status: $data['status'],
            createdAt: new \DateTimeImmutable($data['created_at']),
            statusLabel: self::getStatusLabel($data['status']),
            amountFormatted: self::formatAmount($data['amount'], $data['currency']),
            statusColor: self::getStatusColor($data['status']),
            canRefund: self::canRefund($data['status']),
            canRetry: self::canRetry($data['status'])
        );
    }
    
    private static function getStatusLabel(string $status): string
    {
        return match($status) {
            'initiated' => 'En cours',
            'processed' => 'Traité',
            'failed' => 'Échoué',
            'refunded' => 'Remboursé',
            default => 'Inconnu'
        };
    }
    
    private static function formatAmount(string $amount, string $currency): string
    {
        return number_format($amount, 2) . ' ' . $currency;
    }
    
    private static function getStatusColor(string $status): string
    {
        return match($status) {
            'initiated' => 'blue',
            'processed' => 'green',
            'failed' => 'red',
            'refunded' => 'orange',
            default => 'gray'
        };
    }
    
    private static function canRefund(string $status): bool
    {
        return $status === 'processed';
    }
    
    private static function canRetry(string $status): bool
    {
        return $status === 'failed';
    }
}

// ✅ Query Model Payment Details Hive (Projet Hive)
final class PaymentDetailsQueryModel
{
    public function __construct(
        public readonly string $id,
        public readonly string $organizationId,
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly string $amount,
        public readonly string $currency,
        public readonly string $status,
        public readonly \DateTimeImmutable $createdAt,
        public readonly string $createdBy,
        public readonly ?\DateTimeImmutable $updatedAt = null,
        public readonly ?string $updatedBy = null,
        public readonly ?string $failureReason = null,
        public readonly ?string $refundReason = null,
        public readonly array $auditTrail = [],
        public readonly array $metadata = []
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            organizationId: $data['organization_id'],
            customerName: $data['customer_name'],
            customerEmail: $data['customer_email'],
            amount: $data['amount'],
            currency: $data['currency'],
            status: $data['status'],
            createdAt: new \DateTimeImmutable($data['created_at']),
            createdBy: $data['created_by'],
            updatedAt: $data['updated_at'] ? new \DateTimeImmutable($data['updated_at']) : null,
            updatedBy: $data['updated_by'] ?? null,
            failureReason: $data['failure_reason'] ?? null,
            refundReason: $data['refund_reason'] ?? null,
            auditTrail: json_decode($data['audit_trail'] ?? '[]', true),
            metadata: json_decode($data['metadata'] ?? '{}', true)
        );
    }
}

// ✅ Query Model Payment Analytics Hive (Projet Hive)
final class PaymentAnalyticsQueryModel
{
    public function __construct(
        public readonly int $totalPayments,
        public readonly int $successfulPayments,
        public readonly int $failedPayments,
        public readonly int $refundedPayments,
        public readonly string $totalAmount,
        public readonly string $averageAmount,
        public readonly string $successRate,
        public readonly array $dailyStats = [],
        public readonly array $currencyStats = [],
        public readonly array $statusDistribution = []
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            totalPayments: $data['total_payments'],
            successfulPayments: $data['successful_payments'],
            failedPayments: $data['failed_payments'],
            refundedPayments: $data['refunded_payments'],
            totalAmount: $data['total_amount'],
            averageAmount: $data['average_amount'],
            successRate: $data['success_rate'],
            dailyStats: json_decode($data['daily_stats'] ?? '[]', true),
            currencyStats: json_decode($data['currency_stats'] ?? '[]', true),
            statusDistribution: json_decode($data['status_distribution'] ?? '[]', true)
        );
    }
}
```

**Résultat** : Query Models spécialisés pour chaque usage.

### 3. **Créer les Command Handlers**

**Avec Gyroscops** : J'ai créé les Command Handlers :

```php
// ✅ Command Handler Payment Hive (Projet Hive)
final class PaymentCommandHandler
{
    public function __construct(
        private EventStoreInterface $eventStore,
        private EventBusInterface $eventBus,
        private PaymentCommandModelFactory $commandModelFactory
    ) {}
    
    public function handleCreatePayment(CreatePaymentCommand $command): void
    {
        // Créer le Command Model
        $commandModel = $this->commandModelFactory->create(
            $command->getPaymentId(),
            $command->getOrganizationId(),
            $command->getCustomerName(),
            $command->getCustomerEmail(),
            $command->getAmount(),
            $command->getCurrency(),
            $command->getCreatedBy()
        );
        
        // Exécuter la logique métier
        $commandModel->initiate();
        
        // Créer l'événement
        $event = new PaymentInitiated(
            $command->getPaymentId(),
            $command->getOrganizationId(),
            $command->getCustomerName(),
            $command->getCustomerEmail(),
            $command->getAmount(),
            $command->getCurrency(),
            $command->getCreatedBy()
        );
        
        // Sauvegarder l'événement
        $this->eventStore->append(
            $command->getPaymentId(),
            [$event],
            0
        );
        
        // Publier l'événement
        $this->eventBus->publish($event);
    }
    
    public function handleProcessPayment(ProcessPaymentCommand $command): void
    {
        // Reconstruire le Command Model depuis les événements
        $events = $this->eventStore->getEvents($command->getPaymentId());
        $commandModel = $this->commandModelFactory->fromEvents($events);
        
        // Exécuter la logique métier
        $commandModel->process($command->getProcessedBy());
        
        // Créer l'événement
        $event = new PaymentProcessed(
            $command->getPaymentId(),
            $command->getProcessedBy()
        );
        
        // Sauvegarder l'événement
        $this->eventStore->append(
            $command->getPaymentId(),
            [$event],
            $commandModel->getVersion()
        );
        
        // Publier l'événement
        $this->eventBus->publish($event);
    }
    
    public function handleRefundPayment(RefundPaymentCommand $command): void
    {
        // Reconstruire le Command Model depuis les événements
        $events = $this->eventStore->getEvents($command->getPaymentId());
        $commandModel = $this->commandModelFactory->fromEvents($events);
        
        // Exécuter la logique métier
        $commandModel->refund($command->getReason(), $command->getRefundedBy());
        
        // Créer l'événement
        $event = new PaymentRefunded(
            $command->getPaymentId(),
            $command->getReason(),
            $command->getRefundedBy()
        );
        
        // Sauvegarder l'événement
        $this->eventStore->append(
            $command->getPaymentId(),
            [$event],
            $commandModel->getVersion()
        );
        
        // Publier l'événement
        $this->eventBus->publish($event);
    }
}
```

**Résultat** : Command Handlers pour l'écriture.

### 4. **Créer les Query Handlers**

**Avec Gyroscops** : J'ai créé les Query Handlers :

```php
// ✅ Query Handler Payment List Hive (Projet Hive)
final class PaymentListQueryHandler
{
    public function __construct(
        private Connection $connection,
        private CacheInterface $cache
    ) {}
    
    public function handleGetPaymentList(GetPaymentListQuery $query): array
    {
        $cacheKey = "payment_list_{$query->getOrganizationId()}_{$query->getPage()}_{$query->getLimit()}_{$query->getStatus()}_{$query->getSortBy()}";
        
        // Vérifier le cache
        if ($cached = $this->cache->get($cacheKey)) {
            return array_map([PaymentListQueryModel::class, 'fromArray'], $cached);
        }
        
        // Requête optimisée pour la liste
        $sql = 'SELECT id, customer_name, amount, currency, status, created_at,
                       CASE status
                           WHEN "initiated" THEN "En cours"
                           WHEN "processed" THEN "Traité"
                           WHEN "failed" THEN "Échoué"
                           WHEN "refunded" THEN "Remboursé"
                           ELSE "Inconnu"
                       END as status_label,
                       CONCAT(FORMAT(amount, 2), " ", currency) as amount_formatted,
                       CASE status
                           WHEN "initiated" THEN "blue"
                           WHEN "processed" THEN "green"
                           WHEN "failed" THEN "red"
                           WHEN "refunded" THEN "orange"
                           ELSE "gray"
                       END as status_color,
                       CASE WHEN status = "processed" THEN 1 ELSE 0 END as can_refund,
                       CASE WHEN status = "failed" THEN 1 ELSE 0 END as can_retry
                FROM payment_list_projections 
                WHERE organization_id = ?';
        
        $params = [$query->getOrganizationId()];
        
        if ($query->getStatus()) {
            $sql .= ' AND status = ?';
            $params[] = $query->getStatus();
        }
        
        $sql .= ' ORDER BY ' . $query->getSortBy() . ' ' . $query->getSortDirection();
        $sql .= ' LIMIT ? OFFSET ?';
        $params[] = $query->getLimit();
        $params[] = ($query->getPage() - 1) * $query->getLimit();
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        
        $payments = [];
        while ($data = $stmt->fetch()) {
            $payments[] = PaymentListQueryModel::fromArray($data);
        }
        
        // Mettre en cache
        $this->cache->set($cacheKey, array_map(fn($p) => $p->toArray(), $payments), 1800);
        
        return $payments;
    }
}

// ✅ Query Handler Payment Details Hive (Projet Hive)
final class PaymentDetailsQueryHandler
{
    public function __construct(
        private Connection $connection,
        private CacheInterface $cache
    ) {}
    
    public function handleGetPaymentDetails(GetPaymentDetailsQuery $query): ?PaymentDetailsQueryModel
    {
        $cacheKey = "payment_details_{$query->getPaymentId()}";
        
        // Vérifier le cache
        if ($cached = $this->cache->get($cacheKey)) {
            return PaymentDetailsQueryModel::fromArray($cached);
        }
        
        // Requête optimisée pour les détails
        $sql = 'SELECT p.*, 
                       GROUP_CONCAT(
                           JSON_OBJECT(
                               "event_type", e.event_type,
                               "occurred_at", e.created_at,
                               "user_id", JSON_EXTRACT(e.event_metadata, "$.user_id"),
                               "details", e.event_data
                           )
                           ORDER BY e.created_at
                       ) as audit_trail
                FROM payment_details_projections p
                LEFT JOIN event_store e ON e.aggregate_id = p.id
                WHERE p.id = ?
                GROUP BY p.id';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$query->getPaymentId()]);
        
        $data = $stmt->fetch();
        if (!$data) {
            return null;
        }
        
        $payment = PaymentDetailsQueryModel::fromArray($data);
        
        // Mettre en cache
        $this->cache->set($cacheKey, $payment->toArray(), 3600);
        
        return $payment;
    }
}

// ✅ Query Handler Payment Analytics Hive (Projet Hive)
final class PaymentAnalyticsQueryHandler
{
    public function __construct(
        private Connection $connection,
        private CacheInterface $cache
    ) {}
    
    public function handleGetPaymentAnalytics(GetPaymentAnalyticsQuery $query): PaymentAnalyticsQueryModel
    {
        $cacheKey = "payment_analytics_{$query->getOrganizationId()}_{$query->getStartDate()}_{$query->getEndDate()}";
        
        // Vérifier le cache
        if ($cached = $this->cache->get($cacheKey)) {
            return PaymentAnalyticsQueryModel::fromArray($cached);
        }
        
        // Requête analytique complexe
        $sql = 'SELECT 
                    COUNT(*) as total_payments,
                    SUM(CASE WHEN status = "processed" THEN 1 ELSE 0 END) as successful_payments,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_payments,
                    SUM(CASE WHEN status = "refunded" THEN 1 ELSE 0 END) as refunded_payments,
                    SUM(CASE WHEN status = "processed" THEN CAST(amount AS DECIMAL(10,2)) ELSE 0 END) as total_amount,
                    AVG(CASE WHEN status = "processed" THEN CAST(amount AS DECIMAL(10,2)) ELSE NULL END) as average_amount,
                    ROUND(
                        (SUM(CASE WHEN status = "processed" THEN 1 ELSE 0 END) * 100.0) / COUNT(*), 2
                    ) as success_rate,
                    JSON_ARRAYAGG(
                        JSON_OBJECT(
                            "date", DATE(created_at),
                            "count", daily_count,
                            "amount", daily_amount
                        )
                    ) as daily_stats,
                    JSON_ARRAYAGG(
                        JSON_OBJECT(
                            "currency", currency,
                            "count", currency_count,
                            "amount", currency_amount
                        )
                    ) as currency_stats,
                    JSON_ARRAYAGG(
                        JSON_OBJECT(
                            "status", status,
                            "count", status_count,
                            "percentage", ROUND((status_count * 100.0) / total_count, 2)
                        )
                    ) as status_distribution
                FROM (
                    SELECT p.*,
                           COUNT(*) OVER (PARTITION BY DATE(p.created_at)) as daily_count,
                           SUM(CAST(p.amount AS DECIMAL(10,2))) OVER (PARTITION BY DATE(p.created_at)) as daily_amount,
                           COUNT(*) OVER (PARTITION BY p.currency) as currency_count,
                           SUM(CAST(p.amount AS DECIMAL(10,2))) OVER (PARTITION BY p.currency) as currency_amount,
                           COUNT(*) OVER (PARTITION BY p.status) as status_count,
                           COUNT(*) OVER () as total_count
                    FROM payment_analytics_projections p
                    WHERE p.organization_id = ? 
                    AND p.created_at BETWEEN ? AND ?
                ) as analytics';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $query->getOrganizationId(),
            $query->getStartDate()->format('Y-m-d H:i:s'),
            $query->getEndDate()->format('Y-m-d H:i:s')
        ]);
        
        $data = $stmt->fetch();
        $analytics = PaymentAnalyticsQueryModel::fromArray($data);
        
        // Mettre en cache
        $this->cache->set($cacheKey, $analytics->toArray(), 3600);
        
        return $analytics;
    }
}
```

**Résultat** : Query Handlers spécialisés et optimisés.

### 5. **Créer les Projections Spécialisées**

**Avec Gyroscops** : J'ai créé les projections spécialisées :

```php
// ✅ Projection Payment List Hive (Projet Hive)
final class PaymentListProjectionHandler
{
    public function __construct(
        private Connection $connection,
        private CacheInterface $cache
    ) {}
    
    public function handle(DomainEvent $event): void
    {
        switch ($event::class) {
            case PaymentInitiated::class:
                $this->handlePaymentInitiated($event);
                break;
            case PaymentProcessed::class:
                $this->handlePaymentProcessed($event);
                break;
            case PaymentFailed::class:
                $this->handlePaymentFailed($event);
                break;
            case PaymentRefunded::class:
                $this->handlePaymentRefunded($event);
                break;
        }
        
        // Invalider le cache
        $this->invalidateCache($event);
    }
    
    private function handlePaymentInitiated(PaymentInitiated $event): void
    {
        $sql = 'INSERT INTO payment_list_projections (id, organization_id, customer_name, amount, currency, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getPaymentId(),
            $event->getOrganizationId(),
            $event->getCustomerName(),
            $event->getAmount(),
            $event->getCurrency(),
            'initiated',
            $event->getOccurredAt()->format('Y-m-d H:i:s')
        ]);
    }
    
    private function handlePaymentProcessed(PaymentProcessed $event): void
    {
        $sql = 'UPDATE payment_list_projections SET status = ?, updated_at = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'processed',
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getPaymentId()
        ]);
    }
    
    private function handlePaymentFailed(PaymentFailed $event): void
    {
        $sql = 'UPDATE payment_list_projections SET status = ?, updated_at = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'failed',
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getPaymentId()
        ]);
    }
    
    private function handlePaymentRefunded(PaymentRefunded $event): void
    {
        $sql = 'UPDATE payment_list_projections SET status = ?, updated_at = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'refunded',
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getPaymentId()
        ]);
    }
    
    private function invalidateCache(DomainEvent $event): void
    {
        // Invalider les caches liés à cet événement
        $this->cache->delete("payment_list_{$event->getOrganizationId()}_*");
    }
}

// ✅ Projection Payment Details Hive (Projet Hive)
final class PaymentDetailsProjectionHandler
{
    public function __construct(
        private Connection $connection,
        private CacheInterface $cache
    ) {}
    
    public function handle(DomainEvent $event): void
    {
        switch ($event::class) {
            case PaymentInitiated::class:
                $this->handlePaymentInitiated($event);
                break;
            case PaymentProcessed::class:
                $this->handlePaymentProcessed($event);
                break;
            case PaymentFailed::class:
                $this->handlePaymentFailed($event);
                break;
            case PaymentRefunded::class:
                $this->handlePaymentRefunded($event);
                break;
        }
        
        // Invalider le cache
        $this->invalidateCache($event);
    }
    
    private function handlePaymentInitiated(PaymentInitiated $event): void
    {
        $sql = 'INSERT INTO payment_details_projections (id, organization_id, customer_name, customer_email, amount, currency, status, created_at, created_by, metadata) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $event->getPaymentId(),
            $event->getOrganizationId(),
            $event->getCustomerName(),
            $event->getCustomerEmail(),
            $event->getAmount(),
            $event->getCurrency(),
            'initiated',
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getCreatedBy(),
            json_encode($event->getMetadata())
        ]);
    }
    
    private function handlePaymentProcessed(PaymentProcessed $event): void
    {
        $sql = 'UPDATE payment_details_projections SET status = ?, updated_at = ?, updated_by = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'processed',
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getProcessedBy(),
            $event->getPaymentId()
        ]);
    }
    
    private function handlePaymentFailed(PaymentFailed $event): void
    {
        $sql = 'UPDATE payment_details_projections SET status = ?, failure_reason = ?, updated_at = ?, updated_by = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'failed',
            $event->getReason(),
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getFailedBy(),
            $event->getPaymentId()
        ]);
    }
    
    private function handlePaymentRefunded(PaymentRefunded $event): void
    {
        $sql = 'UPDATE payment_details_projections SET status = ?, refund_reason = ?, updated_at = ?, updated_by = ? WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'refunded',
            $event->getReason(),
            $event->getOccurredAt()->format('Y-m-d H:i:s'),
            $event->getRefundedBy(),
            $event->getPaymentId()
        ]);
    }
    
    private function invalidateCache(DomainEvent $event): void
    {
        // Invalider les caches liés à cet événement
        $this->cache->delete("payment_details_{$event->getPaymentId()}");
    }
}
```

**Résultat** : Projections spécialisées pour chaque usage.

## Les Avantages du Stockage SQL Event Sourcing + CQRS

### 1. **Performance Maximale**

**Avec Gyroscops** : Le stockage SQL Event Sourcing + CQRS m'a donné des performances maximales :
- Modèles optimisés par usage
- Requêtes spécialisées
- Cache intelligent
- Performance prévisible

**Résultat** : Performances de lecture et d'écriture excellentes.

### 2. **Flexibilité Totale**

**Avec Gyroscops** : Le stockage SQL Event Sourcing + CQRS m'a donné une flexibilité totale :
- Modèles indépendants
- Évolution séparée
- Vues personnalisées
- Optimisations ciblées

**Résultat** : Flexibilité maximale pour l'évolution.

### 3. **Audit Trail Complet**

**Avec Gyroscops** : Le stockage SQL Event Sourcing + CQRS m'a conservé l'audit trail :
- Historique complet des changements
- Reconstruction possible
- Debugging facilité
- Traçabilité totale

**Résultat** : Audit trail parfait conservé.

### 4. **Évolutivité Maximale**

**Avec Gyroscops** : Le stockage SQL Event Sourcing + CQRS m'a permis une évolutivité maximale :
- Nouvelles projections sans impact
- Optimisations ciblées
- Évolution indépendante
- Flexibilité totale

**Résultat** : Évolutivité maximale.

## Les Inconvénients du Stockage SQL Event Sourcing + CQRS

### 1. **Complexité Technique Très Élevée**

**Avec Gyroscops** : Le stockage SQL Event Sourcing + CQRS a ajouté une complexité très élevée :
- Courbe d'apprentissage importante
- Beaucoup de composants à maintenir
- Concepts très avancés
- Debugging très complexe

**Résultat** : Complexité technique très élevée.

### 2. **Cohérence Éventuelle**

**Avec Gyroscops** : Le stockage SQL Event Sourcing + CQRS peut avoir des problèmes de cohérence :
- Projections asynchrones
- Délai de synchronisation
- Incohérence temporaire
- Gestion des erreurs complexe

**Résultat** : Cohérence éventuelle à gérer.

### 3. **Gestion du Cache Complexe**

**Avec Gyroscops** : Le stockage SQL Event Sourcing + CQRS nécessite une gestion du cache complexe :
- Invalidation complexe
- Synchronisation des caches
- Gestion des erreurs
- Performance du cache

**Résultat** : Gestion du cache très complexe.

### 4. **Charge Mentale Élevée**

**Avec Gyroscops** : Le stockage SQL Event Sourcing + CQRS a une charge mentale élevée :
- Concepts multiples
- Interactions complexes
- Debugging difficile
- Formation nécessaire

**Résultat** : Charge mentale très élevée.

## Les Pièges à Éviter

### 1. **Modèles Trop Similaires**

**❌ Mauvais** : Command et Query Models trop similaires
**✅ Bon** : Modèles complètement séparés et optimisés

**Pourquoi c'est important ?** Si les modèles sont similaires, CQRS n'apporte rien.

### 2. **Projections Synchrones**

**❌ Mauvais** : Projections mises à jour de façon synchrone
**✅ Bon** : Projections asynchrones avec Event Bus

**Pourquoi c'est crucial ?** Les projections synchrones tuent les performances.

### 3. **Cache Non Invalidé**

**❌ Mauvais** : Cache qui n'est jamais invalidé
**✅ Bon** : Invalidation intelligente du cache

**Pourquoi c'est essentiel ?** Le cache obsolète donne de mauvaises données.

### 4. **Équipe Non Formée**

**❌ Mauvais** : Équipe non formée aux concepts avancés
**✅ Bon** : Formation approfondie de l'équipe

**Pourquoi c'est critique ?** Sans formation, l'équipe ne peut pas maintenir le système.

## 🏗️ Implémentation Concrète dans le Projet Hive

### Stockage SQL Event Sourcing + CQRS Appliqué à Hive

Le projet Hive applique concrètement les principes du stockage SQL Event Sourcing + CQRS à travers son architecture et ses ADR (Architecture Decision Records). Voici comment :

#### Configuration Event Sourcing + CQRS Hive

```php
// ✅ Configuration Event Sourcing + CQRS Hive (Projet Hive)
final class HiveEventSourcingCQRSConfiguration
{
    public function configureEventSourcingCQRS(ContainerBuilder $container): void
    {
        // Configuration de l'Event Store
        $container->register(SqlEventStore::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration des Command Handlers
        $container->register(PaymentCommandHandler::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration des Query Handlers
        $container->register(PaymentListQueryHandler::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        $container->register(PaymentDetailsQueryHandler::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        $container->register(PaymentAnalyticsQueryHandler::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration des projections
        $container->register(PaymentListProjectionHandler::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        $container->register(PaymentDetailsProjectionHandler::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration de l'Event Bus
        $container->register(EventBus::class)
            ->setAutowired(true)
            ->setPublic(true);
        
        // Configuration du cache
        $container->register(CacheInterface::class)
            ->setFactory([RedisAdapter::class, 'createConnection'])
            ->setAutowired(true)
            ->setPublic(true);
    }
}
```

### Références aux ADR du Projet Hive

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du projet Hive :
- **HIVE006** : Query Models for API Platform - Modèles de requête
- **HIVE007** : Command Models for API Platform - Modèles de commande
- **HIVE008** : Event Collaboration - Collaboration par événements
- **HIVE009** : Message Buses - Bus de messages
- **HIVE010** : Repositories - Repositories de base
- **HIVE012** : Database Repositories - Repositories de base de données
- **HIVE014** : Projections Event Sourcing - Projections Event Sourcing

{{< chapter-nav >}}
  {{{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux comprendre le stockage API" 
    subtitle="Vous voulez voir comment intégrer des APIs externes" 
    criteria="Équipe expérimentée,Besoin d'intégrer des services externes,Données distribuées,Intégrations multiples" 
    time="25-35 minutes" 
    chapter="59" 
    chapter-title="Stockage API - Approche Classique" 
    chapter-url="/chapitres/stockage/chapitre-51-stockage-api-classique/" 
  >}}}}
  
  {{{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux comprendre le stockage ElasticSearch" 
    subtitle="Vous voulez voir comment optimiser la recherche" 
    criteria="Équipe expérimentée,Besoin de recherche avancée,Analytics importantes,Performance de recherche critique" 
    time="30-40 minutes" 
    chapter="60" 
    chapter-title="Stockage ElasticSearch - Approche Classique" 
    chapter-url="/chapitres/stockage/chapitre-51-stockage-elasticsearch-classique/" 
  >}}}}
  
  {{{< chapter-option 
    letter="C" 
    color="red" 
    title="Je veux comprendre le stockage MongoDB" 
    subtitle="Vous voulez voir comment gérer des données semi-structurées" 
    criteria="Équipe expérimentée,Besoin de flexibilité du schéma,Données semi-structurées,Performance de lecture élevée" 
    time="30-40 minutes" 
    chapter="61" 
    chapter-title="Stockage MongoDB - Approche Classique" 
    chapter-url="/chapitres/stockage/chapitre-51-stockage-mongodb-classique/" 
  >}}}}
  
{{< /chapter-nav >}}