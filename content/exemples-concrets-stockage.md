# 💾 Exemples Concrets pour Chaque Approche de Stockage

## 📋 Vue d'Ensemble

Ce document fournit des exemples concrets d'implémentation pour chaque approche de stockage présentée dans la documentation. Chaque exemple est basé sur le projet Hive et suit les patterns établis dans les ADR.

## 🏗️ Approches de Stockage

### 1. Stockage SQL - Approche Classique

#### Exemple : Repository de Paiement

```php
// Repository de Paiement Classique - Projet Hive
final class DatabasePaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(
        private Connection $connection,
        private EventBusInterface $eventBus,
    ) {
    }

    public function get(PaymentId $id): Payment
    {
        $sql = <<<'SQL'
            SELECT 
                uuid,
                amount,
                currency,
                status,
                authorization_code,
                created_at,
                processed_at,
                cancelled_at,
                version
            FROM payments
            WHERE uuid = :uuid
            LIMIT 1
            SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':uuid', $id->toString(), ParameterType::STRING);

        $result = $statement->executeQuery();
        if ($result->rowCount() <= 0) {
            throw new NotFoundException();
        }

        $payment = $result->fetchAssociative();
        if (false === $payment) {
            throw new NotFoundException();
        }

        return new Payment(
            id: PaymentId::fromString($payment['uuid']),
            amount: new Amount($payment['amount'], Currencies::from($payment['currency'])),
            status: PaymentStatus::from($payment['status']),
            authorizationCode: $payment['authorization_code'],
            createdAt: new \DateTimeImmutable($payment['created_at']),
            processedAt: $payment['processed_at'] ? new \DateTimeImmutable($payment['processed_at']) : null,
            cancelledAt: $payment['cancelled_at'] ? new \DateTimeImmutable($payment['cancelled_at']) : null,
            version: $payment['version']
        );
    }

    public function save(Payment $payment): void
    {
        $this->connection->beginTransaction();
        try {
            $events = $payment->releaseEvents();

            // Sauvegarder l'état
            $this->savePayment($payment);

            // Émettre les événements
            foreach ($events as $event) {
                $this->eventBus->emit($event);
            }

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    private function savePayment(Payment $payment): void
    {
        $sql = <<<'SQL'
            INSERT INTO payments (
                uuid, amount, currency, status, authorization_code, 
                created_at, processed_at, cancelled_at, version
            ) VALUES (
                :uuid, :amount, :currency, :status, :authorization_code,
                :created_at, :processed_at, :cancelled_at, :version
            ) ON DUPLICATE KEY UPDATE
                amount = VALUES(amount),
                currency = VALUES(currency),
                status = VALUES(status),
                authorization_code = VALUES(authorization_code),
                processed_at = VALUES(processed_at),
                cancelled_at = VALUES(cancelled_at),
                version = VALUES(version)
            SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':uuid', $payment->getId()->toString(), ParameterType::STRING);
        $statement->bindValue(':amount', $payment->getAmount()->getValue(), ParameterType::STRING);
        $statement->bindValue(':currency', $payment->getAmount()->getCurrency()->value, ParameterType::STRING);
        $statement->bindValue(':status', $payment->getStatus()->value, ParameterType::STRING);
        $statement->bindValue(':authorization_code', $payment->getAuthorizationCode(), ParameterType::STRING);
        $statement->bindValue(':created_at', $payment->getCreatedAt()->format('Y-m-d H:i:s'), ParameterType::STRING);
        $statement->bindValue(':processed_at', $payment->getProcessedAt()?->format('Y-m-d H:i:s'), ParameterType::STRING);
        $statement->bindValue(':cancelled_at', $payment->getCancelledAt()?->format('Y-m-d H:i:s'), ParameterType::STRING);
        $statement->bindValue(':version', $payment->getVersion(), ParameterType::INTEGER);

        $statement->executeStatement();
    }
}
```

### 2. Stockage SQL - Approche CQS

#### Exemple : Repository de Paiement CQS

```php
// Repository de Paiement CQS - Projet Hive
final class DatabasePaymentCQSRepository implements PaymentCQSRepositoryInterface
{
    public function __construct(
        private Connection $connection,
        private EventBusInterface $eventBus,
    ) {
    }

    // ===== COMMANDES =====
    
    public function save(PaymentCommand $payment): void
    {
        $this->connection->beginTransaction();
        try {
            $events = $payment->releaseEvents();

            // Sauvegarder l'état
            $this->savePaymentCommand($payment);

            // Émettre les événements
            foreach ($events as $event) {
                $this->eventBus->emit($event);
            }

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    private function savePaymentCommand(PaymentCommand $payment): void
    {
        $sql = <<<'SQL'
            INSERT INTO payments_command (
                uuid, amount, currency, status, authorization_code,
                created_at, processed_at, cancelled_at, version
            ) VALUES (
                :uuid, :amount, :currency, :status, :authorization_code,
                :created_at, :processed_at, :cancelled_at, :version
            ) ON DUPLICATE KEY UPDATE
                amount = VALUES(amount),
                currency = VALUES(currency),
                status = VALUES(status),
                authorization_code = VALUES(authorization_code),
                processed_at = VALUES(processed_at),
                cancelled_at = VALUES(cancelled_at),
                version = VALUES(version)
            SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':uuid', $payment->getId()->toString(), ParameterType::STRING);
        $statement->bindValue(':amount', $payment->getAmount()->getValue(), ParameterType::STRING);
        $statement->bindValue(':currency', $payment->getAmount()->getCurrency()->value, ParameterType::STRING);
        $statement->bindValue(':status', $payment->getStatus()->value, ParameterType::STRING);
        $statement->bindValue(':authorization_code', $payment->getAuthorizationCode(), ParameterType::STRING);
        $statement->bindValue(':created_at', $payment->getCreatedAt()->format('Y-m-d H:i:s'), ParameterType::STRING);
        $statement->bindValue(':processed_at', $payment->getProcessedAt()?->format('Y-m-d H:i:s'), ParameterType::STRING);
        $statement->bindValue(':cancelled_at', $payment->getCancelledAt()?->format('Y-m-d H:i:s'), ParameterType::STRING);
        $statement->bindValue(':version', $payment->getVersion(), ParameterType::INTEGER);

        $statement->executeStatement();
    }

    // ===== REQUÊTES =====
    
    public function find(PaymentId $id): ?PaymentQuery
    {
        $sql = <<<'SQL'
            SELECT 
                uuid,
                amount,
                currency,
                status,
                authorization_code,
                created_at,
                processed_at,
                cancelled_at,
                version,
                metadata
            FROM payments_query
            WHERE uuid = :uuid
            LIMIT 1
            SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':uuid', $id->toString(), ParameterType::STRING);

        $result = $statement->executeQuery();
        if ($result->rowCount() <= 0) {
            return null;
        }

        $payment = $result->fetchAssociative();
        if (false === $payment) {
            return null;
        }

        return new PaymentQuery(
            id: PaymentId::fromString($payment['uuid']),
            amount: new Amount($payment['amount'], Currencies::from($payment['currency'])),
            status: PaymentStatus::from($payment['status']),
            authorizationCode: $payment['authorization_code'],
            createdAt: new \DateTimeImmutable($payment['created_at']),
            processedAt: $payment['processed_at'] ? new \DateTimeImmutable($payment['processed_at']) : null,
            cancelledAt: $payment['cancelled_at'] ? new \DateTimeImmutable($payment['cancelled_at']) : null,
            version: $payment['version'],
            metadata: json_decode($payment['metadata'], true) ?? []
        );
    }

    public function findByOrganization(OrganizationId $organizationId): array
    {
        $sql = <<<'SQL'
            SELECT 
                uuid,
                amount,
                currency,
                status,
                authorization_code,
                created_at,
                processed_at,
                cancelled_at,
                version,
                metadata
            FROM payments_query
            WHERE organization_id = :organization_id
            ORDER BY created_at DESC
            SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':organization_id', $organizationId->toString(), ParameterType::STRING);

        $result = $statement->executeQuery();
        $payments = [];

        while ($payment = $result->fetchAssociative()) {
            $payments[] = new PaymentQuery(
                id: PaymentId::fromString($payment['uuid']),
                amount: new Amount($payment['amount'], Currencies::from($payment['currency'])),
                status: PaymentStatus::from($payment['status']),
                authorizationCode: $payment['authorization_code'],
                createdAt: new \DateTimeImmutable($payment['created_at']),
                processedAt: $payment['processed_at'] ? new \DateTimeImmutable($payment['processed_at']) : null,
                cancelledAt: $payment['cancelled_at'] ? new \DateTimeImmutable($payment['cancelled_at']) : null,
                version: $payment['version'],
                metadata: json_decode($payment['metadata'], true) ?? []
            );
        }

        return $payments;
    }
}
```

### 3. Stockage SQL - Approche CQRS

#### Exemple : Repository de Paiement CQRS

```php
// Repository de Paiement CQRS - Projet Hive
final class DatabasePaymentCQRSRepository implements PaymentCQRSRepositoryInterface
{
    public function __construct(
        private Connection $connection,
        private EventBusInterface $eventBus,
    ) {
    }

    // ===== COMMANDES =====
    
    public function save(PaymentCommand $payment): void
    {
        $this->connection->beginTransaction();
        try {
            $events = $payment->releaseEvents();

            // Sauvegarder l'état
            $this->savePaymentCommand($payment);

            // Émettre les événements
            foreach ($events as $event) {
                $this->eventBus->emit($event);
            }

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    private function savePaymentCommand(PaymentCommand $payment): void
    {
        $sql = <<<'SQL'
            INSERT INTO payments_command (
                uuid, amount, currency, status, authorization_code,
                created_at, processed_at, cancelled_at, version
            ) VALUES (
                :uuid, :amount, :currency, :status, :authorization_code,
                :created_at, :processed_at, :cancelled_at, :version
            ) ON DUPLICATE KEY UPDATE
                amount = VALUES(amount),
                currency = VALUES(currency),
                status = VALUES(status),
                authorization_code = VALUES(authorization_code),
                processed_at = VALUES(processed_at),
                cancelled_at = VALUES(cancelled_at),
                version = VALUES(version)
            SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':uuid', $payment->getId()->toString(), ParameterType::STRING);
        $statement->bindValue(':amount', $payment->getAmount()->getValue(), ParameterType::STRING);
        $statement->bindValue(':currency', $payment->getAmount()->getCurrency()->value, ParameterType::STRING);
        $statement->bindValue(':status', $payment->getStatus()->value, ParameterType::STRING);
        $statement->bindValue(':authorization_code', $payment->getAuthorizationCode(), ParameterType::STRING);
        $statement->bindValue(':created_at', $payment->getCreatedAt()->format('Y-m-d H:i:s'), ParameterType::STRING);
        $statement->bindValue(':processed_at', $payment->getProcessedAt()?->format('Y-m-d H:i:s'), ParameterType::STRING);
        $statement->bindValue(':cancelled_at', $payment->getCancelledAt()?->format('Y-m-d H:i:s'), ParameterType::STRING);
        $statement->bindValue(':version', $payment->getVersion(), ParameterType::INTEGER);

        $statement->executeStatement();
    }

    // ===== REQUÊTES =====
    
    public function find(PaymentId $id): ?PaymentQuery
    {
        $sql = <<<'SQL'
            SELECT 
                uuid,
                amount,
                currency,
                status,
                authorization_code,
                created_at,
                processed_at,
                cancelled_at,
                version,
                metadata
            FROM payments_query
            WHERE uuid = :uuid
            LIMIT 1
            SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':uuid', $id->toString(), ParameterType::STRING);

        $result = $statement->executeQuery();
        if ($result->rowCount() <= 0) {
            return null;
        }

        $payment = $result->fetchAssociative();
        if (false === $payment) {
            return null;
        }

        return new PaymentQuery(
            id: PaymentId::fromString($payment['uuid']),
            amount: new Amount($payment['amount'], Currencies::from($payment['currency'])),
            status: PaymentStatus::from($payment['status']),
            authorizationCode: $payment['authorization_code'],
            createdAt: new \DateTimeImmutable($payment['created_at']),
            processedAt: $payment['processed_at'] ? new \DateTimeImmutable($payment['processed_at']) : null,
            cancelledAt: $payment['cancelled_at'] ? new \DateTimeImmutable($payment['cancelled_at']) : null,
            version: $payment['version'],
            metadata: json_decode($payment['metadata'], true) ?? []
        );
    }

    public function findByOrganization(OrganizationId $organizationId): array
    {
        $sql = <<<'SQL'
            SELECT 
                uuid,
                amount,
                currency,
                status,
                authorization_code,
                created_at,
                processed_at,
                cancelled_at,
                version,
                metadata
            FROM payments_query
            WHERE organization_id = :organization_id
            ORDER BY created_at DESC
            SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':organization_id', $organizationId->toString(), ParameterType::STRING);

        $result = $statement->executeQuery();
        $payments = [];

        while ($payment = $result->fetchAssociative()) {
            $payments[] = new PaymentQuery(
                id: PaymentId::fromString($payment['uuid']),
                amount: new Amount($payment['amount'], Currencies::from($payment['currency'])),
                status: PaymentStatus::from($payment['status']),
                authorizationCode: $payment['authorization_code'],
                createdAt: new \DateTimeImmutable($payment['created_at']),
                processedAt: $payment['processed_at'] ? new \DateTimeImmutable($payment['processed_at']) : null,
                cancelledAt: $payment['cancelled_at'] ? new \DateTimeImmutable($payment['cancelled_at']) : null,
                version: $payment['version'],
                metadata: json_decode($payment['metadata'], true) ?? []
            );
        }

        return $payments;
    }
}
```

### 4. Stockage SQL - Event Sourcing

#### Exemple : Repository de Paiement Event Sourcing

```php
// Repository de Paiement Event Sourcing - Projet Hive
final class DatabasePaymentEventSourcingRepository implements PaymentEventSourcingRepositoryInterface
{
    public function __construct(
        private Connection $connection,
        private EventBusInterface $eventBus,
    ) {
    }

    public function get(PaymentId $id): Payment
    {
        $events = $this->getEvents($id);
        
        if (empty($events)) {
            throw new NotFoundException();
        }

        return $this->reconstructFromEvents($events);
    }

    public function save(Payment $payment): void
    {
        $this->connection->beginTransaction();
        try {
            $events = $payment->releaseEvents();

            // Sauvegarder les événements
            foreach ($events as $event) {
                $this->saveEvent($event);
            }

            // Émettre les événements
            foreach ($events as $event) {
                $this->eventBus->emit($event);
            }

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    private function getEvents(PaymentId $id): array
    {
        $sql = <<<'SQL'
            SELECT event_type, event_data, occurred_on, version
            FROM events
            WHERE aggregate_id = :aggregate_id
            ORDER BY version ASC
            SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':aggregate_id', $id->toString(), ParameterType::STRING);

        $result = $statement->executeQuery();
        $events = [];

        while ($row = $result->fetchAssociative()) {
            $events[] = $this->deserializeEvent($row['event_type'], $row['event_data']);
        }

        return $events;
    }

    private function saveEvent(object $event): void
    {
        $sql = <<<'SQL'
            INSERT INTO events (
                aggregate_id, event_type, event_data, occurred_on, version
            ) VALUES (
                :aggregate_id, :event_type, :event_data, :occurred_on, :version
            )
            SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':aggregate_id', $this->getAggregateId($event), ParameterType::STRING);
        $statement->bindValue(':event_type', $event::class, ParameterType::STRING);
        $statement->bindValue(':event_data', json_encode($this->serializeEvent($event)), ParameterType::STRING);
        $statement->bindValue(':occurred_on', $this->getOccurredOn($event)->format('Y-m-d H:i:s'), ParameterType::STRING);
        $statement->bindValue(':version', $this->getVersion($event), ParameterType::INTEGER);

        $statement->executeStatement();
    }

    private function reconstructFromEvents(array $events): Payment
    {
        $payment = null;
        
        foreach ($events as $event) {
            if ($event instanceof PaymentCreated) {
                $payment = new Payment(
                    id: $event->paymentId,
                    amount: $event->amount,
                    status: PaymentStatus::PENDING
                );
            } else {
                $payment->apply($event);
            }
        }
        
        return $payment;
    }

    private function deserializeEvent(string $eventType, string $eventData): object
    {
        $data = json_decode($eventData, true);
        $eventClass = $eventType;

        return new $eventClass(...$data);
    }

    private function serializeEvent(object $event): array
    {
        // Implémentation de la sérialisation
        return [];
    }

    private function getAggregateId(object $event): string
    {
        // Implémentation de l'extraction de l'ID d'agrégat
        return '';
    }

    private function getOccurredOn(object $event): \DateTimeInterface
    {
        // Implémentation de l'extraction de la date d'occurrence
        return new \DateTimeImmutable();
    }

    private function getVersion(object $event): int
    {
        // Implémentation de l'extraction de la version
        return 1;
    }
}
```

### 5. Stockage API - Approche Classique

#### Exemple : Repository de Paiement API

```php
// Repository de Paiement API - Projet Hive
final class ApiPaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiBaseUrl,
        private string $apiKey,
        private EventBusInterface $eventBus,
    ) {
    }

    public function get(PaymentId $id): Payment
    {
        $response = $this->httpClient->request('GET', "{$this->apiBaseUrl}/payments/{$id->toString()}", [
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ],
        ]);

        if ($response->getStatusCode() === 404) {
            throw new NotFoundException();
        }

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('API request failed');
        }

        $data = json_decode($response->getContent(), true);

        return new Payment(
            id: PaymentId::fromString($data['uuid']),
            amount: new Amount($data['amount'], Currencies::from($data['currency'])),
            status: PaymentStatus::from($data['status']),
            authorizationCode: $data['authorization_code'] ?? null,
            createdAt: new \DateTimeImmutable($data['created_at']),
            processedAt: $data['processed_at'] ? new \DateTimeImmutable($data['processed_at']) : null,
            cancelledAt: $data['cancelled_at'] ? new \DateTimeImmutable($data['cancelled_at']) : null,
            version: $data['version']
        );
    }

    public function save(Payment $payment): void
    {
        $events = $payment->releaseEvents();

        // Sauvegarder via l'API
        $this->saveViaApi($payment);

        // Émettre les événements
        foreach ($events as $event) {
            $this->eventBus->emit($event);
        }
    }

    private function saveViaApi(Payment $payment): void
    {
        $data = [
            'uuid' => $payment->getId()->toString(),
            'amount' => $payment->getAmount()->getValue(),
            'currency' => $payment->getAmount()->getCurrency()->value,
            'status' => $payment->getStatus()->value,
            'authorization_code' => $payment->getAuthorizationCode(),
            'created_at' => $payment->getCreatedAt()->format('Y-m-d H:i:s'),
            'processed_at' => $payment->getProcessedAt()?->format('Y-m-d H:i:s'),
            'cancelled_at' => $payment->getCancelledAt()?->format('Y-m-d H:i:s'),
            'version' => $payment->getVersion(),
        ];

        $response = $this->httpClient->request('PUT', "{$this->apiBaseUrl}/payments/{$payment->getId()->toString()}", [
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('API request failed');
        }
    }
}
```

### 6. Stockage ElasticSearch - Approche Classique

#### Exemple : Repository de Paiement ElasticSearch

```php
// Repository de Paiement ElasticSearch - Projet Hive
final class ElasticSearchPaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(
        private Client $elasticsearchClient,
        private string $indexName,
        private EventBusInterface $eventBus,
    ) {
    }

    public function get(PaymentId $id): Payment
    {
        $response = $this->elasticsearchClient->get([
            'index' => $this->indexName,
            'id' => $id->toString(),
        ]);

        if (!$response['found']) {
            throw new NotFoundException();
        }

        $data = $response['_source'];

        return new Payment(
            id: PaymentId::fromString($data['uuid']),
            amount: new Amount($data['amount'], Currencies::from($data['currency'])),
            status: PaymentStatus::from($data['status']),
            authorizationCode: $data['authorization_code'] ?? null,
            createdAt: new \DateTimeImmutable($data['created_at']),
            processedAt: $data['processed_at'] ? new \DateTimeImmutable($data['processed_at']) : null,
            cancelledAt: $data['cancelled_at'] ? new \DateTimeImmutable($data['cancelled_at']) : null,
            version: $data['version']
        );
    }

    public function save(Payment $payment): void
    {
        $events = $payment->releaseEvents();

        // Sauvegarder dans ElasticSearch
        $this->saveToElasticSearch($payment);

        // Émettre les événements
        foreach ($events as $event) {
            $this->eventBus->emit($event);
        }
    }

    private function saveToElasticSearch(Payment $payment): void
    {
        $data = [
            'uuid' => $payment->getId()->toString(),
            'amount' => $payment->getAmount()->getValue(),
            'currency' => $payment->getAmount()->getCurrency()->value,
            'status' => $payment->getStatus()->value,
            'authorization_code' => $payment->getAuthorizationCode(),
            'created_at' => $payment->getCreatedAt()->format('Y-m-d H:i:s'),
            'processed_at' => $payment->getProcessedAt()?->format('Y-m-d H:i:s'),
            'cancelled_at' => $payment->getCancelledAt()?->format('Y-m-d H:i:s'),
            'version' => $payment->getVersion(),
        ];

        $this->elasticsearchClient->index([
            'index' => $this->indexName,
            'id' => $payment->getId()->toString(),
            'body' => $data,
        ]);
    }

    public function search(array $criteria): array
    {
        $query = [
            'bool' => [
                'must' => [],
            ],
        ];

        if (isset($criteria['status'])) {
            $query['bool']['must'][] = [
                'term' => ['status' => $criteria['status']],
            ];
        }

        if (isset($criteria['organization_id'])) {
            $query['bool']['must'][] = [
                'term' => ['organization_id' => $criteria['organization_id']],
            ];
        }

        if (isset($criteria['date_from'])) {
            $query['bool']['must'][] = [
                'range' => [
                    'created_at' => [
                        'gte' => $criteria['date_from'],
                    ],
                ],
            ];
        }

        if (isset($criteria['date_to'])) {
            $query['bool']['must'][] = [
                'range' => [
                    'created_at' => [
                        'lte' => $criteria['date_to'],
                    ],
                ],
            ];
        }

        $response = $this->elasticsearchClient->search([
            'index' => $this->indexName,
            'body' => [
                'query' => $query,
                'sort' => [
                    ['created_at' => ['order' => 'desc']],
                ],
            ],
        ]);

        $payments = [];
        foreach ($response['hits']['hits'] as $hit) {
            $data = $hit['_source'];
            $payments[] = new Payment(
                id: PaymentId::fromString($data['uuid']),
                amount: new Amount($data['amount'], Currencies::from($data['currency'])),
                status: PaymentStatus::from($data['status']),
                authorizationCode: $data['authorization_code'] ?? null,
                createdAt: new \DateTimeImmutable($data['created_at']),
                processedAt: $data['processed_at'] ? new \DateTimeImmutable($data['processed_at']) : null,
                cancelledAt: $data['cancelled_at'] ? new \DateTimeImmutable($data['cancelled_at']) : null,
                version: $data['version']
            );
        }

        return $payments;
    }
}
```

### 7. Stockage MongoDB - Approche Classique

#### Exemple : Repository de Paiement MongoDB

```php
// Repository de Paiement MongoDB - Projet Hive
final class MongoPaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(
        private Manager $mongoManager,
        private string $databaseName,
        private string $collectionName,
        private EventBusInterface $eventBus,
    ) {
    }

    public function get(PaymentId $id): Payment
    {
        $collection = $this->getCollection();
        $document = $collection->findOne(['uuid' => $id->toString()]);

        if (!$document) {
            throw new NotFoundException();
        }

        return $this->documentToPayment($document);
    }

    public function save(Payment $payment): void
    {
        $events = $payment->releaseEvents();

        // Sauvegarder dans MongoDB
        $this->saveToMongo($payment);

        // Émettre les événements
        foreach ($events as $event) {
            $this->eventBus->emit($event);
        }
    }

    private function saveToMongo(Payment $payment): void
    {
        $collection = $this->getCollection();
        
        $document = [
            'uuid' => $payment->getId()->toString(),
            'amount' => $payment->getAmount()->getValue(),
            'currency' => $payment->getAmount()->getCurrency()->value,
            'status' => $payment->getStatus()->value,
            'authorization_code' => $payment->getAuthorizationCode(),
            'created_at' => $payment->getCreatedAt()->format('Y-m-d H:i:s'),
            'processed_at' => $payment->getProcessedAt()?->format('Y-m-d H:i:s'),
            'cancelled_at' => $payment->getCancelledAt()?->format('Y-m-d H:i:s'),
            'version' => $payment->getVersion(),
        ];

        $collection->replaceOne(
            ['uuid' => $payment->getId()->toString()],
            $document,
            ['upsert' => true]
        );
    }

    private function documentToPayment(array $document): Payment
    {
        return new Payment(
            id: PaymentId::fromString($document['uuid']),
            amount: new Amount($document['amount'], Currencies::from($document['currency'])),
            status: PaymentStatus::from($document['status']),
            authorizationCode: $document['authorization_code'] ?? null,
            createdAt: new \DateTimeImmutable($document['created_at']),
            processedAt: $document['processed_at'] ? new \DateTimeImmutable($document['processed_at']) : null,
            cancelledAt: $document['cancelled_at'] ? new \DateTimeImmutable($document['cancelled_at']) : null,
            version: $document['version']
        );
    }

    private function getCollection(): Collection
    {
        return $this->mongoManager->selectCollection($this->databaseName, $this->collectionName);
    }
}
```

### 8. Stockage In-Memory - Approche Classique

#### Exemple : Repository de Paiement In-Memory

```php
// Repository de Paiement In-Memory - Projet Hive
final class InMemoryPaymentRepository implements PaymentRepositoryInterface
{
    private array $payments = [];

    public function __construct(
        private EventBusInterface $eventBus,
    ) {
    }

    public function get(PaymentId $id): Payment
    {
        if (!isset($this->payments[$id->toString()])) {
            throw new NotFoundException();
        }

        return $this->payments[$id->toString()];
    }

    public function save(Payment $payment): void
    {
        $events = $payment->releaseEvents();

        // Sauvegarder en mémoire
        $this->payments[$payment->getId()->toString()] = $payment;

        // Émettre les événements
        foreach ($events as $event) {
            $this->eventBus->emit($event);
        }
    }

    public function findAll(): array
    {
        return array_values($this->payments);
    }

    public function findByStatus(PaymentStatus $status): array
    {
        return array_filter($this->payments, fn($payment) => $payment->getStatus() === $status);
    }

    public function clear(): void
    {
        $this->payments = [];
    }
}
```

## 🔧 Patterns d'Implémentation

### 1. Factory Pattern pour les Repositories

```php
// Factory Pattern - Projet Hive
final class PaymentRepositoryFactory
{
    public function __construct(
        private Connection $connection,
        private HttpClientInterface $httpClient,
        private Client $elasticsearchClient,
        private Manager $mongoManager,
        private EventBusInterface $eventBus,
    ) {
    }

    public function create(string $type, array $config = []): PaymentRepositoryInterface
    {
        return match ($type) {
            'database' => new DatabasePaymentRepository($this->connection, $this->eventBus),
            'api' => new ApiPaymentRepository(
                $this->httpClient,
                $config['api_base_url'],
                $config['api_key'],
                $this->eventBus
            ),
            'elasticsearch' => new ElasticSearchPaymentRepository(
                $this->elasticsearchClient,
                $config['index_name'],
                $this->eventBus
            ),
            'mongodb' => new MongoPaymentRepository(
                $this->mongoManager,
                $config['database_name'],
                $config['collection_name'],
                $this->eventBus
            ),
            'in_memory' => new InMemoryPaymentRepository($this->eventBus),
            default => throw new \InvalidArgumentException("Unsupported repository type: {$type}"),
        };
    }
}
```

### 2. Strategy Pattern pour les Repositories

```php
// Strategy Pattern - Projet Hive
interface PaymentRepositoryStrategyInterface
{
    public function get(PaymentId $id): Payment;
    public function save(Payment $payment): void;
}

final class PaymentRepositoryStrategy
{
    public function __construct(
        private PaymentRepositoryStrategyInterface $strategy
    ) {
    }

    public function get(PaymentId $id): Payment
    {
        return $this->strategy->get($id);
    }

    public function save(Payment $payment): void
    {
        $this->strategy->save($payment);
    }

    public function setStrategy(PaymentRepositoryStrategyInterface $strategy): void
    {
        $this->strategy = $strategy;
    }
}
```

### 3. Decorator Pattern pour les Repositories

```php
// Decorator Pattern - Projet Hive
final class CachedPaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(
        private PaymentRepositoryInterface $repository,
        private CacheInterface $cache,
        private int $ttl = 3600
    ) {
    }

    public function get(PaymentId $id): Payment
    {
        $cacheKey = "payment_{$id->toString()}";
        
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $payment = $this->repository->get($id);
        $this->cache->set($cacheKey, $payment, $this->ttl);

        return $payment;
    }

    public function save(Payment $payment): void
    {
        $this->repository->save($payment);
        
        $cacheKey = "payment_{$payment->getId()->toString()}";
        $this->cache->delete($cacheKey);
    }
}
```

## 📊 Comparaison des Approches

### Performance

| Approche | Lecture | Écriture | Complexité | Maintenance |
|----------|---------|----------|------------|-------------|
| **SQL Classique** | Bonne | Bonne | Faible | Simple |
| **SQL CQS** | Très Bonne | Bonne | Modérée | Modérée |
| **SQL CQRS** | Excellente | Excellente | Élevée | Complexe |
| **SQL Event Sourcing** | Variable | Bonne | Élevée | Complexe |
| **API** | Variable | Variable | Modérée | Modérée |
| **ElasticSearch** | Excellente | Bonne | Modérée | Modérée |
| **MongoDB** | Très Bonne | Très Bonne | Modérée | Modérée |
| **In-Memory** | Excellente | Excellente | Faible | Simple |

### Utilisation Recommandée

| Approche | Cas d'Usage | Équipe | Budget | Temps |
|----------|-------------|--------|--------|-------|
| **SQL Classique** | Application simple | Junior | Faible | 1-2 semaines |
| **SQL CQS** | Performance modérée | Intermédiaire | Modéré | 2-3 semaines |
| **SQL CQRS** | Performance critique | Expérimentée | Élevé | 1-2 mois |
| **SQL Event Sourcing** | Audit trail critique | Expérimentée | Élevé | 2-3 mois |
| **API** | Intégration externe | Intermédiaire | Modéré | 2-3 semaines |
| **ElasticSearch** | Recherche avancée | Intermédiaire | Modéré | 2-3 semaines |
| **MongoDB** | Données flexibles | Intermédiaire | Modéré | 2-3 semaines |
| **In-Memory** | Tests et cache | Tous niveaux | Faible | 1 semaine |

---

*Ces exemples sont basés sur les Architecture Decision Records (ADR) du projet Hive et suivent les principes établis dans "API Platform Con 2025 - Et si on utilisait l'Event Storming ?"*
