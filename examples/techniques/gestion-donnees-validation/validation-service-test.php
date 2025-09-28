<?php

declare(strict_types=1);

namespace Examples\Techniques\GestionDonneesValidation;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Psr\Log\LoggerInterface;

/**
 * Tests du service de validation selon les standards HIVE027
 * 
 * Ces tests suivent les bonnes pratiques du projet Gyroscops Cloud
 * en respectant l'ADR HIVE027 pour les tests PHPUnit.
 */
final class ValidationServiceTest extends TestCase
{
    private ValidationService $service;
    private ValidatorInterface $validator;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new ValidationService($this->validator, $this->logger);
    }

    /** @test */
    public function itShouldValidateValidCreatePaymentCommand(): void
    {
        // Arrange
        $validData = [
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
            ->with($validData, $this->isType('array'))
            ->willReturn([]);

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Validating create payment command', ['data_keys' => array_keys($validData)]],
                ['Validation completed', $this->isType('array')]
            );

        // Act
        $result = $this->service->validateCreatePaymentCommand($validData);

        // Assert
        $this->assertTrue($result->isValid);
        $this->assertFalse($result->hasViolations());
        $this->assertEmpty($result->getViolationMessages());
    }

    /** @test */
    public function itShouldRejectInvalidCreatePaymentCommand(): void
    {
        // Arrange
        $invalidData = [
            'uuid' => 'invalid-uuid',
            'realm_id' => '0197b105-0c38-75e3-8cd0-32c57bd7f35c',
            'organization_id' => '0197b105-0c38-75e3-8cd0-32c57bd7f35d',
            'subscription_id' => '0197b105-0c38-75e3-8cd0-32c57bd7f35e',
            'creation_date' => '2024-01-01 10:00:00',
            'expiration_date' => '2024-01-31 10:00:00',
            'customer_name' => '', // Invalid: empty name
            'customer_email' => 'invalid-email', // Invalid: malformed email
            'status' => 'invalid-status', // Invalid: not in choices
            'subtotal' => 'invalid-amount', // Invalid: not matching regex
            'subtotal_currency' => 'INVALID', // Invalid: not in choices
            'discount' => '0.00',
            'discount_currency' => 'EUR',
            'taxes' => '20.00',
            'taxes_currency' => 'EUR',
            'total' => '120.00',
            'total_currency' => 'EUR'
        ];

        $violations = [
            new ConstraintViolation('This is not a valid UUID.', 'uuid', 'invalid-uuid', null, 'uuid', 'invalid-uuid'),
            new ConstraintViolation('This value should not be blank.', 'customer_name', '', null, 'customer_name', ''),
            new ConstraintViolation('This value is not a valid email address.', 'customer_email', 'invalid-email', null, 'customer_email', 'invalid-email'),
            new ConstraintViolation('The value you selected is not a valid choice.', 'status', 'invalid-status', null, 'status', 'invalid-status'),
            new ConstraintViolation('This value is not valid.', 'subtotal', 'invalid-amount', null, 'subtotal', 'invalid-amount'),
            new ConstraintViolation('The value you selected is not a valid choice.', 'subtotal_currency', 'INVALID', null, 'subtotal_currency', 'INVALID')
        ];

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($invalidData, $this->isType('array'))
            ->willReturn($violations);

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Validating create payment command', ['data_keys' => array_keys($invalidData)]],
                ['Validation completed', $this->isType('array')]
            );

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Validation failed', $this->isType('array'));

        // Act
        $result = $this->service->validateCreatePaymentCommand($invalidData);

        // Assert
        $this->assertFalse($result->isValid);
        $this->assertTrue($result->hasViolations());
        $this->assertCount(6, $result->getViolationMessages());
        
        $messagesByProperty = $result->getViolationMessagesByProperty();
        $this->assertArrayHasKey('uuid', $messagesByProperty);
        $this->assertArrayHasKey('customer_name', $messagesByProperty);
        $this->assertArrayHasKey('customer_email', $messagesByProperty);
        $this->assertArrayHasKey('status', $messagesByProperty);
        $this->assertArrayHasKey('subtotal', $messagesByProperty);
        $this->assertArrayHasKey('subtotal_currency', $messagesByProperty);
    }

    /** @test */
    public function itShouldValidateValidCreateSubscriptionCommand(): void
    {
        // Arrange
        $validData = [
            'uuid' => '0197b105-0c38-75e3-8cd0-32c57bd7f35b',
            'realm_id' => '0197b105-0c38-75e3-8cd0-32c57bd7f35c',
            'organization_id' => '0197b105-0c38-75e3-8cd0-32c57bd7f35d',
            'name' => 'Premium Subscription',
            'description' => 'Premium subscription with advanced features',
            'status' => 'active',
            'start_date' => '2024-01-01 10:00:00',
            'end_date' => '2024-12-31 23:59:59'
        ];

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($validData, $this->isType('array'))
            ->willReturn([]);

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Validating create subscription command', ['data_keys' => array_keys($validData)]],
                ['Validation completed', $this->isType('array')]
            );

        // Act
        $result = $this->service->validateCreateSubscriptionCommand($validData);

        // Assert
        $this->assertTrue($result->isValid);
        $this->assertFalse($result->hasViolations());
        $this->assertEmpty($result->getViolationMessages());
    }

    /** @test */
    public function itShouldRejectInvalidCreateSubscriptionCommand(): void
    {
        // Arrange
        $invalidData = [
            'uuid' => 'invalid-uuid',
            'realm_id' => '0197b105-0c38-75e3-8cd0-32c57bd7f35c',
            'organization_id' => '0197b105-0c38-75e3-8cd0-32c57bd7f35d',
            'name' => '', // Invalid: empty name
            'description' => str_repeat('a', 1001), // Invalid: too long
            'status' => 'invalid-status', // Invalid: not in choices
            'start_date' => 'invalid-date', // Invalid: not valid datetime
            'end_date' => '2024-12-31 23:59:59'
        ];

        $violations = [
            new ConstraintViolation('This is not a valid UUID.', 'uuid', 'invalid-uuid', null, 'uuid', 'invalid-uuid'),
            new ConstraintViolation('This value should not be blank.', 'name', '', null, 'name', ''),
            new ConstraintViolation('This value is too long.', 'description', str_repeat('a', 1001), null, 'description', str_repeat('a', 1001)),
            new ConstraintViolation('The value you selected is not a valid choice.', 'status', 'invalid-status', null, 'status', 'invalid-status'),
            new ConstraintViolation('This value is not a valid datetime.', 'start_date', 'invalid-date', null, 'start_date', 'invalid-date')
        ];

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($invalidData, $this->isType('array'))
            ->willReturn($violations);

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Validating create subscription command', ['data_keys' => array_keys($invalidData)]],
                ['Validation completed', $this->isType('array')]
            );

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Validation failed', $this->isType('array'));

        // Act
        $result = $this->service->validateCreateSubscriptionCommand($invalidData);

        // Assert
        $this->assertFalse($result->isValid);
        $this->assertTrue($result->hasViolations());
        $this->assertCount(5, $result->getViolationMessages());
        
        $messagesByProperty = $result->getViolationMessagesByProperty();
        $this->assertArrayHasKey('uuid', $messagesByProperty);
        $this->assertArrayHasKey('name', $messagesByProperty);
        $this->assertArrayHasKey('description', $messagesByProperty);
        $this->assertArrayHasKey('status', $messagesByProperty);
        $this->assertArrayHasKey('start_date', $messagesByProperty);
    }

    /** @test */
    public function itShouldValidateValidCreateOrganizationCommand(): void
    {
        // Arrange
        $validData = [
            'uuid' => '0197b105-0c38-75e3-8cd0-32c57bd7f35b',
            'realm_id' => '0197b105-0c38-75e3-8cd0-32c57bd7f35c',
            'name' => 'Acme Corporation',
            'description' => 'A leading technology company',
            'status' => 'active'
        ];

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($validData, $this->isType('array'))
            ->willReturn([]);

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Validating create organization command', ['data_keys' => array_keys($validData)]],
                ['Validation completed', $this->isType('array')]
            );

        // Act
        $result = $this->service->validateCreateOrganizationCommand($validData);

        // Assert
        $this->assertTrue($result->isValid);
        $this->assertFalse($result->hasViolations());
        $this->assertEmpty($result->getViolationMessages());
    }

    /** @test */
    public function itShouldRejectInvalidCreateOrganizationCommand(): void
    {
        // Arrange
        $invalidData = [
            'uuid' => 'invalid-uuid',
            'realm_id' => '0197b105-0c38-75e3-8cd0-32c57bd7f35c',
            'name' => '', // Invalid: empty name
            'description' => str_repeat('a', 1001), // Invalid: too long
            'status' => 'invalid-status' // Invalid: not in choices
        ];

        $violations = [
            new ConstraintViolation('This is not a valid UUID.', 'uuid', 'invalid-uuid', null, 'uuid', 'invalid-uuid'),
            new ConstraintViolation('This value should not be blank.', 'name', '', null, 'name', ''),
            new ConstraintViolation('This value is too long.', 'description', str_repeat('a', 1001), null, 'description', str_repeat('a', 1001)),
            new ConstraintViolation('The value you selected is not a valid choice.', 'status', 'invalid-status', null, 'status', 'invalid-status')
        ];

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($invalidData, $this->isType('array'))
            ->willReturn($violations);

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Validating create organization command', ['data_keys' => array_keys($invalidData)]],
                ['Validation completed', $this->isType('array')]
            );

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Validation failed', $this->isType('array'));

        // Act
        $result = $this->service->validateCreateOrganizationCommand($invalidData);

        // Assert
        $this->assertFalse($result->isValid);
        $this->assertTrue($result->hasViolations());
        $this->assertCount(4, $result->getViolationMessages());
        
        $messagesByProperty = $result->getViolationMessagesByProperty();
        $this->assertArrayHasKey('uuid', $messagesByProperty);
        $this->assertArrayHasKey('name', $messagesByProperty);
        $this->assertArrayHasKey('description', $messagesByProperty);
        $this->assertArrayHasKey('status', $messagesByProperty);
    }
}
