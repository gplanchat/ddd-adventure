---
title: "Stockage SQL Classique - Exemple Concret"
description: "Implémentation concrète du pattern de stockage SQL classique avec Doctrine"
date: 2024-12-19
draft: false
type: "docs"
weight: 1
---

## 🎯 **Contexte de l'Exemple**

Cet exemple montre l'implémentation d'un **Repository SQL classique** pour la gestion des paiements dans l'écosystème Gyroscops. Il illustre les concepts de base du DDD avec une approche simple et efficace.

### **Domaine Métier : Gestion des Paiements**

- **Entité** : `Payment` (paiement)
- **Repository** : `PaymentRepository` (gestion de la persistance)
- **Tests** : Validation du comportement

## 📁 **Structure du Projet**

```
src/
├── Domain/
│   └── Payment/
│       ├── Entity/
│       │   └── Payment.php
│       └── Repository/
│           └── PaymentRepositoryInterface.php
├── Infrastructure/
│   └── Payment/
│       └── Repository/
│           └── DoctrinePaymentRepository.php
└── Tests/
    └── Infrastructure/
        └── Payment/
            └── Repository/
                └── DoctrinePaymentRepositoryTest.php
```

## 🏗️ **Implémentation**

### **1. Entité Payment**

```php
<?php

namespace App\Domain\Payment\Entity;

use App\Domain\Payment\ValueObject\PaymentId;
use App\Domain\Payment\ValueObject\Amount;
use App\Domain\Payment\ValueObject\Currency;
use App\Domain\Payment\ValueObject\PaymentStatus;
use DateTimeImmutable;

class Payment
{
    public function __construct(
        private PaymentId $id,
        private Amount $amount,
        private Currency $currency,
        private PaymentStatus $status,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $paidAt = null
    ) {}

    public function getId(): PaymentId
    {
        return $this->id;
    }

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPaidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function markAsPaid(): void
    {
        $this->status = PaymentStatus::PAID;
        $this->paidAt = new DateTimeImmutable();
    }
}
```

### **2. Interface du Repository**

```php
<?php

namespace App\Domain\Payment\Repository;

use App\Domain\Payment\Entity\Payment;
use App\Domain\Payment\ValueObject\PaymentId;

interface PaymentRepositoryInterface
{
    public function save(Payment $payment): void;
    public function findById(PaymentId $id): ?Payment;
    public function findByStatus(PaymentStatus $status): array;
    public function delete(Payment $payment): void;
}
```

### **3. Implémentation Doctrine**

```php
<?php

namespace App\Infrastructure\Payment\Repository;

use App\Domain\Payment\Entity\Payment;
use App\Domain\Payment\Repository\PaymentRepositoryInterface;
use App\Domain\Payment\ValueObject\PaymentId;
use App\Domain\Payment\ValueObject\PaymentStatus;
use Doctrine\ORM\EntityManagerInterface;

class DoctrinePaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function save(Payment $payment): void
    {
        $this->entityManager->persist($payment);
        $this->entityManager->flush();
    }

    public function findById(PaymentId $id): ?Payment
    {
        return $this->entityManager
            ->getRepository(Payment::class)
            ->find($id->getValue());
    }

    public function findByStatus(PaymentStatus $status): array
    {
        return $this->entityManager
            ->getRepository(Payment::class)
            ->findBy(['status' => $status->getValue()]);
    }

    public function delete(Payment $payment): void
    {
        $this->entityManager->remove($payment);
        $this->entityManager->flush();
    }
}
```

## 🧪 **Tests**

### **Test du Repository**

```php
<?php

namespace App\Tests\Infrastructure\Payment\Repository;

use App\Domain\Payment\Entity\Payment;
use App\Domain\Payment\ValueObject\Amount;
use App\Domain\Payment\ValueObject\Currency;
use App\Domain\Payment\ValueObject\PaymentId;
use App\Domain\Payment\ValueObject\PaymentStatus;
use App\Infrastructure\Payment\Repository\DoctrinePaymentRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DoctrinePaymentRepositoryTest extends TestCase
{
    private DoctrinePaymentRepository $repository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = new DoctrinePaymentRepository($this->entityManager);
    }

    public function testSavePayment(): void
    {
        // Arrange
        $payment = new Payment(
            PaymentId::generate(),
            new Amount(10000), // 100.00 EUR
            new Currency('EUR'),
            PaymentStatus::PENDING,
            new DateTimeImmutable()
        );

        // Expect
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($payment);
        
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $this->repository->save($payment);
    }

    public function testFindById(): void
    {
        // Arrange
        $paymentId = PaymentId::generate();
        $expectedPayment = new Payment(
            $paymentId,
            new Amount(10000),
            new Currency('EUR'),
            PaymentStatus::PENDING,
            new DateTimeImmutable()
        );

        $repositoryMock = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $repositoryMock->expects($this->once())
            ->method('find')
            ->with($paymentId->getValue())
            ->willReturn($expectedPayment);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Payment::class)
            ->willReturn($repositoryMock);

        // Act
        $result = $this->repository->findById($paymentId);

        // Assert
        $this->assertSame($expectedPayment, $result);
    }
}
```

## 🎯 **Points Clés de cette Implémentation**

### **1. Séparation des Responsabilités**
- **Domain** : Logique métier pure
- **Infrastructure** : Détails techniques (Doctrine)
- **Tests** : Validation du comportement

### **2. Value Objects**
- `PaymentId` : Identifiant unique
- `Amount` : Montant avec validation
- `Currency` : Devise avec validation
- `PaymentStatus` : Statut avec énumération

### **3. Interface Repository**
- Contrat clair pour la persistance
- Facilite les tests et les mocks
- Permet de changer d'implémentation

### **4. Tests Complets**
- Tests unitaires du repository
- Mocks des dépendances Doctrine
- Validation du comportement attendu

## 🚀 **Utilisation dans API Platform**

### **Configuration du Service**

```yaml
# config/services.yaml
services:
    App\Domain\Payment\Repository\PaymentRepositoryInterface:
        alias: App\Infrastructure\Payment\Repository\DoctrinePaymentRepository
```

### **API Resource**

```php
<?php

namespace App\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Domain\Payment\Entity\Payment;

#[ApiResource(
    operations: [
        new Get(),
        new Post(),
    ]
)]
class PaymentResource
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository
    ) {}

    public function get(string $id): ?Payment
    {
        return $this->paymentRepository->findById(PaymentId::fromString($id));
    }

    public function post(Payment $payment): Payment
    {
        $this->paymentRepository->save($payment);
        return $payment;
    }
}
```

## 💡 **Avantages de cette Approche**

### **✅ Simplicité**
- Implémentation directe et compréhensible
- Pas de complexité inutile
- Facile à maintenir

### **✅ Testabilité**
- Interface claire pour les mocks
- Tests unitaires simples
- Couverture de code élevée

### **✅ Performance**
- Requêtes SQL optimisées
- Pas de surcharge d'abstraction
- Cache Doctrine intégré

### **✅ Évolutivité**
- Facile d'ajouter de nouvelles méthodes
- Interface stable
- Migration vers CQRS possible

## 🔄 **Prochaines Étapes**

Une fois cette base maîtrisée, vous pouvez évoluer vers :

1. **[Stockage SQL CQS](/examples/stockage-sql-cqs/)** : Séparation des commandes et requêtes
2. **[Stockage SQL CQRS](/examples/stockage-sql-cqrs/)** : Architecture CQRS complète
3. **[Patterns de Stockage](/examples/stockage-patterns/)** : Comparaison des approches

## 🎯 **Votre Prochaine Étape**

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux voir l'implémentation CQS" 
    subtitle="Vous voulez comprendre la séparation des commandes et requêtes"
    criteria="Performance critique,Équipe expérimentée,Architecture à optimiser"
    time="30-40 minutes"
    chapter="17"
    chapter-title="Stockage SQL CQS"
    chapter-url="/examples/stockage-sql-cqs/"
  >}}
  
  {{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux voir l'implémentation CQRS" 
    subtitle="Vous voulez comprendre l'architecture CQRS complète"
    criteria="Système complexe,Équipe expérimentée,Performance critique"
    time="45-60 minutes"
    chapter="18"
    chapter-title="Stockage SQL CQRS"
    chapter-url="/examples/stockage-sql-cqrs/"
  >}}
  
  {{< chapter-option 
    letter="C" 
    color="blue" 
    title="Je veux comparer les patterns" 
    subtitle="Vous voulez comprendre les différences entre les approches"
    criteria="Choix architectural,Équipe technique,Performance à optimiser"
    time="20-30 minutes"
    chapter="0"
    chapter-title="Patterns de Stockage"
    chapter-url="/examples/stockage-patterns/"
  >}}
  
  {{< chapter-option 
    letter="D" 
    color="purple" 
    title="Je veux revenir aux exemples" 
    subtitle="Vous voulez voir la vue d'ensemble des exemples"
    criteria="Besoin de vue d'ensemble,Choix d'exemple à faire"
    time="5-10 minutes"
    chapter="0"
    chapter-title="Exemples et Implémentations"
    chapter-url="/examples/"
  >}}
{{< /chapter-nav >}}

---

*Cet exemple est tiré de l'expérience Gyroscops et adapté pour être réutilisable dans vos projets.*
