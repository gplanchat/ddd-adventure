<?php

declare(strict_types=1);

namespace Examples\Techniques\GestionErreursObservabilite;

use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Gestionnaire d'erreurs selon les bonnes pratiques du projet Gyroscops Cloud
 * 
 * Ce service implémente une gestion d'erreurs robuste
 * en respectant les ADR HIVE038 et HIVE035.
 */
final class ErrorHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private ErrorReporter $reporter,
        private MetricsCollector $metrics
    ) {}

    /**
     * Gère une exception de validation
     */
    public function handleValidationException(ValidationException $exception): array
    {
        $this->logger->warning('Validation exception occurred', [
            'error_code' => $exception->getErrorCode()->getCode(),
            'message' => $exception->getMessage(),
            'context' => $exception->getContext()->toArray(),
            'severity' => $exception->getSeverity()->value
        ]);

        $this->metrics->incrementCounter('validation_errors_total', [
            'error_code' => $exception->getErrorCode()->getCode(),
            'severity' => $exception->getSeverity()->value
        ]);

        return [
            'error' => $exception->getErrorCode()->getCode(),
            'message' => $exception->getMessage(),
            'context' => $exception->getContext()->toArray(),
            'severity' => $exception->getSeverity()->value
        ];
    }

    /**
     * Gère une exception de logique métier
     */
    public function handleBusinessLogicException(BusinessLogicException $exception): array
    {
        $this->logger->error('Business logic exception occurred', [
            'error_code' => $exception->getErrorCode()->getCode(),
            'message' => $exception->getMessage(),
            'context' => $exception->getContext()->toArray(),
            'severity' => $exception->getSeverity()->value
        ]);

        $this->metrics->incrementCounter('business_logic_errors_total', [
            'error_code' => $exception->getErrorCode()->getCode(),
            'severity' => $exception->getSeverity()->value
        ]);

        // Report to external service for high severity errors
        if ($exception->getSeverity()->getPriority() >= ErrorSeverity::HIGH->getPriority()) {
            $this->reporter->report($exception, $exception->getContext());
        }

        return [
            'error' => $exception->getErrorCode()->getCode(),
            'message' => $exception->getMessage(),
            'context' => $exception->getContext()->toArray(),
            'severity' => $exception->getSeverity()->value
        ];
    }

    /**
     * Gère une exception d'infrastructure
     */
    public function handleInfrastructureException(InfrastructureException $exception): array
    {
        $this->logger->error('Infrastructure exception occurred', [
            'error_code' => $exception->getErrorCode()->getCode(),
            'message' => $exception->getMessage(),
            'context' => $exception->getContext()->toArray(),
            'severity' => $exception->getSeverity()->value
        ]);

        $this->metrics->incrementCounter('infrastructure_errors_total', [
            'error_code' => $exception->getErrorCode()->getCode(),
            'severity' => $exception->getSeverity()->value
        ]);

        // Always report infrastructure errors
        $this->reporter->report($exception, $exception->getContext());

        return [
            'error' => $exception->getErrorCode()->getCode(),
            'message' => $exception->getMessage(),
            'context' => $exception->getContext()->toArray(),
            'severity' => $exception->getSeverity()->value
        ];
    }

    /**
     * Gère une exception générique
     */
    public function handleGenericException(\Throwable $exception): array
    {
        $this->logger->error('Generic exception occurred', [
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        $this->metrics->incrementCounter('generic_errors_total', [
            'exception_class' => get_class($exception)
        ]);

        // Report critical errors
        $this->reporter->report($exception, new ErrorContext([
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]));

        return [
            'error' => 'INTERNAL_ERROR',
            'message' => 'An internal error occurred',
            'severity' => 'high'
        ];
    }
}

/**
 * Exception de validation
 */
final class ValidationException extends \DomainException
{
    public function __construct(
        private ErrorCode $errorCode,
        private ErrorContext $context,
        string $message = 'Validation failed'
    ) {
        parent::__construct($message);
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

/**
 * Exception de logique métier
 */
final class BusinessLogicException extends \DomainException
{
    public function __construct(
        private ErrorCode $errorCode,
        private ErrorContext $context,
        string $message = 'Business logic error'
    ) {
        parent::__construct($message);
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

/**
 * Exception d'infrastructure
 */
final class InfrastructureException extends \DomainException
{
    public function __construct(
        private ErrorCode $errorCode,
        private ErrorContext $context,
        string $message = 'Infrastructure error'
    ) {
        parent::__construct($message);
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

/**
 * Code d'erreur
 */
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
}

/**
 * Sévérité d'erreur
 */
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

/**
 * Contexte d'erreur
 */
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

/**
 * Rapporteur d'erreurs
 */
interface ErrorReporter
{
    public function report(\Throwable $exception, ErrorContext $context): void;
}

/**
 * Collecteur de métriques
 */
interface MetricsCollector
{
    public function incrementCounter(string $name, array $labels = []): void;
}
