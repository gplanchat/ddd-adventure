<?php

declare(strict_types=1);

namespace Examples\Techniques\GestionDonneesValidation;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Psr\Log\LoggerInterface;

/**
 * Service de validation des données selon les bonnes pratiques du projet Gyroscops Cloud
 * 
 * Ce service implémente une validation robuste des données d'entrée
 * en respectant les ADR HIVE036, HIVE038 et HIVE027.
 */
final class ValidationService
{
    public function __construct(
        private ValidatorInterface $validator,
        private LoggerInterface $logger
    ) {}

    /**
     * Valide une commande de création de paiement
     */
    public function validateCreatePaymentCommand(array $data): ValidationResult
    {
        $this->logger->info('Validating create payment command', [
            'data_keys' => array_keys($data)
        ]);

        $constraints = [
            'uuid' => [
                new Assert\Type('string'),
                new Assert\Uuid()
            ],
            'realm_id' => [
                new Assert\Type('string'),
                new Assert\Uuid()
            ],
            'organization_id' => [
                new Assert\Type('string'),
                new Assert\Uuid()
            ],
            'subscription_id' => [
                new Assert\Type('string'),
                new Assert\Uuid()
            ],
            'creation_date' => [
                new Assert\Type('string'),
                new Assert\DateTime(['format' => 'Y-m-d H:i:s'])
            ],
            'expiration_date' => [
                new Assert\Type('string'),
                new Assert\DateTime(['format' => 'Y-m-d H:i:s'])
            ],
            'customer_name' => [
                new Assert\Type('string'),
                new Assert\NotBlank(),
                new Assert\Length(['max' => 255])
            ],
            'customer_email' => [
                new Assert\Type('string'),
                new Assert\NotBlank(),
                new Assert\Email()
            ],
            'status' => [
                new Assert\Type('string'),
                new Assert\Choice(['choices' => ['pending', 'completed', 'failed', 'cancelled']])
            ],
            'subtotal' => [
                new Assert\Type('string'),
                new Assert\Regex(['pattern' => '/^\d+\.\d{2}$/'])
            ],
            'subtotal_currency' => [
                new Assert\Type('string'),
                new Assert\Choice(['choices' => ['EUR', 'USD', 'GBP']])
            ],
            'discount' => [
                new Assert\Type('string'),
                new Assert\Regex(['pattern' => '/^\d+\.\d{2}$/'])
            ],
            'discount_currency' => [
                new Assert\Type('string'),
                new Assert\Choice(['choices' => ['EUR', 'USD', 'GBP']])
            ],
            'taxes' => [
                new Assert\Type('string'),
                new Assert\Regex(['pattern' => '/^\d+\.\d{2}$/'])
            ],
            'taxes_currency' => [
                new Assert\Type('string'),
                new Assert\Choice(['choices' => ['EUR', 'USD', 'GBP']])
            ],
            'total' => [
                new Assert\Type('string'),
                new Assert\Regex(['pattern' => '/^\d+\.\d{2}$/'])
            ],
            'total_currency' => [
                new Assert\Type('string'),
                new Assert\Choice(['choices' => ['EUR', 'USD', 'GBP']])
            ]
        ];

        $violations = $this->validator->validate($data, $constraints);

        if (count($violations) > 0) {
            $this->logger->warning('Validation failed', [
                'violations_count' => count($violations),
                'violations' => array_map(fn($v) => [
                    'property' => $v->getPropertyPath(),
                    'message' => $v->getMessage(),
                    'invalid_value' => $v->getInvalidValue()
                ], iterator_to_array($violations))
            ]);
        }

        return new ValidationResult(count($violations) === 0, iterator_to_array($violations));
    }

    /**
     * Valide une commande de création d'abonnement
     */
    public function validateCreateSubscriptionCommand(array $data): ValidationResult
    {
        $this->logger->info('Validating create subscription command', [
            'data_keys' => array_keys($data)
        ]);

        $constraints = [
            'uuid' => [
                new Assert\Type('string'),
                new Assert\Uuid()
            ],
            'realm_id' => [
                new Assert\Type('string'),
                new Assert\Uuid()
            ],
            'organization_id' => [
                new Assert\Type('string'),
                new Assert\Uuid()
            ],
            'name' => [
                new Assert\Type('string'),
                new Assert\NotBlank(),
                new Assert\Length(['max' => 255])
            ],
            'description' => [
                new Assert\Type('string'),
                new Assert\Length(['max' => 1000])
            ],
            'status' => [
                new Assert\Type('string'),
                new Assert\Choice(['choices' => ['active', 'inactive', 'suspended']])
            ],
            'start_date' => [
                new Assert\Type('string'),
                new Assert\DateTime(['format' => 'Y-m-d H:i:s'])
            ],
            'end_date' => [
                new Assert\Type('string'),
                new Assert\DateTime(['format' => 'Y-m-d H:i:s'])
            ]
        ];

        $violations = $this->validator->validate($data, $constraints);

        if (count($violations) > 0) {
            $this->logger->warning('Validation failed', [
                'violations_count' => count($violations),
                'violations' => array_map(fn($v) => [
                    'property' => $v->getPropertyPath(),
                    'message' => $v->getMessage(),
                    'invalid_value' => $v->getInvalidValue()
                ], iterator_to_array($violations))
            ]);
        }

        return new ValidationResult(count($violations) === 0, iterator_to_array($violations));
    }

    /**
     * Valide une commande de création d'organisation
     */
    public function validateCreateOrganizationCommand(array $data): ValidationResult
    {
        $this->logger->info('Validating create organization command', [
            'data_keys' => array_keys($data)
        ]);

        $constraints = [
            'uuid' => [
                new Assert\Type('string'),
                new Assert\Uuid()
            ],
            'realm_id' => [
                new Assert\Type('string'),
                new Assert\Uuid()
            ],
            'name' => [
                new Assert\Type('string'),
                new Assert\NotBlank(),
                new Assert\Length(['max' => 255])
            ],
            'description' => [
                new Assert\Type('string'),
                new Assert\Length(['max' => 1000])
            ],
            'status' => [
                new Assert\Type('string'),
                new Assert\Choice(['choices' => ['active', 'inactive', 'suspended']])
            ]
        ];

        $violations = $this->validator->validate($data, $constraints);

        if (count($violations) > 0) {
            $this->logger->warning('Validation failed', [
                'violations_count' => count($violations),
                'violations' => array_map(fn($v) => [
                    'property' => $v->getPropertyPath(),
                    'message' => $v->getMessage(),
                    'invalid_value' => $v->getInvalidValue()
                ], iterator_to_array($violations))
            ]);
        }

        return new ValidationResult(count($violations) === 0, iterator_to_array($violations));
    }
}

/**
 * Résultat de validation
 */
final class ValidationResult
{
    public function __construct(
        public readonly bool $isValid,
        public readonly array $violations = []
    ) {}

    public function hasViolations(): bool
    {
        return !empty($this->violations);
    }

    public function getViolationMessages(): array
    {
        return array_map(fn($violation) => $violation->getMessage(), $this->violations);
    }

    public function getViolationMessagesByProperty(): array
    {
        $messages = [];
        foreach ($this->violations as $violation) {
            $property = $violation->getPropertyPath();
            if (!isset($messages[$property])) {
                $messages[$property] = [];
            }
            $messages[$property][] = $violation->getMessage();
        }
        return $messages;
    }
}
