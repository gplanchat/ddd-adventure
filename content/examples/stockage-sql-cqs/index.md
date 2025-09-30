---
title: "Stockage SQL CQS - Exemple Concret"
description: "Implémentation concrète du pattern CQS (Command Query Separation) avec Doctrine"
date: 2024-12-19
draft: false
type: "docs"
weight: 3
---

## 🎯 **Contexte de l'Exemple**

Cet exemple montre l'implémentation du pattern **CQS (Command Query Separation)** pour optimiser les performances des lectures dans l'écosystème Gyroscops. Il sépare clairement les opérations d'écriture (Command) des opérations de lecture (Query).

### **Domaine Métier : Gestion des Paiements avec CQS**

- **Command Repository** : Gestion des écritures (création, modification, suppression)
- **Query Repository** : Gestion des lectures (recherche, filtrage, pagination)
- **Optimisation** : Requêtes spécialisées pour chaque cas d'usage

## 📁 **Structure du Projet**

```
src/
├── Domain/
│   └── Payment/
│       ├── Entity/
│       │   └── Payment.php
│       └── Repository/
│           ├── PaymentCommandRepositoryInterface.php
│           └── PaymentQueryRepositoryInterface.php
├── Infrastructure/
│   └── Payment/
│       └── Repository/
│           ├── DoctrinePaymentCommandRepository.php
│           └── DoctrinePaymentQueryRepository.php
└── Tests/
    └── Infrastructure/
        └── Payment/
            └── Repository/
                ├── DoctrinePaymentCommandRepositoryTest.php
                └── DoctrinePaymentQueryRepositoryTest.php
```

## 🏗️ **Implémentation**

### **1. Interface Command Repository**

```php
<?php

namespace App\Domain\Payment\Repository;

use App\Domain\Payment\Entity\Payment;

interface PaymentCommandRepositoryInterface
{
    public function save(Payment $payment): void;
    public function update(Payment $payment): void;
    public function delete(Payment $payment): void;
    public function persist(Payment $payment): void;
    public function flush(): void;
}
```

### **2. Interface Query Repository**

```php
<?php

namespace App\Domain\Payment\Repository;

use App\Domain\Payment\Entity\Payment;
use App\Domain\Payment\ValueObject\PaymentId;
use App\Domain\Payment\ValueObject\PaymentStatus;
use App\Domain\Payment\ValueObject\Amount;

interface PaymentQueryRepositoryInterface
{
    public function findById(PaymentId $id): ?Payment;
    public function findByStatus(PaymentStatus $status): array;
    public function findByAmountRange(Amount $min, Amount $max): array;
    public function findByDateRange(\DateTimeImmutable $start, \DateTimeImmutable $end): array;
    public function findPendingPayments(): array;
    public function findPaidPayments(): array;
    public function countByStatus(PaymentStatus $status): int;
    public function getTotalAmountByStatus(PaymentStatus $status): Amount;
}
```

### **3. Implémentation Command Repository**

```php
<?php

namespace App\Infrastructure\Payment\Repository;

use App\Domain\Payment\Entity\Payment;
use App\Domain\Payment\Repository\PaymentCommandRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class DoctrinePaymentCommandRepository implements PaymentCommandRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function save(Payment $payment): void
    {
        $this->entityManager->persist($payment);
        $this->entityManager->flush();
    }

    public function update(Payment $payment): void
    {
        $this->entityManager->merge($payment);
        $this->entityManager->flush();
    }

    public function delete(Payment $payment): void
    {
        $this->entityManager->remove($payment);
        $this->entityManager->flush();
    }

    public function persist(Payment $payment): void
    {
        $this->entityManager->persist($payment);
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }
}
```

### **4. Implémentation Query Repository**

```php
<?php

namespace App\Infrastructure\Payment\Repository;

use App\Domain\Payment\Entity\Payment;
use App\Domain\Payment\Repository\PaymentQueryRepositoryInterface;
use App\Domain\Payment\ValueObject\PaymentId;
use App\Domain\Payment\ValueObject\PaymentStatus;
use App\Domain\Payment\ValueObject\Amount;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class DoctrinePaymentQueryRepository implements PaymentQueryRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function findById(PaymentId $id): ?Payment
    {
        return $this->entityManager
            ->getRepository(Payment::class)
            ->find($id->getValue());
    }

    public function findByStatus(PaymentStatus $status): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('p')
            ->from(Payment::class, 'p')
            ->where('p.status = :status')
            ->setParameter('status', $status->getValue())
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByAmountRange(Amount $min, Amount $max): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('p')
            ->from(Payment::class, 'p')
            ->where('p.amount >= :min AND p.amount <= :max')
            ->setParameter('min', $min->getValue())
            ->setParameter('max', $max->getValue())
            ->orderBy('p.amount', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('p')
            ->from(Payment::class, 'p')
            ->where('p.createdAt >= :start AND p.createdAt <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingPayments(): array
    {
        return $this->findByStatus(PaymentStatus::PENDING);
    }

    public function findPaidPayments(): array
    {
        return $this->findByStatus(PaymentStatus::PAID);
    }

    public function countByStatus(PaymentStatus $status): int
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(Payment::class, 'p')
            ->where('p.status = :status')
            ->setParameter('status', $status->getValue())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalAmountByStatus(PaymentStatus $status): Amount
    {
        $result = $this->entityManager
            ->createQueryBuilder()
            ->select('SUM(p.amount)')
            ->from(Payment::class, 'p')
            ->where('p.status = :status')
            ->setParameter('status', $status->getValue())
            ->getQuery()
            ->getSingleScalarResult();

        return new Amount($result ?? 0);
    }
}
```

## 🧪 **Tests**

### **Test Command Repository**

```php
<?php

namespace App\Tests\Infrastructure\Payment\Repository;

use App\Domain\Payment\Entity\Payment;
use App\Domain\Payment\ValueObject\Amount;
use App\Domain\Payment\ValueObject\Currency;
use App\Domain\Payment\ValueObject\PaymentId;
use App\Domain\Payment\ValueObject\PaymentStatus;
use App\Infrastructure\Payment\Repository\DoctrinePaymentCommandRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DoctrinePaymentCommandRepositoryTest extends TestCase
{
    private DoctrinePaymentCommandRepository $repository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = new DoctrinePaymentCommandRepository($this->entityManager);
    }

    public function testSavePayment(): void
    {
        // Arrange
        $payment = new Payment(
            PaymentId::generate(),
            new Amount(10000),
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

    public function testUpdatePayment(): void
    {
        // Arrange
        $payment = new Payment(
            PaymentId::generate(),
            new Amount(10000),
            new Currency('EUR'),
            PaymentStatus::PENDING,
            new DateTimeImmutable()
        );

        // Expect
        $this->entityManager->expects($this->once())
            ->method('merge')
            ->with($payment);
        
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $this->repository->update($payment);
    }
}
```

### **Test Query Repository**

```php
<?php

namespace App\Tests\Infrastructure\Payment\Repository;

use App\Domain\Payment\Entity\Payment;
use App\Domain\Payment\ValueObject\Amount;
use App\Domain\Payment\ValueObject\Currency;
use App\Domain\Payment\ValueObject\PaymentId;
use App\Domain\Payment\ValueObject\PaymentStatus;
use App\Infrastructure\Payment\Repository\DoctrinePaymentQueryRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class DoctrinePaymentQueryRepositoryTest extends TestCase
{
    private DoctrinePaymentQueryRepository $repository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = new DoctrinePaymentQueryRepository($this->entityManager);
    }

    public function testFindByStatus(): void
    {
        // Arrange
        $status = PaymentStatus::PENDING;
        $expectedPayments = [
            new Payment(
                PaymentId::generate(),
                new Amount(10000),
                new Currency('EUR'),
                $status,
                new DateTimeImmutable()
            )
        ];

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('p')
            ->willReturnSelf();
        
        $queryBuilder->expects($this->once())
            ->method('from')
            ->with(Payment::class, 'p')
            ->willReturnSelf();
        
        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('p.status = :status')
            ->willReturnSelf();
        
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('status', $status->getValue())
            ->willReturnSelf();
        
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('p.createdAt', 'DESC')
            ->willReturnSelf();

        $query = $this->createMock(\Doctrine\ORM\AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedPayments);

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        // Act
        $result = $this->repository->findByStatus($status);

        // Assert
        $this->assertSame($expectedPayments, $result);
    }

    public function testCountByStatus(): void
    {
        // Arrange
        $status = PaymentStatus::PENDING;
        $expectedCount = 5;

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('COUNT(p.id)')
            ->willReturnSelf();
        
        $queryBuilder->expects($this->once())
            ->method('from')
            ->with(Payment::class, 'p')
            ->willReturnSelf();
        
        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('p.status = :status')
            ->willReturnSelf();
        
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('status', $status->getValue())
            ->willReturnSelf();

        $query = $this->createMock(\Doctrine\ORM\AbstractQuery::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn($expectedCount);

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        // Act
        $result = $this->repository->countByStatus($status);

        // Assert
        $this->assertSame($expectedCount, $result);
    }
}
```

## 🚀 **Utilisation dans API Platform**

### **Configuration des Services**

```yaml
# config/services.yaml
services:
    App\Domain\Payment\Repository\PaymentCommandRepositoryInterface:
        alias: App\Infrastructure\Payment\Repository\DoctrinePaymentCommandRepository
    
    App\Domain\Payment\Repository\PaymentQueryRepositoryInterface:
        alias: App\Infrastructure\Payment\Repository\DoctrinePaymentQueryRepository
```

### **API Resource avec CQS**

```php
<?php

namespace App\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use App\Domain\Payment\Entity\Payment;
use App\Domain\Payment\Repository\PaymentCommandRepositoryInterface;
use App\Domain\Payment\Repository\PaymentQueryRepositoryInterface;

#[ApiResource(
    operations: [
        new Get(),
        new Post(),
        new GetCollection(),
    ]
)]
class PaymentResource
{
    public function __construct(
        private PaymentCommandRepositoryInterface $commandRepository,
        private PaymentQueryRepositoryInterface $queryRepository
    ) {}

    public function get(string $id): ?Payment
    {
        return $this->queryRepository->findById(PaymentId::fromString($id));
    }

    public function post(Payment $payment): Payment
    {
        $this->commandRepository->save($payment);
        return $payment;
    }

    public function getCollection(): array
    {
        return $this->queryRepository->findPendingPayments();
    }
}
```

## 🎯 **Points Clés de cette Implémentation**

### **1. Séparation Claire des Responsabilités**
- **Command** : Gestion des écritures et modifications
- **Query** : Gestion des lectures et recherches
- **Optimisation** : Requêtes spécialisées pour chaque cas

### **2. Performance Optimisée**
- **Requêtes ciblées** : Chaque méthode a sa requête optimisée
- **Index appropriés** : Sur les champs les plus utilisés
- **Cache possible** : Les queries peuvent être mises en cache

### **3. Testabilité Améliorée**
- **Tests séparés** : Command et Query testés indépendamment
- **Mocks spécialisés** : Chaque interface peut être mockée
- **Couverture** : Tests plus ciblés et efficaces

### **4. Évolutivité**
- **Ajout facile** : Nouvelles méthodes de recherche
- **Optimisation** : Amélioration des requêtes sans impact
- **Migration** : Passage vers CQRS possible

## 💡 **Avantages du Pattern CQS**

### **✅ Performance**
- Requêtes optimisées pour chaque cas d'usage
- Possibilité de cache sur les queries
- Index spécialisés par type de requête

### **✅ Maintenabilité**
- Code plus lisible et organisé
- Responsabilités clairement séparées
- Tests plus faciles à écrire

### **✅ Évolutivité**
- Ajout de nouvelles queries sans impact
- Optimisation indépendante des commandes
- Migration vers CQRS facilitée

### **✅ Testabilité**
- Tests unitaires plus ciblés
- Mocks plus simples
- Couverture de code améliorée

## 🔄 **Évolution vers CQRS**

Le pattern CQS est une étape naturelle vers CQRS :

1. **CQS** : Séparation des interfaces
2. **CQRS** : Séparation des modèles de données
3. **Event Sourcing** : Historique complet des événements

## 🎯 **Votre Prochaine Étape**

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux voir l'implémentation CQRS" 
    subtitle="Vous voulez comprendre l'architecture CQRS complète"
    criteria="Système complexe,Équipe expérimentée,Performance critique"
    time="45-60 minutes"
    chapter="18"
    chapter-title="Stockage SQL CQRS"
    chapter-url="/examples/stockage-sql-cqrs/"
  >}}
  
  {{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux comparer les patterns" 
    subtitle="Vous voulez comprendre les différences entre les approches"
    criteria="Choix architectural,Équipe technique,Performance à optimiser"
    time="20-30 minutes"
    chapter="0"
    chapter-title="Patterns de Stockage"
    chapter-url="/examples/stockage-patterns/"
  >}}
  
  {{< chapter-option 
    letter="C" 
    color="blue" 
    title="Je veux revenir aux exemples" 
    subtitle="Vous voulez voir la vue d'ensemble des exemples"
    criteria="Besoin de vue d'ensemble,Choix d'exemple à faire"
    time="5-10 minutes"
    chapter="0"
    chapter-title="Exemples et Implémentations"
    chapter-url="/examples/"
  >}}
  
  {{< chapter-option 
    letter="D" 
    color="purple" 
    title="Je veux voir l'implémentation classique" 
    subtitle="Vous voulez comprendre l'approche la plus simple"
    criteria="Débutant,Application simple,Équipe junior"
    time="30-40 minutes"
    chapter="16"
    chapter-title="Stockage SQL Classique"
    chapter-url="/examples/stockage-sql-classique/"
  >}}
{{< /chapter-nav >}}

---

*Cet exemple est tiré de l'expérience Gyroscops et adapté pour être réutilisable dans vos projets.*
