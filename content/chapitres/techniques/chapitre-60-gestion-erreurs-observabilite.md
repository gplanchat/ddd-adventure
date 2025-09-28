---
title: "Chapitre 60 : Gestion d'Erreurs et Observabilité"
description: "Gestion d'erreurs et observabilité dans le Gyroscops Cloud avec des exemples concrets"
date: 2024-12-19
draft: true
type: "docs"
weight: 60
---

## 🎯 Objectif de ce Chapitre

Ce chapitre vous montre comment gérer les erreurs et implémenter l'observabilité dans le Gyroscops Cloud. Vous apprendrez :
- Comment gérer les erreurs de manière robuste
- Comment implémenter l'observabilité
- Comment monitorer votre application
- Comment tester la gestion d'erreurs

### Références aux ADR du Projet Gyroscops Cloud

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du Gyroscops Cloud :
- **HIVE038** : Robust Error Handling Patterns - Patterns de gestion d'erreurs robustes
- **HIVE035** : Database Operation Logging - Logging des opérations de base de données
- **HIVE036** : Input Validation Patterns - Patterns de validation des entrées
- **HIVE027** : PHPUnit Testing Standards - Standards de test PHPUnit

## 🏗️ Architecture de la Gestion d'Erreurs

### Structure de Gestion d'Erreurs

```
api/src/
├── Platform/
│   ├── Domain/
│   │   ├── Exceptions/
│   │   │   ├── DomainException.php
│   │   │   ├── ValidationException.php
│   │   │   ├── BusinessLogicException.php
│   │   │   └── InfrastructureException.php
│   │   └── ValueObjects/
│   │       ├── ErrorCode.php
│   │       ├── ErrorContext.php
│   │       └── ErrorSeverity.php
│   └── Infrastructure/
│       ├── ErrorHandling/
│       │   ├── ErrorHandler.php
│       │   ├── ErrorLogger.php
│       │   └── ErrorReporter.php
│       └── Observability/
│           ├── MetricsCollector.php
│           ├── TracingService.php
│           └── HealthChecker.php
```

### Hiérarchie des Exceptions

```php
// ✅ Hiérarchie des Exceptions (Projet Gyroscops Cloud)
abstract class DomainException extends \DomainException
{
    public function __construct(
        private ErrorCode $errorCode,
        private ErrorContext $context,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getErrorCode(): ErrorCode
    {
        return $this->errorCode;
    }

    public function getContext(): ErrorContext
    {
        return $this->context;
    }

    public function getSeverity(): ErrorSeverity
    {
        return $this->errorCode->getSeverity();
    }
}

final class ValidationException extends DomainException
{
    public function __construct(
        ErrorCode $errorCode,
        ErrorContext $context,
        string $message = 'Validation failed'
    ) {
        parent::__construct($errorCode, $context, $message);
    }
}

final class BusinessLogicException extends DomainException
{
    public function __construct(
        ErrorCode $errorCode,
        ErrorContext $context,
        string $message = 'Business logic error'
    ) {
        parent::__construct($errorCode, $context, $message);
    }
}

final class InfrastructureException extends DomainException
{
    public function __construct(
        ErrorCode $errorCode,
        ErrorContext $context,
        string $message = 'Infrastructure error'
    ) {
        parent::__construct($errorCode, $context, $message);
    }
}
```

### Value Objects pour les Erreurs

```php
// ✅ Value Objects pour les Erreurs (Projet Gyroscops Cloud)
final class ErrorCode
{
    public function __construct(
        private string $code,
        private ErrorSeverity $severity,
        private string $category
    ) {}

    public function getCode(): string
    {
        return $this->code;
    }

    public function getSeverity(): ErrorSeverity
    {
        return $this->severity;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}

enum ErrorSeverity: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function getPriority(): int
    {
        return match($this) {
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
            self::CRITICAL => 4
        };
    }
}

final class ErrorContext
{
    public function __construct(
        private array $data = [],
        private ?string $userId = null,
        private ?string $organizationId = null,
        private ?string $requestId = null,
        private ?string $traceId = null
    ) {}

    public function getData(): array
    {
        return $this->data;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getOrganizationId(): ?string
    {
        return $this->organizationId;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getTraceId(): ?string
    {
        return $this->traceId;
    }

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'user_id' => $this->userId,
            'organization_id' => $this->organizationId,
            'request_id' => $this->requestId,
            'trace_id' => $this->traceId
        ];
    }
}
```

## 🚨 Gestionnaire d'Erreurs

### Gestionnaire Principal

```php
// ✅ Gestionnaire Principal (Projet Gyroscops Cloud)
final class ErrorHandler
{
    public function __construct(
        private ErrorLogger $logger,
        private ErrorReporter $reporter,
        private MetricsCollector $metrics
    ) {}

    public function handleException(\Throwable $exception): array
    {
        $this->logException($exception);
        $this->reportException($exception);
        $this->collectMetrics($exception);

        return $this->formatErrorResponse($exception);
    }

    private function logException(\Throwable $exception): void
    {
        $context = $this->extractContext($exception);
        $level = $this->determineLogLevel($exception);

        $this->logger->log($level, $exception->getMessage(), [
            'exception' => $exception,
            'context' => $context
        ]);
    }

    private function reportException(\Throwable $exception): void
    {
        if ($this->shouldReport($exception)) {
            $this->reporter->report($exception, $this->extractContext($exception));
        }
    }

    private function collectMetrics(\Throwable $exception): void
    {
        $this->metrics->incrementCounter('exceptions_total', [
            'type' => get_class($exception),
            'severity' => $this->getSeverity($exception)
        ]);
    }

    private function formatErrorResponse(\Throwable $exception): array
    {
        if ($exception instanceof DomainException) {
            return [
                'error' => $exception->getErrorCode()->getCode(),
                'message' => $exception->getMessage(),
                'context' => $exception->getContext()->toArray(),
                'severity' => $exception->getSeverity()->value
            ];
        }

        return [
            'error' => 'INTERNAL_ERROR',
            'message' => 'An internal error occurred',
            'severity' => 'high'
        ];
    }

    private function extractContext(\Throwable $exception): ErrorContext
    {
        if ($exception instanceof DomainException) {
            return $exception->getContext();
        }

        return new ErrorContext([
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    private function determineLogLevel(\Throwable $exception): string
    {
        if ($exception instanceof DomainException) {
            return match($exception->getSeverity()) {
                ErrorSeverity::LOW => 'info',
                ErrorSeverity::MEDIUM => 'warning',
                ErrorSeverity::HIGH => 'error',
                ErrorSeverity::CRITICAL => 'critical'
            };
        }

        return 'error';
    }

    private function shouldReport(\Throwable $exception): bool
    {
        if ($exception instanceof DomainException) {
            return $exception->getSeverity()->getPriority() >= ErrorSeverity::HIGH->getPriority();
        }

        return true;
    }

    private function getSeverity(\Throwable $exception): string
    {
        if ($exception instanceof DomainException) {
            return $exception->getSeverity()->value;
        }

        return 'high';
    }
}
```

### Logger d'Erreurs

```php
// ✅ Logger d'Erreurs (Projet Gyroscops Cloud)
final class ErrorLogger
{
    public function __construct(
        private LoggerInterface $logger,
        private string $environment
    ) {}

    public function log(string $level, string $message, array $context = []): void
    {
        $logData = [
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'environment' => $this->environment,
            'context' => $context
        ];

        $this->logger->log($level, $message, $logData);
    }

    public function logError(\Throwable $exception, array $context = []): void
    {
        $this->log('error', $exception->getMessage(), [
            'exception' => $exception,
            'context' => $context
        ]);
    }

    public function logWarning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function logInfo(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }
}
```

## 📊 Observabilité

### Collecteur de Métriques

```php
// ✅ Collecteur de Métriques (Projet Gyroscops Cloud)
final class MetricsCollector
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function incrementCounter(string $name, array $labels = []): void
    {
        $this->logMetric('counter', $name, 1, $labels);
    }

    public function incrementCounterBy(string $name, float $value, array $labels = []): void
    {
        $this->logMetric('counter', $name, $value, $labels);
    }

    public function setGauge(string $name, float $value, array $labels = []): void
    {
        $this->logMetric('gauge', $name, $value, $labels);
    }

    public function observeHistogram(string $name, float $value, array $labels = []): void
    {
        $this->logMetric('histogram', $name, $value, $labels);
    }

    public function observeSummary(string $name, float $value, array $labels = []): void
    {
        $this->logMetric('summary', $name, $value, $labels);
    }

    private function logMetric(string $type, string $name, float $value, array $labels): void
    {
        $this->logger->info('Metric collected', [
            'type' => $type,
            'name' => $name,
            'value' => $value,
            'labels' => $labels,
            'timestamp' => microtime(true)
        ]);
    }
}
```

### Service de Tracing

```php
// ✅ Service de Tracing (Projet Gyroscops Cloud)
final class TracingService
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function startSpan(string $operationName, array $tags = []): Span
    {
        $spanId = $this->generateSpanId();
        $traceId = $this->generateTraceId();

        $span = new Span($spanId, $traceId, $operationName, $tags);

        $this->logger->info('Span started', [
            'span_id' => $spanId,
            'trace_id' => $traceId,
            'operation' => $operationName,
            'tags' => $tags
        ]);

        return $span;
    }

    public function finishSpan(Span $span, array $tags = []): void
    {
        $span->finish($tags);

        $this->logger->info('Span finished', [
            'span_id' => $span->getId(),
            'trace_id' => $span->getTraceId(),
            'operation' => $span->getOperationName(),
            'duration' => $span->getDuration(),
            'tags' => $tags
        ]);
    }

    public function addEvent(Span $span, string $eventName, array $attributes = []): void
    {
        $span->addEvent($eventName, $attributes);

        $this->logger->info('Event added to span', [
            'span_id' => $span->getId(),
            'trace_id' => $span->getTraceId(),
            'event' => $eventName,
            'attributes' => $attributes
        ]);
    }

    private function generateSpanId(): string
    {
        return bin2hex(random_bytes(8));
    }

    private function generateTraceId(): string
    {
        return bin2hex(random_bytes(16));
    }
}

final class Span
{
    private ?float $startTime = null;
    private ?float $endTime = null;
    private array $events = [];

    public function __construct(
        private string $id,
        private string $traceId,
        private string $operationName,
        private array $tags = []
    ) {
        $this->startTime = microtime(true);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTraceId(): string
    {
        return $this->traceId;
    }

    public function getOperationName(): string
    {
        return $this->operationName;
    }

    public function getDuration(): ?float
    {
        if ($this->startTime === null || $this->endTime === null) {
            return null;
        }

        return $this->endTime - $this->startTime;
    }

    public function finish(array $tags = []): void
    {
        $this->endTime = microtime(true);
        $this->tags = array_merge($this->tags, $tags);
    }

    public function addEvent(string $eventName, array $attributes = []): void
    {
        $this->events[] = [
            'name' => $eventName,
            'attributes' => $attributes,
            'timestamp' => microtime(true)
        ];
    }
}
```

### Vérificateur de Santé

```php
// ✅ Vérificateur de Santé (Projet Gyroscops Cloud)
final class HealthChecker
{
    public function __construct(
        private Connection $database,
        private CacheInterface $cache,
        private LoggerInterface $logger
    ) {}

    public function checkHealth(): HealthStatus
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'memory' => $this->checkMemory(),
            'disk' => $this->checkDisk()
        ];

        $overallStatus = $this->determineOverallStatus($checks);

        return new HealthStatus($overallStatus, $checks);
    }

    private function checkDatabase(): CheckResult
    {
        try {
            $startTime = microtime(true);
            $this->database->executeQuery('SELECT 1');
            $duration = microtime(true) - $startTime;

            return new CheckResult(
                status: 'healthy',
                message: 'Database connection successful',
                duration: $duration
            );
        } catch (\Throwable $e) {
            $this->logger->error('Database health check failed', [
                'error' => $e->getMessage()
            ]);

            return new CheckResult(
                status: 'unhealthy',
                message: 'Database connection failed: ' . $e->getMessage()
            );
        }
    }

    private function checkCache(): CheckResult
    {
        try {
            $startTime = microtime(true);
            $testKey = 'health_check_' . uniqid();
            $this->cache->set($testKey, 'test', 1);
            $value = $this->cache->get($testKey);
            $this->cache->delete($testKey);
            $duration = microtime(true) - $startTime;

            if ($value !== 'test') {
                throw new \RuntimeException('Cache value mismatch');
            }

            return new CheckResult(
                status: 'healthy',
                message: 'Cache connection successful',
                duration: $duration
            );
        } catch (\Throwable $e) {
            $this->logger->error('Cache health check failed', [
                'error' => $e->getMessage()
            ]);

            return new CheckResult(
                status: 'unhealthy',
                message: 'Cache connection failed: ' . $e->getMessage()
            );
        }
    }

    private function checkMemory(): CheckResult
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $memoryPercentage = ($memoryUsage / $memoryLimit) * 100;

        if ($memoryPercentage > 90) {
            return new CheckResult(
                status: 'unhealthy',
                message: "Memory usage too high: {$memoryPercentage}%"
            );
        }

        return new CheckResult(
            status: 'healthy',
            message: "Memory usage: {$memoryPercentage}%"
        );
    }

    private function checkDisk(): CheckResult
    {
        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        $diskPercentage = (($diskTotal - $diskFree) / $diskTotal) * 100;

        if ($diskPercentage > 90) {
            return new CheckResult(
                status: 'unhealthy',
                message: "Disk usage too high: {$diskPercentage}%"
            );
        }

        return new CheckResult(
            status: 'healthy',
            message: "Disk usage: {$diskPercentage}%"
        );
    }

    private function determineOverallStatus(array $checks): string
    {
        foreach ($checks as $check) {
            if ($check->getStatus() === 'unhealthy') {
                return 'unhealthy';
            }
        }

        return 'healthy';
    }

    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        return match($last) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value
        };
    }
}

final class HealthStatus
{
    public function __construct(
        private string $status,
        private array $checks
    ) {}

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getChecks(): array
    {
        return $this->checks;
    }

    public function isHealthy(): bool
    {
        return $this->status === 'healthy';
    }
}

final class CheckResult
{
    public function __construct(
        private string $status,
        private string $message,
        private ?float $duration = null
    ) {}

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getDuration(): ?float
    {
        return $this->duration;
    }
}
```

## 🧪 Tests de Gestion d'Erreurs

### Test du Gestionnaire d'Erreurs

```php
// ✅ Test du Gestionnaire d'Erreurs (Projet Gyroscops Cloud)
final class ErrorHandlerTest extends TestCase
{
    private ErrorHandler $handler;
    private ErrorLogger $logger;
    private ErrorReporter $reporter;
    private MetricsCollector $metrics;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(ErrorLogger::class);
        $this->reporter = $this->createMock(ErrorReporter::class);
        $this->metrics = $this->createMock(MetricsCollector::class);
        $this->handler = new ErrorHandler($this->logger, $this->reporter, $this->metrics);
    }

    /** @test */
    public function itShouldHandleDomainException(): void
    {
        // Arrange
        $errorCode = new ErrorCode('VALIDATION_ERROR', ErrorSeverity::MEDIUM, 'validation');
        $context = new ErrorContext(['field' => 'email']);
        $exception = new ValidationException($errorCode, $context, 'Invalid email format');

        $this->logger->expects($this->once())
            ->method('log')
            ->with('warning', 'Invalid email format', $this->isType('array'));

        $this->reporter->expects($this->once())
            ->method('report')
            ->with($exception, $context);

        $this->metrics->expects($this->once())
            ->method('incrementCounter')
            ->with('exceptions_total', [
                'type' => ValidationException::class,
                'severity' => 'medium'
            ]);

        // Act
        $response = $this->handler->handleException($exception);

        // Assert
        $this->assertEquals('VALIDATION_ERROR', $response['error']);
        $this->assertEquals('Invalid email format', $response['message']);
        $this->assertEquals('medium', $response['severity']);
        $this->assertArrayHasKey('context', $response);
    }

    /** @test */
    public function itShouldHandleGenericException(): void
    {
        // Arrange
        $exception = new \RuntimeException('Something went wrong');

        $this->logger->expects($this->once())
            ->method('log')
            ->with('error', 'Something went wrong', $this->isType('array'));

        $this->reporter->expects($this->once())
            ->method('report')
            ->with($exception, $this->isInstanceOf(ErrorContext::class));

        $this->metrics->expects($this->once())
            ->method('incrementCounter')
            ->with('exceptions_total', [
                'type' => \RuntimeException::class,
                'severity' => 'high'
            ]);

        // Act
        $response = $this->handler->handleException($exception);

        // Assert
        $this->assertEquals('INTERNAL_ERROR', $response['error']);
        $this->assertEquals('An internal error occurred', $response['message']);
        $this->assertEquals('high', $response['severity']);
    }
}
```

### Test du Collecteur de Métriques

```php
// ✅ Test du Collecteur de Métriques (Projet Gyroscops Cloud)
final class MetricsCollectorTest extends TestCase
{
    private MetricsCollector $collector;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->collector = new MetricsCollector($this->logger);
    }

    /** @test */
    public function itShouldIncrementCounter(): void
    {
        // Arrange
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Metric collected', [
                'type' => 'counter',
                'name' => 'requests_total',
                'value' => 1,
                'labels' => ['method' => 'GET'],
                'timestamp' => $this->isType('float')
            ]);

        // Act
        $this->collector->incrementCounter('requests_total', ['method' => 'GET']);

        // Assert - expectations are verified by the mock
    }

    /** @test */
    public function itShouldSetGauge(): void
    {
        // Arrange
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Metric collected', [
                'type' => 'gauge',
                'name' => 'memory_usage',
                'value' => 1024.5,
                'labels' => ['unit' => 'MB'],
                'timestamp' => $this->isType('float')
            ]);

        // Act
        $this->collector->setGauge('memory_usage', 1024.5, ['unit' => 'MB']);

        // Assert - expectations are verified by the mock
    }

    /** @test */
    public function itShouldObserveHistogram(): void
    {
        // Arrange
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Metric collected', [
                'type' => 'histogram',
                'name' => 'request_duration',
                'value' => 0.5,
                'labels' => ['endpoint' => '/api/payments'],
                'timestamp' => $this->isType('float')
            ]);

        // Act
        $this->collector->observeHistogram('request_duration', 0.5, ['endpoint' => '/api/payments']);

        // Assert - expectations are verified by the mock
    }
}
```

## 🏗️ Bonnes Pratiques du Projet Gyroscops Cloud

### Gestion d'Erreurs selon HIVE038

Le Gyroscops Cloud implémente une gestion d'erreurs robuste selon l'ADR HIVE038 :

```php
// ✅ Gestion d'Erreurs Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveErrorHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private ErrorReporter $reporter,
        private MetricsCollector $metrics,
        private ErrorContextBuilder $contextBuilder
    ) {}

    public function handleException(\Throwable $exception): array
    {
        $context = $this->contextBuilder->buildFromException($exception);
        $errorCode = $this->determineErrorCode($exception);
        $severity = $this->determineSeverity($exception);

        $this->logException($exception, $context, $severity);
        $this->reportException($exception, $context, $severity);
        $this->collectMetrics($exception, $severity);

        return $this->formatErrorResponse($errorCode, $exception, $context, $severity);
    }

    private function determineErrorCode(\Throwable $exception): string
    {
        return match (get_class($exception)) {
            ValidationException::class => 'VALIDATION_ERROR',
            BusinessLogicException::class => 'BUSINESS_LOGIC_ERROR',
            InfrastructureException::class => 'INFRASTRUCTURE_ERROR',
            \PDOException::class => 'DATABASE_ERROR',
            \RedisException::class => 'CACHE_ERROR',
            \GuzzleHttp\Exception\RequestException::class => 'API_ERROR',
            default => 'INTERNAL_ERROR'
        };
    }

    private function determineSeverity(\Throwable $exception): ErrorSeverity
    {
        return match (get_class($exception)) {
            ValidationException::class => ErrorSeverity::MEDIUM,
            BusinessLogicException::class => ErrorSeverity::HIGH,
            InfrastructureException::class => ErrorSeverity::HIGH,
            \PDOException::class => ErrorSeverity::CRITICAL,
            \RedisException::class => ErrorSeverity::MEDIUM,
            \GuzzleHttp\Exception\RequestException::class => ErrorSeverity::MEDIUM,
            default => ErrorSeverity::HIGH
        };
    }

    private function logException(\Throwable $exception, ErrorContext $context, ErrorSeverity $severity): void
    {
        $level = match ($severity) {
            ErrorSeverity::LOW => 'info',
            ErrorSeverity::MEDIUM => 'warning',
            ErrorSeverity::HIGH => 'error',
            ErrorSeverity::CRITICAL => 'critical'
        };

        $this->logger->log($level, $exception->getMessage(), [
            'exception' => $exception,
            'context' => $context->toArray(),
            'severity' => $severity->value,
            'trace' => $exception->getTraceAsString()
        ]);
    }

    private function reportException(\Throwable $exception, ErrorContext $context, ErrorSeverity $severity): void
    {
        if ($severity->getPriority() >= ErrorSeverity::HIGH->getPriority()) {
            $this->reporter->report($exception, $context);
        }
    }

    private function collectMetrics(\Throwable $exception, ErrorSeverity $severity): void
    {
        $this->metrics->incrementCounter('exceptions_total', [
            'type' => get_class($exception),
            'severity' => $severity->value
        ]);

        $this->metrics->incrementCounter('exceptions_by_severity_total', [
            'severity' => $severity->value
        ]);
    }
}
```

### Observabilité selon HIVE035

Le Gyroscops Cloud implémente une observabilité complète selon l'ADR HIVE035 :

```php
// ✅ Observabilité Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveObservabilityService
{
    public function __construct(
        private LoggerInterface $logger,
        private MetricsCollector $metrics,
        private TracingService $tracing,
        private HealthChecker $healthChecker
    ) {}

    public function observeOperation(
        string $operationName,
        callable $operation,
        array $context = []
    ): mixed {
        $span = $this->tracing->startSpan($operationName, $context);
        
        try {
            $this->metrics->incrementCounter('operations_started_total', [
                'operation' => $operationName
            ]);

            $result = $operation();
            
            $this->metrics->incrementCounter('operations_completed_total', [
                'operation' => $operationName,
                'status' => 'success'
            ]);

            $this->tracing->addEvent($span, 'operation_completed', [
                'status' => 'success'
            ]);

            return $result;
        } catch (\Throwable $e) {
            $this->metrics->incrementCounter('operations_completed_total', [
                'operation' => $operationName,
                'status' => 'error'
            ]);

            $this->tracing->addEvent($span, 'operation_failed', [
                'status' => 'error',
                'error' => $e->getMessage()
            ]);

            throw $e;
        } finally {
            $this->tracing->finishSpan($span);
        }
    }

    public function observeDatabaseOperation(
        string $operationName,
        callable $operation,
        array $context = []
    ): mixed {
        return $this->observeOperation($operationName, function() use ($operation, $context) {
            $startTime = microtime(true);
            
            $this->logger->info('Database operation started', [
                'operation' => $operationName,
                'context' => $context
            ]);

            try {
                $result = $operation();
                
                $duration = microtime(true) - $startTime;
                
                $this->metrics->observeHistogram('database_operation_duration_seconds', $duration, [
                    'operation' => $operationName
                ]);

                $this->logger->info('Database operation completed', [
                    'operation' => $operationName,
                    'duration' => $duration
                ]);

                return $result;
            } catch (\Throwable $e) {
                $duration = microtime(true) - $startTime;
                
                $this->metrics->incrementCounter('database_operation_errors_total', [
                    'operation' => $operationName,
                    'error_type' => get_class($e)
                ]);

                $this->logger->error('Database operation failed', [
                    'operation' => $operationName,
                    'duration' => $duration,
                    'error' => $e->getMessage()
                ]);

                throw $e;
            }
        }, $context);
    }

    public function getSystemHealth(): HealthStatus
    {
        return $this->healthChecker->checkHealth();
    }
}
```

### Logging selon HIVE035

Le Gyroscops Cloud implémente un logging structuré selon l'ADR HIVE035 :

```php
// ✅ Logging Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveLogger
{
    public function __construct(
        private LoggerInterface $logger,
        private string $environment
    ) {}

    public function logOperation(
        string $level,
        string $operation,
        array $context = [],
        ?\Throwable $exception = null
    ): void {
        $logData = [
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'level' => $level,
            'operation' => $operation,
            'environment' => $this->environment,
            'context' => $this->sanitizeContext($context)
        ];

        if ($exception !== null) {
            $logData['exception'] = [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
        }

        $this->logger->log($level, $operation, $logData);
    }

    public function logDatabaseOperation(
        string $operation,
        string $query,
        array $parameters = [],
        ?float $duration = null,
        ?int $rowCount = null
    ): void {
        $this->logOperation('info', 'database_operation', [
            'query' => $query,
            'parameters' => $this->sanitizeParameters($parameters),
            'duration' => $duration,
            'row_count' => $rowCount
        ]);
    }

    public function logApiCall(
        string $method,
        string $url,
        int $statusCode,
        ?float $duration = null,
        ?string $responseBody = null
    ): void {
        $this->logOperation('info', 'api_call', [
            'method' => $method,
            'url' => $url,
            'status_code' => $statusCode,
            'duration' => $duration,
            'response_body' => $this->sanitizeResponseBody($responseBody)
        ]);
    }

    private function sanitizeContext(array $context): array
    {
        $sensitiveFields = ['password', 'token', 'secret', 'key', 'email', 'phone'];
        $sanitized = [];
        
        foreach ($context as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeContext($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    private function sanitizeParameters(array $parameters): array
    {
        return $this->sanitizeContext($parameters);
    }

    private function sanitizeResponseBody(?string $responseBody): ?string
    {
        if ($responseBody === null) {
            return null;
        }

        // Truncate large response bodies
        if (strlen($responseBody) > 1000) {
            return substr($responseBody, 0, 1000) . '... [TRUNCATED]';
        }

        return $responseBody;
    }
}
```

### Tests d'Observabilité selon HIVE027

Le Gyroscops Cloud teste l'observabilité selon les standards HIVE027 :

```php
// ✅ Tests d'Observabilité Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveObservabilityServiceTest extends TestCase
{
    private HiveObservabilityService $service;
    private LoggerInterface $logger;
    private MetricsCollector $metrics;
    private TracingService $tracing;
    private HealthChecker $healthChecker;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->metrics = $this->createMock(MetricsCollector::class);
        $this->tracing = $this->createMock(TracingService::class);
        $this->healthChecker = $this->createMock(HealthChecker::class);
        $this->service = new HiveObservabilityService(
            $this->logger,
            $this->metrics,
            $this->tracing,
            $this->healthChecker
        );
    }

    /** @test */
    public function itShouldObserveSuccessfulOperation(): void
    {
        // Arrange
        $operationName = 'test_operation';
        $context = ['key' => 'value'];
        $expectedResult = 'test_result';
        
        $span = $this->createMock(Span::class);
        
        $this->tracing->expects($this->once())
            ->method('startSpan')
            ->with($operationName, $context)
            ->willReturn($span);
        
        $this->metrics->expects($this->exactly(2))
            ->method('incrementCounter')
            ->withConsecutive(
                ['operations_started_total', ['operation' => $operationName]],
                ['operations_completed_total', ['operation' => $operationName, 'status' => 'success']]
            );
        
        $this->tracing->expects($this->once())
            ->method('addEvent')
            ->with($span, 'operation_completed', ['status' => 'success']);
        
        $this->tracing->expects($this->once())
            ->method('finishSpan')
            ->with($span);

        // Act
        $result = $this->service->observeOperation($operationName, fn() => $expectedResult, $context);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }

    /** @test */
    public function itShouldObserveFailedOperation(): void
    {
        // Arrange
        $operationName = 'test_operation';
        $context = ['key' => 'value'];
        $exception = new \RuntimeException('Test error');
        
        $span = $this->createMock(Span::class);
        
        $this->tracing->expects($this->once())
            ->method('startSpan')
            ->with($operationName, $context)
            ->willReturn($span);
        
        $this->metrics->expects($this->exactly(2))
            ->method('incrementCounter')
            ->withConsecutive(
                ['operations_started_total', ['operation' => $operationName]],
                ['operations_completed_total', ['operation' => $operationName, 'status' => 'error']]
            );
        
        $this->tracing->expects($this->once())
            ->method('addEvent')
            ->with($span, 'operation_failed', ['status' => 'error', 'error' => 'Test error']);
        
        $this->tracing->expects($this->once())
            ->method('finishSpan')
            ->with($span);

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test error');
        
        $this->service->observeOperation($operationName, fn() => throw $exception, $context);
    }

    /** @test */
    public function itShouldObserveDatabaseOperation(): void
    {
        // Arrange
        $operationName = 'test_db_operation';
        $context = ['table' => 'payments'];
        $expectedResult = [['id' => 1, 'name' => 'Payment 1']];
        
        $span = $this->createMock(Span::class);
        
        $this->tracing->expects($this->once())
            ->method('startSpan')
            ->with($operationName, $context)
            ->willReturn($span);
        
        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Database operation started', $this->isType('array')],
                ['Database operation completed', $this->isType('array')]
            );
        
        $this->metrics->expects($this->exactly(3))
            ->method('incrementCounter')
            ->withConsecutive(
                ['operations_started_total', ['operation' => $operationName]],
                ['operations_completed_total', ['operation' => $operationName, 'status' => 'success']]
            );
        
        $this->metrics->expects($this->once())
            ->method('observeHistogram')
            ->with('database_operation_duration_seconds', $this->isType('float'), [
                'operation' => $operationName
            ]);

        // Act
        $result = $this->service->observeDatabaseOperation(
            $operationName,
            fn() => $expectedResult,
            $context
        );

        // Assert
        $this->assertEquals($expectedResult, $result);
    }
}
```

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux explorer les tests et la qualité" 
    subtitle="Vous voulez améliorer la qualité de votre code" 
    criteria="Besoin de qualité de code,Tests complets,Couverture de code,Standards de qualité" 
    time="35-45 minutes" 
    chapter="61" 
    chapter-title="Tests et Qualité" 
    chapter-url="/chapitres/chapitre-23/" 
  >}}
  
  {{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux explorer la sécurité et l'autorisation" 
    subtitle="Vous voulez sécuriser votre application" 
    criteria="Besoin de sécurité,Gestion des permissions,Authentification,Autorisation fine" 
    time="40-50 minutes" 
    chapter="62" 
    chapter-title="Sécurité et Autorisation" 
    chapter-url="/chapitres/chapitre-24/" 
  >}}
  
  {{< chapter-option 
    letter="C" 
    color="red" 
    title="Je veux explorer le frontend et l'intégration" 
    subtitle="Vous voulez intégrer votre API avec un frontend" 
    criteria="Besoin d'intégration frontend,API Platform,React Admin,Interface utilisateur" 
    time="45-55 minutes" 
    chapter="63" 
    chapter-title="Frontend et Intégration" 
    chapter-url="/chapitres/chapitre-25/" 
  >}}
  
  {{< chapter-option 
    letter="D" 
    color="blue" 
    title="Je veux continuer avec la gestion d'erreurs" 
    subtitle="La gestion d'erreurs me convient parfaitement" 
    criteria="Application simple,Gestion d'erreurs basique,Observabilité simple,Pas de besoins complexes" 
    time="30-40 minutes" 
    chapter="26" 
    chapter-title="Déploiement et Production" 
    chapter-url="/chapitres/chapitre-26/" 
  >}}
  
{{< /chapter-nav >}}