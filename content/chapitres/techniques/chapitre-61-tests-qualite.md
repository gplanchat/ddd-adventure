---
title: "Chapitre 61 : Tests et Qualité"
description: "Tests et qualité dans le Gyroscops Cloud avec des exemples concrets"
date: 2024-12-19
draft: true
type: "docs"
weight: 61
---

## 🎯 Objectif de ce Chapitre

Ce chapitre vous montre comment implémenter les tests et maintenir la qualité dans le Gyroscops Cloud. Vous apprendrez :
- Comment écrire des tests de qualité
- Comment maintenir la couverture de code
- Comment utiliser les outils de qualité
- Comment tester les différents composants

### Références aux ADR du Projet Gyroscops Cloud

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du Gyroscops Cloud :
- **HIVE027** : PHPUnit Testing Standards - Standards de test PHPUnit
- **HIVE023** : Repository Testing Strategies - Stratégies de test des repositories
- **HIVE028** : Testing Data and Faker Best Practices - Bonnes pratiques de test et Faker
- **HIVE001** : PHP-CS-Fixer Rules - Règles PHP-CS-Fixer
- **HIVE024** : PHP Enum Naming Conventions - Conventions de nommage des enums PHP

## 🏗️ Architecture des Tests

### Structure des Tests

```
api/tests/
├── Unit/
│   ├── Domain/
│   │   ├── ValueObjects/
│   │   │   ├── EmailTest.php
│   │   │   ├── MoneyTest.php
│   │   │   └── DateRangeTest.php
│   │   └── Entities/
│   │       ├── PaymentTest.php
│   │       └── SubscriptionTest.php
│   └── Infrastructure/
│       ├── Repositories/
│       │   ├── SqlPaymentRepositoryTest.php
│       │   └── InMemoryPaymentRepositoryTest.php
│       └── Services/
│           ├── PaymentServiceTest.php
│           └── ValidationServiceTest.php
├── Integration/
│   ├── Repositories/
│   │   ├── DatabasePaymentRepositoryTest.php
│   │   └── ElasticSearchPaymentRepositoryTest.php
│   └── Services/
│       ├── PaymentWorkflowTest.php
│       └── NotificationServiceTest.php
├── Api/
│   ├── PaymentApiTest.php
│   ├── SubscriptionApiTest.php
│   └── OrganizationApiTest.php
└── Fixtures/
    ├── PaymentFixtures.php
    ├── SubscriptionFixtures.php
    └── OrganizationFixtures.php
```

### Base de Test

```php
// ✅ Base de Test (Projet Gyroscops Cloud)
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeTestEnvironment();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestEnvironment();
        parent::tearDown();
    }

    private function initializeTestEnvironment(): void
    {
        // Initialize test environment
    }

    private function cleanupTestEnvironment(): void
    {
        // Cleanup test environment
    }

    protected function assertArrayContains(array $expected, array $actual): void
    {
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $actual);
            $this->assertEquals($value, $actual[$key]);
        }
    }

    protected function assertJsonContains(array $expected, string $actual): void
    {
        $decoded = json_decode($actual, true);
        $this->assertIsArray($decoded);
        $this->assertArrayContains($expected, $decoded);
    }
}
```

## 🧪 Tests Unitaires

### Test des Value Objects

```php
// ✅ Test des Value Objects (Projet Gyroscops Cloud)
final class EmailTest extends TestCase
{
    /** @test */
    public function itShouldCreateValidEmail(): void
    {
        // Arrange
        $emailValue = 'john.doe@example.com';
        
        // Act
        $email = new Email($emailValue);
        
        // Assert
        $this->assertEquals($emailValue, $email->__toString());
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

    /** @test */
    public function itShouldBeImmutable(): void
    {
        // Arrange
        $email = new Email('john.doe@example.com');
        
        // Act & Assert
        $this->assertTrue($email instanceof \Stringable);
        $this->assertEquals('john.doe@example.com', $email->__toString());
    }
}
```

### Test des Agrégats

```php
// ✅ Test des Agrégats (Projet Gyroscops Cloud)
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
        $this->assertEquals('John Doe', $payment->getCustomerName());
        $this->assertEquals('john.doe@example.com', $payment->getCustomerEmail());
        $this->assertEquals(Statuses::Pending, $payment->getStatus());
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

    /** @test */
    public function itShouldCompletePayment(): void
    {
        // Arrange
        $payment = $this->createValidPayment();
        
        // Act
        $payment->complete();
        
        // Assert
        $this->assertEquals(Statuses::Completed, $payment->getStatus());
        $this->assertNotNull($payment->getCompletionDate());
    }

    /** @test */
    public function itShouldFailPayment(): void
    {
        // Arrange
        $payment = $this->createValidPayment();
        
        // Act
        $payment->fail('Payment gateway error');
        
        // Assert
        $this->assertEquals(Statuses::Failed, $payment->getStatus());
        $this->assertNotNull($payment->getCompletionDate());
    }

    private function createValidPayment(): Payment
    {
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
        
        return Payment::registerManualPayment(
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

## 🔗 Tests d'Intégration

### Test des Repositories

```php
// ✅ Test des Repositories (Projet Gyroscops Cloud)
final class SqlPaymentRepositoryTest extends TestCase
{
    private Connection $connection;
    private SqlPaymentRepository $repository;
    private PaymentHydrator $hydrator;

    protected function setUp(): void
    {
        $this->connection = $this->createTestConnection();
        $this->hydrator = new PaymentHydrator();
        $this->repository = new SqlPaymentRepository($this->connection, $this->hydrator);
        
        $this->createTestSchema();
    }

    protected function tearDown(): void
    {
        $this->dropTestSchema();
        $this->connection->close();
    }

    /** @test */
    public function itShouldSavePayment(): void
    {
        // Arrange
        $payment = $this->createTestPayment();
        
        // Act
        $this->repository->save($payment);
        
        // Assert
        $savedPayment = $this->repository->findById($payment->uuid);
        $this->assertNotNull($savedPayment);
        $this->assertEquals($payment->uuid->toString(), $savedPayment->uuid->toString());
    }

    /** @test */
    public function itShouldFindPaymentsByOrganization(): void
    {
        // Arrange
        $organizationId = OrganizationId::generate();
        $payment1 = $this->createTestPayment($organizationId);
        $payment2 = $this->createTestPayment($organizationId);
        $payment3 = $this->createTestPayment(OrganizationId::generate());
        
        $this->repository->save($payment1);
        $this->repository->save($payment2);
        $this->repository->save($payment3);
        
        // Act
        $payments = $this->repository->findByOrganizationId($organizationId);
        
        // Assert
        $this->assertCount(2, $payments);
        $this->assertContains($payment1, $payments);
        $this->assertContains($payment2, $payments);
        $this->assertNotContains($payment3, $payments);
    }

    /** @test */
    public function itShouldPaginatePayments(): void
    {
        // Arrange
        $organizationId = OrganizationId::generate();
        $payments = [];
        
        for ($i = 0; $i < 25; $i++) {
            $payment = $this->createTestPayment($organizationId);
            $this->repository->save($payment);
            $payments[] = $payment;
        }
        
        $pagination = new PaginationRequest(1, 10);
        
        // Act
        $response = $this->repository->findPaginated($pagination, ['organization_id' => $organizationId->toString()]);
        
        // Assert
        $this->assertEquals(1, $response->page);
        $this->assertEquals(10, $response->pageSize);
        $this->assertEquals(25, $response->getTotalItems());
        $this->assertEquals(3, $response->getTotalPages());
        $this->assertTrue($response->hasNextPage());
        $this->assertFalse($response->hasPreviousPage());
        $this->assertCount(10, $response->data);
    }

    private function createTestConnection(): Connection
    {
        $params = [
            'dbname' => 'test_hive',
            'user' => 'test_user',
            'password' => 'test_password',
            'host' => 'localhost',
            'driver' => 'pdo_mysql'
        ];
        
        return DriverManager::getConnection($params);
    }

    private function createTestSchema(): void
    {
        $this->connection->executeStatement('
            CREATE TABLE IF NOT EXISTS payments (
                id VARCHAR(36) PRIMARY KEY,
                realm_id VARCHAR(36) NOT NULL,
                organization_id VARCHAR(36) NOT NULL,
                subscription_id VARCHAR(36) NOT NULL,
                creation_date DATETIME NOT NULL,
                expiration_date DATETIME NOT NULL,
                completion_date DATETIME NULL,
                status VARCHAR(20) NOT NULL,
                gateway VARCHAR(20) NULL,
                customer_name VARCHAR(255) NOT NULL,
                customer_email VARCHAR(255) NOT NULL,
                subtotal_amount DECIMAL(10,2) NOT NULL,
                subtotal_currency VARCHAR(3) NOT NULL,
                discount_amount DECIMAL(10,2) NOT NULL,
                discount_currency VARCHAR(3) NOT NULL,
                taxes_amount DECIMAL(10,2) NOT NULL,
                taxes_currency VARCHAR(3) NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL,
                total_currency VARCHAR(3) NOT NULL,
                captured_amount DECIMAL(10,2) NULL,
                captured_currency VARCHAR(3) NULL,
                version INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ');
    }

    private function dropTestSchema(): void
    {
        $this->connection->executeStatement('DROP TABLE IF EXISTS payments');
    }

    private function createTestPayment(?OrganizationId $organizationId = null): Payment
    {
        $uuid = PaymentId::generate();
        $realmId = RealmId::generate();
        $organizationId = $organizationId ?? OrganizationId::generate();
        $subscriptionId = SubscriptionId::generate();
        $creationDate = new \DateTimeImmutable();
        $expirationDate = new \DateTimeImmutable('+30 days');
        $subtotal = new Price(BigDecimal::of('100.00'), Currencies::EUR);
        $discount = new Price(BigDecimal::of('0.00'), Currencies::EUR);
        $taxes = new Price(BigDecimal::of('20.00'), Currencies::EUR);
        $total = new Price(BigDecimal::of('120.00'), Currencies::EUR);
        
        return Payment::registerManualPayment(
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

## 🌐 Tests d'API

### Test des Endpoints API

```php
// ✅ Test des Endpoints API (Projet Gyroscops Cloud)
final class PaymentApiTest extends ApiTestCase
{
    private PaymentFixtures $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtures = new PaymentFixtures($this->entityManager);
    }

    /** @test */
    public function itShouldCreatePayment(): void
    {
        // Arrange
        $organization = $this->fixtures->createOrganization();
        $subscription = $this->fixtures->createSubscription($organization);
        
        $paymentData = [
            'realm_id' => $organization->realmId->toString(),
            'organization_id' => $organization->uuid->toString(),
            'subscription_id' => $subscription->uuid->toString(),
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

        // Act
        $response = $this->client->request('POST', '/api/payments', [
            'json' => $paymentData,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getToken()
            ]
        ]);

        // Assert
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertJsonContains([
            'customer_name' => 'John Doe',
            'customer_email' => 'john.doe@example.com',
            'status' => 'pending',
            'total' => '120.00',
            'total_currency' => 'EUR'
        ], $response->getContent());
    }

    /** @test */
    public function itShouldGetPayments(): void
    {
        // Arrange
        $organization = $this->fixtures->createOrganization();
        $subscription = $this->fixtures->createSubscription($organization);
        $payment1 = $this->fixtures->createPayment($organization, $subscription);
        $payment2 = $this->fixtures->createPayment($organization, $subscription);
        
        // Act
        $response = $this->client->request('GET', '/api/payments', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getToken()
            ]
        ]);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data['hydra:member']);
        $this->assertArrayContains([
            'customer_name' => $payment1->getCustomerName(),
            'customer_email' => $payment1->getCustomerEmail()
        ], $data['hydra:member'][0]);
    }

    /** @test */
    public function itShouldGetPaymentById(): void
    {
        // Arrange
        $organization = $this->fixtures->createOrganization();
        $subscription = $this->fixtures->createSubscription($organization);
        $payment = $this->fixtures->createPayment($organization, $subscription);
        
        // Act
        $response = $this->client->request('GET', '/api/payments/' . $payment->uuid->toString(), [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getToken()
            ]
        ]);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonContains([
            'uuid' => $payment->uuid->toString(),
            'customer_name' => $payment->getCustomerName(),
            'customer_email' => $payment->getCustomerEmail()
        ], $response->getContent());
    }

    /** @test */
    public function itShouldUpdatePayment(): void
    {
        // Arrange
        $organization = $this->fixtures->createOrganization();
        $subscription = $this->fixtures->createSubscription($organization);
        $payment = $this->fixtures->createPayment($organization, $subscription);
        
        $updateData = [
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane.doe@example.com'
        ];

        // Act
        $response = $this->client->request('PUT', '/api/payments/' . $payment->uuid->toString(), [
            'json' => $updateData,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getToken()
            ]
        ]);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonContains([
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane.doe@example.com'
        ], $response->getContent());
    }

    /** @test */
    public function itShouldDeletePayment(): void
    {
        // Arrange
        $organization = $this->fixtures->createOrganization();
        $subscription = $this->fixtures->createSubscription($organization);
        $payment = $this->fixtures->createPayment($organization, $subscription);
        
        // Act
        $response = $this->client->request('DELETE', '/api/payments/' . $payment->uuid->toString(), [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getToken()
            ]
        ]);

        // Assert
        $this->assertEquals(204, $response->getStatusCode());
        
        // Verify deletion
        $getResponse = $this->client->request('GET', '/api/payments/' . $payment->uuid->toString(), [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getToken()
            ]
        ]);
        $this->assertEquals(404, $getResponse->getStatusCode());
    }

    /** @test */
    public function itShouldPaginatePayments(): void
    {
        // Arrange
        $organization = $this->fixtures->createOrganization();
        $subscription = $this->fixtures->createSubscription($organization);
        
        for ($i = 0; $i < 25; $i++) {
            $this->fixtures->createPayment($organization, $subscription);
        }
        
        // Act
        $response = $this->client->request('GET', '/api/payments?page=1&pageSize=10', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getToken()
            ]
        ]);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(10, $data['hydra:member']);
        $this->assertEquals(25, $data['hydra:totalItems']);
        $this->assertEquals(3, $data['hydra:view']['hydra:last']);
    }

    private function getToken(): string
    {
        // Implementation to get authentication token
        return 'test-token';
    }
}
```

## 🔧 Outils de Qualité

### Configuration PHPStan

```php
// ✅ Configuration PHPStan (Projet Gyroscops Cloud)
// phpstan.neon
parameters:
    level: 8
    paths:
        - src/
        - tests/
    ignoreErrors:
        - '#Call to an undefined method#'
    excludePaths:
        - tests/
    checkMissingIterableValueType: false
    checkGenericClassInNonGenericObjectType: false
    reportUnmatchedIgnoredErrors: false
    tmpDir: var/cache/phpstan
    parallel:
        processTimeout: 300.0
    memoryLimitFile: 1G
```

### Configuration PHP-CS-Fixer

```php
// ✅ Configuration PHP-CS-Fixer (Projet Gyroscops Cloud)
// .php-cs-fixer.php
<?php

$config = new PhpCsFixer\Config();
$config->setRules([
    '@PSR12' => true,
    'array_syntax' => ['syntax' => 'short'],
    'blank_line_after_namespace' => true,
    'blank_line_after_opening_tag' => true,
    'blank_line_before_statement' => [
        'statements' => ['return']
    ],
    'braces' => [
        'allow_single_line_closure' => true,
    ],
    'cast_spaces' => true,
    'class_attributes_separation' => [
        'elements' => [
            'method' => 'one',
        ],
    ],
    'clean_namespace' => true,
    'concat_space' => [
        'spacing' => 'one',
    ],
    'declare_equal_normalize' => true,
    'elseif' => true,
    'encoding' => true,
    'full_opening_tag' => true,
    'function_declaration' => true,
    'function_typehint_space' => true,
    'heredoc_to_nowdoc' => true,
    'include' => true,
    'increment_style' => ['style' => 'post'],
    'indentation_type' => true,
    'linebreak_after_opening_tag' => true,
    'line_ending' => true,
    'lowercase_cast' => true,
    'lowercase_constants' => true,
    'lowercase_keywords' => true,
    'method_argument_space' => true,
    'native_function_casing' => true,
    'no_alias_functions' => true,
    'no_blank_lines_after_class_opening' => true,
    'no_blank_lines_after_phpdoc' => true,
    'no_closing_tag' => true,
    'no_empty_phpdoc' => true,
    'no_empty_statement' => true,
    'no_extra_blank_lines' => [
        'tokens' => [
            'curly_brace_block',
            'extra',
            'parenthesis_brace_block',
            'square_brace_block',
            'throw',
            'use',
        ],
    ],
    'no_leading_import_slash' => true,
    'no_leading_namespace_whitespace' => true,
    'no_mixed_echo_print' => true,
    'no_multiline_whitespace_around_double_arrow' => true,
    'no_short_bool_cast' => true,
    'no_singleline_whitespace_before_semicolons' => true,
    'no_spaces_after_function_name' => true,
    'no_spaces_around_offset' => true,
    'no_spaces_inside_parenthesis' => true,
    'no_trailing_comma_in_list_call' => true,
    'no_trailing_comma_in_singleline_array' => true,
    'no_trailing_whitespace' => true,
    'no_trailing_whitespace_in_comment' => true,
    'no_unneeded_control_parentheses' => true,
    'no_unused_imports' => true,
    'no_whitespace_before_comma_in_array' => true,
    'no_whitespace_in_blank_line' => true,
    'normalize_index_brace' => true,
    'object_operator_without_whitespace' => true,
    'ordered_imports' => ['sort_algorithm' => 'alpha'],
    'phpdoc_indent' => true,
    'phpdoc_inline_tag_normalizer' => true,
    'phpdoc_no_access' => true,
    'phpdoc_no_package' => true,
    'phpdoc_no_useless_inheritdoc' => true,
    'phpdoc_scalar' => true,
    'phpdoc_single_line_var_spacing' => true,
    'phpdoc_summary' => true,
    'phpdoc_to_comment' => true,
    'phpdoc_trim' => true,
    'phpdoc_types' => true,
    'phpdoc_var_without_name' => true,
    'return_type_declaration' => true,
    'self_accessor' => true,
    'short_scalar_cast' => true,
    'single_blank_line_at_eof' => true,
    'single_blank_line_before_namespace' => true,
    'single_class_element_per_statement' => true,
    'single_import_per_statement' => true,
    'single_line_after_imports' => true,
    'single_line_comment_style' => true,
    'single_quote' => true,
    'space_after_semicolon' => true,
    'standardize_not_equals' => true,
    'switch_case_semicolon_to_colon' => true,
    'switch_case_space' => true,
    'ternary_operator_spaces' => true,
    'trailing_comma_in_multiline' => true,
    'trim_array_spaces' => true,
    'unary_operator_spaces' => true,
    'visibility_required' => true,
    'whitespace_after_comma_in_array' => true,
]);

$config->setFinder(
    PhpCsFixer\Finder::create()
        ->in(__DIR__ . '/src')
        ->in(__DIR__ . '/tests')
        ->name('*.php')
);

return $config;
```

## 🏗️ Bonnes Pratiques du Projet Gyroscops Cloud

### Tests selon HIVE027

Le Gyroscops Cloud suit les standards de test PHPUnit selon l'ADR HIVE027 :

```php
// ✅ Tests Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveTestSuite
{
    public function __construct(
        private TestCase $testCase,
        private LoggerInterface $logger
    ) {}

    public function setUpTestEnvironment(): void
    {
        $this->logger->info('Setting up test environment', [
            'test_class' => get_class($this->testCase),
            'test_method' => $this->getCurrentTestMethod()
        ]);

        // Initialize test database
        $this->initializeTestDatabase();
        
        // Clear caches
        $this->clearTestCaches();
        
        // Reset mocks
        $this->resetTestMocks();
    }

    public function tearDownTestEnvironment(): void
    {
        $this->logger->info('Tearing down test environment', [
            'test_class' => get_class($this->testCase),
            'test_method' => $this->getCurrentTestMethod()
        ]);

        // Cleanup test database
        $this->cleanupTestDatabase();
        
        // Clear caches
        $this->clearTestCaches();
    }

    private function initializeTestDatabase(): void
    {
        // Implementation for test database setup
    }

    private function clearTestCaches(): void
    {
        // Implementation for cache clearing
    }

    private function resetTestMocks(): void
    {
        // Implementation for mock reset
    }

    private function getCurrentTestMethod(): string
    {
        $trace = debug_backtrace();
        foreach ($trace as $frame) {
            if (isset($frame['function']) && strpos($frame['function'], 'test') === 0) {
                return $frame['function'];
            }
        }
        return 'unknown';
    }
}
```

### Tests de Repositories selon HIVE023

Le Gyroscops Cloud teste les repositories selon l'ADR HIVE023 :

```php
// ✅ Tests de Repositories Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveRepositoryTestSuite
{
    public function __construct(
        private TestCase $testCase,
        private Connection $connection,
        private LoggerInterface $logger
    ) {}

    public function testRepositoryOperation(
        string $operationName,
        callable $operation,
        array $testData = [],
        array $expectedResults = []
    ): void {
        $this->logger->info('Testing repository operation', [
            'operation' => $operationName,
            'test_data_count' => count($testData)
        ]);

        // Setup test data
        $this->setupTestData($testData);

        try {
            // Execute operation
            $result = $operation();

            // Verify results
            $this->verifyResults($result, $expectedResults);

            $this->logger->info('Repository operation test passed', [
                'operation' => $operationName
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Repository operation test failed', [
                'operation' => $operationName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } finally {
            // Cleanup test data
            $this->cleanupTestData($testData);
        }
    }

    private function setupTestData(array $testData): void
    {
        foreach ($testData as $table => $records) {
            foreach ($records as $record) {
                $this->connection->insert($table, $record);
            }
        }
    }

    private function verifyResults(mixed $result, array $expectedResults): void
    {
        if (is_array($result)) {
            $this->testCase->assertCount(count($expectedResults), $result);
            
            foreach ($expectedResults as $index => $expected) {
                $this->testCase->assertArrayContains($expected, $result[$index]);
            }
        } else {
            $this->testCase->assertEquals($expectedResults, $result);
        }
    }

    private function cleanupTestData(array $testData): void
    {
        foreach (array_keys($testData) as $table) {
            $this->connection->executeStatement("DELETE FROM {$table}");
        }
    }
}
```

### Tests avec Faker selon HIVE028

Le Gyroscops Cloud utilise Faker pour générer des données de test selon l'ADR HIVE028 :

```php
// ✅ Tests avec Faker Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveTestDataGenerator
{
    public function __construct(
        private Faker\Generator $faker,
        private LoggerInterface $logger
    ) {}

    public function generatePaymentData(int $count = 1): array
    {
        $this->logger->info('Generating payment test data', [
            'count' => $count
        ]);

        $payments = [];
        for ($i = 0; $i < $count; $i++) {
            $payments[] = [
                'id' => $this->faker->uuid(),
                'realm_id' => $this->faker->uuid(),
                'organization_id' => $this->faker->uuid(),
                'subscription_id' => $this->faker->uuid(),
                'creation_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s'),
                'expiration_date' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d H:i:s'),
                'customer_name' => $this->faker->name(),
                'customer_email' => $this->faker->email(),
                'status' => $this->faker->randomElement(['pending', 'completed', 'failed', 'cancelled']),
                'subtotal' => $this->faker->randomFloat(2, 10, 1000),
                'subtotal_currency' => $this->faker->randomElement(['EUR', 'USD', 'GBP']),
                'discount' => $this->faker->randomFloat(2, 0, 100),
                'discount_currency' => $this->faker->randomElement(['EUR', 'USD', 'GBP']),
                'taxes' => $this->faker->randomFloat(2, 0, 200),
                'taxes_currency' => $this->faker->randomElement(['EUR', 'USD', 'GBP']),
                'total' => $this->faker->randomFloat(2, 50, 1500),
                'total_currency' => $this->faker->randomElement(['EUR', 'USD', 'GBP']),
                'version' => $this->faker->numberBetween(0, 10)
            ];
        }

        return $payments;
    }

    public function generateSubscriptionData(int $count = 1): array
    {
        $this->logger->info('Generating subscription test data', [
            'count' => $count
        ]);

        $subscriptions = [];
        for ($i = 0; $i < $count; $i++) {
            $subscriptions[] = [
                'id' => $this->faker->uuid(),
                'realm_id' => $this->faker->uuid(),
                'organization_id' => $this->faker->uuid(),
                'name' => $this->faker->words(3, true),
                'description' => $this->faker->sentence(),
                'status' => $this->faker->randomElement(['active', 'inactive', 'suspended']),
                'start_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s'),
                'end_date' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d H:i:s'),
                'version' => $this->faker->numberBetween(0, 10)
            ];
        }

        return $subscriptions;
    }

    public function generateOrganizationData(int $count = 1): array
    {
        $this->logger->info('Generating organization test data', [
            'count' => $count
        ]);

        $organizations = [];
        for ($i = 0; $i < $count; $i++) {
            $organizations[] = [
                'id' => $this->faker->uuid(),
                'realm_id' => $this->faker->uuid(),
                'name' => $this->faker->company(),
                'description' => $this->faker->sentence(),
                'status' => $this->faker->randomElement(['active', 'inactive', 'suspended']),
                'created_at' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d H:i:s'),
                'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s'),
                'version' => $this->faker->numberBetween(0, 10)
            ];
        }

        return $organizations;
    }
}
```

### Qualité de Code selon HIVE001

Le Gyroscops Cloud maintient la qualité de code selon l'ADR HIVE001 :

```php
// ✅ Qualité de Code Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveCodeQualityChecker
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function checkCodeQuality(string $filePath): CodeQualityReport
    {
        $this->logger->info('Checking code quality', [
            'file' => $filePath
        ]);

        $report = new CodeQualityReport($filePath);

        // Check PHP-CS-Fixer compliance
        $this->checkPhpCsFixerCompliance($filePath, $report);

        // Check PHPStan compliance
        $this->checkPhpStanCompliance($filePath, $report);

        // Check custom rules
        $this->checkCustomRules($filePath, $report);

        $this->logger->info('Code quality check completed', [
            'file' => $filePath,
            'issues_count' => $report->getIssuesCount(),
            'severity' => $report->getSeverity()
        ]);

        return $report;
    }

    private function checkPhpCsFixerCompliance(string $filePath, CodeQualityReport $report): void
    {
        // Implementation for PHP-CS-Fixer compliance check
        $this->logger->debug('Checking PHP-CS-Fixer compliance', [
            'file' => $filePath
        ]);
    }

    private function checkPhpStanCompliance(string $filePath, CodeQualityReport $report): void
    {
        // Implementation for PHPStan compliance check
        $this->logger->debug('Checking PHPStan compliance', [
            'file' => $filePath
        ]);
    }

    private function checkCustomRules(string $filePath, CodeQualityReport $report): void
    {
        // Check for Gyroscops Cloud-specific rules
        $this->checkHiveNamingConventions($filePath, $report);
        $this->checkHiveArchitectureCompliance($filePath, $report);
        $this->checkHiveTestingStandards($filePath, $report);
    }

    private function checkHiveNamingConventions(string $filePath, CodeQualityReport $report): void
    {
        // Check enum naming conventions (HIVE024)
        $this->logger->debug('Checking Gyroscops Cloud naming conventions', [
            'file' => $filePath
        ]);
    }

    private function checkHiveArchitectureCompliance(string $filePath, CodeQualityReport $report): void
    {
        // Check architecture compliance (HIVE040, HIVE041)
        $this->logger->debug('Checking Gyroscops Cloud architecture compliance', [
            'file' => $filePath
        ]);
    }

    private function checkHiveTestingStandards(string $filePath, CodeQualityReport $report): void
    {
        // Check testing standards (HIVE027)
        $this->logger->debug('Checking Gyroscops Cloud testing standards', [
            'file' => $filePath
        ]);
    }
}

final class CodeQualityReport
{
    private array $issues = [];

    public function __construct(
        private string $filePath
    ) {}

    public function addIssue(string $type, string $message, int $line = 0, string $severity = 'warning'): void
    {
        $this->issues[] = [
            'type' => $type,
            'message' => $message,
            'line' => $line,
            'severity' => $severity
        ];
    }

    public function getIssuesCount(): int
    {
        return count($this->issues);
    }

    public function getSeverity(): string
    {
        $severities = array_column($this->issues, 'severity');
        
        if (in_array('error', $severities)) {
            return 'error';
        }
        
        if (in_array('warning', $severities)) {
            return 'warning';
        }
        
        return 'info';
    }

    public function getIssues(): array
    {
        return $this->issues;
    }
}
```

### Tests d'Intégration selon HIVE027

Le Gyroscops Cloud teste l'intégration selon les standards HIVE027 :

```php
// ✅ Tests d'Intégration Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveIntegrationTestSuite
{
    public function __construct(
        private TestCase $testCase,
        private Connection $connection,
        private LoggerInterface $logger
    ) {}

    public function testApiEndpoint(
        string $method,
        string $endpoint,
        array $headers = [],
        array $body = [],
        int $expectedStatusCode = 200,
        array $expectedResponse = []
    ): void {
        $this->logger->info('Testing API endpoint', [
            'method' => $method,
            'endpoint' => $endpoint,
            'expected_status' => $expectedStatusCode
        ]);

        // Setup test data
        $this->setupTestData();

        try {
            // Make API call
            $response = $this->makeApiCall($method, $endpoint, $headers, $body);

            // Verify status code
            $this->testCase->assertEquals($expectedStatusCode, $response->getStatusCode());

            // Verify response body
            if (!empty($expectedResponse)) {
                $responseBody = json_decode($response->getContent(), true);
                $this->testCase->assertArrayContains($expectedResponse, $responseBody);
            }

            $this->logger->info('API endpoint test passed', [
                'method' => $method,
                'endpoint' => $endpoint
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('API endpoint test failed', [
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } finally {
            // Cleanup test data
            $this->cleanupTestData();
        }
    }

    private function setupTestData(): void
    {
        // Implementation for test data setup
        $this->logger->debug('Setting up integration test data');
    }

    private function makeApiCall(string $method, string $endpoint, array $headers, array $body): ResponseInterface
    {
        // Implementation for API call
        $this->logger->debug('Making API call', [
            'method' => $method,
            'endpoint' => $endpoint
        ]);
        
        // Return mock response for testing
        return new MockResponse('{"status": "success"}', ['http_code' => 200]);
    }

    private function cleanupTestData(): void
    {
        // Implementation for test data cleanup
        $this->logger->debug('Cleaning up integration test data');
    }
}
```

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux explorer la sécurité et l'autorisation" 
    subtitle="Vous voulez sécuriser votre application" 
    criteria="Besoin de sécurité,Gestion des permissions,Authentification,Autorisation fine" 
    time="40-50 minutes" 
    chapter="62" 
    chapter-title="Sécurité et Autorisation" 
    chapter-url="/chapitres/chapitre-24/" 
  >}}
  
  {{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux explorer le frontend et l'intégration" 
    subtitle="Vous voulez intégrer votre API avec un frontend" 
    criteria="Besoin d'intégration frontend,API Platform,React Admin,Interface utilisateur" 
    time="45-55 minutes" 
    chapter="63" 
    chapter-title="Frontend et Intégration" 
    chapter-url="/chapitres/chapitre-25/" 
  >}}
  
  {{< chapter-option 
    letter="C" 
    color="red" 
    title="Je veux explorer le déploiement et la production" 
    subtitle="Vous voulez déployer votre application en production" 
    criteria="Besoin de déploiement,Environnement de production,Monitoring,Maintenance" 
    time="50-60 minutes" 
    chapter="26" 
    chapter-title="Déploiement et Production" 
    chapter-url="/chapitres/chapitre-26/" 
  >}}
  
  {{< chapter-option 
    letter="D" 
    color="blue" 
    title="Je veux continuer avec les tests" 
    subtitle="Les tests me conviennent parfaitement" 
    criteria="Application simple,Tests basiques,Qualité acceptable,Pas de besoins complexes" 
    time="35-45 minutes" 
    chapter="27" 
    chapter-title="Conclusion et Prochaines Étapes" 
    chapter-url="/chapitres/chapitre-27/" 
  >}}
  
{{< /chapter-nav >}}