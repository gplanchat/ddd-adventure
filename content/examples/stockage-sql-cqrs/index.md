---
title: "Stockage SQL CQRS - Exemple Concret"
description: "Implémentation concrète du pattern CQRS (Command Query Responsibility Segregation) avec Doctrine"
date: 2024-12-19
draft: false
type: "docs"
weight: 4
---

## 🎯 **Contexte de l'Exemple**

Cet exemple montre l'implémentation du pattern **CQRS (Command Query Responsibility Segregation)** pour gérer des modèles de données distincts pour les commandes et les requêtes dans l'écosystème Gyroscops. C'est l'approche la plus avancée pour les systèmes complexes.

### **Domaine Métier : Gestion des Paiements avec CQRS**

- **Command Side** : Modèle d'écriture optimisé pour les validations et la cohérence
- **Query Side** : Modèle de lecture optimisé pour les performances et l'affichage
- **Synchronisation** : Mécanisme de synchronisation entre les deux côtés

## 📁 **Structure du Projet**

```
src/
├── Domain/
│   └── Payment/
│       ├── Command/
│       │   ├── Entity/
│       │   │   └── PaymentCommand.php
│       │   └── Repository/
│       │       └── PaymentCommandRepositoryInterface.php
│       ├── Query/
│       │   ├── Entity/
│       │   │   └── PaymentQuery.php
│       │   └── Repository/
│       │       └── PaymentQueryRepositoryInterface.php
│       └── Event/
│           └── PaymentEvent.php
├── Infrastructure/
│   └── Payment/
│       ├── Command/
│       │   └── Repository/
│       │       └── DoctrinePaymentCommandRepository.php
│       ├── Query/
│       │   └── Repository/
│       │       └── DoctrinePaymentQueryRepository.php
│       └── Event/
│           └── PaymentEventHandler.php
└── Tests/
    └── Infrastructure/
        └── Payment/
            ├── Command/
            │   └── Repository/
            │       └── DoctrinePaymentCommandRepositoryTest.php
            └── Query/
                └── Repository/
                    └── DoctrinePaymentQueryRepositoryTest.php
```

## 🏗️ **Implémentation**

### **1. Entité Command (Côté Écriture)**

```php
<?php

namespace App\Domain\Payment\Command\Entity;

use App\Domain\Payment\ValueObject\PaymentId;
use App\Domain\Payment\ValueObject\Amount;
use App\Domain\Payment\ValueObject\Currency;
use App\Domain\Payment\ValueObject\PaymentStatus;
use DateTimeImmutable;

class PaymentCommand
{
    public function __construct(
        private PaymentId $id,
        private Amount $amount,
        private Currency $currency,
        private PaymentStatus $status,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $paidAt = null,
        private ?string $validationErrors = null
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

    public function getValidationErrors(): ?string
    {
        return $this->validationErrors;
    }

    public function markAsPaid(): void
    {
        if ($this->status !== PaymentStatus::PENDING) {
            throw new \InvalidArgumentException('Only pending payments can be marked as paid');
        }

        $this->status = PaymentStatus::PAID;
        $this->paidAt = new DateTimeImmutable();
    }

    public function markAsFailed(string $reason): void
    {
        $this->status = PaymentStatus::FAILED;
        $this->validationErrors = $reason;
    }

    public function validate(): bool
    {
        $this->validationErrors = null;

        if ($this->amount->getValue() <= 0) {
            $this->validationErrors = 'Amount must be positive';
            return false;
        }

        if ($this->status === PaymentStatus::PENDING && $this->paidAt !== null) {
            $this->validationErrors = 'Pending payment cannot have paid date';
            return false;
        }

        return true;
    }
}
```

### **2. Entité Query (Côté Lecture)**

```php
<?php

namespace App\Domain\Payment\Query\Entity;

use App\Domain\Payment\ValueObject\PaymentId;
use App\Domain\Payment\ValueObject\Amount;
use App\Domain\Payment\ValueObject\Currency;
use App\Domain\Payment\ValueObject\PaymentStatus;
use DateTimeImmutable;

class PaymentQuery
{
    public function __construct(
        private PaymentId $id,
        private Amount $amount,
        private Currency $currency,
        private PaymentStatus $status,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $paidAt = null,
        private ?string $displayName = null,
        private ?string $statusLabel = null,
        private ?string $formattedAmount = null
    ) {
        $this->displayName = $this->generateDisplayName();
        $this->statusLabel = $this->generateStatusLabel();
        $this->formattedAmount = $this->generateFormattedAmount();
    }

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

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getStatusLabel(): string
    {
        return $this->statusLabel;
    }

    public function getFormattedAmount(): string
    {
        return $this->formattedAmount;
    }

    private function generateDisplayName(): string
    {
        return sprintf(
            'Payment %s - %s %s',
            $this->id->getValue(),
            $this->formattedAmount,
            $this->statusLabel
        );
    }

    private function generateStatusLabel(): string
    {
        return match ($this->status->getValue()) {
            'pending' => 'En attente',
            'paid' => 'Payé',
            'failed' => 'Échec',
            'cancelled' => 'Annulé',
            default => 'Inconnu'
        };
    }

    private function generateFormattedAmount(): string
    {
        return sprintf(
            '%.2f %s',
            $this->amount->getValue() / 100,
            $this->currency->getValue()
        );
    }
}
```

### **3. Interface Command Repository**

```php
<?php

namespace App\Domain\Payment\Command\Repository;

use App\Domain\Payment\Command\Entity\PaymentCommand;

interface PaymentCommandRepositoryInterface
{
    public function save(PaymentCommand $payment): void;
    public function update(PaymentCommand $payment): void;
    public function delete(PaymentCommand $payment): void;
    public function findById(string $id): ?PaymentCommand;
}
```

### **4. Interface Query Repository**

```php
<?php

namespace App\Domain\Payment\Query\Repository;

use App\Domain\Payment\Query\Entity\PaymentQuery;
use App\Domain\Payment\ValueObject\PaymentStatus;

interface PaymentQueryRepositoryInterface
{
    public function findById(string $id): ?PaymentQuery;
    public function findByStatus(PaymentStatus $status): array;
    public function findRecent(int $limit = 10): array;
    public function findPending(): array;
    public function findPaid(): array;
    public function search(string $query): array;
    public function getStatistics(): array;
}
```

### **5. Implémentation Command Repository**

```php
<?php

namespace App\Infrastructure\Payment\Command\Repository;

use App\Domain\Payment\Command\Entity\PaymentCommand;
use App\Domain\Payment\Command\Repository\PaymentCommandRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class DoctrinePaymentCommandRepository implements PaymentCommandRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function save(PaymentCommand $payment): void
    {
        $this->entityManager->persist($payment);
        $this->entityManager->flush();
    }

    public function update(PaymentCommand $payment): void
    {
        $this->entityManager->merge($payment);
        $this->entityManager->flush();
    }

    public function delete(PaymentCommand $payment): void
    {
        $this->entityManager->remove($payment);
        $this->entityManager->flush();
    }

    public function findById(string $id): ?PaymentCommand
    {
        return $this->entityManager
            ->getRepository(PaymentCommand::class)
            ->find($id);
    }
}
```

### **6. Implémentation Query Repository**

```php
<?php

namespace App\Infrastructure\Payment\Query\Repository;

use App\Domain\Payment\Query\Entity\PaymentQuery;
use App\Domain\Payment\Query\Repository\PaymentQueryRepositoryInterface;
use App\Domain\Payment\ValueObject\PaymentStatus;
use Doctrine\ORM\EntityManagerInterface;

class DoctrinePaymentQueryRepository implements PaymentQueryRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function findById(string $id): ?PaymentQuery
    {
        return $this->entityManager
            ->getRepository(PaymentQuery::class)
            ->find($id);
    }

    public function findByStatus(PaymentStatus $status): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('p')
            ->from(PaymentQuery::class, 'p')
            ->where('p.status = :status')
            ->setParameter('status', $status->getValue())
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecent(int $limit = 10): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('p')
            ->from(PaymentQuery::class, 'p')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findPending(): array
    {
        return $this->findByStatus(PaymentStatus::PENDING);
    }

    public function findPaid(): array
    {
        return $this->findByStatus(PaymentStatus::PAID);
    }

    public function search(string $query): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('p')
            ->from(PaymentQuery::class, 'p')
            ->where('p.displayName LIKE :query OR p.formattedAmount LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getStatistics(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        $stats = $qb
            ->select('p.status, COUNT(p.id) as count, SUM(p.amount) as total')
            ->from(PaymentQuery::class, 'p')
            ->groupBy('p.status')
            ->getQuery()
            ->getResult();

        return array_reduce($stats, function ($carry, $stat) {
            $carry[$stat['status']] = [
                'count' => (int) $stat['count'],
                'total' => (int) $stat['total']
            ];
            return $carry;
        }, []);
    }
}
```

### **7. Event Handler pour la Synchronisation**

```php
<?php

namespace App\Infrastructure\Payment\Event;

use App\Domain\Payment\Command\Entity\PaymentCommand;
use App\Domain\Payment\Query\Entity\PaymentQuery;
use App\Domain\Payment\Query\Repository\PaymentQueryRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class PaymentEventHandler
{
    public function __construct(
        private PaymentQueryRepositoryInterface $queryRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function onPaymentCreated(PaymentCommand $paymentCommand): void
    {
        $paymentQuery = new PaymentQuery(
            $paymentCommand->getId(),
            $paymentCommand->getAmount(),
            $paymentCommand->getCurrency(),
            $paymentCommand->getStatus(),
            $paymentCommand->getCreatedAt(),
            $paymentCommand->getPaidAt()
        );

        $this->entityManager->persist($paymentQuery);
        $this->entityManager->flush();
    }

    public function onPaymentUpdated(PaymentCommand $paymentCommand): void
    {
        $paymentQuery = $this->queryRepository->findById($paymentCommand->getId()->getValue());
        
        if ($paymentQuery) {
            // Mise à jour des champs calculés
            $paymentQuery = new PaymentQuery(
                $paymentCommand->getId(),
                $paymentCommand->getAmount(),
                $paymentCommand->getCurrency(),
                $paymentCommand->getStatus(),
                $paymentCommand->getCreatedAt(),
                $paymentCommand->getPaidAt()
            );

            $this->entityManager->merge($paymentQuery);
            $this->entityManager->flush();
        }
    }
}
```

## 🧪 **Tests**

### **Test Command Repository**

```php
<?php

namespace App\Tests\Infrastructure\Payment\Command\Repository;

use App\Domain\Payment\Command\Entity\PaymentCommand;
use App\Domain\Payment\ValueObject\Amount;
use App\Domain\Payment\ValueObject\Currency;
use App\Domain\Payment\ValueObject\PaymentId;
use App\Domain\Payment\ValueObject\PaymentStatus;
use App\Infrastructure\Payment\Command\Repository\DoctrinePaymentCommandRepository;
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

    public function testSavePaymentCommand(): void
    {
        // Arrange
        $paymentCommand = new PaymentCommand(
            PaymentId::generate(),
            new Amount(10000),
            new Currency('EUR'),
            PaymentStatus::PENDING,
            new DateTimeImmutable()
        );

        // Expect
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($paymentCommand);
        
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $this->repository->save($paymentCommand);
    }

    public function testUpdatePaymentCommand(): void
    {
        // Arrange
        $paymentCommand = new PaymentCommand(
            PaymentId::generate(),
            new Amount(10000),
            new Currency('EUR'),
            PaymentStatus::PENDING,
            new DateTimeImmutable()
        );

        // Expect
        $this->entityManager->expects($this->once())
            ->method('merge')
            ->with($paymentCommand);
        
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $this->repository->update($paymentCommand);
    }
}
```

## 🚀 **Utilisation dans API Platform**

### **Configuration des Services**

```yaml
# config/services.yaml
services:
    App\Domain\Payment\Command\Repository\PaymentCommandRepositoryInterface:
        alias: App\Infrastructure\Payment\Command\Repository\DoctrinePaymentCommandRepository
    
    App\Domain\Payment\Query\Repository\PaymentQueryRepositoryInterface:
        alias: App\Infrastructure\Payment\Query\Repository\DoctrinePaymentQueryRepository
```

### **API Resource avec CQRS**

```php
<?php

namespace App\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use App\Domain\Payment\Command\Entity\PaymentCommand;
use App\Domain\Payment\Query\Entity\PaymentQuery;
use App\Domain\Payment\Command\Repository\PaymentCommandRepositoryInterface;
use App\Domain\Payment\Query\Repository\PaymentQueryRepositoryInterface;

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

    public function get(string $id): ?PaymentQuery
    {
        return $this->queryRepository->findById($id);
    }

    public function post(PaymentCommand $paymentCommand): PaymentCommand
    {
        $this->commandRepository->save($paymentCommand);
        return $paymentCommand;
    }

    public function getCollection(): array
    {
        return $this->queryRepository->findRecent();
    }
}
```

## 🎯 **Points Clés de cette Implémentation**

### **1. Modèles Distincts**
- **Command** : Optimisé pour la validation et la cohérence
- **Query** : Optimisé pour l'affichage et les performances
- **Synchronisation** : Mécanisme de synchronisation entre les deux

### **2. Performance Maximale**
- **Requêtes spécialisées** : Chaque modèle a ses requêtes optimisées
- **Index spécialisés** : Index différents pour commandes et requêtes
- **Cache possible** : Cache indépendant sur chaque côté

### **3. Complexité Gérée**
- **Séparation claire** : Responsabilités bien définies
- **Tests spécialisés** : Tests indépendants pour chaque côté
- **Évolutivité** : Évolution indépendante des modèles

### **4. Synchronisation**
- **Event-driven** : Synchronisation basée sur les événements
- **Cohérence** : Garantie de cohérence entre les modèles
- **Performance** : Synchronisation asynchrone possible

## 💡 **Avantages du Pattern CQRS**

### **✅ Performance Exceptionnelle**
- Modèles optimisés pour chaque cas d'usage
- Requêtes spécialisées et performantes
- Cache indépendant sur chaque côté

### **✅ Scalabilité**
- Scaling indépendant des commandes et requêtes
- Optimisation spécifique par type d'opération
- Possibilité de distribution

### **✅ Flexibilité**
- Évolution indépendante des modèles
- Technologies différentes possibles
- Optimisations spécialisées

### **✅ Complexité Maîtrisée**
- Séparation claire des responsabilités
- Tests plus ciblés
- Maintenance facilitée

## ⚠️ **Inconvénients du Pattern CQRS**

### **❌ Complexité**
- Plus complexe à implémenter et maintenir
- Courbe d'apprentissage importante
- Plus de code à gérer

### **❌ Synchronisation**
- Nécessité de synchroniser les modèles
- Risque d'incohérence temporaire
- Complexité de la gestion des événements

### **❌ Overhead**
- Plus de ressources nécessaires
- Plus de code à tester
- Plus de maintenance

## 🎯 **Votre Prochaine Étape**

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux comparer les patterns" 
    subtitle="Vous voulez comprendre les différences entre les approches"
    criteria="Choix architectural,Équipe technique,Performance à optimiser"
    time="20-30 minutes"
    chapter="0"
    chapter-title="Patterns de Stockage"
    chapter-url="/examples/stockage-patterns/"
  >}}
  
  {{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux voir l'implémentation CQS" 
    subtitle="Vous voulez comprendre l'approche intermédiaire"
    criteria="Performance importante,Équipe expérimentée,Optimisation des lectures"
    time="35-45 minutes"
    chapter="17"
    chapter-title="Stockage SQL CQS"
    chapter-url="/examples/stockage-sql-cqs/"
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
